<?php
function getAggregatedResourceSQL($type,$id){
	global $sql;
        if ($type=="experiments"){
                return $sql['jobs']. " where experiment_id=$id";
        }
        if ($type=="packs"){
                return "select id as proxy_id, contributable_id as id, contributable_version as version, contributable_type as type, '' as uri, 'LocalPackEntry' as entry_type from pack_contributable_entries where pack_id=$id union select id as proxy_id, '' as id, '' as version, '' as type, uri, 'RemotePackEntry' as entry_type from pack_remote_entries where pack_id=$id";
        }
        return "";
}
function getOREAggregatedResources($entry,$type){
        global $datauri,$mappings, $sql,$tables,$userid,$ingroups,$ontent, $iterations;
	$xml="";
        if ($type=="experiments" || $type=="packs"){
                $arsql=getAggregatedResourceSQL($type,$entry['id']);
                $res=mysql_query($arsql);
                for ($i=0; $i<mysql_num_rows($res); $i++){
                        $row=mysql_fetch_assoc($res);
			if ($row['type']=="Blob")  $row['type']="File";
                        if (isset($row['runnable_id'])) $row['type']="Job";
			$etype=array_search($row['type'],$ontent);
			$entrytype=array_search($row['entry_type'],$ontent);
                        if ($row['version']){
                                $row['id']=getVersionID($row);
                                if ($row['type']=="workflows") $row['type']="workflow_versions";
                        }
                        if (isset($row['runnable_id'])) $row['type']="jobs";
                        if ($entrytype=="remote_pack_entries") $fulluri=$row['uri'];
                        else $fulluri=getEntityURI($etype,$row['id'],$row);
                        $xml.="    <ore:aggregates rdf:resource=\"".str_replace("&","&amp;",$fulluri)."\"/>\n";
                }
        }
        return $xml;
}
?>
