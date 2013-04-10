<?php
/**
 * @file inc/functions/sql.inc.php
 * @brief Functions to help build SQL queries to retrieve data from the myExperiment database.
 * @version beta
 * @author David R Newman
 * @details Functions to help build SQL queries to retrieve data from the myExperiment database.
 */
function setUserAndGroups($sql,$userid=0,$ingroups=0){
        return str_replace(array('?','~'),array($userid,$ingroups),$sql);
}
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
function addWhereClause($sql,$whereclause){
	if (stripos($sql,'where')>0) return "$sql and ($whereclause)";
	return "$sql where $whereclause";
}

function getAggregatedResourceSQL($type,$id,$version = null){
        global $sql;
        if ($type=="Experiment"){
                return $sql['Job']. " where experiment_id=$id";
        }
        if ($type=="Pack" || $type == "PackSnapshot"){
                if (empty($version)) {
                        $versionsql = "IS NULL";
                }
                else {
                        $versionsql = "= $version";
                }
                return "select id, contributable_id, contributable_version, contributable_type, '' as uri, 'LocalPackEntry' as entry_type, version as entry_version from pack_contributable_entries where pack_id=$id and version $versionsql union select id, '' as contributable_id, '' as contributable_version, '' as contributable_type, uri, 'RemotePackEntry' as entry_type, version as entry_version from pack_remote_entries where pack_id=$id";
        }
        return "";
}

