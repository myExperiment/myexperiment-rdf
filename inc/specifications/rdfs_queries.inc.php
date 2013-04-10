<?php
/**
 * @file inc/specifications/rdfs_queries.inc.php
 * @brief A set of SPARQL queries to support generation of an HTML specification document for any RDF schema.
 * @version beta
 * @author David R Newman
 * @details This file provides an array of SPARQL queries that extract classes, properties and relationship thereof for any RDF schema.  The results from tehse queries can then be used to generate an HTML specification document for any RDF schema.
 */

/** @brief An array of strings for the SPARQL queries required to generate the HTML specification for any RDF schema. */
$queries = array();

/** @brief Property Domain Class-Property Relations query. */
$queries[1]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?property where { GRAPH <$remoteont> { ?class rdf:type rdfs:Class . ?property rdfs:domain ?class . FILTER( REGEX(str(?class),'^$filteront'))}}";

/** @brief Label and Comment for Classes query. */
$queries[3]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?label ?comment where { GRAPH <$remoteont> { ?class rdf:type rdfs:Class . OPTIONAL{?class rdfs:label ?label} . OPTIONAL{?class rdfs:comment ?comment} . FILTER(REGEX(str(?class),'^$filteront'))} }";

/** @brief Superclasses for Classes query. */
$queries[4]="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?superclass where { GRAPH <$remoteont> { ?class rdfs:subClassOf ?superclass . FILTER(?superclass!=?class && REGEX(str(?class),'^$filteront) && REGEX(str(?superclass),'^$filteront))}}";

/** @brief Label and Comment for Properties query. */
$queries[5]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?property ?type ?label ?comment ?range where { GRAPH <$remoteont> { ?property rdf:type ?type . ?property rdf:type rdf:Property . OPTIONAL{?property rdfs:label ?label}  . OPTIONAL{?property rdfs:comment ?comment } . OPTIONAL{?property rdfs:range ?range } FILTER(REGEX(STR(?type),'^http://www.w3.org/1999/02/22-rdf-syntax-ns#Property$') && REGEX(str(?property),'^$filteront'))}}";

/** @brief SubClass Classes query. */
$queries[8]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?myclass ?exclass where { GRAPH <$remoteont> { ?myclass rdf:type rdfs:Class . ?myclass rdfs:subClassOf ?exclass . FILTER( !REGEX(STR(?exclass),'^$filteront') && !REGEX(STR(?exclass),'^http://www.w3.org/2000/01/rdf-schema#Resource') && REGEX(str(?myclass),'^$filteront'))}}";

/** @brief SubProperty Properties query. */
$queries[9]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?myprop ?exprop where { GRAPH <$remoteont> { ?myprop rdf:type rdf:Property . ?myprop rdfs:isDefinedBy <$filteront> . ?myprop rdfs:subPropertyOf ?exprop . FILTER( !REGEX(STR(?exprop),'^$filteront') && REGEX(str(?myprop),'^$filteront')) }}";

/** @brief Instances of Classes query. */
$queries[12]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?instance where {?class rdf:type rdfs:Class . ?instance rdf:type ?class . FILTER(REGEX(STR(?instance),'^$filteront.+') && REGEX(str(?class),'^$filteront')) } }";
