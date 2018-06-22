<?php
/**
 * @file inc/functions/property.inc.php
 * @brief Functions for generating the property/values pairs for myExperiment RDF entities.
 * @version beta
 * @author David R Newman
 * @details Functions for generating the property/value pairs for myExperiment RDF entities, as specified by the $mappings associative array in inc/config/data.inc.php.
 */

/**
 * @brief Determine the URI for an entity from its type, ID and other fields retrieved from a database query.
 * 
 * @param $type
 * A string containing the type of entity for which the URI is to be determined.
 *
 * @param $id
 * A inetger containing the ID of the entity for which the URI is to be determined.
 * 
 * @param $entity
 * An associative arry containing database fields mapped to values for the entity whose URI is to be determined.
 *
 * @return
 * A string representing the URI for the entity sepcified.
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
				$nesting_entity = $db_entity_mappings[$entity[$entity_type['nested_on'][0]]];
				return $datauri . $entities[$nesting_entity]['url_subpath'] . "/". $entity[$entity_type['nested_on'][1]] . "/$url_subpath/$id";
			case 'Citation': 
				return $datauri."workflows/".$entity[$entity_type['nested_on'][0]]."/versions/".$entity[$entity_type['nested_on'][1]]."/$url_subpath/$id";
			case 'GroupAnnouncement':
				return $datauri . "groups/" . $entity[$entity_type['nested_on'][1]] . "/announcements/$id";
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
			case 'ObjectProperty':
				return $entity['ontology_uri']."/".$entity['title'];
			default:
				break;
		}
	}
	elseif (isset($entity['contributable_version'])) {
		return "{$datauri}{$url_subpath}/{$id}/versions/{$entity['contributable_version']}";
	}
	elseif (isset($entity_type['versioned_id'])) {
		$version_sql = "SELECT {$entity_type['versioned_id']} AS versioned_id, version FROM {$entity_type['table']} WHERE id = $id";
		$version_res = mysqli_query($con, $version_sql);
		if (mysqli_num_rows($version_res) == 0) {
			return "";
		}
		$version = mysqli_fetch_assoc($version_res);
		return $datauri . $entities[$entity_type['versioned_entity']]['url_subpath'] . "/" . $version['versioned_id'] . "/versions/" . $version['version'];
        }
        elseif ($type=="GroupAnnouncement"){
   	 	$gasql="select network_id from group_announcements where id=$id";
                $gares=mysqli_query($con, $gasql);
                return $datauri."groups/".mysqli_result($gares,0,'network_id')."/announcements/".$id;
        }
	elseif ($type=="Ontology") return $entity['uri'];
        return $datauri."$url_subpath/$id";
}

/**
 * @brief Get the URI for either the User who requested to join a Group or the Group that requested a user join it.
 *
 * @param $mship
 * An associative array of database fields mapped to values for a Membership entity.
 * 
 * @return
 * The URI for the Group or User that requested membership of the User to the Group.
 */
function getRequester($mship){
	if (empty($mship['user_established_at'])) return "groups/".$mship['network_id'];
	elseif(empty($mship['network_established_at'])) return "users/".$mship['user_id'];
	$utime=strtotime($mship['user_established_at']);
	$ntime=strtotime($mship['network_established_at']);
	if ($utime<=$ntime) return "users/".$mship['user_id'];
	return "groups/".$mship['network_id'];
}

/**
 * @brief Get the datetime for when membership to a Group was requested.
 *
 * @param $mship
 * An associative array of database fields mapped to values for a Membership entity.
 *
 * @return
 * A string containing the datetime for when the membership to a Group was requested.
 */
function getRequesterTime($mship){
	if (!$mship['user_established_at']) return $mship['network_established_at'];
	elseif(!$mship['network_established_at']) return $mship['user_established_at'];
	$utime=strtotime($mship['user_established_at']);
	$ntime=strtotime($mship['network_established_at']);
	if ($utime<=$ntime) return $mship['user_established_at'];
	return $mship['network_established_at'];
}

/**
 * @brief Get the URI for either the User who accepted to join a Group or the Group that accepted a user join it.
 *
 * @param $mship
 * An associative array of database fields mapped to values for a Membership entity.
 *
 * @return
 * The URI for the Group or User that accepted membership of the User to the Group.
 */
function getAccepter($mship){
	if (empty($mship['user_established_at'])){
		if (!empty($mship['user_id'])) return "users/".$mship['user_id'];
		return "";
	}
	elseif(empty($mship['network_established_at'])) return "groups/".$mship['network_id'];
	$utime=strtotime($mship['user_established_at']);
	$ntime=strtotime($mship['network_established_at']);
	if ($utime<=$ntime) return "groups/".$mship['network_id'];
	return "users/".$mship['user_id'];	
}

/**
 * @brief Get the datetime for when membership to a Group was accepted.
 *
 * @param $mship
 * An associative array of database fields mapped to values for a Membership entity.
 *
 * @return
 * A string containing the datetime for when the membership to a Group was accepted.
 */
function getAccepterTime($mship){
	if ($mship['network_established_at'] && $mship['user_established_at']){
		$utime=strtotime($mship['user_established_at']);
		$ntime=strtotime($mship['network_established_at']);
		if ($utime>$ntime) return $mship['user_established_at'];
		else return $mship['network_established_at'];
	}
	return "";
}

/**
 * @brief Generates a set of sioc:has_member RDF properties for all the members of a specified Group.
 * 
 * @param $group
 * An associative array of database fields mapped to values for a Group entity.
 * 
 * @return
 * A string containing sioc:has_member RDF properties for all the members of a specified Group.
 */
function getMembers($group){
	global $datauri;
	$xml="";
        $msql = "select * from memberships where network_id=$group[id] and network_established_at is not null and user_established_at is not null";
	$mres=mysqli_query($con, $msql);
        $xml.="    <sioc:has_member rdf:resource=\"${datauri}users/".$group['user_id']."\"/>\n";
        for ($m=0; $m<mysqli_num_rows($mres); $m++){
        	$xml.="    <sioc:has_member rdf:resource=\"${datauri}users/".mysqli_result($mres,$m,'user_id')."\"/>\n";
        }
	return $xml;
}

/**
 * @brief Generates a set of mebase:has-membership RDF properties for all the Memberships of a User.
 * 
 * @param $user
 * An associative array of database fields mapped to values for a User entity.
 *
 * @return
 * A string containing mebase:has-membership RDF properties for all the Memberships of a User.
 */
function getMemberships($user){
	global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
		$msql=$sql['Membership']." where user_id=$user[id]";
		$mres=mysqli_query($con, $msql);
		for ($m=0; $m<mysqli_num_rows($mres); $m++){
			$xml.="    <mebase:has-membership rdf:resource=\"${datauri}users/$user[id]/memberships/".mysqli_result($mres,$m,'id')."\"/>\n";
		}
	}
	return $xml;
}

/**
 * @brief Generates a set of mebase:has-friendship RDF properties for all the Friendships of a User.
 *
 * @param $user
 * An associative array of database fields mapped to values for a User entity.
 *
 * @return
 * A string containing mebase:has-friendship RDF properties for all the Friendships of a User.
 */
function getFriendships($user){
        global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
		$fsql=addWhereClause($sql['Friendship'],"user_id=$user[id] or friend_id=$user[id]");
        	$fres=mysqli_query($con, $fsql);
	        for ($f=0; $f<mysqli_num_rows($fres); $f++){
        	        $xml.="    <mebase:has-friendship rdf:resource=\"${datauri}users/".mysqli_result($fres,$f,'user_id')."/friendships/".mysqli_result($fres,$f,'id')."\"/>\n";
        	}
	}
        return $xml;
}

/**
 * @brief Generates a set of meannot:has-favourite RDF properties for all the Favourites of a User.
 *
 * @param $user
 * An associative array of database fields mapped to values for a User entity.
 *
 * @return
 * A string containing meannot:has-favourite RDF properties for all the Favourites of a User.
 */
function getFavourites($user){
	global $sql, $datauri;
	$xml="";
	if (isset($user['id'])){
	        $fsql=addWhereClause($sql['Favourite'],"user_id=$user[id]");
        	$fres=mysqli_query($con, $fsql);
	        for ($f=0; $f<mysqli_num_rows($fres); $f++){
        	        $xml.="    <meannot:has-favourite rdf:resource=\"${datauri}users/$user[id]/favourites/".mysqli_result($fres,$f,'id')."\"/>\n";
        	}
        	return $xml;
	}
}

/**
 * @brief Generates a set of meannot:for-tagging RDF properties for all the Taggings (uses) of a Tag.
 *
 * @param $tag
 * An associative array of database fields mapped to values for a Tag entity.
 *
 * @return
 * A string containing meannot:for-tagging RDF properties for all the Taggings (uses) of a Tag.
 */
function getTaggings($tag){
	global $sql, $datauri;
	$xml="";
	$tsql=addWhereClause($sql['Tagging'],"tag_id=$tag[id]");
	$tres=mysqli_query($con, $tsql);
	for ($t=0; $t<mysqli_num_rows($tres); $t++){
		$row=mysqli_fetch_assoc($tres);
		$xml.="    <meannot:for-tagging rdf:resource=\"".getEntityURI('Tagging',$row['id'],$row)."\"/>\n";
	}
	return $xml;
}

/**
 * @brief Get the URI for the Policy of an entity of a specified type.
 * 
 * @param $contrib
 * An associative array of database fields mapped to values for a specified type of entity that is subclass of Contribution.
 * 
 * @param $type
 * A string containing the type of entity.
 * 
 * @return 
 * A string containing the Policy URI for an entity of a specified type.
 */
function getPolicyURI($contrib, $type){
	global $entities;
	if (isset($entities[$type]['versioned_entity'])){
		$policy['contributable_type']=$entities[$entities[$type]['versioned_entity']]['db_entity'];
		$policy['contributable_id']=$contrib[$entities[$type]['versioned_id']];
	}
	else{
		$policy['contributable_type']=$entities[$type]['db_entity'];
		$policy['contributable_id']=$contrib['id'];
	}
	
	return getEntityURI('Policy',$contrib['policy_id'],$policy);
}

/**
 * @brief Get the thumbnail preview URL for a Workflow or WorkflowVersion.
 * 
 * @param $workflow
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion entity.
 * 
 * @return
 * A string containing the thumbnnail preview URL for a specified Workflow or WorkflowVersion.
 */
function getThumbnail($workflow){
	$url=getPreviewURL($workflow,'thumb');
	return $url;
}

/**
 * @brief Get the big thumbnail preview URL for a Workflow or WorkflowVersion.
 *
 * @param $workflow
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion entity.
 *
 * @return
 * A string containing the big thumbnnail preview URL for a specified Workflow or WorkflowVersion.
 */
function getThumbnailBig($workflow){
	$url=getPreviewURL($workflow,'medium');
	return $url;
}

/**
 * @brief Get the full preview URL for a Workflow or WorkflowVersion.
 *
 * @param $workflow
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion entity.
 *
 * @return
 * A string containing the full preview URL for a specified Workflow or WorkflowVersion.
 */
function getPreview($workflow){
        $url=getPreviewURL($workflow,'full');
	return $url;
}

/**
 * @brief Get the SVG preview URL for a Workflow or WorkflowVersion.
 *
 * @param $workflow
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion entity.
 *
 * @return
 * A string containing the SVG preview URL for a specified Workflow or WorkflowVersion.
 */
function getSVG($workflow){	
	$url=getPreviewURL($workflow,'svg');
	return $url;
}

/**
 * @brief Get a specified type of preview URL for a Workflow or WorkflowVersion.
 *
 * @param $workflow
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion entity.
 * 
 * @param $type
 * A string containing the type of preview URL to be generated.  E.g. thumb, medium, full or svg.
 *
 * @return
 * A string containing a specified preview URL for a specified Workflow or WorkflowVersion.
 */
function getPreviewURL($workflow,$type=""){
 	global $datauri;
        if (isset($workflow['workflow_id'])){
                return $datauri."workflows/".$workflow['workflow_id']."/versions/".$workflow['version']."/previews/".$type;
        }
        return $datauri."workflows/".$workflow['id']."/previews/".$type;
}

/**
 * @brief Get the download URL for an entity that has a downloadable item. (E.g. File, FileVersion, Workflow or WorkflowVersion).
 *
 * @param $entity
 * An associative array of database fields mapped to values for an entity with a downloadable item. 
 *
 * @param $type
 * A string containing the type of entity.
 *
 * @return
 * A string containing the download URL for an entity that has a downloadable item. (E.g. File, FileVersion, Workflow or WorkflowVersion).
 */
function getDownloadURL($entity, $type) {
	switch ($type) {
		case 'File':
			return getEntityURI($type, $entity['id'], $entity)."/download/".urlencode($entity['local_name']);
		case 'FileVersion':
			return getEntityURI($type, $entity['id'], $entity)."/download/".urlencode($entity['local_name']);
		case 'Workflow': 
			return getEntityURI($type, $entity['id'], $entity)."/download/".urlencode($entity['unique_name']).".".$entity['file_ext'];
		case 'WorkflowVersion':
			return getEntityURI("Workflow", $entity['workflow_id'], $entity)."/download/".urlencode($entity['unique_name']).".".$entity['file_ext']."?version=".$entity['version'];	
        }
	return "";
}

/**
 * @brief Get the URI for the current/latest version of a versioned entity (e.g. Workflow, File or Pack).	
 *
 * @param $entity 
 * An associative array of database fields mapped to values for a versioned entity.
 * 
 * @param $type
 * A string containing the type of versione entity (e.g. Workflow, File or Pack).
 * 
 * @return
 * A string containing the URI for the current/latest version of a versioned entity.  If the versioned entity has no versions (e.g. a Pack with no snapshots), then an emoty string is returned.
 */
function getCurrentVersion($entity, $type) {
	global $datauri, $entities;
	if (empty($entities[$type]['version_entity']) || empty($entity['current_version'])) return "";
        return "$datauri{$entities[$type]['url_subpath']}/{$entity['id']}/versions/{$entity['current_version']}";
}

/**
 * @brief Test whether the specified version is the current/latest version of a particular versioned entity (e.g. Workflow, File or Pack).      
 *
 * @param $version
 * An associative array of database fields mapped to values for a version of a versioned entity.
 *
 * @return
 * A boolean.  TRUE if the version specified is the current version of a version entity (e.g. Workflow, File or Pack), FALSE otherwise.
 */
function getIsCurrentVersion($version){
	global $entities;
	foreach ($entities as $type => $entity) {
		if (!empty($entity['versioned_id']) && !empty($version[$entity['versioned_id']])) {
			$db_version_table = $entity['table'];
			$db_table = $entities[$entity['versioned_entity']]['table'];
			$version_sql = "SELECT {$db_table}.id FROM {$db_table} INNER JOIN {$db_version_table} ON {$db_table}.current_version={$db_version_table}.version AND {$db_table}.id={$db_version_table}.{$entity['versioned_id']} WHERE {$db_version_table}.id={$version['id']}";
			break;
		}
	}
        if (!isset($version_sql)) {
		return FALSE;
	}
	$res = mysqli_query($con, $version_sql);
	if (mysqli_num_rows($res) > 0) return TRUE;
	return FALSE;
}

/**
 * @brief Return RDF/XML mebase:has-version (or mepack:has-snapshot for Packs) properties for all versions of a versioned entity (e.g. Workflow, File or Pack).
 *
 * @param $entity 
 * An associative array of database fields mapped to values for a versioned entity.
 * 
 * @param $type
 * A string containing the type of versione entity (e.g. Workflow, File or Pack).
 * 
 * @return
 * A string containing RDF/XML mebase:has-version (or mepack:has-snapshot for Packs) properties for all versions of a versioned entity (e.g. Workflow, File or Pack).
 */
function getVersions($entity, $type){
	global $datauri, $sql, $entities;
	if (empty($entities[$type]['version_entity'])) return "";
	$version_entity_type_name = $entities[$type]['version_entity'];
	$version_entity_type = $entities[$version_entity_type_name];
	$versions_sql = $sql[$version_entity_type_name]. " and {$version_entity_type['versioned_id']}={$entity['id']}";
	if (!empty($entity['current_version'])) {
		$versions_sql .= " and version!={$entity['current_version']}";
	}
	$res=mysqli_query($con, $versions_sql);
	$versions_rdf="";
	if ($type == "Pack") {
		$property_name = "mepack:has-snapshot";
	}
	else {
		$property_name = "mebase:has-version";
	}
	for ($i=0; $i<mysqli_num_rows($res); $i++){
                $row=mysqli_fetch_assoc($res);
                $versions_rdf .= "    <$property_name rdf:resource=\"".$datauri.$entities[$type]['url_subpath']."/".$entity['id']."/versions/".$row['version']."\"/>\n";
        }
        return $versions_rdf;
}
/**
 * @brief Determine the URI (excluding the $datauri part) for the version entity that is referenced in the entity specified.
 *
 * @param $entity 
 * An associative array of database fields mapped to values for an entity that references a version entity.
 * 
 * @return
 * A string containing the URI (excluding the $datauri part) of the version entity referenced by the entity specified.  Otherwise an empty string.
 */
function getVersionURI($entity) {
	global $entities, $versioned_entities, $db_entity_mappings;
	if (!empty($entity['contributable_version'])) {
		$type = $db_entity_mappings[$entity['contributable_version']];
		return $entities[$type]['url_subpath'] . "/" . $entity['contributable_id'] . "/versions/" . $entity['contributable_version'];
	} 
	foreach ($versioned_entities as $versioned_entity => $version_entity) {
		if (isset($entity[$entities[$version_entity]['versioned_id']])) {
			$versioned_id_field = $entities[$version_entity]['versioned_id'];
			$versioned_version_field = str_replace("_id", "_version", $versioned_id_field);
			return $entities[$versioned_entity]['url_subpath'] . "/" . $entity[$versioned_id_field] . "/versions/" . $entity[$versioned_version_field];
		}
	}
	return "";
			
}


/**
 * @brief Return the URL for the picture (avatar) of a specified User (or the default avatar URL if they have no picture).
 *
 * @param $user
 * An associative array of database fields mapped to values for a User.
 *
 * @return
 * A string containing the URL for the picture (avatar) of a specified User (or the default avatar URL if they have no picture).
 */
function getPictureURL($user){
       	global $datauri;
       	if (!empty($user['picture_id'])) return $datauri."pictures/show/".$user['picture_id']."?size=160x160.png";
	return $datauri."images/avatar.png";
}

/**
 * @brief Return RDF/XML for the sioc:name and foaf:name properties (i.e. the User's full name) for a specified User.
 * 
 * @param $user
 * An associative array of database fields mapped to values for a User.
 *
 * @return
 * A string containing RDF/XML for the sioc:name and foaf:name properties (i.e. the User's full name) for a specified user.
 */
function getSIOCAndFOAFName($user){
	global $datatypes;
	if (isset($user['name'])) return "    <sioc:name rdf:datatype=\"&xsd;".$datatypes['sioc:name']."\">$user[name]</sioc:name>\n    <foaf:name rdf:datatype=\"&xsd;".$datatypes['foaf:name']."\">$user[name]</foaf:name>\n";
	return "";
}  

/**
 * @brief Get the SHA1 sum for a User's email address. So they can be uniquely identified by someone who already has their email address.
 *
 * @param $user
 * An associative array of database fields mapped to values for a User.
 *
 * @return
 * A string containing the SHA1 sum of a user's email address.
 */
function getMboxSHA1Sum($user){
	if (isset($user['email'])) return sha1($user['email']);
	return "";
}

/**
 * @brief Generate a mailto URL for a specified User's profile email address.
 *
 * @param $user
 * An associative array of database fields mapped to values for a User.
 *
 * @return
 * A string containing the mailto URL for a specified User's profile email address.
 */
function getMailtoProfile($user){
	if (isset($user['profile_email']) && validateEmail($user['profile_email'])) return "mailto:".$user['profile_email'];
}

/**
 * @brief Attempt to generate RDF/XML dbpedia:residence properties referencing existing DBPedia location URIs from the location_city and location_country values specified free-hand in User's profiles.  (A small number of properties generated may not reference existing DBPedia location URIs).
 * 
 * @param $user
 * An associative array of database fields mapped to values for a User.
 *
 * @return
 * A string containing 0, 1 or 2 RDF/XML db:residence properties.  Dependent on whether the User set their location_city a location_country fields in their profile.
 */
function getResidence($user){
	$residence="";
	$mats=array("/",",","  ");
	$reps=array(" ",", "," ");
	if (!empty($user['location_city'])) $user['location_city'] = trim($user['location_city']);
 	if (!empty($user['location_country'])) $user['location_country'] = trim($user['location_country']);
	if(!empty($user['location_city'])) {
		$city = str_replace("+","_",urlencode(convertToDBPediaResidenceString(str_replace($mats,$reps,$user['location_city']))));
		$residence.="    <dbpedia:residence rdf:resource=\"http://dbpedia.org/resource/$city\"/>\n";
	}
	if(!empty($user['location_country'])) {
		$country = str_replace("+","_",urlencode(convertToDBPediaResidenceString(str_replace($mats,$reps,$user['location_country']))));
		$residence.="    <dbpedia:residence rdf:resource=\"http://dbpedia.org/resource/$country\"/>\n";
	}
	return $residence;
}

/**
 * @brief Get the URL for the HTML homepage for a specified entity.
 *
 * @param $entity
 * An associative array of database fields mapped to values for an entity.
 *
 * @param $type
 * A string containing the type of the entity specified.
 *
 * @return 
 * A string the URL for the HTML homepage for a specified entity.  Otherwise return an empty string if the entity does not have a homepage.
 */
function getHomepage($entity, $type){
	$url = getEntityURI($entity, $type);
	if (!empty($url)) return $url . ".html";
	return "";	
}

/**
 * @brief Get the full filename with extension for a file associated with an entity.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for an entity.
 *
 * @param $type
 * A string containing the type of the entity specified.
 *
 * @return
 * A string containing the full filename with extension for a file associated with an entity.  If there is no associated file return an empty string.
 */
function getFilename($entity, $type){
	if (!empty($entity['unique_name']) && !empty($entity['file_ext'])) return $entity['unique_name'].".".$entity['file_ext'];
	return '';
}

/**
 * @brief Generate mepack:has-entry RDF/XML properties for all the entries (i.e. LocalPackEntry, RemotePackEntry and RelationshipEntry) of a specified Pack.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Pack or PackSnapshot.
 *
 * @param $type
 * A string containing the type of the entity specified.
 * 
 * @return 
 * A string containing mepack:has-entry RDF/XML properties for all the entries (i.e. LocalPackEntry, RemotePackEntry and RelationshipEntry) of a specified Pack.
 */
function getPackEntries($entity, $type){
	global $datauri, $sql, $entities;
	$xml = "";
	if ($type == "PackSnapshot") {
                $id = $entity['pack_id'];
		$versionsql = "and version = {$entity['version']}";
		$packurl=getEntityURI('PackSnapshot',$entity['id'],$entity);
	}
	else {
		$id = $entity['id'];
		$versionsql = "and version IS NULL";
		$packurl=getEntityURI('Pack',$entity['id'],$entity);
	}
	$lsql=$sql['LocalPackEntry'];
	if (stripos($lsql,'where')>0) $lsql.=" and ";
	else $lsql.=" where ";	
	$lsql.="pack_id=$id $versionsql";
	$lres=mysqli_query($con, $lsql);
	for ($e=0; $e<mysqli_num_rows($lres); $e++){
		$xml.="    <mepack:has-entry rdf:resource=\"$packurl/{$entities['LocalPackEntry']['url_subpath']}/".mysqli_result($lres,$e,'id')."\"/>\n";
	}
	$rsql=$sql['RemotePackEntry'];
	if (stripos($rsql,'where')>0) $rsql.=" and ";
        else $rsql.=" where ";
        $rsql.="pack_id=$id $versionsql";
	$rres=mysqli_query($con, $rsql);
        for ($e=0; $e<mysqli_num_rows($rres); $e++){
                $xml.="    <mepack:has-entry rdf:resource=\"$packurl/{$entities['RemotePackEntry']['url_subpath']}/".mysqli_result($rres,$e,'id')."\"/>\n";
        }
	$prsql=$sql['RelationshipEntry'];
	if (stripos($prsql,'where')>0) $prsql.=" and ";
        else $prsql.=" where ";
        $prsql.="context_id={$entity['id']} AND context_type='{$entities[$type]['db_entity']}'";
	//error_log($prsql);
        $prres=mysqli_query($con, $prsql);
	for ($e=0; $e<mysqli_num_rows($prres); $e++){
                $xml.="    <mepack:has-entry rdf:resource=\"$packurl/{$entities['RelationshipEntry']['url_subpath']}/".mysqli_result($prres,$e,'id')."\"/>\n";
        }
	return $xml;
}

/**
 * @brief Generate an RDF/XML meexp:Data entity that references a piece of data generate as an output from an Experiment Job.
 *
 * @param $job
 * An associative array of database fields mapped to values for a Job.
 *
 * @return
 * A string that contains an RDF/XML meexp:Data entity that references a piece of data generate as an output from an Experiment Job. Otherwise if the Job has no output it returns an empty string.
 */
function getOutput($job){
	global $datauri;
	$xml="";
	if (!empty($job['outputs_uri'])){		
		$uri=getEntityURI('Job', $job['id'], $job);
		$xml="<meexp:Data rdf:about=\"$uri/output\">\n";
		$xml.= "        <mebase:uri rdf:resource=\"$job[outputs_uri]\"/>\n";
		$xml.="      </meexp:Data>";
	}
	return $xml;
}

/**
 * @brief Generate an RDF/XML meexp:Data entity that references a piece of data that is an input to an Experiment Job.
 *
 * @param $job
 * An associative array of database fields mapped to values for a Job.
 *
 * @return
 * A string that contains an RDF/XML meexp:Data entity that references a piece of data that is an input to an Experiment Job. Otherwise if the Job has no input it returns an empty string.
 */
function getInput($job){
	$xml="";
	if (!empty($job['inputs_uri']) || !empty($job['inputs_data'])) {
		$uri=getEntityURI('Job', $job ['id'], $job);
		$xml="<meexp:Data rdf:about=\"$uri/input\">\n";
		if (!empty($job['inputs_uri'])) $xml.= "        <mebase:uri rdf:resource=\"$job[inputs_uri]\"/>\n";
		if (!empty($job['inputs_data'])) $xml.= "        <mebase:text rdf:datatype=\"&xsd;string\">$job[inputs_data]</mebase:text>\n";
		$xml.="      </meexp:Data>";
	}
        return $xml;
}

/**
 * @brief Get the URI of the Runnable entity of a specified Experiment Job.  (I.e. a Workflow or WorkflowVersion).
 *
 * @param $job
 * An associative array of database fields mapped to values for a Job.
 *
 * @return
 * A string containing the URI of the Runnable entity of a specified Experiment Job.  (I.e. a Workflow or WorkflowVersion).
 */
function getRunnable($job) {
	global $entities, $db_entity_mappings;
	$runnable_type = $db_entity_mappings[$job['runnable_type']];
	$runnable = $entities[$runnable_type]['url_subpath']."/".$job['runnable_id'];
	if (!empty($job['runnable_version'])) $runnable.="/versions/".$job['runnable_version'];
	return $runnable;
}

/**
 * @brief Get the path of the URI (to which the $datauri can be prepended) for the Runner (e.g. TavernaEnactor) of a specified Experiment Job.
 *
 * @param $job
 * An associative array of database fields mapped to values for a Job.
 *
 * @return
 * A string containing the URI (to which the $datauri can be prepended) for the Runner (e.g. TavernaEnactor) of a specified Experiment Job.
 */
function getRunner($job){
	return "runners/$job[runner_id]";
}

/**
 * @brief Determine either a myExperiment entity URI or generate an rdf:Description RDF/XML for a remote entity.  Either of which represents what the PackEntry is refering to.
 *
 * @param $pack_entry
 * An associative array of database fields mapped to values for a LocalPackEntry or RemotePackEntry.
 *
 * @return
 * A string containing either a myExperiment entity URI or rdf:Description RDF/XML for a remote entity.  Either of which represents what the PackEntry is refering to.
 */
function getProxyFor($pack_entry){
	global $entities, $db_entity_mappings;
	$xml="";
	if (isset($pack_entry['contributable_type'])){
		$entity_type_name = $db_entity_mappings[$pack_entry['contributable_type']];
		return getEntityURI($entity_type_name, $pack_entry['contributable_id'], $pack_entry);
	}
	$xml.="<rdf:Description rdf:about=\"".str_replace("&","&amp;",$pack_entry['uri'])."\"";
        if (!empty($pack_entry['alternate_uri'])) $xml.=">\n      <rdfs:seeAlso>\n        <rdf:Description rdf:about=\"".str_replace("&","&amp;",$pack_entry['alternate_uri'])."\"/>\n      </rdfs:seeAlso>\n    </rdf:Description>\n";
	else $xml.="/>";
        return $xml;
}

/**
 * @brief Get the URI for the Dataflow for a specified Workflow / WorkflowVersion, assuming that the user requesting RDF has permission to download the Workflow / WorkflowVersion entity.  If available and not already retrieved, also write the Dataflow RDF/XML out to a local file.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion.
 *
 * @param $type
 * A string containing the type of the entity specified, (i.e. Workflow or WorkflowVersion).
 *
 * @return
 * A string containing the URI for the Dataflow of specified Workflow of WorkflowVersion, assuming that the user requesting RDF has permission to download the Workflow / WorkflowVersion entity.  If not an empty string is returned.
 */
function getDataflow($entity,$type){
	if (canUserDownload($entity)) return getDataflowComponents($entity,$type,FALSE);	
	return "";
}

/**
 * @brief Get the URI for the Dataflow for a specified Workflow / WorkflowVersion.  If available and not already retrieved, also write the Dataflow RDF/XML out to a local file.
 *
 * @param $entity
 * An associative array of database fields mapped to values for a Workflow or WorkflowVersion.
 *
 * @param $type
 * A string containing the type of the entity specified, (i.e. Workflow or WorkflowVersion).
 *
 * @param $retrieve
 * A boolean.  TRUE if the Dataflow RDF/XML should be return, FALSE if the URI for the Dataflow should be returned instead.
 *
 * @return
 * A string containing the Dataflow RDF/XML or the URI of the Dataflow for a specified Workflow / WorkflowVersion.
 */
function getDataflowComponents($entity,$type,$retrieve=TRUE){
	global $datauri,$datapath,$myexppath,$use_rake;
	$comp_path=$datapath."dataflows/";
	$sql="select workflow_versions.*, content_types.mime_type from workflow_versions inner join content_types on workflow_versions.content_type_id=content_types.id where ";
	if ($type=="Workflow") $sql.="version='$entity[current_version]' and workflow_id='$entity[id]'";
	elseif ($type=="WorkflowVersion") $sql.="workflow_versions.id='$entity[id]'";
	$res=mysqli_query($con, $sql);
	$wfv=mysqli_fetch_assoc($res);
	$ent_uri=$datauri."workflows/$wfv[workflow_id]/versions/$wfv[version]";
        if ($wfv['mime_type']=='application/vnd.taverna.t2flow+xml') $df_uri="$ent_uri#dataflows/1";
        else $df_uri="$ent_uri#dataflow";
	$fileloc=$comp_path.$wfv['id'];
	if (!file_exists($fileloc) && $use_rake) writeDataflowToFile($wfv['id'],$ent_uri,$fileloc,$wfv['mime_type']);
	if (file_exists($fileloc)) $lines=file($fileloc);
	else return "";
	if (!isset($lines[0])) error_log("Error getting dataflow components for $df_uri (Workflow: " . $entity['id'] . ", version: " . $entity['current_version'].")");
	if (trim($lines[0])=="NONE") return "";
	elseif($retrieve==false) return $df_uri;
	return implode("",$lines);
}
	
/**
 * @brief Get the Creative Commons RDF/XML License attribute properties for a specified License.
 * 
 * @param $license
 * An associative array of database fields mapped to values for a License.
 *
 * @return
 * A string containing the Creative Commons RDF/XML License attribute properties for a specified License.
 */
function getLicenseAttributes($license){
	$sql="select license_options.* from license_attributes inner join license_options on license_attributes.license_option_id=license_options.id where license_attributes.license_id={$license['id']}";
	$res=mysqli_query($con, $sql);
	$xml="";
	for ($a=0; $a<mysqli_num_rows($res); $a++){
		$row=mysqli_fetch_array($res);
		$xml.="    <cc:{$row['predicate']} rdf:resource=\"{$row['uri']}\"/>\n";
	}   
	return $xml;
}

/**
 * @brief Generate RDF/XML properties for annotations of all types for a specified entity.
 *
 * @param $entity
 * An associative array of database fields mapped to values for an entity.
 *
 * @param $type
 * A string containing the type of the entity specified.
 * 
 * @return
 * A string containing RDF/XML properties for annotations of all types for a specified entity.
 */
function getAnnotations($entity, $type){
	global $entities, $db_entity_mappings;
	if (empty($entities[$type]['annotations'])) {
		return "";
	}
	$annotations = $entities[$type]['annotations'];
	$xml = "";
	foreach ($annotations as $annotation){
		if ($annotation == "Citation") $annot_sql=getAnnotationSQL($annotation, $entity['workflow_id'], $entity['version']);
		else $annot_sql=getAnnotationSQL($annotation, $type, $entity['id']);
		$res = mysqli_query($con, $annot_sql);
	        for ($a=0; $a<mysqli_num_rows($res); $a++){
			$row = mysqli_fetch_assoc($res);
                        $annot_uri=getEntityURI($annotation, $row['id'], $row);
                        $xml.="    <meannot:".$entities[$annotation]['annotation_property']." rdf:resource=\"$annot_uri\"/>\n";
		}
	}
	return $xml;
}

/**
 * @brief Generates RDF/XML properties for relationships between the specified Predicate and other Predicates.  (Currently this is just rdfs:subClassOf properties but this function will be expanded and rewritten when Predicate relationships are fully deployed).
 *
 * @param $entity
 * An associative array of database fields mapped to values for an entity.
 *
 * @return 
 * A string containing RDF/XML properties for relationships between the specified Predicate and other Predicates.
 */
function getPredicateRelations($entity){
	global $sql, $datauri;
	$xml="";
	$predicatesql=$sql['PredicateRelation'];
	if (!stripos('where',$sql['PredicateRelation'])) $predicatesql.=" where ";
        else $predicatesql.=" and ";
        $predicaterelsql=$predicatesql."subject_predicate_id=$entity[id]";
        $res=mysqli_query($con, $predicaterelsql);
	if ($res!==false){
	        for ($r=0; $r<mysqli_num_rows($res); $r++){
        	        $row=mysqli_fetch_assoc($res);
                	$xml.="    <rdfs:subClassOf rdf:resource=\"$entity[ontology_uri]/$row[object_predicate_id]\"/>\n";
	        }
	}
	return $xml;
}

/**
 * @brief Generate an associative array containing the subject, predicate and object representing the Relationship between two entities.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Relationship entity.
 *
 * @return
 * An associative array containing the subject, predicate and object representing the relationship between two entities.
 */
function getRelationshipSPO($entity){
	return array('subject'=>getRelationshipSubject($entity),'predicate'=>getRelationshipPredicate($entity),'object'=>getRelationshipObject($entity));
}

/**
 * @brief Generate a version 5 (RFC4122) UUID to provide a URN for the specific Relationship.  Using the URIs for the subject, predicate and object of the Relationship.
 *
 * @param $spo
 * An associative array containing the URIs for the subject, predicate and object of a Relationship.
 * 
 * @return
 * A string representing a version 5 (RFC4122) UUID to provide a URN for the specific Relationship.  Using the URIs for the subject, predicate and object of the Relationship.
 */
function getRelationshipURN($spo){
	require_once('class.uuid.php');
        return "urn:uuid:".UUID::generate(UUID::UUID_NAME_SHA1,UUID::FMT_STRING,$spo['subject'].$spo['predicate'].$spo['object']);
}

/**
 * @brief Generate an ore:ProxyFor RDF/XML property with an encapsulated mepack:Relationship entity for a specified Relationship entity.
 *
 * @param $entity
 * An associative array of database fields mapped to values for a Relationship entity.
 *
 * @return
 * A string containing an ore:ProxyFor RDF/XML property with an encapsulated mepack:Relationship entity for a specified Relationship entity.
 */
function getRelationship($entity){
	$spo=getRelationshipSPO($entity);
	$urn=getRelationshipURN($spo);
	return "    <ore:proxyFor>\n      <mepack:Relationship rdf:about=\"$urn\">\n        <rdf:subject rdf:resource=\"$spo[subject]\"/>\n        <rdf:predicate rdf:resource=\"$spo[predicate]\"/>\n        <rdf:object rdf:resource=\"$spo[object]\"/>\n      </mepack:Relationship>\n    </ore:proxyFor>\n";
}

/**
 * @brief Get the URI for the subject of a Relationship entity.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Relationship entity.
 *
 * @return 
 * A string containing the URI for the subject of a Relationship entity.
 */
function getRelationshipSubject($entity){
	return getRelationshipNode($entity['subject_id'],$entity['subject_type']);
}

/**
 * @brief Get the URI for the object of a Relationship entity.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Relationship entity.
 *
 * @return 
 * A string containing the URI for the object of a Relationship entity.
 */
function getRelationshipObject($entity){
        return getRelationshipNode($entity['objekt_id'],$entity['objekt_type']);
}

/**
 * @brief Get the URI for the predicate of a Relationship entity.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for a Relationship entity.
 *
 * @return 
 * A string containing the URI for the predicate of a Relationship entity.
 */
function getRelationshipPredicate($entity){
	return getPredicate($entity['predicate_id']);
}

/**
 * @brief Get the URI for a node (subject or object) of a Relationship entity.
 *
 * @param $id
 * The ID of the entity for which a URI needs to be determined.
 *
 * @param $type
 * The type of entity for which a URI needs to be determined.
 *
 * @return
 * A string containing the URI for a node (subject or object) of a Relationship entity.
 */
function getRelationshipNode($id,$type){
	global $datauri, $entities, $db_entity_mappings;
	$type = $db_entity_mappings[$type];
	$node_sql="select * from {$entities[$type]['table']} where id = $id";
	$res=mysqli_query($con, $node_sql);
	$row=mysqli_fetch_assoc($res);
	if ($type=="RemotePackEntry") return $row['uri'];
	if ($type=="LocalPackEntry"){
		$uri = $datauri.$db_entity_mappings[$row['contributable_type']]."/".$row['contributable_id'];
		if ($row['contributable_version']) return $uri."/versions/".$row['contributable_version'];
		return $uri;
	}
}

/**
 * @brief Generate the URI for a predicate based on its ID value in the database.
 * 
 * @param $id
 * An integer containing the ID value of predicate in the database.
 *
 * @return
 * A string containing the URI for a predicate based on its ID value in the database.
 */
function getPredicate($id){
	$predsql="select ontologies.uri as ontology_uri, predicates.title as predicate from predicates inner join ontologies on predicates.ontology_id=ontologies.id where predicates.id=$id";
	$res=mysqli_query($con, $predsql);
	return mysqli_result($res,0,'ontology_uri')."/".mysqli_result($res,0,'predicate');
}

/**
 * @brief Generate RDF/XML entities for all predicates in a user-defined Ontology.
 * 
 * @param $id
 * An integer containing the database ID of the user-defined Ontology.
 *
 * @return
 * A string containing RDF/XML entities for all predicates in a user-defined Ontology.
 */
function generatePredicatesRDF($id){
	global $sql;
	$predssql=$sql['ObjectProperty'];
	if (!stripos('where',$sql['ObjectProperty'])) $predssql.=" where ";
        else $predssql.=" and ";
	$predssql.=" ontology_id=$id";
	$res=mysqli_query($con, $predssql);
	$xml="";
	for ($p=0; $p<mysqli_num_rows($res); $p++){
		$xml.=generateEntityRDF(mysqli_fetch_assoc($res),"ObjectProperty");
	}
	return $xml;
}

/**
 * @brief Get static (i.e. the same for every user-generated Ontology) RDF/XML properties for user-generated Ontology.
 *
 * @param $entity
 * An associative array of database fields mapped to values for a Ontology entity.
 *
 * @return 
 * A string containing static (i.e. the same for every user-generated Ontology) RDF/XML properties for user-generated Ontology.
 */
function getStaticOntologyDetails($entity){
	return "    <dc:language rdf:datatype=\"&xsd;string\">en</dc:language>\n    <dc:publisher rdf:resource=\"http://www.myexperiment.org\"/>\n    <dc:format rdf:datatype=\"&xsd;string\">rdf/xml</dc:format>\n";
}

/**
 * @brief Get ore:aggregates RDF/XML properties for all items that a specified entity (e.g. Experiment, Pack or PackSnapshot) aggregates.
 * 
 * @param $entity
 * An associative array of database fields mapped to values for an entity that aggregates other entities.
 *
 * @param $type
 * The type of entity that aggregates other entities.
 *
 * @return
 * A string containing ore:aggregates RDF/XML properties for all items that a specified entity (e.g. Experiment, Pack or PackSnapshot) aggregates.
 */
function getOREAggregatedResources($entity, $type){
        global $sql, $entities, $db_entity_mappings;
        $xml="";
        if (!empty($entities[$type]['aggregates_resources'])) {
                if (!isset($entity['version'])) $entity['version'] = null;
                $arsql=getAggregatedResourceSQL($type, $entity);
                $res=mysqli_query($con, $arsql);
                for ($i=0; $i<mysqli_num_rows($res); $i++){
                        $row=mysqli_fetch_assoc($res);
			if (isset($row['entry_type']) && $row['entry_type']=="RemotePackEntry") {
				$fulluri=$row['uri'];
			}
			else{
        	                if (isset($row['runnable_id'])){
                	                $row['contributable_type']="Job";
                        	        $row['contributable_id']=$row['id'];
                                	$row['entry_type']="LocalPackEntry";
	                        }
				else {
					$row['contributable_type'] = $db_entity_mappings[$row['contributable_type']];
				}
        	        	$fulluri=getEntityURI($row['contributable_type'], $row['contributable_id'], $row);
			}
                	$xml.="    <ore:aggregates rdf:resource=\"".str_replace("&","&amp;",$fulluri)."\"/>\n";
                }
                if ($type=="Pack" || $type=="PackSnapshot"){
                        $prsql=$sql['RelationshipEntry'];
                        if (stripos($prsql,'where')>0) $prsql.=" and ";
                        else $prsql.=" where ";
                        $prsql.="context_id=$entity[id]";
                        $res=mysqli_query($con, $prsql);
                        for ($i=0; $i<mysqli_num_rows($res); $i++){
                                $row=mysqli_fetch_assoc($res);
                                $relurn=getRelationshipURN(getRelationshipSPO($row));
                                $xml.="    <ore:aggregates rdf:resource=\"$relurn\"/>\n";
                        }
                }
        }
        return $xml;
}

/**
 * @brief Get the URI for the RDF graph representation of a specific aggregating entity.
 *
 * @param $entity
 * An associative array of database fields mapped to values for an entity that aggregates other entities.
 *
 * @param $type
 * The type of entity for which the URI of the RDF graph representation needs to be determined.
 *
 * @return
 * A string containing the URI for the RDF graph representation of a specific aggregating entity.
 */
function getOREDescribedBy($entity,$type){
        return getEntityURI($type, $entity['id'], $entity).".rdf";
}

/**
 * @brief Generate the RDF/XML properties for a specified access rights Policy entity.
 *
 * @param $policy
 * An associative array of database fields mapped to values for a Policy entity.
 *
 * @return 
 * A string containing the RDF/XML properties for the access rights Policy entity.
 */
function getPolicy($policy){
        $policy_url=getEntityURI("Policy", $policy['policy_id'], $policy);
        $policy_xml="";
        $perms=getPermissions($policy['policy_id']);
        if (!isset($policy['share_mode'])) $policy=addShareAndUpdateMode($policy);
        $policy_xml.=getContributorPermissions($policy['contributor_type'],$policy['contributor_id']);
        $policy_xml.=getShareModeAccesses($policy['share_mode']);
        $policy_xml.=getUpdateModeAccesses($policy['update_mode'], $policy['share_mode'],$perms);
        $policy_xml.=getPermissionAccesses($perms,$policy_url);
        return $policy_xml;
}

/**
 * @brief Generate the contributor permissions RDF/XML for a specified Policy.
 * 
 * @param $contrib_type
 * A string containing the type of Contribution the Policy is for.
 *
 * @param $contrib_id
 * A string containing the ID of the Contribution the Policy is for.
 *
 * @return
 * A string containg the contributor permissions RDF/XML for a specified Policy.
 */
function getContributorPermissions($contrib_type,$contrib_id){
        global $datauri, $entities, $db_entity_mappings;
        $contrib_type=$db_entity_mappings[$contrib_type];
        $contributor=$entities[$contrib_type]['url_subpath']."/".$contrib_id;
        return "    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;View\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Download\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n    <snarm:has-access>\n      <snarm:RestrictedAccess>\n        <snarm:has-accesser rdf:resource=\"".$datauri."$contributor\"/>\n        <snarm:has-access-type rdf:resource=\"&mespec;Edit\"/>\n      </snarm:RestrictedAccess>\n    </snarm:has-access>\n";
}

/**
 * @brief Generate RDF/XML for a Policy with a specific share mode.
 *
 * @param $sm
 * An integer representing the share mode for which an RDF/XML representation is required.
 *
 * @return
 * A string containing RDF/XML for a Policy with a specific share mode.
 */
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

/**
 * @brief Generate RDF/XML for a Policy with a specific update mode. (Also depependent on the Policy's share mode).
 *
 * @param $um   
 * An integer representing the update mode for which an RDF/XML representation is required.
 *
 * @param $sm
 * An integer representing the share mode for which an RDF/XML representation is required.
 *
 * @return
 * A string containing RDF/XML for a Policy with a specific update mode.
 */
function getUpdateModeAccesses($um,$sm){
        switch ($um){
                case 0:
                        if ($sm==0 && is_int($sm)) return "    <snarm:has-access rdf:resource=\"&mespec;PublicEdit\"/>\n";
                        elseif ($sm==1 || $sm==3) return "    <snarm:has-access rdf:resource=\"&mespec;FriendsEdit\"/>\n";
                case 1:
			
                        return "    <snarm:has-access rdf:resource=\"&mespec;FriendsView\"/>\n    <snarm:has-access rdf:resource=\"&mespec;FriendsDownload\"/>\n    <snarm:has-access rdf:resource=\"&mespec;FriendsEdit\"/>\n";
                default:
                        return "";
                }
        return "";
}

/**
 * @brief Generate RDF/XML for explicitly defined permissions for a Policy.
 * 
 * @param $perms
 * An array containing a list of explicitly defined permissions for a Policy.
 *
 * @return
 * A string containing RDF/XML for explicitly defined permissions for a Policy.
 */
function getPermissionAccesses($perms){
        global $datauri, $entities, $db_entity_mappings;
        $accesses="";
        $a=3;
        for ($p=0; $p<sizeof($perms); $p++){
                $perms[$p]['contributor_type'] = $db_entity_mappings[$perms[$p]['contributor_type']];
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

/**
 * @brief Generate RDF/XML for the Dataflow (and potentially nested Dataflows thereof) of a particular Workflow or WorkflowVersion.
 * 
 * @param $dataflows
 * A multidimensional associative array of one or more Dataflows (where additional Dataflows are nested within the main Dataflow) that contains information about the Dataflows and the components which they contain.
 * 
 * @param $ent_uri
 * A string containing the entity URI of the Workflow or WorkflowVersion that has the Dataflow(s) specified.
 * 
 * @return 
 * A string containing RDF/XML for the Dataflow (and potentially nested Dataflows thereof) of a particular Workflow or WorkflowVersion.
 */
function generateDataflows($dataflows,$ent_uri){
        $dtstr=array('dcterms:title','dcterms:description','dcterms:identifier','mecomp:processor-type','mecomp:processor-script','mecomp:service-name','mecomp:authority-name','mecomp:service-category','mecomp:example-value');
        $dturi=array('mecomp:processor-uri');
        $rdf="";
        $dfmap=array();
        $components="";
        foreach($dataflows as $dfuri => $dataflow){
                $rdf.="  <mecomp:Dataflow rdf:about=\"$dfuri\">\n";
                if (isset($dataflow['id'])){
                        if (sizeof($dfmap)==0) $dfmap=generateDataflowMappings($dataflows);
                        $rdf.="    <dcterms:identifier rdf:datatype=\"&xsd;string\">$dataflow[id]</dcterms:identifier>\n";
                        unset($dataflow['id']);
                }
                foreach ($dataflow as $cnum => $comp){
                        $comptype=$comp['type'];
                        $rdf.="    <mecomp:has-component>\n      <mecomp:$comptype rdf:about=\"".$dfuri."/components/$cnum\">\n";
                        foreach ($comp['props'] as $prop){
                                if (in_array($prop['type'],$dtstr) && isset($prop['value'])){
                                       $rdf.="        <".$prop['type']." rdf:datatype=\"&xsd;string\">".convertToXMLEntities($prop['value'])."</".$prop['type'].">\n";
                                }
                                elseif (in_array($prop['type'],$dturi)){
                                        if (isset($prop['value'])) $rdf.="        <".$prop['type']." rdf:resource=\"".convertToXMLEntities($prop['value'])."\"/>\n";
                                }
                                elseif ($prop['type']=="mecomp:executes-dataflow"){
                                        if (substr($prop['value'],0,7)!="http://") $prop['value']=$dfmap[$prop['value']];
                                        $rdf.="        <".$prop['type']." rdf:resource=\"$prop[value]\"/>\n";
                                }
                                elseif (isset($prop['value'])) $rdf.="        <".$prop['type']." rdf:resource=\"$dfuri/components/".urlencode($prop['value'])."\"/>\n";
                        }
                        $rdf.="        <mecomp:belongs-to-workflow rdf:resource=\"$ent_uri\"/>\n      </mecomp:$comptype>\n    </mecomp:has-component>\n";
                }
                $rdf.="  </mecomp:Dataflow>\n\n";
        }
        return $rdf;
}


?>
