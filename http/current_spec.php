<?php
/**
 * @file http/current_spec.php
 * @brief Generates HTML specification of MyExperiment ontology.
 * @version beta
 * @author David R Newman
 * @details Generates HTML specification of MyExperiment ontology using SPARQL queries to the 4Store SPARQL endpoint.
 */

include('include.inc.php');
require_once('functions/xml.inc.php');
require_once('functions/4store.inc.php');

/** @brief An array of strings for the SPARQL queries required to generate the HTML specification for the MyExperiment ontology. */
$query = array();

/** @brief Property Domain Class-Property Relations query. */
$query[1]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select ?class ?property where { ?class rdf:type owl:Class . ?property rdfs:domain ?class . FILTER( REGEX(STR(?class),'^$ontopath'))}";

/** @brief Class Property Restictions Class-Property Relations query. */
$query[2]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select ?class ?property where { ?class rdfs:subClassOf ?sclass . ?sclass rdf:type owl:Restriction . ?sclass owl:onProperty ?property . FILTER( REGEX(STR(?class),'^$ontopath'))}";

/** @brief Label and Comment for Classes query. */
 $query[3]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select ?class ?label ?comment where { ?class rdf:type owl:Class . ?class rdfs:label ?label . ?class rdfs:comment ?comment . FILTER( REGEX(STR(?class),'^$ontopath'))}";

/** @brief Superclasses for Classes query. */
$query[4]="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select ?class ?superclass where { ?class rdfs:subClassOf ?superclass . FILTER(?superclass!=?class && REGEX(STR(?class),'^$ontopath') && REGEX(STR(?superclass),'^$ontopath'))}";

/** @brief Label and Comment for Properties query. */
$query[5]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select ?property ?label ?comment ?property_type where { ?property rdfs:label ?label . ?property rdfs:comment ?comment . ?property rdf:type ?property_type . { ?property rdf:type <http://www.w3.org/2002/07/owl#ObjectProperty> } union { ?property rdf:type <http://www.w3.org/2002/07/owl#DatatypeProperty> } . FILTER(REGEX(STR(?property),'^$ontopath'))}";

/** @brief Equivalent Classes query. */
$query[6]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where { ?myclass rdf:type owl:Class . ?myclass owl:equivalentClass ?exclass . FILTER( !REGEX(STR(?exclass),'^$ontopath') && REGEX(STR(?myclass),'^$ontopath'))}";

/** @brief Equivalent Properties query. */
$query[7]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where {{?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} . ?myprop owl:equivalentProperty ?exprop . FILTER( !REGEX(STR(?exprop),'^$ontopath') && REGEX(STR(?myprop),'^$ontopath'))}";

/** @brief SubClass Classes query. */
$query[8]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myclass ?exclass where {  ?myclass rdf:type owl:Class . ?myclass rdfs:subClassOf ?exclass . FILTER( !REGEX(STR(?exclass),'^$ontopath') && !REGEX(STR(?exclass),'^http://www.w3.org/2000/01/rdf-schema#Resource') && REGEX(STR(?myclass),'^$ontopath'))}";

/** @brief SubProperty Properties query. */
$query[9]="PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
select distinct ?myprop ?exprop where {{?myprop rdf:type owl:DatatypeProperty} UNION {?myprop rdf:type owl:ObjectProperty} . ?myprop rdfs:subPropertyOf ?exprop . FILTER( !REGEX(STR(?exprop),'^$ontopath') && REGEX(STR(?myprop),'^$ontopath'))}";

/** @brief The results from th SPARQL queries for generating the HTML specification. */
$res=callSPARQLQueryClientMultiple($myexp_kb,$query,100000,300,true);

/** @brief Array to store error messages from failed SPARQL queries. */
$errs = array();

/** @brief The results from the first SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres1=array();
if (queryFailed($res[1])){
	$errs[]="Property Domain Class-Property Relations Query Failed";
}
else {
	$tableres1=tabulateSPARQLResultsAssoc(parseXML($res[1]));
}

/** @brief The results from the second SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres2=array();
if (queryFailed($res[2])){
        $errs[]="Class Property Restictions Class-Property Relations Query Failed";
}
else {
	$tableres2=tabulateSPARQLResultsAssoc(parseXML($res[2]));
}

/** @brief Array containing the class properties found from the SPARQL queries against the MyExperiment ontology. */
$classprops=multidimensionalArrayUnique(array_merge($tableres1,$tableres2));

/** @brief Array containing the classes found from the SPARQL queries against the MyExperiment ontology. */
$classes=array();
if (queryFailed($res[3])){
        $errs[]="Label and Comment for Classes Query Failed";
}
else{
        $classes=tabulateSPARQLResultsAssoc(parseXML($res[3]));
        $classes=setKey($classes,'class');
}

/** @brief The results from the fourth SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres4=array();
if (queryFailed($res[4])){
        $errs[]="Superclasses for Classes Query Failed";
}
else {
	$tableres4=tabulateSPARQLResultsAssoc(parseXML($res[4]));
}
foreach ($tableres4 as $sclass){
	$classes[$sclass['class']]['subclassof'][]=$sclass['superclass'];
}

/** @brief Array containing the properties found from the SPARQL queries against the MyExperiment ontology. */
$properties=array();
if (queryFailed($res[5])){
        $errs[]="Label and Comment for Properties Failed";
}
else{
        $properties=tabulateSPARQLResultsAssoc(parseXML($res[5]));
        $properties=setkey($properties,'property');
}
foreach ($classprops as $classprop){
        $classes[$classprop['class']]['property'][]=$classprop['property'];
        if (isMMyexperimentNamespace(replaceNamespace($classprop['property']))) $properties[$classprop['property']]['inclass'][]=$classprop['class'];
}

ksort($classes);
ksort($properties);

/** @brief The results from the sixth SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres6=array();
if (queryFailed($res[6])){
        $errs[]="Equivalent Classes Query Failed";
}
else {
	$tableres6=tabulateSPARQLResultsAssoc(parseXML($res[6]));
}
/** @brief The results from the seventh SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres7=array();
if (queryFailed($res[7])){
        $errs[]="Equivalent Properties Query Failed";
}
else {
	$tableres7=tabulateSPARQLResultsAssoc(parseXML($res[7]));
}
/** @brief The results from the eighth SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres8=array();
if (queryFailed($res[8])){
        $errs[]="SubClass Classes Query Failed";
}
else {
	$tableres8=tabulateSPARQLResultsAssoc(parseXML($res[8]));
}
/** @brief The results from the ninth SPARQL query for generating the HTML specifications formatted as a tabular array. */
$tableres9=array();
if (queryFailed($res[9])){
	$errs[]="SubProperty Properties Query Failed";
}
else {
	$tableres9=tabulateSPARQLResultsAssoc(parseXML($res[9]));
}

/** @brief The page title to be displayed in an h1 tag and the title of the html header. */
$pagetitle="Ontology Specification";
include('partials/header.inc.php');

if (isset($errs) && sizeof($errs)>0){
        echo "    <!-- Errors -->\n";
        echo "    <div class=\"red\">\n";
        echo "      <h3>Errors:</h3>\n";
        foreach ($errs as $err){
	        echo "      <p>$err</p>\n";
        }
        echo "    </div>\n    <br/>\n";
}
else {
	echo "    <!-- Correct -->\n";
}

//Print Class Listing
echo "  <div class=\"purple\">\n";
/** @brief $counter for the number of classes. */
$c=0;
echo "    <h3>Classes</h3>\n";
/** @brief The namespace of the previous class. */
$previous_namespace="";
/** @brief Text to be printed out as part of the HTML specification. */
$text="  \n";
foreach ($classes as $class_uri => $class){
	/** @brief The class URI broken up into an array of strings on slashes. */
	$class_uri_bits=explode("/",$class_uri);
	/** @brief The namespace of the current class. */
	$current_namespace=$class_uri_bits[sizeof($class_uri_bits)-2];
	/** @brief The class name using the URI's path replaced with the ontology prefix (e.g. mebase). */
	$class_name=replaceNamespace($class_uri);
	if ($current_namespace!=$previous_namespace){
		echo substr($text,0,-3);
		$text="";
		if ($previous_namespace) echo "    </p>\n";
		$text.="    <h4>".ucwords(str_replace("_"," ",$current_namespace))."</h4>\n    <p>\n";
	}
	else{
		echo $text;
		$text="";
	}
	$text.="      <a href=\"#".$class_name."\">".$class_name."</a>, \n";
	$c++;
	$previous_namespace=$current_namespace;
}
echo substr($text,0,-3);
echo "\n    </p>\n  </div>\n  <br/>\n";

//Print Properties Listing
echo "  <div class=\"purple\">\n";
/** @brief $counter for the number of properties. */
$p=0;
echo "    <h3>Properties</h3>\n    <p>\n";
$text="";
$previous_namespace="";
foreach ($properties as $property_name => $property) {
	/** @brief The property URI broken up into an array of strings on slashes. */
        $property_uri_bits=explode("/",$property_uri);
	$current_namespace=$property_uri_bits[sizeof($property_uri_bits)-2];
	/** @brief The property name using the URI's path replaced with the ontology prefix (e.g. mebase). */
	$property_name=replaceNamespace($property_uri);
	if ($current_namespace!=$previous_namespace){
                echo substr($text,0,-3);
                $text="";
                if ($previous_namespace) echo "    </p>\n";
                $text.="    <h4>".ucwords(str_replace("_"," ",$current_namespace))."</h4>\n    <p>\n";
        }
	else{
                echo $text;
                $text="";
        }
	$text.="      <a href=\"#".$property_name_prefixed."\">".$property_name_prefixed."</a>, \n";
        $p++;
	$previous_namespace = $current_namespace;
}
echo substr($text,0,-3);
echo "\n    </p>\n  </div>\n  <br/>\n";

//Borrowed Classes/Properties Mappings
echo "  <div class=\"purple\">\n";
echo "    <h3>Borrowed Classes and Properties</h3>\n";
echo "    <h4>Equivalent Classes</h4>\n    <p>\n";
foreach ($tableres6 as $eqclass){
	/** @brief local class name equivalent/subclass to an external class. */
	$myclass=replaceNamespace($eqclass['myclass']);
        echo "<a href=\"#$myclass\">$myclass</a> - ".replaceNamespace($eqclass['exclass'])."<br/>\n";
}
if (sizeof($tableres6)==0) echo "none";
echo "    </p>\n      <h4>Equivalent Properties</h4>\n    <p>\n";
foreach ($tableres7 as $eqprop){
	/** @brief local property name equivalent/subproperty to an external property. */
	$myprop=replaceNamespace($eqprop['myprop']);
        echo "<a href=\"#$myprop\">$myprop</a> - ".replaceNamespace($eqprop['exprop'])."<br/>\n";
}
if (sizeof($tableres7)==0) echo "none";
echo "    </p>\n      <h4>Subclasses of</h4>\n    <p>\n";
foreach ($tableres8 as $subclass){
	$myclass=replaceNamespace($subclass['myclass']);
        echo "<a href=\"#$myclass\">$myclass</a> - ".replaceNamespace($subclass['exclass'])."<br/>\n";
}
if (sizeof($tableres8)==0) echo "none";
echo "    </p>\n      <h4>Subproperties of</h4>\n    <p>\n";
foreach ($tableres9 as $subprop){
	$myprop=replaceNamespace($subprop['myprop']);
        echo "<a href=\"#$myprop\">$myprop</a> - ".replaceNamespace($subprop['exprop'])."<br/>\n";
}
if (sizeof($tableres9)==0) echo "none";
echo "    </p>\n  </div>\n  <br/>\n";

//Individual Classes
echo "  <h2>Classes</h2>\n";
foreach ($classes as $class_uri => $class){
	$class_name=replaceNamespace($class_uri);
	echo "  <div class=\"yellow\">\n";
	echo "  <a name=\"".$class_name."\"/>\n    <h3>".$class_name."</h3>\n    <p><b>Label:</b> ".$class['label']."\n      <br/>\n      <b>Comment:</b> ".$class['comment']."\n      <br/>\n      <b>Subclass of:</b>\n";
	$sc=0;
	if ($class['subclassof']){
		foreach ($class['subclassof'] as $subclassof){
			/** @brief Name of a subclass of a particular class. */
			$subclass_name=replaceNamespace($subclassof);
			echo "        <a href=\"#".$subclass_name."\">".$suvclass_name."</a>";
			if ($sc<sizeof($class['subclassof'])-1) echo ",\n";
			$sc++;
		}
	}
	echo "\n      <br/>\n      <b>Properties:</b>\n";
	$p=0;
	if ($class['property']){
		foreach ($class['property'] as $property){
			$property_name=replaceNamespace($property);
			if (strpos($property,":")>0 && !isMyexperimentNamespace($property_name)) echo "        ".$property_name;
	                else echo "        <a href=\"#$property_name\">".$property_name."</a>";
        	        if ($p<sizeof($class['property'])-1) echo ",\n";
                	$p++;
		}
	}
	echo "\n    </p>\n";
	echo "  </div>\n  <br/>\n";
}

//Individual Properties
echo "<h2>Properties</h2>\n";
foreach ($properties as $pnoperty_uri => $property){
	$property_name=replaceNamespace($property_uri);
	$property_type_bits=explode("#",$property['property_type']);
 	echo "  <div class=\"green\">\n";
	echo "    <a name=\"".$property_name."\"/>\n    <h3>".$property_name."</h3>\n    ";
	echo "    <p>\n      <b>Type:</b> ".$property_type_bits[1]."<br/>\n      <b>Label:</b> ".$property['label']."</br/>\n      <b>Comment:</b> ".$property['comment']."\n      <br/>      <b>Used in classes:</b>\n";
	$c=0;
	foreach ($property['inclass'] as $class){
		$class_name=replaceNamespace($class);
                echo "        <a href=\"#".$class_name."\">".$class_name."</a>";
                if ($c<sizeof($property['inclass'])-1) echo ",\n";
                $c++;
        }
	echo "\n    </p>\n  </div>\n  <br/>\n";
}

include('partials/footer.inc.php');
?>

