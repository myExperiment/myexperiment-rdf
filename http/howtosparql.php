<?php
/**
 * @file http/howtosparql.php
 * @brief Page for navigating the interactive tutorial in writing SPARQL queries
 * @version beta
 * @author David R Newman
 * @details Page for navigating the interactive tutorial in writing SPARQL queries against myExperiment RDF data using the myExperiment SPARQL endpoint.
 */

include_once('include.inc.php');
/** @brief The page title to be displayed in an h1 tag and the title of the html header. */
$pagetitle="How To SPARQL";
/** @brief An array for adding additional lines the the head entity of the HTML page, (e.g. CSS and Javascript files). */
$htmlheader=array('<script src="/js/howtosparql.js" type="text/javascript"></script>');
include('partials/header.inc.php');
require_once('hts/nav.inc.php');
/** @brief An associative array mapping page numbers to title of each page in the How To SPARQL guide. */
$pages=array(1=>"Using the SPARQL Endpoint", 2=>"PREFIX", 3=>"SELECT", 4=>"WHERE", 5=>"FILTER", 6=> "GROUP BY", 7=>"ORDER BY", 8=>"LIMIT", 9=>"Troubleshooting");
if (!$_GET['page']){
	include('hts/intro.php');
}
else{
	$pageno=array_search($_GET['page'],$pages);
	echo "<div style=\"text-align: center;\">\n";
	printHowToSPARQLNavigationForm('top_nav',$pageno,$pages);
	echo "</div>\n";
	if ($pageno) include("hts/$pageno.php");	
	else echo "<h2>Page Not Found</h2>";
	echo "<div style=\"text-align: center; clear: both;\">\n";
	printHowToSPARQLNavigationForm('bottom_nav',$pageno,$pages);
	echo "</div>\n";
}	
?>
<br/>
<?php include('partials/footer.inc.php'); ?>
