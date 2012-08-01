<?php

	if (!defined("CURRENT_WORKING_DIR")) {
		define("CURRENT_WORKING_DIR", str_replace("\\", "/", dirname(dirname(__FILE__))));
	}

	if(!defined('CONFIG_INI_PATH')) {
		define('CONFIG_INI_PATH', CURRENT_WORKING_DIR . '/config.ini');
	}

	if (!function_exists('showWorkTime')) {
		require_once CURRENT_WORKING_DIR . '/libs/root-src/profiler.php';
	}
	showWorkTime("config start");

	if(!class_exists('mainConfiguration')) {
		require CURRENT_WORKING_DIR . '/libs/configuration.php';
	}

	try {
		$config = mainConfiguration::getInstance();
	} catch (Exception $e) {
		echo 'Critical error: ', $e->getMessage();
		exit;
	}

	$ini = $config->getParsedIni();

	initConfigConstants($ini);

	define("SYS_KERNEL_PATH", $config->includeParam('system.kernel'));
	define("SYS_KERNEL_ASM", $config->includeParam('system.kernel.assebled'));
	define("SYS_LIBS_PATH", $config->includeParam('system.libs'));
	define("SYS_DEF_MODULE_PATH", $config->includeParam('system.default-module'));
	define("SYS_TPLS_PATH", $config->includeParam('templates.tpl'));
	define("SYS_XSLT_PATH", $config->includeParam('templates.xsl'));
	define("SYS_SKIN_PATH", $config->includeParam('templates.skins'));
	define("SYS_ERRORS_PATH", $config->includeParam('system.error'));
	define("SYS_MODULES_PATH", $config->includeParam('system.modules'));
	define("SYS_CACHE_RUNTIME", $config->includeParam('system.runtime-cache'));
	define("SYS_MANIFEST_PATH", $config->includeParam('system.manifest'));
	define("SYS_KERNEL_STREAMS", $config->includeParam('system.kernel.streams'));

	define("KEYWORD_GRAB_ALL", $config->get('kernel', 'grab-all-keyword'));

	$cacheSalt = $config->get('system', 'salt');
	if(!$cacheSalt) {
		$cacheSalt = sha1(rand());
		$config->set('system', 'salt', $cacheSalt);
	}
	define("SYS_CACHE_SALT", $cacheSalt);

	spl_autoload_register('umiAutoload');
                                      
	if(!defined('_C_REQUIRES')) {
        showWorkTime("config require requires start");
		require SYS_LIBS_PATH . 'requires.php';
        showWorkTime("config require requires end");
	}

	// [debug]
	$debug = false;
	if($config->get('debug', 'enabled')) {
		$ips = $config->get('debug', 'filter.ip');
		if(is_array($ips)) {
			if(in_array(getServer('REMOTE_ADDR'), $ips)) {
				$debug = true;
			}
		} else {
			$debug = true;
		}
	}
	if (!defined('DEBUG')) define('DEBUG', $debug);
	if (!defined('DEBUG_SHOW_BACKTRACE')) {

		$showBacktrace = false;
		$allowedIps = $config->get('debug', 'allowed-ip');
		$allowedIps = is_array($allowedIps) ? $allowedIps : array();
		if ($config->get('debug', 'show-backtrace') && (!count($allowedIps) || in_array(getServer('REMOTE_ADDR'), $allowedIps))) $showBacktrace = true;
		define('DEBUG_SHOW_BACKTRACE', $showBacktrace);
	}

	if(!defined('_C_ERRORS')) {
		require SYS_LIBS_PATH . 'errors.php';
	}

	if ($timezone = $config->get("system", "time-zone")) {
		@date_default_timezone_set($timezone);
	}

	initConfigConnections($ini);

	if(defined("LIBXML_VERSION")) {
		define("DOM_LOAD_OPTIONS", (LIBXML_VERSION < 20621) ? 0 : LIBXML_COMPACT);
	} else {
		define("DOM_LOAD_OPTIONS", LIBXML_COMPACT);
	}
	if (!defined("PHP_INT_MAX")) define("PHP_INT_MAX", 4294967296 / 2 - 1);


	if(!isset($_ENV['OS']) || strtolower(substr($_ENV['OS'], 0, 3)) != "win") {
		setlocale(LC_NUMERIC, 'en_US.utf8');
	}

	if(function_exists("mb_internal_encoding")) {
		mb_internal_encoding('UTF-8');
	}

	// system.session-lifetime
	ini_set("session.gc_maxlifetime", SESSION_LIFETIME * 60);
	if((int) $config->get('system', 'session-force-gc')) {
		ini_set("session.gc_probability", 1);
		ini_set("session.gc_divisor", 1);
	}
	ini_set("session.cookie_lifetime", "0");
	ini_set("session.use_cookies", "1");
	ini_set("session.use_only_cookies", "1");

	// kernel:cluster-cache-correction
	if(CLUSTER_CACHE_CORRECTION) {
		cacheFrontend::getInstance();
		clusterCacheSync::getInstance();
	}

	$remoteIP = getServer('REMOTE_ADDR');
	$blackIps = array();

	$result1 = l_mysql_query("SHOW TABLES LIKE 'cms3_objects'");
	$result2 = l_mysql_query("SHOW TABLES LIKE 'cms3_object_types'");
	if(mysql_num_rows($result1) && mysql_num_rows($result2)) {
		$result =	l_mysql_query("SELECT name FROM `cms3_objects` where type_id = (SELECT id FROM `cms3_object_types` where guid='ip-blacklist')");
		while($row = mysql_fetch_array($result)){
			$blackIps[] = $row[0];
		}
	}

	$ipList = $config->get('kernel', 'ip-blacklist');
	if(!empty($ipList) && $remoteIP !== null) {
		$ips = explode(",", $ipList);
		$blackIps = array_merge($blackIps, $ips);
	}

	foreach ($blackIps as $id => $blackIp) {
		$blackIp = trim($blackIp);
			if ($blackIp == $remoteIP) {
			$buffer = OutputBuffer::current('HTTPOutputBuffer');
			$buffer->contentType('text/html');
			$buffer->charset('utf-8');
			$buffer->status('403 Forbidden');
			$buffer->clear();
			$buffer->end();
		}
	}

	function umiAutoload($className) {
        showWorkTime("umiAutoload ".$className." start",2);
		global $includes;

		if ($className == "XSLTProcessor" && !class_exists("XSLTProcessor")){
			xslt_fatal();
		}
        
		//Debug section
		if(defined('INTERRUPT_DEPRECATED_CALL') && INTERRUPT_DEPRECATED_CALL) {
			$deprecatedClasses = array('umiSelection', 'umiSelectionsParser');
			if(in_array($className, $deprecatedClasses)) {
				$e = new coreException("Deprecated class \"{$className}\" called");
				traceException($e);
			}
		}

		if(isset($includes[$className])) {
			$files = $includes[$className];
			if(is_array($files))
            {
                foreach($files as $filePath)
                {
                    require_once $filePath;
                    showWorkTime("umiAutoload ".basename($filePath)." required",2);
                }
            }
		}
        showWorkTime("umiAutoload ".$className." end",2);
	}


	function initConfigConstants($ini) {
		$defineConstants = array(
			'system:db-driver' => array('DB_DRIVER', '%value%'),
			'system:version-line' => array('CURRENT_VERSION_LINE', '%value%'),
			'system:session-lifetime' => array('SESSION_LIFETIME', '%value%'),
			'system:default-date-format' => array('DEFAULT_DATE_FORMAT', '%value%'),
			'kernel:use-reflection-extension' => array('USE_REFLECTION_EXT', '%value%'),
			'kernel:cluster-cache-correction' => array('CLUSTER_CACHE_CORRECTION', '%value%'),
			'kernel:xslt-nested-menu' => array('XSLT_NESTED_MENU', '%value%'),
			'kernel:pages-auto-index' => array('PAGES_AUTO_INDEX', '%value%'),
			'kernel:enable-pre-auth' => array('PRE_AUTH_ENABLED', '%value%'),
			'kernel:ignore-module-names-overwrite' => array('IGNORE_MODULE_NAMES_OVERWRITE', '%value%'),
			'kernel:xml-format-output' => array('XML_FORMAT_OUTPUT', '%value%'),
			'kernel:selection-max-joins' => array('MAX_SELECTION_TABLE_JOINS', '%value%'),
			'kernel:property-value-mode' => array('XML_PROP_VALUE_MODE', '%value%'),
			'kernel:xml-macroses-disable' => array('XML_MACROSES_DISABLE', '%value%'),
			'kernel:selection-calc-found-rows-disable' => array('DISABLE_CALC_FOUND_ROWS', '%value%'),
			'kernel:sql-query-cache' => array('SQL_QUERY_CACHE', '%value%'),
			'seo:calculate-e-tag' => array('CALC_E_TAG', '%value%'),
			'seo:calculate-last-modified' => array('CALC_LAST_MODIFIED', '%value%')
		);

		foreach($defineConstants as $name => $const) {
			list($section, $variable) = explode(':', $name);
			$value = $const[1];

			if(is_string($value)) {
				$iniValue = isset($ini[$section][$variable]) ? $ini[$section][$variable] : "";
				$value = str_replace('%value%', $iniValue, $value);
			} else if (!$value && isset($const[2])) {
				$value = $const[2];
			}

			if(!defined($const[0])) {
				if($const[0] == 'CURRENT_VERSION_LINE' && !$value) {
					continue;
				}
				define($const[0], $value);
			}
		}
	}


	function initConfigConnections($ini) {
		$connections = array();

		foreach($ini['connections'] as $name => $value) {
			list($class, $pname) = explode('.', $name);
			if(!isset($connections[$class])) {
				$connections[$class] = array(
										'type'        => 'mysql',
										'host'		  => 'localhost',
										'login'       => 'root',
										'password'    => '',
										'dbname'      => 'umi',
										'port'	      => false,
										'persistent'  => false,
										'compression' => false);
			}
			$connections[$class][$pname] = $value;
		}

		$pool = ConnectionPool::getInstance();
		foreach($connections as $class => $con) {
				switch($con['type']) {
						default:
								$pool->setConnectionObjectClass();
				}

				if($con['dbname'] == '-=demo=-' || $con['dbname'] == '-=custom=-') {
					if($con['dbname'] == '-=demo=-') {
						require './demo-center.php';
					}

					$con['host'] = MYSQL_HOST;
					$con['login'] = MYSQL_LOGIN;
					$con['password'] = MYSQL_PASSWORD;
					$con['dbname'] = ($con['dbname'] == '-=custom=-') ? MYSQL_DB_NAME : DEMO_DB_NAME;
				}

				$pool->addConnection($class, $con['host'], $con['login'], $con['password'], $con['dbname'],
					($con['port'] !== false) ? intval($con['port']) : false,
					(bool) intval($con['persistent']) );
		}

		if(DB_DRIVER == "mysql") {
			$connection = ConnectionPool::getInstance()->getConnection();
			ini_set('mysql.trace_mode', false);
		}
	}

	function mysql_fatal() {
		require "./errors/mysql_failed.html";
		exit();
	}

	function xslt_fatal(){
		require ("./errors/xslt_failed.html");
		exit();
	}
?>