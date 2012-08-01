<?php
	define("CRON", (isset($_SERVER['HTTP_HOST'])?"HTTP":"CLI"));
	require CURRENT_WORKING_DIR . "/libs/root-src/standalone.php";

	@ob_clean();
	if (CRON == "HTTP") {
		$buffer = outputBuffer::current('HTTPOutputBuffer');
		$buffer->contentType('text/plain');
		
		$comment = <<<END
This file should be executed by cron only. Please, run it via HTTP for test only.
Notice: maximum priority level can accept values between "1" and "10", where "1" is maximum priority.


END;
		$buffer->push($comment);
	}
	else $buffer = outputBuffer::current('CLIOutputBuffer');

	$modules = array();

	if (!empty($argv[1])) {
		$modules = explode(',',$argv[1]);
	}

	if (!empty($_GET['module'])) {
		$modules = (array) $_GET['module'];
	}

	$cron = new umiCron;
	$cron->setModules($modules);
	$cron->run();
	
	$buffer->push($cron->getParsedLogs());
	$buffer->end();
?>