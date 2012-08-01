<?php

	error_reporting(-1);
	ini_set('display_errors', 1);

	$_SERVER['SCRIPT_NAME']='/'.basename(__FILE__);
	$_SERVER['SCRIPT_FILENAME']=__FILE__;

	$_SERVER['HTTP_HOST'] = 'localhost';
	$_SERVER['SERVER_ADDR'] = '127.0.0.1';

	require_once(dirname(__FILE__).'/../standalone.php');


	class umiTestCase extends PHPUnit_Framework_TestCase {
		protected static $fixtures = array();

		protected static function clearFixtures() {
			//return false;
			foreach (self::$fixtures as $fixture) {
				switch (true) {
					case $fixture instanceof umiHierarchyElement : {
						self::hierarchy()->delElement($fixture->getId());
					} break;
				}
			}
			self::hierarchy()->removeDeletedAll();
		}

		/**
		 * Получить иерархию
		 * @return umiHierarchy
		 */
		protected static function hierarchy() {
			return umiHierarchy::getInstance();
		}

		/**
		 * Получить коллекцию объектов
		 * @return umiObjectsCollection
		 */
		protected static function objects() {
			return umiObjectsCollection::getInstance();
		}

		/**
		 * Получить коллекцию прав
		 * @return permissionsCollection
		 */
		protected static function permissions() {
			return permissionsCollection::getInstance();
		}

		/**
		 * Получить коллекцию иерархических типов
		 * @return umiHierarchyTypesCollection
		 */
		protected static function hierarchyTypes() {
			return umiHierarchyTypesCollection::getInstance();
		}

		/**
		 * Получить коллекцию объектных типов
		 * @return umiObjectTypesCollection
		 */
		protected static function objectTypes() {
			return umiObjectTypesCollection::getInstance();
		}

		/**
		 * @static
		 * Выполнить sql-запрос, получить результат в виде массива
		 *
		 * @param string $sql
		 *
		 * @return array()
		 */
		protected static function queryResult($sql) {
			$result = array();
			$queryResult = l_mysql_query($sql);
			while ($row = mysql_fetch_assoc($queryResult)) {
				$result[] = $row;
			}

			return $result;
		}
		/**
		 *  Получить cmsController
		 * @return cmsController
		 */
		protected static function controller() {
			return cmsController::getInstance();
		}

		/**
		 * Создает фикстуру umiHierarchyElement
		 *
		 * @param string $testCaseName - имя тест-кейса
		 * @param string $module - модуль
		 * @param string $method - метод
		 * @param int $parentId - родитель страницы, если не задано - корень дефолтного домена
		 * @param bool|int $objectTypeId - id объектного типа
		 * @param bool|string $pageName - имя страницы, если не задано, создается автоматически
		 *
		 * @return bool|umiHierarchyElement
		 */
		protected static function createPageFixture($testCaseName, $module, $method, $parentId = 0, $objectTypeId = false, $pageName = false) {
			$hierarchyType = self::hierarchyTypes()->getTypeByName($module, $method);
			$pageName = $pageName ? $pageName : 'Page for "' . $testCaseName . '"';
			$pageId = self::hierarchy()->addElement($parentId, $hierarchyType->getId(), $pageName, uniqid($pageName), $objectTypeId);
			self::permissions()->setDefaultPermissions($pageId);
			$page = self::hierarchy()->getElement($pageId);
			$page->setIsActive(true);
			return self::$fixtures[] = $page;
		}

		public static function tearDownAfterClass() {
			self::clearFixtures();
		}
	}


	// select buffer
	OutputBuffer::current('CLIOutputBuffer');

?>