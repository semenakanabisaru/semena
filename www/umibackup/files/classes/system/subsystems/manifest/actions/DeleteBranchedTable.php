<?php
	class DeleteBranchedTableAction extends atomicAction {
		
		public function execute() {
			$hierarchyTypeId = $this->getParam('hierarchy-type-id');
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			$sql = <<<SQL
DROP TABLE IF EXISTS `{$secondaryTableName}`
SQL;
			$this->mysql_query($sql);
		}
		
		public function rollback() {}
		
	};
?>