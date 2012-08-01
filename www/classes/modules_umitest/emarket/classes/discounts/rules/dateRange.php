<?php
	class dateRangeDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
		public function validateOrder(order $order) {
			return $this->validate();
		}
		
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}
	
		public function validate() {
			$startDate = $this->start_date;
			$endDate = $this->end_date;
			
			
			
			if($startDate instanceof umiDate) {
				if($startDate->getDateTimeStamp() > time()) {
					return false;
				}
			}
			
			if($endDate instanceof umiDate) {
				if($endDate->getDateTimeStamp() < time()) {
					return false;
				}
			}
			
			return true;
		}
	};
?>