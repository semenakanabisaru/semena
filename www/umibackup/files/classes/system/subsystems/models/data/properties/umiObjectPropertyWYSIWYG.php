<?php
/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "WYSIWYG".
*/
	class umiObjectPropertyWYSIWYG extends umiObjectPropertyText {
		protected function saveValue() {
			foreach($this->value as $i => $value) {
				$value = str_replace(array('&lt;!--', '--&gt;'), array('<!--', '-->'),  $value);
				$value = preg_replace('/<!--\[if(.*?)>(.*?)<!(-*)\[endif\][\s]*-->/mis', '', $value);
				$this->value[$i] = $value;
			}
			parent::saveValue();
		}

		/**
			* Загружает значение свойства из БД, если тип свойства "HTML-текст"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					if(str_replace("&nbsp;", "", trim($val)) == "") continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				if(str_replace("&nbsp;", "", trim($val)) == "") continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}
	};
?>