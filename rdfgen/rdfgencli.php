#!/usr/bin/php
<?php 
//	ini_set('display_errors', 1);
//	error_reporting(E_ALL);
	include('include.inc.php');
	require('genrdf.inc.php');
	function setTypeIDandParams($args,$noexit=0){
		global $nesting, $rdfgen_userid, $tables;
		$rdfgen_userid=0;
		if (isset($args[2])){
			$type=$args[1];
			$id=$args[2];
		}
		else exit("Not enough arguments!\n");
		$params = array();
		if (in_array($type, array("files", "packs", "workflows"))){
			if (isset($args[4])){
				$rdfgen_userid = $args[4];
				$params = $params=explode("/",$args[3]);
			}
			elseif (isset($args[3])) {
				$paramstemp = explode("/",$args[3]);
				if ($paramstemp[0] =='versions') $params = $paramstemp;
				else $rdfgen_userid = $args[3];
			}
		}
		elseif (isset($args[3])) $params=explode("/",$args[3]);
		$primary_id='0';
		if (isset($params[0]) and strlen($params[0])>0){
			if (sizeof($params)>1 && isset($nesting[$params[sizeof($params)-2]])){
				$type=$params[sizeof($params)-2];
				$id=$params[sizeof($params)-1];
				$params=array();
         		}
			elseif ($params[0]=="versions") {
				switch($type) {
					case "files":
                                                $type = "file_versions";
                                                break;
					case "packs":
						if (isset($params[2]) && $params[2]=="relationships") {
							$type="pack_relationships";
                                			$id=$params[3];
						}
						else {
                                                	$type = "pack_versions";
						}
                                                break;
					case "workflows":
						$type = "workflow_versions";
						break;
				}
				$typebits=explode('_', $tables[$type]);
				if ($typebits[1]=="versions") {
					$version_sql="select id from {$tables[$type]} where {$typebits[0]}_id=$id and version=$params[1]";
					$version_res=mysql_query($version_sql);
					$primary_id=$id;
					$id=mysql_result($version_res,0,'id');
				}
			}
			elseif ($params[0]=="announcements" && $type=="groups"){
				$type="group_announcements";
				$id=$params[1];
			}
                        elseif ($params[0]=="relationships" && $type=="packs"){
                                $type="pack_relationships";
                                $id=$params[1];
                        }	
			elseif (!$noexit){
				exit();
			}
		}
		return array($type,$id,$params,$primary_id);
	}
	function getEntityResults($type,$id){
		global $tables, $sql;
		if ($id){
			$whereclause=$tables[$type].".id=$id";
                        if (stripos($sql[$type],"where") === false ){
                           $cursql=$sql[$type]." where ".$whereclause;
                        }
                        else $cursql=$sql[$type]." and ".$whereclause;
                }
                else $cursql=$sql[$type];
                return mysql_query($cursql);
	}
	if (sizeof($argv)>5) exit("Too many arguments!\n");
	list($type,$id,$params,$primary_id)=setTypeIDandParams($argv);
	if (entityExists($type,$id)){
	        $res=getEntityResults($type,$id);
		$row=mysql_fetch_assoc($res);
		$uri=getEntityURI($type,$id,$row);
		if (mysql_num_rows($res)>0) mysql_data_seek($res,0);
		$e=1;
		if ($type=="ontologies" && $id){
			$xml=ontologypageheader($row);
		}
		else{
			$xml=pageheader();
			if ($id) $xml.=rdffiledescription($uri);
		}	
		for ($e=0; $e<mysql_num_rows($res); $e++){
       			$xml.=printEntity(mysql_fetch_assoc($res),$type);
	       	}
		if ($e==1 && $type=="ontologies"){
			$xml.=printPredicates($id);
		}
		elseif ($e==1 && $type=="predicates"){
			$ontres=getEntityResults("ontologies",$row['ontology_id']);
			$xml.=printEntity(mysql_fetch_assoc($ontres),"ontologies");
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
					$mbits=explode('/',$mhbits[0]);
					if (in_array("previews",$mbits) && (in_array("full",$mbits)||in_array("medium",$mbits)||in_array("thumb",$mbits)||in_array("svg",$mbits))) continue;
					elseif (strpos($m,'.') === false && $mbits[0] && isset($sql[$mbits[0]]) && $datauri.$m != $uri){
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
							if (isset($type)){
								$res=getEntityResults($type,$id);	
								$xml.=printEntity(mysql_fetch_assoc($res),$type);	
							}
						}
					}
					elseif ($datauri.$m != $uri){
						$onturl=$datauri;
						for ($i=0;$i<sizeof($mbits)-1;$i++) $onturl.=$mbits[$i]."/";
						$onturl=substr($onturl,0,-1);
						$predsql=$sql['predicates'];
						if (stripos("where",$predsql)>0) $predsql.=" and ";
						else $predsql.=" where ";
						$predsql.="ontologies.uri=\"$onturl\" and predicates.title=\"".$mbits[sizeof($mbits)-1]."\"";
						$res=mysql_query($predsql);
						if ($res && mysql_num_rows($res)>0) $xml.=printEntity(mysql_fetch_assoc($res),'predicates');
					}
				}
			}
		}
  		$xml.=pagefooter();
		header('Content-type: application/rdf+xml');
		echo $xml;
	}
//	echo "Entity - type:$type id:$id does not exist";
?>
