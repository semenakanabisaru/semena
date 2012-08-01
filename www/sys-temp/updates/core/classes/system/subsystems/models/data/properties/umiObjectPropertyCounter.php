<?php
/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Счетчик"
*/
	class umiObjectPropertyCounter extends umiObjectProperty {
		protected $oldValue;
		
		/**
			* Загружает значение свойства из БД
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			$sql = "SELECT cnt FROM `cms3_object_content_cnt` WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			if(list($val) = mysql_fetch_row($result)) {
				$cnt = (int) $val;
			} else {
				$cnt = 0;
			}
			$this->oldValue = $cnt;

			return Array($cnt);
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$value = sizeof($this->value) ? (int) $this->value[0] : 0;
			$lambda = $value - $this->oldValue;
			if((abs($lambda) == 1) && $value !== 0 && $this->oldValue) {
				$sql = "UPDATE `cms3_object_content_cnt` SET cnt = cnt + ({$lambda}) WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				l_mysql_query($sql);
			} else {
				$this->deleteCurrentRows();
				$sql = "INSERT INTO `cms3_object_content_cnt` (obj_id, field_id, cnt) VALUES('{$this->object_id}', '{$this->field_id}', '{$value}')";
				l_mysql_query($sql);
			}
		}
		
		protected function deleteCurrentRows() {
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			
			$sql = "DELETE FROM `cms3_object_content_cnt` WHERE `obj_id` = {$objectId} AND `field_id` = {$fieldId}";
			l_mysql_query($sql);
		}
		
		protected function fillNull() {
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			
			$sql = "SELECT COUNT(*) FROM `cms3_object_content_cnt` WHERE `obj_id` = {$objectId} AND `field_id` = {$fieldId}";
			$result = l_mysql_query($sql);
			list($count) = mysql_fetch_row($result);
			if($count == 0) {
				$sql = "INSERT INTO `cms3_object_content_cnt` (`obj_id`, `field_id`) VALUES ('{$objectId}', '{$fieldId}')";
				l_mysql_query($sql);
			}
		}
	};
?>