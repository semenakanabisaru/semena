<?php
/**
	* Класс для управления табами в админке модулей.
	* Необходим для динамического изменния количества табов в модулях.
	* Должен быть доступен для подключаемых библиотек модуля при инициализации
	* При инициализации класса создается 2 экземпляра: 'common' и 'config', которые
	* содержат модифицируемый список табов для админки модуля и для конфигурации модуля.
	* После инициализации из шаблона вызывается макрос, который в зависимости от текущей страницы выбирает
	* необходимый экземпляр класса и выводит список табов для отрисовки.
*/
	class adminModuleTabs implements iAdminModuleTabs {
		private $type;
		private $tabs = array();
		/**
			* Конструктор, создает класс определенного типа.
			* @param String $type = 'common' тип содержимого. Либо 'common', либо 'config'.
		*/
		public function __construct($type = 'common') {
			if ($type == 'common' || $type == 'config') {
				$this->type = $type;
			} else {
				throw new coreException("Tabs type \"{$type}\" is unknown.");
			}
		}
		
		/**
			* Добавить новый таб для метода $methodName
			* @param String $methodName название метода класса-модуля
			* @param Array $aliases = NULL список методов-алиасов, при котором данный таб будет считаться активным (помимо $methodName)
			* @return Boolean результат операции
		*/
		public function add($methodName, $aliases = array()) {
			$this->tabs[$methodName] = $aliases;
		}
		
		/**
			* Получить список алиасов для таба $methodName
			* @param String $methodName название метода класса-модуля
			* @return Array|Boolean массив алиасов, либо false в случае ошибки
		*/
		public function get($methodName) {
			if (isset($this->tabs[$methodName])) {
				return $this->tabs[$methodName];
			}
			return false;
		}
		
		/**
			* Получить основной метод таба по методу-алиасу, либо по его основному методу
			* @param String $methodOrAlias 
			* @return String $methodName, либо false в случае ошибки
		*/

		public function getTabNameByAlias($method) {
			if (isset($this->tabs[$method])) return $method;

			foreach ($this->tabs as $tabMethod => $aliases) {
				if (in_array($method, $aliases)) return $tabMethod;
			}

			return false;
		}

		
		/**
			* Удалить таб метода $methodName из списка табов
			* @param String $methodName название метода класса-модуля
			* @return Boolean результат операции
		*/
		public function remove($methodName) {
			if (isset($this->tabs[$methodName])) {
				unset($this->tabs[$methodName]);
				return true;
			}
			return false;
		}
		
		/**
			* Получить список всех табов
			* @return Array массив, список всех табов
		*/
		public function getAll() {
			return (sizeof($this->tabs) > 1) ? $this->tabs : array();
		}
	};
?>