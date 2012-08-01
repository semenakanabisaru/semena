<?php
	interface iPermissionsCollection {

		public function getOwnerType($ownerId);
		public function makeSqlWhere($ownerId);

		public function isAllowedModule($ownerId, $module);
		public function isAllowedMethod($ownerId, $module, $method);
		public function isAllowedObject($ownerId, $objectId);
		public function isSv($userId = false);
		public function isAdmin($userId = false);
		public function isOwnerOfObject($objectId, $userId = false);

		public function resetElementPermissions($elementId, $ownerId = false);
		public function resetModulesPermissions($ownerId);
		
		public function setElementPermissions($ownerId, $elementId, $level);
		public function setModulesPermissions($ownerId, $module, $method = false);

		public function setDefaultPermissions($elementId);

		public function hasUserPermissions($ownerId);
		
		public function copyHierarchyPermissions($fromOwnerId, $toOwnerId);
		
		public function getUserId();
		
		public function setAllElementsDefaultPermissions($ownerId);
		
		public function getUsersByElementPermissions($elementId, $level = 1);
		
		public function pushElementPermissions($elementId, $level = 1);
		
		public function cleanupBasePermissions();
		
		public function isAuth();
	};
?>