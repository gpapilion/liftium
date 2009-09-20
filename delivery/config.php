<?php
// Have the JSON still try to execute if we get errors
ini_set('display_errors', true);
ini_set('error_prepend_string', '/*');
ini_set('error_append_string', '*/');
ini_set('html_errors', false);
header('Cache-Control: public, max-age=90, s-maxage=10');

require dirname(__FILE__) . '/../includes/Framework.php';
require dirname(__FILE__) . '/../includes/LiftiumConfig.php';

$LiftiumConfig = new LiftiumConfig();

$pubid = Framework::getRequestVal("pubid", null, FILTER_VALIDATE_INT);
if (empty($pubid)){
	echo "/*";
	trigger_error("Missing pubid from " . @$_SERVER['HTTP_REFERER'], E_USER_WARNING);
	echo "*/";
	$config = array('error'=>"Missing pubid");
} else {
	$config = $LiftiumConfig->getConfig($_GET);
	if (empty($config->sizes)){
		$config = array('error'=>"No tags for this publisher");
	}
}

// Check to see if we can use the Etag to return a 304.
$checksum = md5(serialize($config));
header("ETag: $checksum");
if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $checksum){
	// Nothing has changed since the last time they asked.
	// NOTE THAT VARNISH HANDLES THIS, so we don't really need to here.
	header("HTTP/1.0 304 Not Modified");	
	exit;
}

$format = Framework::getRequestVal("format", "json", FILTER_SANITIZE_STRING);
if ($format == 'text'){
	header('Content-Type: text/plain');
	print_r($config);
} else {
	header('Content-Type: application/x-javascript');
	echo 'Liftium.config = ' . json_encode($config) . ';';
	if (Framework::getRequestVal('liftium_debug', 0, FILTER_VALIDATE_INT)){
		echo '/*' . print_r($_GET, true) . '*/' . "\n";
	}
}
?>
