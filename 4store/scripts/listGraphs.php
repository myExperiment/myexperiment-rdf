#!/usr/bin/php 
<?php
/**
 * @file 4store/scripts/listGraphs.php
 * @brief Lists the graphs that have been uploaded to a specified 4Store knowledge base
 * @version beta
 * @author David R Newman
 * @details This script prints a newline separated list of graphs uploaded to a 4Store knowledge base specified as the first argument on the command line.
 *
 * Usage: ./listGraphs.php <knowledge_base>
 */
	include('include.inc.php');
	include('functions/xml.inc.php');
	if (!$argv[1]) die("No Knowledge Base specified!\n");
	$query=$store4execpath."4s-query $argv[1] ".'"select distinct ?g where { graph ?g {?s ?p ?o} }"';
	$ph=popen($query,'r');
	$line=fgets($ph,8192);
	$xml="";
	while (sizeof($line)>0 && !feof($ph)){
		$xml.=$line;
		$line=fgets($ph,8192);
	}	
	fclose($ph);
	if (!$xml) die("Knowledge Base does not exist!\n");
	$pxml=parseXML($xml);
	$glist="";
	if (isset($pxml[0]['children'][1]['children'])){
		foreach ($pxml[0]['children'][1]['children'] as $graphs){
			$glist.=$graphs['children'][0]['tagData']."\n";
		}
		echo substr($glist,0,-1);
	}
?>

