<?php

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], "/index.php") === 0) {
		header('Location: /', 301);
		exit();
	}

	require CURRENT_WORKING_DIR . '/libs/config.php';

	// Patch for Flex files uploader
	if (isset($_SERVER['HTTP_USER_AGENT']) &&
		(strstr($_SERVER['HTTP_USER_AGENT'], 'Shockwave Flash')!== false || strstr($_SERVER['HTTP_USER_AGENT'], 'Adobe Flash Player'))
		&& isset($_GET['PHPSESSID'])) {
		session_id($_GET['PHPSESSID']);
	}
	$config = mainConfiguration::getInstance();
	$buffer = OutputBuffer::current('HTTPOutputBuffer');

	if(PRE_AUTH_ENABLED) {
		umiAuth::tryPreAuth();
	}

	if(strpos(getServer("HTTP_REFERER"), getServer("HTTP_HOST")) === false) {
		$_SESSION["http_referer"] = getServer("HTTP_REFERER");
	}

	//Parse [stub] ini section
	if($config->get('stub', 'enabled')) {
		if(is_array($ips = $config->get('stub', 'filter.ip'))) {
			$enabled = !in_array(getServer('REMOTE_ADDR'), $ips);
		}
		else $enabled = true;

		if ($enabled) {
			$stubFilePath = $config->includeParam('system.stub');
			if (is_file($stubFilePath)) {
				require $stubFilePath;
				exit;
			}
			else throw new coreException("Stub file \"{$stubFilePath}\" not found");
		}
	}

	$cmsController = cmsController::getInstance();
	$cmsController->analyzePath();

	$currentModule = $cmsController->getCurrentModule();

	$currentTemplater = $cmsController->setCurrentTemplater(system_get_tpl('current'));
	$currentTemplater->init();

	if ($config->get('kernel', 'matches-enabled')) {
		$matches = new matches("sitemap.xml");
		$matches->setCurrentURI(getRequest('path'));

		try {
			$matches->execute();
		} catch (Exception $e) {
			traceException($e);
		}

		unset($matches);
	}

	if ($config->get('cache', 'static.enabled')) {
		require CURRENT_WORKING_DIR . '/libs/cacheControl.php';
		$staticCache = new staticCache;
		$staticCache->load();
	}
	else $staticCache = null;

	if ($currentTemplater instanceof xslTemplater) {
		switch ("force") {
			case (getRequest("xmlMode")):
				$buffer->contentType('text/xml');
				$buffer->push($currentTemplater->flushXml());
				break;
			case (getRequest("jsonMode")):
				$buffer->contentType('text/javascript');
				$buffer->push($currentTemplater->flushJson());
				break;
			default :
				$cmsController->getModule($currentModule)->cms_callMethod('systemonBeforeDisplay', array());
				$buffer->push($currentTemplater->parseResult());
				if (is_null(getRequest('showStreamsCalls')) == false) {
					$buffer->contentType('text/xml');
					$buffer->clear();
					$buffer->push(umiBaseStream::getCalledStreams());
					$buffer->end();
				}
		}
	}
	else {
		$cmsController->getModule($currentModule)->cms_callMethod('systemonBeforeDisplay', array());
		$buffer->push($currentTemplater->parseResult());
	}
	if ($statistics = $cmsController->getModule('stat')) {
		$statistics->pushStat();
	}

	if ($staticCache instanceof staticCache) {
		$staticCache->save($buffer->content());
	}

	$buffer->end();
?>