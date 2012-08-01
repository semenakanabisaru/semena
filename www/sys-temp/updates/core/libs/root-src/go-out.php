<?php

	$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : false;
	$host = isset($_SERVER['HTTP_HOST']) ? str_replace('www.', '', $_SERVER['HTTP_HOST']) : false;
	$referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : false;

	$refererHost = false;
	if ($referer && isset($referer['host'])) $refererHost = $referer['host'];

	if (!$url || !$refererHost || !$host || strpos($refererHost, $host) === false){
		header ("HTTP/1.0 404 Not Found");
		exit();
	} else {
		header('Location:' . $url);
		exit();
	}



?>
