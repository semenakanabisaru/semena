<?php
/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Ссылка на дерево".
*/
	class umiObjectPropertySymlink extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на дерево"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['tree_val'] as $val) {
					if(is_null($val)) continue;
					$element = umiHierarchy::getInstance()->getElement( (int) $val );
					if($element === false) continue;
					if($element->getIsActive() == false) continue;
	
					$res[] = $element;
				}
				return $res;
			}

			$sql = "SELECT  tree_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$element = umiHierarchy::getInstance()->getElement( (int) $val );
				if($element === false) continue;
				if($element->getIsActive() == false) continue;

				$res[] = $element;
			}

			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на дерево"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			$hierarchy = umiHierarchy::getInstance();

			$cnt = 0;
			foreach($this->value as $i => $val) {
				if($val === false || $val === "") continue;

				if(is_object($val)) {
					$val = (int) $val->getId();
				} else {
					$val = intval($val);
				}
				
				if(!$val) continue;

				if(is_numeric($val)) {
					$val = (int) $val;
					$this->value[$i] = $hierarchy->getElement($val);
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, tree_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";

				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
	};
?>