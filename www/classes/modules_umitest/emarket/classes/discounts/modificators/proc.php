<?php
	class procDiscountModificator extends discountModificator {
		public function recalcPrice($originalPrice) {
			return $originalPrice - ($originalPrice * $this->proc / 100);
		}
	};
?>