<?php
/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Текст".
*/
	class umiObjectPropertyText extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Текст"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Текст"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if($val == "<p />" || $val == "&nbsp;") $val = "";

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
		
		public function __wakeup() {
			foreach($this->value as $i => $v) {
				if(is_string($v)) {
					$this->value[$i] = str_replace("&#037;", "%", $v);
				}
			}
		}
	};
?>