<?php
/**
    * Класс, предоставляющий заказ, либо корзину заказов в интернет-магазине
*/
    class order extends umiObjectProxy {
        protected $items = Array(), $actualPrice, $originalPrice, $totalAmount, $discount, $domain;

        /**
            * Получить экземпляр заказа по его id. Если id заказа false, то метод вернет текущий объект со статусом "в корзине".
            * Если такого объекта еще нет, то он его создаст
            * @param Integer $orderId = false id заказа
            * @return order заказ
        */
        public static function get($orderId = false) {
            if($orderId == false) {
                return $object = self::create();
            }

            $objects = umiObjectsCollection::getInstance();
            $object = $objects->getObject($orderId);

            if($object instanceof iUmiObject) {
                return new order($object);
            } else {
                return null;
            }

        }

        /**
            * Создать новый пустой заказ
            * @return Integer $order id нового заказа
        */
        public static function create($useDummyOrder = false) {
            $objectTypes = umiObjectTypesCollection::getInstance();
            $objects = umiObjectsCollection::getInstance();
            $permissions = permissionsCollection::getInstance();
            $cmsController = cmsController::getInstance();


            $orderTypeId = $objectTypes->getBaseType('emarket', 'order');

            if($useDummyOrder) {

            	$cmsController = cmsController::getInstance();
				$domain = $cmsController->getCurrentDomain();
				$domainId = $domain->getId();

                $sel = new selector('objects');
                $sel->types('object-type')->name('emarket', 'order');
                $sel->where('customer_id')->isnull(true);
                $sel->where('domain_id')->equals($domainId);
                if($sel->length()) {
                    $orderId = $sel->first->id;
                } else {
                    $orderTypeId = $objectTypes->getBaseType('emarket', 'order');
                    $orderId = $objects->addObject('dummy', $orderTypeId);
                    $order = $objects->getObject($orderId);
                    if($order instanceof iUmiObject == false) {
                        throw new publicException("Can't load dummy object for order #{$orderId}");
                    } else {
                    	$order->setValue('domain_id', $domainId);
                    	$order->commit();
                    }
                }
                return self::get($orderId);
            }

            $domain = $cmsController->getCurrentDomain();
            $domainId = $domain->getId();

            $managerId = 0;
            $statusId = self::getStatusByCode('basket');
            $customerId = customer::get()->getId();
            $createTime = time();

            $orderId = $objects->addObject('', $orderTypeId);
            $order = $objects->getObject($orderId);
            if($order instanceof iUmiObject == false) {
                throw new publicException("Can't load created object for order #{$orderId}");
            }
            $order->domain_id = $domainId;
            $order->manager_id = $managerId;
            $order->status_id = $statusId;
            $order->customer_id = $customerId;
            $order->order_create_date = $createTime;
            $order->commit();

            return self::get($orderId);
        }

        /**
            * Получить id объекта статуса заказа
            * @param String $codename код статуса заказа
            * @param String $statusClass = 'order_status' группа статуса
            * @return Integer id объекта статуса заказа
        */
        public static function getStatusByCode($codename, $statusClass = 'order_status') {
            $sel = new selector('objects');
            $sel->types('object-type')->name('emarket', $statusClass);
            $sel->where('codename')->equals($codename);

            return $sel->first ? $sel->first->id : false;
        }

        /**
            * Получить код статуса заказа
            * @param Integer $id идентификатор объекта статуса заказа
            * @return String код статуса заказа
        */
        public static function getCodeByStatus($id) {
            $status = selector::get('object')->id($id);
            return $status ? $status->codename : false ;
        }


        /**
            * Получить список наименований в заказе
            * @return Array массив, состоящий из экземпляров или класса или потомков класса orderItem
        */
        public function getItems() {
            return $this->items;
        }


        /**
            * Добавить наименование в заказ
            * @param orderItem $orderItem наменование заказа (объект класса orderItem, либо его потомок)
        */
        public function appendItem(orderItem $orderItem) {
            foreach($this->items as $item) {
                if($item->getId() == $orderItem->getId()) {
                    return false;
                }
            }
            $orderItem->refresh();
            $this->items[] = $orderItem;
        }


        /**
            * Удалить наименование из заказа. После удаления из заказа объект orderItem будет уничтожен
            * @param orderItem $orderItem наименование в заказе  (объект класса orderItem, либо его потомок)
        */
        public function removeItem(orderItem $orderItem) {
            foreach($this->items as $i => $item) {
                if($item->getId() == $orderItem->getId()) {
                    unset($this->items[$i]);
                    return true;
                }
            }
            return false;
        }


        /**
            * Получить экземпляр наименования заказа по id
            * @param Integer $itemId
            * @return orderItem|Boolean
        */
        public function getItem($itemId) {
            foreach($this->items as $item) {
                if($item->getId() == $itemId) return $item;
            }
            return false;
        }


        /**
            * Узнать, есть ли наименования в заказе
            * @return Boolean true, если в заказе есть хотя бы 1 наименование, в противном случае - false
        */
        public function isEmpty() {
            return (sizeof($this->items) == 0);
        }


        /**
            * Очистить список товаров в заказе. При этом будут уничтожены все orderItem'ы
        */
        public function earse() {
            $this->items = Array();
        }


        /**
            * Получить текущий статус заказа
            * @return Integer id объекта-статуса заказа
        */
        public function getOrderStatus() {
            return $this->object->status_id;
        }


        /**
            * Изменить текущий статус заказа
            * @param Integer $statusId id объекта-статуса заказа
        */
        public function setOrderStatus($newStatusId) {
            if($newStatusId && !is_numeric($newStatusId)) {
                $newStatusId = self::getStatusByCode($newStatusId, 'order_status');
            }
            $oldStatusId = $this->object->status_id;

            $event = new umiEventPoint('order-status-changed');
            $event->addRef('order', $this);
            $event->setParam('old-status-id', $oldStatusId);
            $event->setParam('new-status-id', $newStatusId);

            if($oldStatusId != $newStatusId) {
                $event->setMode('before');
                $event->call();
            }

            $this->object->status_id = $newStatusId;

            if($oldStatusId != $newStatusId) {
                $event->setMode('after');
                $event->call();

                $status = selector::get('object')->id($newStatusId);
                switch($status->codename) {
                    case 'waiting': {
                        $this->reserve();
                        break;
                    }

                    case 'canceled': {
                        $this->unreserve();
                        break;
                    }

                    case 'ready': {
                        $this->writeOff();
                        break;
                    }
                }
            }
        }


        /**
            * Получить текущий статус оплаты заказа
            * @return Integer id объекта-статуса оплаты
        */
        public function getPaymentStatus() {
            return $this->object->payment_status_id;
        }


        /**
            * Изменить текущий статус оплаты заказа
            * @param Integer $statusId id объекта-статуса оплаты
        */
        public function setPaymentStatus($newStatusId) {
            if($newStatusId && !is_numeric($newStatusId)) {
                $statusCode  = $newStatusId;
                $newStatusId = self::getStatusByCode($newStatusId, 'order_payment_status');
            } else {
                $statusCode  = self::getCodeByStatus($newStatusId);
            }
            $oldStatusId = $this->object->payment_status_id;

            $event = new umiEventPoint('order-payment-status-changed');
            $event->addRef('order', $this);
            $event->setParam('old-status-id', $oldStatusId);
            $event->setParam('new-status-id', $newStatusId);

            if($oldStatusId != $newStatusId) {
                $event->setMode('before');
                $event->call();
            }

            $this->object->payment_status_id = $newStatusId;

            if($oldStatusId != $newStatusId) {
                $event->setMode('after');
                $event->call();
            }

            switch($statusCode) {
                case 'initialized' : $this->setOrderStatus('payment');   break;
                case 'declined'    : $this->setOrderStatus('execution'); break;
                case 'accepted'    : {
                            $this->object->payment_date = new umiDate();
                            $this->order();
                            break;
                }
            }
        }


        /**
            * Получить текущий статус доставки заказа
            * @return Integer id объекта-статуса доставки
        */
        public function getDeliveryStatus() {
            return $this->object->order_delivery_props;
        }


        /**
            * Изменить текущй статус доставки заказа
            * @param Integer $statusId id объекта-статуса доставки
        */
        public function setDeliveryStatus($newStatusId) {
            if($newStatusId && !is_numeric($newStatusId))
                $newStatusId = self::getStatusByCode($newStatusId, 'order_delivery_status');
            $oldStatusId = $this->object->delivery_status_id;

            $event = new umiEventPoint('order-delivery-status-changed');
            $event->addRef('order', $this);
            $event->setParam('old-status-id', $oldStatusId);
            $event->setParam('new-status-id', $newStatusId);

            if($oldStatusId != $newStatusId) {
                $event->setMode('before');
                $event->call();
            }

            $this->object->delivery_status_id = $newStatusId;

            if($oldStatusId != $newStatusId) {
                $event->setMode('after');
                $event->call();
            }
        }


        /**
            * Получить цену всего заказа с учетом скидки на этот заказ
            * @return Float цена с учетом скидки на заказ
        */
        public function getActualPrice() {
            return $this->actualPrice;
        }

        /**
            * Получить цену всего заказа без учета скидки на этот заказ
            * @return Float цена без учета скидки на заказ
        */
        public function getOriginalPrice() {
            return $this->originalPrice;
        }

        /**
            * Получить количество наименований в заказе
            * @return Integer количество наименований в заказе
        */
        public function getTotalAmount() {
            return $this->totalAmount;
        }

        /**
            * Получить стоимость доставки
            * @return Integer стоимость доставки
        */
        public function getDeliveryPrice() {
            return $this->delivery_price;
        }

        /**
            * Пересчитать содержимое корзины
        */
        public function refresh() {
            $object = $this->object; $items = $this->getItems();
            $originalPrice = 0; $totalAmount = 0;

            $eventPoint = new umiEventPoint("order_refresh");
            $eventPoint->setMode('before');
            $eventPoint->addRef("order", $object);
            $eventPoint->setParam("items", $items);
            $eventPoint->call();

            foreach($items as $item) {
                $succ = $item->refresh();
                if ($succ === false) {
                	$this->removeItem($item);
                	continue;
				}
                $originalPrice += $item->getTotalActualPrice();
                $totalAmount += $item->getAmount();
            }

            $discount = $this->searchDiscount();
            if($discount instanceof orderDiscount) {
                $actualPrice = $discount->recalcPrice($originalPrice);
            } else {
                $actualPrice = $originalPrice;
            }

            $actualPrice += (float) $this->delivery_price;

            $eventPoint->setMode('after');
            $eventPoint->setParam("originalPrice", $originalPrice);
            $eventPoint->setParam("totalAmount", $totalAmount);
            $eventPoint->addRef("actualPrice", $actualPrice);
            $eventPoint->call();

            $this->originalPrice = $originalPrice;
            $this->actualPrice = $actualPrice;
            $this->totalAmount = $totalAmount;
            $this->discount = $discount;



            $this->commit();
        }


        /**
            * Получить стоимость всего заказа с учетом скидки на заказ
            * @return Float цена заказа с учетом скидки на заказ
        */
        //public function getOrderDiscountedPrice();


        /**
            * Получить id клиента. Это может быть как id пользователя, так и id временного покупателя
            * @return Integer id объекта-клиента
        */
        public function getCustomerId() {
            return $this->object->customer_id;
        }

        /**
            * Получить домен, в котором производится заказ
            * @return domain домена
        */
        public function getDomain() {
            return $this->domain;
        }


        /**
            * Изменить домен, в котором производится заказ
            * @param domain $domain домена
        */
        public function setDomainId(domain $domai) {
            $this->domain = $domain;
        }

        /**
            * Получить текущую скидку на этот заказ
            * @return discount скидка на заказ
        */
        public function getDiscount() {
            return $this->discount;
        }

        /**
            * Назначить скидку на заказ
            * @param discount $discount скидка на заказ
        */
        public function setDiscount(discount $discount = null) {
            if($discount && ($discount->validate($this) == false)) {
                $discount = null;
            }
            $this->discount = $discount;
        }


        /**
            * Сгенерировать номер заказа
        */
        public function generateNumber() {
            $config = mainConfiguration::getInstance();
            $className = $config->get('modules', 'emarket.numbers') . 'OrderNumber';
            if(class_exists($className)) {
                $object = new $className($this);
                return $object->number();
            } else {
                throw new coreException("Can't load order numbers generator. Check modules.emarket.numbers config setting");
            }
        }

        public function order() {
            $status = $this->getOrderStatus();
            if(is_null($status) || self::getCodeByStatus($status) == 'payment') {
                $this->generateNumber();
                $this->object->order_date = time();
                $this->setOrderStatus('waiting');
                $this->object->commit();
                return true;
            } else return false;
        }

        public function commit() {
            $object = $this->object;

            $object->total_original_price = $this->originalPrice;
            $object->total_price = $this->actualPrice;
            $object->total_amount = $this->totalAmount;
            $object->domain_id = ($this->domain) ? $this->domain->getId() : false;
            $object->order_discount_id = ($this->discount ? $this->discount->getId() : false);
            $object->http_referer = getSession("http_referer");

            $this->applyItems();

            parent::commit();
        }


        /**
            * Получить заказ по объекту заказа
            * @param Integer $object объект заказа
        */
        protected function __construct(umiObject $object) {
            parent::__construct($object);

            $domains = domainsCollection::getInstance();

            $this->totalAmount = (int) $object->total_amount;
            $this->originalPrice = (float) $object->total_original_price;
            $this->actualPrice = (float) $object->total_price;
            $this->domain = $domains->getDomain($domains->getDomainId($object->domain_id));
            $this->discount = orderDiscount::get($object->order_discount_id);

            $this->readItems();
        }

        /**
            * Загрузить список наименований в заказе из объекта заказа
        */
        protected function readItems() {
            $objectItems = $this->object->order_items;
            $items = array();
            foreach($objectItems as $objectId) {
                try {
                    $items[] = orderItem::get($objectId);
                } catch (privateException $e) {}
            }
            $this->items = $items;
        }

        /**
            * Сохранить данные о наименованиях заказа в объект заказа
        */
        protected function applyItems() {
            $values = Array();
            foreach($this->items as $item) {
                $values[] = $item->getId();
            }
            $this->object->order_items = $values;
        }

        /**
            * Определить скидку для этого заказа
            * @param orderDiscount $discount скидка заказа
        */
        public function searchDiscount() {
            $discount = orderDiscount::search($this);
            return ($discount instanceof orderDiscount) ? $discount : null;
        }


        public function reserve($reserve = true) {
            if($this->is_reserved == $reserve) {
                return false;
            }

            $primaryStore = $this->getPrimaryStore();
            if(!$primaryStore) return false;

            foreach($this->getItems() as $item) {
                if($element = $item->getItemElement()) {
                    $amount = $item->getAmount();
                    $storesState = $element->getValue('stores_state', array('filter' => array('rel' => $primaryStore->id)));
                    if(sizeof($storesState)) {
                        $total = $storesState[0];
                        $total = (int) getArrayKey($total, 'int');
                    } else {
                        $total = 0;
                    }

                    $reserved = (int) $element->reserved + $amount * ($reserve ? 1 : -1);
                    $element->reserved = ($reserved > 0) ? ($reserved > $total ? $total : $reserved) : 0;
                    $element->commit();
                }
            }
            $this->is_reserved = $reserve;
            $this->commit();

            return true;
        }

        public function unreserve() {
            return $this->reserve(false);
        }

        public function writeOff() {
            if(!$this->is_reserved) return false;

            $primaryStore = $this->getPrimaryStore();
            if(!$primaryStore) return false;

            foreach($this->getItems() as $item) {
                if($element = $item->getItemElement()) {
                    $amount = $item->getAmount();
                    $storesState = $element->getValue('stores_state');
                    foreach($storesState as $i => $storeState) {
                        $total = getArrayKey($storeState, 'int');
                        $id = getArrayKey($storeState, 'rel');
                        if($primaryStore->id == $id) {
                            $storesState[$i]['int'] = $total - $amount;
                            $element->setValue('stores_state', $storesState);
                            break;
                        }
                    }

                    $reserved = (int) $element->reserved - $amount;
                    $element->reserved = ($reserved > 0) ? ($reserved) : 0;
                    $element->commit();
                }
            }

            $this->is_reserved = false;
            $this->commit();
            return true;
        }


        private function getPrimaryStore() {
            $stores = new selector('objects');
            $stores->types('object-type')->name('emarket', 'store');
            $stores->where('primary')->equals(true);
            return $stores->first;
        }
    };
?>
