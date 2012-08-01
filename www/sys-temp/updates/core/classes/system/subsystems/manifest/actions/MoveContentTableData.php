<?php
	class MoveContentTableDataAction extends atomicAction {
		protected $hierarchyTypeId;
		
		public function execute() {		
			$this->hierarchyTypeId = $this->getParam('hierarchy-type-id');
			
			if(is_numeric($this->hierarchyTypeId) == false) {
				throw new Exception("Param \"hierarchy-type-id\" must be numeric");
			}
			
			$this->moveBranchedData();
		}
		
		public function rollback() {
			$hierarchyTypeId = $this->hierarchyTypeId;
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			$sql = <<<SQL
TRUNCATE TABLE `{$secondaryTableName}`
SQL;
			$this->mysql_query($sql);
		}
		
		protected function moveBranchedData() {
			$hierarchyTypeId = $this->hierarchyTypeId;
			$primaryTableName = "cms3_object_content";
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			
			$objectTypes = $this->getObjectTypes($hierarchyTypeId);
			
			if(sizeof($objectTypes) == 0) {
				return;
			}
			
			$objectTypesCondition = implode(", ", $objectTypes);
			
			$this->mysql_query("SET FOREIGN_KEY_CHECKS=0");
			
			$sql = <<<SQL
INSERT INTO `{$secondaryTableName}` SELECT * FROM `{$primaryTableName}`
	WHERE `obj_id` IN (SELECT `id` FROM `cms3_objects` WHERE `type_id` IN ({$objectTypesCondition}))
SQL;
			$this->mysql_query($sql);
			
			$this->mysql_query("SET FOREIGN_KEY_CHECKS=1");
		}

		protected function getObjectTypes($hierarchyTypeId) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			return array_keys($objectTypes->getTypesByHierarchyTypeId($hierarchyTypeId));
		}
	};
?>