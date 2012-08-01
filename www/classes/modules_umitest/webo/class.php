<?php

class webo extends def_module {
	
	public function __construct () {
		parent::__construct ();
		
		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__webo");
		}
	}
	
	/*public function __call ($a, $b) {
		throw new Exception ("Module WEBO doesn't contain public methods!");
	}*/
	
}