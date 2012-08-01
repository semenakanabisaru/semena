<?php
	interface iRedirects {
		public static function getInstance();
		public function add($source, $target, $status = 301);
		public function getRedirectsIdBySource($source);
		public function getRedirectIdByTarget($target);
		public function del($id);
		public function redirectIfRequired($currentUri);
		
		public function init();
	};
?>