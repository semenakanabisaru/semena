<?php
	class usersDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
		public function validateOrder(order $order) {
			return $this->validate();
		}
		
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}
		
		public function validate() {
			if(is_array($this->users)) {
				$customer = customer::get();
				return in_array($customer->id, $this->users);
			} else return false;
		}
	};
?>