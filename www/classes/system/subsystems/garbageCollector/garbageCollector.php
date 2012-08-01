<?php
/**
	* Класс, который реализует сборщик мусора: подчищает кеш, удаляет лишнии записи из базы и т.д..
*/
	class garbageCollector implements iGarbageCollector {
		protected $maxStaticCacheLifeTime = 86400;
		protected $maxIterations = 5000, $executedIterations;
		
		/**
			* Запустить сборщик мусора. Сбрасывает счетчик итераций в ноль.
		*/
		public function run() {
			$this->executedIterations = 0;
			
			$this->deleteStaticCache();
			$this->deleteBrokenDBRelations();
		}
		
		/**
			* Удалить старый статический html-кеш
			* @param Boolean $ignoreMaxLifeTime = false игнорировать время жизни кеша и удалять все
		*/
		protected function deleteStaticCache($ignoreMaxLifeTime = false) {
			if($ignoreMaxLifeTime) {
				$ttl = 0;
			} else {
				$ttl = (int) $this->maxStaticCacheLifeTime;
			}
		
			$this->deleteDirectory("./static/userCache", $ttl);
			$this->deleteDirectory("./static/cacheElementsRelations", $ttl);
			$this->deleteDirectory("./static/cacheObjectsRelations", $ttl);
		}
		
		/**
			* Рекурсивно удалить содержание директории, учитывая максимальное время жизни файла
			* @param String $directoryPath путь до директории, которую необходимо удалить
			* @param Integer $ttl максимальное время жизни файлов в этой директории (в секундах)
		*/
		protected function deleteDirectory($directoryPath, $ttl = 0) {
			$time = time();
		
			if($this->checkDirectory($directoryPath) == false) {
				return false;
			}
			
			$dir = new umiDirectory($directoryPath);
			foreach($dir as $item) {
				$this->checkMaxIterations();
				
				if($item instanceof umiDirectory) {
					$this->deleteDirectory($item->getPath(), $ttl);
					$item->delete();
				} else if ($item instanceof umiFile) {
					if($item->getModifyTime() <= ($time - $ttl)) {
						$item->delete();
					}
				} else {
					throw new coreException("Got unexpected result of type \"" . gettype($item) . "\"");
				}
			}
		}
		
		/**
			* Проверить директорию на существование и возможность перезаписи
			* @param String $directoryPath путь до проверяемой директории
			* @return Boolean true, если директория существует и перезаписываемая
		*/
		protected function checkDirectory($directoryPath) {
			if(is_dir($directoryPath)) {
				if(is_writable($directoryPath)) {
					return true;
				}
			}
			return false;
		}
		
		/**
			* Проверить, не превысили ли мы лимит по количеству итераций
		*/
		public function checkMaxIterations() {
			if(++$this->executedIterations > $this->maxIterations) {
				throw new maxIterationsExeededException("Maximum iterations count reached: " . $this->maxIterations);
			}
		}
		
		/**
			* Удалить мертвые связи в таблицах
		*/
		protected function deleteBrokenDBRelations() {
			if(defined("DB_DRIVER")) {
				if(DB_DRIVER == "xml") {
					return false;
				}
			}
		
			$this->deleteBrokenForeignRelations("cms3_hierarchy", "obj_id", "cms3_objects", "id");
			$this->deleteBrokenForeignRelations("cms3_objects", "type_id", "cms3_object_types", "id");
			$this->deleteBrokenForeignRelations("cms3_hierarchy", "rel", "cms3_hierarchy", "id");
		}
		
		/**
			* Удалить связанные записи таблицы $leftTableName, если нет соответствия полей $leftColumnName 
			* в таблице $rightTableName по полю $rightColumnName
			* @param String $leftTableName название проверяемой таблицы
			* @param String $leftColumnName название столбца, который связывает проверяемую таблицу
			* @param String $rightTableName название таблицы, с которой связана проверяемая таблица
			* @param String $rightColumnName название столбца в связанной таблице
		*/
		protected function deleteBrokenForeignRelations($leftTableName, $leftColumnName, $rightTableName, $rightColumnName) {
			$sql = "SELECT `id`, `{$leftColumnName}` FROM `{$leftTableName}`";
			$result = l_mysql_query($sql, true);
			
			if($err = l_mysql_error()) {
				throw new coreException($err);
			}
			
			while(list($id, $referenceValue) = mysql_fetch_row($result)) {
				$this->checkMaxIterations();
				
				if(!$referenceValue) {
					continue;
				}
			
				$sql1 = "SELECT COUNT(*) FROM `{$rightTableName}` WHERE `{$rightColumnName}` = '{$referenceValue}'";
				$result1 = l_mysql_query($sql1, true);
				
				if($err = l_mysql_error()) {
					throw new coreException($err);
				}
				
				if(list($count) = mysql_fetch_row($result1)) {
					if($count > 0) {
						continue;
					}
				}
				
				$sql1 = "DELETE FROM `{$leftTableName}` WHERE `id` = '{$id}'";
				l_mysql_query($sql1);
				
				if($err = l_mysql_error()) {
					throw new coreException($err);
				}
			}
		}
		
	};
?>