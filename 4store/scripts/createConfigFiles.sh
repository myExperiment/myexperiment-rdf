#!/bin/bash
## @file 4store/scripts/createConfigFiles.sh
## @brief Used by the installer (install.sh) to generate configuration files for managing reasoning over the myExperiment ontology.
## @author David R Newman
## @version beta
## @details This script generates a file listing of ontology modules that need to be reasoned concurrently to produced an reasoned version of the complete ontology.  It alos generates a configuration file specifying where the reasoned version of the complete ontology should be stored.
source `dirname $BASH_SOURCE`/settings.sh
if [ -e $STORE4_PATH/config/$STORE4_PATH/log ]; then
	"Config files have already been created!"
else
	cd $STORE4_PATH/config/
	echo "$HTTPRDF_PATH/ontologies/snarm/
$HTTPRDF_PATH/ontologies/base/
$HTTPRDF_PATH/ontologies/attrib_credit/
$HTTPRDF_PATH/ontologies/annotations/
$HTTPRDF_PATH/ontologies/packs/
$HTTPRDF_PATH/ontologies/experiments/
$HTTPRDF_PATH/ontologies/viewings_downloads/
$HTTPRDF_PATH/ontologies/contributions/
$HTTPRDF_PATH/ontologies/components/
$HTTPRDF_PATH/ontologies/specific/" > $TRIPLESTORE"_ontologies.txt"
	echo "$DATA_PATH/$TRIPLESTORE/"$TRIPLESTORE"_reasoned.owl" > $TRIPLESTORE"_files.txt"
fi
