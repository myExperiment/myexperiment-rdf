<?php
/**
 * @file inc/config/defaultsettings_installer.inc.php
 * @brief Default settings used by the installer of myExperiment Linked Data.
 * @version beta
 * @author David R Newman
 * @details Default settings used unchanged by the installer of myExperiment Linked Data, including RDF generation, SPARQL endpoint, ontology, HTML ontology specification generator, etc.
 */

	$lddir="THIS_DIR";
	ini_set('include_path',".:${lddir}inc/:");
	$datapath="${lddir}data/";
	$datauri="DATAURI";
	$ontopath="http://rdf.myexperiment.org/ontologies/";
        $myexppath="MYEXP_PATH";
	$myexp_db=array("user"=>"MYSQL_USERNAME","password"=>"MYSQL_PASSWORD","server"=>"localhost","database"=>"DATABASE");
	$use_rake=TRUE;
?>
