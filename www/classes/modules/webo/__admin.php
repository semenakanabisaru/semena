<?php

abstract class __webo extends baseModuleAdmin {

	public function show () {
		
		$host = (string) (strlen (getRequest ("host"))) ? getRequest ("host") : $_SERVER['HTTP_HOST']; 
		
		$error = ($host == "localhost") ? 1 : 0;
		
		$params = Array(
				"config" => Array(
					"url:http_host" => $host
				)
			);
		
		$this->setDataType("list");
		$this->setActionType("view");
		
		$data = $this->prepareData($params, "settings");
		if($host) {
			$data['xml:info'] = umiRemoteFileGetter::get('http://webo.in/check/index2.php?url=' . $host . '&mode=xml');
		}
		$this->setData ($data);
		$this->doData ();
		
	}
	
}