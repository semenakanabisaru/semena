<?php
	class courierDelivery extends delivery {
		public function validate(order $order) {
			return true;
		}

		public function getDeliveryPrice(order $order) {
			$deliveryPrice = $this->object->price;
			$minOrderPrice = $this->object->order_min_price;

			if(is_null($minOrderPrice)){
				return $deliveryPrice;
			}

			$orderPrice = $order->getActualPrice() - $order->getDeliveryPrice();
			return ($orderPrice < $minOrderPrice) ? $deliveryPrice : 0;
		}
	};
?>