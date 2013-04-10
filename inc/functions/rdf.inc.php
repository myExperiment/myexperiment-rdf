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
		
function setTypeIDandParams($args,$noexit=0){
        global $entities, $path_entity_mappings, $rdfgen_userid;
 	$params = array();
        $rdfgen_userid = 0;
	$primary_id = 0;
        if (isset($args[2])){
                $type=$path_entity_mappings[$args[1]];
                $id=$args[2];
        }
        else exit("Not enough arguments!\n");
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
		$sub_entity = $params[sizeof($params)-2];
		$sub_entity_num = $params[sizeof($params)-1];
                if ($sub_entity =="versions") {	
			$type = $entities[$type]['version_entity'];
                        if (!empty($entities[$type]['parent_entity'])) {
                                $version_sql="select id from {$entities[$type]['table']} where {$entities[$type]['parent_id']}=$id and version=$sub_entity_num";
                                $version_res=mysql_query($version_sql);
                                $primary_id=$id;
				if (mysql_num_rows($version_res) == 0) {
					return array(null,null,null,null);
				}
                                $id=mysql_result($version_res,0,'id');
                        }
                }
		elseif (isset($entities[$type]['child_url_subpaths'][$sub_entity])) {
			$type = $entities[$type]['child_url_subpaths'][$sub_entity];
			$id=$sub_entity_num;
		}
                elseif (!$noexit){
                        exit();
                }
        }
        return array($type,$id,$params,$primary_id);
}

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
        return mysql_query($cursql);
}

function entityExists($type,$ids=array()){
        global $entities;
        if (!isset($entities[$type])) return 0;
        if (!is_array($ids)) $ids=array($ids);
        $cursql="select * from ".$entities[$type]['table'];
        if (sizeof($ids)>0 && $ids[0]>0){
                $idstr="";
                foreach($ids as $id) $idstr.="$id,";
                $cursql.=" where id in (".substr($idstr,0,-1).")";
                if ($type=="Comment") $cursql.=" and commentable_type in ('Workflow','Blob','Pack','Network')";
        }
        else{
                $res=mysql_query($cursql);
                if ($res) return 1;
        }
        $res=mysql_query($cursql);
        if ($res===false) return 0;
        return mysql_num_rows($res);
}


	function printEntity($row, $type){
		global $datauri, $domain, $datatypes, $entities, $mappings; 
		//error_log(print_r($row,1). "type: $type");
		$template = $mappings[$type];
		if (!is_array($template)) return "";
		$idfield = array_search('url',$template);
		$id = $row[$idfield];
		unset($template[$idfield]);
		$ontology_prefix = $entities[$type]['ontology_prefix'];
		$full_entity = "$ontology_prefix:$type";
		$uri = getEntityURI($type, $id ,$row);	
		$xml = "  <$full_entity rdf:about=\"$uri\">\n";
		$xml .= getHomepageAndFormats($uri, $type, $id, $row);
		foreach ($template as $field => $property) {
			switch (substr($property, 0, 1)) {
			case '<':
				if (!isset($row[$field])) break;
				$row[$field]=xmlentities($row[$field]);
				if (isset($row[$field])){
                                        if (isset($datatypes[substr($property, 1)])){
                                                $xml.=printDatatypeProperty(substr($property, 1),$row[$field]);
                                        }
                                        else{
                                                $xml.=printEntityProperty($field,substr($property, 1),$row[$field],"NoDataURI");
                                        }
                                        break;
                                }
				break;
			case '+':
				$propbits=explode("+",$property);
				array_shift($propbits);
				$xml.=printCombinedProperty($field,$propbits,$row);
				break;
			case '&':
				$xml.=printEntityProperty($field,substr($property,1),$row[$field]);
				break;
			case '@':
				if (substr($property, 1, 1)=="-")  $xml.=printFunctionProperty(substr($property,2),$row,"","NoDataURI");
				elseif (substr($property, 1, 1)=="#"){
					if ($domain!="private"){
					 	$xml.=printFunctionProperty(substr($property,2),$row,"","Hash");
					}
					else{
						$xml.=printFunctionProperty(substr($property,2),$row,"");
					}
				}
				elseif (substr($property, 1, 1)=="&"){
					if (substr($property, 2, 1)=="-") $xml.=printFunctionProperty(substr($property,3),$row,$type,"NoDataURI");
					elseif (substr($property, 2, 1)=="%") $xml.=printFunctionProperty(substr($property,3),$row,$type,"EncapsulatedObject");
					else $xml.=printFunctionProperty(substr($property,2),$row,$type);
				}
				elseif (substr($property, 1, 1)=="%") $xml.=printFunctionProperty(substr($property,2),$row,"","EncapsulatedObject");
				else{
					$xml.=printFunctionProperty(substr($property,1),$row,"");
				}
				break;
			case '!':
				break;
			default:
                                if (isset($row[$field])){
					if (isset($datatypes[$property])){
						$xml.=printDatatypeProperty($property,$row[$field]);
					}
					else{
						$xml.=printEntityProperty($field,$property,$row[$field],"NoDataURI");
					}	
					break;
				}
			}	
		}
		$xml .= "  </$full_entity>\n\n";
		return $xml;
	}

function getHomepageAndFormats($uri,$type,$id,$entity=''){
	global $entities;
	$xml="";
	if (!empty($entities[$type]['homepage'])) $xml .= "    <foaf:homepage rdf:resource=\"${uri}.html\"/>\n";
        if (empty($entities[$type]['no_rdf_uri'])) $xml .= "    <dcterms:hasFormat rdf:resource=\"${uri}.rdf\"/>\n";
	if (!empty($entities[$type]['xml_service'])) $xml .= "    <dcterms:hasFormat rdf:resource=\"{$uri}.xml\"/>\n";
	return $xml;
}

function getWorkflowVersion($wfid,$version){
        $wfvsql="select id from workflow_versions where workflow_id=$wfid and version=$version";
        $res=mysql_query($wfvsql);
        return mysql_result($res,0,'id');
}

function getDatatypes(){
        global $lddir;
        $dtfile=file($lddir.'inc/config/datatypes.txt');
        foreach ($dtfile as $dt){
                $dtbits = explode(" ",$dt);
                $datatypes[trim($dtbits[0])]=trim($dtbits[1]);
        }
        return $datatypes;
}

function isDataflow($params){
        if (in_array('dataflow',$params)) return true;
        if (in_array('dataflows',$params)) return true;
        return false;
}

function getPermissions($policy){
        $permsql="select * from permissions where policy_id=".$policy;
        $permres=mysql_query($permsql);
        $perms=array();
        for ($p=0; $p<mysql_num_rows($permres); $p++){
                $perms[$p]=mysql_fetch_array($permres);
        }
        return $perms;
}

function addShareAndUpdateMode($contrib){
        $policysql="select share_mode, update_mode from policies where id =".$contrib['policy_id'];
        $pres=mysql_query($policysql);
        $contrib['share_mode']=mysql_result($pres,0,'share_mode');
        $contrib['update_mode']=mysql_result($pres,0,'update_mode');
        return $contrib;
}


	function printUsage($type, $id, $views, $downloads){
		global $datauri, $entities;
		return "  <rdf:Description rdf:about=\"$datauri{$entities[$type]['url_subpath']}/$id\">
    <mevd:viewed rdf:datatype=\"http://www.w3.org/2001/XMLSchema#nonNegativeInteger\">$views</mevd:viewed>
    <mevd:downloaded rdf:datatype=\"http://www.w3.org/2001/XMLSchema#nonNegativeInteger\">$downloads</mevd:downloaded>
  </rdf:Description>\n\n";
	}	
		
	function printCombinedProperty($field,$property,$row){
		global $datauri, $entities; 
		$url_subpath = $entities[$row[$field]]['url_subpath'];
		$pbits=explode('|',$property[sizeof($property)-1]);
		if (sizeof($property)>1)$uri=$datauri.$url_subpath."/".$row[$property[0]];
		else $uri=$datauri.$url_subpath."/".$row[$pbits[0]];
		if (!empty($row[$pbits[0]]) && preg_match('/workflow_version/',$pbits[0])){
			$uri=$datauri."workflows/".$row[$field]."/versions/".$row[$pbits[0]];
		}
		$nsandp=$pbits[1];
		$line="    <$nsandp rdf:resource=\"$uri\"/>\n";
		return $line;
	}
	function printEntityProperty($field, $property, $value, $msg="") {
		global $datauri, $entities;
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
	function printFunctionProperty($property, $row, $entity_type="", $msg=""){
		global $datauri, $datatypes;
		$pbits=explode('|',$property);
		if (isset($msg) && is_string($msg)>0) $msgs=explode(",",$msg);
		else $msgs=array();
		if (in_array('hide',$msgs) && $pbits[0]=="getOREAggregatedResources") return;
		if ($entity_type) $value=call_user_func($pbits[0], $row, $entity_type);
		elseif (in_array('Hash',$msgs)){
			$value=call_user_func($pbits[0],$row,1);
		}
		else $value=call_user_func($pbits[0],$row);
		if (!isset($pbits[1]) || !$pbits[1]) return $value;
		elseif (in_array("EncapsulatedObject",$msgs)){
			$nsandp=$pbits[1];
			$line="";
			if ($value){
				$line="    <$nsandp>\n      $value\n    </$nsandp>\n";
			}
			return $line;
		}
		elseif (array_key_exists($pbits[1],$datatypes) && $datatypes[$pbits[1]]!=""){
			$fh="";
			return printDatatypeProperty($pbits[1],$value,$fh);
		}
		elseif ($pbits[0]=="getComponentsAsResources" || $pbits=="getWorkflowVersions" ) return $value;
		else{
			if (in_array("NoDataURI",$msgs)) $uri=$value;
			else $uri=$datauri.$value;
			$nsandp=$pbits[1];
			if ($uri) return "    <$nsandp rdf:resource=\"$uri\"/>\n";
			return;
		}
	}	
	function printDatatypeProperty($property,$value){
		global $datatypes;
		$value=xmlentities($value);
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
			//else echo $property."1";
			$line.=">".trim(base64_encode($value))."</$nsandp>\n";
		}
		return $line;
	}

	function getUsedTagsFromTaggings($wherestat){
		$sql="select tag_id from taggings ".$wherestat;
		$res=mysql_query($sql);
		$tagid=array();
		for ($t=0; $t<mysql_num_rows($res); $t++){
			$tagid[mysql_result($res,$t,'tag_id')]++;
		}
		return array_keys($tagid);
	}

	function varpageheader($namespaces){
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
	function ontologypageheader($ontology){
		$namespaces['rdf']='http://www.w3.org/1999/02/22-rdf-syntax-ns#';
                $namespaces['rdfs']='http://www.w3.org/2000/01/rdf-schema#';
                $namespaces['owl']='http://www.w3.org/2002/07/owl#';
                $namespaces['dc']='http://purl.org/dc/elements/1.1/';
                $namespaces['dcterms']='http://purl.org/dc/terms/';
                $namespaces['xsd']='http://www.w3.org/2001/XMLSchema#';
		$namespaces[$ontology['prefix']]=$ontology['uri']."/";
		return varpageheader($namespaces);
	}	

	function pageheader($namespaces=array()){
		global $ontopath;
                $namespaces['mebase']=$ontopath.'base/';
                $namespaces['meac']=$ontopath.'attrib_credit/';
                $namespaces['meannot']=$ontopath.'annotations/';
                $namespaces['mepack']=$ontopath.'packs/';
                $namespaces['meexp']=$ontopath.'experiments/';
                $namespaces['mecontrib']=$ontopath.'contributions/';
                $namespaces['mevd']=$ontopath.'viewings_downloads/';
                $namespaces['mecomp']=$ontopath.'components/';
                $namespaces['mespec']=$ontopath.'specific/';
        	$namespaces['rdf']='http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	        $namespaces['rdfs']='http://www.w3.org/2000/01/rdf-schema#';
		$namespaces['owl']='http://www.w3.org/2002/07/owl#';
        	$namespaces['dc']='http://purl.org/dc/elements/1.1/';
	        $namespaces['dcterms']='http://purl.org/dc/terms/';
	        $namespaces['cc']='http://creativecommons.org/ns#';
        	$namespaces['foaf']='http://xmlns.com/foaf/0.1/';
	        $namespaces['sioc']='http://rdfs.org/sioc/ns#';
		$namespaces['skos']='http://www.w3.org/2004/02/skos/core#';
        	$namespaces['ore']='http://www.openarchives.org/ore/terms/';
		$namespaces['dbpedia']='http://dbpedia.org/ontology/';
        	$namespaces['snarm']=$ontopath.'snarm/';
	        $namespaces['xsd']='http://www.w3.org/2001/XMLSchema#';
        	return varpageheader($namespaces);
	}
	function pagefooter(){
        	return "</rdf:RDF>";
	}
	function rdffiledescription($url){
		return "  <rdf:Description rdf:about=\"$url.rdf\">\n    <foaf:primaryTopic rdf:resource=\"$url\"/>\n  </rdf:Description>\n\n";
	}


?>
