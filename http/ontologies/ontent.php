<?php
/**
 * @file http/ontologies/ontent.php
 * @brief RDF for single class/property in myExperiment Ontology.
 * @version beta
 * @author David R Newman
 * @details Displays RDF/XML for individual class or property in one of the modules of the myExperiment Ontology.  E.g. ontent.php?ontology=contributions&entity=Workflow display the RDF/XML for the Workflow class.
 */

include('../include.inc.php');
include('functions/rdf.inc.php');
/** @brief A string containing the URL subpath of the myExperiment ontology module the entity is in. */
$ontology="$ontopath$_GET[ontology]/";
/** @brief An array containing every line of the ontology file requested. */
$lines = file($ontology);
/** @brief An integer containing the current line number that is being look in the ontology file. */
$l=0;
while (strpos($lines[$l],'rdf:about="'.$_GET['entity'].'"') ==0 and $l < sizeof($lines)){
        $l++;   
}
/** @brief A string containing the full URI for the ontology entity that has been requested. */
$entity=str_replace($_GET['entity'],$ontology.$_GET['entity'],$lines[$l]);
$l++;
while (strlen(trim($lines[$l]))>0 and $l<sizeof($lines)){
         if (preg_match('/rdf:resource="([^"]+)"/',$lines[$l],$matches)){
                if ($matches[1] && !preg_match('@(&[a-z]+;|http://)@',$matches[1])){
                        $lines[$l]=str_replace($matches[1],$ontology.$matches[1],$lines[$l]);
                }
        }
        $entity.=$lines[$l];
        $l++;
}
if (!empty($entity)) {
	header('Content-type: application/rdf+xml');
	echo generateGenericRDFHeader();
	echo $entity;
	echo generateRDFFooter(); 
}
else {
	header("HTTP/1.1 404 Not Found");
	echo "404 Not Found";
}
?> 
