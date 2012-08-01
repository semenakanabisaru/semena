<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Дата"
*/
	class umiObjectPropertyDate extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Дата"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['int_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = new umiDate((int) $val);
				}
				return $res;
			}

			$sql = "SELECT  int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = new umiDate((int) $val);
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Дата"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			
			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") {
					continue;
				} else {
					$val = (is_object($val)) ? (int) $val->timestamp : (int) $val;
					if($val == false) {
						continue;
					}
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};
?>