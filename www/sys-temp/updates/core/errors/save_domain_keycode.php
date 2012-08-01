<?php

	require_once("../libs/config.php");
	$regedit = regedit::getInstance();

	if ($regedit->checkSelfKeycode()) exit;

	if (is_file(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/registry")) {
		unlink(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/registry");
	}

	if (is_file(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/trash")) {
		unlink(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/trash");
	}

	cacheFrontend::getInstance()->flush();

	$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : str_replace("\\", "", $_SERVER['DOCUMENT_ROOT']);
	$domain = getServer('HTTP_HOST');
	$keycode = getRequest('keycode');
	$do = getRequest('do');
	$domain_keycode = getRequest('domain_keycode');
	$license_codename = getRequest('license_codename');

	if ( $do == 'load' ) {
		header("Content-type: text/xml; charset=utf-8");
		$url = 'aHR0cDovL2luc3RhbGwudW1pLWNtcy5ydS9maWxlcy90ZXN0aG9zdC5waHA=';
		$result = umiRemoteFileGetter::get(base64_decode($url), dirname(__FILE__).'/testhost.php');
		$content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		if ($result->getSize() == 0) {
			$content.= "<result><error>Не удается загрузить тесты хостинга.</error></result>";
		}
		else{
			$content.= "<result>ok</result>";
		}

		echo $content;
		exit();
	}
	
	if ( $do == 'test') {
		header("Content-type: text/xml; charset=utf-8");
		require(dirname(__FILE__).'/testhost.php');
		$tests = new testHost();
		$conInfo = ConnectionPool::getInstance()->getConnection()->getConnectionInfo();
		$tests->setConnect($conInfo['host'], $conInfo['user'], $conInfo['password'], $conInfo['dbname']);
		$result = $tests->getResults();
		$content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$content .= "<result>";
		if (count($result)>0) {
			foreach($result as $error) {
				$error_url = "http://errors.umi-cms.ru/upage://".$error[0]."/";
				$error_xml = simplexml_load_string(umiRemoteFileGetter::get($error_url));
				$error_msg = $error_xml->xpath('//property[@name = "short_description"]/value');
				$content .= '<error code="'.$error[0].'" critical="'.$error[1].'">'.((string)$error_msg[0]).'</error>';
			}
		}
		else {
			$content .= '<message>ok</message>';
		}
		$content .= '</result>';
		echo $content;
		exit();
	}	
	
	if ( ( is_null($domain_keycode) || is_null($license_codename) ) && !is_null($keycode) ) {
		// Проверка лицензионного ключа
		$params = array(
			'ip' => $ip,
			'domain' => $domain,
			'keycode' => $keycode
		);
		$url = 'aHR0cDovL3Vkb2QudW1paG9zdC5ydS91ZGF0YTovL2N1c3RvbS9wcmltYXJ5Q2hlY2tDb2RlLw==';
		$url = base64_decode($url).base64_encode(serialize($params)).'/';
		$result = umiRemoteFileGetter::get($url);
		header("Content-type: text/xml; charset=utf-8");
		echo $result;
		exit();
	}
	
	if (strlen(str_replace("-", "", $domain_keycode)) != 33) exit;

	if (!$license_codename) exit;

	$pro = array('commerce', 'business', 'corporate', 'commerce_enc', 'business_enc', 'corporate_enc', 'gov');

	$internalCodeName = in_array($license_codename, $pro) ? 'pro' : $license_codename;

	$checkKey = umiTemplater::getSomething($internalCodeName, $domain);
	if ($checkKey != substr($domain_keycode, 12)) exit;

	$primaryDomain = selector::get('domain')->id(1);
	$primaryDomain->setHost($domain);
	$primaryDomain->commit();

	$regedit = regedit::getInstance();
	$regedit->setVar("//settings/keycode", $domain_keycode);
	$regedit->setVar("//settings/system_edition", $license_codename);
	$regedit->setVar("//modules/autoupdate/system_edition", $license_codename);

	include CURRENT_WORKING_DIR . "/classes/modules/autoupdate/ch_m.php";
	ch_remove_m_garbage();

?>