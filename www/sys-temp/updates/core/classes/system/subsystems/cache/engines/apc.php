<?php
	class apcCacheEngine implements iCacheEngine {
		private $connected = true;
		
		public function __construct() {
			$this->connected = function_exists('apc_fetch');
		}
		
		public function getIsConnected() {
			return (bool) $this->connected;
		}

		public function saveObjectData($key, $object, $expire) {
			return $this->saveRawData($key, $object, $expire);
		}
		
		public function saveRawData($key, $string, $expire) {
			return apc_store($key, $string, $expire);
		}
		
		public function loadObjectData($key) {
			return $this->loadRawData($key);
		}
		public function loadRawData($key) {
			return apc_fetch($key);
		}
		
		public function delete($key) {
			return apc_delete($key);
		}
		
		public function flush() {
			$result = apc_clear_cache();
			$result &= apc_clear_cache('user');
			return $result;
		}
	};
?>