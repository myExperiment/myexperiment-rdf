<?php
	//Symbolic links to dirs
	$lddir="/var/linkeddata/";
        $httpdir="/var/www/html/linkeddata/";
        $datadir="/var/data/";


        //Setup include_path
        ini_set('include_path',".:$ldpath/inc/:");

	//The URI for RDF data
	$datauri="http://www.myexperiment.org/";
	//The URI for RDF data in the guide"
        $guidedatauri="http://www.myexperiment.org/";
	//The URI path for the ontologies
	$ontouri="http://rdf.myexperiment.org/ontologies/";
	//The salt to encrypt hidden values
        $salt="changeme";

	//Database Settings
	$myexp_db=array("user"=>"username","password"=>"password","server"=>"servername","database"=>"dbname");
	$onto_db=array("user"=>"username","password"=>"password","server"=>"servername","database"=>"dbname");
	$sparql_db=array("user"=>"username","password"=>"password","server"=>"servername","database"=>"dbname");

?>
