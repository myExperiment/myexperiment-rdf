<?php
/**
 * @file inc/functions/property.inc.php
 * @brief Functions for generating the property/values pairs for myExperiment RDF entities.
 * @version beta
 * @author David R Newman
 * @details Functions for generating the property/value pairs for myExperiment RDF entities, as specified by the $mappings associative array in inc/config/data.inc.php.
 */

function getEntityURI($type,$id,$entity){
	global $datauri, $entities, $db_entity_mappings;
	if (empty($type) || empty($id)) {
		return "";
	}
	$entity_type = $entities[$type];
	$url_subpath = $entity_type['url_subpath'];
	if (isset($entity_type['nested_on'])){
	 	switch ($type){
			case 'Attribution':
			case 'Comment':
                        case 'Creditation':
			case 'Rating':
			case 'Review':
                        case 'Policy':
				$nesting_entity = $entity[$entity_type['nested_on'][0]];
				if (!empty($db_entity_mappings[$nesting_entity])) $nesting_entity = $db_entity_mappings[$nesting_entity];
				return $datauri . $entities[$nesting_entity]['url_subpath'] . "/". $entity[$entity_type['nested_on'][1]] . "/$url_subpath/$id";
			case 'Citation': 
				return $datauri."workflows/".$entity[$entity_type['nested_on'][0]]."/versions/".$entity[$entity_type['nested_on'][1]]."/$url_subpath/$id";
			case 'Favourite':
			case 'Friendship':
			case 'Membership':
				return $datauri."users/".$entity[$entity_type['nested_on'][0]]."/$url_subpath/$id";
			case 'Job':
				return $datauri."experiments/".$entity[$entity_type['nested_on'][0]]."/$url_subpath/$id";
			case 'LocalPackEntry':
			case 'RemotePackEntry':
			case 'RelationshipEntry':
				return $datauri."packs/".$entity[$entity_type['nested_on'][0]]."/$url_subpath/$id";
			case 'Tagging':
				return $datauri."tags/".$entity[$entity_type['nested_on'][0]]."/$url_subpath/$id";
			case 'Ontology':
                                return $entity['uri'];
			case 'Predicate':
				return $entity['ontology_uri']."/".$entity['title'];
			default:
				break;
		}
	}
	elseif (isset($entity['contributable_version'])) {
		return "{$datauri}{$url_subpath}/{$id}/versions/{$entity['contributable_version']}";
	}
	elseif (isset($entity_type['parent_id'])) {
		$version_sql = "SELECT {$entity_type['parent_id']} AS parent_id, version FROM {$entity_type['table']} WHERE id = $id";
		$version_res = mysql_query($version_sql);
		if (mysql_num_rows($version_res) == 0) {
			return "";
		}
		$version = mysql_fetch_assoc($version_res);
		return $datauri . $entities[$entity_type['parent_entity']]['url_subpath'] . "/" . $version['parent_id'] . "/versions/" . $version['version'];
        }
        elseif ($type=="GroupAnnouncement"){
   	 	$gasql="select network_id from group_announcements where id=$id";
                $gares=mysql_query($gasql);
                return $datauri."groups/".mysql_result($gares,0,'network_id')."/announcements/".$id;
        }
	elseif ($type=="Ontology") return $entity['uri'];
        return $datauri."$url_subpath/$id";
}

function getRequester($mship){
	if (!$mship['user_established_at']) return "groups/".$mship['network_id'];
	elseif(!$mship['network_established_at']) return "users/".$mship['user_id'];
	else{
		$utime=strtotime($mship['user_established_at']);
		$ntime=strtotime($mship['network_established_at']);
		if ($utime<=$ntime) return "users/".$mship['user_id'];
		else return "groups/".$mship['network_id'];
	}
	return "";
}
function getRequesterTime($mship){
	if (!$mship['user_established_at']) return $mship['network_established_at'];
	elseif(!$mship['network_established_at']) return $mship['user_established_at'];
	else{
		$utime=strtotime($mship['user_established_at']);
		$ntime=strtotime($mship['network_established_at']);
		if ($utime<=$ntime) return $mship['user_established_at'];
		else return $mship['network_established_at'];
	}
	return "";
}

function getAccepter($mship){
	if (!$mship['user_established_at']){
		if ($mship['user_id']) return "users/".$mship['user_id'];
		return "";
	}
	elseif(!$mship['network_established_at']) return "groups/".$mship['network_id'];
	else{
		$utime=strtotime($mship['user_established_at']);
		$ntime=strtotime($mship['network_established_at']);
		if ($utime<=$ntime) return "groups/".$mship['network_id'];
		else return "users/".$mship['user_id'];	
	}
	return "";
}

function getAccepterTime($mship){
	if ($mship['network_established_at'] && $mship['user_established_at']){
		$utime=strtotime($mship['user_established_at']);
		$ntime=strtotime($mship['network_established_at']);
		if ($utime>$ntime) return $mship['user_established_at'];
		else return $mship['network_established_at'];
	}
	return "";
}

function getMembers($group){
	global $datauri;
	$xml="";
        $msql = "select * from memberships where network_id=$group[id] and network_established_at is not null and user_established_at is not null";
	$mres=mysql_query($msql);
        $xml.="    <sioc:has_member rdf:resource=\"${datauri}users/".$group['user_id']."\"/>\n";
        for ($m=0; $m<mysql_num_rows($mres); $m++){
        	$xml.="    <sioc:has_member rdf:resource=\"${datauri}users/".mysql_result($mres,$m,'user_id')."\"/>\n";
        }
	return $xml;
}

function getMemberships($user){
	global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
		$msql=$sql['Membership']." where user_id=$user[id]";
		$mres=mysql_query($msql);
		for ($m=0; $m<mysql_num_rows($mres); $m++){
			$xml.="    <mebase:has-membership rdf:resource=\"${datauri}users/$user[id]/memberships/".mysql_result($mres,$m,'id')."\"/>\n";
		}
	}
	return $xml;
}
function getFriendships($user){
        global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
		$fsql=addWhereClause($sql['Friendship'],"user_id=$user[id] or friend_id=$user[id]");
        	$fres=mysql_query($fsql);
	        for ($f=0; $f<mysql_num_rows($fres); $f++){
        	        $xml.="    <mebase:has-friendship rdf:resource=\"${datauri}users/".mysql_result($fres,$f,'user_id')."/friendships/".mysql_result($fres,$f,'id')."\"/>\n";
        	}
	}
        return $xml;
}
function getFavourites($user){
	global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
	        $fsql=addWhereClause($sql['Favourite'],"user_id=$user[id]");
        	$fres=mysql_query($fsql);
	        for ($f=0; $f<mysql_num_rows($fres); $f++){
        	        $xml.="    <meannot:has-favourite rdf:resource=\"${datauri}users/$user[id]/favourites/".mysql_result($fres,$f,'id')."\"/>\n";
        	}
        	return $xml;
	}
}
function getUser($action,$hash=0){
	global $salt, $ontopath;
	if ($action['user_id'] && $action['user_id']!="AnonymousUser"){
		if ($hash) return "users/".md5($salt.$action['user_id']);
	 	return "users/".$action['user_id'];
	}
	return $ontopath."specific/AnonymousUser";
}
function getTaggings($tag){
	global $sql, $datauri;
	$xml="";
	$tsql=addWhereClause($sql['Tagging'],"tag_id=$tag[id]");
	$tres=mysql_query($tsql);
	for ($t=0; $t<mysql_num_rows($tres); $t++){
		$row=mysql_fetch_assoc($tres);
		$xml.="    <meannot:for-tagging rdf:resource=\"".getEntityURI('Tagging',$row['id'],$row)."\"/>\n";
	}
	return $xml;
}
function getPolicyURI($contrib,$type){
	global $entities;
	if (isset($entities[$type]['parent_entity'])){
		$policy['contributable_type']=$entities[$type]['parent_entity'];
		$policy['contributable_id']=$contrib[$entities[$type]['parent_id']];
	}
	else{
		$policy['contributable_type']=$type;
		$policy['contributable_id']=$contrib['id'];
	}
	
	return getEntityURI('Policy',$contrib['policy_id'],$policy);
}
function getThumbnail($workflow){
	$url=getUrl($workflow,'thumb');
	return $url;
}
function getThumbnailBig($workflow){
	$url=getUrl($workflow,'medium');
	return $url;
}
function getPreview($workflow){
        $url=getUrl($workflow,'full');
	return $url;
}
function getSVG($workflow){	
	$url=getUrl($workflow,'svg');
	return $url;
}
function workflowOrVersion($entity){
	global $datauri;
	if (isset($entity['current_version'])) $type="Workflow";
        else $type="WorkflowVersion";
	return $datauri.$type."/".$entity['id'];
}
function getUrl($workflow,$type=""){
 	global $datauri;
        if (isset($workflow['workflow_id'])){
                return $datauri."workflows/".$workflow['workflow_id']."/versions/".$workflow['version']."/previews/".$type;
        }
        return $datauri."workflows/".$workflow['id']."/previews/".$type;
}
function getWorkflowDownloadUrl($workflow){
	global $datauri;
	if (isset($workflow['workflow_id'])){
                $url=$datauri."workflows/".$workflow['workflow_id']."/download/".urlencode($workflow['unique_name']).".".$workflow['file_ext']."?version=".$workflow['version'];
		$table="workflow_versions";
		$id=$workflow['workflow_id'];
        }
        else{
		$url=$datauri."workflows/".$workflow['id']."/download/".urlencode($workflow['unique_name']).".".$workflow['file_ext'];
		$table="workflows";
		$id= $workflow['id'];
	}
	$ctsql="select mime_type from content_types where id=$workflow[content_type_id]";
	$ctres=mysql_query($ctsql);
	return $url;
	
}
function getFileDownloadUrl($file){
	global $datauri;
	return $datauri."blobs/".$file['id']."/download/".urlencode($file['local_name']);
}
	
function getLicense($contrib){
	global $licenses;
	return $licenses[$contrib['license']];
}
function getCurrentVersion($entity, $type) {
	global $datauri, $entities;
	if (empty($entities[$type]['version_entity']) || empty($entity['current_version'])) return "";
        return "$datauri{$entities[$type]['url_subpath']}/{$entity['id']}/versions/{$entity['current_version']}";
}

function getIsCurrentVersion($version){
	global $db_parent_ids, $entities;
	foreach ($db_parent_ids as $parent_id => $type) {
		if (isset($version[$parent_id])) {
			$db_table = $entities[$type]['table'];
			$db_version_table = $entities[$entities[$type]['version_entity']]['table'];
			$version_sql = "SELECT {$db_table}.id FROM {$db_table} INNER JOIN {$db_version_table} ON {$db_table}.current_version={$db_version_table}.version AND {$db_table}.id={$db_version_table}.workflow_id WHERE {$db_version_table}.id={$version['id']}";
			break;
		}
	}
        if (!isset($version_sql)) {
		return;
	}
	$res = mysql_query($version_sql);
	return mysql_num_rows($res);
}
function getVersions($entity, $type){
	global $datauri, $sql, $entities;
	if (empty($entities[$type]['version_entity'])) return "";
	$version_entity_type_name = $entities[$type]['version_entity'];
	$version_entity_type = $entities[$version_entity_type_name];
	$versions_sql = $sql[$version_entity_type_name]. " and {$version_entity_type['parent_id']}={$entity['id']}";
	if (!empty($entity['current_version'])) {
		$versions_sql .= " and version!={$entity['current_version']}";
	}
	$res=mysql_query($versions_sql);
	$versions_rdf="";
	if ($type == "Pack") {
		$property_name = "mepack:has-snapshot";
	}
	else {
		$property_name = "mebase:has-version";
	}
	for ($i=0; $i<mysql_num_rows($res); $i++){
                $row=mysql_fetch_assoc($res);
                $versions_rdf .= "    <$property_name rdf:resource=\"".$datauri.$entities[$type]['url_subpath']."/".$entity['id']."/versions/".$row['version']."\"/>\n";
        }
        return $versions_rdf;
}
function getPictureURL($user){
       	global $datauri;
       	if (isset($user['picture_id']) && $user['picture_id']>0) return $datauri."pictures/show/".$user['picture_id']."?size=160x160.png";
	return $datauri."images/avatar.png";
}

function getSiocAndFoafName($user){
	global $datatypes;
	if (isset($user['name'])) return "    <sioc:name rdf:datatype=\"&xsd;".$datatypes['sioc:name']."\">$user[name]</sioc:name>\n    <foaf:name rdf:datatype=\"&xsd;".$datatypes['foaf:name']."\">$user[name]</foaf:name>\n";
	return "";
}   
function getMailto($user){
	global $userid, $domain;
	if (validateEmail($user['email']) && ($userid==$user['id'] || $domain=="private")) return "mailto:".$user['email'];
}
function getMboxSha1sum($user){
	if (isset($user['email'])) return sha1($user['email']);
	return "";
}
function getMailtoUnconfirmed($user){
	global $userid, $domain;
	if (validateEmail($user['unconfirmed_email']) && ($userid==$user['id'] || $domain=="private")) return "mailto:".$user['unconfirmed_email'];
}
function getMailtoProfile($user){
	if (isset($user['profile_email']) && validateEmail($user['profile_email'])) return "mailto:".$user['profile_email'];
}
function getOpenidUrl($user){
	global $userid, $domain;
	if ($userid==$user['id'] || $domain=="private") return $user['openid_url'];
}
function getResidence($user){
	$residence="";
	$mats=array("/",",","  ");
	$reps=array(" ",", "," ");
	if(isset($user['location_city']) && strlen(trim($user['location_city']))>0) {
		$city = str_replace("+","_",urlencode(my_ucwords(trim(str_replace($mats,$reps,$user['location_city'])))));
		$residence.="    <dbpedia:residence rdf:resource=\"http://dbpedia.org/resource/$city\"/>\n";
	}
	if(isset($user['location_country']) && strlen(trim($user['location_country']))>0) {
		$country = str_replace("+","_",urlencode(my_ucwords(trim(str_replace($mats,$reps,$user['location_country'])))));
		$residence.="    <dbpedia:residence rdf:resource=\"http://dbpedia.org/resource/$country\"/>\n";
	}
	return $residence;
}
function getUsername($user){
	global $userid, $domain;
	if ($userid==$user['id'] || $domain=="private") return $user['username'];
}	
function isPartOfURI($entry){
	return  "packs/".$entry['pack_id'];
}
function getVersionID($entity){
        global $entities, $db_entity_mappings;
	if (!empty($db_entity_mappings[$entity['contributable_type']])) {
		$entity['contributable_type'] = $db_entity_mappings[$entity['contributable_type']];
	}
	if (empty($entities[$entity['contributable_type']]['version_entity'])) return;
	$entity_version = $entities[$entities[$entity['contributable_type']]['version_entity']];
	$version_sql = "SELECT id FROM {$entity_version['table']} WHERE {$entity_version['parent_id']} = {$entity['contributable_id']} AND version = {$entity['contributable_version']}";
	//error_log($version_sql);
	$res = mysql_query($version_sql);
	if (mysql_num_rows($res) == 0) {
		return "";
	}
	return mysql_result($res, 0, 'id');
}
	
function getHomepage($entity, $type){
	$url = getEntityURI($entity, $type);
	if (!empty($url)) return $url . ".html";
	return "";	
}

function getFilename($entity, $type){
	if ($type=="Workflow"||$type="WorkflowVersion") return $entity['unique_name'].".".$entity['file_ext'];
	return '';
}
function getPackEntries($pack){
	global $datauri, $sql;
	$lsql=$sql['LocalPackEntry'];
	if (stripos($lsql,'where')>0) $lsql.=" and ";
	else $lsql.=" where ";	
	$lsql.="pack_id=$pack[id]";
	if (empty($pack['version'])) {
		$lsql.=" and version IS NULL";
	}
	else {
		$lsql.= " and version = {$pack['version']}";
	}
	$lres=mysql_query($lsql);
	$xml="";
	$packurl=getEntityURI('Pack',$pack['id'],$pack);
	for ($e=0; $e<mysql_num_rows($lres); $e++){
		$xml.="    <mepack:has-entry rdf:resource=\"$packurl/local_pack_entries/".mysql_result($lres,$e,'id')."\"/>\n";
	}
	$rsql=$sql['RemotePackEntry'];
	if (stripos($rsql,'where')>0) $rsql.=" and ";
        else $rsql.=" where ";
        $rsql.="pack_id=$pack[id]";
	$rres=mysql_query($rsql);
        for ($e=0; $e<mysql_num_rows($rres); $e++){
                $xml.="    <mepack:has-entry rdf:resource=\"$packurl/remote_pack_entries/".mysql_result($rres,$e,'id')."\"/>\n";
        }
	$prsql=$sql['RelationshipEntry'];
	if (stripos($prsql,'where')>0) $prsql.=" and ";
        else $prsql.=" where ";
        $prsql.="context_id=$pack[id]";
        $prres=mysql_query($prsql);
	for ($e=0; $e<mysql_num_rows($prres); $e++){
                $xml.="    <mepack:has-entry rdf:resource=\"$packurl/relationship_entries/".mysql_result($prres,$e,'id')."\"/>\n";
        }
	return $xml;
}

	
function getOutput($entity){
	global $datauri;
	$xml="";
	if ($entity['outputs_uri']){		
		$uri=getEntityURI('jobs',$entity['id'],$entity);
		$xml="<meexp:Data rdf:about=\"$uri/output\">\n";
		if ($entity['outputs_uri']) $xml.= "        <mebase:uri rdf:resource=\"$entity[outputs_uri]\"/>\n";

		$xml.="      </meexp:Data>";
	}
	return $xml;
}
function getInput($entity){
	$xml="";
	if ($entity['inputs_uri'] || $entity['inputs_data']){
		$uri=getEntityURI('jobs',$entity['id'],$entity);
		$xml="<meexp:Data rdf:about=\"$uri/input\">\n";
		if ($entity['inputs_uri']) $xml.= "        <mebase:uri rdf:resource=\"$entity[inputs_uri]\"/>\n";
		if ($entity['inputs_data']) $xml.= "        <mebase:text rdf:datatype=\"&xsd;string\">$entity[inputs_data]</mebase:text>\n";
		$xml.="      </meexp:Data>";
	}
        return $xml;
}
function getRunnable($entity) {
	global $entities, $db_entity_mappings;
	if (!empty($db_entity_mappings[$entity['runnable_type']])) $runnable_type = $db_entity_mappings[$entity['runnable_type']];
	else $runnable_type = $entity['runnable_type'];
	$runnable = $entities[$runnable_type]['url_subpath']."/".$entity['runnable_id'];
	if (!empty($entity['runnable_version'])) $runnable.="/versions/".$entity['runnable_version'];
	return $runnable;
}
function getJobURI($entity){
	return $entity['job_uri'];
}
function getRunner($entity){
	return "runners/$entity[runner_id]";
}
function getProxyFor($entity){
	global $entities, $db_entity_mappings;
	$xml="";
	if (isset($entity['contributable_type'])){
		if (!empty($db_entity_mappings[$entity['contributable_type']])) $entity_type_name = $db_entity_mappings[$entity['contributable_type']];
                else $entity_type_name = $entity['contributable_type'];
		return getEntityURI($entity_type_name, $entity['contributable_id'], $entity);
	}
	$xml.="<rdf:Description rdf:about=\"".str_replace("&","&amp;",$entity['uri'])."\"";
        if ($entity['alternate_uri']) $xml.=">\n      <rdfs:seeAlso>\n        <rdf:Description rdf:about=\"".str_replace("&","&amp;",$entity['alternate_uri'])."\"/>\n      </rdfs:seeAlso>\n    </rdf:Description>\n";
	else $xml.="/>";
        return $xml;
}
function writeDataflowToFile($wfvid,$ent_uri,$fileloc,$content_type){
	global $myexppath, $rakepath, $rails_env;
	require_once('xmlfunc.inc.php');
	$ph=popen("cd $myexppath; ".$rakepath."rake RAILS_ENV=$rails_env myexp:workflow:components ID=$wfvid | grep -v '^(in' 2>/dev/null",'r');
        $xml="";
        while(!feof($ph)){
	        $xml.=fgets($ph);
        }
        fclose($ph);
        $parsedxml=parseXML($xml);
        $dataflows=tabulateDataflowComponents($parsedxml[0]['children'][0]['children'],$ent_uri,$content_type);
        $fh=fopen($fileloc,'w');
        if ($dataflows) fwrite($fh,generateDataflows($dataflows,$ent_uri));
        else fwrite($fh,"NONE");
        fclose($fh);
}

function getDataflow($entity,$type){
	if (canUserDownload($entity)) return getDataflowComponents($entity,$type,0);	
	return "";
}

function canUserDownload($entity){
	global $rdfgen_userid, $use_rake;
	if (!$use_rake) return TRUE;
	if ($entity['contributor_type'] != 'User') return FALSE;
        if ($rdfgen_userid == $entity['contributor_id']) return TRUE;
        elseif ($entity['share_mode'] == 0) return TRUE;
        elseif (in_array($entity['share_mode'],array(1,3))){
        	$friendship_sql = "SELECT * FROM friendships WHERE accepted_at IS NOT NULL AND (user_id = $entity[contributor_id] OR friend_id = $entity[contributor_id]) AND (user_id = $rdfgen_userid OR friend_id = $rdfgen_userid)";  
                $res = mysql_query($friendship_sql);
                if (mysql_num_rows($res2)>0) return TRUE;
        }                       
        else{
                $membership_sql = "SELECT * FROM memberships WHERE network_id IN (SELECT contributor_id FROM permissions WHERE policy_id = ".$entity['policy_id']." AND contributor_type = 'Network' AND download = 1) AND user_id = $rdfgen_userid";
                $res = mysql_query($membership_sql);
                if (mysql_num_rows($res)>0) return TRUE;
        }
	return FALSE;
}


function getDataflowComponents($entity,$type,$retrieve=true){
	global $datauri,$datapath,$myexppath,$use_rake;
	$comp_path=$datapath."dataflows/";
	$sql="select workflow_versions.*, content_types.mime_type from workflow_versions inner join content_types on workflow_versions.content_type_id=content_types.id where ";
	if ($type=="Workflow") $sql.="version='$entity[current_version]' and workflow_id='$entity[id]'";
	elseif ($type=="WorkflowVersion") $sql.="workflow_versions.id='$entity[id]'";
	$res=mysql_query($sql);
	$wfv=mysql_fetch_assoc($res);
	$ent_uri=$datauri."workflows/$wfv[workflow_id]/versions/$wfv[version]";
        if ($wfv['mime_type']=='application/vnd.taverna.t2flow+xml') $df_uri="$ent_uri#dataflows/1";
        else $df_uri="$ent_uri#dataflow";
	$fileloc=$comp_path.$wfv['id'];
	if (!file_exists($fileloc) && $use_rake) writeDataflowToFile($wfv['id'],$ent_uri,$fileloc,$wfv['mime_type']);
	if (file_exists($fileloc)) $lines=file($fileloc);
	else return "";
	if (trim($lines[0])=="NONE") return "";
	elseif($retrieve==false) return $df_uri;
	return implode("",$lines);
}
	
function getLicenseAttributes($license){
	$sql="select license_options.* from license_attributes inner join license_options on license_attributes.license_option_id=license_options.id where license_attributes.license_id={$license['id']}";
	$res=mysql_query($sql);
	$xml="";
	for ($a=0; $a<mysql_num_rows($res); $a++){
		$row=mysql_fetch_array($res);
		$xml.="    <cc:{$row['predicate']} rdf:resource=\"{$row['uri']}\"/>\n";
	}   
	return $xml;;
}
function getAnnotationSQL($type, $p1, $p2){
	global $sql, $entities;
	$annotation_sql = addWhereClause($sql[$type], str_replace('~', $p2, str_replace('?', $p1, $entities[$type]['annotation_where_clause'])));
	return $annotation_sql;
	
}

function getAnnotations($entity, $type){
	global $entities, $db_entity_mappings;
	if (empty($entities[$type]['annotations'])) {
		return "";
	}
	$annotations = $entities[$type]['annotations'];
	if (isset($db_entity_mappings[$type])) $type = $db_entity_mappings[$type];
	$xml = "";
	foreach ($annotations as $annotation){
		if ($annotation == "Citation") $annot_sql=getAnnotationSQL($annotation, $entity['workflow_id'], $entity['version']);
		else  $annot_sql=getAnnotationSQL($annotation, $type, $entity['id']);
		$res = mysql_query($annot_sql);
	        for ($a=0; $a<mysql_num_rows($res); $a++){
			$row = mysql_fetch_assoc($res);
                        $annot_uri=getEntityURI($annotation, $row['id'], $row);
                        $xml.="    <meannot:".$entities[$annotation]['annotation_property']." rdf:resource=\"$annot_uri\"/>\n";
		}
	}
	return $xml;
}

function getPackRelationshipEntries($pack){
	global $sql, $datauri;
	$rsql=$sql['RelationshipEntry'];
	if (strpos("where",$rsql)>0) $rsql.=" and ";
	else $rsql.=" where ";
	$rsql.="pack_id=$pack[id]";
	$res=mysql_query($rsql);
	$xml="";
	if ($res!==false){
		for ($r=0; $r<mysql_num_rows($res); $r++){
			$xml.="    <mepack:has-relationship-entry rdf:resource=\"${datauri}packs/$pack[id]/relationship_entries/".mysql_result($res,$r,'id')."\"/>\n";
		}
	}
	return $xml;
}

// Needs rewriting once predicate relationships are introduced.
function getPredicateRelations($entity){
	global $sql, $datauri;
	$xml="";
	$predicatesql=$sql['PredicateRelation'];
	if (!stripos('where',$sql['PredicateRelation'])) $predicatesql.=" where ";
        else $predicatesql.=" and ";
        $predicaterelsql=$predicatesql."subject_predicate_id=$entity[id]";
        $res=mysql_query($predicaterelsql);
	if ($res!==false){
	        for ($r=0; $r<mysql_num_rows($res); $r++){
        	        $row=mysql_fetch_assoc($res);
                	$xml.="    <rdfs:subClassOf rdf:resource=\"$entity[ontology_uri]/$row[object_predicate_id]\"/>\n";
	        }
	}
	return $xml;
}
function getRelationshipSPO($entity){
	return array('subject'=>getRelationshipSubject($entity),'predicate'=>getRelationshipPredicate($entity),'object'=>getRelationshipObject($entity));
}
function getRelationshipURN($spo){
	require_once('class.uuid.php');
        return "urn:uuid:".UUID::generate(UUID::UUID_NAME_SHA1,UUID::FMT_STRING,$spo['subject'].$spo['predicate'].$spo['object']);
}
function getRelationship($entity){
	$spo=getRelationshipSPO($entity);
	$urn=getRelationshipURN($spo);
	return "    <ore:proxyFor>\n      <mepack:Relationship rdf:about=\"$urn\">\n        <rdf:subject rdf:resource=\"$spo[subject]\"/>\n        <rdf:predicate rdf:resource=\"$spo[predicate]\"/>\n        <rdf:object rdf:resource=\"$spo[object]\"/>\n      </mepack:Relationship>\n    </ore:proxyFor>\n";
}
function getRelationshipSubject($entity){
	return getRelationshipNode($entity['subject_id'],$entity['subject_type']);
}
function getRelationshipObject($entity){
        return getRelationshipNode($entity['objekt_id'],$entity['objekt_type']);
}
function getRelationshipPredicate($entity){
	return getPredicate($entity['predicate_id']);
}
function getRelationshipNode($id,$type){
	global $datauri, $entities;
	$oetype=getOntologyEntityTypeFromDBType($type);
	$node_sql="select * from {$entities[$type]['table']} where id = $id";
	$res=mysql_query($node_sql);
	$row=mysql_fetch_assoc($res);
	if ($type=="PackRemoteEntry") return $row['uri'];
	if ($type=="PackContributableEntry"){
		$uri = $datauri.getOntologyEntityTypeFromDBType($row['contributable_type'])."/".$row['contributable_id'];
		if ($row['contributable_version']) return $uri."/versions/".$row['contributable_version'];
		return $uri;
	}
}
function getPredicate($id){
	$predsql="select ontologies.uri as ontology_uri, predicates.title as predicate from predicates inner join ontologies on predicates.ontology_id=ontologies.id where predicates.id=$id";
	$res=mysql_query($predsql);
	return mysql_result($res,0,'ontology_uri')."/".mysql_result($res,0,'predicate');
}
function printPredicates($id){
	global $sql;
	$predssql=$sql['Predicate'];
	if (!stripos('where',$sql['Predicate'])) $predssql.=" where ";
        else $predssql.=" and ";
	$predssql.="predicates.ontology_id=$id";
	echo $predssql;
	$res=mysql_query($predssql);
	$xml="";
	for ($p=0; $p<mysql_num_rows($res); $p++){
		$xml.=printEntity(mysql_fetch_assoc($res),"Predicate");
	}
	return $xml;
}
	
function getOntologyEntityTypeFromDBType($type){
	global $db_entity_mappings;
	if (!empty($db_entity_mappings[$type])) $type = $db_entity_mappings[$type];
	return $type;
}
function getStaticOntologyDetails($entity){
	return "    <dc:language rdf:datatype=\"&xsd;string\">en</dc:language>\n    <dc:publisher rdf:resource=\"http://www.myexperiment.org\"/>\n    <dc:format rdf:datatype=\"&xsd;string\">rdf/xml</dc:format>\n";
}
function getOREAggregatedResources($entry, $type){
        global $sql, $entities, $db_entity_mappings;
        $xml="";
        if ($type=="Experiment" || $type=="Pack" || $type=="PackSnapshot") {
                if (!isset($entry['version'])) $entry['version'] = null;
                $arsql=getAggregatedResourceSQL($type,$entry['id'],$entry['version']);
                $res=mysql_query($arsql);
                for ($i=0; $i<mysql_num_rows($res); $i++){
                        $row=mysql_fetch_assoc($res);
                        if (!empty($db_entity_mappings[$row['contributable_type']])) $row['contributable_type'] = $db_entity_mappings[$row['contributable_type']];
                        if (isset($row['runnable_id'])){
                                $row['contributable_type']="Job";
                                $row['contributable_id']=$row['id'];
                                $row['entry_type']="LocalPackEntry";
                        }
			if ($row['entry_type']=="RemotePackEntry") $fulluri=$row['uri'];
        	        else $fulluri=getEntityURI($row['contributable_type'], $row['contributable_id'], $row);
                	$xml.="    <ore:aggregates rdf:resource=\"".str_replace("&","&amp;",$fulluri)."\"/>\n";
                }
                if ($type=="Pack" || $type=="PackSnapshot"){
                        $prsql=$sql['RelationshipEntry'];
                        if (stripos($prsql,'where')>0) $prsql.=" and ";
                        else $prsql.=" where ";
                        $prsql.="context_id=$entry[id]";
                        $res=mysql_query($prsql);
                        for ($i=0; $i<mysql_num_rows($res); $i++){
                                $row=mysql_fetch_assoc($res);
                                $relurn=getRelationshipURN(getRelationshipSPO($row));
                                $xml.="    <ore:aggregates rdf:resource=\"$relurn\"/>\n";
                        }
                }
        }
        return $xml;
}
function getOREDescribedBy($entity,$type){
        return getEntityURI($type, $entity['id'], $entity).".rdf";
}
function getPolicy($contrib,$type=''){
        $policy_url=getEntityURI("Policy",$contrib['policy_id'],$contrib);
        $policy="";
        if ($type!="Policy") $policy.="<snarm:Policy rdf:about=\"$policy_url\">\n";
        $perms=getPermissions($contrib['policy_id']);
        if (!isset($contrib['share_mode'])) $contrib=addShareAndUpdateMode($contrib);
        $policy.=getContributorPermissions($contrib['contributor_type'],$contrib['contributor_id'],$policy_url);
        $policy.=getShareModeAccesses($contrib['share_mode']);
        $policy.=getUpdateModeAccesses($contrib['update_mode'],$contrib['share_mode'],$perms);
        $policy.=getPermissionAccesses($perms,$policy_url);
        if ($type!="Policy") $policy.="      </snarm:Policy>";
        return $policy;
}

function getContributorPermissions($contrib_type,$contrib_id,$policy_url){
        global $datauri, $entities, $db_entity_mappings;
        if (!empty($db_entity_mappings[$contrib_type])) $contrib_type=$db_entity_mappings[$contrib_type];
        $contributor=$entities[$contrib_type]['url_subpath']."/".$contrib_id;
        return "    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;View\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Download\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Edit\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n";
}

function getShareModeAccesses($sm){
        switch ($sm){
                case 0:
                        return "    <snarm:has-access rdf:resource=\"&mespec;PublicView\"/>\n    <snarm:has-access rdf:resource=\"&mespec;PublicDownload\"/>\n";
                case 1:
                        return "    <snarm:has-access rdf:resource=\"&mespec;PublicView\"/>\n    <snarm:has-access rdf:resource=\"&mespec;FriendsDownload\"/>\n";
                case 2:
                        return "    <snarm:has-access rdf:resource=\"&mespec;PublicView\"/>\n";
                case 3:
                        return "    <snarm:has-access rdf:resource=\"&mespec;FriendsView\"/>\n    <snarm:has-access rdf:resource=\"&mespec;FriendsDownload\"/>\n";
                case 4;
                        return "    <snarm:has-access rdf:resource=\"&mespec;FriendsView\"/>\n";
                default:
                        return "";
        }
        return "";
}

function getUpdateModeAccesses($um,$sm){
        switch ($um){
                case 0:
                        if ($sm==0 && is_int($sm)) return "    <snarm:has-access rdf:resource=\"&mespec;PublicEdit\"/>\n";
                        elseif ($sm==1 || $sm==3) return "    <snarm:has-access rdf:resource=\"&mespec;FriendsEdit\"/>\n";
                case 1:
                        return "    <snarm:has-access rdf:resource=\"&mespec;FriendsEdit\"/>\n";
                default:
                        return "";
                }
        return "";
}

function getPermissionAccesses($perms,$policy_url){
        global $datauri, $entities, $modelalias;
        $accesses="";
        $a=3;
        for ($p=0; $p<sizeof($perms); $p++){
                if (!empty($db_entity_mappings[$perms[$p]['contributor_type']])) $perms[$p]['contributor_type'] = $db_entity_mappings[$perms[$p]['contributor_type']];
                $url_subpath = $entities[$perms[$p]['contributor_type']]['url_subpath'];
                if ($perms[$p]['view']){
                        $a++;
                        $accesses.="    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri.$url_subpath."/".$perms[$p]['contributor_id']."\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;View\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n";
                }
                if ($perms[$p]['download']){
                        $a++;
                        $accesses.="    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri.$url_subpath."/".$perms[$p]['contributor_id']."\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Download\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n";
                }
                if ($perms[$p]['edit']){
                        $a++;
                        $accesses.="    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri.$url_subpath."/".$perms[$p]['contributor_id']."\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Edit\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n";
                }
        }
        return $accesses;
}


?>
