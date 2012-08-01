<?php
	interface iSingleton {
		public static function getInstance();
	};

	interface iUmiEntinty {
		public function getId();
		public function commit();
		public function update();

		public static function filterInputString($string);
	};
?>