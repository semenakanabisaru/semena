<?php
	class customer extends umiObjectProxy {
		public static $defaultExpiration = 2678400;	// 31 days
		protected $isAuth;

		public static function get($nocache = false) {
			static $customer;
			if(!$nocache && !is_null($customer)) {
				return $customer;
			}

			$objects = umiObjectsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();

			if($permissions->isAuth()) {
				$userId = $permissions->getUserId();
				$object = $objects->getObject($userId);
			} else {
				$object = self::getCustomerId();

				//Second try may be usefull to avoid server after-reboot conflicts
				if($object === false) $object = self::getCustomerId(true);
			}

			if($object instanceof iUmiObject) {
				$customer = new customer($object);
				$customer->tryMerge();
				return $customer;
			}
		}

		public function __construct(iUmiObject $object) {
			$permissions = permissionsCollection::getInstance();

			$userId = $permissions->getUserId();
			$guestId = permissionsCollection::getGuestId();
			$this->isAuth = ($userId == $guestId) ? false : $userId;

			parent::__construct($object);
		}

		public function isUser() {
			return (bool) $this->isAuth;
		}

		public function tryMerge() {
			if($this->isUser() && getCookie('customer-id')) {
				$guestCustomer = self::getCustomerId();
				if($guestCustomer instanceof iUmiObject) {
					$this->merge($guestCustomer);
				}
			}
		}


		/**
			* Слить все заказы покупателя в профиль пользователя.
			* Объект покупателя после этого будет уничтожен
		*/
		public function merge(umiObject $customer) {

			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$domainId = $domain->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customer->id);
			$sel->where('domain_id')->equals($domainId);
			$sel->order('id')->desc();

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			foreach($sel as $order) {
				if(!$order->status_id) {
					$this->mergeBasket($order);
					continue;
				}

				$order->customer_id = $userId;
				$order->commit();
			}
			setcookie('customer-id', 0, 1, '/');
			$customer->delete();
		}

		protected function mergeBasket(umiObject $guestBasket) {
			$orderItems = $guestBasket->order_items;

			if(is_array($orderItems)) {
				$userBasket = __emarket_purchasing::getBasketOrder(false);

				if($userBasket) {
					foreach($orderItems as $orderItemId) {
						$orderItem = orderItem::get($orderItemId);
						if($orderItem) {
							$userBasket->appendItem($orderItem);
						}
					}
					$userBasket->commit();
				}
			}

			$guestBasket->delete();
		}

		/**
			* "Заморозить" покупателя (по умолчанию через 31 дней после последнего входа, объект будет удален)
		*/
		public function freeze() {
			$expirations = umiObjectsExpiration::getInstance();
			$expirations->clear($this->id);
		}

		public function __toString() {
			return (string) $this->object->id;
		}

		/**
			* Получить id покупателя-гостя, и, возможно, создать нового.
			* @param Boolean $noCookie = false не использовать данные кук
			* @return Integer id покупателя
		*/
		protected static function getCustomerId($noCookie = false) {
			static $customerId;
			if(is_null($customerId)) {
				$customerId = (int) getCookie('customer-id');
			}

			$customer = selector::get('object')->id($customerId);

			if($customer instanceof iUmiObject != false) {
				$type = selector::get('object-type')->id($customer->getTypeId());

				if($type->getMethod() != 'customer') {
					$customer = null;
				}
			} else {
				$customer = null;
			}

			if(!$customer) {
				$customerId = self::createGuestCustomer();
				$customer = selector::get('object')->id($customerId);
			}

			if(!$customerId) {
				$customerId = self::createGuestCustomer();
			}

			setcookie('customer-id', $customerId, (time() + self::$defaultExpiration), '/');

			$expirations = umiObjectsExpiration::getInstance();
			$expirations->set($customerId, self::$defaultExpiration);

			return $customer;
		}

		/**
			* Создать нового покупателя-гостя
			* @return Integer id нового покупателя
		*/
		protected static function createGuestCustomer() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$objectTypeId = $objectTypes->getBaseType('emarket', 'customer');

			return $objects->addObject(getServer('REMOTE_ADDR'), $objectTypeId);
		}
	};
?>