<?php
	include "./standalone.php";
	$cmsController = cmsController::getInstance();
	
	$banners = $cmsController->getModule("banners");
	if($banners instanceof def_module) {
		header("Content-type: text/javascript; charset=utf-8");
		
		$place = getRequest('place');
		$current_element_id = getRequest('current_element_id');
		
		$result = $banners->insert($place, 0, false, $current_element_id);
		$result = trim($result);
		$result = mysql_real_escape_string($result);
		$result = str_replace('\"', '"', $result);
		echo <<<JS

var response = {
	'place':	'{$place}',
	'data':		'{$result}'
};

if(typeof window.onBannerLoad == "function") {
	window.onBannerLoad(response);
} else {
	var placer = document.getElementById('banner_place_{$place}');
	if(placer) {
		placer.innerHTML = response['data'];
	}
}
JS;
	} else {
		echo "";
	}
	
?>