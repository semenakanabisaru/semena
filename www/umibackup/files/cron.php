#!/usr/local/bin/php
<?php
	define("CURRENT_WORKING_DIR", str_replace("\\", "/", $dirname = dirname(__FILE__)));
	require CURRENT_WORKING_DIR . '/libs/root-src/cron.php';
?>