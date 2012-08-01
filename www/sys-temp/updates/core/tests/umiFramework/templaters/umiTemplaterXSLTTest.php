<?php
	class umiTemplaterXSLTTest extends umiTestCase {

		/**
		 * @var umiTemplaterXSLT
		 */
		protected static $templater;

		public static function setUpBeforeClass() {
			self::$templater = umiTemplater::create('XSLT', dirname(__FILE__) . "/data/umiTemplaterXSLTTest.xsl");
		}

		public static function tearDownAfterClass() {

		}

		public function testValidInstance() {
			$this->assertInstanceOf('umiTemplaterXSLT', self::$templater);
		}

		/**
		 * Тест на получение глобальных переменных
		 */
		public function testGlobalVariables() {
			$vars = self::controller()->getGlobalVariables();
			$this->assertArrayHasKey('@module', $vars);
		}

		/**
		 * umiTemplaterXSLT::getTemplates()
		 */
		public function testGetTemplates() {
			$sourceFile = self::$templater->getTemplatesSource();
			$actualResult = self::$templater->getTemplates($sourceFile, 'template1', 'template2', 'template3');

			$expectedResult = array(
				0 => 'file://' . $sourceFile . "#template1",
				1 => 'file://' . $sourceFile . "#template2",
				2 => 'file://' . $sourceFile . "#template3"
			);
			$this->assertEquals($expectedResult, $actualResult);
		}

	}


?>