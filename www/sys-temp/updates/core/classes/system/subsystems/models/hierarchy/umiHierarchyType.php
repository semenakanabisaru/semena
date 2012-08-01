<?php
/**
	* Базовый тип, используется:
	* 1. Для связывание страниц с соответствующим обработчиком (модуль/метод)
	* 2. Для категоризации типов данных
	* В новой терминологии getName()/getExt() значило бы getModule()/getMethod() соответственно
*/
	class umiHierarchyType extends umiEntinty implements iUmiEntinty, iUmiHierarchyType {
		private $name, $title, $ext;
		protected $store_type = "element_type";

		/**
			* Получить название модуля, отвечающего за этот базовый тип
			* @return String название модуля
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название базового типа
			* @return String название типа
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}
		
		public function getModule() {
			return $this->getName();
		}
		
		public function getMethod() {
			return $this->getExt();
		}

		/**
			* Получить название метода, отвечающего за этот базовый тип
			* @return String название метода
		*/
		public function getExt() {
			return $this->ext;
		}

		/**
			* Изменить название модуля, отвечающего за этот базовый тип
			* @param String $name название модуля
		*/
		public function setName($name) {
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Изменить название базового типа
			* @param String $title название типа
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "hierarchy-type-");
			$this->title = $title;
			$this->setIsUpdated();
		}

		/**
			* Изменить название метода, отвечающего за этот базовый тип
			* @param String $ext название метода
		*/
		public function setExt($ext) {
			$this->ext = $ext;
			$this->setIsUpdated();
		}

		/**
			* Загрузить информацию о базовом типа из БД
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, ext FROM cms3_hierarchy_types WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $ext) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->ext = $ext;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Сохранить внесенные изменения в БД
		*/
		protected function save() {
			$name = self::filterInputString($this->name);
			$title = self::filterInputString($this->title);
			$ext = self::filterInputString($this->ext);

			$sql = "UPDATE cms3_hierarchy_types SET name = '{$name}', title = '{$title}', ext = '{$ext}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);

			return true;
		}
	}
?>