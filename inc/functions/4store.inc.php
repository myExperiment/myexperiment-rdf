<?php
/**
 * @file inc/functions/4store.inc.php
 * @brief Functions supporting the querying of 4Store knowledge bases. 
 * @version beta
 * @author David R Newman
 * @details Functions for supporting the building and execution of SPARQL and other queries against the various 4Store knowledge bases.
 */

/**
 * @brief Get the path to where 4Store-related scripts are located.
 *
 * @return
 * A string containing the path to where 4Store-related scripts are located.
 */ 
function getScriptPath(){
	global $lddir;
        return $lddir."4store/scripts";
}


/**
 * @brief Get the path to where 4Store eexcutables such as 4s-query are located.  (This will be deprecated when the codebase is rewritten to use 4Store HTTP query interface).
 *
 * @return
 * A string containing the path to where 4Store eexcutables such as 4s-query are located.
 */
function getPath(){
	global $store4execpath;
	return $store4execpath;
}

/**
 * @brief Setup environment allowing a query to be run successfully from the command line.  Namely export the LD_LIBRARY_PATH for 4Store and export the variable BANG so that exclaimation marks can be used as part of a SPARQL query. (This will be deprecated when the codebase is rewritten to use 4Store HTTP query interface).
 *
 * @return 
 * A string containing environment setup to allow  a query to be run successfully from the command line.  Namely export the LD_LIBRARY_PATH for 4Store and export the variable BANG so that exclaimation marks can be used as part of a SPARQL query.
 */
function getQueryPreamble(){
	return "export LD_LIBRARY_PATH=/usr/local/lib; export BANG='!'; "; 
}


/**
 * @brief Pre-process a query to escape quote marks and add PREFIX statements for known namespaces whoese prefixes have been used in the query body.
 *
 * @param $query
 * The query string to be pre-processed.
 *
 * @return
 * A string containing the query after pre-processing, (i.e. the escaping of quote marks and the adding of PREFIX statements).
 */
function preProcessQuery($query){
	global $domain;
	$query=str_replace('"',"'",$query);
	$query=str_replace("\'","'",$query);
        preg_match_all("/([^ \t\n:]+):[^ \t\n:]+/",$query, $namespaces);
        preg_match_all('/PREFIX ([\w]+):[^\n]+/i',$query, $prefixes);
        $allprefixes=getUsefulPrefixesArray($domain, true);
        foreach ($namespaces[1] as $ns){
        	if (!in_array($ns,$prefixes[1])){
                	if (!empty($allprefixes[$ns])){
                        	$query="PREFIX $ns: <".$allprefixes[$ns].">\n".$query;
                                $prefixes[1][]=$ns;
                        }
                }
        }
	$qlines=explode("\n",$query);
	foreach ($qlines as $q => $line){
		if (preg_match("/^[ \t]*BASE/",$line)){
			array_splice($qlines,$q,1);
			array_unshift($qlines,$line);
			break;
		}
	}
	$query=implode("\n",$qlines);
	return $query;
}

/**
 * @brief Test to see where the result string from the SPARQL query indicates it has failed.
 * 
 * @param $res
 * The results string from a SPARQL query.
 *
 * @return 
 * A boolean.  TRUE if the query has failed, FALSE otherwise.
 */
function queryFailed($res){
        if (preg_match("/^Query Failed:/",$res)) return TRUE;
        return FALSE;
}

/**
 * @brief List all the named graphs in a specified knowledge base.  (This will be refactored when the codebase is redesigned to use 4Store's HTTP interface).
 *
 * @param $kb
 * A string containing the knowledge base for which a list of the name graphs is required.
 * 
 * @return
 * A string containing a list of the named graphs for the specified knowledge base.
 */
function listNamedGraphs($kb){
	$cmd=getScriptPath()."/sqs.sh $kb list-graphs";
	$ph=popen($cmd,"r");
	$data="";
	while ($line=fgets($ph,4096)){
		$data.=str_replace(" ","",$line);
	}
	return $data;
}

/**
 * @brief Add a named graph specified by a URL to a specified knowledge base.  (This may be refactored when the codebase is redesigned to use 4Store's HTTP interface).
 *
 * @param $kb
 * A string containing the knowledge base for which a specified name graph is to be added.
 * 
 * @param $url
 * A string containing the URL for the named graph to be added.
 * 
 * @return
 * A string containing the message returned by the script that added the graph.
 */
function addNamedGraph($kb, $url){
	$cmd=getScriptPath()."/sqs.sh $kb remove $url";
	$ph=popen($cmd,"r");
	while ($line=fgets($ph,4096)){
                $data.=str_replace(" ","",$line);
        }
	$cmd=getScriptPath()."/sqs.sh $kb add $url";
        $ph=popen($cmd,"r");
        while ($line=fgets($ph,4096)){
                $data.=str_replace(" ","",$line);
        }
	return $data;
}

/**
 * @brief Remove a named graph specified by a URL to a specified knowledge base.  (This may be refactored when the codebase is redesigned to use 4Store's HTTP interface).
 *
 * @param $kb
 * A string containing the knowledge base for which a specified name graph is to be removeded.
 * 
 * @param $url
 * A string containing the URL for the named graph to be removeed.
 * 
 * @return
 * A string containing the message returned by the script that added the graph.
 */
function removeNamedGraph($kb, $url){
	$cmd=getScriptPath()."/sqs.sh $modelname remove $url";
	$ph=popen($cmd,"r");
	while ($line=fgets($ph,4096)){
                $data.=str_replace(" ","",$line);
        }
	return $data;
}

/**
 * @brief Send a SPARQL query (and retrieve results) to a specified knowledge base.  (This will be refactored when the codebase is redesigned to use 4Store's HTTP interface).
 *
 * @param $kb
 * A string containing the knowledge base for which a SPARQL query is to be submitted.
 *
 * @param $query
 * A string containing the SPARQL query to be submitted to a specified knowledge base.
 *
 * @param $format
 * A string containing the format required from the resukts of the SPARQL query, (e.g. sparql, test or json).
 *
 * @param $softlimit
 * An integer containing the soft limit (a 4Store parameter for how much resource to put towards finding results) for the query being executed.
 *
 * @param $reasoning
 * A boolean specifying whether reasoning (provided by 4store-reasoner) should be used for the query specified.
 * 
 * @return
 * A string containing the results from the SPARQL query specified in the format also specified.
 */
function callSPARQLQueryClient($kb,$query,$format="sparql",$softlimit=1000,$reasoning=0){
	global $timetaken, $errs;
	$errs=array();
        $data="";
	$oquery=$query;
	$query=str_replace(array('!',"\r","\n","\t","  "),array('${BANG}',' ',' ',' ',' '),$query);
	$reason="";
	if ($reasoning==1 || (is_string($reasoning) && (strtolower($reasoning)=="true" || strtolower($reasoning)=="yes"))) $reason="-R CPDR";
        $cmd=getQueryPreamble().getPath()."4s-query $reason -f $format $kb \"".$query."\" -s $softlimit";
	$start=time();
        $ph=popen($cmd,'r');
	$data="";
        while (!feof($ph)) {
                $data.=fgets($ph, 4096);
        }
        pclose($ph);
	$stop=time();
	$timetaken=$stop-$start;
        if ($data){
		$rbits=explode('<!--',$data);
                unset($rbits[0]);
                foreach($rbits as $rb){
                        $rbb=explode('-->',$rb);
                        $errs[]=$rbb[0];
                }
		if (sizeof($errs)>0) $status="errors";
		else $status="succeeded";
	}
	else $status='failed';
	$sql="insert into sparql_queries values('','$kb','$oquery',$start,$stop,'$status','$data')";
	mysqli_query($con, $sql);
        return $data;
}

/**
 * @brief Send multiple SPARQL queries in parallel (and retrieve results) to a specified knowledge base.  (This will be refactored when the codebase is redesigned to use 4Store's HTTP interface).
 *
 * @param $kb
 * A string containing the knowledge base for which a SPARQL query is to be submitted.
 *
 * @param $queries
 * An array of strings containing the SPARQL queries to be submitted to a specified knowledge base.
 *
 * @param $softlimit
 * An integer containing the soft limit (a 4Store parameter for how much resource to put towards finding results) for the queries being executed.
 * 
 * @param $timeout
 * A integer containing the timeout for all SPARQL queries to return results.
 *
 * @param $reasoning
 * A boolean specifying whether reasoning (provided by 4store-reasoner) should be used for the queries specified.
 * 
 * @return
 * A string containing the results from the SPARQL queries specified in the format also specified.
 */
function callSPARQLQueryClientMultiple($kb,$queries,$softlimit=1000,$timeout=30,$reasoning=0){
	global $lddir, $datapath;
	$qfp=md5(time().rand());
	$qids=array_keys($queries);
	if ($reasoning==1 || (is_string($reasoning) && (strtolower($reasoning)=="true" || strtolower($reasoning)=="yes"))) $reason='"-R CPDR"';
	foreach($qids as $qid){
		$filenames[$qid]="${datapath}tmp/queries/".$qfp."_$qid";
		$cmd=$lddir."4store/scripts/runquery.sh $kb \"".$queries[$qid]."\" $softlimit ".$filenames[$qid]." $reason &";
		exec($cmd);
	}

	$start=time();
	$check="ps aux | grep '$qfp' | grep -v 'grep' | wc -l";
	while ($start+$timeout>time()){
		$ph=popen($check,'r');
		$queriesleft=fread($ph,8192);
		pclose($ph);
		if ($queriesleft==0) break;
		sleep(1);
	}
	$results=array();	
	foreach($qids as $qid){
		if (file_exists($filenames[$qid])){
			$fh=fopen($filenames[$qid],'r');
			while(!feof($fh)){
				$data=fread($fh,8192);
                                if (!isset($results[$qid])) $results[$qid]=$data;
				else $results[$qid].=$data;
			}
			exec("rm -f ".$filenames[$qid]);
			fclose($fh);
		}
	}			
        return $results;
}

/**
 * @brief Tests whether is is possible to reach the SPARQL endpoint for a specified knowledge base.  (A new test for this needs to be found, currently just returning TRUE).
 *
 * @param $kb
 * A string containing the knowledge base for which the SPARQL endpoint is to be contacted.
 * 
 * @return
 * A boolean. TRUE if the SPARQL endpoint specified can be contacted, FALSE otherwise.
 */
function testSPARQLQueryClient($kb){
	return TRUE;
}

/**
 * @brief Perform a full test (with query) on a myExperiment SPARQL endpoint for a specified knowledge base.
 *
 * @param $kb
 * A string containing the myExperiment knowledge base for which the SPARQL endpoint is to be tested.
 *
 * @return
 * A boolean. TRUE if the SPARQL endpoint for the specified myExperiment knowledge base returns the expected SPARQL query result, FALSE otherwise.
 */
function myexperimentFullTestSPARQLQueryClient($kb){
        global $ontopath;
	$query="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select ?x where {?x rdfs:isDefinedBy <".$ontopath."snarm/>}";
        $cmd=getQueryPreamble().getPath()."4s-query $kb \"".$query."\" -s 50000 2>/dev/null";
	$ph=popen($cmd,"r");
	$data="";
        while ($line = fgets($ph, 4096)) {
                $data.=$line;
        }
	pclose($ph);
        if (strlen($data)==1184) return '1';
        return '0';
}

/**
 * @brief Perform a full test (with query) on an ontologies SPARQL endpoint for a specified knowledge base.
 *
 * @param $kb
 * A string containing the ontologies knowledge base for which the SPARQL endpoint is to be tested.
 *
 * @return
 * A boolean. TRUE if the SPARQL endpoint for the specified ontologies knowledge base returns the expected SPARQL query result, FALSE otherwise.
 */
function ontologiesFullTestSPARQLQueryClient($kb){
        global $ontopath;
        $query="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select ?x where {?x rdfs:isDefinedBy <".$ontopath."snarm/>}";
        $cmd=getQueryPreamble().getPath()."4s-query $kb \"".$query."\" -s 50000 2>/dev/null";
        $ph=popen($cmd,"r");
        $data="";
        while ($line = fgets($ph, 4096)) {
                $data.=$line;
        }
        pclose($ph);
        if (strlen($data)==1184) return '1';
        return '0';
}

/**
 * @brief Retrieve and store locally an ontology at a remote URL.
 *
 * @param $name 
 * A string containing the human-readable name to give to the ontology locally.
 * 
 * @param $url
 * A string containing the URL where the remote ontology is located.
 * 
 * @param $id
 * A integer containing the local database ID for the ontology being retrieved.
 *
 * @param $log
 * a string containing the location of the the log file that should be written to for the script retrieving the ontology.
 */ 
function retrieveOntology($name,$url,$id,$log){
	$cmd=getScriptPath()."/retrieveRemoteOntology.sh '$url' $name $id > $log &";
        exec($cmd);
}

/**
 * @brief Generate and cache an HTML specification document for the ontology specified.
 *
 * @param $name 
 * A string containing the human-readable name to give to the ontology locally.
 * 
 * @param $url
 * A string containing the URL where the remote ontology is located.
 * 
 * @param $id
 * A integer containing the local database ID for the ontology having its HTML specification document cached.
 *
 * @param $log
 * a string containing the location of the the log file that should be written to for the script caching the ontology's HTML specification document.
 */
function cacheSpec($name,$url,$id,$log){
        $cmd=getScriptPath()."/cacheSpec.sh '$url' $name $id > $log &";
	exec($cmd);
}

/** 
 * @brief Determine the number of triples in a specified knowledge base.
 *
 * @param $kb
 * A string containing the knowledge base for which the number of triples is to be determined.
 *
 * @return
 * An integer representing the number of triples in a specified knowledge base.
 */
function getNoTriples($kb){
	global $lddir;
	$lines=@file($lddir."4store/log/".$kb."_triples.log");
	if (!empty($lines) && is_array($lines) && sizeof($lines)>0 && $lines[0]>0) return $lines[0];
	$cmd=getScriptPath()."/sqs.sh $kb count-triples";
	exec($cmd);
	$lines=@file($lddir."4store/log/".$kb."_triples.log");
        if (!empty($lines) && is_array($lines)) return str_replace("\n", "", $lines[0]);
}

/** 
 * @brief Get the last time a specified knowledge base was updated.
 *
 * @param $kb
 * A string containing the knowledge base for which the last updated time should be returned.
 *
 * @return
 * A string containing a timestamp of the last updated time of the knowledge base specified.  If this is unknown the string UNKNOWN is returned instead.
 */
function getLastUpdated($kb){
	global $lddir;
	$lines=@file($lddir."4store/log/".$kb."_update_time.log");
	if (!empty($lines[0])) {
	        return str_replace("\n", "", $lines[0]);
	}
	return "UNKNOWN";
}

/**
 * @brief Get the versions of 4Store, RAPTOR and RASQAL that are being used by knowledge bases / SPARQL endpoints.
 *
 * @return
 * A string containing the versions of 4Store, RAPTOR and RASQAL that are being used by knowledge bases / SPARQL endpoints.
 */
function get4StoreVersions(){
        global $lddir;
        $lines=@file($lddir."4store/log/4storeversions.log");
        return $lines[0];
}
?>
