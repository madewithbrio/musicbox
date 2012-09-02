<?php
require_once ('../bootstrap.php');

class Exception_NotFound extends Exception {}

try {
	
	if (preg_match('@^\/static\/@', $_SERVER['REQUEST_URI']))
	{
		throw new Exception_NotFound("request static file");
	}

	$section = Sapo_Context::getProcessedUri()->getSection();
	$controller = sprintf("Musicbox_Controller_%s", trim($section) ? ucwords( $section ) : "Default");
	try {
		$gui = Sapo_Controller::factory($controller);
	} catch (Exception $e) {
		$gui = Sapo_Controller::factory("Musicbox_Controller_Default"); // default 
	}
	$gui->run();

}
catch (Exception $ex)
{
	ob_clean();
	if (ENV == 'production') die(header('x', null, 500));

	header("HTTP/1.0 500 Internal Server Error");                
	print "<h1>Internal Server Error</h1>"; 
	print $ex->getMessage();
	print "<pre>";
	print $ex->getTraceAsString();
	ob_end_flush();
	die();
}
