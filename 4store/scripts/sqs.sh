#!/bin/bash
## @file 4store/scripts/sqs.sh
## @brief Script that automates the RDF generation and triplestore upload and control.
## @author David R Newman
## @version beta
## @details Init.d style script that supports myExperiment RDF generation; starting, stopping and restarting of 4Store knowledge base interfaces; the adding and removing of graphs from 4Store knowledge bases; generation of linkset and VoiD specifications.  And overall the whole process of updating the myExperiment 4Store knowledge base so the SPARQL endpoint can query the latest available version of the myExperiment database.  The following help instructions are provided:
##
## Usage: triplestore &lt;triplestore_name&gt; {start|stop|restart|status|update|import|generate-dataflows-rdf|add|remove|test|list-graphs|count-triples|generate-spec|graph-size|data-dump|generate-linksets|generate-voidspec|run-diagnostic|check-versions|help} [OPTIONS]
## 
## Examples
## 
## ./sqs.sh &lt;triplestore&gt; start
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; stop
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; restart
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; test
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; update [ no-db-update ]
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; import
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; generate-dataflows-rdf
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; add &lt;filename&gt; 
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
##   E.g. &lt;filename&gt; = /var/www/linkeddata_dev/data/myexp_public/workflows/12
## 
## ./sqs.sh &lt;triplestore&gt; add-list &lt;filename&gt;
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
##   E.g. &lt;filename&gt; = /tmp/graphs_to_add.txt
## 
## ./sqs.sh &lt;triplestore&gt; remove &lt;filename&gt; &lt;option&gt;
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
##   E.g. &lt;filename&gt; = /var/www/linkeddata_dev/data/myexp_public/workflows/12
##   &lt;option&gt; = [ delete, NULL ]
## 
## ./sqs.sh &lt;triplestore&gt; remove-list &lt;filename&gt; &lt;option&gt;
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
##   E.g. &lt;filename&gt; = /tmp/graphs_to_remove.txt
##   &lt;option&gt; = [ delete, NULL ]
## 
## ./sqs.sh &lt;triplestore&gt; list-graphs
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; count-triples
##   &lt;triplestore&gt; = [ myexp_public, ontologies ]
## 
## ./sqs.sh &lt;triplestore&gt; generate-spec
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; graph-size &lt;graphuri&gt;
##   &lt;triplestore&gt; = [ myexp_public ]
##   E.g. &lt;graphuri&gt; = file:///var/www/linkeddata_dev/data/myexp_public/workflows/12
## 
## ./sqs.sh &lt;triplestore&gt; data-dump
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; generate-data-and-ontologies-zip
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; generate-linksets
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; generate-voidspec
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; run-diagnostic
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; check-versions
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; check-entity-sizes
##   &lt;triplestore&gt; = [ myexp_public ]
## 
## ./sqs.sh &lt;triplestore&gt; help
##   &lt;triplestore&gt; = [ myexp_public ontologies ]
## 

d=`dirname $0`
basedir=`cd ${d}; pwd`
source "$basedir/settings.sh"

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
		for pid in `ps aux | grep $1 | grep 4s- | awk 'BEGIN{FS=" "}{print $2}'`; do
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
	`$PHPEXEC_PATH/php $STORE4_PATH/scripts/test4store.php $1` && { 
		echo "[`date +%T`] SPARQL Query Server $1 is functioning correctly" 
	} || { 
		echo "[`date +%T`] SPARQL Query Server $1 is not functioning correctly and will be restarted"; 
		stop $1; 
		sudo /etc/init.d/avahi-daemon restart; 
		start $1; 
	}
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
import(){
 	day=`date +%e`
        month=`date +%b`
        date +%s > $STORE4_PATH/log/$1_update_time.log
        ontologies=( myexp_snarm.owl myexp_base.owl myexp_annot.owl myexp_attrib_cred.owl myexp_view_down.owl myexp_packs.owl myexp_contrib.owl myexp_exp.owl myexp_components.owl myexp_specific.owl )
        for o in ${ontologies[@]}; do
                added=`add $1 $LD_PATH/http/ontologies/$o`
                if [ $added -gt 0 ]; then
                        echo "[`date +%T`] Added/Updated myExperiment Ontology module $LD_PATH/http/ontologies/$o to $1 Knowledge Base"
                else
                        echo "[`date +%T`] Could Not Add/Update myExperiment Ontology module $LD_PATH/http/ontologies/$o to $1 Knowledge Base"
                fi
        done
        added=`add $1 $DATA_PATH/$1/myexperiment.rdf`
        if [ $added -gt 0 ]; then
                echo "[`date +%T`] Added/Updated myExperiment Public Dataset ($DATA_PATH/$1/myexperiment.rdf) to $1 Knowledge Base"
        else
                echo "[`date +%T`] Could Not Add/Update myExperiment Public Dataset ($DATA_PATH/$1/myexperiment.rdf) to $1 Knowledge Base"
        fi
	count-triples $1
}
update(){
        check_triplestore $1
	if [ "$2" != "no-db-update" ]; then
		update-database
	fi
        check-versions $1
	generate-dataflows-rdf $1
	data-dump $1
	import $1
	check-entity-sizes
        generate-data-and-ontologies-zip $1
	generate-spec $1
	generate-linksets $1
	generate-voidspec $1
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
generate-dataflows-rdf(){
	echo "[`date +%T`] Generating Dataflow RDF"
	`which php` $STORE4_PATH/scripts/generateDataflowRDF.php
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
	for l in `cat $STORE4_PATH/config/linksets.txt`; do
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
	cat $STORE4_PATH/config/voidbase.rdf | sed "s/NO_OF_TRIPLES/$notriples/" > $outputfile
	for l in `cat $STORE4_PATH/config/linksets.txt`; do
        	linkset=`echo $l | awk 'BEGIN{FS="|"}{print $1}'`
	        linkseturi=`echo $l | awk 'BEGIN{FS="|"}{print $2}'`
        	objset=`echo $l | awk 'BEGIN{FS="|"}{print $3}'`
	        filename="myExperiment-$linkset"
        	nolinks=`cat $DATA_PATH/${1}/linksets/$filename.nt | wc -l`
	        echo "  <void:Linkset rdf:about=\"$HTTPRDF_PATH/linksets/$filename\">
    <void:subjectsTarget rdf:resource=\"$HTTPRDF_PATH/void.rdf#myexpDataset\"/>
    <void:objectsTarget rdf:resource=\"$objset\"/>
    <void:dataDump rdf:resource=\"$HTTPRDF_PATH/linksets/$filename.nt\"/>" >> $outputfile
        	cat $DATA_PATH/${1}/linksets/$filename.nt | awk 'BEGIN{FS=" "}{print $2}' | sed 's/[<>]//g' | sort -u | sed 's/^/    <void:linkPredicate rdf:resource=\"/g' | sed 's/$/\"\/>/g' >> $outputfile
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
run-diagnostic(){
 	check_triplestore $1
        if [[ "$2" == "graphs" ]]; then
                nographs=`$STORE4EXEC_PATH/4s-query --soft-limit=1000000 $1 "SELECT DISTINCT ?g WHERE { GRAPH ?g { ?s ?p ?o }}" | grep "<result>" | wc -l`
                echo "[`date +%T`] $1 has $nographs graphs"
                for e in ${ENTITIES[@]}; do
                   nofiles=`ls $DATA_PATH/$1/$e/ | wc -l`
                   nographs=`$STORE4EXEC_PATH/4s-query --soft-limit=1000000 $1 "SELECT DISTINCT ?g WHERE { GRAPH ?g { ?s ?p ?o } . FILTER (REGEX(STR(?g),'/$e/')) }" | grep "<result>" | wc -l`
                  echo "[`date +%T`] $e has $nographs graphs for $nofiles files"
                done
        elif [[ "$2" == "triples" ]]; then
                count-triples $1
                for e in ${ENTITIES[@]}; do
                   notriples=`$STORE4EXEC_PATH/4s-query --soft-limit=1000000 $1 "SELECT ?o WHERE { GRAPH ?g { ?s ?p ?o } . FILTER (REGEX(STR(?g),'/$e/')) }" | grep "<result>" | wc -l`
                   echo "[`date +%T`] $e has $notriples triples"
                done
        else
                $STORE4EXEC_PATH/4s-query --soft-limit=1000000 $1 "SELECT ?g WHERE { GRAPH ?g { ?s ?p ?o } . FILTER (REGEX(STR(?g),'/$2/')) }" | grep "<binding" | awk 'BEGIN{FS="<|>"}{print $5}' | awk 'BEGIN{FS="/"}{print $NF}' | sort | uniq -c
        fi
}
check-versions(){
#	store4version=`$STORE4EXEC_PATH/4s-info --version 2>&1 | head -n 1 | awk '{print $NF}'`
	store4version="4sr-2fc0eaf"
 	raptorversion=`pkg-config raptor2 --modversion`
 	rasqalversion=`$STORE4EXEC_PATH/rasqal-config --version`
 	echo "4store ($store4version), Raptor (v$raptorversion), Rasqal (v$rasqalversion)" > $STORE4_PATH/log/4storeversions.log
 	echo "[`date +%T`] Check 4Store, Raptor and Rasqal versions for $1 triplestore and written to $STORE4_PATH/log/4storeversions.log"
}

check-entity-sizes(){
	${PHPEXEC_PATH}/php ${STORE4_PATH}/scripts/testrdfgen.php 1>> ${STORE4_PATH}/log/entity_sizes.log
}
update-database(){
	cd $STORE4_PATH/scripts
	filename=`echo ${MYSQL_BACKUP_LOCATION} | awk 'BEGIN{FS="/"}{print $NF}'`
	scp ${BACKUP_USER}@${BACKUP_SERVER}:${MYSQL_BACKUP_LOCATION} /tmp/
	echo "[`date +%T`] Downloaded Latest myExperiment Database Snapshot"
	if [ ${#MYSQL_PASSWORD} -gt 0 ]; then
		mysqlparams="-p${MYSQL_PASSWORD}"
        fi
        if [ ${#MYSQL_HOST} -gt 0 ]; then
                mysqlparams="-h ${MYSQL_HOST} ${mysqlparams}"
        fi
	zcat /tmp/$filename | egrep -v '^INSERT INTO `(activity_limits|downloads|key_permissions|oauth_|picture|previews|topic|viewings|workflow_processors)' | mysql -u $MYSQL_USERNAME $mysqlparams $MYSQL_DATABASE
	echo "[`date +%T`] Uploaded SQL File ($filename) to MySQL"
	rm -f /tmp/$filename
}

generate-data-and-ontologies-zip(){
	myexp_dao_tempdir=`mktemp -d /tmp/myexp_dao_XXXXX`
	mkdir $myexp_dao_tempdir/myexp_data_and_ontologies
	cd $myexp_dao_tempdir 
	cp $DATA_PATH/$1/myexperiment.rdf myexp_data_and_ontologies/
        cp $LD_PATH/http/ontologies/myexp_* myexp_data_and_ontologies/
	zip -r myexp_data_and_ontologies myexp_data_and_ontologies/
	mv myexp_data_and_ontologies.zip $DATA_PATH/$1/
	rm -r $myexp_dao_tempdir
	echo "[`date +%T`] Created myexp_data_and_ontologies.zip containing RDF graphs for all myExperiment's public data and ontologies."
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
        if [ -n "$3" ]; then
          sleep $3
        fi 
	stop $1
	start $1
	;;
  test)
	echo "============== `date` =============="
	test $1
	;;
  update)
	echo "============== `date` =============="
	update $1 $3
	;;
  import)
	import $1
	;;
  generate-dataflows-rdf)
        generate-dataflows-rdf $1
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
  run-diagnostic)
	run-diagnostic $1 $3
	;;
  check-versions)
        check-versions $1
        ;; 
  check-entity-sizes)
	check-entity-sizes
	;;
  update-database)
	update-database
	;;
  generate-data-and-ontologies-zip)
  	generate-data-and-ontologies-zip $1
	;;
  *)
	$STORE4_PATH/scripts/sqs_help.sh
	exit 1
	;;
esac
