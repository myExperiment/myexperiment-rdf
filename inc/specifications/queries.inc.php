<?php
/**
 * @file inc/specifications/queries.inc.php
 * @brief A set of SPARQL queries to support generation of an HTML specification document for a file that neither specifically an OWL ontology or RDF schema.
 * @version beta
 * @author David R Newman
 * @details This file provides an array of SPARQL queries that extract classes, properties and relationship thereof for any non-specific schema.  The results from tehse queries can then be used to generate an HTML specification document for any non-specific schema.
 */

/** @brief An array of strings for the SPARQL queries required to generate the HTML specification for any non-specific schema. */
$queries = array();

/** @brief Property Domain Class-Property Relations query. */
$queries[1]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?property where {{?class rdf:type rdfs:Class} UNION {?class rdf:type owl:Class} . ?class rdfs:subClassOf ?sclass . ?property rdfs:domain ?sclass . FILTER( REGEX(STR(?sclass),'^$filteront') && REGEX(STR(?class),'^$filteront'))}";

/** @brief Class Property Restictions Class-Property Relations query. */
$queries[2]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?property where {?class rdfs:subClassOf ?sclass . ?sclass rdfs:subClassOf ?a. ?a rdf:type owl:Restriction. ?a owl:onProperty ?property . FILTER( REGEX(STR(?sclass),'^$filteront') && REGEX(STR(?class),'^$filteront'))}";

/** @brief Label and Comment for Classes query. */
$queries[3]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?label ?comment where {{?class rdf:type rdfs:Class} UNION {?class rdf:type owl:Class} . OPTIONAL{?class rdfs:label ?label} . OPTIONAL{?class rdfs:comment ?comment} . FILTER( REGEX(STR(?class),'^$filteront'))}";

/** @brief Superclasses for Classes query. */
$queries[4]="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?class ?superclass where {?class  rdfs:subClassOf ?superclass . FILTER(?superclass!=?class && REGEX(STR(?class),'^$filteront') && REGEX(STR(?superclass),'^$filteront'))}";

/** @brief Label and Comment for Properties query. */
$queries[5]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?property ?type ?label ?comment ?range where {?property rdf:type ?type . OPTIONAL{ ?property rdfs:label ?label}  . OPTIONAL{ ?property rdfs:comment ?comment } . OPTIONAL{ ?property rdfs:range ?range } OPTIONAL { ?restriction owl:onProperty ?property . ?restriction owl:someValuesFrom ?range } . FILTER((REGEX(STR(?type),'http://www.w3.org/2002/07/owl#DatatypeProperty') || REGEX(STR(?type),'http://www.w3.org/2002/07/owl#ObjectProperty') || REGEX(STR(?type),'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property')) && REGEX(STR(?property),'^$filteront'))}";

/** @brief Equivalent Classes query. */
$queries[6]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where {{?myclass rdf:type rdfs:Class} UNION {?myclass rdf:type owl:Class}  . ?myclass owl:equivalentClass ?exclass . FILTER( !REGEX(STR(?exclass),'^$filteront') && REGEX(STR(?myclass),'^$filteront'))}";

/** @brief Equivalent Properties query. */
$queries[7]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where { {?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} UNION {?myprop rdf:type rdf:Property}  . ?myprop owl:equivalentProperty ?exprop . FILTER( !REGEX(STR(?exprop),'^$filteront') && REGEX(STR(?myprop),'^$filteront'))}";

/** @brief SubClass Classes query. */
$queries[8]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where { { ?myclass rdf:type rdfs:Class} UNION { ?myclass rdf:type owl:Class } . ?myclass rdfs:subClassOf ?exclass . FILTER( !REGEX(STR(?exclass),'^$filteront') && !REGEX(STR(?exclass),'^http://www.w3.org/2000/01/rdf-schema#Resource') && !REGEX(STR(?exclass),'^http://www.w3.org/2002/07/owl#Thing') && REGEX(STR(?myclass),'^$filteront'))}";

/** @brief SubProperty Properties query. */
$queries[9]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where { {?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} UNION {?myprop rdf:type rdf:Property} .  ?myprop rdfs:subPropertyOf ?exprop . FILTER( !REGEX(STR(?exprop),'^$filteront') && REGEX(STR(?myprop),'^$filteront'))}";

/** @brief $filteront URI with all hashes (#) removed. */
$filteront_nohash=str_replace("#","",$filteront);

/** @brief Imported Ontologies query. */
$queries[10]="PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?import_ont where { {<$filteront> owl:imports ?import_ont} union {<$url> owl:imports ?import_ont} union {<$filteront_nohash>  owl:imports ?import_ont}}";

/** @brief Ontology Information query. */
$queries[11]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?schema ?schema2 ?prop ?val ?label where {{?schema rdf:type <http://www.w3.org/1999/02/22-rdf-syntax-ns#Description> . ?schema ?prop ?val } union { ?schema2 ?prop ?val } . OPTIONAL { ?val rdfs:label ?label } . FILTER( REGEX(STR(?schema2),'^$filteront2$') || REGEX(STR(?schema),'^$url$') || REGEX(STR(?schema2),'^$filteront$')) }";

/** @brief Instances of Classes query. */
$queries[12]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?class ?instance where {{?class rdf:type rdfs:Class} UNION {?class rdf:type owl:Class} . ?instance rdf:type ?class . FILTER( REGEX(STR(?class),'^$filteront') && REGEX(STR(?instance),'^$filteront.+'))}";

/** @brief Listed Domains and Ranges query. */
$queries[13]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?property ?dorr where { {?property rdfs:domain ?tmp} UNION {?property rdfs:range ?tmp} . ?tmp owl:unionOf ?list . ?property ?dorr ?tmp . FILTER(REGEX(STR(?property),'^$filteront'))}";


?>
