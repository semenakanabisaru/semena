<?php
	class absoluteDiscountModificator extends discountModificator {
		public function recalcPrice($originalPrice) {
			return $originalPrice - $this->size;
		}
	};
?>