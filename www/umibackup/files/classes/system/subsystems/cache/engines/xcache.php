<?php
	class xcacheCacheEngine implements iCacheEngine {
		protected $mode, $enabled = false, $connected = false, $compress = false, $is_sleep = false;
		public static $cacheMode = false;
		
		public function __construct() {
			$this->connected = true;	//TODO: Temp
			$this->enabled = true;
			self::$cacheMode = true;
		}
		
		public function getIsConnected() {
			return (bool) $this->connected;
		}

		public function saveObjectData($key, $object, $expire) {
			return xcache_set($key, serialize($object), $expire);
		}
		
		public function saveRawData($key, $string, $expire) {
			return xcache_set($key, $string, $expire);
		}
		
		public function loadObjectData($key) {
			return unserialize(xcache_get($key));
		}
		public function loadRawData($key) {
			return xcache_get($key);
		}
		
		public function delete($key) {
			return xcache_unset($key);
		}
		
		public function flush() {
			return false;	//Not implemented in this cache engie
		}
	};
?>