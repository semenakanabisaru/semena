<?php
	interface iUmiSelection {
		public function forceHierarchyTable($isForced = true);

		public function addObjectType($objectTypeId);
		public function addElementType($elementTypeId);

		public function addLimit($resultsPerQueryPage, $resultsPage = 0);

		public function setOrderByProperty($fieldId, $asc = true);
		public function setOrderByOrd();
		public function setOrderByRand();
		public function setOrderByName($asc = true);
		public function setOrderByObjectId($asc = true);

		public function addHierarchyFilter($elementId, $depth = 0, $ignoreIsDefault = false);

		public function addPropertyFilterBetween($fieldId, $minValue, $maxValue);
		public function addPropertyFilterEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterNotEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterLike($fieldId, $likeValue, $caseInsencetive = true);
		public function addPropertyFilterMore($fieldId, $val);
		public function addPropertyFilterLess($fieldId, $val);
		public function addPropertyFilterIsNull($fieldId);
		public function addActiveFilter($active);
		public function addOwnerFilter($owner);
		public function addObjectsFilter($vOids);
		public function addElementsFilter($vEids);

		public function addNameFilterEquals($exactValue);
		public function addNameFilterLike($likeValue);

		public function addPermissions($userId = false);
		public function setPermissionsLevel($level = 1);
		
		public function setDomainId($domainId = false);
		public function setLangId($langId = false);

		public function setConditionModeOR();
		
		public function setIsDomainIgnored($isDomainIgnored = false);
		public function setIsLangIgnored($isLangIgnored = false);
		
		public function resetTextSearch();
		
		public function result();
		public function count();
	}
?>