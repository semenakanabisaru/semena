<?php
	interface iDomain {
		public function getIsDefault();
		public function setIsDefault($isDefault);

		public function addMirrow($mirrowHost);
		public function delMirrow($mirrowId);

		public function getMirrowId($mirrowHost);
		public function getMirrow($mirrowId);

		public function getMirrowsList();
		public function delAllMirrows();


		public function isMirrowExists($mirrowId);

		public function getDefaultLangId();
		public function setDefaultLangId($langId);
	};

	interface iDomainMirrow {
		public function getHost();
		public function setHost($host);
	};

	interface iDomainsCollection {
		public function addDomain($host, $defaultLangId, $isDefault = false);
		public function delDomain($domainId);
		public function getDomain($domainId);

		public function getDefaultDomain();
		public function setDefaultDomain($domainId);

		public function getDomainId($host, $useMirrows = true);

		public function getList();
	};

	interface iLang {
		public function getTitle();
		public function setTitle($title);

		public function getPrefix();
		public function setPrefix($prefix);

		public function getIsDefault();
		public function setIsDefault($isDefault);
	};

	interface iLangsCollection {
		public function addLang($prefix, $title, $isDefault = false);
		public function delLang($langId);

		public function getDefaultLang();
		public function setDefault($langId);

		public function getLangId($prefix);
		public function getLang($langId);

		public function getList();

		public function getAssocArray();
	};

	interface iTemplate {
		/**
		 * Получить директорию с ресурсами для шаблона дизайна
		 * @return string
		 */
		public function getResourcesDirectory();

		/**
		 * Получть полный путь к шаблону дизайна
		 * @return string
		 */
		public function getFilePath();

		public function getFilename();
		public function setFilename($filename);

		public function getTitle();
		public function setTitle($title);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getLangId();
		public function setLangId($langId);

		public function getIsDefault();
		public function setIsDefault($isDefault);

		public function getUsedPages();
		public function setUsedPages($elementIdArray);
	};

	interface iTemplatesCollection {
		public function addTemplate($filename, $title, $domainId = false, $langId = false, $isDefault = false);
		public function delTemplate($templateId);


		public function getDefaultTemplate($domain_id = false, $lang_id = false);
		public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false);

		public function getTemplatesList($domainId, $langId);

		public function getTemplate($templateId);
	};

	interface iUmiHierarchy {
		public function addElement($relId, $hierarchyTypeId, $name, $alt_name, $objectTypeId = false, $domainId = false, $langId = false, $templateId = false);
		public function getElement($elementId, $ignorePermissions = false, $ignoreDeleted = false);
		public function delElement($elementId);

		public function copyElement($elementId, $newRelId, $copySubPages = false);
		public function cloneElement($elementId, $newRelId, $copySubPages = false);


		public function getDeletedList();

		public function restoreElement($elementId);
		public function removeDeletedElement($elementId);
		public function removeDeletedAll();


		public function getParent($elementId);
		public function getAllParents($elementsId, $selfInclude = false);

		public function getChilds($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);
		public function getChildsCount($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);

		public function getPathById($elementId, $ignoreLang = false, $ignoreIsDefaultStatus = false);
		public function getIdByPath($elementPath, $showDisabled = false, &$errorsCount = 0);

		public static function compareStrings($string1, $string2);
		public static function convertAltName($alt_name, $separator = false);
		public static function getTimeStamp();

		public function getDefaultElementId($langId = false, $domainId = false);

		public function moveBefore($elementId, $relId, $beforeId = false);
		public function moveFirst($elementId, $relId);

		public function getDominantTypeId($elementId);

		//public function applyFilter(umiHierarchyFilter);

		public function addUpdatedElementId($elementId);
		public function getUpdatedElements();

		public function unloadElement($elementId);

		public function getElementsCount($module, $method = "");

		public function forceAbsolutePath($bIsForced = true);

		public function getObjectInstances($objectId, $bIgnoreDomain = false, $bIgnoreLang = false, $bIgnoreDeleted = false);

		public function getLastUpdatedElements($limit, $updateTimeStamp = 0);

		public function checkIsVirtual($elementIds);
	};

	interface iUmiHierarchyElement {
		public function getIsDeleted();
		public function setIsDeleted($isDeleted = false);

		public function getIsActive();
		public function setIsActive($isActive = true);

		public function getIsVisible();
		public function setIsVisible($isVisible = true);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getLangId();
		public function setLangId($langId);

		public function getTplId();
		public function setTplId($tplId);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getUpdateTime();
		public function setUpdateTime($timeStamp = 0);

		public function getOrd();
		public function setOrd($ord);

		public function getRel();
		public function setRel($rel_id);

		public function getObject();
		public function setObject(umiObject $object);

		public function setAltName($altName, $autoConvert = true);
		public function getAltName();

		public function setIsDefault($isDefault = true);
		public function getIsDefault();

		public function getParentId();

		public function getValue($propName, $params = NULL);
		public function setValue($propName, $propValue);

		public function getFieldId($FieldName);

		public function getName();
		public function setName($name);

		public function getObjectTypeId();

		public function getHierarchyType();

		public function getObjectId();


		public function getModule();
		public function getMethod();
	};

	interface iUmiHierarchyType {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getExt();
		public function setExt($ext);
	};

	interface iUmiHierarchyTypesCollection {
		public function addType($name, $title, $ext = "");
		public function getType($typeId);
		public function delType($typeId);
		public function getTypeByName($typeName, $extName = false);

		public function getTypesList();
	};
?>