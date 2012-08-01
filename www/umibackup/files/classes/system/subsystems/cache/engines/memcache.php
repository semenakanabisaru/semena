<?php
	class memcacheCacheEngine implements iCacheEngine {
		protected $memcache, $mode, $enabled = false, $connected = false, $compress = false, $is_sleep = false;
		public static $cacheMode = false;
		
		public function __construct() {
			$md = (defined("SERVER_ID")) ? SERVER_ID : getServer('SERVER_ADDR');
			if (defined("MEMCACHE_COMPRESSED")) {
				$this->compress = MEMCACHE_COMPRESSED;
			}
			$this->mode = md5($md) . "_";
			$this->enabled = $this->connect();
		}
		
		protected function connect() {
			if(class_exists("Memcache")) {
				if(function_exists("getMemcached")) {
					if($memcache = getmemcached()) {
						$this->memcache = $memcache;
						$this->connected = true;
						return true;
					}
				}
				$memcache = new Memcache;
				
				$conf = mainConfiguration::getInstance();
				
				$host = $conf->get('cache', 'memcache.host');
				if(empty($host)) $host = 'localhost';

				$port = $conf->get('cache', 'memcache.port');
				if (is_null($port)) $port = '11211';

				if($memcache->connect($host, $port)) {
					$this->memcache = $memcache;
					$this->connected = true;
					return true;
				}
				return false;
			} else {
				return false;
			}
		}
		
		public function getIsConnected() {
			return (bool) $this->connected;
		}

		public function saveObjectData($key, $object, $expire) {
			return $this->saveRawData($key, $object, $expire);
		}
		
		public function saveRawData($key, $string, $expire) {
			return $this->memcache->set($key, $string, $this->compress, $expire);
		}
		
		public function loadObjectData($key) {
			return $this->loadRawData($key);
		}
		public function loadRawData($key) {
			return $this->memcache->get($key);
		}
		
		public function delete($key) {
			return $this->memcache->delete($key, 0);
		}
		
		public function flush() {
			return $this->memcache->flush();
		}
	};
?>