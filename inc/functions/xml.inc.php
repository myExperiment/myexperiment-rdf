<?php
/**
 * @file inc/functions/xml.inc.php
 * @brief Functions for parsing, generating and examining XML.
 * @version beta
 * @author David R Newman
 * @details Functions for parsing, generating and examining XML. Including myExperiment RDF/XML and SPARQL results in application/sparql-results+xml format and XML representations of workflows including Taverna, Taverna 2, Galaxy and RapidMiner.
 */

/**
 * @brief Parses and XML string an returns an array-based representation of the XML. (Reverse of generateXML function).
 *
 * @param $xmldata
 * A string containing the XML to be parsed.
 *
 * @param $localns
 * A string containing the local namespace for the XML string provided.  Allowing xmlns and xml:base attributes to be set correctly.
 *
 * @return
 * An array representation of the XML string provided.
 */
function parseXML($xmldata, $localns=""){
	global $x2ans;
	if (!$xmldata) return array();
	$x2ans=array();
	$doc=new DOMDocument();
	$doc->substituteEntities = true;
        $doc->loadXML($xmldata);
	$xmldataex=$doc->saveXML();
	$reader = new XMLReader();
	$reader->XML($xmldataex);
	$array['version']=$doc->xmlVersion;
        $array['encoding']=$doc->xmlEncoding;
	while ($reader->next()){
		$domnode[0]=$reader->expand();
	}
	foreach($domnode as $dn) $array[]=convertXMLToArray($dn,true);
	if ($localns){
		if (!$array[0]['attrs']['xmlns']) $array[0]['attrs']['xmlns']=$localns;
		if (!$array[0]['attrs']['xml:base']) $array[0]['attrs']['xml:base']=$localns;
	}
	return $array;
}

/**
 * @brief Generate an XML string from the XML array representation provided. (Reverse of parseXML function).
 * 
 * @param $array
 * Array-based version of some XML.
 *
 * @return
 * A string containing the XML represented by the array provided.
 */
function generateXML($array){
	if (!$array['encoding']) $array['encoding']="UTF-8";
	if (!$array['version']) $array['version']="1.0";
	$xml="<?xml version=\"$array[version]\" encoding=\"$array[encoding]\" ?>\n\n";
	unset($array['encoding']);
	unset($array['version']);
	foreach($array as $arr)$xml.=convertArrayToXML($arr);
	return $xml;
}

/**
 * @brief Takes a DOM node created by an XMLReader's conversion of an XML string into a DOM document and populates a multidimensional array representation of the original XML string (recursively).
 * 
 * @param $domnode
 * The DOM node that is to be used to populate part multidimensional array representation of the original XML string.
 *
 * @param $topelement
 * Is the DOM node provided the top element in the DOM document.
 *
 * @return
 * An multidimensional array representation of the current DOM node and all of its descendants.
 */
function convertXMLToArray($domnode,$topelement=false){
	global $x2ans;
	$nodearray = array();
	if (!$topelement){
		$domnode = $domnode->firstChild;
	}
     	while(!is_null($domnode)){
		$currentnode = $domnode->nodeName;
		if($currentnode!="#comment"){
		$cnbits=explode(":",$currentnode);
		if (sizeof($cnbits)>1){
			if (!$x2ans["xmlns:$cnbits[0]"]){
                        	$x2ans["xmlns:$cnbits[0]"]=$domnode->lookupNamespaceURI($cnbits[0]);
                        }
		}
		$elementarray=array();
		switch ($domnode->nodeType){
         	  case XML_TEXT_NODE:
          		if(!(trim($domnode->nodeValue) == "")) $nodeind['tagData'] = $domnode->nodeValue;
        		break;
		  case XML_ELEMENT_NODE:
          		if($domnode->hasAttributes()){
            			$attributes = $domnode->attributes;
				foreach($attributes as $index => $domobj){
					if ($domobj->prefix){
						$prefix=$domobj->prefix;
						if (!$x2ans["xmlns:$prefix"]){
							$x2ans["xmlns:$prefix"]=$domnode->lookupNamespaceURI($prefix);
						}
						$pname="$prefix:".$domobj->name;
					}
					else{
						$pname=$domobj->name;
					}
              				$elementarray[$pname] = $domobj->value;
             			}
           		}
         		break;
		}
		if($domnode->hasChildNodes()){
			$nodeind['name']=$currentnode;
			if(isset($elementarray)){
	  			$nodeind['attrs']=$elementarray;
          		}
			$nodeind['name']=$currentnode;
			if (is_object($domnode->firstChild) && !is_object($domnode->firstChild->nextSibling) && $domnode->firstChild->nodeValue) $nodeind['tagData']=$domnode->firstChild->nodeValue;
			elseif ($domnode->firstChild->nodeValue == '0') $nodeind['tagData']=$domnode->firstChild->nodeValue;
			else $nodeind['children']=convertXMLToArray($domnode);

        	}
       		else{
         		if(isset($elementarray) && $domnode->nodeType != XML_TEXT_NODE){
				$nodeind['name']=$currentnode;
				$nodeind['attrs']=$elementarray;
        	  	}
        	}
		if (isset($nodeind)){
			$nodearray[]=$nodeind;
			$nodeind=null;
		}
		}
	     	$domnode = $domnode->nextSibling;
      	}
	if($topelement){
		if (!isset($x2ans['xmlns:rdfs'])) $x2ans['xmlns:rdfs']="http://www.w3.org/2000/01/rdf-schema#";
		
		$nodearray[0]['attrs']=$x2ans;
		return $nodearray[0];
	}
    	return $nodearray;
}

/**
 * @brief Converts a potentially multidimensional array representation of XML into a string (recursively).
 * 
 * @param $array
 * The (multidimensional) array representation of XML that is to be converted into a string.
 *
 * @return
 * An string of XML generated from the multidimensional array representation of some XML.
 */
function convertArrayToXML($array){
	if ($array['name']) $xml="<$array[name]";
	if (is_array($array['attrs'])){
		foreach ($array['attrs'] as $attr => $val){
			$xml.=" $attr=\"$val\"";
		}
	}
	if ($array['tagData']){
		$xml.=">".convertToXMLEntities($array['tagData'])."</$array[name]>";
	}
	elseif (sizeof($array['children'])>0){
		if ($array['name']) $xml.=">\n";
		foreach( $array['children'] as $k => $v){
			$subxml=explode("\n", convertArrayToXML($v));
			if (sizeof($subxml)==1 && !preg_match("/>$/",$subxml[0])){
				$xml=rtrim($xml);
				$xml.=$subxml[0];
			}
			else{
				foreach ($subxml as $line){
					$xml.="  ".$line."\n";
				}
			}
		}
		if ($array['name']) $xml.="</$array[name]>";
	}
	else $xml.="/>";
	return $xml;
}

/**
 * @brief Convert the array-based representation of the SPARQL results XML into a tabular form. So it is amongst other things, easy to render as an HTML table of results.
 *
 * @param $parsedxml
 * A array-based representation of some SPARQL results XML.
 *
 * @return
 * A tabular form of the array-based SPARQL results XML.
 */
function tabulateSPARQLResults($parsedxml){
	$table = array();
	if (isset($parsedxml[0]['children'][0]['children'])) {
	  	$vars=$parsedxml[0]['children'][0]['children'];
        	for ($v=0; $v<sizeof($vars); $v++){
                	$table['vars'][$v]=$vars[$v]['attrs']['name'];
        	}
	        $recs=$parsedxml[0]['children'][1]['children'];
        	for ($r=0; $r<sizeof($recs); $r++){
                	for ($v=0; $v<sizeof($vars); $v++){
                        	$varname=$recs[$r]['children'][$v]['attrs']['name'];
				if ($varname){
		                	$varnum=array_search($varname,$table['vars']);
                	        	$table[$r][$varnum]=$recs[$r]['children'][$v]['tagData'];
				}
                	}
        	}
	}
        return $table;
}

/**
 * @brief Convert a tabular representation of SPARQL results into a CSV representation.
 *
 * @param $table
 * Some SPARQL results represented in tabular form.
 * 
 * @return
 * A CSV representation of the SPARQL results provided in tabular form.
 */
function convertTableToCSV($table){
	foreach ($table as $rname => $row){	
		$csvline="";
		for ($i=0; $i<sizeof($row); $i++){
			$csvline.='"'.str_replace('"','"""',$row[$i]).'",';
		}
		$csv.=substr($csvline,0,-1)."\n";
	}
	return $csv;
}

/**
 * @brief Convert a tabular representation of SPARQL results into a two dimensional CSV matrix representation.  E.g. Convert a table of users and the groups that they belong to into a two dimenesional matrix (encoded in CSV) with the user on one axis and the group on the other.
 *
 * @param $table
 * A set of SPARQL results in tabular form.
 * 
 * @return
 * A string representing a CSV-encoded two dimensionsal matrix of results where the first column of results provides one axis of the matrix and the second column provides the other axis.
 */
function convertTableToCSVMatrix($table) {
	if (sizeof($table['vars'])!=2) return "ERROR: Query must select exactly two variables to draw a CSV matrix";
	$tabnn=array_slice($table,1);
	$uniqx=array();
	$uniqy=array();
	foreach ($tabnn as $row){
		if (!in_array($row[0],$uniqx))$uniqx[]=$row[0];
		if (!in_array($row[1],$uniqy))$uniqy[]=$row[1];
	}
	sort($uniqx);
	sort($uniqy);
	foreach ($tabnn as $row){
		$matrix[$row[0]][$row[1]]++;
	}
	$csvmatrix=" ,";
	for ($y=0; $y<sizeof($uniqy); $y++){
		$csvmatrix.='"'.str_replace('"','"""',$uniqy[$y]).'"';
		if ($y<sizeof($uniqy)-1) $csvmatrix.=',';
	}
	$csvmatrix.="\n";
	for ($x=0; $x<sizeof($uniqx); $x++){
		$csvmatrix.='"'.str_replace('"','"""',$uniqx[$x]).'",';
		for ($y=0; $y<sizeof($uniqy); $y++){
			if ($matrix[$uniqx[$x]][$uniqy[$y]]) $csvmatrix.=$matrix[$uniqx[$x]][$uniqy[$y]];
			else $csvmatrix.=' ';
			if ($y<sizeof($uniqy)-1) $csvmatrix.=',';
		}
		$csvmatrix.="\n";
	}
	return $csvmatrix;
}

/**
 * @brief Generates an HTML table, allowing SPARQL results in tabular form to be displayed on a web page.
 *
 * @param $table
 * A set of SPARQL results in tabular form.
 *
 * @return
 * An HTML table representation of a set of SPARQL results.
 */
function drawSPARQLResultsTable($table){
	$tablehtml = "";
	if (!empty($table['vars'])) {
		$tablehtml.="<table class=\"listing\">\n  <tr>";
		for ($v=0; $v<sizeof($table['vars']); $v++){
			$tablehtml.="<th>".$table['vars'][$v]."</th>";
		}
		$shade=" class=\"shade\"";
		$tablehtml.="</tr>\n";
		for ($r=0; $r<sizeof($table)-1; $r++){
			$tablehtml.="  <tr>";
			for($v=0; $v<sizeof($table['vars']); $v++){
				$tablehtml.="<td$shade>".$table[$r][$v]."</td>";
			}
			$tablehtml.="</tr>\n";
			if (!$shade) $shade=" class=\"shade\"";
			else $shade="";
		}
		$tablehtml.="</table>\n";
	}
	return $tablehtml;
}

/**
 * @brief Sanitizes and string so that it render as a value within some XML markup.
 *
 * @param $data
 * A string which needs to be sanitized so it can be renders as a value within some XML markup.
 * 
 * @return
 * The sanitized version of the string provided that can now be rendered as a value in some XML markup.
 */
function convertToXMLEntities($data){
	$find=array("&#xD;","&","<",">","'",'"',"\x0b","\x0c","\x09","\x0a","\x0d","\x1d","\x1e","\xa0");
	$replace=array(" ","&amp;","&lt;","&gt;","&apos;","&quot;"," ","","\t","\n","\t","-","-","&#160;");
	$replaced=str_replace($find, $replace, $data);
	for( $c=0; $c<strlen($replaced); $c++){
		$char=substr($replaced,$c,1);
		if ((ord($char)<32 || ord($char)>126) && (!(ord($char)>8 && ord($char)<14))) $replaced=substr_replace($replaced,'?',$c,1);
	}
	return $replaced;
}

/**
 * @brief Convert the array-based representation of the SPARQL results XML into a tabular form.  Like tabulateSPARQLResults but using the field names as the key for it column in the tabular array rather than as the first entry of each column sub-array.
 *
 * @param $parsedxml
 * A array-based representation of some SPARQL results XML.
 *
 * @return
 * A tabular form of the array-based SPARQL results XML. Using the field names of the results as the keys of the sub-arrays.
 */

function tabulateSPARQLResultsAssoc($parsedxml){
	$vararray=$parsedxml[0]['children'][0]['children'];
        for ($v=0; $v<sizeof($vararray); $v++){
                $vars[$v]=$vararray[$v]['attrs']['name'];
        }
	$table=array();
        if (isset($parsedxml[0]['children'][1]['children'])) $recs=$parsedxml[0]['children'][1]['children'];
        else $recs=array();
        for ($r=0; $r<sizeof($recs); $r++){
                for ($v=0; $v<sizeof($vars); $v++){
			$bname=$recs[$r]['children'][$v]['attrs']['name'];
                        $table[$r][$bname]=$recs[$r]['children'][$v]['tagData'];
                }
        }
        return $table;
}

/**
 * @brief Generates a table of Dataflow components extracted from the following MIME type of Workflows application/vnd.taverna.scufl+xml, application/vnd.taverna.t2flow+xml, application/vnd.galaxy.workflow+xml, application/vnd.galaxy.workflow+json, application/vnd.rapidminer.rmp+zip.
 * 
 * @param $allcomponents
 * The XML for the Dataflow transformed into an array-based representation.
 * 
 * @param $ent_uri
 * A string containing the entity URI of the Dataflow for which components are to be extracted.
 *
 * @param $mime_type
 * A string containing the MIME type of the Dataflow of which components are to be extracted.
 * 
 * @param $nested
 * A boolean specifying whether the Dataflow for which components are to be extracted is a part of another Dataflow or the top-level Dataflow of a Workflow.
 *
 * @return
 * A table of Dataflow components that have be extracted from Dataflow specified.
 */
function tabulateDataflowComponents($allcomponents, $ent_uri, $mime_type, $nested=0){
	global $dfs;
	$d=2;
	if (!$nested) $dfs=array();
	switch($mime_type){
		case 'application/vnd.taverna.scufl+xml':
			if (strpos($ent_uri,'dataflow') > 0) $dfs[$ent_uri."/dataflow"]=processTavernaComponents($allcomponents,$ent_uri."/dataflow/",$mime,$nested);
	                else $dfs[$ent_uri."#dataflow"]=processTavernaComponents($allcomponents,$ent_uri."#dataflow/",$mime_type,$nested);
			break;
		case 'application/vnd.taverna.t2flow+xml':
			foreach ($allcomponents[0]['children'] as $dataflow){
	                        if (is_array($dataflow)){
        	                        if ($dataflow['attrs']['role']=="top") $id=1;
                	                else{
                        	                $id=$d;
                                	        $d++;
	                                }
        	                        $dfs[$ent_uri."#dataflows/$id"]=processTavernaComponents($dataflow['children'],$ent_uri."#dataflows/$d/",$mime_type,$nested);
                	                $dfs[$ent_uri."#dataflows/$id"]['id']=$dataflow['attrs']['id'];
	                        }
        	        }
			break;
		case 'application/vnd.galaxy.workflow+xml':
		case 'application/vnd.galaxy.workflow+json':
			$dfs[$ent_uri."/dataflow"]=processGalaxyComponents($allcomponents,$ent_uri."/dataflow/",$nested); 
			break;
		case 'application/vnd.rapidminer.rmp+zip':
			if (strpos($ent_uri,'dataflow') > 0) $dfs[$ent_uri."/dataflow"]=processRapidMinerComponents($allcomponents,$ent_uri."/dataflow/",$mime_type,$nested);
                        else $dfs[$ent_uri."#dataflow"]=processRapidMinerComponents($allcomponents,$ent_uri."#dataflow/",$mime_type,$nested);
                        break;
	}
	if (!$nested){
		if ($allcomponents[0]['name']!="dataflows") $dfs=array_reverse($dfs);
		return $dfs;
	}
}

/**
 * @brief Extracts Dataflow components from a Galaxy workflow.
 * 
 * @param $allcomponents
 * The XML for the Galaxy Dataflow transformed into an array-based representation.
 *
 * @param $ent_uri
 * A string containing the entity URI of the Galaxy Dataflow for which components are to be extracted.
 *
 * @return
 * A table of Dataflow components that have be extracted from Galaxy Dataflow specified.
 */
function processGalaxyComponents($allcomponents,$ent_uri){
	$comps=array();
	$components=array();
	$hassource=array();
	foreach ($allcomponents as $typedcomponents){
		if (!isset($typedcomponents['children']) || !is_array($typedcomponents['children'])) $typedcomponents['children']=array();
		$cc=0;	
                foreach ($typedcomponents['children'] as $comp){
			$cc++;
			$props=array();
			$cid=null;
                	foreach ($comp['children'] as $property){
				if (isset($property['tagData'])) $props[$property['name']]=$property['tagData'];
				if ($property['name']=="id" || $property['name']=="step-id") $cid=$property['tagData'];
				elseif ($property['name']=="source-id") $hassource[$property['tagData']]=true;
			}
			if (isset($cid)) $comps[$comp['name']][$cid]=$props;
			else $comps[$comp['name']]["cid$cc"]=$props;
		}
	}
	$c=1;
	foreach ($comps['input'] as $input){
                $components[$c]['type']="Source";
		if (!isset($input['description'])) $input['description']='';
		$components[$c++]['props']=array(array('type'=>'dcterms:title','value'=>$input['name']),array('type'=>'dcterms:description','value'=>$input['description']));
        }
	foreach ($comps['output'] as $o => $output){
                if (!isset($hassource[$o])){
                        $components[$c]['type']="Sink";
			$components[$c++]['props'][]=array('type'=>'dcterms:title','value'=>$output['name']);
                }
	}
	foreach ($comps['step'] as $s => $step){
		if (!isset($comps['input'][$s])){
			$components[$c]['type']="Processor";
			$components[$c]['props'][]=array('type'=>'dcterms:title','value'=>$step['name']);
			if (isset($step['description'])) $components[$c]['props'][]=array('type'=>'dcterms:description','value'=>$step['description']);
			$components[$c]['props'][]=array('type'=>'mecomp:service-name','value'=>$step['tool']);
			$components[$c]['props'][]=array('type'=>'mecomp:waits-on', 'value'=>$c-1);	
			$c++;
		}
	}
	foreach ($comps['connection'] as $conn){
		$components[$c]['type']="Input";
                $components[$c++]['props']=array(array('type'=>'dcterms:title', 'value'=>$conn['sink-input']),array('type'=>'mecomp:for-component','value'=>$conn['sink-id']));
		$components[$c]['type']="Output";
		$components[$c++]['props']=array(array('type'=>'dcterms:title', 'value'=>$conn['source-output']),array('type'=>'mecomp:for-component','value'=>$conn['source-id']));
		$components[$c]['type']="Link";
		$components[$c]['props'][]=array('type'=>'mecomp:to-input', 'value'=>$c-2);
		$components[$c]['props'][]=array('type'=>'mecomp:from-output', 'value'=>$c-1);
		if (isset($comps['output'][$conn['source-id']])) $components[$c]['props'][]=array('type'=>'mecomp:link-datatype', 'value'=>$comps['output'][$conn['source-id']]['type']);
		$c++;
	}
	return $components;
		
}

/**
 * @brief Extracts Dataflow components from a RapidMiner workflow.
 * 
 * @param $allcomponents
 * The XML for the RapidMiner Dataflow transformed into an array-based representation.
 *
 * @param $ent_uri
 * A string containing the entity URI of the RapidMiner Dataflow for which components are to be extracted.
 * 
 * @param $mime_type
 * A string containing the MIME type of the RapidMiner Dataflow of which components are to be extracted.
 * 
 * @param $nested
 * A boolean specifying whether the RapidMiner Dataflow for which components are to be extracted is a part of another Dataflow or the top-level Dataflow of a Workflow.
 * 
 * @return
 * A table of Dataflow components that have be extracted from RapidMiner Dataflow specified.
 */
function processRapidMinerComponents($allcomponents,$ent_uri,$mime_type,$nested=0){
	$components=array();
	if ($nested==0) $mainprocesscomps=$allcomponents[0]['children'][0]['children'][0]['children'];
	elseif (isset($allcomponents[0]['children'])) $mainprocesscomps=$allcomponents[0]['children'];
	else $mainprocesscomps=array();
	$c=1;
	foreach ($mainprocesscomps as $mpcomp){
		$props=array();
		$props[]=array("type"=>"dcterms:title","value"=>$mpcomp['attrs']['name']);
		if (isset($mpcomp['children']) && sizeof($mpcomp['children'])>0){
			$classtype="DataflowProcessor";
			tabulateDataflowComponents($mpcomp['children'],$ent_uri."components/$c",$mime_type,$nested+1);
                        $props[]=array('type'=>'mecomp:executes-dataflow','value'=>$ent_uri."components/$c/dataflow");
		}
		else{
			$classtype="Processor";
		}
		$components[$c]=array('type'=>$classtype,'props'=>$props);
		$c++;
	}
	return $components;
}

/**
 * @brief Extracts Dataflow components from a Taverna (version 1 or 2) Workflow.
 * 
 * @param $allcomponents
 * The XML for the Taverna Dataflow transformed into an array-based representation.
 *
 * @param $ent_uri
 * A string containing the entity URI of the Taverna Dataflow for which components are to be extracted.
 * 
 * @param $mime_type
 * A string containing the MIME type of the Taverna Dataflow of which components are to be extracted.
 * 
 * @param $nested
 * A boolean specifying whether the Taverna Dataflow for which components are to be extracted is a part of another Dataflow or the top-level Dataflow of a Workflow.
 * 
 * @return
 * A table of Dataflow components that have be extracted from Taverna Dataflow specified.
 */
function processTavernaComponents($allcomponents,$ent_uri,$mime_type,$nested=0){
	$components=array();
        $ptmappings=array("wsdl"=>"WSDLProcessor","arbitrarywsdl"=>"WSDLProcessor","soaplabwsdl"=>"WSDLProcessor","biomobywsdl"=>"WSDLProcessor","beanshell"=>"BeanshellProcessor","workflow"=>"DataflowProcessor");
        $c=1;
	foreach ($allcomponents as $typedcomponents){
		if (!isset($typedcomponents['children']) || !is_array($typedcomponents)) $typedcomponents=array('children'=>array());
		foreach ($typedcomponents['children'] as $comp){
			$props=array();
                       	$ctype=ucfirst(strtolower($comp['name']));
			if ($ctype=="Datalink") $ctype="Link";
			$classtype=$ctype;
			if ($ctype=="Source"||$ctype=="Sink"||$ctype=="Processor"){
				if ($ctype=="Processor") $classtype="OtherProcessor";
				foreach ($comp['children'] as $property){
					switch ($property['name']){
					  case 'name':
						$props[]=array('type'=>'dcterms:title','value'=>$property['tagData']);
						break;
					  case 'description':
						if (isset($property['tagData'])) $props[]=array('type'=>'dcterms:description','value'=>$property['tagData']);
						break;
					  case 'type':
						if (isset($ptmappings[$property['tagData']])) $classtype=$ptmappings[$property['tagData']];
						else $classtype="OtherProcessor";
						$props[]=array('type'=>'mecomp:processor-type','value'=>$property['tagData']);
						break;
					  case 'examples':
						if (isset($property['children']) && is_array($property['children'])){
							foreach ($property['children'] as $example){
								if (isset($example['tagData'])) $props[]=array('type'=>'mecomp:example-value','value'=>$example['tagData']);
							}
						}
						break;
					  case 'script':
						if (isset($property['tagData'])) $props[]=array('type'=>'mecomp:processor-script','value'=>$property['tagData']);
						else $props[]=array('type'=>'mecomp:processor-script');
						break;
					  case 'model':
						tabulateDataflowComponents($property['children'],$ent_uri."components/$c",$mime_type,$nested+1);
						$props[]=array('type'=>'mecomp:executes-dataflow','value'=>$ent_uri."components/$c/dataflow");
                                                break;
					  case 'dataflow-id':
						$props[]=array('type'=>'mecomp:executes-dataflow','value'=>$property['tagData']);
						break;
					  case 'endpoint':
					  case 'wsdl':
						$props[]=array('type'=>'mecomp:processor-uri','value'=>$property['tagData']);
                                                break;
					  case 'service-name':
					  case 'wsdl-operation':
                                               $props[]=array('type'=>'mecomp:service-name','value'=>$property['tagData']);
                                                break;
					  case 'biomoby-authority-name':
						if (isset($property['tagData'])) $props[]=array('type'=>'mecomp:authority-name','value'=>$property['tagData']);
                                                break;
					  case 'biomoby-service-category':
					}	
				}
				$complist[$ctype][$c]=$comp['children'][0]['tagData'];
			}
			elseif ($ctype=="Link"){
				//Input
				$inputprops=array();	
				if (sizeof($comp['children'][0]['children'])==2){
 					$inputprops[]=array('type'=>'dcterms:title','value'=>$comp['children'][0]['children'][1]['tagData']);
					$cval=array_search($comp['children'][0]['children'][0]['tagData'],$complist['Processor']);
				}
				else if (isset($complist['Sink'])) $cval=array_search($comp['children'][0]['children'][0]['tagData'],$complist['Sink']);
				else $cval=array_search($comp['children'][0]['children'][0]['tagData'],$complist['Processor']);
				$inputprops[]=array('type'=>'mecomp:for-component','value'=>$cval);
				$components[$c++]=array('type'=>'Input','props'=>$inputprops);
				//Output
				$outputprops=array();
				if (sizeof($comp['children'][1]['children'])==2){
					$outputprops[]=array('type'=>'dcterms:title','value'=>$comp['children'][1]['children'][1]['tagData']);
					$cval=array_search($comp['children'][1]['children'][0]['tagData'],$complist['Processor']);
				}
				else $cval=array_search($comp['children'][1]['children'][0]['tagData'],$complist['Source']);
				$outputprops[]=array('type'=>'mecomp:for-component','value'=>$cval);
				$components[$c++]=array('type'=>'Output','props'=>$outputprops);
				$ino=$c-2;
				$ono=$c-1;
				$props[]=array('type'=>'mecomp:to-input','value'=>$ino);
				$props[]=array('type'=>'mecomp:from-output','value'=>$ono);
			}
			if ($ctype=="Coordination"){
				foreach ($comp['children'] as $property){
					if ($property['name']=="controller") $controller=array_search($property['tagData'],$complist['Processor']);
					elseif ($property['name']=="target") $target=array_search($property['tagData'],$complist['Processor']);
				}
				$components[$target]['props'][]=array('type'=>'mecomp:waits-on','value'=>$controller);
			}
			else{
				$components[$c]=array('type'=>$classtype,'props'=>$props);
				$c++;
			}
		}
	}
	return $components;		
}

/**
 * @brief Extracts information about an OWL ontology / RDF Schema from an rdf:Description entity.
 *
 * @param $filename
 * The filename which should be parsed to extract information about the ontology / schema from an rdf:Description entity.
 *
 * @param $ontnames
 * An array containing a set of URI strings for ontologies / schemas which property-value pairs need to be found for.
 * 
 * @return
 * An associative array containing key values pairs of the information about the ontology / schema extracted for the rdf:Description entity.
 */
function processRDFDescription($filename, $ontnames){
	$props=array();
	if (file_exists($filename)){
		$fh=fopen($filename,'r');
		$xmlstr="";
		while (!feof($fh)){
			$xmlstr.=fgets($fh,8192);
		}
		fclose($fh);
		$xml=parseXML($xmlstr);
		$props=getOntologyProperties($xml[0]['children'][0],$ontnames);
	}
	else echo "$filename does not exists";
	return $props;
}

/**
 * @brief Extracts information about an OWL ontology from its owl:Ontology entity.
 *
 * @param $filename
 * The filename which should be parsed to extract information about the ontology form its owl:Ontology entity.
 *
 * @param $ontnames
 * An array containing a set of URI strings for ontologies / schemas which property-value pairs need to be found for.
 * 
 * @return
 * An associative array containing key values pairs of the information about the ontology from its owl:Ontology entity.
 */
function processOWLInfo($filename,$ontnames){
	 $props=array();
        if (file_exists($filename)){
                $fh=fopen($filename,'r');
                $xmlstr="";
                while (!feof($fh)){
                        $xmlstr.=fgets($fh,8192);
                }
                fclose($fh);
                $xml=parseXML($xmlstr);
		if (is_array($xml[0]['children'])){
			foreach($xml[0]['children'] as $entno => $ent){
				if ($ent['name']=="owl:Ontology"){
					$props=getOntologyProperties($ent,$ontnames);
					break;
				}
			}
		}
        }
        else echo "$filename does not exists";
        return $props;	
}

/**
 * @brief Extracts property-value pairs from an entity used to describe an OWL ontology or RDF schema.
 *
 * @param $ontent
 * An array-based representation of the XML for the entity that describes the ontology / schema.
 *
 * @param $ontnames
 * An array containing a set of URI strings for ontologies / schemas which property-value pairs need to be found for.
 *
 * @return
 * An associative array containing key values pairs of the information about the ontology / schema extracted for entity provided.
 */
function getOntologyProperties($ontent,$ontnames){
	global $ontimports;
	$props=array();
        // If the entity either does not have an rdf:about property or specifies one of a defined set of ontologies and there is at least one property-value pair
	if ((!$ontent['attrs']['rdf:about'] || in_array($ontent['attrs']['rdf:about'],$ontnames)) && sizeof($ontent['children'])>0){
		foreach ($ontent['children'] as $prop){
        		if ($prop['name']=="owl:imports"){
				$ontimports[]=$prop['attrs']['rdf:resource'];
			}
			else{
				if ($prop['tagData']) $val=$prop['tagData'];
	        	        else{
        	         		if ($prop['children']){
                        	        	$val="";
                                        	foreach($prop['children'] as $spn => $subprop){
                                	        	if ($subprop['tagData']) $val.=$subprop['name']." = ".$subprop['tagData'].", ";
                                                	else  $val.=$subprop['name']." = <a href=\"".$subprop['attrs']['rdf:resource']."\">".$subprop['attrs']['rdf:resource']."</a>, ";
                                        	}
                                	}
                                	else $val="<a href=\"".$prop['attrs']['rdf:resource']."\">".$prop['attrs']['rdf:resource']."</a>";
                        	}
                        	$props[]=array('prop'=>$prop['name'],'val'=>$val);
			}
                 }
        }
	return $props;
}
?>
