<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Файл"
*/
	class umiObjectPropertyFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Файл"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$val = self::unescapeFilePath($val);
					
					$file = new umiFile(self::filterOutputString($val));
					if($file->getIsBroken()) continue;
					$res[] = $file;
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$file = new umiFile($val);
				if($file->getIsBroken()) continue;
				$res[] = $file;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Файл"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$cnt = 0;
			foreach($this->value as $val) {
				if(!$val) continue;
				
				if(is_object($val)) {
					if(!@is_file($val->getFilePath())) {
						continue;
					}
					$val = l_mysql_real_escape_string($val->getFilePath());
				} else {
					$val = l_mysql_real_escape_string($val);
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};
?>