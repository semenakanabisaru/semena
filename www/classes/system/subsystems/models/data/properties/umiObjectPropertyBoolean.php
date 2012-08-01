<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Кнопка-флажок" (булевый тип)
*/
	class umiObjectPropertyBoolean extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['int_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = (int) $val;
				}
				return $res;
			}

			$sql = "SELECT  int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = (int) $val;
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if(!$val) continue;
				$val = (int)$this->boolval($val,true);
				
				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
		protected function boolval($in, $strict=false) {
			$out = null;
			// if not strict, we only have to check if something is false
			if (in_array($in,array('false', 'False', 'FALSE', 'no', 'No', 'n', 'N', '0', 'off',
                           'Off', 'OFF', false, 0, null), true)) {
				$out = false;
			} else if ($strict) {
				// if strict, check the equivalent true values
				if (in_array($in,array('true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1',
                               'on', 'On', 'ON', true, 1), true)) {
					$out = true;
				}
			} else {
				// not strict? let the regular php bool check figure it out (will
				//     largely default to true)
				$out = ($in?true:false);
			}
			return $out;
		}
	};
?>