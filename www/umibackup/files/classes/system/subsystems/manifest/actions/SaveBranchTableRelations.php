<?php
	class SaveBranchTableRelationsAction extends atomicAction {
	
		public function execute() {
			$filePath = CURRENT_WORKING_DIR . "/cache/branchedTablesRelations.rel";
			
			if(is_file($filePath)) {
				unlink($filePath);
			}
			
			umiBranch::saveBranchedTablesRelations();
		}
		
		public function rollback() {
			$filePath = CURRENT_WORKING_DIR . "/cache/branchedTablesRelations.rel";
			if(is_file($filePath)) {
				unlink($filePath);
			}
		}
		
	};
?>