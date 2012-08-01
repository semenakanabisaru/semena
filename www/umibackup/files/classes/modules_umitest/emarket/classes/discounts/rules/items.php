<?php
	/**
		* Скидка для определенных товаров.
		* Иногда может быть предпочтительнее, чем скидка на раздел, так как может работать немного быстрее.
	*/
	class itemsDiscountRule extends discountRule implements itemDiscountRule {
		public function validateItem(iUmiHierarchyElement $orderItem) {
			if(!is_array($this->catalog_items)) {
				return false;
			}
			
			foreach($this->catalog_items as $catalogItem) {
				if($catalogItem->id == $orderItem->id) {
					return true;
				}
			}
			
			$parentId = $orderItem->getParentId();
			if($parentId) {
				$hierarchy = umiHierarchy::getInstance();
				$parents = $hierarchy->getAllParents($parentId, true);
				if(isset($parents[0])) unset($parents[0]);
				foreach($this->catalog_items as $catalogItem) {
					if(in_array($catalogItem->id, $parents)) {
						return true;
					}
				}
			}
			
			return false;
		}
	};
?>