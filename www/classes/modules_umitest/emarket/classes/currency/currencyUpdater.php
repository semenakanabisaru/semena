<?php
	abstract class currencyUpdater {
		protected $currencyObject;
		
		public function __construct(iUmiObject $currencyObject) {
			$this->currencyObject = $currencyObject;
		}
		
		protected function getRate() {
			return $this->currencyObject->rate;
		}
		
		public function update() {
			$object = $this->currencyObject;
			$rate = $this->getRate();

			$object->rate = $rate;
			$object->commit();
			
			return $rate;
		}
		
		final static public function get(iUmiObject $currencyObject) {
			$codeName = $currencyObject->codename;
			if(!$codeName) {
				throw new coreException("Can't get currency codename");
			}
			$codeName = strtolower($codeName);
			
			$config = mainConfiguration::getInstance();
			$includePath = $config->includeParam('system.default-module') . 'emarket/classes/currency/updaters/' . $codeName . '.php';
			
			if(is_file($includePath) == false) {
				throw new coreException("Can't load updater file \"{$includePath}\"");
			}
			
			require $includePath;
			
			$className = $codeName . 'CurrencyUpdater';
			if(class_exists($className)) {
				$updater = new $className($currencyObject);
				if($updater instanceof currencyUpdater) {
					return $updater;
				} else {
					throw new coreException("Class \"{$className}\" MUST be extended from currencyUpdater class");
				}
			} else {
				throw new coreException("Class \"{$className}\" not found");
			}
		}
	};
?>