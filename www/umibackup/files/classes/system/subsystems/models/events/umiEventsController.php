<?php
/**
	* Класс для регистрации и управления вызовами событий
*/
	class umiEventsController /*extends singleton*/ implements /*iSingleton,*/ iUmiEventsController {
		protected static $eventListeners = Array();
		private   static $oInstance  = null;

		protected function __construct() {
			$this->loadEventListeners();
		}

		/**
			* Вернуть экземпляр коллекции
			* @return umiEventsController
		*/
		public static function getInstance() {
			if(self::$oInstance == null) {
				self::$oInstance = new umiEventsController();
			}
			return self::$oInstance;
		}


		protected function loadEventListeners() {
			$modules_keys = regedit::getInstance()->getList("//modules");

			foreach($modules_keys as $arr) {
				$module = $arr[0];

				$this->loadModuleEventListeners($module);
			}
		}


		protected function loadModuleEventListeners($module) {
			$path = CURRENT_WORKING_DIR."/classes/modules/{$module}/events.php";
			$path_custom = CURRENT_WORKING_DIR."/classes/modules/{$module}/custom_events.php";

			$this->tryLoadEvents($path_custom);
			$this->tryLoadEvents($path);
		}


		protected function tryLoadEvents($path) {
			if(file_exists($path)) {
				require $path;
				return true;
			} else {
				return false;
			}
		}


		protected function searchEventListeners($eventId) {
			$result = array();

			foreach(self::$eventListeners as $eventListener) {
				if($eventListener->getEventId() == $eventId) {
					$result[] = $eventListener;
				}
			}

			return $result;
		}


		protected function executeCallback($callback, $eventPoint) {
			$module = $callback->getCallbackModule();
			$method = $callback->getCallbackMethod();

			if($module_inst = cmsController::getInstance()->getModule($module)) {
				$module_inst->$method($eventPoint);
			} else {
				throw new coreException("Cannot find module \"{$module}\"");
			}
		}

		/**
			* Вызвать событие и выполнить все обработчики, которые его слушают
			* @param umiEventPoint $eventPoint точка входа в событие
			* @return Array лог обработанных callback'ов
		*/
		public function callEvent(iUmiEventPoint $eventPoint, $allowed_modules = array()) {
			$eventId = $eventPoint->getEventId();
			$callbacks = $this->searchEventListeners($eventId);
			$callbacks = $this->sortCallbacksByPriority($callbacks);
			
			$logs = array('executed' => array(), 'failed' => array(), 'breaked' => array());
			
			
			foreach($callbacks as $callback) {//var_dump(($allowed_modules));
			
				if(!empty($allowed_modules) && !in_array($callback->getCallbackModule(), $allowed_modules)) 
				{
					continue;
				}				
				
				try {
					$this->executeCallback($callback, $eventPoint);
					$logs['executed'][] = $callback;
				} catch (baseException $e) {
					$logs['failed'][] = $callback;
					
					if($callback->getIsCritical()) {
						throw $e;
					} else {
						continue;
					}
				} catch (breakException $e) {
					$logs['breaked'][] = $callback;
					break;
				}
			}
			
			return $logs;
		}
		
		/**
			* Отсортировать callback'и по приоритету
			* @params Array $callback массив коллбеков
			* @return Array отсортированный по приоритету массив коллбеков
		*/
		protected function sortCallbacksByPriority($callbacks) {
			$result = Array();
			$temp = Array();
			
			foreach($callbacks as $callback) {
				$temp[$callback->getPriority()][] = $callback;
			}
			ksort($temp);
			foreach($temp as $callbackArray) {
				foreach($callbackArray as $callback) {
					$result[] = $callback;
				}
			}
			return $result;
		}

		/**
			* Зарегистрировать в коллекции обработчик события
			* @param umiEventListener $eventListener обработчик события
		*/
		static public function registerEventListener(iUmiEventListener $eventListener) {
			self::$eventListeners[] = $eventListener;
		}
	};
?>