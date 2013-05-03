#!/usr/bin/php
<?php 
/**
 * @file rdfgen/rdfgencli.php
 * @brief Command line utility for generation MyExperiment RDF for any specified myExperiment entity.
 * @version beta
 * @author David R Newman
 * @details This script provides a command line utility to generate RDF/XML for any myExperiment entity.  This utility can be invoked using the following command:
 *
 * ./rdfgencli.php &lt;primary_entity_type&gt; &lt;primary_entity_id&gt; [nested_entity_path_and_id] [myexperiment_user_id_requesting_rdf]
 *
 * E.g. 
 *   ./rdfgencli.php workflows 16
 *   ./rdfgencli.php users 12
 *   ./rdfgencli.php files 111
 *   ./rdfgencli.php workflows 16 versions/1
 *   ./rdfgencli.php groups 180 announcements/16
 *   ./rdfgencli.php workflows 16 12 
 *   ./rdfgencli.php workflows 16 versions/1 12
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once('include.inc.php');
require_once('functions/rdf.inc.php');

// Only respond to requests with a correct number of arguments.
if (sizeof($argv) > 5) {
	error_log("Too many arguments: " . print_r($argv, true));
	exit(1);
}
else if (sizeof($argv) < 2) {
	error_log("Too few arguments: " . print_r($argv, true));
        exit(1);
}
// Get the entity Type and ID from the submitted request (also set the global $rdfgen_userid for ensuring requesting User has permission).
list($type,$id) = getEntityTypeAndID($argv);
// If entity exists then generate RDF
if (entityExists($type,$id)){
	// Get result for entity via a database query
        $entity_result = getEntityResults($type, $id);
	if (mysql_num_rows($entity_result) == 0) {
		error_log("Entity does not exist: " . print_r($argv, true));
        	exit(1);
	}
	$entity = mysql_fetch_assoc($entity_result);
	// Determine URI for the entity requested
	$entity_uri = getEntityURI($type, $id, $entity);
	if ($type != "Ontology") {	
		$xml = generateGenericRDFHeader();
		$xml .= generateRDFFileDescription($entity_uri);
		$entity_xml = generateEntityRDF($entity, $type);
		// Use a regex for matching URIs that ensures that are an atrribute of an XML tag not a value.
		$regex = $datauri . '[^<>]+>';
		// Find entity URI matches in entity RDF/XML only.
		preg_match_all("!$regex!", $entity_xml, $matches);
		// Remove any errant whitespace characters from URI matches
		$matches = array_unique(str_replace(array(" ","\n","\t"), array("","",""), $matches[0]));
		$xml .= $entity_xml;
		// Generate RDF/XML for every entity match
		foreach ($matches as $match) {
			if (strpos($match, '/>') > 0){
				// Extract Entity URI from RDF
				$match_tmp = explode('"',$match);
				// Remove Data URI from front of Entity URI
				$match = str_replace($datauri, "", $match_tmp[0]);
				// Extract part of URI after #, if it exists
				$match_hash_bits=explode('#', $match);
				// Break down the path of the URI so the entity type can be determined
				$match_bits=explode('/',$match_hash_bits[0]);
				// If entity can be determine then set $entity_type else ignore this entity.
				if (!empty($path_entity_mappings[$match_bits[0]])){
					$entity_type = $path_entity_mappings[$match_bits[0]];
				}
				else{
					continue;
				}
				// If the URL has a post # part then it is a Dataflow.  Otherwise it is a regular entity.
				if (!empty($match_hash_bits[1])){
					// Determine the WorkflowVersion ID so that the local Dataflow RDF file can be found and Dataflow RDF retrieved.
                                      	$id = getVersionID(array("contributable_type" => "Workflow", "contributable_id" => $match_bits[1], "contributable_version" => $match_bits[3]));
                       	                $xml .= retrieveDataflowRDF($id)."\n";
				}
				else{
					// Do not attempt to generate RDF for format URI (e.g. http://www.myexperiment.org/workflows/16.rdf)
					if (strpos($match_bits[sizeof($match_bits)-1], ".") > 0) {
						continue;
					}
					// Use the parts of the path to build at set of arguments for retrieving the entity type and ID
					$args[1] = array_shift($match_bits);
					$args[2] = array_shift($match_bits);
					$args[3] = implode('/', $match_bits);
					// Use the same RDF requesting User ID that was determined from the original RDF request.
					$args[4] = $rdfgen_userid;
					list($type, $id) = getEntityTypeAndId($args);
					if (entityExists($type, $id)){
						$res = getEntityResults($type, $id);	
						$xml .= generateEntityRDF(mysql_fetch_assoc($res), $type);
					}
					
				}
			}
		}
	}
	else {
		// Generate RDF for a user generated OWL Ontology rather than standard RDF entities.
                $xml = generateOntologyRDFHeader($entity);
		$xml .= generateEntityRDF($entity, $type);
                $xml .= generatePredicatesRDF($id);
        }
	// Add RDF page footer and print RDF/XML response to original request.
  	$xml.=generateRDFFooter();
	echo $xml;
}
else {
	error_log("Entity with type: $type and ID: $id could not be found!");
	exit(1);
}
?>
