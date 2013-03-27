<?php
function getAggregatedResourceSQL($type,$id,$version = null){
	global $sql;
        if ($type=="experiments"){
                return $sql['jobs']. " where experiment_id=$id";
        }
        if ($type=="packs" || $type == "pack_versions"){
		if (empty($version)) {
                        $versionsql = "IS NULL";
                }
                else {
                        $versionsql = "= $version";
                }
                return "select id as proxy_id, contributable_id, contributable_version, contributable_type, '' as uri, 'LocalPackEntry' as entry_type, version as entry_version from pack_contributable_entries where pack_id=$id and version $versionsql union select id as proxy_id, '' as contributable_id, '' as contributable_version, '' as contributable_type, uri, 'RemotePackEntry' as entry_type, version as entry_version from pack_remote_entries where pack_id=$id";
        }
        return "";
}
function getOREAggregatedResources($entry,$type){
        global $datauri,$mappings,$sql,$tables,$userid,$ingroups,$ontent, $iterations;
	$xml="";
        if ($type=="experiments" || $type=="packs" || $type=="pack_versions") {
		if (!isset($entry['version'])) $entry['version'] = null;
                $arsql=getAggregatedResourceSQL($type,$entry['id'],$entry['version']);
                $res=mysql_query($arsql);
                for ($i=0; $i<mysql_num_rows($res); $i++){
                        $row=mysql_fetch_assoc($res);
			if (isset($row['contributable_type']) && $row['contributable_type']=="Blob")  $row['contributable_type']="File";
                        if (isset($row['runnable_id'])){
				$row['contributable_type']="Job";
				$row['contributable_id']=$row['id'];
				$row['entry_type']="LocalPackEntry";
			}
                        if (isset($row['contributable_version']) && $row['contributable_version']>0){
                                $row['contributable_id']=getVersionID($row);
                                if ($row['contributable_type']=="Workflow") $row['contributable_type']="WorkflowVersion";
				elseif ($row['contributable_type']=="File") $row['contributable_type']="FileVersion";
				elseif ($row['contributable_type']=="Pack") $row['contributable_type']="PackSnapshot";
                        }
			$entity_type = array_search($row['contributable_type'], $ontent);
                        if (isset($row['runnable_id'])) $row['contributable_type']="Job";
//			error_log(implode(",", array_keys($row)). "; ".implode(",", $row));
                        if ($row['entry_type']=="RemotePackEntry") $fulluri=$row['uri'];
                        else $fulluri=getEntityURI($entity_type,$row['contributable_id'],$row);
                        $xml.="    <ore:aggregates rdf:resource=\"".str_replace("&","&amp;",$fulluri)."\"/>\n";
                }
		if ($type=="packs" || $type=="pack_versions"){
			$prsql=$sql['relationship_entries'];
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
        return getEntityURI($type,$entity['id'],$entity).".rdf";
}
?>
