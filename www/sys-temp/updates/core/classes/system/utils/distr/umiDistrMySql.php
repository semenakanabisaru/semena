<?php
	class umiDistrMySql extends umiDistrInstallItem {
		protected $tableName, $permissions, $sqls = Array();

		public function __construct($tableName = false) {
			if($tableName !== false) {
				$this->tableName = $tableName;
				$this->readTableDefinition();
				$this->readData();
			}
		}

		public function pack() {
			return base64_encode(serialize($this));
		}

		public static function unpack($data) {
			return base64_decode(unserialize($data));
		}

		public function restore() {
			//TODO
			//TODO
		}


		protected function readTableDefinition() {
			$sql = "SHOW CREATE TABLE {$this->tableName}";
			$result = l_mysql_query($sql);
			list(, $cont) = mysql_fetch_row($result);
			$this->sqls[] = $cont;
		}


		protected function readData() {
			$sql = "SELECT * FROM {$this->tableName}";
			$result = l_mysql_query($sql);

			$rows = Array();
			while($row = mysql_fetch_assoc($result)) {
				//$this->sqls[] = $this->generateInsertRow($row);
				$rows[] = $row;
			}

			if($rows) {
				$this->sqls[] = $this->generateInsertRows($rows);
			}
		}


		protected function generateInsertRow($row) {
			$sql = "INSERT INTO {$this->tableName} (";

			$fields = array_keys($row);
			$sz = sizeof($fields);
			for($i = 0; $i < $sz; $i++) {
				$sql .= "`" . $fields[$i] . "`";

				if($i < ($sz - 1)) {
					$sql .= ", ";
				}
			}
			unset($fields);

			$sql .= ") VALUES(";
			
			
			$sql_init = $sql;
			$sql = "";



  
  
			$values = array_values($row);
			$sz = sizeof($values);
			for($i = 0; $i < $sz; $i++) {
				$sql .= $sql_init;
				
				$val = $values[$i];
				if(strlen($val)) {
					$val = "'" . mysql_escape_string($val) . "'";
				} else {
					$val = "NULL";
				}
				$sql .= $val;

				if($i < ($sz - 1)) {
					$sql .= ", ";
				}
			}
			unset($values);

			$sql .= ")";

			return $sql;
		}


		protected function generateInsertRows($rows) {
			$sql = "INSERT INTO {$this->tableName} (";

			$fields = array_keys($rows[0]);
			$sz = sizeof($fields);
			for($i = 0; $i < $sz; $i++) {
				$sql .= "`" . $fields[$i] . "`";

				if($i < ($sz - 1)) {
					$sql .= ", ";
				}
			}
			unset($fields);

			$sql .= ") VALUES";

			for($n = 0; $n < sizeof($rows); $n++) {
				$row = $rows[$n];

				$sql .= "(";
				$values = array_values($row);
				$sz = sizeof($values);
				for($i = 0; $i < $sz; $i++) {
					$val = $values[$i];
					if(strlen($val)) {
						$val = "'" . mysql_escape_string($val) . "'";
					} else {
						$val = "NULL";
					}
					$sql .= $val;

					if($i < ($sz - 1)) {
						$sql .= ", ";
					}
				}
				unset($values);
				$sql .= ")";

				if($n < (sizeof($rows) - 1)) {
					$sql .= ", ";
				}
			}

			return $sql;
		}

	};
?>