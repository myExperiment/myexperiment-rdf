<?php
/**
 * @file inc/specifications/owl_queries.inc.php
 * @brief A set of SPARQL queries to support generation of an HTML specification document for any OWL ontology.
 * @version beta
 * @author David R Newman
 * @details This file provides an array of SPARQL queries that extract classes, properties and relationship thereof for any OWL ontology.  The results from tehse queries can then be used to generate an HTML specification document for any OWL ontology.
 */

if (!$filteront){
	include_once('inc/config/settings.inc.php');
	$filteront=$ontopath;
	$url=$ontopath;
	$print=1;
}
/** @brief An array of strings for the SPARQL queries required to generate the HTML specification for any OWL ontology. */
$queries = array();

/** @brief  Property Domain Class-Property Relations query. */
$queries[1]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?property where { GRAPH <$remoteont>  { ?class rdf:type owl:Class . ?property rdfs:domain ?class . FILTER(REGEX(str(?class),'^$filteront')) }}";

/** @brief Class Property Restictions Class-Property Relations query. */
$queries[2]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?property where { GRAPH <$remoteont> { ?class rdfs:subClassOf ?sclass . ?sclass rdf:type owl:Restriction . ?a owl:onProperty ?property . FILTER( REGEX(STR(?class),'^$filteront')} }";

/** @brief Label and Comment for Classes query. */
$queries[3]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?label ?comment where { GRAPH <$remoteont> { ?class rdf:type owl:Class . OPTIONAL{?class rdfs:label ?label} . OPTIONAL{?class rdfs:comment ?comment} . FILTER( REGEX(STR(?class),'^$filteront'))}}";

/** @brief Superclasses for Classes query. */
$queries[4]="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?superclass where { GRAPH <$remoteont> { ?class rdfs:subClassOf ?superclass . FILTER(?superclass!=?class &&  REGEX(STR(?class),'^$filteront') &&  REGEX(STR(?superclass),'^$filteront')) } }";

/** @brief Label and Comment for Properties query. */
$queries[5]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?property ?type ?label ?comment ?range where { GRAPH <$remoteont> { ?property rdf:type ?type .  ?property rdfs:isDefinedBy <$filteront> . {?property rdf:type owl:DatatypeProperty} UNION {?property rdf:type owl:ObjectProperty} . OPTIONAL{ ?property rdfs:label ?label}  . OPTIONAL{ ?property rdfs:comment ?comment } . OPTIONAL{ ?property rdfs:range ?range } OPTIONAL { ?restriction owl:onProperty ?property . ?restriction owl:someValuesFrom ?range } .  FILTER(REGEX(STR(?property),'^$filteront'))}}";

/** @brief Equivalent Classes query. */
$queries[6]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where { GRAPH <$remoteont> { ?myclass rdf:type owl:Class . ?myclass owl:equivalentClass ?exclass . FILTER( !REGEX(STR(?exclass),'^$filteront') &&  REGEX(STR(?myclass),'^$filteront'))}}";

/** @brief Equivalent Properties query. */
$queries[7]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where { GRAPH <$remoteont> { {?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} . ?myprop owl:equivalentProperty ?exprop . FILTER( REGEX(STR(?myprop),'^$filteront') && !REGEX(STR(?exprop),'^$filteront') && (REGEX(STR(?type),'^http://www.w3.org/2002/07/owl#DatatypeProperty$') || REGEX(STR(?type),'^http://www.w3.org/2002/07/owl#ObjectProperty$')))}}";

/** @brief SubClass Classes query. */
$queries[8]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where { GRAPH <$remoteont> { ?myclass rdf:type owl:Class . ?myclass rdfs:subClassOf ?exclass . FILTER( REGEX(STR(?myclass),'^$filteront') && !REGEX(STR(?exclass),'^$filteront') && !REGEX(STR(?exclass),'^http://www.w3.org/2000/01/rdf-schema#Resource') && !REGEX(STR(?exclass),'^http://www.w3.org/2002/07/owl#Thing'))}}";

/** @brief SubProperty Properties query. */
$queries[9]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where { GRAPH <$remoteont> {{?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} . ?myprop rdfs:subPropertyOf ?exprop . FILTER( REGEX(STR(?myprop),'^$filteront') && !REGEX(STR(?exprop),'^$filteront'))}}";

/** @brief Instances of Classes query. */
$queries[12]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?instance where {GRAPH <$remoteont> { ?class rdf:type owl:Class . ?instance rdf:type ?class . FILTER( REGEX(STR(?class),'^$filteront') && REGEX(STR(?instance),'^$filteront.+'))}}";

/** @brief Listed Domains and Ranges query. */
$queries[13]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?property ?dorr where { GRAPH <$remoteont> {{?property rdfs:domain ?tmp} UNION {?property rdfs:range ?tmp} . ?tmp owl:unionOf ?list . ?property ?dorr ?tmp . FILTER( REGEX(STR(?property),'^$filteront'))}}";

if ($print) print_r($queries);
