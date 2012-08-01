<?php
	/**
	 * Тестирование процесса парсинга страницы TPL-шаблонизатором
	 * @author Anton Prusov
	 */
	class umiTemplaterTPLParsePageTest extends umiTestCase {
		/**
		 * @var umiTemplaterTPL
		 */
		protected static $templater;
		/**
		 * @var umiHierarchyElement
		 */
		protected static $page;

		protected static $globalVars = array();


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
			self::$globalVars = self::controller()->getGlobalVariables();

			self::$templater = umiTemplater::create('TPL', dirname(__FILE__) . "/data/umiTemplaterTPLParsePageTest.tpl");
			self::$templater->setScope(self::$page->getId());


		}

		public static function tearDownAfterClass() {
			parent::tearDownAfterClass();
			// remove fixtures
			unset($_REQUEST['path']);
			unset($_REQUEST['test_request_param']);
			unset($_SERVER['test_server_param']);

		}


		/**
		 * Тест на парсинг коротких макросов
		 */
		public function testParseShortMacroses() {
			$content = "%header%";
			$actualResult = self::$templater->parse(self::$globalVars, $content);
			$this->assertEquals('Header', $actualResult);
		}
		/**
		 * Тест на парсинг основного шаблона и макросов в нем
		 */
		public function testParsePage() {
			$templatesSource = self::$templater->getTemplatesSource();
			list($actualTpl) = self::$templater->getTemplates($templatesSource, 'common');
			$expectedTpl = file_get_contents($templatesSource);

			$this->assertEquals($expectedTpl, $actualTpl, 'Ошибка загрузки основного шаблона');

			$actualContent = self::$templater->parse(self::$globalVars, $actualTpl);

			$page = self::$page;
			$pid = $page->getId();
			$expectedContent = <<<EOF
Id: {$pid}
Keywords: Keywords
Description: Description
Title: UMI.CMS - Header
H1: Header
Content: Content
Keywords: Keywords
EOF;

			$this->assertEquals($expectedContent, $actualContent);
		}



	}
