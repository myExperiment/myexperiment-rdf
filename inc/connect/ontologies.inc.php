<?php
/**
 * @file inc/connect/ontologies.inc.php
 * @brief Set up a MySQL database connection to the ontologies database.
 * @version beta
 * @author David R Newman
 * @details The script uses settings for inc/config/settings.inc.php to set up a MySQL database connection to the ontologies database.
 */

if (!empty($onto_db['password'])) {
	mysql_connect($onto_db['server'],$onto_db['user'],$onto_db['password']) or die("Could not connect to MySQL on {$onto_db['server']} as user {$onto_db['user']} WITH password.");
}
else { 
	mysql_connect($onto_db['server'],$onto_db['user']) or die("Could not connect to MySQL on {$onto_db['server']} as user {$onto_db['user']} WITHOUT password.");
}
mysql_select_db($onto_db['database']) or die("Could not select database {$onto_db['database']}.");
mysql_set_charset('utf8');
