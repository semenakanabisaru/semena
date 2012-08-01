<?php
	abstract class umiImportSplitter implements iUmiImportSplitter {
		protected $offset = 0;
		protected $block_size = 10;
		protected $type = "";
		protected $file_path = false;
		protected $complete = false;
		public $ignoreParentGroups = true;
		public $autoGuideCreation = false;
		public $renameFiles = false;

		abstract protected function readDataBlock();

		final static public function get($className) {
			if($wrapper = self::loadWrapper($className)) {
				return $wrapper;
			} else {
				throw new publicException("Can't load splitter for type \"{$className}\"");
			}
		}

		public function translate(DomDocument $doc) {
			global $includes;
			$config = mainConfiguration::getInstance();
			$style_file = CURRENT_WORKING_DIR . '/xsl/import/' . $this->type . '.xsl';
			if (!is_file($style_file)) {
				throw new publicException("Can't load translator {$style_file}");
			}
			$includes['xslTemplater'] = array(SYS_KERNEL_PATH . 'subsystems/templaters/xslt/xslTemplater.php');

			$templater = xslTemplater::getInstance();
			$templater->setFilePath($style_file);
			$templater->setXmlDocument($doc);
			return $templater->parseResult();
		}

		public function __construct($type) {
			$this->type = $type;
		}

		final static private function loadWrapper($className) {
			static $loaded = array(), $config;

			if(isset($loaded[$className])) {
				return $loaded[$className];
			}

			if(is_null($config)) {
				$config = mainConfiguration::getInstance();
			}


			$wrapperClassName = $className . 'Splitter';
			$filePath = $config->includeParam('system.kernel') . 'subsystems/import/splitters/' . $wrapperClassName . '.php';
			if(is_file($filePath) == false) {
				$loaded[$className] = false;
				throw new publicException("Can't load splitter \"{$filePath}\" for \"{$className}\" file type");
			}

			require $filePath;

			if(!class_exists($wrapperClassName)) {
				$loaded[$className] = false;
				throw new publicException("Spliter class \"{$wrapperClassName}\" not found");
			}

			$wrapper = new $wrapperClassName($className);

			if($wrapper instanceof self == false) {
				$loaded[$className] = false;
				throw new publicException("Splitter class \"{$wrapperClassName}\" should be instance of umiImportSplitter");
			}

			return $loaded[$className] = $wrapper;

		}


		public function load($file_path, $block_size = 100, $offset = 0) {
			if (!is_file($file_path)) {
				throw new publicException("File " . $file_path . " does not exists.");
			}
			$this->block_size = (int) $block_size;
			$this->offset = (int) $offset;
			$this->file_path = $file_path;
		}

		public function getIsComplete() {
			return $this->complete;
		}

		public function getXML() {
			$doc = $this->readDataBlock();
			return $doc ? $doc->saveXML() : false;
		}

		public function getDocument() {
			$doc = $this->readDataBlock();
			return $doc;
		}

		public function getOffset() {
			return $this->offset;
		}

		public function getRenameFiles() {
			return (bool) $this->renameFiles;
		}

	}

?>