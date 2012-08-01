<?php
	require CURRENT_WORKING_DIR . '/libs/config.php';
	
	if(defined("CLUSTER_CACHE_CORRECTION") && CLUSTER_CACHE_CORRECTION) {
		cacheFrontend::getInstance();
		clusterCacheSync::getInstance();
	}

	function run_standalone($module_name) {
		return cmsController::getInstance()->getModule($module_name);
	}
?>