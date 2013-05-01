#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/testrdfgen.php
 * @brief Tests that the RDF generator at rdfgen/rdfgencli.php is generating myExperiment RDF correctly.
 * @version beta
 * @author David R Newman
 * @details This script tests that the RDF generator at rdfgen/rdfgencli.php is generating myExperiment RDF correctly by testing the number of characters size of various myExperiment entities (formatted in RDF/XML) is at least the same as (if not greater than) a pre-calculated baseline number.
 */

/** @brief An associative array mapping myExperiment entity types to the character length sizes of a particular entities of each type. */
$entity_sizes=array();
include('include.inc.php');
echo "========= ".date('D M d H:i:s T Y')." =========\n";
if (file_exists("${lddir}inc/config/entity_sizes.txt")){
	$lines=file("${lddir}inc/config/entity_sizes.txt");
	foreach ($lines as $line){
		$lbits=explode(" ",$line);
		$entity_sizes[$lbits[0]]=array('id'=>$lbits[1],'size'=>trim($lbits[2]));
	}
}
else{
	$argv[1]="Regenerate";
	$lines=file("${lddir}inc/config/entity_sizes.txt.pre");
        foreach ($lines as $line){
                $lbits=explode(" ",$line);
                $entity_sizes[$lbits[0]]=array('id'=>$lbits[1],'size'=>trim($lbits[2]));
        }
}
if (isset($argv[1]) && $argv[1]=="Regenerate") $fh=fopen("${lddir}inc/config/entity_sizes.txt",'w');
foreach ($entity_sizes as $entity => $entsize){
	echo "Checking $entity/$entsize[id]:\n";
	$ph=popen("${lddir}rdfgen/rdfgencli.php $entity $entsize[id] | wc -l",'r');
	$entsize['newsize']=trim(fgets($ph,8192));
	fclose($ph);
	if ($entsize['newsize']<$entsize['size']){
		file_put_contents("php://stderr","Current size of $entity/$entsize[id] is smaller than previous size ($entsize[newsize] v $entsize[size])\n");
		echo "\nERROR: $entity/$entsize[id] - $entsize[newsize] < $entsize[size]\n\n\n";
	}
	echo "\nOK: $entity/$entsize[id] - $entsize[newsize] >= $entsize[size]\n\n\n";
	if (isset($argv[1]) && $argv[1]=="Regenerate") fwrite($fh,"$entity $entsize[id] $entsize[newsize]\n");
}
if (isset($argv[1]) && $argv[1]=="Regenerate") fclose($fh);
