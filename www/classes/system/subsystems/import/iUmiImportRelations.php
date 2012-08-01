<?php

	interface iUmiImportRelations {
		public function getSourceId($sourceName);
		public function addNewSource($sourceName);

		public function setIdRelation($sourceId, $oldId, $newId);
		public function getNewIdRelation($sourceId, $oldId);
		public function getOldIdRelation($sourceId, $newId);

		public function setTypeIdRelation($sourceId, $oldId, $newId);
		public function getNewTypeIdRelation($sourceId, $oldId);
		public function getOldTypeIdRelation($sourceId, $newId);


		public function setFieldIdRelation($sourceId, $typeId, $oldFieldName, $newFieldId);
		public function getNewFieldId($sourceId, $typeId, $oldFieldName);
		public function getOldFieldName($sourceId, $typeId, $newFieldId);
	};

?>