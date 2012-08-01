<?php
	//Убираем автоматическое экранирование, если оно включено (в нем нет необходимости).
	function protect_array(&$input_array)  {
		if(is_array($input_array)) {
			foreach($input_array as $var => $val) {
				if(is_array($val)) {
					$val = protect_array($val);
				} else {
					$val = preg_replace('/\0/s', '', $val);
					$val = stripslashes($val);
				}
				$input_array[$var] = $val;
			}
		}
		return $input_array;
	}
	 
	$_REQUEST = protect_array($_REQUEST);
	$_POST = protect_array($_POST);
	$_GET = protect_array($_GET);
?>
