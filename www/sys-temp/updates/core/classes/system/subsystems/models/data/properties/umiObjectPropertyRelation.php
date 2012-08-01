<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Выпадающий список", т.е. свойства с использованием справочников.
*/
	class umiObjectPropertyRelation extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на объект"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['rel_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = $val;
				}
				return $res;
			}

			if($this->getIsMultiple()) {
				$sql = "SELECT  rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			} else {
				$sql = "SELECT  rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			}

			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = $val;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на объект"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$tmp = Array();
			foreach($this->value as $val) {
				if(!$val) continue;

				if(is_string($val) && strpos($val, "|") !== false) {
					$tmp1 = explode("|", $val);
					foreach($tmp1 as $v) {
						$v = trim($v);
						if($v) $tmp[] = $v;
						unset($v);
					}
					unset($tmp1);
					$this->getField()->setFieldTypeId(umiFieldTypesCollection::getInstance()->getFieldTypeByDataType('relation',1)->getId());	//Check, if we can use it without fieldTypeId

				} else {
					$tmp[] = $val;
				}
			}
			$this->value = $tmp;
			unset($tmp);

			$cnt = 0;

			foreach($this->value as $key => $val) {
				if($val) {
					$val = $this->prepareRelationValue($val);
					$this->values[$key] = $val;
				}
				if(!$val) continue;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, rel_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}

			if(!$cnt) $this->fillNull();
		}
	};
?>