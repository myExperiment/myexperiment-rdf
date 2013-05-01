#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/test4store.php
 * @brief Checks that the SPARQL endpoint for a particular 4Store knowledge base is functioning correctly.
 * @version beta
 * @author David R Newman
 * @details This script runs a function that checks that a SPARQL query to an endpoint for a particular 4Store knowledge base returns the correct output.  If so it exits with status 0 otherwsie it exists with status 1.
 */

include('include.inc.php');
include('functions/4store.inc.php');
if ($argv[1]==$myexp_kb){
	if (myexperimentFullTestSPARQLQueryClient($myexp_kb)) exit(0);
}
elseif($argv[1]==$onto_kb){
	if (ontologiesFullTestSPARQLQueryClient($onto_kb)) exit(0);
}
exit(1);
?>
