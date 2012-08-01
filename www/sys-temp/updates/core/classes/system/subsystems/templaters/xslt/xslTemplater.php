<?php
	/**
	 * @deprecated
	 * Используйте umiTemplater::create('XSLT');
	 */
	class xslTemplater extends singleton {
		protected function __construct() {}
		/**
		 * @static
		 * @param null $c
		 * @return umiTemplaterXSLT
		 */
		public static function getInstance($c = NULL) {
			return umiTemplater::create('XSLT', null);
		}
	}
?>