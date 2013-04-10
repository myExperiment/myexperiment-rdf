#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/getDatatypes.php
 * @brief Queries the myExperiment 4Store knowledge base to determine the datatypes of all myExperiment properties.
 * @version beta
 * @author David R Newman
 * @details This script use the 4s-query command line utility to perform a SPARQL query of the myExperiment 4Store knowledge base to determine the datatype of each property defined in the myExperiment ontology (that has already been imported into this knowledge base).
 */

include('include.inc.php');
include('functions/xml.inc.php');
include('config/data.inc.php');
$ph=popen($store4execpath."4s-query myexp_public \"PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX owl: <http://www.w3.org/2002/07/owl#> PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> select distinct ?prop ?dt where {?prop rdf:type owl:DatatypeProperty . ?prop rdfs:range ?dt}\"",'r');
$res="";
while (!feof($ph)) {
        $res.=fgets($ph, 4096);
}
$resarr=parseXML($res);
$fh=fopen($ldpath.'inc/config/datatypes.txt','w');
foreach ($resarr[0]['children'][1]['children'] as $num => $rec){
	$prop = str_replace(array_values($ontology_prefixes), array_keys($ontology_values), $rec['children'][0]['tagData']);
        $dt = str_replace('http://www.w3.org/2001/XMLSchema#','',$rec['children'][1]['tagData']);
	fwrite($fh,"$prop $dt\n");
}
fclose($fh);
