<?php
/**
 * @file inc/connect/myexperiment.inc.php
 * @brief Set up a MySQL database connection to the myExperiment database.
 * @version beta
 * @author David R Newman
 * @details The script uses settings for inc/config/settings.inc.php to set up a MySQL database connection to the myExperiment database.
 */

if (!empty($myexp_db['password'])) {
	$GLOBALS['con'] = mysqli_connect($myexp_db['server'],$myexp_db['user'],$myexp_db['password']) or die("Could not connect to MySQL on {$myexp_db['server']} as user {$myexp_db['user']} WITH password.");
}
else {
	$GLOBALS['con'] = mysqli_connect($myexp_db['server'],$myexp_db['user']) or die("Could not connect to MySQL on {$myexp_db['server']} as user {$myexp_db['user']} WITHOUT password.");
}

mysqli_select_db($GLOBALS['con'], $myexp_db['database']) or die("Could not select database {$myexp_db['database']}.");
mysqli_set_charset($GLOBALS['con'], 'utf8');
