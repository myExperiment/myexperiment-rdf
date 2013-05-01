#!/usr/bin/php 
<?php
/**
 * @file 4store/scripts/listGraphs.php
 * @brief Lists the graphs that have been uploaded to a specified 4Store knowledge base
 * @version beta
 * @author David R Newman
 * @details This script prints a newline separated list of graphs uploaded to a 4Store knowledge base specified as the first argument on the command line.
 *
 * Usage: ./listGraphs.php &lt;knowledge_base&gt;
 */
	include('include.inc.php');
	include('functions/xml.inc.php');
	if (!$argv[1]) die("No Knowledge Base specified!\n");
	/** @brief A string representing a SPARQL query call to get the list of graphs in a specified knowledge base. */
	$query=$store4execpath."4s-query $argv[1] ".'"select distinct ?g where { graph ?g {?s ?p ?o} }"';
	/** @brief A handler for a piped stream containing the SPARQL results XML. */
	$ph=popen($query,'r');
	/** @brief A chunk of data return as part of the SPARQL results XML. */
	$line=fgets($ph,8192);
	/** @brief A string containing the complete SPARQL results XML from the graphs query. */
	$xml="";
	while (sizeof($line)>0 && !feof($ph)){
		$xml.=$line;
		$line=fgets($ph,8192);
	}	
	fclose($ph);
	if (empty($xml)) die("Knowledge Base does not exist!\n");
 	/** @brief A multidimemensional associative array representing the parsed SPARQL XML results returned from the graphs SPARQL query. */
	$pxml=parseXML($xml);
	/** @brief A string containing a new line separate list of all graphs found for a specified knowledge base. */
	$glist="";
	if (isset($pxml[0]['children'][1]['children'])){
		foreach ($pxml[0]['children'][1]['children'] as $graphs){
			$glist.=$graphs['children'][0]['tagData']."\n";
		}
		echo substr($glist,0,-1);
	}
?>

