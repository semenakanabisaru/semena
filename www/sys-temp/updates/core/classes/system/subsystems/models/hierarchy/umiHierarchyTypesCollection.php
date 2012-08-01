<?php
/**
 * Класс-коллекция, который обеспечивает управление иерархическими типами
*/
	class umiHierarchyTypesCollection extends singleton implements iSingleton, iUmiHierarchyTypesCollection {
		private $types = array();

		protected function __construct() {
			$this->loadTypes();
		}

		/**
			* Получить экземпляр коллекции
			* @return umiHierarchyTypesCollection экземпляр коллекции
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Получить тип поего id
			* @param Integer $type_id id типа
			* @return umiHierarchyType иерархический тип (класс umiHierarchyType), либо false
		*/
		public function getType($type_id) {
			if($this->isExists($type_id)) {
				return $this->types[$type_id];
			} else {
				return false;
			}
		}

		/**
			* Получить иерархический тип по его модулю/методу
			* @param String $name модуль типа
			* @param String $ext = false метод типа
			* @return umiHierarchyType иерархический тип (класс umiHierarchyType), либо false
		*/
		public function getTypeByName($name, $ext = false) {
			if($name == 'content' and $ext == 'page') $ext = false;
			foreach($this->types as $type) {
				if($type->getName() == $name && $ext === false) return $type;
				if($type->getName() == $name && $type->getExt() == $ext && $ext !== false ) return $type;
			}
			return false;
		}

		/**
			* Добавить новый иерархический тип
			* @param String $name модуль типа
			* @param String $title название типа
			* @param String $ext метод типа
			* @return Integer id иерархического типа (класс umiHierarchyType)
		*/
		public function addType($name, $title, $ext = "") {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			if($hierarchy_type  = $this->getTypeByName($name, $ext)) {
				$hierarchy_type->setTitle($title);
				return $hierarchy_type->getId();
			}

			$nameTemp = l_mysql_real_escape_string($name);
			$sql = "INSERT INTO cms3_hierarchy_types (name) VALUES('{$nameTemp}')";
			l_mysql_query($sql);

			$type_id = l_mysql_insert_id();

			$type = new umiHierarchyType($type_id);
			$type->setName($name);
			$type->setTitle($title);
			$type->setExt($ext);
			$type->commit();

			$this->types[$type_id] = $type;


			return $type_id;
		}

		/**
			* Удалить тип
			* @param Integer $type_id id иерархического типа, который нужно удалить
			* @return Boolean true, если тип удален успешно
		*/
		public function delType($type_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			if($this->isExists($type_id)) {
				unset($this->types[$type_id]);

				$type_id = (int) $type_id;
				$sql = "DELETE FROM cms3_hierarchy_types WHERE id = '{$type_id}'";
				l_mysql_query($sql);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Проверить, существует ли иерархический тип с таким id
			* @param Integer $typeId id типа
			* @return Boolean true если тип существует
		*/
		public function isExists($typeId) {
			if($typeId === false) return false;
			return (bool) array_key_exists($typeId, $this->types);
		}


		private function loadTypes() {
			$cacheFrontend = cacheFrontend::getInstance();

			$hierarchyTypeIds = $cacheFrontend->loadData('hierarchy_types');
			if(!is_array($hierarchyTypeIds)) {
				$sql = "SELECT  `id`, `name`, `title`, `ext` FROM `cms3_hierarchy_types` ORDER BY `name`, `ext`";
				$result = l_mysql_query($sql);
				$hierarchyTypeIds = array();
				while(list($id) = $row = mysql_fetch_row($result)) {
					$hierarchyTypeIds[$id] = $row;
				}
				$cacheFrontend->saveData('hierarchy_types', $hierarchyTypeIds, 3600);
			}

			foreach($hierarchyTypeIds as $id => $row) {
				$type = $cacheFrontend->load($id, 'element_type');
				if($type instanceof iUmiHierarchyType == false) {
					try {
						$type = new umiHierarchyType($id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($type, 'element_type');
				}
				$this->types[$id] = $type;
			}

			return true;
		}

		/**
			* Получить список всех иерархических типов
			* @return Array массив, где ключ это id типа, а значение экземпляр класса umiHierarchyType
		*/
		public function getTypesList() {
			return $this->types;
		}

		public function clearCache() {
			$keys = array_keys($this->types);
			foreach($keys as $key) unset($this->types[$key]);
			$this->types = array();
			$this->loadTypes();
		}
	}
?>