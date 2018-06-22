<?php
/**
 * @file inc/connect/sparql.inc.php
 * @brief Set up a MySQL database connection to the sparql database.
 * @version beta
 * @author David R Newman
 * @details The script uses settings for inc/config/settings.inc.php to set up a MySQL database connection to the sparql database.
 */

if (!empty($sparql_db['password'])) {
	mysqli_connect($sparql_db['server'],$sparql_db['user'],$sparql_db['password']) or die("Could not connect to MySQL on {$sparql_db['server']} as user {$sparql_db['user']} WITH password.");
}
else {
	mysqli_connect($sparql_db['server'],$sparql_db['user']) or die("Could not connect to MySQL on {$sparql_db['server']} as user {$sparql_db['user']} WITHOUT password.");
}
mysqli_select_db($sparql_db['database']) or die("Could not select database {$sparql_db['database']}.");
mysqli_set_charset('utf8');
