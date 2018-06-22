<?php
/**
 * @file http/generic_spec.php
 * @brief Generates and displays the HTML specification of any RDFS schema / OWL ontology.
 * @version beta
 * @author David R Newman
 * @details Generates HTML specification of any RDFS schema / OWL ontology using SPARQL queries to the 4Store SPARQL endpoint.
 */

include_once('include.inc.php');
include_once('connect/ontologies.inc.php');
/** @brief A string containg the SQL query to retrieve the details about all registered ontologies in the HTML Document Specification generator system. */
$onto_query="select * from ontologies order by name, namespace";
/** @brief The page title to be displayed in an h1 tag and the title of the html header. */
$pagetitle="Ontology Specifications";
/** @brief An array listing all the ontologies imported by the current ontology specified in the HTML Specification Document generator system.  */
$ontimports=array();
require_once('functions/4store.inc.php');
require_once('functions/utility.inc.php');
/** @brief A string specifying the ID of the current ontology specified in the HTML Specification Document generator system. */
$ontology="";
if (!empty($_GET['ontology'])) $ontology=$_GET['ontology'];
else {
	if(!empty($_POST['ontology'])) {
		$ontology=$_POST['ontology'];
	}
	include('partials/header.inc.php');
}
/** @brief The result from the $onto_query SQL query to retrieve details about all registered ontologies in the HTML Document Specification generator system. */
$onto_res=mysqli_query($GLOBALS['con'], $onto_query);
for ($i=0; $i<mysqli_num_rows($onto_res); $i++){
	$ontologies[]=mysqli_fetch_assoc($onto_res);
        if ($ontology==$ontologies[$i]['id']){
        	$headername=$ontologies[$i]['name'];
                $url=$ontologies[$i]['url'];
                $headerimg=$ontologies[$i]['image'];
		$filteront=$ontologies[$i]['namespace'];
		$queryset=$ontologies[$i]['ontology_type'];
		$timeout=$ontologies[$i]['timeout'];
		$remoteont="file://".$datapath."ontologies/remoteont/".$ontology."_".$headername.".owl";
        }
}
if (empty($_GET['ontology'])){
?>
<form name="ontologyselect" method="post" action="">
<p style="text-align: center; font-weight: bold;">Ontology:&nbsp;&nbsp;
  <select name="ontology">
    <option value="">Select...</option>
<?php
	foreach ($ontologies as $curont){
		echo "<option ";
		if ($curont['id']==$ontology) echo 'selected="selected" ';
		echo 'value="'.$curont['id'].'">'.$curont['name'].' ('.$curont['namespace'].')</option>'."\n";
	}
?>
  </select>
  &nbsp;&nbsp;
  <input type="submit" name="view" value="View"/>
</p>
</form>
<div class="hr"></div>
<?php
}
if (empty($_GET['uncached']) && !empty($headername)){
	if (!empty($_GET['ontology'])) $print=1;
	$spec_doc_path = $datapath.'ontologies/cachedspec/';
	$spec_doc_name = $ontology.'_'.$headername.'_spec.html';
	if (file_exists($spec_doc_path.$spec_doc_name)) {
		$lines=file($spec_doc_path.$spec_doc_name);
	}
	if (!empty($lines) && is_array($lines)) {
		foreach ($lines as $line){
			if (preg_match('!</body>!',$line)) $print=0;
			if ($print) echo $line;
			if (preg_match('/<body>/',$line)) $print=1;
		}
	}
	else printError("Specification document '{$spec_doc_name}' could not be found.");
	if (empty($_GET['ontology'])) include('partials/footer.inc.php');
}
else{
if ($ontology){
	require_once('functions/property.inc.php');
	require_once('functions/4store.inc.php');
	if ($queryset=="OWL Ontology") include_once('specifications/owl_queries.inc.php');
	elseif ($queryset=="RDFS Schema") include_once('specifications/rdfs_queries.inc.php');
	else include_once('specifications/queries.inc.php');
	$res=callSPARQLQueryClientMultiple($onto_kb,$queries,10000,$timeout,true);
	//Retrieve Class Property Relations
	$tableres1=array();
	if (isset($res[1])){
		if (queryFailed($res[1])){
			$errs[]="Property Domain Class-Property Relations Query Failed";
		}
		else $tableres1=tabulateSPARQLResultsAssoc(parseXML($res[1]));
	}
	$tableres2=array();
	if (isset($res[2])){
		if (queryFailed($res[2])){
			$errs[]="Class Property Restictions Class-Property Relations Query Failed";
		}
		else $tableres2=tabulateSPARQLResultsAssoc(parseXML($res[2]));
	}
	$classprops=multidimensionalArrayUnique(array_merge($tableres1,$tableres2));
	
	//Retrieve Class Labels and Comments
	$classes=array();
	if (isset($res[3])){
		if (queryFailed($res[3])){
                	$errs[]="Label and Comment for Classes Query Failed";
        	}
		else{
			$classes=tabulateSPARQLResultsAssoc(parseXML($res[3]));
			$classes=setKey($classes,'class');
		}
	}


	//Retrieve Superclasses of Classes
	$tableres4=array();
	if (isset($res[4])){
		if (queryFailed($res[4])){
        	        $errs[]="Superclasses for Classes Query Failed";
       		}
		else $tableres4=tabulateSPARQLResultsAssoc(parseXML($res[4]));
	}
	foreach ($tableres4 as $sclass){
		$classes[$sclass['class']]['subclassof'][]=$sclass['superclass'];
		$classes[$sclass['superclass']]['subclass'][]=$sclass['class'];
	}	

	//Retrieve Instances of Classes
	$tableres12=array();
	if (isset($res[12])){
		if(queryFailed($res[12])){
			$errs="Class Instances Query Failed";
			echo "ERROR";
		}
		else $tableres12=tabulateSPARQLResultsAssoc(parseXML($res[12]));
	}
	foreach ($tableres12 as $instance){
                $classes[$instance['class']]['instance'][]=$instance['instance'];
        }


	//Retrieve Labels, Comments, Types and Values for Properties
	$properties=array();
	if (isset($res[5])){
		if (queryFailed($res[5])){
	  		$errs[]="Labels, Comments, Types and Values for Properties Query Failed";
        	}
	        else{
			$properties=tabulateSPARQLResultsAssoc(parseXML($res[5]));
			$properties=setkey($properties,'property');
			foreach ($properties as $k => $v){
				if ($v['range']) $properties[$k]['range']=array($v['range']);
				else $properties[$k]['range']=array();
			}
		}
	}
	
	//Add Properties to Classes and vice-versa
	foreach ($classprops as $classprop){
        	$classes[$classprop['class']]['property'][]=$classprop['property'];
		$pebits=explode(":",replaceNamespace($classprop['property'],$filteront));
		if (sizeof($pebits)==1) $properties[$classprop['property']]['inclass'][]=$classprop['class'];
	}

	//Add Property Domain and Ranges when specified as a list
	if (isset($res[13])){
		if (queryFailed($res[13])){
			$errs[]="Listed Domain and Range Query Failed";
		}
		else{
			$dandrs=tabulateSPARQLResultsAssoc(parseXML($res[13]));
	                $drqueries=array();
        	        foreach ($dandrs as $dandr){
                	        $drqueries[$dandr['property']]="DESCRIBE ?x where {<$dandr[property]> <$dandr[dorr]> ?x}";
				$dorr[$dandr['property']]=$dandr['dorr'];
        	        }
	                if (sizeof($drres>0)){
        	                $drres=callSPARQLQueryClientMultiple($useport,$drqueries,"DESCRIBE","sparql");
                	        foreach ($drres as $prop => $drr){
	                                $drxml=parseXML($drr);
        	                        $lists=$drxml[0]['children'];
                	                foreach($lists as $list){
                        	                foreach ($list['children'] as $kid){
                                	                if ($kid['name']=="RDF:FIRST"){
								if ($dorr[$prop]=="http://www.w3.org/2000/01/rdf-schema#domain"){
									$properties[$prop]['inclass'][]=$kid['attrs']["RDF:RESOURCE"];
								}
								else $properties[$prop]['range'][]=$kid['attrs']["RDF:RESOURCE"];
                	                                        break;
                        	                        }
                                	        }	
	                                }
					if (is_array($properties[$prop]['inclass'])){
						foreach ($properties[$prop]['inclass'] as $k => $v){
        	        	                	if (preg_match('/b[0-9]+/',$v)) unset($properties[$prop]['inclass'][$k]);
                	        	        }
					}
					if (is_array($properties[$prop]['range'])){
		                                foreach ($properties[$prop]['range'] as $k => $v){
        		                        	if (preg_match('/b[0-9]+/',$v)) unset($properties[$prop]['range'][$k]);
                		                }
					}
                        	}
	                }
		}	
	}
	//Sort Classes an Properties 
	ksort($classes);
	ksort($properties);

	//Retrieve Borrowed Equivalent Classes
	$tab6=array();
	if (isset($res[6])){
		if (queryFailed($res[6])){
                	$errs[]="Equivalent Classes Query Failed";
	        }
        	else $tab6=tabulateSPARQLResultsAssoc(parseXML($res[6]));
	}

	//Retrieve Borrowed Equivalent Properties
	$tab7=array();
        if (isset($res[7])){
  		if (queryFailed($res[7])){
                	$errs[]="Equivalent Properties Query Failed";
       		}
		else $tab7=tabulateSPARQLResultsAssoc(parseXML($res[7]));
	}

	//Retrieve Borrowed SubClasses
	$tab8=array();
	if (isset($res[8])){
	        if (queryFailed($res[8])){
        	        $errs[]="SubClass Classes Query Failed";
	        }
       		$tab8=tabulateSPARQLResultsAssoc(parseXML($res[8]));
	}

	//Retrieve Borrowed SubProperties
	$tab9=array();
	if (isset($res[9])){
	        if (queryFailed($res[9])){
        	        $errs[]="SubProperty Properties Query Failed";
        	}
       		$tab9=tabulateSPARQLResultsAssoc(parseXML($res[9]));
	}

	//Retrieve Ontology Info
	$tab11=processOWLInfo($datapath."ontologies/remoteont/".$ontology."_".$headername.".owl",array($filteront,$url,substr($filteront,0,-1)));
	if (sizeof($tab11)==0) $tab11=processRDFDescription($datapath."ontologies/remoteont/".$ontology."_".$headername.".owl",array($filteront,$url,substr($filteront,0,-1)));


	//Display Page
	$pagetitle="$filteront Specification";
	include('specifications/header.inc.php');
	if (sizeof($errs)>0){
		echo "    <!-- Errors -->\n";
		echo "    <div style=\"background-color: LightCoral; border: 2px solid red; padding: 10px; margin: 0;\">\n";
		echo "      <h3 style=\"margin: 0;\">Errors:</h3>\n";
		foreach ($errs as $err){
	 	    	echo "      <p style=\"margin: 0;\">$err</p>\n";
		}
      		echo "    </div>\n    <br/>\n";
	}

	//Print Ontology Info
	echo "  <div class=\"purple\">\n";
        echo "    <h3>Information</h3>\n";
	echo "    <p>";
	echo "<b>url: </b><a href=\"$url\">$url</a><br/>"; 
	$donotshow=array("http://www.w3.org/1999/02/22-rdf-syntax-ns#type","http://www.w3.org/2002/07/owl#imports");
	foreach ($tab11 as $propval){
		if (!in_array($propval['prop'],$donotshow)){
			$descfield="<b>".replaceNamespace($propval['prop']).": </b>";
			foreach($tab11 as $propval2){
				if ($propval['prop']==$propval2['prop']){
					if (preg_match("/b[0-9]+/",$propval2['val']) ) $descfield.=$propval2['label'].", ";
					else $descfield.=$propval2['val'].", ";
				}
			}
			echo substr($descfield,0,-2)."<br/>";
			$donotshow[]=$propval['prop'];
		}
	}
	echo "</p>\n</div>\n";
	echo "<br/>\n";

	//Print Class Listing
	echo "  <div class=\"purple\">\n";
	$c=0;
	echo "    <h3>Classes</h3>\n";
	$oldns="";
	$text="  \n";
	foreach ($classes as $cname => $class){
		$caname=replaceNamespace($cname,$filteront);
		echo $text;
		$text="";
		$text.="      <a href=\"#".$caname."\">".$caname."</a>, \n";
		$c++;
		$oldns=$cbits[sizeof($cbits)-2];
	}
	echo substr($text,0,-3);
	echo "\n    </p>\n  </div>\n  <br/>\n";


	//Print Property Listing
	echo "  <div class=\"purple\">\n";
	$p=0;
	echo "    <h3>Properties</h3>\n    <p>\n";
	$text="";
	$oldns="";
	foreach ($properties as $pname => $property){
		if (strpos($pname,"#")>0) $pbits=explode("#",$pname);
	        else $pbits=explode("/",$pname);
		$paname=replaceNamespace($pname,$filteront);
	        echo $text;
        	$text="";
		$text.="      <a href=\"#".$paname."\">".$paname."</a>, \n";
       		$p++;
		$oldns=$pbits[sizeof($pbits)-2];
	}
	echo substr($text,0,-3);
	echo "\n    </p>\n  </div>\n  <br/>\n";

	//Imported and Borrowed
	echo "  <div class=\"purple\">\n";
	echo "    <h3>Imported and Borrowed</h3>\n";
	echo "    <h4>Imported Ontologies</h4>\n    <p>\n";
        foreach ($ontimports as $ontology){
		$oquery="select * from ontologies where namespace like '$ontology%' or url like '$ontology%'";
		$ores=mysqli_query($GLOBALS['con'], $oquery);
		if (mysqli_num_rows($ores)==1){
			$ourl="?ontology=".mysqli_fetch_assoc($ores)['id'];
	                echo "<a href=\"$ourl\">".$ontology."</a><br/>\n";
		}
		else echo $ontology."<br/>\n";
        }
	if (sizeof($ontimports)==0) echo "none";
	echo "    <h4>Equivalent Classes</h4>\n    <p>\n";
	foreach ($tab6 as $eqclass){
		$myclass=replaceNamespace($eqclass['myclass'],$filteront);
	        echo "<a href=\"#$myclass\">$myclass</a> - ".replaceNamespace($eqclass['exclass'],$filteront)."<br/>\n";
	}
	if (sizeof($tab6)==0) echo "none";
	echo "    </p>\n      <h4>Equivalent Properties</h4>\n    <p>\n";
	foreach ($tab7 as $eqprop){
		$myprop=replaceNamespace($eqprop['myprop'],$filteront);
	        echo "<a href=\"#$myprop\">$myprop</a> - ".replaceNamespace($eqprop['exprop'],$filteront)."<br/>\n";
	}
	if (sizeof($tab7)==0) echo "none";
	echo "    </p>\n      <h4>Subclasses of</h4>\n    <p>\n";
	foreach ($tab8 as $subclass){
		$myclass=replaceNamespace($subclass['myclass'],$filteront);
        	echo "<a href=\"#$myclass\">$myclass</a> - ".replaceNamespace($subclass['exclass'],$filteront)."<br/>\n";
	}
	if (sizeof($tab8)==0) echo "none";
	echo "    </p>\n      <h4>Subproperties of</h4>\n    <p>\n";
	foreach ($tab9 as $subprop){
		$myprop=replaceNamespace($subprop['myprop'],$filteront);
	        echo "<a href=\"#$myprop\">$myprop</a> - ".replaceNamespace($subprop['exprop'],$filteront)."<br/>\n";
	}
	if (sizeof($tab9)==0) echo "none";
	echo "    </p>\n  </div>\n  <br/>\n";

	//Individual Classes
	echo "  <h2>Classes</h2>\n";
	foreach ($classes as $cname => $class){
	 	$caname=replaceNamespace($cname,$filteront);
		$class['shortclass']=str_replace($filteront,'',$class['class']);
		echo "  <div class=\"yellow\">\n";
		echo "  <a name=\"".$caname."\"/>\n    <h3>".$class['shortclass']."</h3>\n    <p><b>Label:</b> ".$class['label']."\n      <br/>\n      <b>Comment:</b> ".$class['comment']."\n      <br/>\n      <b>Subclass of:</b>\n";
		$sc=0;
		if ($class['subclassof']){
			foreach ($class['subclassof'] as $subclassof){
				$scaname=replaceNamespace($subclassof,$filteront);
				if (strpos($subclassof,"#")>0) $scbits=explode("#",$subclassof);
			        else $scbits=explode("/",$subclassof);
				echo "        <a href=\"#".$scaname."\">".$scaname."</a>";
				if ($sc<sizeof($class['subclassof'])-1) echo ",\n";
				$sc++;
			}
		}
		if ($class['subclass']){
			$sc=0;
			echo "\n      <br/>\n      <b>Subclasses:</b>\n";
                        foreach ($class['subclass'] as $subclass){
                                $scaname=replaceNamespace($subclass,$filteront);
                                if (strpos($subclass,"#")>0) $scbits=explode("#",$subclass);
                                else $scbits=explode("/",$subclass);
                                echo "        <a href=\"#".$scaname."\">".$scaname."</a>";
                                if ($sc<sizeof($class['subclass'])-1) echo ",\n";
                                $sc++;
                        }
                }
                echo "\n      <br/>\n      <b>Properties:</b>\n";
		$p=0;
		if ($class['property']){	
			foreach ($class['property'] as $property){
				$paname=replaceNamespace($property,$filteront);
				if (strpos($property,"#")>0) $pbits=explode("#",$property);
	                        else $pbits=explode("/",$property);		
		                if (strpos($paname,":")>0) echo "        ".$paname;
				else echo "        <a href=\"#".$paname."\">".$paname."</a>";
        		        if ($p<sizeof($class['property'])-1) echo ",\n";
	                	$p++;
			}
		}
                $i=0;
                if ($class['instance']){
			echo "\n      <br/>\n      <b>Instances:</b>\n";
                        foreach ($class['instance'] as $instance){
                                $ianame=replaceNamespace($instance,$filteront);
                                echo "        ".$ianame;
                                if ($i<sizeof($class['instance'])-1) echo ",\n";
                                $i++;
                        }
                }
		echo "\n    </p>\n";
		echo "  </div>\n  <br/>\n";
	}


	//Individual Properties
	echo "<h2>Properties</h2>\n";
	foreach ($properties as $pname => $property){
		if (strpos($pname,"#")>0) $pbits=explode("#",$pname);
	        else $pbits=explode("/",$pname);
		$paname=replaceNamespace($pname,$filteront);
  		if (strpos($property['type'],"#")>0) $ptbits=explode("#",$property['type']);
       		else $ptbits=explode("/",$property['type']);
		$pnameshort=str_replace($filteront,"",$pname);
	 	echo "  <div class=\"green\">\n";
		echo "    <a name=\"".$paname."\"/>\n    <h3>".$pnameshort."</h3>\n    ";
		echo "    <p>\n      <b>Type:</b> ".$ptbits[1]."<br/>\n      <b>Label:</b> ".$property['label']."<br/>\n      <b>Comment:</b> ".$property['comment']."\n      <br/>      <b>Used in classes:</b>\n";
		$c=0;
		if ($property['inclass']){
			foreach ($property['inclass'] as $class){
				if (strpos($class,"#")>0) $cbits=explode("#",$class);
			        else $cbits=explode("/",$class);
				$caname=replaceNamespace($class,$filteront);
        		        echo "        <a href=\"#".$caname."\">".$caname."</a>";
		                if ($c<sizeof($property['inclass'])-1) echo ",\n";
        		        $c++;
	        	}
		}
		else echo "Unspecified";
		$p=0;
		echo "    <br/>\n      <b>Value: </b> ";
		if (sizeof($property['range'])>0){
			foreach ($property['range'] as $arange){
				$pvalue=replaceNamespace($arange,$filteront);
				if (strpos($pvalue,":")==0) echo "<a href=\"#$pvalue\">$pvalue</a>";
				else echo $pvalue;
				if ($p<sizeof($property['range'])-1) echo ",\n";
                                $p++;
			}
		}
		else echo "Unspecified";
		echo "\n    </p>\n  </div>\n  <br/>\n";
	}
	include('specifications/footer.inc.php');
}
else include('partials/footer.inc.php');
}
?>

