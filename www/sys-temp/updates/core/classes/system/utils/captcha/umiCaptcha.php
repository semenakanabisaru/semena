<?php
/**
	* Класс, контролирующий работу с капчей
*/
class umiCaptcha implements iUmiCaptcha {
	/**
		* Генерирует код вызова капчи
		* @param String $template = "default" шаблон для генерации кода капчи
		* @param String $input_id = "sys_captcha" id инпута для капчи
		* @param String $captcha_hash = "" md5-хеш кода, который будет выведен на картинке для предворительно проверки на клиенте
		* @return Array|String результат обработки в зависимости от текущего шаблонизатора
	*/
	public static function generateCaptcha($template="default", $input_id="sys_captcha", $captcha_hash="") {
		// check captcha
		if (!self::isNeedCaptha()) {
			return def_module::isXSLTResultMode() ? array(
				'comment:explain' => 'Captcha is not required for logged users'
			) : '';
		}

		if(!$template) $template = "default";
		if(!$input_id) $input_id = "sys_captcha";
		if(!$captcha_hash) $captcha_hash = "";
		$randomString = "?" . time();

		$block_arr = array();
		$block_arr['void:input_id'] = $input_id;
		$block_arr['attribute:captcha_hash'] = $captcha_hash;
		$block_arr['attribute:random_string'] = $randomString;
		$block_arr['url'] = array(
			'attribute:random-string'	=> $randomString,
			'node:value'				=> '/captcha.php'
		);

		list($template_captcha) = def_module::loadTemplates("captcha/".$template, "captcha");

		return def_module::parseTemplate($template_captcha, $block_arr);
	}

	/**
		* Свериться с системными настройками, чтобы выяснить, нужно ли использовать капчу.
		* @return Boolean true, если капча обязательна, false если капча отключена или пользователь уже авторизован
	*/
	public static function isNeedCaptha() {
		if (cmsController::getInstance()->getModule('users')->is_auth()) return false;
		if(getCookie('umi_captcha') == md5(getCookie('user_captcha'))) {
			$_SESSION['is_human'] = 1;
		}

		return (getSession('is_human') != 1);
	}

	/**
		* Проверить была корректно введена капча в текущей сессии
		* @return Boolean если true, то пользователь определен как не робот, false - еще не определен
	*/
	public static function checkCaptcha() {
		$config = mainConfiguration::getInstance();
		if(!$config->get('anti-spam', 'captcha.enabled')) {
			return true;
		}

		$permissions = permissionsCollection::getInstance();
		if ($permissions->isAuth()) return true;

		if (isset($_COOKIE['umi_captcha']) && strlen($_COOKIE['umi_captcha'])) {
			if ($_COOKIE['umi_captcha'] == md5(getArrayKey($_COOKIE, 'user_captcha')) || $_COOKIE['umi_captcha'] == md5(getRequest('captcha')) ) {
				$captcha = getRequest('captcha');
				if(md5($captcha) == $_COOKIE['umi_captcha']) {
					setcookie("user_captcha", $captcha, time() + 3600*24*31, "/");
				}
				$_SESSION['is_human'] = 1;
				return true;
			} else {
				unset($_SESSION['is_human']);
				return false;
			}
		}
		return false;
	}

	/**
		* Получить объект отрисовки капчи
		* @return captchaDrawer объект отрисовки капчи
	*/
	public static function getDrawer() {
		static $drawer = null;
		if(!is_null($drawer)) return $drawer;

		$config = mainConfiguration::getInstance();
		$drawerName = $config->get('anti-spam', 'captcha.drawer');
		if(!$drawerName) {
			$drawerName = 'default';
		}
		$path = CURRENT_WORKING_DIR . '/classes/system/utils/captcha/drawers/' . $drawerName . '.php';
		if(!is_file($path)) {
			throw new coreException("Captcha image drawer named \"{$drawerName}\" not found");
		}
		require $path;

		$className = $drawerName . 'CaptchaDrawer';
		if(class_exists($className)) {
			return $drawer = new $className;
		} else {
			throw new coreException("Class \"{$className}\" not found in \"{$path}\"");
		}
	}
}

?>
