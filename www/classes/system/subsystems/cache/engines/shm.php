<?php
	class shmCacheEngine implements iCacheEngine {
		private $connected = true, $segmentKey, $index = Array(1 => false);
		const segmentPermissions = 0666, segmentSize = 10485760; //10 Mb
		
		public function __construct() {
			$this->connected = true;
			$this->connect(9);
		}
		
		protected function connect($keyId) {
			$this->segmentKey = shm_attach($keyId, self::segmentSize, self::segmentPermissions);
			if($index = @shm_get_var($this->segmentKey, 1)) {
				$this->index = $index;
			} else {
				shm_put_var($this->segmentKey, 1, Array(0 => false));
			}
		}
		
		public function getIsConnected() {
			return (bool) $this->connected;
		}

		public function saveObjectData($key, $object, $expire) {
			return $this->saveRawData($key, $object, $expire);
		}
		
		public function saveRawData($key, $string, $expire) {
			$key = $this->transformKey($key);
			return shm_put_var($this->segmentKey, $key, $string);
		}
		
		public function loadObjectData($key) {
			return $this->loadRawData($key);
		}
		public function loadRawData($key) {
			$key = $this->transformKey($key);
			$res = @shm_get_var($this->segmentKey, $key);
			return $res;
		}
		
		public function delete($key) {
			$key = $this->transformKey($key);
			return shm_remove_var($this->segmentKey, $key);
		}
		
		public function flush() {
			shm_remove($this->segmentKey);
		}
		
		public function __destruct() {
			if($this->segmentKey) {
				shm_put_var($this->segmentKey, 1, $this->index);
				//shm_detach($this->segmentKey);
			}
		}
		
		protected function transformKey($key) {
			$index = &$this->index;
			$sz = sizeof($index);
			for($i = 1; $i < $sz; $i++) {
				if($index[$i] == $key) {
					return $i;
				}
			}
			$index[] = $key;
			$i = sizeof($index);
			return $i;
		}
	};
?>