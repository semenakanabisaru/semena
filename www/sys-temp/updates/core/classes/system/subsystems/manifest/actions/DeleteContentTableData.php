<?php
	class DeleteContentTableDataAction extends atomicAction {
		protected $hierarchyTypeId;
		
		public function execute() {
			$this->hierarchyTypeId = $this->getParam('hierarchy-type-id');
			
			$this->deleteBranchedDataFromSource();
		}
		
		public function rollback() {}
		
		protected function deleteBranchedDataFromSource() {
			$hierarchyTypeId = $this->hierarchyTypeId;
			$primaryTableName = "cms3_object_content";
			$secondaryTableName = "cms3_object_content_" . $hierarchyTypeId;
			
			
			$objectTypes = $this->getObjectTypes($hierarchyTypeId);
			
			if(sizeof($objectTypes) == 0) {
				return;
			}
			
			$objectTypesCondition = implode(", ", $objectTypes);
			
			$sql = <<<SQL
DELETE FROM `{$primaryTableName}`
	WHERE `obj_id` IN (SELECT `id` FROM `cms3_objects` WHERE `type_id` IN ({$objectTypesCondition}))
SQL;
			$this->mysql_query($sql);
		}
		
		protected function getObjectTypes($hierarchyTypeId) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			return array_keys($objectTypes->getTypesByHierarchyTypeId($hierarchyTypeId));
		}
	};
?>