<?php
/**
 * @file http/ontologies/sparql.php
 * @brief Web interface for the SPARQL endpoint to the 4Store ontologies knowledge base.
 * @version beta
 * @author David R Newman
 * @details Web interface for the SPARQL endpoint to the 4Store ontologies knowledge base.  Allows queries on the ontologies uploaded into the specification document generator to be made.
 */

/** @brief The page title to be displayed in an h1 tag and the title of the html header. */
$pagetitle="<small>Ontologies SPARQL Endpoint</small>";
/** @brief An array for adding additional lines the the head entity of the HTML page, (e.g. CSS and Javascript files). */
$htmlheader[]='<link rel="stylesheet" type="text/css" href="/css/style.css"/>';
$htmlheader[]='<script src="/js/sparql.js" type="text/javascript"></script>';
$htmlheader[]='<script src="/js/codemirror.js" type="text/javascript"></script>';
include_once('../include.inc.php');
include_once('connect/ontologies.inc.php');
require_once('functions/rdf.inc.php');
require_once('functions/4store.inc.php');
/** @brief A string containing the number of triples in the myExperiment knowledge base (or the string UNKNOWN if this is unknown). */
$notriples=getNoTriples($onto_kb);
if (empty($notriples) && $notriples !== "0") {
	$notriples = "UNKNOWN";
}
/** @brief A string containing the datetime when the database dump used to generate data in the myExperiment knowledge base was taken. */
$lmdate=getLastUpdated($onto_kb);
/** @brief An integer containing the percentage of resources the SPARQL endpoint can use to find results to a query.  Based on 4Store's soft limit parameter. */
$softlimit=1;
/** @brief An integer.  1 if reasoning should be performed on a query, 0 otherwise. */
$reasoning=0;
/** @brief The maximum value to which a user can set the softlimit endpoint form parameter.  As for this endpoint form the softlimit parameter represents a percentage, the maximum must be 100. */
$maxsoftlimit=100;
/** @brief A string representing how the results returned from 4Store should be formatted. */
$formatting="XML";
/** @brief A string representing the MIME type to be used in the HTTP response to the SPARQL query submitted.  So that the user client waiting for the SPARQL query results knowshow to handle the data sent back. */
$mimetype="application/xml";
/** @brief A string representing the format in which results returned from 4Store shoud be returned. */
$format="sparql";
/** @brief A multi-dimensional associative array mapping the format requested from 4Store to the MIME Type that can be encoded from for this format. (E.g. application/json MIME type can be encoded from a 4Store format request of json). */
$formats=array("HTML Table"=>array("sparql",array("text/html","application/xhtml+xml")), "XML"=>array("sparql",array("application/xml","applications/sparql-results+xml")),"Text"=>array("text",array("text/plain")),"JSON"=>array("json",array("application/json")),"CSV"=>array("sparql",array("text/csv","application/csv")),"CSV Matrix"=>array("sparql",array("text/csv","application/csv")));
if (isset($_POST['formatting'])) $formatting=$_POST['formatting'];
elseif (isset($_GET['formatting']) && strlen($_GET['formatting'])>0) $formatting=$_GET['formatting'];
else{
	$mtfound=0;
	$fc_mimetype=getFirstChoiceMIMEType($_SERVER['HTTP_ACCEPT']);
	foreach ($formats as $fname => $aformat){
		foreach ($aformat[1] as $amimetype){
			if ($fc_mimetype==$amimetype){
				$formatting=$fname;
				$mtfound=1;
				break;
			}
		}
		if ($mtfound) break;
	}
}
if ($formatting!="XML"){
	$format=$formats[$formatting][0];
	$mimetype=$formats[$formatting][1][0];
}
$clientlive=true;
if (!empty($_POST['generate_service'])){
	if (!empty($_POST['query'])){
		$pagetitle="<small>Ontologies SPARQL Query Service</small>";
	        include('partials/header.inc.php');
		if (!empty($formatting) && $formatting!="HTML Table") $formatparam="&formatting=".$formatting;
		if ($_POST['softlimit']>1) $softlimitparam="&amp;softlimit=$_POST[softlimit]";
		if (!empty($_POST['reasoning'])) $reasoningparam="&amp;reasoning=$_POST[reasoning]";
		$query=urlencode(preProcessQuery($_POST['query']));
		$service_url="http://".$_SERVER['SERVER_NAME']."/sparql?query=$query$formatparam$softlimitparam$reasoningparam";
		echo "<p>Below is a URL which you can use give to any application capable of making HTTP requests and it will return you the current results for the query you made.</p>";
		if ($formatting=="HTML Table"){
			echo "<div class=\"red\"><b>WARNING:</b> This service require the HTTP request to explictly specify its accept type in the request header.  if this is not set appropriately the format returned will most likely be HTML with an embedded table of results. To select a particular format, click back and select it from the list provided before clicking &quot;Generate Service for Query&quot; again.</div><br/>\n";
		}
		echo "<p style=\"margin: 0 30px\"><a href=\"$service_url\">$service_url</a></p>";
		include('partials/footer.inc.php');
	       exit(1);
	}
	else{
		$err="No query was submitted";
	}
}
if (!$clientlive) $err="This Ontologies SPARQL Endpoint is currently unavailable";
else{
	if (!empty($_POST)){
		error_log("POST");
		$query = $_POST['query'];
		if (is_int(intval($_POST['softlimit'])) && intval($_POST['softlimit'])>0 && intval($_POST['softlimit'])<=$maxsoftlimit) $softlimit=intval($_POST['softlimit']);	
		if (isset($_POST['reasoning']) && strlen($_POST['reasoning'])>0) $reasoning=1;
	}
	elseif (!empty($_GET)){	
		error_log("GET");	
		$query = rawurldecode($_GET['query']);
		if (is_int(intval($_GET['softlimit'])) && intval($_GET['softlimit'])>0 && intval($_GET['softlimit'])<=$maxsoftlimit) $softlimit=intval($_GET['softlimit']);
		if (isset($_GET['reasoning']) && strlen($_GET['reasoning'])>0) $reasoning=1;
	}
	if (!empty($query)) {	
		error_log("QUERY FOUND");
		$query=preProcessQuery($query);
		$results=callSPARQLQueryClient($onto_kb,$query,$format,$softlimit*10000,$reasoning);
		$err=implode('<br/>',$errs);
	}
}
$done=FALSE;
if ($formatting!="HTML Table"){
	$done=TRUE;
	if ($formatting=="CSV") $results=convertTableToCSV(tabulateSPARQLResults(parseXML($results)));
	elseif ($formatting=="CSV Matrix"){
		$csvmatrix=convertTableToCSVMatrix(tabulateSPARQLResults(parseXML($results)));
		if ($err){
                	$results=null;
			$done=0;
	        }
        	elseif (substr($csvmatrix,0,5)=="ERROR"){
                	$err=$csvmatrix;
	                $results=null;
			$done=0;
	        }
		else{
	                $results=$csvmatrix;
	        }
	}
	if ($done){
		header("Content-type: $mimetype");
		echo $results;
	}
}
if($clientlive && !$done){
	include('partials/header.inc.php');
?>
    <div align="center"> 
    <div class="purple">
      <div style="float: left; width: 10%;">&nbsp;</div>
      <h3 style="float: left; width: 80%; text-align: center; margin: 0;">Useful Prefixes</h3>
      <div id="prefixes_show" onclick="showPrefixes();" style="float: right; text-align: right; width: 10%; display: none;">[<span class="link" style="font-size: 1em;">Show</span>]</div><div id="prefixes_hide" onclick="hidePrefixes();" style="float: right; text-align: right; width: 10%;">[<span class="link" style="font-size: 1em;">Hide</span>]</div>

      <div style="clear: both;"></div>
      <div id="prefixes" style="font-size: 0.8em;"><?=getUsefulPrefixes($onto_kb)?></div>
    </div>
    <br/>
    <div class="yellow">
      <h3 style="text-align: center; margin: 0; margin-bottom: 10px;">Querying</h3>
      <p style="text-align: right; position: relative; top: -38px;"><small><a href="/howtosparql" title="How to SPARQL in myExperiment Guide">Need help querying myExperiment RDF?<br/>New to SPARQL?</a></small></p>
      <div class="green" style="position: relative; top: -28px; font-size: 0.9em;">This SPARQL Endpoint nows supports query time RDFS reasoning using <a href="http://4sreasoner.ecs.soton.ac.uk/">4Store Reasoner</a>.  Click <a href="howtosparql?page=Using the SPARQL Endpoint#Reasoning">here</a> for more details.</div>
    <form name="queryform" method="post" action="">
       <table style="font-size: 10pt;">
          <tr>
            <th style="text-align: right;">Version Info:</th>
            <td style="text-align: left;"><?= get4StoreVersions() ?></td>
          </tr>
          <tr>
            <th style="text-align: right;">No. of Triples:</th>
            <td style="text-align: left;"><?= $notriples ?></td>
          </tr>
          <tr>
            <th style="text-align: right;">Last Snapshot Taken At:</th>
            <td style="text-align: left;"><?= $lmdate ?></td>
          </tr>
          <tr>
            <th style="text-align: right;">Format:</th>
            <td style="text-align: left;">
              <select name="formatting">
          <?php
	$formattings=array_keys($formats);
        for ($f=0; $f<sizeof($formattings); $f++){
                echo "            <option ";
                if ($formattings[$f]==$formatting) echo 'selected="selected" ';
                echo "value=\"".$formattings[$f]."\">".$formattings[$f]."</option>\n";
        }
?>
              </select>
           </td>
        </tr>
        <tr>
          <th style="text-align: right;">Soft Limit:</th>
          <td style="text-align: left;"><input type="text" size="3" maxlength="3" name="softlimit" value="<?=$softlimit?>" />%</td>
        </tr>
        <tr>
          <th style="text-align: right;">Enable RDFS Reasoning:</th>
          <td style="text-align: left;"><input type="checkbox" <?php if ($reasoning) echo "checked=\"checked\""; ?> name="reasoning" value="1"/></td>
        </tr>
      </table>
      <?php if (!empty($err)) printError($err); ?>
      <?php if (!empty($msg)) printMessage($msg); ?>
      
      <p>
        <textarea name="query" id="querybox" cols="110" rows="12" style="width: 800px;"><?php if (!empty($query)) { htmlentities($query); } ?></textarea>
      </p>
      <script type="text/javascript">
var editor = CodeMirror.fromTextArea('querybox', {
    parserfile: ["parsesparql.js"],
    path: "/js/",
    stylesheet: "/css/sparqlcolors.css"
});
      </script>

      <p>
        <input type="submit" name="submit_query" value ="Submit Query"/>
        &nbsp;&nbsp;
        <input type="submit" name="generate_service" value ="Generate Service for Query"/>
      </p>
    </form>
 </div>
</div>

  <br/>
    <?php 
	if (!empty($results)){
		echo "<div class=\"results\">\n";
		echo "<h3>Results</h3>";
		if ($timetaken==0) $timetaken="<1";
		echo "<p><b>Time Taken:</b> $timetaken seconds</p>\n";
		if ($formatting=="HTML Table"){
			$parsedxml=parseXML($results);
			$tabres=tabulateSPARQLResults($parsedxml);
			$formattedoutput=drawSPARQLResultsTable($tabres);
			$nores=sizeof($tabres)-1;
			echo "<p><b>No. of Results:</b> $nores</p>\n";
			echo $formattedoutput;
		}
		echo "</div>\n<br/><br/>\n";
	}
    ?>
    <script type= "text/javascript"><!-- 
      hidePrefixes();
    --></script>
<?php 
	include('partials/footer.inc.php');
}
elseif (!$done){ 
	include('partials/header.inc.php');
        printError($err);
	include('partials/footer.inc.php');
} 
?> 
