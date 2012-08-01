<?php
	error_reporting(0);
	ini_set('display_errors', 0);

	set_time_limit(0);

	if ((isset($_REQUEST['step'])&&$_REQUEST['step']=='ping')&&(isset($_REQUEST['guiUpdate'])&&$_REQUEST['guiUpdate']=='true')) {
		header('Content-Type: text/xml; charset=utf-8');
		echo "<result>ok</result>";
		die();
	}

	// check is cli mode
	if (isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT'])) {
		define("INSTALLER_CLI_MODE", false);
	} else {
		define("INSTALLER_CLI_MODE", true);
	}

	/**
	* Режим дебага исталлера, когда инсталлер не обновляется
	*/
	define("INSTALLER_DEBUG", false);

	define("_C_REQUIRES", true);
	define('_C_ERRORS', true);
	define('CRON', true);
	define('DEBUG', true);
	define('UMICMS_CLI_MODE', INSTALLER_CLI_MODE);


	if(INSTALLER_CLI_MODE) {
		// error handlers
		function exception_error_handler($errno, $errstr, $errfile, $errline) {
			try {
				throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
			} catch (ErrorException $exception) {
				$msg = "Ошибка установки #{$errno}: \"" . $errstr . "\" в строке " . $errline . " файла " . $errfile . "\n";
				if ($errno!=0) {
					$msg.= "Подробнее об ошибке http://errors.umi-cms.ru/{$errno}/\n";
				}
				echo $msg;
			}
		}
		set_error_handler("exception_error_handler");

		function exception_handler(Exception $exception) {
			$errno = $exception->getCode();
			$msg = "Критическая ошибка установки #{$errno}: \"" . $exception->getMessage() . "\" в строке " . $exception->getLine() . " файла " . $exception->getFile() . "\n";
			if ($errno!=0) {
				$msg.= "Подробнее об ошибке http://errors.umi-cms.ru/{$errno}/\n";
			}
			// write into stderr
			if ($fp = fopen("php://stderr", "w")) {
				fputs($fp, $msg);
			}
			die();
		}
		set_exception_handler('exception_handler');

		$args = parse_argv($_SERVER['argv']);
	} else {
		$args = $_REQUEST;
		header("Content-type: text/xml; charset=utf-8");
	}

	$install_mode = true; // Install
	if (in_array(substr(dirname(__FILE__), -4, 4), array('/smu', '\smu'))) {
		define("CURRENT_WORKING_DIR", realpath(dirname(__FILE__).'/..'));
		if (is_file(CURRENT_WORKING_DIR.'/installed')) { // Update
			$install_mode = false;
		}
	}
	else {
		define("CURRENT_WORKING_DIR", realpath(dirname(__FILE__)));
	}

	$step = isset($args['step']) ? strtolower(trim($args['step'])) : 'install-run';
	$temp_dir = './sys-temp/updates/';

	umask(0);

	$olddir = getcwd();
	chdir(CURRENT_WORKING_DIR);

	$installer = new umiInstallExecutor($temp_dir, $install_mode, $args);
	$installer->run($step, INSTALLER_CLI_MODE, $args);

	chdir($olddir);

	exit();

	/**
	* Производит процесс установки /обновлений
	*/
	class umiInstallExecutor {
		const BUFFER_SIZE = 128;
		const STATE_FILE_NAME = ".isf";

		private $step = 'run';
		private $cli_mode = true;
		private $settings = null;
		private $params = array();
		private $connection = null;
		private $install_mode = false;

		static private $split_block_size;

		static private $state = false;
		static private $log   = array();

		private $temp_dir = "";

		private function flushLog($msg) {
			if ($this->cli_mode) {
				echo $msg, "\n";
			} else {
				self::$log[] = $msg;
			}
		}

		private function appendLog($tail) {
			if($this->cli_mode) return;
			self::$log = array_merge(self::$log, $tail);
		}

		private function getConfigOption($section, $option, $default = null, $error_message = false, $error_no = 0) {
			if (is_null($this->settings)) $this->getInstallConfig( isset($error_message)&&$error_message!==false );
			if (isset($this->settings[$section][$option])) {
				return $this->settings[$section][$option];
			} else {
				if ($error_message) {
					throw new Exception($error_message, $error_no);
				}
				return $default;
			}
		}

		private function getInstallConfig($throw=true) {
			if (!is_null($this->settings)) return $this->settings;
			if ($this->install_mode) {
				// В режиме установки ищем настройки рядом с инсталлятором
				$config_path = dirname(__FILE__) . "/install.ini";
			}
			else {
				// В режиме обновления - в корне сайта
				$config_path = CURRENT_WORKING_DIR . "/install.ini";
			}

			if (!is_file($config_path)) {
				if ($throw) {
					throw new Exception("Не найден файл настроек для установки install.ini");
				}
				else {
					return false;
				}
			}
			$this->settings = parse_ini_file($config_path, true);
		}

		private function checkDone($method) {
			return /*!$this->cli_mode &&*/ isset(self::$state[$method]) && self::$state[$method];
		}

		private function setDone($method, $done = true) {
			self::$state[$method] = $done;
			$this->saveState();
		}

		private function getComponentOffset($component) {
			return (isset(self::$state['@components']) && isset(self::$state['@components'][$component]))
					?
						(int) self::$state['@components'][$component]
					:
						0;
		}


		/**
		* Загружает состояние установщика из файла
		*
		*/
		private function loadState() {
			$sf = $this->temp_dir . umiInstallExecutor::STATE_FILE_NAME;
			if (file_exists($sf) && $c = file_get_contents($sf)) {
				self::$state = @unserialize($c);
			}
			if (!self::$state) self::$state = array();
		}
		/**
		* Сохраняет состояние установщика в файл
		*
		*/
		private function saveState() {
			$sf = $this->temp_dir . umiInstallExecutor::STATE_FILE_NAME;
			file_put_contents($sf, serialize(self::$state));
		}

		private function getParam($name) {
			return isset($this->params[$name]) ? $this->params[$name] : null;
		}

		private function setComponentOffset($component, $offset) {
			if (!isset(self::$state['@components']) || !is_array(self::$state['@components'])) self::$state['@components'] = array();
			self::$state['@components'][$component] = (int) $offset;
			$this->saveState();
		}

		public function __construct($temp_dir, $install_mode = true, $params = array()) {
			$this->temp_dir = $temp_dir;
			$this->install_mode = $install_mode;

			if (!defined("PHP_FILES_ACCESS_MODE")) {
				$mode = $this->getConfigOption("SETUP", "php_files_access_mode", false);
				if (!$mode) {
					if (INSTALLER_CLI_MODE || !$this->install_mode) {
						$mode = substr(decoct(fileperms(__FILE__)), -4, 4);
					}
					else {
						$mode = substr(decoct(fileperms(CURRENT_WORKING_DIR."/install.php")), -4, 4);
					}
				}
				define("PHP_FILES_ACCESS_MODE", octdec($mode));
			}

			if (!self::$split_block_size) {
				self::$split_block_size = $this->getConfigOption("SETUP", "split_block_size", 100);
			}

			if(self::$state === false) {
				$this->loadState();
			}
		}

		public function __destruct() {
			//$sf = $this->temp_dir . umiInstallExecutor::STATE_FILE_NAME;
			//@file_put_contents($sf, serialize(self::$state));
		}

		/**
		* Возвращает информацию о dummy-файле
		*
		*/
		private function getDummyInfo() {
			$ht = array();
			$ht['begin'] = '########## UMI.CMS - update begin ##########';
			$ht['end'] =   '########### UMI.CMS - update end ###########';
			$ht['dummyname'] = "dummy.php";
			$ht['allow_array'] = array("install.php", "installer.php", "smu/install.php", "smu/installer.php");
			return $ht;
		}


		/**
		* Создает заглушку на время обновления
		*
		* @param mixed $fulldummyname
		*/
		private function ht_create_dummy($fulldummyname) {
			$downloader = $this->getDownloader();
			$dummy = $downloader->get_file(base64_decode("aHR0cDovL3d3dy51bWktY21zLnJ1L2luc3RhbGwvZmlsZXMvZHVtbXkuaHRtbA=="));
			file_put_contents($fulldummyname, $dummy);
		}

		/**
		* Добавляет запрещающие доступ инструкции в .htaccess на время обновления или установки.
		*
		*/
		private function setUpdateMode() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$ht = $this->getDummyInfo();

			$this->ht_create_dummy(CURRENT_WORKING_DIR.'/'.$ht['dummyname']);

			$dummy = array();
			//$dummy[] = 'ErrorDocument 503 /dummy.php';
			if ( is_array($ht['allow_array']) && 0<count($ht['allow_array']) ) {
				foreach($ht['allow_array'] as $file) {
					#$dummy[] = 'RewriteCond %{REQUEST_URI} !^/'.$file.'$';
					$dummy[] = 'RewriteCond %{REQUEST_URI} !/'.$file.'$';
					}
			}
			//$dummy[] = 'RewriteCond %{REQUEST_URI} !^/'.$ht['dummyname'].'$';
			$dummy[] = 'RewriteCond %{REQUEST_URI} !/'.$ht['dummyname'].'$';
			$dummy[] = 'RewriteRule ^.*$ /'.$ht['dummyname'].' [L]';

			$ht_array = array();
			if (file_exists(CURRENT_WORKING_DIR.'/.htaccess')) {
				$ht_array = $this->ht_get_clean_array(CURRENT_WORKING_DIR.'/.htaccess', $ht);
			}

			$result = array();
			$do_insert = true;
			if (count($ht_array)>0) {
				foreach($ht_array as $line) {
					$result[] = $line;
					if ($do_insert && preg_match('|^[ \t]*RewriteEngine|i', $line)) {
						$do_insert = false;
						$result[] = $ht['begin'];
						foreach($dummy as $d_line) {
							$result[] = $d_line;
			}
						$result[] = $ht['end'];
					}
				}
			}

			$content = implode("\r\n", $result)."\r\n";

			if ($do_insert) {
				$content .= $ht['begin']."\r\n";
				$content .= "RewriteEngine On\r\n";
				foreach($dummy as $d_line) {
					$content .= $d_line."\r\n";
				}
				$content .= $ht['end']."\r\n";
			}

			file_put_contents(CURRENT_WORKING_DIR.'/.htaccess', $content);

			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Отменяет блокирование для режима обновления
		*
		*/
		private function cleanUpdateMode() {
			$ht = $this->getDummyInfo();
			$ht_array = $this->ht_get_clean_array(CURRENT_WORKING_DIR.'/.htaccess', $ht);
			file_put_contents(CURRENT_WORKING_DIR.'/.htaccess', implode("\r\n", $ht_array)."\r\n");
			return true;
		}

		/**
		* Удаляет из htaccess блок инструкций
		*
		* @param mixed $filename
		* @param mixed $ht('start_string', 'end_string')
		*/
		private function ht_get_clean_array($filename, $ht) {
			$content = file_get_contents($filename);

			$ht_array = array();

			foreach(explode("\n", $content) as $ht_key=>$ht_line) {
				$one_line = explode(" ", trim($ht_line));
				$ht_array[] = trim($ht_line);
				}

			if ( in_array($ht['begin'], $ht_array) && in_array($ht['end'], $ht_array) ) {
				$clear = false;
				foreach($ht_array as $ht_key=>$ht_line) {
					if ($ht_line==$ht['begin']) {
						$clear = true;
						unset($ht_array[$ht_key]);
						continue;
			}
					if ($ht_line==$ht['end']) {
						unset($ht_array[$ht_key]);
						break;
					}
					if ($clear) {
						unset($ht_array[$ht_key]);
						continue;
					}
				}
			}

			return $ht_array;
		}


		public function run($step = 'install-run', $cli = true, $params = array()) {
			$this->step = $step;
			$this->cli_mode = $cli;
			$this->params = $params;
			$result = false;

			if (!$this->cli_mode // Запрос выполнен из браузера
				&& $step!="check-user" // И это не проверка прав пользователя
				&& !$this->isSV() // не sv
				) {
				if (!$this->install_mode) {
					$error = array("mess"=>"Недостаточно прав для выполнения обновлений!", "no"=>"15001");
					$step = "error";
				}
				elseif (file_exists("./installed")&&is_file("./installed")) {
					$error = array("mess"=>"Система уже установлена.", "no"=>"15002");
					$step = "error";
				}
			}

			try {
				switch($step) {
					case "error":	throw new Exception($error["mess"], $error["no"]);
					// Проверяет права пользователя на установку обновлений
					case "check-user":				$result = $this->checkUser(); break;
					// Проверяет, есть ли доступные обновления для текущей ревизии
					case "check-update":			$result = $this->checkUpdate(); break;
					// Очищает список бекапов
					case "check-installed":			$result = $this->checkInstalled(); break;

					// сохраняет переданные данные инсталляции
					case "save-settings":			$result = $this->saveSettings(); break;
					// точка входа, запускает шаги инсталляции
					case "install-run":				$result = $this->runInstaller(); break;
					// получаем инструкции для обновления с сервера
					case "get-update-instructions":	$result = $this->downloadUpdateInstructions(); break;
					// скачиваем компоненты
					case "download-components":		$result = $this->downloadComponents(); break;
					case "download-component":		$result = $this->downloadComponent(); break;
					// распаковываем компоненты
					case "extract-components":		$result = $this->extractComponents(); break;
					case "extract-component":		$result = $this->extractComponent(); break;
					// проверяем компоненты на целостность
					case "check-components": 		$result = $this->checkComponents(); break;
					case "check-component":			$result = $this->checkComponent(); break;
					// обновляем инсталлятор
					case "update-installer":		$result = $this->updateInstaller(); break;
					// обновляем структуру базы данных
					case "update-database": 		$result = $this->updateDatabaseStructure(); break;

					// конфигурируем установленную систему
					case "configure":				$result = $this->configure(); break;
					// установка деволтного значения домена из полученного пакета
					case "set-default-domain":		$result = $this->setDefaultDomain(); break;

					case 'download-service-package':	$result = $this->downloadServicePackage(); break;
					case 'extract-service-package':		$result = $this->extractServicePackage(); break;
					case 'write-initial-configuration': $result = $this->writeInitialConfiguration(); break;
					// Запускаем тесты системы
					case 'run-tests': 				$result = $this->runTests(); break;

					// устанавливаем компоненты
					case "install-components": 		$result = $this->installComponents(); break;
					case "install-component": 		$result = $this->installComponent(); break;

					//case "set-update-mode":			$result = $this->setUpdateMode(); break;
					//case "clean-update-mode":			$result = $this->cleanUpdateMode(); break;

					// демосайт
					case "download-demosite":		$result = $this->downloadDemosite(); break;
					case "extract-demosite":		$result = $this->extractDemosite(); break;
					case "install-demosite":		$result = $this->installDemosite(); break;
					case "check-demosite": 			$result = $this->checkDemosite(); break;

					// бекапирование
					case "backup-files":				$result = $this->backupFiles(
						'/backup_files.xml', // Имя xml файла для списка копирования файлов
						'/umibackup', // Директория, куда будут копироваться файлы
						array('.'), // Директория, с которой надо начинать поиск файлов
						array('php', 'ini', 'js', 'htaccess', 'xsl', 'css', 'tpl')); // Расширения файлов, которые надо бекапировать
						break;
					case "backup-file":					$result = $this->backupFile(); break;
					case "backup-mysql":					$result = $this->backupMySQL(
						$backup_file='/backup_database.xml',
						$backup_dir='/umibackup'
						);
						break;
					case "backup-mysql-table":			$result = $this->backupMySQLTable(); break;
					case "backup-mysql-table-part":	$result = $this->backupMySQLTablePart(); break;

/*					// восстановление из бекапа файлов
					case 'restore-file':					$result = $this->restoreFile(); break;

					// восстановление базы из бекапов
					case 'restore-mysql': 				$result = $this->restoreMysql(
						$backup_file='/backup_database.xml', // Имя файла со структурой восстанавливаемой базы
						$backup_dir='/umibackup' // Директория с данными для востановления
						);
						break;
					// восстановление значений таблицы, параметр - количество записей для восстановления за одну интерацию
					case 'restore-mysql-table':			$result = $this->restoreMysqlTable(50); break;
					case 'restore-mysql-table-part':		$result = $this->restoreMysqlTablePart(); break;*/

					case "cleanup":					$result = $this->cleanup(); break;
					case "clear-cache":				$result = $this->clearCache(); break;

					case 'get-demosite-list':    $result = $this->getDemositeList(); break;

					// Восстановление из бекапа
					case 'rollback':			$result = $this->rollback(); break;
					case 'clear-tables': 		$result = $this->clearTables(); break;
					case 'restore-structure':	$result = $this->restoreStructure(); break;
					case 'restore-data':		$result = $this->restoreData(); break;
					case 'restore-files':		$result = $this->restoreFiles(); break;
					case 'restore-file':		$result = $this->restoreFile(); break;

					case 'set-update-mode':		$result = $this->setUpdateMode(); break;

					default: throw new Exception('Неизвестный шаг установки "' . $step . '" для установки');
				}
			} catch (Exception $e) {
				if (!$this->cli_mode && $this->install_mode) {
					self::returnErrorXML($e);
				}
				if(!$this->getParam('guiUpdate') && ($this->cli_mode || $step !== "install-run")) {
					throw $e;
				}
				self::returnErrorXML($e);
			}
			if(!$this->cli_mode) {
				if(in_array($step, array('install-run', 'save-settings', 'download-service-package', 'extract-service-package', 'write-initial-configuration', 'run-tests', 'backup-files', 'backup-mysql', "get-update-instructions", "download-components", "extract-components", "check-components", "update-database", "install-components", "configure", "download-demosite", "extract-demosite", "check-demosite", "install-demosite", "set-default-domain", "clear-cache")) || $this->getParam('guiUpdate') ) {
					self::returnResultXML($result);
				} else {
					return $result;
				}
			}
			else {
				return 1;
			}
		}

		/**
		* Скачивает с сервера инструкции по обновлению и проверяет, доступна ли новая ревизия
		*/
		private function checkUpdate() {
			if (is_file($this->temp_dir . "/update-instructions.xml")) {
				unlink($this->temp_dir . "/update-instructions.xml");
			}

			// Сбрасываем загруженное из файла состояние обновления и удаляем файл.
			self::$state = array();
			if (is_file($this->temp_dir . umiInstallExecutor::STATE_FILE_NAME)) {
				unlink($this->temp_dir . umiInstallExecutor::STATE_FILE_NAME);
			}

			$this->downloadUpdateInstructions();

			$xml = new DOMDocument();
			$xml->load($this->temp_dir . "/update-instructions.xml");

			$xpath = new DOMXPath($xml);

			$package = $xpath->query('/package')->item(0);

			if (!$this->install_mode) {
				$this->includeMicrocore();
				$regedit = regedit::getInstance();
				$key = $regedit->getVal("//settings/keycode");
				$domain_key = $package->getAttribute('domain_key');
				if ($key!=$domain_key) {
					$regedit->setVal("//settings/keycode", $domain_key);
				}
			}

			if ($package->getAttribute('last-revision') == $package->getAttribute('client-revision')) {
				throw new Exception('Updates not avaiable.');
			}
			else {
				if (!$this->install_mode && !INSTALLER_CLI_MODE && $package->getAttribute('client-revision')>18080) {
				throw new Exception('Updates avaiable.');
			}
				else {
					$this->flushLog('Updates avaiable.');
				}
			}
			return true;
		}

		private function isSV() {
			@session_start();
			return (isset($_SESSION['user_is_sv']) && $_SESSION['user_is_sv']==true);
		}

		private function checkUser() {
			if ($this->isSV()) {
				$this->flushLog('Права на выполнение обновления подтверждены.');
			}
			else {
				throw new Exception('Недостаточно прав для выполнения обновлений!');
			}

			return true;
		}





	/** Операции по восстановлению системы */

		/**
		* Выполняет восстановление системы
		*
		*/
		private function rollback() {
			if ( $this->checkRestore() // Проверяем, что действительно необходимо восстанавливать систему
				and $this->execSubProcess('clear-tables')
				//and $this->clearTables() // Удаление таблиц в базе данных
				and $this->execSubProcess('restore-structure')
				//$this->restoreStructure() // Восстановление структуры
				and $this->execSubProcess('restore-data')
				//and $this->restoreData() // Восстановление данных в базе
				and $this->execSubProcess('restore-files')
				//and $this->restoreFiles() // Восстановление файлов
			) {
				// После успешного восстановления удаляем файл разрешения на эту операцию.
				if (file_exists('./restore')&&is_file('./restore')) {
					unlink('./restore');
				}
				if (file_exists('../restore')&&is_file('../restore')) {
					unlink('../restore');
				}
				// И удаляем файл состояния
				if (file_exists(CURRENT_WORKING_DIR.'/sys-temp/updates/'.self::STATE_FILE_NAME)) {
					unlink(CURRENT_WORKING_DIR.'/sys-temp/updates/'.self::STATE_FILE_NAME);
				}

				$this->cleanUpdateMode();

				$this->flushLog('Состояние системы восстановлено.');
				return true;
			}
			return false;
		}


		/**
		* Проверяет, можно ли восстанавливать систему.
		* Удаляет файл состояния.
		*
		*/
		private function checkRestore() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$this->flushLog("Проверка возможности восстановления...");
			// Сначала посмотрим, возможно ли восстановление вообще
			$backupdir = CURRENT_WORKING_DIR.'/umibackup';
			if (!file_exists($backupdir.'/backup_database.xml') && !file_exists($backupdir.'/backup_files.xml')) {
				throw new Exception("Восстановление системы невозможно - отсутствуют файлы бекапирования.");
			}

			// Проверяем, можно ли выполнять восстановление
			if (!$this->cli_mode && !(file_exists(CURRENT_WORKING_DIR.'/restore')&&is_file(CURRENT_WORKING_DIR.'/restore'))) {
				throw new Exception("Для запуска восстановления необходим пустой файл restore в корне сайта.");
			}

			// Удаляем файл состояния
			if (file_exists(CURRENT_WORKING_DIR.'/sys-temp/updates/.isf')) {
				unlink(CURRENT_WORKING_DIR.'/sys-temp/updates/.isf');
			}

			$this->setDone(__METHOD__);
			return true;
		}


		/**
		* Загружает указанный xml документ
		*
		* @param mixed $filename
		* @return DOMDocument
		*/
		private function loadDomDocument($filename="") {
			$dom = new DOMDocument();
			if (!$dom->load($filename)) {
				throw new Exception('Не удалось загрузить xml документ.');
			}
			return $dom;
		}



		/**
		* Сохраняет переданный объект документа по указанному пути
		*
		* @param DomDocument $doc
		* @param mixed $filename
		*/
		private function saveDomDocument(DomDocument $doc, $filename="") {
			if (!$doc->save($filename)) {
				throw new Exception('Не удалось сохранить xml документ.');
			}
			return true;
		}



		/**
		* Выполняет удаление используемых таблиц
		*
		*/
		private function clearTables() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$backupfilename = CURRENT_WORKING_DIR.'/umibackup/backup_database.xml';

			$doc = $this->loadDomDocument($backupfilename);
			$xpath = new DOMXPath($doc);

			if ($xpath->query('//table[@deleted]')->length==0) {
				$this->flushLog("Удаление таблиц:");
			}

			$not_deleted = $xpath->query('//table[not(@deleted)]');

			if ($not_deleted->length>0) {
				$this->includeMicrocore();
				$query = "SET foreign_key_checks = 0";
				l_mysql_query($query);
				foreach($not_deleted as $table) {
					$name = $table->getAttribute('name');

					// Таблица существует?
					$query = "SHOW TABLES LIKE '{$name}'";
					$res = l_mysql_query($query);

					if (mysql_numrows($res)!=0) {
						$query = "DROP TABLE `".$name."`";
						l_mysql_query($query);
						$this->flushLog("Таблица ".$name." была удалена.");
					}
					else {
						$this->flushLog("Таблица ".$name." отсутствовала.");
					}

					$table->setAttribute('deleted', 'true');

					if (!$this->cli_mode) {
						$this->saveDomDocument($doc, $backupfilename);
						return false;
					}
				}
			}

			$deleted = $xpath->query('table[@deleted]');
			foreach($deleted as $table) {
				$table->removeAttribute('deleted');
			}
			$this->saveDomDocument($doc, $backupfilename);

			$this->flushLog("Удаление завершено.");
			$this->setDone(__METHOD__);
			return true;
		}


		/**
		* Выполняет восстановление структуры базы данных
		*
		*/
		private function restoreStructure() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$backupfilename = CURRENT_WORKING_DIR.'/umibackup/backup_database.xml';
			$database = $this->loadDomDocument($backupfilename);
			$xpath = new DOMXPath($database);

			$this->includeMicrocore();
			$converter = new dbSchemeConverter($this->connection);
			$converter->setDestinationFile(str_replace('.xml', '_old.xml', $backupfilename));
			$converter->setMode('save');
			$converter->run();

			$converter->setMode('restore');
			$converter->setSourceFile($backupfilename);
			$converter->run();

			$this->flushLog("Структура базы данных восстановлена.");
			$this->setDone(__METHOD__);
			return true;
		}


		/**
		* Восстанавливает данные таблиц из файлов
		*
		*/
		private function restoreData() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}

			$backupfilename = CURRENT_WORKING_DIR.'/umibackup/backup_database.xml';
			$parse_by = 50;

			$database = $this->loadDomDocument($backupfilename);
			$xpath = new DOMXPath($database);

			if ($xpath->query("table")->length==0) {
				$this->flushLog("Нет таблиц для восстановления.");
				return true;
			}

			if ($xpath->query("table[@restored]")->length==0) {
				$this->flushLog("Восстановление данных таблиц:");
			}

			$to_restore = $xpath->query("table[not(@restored)]");

			if ($to_restore->length>0) {
				foreach($to_restore as $table) {
					$name = $table->getAttribute("name");
					$offset = $table->getAttribute("offset");
					if (!$offset) {
						$offset=0;
					}


					$src_file = CURRENT_WORKING_DIR."/umibackup/mysql/{$name}.sql";
					$count = $this->getCountMysqlValues($src_file);

					if ($offset==0) {
						$this->flushLog("Таблица {$name}, количество записей {$count}");
					}

					do {
						if ($count==0) {
							break;
						}

						$end = $offset + $parse_by - 1;
						if ($end>$count) {
							$end = $count - 1;
						}

						// Выполняем добавление данных
						$this->flushLog("Добавление записей с ".($offset+1)." по ".($end+1)."...");
						$this->insertMysqlData($src_file, $name, $offset, $end);

						$offset = $end + 1;

						if (($count>$offset) && (!$this->cli_mode)) {
							$table->setAttribute("offset", $offset);
							$this->saveDomDocument($database, $backupfilename);
							return false;
						}
					} while ($count>$offset);

					$table->removeAttribute("offset");
					$table->setAttribute("restored", "true");
					if (!$this->cli_mode) {
						$this->saveDomDocument($database, $backupfilename);
						return false;
					}
				}
			}

			if ($xpath->query("table[not(@restored)]")->length==0) {
				$to_restore = $xpath->query("table[@restored]");
				foreach($to_restore as $table) {
					$table->removeAttribute("restored");
				}
				$this->saveDomDocument($database, $backupfilename);
				$this->flushLog("Данные восстановлены.");

				$this->setDone(__METHOD__);
				return true;
			}
		}


		/**
		* Возвращает количество строк для импорта в таблицу
		*
		* @src_file string $src_file
		* @return int
		*/

		private function getCountMysqlValues($src_file) {
			if (filesize($src_file)==0) {
				return 0;
			}
			else {
				return count($this->getMysqlValues($src_file));
			}
		}


		/**
		 * Разбирает файл со значениями для mysql в массив
		 * @param String $src_file - имя файла для разбора
		 */
		private function getMysqlValues($src_file) {
			if (!file_exists($src_file)) {
				throw new Exception("Отсутствует файл с данными для восстановления {$src_file}");
			}
			$content = file_get_contents($src_file);
			$content = substr($content, strpos($content, '('));
			$data = explode("'), \n", $content);
			return $data;
		}

		/**
		* Добавляет данные в указанную таблицу
		*
		* @param mixed $src_file - файл с исходными данными
		* @param mixed $name - имя таблицы
		* @param mixed $start - начальное смещение
		* @param mixed $stop - конечное смещение
		*/
		private function insertMysqlData($src_file, $name, $start, $stop) {
			$this->includeMicrocore();

			$query = "SET foreign_key_checks = 0";
			$res = l_mysql_query($query);

			$data = array_slice($this->getMysqlValues($src_file), $start, $stop-$start+1);

			$values = trim(implode("'), ", $data));

			if (substr($values, -1, 1)!=')') {
				$values.="')";
			}

			$query = "INSERT INTO `".$name."` VALUES ".$values;
			l_mysql_query($query);

			$query = "SET foreign_key_checks = 1";
			$res = l_mysql_query($query);

			return true;
		}


		/**
		 * Выполняет восстановление файлов из бекапа
		 * @param String $backup_file - xml файл со списком файлов для восстановления
		 * @param String $backup_dir - директория, в которой находятся забекапированные файлы
		 * @param String $dest_dir - корневая директория для восстановления файлов
		 */
		private function restoreFiles() {
			if($this->checkDone(__METHOD__)) return true;

			$backup_dir = CURRENT_WORKING_DIR.'/umibackup/files';
			$backup_file = CURRENT_WORKING_DIR.'/umibackup/backup_files.xml';
			$dest_dir = CURRENT_WORKING_DIR;

			if (!file_exists($backup_dir)||!is_dir($backup_dir)) {
				throw new Exception('Директория '.$backup_dir.' не существует.');
			}
			if ( !file_exists($backup_file) ) {
				throw new Exception('Отсутствует список файлов для восстановления - '.$backup_file);
			}
			if ( !is_writable($dest_dir) ) {
				throw new Exception('Директория '.$dest_dir.' закрыта для записи. Проверьте разрешения.');
			}

			$xml = $this->loadDomDocument($backup_file);
			$xpath = new DOMXPath($xml);

			if ($xpath->query("//file[@restored]")->length==0) {
				$this->flushLog('Восстановление файлов...');
			}

			$files = $xpath->query('//file[not(@restored)]');

			if ( 0<$files->length ) {
				foreach($files as $file) {
					$this->execSubProcess('restore-file', array(
						'backup_dir'=>$backup_dir,
						'src_file'=>$file->getAttributeNode('path')->nodeValue,
						'dest_dir'=>$dest_dir
						)
					);
					$file->setAttribute('restored', 'true');
					if (!$this->cli_mode) {
						$this->saveDomDocument($xml, $backup_file);
						return false;
					}
				}
			}

			$files = $xpath->query('//file[@restored]');
			foreach($files as $file) {
				$file->removeAttribute("restored");
			}
			$this->saveDomDocument($xml, $backup_file);

			$this->flushLog('Файлы были восстановлены.');
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		 * Выполняет восстановление одного файла из backup
		 */
		private function restoreFile() {
			$backup_dir = $this->params['backup_dir'];
			$dest_dir = $this->params['dest_dir'];
			$src_file = (strpos($this->params['src_file'], './')===0)?substr($this->params['src_file'], 1):$this->params['src_file'];

			$this->flushLog($src_file.'...');

			$dest_file = $dest_dir.$src_file;
			$dest_dir = pathinfo($dest_file, PATHINFO_DIRNAME);

			if ( (!file_exists($dest_dir) || !is_dir($dest_dir)) && !mkdir($dest_dir, 0777, true)) {
				throw new Exception('Не удается создать директорию '.$dest_dir);
			}

			if (!copy($backup_dir.$src_file, $dest_file)) {
				throw new Exception('Не удается скопировать файл '.$dest_file);
			}

			return true;
		}

	/** Операции по восстановлению системы закончились */




















		private function getDemositeList() {
			header("Content-Type: text/xml; charset=utf-8");
			 echo $this->getDownloader()->getDemositesList()->saveXML();
		}

		private function saveSettings($values='') {

			if (!INSTALLER_CLI_MODE && $this->install_mode) {
				$this->checkSelf();
			}
			if (file_exists(CURRENT_WORKING_DIR . "/install.ini")) {
				$settings = parse_ini_file(CURRENT_WORKING_DIR . "/install.ini", true);
			}
			else {
				$settings = array();
			}


			if (!$this->install_mode && $values!='') { // GUI - обновление
				foreach($values as $k=>$v) {
					$settings['SUPERVISOR'][$k] = $v;
				}
			}
			else { // GUI - установка
				if (!is_null($this->getParam("demosite"))) {
					$settings['DEMOSITE']['name'] 	= $this->getParam("demosite");
				}
				elseif (!is_null($this->getParam("sv_login"))) {
					if (is_null($this->getParam("sv_password"))) {
						throw new Exception("Не указан пароль суперпользователя");
					}
					if ($this->getParam("sv_password")!=$this->getParam("sv_password2")) {
						throw new Exception("Пароли не совпадают!");
					}
					if (strlen($this->getParam("sv_login"))<2) {
						throw new Exception("Имя пользователя должно быть не менее 2-х символов");
					}

					$settings['SUPERVISOR']['login'] 	= $this->getParam("sv_login");
					$settings['SUPERVISOR']['password'] = $this->getParam("sv_password");
					$settings['SUPERVISOR']['email'] 	= $this->getParam("sv_email");

					$this->cleanUpdateMode();
				}
				else {
					$settings['LICENSE']['domain'] 	= $_SERVER['HTTP_HOST'];
					$settings['LICENSE']['ip'] = isset($_SERVER['SERVER_ADDR'])?($_SERVER['SERVER_ADDR']):str_replace("\\", "", $_SERVER['DOCUMENT_ROOT']);
					$settings['LICENSE']['key'] = $this->getParam("license_key");
					$settings['DB']['host'] 	= $this->getParam("db_host");
					$settings['DB']['user'] 	= $this->getParam("db_login");
					$settings['DB']['password'] = $this->getParam("db_password");
					$settings['DB']['dbname'] 	= $this->getParam("db_name");
					$settings['BACKUP']['mode'] 	= $this->getParam("backup_mode");
					$this->cleanup();
					if ( $settings['BACKUP']['mode']=='all' || $settings['BACKUP']['mode']=='files' ) {
						$backup_file = CURRENT_WORKING_DIR.'/umibackup/backup_files.xml';
						if (file_exists($backup_file)) unlink($backup_file);
					}
					if ( $settings['BACKUP']['mode']=='all' || $settings['BACKUP']['mode']=='base' ) {
						$backup_file = CURRENT_WORKING_DIR.'/umibackup/backup_database.xml';
						if (file_exists($backup_file)) unlink($backup_file);
					}
				}
			}


			$installSettings = "";
			foreach($settings as $groupname => $group) {
				$installSettings .= "[{$groupname}]\n";
				foreach($group as $fieldname => $field)  {
					$installSettings .= "{$fieldname} = \"" . addslashes($field) . "\"\n";
				}
			}
			if (!file_put_contents(CURRENT_WORKING_DIR . "/install.ini", $installSettings)) {
				throw new Exception('Не удается сохранить файл install.ini, проверьте права доступа.', 13049);
			}
			return true;
		}

		/**
		 * Выполняет восстановление значений таблицы
		 * @param Integer $parse_by - количество записей для восстановления за один проход
		 */
/*		private function restoreMysqlTable($parse_by=50) {
			$backup_dir = $this->params['backup_dir'];
			$table_name = $this->params['table_name'];
			$src_file = $this->params['src_file'];

			$count = count($this->getMysqlValues($backup_dir.'/'.$src_file));
			$this->flushLog($table_name.', count of strings - '.$count);
			$interations = ceil($count/$parse_by);
			for($i=1; $i<=$interations; $i++) {
				$start = ($i-1)*$parse_by;
				$length = $parse_by;
				if (($start+$length)>$count) {
					$length = $count-$start;
				}
				$this->flushLog('Offset '.$start.', count '.$length);
				$this->execSubProcess('restore-mysql-table-part', array(
					'table_name' => $table_name,
					'src_file'=>$backup_dir.'/'.$src_file,
					'start'=>$start,
					'count'=>$length
					)
				);
			}

		}*/

		/**
		 * Выполняет восстановление части значений таблицы
		 *
		 */
/*		private function restoreMysqlTablePart() {
			$table_name = $this->params['table_name'];
			$src_file = $this->params['src_file'];
			$start = $this->params['start'];
			$count = $this->params['count'];

			$data = $this->getMysqlValues($src_file);
			$strings = array_slice($data, $start, $count);
			unset($data);

			$values = implode("'), ", $strings);

			if (substr($values, -1, 1)!=')') {
				$values.="')";
			}

			$this->includeMicrocore();
			$query = "SET foreign_key_checks = 0";
			l_mysql_query($query);

			$query = "INSERT INTO `".$table_name."` VALUES ".$values;
			l_mysql_query($query);

			$query = "SET foreign_key_checks = 1";
			l_mysql_query($query);
		}*/




		/**
		 * Выполняет восстановление таблиц в базе данных
		 *	@param String $backup_file - имя файла со структурой базы данных
		 *	@param String $backup_dir - директория с данными таблиц для восстановления
		 */
/*		private function restoreMysql($backup_file='/backup_database.xml', $backup_dir='/umibackup') {
			die();
			if($this->checkDone(__METHOD__)) return true;

			$this->flushLog('Восстановление базы данных...');

			$backup_dir = CURRENT_WORKING_DIR.$backup_dir;
			if ( !file_exists($backup_dir.'/mysql') || !is_dir($backup_dir.'/mysql') ) {
				throw new Exception('Директория с файлами данных не существует.');
			}

			$backup_file = $backup_dir.$backup_file;
			if ( !file_exists($backup_file) ) {
				throw new Exception('Отсутствует файл со структурой базы данных.');
			}

			$xml = new DomDocument();
			if (!$xml) {
				throw new Exception('Не удается создать модель xml документа.');
			}
			if (!$xml->load($backup_file)) {
				throw new Exception('Не удается загрузить файл со структорой базы данных.');
			}

			$xpath = new DOMXPath($xml);
			if (!$xpath) {
				throw new Exception('Не удается создать модель поиска по структуре базы данных.');
			}

			$tables = $xpath->query('//table');
			if ($tables->length > 0) {
				$this->includeMicrocore();
				// Выключаем проверку внешних ключей, сносим таблицы
				$query = "SET foreign_key_checks = 0";
				l_mysql_query($query);

				$query = "DROP TABLE IF EXISTS ";
				$tables = $xpath->query('//table');
				for($i=$tables->length-1; $i>=0; $i--) {
					$query.='`'.$tables->item($i)->getAttributeNode('name')->nodeValue.'`';
					if ($i>0) {
						$query.=', ';
					}
				}

				l_mysql_query($query);

				$query = "SET foreign_key_checks = 1";
				l_mysql_query($query);

				$converter = new dbSchemeConverter($this->connection, $backup_file);
				$converter->restoreDataBase();

				// Выполняем восстановление данных
				$tables = $xpath->query('//table');
				if ( 0<$tables->length ) {
					for($i=$tables->length-1; $i>=0; $i--) {
						$table = $tables->item($i);
						$table_name = $table->getAttributeNode('name')->nodeValue;
						if (!file_exists($backup_dir.'/mysql/'.$table_name.'.sql')) {
							throw new Exception('Отсутствует файл с данными таблицы '.$table_name);
						}
						if (filesize($backup_dir.'/mysql/'.$table_name.'.sql')==0) {
							$this->flushLog($table_name.' - восстановлена как пустая (без значений)');
						}
						else {
							$this->execSubProcess('restore-mysql-table', array(
								'backup_dir'=>$backup_dir.'/mysql',
								'table_name'=>$table_name,
								'src_file'=>$table_name.'.sql'
								)
							);
						}
						$table->setAttribute('restored', 'true');
						$xml->save($backup_file);
						if (!$this->cli_mode) return false;
					}
				}
			}

			$this->flushLog('База данных была восстановлена.');
			$this->setDone(__METHOD__);
		}*/

		/**
		 * Выполняет бекапирование базы данных
		 * @param String $backup_file - имя файла для сохранения структуры базы данных
		 * @param String $backup_dir - имя директории для файлов с данными таблиц
		 */
		private function backupMySQL($backup_file='/backup_database.xml', $backup_dir='/umibackup') {
			if($this->checkDone(__METHOD__)) return true;

			$this->setUpdateMode();

			if ( (INSTALLER_CLI_MODE && !$this->getBackupMode('base')) // Консольный запуск, и базу бекапировать не нужно
				|| (!INSTALLER_CLI_MODE && $this->install_mode && !$this->getBackupMode('base'))) {
					return true;
				}

			$backup_dir = CURRENT_WORKING_DIR.$backup_dir;
			$backup_file = $backup_dir.$backup_file;

			if ( (!file_exists($backup_dir.'/mysql')||!is_dir($backup_dir.'/mysql'))
				 && !mkdir($backup_dir.'/mysql', 0777, true) ) {
				throw new Exception('Не удается создать директорию для сохранения данных таблиц.');
			}

			if (!$this->closeByHtaccess($backup_dir)) {
				throw new Exception('Не удается закрыть директорию данных базы для доступа!');
			}

			$this->includeMicrocore();
			$this->flushLog("Бэкапирование базы...");

			if (file_exists($backup_file) ) {
				unlink($backup_file);
			}

			// Бекапирование структуры
			$converter = new dbSchemeConverter($this->connection);
			$converter->setMode('save');
			$converter->setDestinationFile($backup_file);
			$converter->run();

			// Список таблиц для выборки значений
			$xml = new DomDocument();
			$xml->formatOutput = true;
			$xml->load($backup_file);
			$xpath = new DOMXPath($xml);
			$tables = $xpath->query('//table');
			foreach($tables as $table) {
				$backuped = $xml->createAttribute('backuped');
				$table->appendChild($backuped);
				$backuped->appendChild($xml->createTextNode('false'));
			}
			$xml->save($backup_file);

			if (!isset($xml)) {
				$xml = new DomDocument();
				$xml->formatOutput = true;
				$xml->load($backup_file);
				if (!$xml) {
					throw new Exception('Не удается загрузить xml документ.');
				}
				$xpath = new DOMXPath($xml);
			}
			// Собираем список таблиц для блокировки
			$tables = $xpath->query('//table');
			if ( 0<$tables->length ) {
				foreach($tables as $table) {
					$table_name[] = $table->getAttributeNode('name')->nodeValue;
				}
				$query = "LOCK TABLES `".implode("` READ, `", $table_name)."` READ";
				l_mysql_query($query);

				$tables = $xpath->query('//table[@backuped="false"]');

				if ( 0<$tables->length ) {
					foreach($tables as $table) {
						$this->execSubProcess('backup-mysql-table', array(
							'table_name'=>$table->getAttributeNode('name')->nodeValue,
							'backup_dir'=>$backup_dir.'/mysql'
							)
						);
						$table->setAttribute('backuped', 'true');
						$xml->save($backup_file);
					}
				}

				$query = "UNLOCK TABLES";
				l_mysql_query($query);

				$this->flushLog("База данных была сохранена.");
			}
			else {
				$this->flushLog("В базе данных отсутствуют таблицы для сохранения.");
			}
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		 * Выполняет бекапирование данных из таблицы в файл
		 * @param Integer $parse_by - количество записей для выборки за одну интерацию
		 */
		private function backupMySQLTable($parse_by=50) {
			$table_name = $this->params['table_name'];
			$backup_dir = $this->params['backup_dir'];
			$offset = isset($this->params['offset'])?$this->params['offset']:0;

			$file_name = $backup_dir.'/'.$table_name.'.sql';

			$this->includeMicrocore();

			if (file_exists($file_name) ) {
				unlink($file_name);
			}

			$this->flushLog("Сохранение таблицы ".$table_name."...");
			touch($file_name);

			// Получаем количество записей в таблице
			$count = mysql_result(l_mysql_query("SELECT COUNT(*) FROM `".$table_name."`"), 0);

			if ($count!=0) {
				$interations = ceil($count/$parse_by);
				for($i=1; $i<=$interations; $i++) {
					if ($i==1) {
						file_put_contents($file_name, "INSERT INTO `".$table_name."` VALUES \n");
					}
					$this->execSubProcess('backup-mysql-table-part', array(
						'table_name'=>$table_name,
						'backup_dir'=>$backup_dir,
						'start'=>(($i-1)*$parse_by),
						'parse_by'=>$parse_by
						)
					);
					if ($i!=$interations) {
						file_put_contents($file_name, ", \n", FILE_APPEND);
					}
				}
			}
			$this->flushLog("завершено.");
		}

		/**
		 * Выполняет переданный запрос на частичную выборку и сохраняет её в файл
		 *
		 */
		private function backupMySQLTablePart() {
			$table_name = $this->params['table_name'];
			$backup_dir = $this->params['backup_dir'];
			$start = $this->params['start'];
			$parse_by = $this->params['parse_by'];
			$query = "SELECT * FROM `".$table_name."` LIMIT ".$start.", ".$parse_by;

			$file_name = $backup_dir.'/'.$table_name.'.sql';

			$this->includeMicrocore();

			$strings = array();
			$res = l_mysql_query($query);
			while($row = mysql_fetch_row($res)) {
				foreach($row as $k=>$v) {
					$row[$k] = addslashes($v);
				}
				$strings[] = "('".implode("', '", $row)."')";
			}
			file_put_contents($file_name, implode(", \n", $strings), FILE_APPEND);
		}


		private function closeByHtaccess($dir) {
			if (is_file($dir.'/.htaccess') || file_put_contents($dir.'/.htaccess', 'Deny from all')) {
				return true;
			}
			return false;
		}

		/**
		* Получает тип бекапирования данных из install.ini
		*/
		private function getBackupMode($mode) {
			$backup_mode = $this->getConfigOption('BACKUP', 'mode', null, "В install.ini не указан тип бекапирования.", 13060);
			if (!in_array($backup_mode, array('all', 'files', 'base', 'none'))) {
				throw new Exception('Ошибка: install.ini, секция BACKUP, значение не в списке разрешенных (all, none).', 13060);
			}
			if ($backup_mode=='all' || $backup_mode==$mode) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Создает список файлов для бекапирования в backup_files.xml
		 * Выполняет создание процессов добавления файлов в архив
		 * @param String $xml_file - имя файла для сохранения списка
		 * @param String $backup_dir - директория относительно рабочей, в которую будут копироваться файлы
		 * @param Array $dir - массив директорий, в которых будут собираться файлы для бекапирования
		 * @param Array $exts - массив разрешенных расширений файлов для бекапирования
		 */
		private function backupFiles($xml_file='/backup_files.xml', $backup_dir='/umibackup', $dirs=array('.'), $exts=array('php', 'ini', 'js', 'htaccess', 'xsl', 'css', 'tpl')){
			if($this->checkDone(__METHOD__)) return true;
			$this->setUpdateMode();

			if ( (INSTALLER_CLI_MODE && !$this->getBackupMode('files')) // Консольный запуск, и файлы бекапировать не нужно
				|| (!INSTALLER_CLI_MODE && $this->install_mode && !$this->getBackupMode('files'))) {
					return true;
				}

			$this->flushLog('Сохранение файлов...');

			$backup_dir = CURRENT_WORKING_DIR.$backup_dir;

			$xml_file = $backup_dir.$xml_file;

			if ( (!file_exists($backup_dir.'/files') || !is_dir($backup_dir.'/files')) && !mkdir($backup_dir.'/files', 0777, true) ) {
				throw new Exception('Не удается создать директорию для сохранения файлов!');
			}

			if (!$this->closeByHtaccess($backup_dir)) {
				throw new Exception('Не удается закрыть директорию с резервной копией для доступа!');
			}

			if (INSTALLER_CLI_MODE && file_exists($xml_file)) {
				unlink($xml_file);
			}

			if (!file_exists($xml_file)) {
				$listing = array();
				foreach($dirs as $dir) {
					$this->getAllFiles($dir, $listing, true);
				}
				$xml = new DOMDocument('1.0', 'utf-8');
				if (!$xml) {
					throw new Exception('Не удается создать модель xml документа.');
					}
					$xml->formatOutput = true;

				$backup = $xml->createElement('backup');
				$xml->appendChild($backup);

				$files = $xml->createElement('files');
				$backup->appendChild($files);

				foreach($listing as $one) {
					$ext = strtolower(pathinfo($one, PATHINFO_EXTENSION));
					if ( !in_array($ext, $exts) ) {
						continue;
					}

					$file = $xml->createElement('file');
					$files->appendChild($file);

//					$path = $xml->createAttribute('path');
//					$file->appendChild($path);

					$path = $xml->createAttribute('path');
					$file->appendChild($path);
					$path->appendChild($xml->createTextNode(htmlentities($one)));

//					$path3 = $xml->createAttribute('path3');
//					$file->appendChild($path3);
//					$path3->appendChild($xml->createTextNode(html_entity_decode($file->getAttribute("path2"))));

//					$exists = $xml->createAttribute('exists');
//					$file->appendChild($exists);
//					$exists->appendChild($xml->createTextNode(file_exists($file->getAttribute("path3"))));

//					$path->appendChild($xml->createTextNode($one));

					$copied = $xml->createAttribute('copied');
					$file->appendChild($copied);

					$copied->appendChild($xml->createTextNode('false'));
				}
				if (!$xml->save($xml_file)) {
					throw new Exception('Не удается сохранить xml документ.');
				}
			}

			if (!isset($xml)) {
				$xml = new DOMDocument();
				if (!$xml) {
					throw new Exception('Не удается создать модель xml документа.');
				}
				if (!$xml->load($xml_file)) {
					throw new Exception('Не удается загрузить xml файл.');
				}
			}

			$xpath = new DOMXPath($xml);
			$files = $xpath->query('//file[@copied="false"]');

			if (!INSTALLER_CLI_MODE && $files->length>50) {
				$i = 50;
			}
			else {
				$i = $files->length+1;
			}

			foreach($files as $file) {
				if (0==$i--) break;
				$this->execSubProcess('backup-file', array(
					'src'=>html_entity_decode($file->getAttributeNode('path')->nodeValue),
					'backup_dir'=>$backup_dir.'/files'
					)
				);
				$file->setAttribute('copied', 'true');
				$xml->save($xml_file);
			}

			if ($i==-1) return false;

			$this->setDone(__METHOD__);
			$this->flushLog('завершено.');
		}

		/**
		 * Читает список файлов в директории. При необходимости - рекурсивно.
		 * @param string $dirs - директория, в которой необходимо прочитать файлы
		 * @param array() $files - ссылка на массив файлов
		 * @param boolean $rec - выполнять рекурсивно
		 */
		private function getAllFiles($dir, &$files, $rec=false) {
			$handler = opendir(CURRENT_WORKING_DIR.'/'.$dir);
			while ( false!==($filename = readdir($handler)) ) {
				if ($filename == "." || $filename == ".." || $filename == 'umibackup' || $filename == 'sys-temp') {
					continue;
				}
				if ($rec && is_dir($dir.'/'.$filename)) {
					$this->getAllFiles($dir.'/'.$filename, $files, true);
				}
				else {
					$files[] = $dir.'/'.$filename;
				}
			}
			return;
		}

		/**
		 * Копирует иcходный файл в backup директорию
		 */
		private function backupFile() {
			$src_file = htmlentities($this->params['src']);
			$backup_dir = $this->params['backup_dir'];

			$src_info = pathinfo($src_file);
			$src_info['dirname'] = realpath($src_info['dirname']);

			$dest_file = str_replace('./', $backup_dir.'/', $src_file);
			$dest_info = pathinfo($dest_file);

			if ( (!file_exists($dest_info['dirname']) || !is_dir($dest_info['dirname']))
				 && !mkdir($dest_info['dirname'], 0777, true) ) {
				throw new Exception('Не удается создать директорию для бэкапирования '.$dest_dir);
			}

			$this->flushLog('Копирование: '.$src_info['dirname'].'/'.htmlentities($src_info['basename']).'...');
			if ( !copy($src_info['dirname'].'/'.html_entity_decode($src_info['basename']), $dest_info['dirname'].'/'.html_entity_decode($dest_info['basename'])) ) {
				throw new Exception('Не удается скопировать файл '.$src_info['dirname'].'/'.$src_info['basename']);
			}
			$this->flushLog("завершено");
		}


		public static function returnResultXML($done) {
			$document = new DOMDocument("1.0", "utf-8");
			$root = $document->createElement("result");
			$document->appendChild($root);

			$install = $document->createElement("install");
			$state = $document->createAttribute("state");
			$state->value = $done ? "done" : "inprogress";
			$install->appendChild($state);
			$root->appendChild($install);
			$root->appendChild( self::getLogXML($document) );
			header("Content-Type: text/xml; charset=utf-8");
			echo $document->saveXML();
			die();
		}

		public static function returnErrorXML(Exception $e) {
			$document = new DOMDocument("1.0", "utf-8");
			$root = $document->createElement("result");
			$document->appendChild($root);

			$error = $document->createElement("error");
			$message = $document->createAttribute("message");
			$message->value = $e->getMessage();
			$error->appendChild($message);
			$error->appendChild( self::getBacktraceXML($document, $e->getTrace()) );
			$root->appendChild($error);
			$root->appendChild( self::getLogXML($document) );
			header("Content-Type: text/xml; charset=utf-8");
			echo $document->saveXML();
			die();
		}

		private static function getBacktraceXML(DOMDocument $document, $trace) {
			$backtrace = $document->createElement("backtrace");
			foreach($trace as $callInfo) {
				$call = $document->createElement("call");
				$arguments = "";
				$all = array();
				foreach($callInfo["args"] as $arg) {
					switch(gettype($arg)) {
						case "string" : $all[] = "\"{$arg}\""; break;
						case "boolean": $all[] = $arg ? "true" : "false"; break;
						case "array"  : $all[] = "array"; break;
						case "object" : $all[] = get_class($arg); break;
						default : $all[] = (string) $arg;
					}
					$arguments = implode(", ", $all);
				}
				$callString = $callInfo["class"] .
								$callInfo["type"] .
								$callInfo["function"] .
								"({$arguments})";
				$cdata = $document->createCDATASection($callString);
				$call->appendChild($cdata);
				$backtrace->appendChild($call);
			}
			return $backtrace;
		}

		private static function getLogXML(DOMDocument $document) {
			$log = $document->createElement("log");
			foreach(self::$log as $messageText) {
				$message = $document->createElement("message", $messageText);
				$log->appendChild($message);
			}
			return $log;
		}

		/**
		* Создает и инициализирует umiUpdateDownloader
		* @return umiUpdateDownloader
		*/
		private function getDownloader() {
			if ($this->install_mode) { // Режим установки
				$key = $this->getConfigOption('LICENSE', 'key', null, "В install.ini не указан лицензионный ключ.");
				$host = $this->getConfigOption('LICENSE', 'domain', null, "В install.ini не указано имя домена для установки.");
				$ip = $this->getConfigOption('LICENSE', 'ip', "В install.ini не указан ip адрес сервера.");
				$current_revision = 'last';
			}
			else { // Режим обновления, в нем мы всегда в папке smu
				$this->includeMicrocore();

				if (INSTALLER_CLI_MODE) {
					$ip = $this->getConfigOption('LICENSE', 'ip', "В install.ini не указан ip адрес сервера.");
				}
				else {
					$ip = $_SERVER['SERVER_ADDR'];
				}


				$regedit = regedit::getInstance();
				$key = $regedit->getVal("//settings/keycode");
				$current_revision = $regedit->getVal("//modules/autoupdate/system_build");

				$host = domainsCollection::getInstance()->getDefaultDomain()->getHost();
			}

			$downloader = new umiUpdateDownloader($this->temp_dir, $key, $host, $ip, $current_revision);
			return $downloader;
		}

		/**
		* Скачивает сервисный пакет
		*/
		private function downloadServicePackage() {
			if($this->checkDone(__METHOD__)) return true;
			$this->flushLog("Загрузка сервисного компонента...");

			$downloader = $this->getDownloader();
			$downloader->downloadServiceComponent("installer");
			$this->flushLog("Сервисный компонент загружен.");
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Распаковывает сервисный пакет
		*/
		private function extractServicePackage() {
			if($this->checkDone(__METHOD__)) return true;
			$result = $this->execSubProcess('extract-component', array('component' => 'installer.service'));
			$this->setDone(__METHOD__);
			return $result;
		}

		/**
		* Записывает начальную конфигурацию
		*/
		private function writeInitialConfiguration() {
			if($this->checkDone(__METHOD__)) return true;

			$core = $this->temp_dir . "/installer.service/umicms-microcore.php";
			if(!file_exists($core)) {
				throw new Exception("В сервисном пакете отсутствует microcore.php!");
			}
			if (!is_dir($this->temp_dir."/core/smu") && !mkdir($this->temp_dir."/core/smu", 0777,true)) {
				throw new Exception("Не удается создать временную директорию для ядра!");
			}
			if (!copy($core, $this->temp_dir."/core/smu/umicms-microcore.php")) {
				throw new Exception("Не удается скопировать ядро!");
			}
			else {
				chmod($this->temp_dir."/core/smu/umicms-microcore.php", PHP_FILES_ACCESS_MODE);
			}

			$configSource = $this->temp_dir . "/installer.service/config.ini.original";
			if(!file_exists($configSource)) {
				throw new Exception("В сервисном пакете отсутствует файл с примером конфигурации системы.");
			}

			if ($this->install_mode || (!file_exists("config.ini")||!is_file("config.ini"))) {
				// В режиме установки просто записывам config.ini
				$port = "";
			$host = $this->getConfigOption('DB', 'host', "localhost");
				if (strpos($host, ':')!==false) {
					list($host, $port) = explode(':', $host);
					$host = trim($host);
					$port = trim($port);
					$port = ($port==='')?($port):((int)$port);
				}
			$login = $this->getConfigOption('DB', 'user', "root");
			$password = $this->getConfigOption('DB', 'password', "");
			$dbname = $this->getConfigOption('DB', 'dbname', null, "В install.ini не указано имя базы данных.");

			$config = file_get_contents($configSource);
			$config = str_replace("%db-core-host%", $host, $config);
				$config = str_replace("%db-core-port%", $port, $config);
			$config = str_replace("%db-core-login%", $login, $config);
			$config = str_replace("%db-core-password%", $password, $config);
			$config = str_replace("%db-core-name%", $dbname, $config);
			}
			else {
				// В режиме обновления - обновляем, соответственно
				$new_config = parse_ini_file($configSource, true);
				$old_config = parse_ini_file("config.ini", true);

				foreach($new_config as $section=>$s_values) {
					foreach($s_values as $s_value=>$p_value) {
						if (is_array($p_value)) {
							foreach($p_value as $val) {
								if ( !isset($old_config[$section][$s_value]) || !in_array($val, $old_config[$section][$s_value]) ) {
									$old_config[$section][$s_value][]="{$val}";
								}
							}
						}
						else {
							if (!isset($old_config[$section][$s_value])) {
								$old_config[$section][$s_value] = "{$new_config[$section][$s_value]}";
							}
						}
					}
				}

				// Формируем контент результата
				$config = "";
				ksort($old_config);
				foreach($old_config as $section=>$s_values) {
					ksort($old_config[$section]);
					$config .= "[{$section}]\n";
					foreach($s_values as $s_value=>$p_value) {
						if (is_array($p_value)) {
							sort($old_config[$section][$s_value]);
							foreach($p_value as $val) {
								$config .= "{$s_value}[] = \"{$val}\"\n";
							}
						}
						else {
							$config .= "{$s_value} = \"{$p_value}\"\n";
						}
					}
				$config .= "\n";
				}
			}

			file_put_contents("config.ini", $config);
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		// Записывает корневой htaccess в режиме установки
		private function writeHtaccess() {
			$htaccess = $this->temp_dir.'/installer.service/.htaccess.original';
			$new_htaccess = './.htaccess';
				$begin = '####################### UMI_CMS_HTACCESS_BEGIN ###########################';
				$end  =  '######################## UMI_CMS_HTACCESS_END ############################';

			if (!file_exists($htaccess)) {
				throw new Exception("В сервисном пакете отсутствует корневой файл настроек .htaccess");
			}

			if (!file_exists($new_htaccess)) {
				if (!file_put_contents($new_htaccess, $begin."\r\n".file_get_contents($htaccess)."\r\n".$end)) {
					throw new Exception("Не удается записать .htaccess, проверьте разрешения!");
				}
			}
			else {
				$old_ht = file_get_contents($new_htaccess);
					if ( false!==($b=stripos($old_ht, $begin)) && false!==($e=stripos($old_ht, $end)) ) {
						$old = substr($old_ht, 0, $b).substr($old_ht, $e+strlen($end));
					}
					else {
						$old = $old_ht;
					}
					copy('./.htaccess', './.htaccess_old');
					if (!file_put_contents('./.htaccess', $old."\r\n".$begin."\r\n".file_get_contents($htaccess)."\r\n".$end)) {
					throw new Exception("Не удается записать .htaccess, проверьте разрешения!");
					}
			}

		}

		/**
		* Выполняет тесты совместимости
		*/
		private function runTests() {
			if($this->checkDone(__METHOD__)) return true;
			$this->flushLog("Проверка системных требований...");
			$tests = $this->temp_dir . "/installer.service/testhost.php";
			if (!is_file($tests)) {
				throw new Exception('Не удается найти запрошенный файл: ' . $tests);
			}
			include $tests;

			if ($this->getParam('guiUpdate')  && is_file('config.ini')) {
				$ini = parse_ini_file('config.ini');
				$host = $ini['core.host'];
				if (trim($ini['core.port'])!=='') $host .= ':' . (string)(int)trim($ini['core.port']);
				$login = $ini['core.login'];
				$password = $ini['core.password'];
				$dbname = $ini['core.dbname'];
			}
			else {
				$host = $this->getConfigOption('DB', 'host', "localhost");
				$login = $this->getConfigOption('DB', 'user', "root");
				$password = $this->getConfigOption('DB', 'password', "");
				$dbname = $this->getConfigOption('DB', 'dbname', null, "В install.ini не указано имя базы данных.");
			}

			$tests = new testHost();
			$tests->setConnect($host, $login, $password, $dbname);
			$tests->getResults();

			if ( 0!=count($tests->listErrors) ) {
				$critical_errors = false;
				foreach($tests->listErrors as $key => $value) {
					if($value[1] == 1) {
						throw new Exception(' Cервер не соответствует системным требованиям для установки UMI.CMS. Подробное описание ошибки и способы её устранения доступны по ссылке <a href="http://errors.umi-cms.ru/'.$value[0].'/" target="_blank">http://errors.umi-cms.ru/'.$value[0].'/</a>');
					}
					else {
						$this->flushLog("Ошибка #".$value[0]." Сервер не соответствует системным требованиям для установки UMI.CMS. Подробная информация по ссылке http://errors.umi-cms.ru/".$value[0]."/");
					}
				}
			}
			$this->flushLog("завершено.");
			$this->setDone(__METHOD__);
			return true;
		}


		/**
		* Скачивает changelog и инструкции для установки/обновления с сервера
		*/
		private function downloadUpdateInstructions() {
			if($this->checkDone(__METHOD__)) return true;
			$this->flushLog("Загрузка инструкций по обновлению...");
			if (is_file($this->temp_dir . "/update-instructions.xml")) {
				unlink($this->temp_dir . "/update-instructions.xml");
			}

			$downloader = $this->getDownloader();
			$downloader->downloadUpdateInstructions();
			$this->flushLog("Инструкции загружены.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Скачаивает все доступные компоненты
		*/
		private function downloadComponents() {
			if($this->checkDone(__METHOD__)) return true;

			$this->setUpdateMode();

			$instructions = $this->temp_dir . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
			$xpath = new DOMXPath($doc);

			$package_size = (int) $this->getConfigOption("SETUP", "download_by");
			if ($package_size<=0) {
				$package_size = 256;
			}

			$package_size*=1024;

			$components = $xpath->query("//package/component[not(@downloaded)]");
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$filesize = $component->getAttribute('filesize');
				$offset = $component->getAttribute('offset');

				$fname = $this->temp_dir . $name . ".tar";

				if (!$offset) {
					$offset = 0;
					$this->flushLog("Загрузка компонента {$name}");
					// В случае перезагрузки пакетов избегаем склеивания
					file_put_contents($fname, '');
				}

				do {
					$start = $offset;
					$end = $start + $package_size;
					if ($end>$filesize) {
						$end = $filesize;
					}
					$result = $this->execSubProcess('download-component', array('component' => $name, 'fname'=>$fname, 'start' => $start, 'end' => $end-1));

					$offset = $end;

					if (!$this->cli_mode && $end<$filesize) {
						$component->setAttribute('offset', $offset);
						$doc->save($instructions);
						return false;
					}
				} while ($end<$filesize);

				$component->removeAttribute("offset");
				$component->setAttribute("downloaded", true);
				$doc->save($instructions);

				if (!$this->cli_mode) {
					return false;
				}
			}
			$this->flushLog("Все компоненты загружены.");
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Скачивает компонент с сервера
		*/
		private function downloadComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';
			$fname = isset($this->params['fname']) ? trim($this->params['fname']) : '';
			$start = isset($this->params['start']) ? trim($this->params['start']) : false;
			$end = isset($this->params['end']) ? trim($this->params['end']) : false;
			if (!strlen($name)) {
				throw new Exception("Отсутствует имя компонента (пример: installer.php  --component=core).");
			}
			if (!strlen($fname)) {
				throw new Exception("Отсутствует имя файла компонента (пример: installer.php  --fname=core.tar).");
			}

			$downloader = $this->getDownloader();

			if ($start!==false && $end!==false) {
				$this->flushLog("с ".$start.' по '.$end.'...');
				$content = $downloader->query('get-component', array('component' => $name), $start, $end);
			}
			else {
				$this->flushLog("Загрузка компонента \"{$name}\"...");
				$content = $downloader->query('get-component', array('component' => $name));
			}

			file_put_contents($fname, $content, FILE_APPEND);
			return true;
		}

		/**
		* Распаковывает скачанные ранее компоненты
		*/
		private function extractComponents() {
			if($this->checkDone(__METHOD__)) return true;
			$instructions = $this->temp_dir . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query("//package/component[not(@extracted)]");
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$result = $this->execSubProcess('extract-component', array('component' => $name));
				if($result) {
					$component->setAttribute('extracted', true);
					$doc->save($instructions);
				}
				if(!$this->cli_mode) return false;
			}
			$this->flushLog("Все компоненты были распакованы.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Распаковывает указанный компонент
		*/
		private function extractComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';
			if (!strlen($name)) {
				throw new Exception("Отсутствует имя компонента (пример: installer.php  --component=core).");
			}

			$this->flushLog("Распаковка компонента \"{$name}\"...");

			$cwd = getcwd();
			$extract_dir = $this->temp_dir . "/" . $name;
			if (!is_dir($extract_dir)) {
				mkdir($extract_dir);
			}

			chdir($extract_dir);
			$extracter = new umiTarExtracter("../" . $name . ".tar");
			$extracter->extractFiles();
			chdir($cwd);

			unlink($this->temp_dir.$name.".tar");

			$this->flushLog("Компонент \"{$name}\" был распакован.");
			return true;
		}

		/**
		* Проверяет распакованные компоненты на целостность
		*/
		private function checkComponents() {
			if($this->checkDone(__METHOD__)) return true;
			$instructions = $this->temp_dir . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query("//package/component[not(@checked)]");
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$result = $this->execSubProcess('check-component', array('component' => $name));
				if($result) {
					$component->setAttribute('checked', true);
					$doc->save($instructions);
				}
				if(!$this->cli_mode) return false;
			}
			$this->flushLog("Все компоненты были проверены.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Скачивает демосайт с сервера
		*/
		private function downloadDemosite() {
			if (!$this->install_mode) {
				$this->setDone(__METHOD__);
				return true;
			}
			if($this->checkDone(__METHOD__)) return true;

			$name = $this->getConfigOption("DEMOSITE", "name", "_blank");
			if($name == "_blank") return true;

			$this->flushLog("Загрузка демосайта \"{$name}\"...");

			$downloader = $this->getDownloader();
			$downloader->downloadDemosite($name);
			$this->flushLog("Демосайт \"{$name}\" был загружен.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Распаковывает демосайт
		*/
		private function extractDemosite() {
			if (!$this->install_mode) {
				$this->setDone(__METHOD__);
				return true;
			}
			if($this->checkDone(__METHOD__)) return true;

			$name = $this->getConfigOption("DEMOSITE", "name", "_blank");
			if($name == "_blank") return true;

			$result = $this->execSubProcess('extract-component', array('component' => $name));
			$this->setDone(__METHOD__, $result);
			return $result && $this->cli_mode;
		}

		/**
		* Устанавливает демосайт
		*/
		private function installDemosite() {
			if (!$this->install_mode) {
				$this->setDone(__METHOD__);
				return true;
			}
			if($this->checkDone(__METHOD__)) return true;

			$name = $this->getConfigOption("DEMOSITE", "name", "_blank");
			if($name == "_blank") return true;

			do {
				$offset = $this->getComponentOffset($name);
				if ($offset==0) {
					$this->flushLog("Установка демосайта {$name}...");
				}
				$result = $this->execSubProcess('install-component', array('component' => $name, 'type' => 'demosite'));

				// Перезагружаем состояние
				$this->loadState();
				$new_offset = $this->getComponentOffset($name);

				if ($new_offset!=$offset && !$this->cli_mode) {
					return false;
				}
			} while ($offset!=$new_offset);

			$this->flushLog("Демосайт {$name} установлен.");

			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Проверяет демосайт на целостность
		*/
		private function checkDemosite() {
			if (!$this->install_mode) {
				$this->setDone(__METHOD__);
				return true;
			}
			if($this->checkDone(__METHOD__)) return true;

			$name = $this->getConfigOption("DEMOSITE", "name", "_blank");
			if($name == "_blank") return true;

			$result = $this->execSubProcess('check-component', array('component' => $name));
			$this->setDone(__METHOD__, $result);
			return $result && $this->cli_mode;
		}

		/**
		* Обновляет инсталлятор
		*/
		private function updateInstaller() {
			if($this->checkDone(__METHOD__)) return true;
			$this->flushLog("Обновление инсталлятора...");
			if (defined('INSTALLER_DEBUG') && INSTALLER_DEBUG) {
				$this->flushLog("Not updated (debug mode).");
				$this->setDone(__METHOD__);
				return true;
			}
			$installer = $this->temp_dir . "/core/smu/installer.php";
			if (!is_file($installer)) {
				throw new Exception("Инсталлятор не найден в пакете: " . $installer);
			}
			if (!copy($installer, __FILE__)) {
				throw new Exception("Не удалось обновить инсталлятор, возможно файл " . __FILE__ . " не доступен для записи." . $installer);
			}
			else {
				chmod(__FILE__, PHP_FILES_ACCESS_MODE);
			}

			$this->flushLog("Инсталлятор был обновлен.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Проверяет указанный компонент на целостность,
		* проверяет возможность перезаписать файлы компонента у клиента
		*/
		private function checkComponent() {
			$name = isset($this->params['component']) ? trim($this->params['component']) : '';
			if (!strlen($name)) {
				throw new Exception("Не передано имя компонента для проверки (пример: installer.php  --component=core).");
			}

			$this->flushLog("Проверка компонента \"$name\"...");
			$config = $this->temp_dir . "/" . $name . "/{$name}.xml";

			if (!is_file($config)) {
				throw new Exception("Не удается загрузить конфигурацию компонента: " . $config);
			}

			$r = new DomDocument();
			$r->load($config);

			$xpath = new DOMXPath($r);

			$notwritable = array();

			$dirs = $xpath->query('//directory');
			if ($dirs->length>0) {
				foreach($dirs as $dir) {
					$dir_path = $dir->getAttribute('path');
					if (is_dir($dir_path) && !is_writable($dir_path)) {
						$notwritable[] = $dir_path;
					}
				}
			}
			if (!$this->install_mode) {
				$files = $xpath->query('//file[not(@only_install)]');
			}
			else {
				$files = $xpath->query('//file');
			}

			if ($files->length>0) {
				foreach($files as $file) {
					$file_path = $file->getAttribute('path');
					if (is_file($file_path) && !is_writable($file_path)) {
						$notwritable[] = $file_path;
					}
					// packet
					$file_path = $this->temp_dir . "/" . $name . "/" . $file->textContent;
					$file_hash = $file->getAttribute('hash');

					if (!is_file($file_path)) {
						throw new Exception("Файл \"{$file_path}\" не существует");
					}
					if ($file_hash != md5_file($file_path)) {
						throw new Exception("Файл \"{$file_path}\" загружен неверно (контрольная сумма: {$file_hash})");
					}
				}
			}

			if (count($notwritable)) {
				throw new Exception("Невозможно обновить систему, пока следующие файлы и директории недоступны на запись:<br/>\n" . implode("<br/>\n", $notwritable));
			}

			$this->flushLog("Компонент \"{$name}\" был проверен.");
			return true;
		}

		/**
		* Подключает ядро для обновления
		*/
		private function includeMicrocore() {
			$core = $this->temp_dir . "core/smu/umicms-microcore.php";

			if (!is_file($core)) {
				throw new Exception('Не найдено microcore для обновления: ' . $core);
			}
			include_once $core;
			ini_set('display_errors', 0);
			error_reporting(0);

			$this->connection = ConnectionPool::getInstance()->getConnection();
		}

		/**
		* В режиме обновления сохраняет данные супервайзера
		*
		*/
		private function saveSVInfo() {
			if($this->checkDone(__METHOD__)) return true;
			$this->flushLog('Сохранение данных супервайзера...');
			$sv = umiObjectsCollection::getInstance()->getObjectByGUID('system-supervisor');
			if (!$sv) {
				$sv = umiObjectsCollection::getInstance()->getObject(14);
			}
			$_SV['login'] = $sv->login;
			$_SV['md5pass'] = $sv->password;
			$_SV['email'] = $sv->getValue("e-mail");
			$_SV['fname'] = $sv->fname;
			$_SV['lname'] = $sv->lname;
			$_SV['mname'] = $sv->father_name;
			$this->saveSettings($_SV);

			$this->flushLog('завершено.');
			$this->setDone(__METHOD__);
			return false;
		}

		/**
		* Удаляет таблицы, которые используются для UMI.CMS
		*/
		private function dropTables($path) {
			if($this->checkDone(__METHOD__)) return true;

			$xml = new DOMDocument();
			$xml->load($path);

			$xpath = new DOMXPath($xml);
			$tables = $xpath->query("//table[@drop]");

			if ($tables->length==0) {
				$this->flushLog('Удаление таблиц в базе данных...');
			}

			$tables = $xpath->query("//table[not(@drop)]");
			if ($tables->length==0) {
				// Удалить аттрибут, поставить статус завершено, вернуть false
				$tables = $xpath->query("//table");
				foreach($tables as $table) {
					$table->removeAttribute('drop');
				}
				$xml->save($path);
				$this->flushLog('завершено');
				$this->setDone(__METHOD__);
				return false;
			}

			// Выключаем проверку внешних ключей
			$query = "SET foreign_key_checks = 0";
			l_mysql_query($query);
			foreach($tables as $table) {
				l_mysql_query("DROP TABLE IF EXISTS `".$table->getAttribute('name')."`");
				$this->flushLog("Очистка таблицы ".$table->getAttribute('name'));
				$table->setAttribute('drop', 1);
				$xml->save($path);
				return false;
			}
		}

		/**
		* Сохраняет структуру базы данных в xml файл
		*
		* @param string $path - путь к файлу
		*/
		private function saveDatabaseStructure($path) {
			if ($this->checkDone(__METHOD__)) return true;
			$this->flushLog("Обновление структуры базы данных...");

			$converter = new dbSchemeConverter($this->connection);
			$converter->setDestinationFile($path);
			$converter->setMode('save');
			$converter->run();

			$this->setDone(__METHOD__);
			return false;
		}

		private function updateDatabaseStructureFromFile($old_structure, $database_structure, $byParts=false) {
			if ($this->checkDone(__METHOD__)) return true;

			$converter = new dbSchemeConverter($this->connection);
			$converter->setDestinationFile($old_structure);
			$converter->setSourceFile($database_structure);
			$converter->setMode('restore', $byParts);

			while(true) {
				$answer = $converter->run();
				$result = $converter->getConverterLog();
				foreach($result as $message) {
					$this->flushLog($message);
				}
				if ($answer===true) {
					break;
				}
				return false;
			}

			$this->setDone(__METHOD__);
			return false;
		}

		/**
		* Обновляет структуру базы данных
		*/
		private function updateDatabaseStructure() {
			if($this->checkDone(__METHOD__)) return true;

			$this->includeMicrocore();

			/* В режиме обновления сохраняем данные супервайзера */
			if (!$this->install_mode) {
				if (!$this->saveSVInfo() && !INSTALLER_CLI_MODE) return false;
			}

			$database_structure = $this->temp_dir . "/core/smu/database.xml";
			if (!is_file($database_structure)) {
				throw new Exception("Не удается найти структуру базы данных: " . $database_structure);
			}

			// В режиме установки очищаем таблицы
			if ($this->install_mode) {
				while(!$this->dropTables($database_structure)) {
					if (!INSTALLER_CLI_MODE) return false;
				}
			}

			$old_structure = str_replace('.xml', '_old.xml', $database_structure);

			// Сохраняем существующую структуру
			while(!$this->saveDatabaseStructure($old_structure)) {
				if (!INSTALLER_CLI_MODE) return false;
			}

			// Обновляем структуру базы данных
			if ($this->install_mode) {
				// Режим установки
				$this->updateDatabaseStructureFromFile($old_structure, $database_structure, false);
			} else {
				while(!$this->updateDatabaseStructureFromFile($old_structure, $database_structure, true)) {
					if (!INSTALLER_CLI_MODE) return false;
				}
			}

			$this->flushLog("Структура базы данных обновлена.");
			$this->setDone(__METHOD__);
			return $this->cli_mode;
		}

		/**
		* Запускает установку всех не установленных компонентов
		*/
		private function installComponents() {
			if($this->checkDone(__METHOD__)) return true;
			$instructions = $this->temp_dir . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query("//package/component[not(@installed)]");

			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				if ($xpath->query("//package/component[@installed]")->length==0 && $this->getComponentOffset($name)==0) {
					$this->flushLog("Установка компонентов...");
				}

				do {
					$old_offset = $this->getComponentOffset($name);
					if ($old_offset==0) {
						$this->flushLog("Установка компонента {$name}...");
						// При первом запуске удаляем файлы, которые предназначены только для установки, и уже существуют
						if (!$this->install_mode) {
							$component_config = $this->temp_dir . "/{$name}/{$name}.xml";
							$source = new DomDocument();
							$source->load($component_config);
							$source_xpath = new DOMXPath($source);
							$to_del = $source_xpath->query('//file[@only_install]');
							foreach($to_del as $file) {
								$path = $file->textContent;
								if (file_exists(CURRENT_WORKING_DIR.$path)) {
									$file->parentNode->removeChild($file);
								}
								else {
									$file->removeAttribute("only_install");
								}
							}
							$source->save($component_config);
						}
					}

					$result = $this->execSubProcess('install-component', array('component' => $name));

					// Перезагружаем состояние
					$this->loadState();
					$new_offset = $this->getComponentOffset($name);

					if ($new_offset>=$old_offset+self::$split_block_size && !$this->cli_mode) {
						return false;
					}

				} while ($new_offset>=$old_offset+self::$split_block_size);

				$this->flushLog("Компонент {$name} установлен.");

				$component->setAttribute('installed', true);
				$doc->save($instructions);

				if(!$this->cli_mode) return false;
			}

			$this->flushLog("Все компоненты были установлены.");
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Удаляет директорию полностью
		*
		* @param mixed $path - путь к директории
		*/
		private function delete($path) {
				if (is_dir($path)) {
				$objects = scandir($path);
				foreach ($objects as $object) {
						if ($object != "." && $object != "..") {
							if (filetype($path."/".$object) == "dir") {
							$this->delete($dir."/".$object);
							} else {
							unlink($dir."/".$object);
							}
						}
				}
			reset($objects);
			rmdir($dir);
				}
		}

		/**
		* Запускает установку указанного компонента
		*/
		private function installComponent() {
			$this->includeMicrocore();
			$name = $component_name = isset($this->params['component']) ? trim($this->params['component']) : '';
			if (!strlen($name)) {
				throw new Exception("Не передано имя компонента для установки (пример: installer.php  --component=core).");
			}

			$offset = $offsetOld = $this->getComponentOffset($component_name);

			$component_config = $this->temp_dir . "/{$component_name}/{$component_name}.xml";
			if (!is_file($component_config)) {
				throw new Exception("Не удается найти файл конфигурации компонента \"{$component_name}\": " . $component_config);
			}

			$this->flushLog("с {$offset} по ".($offset+self::$split_block_size));

			$importer = new xmlImporter(isset($this->params['type']) ? trim($this->params['type']) : 'system');

			if (isset($this->params['type']) && trim($this->params['type']) == 'demosite') {
				$update_ignore_mode = false;
				$importer->setDemositeMode(true);
			}
			else {
				$update_ignore_mode = $this->install_mode;
			}

			$importer->setUpdateIgnoreMode($update_ignore_mode);
			$importer->setFilesSource($this->temp_dir . "/{$component_name}/");

			$splitterType = 'umiDump20';
			if ( (isset($this->params['type']) && trim($this->params['type']) == 'demosite') || !$this->install_mode) {
				$splitterType = 'transfer';
			}

			$splitterClass = $splitterType . 'Splitter';

			$splitter = new $splitterClass($splitterType);

			$splitter->load($component_config, self::$split_block_size, $offset);

			$importer->loadXmlDocument($splitter->getDocument());
			$offset = $splitter->getOffset();
			$this->setComponentOffset($component_name, $offset);

			$importer->execute();
			file_put_contents(CURRENT_WORKING_DIR . "/install.log", implode("\r\n", $importer->getImportLog()), FILE_APPEND);

			if(($offset < ($offsetOld + self::$split_block_size))) {
				return true;
			} else {
				return false;
			}
		}

		/**
		* Устанавливает домен по умолчанию
		*
		*/
		private function setDefaultDomain() {
			if ($this->checkDone(__METHOD__)) return true;

			$this->flushLog("Установка домена по умолчанию...");

			$doc = $this->loadDomDocument($this->temp_dir . "/update-instructions.xml");
			$xpath = new DOMXPath($doc);

			$host = $xpath->evaluate("/package")->item(0)->getAttribute("host");

			$this->includeMicrocore();

			$defaultDomain = domainsCollection::getInstance()->getDefaultDomain();
			$defaultDomain->setHost($host);
			$defaultDomain->commit();

			unset($doc, $xpath, $defaultDomain);
			$this->flushLog("завершено.");
			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Конфигурирует установленную систему
		*/
		private function configure() {
			if($this->checkDone(__METHOD__)) return true;

			$this->includeMicrocore();

			$instructions = $this->temp_dir . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');

			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
			$package  = $doc->firstChild;

			$xpath = new DOMXPath($doc);
			$version = $xpath->evaluate("/package/component[@name='core']/version")->item(0);

			//$host = $package->getAttribute("host");

			$login    = $this->getConfigOption('SUPERVISOR', 'login', null, "В install.ini не указан логин супервайзера.");

			$md5pass = $this->getConfigOption('SUPERVISOR', 'md5pass', null);
			if (is_null($md5pass)) {
				$password = $this->getConfigOption('SUPERVISOR', 'password', null, "В install.ini не указан пароль супервайзера.");
			}

			$fname 	  = $this->getConfigOption('SUPERVISOR', 'fname', $package->getAttribute("owner_fname"));
			$lname 	  = $this->getConfigOption('SUPERVISOR', 'lname', $package->getAttribute("owner_lname"));
			$mname    = $this->getConfigOption('SUPERVISOR', 'mname', $package->getAttribute("owner_mname"));
			$email	  = $this->getConfigOption('SUPERVISOR', 'email', $package->getAttribute("owner_email"));

			//$defaultDomain = domainsCollection::getInstance()->getDefaultDomain();
			//$defaultDomain->setHost($host);
			//$defaultDomain->commit();

			$regedit = regedit::getInstance();
			$regedit->setVar("//settings/keycode", $package->getAttribute("domain_key"));
			$regedit->setVar("//settings/system_edition", $package->getAttribute("edition"));
			$regedit->setVar("//modules/autoupdate/system_edition", $package->getAttribute("edition"));
			$regedit->setVal('//modules/autoupdate/system_version', $version ? $version->getAttribute("name") : "");
			$regedit->setVal('//modules/autoupdate/system_build',   $package->getAttribute("last-revision"));
			$regedit->setVal('//modules/autoupdate/last_updated',   time());

			$regedit->setVal('//modules/users/def_group', umiObjectsCollection::getInstance()->getObjectIdByGUID('users-users-2374'));
			$regedit->setVal('//modules/users/guest_id', umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest'));

			$sv = umiObjectsCollection::getInstance()->getObjectByGUID('system-supervisor');
			$sv->setName($login);
			$sv->login = $login;
			$sv->password = is_null($md5pass)?md5($password):$md5pass;
			if(strlen($fname)) $sv->fname = $fname;
			if(strlen($lname)) $sv->lname = $lname;
			if(strlen($mname)) $sv->father_name = $mname;
			if(strlen($email)) $sv->setValue("e-mail",  $email);
			$sv->commit();

			// GUI установка
			if (!INSTALLER_CLI_MODE && $this->install_mode) {
				$this->cleanup();
				$this->setInstalled();
			}
			// Записываем htaccess
			$this->writeHtaccess();
			// Удаляем install.ini
			if (!INSTALLER_CLI_MODE) {
				$this->deleteInstallIni();
			}

			$this->setDone(__METHOD__);
			return true;
		}

		/**
		* Check this script for writable
		*/
		private function checkSelf() {
			if ( !is_writable(__FILE__) ) {
				throw new Exception('Файл ' . __FILE__ . ' должен быть доступен на запись');
			}
			if ( !is_dir($this->temp_dir) && !mkdir($this->temp_dir, 0777, true) ) {
				throw new Exception("Не удается создать временную директорию \"{$this->temp_dir}\".");
			}
			if ( !is_writeable($this->temp_dir) ) {
				throw new Exception("Временная директория \"{$this->temp_dir}\" не доступна для записи. Пожалуйста, проверьте разрешения.");
			}
			if ( !$this->closeByHtaccess($this->temp_dir.'/..') ) {
				throw new Exception("Не удается создать htaccess в директории \"{$this->temp_dir}\". Пожалуйста, проверьте разрешения.");
			}

			return true;
		}

		/**
		* Check system for being already installed
		*/
		private function checkInstalled() {
			if ($this->install_mode && is_file("./installed")) {
				if (is_dir(CURRENT_WORKING_DIR."/umibackup")
					&& (file_exists(CURRENT_WORKING_DIR."/umibackup/backup_database.xml") && is_file(CURRENT_WORKING_DIR."/umibackup/backup_database.xml"))
					&& (file_exists(CURRENT_WORKING_DIR."/umibackup/backup_files.xml") && is_file(CURRENT_WORKING_DIR."/umibackup/backup_files.xml"))
				) {
					throw new Exception('UMI.CMS уже установлена. Для принудительной установки удалите файл installed из корневой директории сервера. Для восстановления системы из бекапа запустите installer.php c параметром --step=rollback');
				}
				else {
					throw new Exception('UMI.CMS уже установлена. Для принудительной установки удалите файл installed из корневой директории сервера.');
				}
			}
			if (!$this->install_mode && !INSTALLER_CLI_MODE) {
				$backup_name = CURRENT_WORKING_DIR.'/umibackup/backup_files.xml';
				if (file_exists($backup_name)) {
					unlink($backup_name);
				}
				$backup_name = CURRENT_WORKING_DIR.'/umibackup/backup_database.xml';
				if (file_exists($backup_name)) {
					unlink($backup_name);
				}
			}
			return true;
		}

		/**
		* System cleanup
		*/
		private function cleanup() {
			if ( file_exists("./sys-temp/runtime-cache/registry") ) {
				unlink("./sys-temp/runtime-cache/registry");
			}
			// Удаляем файл состояния
			if ( is_file($this->temp_dir . umiInstallExecutor::STATE_FILE_NAME) ) {
				unlink($this->temp_dir . umiInstallExecutor::STATE_FILE_NAME);
			}
			self::$state = array();
			return true;
		}


		/**
		* Очистка системного кеша.
		*
		*/
		private function clearCache() {
			if ($this->checkDone(__METHOD__)) {
				return true;
			}
			$this->flushLog("Очистка системного кеша...");

			$this->includeMicrocore();

			$downloader = $this->getDownloader();
			$modules = $downloader->query('get-modules-list');

			$xml = new DOMDocument();
			if ($xml->loadXML($modules)) {
				$xpath = new DOMXPath($xml);
				$no_active = $xpath->query("//module[not(@active)]");

				if ($no_active->length>0) {
					$regedit = regedit::getInstance();
					foreach($no_active as $module) {
						$name = $module->getAttribute('name');
						if ($regedit->getVal("//modules/{$name}")) {
							$regedit->delVar("//modules/{$name}");
						}
					}
				}

			}

			$cache = cacheFrontend::getInstance();
			$cache->flush();

			$this->flushLog("Завершено.");

			$this->cleanUpdateMode();

			$this->setDone(__METHOD__);
			return true;
		}


		private function deleteInstallIni() {
			if (file_exists(CURRENT_WORKING_DIR."/install.ini") && is_file(CURRENT_WORKING_DIR."/install.ini")) {
				return unlink(CURRENT_WORKING_DIR."/install.ini");
			}
		}

		private function setInstalled() {
			if(!defined("INSTALLER_DEBUG") || !INSTALLER_DEBUG) {
				touch("./installed");
			}
			if ($this->install_mode) {
				$this->flushLog('UMI.CMS установлена.');
			}
			else {
				$this->flushLog('UMI.CMS обновлена.');
			}

			if (INSTALLER_CLI_MODE) {
				if ($this->deleteInstallIni()) {
					$this->flushLog('Файл install.ini удален.');
				}
				else {
					$this->flushLog('Не удалось удалить '.CURRENT_WORKING_DIR.'/install.ini. В целях обеспечения безопасности, пожалуйста, удалите его самостоятельно.');
				}
			}

			return true;
		}

		///TODO Проверка, что install.ini существует, и в нем корректные данные. В противном случае - создаем пример и выбрасываем ошибку
		private function checkIniFile() {
			return true;
		}

		///TODO Пытаемся подконнектится к базе данных с указанными данными. В противном случае - сообщаем пользователю
		private function checkMysqlConnect() {
			return true;
		}

		/**
		* Запускает шаг установки в отдельном процессе
		* Как только в stderr попадают ошибки, установка/обновление прерывается
		* @param string $step - имя шага
		* @param array $params - параметры запроса
		* @param string $data - stdin for child process
		*/
		private function execSubProcessCLI($step, $params = array(), $data = "") {
			$php = $this->getConfigOption('SERVER', 'phppath', 'php');
			$sleep = (int) $this->getConfigOption('SETUP', 'sleep', 0);
			if ($sleep>0) {
				$this->flushLog("Sleep ".($sleep/1000)." sec");
				usleep($sleep*1000);
			}
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
				2 => array("pipe", "w")   // stderr is a file to write to
			);

			$s_params = "";
			foreach($params as $param_name => $param_val) {
				$s_params .= " --{$param_name}={$param_val}";
			}
			$cmd = $php.' -f ' . __FILE__ . ' -- --step=' . $step . $s_params;
			$process = proc_open($cmd, $descriptorspec, $pipes);
			if (is_resource($process)) {
				// send data for child process
				if (strlen($data)) {
					fwrite($pipes[0], $data);
					fclose($pipes[0]);
				}
				// run process
				$errors = "";
				while (($buffer = fgets($pipes[1], self::BUFFER_SIZE)) != NULL || ($errbuf = fgets($pipes[2], self::BUFFER_SIZE)) != NULL) {
					$this->flushLog($buffer);
					if (isset($errbuf)) {
						$errors .= $errbuf;
					}
				}
				if (strlen($errors)) {
					echo $errors;
					die();
				}

				// close all pipes and process
				foreach ($pipes as $pipe) fclose($pipe);
				proc_close($process);
			} else {
				throw new Exception("Не могу запустить дочерний процесс: {$cmd}");
			}
			return true;
		}

		private function execSubProcessNonCLI($step, $params = array(), $data = "") {
			$subInstaller = new umiInstallExecutor($this->temp_dir, $this->install_mode, $params);
			return $subInstaller->run($step, $this->cli_mode, $params);
		}

		private function execSubProcess($step, $params = array(), $data = "") {
			if($this->cli_mode) {
				return $this->execSubProcessCLI($step, $params, $data);
			} else {
				return $this->execSubProcessNonCLI($step, $params, $data);
			}
		}

		/**
		* Запускает процесс установки в CLI-режиме
		*/
		private function runInstaller() {
			return

			// check already installed
			$this->checkInstalled() and

			// check for writable
			$this->checkSelf() and

			// check ini file
			$this->checkIniFile() and

			// check mysql connect
			$this->checkMysqlConnect() and

			// clear system
			$this->cleanup() and

			// create backup files
			$this->execSubProcess('backup-files') and

			// download and extract service package
			$this->execSubProcess('download-service-package') and
			$this->execSubProcess('extract-service-package') and
			// run tests
			$this->execSubProcess('run-tests') and

			// write configuration required for installation purposes
			$this->execSubProcess('write-initial-configuration') and

			// download instructions
			$this->execSubProcess('get-update-instructions') and
			$this->execSubProcess('download-components') and
			$this->execSubProcess('extract-components') and
			$this->execSubProcess('check-components') and

			// backup mysql base
			$this->execSubProcess('backup-mysql') and

			// install the system
			$this->execSubProcess('update-installer') and
			$this->execSubProcess('update-database') and
			$this->execSubProcess('install-components') and

			// set default domains from package
			$this->execSubProcess('set-default-domain') and

			// install demosite
			$this->execSubProcess('download-demosite') and
			$this->execSubProcess('extract-demosite') and
			$this->execSubProcess('check-demosite') and
			$this->execSubProcess('install-demosite') and

			// configure installed system
			$this->execSubProcess('configure') and

			// Очистка системного кеша
			$this->execSubProcess('clear-cache') and

			// cleanup installed system
			$this->cleanup() and

			// set installed
			$this->setInstalled();
		}
	}



	/**
	* Класс для последовательного скачивания и распаковки пакетов с сервера обновлений
	* @author Anton Prusov
	*/
	class umiUpdateDownloader {
		private $destination, $key, $host, $ip, $current_revision;
		private $license = array();

		/**
		* Создает экземпляр класса umiUpdateDownloader
		*
		* @param string $destination - путь до директории, в которую будет происходить скачивание. Директория должна существовать и быть доступна на запись.
		* @param mixed $key - Лицензионный или доменный ключ
		* @param mixed $host - Имя домена, на который выписана лицензия
		* @param mixed $ip - Ip-адрес, на который выписана лицензия
		* @param mixed $current_revision - Номер текущей ревизии, 'last', если это установка, либо ревизия не актуальна
		* @return umiUpdateDownloader
		*/
		public function __construct($destination, $key, $host, $ip, $current_revision = 'last') {
			if (!is_dir($destination)) {
				throw new Exception("Директория назначения не найдена");
			}
			$this->destination = realpath($destination);

			$this->key = $key;
			$this->host = $host;
			$this->ip = $ip;
			$this->current_revision = $current_revision;

		}

		/**
		* Скачивает $component_name с сервера обновлений
		*
		* @param mixed $component_name - имя компонента
		*/
		public function downloadComponent($component_name) {
			$this->downloadFile("get-component", $component_name);
		}

		/**
		* Скачивает указанный демосайт с сервера обновлений
		*
		* @param string $demosite_name имя демосайта
		*/
		public function downloadDemosite($demosite_name) {
			$this->downloadFile("get-demosite", $demosite_name);
		}

		/**
		* Скачивает указанный сервисный пакет с сервера обновлений
		*
		* @param string $service_name имя сервисного пакета
		*/
		public function downloadServiceComponent($service_name) {
			$this->downloadFile("get-service", $service_name, "service");
		}

		private function downloadFile($request_type, $filename, $fileSuffix = false) {
			$fname = $this->destination . "/" . $filename . ($fileSuffix ? ".{$fileSuffix}" : "") . ".tar";
			$content = $this->query($request_type, array('component' => $filename));
			file_put_contents($fname, $content);
		}

		public function getDemositesList() {
			$result = $this->query('get-demosite-list');
			$doc = new DOMDocument('1.0', 'utf-8');
			if ($doc->loadXML($result)) {
				$this->checkResponseErrors($doc);
				return $doc;
			}
			else {
				throw new Exception("Не удается загрузить список демосайтов.");
			}
		}

		/**
		* Скачивает все компоненты, доступные для лицензии
		*/
		public function downloadAllComponents() {
			$instructions = $this->destination . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Не удается загрузить инструкции по обновлению.");
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query("//package/component[not(@downloaded)]");
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$this->downloadComponent($name);
				$component->setAttribute('downloaded', true);
				$doc->save($instructions);
			}
		}

		/**
		* Скачивает changelog и инструкции для установки/обновления с сервера
		*/
		public function downloadUpdateInstructions() {
			$result = $this->query('get-update-instructions');
			$doc = new DOMDocument('1.0', 'utf-8');
			if ($doc->loadXML($result)) {
				$this->checkResponseErrors($doc);
				file_put_contents($this->destination . "/update-instructions.xml", $doc->saveXML());
				return $doc;
			}
			else {
				throw new Exception("Не удается загрузить инструкции по обновлению");
			}
		}


		private function flushLog($msg) {
			echo $msg;
		}

		private function checkResponseErrors(DOMDocument $doc) {
			if ($doc->documentElement->getAttribute('type') == 'exception') {
				$xpath = new DOMXPath($doc);
				$errors = $xpath->query("//error");
				foreach($errors as $error) {
					throw new Exception($error->nodeValue, $error->getAttribute('code'));
				}
			}
		}

		public function query($type, $params = array(), $start=false, $end=false) {
			$url = $this->build_query($type, $params);
			$url = preg_replace('|^http:\/\/|i', '', $url);
			$host = preg_replace('|\/.+$|i', '', $url);
			$query = preg_replace('|^[^/]+|i', '', $url);

			if (is_callable("curl_init")) {
				return $this->get_file_by_curl($host, $query, $start, $end);
			}
			elseif($fp=fsockopen($host, 80)) {
				fclose($fp);
				return $this->get_file_by_socket($host, $query, $start, $end);
			}
			else {
				throw new Exception('Не удается создать исходящее соединение.');
			}
		}

		/**
		* Возвращает запрошенный файл
		*
		* @param mixed $url - http адрес файла.
		*/
		public function get_file($url) {
			$url = preg_replace('|^http:\/\/|i', '', $url);
			$host = preg_replace('|\/.+$|i', '', $url);
			$query = preg_replace('|^[^/]+|i', '', $url);

			if (is_callable("curl_init")) {
				return $this->get_file_by_curl($host, $query);
			}
			elseif($fp=fsockopen($host, 80)) {
				fclose($fp);
				return $this->get_file_by_socket($host, $query);
			}
			else {
				throw new Exception('Не удается создать исходящее соединение.');
			}
		}


		// Формирует адрес для запроса и возвращает его
		private function build_query($type, $params = array()) {
			$params['type'] = $type;
			$params['host'] = $this->host;
			$params['ip'] = $this->ip;
			$params['key'] = $this->key;
			$params['revision'] = $this->current_revision;
			return base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv') . "?" . http_build_query($params, '', '&');
		}

		// Получение содержимого файла через сокеты
		private function get_file_by_socket($host, $query, $start=false, $end=false) {
			$fp = fsockopen($host, 80, $errno, $errstr, 30);
			if ($start!==false && $end!==false) {
				$out = "GET {$query} HTTP/1.1\r\n";
				$out .= "Range: bytes={$start}-{$end}\r\n";
			}
			else {
				$out = "GET {$query} HTTP/1.0\r\n";
			}
			$out .= "Host: {$host}\r\n";
			$out .= "Connection: Close\r\n\r\n";

			fwrite($fp, $out);
			// Пропускаем заголовки
			$heads = "";
			while(!feof($fp)) {
				$str = fgets($fp, 1024);
				$heads.=$str;
				if (strlen($str)==2) {
					break;
				}
			}
			// Читаем содержимое
			$res = '';
			while (!feof($fp)) {
				$res .= fread($fp, 1024);
			}
			fclose($fp);

			if (stripos($heads, "text/xml")!==false) {
				if (!class_exists("DomDocument")) {
					throw new Exception("Отсутствует класс DomDocument.  Подробное описание ошибки и способы её устранения доступны по ссылке http://errors.umi-cms.ru/13051/");
				}

				$doc = new DOMDocument('1.0', 'utf-8');
				if ($doc->loadXML($res)) {
					$this->checkResponseErrors($doc);
				}
				unset($doc);
			}
			return $res;
		}

		// Получение содержимого файла через curl
		private function get_file_by_curl($host, $query, $start=false, $end=false) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://{$host}{$query}");
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if ($start!==false && $end!==false) {
				curl_setopt($ch, CURLOPT_RANGE, $start.'-'.$end);
				$res = curl_exec($ch);
			}
			$res = curl_exec($ch);

			$info = curl_getinfo($ch);
			curl_close($ch);

			if (isset($info["content_type"]) && stripos($info["content_type"], "text/xml")!==false) {
				if (!class_exists("DomDocument")) {
					throw new Exception("Отсутствует класс DomDocument.  Подробное описание ошибки и способы её устранения доступны по ссылке http://errors.umi-cms.ru/13051/");
				}
				$doc = new DOMDocument('1.0', 'utf-8');
				if ($doc->loadXML($res)) {
					$this->checkResponseErrors($doc);
				}
				unset($doc);
			}
			return $res;
		}
	}


	/**
	* Class for extracting files from uncompressed tarball (ustar) archives
	* @author Leeb
	* @link http://www.freebsd.org/cgi/man.cgi?query=tar&sektion=5&manpath=FreeBSD+8-current
	*/
	class umiTarExtracter {
		const TAR_CHUNK_SIZE = 512;
		/**
		* Tar entry type flags
		*/
		const TAR_ENTRY_REGULARFILE = '0';
		const TAR_ENTRY_HARDLINK 	= '1';
		const TAR_ENTRY_SYMLINK 	= '2';
		const TAR_ENTRY_CHARDEVICE 	= '3';
		const TAR_ENTRY_BLOCKDEVICE = '4';
		const TAR_ENTRY_DIRECTORY	= '5';
		const TAR_ENTRY_FIFO 		= '6';
		const TAR_ENTRY_RESERVED 	= '7';

		/**
		* Path to the tarball archive file
		*
		* @var string
		*/
		private $archiveFilename = null;

		/**
		* Archive file handle
		*
		* @var resource
		*/
		private $handle = null;

		/**
		* @param string $filename path to tarball archive file
		* @return umiTarExtracter
		*/
		public function __construct($filename) {
			$this->archiveFilename = $filename;
			if(!is_file($this->archiveFilename)) {
				throw new Exception("umiTarExtracter: {$this->archiveFilename} не существует.");
			}
		}

		public function __destruct() {
			$this->close();
		}

		/**
		* Extract $limit file records starting from $offset position
		*
		* @param int|false $offset
		* @param int|false $limit
		* @return int new offset (after extracting)
		*/
		public function extractFiles($offset = false, $limit = false) {
			$currentOffset = 0;

			$this->open();

			fseek($this->handle, 0, SEEK_SET);

			while($currentOffset < $offset) {
				$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
				if($this->eof($data)) {
					return $currentOffset;
				}
				$header = $this->parseEntryHeader($data);
				if($header['typeflag'] == umiTarExtracter::TAR_ENTRY_REGULARFILE) {
					$fileChunkCount = floor($header['size'] / umiTarExtracter::TAR_CHUNK_SIZE) + 1;
					fseek($this->handle, $fileChunkCount * umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
				}
				$currentOffset++;
			}

			while($limit === false || ($currentOffset < $offset + $limit)) {
				$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
				if($this->eof($data)) {
					break;
				}
				$header = $this->parseEntryHeader($data);
				$name = (strlen($header['prefix']) ? ($header['prefix'] . '/') : '') . $header['name'];
				switch($header['typeflag']) {
					case umiTarExtracter::TAR_ENTRY_REGULARFILE : {
						$dstHandle = fopen($name, "wb");
						if (!$dstHandle) {
							throw new Exception("umiTarExtracter: не удается записать файл: " . $name);
						}
						$bytesLeft = $header['size'];
						if ($bytesLeft)
						do {
							$bytesToWrite = $bytesLeft < umiTarExtracter::TAR_CHUNK_SIZE ? $bytesLeft : umiTarExtracter::TAR_CHUNK_SIZE;
							$bytes = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
							fwrite($dstHandle, $bytes, $bytesToWrite);
							$bytesLeft -= umiTarExtracter::TAR_CHUNK_SIZE;
						} while($bytesLeft > 0);
						fclose($dstHandle);
						if (strtolower(substr($name, -4, 4))==='.php') {
							chmod($name, PHP_FILES_ACCESS_MODE);
						}
						break;
					}
					case umiTarExtracter::TAR_ENTRY_DIRECTORY : {
						if(!is_dir($name)) {
							if (!mkdir($name, 0777, true)) {
								throw new Exception("umiTarExtracter: не удается создать директорию: " . $name);
								exit();
							}
						}
						break;
					}
				}
				$currentOffset++;
			}

			return $currentOffset;
		}

		private function open() {
			if($this->handle == null) {
				$this->handle = fopen($this->archiveFilename, 'rb');
				if($this->handle === false) {
					throw new Exception("umiTarExtracter: Не удается открыть {$this->archiveFilename}");
				}
			}
			return $this->handle;
		}

		private function close() {
			if($this->handle != null) {
				fclose($this->handle);
			}
		}

		private function parseEntryHeader($rawHeaderData) {
			$header = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/atypeflag/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix/x12pad', $rawHeaderData);
			$header['uid'] 		= octdec($header['uid']);
			$header['gid'] 		= octdec($header['gid']);
			$header['size'] 	= octdec($header['size']);
			$header['mtime'] 	= octdec($header['mtime']);
			$header['checksum'] = octdec(substr($header['checksum'], 0, 6));
			return $header;
		}

		private function eof(&$data) {
			$eofPattern = null;
			if($eofPattern == null){
				$eofPattern = str_repeat(chr(0), 512);
			}
			if(strcmp($data, $eofPattern) == 0) {
				$ahead = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
				if(strcmp($ahead, $eofPattern) == 0) {
					return true;
				}
				fseek($this->handle, -umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
			}
			return false;
		}

	};

	function parse_argv($arr) {
		$args = Array();
		foreach($arr as $v) {
			$va = explode("=", $v);
			if(sizeof($va) != 2) continue;
			list($k, $p) = $va;
			$args[trim(substr($k, 2))] = trim($p);
		}
		return $args;
	}

?>
