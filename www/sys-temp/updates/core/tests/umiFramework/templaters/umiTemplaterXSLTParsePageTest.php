<?php
	/**
	 * Тестирование процесса парсинга страницы XSLT шаблонизатором
	 * @author Anton Prusov
	 */
	class umiTemplaterXSLTParsePageTest extends umiTestCase {
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


			$_REQUEST['path'] = '/' . self::$page->getAltName() . "/";
			self::controller()->analyzePath();


			$_REQUEST['test_request_param'] = 'testRequestParamValue';
			$_SERVER['test_server_param'] = 'testServerParamValue';

			self::$templater = umiTemplater::create('XSLT', dirname(__FILE__) . "/data/umiTemplaterXSLTParsePageTest.xsl");
			self::$templater->setScope(self::$page->getId());

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

		/**
		 * Тест на передачу $_REQUEST-параметра в шаблон
		 */
		public function testRequestParam() {
			$nl = self::$xPath->evaluate("//test[@name = 'testRequestParam']");
			$this->assertEquals('testRequestParamValue', $nl->item(0)->nodeValue, 'Не передаются $_REQUEST-параметры в шаблон.');
		}

		/**
		 * Тест на передачу $_SERVER-параметра в шаблон
		 */
		public function testServerParam() {
			$nl = self::$xPath->evaluate("//test[@name = 'testServerParam']");
			$this->assertEquals('testServerParamValue', $nl->item(0)->nodeValue, 'Не передаются $_SERVER-параметры в шаблон.');
		}


		/**
		 * Тест проверяет что при повторном парсинге шаблона с теми же данными мы получаем
		 * один и тот же результат (актуально для кэша и тп)
		 */
		public function testRepeatParse() {
			$prevResult = self::$actualResult;

			$nextResultXML = self::$templater->parse(self::$globalVars);
			$nextResult = new DOMDocument('1.0', 'utf-8');
			$nextResult->loadXML($nextResultXML);

			$this->assertEquals($nextResult->saveXML(), $prevResult->saveXML());
		}
		/**
		 * Тест на парсинг простых свойств страницы
		 */
		public function testParseSimpleProperies() {
			// title
			$nl = self::$xPath->evaluate("//test[@name = 'testParseProperies']/prop[@name = 'title']");
			$this->assertNotEmpty($nl->item(0));
			$this->assertEquals(self::$page->title, $nl->item(0)->nodeValue);
			// keywords
			$nl = self::$xPath->evaluate("//test[@name = 'testParseProperies']/prop[@name = 'meta_keywords']");
			$this->assertNotEmpty($nl->item(0));
			$this->assertEquals(self::$page->meta_keywords, $nl->item(0)->nodeValue);
			// descriptions
			$nl = self::$xPath->evaluate("//test[@name = 'testParseProperies']/prop[@name = 'meta_descriptions']");
			$this->assertNotEmpty($nl->item(0));
			$this->assertEquals(self::$page->meta_descriptions, $nl->item(0)->nodeValue);
			// header
			$this->assertNotEmpty($nl->item(0));
			$nl = self::$xPath->evaluate("//test[@name = 'testParseProperies']/prop[@name = 'h1']");
			$this->assertEquals(self::$page->h1, $nl->item(0)->nodeValue);
		}


		/**
		 * Тест на отработку контекстозависимых TPL-макросов (%prop_name%),
		 * используемых в свойствах самой страницы
		 */
		public function testParseTplScopeMacroses() {
			if (defined('XML_MACROSES_DISABLE') && XML_MACROSES_DISABLE) {
				throw new Exception("Не могу протестировать обработку TPL-макросов, включена опция XML_MACROSES_DISABLE");
			}

			self::$page->content = '%meta_keywords%,%pid%';
			self::$page->commit();


			$actualXML = self::$templater->parse(self::$globalVars);
			$actualResult = new DOMDocument('1.0', 'utf-8');
			$actualResult->loadXML($actualXML);
			$xPath = new DOMXPath($actualResult);

			$nl = $xPath->evaluate("//test[@name = 'testParseProperies']/prop[@name = 'content']");
			$this->assertEquals(self::$page->meta_keywords. "," . self::$page->getId(), $nl->item(0)->nodeValue);
		}


	}
