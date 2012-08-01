<?php
/**
	* Скидка на заказ в интернет-магазине
*/
	class orderDiscount extends discount {
		/**
			* Проверить, применима ли скидка к заказу $order
			* @param order $order
			* @return Boolean
		*/
		public function validate(order $order) {
			$rules = $this->getDiscountRules();
			
			$validateCount = 0;
			foreach($rules as $rule) {
				if($rule instanceof orderDiscountRule == false) {
					continue;
				}
				
				if($rule->validateOrder($order) == false) {
					return false;
				}
				$validateCount++;
			}
			return $validateCount > 0;
		}
		
		
		/**
			* Найти наиболее подходящую скидку для заказа $order
			* @param order $order заказ
			* @return orderDiscount самая подходящая скидка для заказа $order
		*/
		public static function search(order $order) {
			$cmsController = cmsController::getInstance();
			$emarket = $cmsController->getModule('emarket');

			if($emarket instanceof def_module == false) {
				throw new coreException('Emarket module must be installed in order to calculate discounts');
			}
			
			$allDiscounts = $emarket->getAllDiscounts('order'); $discounts = array();
			foreach($allDiscounts as $discountObject) {
				$discount = discount::get($discountObject->id);
				if($discount instanceof orderDiscount == false) continue;
				if($discount->validate($order)) $discounts[] = $discount;
			}
			
			switch(sizeof($discounts)) {
				case 0: return null;
				default:
					$orderPrice = $order->getOriginalPrice();
					$maxDiscount = null; $minPrice = null;
					foreach($discounts as $i => $discount) {
						$price = $discount->recalcPrice($orderPrice);
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