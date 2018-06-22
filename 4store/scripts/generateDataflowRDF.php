#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/generateDataflowRDF.php
 * @brief Generates RDF to represent the dataflow of publically downloadable workflows on a particular myExperiment instance.
 * @version beta
 * @author David R Newman
 * @details This script queries the database to determine publically downloadable workflows.  It then downloads those public workflows for which does not already have dataflow RDF generated and parses the XML returned to generate this dataflow RDF which is then stored locally.
 */

	include('include.inc.php');
	require_once('functions/rdf.inc.php');
	/** @brief A handler for a piped stream containing a list of all the local Dataflow RDF files that have content (i.e. not the the 4 character string NONE). */
	$ph=popen("cd ${datapath}dataflows/; du -b * | awk 'BEGIN{FS=\" \"}{ if ($1!=\"4\") print $2 }'",'r');
	/** @brief A string listing all the local Dataflow RDF files that have content. */
	$temp="";
	while (!feof($ph)){
		$temp.=fread($ph,8192);
	}
	/** @brief An array listing all the local Dataflow RDF files that have content. */
	$curfiles=explode("\n",trim($temp));
	pclose($ph);
	/** @brief An array containing quoted strings of all the Dataflow content types. */
	$dfct=array();
	foreach ($dataflow_contenttypes as $ct) $dfct[]="'$ct'";
	/** @brief A string containing an SQL query to find all the publically downloadable workflows that have dataflows that can be represented in RDF. */
        $sql="select workflow_versions.id as wfvid from workflow_versions inner join content_types on workflow_versions.content_type_id = content_types.id inner join contributions on workflow_versions.workflow_id=contributions.contributable_id and contributions.contributable_type='Workflow' inner join policies on contributions.policy_id=policies.id where content_types.mime_type in (".implode(',',$dfct).") and content_types.category='Workflow' and policies.share_mode = 0";
	/** @brief An SQL result for all the publically downloadable workflows that have dataflows that can be represented in RDF. */
        $res=mysqli_query($con, $sql);
	/** @brief An array containing all the workflow version IDs for publically downloadable workflows that have dataflows that can be represented in RDF.  These are used as the filenames for the local files that store Dataflow RDF. */
	$dbfiles=array();
	for ($i=0; $i<mysqli_num_rows($res); $i++){
		$dbfiles[]=mysqli_result($res,$i,'wfvid');
	}
	foreach ($curfiles as $cf){
		if (!in_array($cf,$dbfiles)){
			exec("echo -n 'NONE' > ".$datapath."dataflows/$cf");
			echo "[".date("H:i:s")."] Removed RDF for components of workflow_versions $cf because permissions have changed or workflow has been deleted\n";
		}
	}
	/** @brief An array containing any new workflow versions for publically downloadable workflows that have dataflows that can be represented in RDF.  So that Dataflow RDF for these can be generated. */
	$newfiles=array();
	foreach ($dbfiles as $df){
		if (!in_array($df,$curfiles)){
			$newfiles[]=$df;
		}
	}
	/** @brief A string containg the path to where the local Dataflow RDF files are stored. */
	$filelocdir=$datapath."dataflows/";
	foreach ($newfiles as $wfvid){
                if (file_exists($filelocdir.$wfvid)) continue;
                $sql="select workflow_versions.*, content_types.mime_type from workflow_versions inner join content_types on workflow_versions.content_type_id=content_types.id where workflow_versions.id='$wfvid'";
                $wfv=mysqli_fetch_assoc(mysqli_query($con, $sql);));
                $wget="wget -q -O /tmp/wfvc_$wfvid.xml -o /dev/null '${datauri}workflow.xml?id=$wfv[workflow_id]&versions=$wfv[version]&elements=components'";
                exec($wget);
                $parsedxml=parseXML(implode("",file("/tmp/wfvc_$wfvid.xml")));
		if (!isset($parsedxml[0]['children'][0]['children'])) continue;
                $wfvc=$parsedxml[0]['children'][0]['children'];
		$ent_uri=$datauri."workflows/$wfv[workflow_id]/versions/$wfv[version]";
                $dataflows=tabulateDataflowComponents($wfvc,$ent_uri,$wfv['mime_type']);
                $fh=fopen($filelocdir.$wfvid,'w');
                if ($dataflows) fwrite($fh,generateDataflows($dataflows,$ent_uri));
                else fwrite($fh,"NONE");
                fclose($fh);
		echo "[".date("H:i:s")."] Generated RDF for components of workflow_versions $wfvid\n";
        }

?>
