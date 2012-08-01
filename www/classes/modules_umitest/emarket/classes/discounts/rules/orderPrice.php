<?php
	class orderPriceDiscountRule extends discountRule implements orderDiscountRule {
		public function validateOrder(order $order) {
			$orderPrice = $order->getOriginalPrice();
			
			if($this->minimum && ($orderPrice < $this->minimum)) {
				return false;
			}
			
			if($this->maximum && ($orderPrice > $this->maximum)) {
				return false;
			}
			return true;
		}
	};
?>