<?php
	interface iUmiField {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getIsInheritable();
		public function setIsInheritable($isInheritable);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getFieldTypeId();
		public function setFieldTypeId($fieldTypeId);

		public function getFieldType();

		public function getGuideId();
		public function setGuideId($guideId);

		public function getIsInSearch();
		public function setIsInSearch($isInSearch);

		public function getIsInFilter();
		public function setIsInFilter($isInFilter);

		public function getTip();
		public function setTip($tip);

		public function getIsRequired();
		public function setIsRequired($isRequired = false);

		public function getIsSortable();
		public function setIsSortable($sortable = false);

		public function getRestrictionId();
		public function setRestrictionId($restrictionId = false);

		public function getIsSystem();
		public function setIsSystem($isSystem = false);

		public function getDataType();
	};

	interface iUmiFieldType {
		public function getName();
		public function setName($name);

		public function getIsMultiple();
		public function setIsMultiple($isMultiple);

		public function getIsUnsigned();
		public function setIsUnsigned($isUnsigned);

		public function getDataType();
		public function setDataType($dataTypeStr);

		public static function getDataTypes();
		public static function getDataTypeDB($dataType);
		public static function isValidDataType($dataTypeStr);
	};

	interface iUmiFieldTypesCollection {
		public function addFieldType($name, $dataType = "string", $isMultiple = false, $isUnsigned = false);
		public function delFieldType($fieldTypeId);
		public function getFieldType($fieldTypeId);

		public function getFieldTypesList();
	};

	interface iUmiFieldsCollection {
		public function addField($name, $title, $fieldTypeId, $isVisible = true, $isLocked = false, $isInheritable = false);
		public function delField($field_id);
		public function getField($fieldId);
	};

	interface iUmiFieldsGroup {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getOrd();
		public function setOrd($ord);

		public function getIsActive();
		public function setIsActive($isActive);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getFields();

		public function attachField($fieldId);
		public function detachField($fieldId);

		public function moveFieldAfter($fieldId, $beforeFieldId, $group_id, $is_last);

		public static function getAllGroupsByName($fieldName);
	};

	interface iUmiObject {
		public function getName();
		public function setName($name);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getTypeId();
		public function getTypeGUID();
		public function setTypeId($typeId);

		public function getPropGroupId($groupName);
		public function getPropGroupByName($groupName);
		public function getPropGroupById($groupId);

		public function getPropByName($propName);
		public function getPropById($propId);

		public function isPropertyExists($id);

		public function isFilled();

		public function getValue($propName);
		public function setValue($propName, $propValue);

		public function setOwnerId($ownerId);
		public function getOwnerId();
	};

	interface iUmiObjectProperty {
		public function getValue();
		public function setValue($value);
		public function resetValue();

		public function getName();
		public function getTitle();

		public function getIsMultiple();
		public function getIsUnsigned();
		public function getDataType();
		public function getIsLocked();
		public function getIsInheritable();
		public function getIsVisible();

		public static function filterOutputString($string);
		public static function filterCDATA($string);

		public function getObject();
		public function getField();
	};

	interface iUmiObjectType {
		public function addFieldsGroup($name, $title, $isActive = true, $isVisible = true);
		public function delFieldsGroup($fieldGroupId);

		public function getFieldsGroupByName($fieldGroupName);

		public function getFieldsGroup($fieldGroupId);
		public function getFieldsGroupsList($showDisabledGroups = false);

		public function getName();
		public function setName($name);

		public function setIsLocked($isLocked);
		public function getIsLocked();

		public function setIsGuidable($isGuidable);
		public function getIsGuidable();

		public function setIsPublic($isPublic);
		public function getIsPublic();

		public function setHierarchyTypeId($hierarchyTypeId);
		public function getHierarchyTypeId();

		public function getParentId();

		public function setFieldGroupOrd($groupId, $newOrd, $isLast);


		public function getFieldId($fieldName);

		public function getAllFields($returnOnlyVisibleFields = false);

		public function getModule();
		public function getMethod();
	};

	interface iUmiObjectTypesCollection {
		public function addType($parentId, $name, $isLocked = false);
		public function delType($typeId);

		public function getType($typeId);
		public function getSubTypesList($typeId);

		public function getParentClassId($typeId);
		public function getChildClasses($typeId);

		public function getGuidesList($publicOnly = false);

		public function getTypesByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);
		public function getTypeByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);

		public function getBaseType($typeName, $typeExt = "");
	};

	interface iUmiObjectsCollection {
		public function getObject($objectId);
		public function addObject($name, $typeId, $isLocked = false);
		public function delObject($objectId);

		public function cloneObject($iObjectId);

		public function getGuidedItems($guideId);

		public function unloadObject($objectId);
	};
?>
