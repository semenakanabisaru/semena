<?php
	class selfDelivery extends delivery {

		public function validate(order $order) {
			return true;
		}

		public function getDeliveryPrice(order $order) {
			return 0;
		}

	};
?>