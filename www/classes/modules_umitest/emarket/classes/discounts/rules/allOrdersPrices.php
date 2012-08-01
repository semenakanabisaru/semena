<?php
	class allOrdersPricesDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
		public function validateOrder(order $order) {
			$orderPricesSum = $this->getPricesSum();

			if($this->minimal && $orderPricesSum < $this->minimal) {
				return false;
			}

			if($this->maximum && $orderPricesSum > $this->maximum) {
				return false;
			}

			return true;
		}

		public function validateItem(iUmiHierarchyElement $element) {
			$orderPricesSum = null;
			if($orderPricesSum == null) {
				$orderPricesSum = $this->getPricesSum();
			}

			if($this->minimal && $orderPricesSum < $this->minimal) {
				return false;
			}

			if($this->maximum && $orderPricesSum > $this->maximum) {
				return false;
			}

			return true;
		}

		protected function getPricesSum() {
			$orders = $this->getCustomerOrders();

			$price = 0;
			foreach($orders as $orderObject) {
				$order = order::get($orderObject->id);
				$price += $order->getActualPrice();
			}

			return $price;
		}

		protected function getCustomerOrders() {

			static $customerOrders = null;
			if(!is_null($customerOrders)) {
				return $customerOrders;
			}
			$customer = customer::get();

			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$domainId = $domain->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customer->id);
			$sel->where('domain_id')->equals($domainId);
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			return $customerOrders = $sel->result;
		}
	};
?>