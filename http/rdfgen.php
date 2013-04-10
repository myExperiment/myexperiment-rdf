<?php
/**
 * @file http/rdfgen.php
 * @brief Web-based version of myExperiment RDF generator at rdfgen/rdfgencli.php.
 * @version beta
 * @author David R Newman
 * @details Web-based version of myExperiment RDF generator at rdfgen/rdfgencli.php.  Locked down by a password preotected .htaccess file.
 */

	$url=str_replace("http://www.myexperiment.org/","",$_GET['url']);
	if (strpos($url,".")>0){
		if (strpos($url,".rdf")>0){
			$url=str_replace(".rdf","",$url);
		}
 		else{
			header("HTTP/1.1 404 Not Found");
			exit();
		}
	}	
	$urlbits=explode("/",$url);
	$type=array_shift($urlbits);
	$id=array_shift($urlbits);
	$params=implode('/',$urlbits);
	$userid='';
	if ($type == "workflows") $userid='0';
	$data="";
	if ($type){
		$cmd="/usr/bin/php ../rdfgen/rdfgencli.php $type $id $params";
		$ph=popen($cmd,'r');
		if ($ph !== false) {
	      	  	while (!feof($ph)) {
         		       	$data.=fgets($ph, 512); 
  	      		}
			fclose($ph);
		}
	}
	if ($data){
		header('Content-Type: application/rdf+xml; charset=utf-8');
		echo $data;
	}
	else{
		 header("HTTP/1.1 404 Not Found");
	}
	
?>

