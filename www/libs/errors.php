<?php
	if (!defined('_C_ERRORS')) define('_C_ERRORS', true);

	error_reporting(DEBUG ? ~E_STRICT : E_ERROR);
	
	ini_set("display_errors", "1");

	function traceException($e) {
		global $message, $traceAsString;

		$message = $e->getMessage();
		$traceAsString = $e->getTraceAsString();

		header("HTTP/1.1 500 Internal Server Error");
		header("Content-type: text/html; charset=utf-8");
		header("Status: 500 Internal Server Error");
		require SYS_ERRORS_PATH . "exception.php";
		exit();
	}
	
	if (!defined('CRON')) set_exception_handler('traceException');

	function criticalErrorsBufferHandler($buffer) {
		if(isset($GLOBALS['memoryReserve'])) unset($GLOBALS['memoryReserve']);
		$errors = Array('Fatal', 'Parse');
		
		foreach($errors as $error) {
			if(strstr($buffer, "<br />\n<b>{$error} error</b>:") !== false) {
				$message = substr(trim(strip_tags($buffer)), strlen($error) + 9);
				$traceAsString  = "Backtrace can't be displayed";
				$e = new coreException($message);
				require SYS_ERRORS_PATH . 'exception.php';
				
				$errorBuffer = ob_get_contents();
				$buffer = substr($errorBuffer, strlen($buffer));
				break;
			}
		}
		return $buffer;
	}
	
	$GLOBALS['memoryReserve'] = str_repeat(" ", 1024);
	
	if(!defined("DEBUG") && function_exists("libxml_use_internal_errors")) {
	    libxml_use_internal_errors(true);
	}
	
	function checkXmlError($dom) {
	    if(defined("DEBUG") || !function_exists("libxml_get_last_error")) return;

		if($dom === false) {
			$error = libxml_get_last_error();
			libxml_clear_errors();
			
			$message = $error->message;
			$traceAsString = $error->file . "<br />in line " . $error->line . " column " . $error->column;
			
			require SYS_ERRORS_PATH . "exception.php";
			exit();
		}
	}
	
	function xsltErrorsHandler($errno, $errstr, $errfile, $errline, $e) {
	    if(defined("DEBUG") || !function_exists("libxml_get_last_error")) return;
		$message = $errfile;

		if($errline != 0 || $errno != 2) return;

		$message = "XSLT template in not correct.";
		$errors = libxml_get_errors();
			
		$traceAsString = "";
		foreach($errors as $error) {
			$traceAsString .= "<li>XSLT error: " . $error->message . "</li>";
		}
			
		require SYS_ERRORS_PATH . "exception.php";
		exit();
	}
	
	function errorsXsltListen() {
	    if(defined("DEBUG")) return;
		set_error_handler("xsltErrorsHandler");
		return error_reporting(~E_STRICT);
	}
	
	function errorsXsltCheck($er) {
	    if(defined("DEBUG")) return;
		error_reporting($er);
		restore_error_handler();
	}
?>
