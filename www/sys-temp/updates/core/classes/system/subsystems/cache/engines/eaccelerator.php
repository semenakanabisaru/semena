<?php
	class eacceleratorCacheEngine implements iCacheEngine {
		protected $connected = false;
		
		public function __construct() {
			$this->connected = true;	//TODO: Temp
		}
		
		public function getIsConnected() {
			return (bool) $this->connected;
		}
		
		public function saveObjectData($key, $object, $expire) {
			return eaccelerator_put($key, serialize($object), $expire);
		}
		
		public function saveRawData($key, $string, $expire) {
			return eaccelerator_put($key, $string, $expire);
		}
		
		public function loadObjectData($key) {
			$result = eaccelerator_get($key);
			if($result) {
				return unserialize($result);
			} else {
				return false;
			}
		}
		public function loadRawData($key) {
			return eaccelerator_get($key);
		}
		
		public function delete($key) {
			return eaccelerator_rm($key);
		}
		
		public function flush() {
			$res = eaccelerator_clear();
			
			//Be sure, that cache will be removed even if we're not in eaccelerator.admin_path
			$keys = eaccelerator_list_keys();
			foreach($keys as $key) {
				eaccelerator_rm(substr($key['name'], 1));
			}
			
			return $res;
		}
	};
?>