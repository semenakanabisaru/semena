<?php
	abstract class umiExporter implements iUmiExporter {
		protected $type = "";
		protected $file_path = false;
		protected $complete = false;
		protected $source_name = false;
		protected $completed = true;

		abstract public function export($stems);
		abstract public function setOutputBuffer();

		final static public function get($className) {
			if($wrapper = self::loadWrapper($className)) {
				return $wrapper;
			} else {
				throw new publicException("Can't load exporter for type \"{$className}\"");
			}
		}

		public function __construct($type) {
			$this->type = $type;
		}

		public function getFileExt() {
			return "xml";
		}

		public function setSourceName($source_name = false) {
			$this->source_name = $source_name;
		}

		public function getSourceName() {
			return $this->source_name ? $this->source_name : $this->type;
		}

		public function getIsCompleted() {
			return $this->completed;
		}


		final static private function loadWrapper($className) {
			static $loaded = array(), $config;

			if(isset($loaded[$className])) {
				return $loaded[$className];
			}

			if(is_null($config)) {
				$config = mainConfiguration::getInstance();
			}


			$wrapperClassName = $className . 'Exporter';
			$filePath = $config->includeParam('system.kernel') . 'subsystems/export/exporters/' . $wrapperClassName . '.php';
			if(is_file($filePath) == false) {
				$loaded[$className] = false;
				throw new publicException("Can't load exporter \"{$filePath}\" for \"{$className}\" file type");
			}

			require $filePath;

			if(!class_exists($wrapperClassName)) {
				$loaded[$className] = false;
				throw new publicException("Exporter class \"{$wrapperClassName}\" not found");
			}

			$wrapper = new $wrapperClassName($className);

			if($wrapper instanceof self == false) {
				$loaded[$className] = false;
				throw new publicException("Exporter class \"{$wrapperClassName}\" should be instance of umiExporter");
			}

			return $loaded[$className] = $wrapper;

		}

		protected function getUmiDump($branches, $source_name = false) {
			if (!$source_name) {
				$source_name = $this->getSourceName();
			}
			$exporter = new xmlExporter($source_name);
			$exporter->addBranches($branches);
			$exporter->setIgnoreRelations();
			$result = $exporter->execute();

			return $result->saveXML();
		}

		protected function getUmiDumpObjects($objects, $source_name = false) {
			if (!$source_name) {
				$source_name = $this->getSourceName();
			}
			$exporter = new xmlExporter($source_name);
			$exporter->addObjects($objects);
			$exporter->setIgnoreRelations();
			$result = $exporter->execute();

			return $result->saveXML();
		}


	}

?>