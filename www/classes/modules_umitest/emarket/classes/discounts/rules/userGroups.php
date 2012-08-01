<?php
	class userGroupsDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
		public function validateOrder(order $order) {
			return $this->validate();
		}
		
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}
		
		public function validate() {
			$customer = customer::get();
			if(is_array($this->user_groups) && is_array($customer->groups)) {
				return sizeof(array_intersect($customer->groups, $this->user_groups));
			} else return false;
		}
	};
?>