<?php

	require CURRENT_WORKING_DIR . '/libs/config.php';
	showWorkTime("config readed");

	// Patch for Flex files uploader
	if (isset($_SERVER['HTTP_USER_AGENT']) &&
		(strstr($_SERVER['HTTP_USER_AGENT'], 'Shockwave Flash')!== false || strstr($_SERVER['HTTP_USER_AGENT'], 'Adobe Flash Player'))
		&& isset($_GET['PHPSESSID'])) {
		session_id($_GET['PHPSESSID']);
	}
	$config = mainConfiguration::getInstance();

	
	if ($config->get('seo', 'index-redirect') && strpos(trim($_SERVER['REQUEST_URI'], ' /'), "index.php") === 0) {
		header('Location: /', 301);
		exit();
	}
	
	
	$buffer = OutputBuffer::current('HTTPOutputBuffer');
	showWorkTime("buffer started");

	if(PRE_AUTH_ENABLED) {
		umiAuth::tryPreAuth();
	}

	if(strpos(getServer("HTTP_REFERER"), getServer("HTTP_HOST")) === false) {
		$_SESSION["http_referer"] = getServer("HTTP_REFERER");
	}
	
	if(!getSession("http_target")) {
		$_SESSION["http_target"] = getServer("REQUEST_URI");
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
	showWorkTime("stub parsed");

	if ($config->get('kernel', 'matches-enabled')) {
		try {
			$matches = new matches("sitemap.xml");
			showWorkTime("matches init");
			$matches->setCurrentURI(getRequest('path'));
			showWorkTime("current uri setted");
			$matches->execute();
			showWorkTime("matches executed");
		} catch (Exception $e) {
			// Disabled exception
			//traceException($e);
			showWorkTime("exception catched");
		}

		unset($matches);
	}
 
	$cmsController = cmsController::getInstance();
	showWorkTime("cmsController init");
	$cmsController->analyzePath();
	showWorkTime("path analyzed");

	// static cache
	if ($config->get('cache', 'static.enabled')) {
		require_once CURRENT_WORKING_DIR . '/libs/cacheControl.php';
		$staticCache = new staticCache;
		$staticCache->load();
	} else {
		$staticCache = null;
	}
    showWorkTime("cacheControl started");

	$currentModule = $cmsController->getCurrentModule();
	showWorkTime("cmsController getCurMod");
	$currentMethod = $cmsController->getCurrentMethod();

	$currentTemplater = $cmsController->getCurrentTemplater();

	showWorkTime("templater init");

	if (getRequest("xmlMode") == 'force') {
		// flush XML
		$dom = new DOMDocument('1.0', 'utf-8');
		$rootNode = $dom->createElement("result");
		$dom->appendChild($rootNode);
		$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');


		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);
		$globalVars = $cmsController->getGlobalVariables();

		$translator = new xmlTranslator($dom);
		$translator->translateToXml($rootNode, $globalVars);

		$buffer->contentType('text/xml');
		$buffer->push($dom->saveXML());
	} elseif (getRequest("jsonMode") == 'force') {

		// flush json
		$buffer->contentType('text/javascript');
		$buffer->option('generation-time', false);

		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);
		$globalVars = $cmsController->getGlobalVariables();

		$translator = new jsonTranslator;
		$result = $translator->translateToJson($globalVars);
		$buffer->push($result);
	} else {

		//$cmsController->getModule($currentModule)->cms_callMethod('systemonBeforeDisplay', array());
		$templatesSource = $currentTemplater->getTemplatesSource();
		list($commonTemplate) = $currentTemplater->getTemplates($templatesSource, 'common');

		$globalVars = $cmsController->getGlobalVariables();
		if ($currentElementId = $cmsController->getCurrentElementId()) {
			$currentTemplater->setScope($currentElementId);
		}
		$result = $currentTemplater->parse($globalVars, $commonTemplate);
		$result = $currentTemplater->cleanup($result);
		$buffer->push($result);

		// flush streams calls
		if (!is_null(getRequest('showStreamsCalls'))) {
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push(umiBaseStream::getCalledStreams());
			$buffer->end();
		}
	}

    showWorkTime("stream parsed");
    
	if ($statistics = $cmsController->getModule('stat')) {
		$statistics->pushStat();
	}
    showWorkTime("stat pushed");

	if ($staticCache instanceof staticCache) {
		$staticCache->save($buffer->content());
	}
    showWorkTime("static cache saved");

	$buffer->end();
?>