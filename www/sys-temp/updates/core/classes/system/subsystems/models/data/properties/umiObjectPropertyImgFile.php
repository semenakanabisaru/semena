<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Картинка"
*/
	class umiObjectPropertyImgFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Изображение"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$val = self::unescapeFilePath($val);
					
					$img = new umiImageFile(self::filterOutputString($val));
					if($img->getIsBroken()) continue;
					$res[] = $img;
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				
				$val = self::unescapeFilePath($val);
				
				$img = new umiImageFile(self::filterOutputString($val));
				if($img->getIsBroken()) continue;
				$res[] = $img;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Изображение"
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
				
				$val = self::unescapeFilePath($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};
?>