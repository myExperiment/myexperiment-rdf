<?php
/**
 * @file inc/functions/sql.inc.php
 * @brief Functions to help build SQL queries to retrieve data from the myExperiment database.
 * @version beta
 * @author David R Newman
 * @details Functions to help build SQL queries to retrieve data from the myExperiment database.
 */

/**
 * @brief Set the user ID and group IDs the user is part of to a SQL query to find all entities viewable by a particular user.
 *
 * @param $sql
 * The SQL query for which the user ID and group IDs are to be added.
 *
 * @param $userid
 * An integer representing he user ID which is to be added to the SQL query.
 * 
 * @param $ingroups
 * An array containing the group IDs which are to be added to the the SQL query.
 * 
 * @return 
 * The SQL query provided with the specified user ID and group IDs added.
 */
function setUserAndGroups($sql,$userid=0,$ingroups=0){
        return str_replace(array('?','~'),array($userid,$ingroups),$sql);
}

/**
 * @brief Generates an SQL query for a particular type of entity restricted by specified where subclauses and which user/groups instances of this entity are shared with.
 * 
 * @param $type
 * A string containing the type of entity for which an SQL query is to be generated.
 * 
 * @param $wclause
 * An array containing triplets (field-equal/like/in-value) to build restrictions in the where clause of the SQL query.
 *
 * @param $userid
 * An integer representing he user ID which is to be added to the SQL query.
 *
 * @param $ingroups
 * An array containing the group IDs which are to be added to the the SQL query.
 *
 * @return
 * An SQL query for a particular type of entity with restrictions specified (including whether the entity is viewable to either the user or groups specified). 
 */
function setRestrict($type,$wclause=array(),$userid=0,$ingroups=0){
        global $mappings, $tables, $sql, $domain;
	$whereclause="";
        for ($w=0; $w<sizeof($wclause); $w=$w+3){
                if (!strpos($wclause[$w],'.')) $wclause[$w]=$tables[$type].".".$wclause[$w];
                if ($wclause[$w+1]=="=" || $wclause[$w+1]=="like") $wclause[$w+2]="'".$wclause[$w+2]."'";
                else if ($wclause[$w+1]=="in") $wclause[$w+2]="(".$wclause[$w+2].")";
                $whereclause.=" ".$wclause[$w]." ".$wclause[$w+1]." ".$wclause[$w+2];
                if ($w<sizeof($wclause)-3) $whereclause.=" and";
        }
        $cursql=$sql[$type];
        $csqlbits=spliti("group by",$cursql);
        if (stripos($cursql,"where")) $whereand="and";
        else $whereand="where";
        if (sizeof($csqlbits) > 1) $csqlbits[1]="group by ".$csqlbits[1];
	else $csqlbits[1]="";
        if ($whereclause) $retsql=$csqlbits[0]." $whereand $whereclause ".$csqlbits[1];
        else $retsql=$cursql;
        if ($domain!="private") $retsql=setUserAndGroups($retsql,$userid,$ingroups);
        return $retsql;
}

/**
 * @brief Add the first or an additional sub-clause to the where clause of an SQL query.
 *
 * @param $sql
 * A string containing the SQL query for which a sub-clause is to the where clause is to be added.
 *
 * @param $whereclause
 * The where sub-clause to be added to the SQL query.
 *
 * @return
 * A string representing the SQL query with the specified where sub-clause added.
 */
function addWhereClause($sql, $whereclause){
	if (stripos($sql,'where')>0) return "$sql and ($whereclause)";
	return "$sql where $whereclause";
}

/**
 * @brief Generates an SQL query to find the aggregated resources for a particular Experiment, Pack or PackSnapshot.
 *
 * @param $type
 * The type of entity for which an SQL query to find its aggregated resources is to be generated.
 *
 * @param $entity
 * An associative array of database fields mapped to values for an aggregatable entity.
 * 
 * @return
 * A string containing the SQL query to find the aggregated resources for a particular entity.
 */
function getAggregatedResourceSQL($type,$entity){
        global $sql;
	$cursql = "";
        if ($type=="Experiment"){
		$job_sql = preg_replace("/ from /i", ", 'Job' as contributable_type FROM ", $sql['Job'], 1);
                return $job_sql . " where experiment_id={$entity['id']}";
        }
        if ($type=="Pack" || $type == "PackSnapshot"){
                if (empty($entity['version'])) {
                        $versionsql = "IS NULL";
			$id = $entity['id'];
                }
                else {
                        $versionsql = "= {$entity['version']}";
			$id = $entity['pack_id'];
                }
		$cursql = "select id, contributable_id, contributable_version, contributable_type, '' as uri, 'LocalPackEntry' as entry_type, version as entry_version from pack_contributable_entries where pack_id=$id and version $versionsql union select id, '' as contributable_id, '' as contributable_version, '' as contributable_type, uri, 'RemotePackEntry' as entry_type, version as entry_version from pack_remote_entries where pack_id=$id and version $versionsql";
        }
        return $cursql;
}

/**
 * @brief Generate an SQL query that will find all annotations of a specified type for a specified entity.
 *
 * @param $type
 * A string containing the type of annotation for which the SQL query should instances.
 *
 * @param $p1
 * The parameter to swap for the first unknown of annotation_where_clause for the specified type of anotation.
 *
 * @param $p2
 * The parameter to swap for the second unknown of annotation_where_clause for the specified type of anotation.
 *
 * @return
 * A string containing an SQL query that will find all annotations of a specified type for a specified entity.
 */
function getAnnotationSQL($type, $p1, $p2){
        global $sql, $entities;
	if (!empty($entities[$p1]['db_entity'])) $p1 = $entities[$p1]['db_entity'];
	if (!empty($entities[$p2]['db_entity'])) $p2 = $entities[$p2]['db_entity'];
        $annotation_sql = addWhereClause($sql[$type], str_replace('~', $p2, str_replace('?', $p1, $entities[$type]['annotation_where_clause'])));
        return $annotation_sql;
        
}


