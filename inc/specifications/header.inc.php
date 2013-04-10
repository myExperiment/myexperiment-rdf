<?php 
/**
 * @file inc/specifications/header.inc.php
 * @brief The header partial for ontology specification web pages.
 * @version beta
 * @author David R Newman
 * @details This script prints The header partial for ontology specification web pages.
 */

/** @brief A sanitized version of the page title. */
$sanitized_pagetitle = preg_replace("/<[^>]+>/","",$pagetitle); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title><?php if (!$headername) echo 'myExperiment'; else echo $headername ?> <?= $sanitized_pagetitle; ?></title>
  <link rel="stylesheet" type="text/css" href="/css/style.css"/>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
</head>
<body>

<div class="page">
 <h2><?php
	if ($headerimg && $headerimg!="none") echo "    <a href=\"$url\"><img src=\"$headerimg\" alt=\"$headername Logo\"/></a>";
	echo $pagetitle;
?></h2>
