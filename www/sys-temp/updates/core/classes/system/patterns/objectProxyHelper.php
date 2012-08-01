<?php
	abstract class objectProxyHelper {
		/**
			* Получить префикс класса скидки по id его объекта-типа
			* @param Integer $objectId id объекта типа скидки
			* @return String префикс класса скидки
		*/		
		public static function getClassPrefixByType($objectId) {
			static $cache = array();
			if(isset($cache[$objectId])) {
				return $cache[$objectId];
			}
			$classPrefix = '';
			
			$object = selector::get('object')->id($objectId);
			if($object instanceof iUmiObject) {
				if($object->class_name) {
					$classPrefix = $object->class_name;
				}
			} else {
				throw new coreException("Can't get class name prefix from object #{$objectId}");
			}
			
			return $cache[$objectId] = $classPrefix;
		}


		/**
			* Подключить файл, содержащий класс скидки
			* @param String $classPrefix префикс класса скидки
		*/
		public static function includeClass($classPath, $classPrefix) {
			static $included = array();

			if(in_array($classPath . $classPrefix, $included)) {
				return;
			} else {
				$included[] = $classPath . $classPrefix;
			}
			
			$config = mainConfiguration::getInstance();
			$filePath = $config->includeParam('system.default-module') . $classPath . $classPrefix . '.php';
			
			if(is_file($filePath) == false) {
				throw new coreException("Required source file {$filePath} is not found");
			}
			
			require $filePath;
		}
	};
?>