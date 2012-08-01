<?php
	interface iTemplater {
		public function init();

		public function loadLangs();
		public function putLangs($input);

		public function parseInput($input);
		public function parseResult();
		public function parseContent($arr, $templater);

		public function loadTemplates($filepath, $c, $args);

		public function parseMacros($macrosStr);
		public function executeMacros($macrosArr);

		public static function pushEditable($module, $method, $id);
		
		public function cleanUpResult($input);
	}
?>