#!/bin/bash
## @file 4store/scripts/runquery.sh
## @brief Bash script to run SPARQL query against a specified 4Store knowledge base using 4s-query command line utility. (To be deprecated in favour of using 4Store HTTP interface). 
## @author David R Newman
## @version beta
## @details This script allows SPARQL queries to be made against a specified knowledge base using the 4s-query command line utility.  This will be deprecated a replaced with PHP functions that can query knowledge bases using the 4Store's HTTP interface.  The current usage of this script is as follows:
##
## ./runquery.sh <knowledge_base> <quoted_sparql_query> <soft_limit> <output_file> <quoted_reasoning_parameters> 

bashsource=`dirname $BASH_SOURCE`
if [ "${bashsource:0:1}" == "/" ]; then
  source "$bashsource/settings.sh"
else
  source "`pwd`/$bashsource/settings.sh"
fi
$STORE4EXEC_PATH/4s-query $5 $1 "$2" -s $3 > $4
echo "$STORE4EXEC_PATH/4s-query $5 $1 \"$2\" -s $3 > $4"
