<?php
	/**
	 * Тестирование модели поиска
	 * @author guzhova
	 */
	class searchModelTest extends umiTestCase {
		private static $testFixtures = array();

		public static function searchModel() {
			return searchModel::getInstance();
		}

		/**
		 * @static
		 * Setup для всего тест-кейса
		 */
		public static function setUpBeforeClass() {
			// cleanup index
			self::searchModel()->truncate_index();

			// create fixtures
			$page1 = self::createPageFixture(__CLASS__, 'content', '');
			$page1->setName(__CLASS__ . ': Страница не в индексе');
			$page1->setValue('is_unindexed', 1);
			$page1->setValue('content', 'НеВИндексе');
			$page1->commit();

			$page2 = self::createPageFixture(__CLASS__, 'content', '');
			$page2->setName(__CLASS__ . ': Страница с ожидаемым весом слов 2');
			$page2->setValue('content', 'Тестовое словосочетание, Тестовое словосочетание, на из под');
			$page2->commit();

			$page3 = self::createPageFixture(__CLASS__, 'content', '');
			$page3->setValue('content', 'Тестовые словосочетания');
			$page3->setName(__CLASS__ . ': Страница с ожидаемым весом слов 1');
			$page3->commit();

			$page4 = self::createPageFixture(__CLASS__, 'content', '');
			$page4->setValue('content', 'Тестовое словосочетание');
			$page4->setValue('h1', 'Тестовое словосочетание');
			$page4->setValue('title', 'Тестовое словосочетание');
			$page4->setValue('meta_keywords', 'Тестовое словосочетание');
			$page4->setValue('meta_descriptions', 'Тестовое словосочетание');
			$page4->setValue('tags', 'Тестовое словосочетание');

			$page4->setName(__CLASS__ . ': Страница с ожидаемым весом слов 20');
			$page4->commit();

			self::$testFixtures = array($page1, $page2, $page3, $page4);
		}

		/**
		 * @static
		 * tearDown для всего тест-кейса
		 */
		public static function tearDownAfterClass() {
			// обязательно вызываем
			// общий tearDown, сам чистит фикстуры, созданные через методы umiTestCase
			parent::tearDownAfterClass();
		}


		public function testValidInstance() {
			$this->assertInstanceOf('iSearchModel', self::searchModel());
		}


		/**
		 * Проверяем что правильно проидексировались слова
		 */
		public function testIndexWords() {
			$expectedResult = array(
				"под",
				"словосочетание",
				"словосочетания",
				"тестовое",
				"тестовые"
			);

			sort($expectedResult);

			$result = $this->queryResult('SELECT word FROM cms3_search_index_words');
			$actualResult = array();
			foreach ($result as $row) {
				$actualResult[] = $row['word'];
			}

			$this->assertEquals($expectedResult, $actualResult, 'Проблема с индексацией');
		}

		/**
		 * Проверяем что нужные страницы проиндексированы
		 * Проверяем правильность вычисления веса слов
		 * searchModel::index_item()
		 */
		public function testIndexPages() {
			$page2Id = self::$testFixtures[1]->getId();
			$page3Id = self::$testFixtures[2]->getId();
			$page4Id = self::$testFixtures[3]->getId();

			$expectedResult = <<<EOF
{$page2Id},1,под
{$page2Id},2,словосочетание
{$page2Id},2,тестовое
{$page3Id},1,словосочетания
{$page3Id},1,тестовые
{$page4Id},17,словосочетание
{$page4Id},17,тестовое
EOF;
			$sql = <<<EOF
				SELECT si.rel_id, si.weight, w.word
				FROM cms3_search_index as si
					INNER JOIN cms3_search_index_words as w ON si.word_id = w.id
				ORDER BY si.rel_id, w.word

EOF;
			$queryResult = $this->queryResult($sql);
			$actualResult = array();
			foreach ($queryResult as $row) {
				$actualResult[] = $row['rel_id'] . "," . $row['weight'] . "," . $row['word'];
			}
			$actualResult = implode("\n", $actualResult);

			$this->assertEquals($expectedResult, $actualResult, "Веса проиндексированных страниц не соответсвуют ожиданиям");
		}

		/**
		 * Проверяем правильность возврата результатов поиска
		 * searchModel::runSearch()
		 */
		public function testRunSearch() {

			$this->assertEquals(array(), self::searchModel()->runSearch(''), 'Поиск по путой строке');
			$this->assertEquals(array(), self::searchModel()->runSearch('НеВИндексе'), "Страница не в индексе попала в результат поиска");

			$this->assertEquals(array(self::$testFixtures[1]->getId()), self::searchModel()->runSearch('тест под'), 'Поиск по сочетанию');

			$this->assertEquals(self::searchModel()->runSearch('Тест'), self::searchModel()->runSearch('тестовое'));

			$expectedResult = array(self::$testFixtures[3]->getId(), self::$testFixtures[1]->getId(), self::$testFixtures[2]->getId());
			$this->assertEquals($expectedResult, self::searchModel()->runSearch('тест'), 'Порядок вывода страниц по запросу "тест" не соответсвует ожиданиям');

		}

	}

?>