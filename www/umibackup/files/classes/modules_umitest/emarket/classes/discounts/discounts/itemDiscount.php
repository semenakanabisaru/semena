<?php
/**
	* Скидки, применяемые к товарам интернет-магазина
*/
	class itemDiscount extends discount {
		/**
			* Проверить, подходит ли скидка для товара $element
			* @param iUmiHierarchyElement $element
			* @return Boolean
		*/
		public function validate(iUmiHierarchyElement $element) {
			$rules = $this->getDiscountRules();
			
			$validateCount = 0;
			foreach($rules as $rule) {
				if($rule instanceof itemDiscountRule == false) {
					continue;
				}
				
				if($rule->validateItem($element) == false) {
					return false;
				}
				$validateCount++;
			}
			return $validateCount > 0;
		}
		
		/**
			* Найти наиболее оптимальную скидку для товара $element
			* @param iUmiHierarchyElement $element товар в каталоге
			* @return itemDiscount скидка на товар
		*/
		final public static function search(iUmiHierarchyElement $element) {
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule('emarket');

			if($emarket instanceof def_module == false) {
				throw new privateException('Emarket module must be installed in order to calculate discounts');
			}
			
			$allDiscounts = $emarket->getAllDiscounts('item'); $discounts = array();
			foreach($allDiscounts as $discountObject) {
				$discount = discount::get($discountObject->id);
				if($discount instanceof itemDiscount == false) continue;
				if($discount->validate($element))  $discounts[] = $discount; 
			}
			
			
			switch(sizeof($discounts)) {
				case 0: return null;
				default:
					$elementPrice = $element->price;
					$maxDiscount = null; $minPrice = null;
					foreach($discounts as $i => $discount) {
						$price = $discount->recalcPrice($elementPrice);
						if($price <= 0) continue;
						
						if(is_null($minPrice) || $minPrice > $price) {
							$minPrice = $price;
							$maxDiscount = $discount;
						}
					}
					return $maxDiscount;
			}
		}
	};
?>