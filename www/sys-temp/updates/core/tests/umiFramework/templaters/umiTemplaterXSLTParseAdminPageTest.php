<?php
	/**
	 * Тестирование процесса парсинга страницы XSLT шаблонизатором внутри админки
	 * @author Anton Prusov
	 */
	class umiTemplaterXSLTParseAdminPageTest extends umiTestCase {
		/**
		 * @var umiTemplaterXSLT
		 */
		protected static $templater;
		/**
		 * @var umiHierarchyElement
		 */
		protected static $page;
		protected static $globalVars = array();
		/**
		 * @var DOMDocument
		 */
		protected static $actualResult;
		protected static $xPath;

		public static function setUpBeforeClass() {
			self::$page = self::createPageFixture(__CLASS__, 'content', '');
			self::$page->content = 'Content';
			self::$page->title = 'Title';
			self::$page->meta_keywords = 'Keywords';
			self::$page->meta_descriptions = 'Description';
			self::$page->h1 = 'Header';
			self::$page->commit();

			self::controller()->setCurrentMode('admin');

			$_REQUEST['path'] = '/admin/content/edit/' . self::$page->getId() . "/";
			self::controller()->analyzePath();


			$_REQUEST['test_request_param'] = 'testRequestParamValue';
			$_SERVER['test_server_param'] = 'testServerParamValue';

			self::$templater = umiTemplater::create('XSLT', dirname(__FILE__) . "/data/umiTemplaterXSLTParsePageTest.xsl");
			self::$globalVars = self::controller()->getGlobalVariables();


			$actualXML = self::$templater->parse(self::$globalVars);

			self::$actualResult = new DOMDocument('1.0', 'utf-8');
			self::$actualResult->loadXML($actualXML);
			self::$xPath = new DOMXPath(self::$actualResult);
		}

		public static function tearDownAfterClass() {
			parent::tearDownAfterClass();
			// remove fixtures
			unset($_REQUEST['path']);
			unset($_REQUEST['test_request_param']);
			unset($_SERVER['test_server_param']);
		}

		public function _testStreamWork() {
			$result = file_get_contents('udata://system/get_module_tabs/content/content');
		}

		/**
		 * Тест на передачу $_REQUEST-параметра в шаблон
		 */
		public function testRequestParam() {
			$nl = self::$xPath->evaluate("//test[@name = 'testRequestParam']");
			$this->assertEquals('testRequestParamValue', $nl->item(0)->nodeValue, 'Не передаются $_REQUEST-параметры в шаблон.');
		}


	}
