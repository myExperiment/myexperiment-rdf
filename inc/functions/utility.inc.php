<?php
/**
 * @file inc/functions/utility.inc.php
 * @brief Generic utility functions for printing standard HTML output, array processing, text formatting, validation, etc.
 * @version beta
 * @author David R Newman
 * @details Generic utility functions for printing standard HTML output, array processing, text formatting, validation, etc.
 */

/**
 * @brief Generates HTML to display a formatted version of the message provided.
 *
 * @param $msg
 * A string containing the message to be formatted in HTML.
 *
 * @param $align
 * The text alignment for the HTML formatted message.
 *
 * @return
 * The message provided formatted in HTML markup.
 */
function formatMessage($msg,$align=""){
	if (!$align) $align="style=\"text-align: center;\"";
	return "<br/><div class=\"green\" $align><b>$msg</b></div>";
}

/**
 * @brief Generates HTML to display a formatted version of the error message provided.
 *
 * @param $err
 * A string containing the error message to be formatted in HTML.
 *
 * @param $align
 * The text alignment for the HTML formatted error message.
 *
 * @return
 * The error message provided formatted in HTML markup.
 */
function formatError($err,$align=""){
	if (!$align) $align="style=\"text-align: center;\"";
	return "<br/><div class=\"red\" $align><b>$err</b></div>";
}

/**
 * @brief Prints HTML to display a formatted version of the message provided.
 *
 * @param $msg
 * A string containing the message to be formatted in HTML.
 *
 * @param $align
 * The text alignment for the HTML formatted message.
 */
function printMessage($msg,$align=""){
	echo formatMessage($msg,$align);
}

/**
 * @brief Prints HTML to display a formatted version of the error message provided.
 *      
 * @param $err
 * A string containing the error message to be formatted in HTML.
 *      
 * @param $align
 * The text alignment for the HTML formatted error message.
 */
function printError($err,$align=""){
	echo formatError($err,$align);
}


/**
 * @brief Upper cases the first letter of each word in a string as long as it is not already fully upper case or only two characters long.  (Used for trying to match user's location/birthplace to entities in DBPedia).
 * 
 * @param $str
 * A string which should have its words' first letter upper-cased where appropriate.
 *
 * @return 
 * The string provided with the first letter of its words upper-cased where appropriate.
 */
function convertToDBPediaResidenceString($str){
	$strbits=explode(' ',$str);
	foreach ($strbits as $sbn => $sbit ){
		if (!(strlen($sbit)==2 && $sbit == strtoupper($sbit))){
			$strbits[$sbn]=ucfirst(strtolower($sbit));
		}
	}
	$str=implode(' ',$strbits);
	return $str;
}

/**
 * @brief Validate that a string provided is a valid email address.
 * 
 * @param $email
 * A string containing the email address to be validated.
 *
 * @return
 * A boolean.  TRUE if the email address provided is valid, FALSE otherwise.
 */
function validateEmail($email){
       if (!preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/", $email)) return TRUE;
       return FALSE;
}

/**
 * @brief Replaces namespace part of URIs with their prefixes (or nothing where appropriate) across a multi-dimensional array of URIs. 
 *
 * @param $array
 * The multi-dimensional array of URIs which should have their namespaces parts replaces with prefixes (or nothing where appropriate).
 *
 * @param $local_namespace
 * A string containing the local namespace for which instead of replacing with a prefix should just remove the namespace altogether.
 *
 * @return
 * A multi-dimensional array where namespace URIs have been replaced with prefixes (or with nothing where appropriate).
 */
function replaceNamespaces($array, $local_namespace=""){
        global $namespace_prefixes;
        foreach ($namespace_prefixes as $prefix => $namespace) {
                $namespace_prefixes_copy["$prefix:"] = $namespace;
        }
        $prefix=array_search($local_namespace, $namespace_prefixes_copy);
        if (!empty($prefix)){
                $namespace_prefixes_copy[$prefix] = "";
        }
        $namespaces = array_values($namespace_prefixes_copy);
        $prefixes = array_keys($namespace_prefixes_copy);
        if (empty($prefix)){
                $prefixes[] = "";
        }
        foreach ($array as $res => $vals){
                foreach ($vals as $k => $v){
                        $arr[$res][$k]=str_replace($namespaces, $prefixes, $v);
                }
        }
        return $arr;
}

/**
 * @brief Replaces namespace part of a URI with its prefix (or nothing where appropriate).
 *
 * @param $uri
 * A string containing the URI which should have its namespace part replaced with its prefix (or nothing where appropriate).
 *
 * @param $local_namespace
 * A string containing the local namespace for which instead of replacing with a prefix should just remove the namespace altogether.
 *
 * @return
 * A string where the namespace URI has been replaced with its prefix (or with nothing where appropriate).
 */
function replaceNamespace($uri, $local_namespace=""){
        global $namespace_prefixes;
        foreach ($namespace_prefixes as $prefix => $namespace) {
                $namespace_prefixes_copy["$prefix:"] = $namespace;
        }
        $prefix=array_search($local_namespace, $namespace_prefixes_copy);
        if (!empty($prefix)){
                $namespace_prefixes_copy[$prefix] = "";
        }
        $namespaces = array_values($namespace_prefixes_copy);
        $prefixes = array_keys($namespace_prefixes_copy);
        if (empty($prefix)){
                $prefixes[] = "";
        }
        return str_replace($namespaces, $prefixes, $uri);
}

/**
 * @brief Replaces a prefix with a namespace for an entity string provided (e.g mebase:User -&gt; http://rdf.myexperiment.org/ontologies/base/User).  Reverse of replaceNamespace function.
 *
 * @param $entity
 * The entity string for which the prefix should be replaced with the full namespace URI.
 *
 * @param $local_namespace
 * A string containing the local namespace to use if the entity has now prefix.
 *
 * @return
 * A string containing the full URI for the entity provided.
 */
function reinstateNamespace($entity, $local_namespace=""){
        global $namespace_prefixes;
        foreach ($namespace_prefixes as $prefix => $namespace) {
                $namespace_prefixes_copy["$prefix:"] = $namespace;
        }
        $namespaces = array_values($namespace_prefixes_copy);
        $prefixes = array_keys($namespace_prefixes_copy);
        $entity_bits=explode(":",$entity);
        if (sizeof($entity_bits)>1) return str_replace($prefixes, $namespaces, $entity);
        return $local_namespace.$entity;
}

/**
 * @brief Tests whether the entity provided is part of the local or one of the myExperiment namespaces.
 * 
 * @param $entity
 * A string containing the entity (prefix:entity_name) that is to be tested to see whether it is in the local of a myExperiment namespace.
 *
 * @return
 * A boolean.  TRUE if the entity is in the local or a myExperiment namespace, FALSE otherwise.
 */
function isMyexperimentNamespace($entity){
        global $namespace_prefixes;
        $myexp_prefixes = array("");
        $separated_namespaces = separateNamespaces($namespace_prefixes);
        $entity_bits = explode(":", $entity);
        return in_array($entity_bits[0], array_keys($separated_namespaces['myexperiment']));
}

/**
 * @brief Separates namespaces into myExperiment and other namespaces.
 *
 * @param $namespaces
 * An array of namespaces to be broken down into myExperiment and other namespaces.
 *
 * @return 
 * A multi-dimensional array containing sub-array listings of myExperiment and other namespaces.
 */
function separateNamespaces($namespaces) {
        global $ontopath, $datauri;
        $myexp_namespaces = array();
        $other_namespaces = array();
        foreach ($namespaces as $prefix => $namespace) {
                if (preg_match("!^$ontopath!", $namespace) || preg_match("!^$datauri!", $namespace)) {
                        $myexp_namespaces[$prefix] = $namespace;
                }
                else {
                        $other_namespaces[$prefix] = $namespace;
                }
        }
        return array("myexperiment" => $myexp_namespaces, "other" => $other_namespaces);
}

/**
 * @brief Retrieve a list of properties to XML datatypes mappings that have being generated and cached by querying the myExperiment ontology.
 *
 * @return
 * An associative array mapping property names (e.g. sioc:name) to an XML datatype properties (e.g. string).
 */
function getDatatypes(){
        global $lddir;
        $dtfile=file($lddir.'inc/config/datatypes.txt');
        foreach ($dtfile as $dt){
                $dtbits = explode(" ",$dt);
                $datatypes[trim($dtbits[0])]=trim($dtbits[1]);
        }
        return $datatypes;
}

/**
 * @brief Provides an array of useful prefix to namespace mappings.
 *
 * @param $domain
 * The domain for which useful prefixes are required.  (One of the myExperiment domains (public, private, protected) or the ontologies domain (knowledge base).
 *
 * @param $merge
 * A boolean specifying whether all the namespaces should be returned as simple list or broken up in myExperiment / non-myExperiment sub-groups.
 *
 * @return 
 * An associative array or multidimensional associative array, listing prefixes mapped to their namespace URIs.
 */
function getUsefulPrefixesArray($domain, $merge=false){
	global $namespace_prefixes;
	$domain = strtolower(trim($domain));
	if (in_array($domain, array("public", "private", "protected"))) {
		if ($merge) {
			return $namespace_prefixes;
		}
		return separateNamespaces($namespace_prefixes);
	}
	$separate_namespaces = separateNamespaces($namespace_prefixes);
	return array(array(), $separate_namespaces[1]);
}

/**
 * @brief Generates HTML table of useful prefixes and their namespaces.  For use a the top of a SPARQL endpoint web interface page. 
 *
 * @param $domain
 * The domain for which useful prefixes are required.  (One of the myExperiment domains (public, private, protected) or the ontologies domain (knowledge base).
 *
 * @return
 * An HTML table containing useful prefixes and their namespaces.
 */
function getUsefulPrefixes($domain){
	global $datauri;
	$namespaces = getUsefulPrefixesArray($domain);
		
	if (sizeof($namespaces['myexperiment'])>0 || sizeof($namespaces['other'])>0){
		$usepref='    <table class="borders" style="width: 100%;">
      <tr><th>myExperiment</th><th>Other</th></tr>
      <tr>
        <td style="text-align: left;"><ul class="nonesmall">
           <li class="prefix">BASE &lt;'.$datauri.'&gt;</li>
';
		foreach ($namespaces['myexperiment'] as $pref => $ns){
			$usepref.="          <li class=\"prefix\"><a onclick=\"addPrefixToQuery('$pref','$ns')\">PREFIX $pref: &lt;$ns&gt;</a></li>\n";
		}
		$usepref.='        </ul></td>
        <td style="text-align: justify;"><ul class="nonesmall">
';		
		foreach ($namespaces['other'] as $pref => $ns){
		  	$usepref.="          <li class=\"prefix\"><a onclick=\"addPrefixToQuery('$pref','$ns')\">PREFIX $pref: &lt;$ns&gt;</a></li>\n";
                }
                $usepref.="        </ul></td>\n      </tr>\n    </table>\n";
		return $usepref;
	}
	$usepref='    <ul class="none">
';
	foreach ($other as $pref => $ns){
                          $usepref.="      <li class=\"prefix\"><a onclick=\"addPrefixToQuery('$pref','$ns')\">PREFIX $pref: &lt;$ns&gt;</a></li>\n";
        }
        $usepref.="    </ul>\n";
        return $usepref;
}

/**
 * @brief Generates an array of acceptable MIME types ordered by their 'q' value based on the string provided.
 *
 * @param $accept_str
 * An accept string from which an ordered array of accept types is to be determined.
 *
 * @return
 * An array of acceptable MIME types for which to return data as.  Ordered by the MIME types with the highest 'q' value as specified in the accept string provided.
 */
function getAcceptTypes($accept_str){
        $accepts=array();
        $types=explode(",",$accept_str);
        foreach ($types as $type){
                $tbits=explode(";",$type);
                if (sizeof($tbits)>1){
                        $qvalbits=explode("=",$tbits[1]);
                        $accepts[$tbits[0]]=$qvalbits[1];
                }
                else $accepts[$tbits[0]]=1;
        }
        arsort($accepts,SORT_NUMERIC);
	return $accepts;
}

/**
 * @brief Determine what is the first choice MIME type from the accept string provided.
 *
 * @param $accept_str
 * An accept string from which the first choice MIME type is to be determined.
 *
 * @return
 * A string containing the first choice MIME type.
 */
function getFirstChoiceMIMEType($accept_str){
	$priority=array("text/html","application/xhtml+xml","application/xml");
	$accepts=getAcceptTypes($accept_str);
	$akeys=array_keys($accepts);
	foreach ($priority as $p){
		if ($accepts[$p]==1) return $p;
	}
	if ($accepts['*/*']==1) return "text/html";
	return $akeys[0];
}

/**
 * @brief Evaluates a multi-dimensional array to see whether any top-level entries have identical sub-arrays and removes these duplicates.  Uses json_encode to encode the sub-arrays so that can be easily compared.
 * 
 * @param $arr
 * A multi-dimensional array for which top-level entry sub-arrays are to be evaulated to remove duplicates.
 *
 * @return
 * A multi-dimensional array which no top-level entries that have identical sub-arrays.
 */
function multidimensionalArrayUnique($arr){
        $jarr=array();
        foreach ($arr as $k => $v){
                $jarr[$k]=json_encode($v);
        }
        $ujarr=array_unique($jarr);
        $uarr=array();
        foreach ($ujarr as $k => $v){
                $uarr[$k]=convertObjectToArray(json_decode($v));
        }
        return $uarr;
}

/**
 * @brief Converts an array that have been encoded and then decoded from JSON back from a PHP object into an array.
 *
 * @param $result
 * The JSON decoded, encoded PHP array that is now a PHP object.
 * 
 * @return
 * An array generated from a PHP object that was created from JSON encoding and then decoding a PHP array.
 */
function convertObjectToArray($result){
    $array = array();
    foreach ($result as $key=>$value){
        if (is_object($value)){
            $array[$key]=convertObjectToArray($value);
        }
        if (is_array($value)){
            $array[$key]=convertObjectToArray($value);
        }
        else{
            $array[$key]=$value;
        }
    }
    return $array;
}

/**
 * @brief Set of the key for each top-level entry in a multidimensional array to the value of one of the fields in the sub-array for that top-level entry.
 *
 * E.g. $arr = array(0 => array("name" => "", "size" => "small"), 1 => array("name" => "eagle", "size" => "large")); and $key = "name" would return:
 * array("robin" =&gt; array("name" =&gt; "robin", "size" =&gt; "large"), "eagle" =&gt; array("name" =&gt; "eagle", "size" =&gt; "large"));
 *
 * @param $arr
 * A multi-dimensional array for which the key to top-level entries is to be set.
 *
 * @param $key
 * The field in the top-level entry's sub-array thats value should be used as the key for top-level of the array.
 */
function setKey($arr,$key){
        $assocarr=array();
        foreach($arr as $rec => $data){
                $assocarr[$data[$key]]=$data;
        }
        return $assocarr;
}

?>
