<?php

	/**
	 * @deprecated
	 * Оставлено для соблюдения обратной совместимости
	 * Используйте umiTemplater
	 */
	class templater extends singleton {
		public static function getInstance($c = NULL) {
			return cmsController::getInstance()->getCurrentTemplater();
		}

		protected function __construct() {}

		public function init() {}

		public function loadLangs() {}

		public function putLangs($input) {}

		public function parseInput($input) {}
		public function parseResult() {}
		public function parseContent($arr, $templater) {}

		public function loadTemplates($filepath, $c, $args) {
			return array();
		}

		public function parseMacros($macrosStr) {
			return def_module::parseTPLMacroses($macrosStr);
		}
		public function executeMacros($macrosArr) {}

		public static function pushEditable($module, $method, $id) {
			return def_module::pushEditable($module, $method, $id);
		}

		final public static function getSomething($version_line = "pro", $forceHost = null) {
			return umiTemplater::getSomething($version_line, $forceHost);
		}


		public function cleanUpResult($input) {}
	}

?>