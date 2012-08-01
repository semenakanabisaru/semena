<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Пароль"
*/
	class umiObjectPropertyPassword extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
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

			$sql = "SELECT  varchar_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}


		/**
			* Сохраняет значение свойства в БД, если тип свойства "Пароль"
		*/
		protected function saveValue() {
			$cnt = 0;
			foreach($this->value as $val) {
				if(strlen($val) == 0) continue;

				$this->deleteCurrentRows();

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, varchar_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};
?>