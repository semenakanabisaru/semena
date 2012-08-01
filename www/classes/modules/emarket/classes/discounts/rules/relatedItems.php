<?php
	class relatedItemsDiscountRule extends discountRule implements itemDiscountRule {
		public function validateItem(iUmiHierarchyElement $element) {
			$relatedItems = array();
			foreach($this->related_items as $element) {
				if($element instanceof iUmiHierarchyElement) {
					$relatedItems[] = $element->id;
				}
			}

			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule('emarket');
			if($emarket instanceof def_module) {
				$order = $emarket->getBasketOrder();
				
				foreach($order->getItems() as $orderItem) {
					$element = $orderItem->getItemElement();
					if($element instanceof iUmiHierarchyElement) {
						if(in_array($element->id, $relatedItems)) {
							return true;
						}
					}
				}
				
				return false;
			} else throw new privateException('Emarket module must be installed');
			
		}
	};
?>