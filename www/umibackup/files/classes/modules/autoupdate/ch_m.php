<?php
	function ch_get_version_line() {
		$regedit = regedit::getInstance();
		return (string) $regedit->getVal("//modules/autoupdate/system_edition");
	}
	
	function get_file($url) {
		return file_get_contents($url);
			}
			
	function ch_get_illegal_modules() {
		$regedit = regedit::getInstance();
			
		$info = array();
		$info['type']='get-modules-list';
		$info['revision'] = $regedit->getVal("//modules/autoupdate/system_build");
		$info['host']=getServer('HTTP_HOST');
		$info['ip']  =getServer('SERVER_ADDR');
		$info['key'] =$regedit->getVal("//settings/keycode");		
		$url = base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv') . "?" . http_build_query($info, '', '&');
			
		$result = get_file($url);
			
		$xml = new DOMDocument();
		$xml->loadXML($result);
			
		$xpath = new DOMXPath($xml);
			
		$illegal_modules = array();
		
		$no_active = $xpath->query("//module[not(@active)]");		
		foreach($no_active as $module) {
			$illegal_modules[] = $module->getAttribute("name");
		}
		
		unset($regedit, $info, $url, $result, $xml, $xpath, $no_active, $module);
		return $illegal_modules;
	}
	
	function ch_remove_m_garbage() {
		$modules = ch_get_illegal_modules();
		foreach($modules as $module) {
			ch_remove_illegal_module($module);
		}
	}
	
	function ch_remove_illegal_module($module_name) {
		if(!trim($module_name, " \r\n\t\/")) {
			return;
		}
		$regedit = regedit::getInstance();
		$regedit->delVar("//modules/{$module_name}");
		//ch_remove_dir("./classes/modules/{$module_name}/");
	}
	
	function ch_remove_dir($path) {
		if(is_dir($path)) {
			if(is_writable($path)) {
				$dir = opendir($path);
				while($obj = readdir($dir)) {
					if($obj == "." || $obj == "..") continue;
					$objpath = $path . $obj;
					
					if(is_file($objpath)) {
						if(is_writable($objpath)) {
							unlink($objpath);
						}
					} else if (is_dir($objpath)) {
						ch_remove_dir($objpath . '/');
					} else {
						continue;
					}
				}
				rmdir($path);
			}
		}
		
	}
	
?>
