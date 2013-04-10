#!/usr/bin/php
<?php 
/**
 * @file rdfgen/rdfgencli.php
 * @brief Command line utility for generation MyExperiment RDF for any specified myExperiment entity.
 * @version beta
 * @author David R Newman
 * @details This script provides a command line utility to generate RDF/XML for any myExperiment entity.  This utility can be invoked using the following command:
 *
 * ./rdfgencli.php <primary_entity_type> <primary_entity_id> [nested_entity_path_and_id] [myexperiment_user_id_requesting_rdf]
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

if (sizeof($argv)>5) exit("Too many arguments!\n");
list($type,$id,$params,$primary_id)=setTypeIDandParams($argv);
if (entityExists($type,$id)){
        $res=getEntityResults($type,$id);
	$row=mysql_fetch_assoc($res);
	$uri=getEntityURI($type,$id,$row);
	if (mysql_num_rows($res)>0) mysql_data_seek($res,0);
	$e=1;
	if ($type=="Ontology" && $id){
		$xml=ontologypageheader($row);
	}
	else{
		$xml=pageheader();
		if ($id) $xml.=rdffiledescription($uri);
	}	
	for ($e=0; $e<mysql_num_rows($res); $e++){
		$xml.=printEntity(mysql_fetch_assoc($res),$type);
       	}
	if ($e==1 && $type=="Ontology"){
		$xml.=printPredicates($id);
	}
	elseif ($e==1 && $type=="Predicate"){
		$ontres=getEntityResults("Ontology",$row['ontology_id']);
		$xml.=printEntity(mysql_fetch_assoc($ontres),"Ontology");
	}
	elseif ($e==1){
		$regex=$datauri.'[^<>]+>';
		preg_match_all("!$regex!",$xml,$matches);
		foreach ($matches as $m => $match){
			$matches[$m]=str_replace(array(" ","\n","\t"),array("","",""),$match);
		}
	        $matches=array_unique($matches[0]);
		foreach($matches as $m){
			if (strpos($m,'/>')>0){
				$mtmp=explode('"',$m);
				$m=str_replace($datauri,"",$mtmp[0]);
				$mhbits=explode('#',$m);
				if (isset($mhbits[1])) $posthash=$mhbits[1];
				else $posthash="";
				$entity_type = "";
				$mbits=explode('/',$mhbits[0]);
				if (!empty($path_entity_mappings[$mbits[0]])) $entity_type = $path_entity_mappings[$mbits[0]];
				if (in_array("previews",$mbits) && (in_array("full",$mbits)||in_array("medium",$mbits)||in_array("thumb",$mbits)||in_array("svg",$mbits))) continue;
				elseif (strpos($m,'.') === false && !empty($entity_type) && isset($sql[$entity_type]) && $datauri.$m != $uri){
					if ($posthash){
						$primary_id=$mbits[1];
                                      	        $version=$mbits[3];
                                               	$id=getWorkflowVersion($primary_id,$version);
                       	                        $xml.=extractRDF($id,$primary_id,$version,$posthash)."\n";
					}
					else{
						$args[1]=array_shift($mbits);
						$args[2]=array_shift($mbits);
						$args[3]=implode('/',$mbits);
						$args[4]=1;
						list($type,$id,$params,$primary_id)=setTypeIDandParams($args,true);
						if (!empty($type)){
							$res=getEntityResults($type,$id);	
							$xml .= printEntity(mysql_fetch_assoc($res), $type);
						}
						
					}
				}
				elseif ($datauri.$m != $uri){
					$onturl=$datauri;
					for ($i=0;$i<sizeof($mbits)-1;$i++) $onturl.=$mbits[$i]."/";
					$onturl=substr($onturl,0,-1);
					$predsql=$sql['Predicate'];
					if (stripos("where",$predsql)>0) $predsql.=" and ";
					else $predsql.=" where ";
					$predsql.="ontologies.uri=\"$onturl\" and predicates.title=\"".$mbits[sizeof($mbits)-1]."\"";
					$res=mysql_query($predsql);
					if ($res && mysql_num_rows($res)>0) $xml.=printEntity(mysql_fetch_assoc($res),'Predicate');
				}
			}
		}
	}
  	$xml.=pagefooter();
	header('Content-type: application/rdf+xml');
	echo $xml;
}
else error_log("type: $type, id: $id does not exist");
?>
