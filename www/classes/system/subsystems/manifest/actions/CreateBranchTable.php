<?php
	class CreateBranchTableAction extends atomicAction {
		protected $hierarchyTypeId, $didTableExisted = false;
		
		public function execute() {		
			$this->hierarchyTypeId = $this->getParam('hierarchy-type-id');
			
			if(is_numeric($this->hierarchyTypeId) == false) {
				throw new Exception("Param \"hierarchy-type-id\" must be numeric");
			}
			
			$this->checkIfTableExists($this->hierarchyTypeId);
			$this->createBranchTable($this->hierarchyTypeId);
		}
		
		public function rollback() {
			if($this->didTableExisted == false) {
				$this->dropBranchTable($this->hierarchyTypeId);
			}
		}

		protected function createBranchTable($hierarchyTypeId) {
			$primaryTableName = "cms3_object_content";
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			$sql = <<<SQL
CREATE TABLE `{$secondaryTableName}` LIKE `{$primaryTableName}`
SQL;
			$this->mysql_query($sql);
			
			$sql = <<<SQL
ALTER TABLE `{$secondaryTableName}` ADD CONSTRAINT `FK_Content to object relation {$hierarchyTypeId}` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL;
			$this->mysql_query($sql);

			$sql = <<<SQL
ALTER TABLE `{$secondaryTableName}`  ADD CONSTRAINT `FK_content2tree {$hierarchyTypeId}` FOREIGN KEY (`tree_val`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE
SQL;
			$this->mysql_query($sql);

			$sql = <<<SQL
ALTER TABLE `{$secondaryTableName}`  ADD CONSTRAINT `FK_Contents field id relation {$hierarchyTypeId}` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL;
			$this->mysql_query($sql);

			$sql = <<<SQL
ALTER TABLE `{$secondaryTableName}`  ADD CONSTRAINT `FK_Relation value reference {$hierarchyTypeId}` FOREIGN KEY (`rel_val`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL;
			$this->mysql_query($sql);
		}
		
		protected function dropBranchTable($hierarchyTypeId) {
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			$sql = <<<SQL
DROP TABLE IF EXISTS `{$secondaryTableName}`
SQL;
			$this->mysql_query($sql);
		}

		protected function checkIfTableExists($hierarchyTypeId) {
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			$sql = <<<SQL
SHOW TABLES LIKE '{$secondaryTableName}'
SQL;
			$result = $this->mysql_query($sql);
			
			if(mysql_num_rows($result) > 0) {
				$this->didTableExisted = true;
				throw new Exception("Table already branched to \"{$secondaryTableName}\"");
			}
		}
	};
?>