<?php
/**
	* Этот класс служит для управления разделением и объединением контентных таблиц
	* Впервые появился в версии 2.7.0. Логика работы класса находится на уровне mysql-драйвера.
	* Данный класс не следует использовать в прикладном коде модулей.
*/
	class umiBranch {
		static protected $branchedObjectTypes = false;
		
		/**
			* Проанализировать текущее состояние контентных таблиц и сохранить в кеш
			* @return Array список типов данных, которых затронули изменения
		*/
		public static function saveBranchedTablesRelations() {
			$filePath = self::getRelationsFilePath();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			self::$branchedObjectTypes = Array();
			
			clearstatcache();
			if(file_exists($filePath)) {
				unlink($filePath);
			}
			
			$sql = "SHOW TABLES LIKE 'cms3_object_content%'";
			$result = l_mysql_query($sql, true);
			
			if($err = l_mysql_error()) {
				throw new coreException($err);
			}
			
			$branchedHierarchyTypes = Array();
			while(list($tableName) = mysql_fetch_row($result)) {
				if(preg_match("/cms3_object_content_([0-9]+)/", $tableName, $out))
					$branchedHierarchyTypes[] = (int) $out[1];
			}
			
			$branchedObjectTypes = Array();
			foreach($branchedHierarchyTypes as $hierarchyTypeId) {
				$objectTypes = array_keys($objectTypesCollection->getTypesByHierarchyTypeId($hierarchyTypeId));
				if(is_array($objectTypes)) {
					foreach($objectTypes as $objectTypeId) {
						$branchedObjectTypes[$objectTypeId] = $hierarchyTypeId;
					}
				}
			}
			
			file_put_contents($filePath, serialize($branchedObjectTypes));
			chmod($filePath, 0777);
			
			return self::$branchedObjectTypes = $branchedObjectTypes;
		}
		
		/**
			* Узнать, таблице с каким названием лежат данные для типа данных $objectTypeId
			* @param Integer $objectTypeId id типа данных
			* @return String название mysql-таблицы
		*/
		public static function getBranchedTableByTypeId($objectTypeId) {
			$branchedObjectTypes = self::$branchedObjectTypes;
			
			if(!is_array($branchedObjectTypes)) {
				$branchedObjectTypes = self::getBranchedTablesRelations();
			}
			
			if(isset($branchedObjectTypes[$objectTypeId])) {
				$hierarchyTypeId = $branchedObjectTypes[$objectTypeId];
				return "cms3_object_content_" . $hierarchyTypeId;
			} else {
				return "cms3_object_content";
			}
		}
		
		/**
			* Узнать, разделены ли данные с иерархическим типом $hierarchyTypeId
			* @param Integer $hierarchyTypeId
			* @return Boolean true, если разделены, false если данные лежат в общей таблице
		*/
		public static function checkIfBranchedByHierarchyTypeId($hierarchyTypeId) {
			$branchedObjectTypes = self::$branchedObjectTypes;
			
			if(!is_array($branchedObjectTypes)) {
				$branchedObjectTypes = self::getBranchedTablesRelations();
			}
			return (bool) in_array($hierarchyTypeId, $branchedObjectTypes);
		}
		
		/**
			* Получить текущее состояние базы данных для принятия решения о необходимости branch/merge таблиц.
			* @return Array ассоциативный массив с распределением объектов (count) по hierarchy-type-id.
		*/
		public static function getDatabaseStatus() {
			$result = Array();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			
			$hierarchyTypesList = $hierarchyTypesCollection->getTypesList();
			foreach($hierarchyTypesList as $hierarchyType) {
				$hierarchyTypeId = $hierarchyType->getId();
				$isBranched = self::checkIfBranchedByHierarchyTypeId($hierarchyTypeId);
				
				$sel = new umiSelection;
				$sel->addElementType($hierarchyTypeId);
				$count = umiSelectionsParser::runSelectionCounts($sel);
				
				$result[] = Array(
					'id' => $hierarchyTypeId,
					'isBranched' => $isBranched,
					'count' => $count
				);
			}
			return $result;
		}
		
		/**
			* Загрузить из кеша данные о состоянии таблиц данных
			* @return Array список типов данных, которых затронули изменения
		*/
		protected static function getBranchedTablesRelations() {
			$filePath = self::getRelationsFilePath();
			
			if(is_file($filePath)) {
				$branchedObjectTypes = unserialize(file_get_contents($filePath));
				if(is_array($branchedObjectTypes)) {
					return self::$branchedObjectTypes = $branchedObjectTypes;
				}
			}
			
			return self::saveBranchedTablesRelations();
		}
		
		protected static function getRelationsFilePath() {
			static $path;
			if(is_null($path)) {
				$config = mainConfiguration::getInstance();
				$path = $config->includeParam('system.runtime-cache') . "/branchedTablesRelations.rel";
			}
			return $path;
		}
	};
?>