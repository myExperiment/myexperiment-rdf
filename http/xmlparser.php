<?php
/**
 * @file http/xmlparser.php
 * @brief Web-based utility converting XML file in PHP array
 * @version beta
 * @author David R Newman
 * @details Web-based utility for converting XML file into multidimensional PHP array.  To test the validity of the chosemn XML file and evaluate how this is represented in a PHP array.
 */

	include('include.inc.php');
	include('functions/xml.inc.php');
	if ($_POST['filename']){
		$fh=fopen($_POST['filename'],'r');
		while ($line = fgets($fh, 4096)) {
                	$fdata.=$line;
	        }
		print_r($fdata);
        	$pxml=parseXML($fdata);
	}
?>
<html>
<head></head>
<body>
	<form method="POST">
          <p>Filename: <input type="text" name="filename" value="<?=$_POST['filename']?>"/> <input type="submit" name="submit" value="Submit"/></p>
       </form>
<?php print_r($pxml); ?>
</body>
</html>
