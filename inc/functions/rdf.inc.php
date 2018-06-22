<?php
/**
 * @file inc/functions/rdf.inc.php
 * @brief Functions used by myExperiment's RDF generator at rdfgen/rdfgencli.php.
 * @version beta
 * @author David R Newman
 * @details Functions used by myExperiment's RDF generator at rdfgen/rdfgencli.php.  It also requires various other sets of functions and configuration including:
 *   - connect/myexperiment.inc.php: To setup a connection to the myExperiment database.
 *   - functions/utility.inc.php: Various generic utility functions.
 *   - functions/property.inc.php: Functions for generating property/value pairs for myExperiment RDF entities.
 *   - config/data.inc.php: Specifies how myExperiment RDF entities should be generated from the myExperiment database.
 *   - functions/sql.inc.php: SQL specific functions.
 *   - functions/xml.inc.php: XML parsing, generating and examining functions.
 */
require_once('connect/myexperiment.inc.php');
require_once('functions/utility.inc.php');
require_once('functions/property.inc.php');
require_once('config/data.inc.php');
require_once('functions/sql.inc.php');
require_once('functions/xml.inc.php');
		
/**
 * @brief Determine the entity type and ID from the arguments provided at the command line.
 *
 * @param $args
 * An array containing the top-level parent entity type, the top-level parent ID, optionally the URI path to the child entity and optionally the myExperiment user requesting the RDF.
 * 
 * @return
 * An array containing first the entity type and second the entity ID.  Both these values will be set to null if the entity cannot be found.
 */
function getEntityTypeAndID($args){
        global $entities, $path_entity_mappings, $rdfgen_userid, $exclude_url_subpaths;
 	$params = array();
        $rdfgen_userid = 0;
	if (!isset($path_entity_mappings[$args[1]])) {
		error_log("Type does not exist: " . print_r($args, true));
		return array(null, null);
	}
        if (isset($args[2])){
                $type=$path_entity_mappings[$args[1]];
                $id=$args[2];
        }
        else {
		error_log("Not enough arguments: " . print_r($args, true));
		return array(null, null);
	}
        if (isset($args[4])){
                $rdfgen_userid = $args[4];
                $params = explode("/",$args[3]);
        }
        elseif (isset($args[3])) {
        	$paramstemp = explode("/",$args[3]);
                if (sizeof($paramstemp) > 1) $params = $paramstemp;
                else $rdfgen_userid = $args[3];
        }
        if (!empty($params[0])){
		$nested_url_subpath = $params[sizeof($params)-2];
		$nested_entity_num = $params[sizeof($params)-1];
		if (isset($entities[$type]['nested_url_subpath_entities'][$nested_url_subpath])){
			$nested_entity_type = $entities[$type]['nested_url_subpath_entities'][$nested_url_subpath];
		}
		else if (isset($path_entity_mappings[$nested_url_subpath])) {
			$nested_entity_type = $path_entity_mappings[$nested_url_subpath];
		}
                if ($nested_url_subpath =="versions") {	
			$type = $entities[$type]['version_entity'];
                        if (!empty($entities[$type]['versioned_entity'])) {
                                $version_sql="select id from {$entities[$type]['table']} where {$entities[$type]['versioned_id']}=$id and version=$nested_entity_num";
                                $version_res=mysqli_query($GLOBALS['con'], $version_sql);
				if (mysqli_num_rows($version_res) == 0) {
					error_log("Version ID could not be deterimed for version $nested_entity_num of $type $id");
					return array(null,null);
				}
                                $id=mysqli_fetch_assoc($version_res)['id'];
                        }
                }
		elseif (isset($nested_entity_type)) {
			return array($nested_entity_type, $nested_entity_num);
		}
                else {
			if (!in_array($nested_url_subpath, $exclude_url_subpaths)) {
				error_log("Type of nested entity could not be determined from URL subpath: $nested_url_subpath");
			}
			return array(null, null);
		}
        }
        return array($type,$id);
}

/**
 * @brief Get MySQL result for query of an entity with a specific ID.
 *
 * @param $type
 * A string representing the type of entity to be queried.
 * 
 * @param $id
 * An integer representing the ID of the entity to be queried.
 *
 * @return
 * A MySQL result of the query of an entity with a specific ID.
 */
function getEntityResults($type,$id){
        global $entities, $sql;
        if ($id){
                $whereclause=$entities[$type]['table'].".id=$id";
                if (stripos($sql[$type],"where") === false ){
                        $cursql=$sql[$type]." where ".$whereclause;
                }
                else $cursql=$sql[$type]." and ".$whereclause;
        }
        else $cursql=$sql[$type];
        return mysqli_query($GLOBALS['con'], $cursql);
}

/**
 * @brief Test whether an entity or one of a number entities or a type of entity exists.
 *
 * @param $type
 * A string representing the type of entity that is being tested to see if it exists.
 *
 * @param $ids
 * An array containing the IDs of the entities to be tested to see if any exist.
 *
 * @return
 * TRUE if at least one entity exists, FALSE otherwise.
 */
function entityExists($type,$ids=array()){
        global $entities;
        if (!isset($entities[$type])) return FALSE;
        if (!is_array($ids)) $ids=array($ids);
        $cursql="select * from ".$entities[$type]['table'];
        if (sizeof($ids)>0 && $ids[0]>0){
                $cursql.=" where id in (".implode(",", $ids).")";
        }
        $res = mysqli_query($GLOBALS['con'], $cursql);
        if (empty($res)) return FALSE;
        return TRUE;
}

/**
 * @brief Generates RDF/XML for a particular entity.
 * 
 * @param $entity
 * An associative array containing the fields/values of the entity needed to generate its RDF/XML.
 * 
 * @param $type
 * A string containing the type of entity for which RDF/XML is being generated.
 *
 * @return 
 * A string containing the RDF/XML generated for an entity whose field values have been provided.
*/
function generateEntityRDF($entity, $type){
	global $datauri, $domain, $datatypes, $entities, $mappings; 
	$template = $mappings[$type];
	if (!is_array($template)) return "";
	$idfield = array_search('url',$template);
	$id = $entity[$idfield];
	unset($template[$idfield]);
	$ontology_prefix = $entities[$type]['ontology_prefix'];
	$full_entity = "$ontology_prefix:$type";
	$uri = getEntityURI($type, $id ,$entity);	
	$xml = "  <$full_entity rdf:about=\"$uri\">\n";
	$xml .= getHomepageAndFormats($uri, $entity, $id, $type);
	foreach ($template as $field => $property) {
		switch (substr($property, 0, 1)) {
		case '<':
			if (!isset($entity[$field])) break;
			$entity[$field]=convertToXMLEntities($entity[$field]);
			if (isset($entity[$field])){
                        	if (isset($datatypes[substr($property, 1)])){
                                       $xml.=printDatatypeProperty(substr($property, 1),$entity[$field]);
                                }
                                else{
                                        $xml.=printEntityProperty($field,substr($property, 1),$entity[$field],"NoDataURI");
                                }
                        }
			break;
		case '+':
			$propbits=explode("+",$property);
			array_shift($propbits);
			$xml.=printCombinedProperty($field,$propbits,$entity);
			break;
		case '&':
			$xml.=printEntityProperty($field,substr($property,1),$entity[$field]);
			break;
		case '@':
			if (substr($property, 1, 1)=="-")  $xml.=printFunctionProperty(substr($property,2),$entity,"","NoDataURI");
			elseif (substr($property, 1, 1)=="&"){
				if (substr($property, 2, 1)=="-") $xml.=printFunctionProperty(substr($property,3),$entity,$type,"NoDataURI");
				elseif (substr($property, 2, 1)=="%") $xml.=printFunctionProperty(substr($property,3),$entity,$type,"EncapsulatedObject");
				else $xml.=printFunctionProperty(substr($property,2),$entity,$type);
			}
			elseif (substr($property, 1, 1)=="%") $xml.=printFunctionProperty(substr($property,2),$entity,"","EncapsulatedObject");
			else{
				$xml.=printFunctionProperty(substr($property,1),$entity,"");
			}
			break;
		case '-':
			$xml.=printEntityProperty($field,$property,$entity[$field],"NoDataURI");
			break;
		default:
                        if (isset($entity[$field])){
				if (isset($datatypes[$property])){
					$xml.=printDatatypeProperty($property,$entity[$field]);
				}
				else{
					$xml.=printEntityProperty($field,$property,$entity[$field],"NoDataURI");
				}	
				break;
			}
		}	
	}
	$xml .= "  </$full_entity>\n\n";
	return $xml;
}

/**
 * @brief Get the ID field value from the database table of a version of a specified versioned entity, (e.g. Workflow, File or Pack).
 *
 * @param $entity
 * An associative array of database fields mapped to values for a versioned entity.
 *
 * @return
 * An integer containing the ID field value from the database table of a version of a specified versioned entity, (e.g. Workflow, File or Pack).
 */
function getVersionID($entity){
        global $entities, $db_entity_mappings;
        if (!empty($db_entity_mappings[$entity['contributable_type']])) {
                $entity['contributable_type'] = $db_entity_mappings[$entity['contributable_type']];
        }
        if (empty($entities[$entity['contributable_type']]['version_entity'])) return;
        $entity_version = $entities[$entities[$entity['contributable_type']]['version_entity']];
        $version_sql = "SELECT id FROM {$entity_version['table']} WHERE {$entity_version['versioned_id']} = {$entity['contributable_id']} AND version = {$entity['contributable_version']}";
        $res = mysqli_query($GLOBALS['con'], $version_sql);
        if (mysqli_num_rows($res) == 0) {
                return "";
        }
        return mysqli_result($res, 0, 'id');
}

/**
 * @brief Generate RDF/XML representing the foaf:homepage and dcterms:hasFormat(s) properties for a specified Non-Information Resource (NIR) URI with a particular entity type.
 *
 * @param $uri
 * A string containing the NIR URI for a particular entity.
 *
 * @param $type
 * A string containing the type of entity for which foaf:homepage and dcterms:hasFormat(s) RDF/XML should be generated.
 *
 * @return
 * A string containing the RDF/XML representing the foaf:homepage and dcterms:hasFormat(s) properties for a specified Non-Information Resource (NIR) URI.
 */
function getHomepageAndFormats($uri, $entity, $id, $type){
	global $datauri, $entities;
	$xml="";
	if (!empty($entities[$type]['homepage'])) $xml .= "    <foaf:homepage rdf:resource=\"${uri}.html\"/>\n";
        if (empty($entities[$type]['no_rdf_uri'])) $xml .= "    <dcterms:hasFormat rdf:resource=\"${uri}.rdf\"/>\n";
	if (!empty($entities[$type]['xml_service'])) {
		$xml_uri = $datauri . $entities[$type]['xml_service'] . ".xml?id=";
		if (isset($entities[$type]['versioned_id'])) {
			$xml_uri .= $entity[$entities[$type]['versioned_id']] . "&amp;version={$entity['version']}";
		}
		else {
			$xml_uri .= $id;
		}
		$xml .= "    <dcterms:hasFormat rdf:resource=\"{$xml_uri}\"/>\n";
	}
	return $xml;
}

/**
 * @brief Write to a local file the RDF/XML to represent the Dataflow for a specified WorkflowVersion.
 *
 * @param $wfvid
 * An integer representing the database table ID field of the WorkflowVersion.
 *
 * @param $ent_uri
 * A string containing the URI of the WorkflowVersion entity.
 *
 * @param $fileloc
 * A string containing the local file location to which the generated RDF/XML should be written.
 * 
 * @param $mime_type
 * A string containing the MIME type of the specified WorkflowVersion, (e.g. application/vnd.taverna.scufl+xml, application/vnd.taverna.t2flow+xml, application/vnd.galaxy.workflow+xml, application/vnd.galaxy.workflow+json or application/vnd.rapidminer.rmp+zip).
 */
function writeDataflowToFile($wfvid,$ent_uri,$fileloc,$mime_type){
        global $myexppath, $rakepath, $rails_env;
        $ph=popen("cd $myexppath; ".$rakepath."rake RAILS_ENV=$rails_env myexp:workflow:components ID=$wfvid | grep -v '^(in' 2>/dev/null",'r');
        $xml="";
        while(!feof($ph)){
                $xml.=fgets($ph);
        }
        fclose($ph);
        $parsedxml=parseXML($xml);
        $dataflows=tabulateDataflowComponents($parsedxml[0]['children'][0]['children'],$ent_uri,$mime_type);
        $fh=fopen($fileloc,'w');
        if ($dataflows) fwrite($fh,generateDataflows($dataflows,$ent_uri));
        else fwrite($fh,"NONE");
        fclose($fh);
}

/**
 * @brief Retrieve the RDF/XML pre-generated for a specified WorkflowVersion's Dataflow.
 *
 * @param $id
 * An integer containing the ID of the WorkflowVersion for which RDF/XML for its Dataflow should be retrieved.
 *
 * @return
 * A string containing the RDF/XML for the Dataflow of the specified WorkflowVersion.  If this is not present the file being read for the RDF/XML will contain the string NONE, which will be returned instead.
 */
function retrieveDataflowRDF($id){
        global $datapath;
        $filename=$datapath."dataflows/$id";
        return implode('',file($filename));
}

/**
 * @brief Test whether based on parts of the path of the URI the entity is a Dataflow or not.
 *
 * @param $params
 * An array containing the parts of the path of the entity's URI, (split on the / character).
 *
 * @return
 * TRUE if one of the parts of the path is dataflow or dataflows (meaning it is a Dataflow), FALSE otherwise.
 */
function isDataflow($params){
        if (in_array('dataflow',$params)) return true;
        if (in_array('dataflows',$params)) return true;
        return false;
}

/**
 * @brief Take an array of Dataflows and map their internal IDs the their URIs.
 *
 * @param $dataflows
 * An associative array of Dataflow, where their URIs are used as array keys.
 *
 * @return
 * An associative array mapping Dataflow internal IDs to their URIs/
 */
function generateDataflowMappings($dataflows){
        $dfmap=array();
        foreach($dataflows as $dfuri => $df) $dfmap[$df['id']]=$dfuri;
        return $dfmap;
}

/**
 * @brief Get the permissions associated with a specified Policy.
 * 
 * @param $policy_id
 * An integer containing the ID of the policy for which permissions are to be found.
 *
 * @return
 * An array containing the permission database records for a specified Policy.
 */
function getPermissions($policy_id){
        $permsql="select * from permissions where policy_id=".$policy_id;
        $permres=mysqli_query($GLOBALS['con'], $permsql);
        $perms=array();
        for ($p=0; $p<mysqli_num_rows($permres); $p++){
                $perms[$p]=mysqli_fetch_array($permres);
        }
        return $perms;
}

/**
 * @brief Get the share and update modes for the Policy of a specified Contribution.
 * 
 * @param $contrib
 * An associative array containing the Contribution whose's Policy should be queried for its share and update modes.
 *
 * @return
 * An associative array containing the Contribution with additional fields specifying the share and update modes.
 */
function addShareAndUpdateMode($contrib){
        $policysql="select share_mode, update_mode from policies where id =".$contrib['policy_id'];
        $pres=mysqli_query($GLOBALS['con'], $policysql);
        $contrib['share_mode']=mysqli_result($pres,0,'share_mode');
        $contrib['update_mode']=mysqli_result($pres,0,'update_mode');
        return $contrib;
}

/**
 * @brief Determine whether the User requesting RDF has permission to download a specific downloadable entity.
 * 
 * @param $entity
 * An associative array containing the entity, which needs to have determined whether it can be downloaded by the RDF requesting User.
 *
 * @return
 * A boolean.  TRUE if the RDF requesting User can download the entity, FALSE otherwise.
 */
function canUserDownload($entity){
        global $rdfgen_userid, $use_rake;
        if (!$use_rake) return TRUE;
        if ($entity['contributor_type'] != 'User') return FALSE;
        if ($rdfgen_userid == $entity['contributor_id']) return TRUE;
        elseif ($entity['share_mode'] == 0) return TRUE;
        elseif (in_array($entity['share_mode'],array(1,3))){
                $friendship_sql = "SELECT * FROM friendships WHERE accepted_at IS NOT NULL AND (user_id = $entity[contributor_id] OR friend_id = $entity[contributor_id]) AND (user_id = $rdfgen_userid OR friend_id = $rdfgen_userid)";  
                $res = mysqli_query($GLOBALS['con'], $friendship_sql);
                if (mysqli_num_rows($res2)>0) return TRUE;
        }                       
        else{
                $membership_sql = "SELECT * FROM memberships WHERE network_id IN (SELECT contributor_id FROM permissions WHERE policy_id = ".$entity['policy_id']." AND contributor_type = 'Network' AND download = 1) AND user_id = $rdfgen_userid";
                $res = mysqli_query($GLOBALS['con'], $membership_sql);
                if (mysqli_num_rows($res)>0) return TRUE;
        }
        return FALSE;
}

/**
 * @brief Generate RDF/XML for a property by combining two or more database fields.
 * 
 * E.g. 'contributor_type'=&gt;'+contributor_id|sioc:has_owner','content_type_id'  --&gt;  &lt;sioc:has_owner rdf:resource="&lt;datauri&gt;/&lt;contributor_type&gt;/&lt;contributor_id&gt;"/&gt;
 *
 * @param $field 
 * A string containing the primary field used to build the combined property.
 *
 * @param $property
 *
 * @param $entity
 *
 * @return
 * A string representing the specified RDF property containing a URI reference to a particular entity.
 */
function printCombinedProperty($field,$property,$entity){
	global $datauri, $entities, $db_entity_mappings;
	$url_subpath = $entities[$db_entity_mappings[$entity[$field]]]['url_subpath'];
	$pbits=explode('|',$property[sizeof($property)-1]);
	if (sizeof($property)>1)$uri=$datauri.$url_subpath."/".$entity[$property[0]];
	else $uri=$datauri.$url_subpath."/".$entity[$pbits[0]];
	if (!empty($entity[$pbits[0]]) && preg_match('/workflow_version/',$pbits[0])){
		$uri=$datauri."workflows/".$entity[$field]."/versions/".$entity[$pbits[0]];
	}
	$nsandp=$pbits[1];
	$line="    <$nsandp rdf:resource=\"$uri\"/>\n";
	return $line;
}

/**
 * @brief Generates RDF/XML for a property by builidng a URI from optionally prepending $datauri and the URL subpath for the entity type before the value for the specified database field.
 * 
 * E.g. 'user_id'=>'&User|mebase:has-announcer'  --&gt;  <mebase:has-announcer rdf:resource="&lt;datauri&gt;users/&lt;user_id&gt;"/>
 *
 * @param $field
 * A string containing the name of the database field from which the value is provided.
 *
 * @param $property
 * A string containing the RDF property name and if required the type of entity for which the URI is being generated.
 * 
 * @param $value
 * A string containing the value that makes up the final part of the URI being generated as part of the RDF property.
 *
 * @param $msg
 * A string optionally containing comma-separated messages about any special processing for generating the RDF property.  In particular NoDataURI which uses $value as the full URI rathering than prepending $datauri or the entity's URL subpath.
 *
 * @return
 * A string containing the RDF/XML for the specified property including the URI reference to a particular instance.
 */
function printEntityProperty($field, $property, $value, $msg="") {
	global $datauri, $entities, $db_entity_mappings;
	$msgs=explode(",",$msg);
	if ($value){
		$pbits=explode('|',$property);
		if (in_array("NoDataURI",$msgs)){
			$uri=$value;
			$nsandp=$pbits[0];
		}
		else{
			$url_subpath=$entities[$pbits[0]]['url_subpath'];
			$uri=$datauri.$url_subpath."/".$value;
			$nsandp=$pbits[1];
		}
		$uri=str_replace("&","&amp;",$uri);
		$line="    <$nsandp rdf:resource=\"$uri\"/>\n";
		return $line;
	}
}

/**
 * @brief Generates RDF/XML for a property by building the value of the property by calling a particular function.
 *
 * E.g. 'requester'=&gt;'&#64;getRequester|mebase:has-requester'  --&gt;  &lt;mebase:has-requester rdf:resource="&lt;datauri&gt;&lt;returned_by_function_call&gt;  (Where &lt:returned_by_function_call&gt; may be something like "users/16."
 * 
 * @param $property
 * A string containing the RDF property name.
 *
 * @param $entity
 * An array containing the results from a database query for the entity for which the RDF/XML property is being generated.
 *
 * @param $type
 * A string containing the type of entity for which the RDF/XML property is being generated.
 *
 * @param $msg
 * A string optionally containing comma-separated messages about any special processing for generating the RDF property.  In particular NoDataURI which uses whatever is returned from the function as the full URI rathering than prepending $datauri or the entity's URL subpath. Or EncapsulatedObject which encapsulates whatever is returned from the function between start and end tags for the property (rather than assuming it is a URI).
 * 
 * @return
 * A string containing the RDF/XML of the specified property and either a URI reference to an entity or some additional RDF/XML generated by a separate function.
 */
function printFunctionProperty($property, $entity, $type="", $msg=""){
	global $datauri, $datatypes;
	$pbits=explode('|',$property);
	if (isset($msg) && is_string($msg)>0) $msgs=explode(",",$msg);
	else $msgs=array();
	if (!empty($type)) $value=call_user_func($pbits[0], $entity, $type);
	else $value=call_user_func($pbits[0], $entity);
	if (empty($pbits[1])) return $value;
	elseif (in_array("EncapsulatedObject",$msgs)){
		$nsandp=$pbits[1];
		$line="";
		if (!empty($value)){
			$line="    <$nsandp>\n      $value\n    </$nsandp>\n";
		}
		return $line;
	}
	elseif (array_key_exists($pbits[1],$datatypes) && $datatypes[$pbits[1]]!=""){
		$fh="";
		return printDatatypeProperty($pbits[1],$value,$fh);
	}
	else{
		if (in_array("NoDataURI",$msgs)) $uri=$value;
		else $uri=$datauri.$value;
		$nsandp=$pbits[1];
		if ($uri) return "    <$nsandp rdf:resource=\"$uri\"/>\n";
		return;
	}
}	

/**
 * @brief Generates RDF/XML for a property with an XML datatype.
 * 
 * E.g.  'created_at'=>'dcterms:created'  --&gt;  &gt;dcterms:created rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime"&gt;2009-03-05T21:00:03Z&lt;/dcterms:created&lt;
 *
 * @param $property
 * A string containing the name (e.g. dcterms:created) of the RDF property to be generated.
 *
 * @param $value
 * A string containing the value to be used for the RDF property.
 *
 * @return 
 * a string containing the RDF/XML of the datatyped property encapsulating the value provided appropriately formatted.
 */
function printDatatypeProperty($property,$value){
	global $datatypes;
	$value=convertToXMLEntities($value);
	$line="";
	if (($datatypes[$property]=="nonNegativeInteger" || $datatypes[$property]=="boolean") && !$value) $value="0";
	elseif ($datatypes[$property]=="dateTime"){
		$value=str_replace(" ","T",$value);
		if ($value) $value.="Z";
	}
	$nsandp=$property;			
	if ($datatypes[$property]!="base64Binary"){
		if ($value || $datatypes[$property]=="boolean" || $datatypes[$property]=="nonNegativeInteger"){
			$line="    <$nsandp"; 
			if ($datatypes[$property]) $line.=" rdf:datatype=\"&xsd;".$datatypes[$property]."\"";
			$line.=">".trim($value)."</$nsandp>\n";
		}
		if (($nsandp=="mevd:viewed" || $nsandp=="mevd:downloaded") && $value==0) $line="";
	}
	else{
		$line="    <$nsandp"; 
		if ($datatypes[$property]) $line.=" rdf:datatype=\"&xsd;".$datatypes[$property]."\"";
		$line.=">".trim(base64_encode($value))."</$nsandp>\n";
	}
	return $line;
}

/**
 * @brief Generate the header for an RDF/XML document based on a specified set of namespaces.
 *
 * @param $namespaces
 * An associative array of prefix to namespace mappings for namespaces that need to be defined in the RDF/XML document header string to be returned.
 *
 * @return
 * A string containing the header for an RDF/XML document for a specific set of namespaces.
 */
function generateSpecificRDFHeader($namespaces){
       	$header="<?xml version=\"1.0\" encoding=\"UTF-8\" ?>

<!DOCTYPE rdf:RDF [\n";
        foreach ($namespaces as $ent => $ns){
       	        $header.=" <!ENTITY $ent '$ns'>\n";
        }
       	$header.="]>\n\n<rdf:RDF ";
        $ents=array_keys($namespaces);
       	for ($e=0; $e<sizeof($ents); $e++){
               	$header.="xmlns:$ents[$e]\t=\"&$ents[$e];\"\n";
                if ($e<sizeof($ents)-1) $header.="\t";
       	}
        $header.=">\n";
        return $header;
}

/**
 * @brief Generate the header for an RDF/XML document including only those namespaces required when defining a generic OWL ontology.
 * 
 * @param $ontology
 * A associative array of the ontology entity containing its URI and prefix, so the RDF/XML document header can define this along with the other required namespaces.
 * 
 * @return
 * A string containing the header for an RDF/XML document for those namespaces required in defining a generic OWL ontology.
 */
function generateOntologyRDFHeader($ontology){
	global $namespace_prefixes;
	foreach (array('rdf', 'rdfs', 'owl', 'dc', 'dcterms', 'xsd') as $prefix) {
		$pageheader_namespaces[$prefix] = $namespace_prefixes[$prefix];
	}
	$pageheader_namespaces[$ontology['prefix']]=$ontology['uri']."/";
	return generateSpecificRDFHeader($pageheader_namespaces);
}	

/**
 * @brief Generate the header for an RDF/XML document including those namespaces required when defining all myExperiment entities.
 * 
 * @param $namespaces
 * An associative array of prefix to namespace mappings for additional namespaces that need to be defined in the RDF/XML document header that are not part of the generic set of namespaces used by myExperiment entities. (I.e. not part of the set of all the myExperiment ontology modules, all the ontologies/schemas reused by the myExperiment ontology and the namespaces required to define a generic OWL ontology)
 * 
 * @return
 * A string containing the header for an RDF/XML document required to define all myExperiment entities.
 */
function generateGenericRDFHeader($namespaces=array()){
	global $namespace_prefixes;
	foreach (array('mebase', 'meac', 'meannot', 'mepack', 'meexp', 'mecontrib', 'mevd', 'mecomp', 'mespec', 'rdf', 'rdfs', 'owl', 'dc', 'dcterms', 'cc', 'foaf', 'sioc', 'skos', 'ore', 'dbpedia', 'snarm', 'xsd') as $prefix) {
		$namespaces[$prefix] = $namespace_prefixes[$prefix];
	}
        return generateSpecificRDFHeader($namespaces);
}

/**
 * @brief Generate the footer for an RDF/XML document. (I.e. close the rdf:RDF tag).
 *
 * @return
 * A string containing the footer for an RDF/XML document. (I.e. &lt;/rdf:RDF&gt;).
 */
function generateRDFFooter(){
       	return "</rdf:RDF>";
}

/**
 * @brief Generate an RDF description entity to describe an RDF/XML document for an entity with a specified URI.
 * 
 * @param $uri
 * A string containing the URI of the entity for which an RDF description entity is required to describe the original entity's RDF/XML document representation.
 * 
 * @return
 * A string containing the RDF description entity describing the RDF/XML document representation of a specific entity.
 */
function generateRDFFileDescription($uri){
	return "  <rdf:Description rdf:about=\"$uri.rdf\">\n    <foaf:primaryTopic rdf:resource=\"$uri\"/>\n  </rdf:Description>\n\n";
}
