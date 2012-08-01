<?php
/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Теги".
*/
	class umiObjectPropertyTags extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Тэги"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['varchar_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  varchar_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Тэги"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(sizeof($this->value) == 1) {				
				$value = trim($this->value[0], ",");				
				$value = preg_replace("/[^A-Za-z0-9А-Яа-яЁё'\-$%_,\s]/u", "", $value);				
				$value =  explode(",", $value);
			} else {
				$value = array_map( create_function('$a', " return preg_replace(\"/[^A-Za-z0-9А-Яа-яЁё'\\-\\$%_,\s]?/u\", \"\", \$a); ") , $this->value);
			}

			$cnt = 0;
			foreach($value as $val) {
				$val = trim($val);
				if(strlen($val) == 0) continue;

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, varchar_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
	};
?>