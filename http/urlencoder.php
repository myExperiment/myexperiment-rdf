<?php
/**
 * @file http/urlencoder.php
 * @brief URL Encoder/Decoder web page
 * @version beta
 * @author David R Newman
 * @details Web page that provides an application for encoding/decoding strings so they can be added to / extracted from the GET parameters of a URL.
 */

include('include.inc.php');
/** @brief The title of the page to be rendered in an h1 tag and in title of the html header. */
$pagetitle="URL Encoder/Decoder";
include('partials/header.inc.php');
include('functions/utility.inc.php');
if ($_POST['encode']){
	/** @brief The string to URL encoded/decoded. */
	$string=urlencode($_POST['string']);
	printMessage("String Encoded",'center');
}
else {
	if ($_POST['decode']){
		$string=urldecode($_POST['string']);
		printMessage("String Decoded",'center');
	}
}	
?>
<form name="urlendec" method="post">
<p style="text-align: center;">
  <b>String to Encode/Decode:</b>
  <br/>
  <textarea name="string" cols="80" rows="8"><?php echo $string; ?></textarea>
  <br/>
  <input type="submit" name="encode" value="Encode">&nbsp;&nbsp;
  <input type="submit" name="decode" value="Decode">
</p>

<?php include('partials/footer.inc.php');
