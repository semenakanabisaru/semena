<?php
	class umiTemplaterTPLTest extends umiTestCase {

		/**
		 * @var umiTemplaterTPL
		 */
		protected static $templater;

		public static function setUpBeforeClass() {
			self::$templater = umiTemplater::create('TPL', dirname(__FILE__) . "/data/umiTemplaterTPLTest.tpl");
		}

		public function testValidInstance() {
			$this->assertInstanceOf('umiTemplaterTPL', self::$templater);
		}

		/**
		 * umiTemplaterTPL::getTemplates()
		 */
		public function testGetTemplates() {
			$sourceFile = self::$templater->getTemplatesSource();
			$actualResult = self::$templater->getTemplates($sourceFile, 'template1', 'template2', 'template3');
			$expectedResult = array(0 => 'template1', 1 => 'template2', 2 => 'template3');
			$this->assertEquals($expectedResult, $actualResult);
		}

	}


?>