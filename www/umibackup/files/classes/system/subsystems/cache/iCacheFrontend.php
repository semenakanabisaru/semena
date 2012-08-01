<?php
	interface iCacheFrontend {
		public function save(umiEntinty $object, $objectType = "unknown", $expire = 86400);
		public function load($objectId, $objectType = "unknwon");
		
		public function saveSql($sqlString, $sqlResourse, $expire = 2);
		public function loadSql($sqlString);
		
		public function saveData($key, $data, $expire = 5);
		public function saveObject($key, $data, $expire = 5);
		public function saveElement($key, $data, $expire = 10);
		public function loadData($key);
		
		public function makeSleep($sleep = false);
		
		public function del($id, $type = false);
		public function deleteKey($key, $addSuffix = false);
		public function flush();
		
		public function getIsConnected();
		
		public static function getPriorityEnginesList($enabledOnly = false);
		public static function chooseCacheEngine($enginesList);
		
		public function switchCacheEngine($cacheEngineName);
		
		public function getCurrentCacheEngineName();
	};
	

	interface iCacheEngine {
		public function saveObjectData($key, $object, $expire);
		public function saveRawData($key, $string, $expire);
		
		public function loadObjectData($key);
		public function loadRawData($key);
		
		public function delete($key);
		
		public function flush();
		public function getIsConnected();
	};
?>