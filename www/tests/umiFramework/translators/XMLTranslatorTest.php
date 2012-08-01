<?php
	class XMLTranslatorTest extends  umiTestCase {
		/**
		 * @var umiHierarchyElement
		 */
		protected static $page;

		public static function setUpBeforeClass() {
			self::$page = self::createPageFixture(__CLASS__, 'content', '');
			self::$page->content = 'Content';
			self::$page->title = 'Title';
			self::$page->meta_keywords = 'Keywords';
			self::$page->meta_descriptions = 'Description';
			self::$page->h1 = 'Header';
			self::$page->commit();
		}

		/**
		 * Тест на идентичность транслирования одной и той же страницы (проверка кэша)
		 */
		public function testMultiTranslatePage() {
			$domXML1 = new DOMDocument("1.0", "utf-8");
			$domXML1->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode1 = $domXML1->appendChild($domXML1->createElement("result"));
			$rootNode1->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$domXML2 = new DOMDocument("1.0", "utf-8");
			$domXML2->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode2 = $domXML2->appendChild($domXML2->createElement("result"));
			$rootNode2->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$translator1 = new xmlTranslator($domXML1);
			$translator1->translateToXml($rootNode1, array('full:page' => self::$page));

			$translator2 = new xmlTranslator($domXML2);
			$translator2->translateToXml($rootNode2, array('full:page' => self::$page));

			$this->assertEquals($domXML2->saveXML(), $domXML1->saveXML());
		}
	}


?>