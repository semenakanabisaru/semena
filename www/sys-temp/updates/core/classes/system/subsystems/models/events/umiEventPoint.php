<?php
/**
	* Точка возникновения ошибки
*/
	class umiEventPoint implements iUmiEventPoint {
		protected $eventPointId, $eventParams = array(), $eventRefs = array();
		private $mode;
		private static $modes = array("before", "process", "after");
		private  $modules = array();
		

		/**
			* Конструктор, который принимает идентификатор события
			* @param String $eventPointId произвольный строковой id события. Используется для перехвата.
		*/
		public function __construct($eventPointId) {
			if(!$eventPointId) {
				throw new coreException("EventPoint id is required to create event point");
			}

			$this->eventPointId = $eventPointId;
			$this->mode = "process";
		}

		/**
			* Получить id события
			* @return String строковой id события
		*/
		public function getEventId() {
			return $this->eventPointId;
		}

		/**
			* Добавить параметр к событию. Позволяет передавать в обработчики события параметры
			* @param String $paramName строковой id параметра
			* @param Mixed $paramValue=NULL значение параметра
		*/
		public function setParam($paramName, $paramValue = NULL) {
			$this->eventParams[$paramName] = $paramValue;
		}

		/**
			* Получить параметр события
			* @param String $paramName строковой id события
			* @return Mixed значение параметра
		*/
		public function getParam($paramName) {
			if(array_key_exists($paramName, $this->eventParams)) {
				return $this->eventParams[$paramName];
			} else {
				return false;
			}
		}

		/**
			* Добавить параметр-ссылку на значение, чтобы можно было из обработчика изменить значение переменной в контексте вызова события
			* @param String $refName строковой id параметра-ссылки
			* @param &Mixed ссылка на значение
		*/
		public function addRef($refName, &$refValue) {
			$this->eventRefs[$refName] = &$refValue;
		}

		/**
			* Получить ссылку на значение из контекста вызова события
			* @param String $refName строковой id параметра-ссылки
			* @return &Mixed ссылка на значение
		*/
		public function &getRef($refName) {
			if(array_key_exists($refName, $this->eventRefs)) {
				return $this->eventRefs[$refName];
			} else {
				$nl = NULL;
				return $nl;
			}
		}

		/**
			* Установить модули, для которых вызывать событие
		*/
		public function setModules($modules = array()) {
			$this->modules = (array) $modules;
		}

		/**
			* Установить режим вызова события
			* @param String $mode="process" режим вызова ("before"/"process"/"after")
		*/
		public function setMode($mode = "process") {
			$mode = wa_strtolower($mode);

			if(!in_array($mode, self::$modes)) {
				throw new coreException("Unknown eventPoint mode \"{$mode}\"");
			}

			$this->mode = $mode;
		}

		/**
			* Получить режим вызова события
			* @return String режим вызова события ("before"/"process"/"after")
		*/
		public function getMode() {
			return $this->mode;
		}
		
		/**
			* Запустить событие
			* @return Array лог выполненных event'ов
		*/
		public function call() {
			return umiEventsController::getInstance()->callEvent($this, $this->modules);
		}
	};
?>