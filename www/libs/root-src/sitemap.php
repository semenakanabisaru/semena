<?php

	header("Content-type: text/xml");
	if (ob_get_level()>0) {
	ob_clean();
	}

	require CURRENT_WORKING_DIR . '/libs/config.php';

	echo '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

	$cmsController = cmsController::getInstance();
	$domainId = $cmsController->getCurrentDomain()->getId();

	$dirName = CURRENT_WORKING_DIR . "/sys-temp/sitemap/{$domainId}/";
	$dir = dir($dirName);
	while (false !== ($file = $dir->read())) {
	   if(is_file($dirName . $file)) readfile($dirName . $file);
	}
	$dir->close();

	echo '</urlset>';
?>
