<?php
/**
 * @file inc/partials/header.inc.php
 * @brief The header partial for web pages.
 * @version beta
 * @author David R Newman
 * @details The header partial for web pages.
 */
 
/** @brief A sanitized version of the page title. */
$sanitized_pagetitle = preg_replace("/<[^>]+>/","",$pagetitle);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 

<head>
  <title><?php if (empty($headername)) echo 'myExperiment'; else echo $headername ?> <?= $sanitized_pagetitle ?></title>
  <link rel="stylesheet" type="text/css" href="/css/<?php if (!empty($_GET['nobackground'])) echo "nb"; ?>style.css"/>
  <link rel="icon" href="/img/favicon.ico" type="image/x-icon" />
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
<?php
	if (isset($htmlheader) && is_array($htmlheader)){
		foreach ($htmlheader as $line) echo "  $line\n";
	}
?>
</head>
<body>

<div class="page">
  <div style="float: right;"><a href="http://www.myexperiment.org/feedback?subject=Linked%20Data">Submit Feedback/Bug Report</a></div> 
  <h1>
<?php
if (isset($headerimg)){
	if ($headerimg!="none") echo "    <img src=\"$headerimg\" alt=\"$headername Logo\"/>";
	echo $pagetitle;
}
else{
	echo "    <a href=\"/\"><img src=\"/img/logo.png\" alt=\"myExperiment Logo\"/></a>&nbsp;$pagetitle";
}

?>
  
  </h1>
