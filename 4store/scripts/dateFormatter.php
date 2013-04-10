#!/usr/bin/php
<?php
/**
 * @file 4store/scripts/dateFormatter.php
 * @brief Prints an current datetime to prefix log lines generated by PHP scripts
 * @version beta
 * @author David R Newman
 * @details This script prints the current datetime to prefix log lines generated by PHP scripts so they are of identical format to those in bash scripts an java applications.
 */

echo date('r',$argv[1]); 
