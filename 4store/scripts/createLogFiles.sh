#!/bin/bash
## @file 4store/scripts/createLogFiles.sh
## @brief Generates empty log files for managing 4Store and the graphs uploaded to the myExperiment 4Store knowledge base, aswell as the generator of myExperiment RDF.
## @author David R Newman
## @version beta
## @details This script generates empty log files to:
##  - 4storeversions.log: Store the version numbers of RAPTOR, RASQAL and 4Store for display on web interfaces to SPARQL endpoints.
##  - entity_sizes: Store the number of characters size of various myExperiment entities to test the RDF generator consistently produces the correct output.
##  - ${TRIPLESTORE}_4store.log: Logs any output generated whilst running either the update or test commands from the 4store/scripts/sqs.sh script.
##  - ${TRIPLESTORE}_triples.log: Stores a record of the number of triples in the myExperiment 4Store knowledge base for use by the SPARQL endpoint web interface.
##  - ${TRIPLESTORE}_update_time.log: Stores a record of the time snapshot of the database used to generate myExperiment RDF was taken for use by the SPARQL endpoint web interface.
##  - ${TRIPLESTORE}_datadump_triples.log: Stores the number of RDF triples in the data dump generated for all myExperiment's RDF data.  This is generated using the RAPTOR's rapper command line utility.

source `dirname $BASH_SOURCE`/settings.sh
if [ -e $STORE4_PATH/log/4storeversions.log ]; then
	echo "Log files have already been created!"
else
	cd $STORE4_PATH/log
        touch 4storeversions.log
	touch entity_sizes.log
	touch "${TRIPLESTORE}_4store.log"
	touch "${TRIPLESTORE}_triples.log"
	touch "${TRIPLESTORE}_update_time.log"
	touch "${TRIPLESTORE}_datadump_triples.log"
	touch "ontologies_triples.log"
        touch "ontologies_update_time.log"
	chmod -R ug+w *
	chown -R $USER:$HTTPGROUP *
fi
