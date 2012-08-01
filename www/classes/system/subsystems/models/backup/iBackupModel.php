<?php
	interface iBackupModel {
		public function getChanges($param = "");
		public function save($cparam = "");
		public function rollback($revisionId);
		public function addLogMessage($elementId);
		public function fakeBackup($elementId);
	};
?>