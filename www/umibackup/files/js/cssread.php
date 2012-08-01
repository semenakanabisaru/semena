<?php
$css_path = "./style.css";
error_reporting(0);
function parse_css($css_path) {
	global $g_css_content;
	$styles = Array();

	if(!is_file($css_path)) return false;
	
	$css_path = realpath($css_path);
	
	$css_path = str_replace("\\", "/", $css_path);
	$pathinfo = pathinfo($path);
	
	//print_R($pathinfo);die;
	if(strpos ($css_path, CURRENT_WORKING_DIR) !== 0 ) {
		 return false;
	}
	if( $pathinfo['filename'] == '.htaccess' || $pathinfo['filename'] == '.htpasswd') {
		 return false;
	}

	$css_content = file_get_contents($css_path);
	$g_css_content = $css_content;

	$pattern = "/\/\*(.*)\*\//";
	preg_match_all($pattern, $css_content, $ss);
	$ss = $ss[1];
	foreach($ss as $style_element) {
		$style_element = trim($style_element);

		$type = "";
		list($type, $element, $alias) = split("->", $style_element);


		if($type == "style") {
			list($stag, $sclass)  = split("\.", $element);

			$styles[] = Array(
						"alias" => $alias,
						"tag" => $stag,
						"class" => $sclass
					);
		}
	}
	
	$g_css_content .= <<<CSS
virtual-property {
	color:	#5A5A5A;
	font-style: italic;
}
CSS;
	return $styles;
}

?>