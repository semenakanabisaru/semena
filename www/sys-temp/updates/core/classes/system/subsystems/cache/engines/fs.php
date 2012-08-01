<?php
	class fsCacheEngine implements iCacheEngine {
		protected $wc, $is_connected = false;
		const mask = 0777, entLevel = 5;
		
		public function __construct() {
			$this->wc = $this->requireFolder(SYS_CACHE_RUNTIME . 'fs-cache/');
			$this->is_connected = (bool) $this->wc;
		}
		
		public function getIsConnected() {
			return $this->is_connected;
		}
		
		public function saveObjectData($key, $object, $expire) {
			$this->saveRawData($key, $object, $expire);
		}

		public function saveRawData($key, $string, $expire) {
			if($expire <= 0) $this->delete($key);
			$path = $this->calcPathByKey($key);
			$this->requireFile($path);
			
			$content = (int) $expire . "\n" . serialize($string);
			file_put_contents($path, $content);
		}
		
		public function loadObjectData($key) {
			return $this->loadRawData($key);
		}

		public function loadRawData($key) {
			$path = $this->calcPathByKey($key);
			if(is_file($path) == false) return false;
			
			$mtime = filemtime($path);
			$content = file_get_contents($path);
			$i = strpos($content, "\n");
			$expire = (int) substr($content, 0, $i);
			
			if(time() > ($mtime + $expire)) {
				$this->delete($key);
				return false;
			}
			
			$data = substr($content, $i + 1);
			return unserialize($data);
		}

		public function delete($key) {
			$path = $this->calcPathByKey($key);
			if(is_file($path)) @unlink($path);
		}

		public function flush() {
			if($this->wc) {
				$dir = new umiDirectory($this->wc);
				$dir->delete();
			}
		}
		
		protected function requireFolder($folder) {
			if(!is_dir($folder)) mkdir($folder, self::mask, true);
			return $folder;
		}

		protected function requireFile($file) {
			$this->requireFolder(dirname($file));
			touch($file);
			chmod($file, self::mask);
		}

		protected function calcPathByKey($key) {
			$length = self::entLevel;
			$parts = array_reverse(preg_split("/[_\.\/:]+/", $key));
			$lastPart = array_pop($parts);
			
			if(strlen($lastPart) < $length) {
				$lastPart = str_repeat('0', $length - strlen($lastPart)) . $lastPart;
			}
			
			for($i = 0; $i < $length; $i++)  $parts[] = substr($lastPart, $i, 1);
			if($length < strlen($lastPart)) $parts[] = substr($lastPart, $length);
			return $this->wc . implode("/", $parts);
		}
	};
?>