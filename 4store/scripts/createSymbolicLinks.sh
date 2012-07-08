#!/bin/bash
source `dirname $BASH_SOURCE`/settings.sh

if [ -e $LD_PATH/http/myexperiment.rdf.gz ]; then
	rm $LD_PATH/http/myexperiment.rdf.gz
fi
ln -s $DATA_PATH/$TRIPLESTORE/myexperiment.rdf.gz  $LD_PATH/http/myexperiment.rdf.gz
echo "Created Symbolic Link for myexperiment.rdf (gzipped for transfer-encoding)"

if [ -e $LD_PATH/http/myexp_data_and_ontologies.zip ]; then
        rm $LD_PATH/http/myexp_data_and_ontologies.zip
fi
ln -s $DATA_PATH/$TRIPLESTORE/myexp_data_and_ontologies.zip  $LD_PATH/http/myexp_data_and_ontologies.zip
echo "Created Symbolic Link for myexperiment_data_and_ontologies.zip"

if [ -e $LD_PATH/http/ontologies/spec.html ]; then
        rm $LD_PATH/http/ontologies/spec.html
fi
ln -s $DATA_PATH/$TRIPLESTORE/html/spec.html  $LD_PATH/http/ontologies/spec.html
echo "Created Symbolic Link for spec.html"

if [ -e $LD_PATH/http/linksets ]; then
        rm $LD_PATH/http/linksets
fi
ln -s $DATA_PATH/$TRIPLESTORE/linksets  $LD_PATH/http/linksets
echo "Created Symbolic Link for linksets folder"

if [ -e $LD_PATH/http/void.rdf ]; then
	rm $LD_PATH/http/void.rdf
fi
ln -s $DATA_PATH/$TRIPLESTORE/void.rdf  $LD_PATH/http/void.rdf
echo "Created Symbolic Link for void.rdf"


