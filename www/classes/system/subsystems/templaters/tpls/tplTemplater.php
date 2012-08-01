<?php

	/**
	 * @deprecated
	 * Используйте umiTemplater::create('XSLT');
	 */
	class tplTemplater extends singleton {
		protected function __construct() {}
		/**
		 * @static
		 * @param null $c
		 * @return umiTemplaterXSLT
		 */
		public static function getInstance($c = NULL) {
			return umiTemplater::create('TPL', null);
		}
		}

?>