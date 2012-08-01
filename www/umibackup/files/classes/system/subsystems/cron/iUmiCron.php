<?php
	interface iUmiCron {
		public function run();
		public function getBuffer();
		public function setModules();
		
		public function getLogs();
		public function getParsedLogs();
	};
?>