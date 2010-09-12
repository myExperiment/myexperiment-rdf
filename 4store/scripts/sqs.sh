#!/bin/bash
source `dirname $BASH_SOURCE`/settings.sh
cd $STORE4_PATH/code

echo "============== `date` =============="
check(){
	ps aux | grep 4s- | grep $1 | grep -v grep | wc -l
}	
check_triplestore(){
	case $1 in
          $TRIPLESTORE)
                ;;
          ontologies)
                ;;
          *)
                echo "[`date +%T`] Unrecognized SPARQL Query Server: $1"
                exit
                ;;
        esac
}
	
stop(){
	check_triplestore $1
	running=$(check $1)
	if [ $running -eq  0 ]; then
                echo "[`date +%T`] SPARQL Query Server $1 is not running"
	else
		for pid in `ps aux | grep myexp_ld | grep 4s- | awk 'BEGIN{FS=" "}{print $2}'`; do
			kill -9 $pid
		done	
		echo "[`date +%T`] Stopped SPARQL Query Server $1"
	fi
}
start(){
	check_triplestore $1
	running=$(check $1)
        if [ $running -gt 0 ]; then
		echo "[`date +%T`] Cannot start SPARQL Query Server $1 as is already running"
		exit
    	fi
	$STORE4EXEC_PATH/4s-backend $1
	echo "[`date +%T`] Started SPARQL Query Server using $1"
}
status(){
	check_triplestore $1
	running=$(check $1)
	updatetime=`head -n 1 $STORE4_PATH/log/$1_updated.log`
	updatetimef=`$PHPEXEC_PATH/php $STORE4_PATH/scripts/dateFormatter.php "$updatetime"`
	if [ $running -gt 0 ]; then  
		echo "[`date +%T`] SPARQL Query Server $1 running"
	else
		echo "[`date +%T`] SPARQL Query Server $1 is not running"
	fi
	echo "[`date +%T`] SPARQL Query Server $1 was updated with database snapshot from $updatetimef ($updatetime)"
}
test(){
	working=`$PHPEXEC_PATH/php $STORE4_PATH/scripts/test4store.php $1`
	if [ "$working" ]; then
		if [ $working -eq 1 ]; then
			echo "[`date +%T`] SPARQL Query Server $1 is functioning correctly"
		else
			echo "[`date +%T`] SPARQL Query Server $1 is not functioning correctly and will be restarted"
	                stop $1
        	        start $1
		fi
	else
		echo "[`date +%T`] SPARQL Query Server $1 is not functioning correctly and will be restarted"
		stop $1
		start $1
	fi
}
reason-ontology(){
	check_triplestore $1
	myexp_ts=`expr match "$1" 'myexp_[a-zA-Z0-9_]*'`
	if [ $myexp_ts -gt 0 ]; then
		reasoned_filename="$DATA_PATH/$1/$1_reasoned.owl"
		java -cp $JAVA_CP RDFSReasonerOntologyMerger $STORE4_PATH/config/$1_ontologies.txt > $reasoned_filename 2> /dev/null
		chmod 777 $reasoned_filename
		echo "[`date +%T`] Ontologies from $1_ontologies.txt successfully reasoned and written to $reasoned_filename"
	else
		echo "[`date +%T`] reason command cannot be used with $1 triplestore"
	fi
}
reason-file(){
	check_triplestore $1
        java -cp $JAVA_CP RDFSReasonerSingleFile $STORE4_PATH/config/$1_ontologies.txt $2 $3
	echo "[`date +%T`] Reasoned file $2 using $1 ontologies and saved to $3"
}
add(){
	error=`$STORE4EXEC_PATH/4s-import $1 $2 2>&1`
	errcount=1
	while [[ -n "$error" && $errcount -lt 3 ]]; do
		sleep 1;
		error=`$STORE4EXEC_PATH/4s-import $1 $2 2>&1`
		errcount=$errcount+1
	done	
	if [ -n "$error" ]; then 
		echo 0
	else
		echo 1
	fi
}
remove(){
	if [ ${2:0:7} == "http://" ]; then
		$STORE4EXEC_PATH/4s-delete-model $1 $2
	else
		$STORE4EXEC_PATH/4s-delete-model $1 file://$2
		if [[ "$3" == "delete" ]]; then
        		if [ -f "$2" ]; then
	        		echo $2 | grep -v '*' | grep -v '^-r' | xargs rm
        			echo "[`date +%T`] Deleted $2"
			fi
		fi
        fi
}
add-list(){
	thelist=`cat $2 | tr '\n' ' '`
	$STORE4EXEC_PATH/4s-import $1 $thelist 2>&1
       	echo "[`date +%T`] Finished adding all graphs in $2 to $1 Knowledge Base"
}
remove-list(){
        for graph in `cat $2`; do
                remove $1 $graph $3
		echo "[`date +%T`] Removed graph $graph from $1 Knowledge Base"
        done
}

update-cached-files(){	
        echo "[`date +%T`] Updating Cached Files for $1"
	$PHPEXEC_PATH/php $STORE4_PATH/scripts/changeddata.php $1
	echo "[`date +%T`] Updated Cached Files for $1"
}
update(){
	myexp_ts=`expr match "$1" 'myexp_[a-zA-Z0-9_]*'`
        if [ $myexp_ts -gt 0 ]; then
		get-dataflows
                reason-dataflows $1
		if [ -n "$2" ]; then 
			if [ $2 == "no-cache" ]; then
				echo "[`date +%T`] Not Updating Cached Files"
			else
				update-cached-files $1
			fi
		else
			update-cached-files $1
		fi
		day=`date +%e`
                month=`date +%b`
		date +%s > $STORE4_PATH/log/$1_update_time.log
		stop $1
		start $1
		sleep 3
		for graph in `cat $DATA_PATH/tmp/$1/delete_files`; do
                        remove $1 $graph delete 
			echo "[`date +%T`] Removed $graph from $1"
		done
		echo "[`date +%T`] Removed all deleted entities from $1"
		if `ls -l $DATA_PATH/$1/$1_reasoned.owl 2>/dev/null | awk -v month="$month" -v day="$day" '{if ($6 == month && $7 == day) print $9}'`; then
			remove $1 $DATA_PATH/$1/$1_reasoned.owl
			added=`add $1 $DATA_PATH/$1/$1_reasoned.owl`
			if [ $added -gt 0 ]; then
				echo "[`date +%T`] Added/Updated $DATA_PATH/$1/$1_reasoned.owl to $1 Knowledge Base"
			else
				echo "[`date +%T`] Could Not Add/Update $DATA_PATH/$1/$1_reasoned.owl to $1 Knowledge Base"
			fi
		fi
		for e in ${ENTITIES[@]}; do
			filepath="$DATA_PATH/$1/$e/"
			thelist=`ls -l $filepath* 2>/dev/null | awk -v month="$month" -v day="$day" '{if ($6 == month && $7 == day) print $9}' | tr '\n' ' '`
			if [ `echo $thelist | wc -w` -gt 0 ]; then 
				$STORE4EXEC_PATH/4s-import $1 $thelist 2>&1
                       		echo "[`date +%T`] Finished adding/updating all graphs for $e to $1 Knowledge Base"
			else
				echo "[`date +%T`] No graphs to add/update for $e to $1 Knowledge Base"
			fi
                done
	fi
	count-triples $1
}	
list-graphs(){
	check_triplestore $1
	echo "[`date +%T`] Listing Graphs of $1 Triplestore:"
	$PHPEXEC_PATH/php $STORE4_PATH/scripts/listGraphs.php $1
}
count-triples(){
	check_triplestore $1
	notriples=`$STORE4EXEC_PATH/4s-size $1 | tail -3 | head -1 | awk 'BEGIN{FS=" "}{print $2}'`
	echo "[`date +%T`] $1 Triplestore has $notriples triples"
	echo $notriples > $STORE4_PATH/log/$1_triples.log
	echo "[`date +%T`] Printing number of triples to file $STORE4_PATH/log/$1_triples.log"
}
get-dataflows(){
	$PHPEXEC_PATH/php $STORE4_PATH/scripts/getNewWorkflowVersions.php | awk -v datapath="$DATA_PATH" -v httpwwwpath="$HTTPWWW_PATH" 'BEGIN{FS=","}{ print " -O " datapath "/dataflows/xml/" $1 " -q " httpwwwpath "/workflow.xml?id=" $2 "&version=" $3 "&elements=components" }' > /tmp/dataflow_wgets.txt
	exec</tmp/dataflow_wgets.txt
	while read line
	do
        	wget $line
	        echo "[`date +%T`] Executed wget $line"
#	        sleep 2
	done
	rm /tmp/dataflow_wgets.txt
}
reason-dataflows(){
	echo "[`date +%T`] Generating Dataflow RDF"
	$PHPEXEC_PATH/php $STORE4_PATH/scripts/generateDataflowRDF.php $1
	nographs=`cat /tmp/dataflows.txt | wc -l`
	if [ $nographs -gt 0 ]; then
		echo "[`date +%T`] Reasoning Dataflow RDF"
		java -cp $JAVA_CP RDFSReasonerMultiFile $STORE4_PATH/config/"$TRIPLESTORE"_ontologies.txt /tmp/dataflows.txt $DATA_PATH/dataflows/reasoned/
	else
		echo "[`date +%T`] No Workflow Dataflow RDF to reason"
	fi
	rm /tmp/dataflows.txt
}
reason-files(){
	check_triplestore $1
	java -cp $JAVA_CP RDFSReasonerMultiFile $STORE4_PATH/config/$1_ontologies.txt $2 $3
	echo "java RDFSReasonerMultiFile $STORE4_PATH/config/$1_ontologies.txt $2 $3"
	echo "[`date +%T`] Reasoned files in $2 using $1 ontologies and saved to $3"
}
generate-spec(){
	if [ $1 == $TRIPLESTORE ]; then
		echo "[`date +%T`] Retrieving specification document from $HTTPSPEC_PATH/current/spec"
		wget -O $DATA_PATH/$1/html/spec.html -q $HTTPSPEC_PATH/current/spec
		echo "[`date +%T`] Retrieved specification document for $1 and saved to $DATA_PATH/$1/html/spec.html"
		#echo "[`date +%T`] Setting group for permissions to $HTTPGROUP for $DATA_PATH/$1/html/spec.html"
		#sudo chgrp $HTTPGROUP $DATA_PATH/$1/html/spec.html
	else
		echo "[`date +%T`] Specification document can not be generated for $1"
	fi
}
graph-size(){
	check_triplestore $1
	notriples=`$STORE4EXEC_PATH/4s-query $1 "SELECT * WHERE { GRAPH <$2> { ?s ?p ?o }}" | grep "<result>" | wc -l`	
	echo "[`date +%T`] There are $notriples triples in $2"
}
data-dump(){
	check_triplestore $1
	$PHPEXEC_PATH/php $STORE4_PATH/scripts/datadump.php $1
}
generate-linksets(){
	check_triplestore $1
	for l in `cat ../config/linksets.txt`; do
	        linkset=`echo $l | awk 'BEGIN{FS="|"}{print $1}'`
        	linkseturi=`echo $l | awk 'BEGIN{FS="|"}{print $2}'`
	        $STORE4EXEC_PATH/4s-query -f text --soft-limit=1000000 $1 "select * where { ?s ?p ?o . FILTER(isURI(?o) && REGEX(STR(?o),'^$linkseturi+','i'))}" | grep "^<" | sed "s/$/ ./g" > $DATA_PATH/$1/linksets/myExperiment-$linkset.nt
        	echo "[`date +%T`] Created Linkset $DATA_PATH/$1/linksets/myExperiment-$linkset.nt"
	done
}
generate-voidspec(){
	check_triplestore $1
	notriples=`cat $STORE4_PATH/log/${1}_datadump_triples.log`
	outputfile="$DATA_PATH/${1}/void.rdf"
	cat ../config/voidbase.rdf | sed "s/NO_OF_TRIPLES/$notriples/" > $outputfile
	for l in `cat ../config/linksets.txt`; do
        	linkset=`echo $l | awk 'BEGIN{FS="|"}{print $1}'`
	        linkseturi=`echo $l | awk 'BEGIN{FS="|"}{print $2}'`
        	objset=`echo $l | awk 'BEGIN{FS="|"}{print $3}'`
	        filename="myExperiment-$linkset.nt"
        	nolinks=`cat $DATA_PATH/${1}/linksets/$filename | wc -l`
	        echo "  <void:Linkset rdf:about=\"$HTTPRDF_PATH/linksets/$filename\">
    <void:subjectsTarget rdf:resource=\"$HTTPRDF_PATH/myExperiment.rdf#myexpDataset\"/>
    <void:objectsTarget rdf:resource=\"$objset\"/>" >> $outputfile
        	cat $DATA_PATH/${1}/linksets/$filename | awk 'BEGIN{FS=" "}{print $2}' | sed 's/[<>]//g' | sort -u | sed 's/^/    <void:linkPredicate rdf:resource=\"/g' | sed 's/$/\"\/>/g' >> $outputfile
	        echo "    <void:statItem>
      <scovo:Item>
        <scovo:dimension rdf:resource=\"http://rdfs.org/ns/void#noOfTriples\"/>
        <rdf:value rdf:datatype=\"http://www.w3.org/2001/XMLSchema#nonNegativeInteger\">$nolinks</rdf:value>
      </scovo:Item>
    </void:statItem>
  </void:Linkset>
" >> $outputfile
	done
	echo "</rdf:RDF>" >> $outputfile
	echo "[`date +%T`] Created voID specification at $outputfile"
}
	
case "$2" in
  start)
	start $1
	;;
  stop)
	stop $1
	;;
  status)
        status $1
	;;
  restart)
	stop $1
	start $1
	;;
  test)
	test $1
	;;
  update)
	update $1 $3
	;;
  reason-ontology)
	reason-ontology $1
	;;
  reason-dataflows)
	reason-dataflows $1
	;;
  get-dataflows)
        get-dataflows $1
        ;;
  reason-file)
	reason-file $1 $3 $4
	;;
  reason-files)
        reason-files $1 $3 $4
	;;
  add)
	added=`add $1 $3`
        if [ $added -gt 0 ]; then
        	echo "[`date +%T`] Added $3 to $1 Knowledge Base"
        else
        	echo "[`date +%T`] Could Not Add $3 to $1 Knowledge Base"
        fi
	count-triples $1
  	;;
  add-list)
	add-list $1 $3
	count-triples $1 
	;;
  remove)
	remove $1 $3 $4
	echo "[`date +%T`] Removed graph $3 from $1 Knowledge Base"
	count-triples $1
	;;
  remove-list)
	remove-list $1 $3 $4
	count-triples $1
	;;
  reload)
	remove $1 $3 keep-file
	echo "[`date +%T`] Removed graph $3 from $1 Knowledge Base"
	added=`add $1 $3`
        if [ $added -gt 0 ]; then
                echo "[`date +%T`] Added $3 to $1 Knowledge Base"
        else
                echo "[`date +%T`] Could Not Add $3 to $1 Knowledge Base"
        fi

	count-triples $1
  	;;
  reload-list)
	remove-list $1 $3 keep-file
	add-list $1 $3
	count-triples $1
	;;
  list-graphs)
	list-graphs $1
	;;
  count-triples)
	count-triples $1
	;;
  generate-spec)
	generate-spec $1
        ;;
  graph-size)
	graph-size $1 $3
        ;;
  data-dump)
	data-dump $1
	;;
  generate-linksets)
	generate-linksets $1
	;;
  generate-voidspec)
        generate-voidspec $1
        ;;
  *)
	$STORE4_PATH/scripts/sqs_help.sh
	;;
esac
exit 1
