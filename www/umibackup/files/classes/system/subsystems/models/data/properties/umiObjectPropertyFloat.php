<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Число с точкой"
*/
	class umiObjectPropertyFloat extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "число с точкой"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['float_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = (float) $val;
				}
				return $res;
			}

			$sql = "SELECT  float_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = (float) $val;
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число с точкой"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;

				if(strpos(".", $val) === false) $val = str_replace(",", ".", $val);
				$val = (float) $val;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, float_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};
?>