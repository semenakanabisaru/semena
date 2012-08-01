<?php
/**
	* Обработчик события, которые опеределяет выполняемый метод в случае вызова события
*/
	class umiEventListener implements iUmiEventListener {
		protected	$eventId, $callbackModule, $callbackMethod,
				$isCritical,
				$priority;

		/**
			* Конструктор обработчика события, где событие определяется строковым id, а обработчик исполняемым модулем/методом.
			* @param Integer $eventId строковой id события
			* @param String $callbackModule название модуля, котороый будет выполнять обработку
			* @param String $callbackMethod название метода, котороый будет выполнять обработку
		*/
		public function __construct($eventId, $callbakModule, $callbackMethod) {
			$this->eventId = $eventId;
			$this->callbackModule = (string) $callbakModule;
			$this->callbackMethod = (string) $callbackMethod;

			$this->setPriority();
			$this->setIsCritical();

			umiEventsController::registerEventListener($this);
		}

		/**
			* Установить приоритет обработчика события.
			* @param Integer $priority = 5 приоритет от 0 до 9
		*/
		public function setPriority($priority = 5) {
			$priority = (int) $priority;

			if($priority < 0 || $priority > 9) {
				throw new coreException("EventListener priority can only be between 0 ... 9");
			}
			$this->priority = $priority;
		}

		/**
			* Узнать текущий приоритет
			* @return Integer приоритет обработчика событий
		*/
		public function getPriority() {
			return $this->priority;
		}

		/**
			* Установить критичность обработчика события.
			* Если событие критично, то при возникновении любого исключения в этом обработчике, цепочка вызова обработчиков событий будет прервана.
			* @param Boolean $isCritical = false критичность обработчика
		*/
		public function setIsCritical($isCritical = false) {
			$this->isCritical = (bool) $isCritical;
		}

		/**
			* Получить критичность обработчика события
			* @return Boolean критичность обработчика события
		*/
		public function getIsCritical() {
			return $this->isCritical;
		}

		/**
			* Узнать строковой id события, который прослушивает этот обработчик события
			* @return String строковой id события
		*/
		public function getEventId() {
			return $this->eventId;
		}

		/**
			* Узнать, какой модуль будет выполнять обработку события
			* @return String название модуля-обработчика
		*/
		public function getCallbackModule() {
			return $this->callbackModule;
		}

		/**
			* Узнать, какой метод будет выполнять обработку события
			* @return String название метода-обработчика
		*/
		public function getCallbackMethod() {
			return $this->callbackMethod;
		}
	};
?>