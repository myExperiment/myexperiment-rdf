#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/datadump.php
 * @brief Generates a data dump of all public myExperiment RDF.
 * @version beta
 * @author David R Newman
 * @details This script generates a data dump of all public myExperiment RDF data so that it can be wrapped up in a single file.
 */
  
/** @brief A string containing the domain for generating RDF data. (public: publicly available myExperiment RDF data; private: all myExperiment RDF data). */
$domain="public";
include('include.inc.php');
include('functions/rdf.inc.php');
	
/** @brief A string specifying where the RDF dump of myExperiment data should be stored locally. */
$rdffile="$datapath$myexp_kb/myexperiment.rdf";
/** @brief A string specifying where the gzipped RDF dump of myExperiment data should be stored locally. */
$rdfgzfile="$datapath$myexp_kb/myexperiment.rdf.gz";
				
echo "[".date("H:i:s")."] Creating temporary file $rdffile\n";
/** @brief A file handler for writing myExperiment RDF data out to a local file. */
$fh=fopen($rdffile,"w");
fwrite($fh,generateGenericRDFHeader());
foreach ($sql as $k => $v){
	$v=setUserAndGroups($v);
	echo "[".date("H:i:s")."] Adding $k\n";
	$res=mysqli_query($GLOBALS['con'], $v);
	if ($res!==false){
		$rows=mysqli_num_rows($res);
		$xml="";
		$ents=0;
		for ($i=0; $i<$rows; $i++){
			$row=mysqli_fetch_assoc($res);
			$id=$row['id'];
			if (!isset($row['user_id'])) $row['user_id']="AnonymousUser";
			$xml.=generateEntityRDF($row,$k);
			$ents++;
			if ($ents==1000){
	        	        fwrite($fh,$xml);
				$ents=0;
				$xml="";	
			}	
		}
	        fwrite($fh,$xml);
	}
	else{
		 echo "[".date("H:i:s")."] Invalid query <$v>\n";
	}
}
fclose($fh);
echo "[".date("H:i:s")."] Adding dataflows\n";
exec("for dffile in `du -b ".$datapath."dataflows/* | awk 'BEGIN{FS=\" \"}{ if ($1 != \"4\") print $2 }'`; do
        cat \$dffile >> $rdffile
done");
$fh=fopen($rdffile,"a");
fwrite($fh,generateRDFFooter());
fclose($fh);
echo "[".date("H:i:s")."] Calculating the number of triples and saving to ${lddir}4store/log/${myexp_kb}_datadump_triples.log.\n";
exec("/usr/local/bin/rapper -c $rdffile 2>&1 | tail -n 1 | awk 'BEGIN{FS=\" \"}{print $4}' > ${lddir}/4store/log/${myexp_kb}_datadump_triples.log");
echo "[".date("H:i:s")."] Gzipping to $rdfgzfile\n";
exec("/bin/gzip -c $rdffile > $rdfgzfile");	
echo "[".date("H:i:s")."] Data dump of $myexp_kb complete\n";
