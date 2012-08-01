<?php
	abstract class translatorWrapper {
		public $isFull = false;
		public static $showEmptyFields = false;
		
		abstract public function translate($data);
		
		final static public function get($object) {
			if(is_object($object) == false) {
				throw new coreException("Object required to apply class translation");
			}
			
			$className = self::getClassAlias($object);
			if($wrapper = self::loadWrapper($className)) {
				return $wrapper;
			} else {
				throw new coreException("Can't load translation wrapper for class \"{$className}\"");
			}
		}
		
		final static protected function loadWrapper($className) {
			static $loaded = array(), $config;
			
			if(isset($loaded[$className])) {
				return $loaded[$className];
			}
			
			if(is_null($config)) {
				$config = mainConfiguration::getInstance();
			}
			
			
			$wrapperClassName = $className . 'Wrapper';
			if (!class_exists($wrapperClassName)) {
				$filePath = $config->includeParam('system.kernel') . 'utils/translators/wrappers/' . $className . 'Wrapper.php';
				if(is_file($filePath) == false) {
					$loaded[$className] = false;
					throw new coreException("Can't load file \"{$filePath}\" to translate object of class \"{$className}\"");
				}
				
				require $filePath;
			}
			
			if(!class_exists($wrapperClassName)) {
				$loaded[$className] = false;
				throw new coreException("Translation wrapper class \"{$wrapperClassName}\" not found");
			}
			
			$wrapper = new $wrapperClassName($translator);
			
			if($wrapper instanceof translatorWrapper == false) {
				$loaded[$className] = false;
				throw new coreException("Translation wrapper class \"{$wrapperClassName}\" should be instance of translatorWrapper");
			}
			
			return $loaded[$className] = $wrapper;
		}
		
		protected static function getClassAlias($object) {
			$baseClasses = array(
				'baseRestriction', 'publicException'
			);
			
			$aliases = array(
				'umiObjectProperty' => array(
					'umiObjectPropertyPrice', 
					'umiObjectPropertyFloat', 
					'umiObjectPropertyTags', 
					'umiObjectPropertyBoolean', 
					'umiObjectPropertyImgFile', 
					'umiObjectPropertyRelation', 
					'umiObjectPropertyText', 
					'umiObjectPropertyDate', 
					'umiObjectPropertyInt', 
					'umiObjectPropertyString', 
					'umiObjectPropertyWYSIWYG', 
					'umiObjectPropertyFile', 
					'umiObjectPropertyPassword', 
					'umiObjectPropertySymlink',
					'umiObjectPropertyCounter', 
					'umiObjectPropertyOptioned'
				),
				
				'umiFile' => array(
					'umiImageFile'
				)
			);
			
			$className = get_class($object);
			
			foreach($aliases as $baseClassName => $alias) {
				if(in_array($className, $alias)) {
					return $baseClassName;
				}
			}
			
			foreach($baseClasses as $baseClass) {
				if(in_array($baseClass, class_parents($object))) {
					return $baseClass;
				}
			}

			
			return $className;
		}
	};
?>