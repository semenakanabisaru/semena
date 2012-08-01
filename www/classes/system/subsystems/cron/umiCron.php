<?php
	/**
	* @desc Класс, позволяющий запускать действия по расписанию
	*/
	class umiCron implements iUmiCron {
		protected	$statFile, $buffer = array(), $logs = NULL;
		
		private $modules =array();
		
		/**
		* @desc Конструктор
		*/		
		public function __construct() {
			$config = mainConfiguration::getInstance();
			$this->statFile = $config->includeParam('system.runtime-cache') . 'cron';
		}
		
		/**
		* @desc Деструктор
		*/
		public function __destruct() {
			$this->setLastCall();
		}

		/**
		* @desc Запуск обработки событий
		* @return Int (зарезервировано)
		*/
		public function run() {
			$lastCallTime = $this->getLastCall();
			$currCallTime = time();
			
			$result = $this->callEvent($lastCallTime, $currCallTime);
			$this->setLastCall();
			return $result;
		}
		
		/**
		* @desc Возвращает буффер
		* @return Mixed буфер
		*/
		public function getBuffer() {
			return $this->buffer;
		}
		
		/**
		* @desc Установить модуль, для которого выполнить крон, если пустое значение - то пройти по всем модулям
		*/
		public function setModules($modules = array()) {
			$this->modules = (array)$modules;
		}
		
		/**
		* @desc Получить лог выполнения umiEventListener'ов
		* @return Array массив из объектов класса umiEventListener. в ключе executed тоработавшие, в failed - завершенные с ошибкой
		*/
		public function getLogs() {
			return $this->logs;
		}
		
		public function getParsedLogs() {
			$result = "";
			$logs = $this->getLogs();
			
			if(sizeof($logs['executed'])) {
				$result .= "Executed event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['executed']);
				$result .= "\n";
			}
			
			if(sizeof($logs['failed'])) {
				$result .= "Failed event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['failed']);
				$result .= "\n";
			}
			
			if(sizeof($logs['breaked'])) {
				$result .= "Breaked event handlers:\n";
				$result .= $this->getParsedLogsByArray($logs['breaked']);
				$result .= "\n";
			}
			
			return $result ? $result : "No event handlers found";
		}
		
		protected function getParsedLogsByArray($arr) {
			$result = "";
			for($i = 0; $i < sizeof($arr); $i++) {
				$eventPoint = $arr[$i];
				$module = $eventPoint->getCallbackModule();
				$method = $eventPoint->getCallbackMethod();
				$priority = $eventPoint->getPriority();
				$critical = $eventPoint->getIsCritical() ? "critical" : "not critial";
				
				$n = $i + 1;
				$result .= <<<END
	{$n}. {$module}::{$method} (umiEventPoint), priority = {$priority}, {$critical}

END;
			}
			return $result;
		}

        /**
        * @desc Возвращает время последнего запуска
        * @return Int Time Stamp последнего запуска
        */
		protected function getLastCall() {
			if(is_file($this->statFile)) {
				return filemtime($this->statFile);
			} else {
				$this->setLastCall();
				return time();
			}
		}
		
		/**
		* @desc Меняет время последнего запуска на текущее
		* @return Boolean true - в случае успеха, false - в случае ошибки
		*/
		protected function setLastCall() {
			if (!$res = @touch($this->statFile)) {
				$res = @touch($this->statFile);
			}
			return $res;
		}
		
		
		protected function callEvent($lastCallTime, $currCallTime) {
			static $counter = 0;
		
			$event = new umiEventPoint("cron");
			$event->setMode("process");
			$event->setModules( $this->modules );
			$event->setParam("lastCallTime", $lastCallTime);
			$event->setParam("currCallTime", $currCallTime);
			$event->addRef("buffer", $this->buffer);
			$event->addRef("counter", $counter);
			
			$this->logs = $event->call();

			return $counter;
		}
	};
?>
