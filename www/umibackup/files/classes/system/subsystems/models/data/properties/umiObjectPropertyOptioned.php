<?php
/**
	* 
	* 
	* 
*/
	class umiObjectPropertyOptioned extends umiObjectProperty {
		public function setValue($value) {
			if(is_array($value)) {
				$value = array_distinct($value);
			}
			parent::setValue($value);
		}
		
		/**
			* 
		*/
		protected function loadValue() {
			$values = array();
			
			$data = $this->getPropData();
			if($data == false) {
				$data = array();
				$sql = "SELECT int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				$result = l_mysql_query($sql, true);
				while($row = mysql_fetch_assoc($result)) {
					foreach($row as $i => $v) {
						$data[$i][] = $v;
					}
				}
			}
			

			for($i = 0; true; $i++) {
				if($value = $this->parsePropData($data, $i)) {
					foreach($value as $t => $v) {
						$value[$t] = ($t == 'float') ? $this->filterFloat($v) : self::filterOutputString($v);
					}
					
					$values[] = $value;
					continue;
				} break;
			}
			
			return $values;
		}


		/**
			* 
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $key => $data) {
				$sql = "INSERT INTO `{$this->tableName}` (`obj_id`, `field_id`, `int_val`, `varchar_val`, `rel_val`, `tree_val`, `float_val`) VALUES ('{$this->object_id}', '{$this->field_id}', ";
				
				$cnt = 0;
				if($intValue = (int) getArrayKey($data, 'int')) {
					$sql .= "'{$intValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($varcharValue = (string) getArrayKey($data, 'varchar')) {
					$varcharValue = self::filterInputString($varcharValue);
					$sql .= "'{$varcharValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($relValue = (int) $this->prepareRelationValue(getArrayKey($data, 'rel'))) {
					$sql .= "'{$relValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				$this->values[$key]['rel'] = $relValue;
				
				if($treeValue = (int) getArrayKey($data, 'tree')) {
					$sql .= "'{$treeValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($floatValue = (float) getArrayKey($data, 'float')) {
					$sql .= "'{$floatValue}'";
					++$cnt;
				} else {
					$sql .= "NULL";
				}
				
				$sql .= ")";
				
				if($cnt < 2) {
					continue;
				}
				
				l_mysql_query($sql);
			}
		}
		
		
		protected function parsePropData($data, $index) {
			$result = Array();
			$hasValue = false;
			foreach($data as $contentType => $values) {
				if(isset($values[$index])) {
					$contentType = $this->decodeContentType($contentType);
					$result[$contentType] = $values[$index];
					$hasValue = true;
				}
			}
			return $hasValue ? $result : false;
		}
		
		protected function decodeContentType($contentType) {
			if(substr($contentType, -4) == '_val') {
				$contentType = substr($contentType, 0, strlen($contentType) - 4);
			}
			return $contentType;
		}
		
		protected function applyParams($values, $params = NULL) {
			$filter = getArrayKey($params, 'filter');
			$requireFieldType = getArrayKey($params, 'field-type');
			
			if(!is_null($filter)) {
				$result = Array();
				foreach($values as $index => $value) {
					foreach($filter as $fieldType => $filterValue) {
						if(isset($value[$fieldType]) && $value[$fieldType] == $filterValue) {
							$result[] = $value;
						}
					}
				}
				$values = $result;
			}
			
			if(!is_null($requireFieldType)) {
				foreach($values as $i => $value) {
					$values[$i] = getArrayKey($value, $requireFieldType);
				}
			}
			return $values;
		}
		
		protected function filterFloat($value) {
			return round($value, 2);
		}
	};
?>