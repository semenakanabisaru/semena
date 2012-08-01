<?php
	class staticCache {
		protected	$config, $enabled, $splitLevel = 5,
					$requestUri, $isAdmin = false, $cacheFolder, $cacheFilePath;

		public function __construct() {
			$this->config = mainConfiguration::getInstance();
			$this->enabled = (bool) $this->config->get('cache', 'static.enabled');

			if($this->enabled) {
				$folder = $this->config->includeParam('system.static-cache');
				if(!$folder) {
					$folder = dirname(dirname(__FILE__)) . '/sys-temp/runtime-cache/';
				}
				if (substr($folder, -1, 1)!='/') {
					$folder.='/';
				}
				$folder.= preg_replace('/^www\./i', '', getServer("HTTP_HOST")."/");
				$this->setRequestUri(getServer('REQUEST_URI'));
				$this->setCacheFolder($folder);
				$this->cacheFilePath = $this->prepareCacheFile();
			}
		}

		public function setRequestUri($requestUri) {
			$requestUri = trim($requestUri, '/');
			if(!$requestUri) $requestUri = '__splash';
			$this->requestUri = $requestUri;

			$isAdmin = false;
			if(substr($requestUri, 0, strlen('admin')) == 'admin') {
				$isAdmin = true;
			} elseif (substr($requestUri, 3, strlen('admin')) == 'admin') {
				$isAdmin = true;
			}
			$this->isAdmin = $isAdmin;
		}

		public function setCacheFolder($cacheFolder) {
			if(is_dir($cacheFolder)) {
				$this->cacheFolder = $cacheFolder;
				return true;
			} else {
                echo '<!--'.$cacheFolder.' -->';
				mkdir($cacheFolder, 0777, true);
				if(is_dir($cacheFolder)) {
					$this->cacheFolder = $cacheFolder;
					return true;
				} else {
					return false;
				}
			}
		}

		public function cleanup() {
			$this->deleteElementsRelatedPages();
		}

		public function load() {
			$config = $this->config;
			$contentPath = $this->cacheFilePath;
			$headersPath = $this->cacheFilePath . '.store.php';

			if($config->get('cache', 'static.mode') == 'nginx') {
			$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
				$userId = getSession('user_id');
				if( $userId  && $userId != $guestId ) {
					return false;
				}
			}

			if(sizeof($_POST) > 0 || $this->isAdmin || !file_exists($contentPath)) {
				return false;
			}

			switch($config->get('cache', 'static.mode')) {
				case 'short':
					$expire = 10 * 60;
					break;

				case 'login':
					$expire = 3600*2*365;
					break;

				default:	// (normal)
					$expire = 3600 * 24;
					break;
			}

			if((filemtime($contentPath) + $expire) < time()) {
				$this->deleteFileIfExists($contentPath);
				return false;
			}

			if(file_exists($headersPath)) {
				include $headersPath;
			}

			$content = trim(file_get_contents($contentPath));
			if($content) {
				if(!$config->get('cache', 'static.ignore-stat')) {
					$this->saveStatInfo();
				}

				$buffer = outputBuffer::current();
				$buffer->contentType('text/html');
				$buffer->charset('utf-8');
				$buffer->clear();
				$buffer->push($content);
				$buffer->end();
			}
		}

		public function save($content) {
			if(!$this->enabled || !$content) return false;
			if($this->isAdmin) return false;
			if($this->config->get('cache', 'static.mode') == 'nginx') {
				$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
				if(($userId = getSession('user_id')) && ($userId != $guestId)) {
					return false;
				}

				$config = mainConfiguration::getInstance();
				$rules = $config->get('cache', 'not-allowed-methods');
				if($rules) {
					foreach($rules as $rule) {
						if ($rule && strpos($this->cacheFilePath, $rule) !== false) return false;
					}
				}
			}

			$content = str_replace(array('<?', '?>'), array('&lt?;', '?&gt;'), $content);
			$path = $this->cacheFilePath;
			file_put_contents($path, $content);
			@chmod($path, 0777);

			$this->saveCacheElementsRelations();

			if($this->config->get('cache', 'static.mode') == 'nginx') return true;

			$headers_list_ser = base64_encode(serialize(headers_list()));
			$session_ser = base64_encode(serialize($_SESSION));

			$store = <<<END
<?php
	\$headers = unserialize(base64_decode('$headers_list_ser'));
	\$session = unserialize(base64_decode('$session_ser'));

	if(is_array(\$headers)) {
		\$cmp = strtolower("Set-Cookie");

		for(\$i = 0; \$i < sizeof(\$headers); \$i++) {
			if(substr(strtolower(\$headers[\$i]), 0, strlen(\$cmp)) == \$cmp) {
				continue;
			} else {
				header(\$headers[\$i]);
			}
		}
	}
	if (!session_id()) session_start();
	\$_SESSION = \$session;
?>
END;

			file_put_contents($path . ".store.php", $store);
			@chmod($path . ".store.php", 0777);
		}

		protected function saveCacheElementsRelations() {
			$hierarchy = umiHierarchy::getInstance();
			$elements = $hierarchy->getCollectedElements();
			$elements = array_unique($elements);
			foreach($elements as $elementId) {
				$this->saveCacheElementRelation($elementId);
			}
		}

		protected function saveCacheElementRelation($elementId) {
			$filePath = $this->cacheFolder . 'cacheElementsRelations/' . $this->defragmentDirPath($elementId, 3);
			if($this->createDirectory($filePath) == false) return;

			$filePath .= $elementId . '.tmp';

			if(!file_exists($filePath)) {
				touch($filePath);
				@chmod($filePath, 0777);
			}

			if(!file_exists($filePath)) return false;

			$f = fopen($filePath, "a+");
			fwrite($f, $this->cacheFilePath . "\n");
			fclose($f);

			$this->cleanupRelationsFile($filePath);

			return true;
		}

		protected function deleteElementsRelatedPages() {
			$hierarchy = umiHierarchy::getInstance();
			$updatedElements = $hierarchy->getUpdatedElements();

			foreach($updatedElements as $elementId) {
				$this->deleteElementRelatedPages($elementId);
			}
		}

		protected function deleteElementRelatedPages($elementId) {
			$this->enabled = (bool) $this->config->get('cache', 'static.enabled');
			$nginx = $this->config->get('cache', 'static.mode') == 'nginx';

			if ($this->enabled && $nginx) {
				$hierarchy = umiHierarchy::getInstance();
				$element = $hierarchy->getElement($elementId);
				if ($element instanceOf umiHierarchyElement) {
					if (is_object(domainsCollection::getInstance()->getDomain($element->getDomainId()))) {
						$domain = domainsCollection::getInstance()->getDomain($element->getDomainId())->getHost();
						$lang = langsCollection::getInstance()->getLang($element->getLangId())->getPrefix();
						$folder = $this->config->includeParam('system.static-cache');
						if(!$folder) {
							$folder = dirname(dirname(__FILE__)) . '/sys-temp/runtime-cache/';
						}
						if (substr($folder, -1, 1)!='/') {
							$folder.='/';
						}
						$folder .= $domain . $hierarchy->getPathById($elementId);
						$file = $folder . 'index.html';
						$this->deleteFileIfExists($file);
						$this->deleteFolderIfEmpty(dirname($file));
					}
				}
			}

			$filePath = $this->cacheFolder . 'cacheElementsRelations/' . $this->defragmentDirPath($elementId) . '/' . $elementId . '.tmp';
			if(!file_exists($filePath)) return false;

			$f = fopen($filePath, "r");
			if($f !== false) {
				while(!feof($f)) {
					$path = trim(fgets($f, 1024));
					if($this->deleteFileIfExists($path)) {
						$this->deleteFileIfExists($path . ".store.php");
					}
					$this->deleteFolderIfEmpty(dirname($path));
				}
				fclose($f);
				unlink($filePath);
			}

			$this->deleteFolderIfEmpty(dirname($filePath));

			$elements = umiHierarchy::getInstance()->getObjectInstances($elementId);
			if(is_array($elements)) {
				foreach($elements as $id) {
					if($id != $elementId) {
						$this->deleteElementRelatedPages($id);
					}
				}
			}
		}

		protected function prepareCacheFile() {
			if($this->config->get('cache', 'static.mode') == 'nginx') {
				$preparedDirPath = $this->cacheFolder;
				
				if($this->requestUri != '__splash') {
					$preparedDirPath .= $this->requestUri;
				}
				if(substr($preparedDirPath, -1) != '/') {
					$preparedDirPath .= '/';
				}
				$preparedFilePath = $preparedDirPath . 'index.html';
				
			} else {
				$userId = getSession('user_id');
				$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
				if(!$userId) $userId = $guestId;

				$requestHash = sha1($this->requestUri);
				$hostHash = sha1(getServer('HTTP_HOST'));

				$hashPrefix = substr($requestHash, 0, $this->splitLevel);

				$preparedDirPath =  "{$this->cacheFolder}userCache/{$userId}/{$hostHash}/";
				$preparedDirPath .= $this->defragmentDirPath($hashPrefix, $this->splitLevel);

				$preparedFilePath = $preparedDirPath . substr($requestHash, $this->splitLevel);

				if($userId == $guestId && getSession('is_human')) {
					$preparedFilePath .= '_human';
				}
				$preparedFilePath .= '.tmp';
			}
			if(!$this->isAdmin && $this->createDirectory($preparedDirPath) == false) {
				return false;
			}
			return $preparedFilePath;
		}

		protected function defragmentDirPath($path, $level = 5) {
			$result = "";

			if(strlen($path) < $level) {
				$level = strlen($path);
			}

			for($i = 0; $i < $level; $i++) {
				$result .= substr($path, $i, 1) . "/";
			}

			$result .= substr($path, $i);

			if($i < strlen($path)) {
				$result .= "/";
			}

			return $result;
		}

		protected function createDirectory($path) {
			return is_dir($path) ? is_writable($path) : mkdir($path, 0777, true);
		}

		protected function cleanupRelationsFile($filePath) {
			if(rand(0, 25) == 3) {
				file_put_contents($filePath, array_unique(file($filePath)));
			}
		}

		protected function deleteFileIfExists($filePath) {
			if(is_file($filePath)) {
				return is_writable($filePath) ? unlink($filePath) : false;
			} return true;
		}

		protected function deleteFolderIfEmpty($path) {
			$path = realpath($path);
			if(!is_dir($path)) return false;

			$dir = opendir($path);
			while(($obj = readdir($dir)) !== false) {
				if($obj == "." || $obj == "..") continue;
				return false;
			}
			if(is_writable($path)) {
				$parentPath = realpath($path . "/../");
				rmdir($path);
				$this->deleteFolderIfEmpty($parentPath);
				return true;
			} else return false;
		}

		protected function saveStatInfo() {
			$cmsController = cmsController::getInstance();

			$cmsController->analyzePath();
			if($stat_inst = $cmsController->getModule("stat")) {
				$stat_inst->pushStat();
			}
		}
	};
?>
