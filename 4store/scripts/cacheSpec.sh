#!/bin/bash
## @file 4store/scripts/cacheSpec.sh
## @brief Caches an HTML specification document of a currently uploaded ontology/schema.
## @author David R Newman
## @version beta
## @details This script uses an OWL ontology or RDF schema that has already been loaded into the 4Store ontologies knowledge base to generate an HTML specification document of it.  It calls the http/generic_spec.php script with the uncached GET parameter to 1, ensuring the document is generated anew rather than from a copy already generated.  Appropriate logging messages are output dependent on the success of failure of this script.

source `dirname $BASH_SOURCE`/settings.sh
cd $STORE4_PATH/scripts
echo "============== `date` =============="
wget -O $DATA_PATH/ontologies/cachedspec/$3_$2_spec.html -q "$HTTPRDF_PATH/generic/spec?ontology=$3&uncached=1"
errors=`grep '<!-- Errors -->' $DATA_PATH/ontologies/cachedspec/$3_$2_spec.html`
errors2=`grep 'XML error: Empty document at line ' $DATA_PATH/ontologies/cachedspec/$3_$2_spec.html`
if [ ${#errors} -gt 0 ]; then
        echo "[`date +%T`] Cached spec of $1 at $DATA_PATH/ontologies/cachedspec/$3_$2_spec.html <b>with Query Failures</b>";
elif [ ${#errors2} -gt 0 ]; then
        echo "[`date +%T`] XML Error prevented $1 from being cached properly";
else
        echo "[`date +%T`] Cached spec of $1 at $DATA_PATH/ontologies/cachedspec/$3_$2_spec.html";
fi
echo "[`date +%T`] Finished";
