<?php

	interface iUmiLogger {

		
		public function __construct($logDir = "./logs/");

		public function pushGlobalEnviroment();

		public function push($mess, $enableTimer = true);

		public function log();

		public function save();

		public function get();
	};

?>