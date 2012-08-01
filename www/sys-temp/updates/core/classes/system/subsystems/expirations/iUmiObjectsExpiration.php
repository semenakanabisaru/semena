<?php
	interface iUmiObjectsExpiration {
		public function run();
		
		public function set($objectId, $expiration = false);
		public function clear($objectId);
	};
?>