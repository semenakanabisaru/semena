#!/usr/local/bin/php
<?php

	$updater_dir = dirname(__FILE__);

	chdir($updater_dir . '/../../../');
	define("CURRENT_WORKING_DIR", realpath("."));
	define("_C_REQUIRES", true);
	define('_C_ERRORS', true);
	define('CRON', true);
	define('DEBUG', true);
	
	
	define('UMICMS_CLI_MODE', true);
	
	include $updater_dir . "/umicms-microcore.php";

	$updater = new umiUpdateInstaller(realpath("./tmp"), realpath("."), true);
	$updater->updateDatabaseStructure();
	$updater->updateAllComponents();

	class umiUpdateInstaller {
		private $source, $destination;
		private $connection;
		private $install_mode = false;
		
		public function __construct($source, $destination, $install_mode = false) {
			if (!is_dir($source)) {
				throw new Exception("Source directory for update \"{$source}\" does not exists.");
			}
			if (!is_dir($destination)) {
				throw new Exception("Destination directory for update \"{$destination}\" does not exists.");
			}
			$this->install_mode = $install_mode;
			$this->source = $source;
			$this->destination = $destination;
			$this->connection = ConnectionPool::getInstance()->getConnection();
		}
		
		private function writeLog($msg) {
			echo $msg;
		}
		
		public function updateDatabaseStructure() {
			$this->writeLog("Updating database strucure...\r\n");
			$database_structure = $this->source . "/core/smu/database.xml";
			
			if (!is_file($database_structure)) {
				throw new Exception("Can't found database structure: " . $database_structure);
			}
			
			$converter = new dbSchemeConverter($this->connection, $database_structure);
			$converter->restoreDataBase();
			
			$this->writeLog("Database structure has been updated.\r\n");
		}
		
		
		public function updateAllComponents() {
			$instructions = $this->source . "/update-instructions.xml";
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!$doc->load($instructions)) {
				throw new Exception("Can't load update instructions");
			}
			$xpath = new DOMXPath($doc);
			$components = $xpath->query("//package/component[not(@updated)]");
			foreach ($components as $component) {
				$name = $component->getAttribute('name');
				$this->updateComponent($name);
				$component->setAttribute('updated', true);
				$doc->save($instructions);
			}
		}
		
		public function updateComponent($component_name) {
			$this->writeLog("Updating component \"{$component_name}\"...\r\n");

			$component_config = $this->source . "/{$component_name}/{$component_name}.xml";
			if (!is_file($component_config)) {
				throw new Exception("Can't found component \"{$component_name}\" config: " . $component_name);
			}

			$importer = new xmlImporter('system');
			$importer->setUpdateIgnoreMode($this->install_mode);
			$importer->setFilesSource($this->source . "/{$component_name}/");
			$importer->loadXmlFile($component_config);
			$importer->execute();
			
			$this->writeLog("Component \"{$component_name}\" has been updated.\r\n");
		}
		
	}

?>
