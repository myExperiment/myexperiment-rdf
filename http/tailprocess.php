<?php
/**
 * @file http/tailprocess.php
 * @brief Display output of ontology specification document generation.
 * @version beta
 * @author David R Newman
 * @details Displays the output for an iframe of the upload, SPARQL querying and document generation of an ontology added to the HTML specification generation tool.
 */

include('include.inc.php');
require_once('functions/4store.inc.php');

/** @brief a string containing log output from the script that either uploads or caches the HTML spefication document of an ontology. */
$logtext="";
if(file_exists($_GET['log'])){
        $handle = fopen($_GET['log'], "r");
        if($handle != false){
                while(!feof($handle)){
                        $logtext.=fgets($handle);
                }
                fclose($handle);
        }
}
else{
	if ($_GET['op']=="retrieveOntology") retrieveOntology($_GET['name'],$_GET['url'],$_GET['ontology'],$_GET['log']);
	elseif ($_GET['op']=="cacheSpec") cacheSpec($_GET['name'],$_GET['url'],$_GET['ontology'],$_GET['log']);
	$logtext="Initializing - About to start printing logfile ".$_GET['log'];
}
if ($logtext){
	echo '<html><head>';
	if (!preg_match("/Finished/",$logtext)){
		echo '<meta http-equiv="refresh" content="5"/>';
	}
	echo "<pre>$logtext</pre>"; 
	echo '</head><body>';
        echo '<a name="BOTTOM">&nbsp;</a>';
 	echo '</body></html>';
}
