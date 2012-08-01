<?php
/**
	* Класс orderItem связывает объекты каталога в магазине и непосредственно заказ (корзину товаров).
	* Подобная связка необходима для обеспечения следующих требования:
	* 1. Поддержка составных товаров (с опциями)
	* 2. Унификация доступа к параметрам товара в заказе
*/
	class orderItem extends umiObjectProxy {
		protected $price, $totalOriginalPrice, $totalActualPrice, $amount, $discount, $itemElement, $isDigital = false;
		
		/**
			* Получить экземпляр наименования в заказе с учетом текущих настроек интернет-магазина и типа товара.
			* @param Integer $objectId id объекта, по которому нужно строить класс
			* @return orderItem объект наименования в заказе (допускается, что может вернуться любой класс-наследник от orderItem)
		*/
		public static function get($objectId) {
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);
			
			if($object instanceof iUmiObject == false) {
				throw new privateException("Couldn't load order item object #{$objectId}");
			}
			
			$classPrefix = "";
			if($object->item_type_id) {
				$classPrefix = objectProxyHelper::getClassPrefixByType($object->item_type_id);
				objectProxyHelper::includeClass('emarket/classes/orders/items/', $classPrefix);
			}
			
			$className = $classPrefix ? ($classPrefix . 'OrderItem') : 'orderItem';
			return new $className($object);
		}


		/**
			* Создать новый товар для заказа.
			* Выбор класса осуществляется следующим образом:
			* 1. берется элемент $elementId - товар каталога
			* 2. у него берется значение свойства "item_type_id" ("Тип товара"), которое является справочником
			* 3. у него в свою очередь берется свойство "class_name", по которому определяется необходимый класс
			* Если тип не указан, то используется класс orderItem.
			* @param Integer $elementId
			* @param Integer|Boolean $storeId = false id склада, на котором находится товар
			* @return orderItem товар в заказе (объект класса orderItem или его наследнка)
		*/
		public static function create($elementId, $storeId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$emarket = cmsController::getInstance()->getModule('emarket');
			$objectTypeId = $objectTypes->getBaseType('emarket', 'order_item');
			$hierarchy = umiHierarchy::getInstance();
			
			$objectId = $objects->addObject('', $objectTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof iUmiObject == false) {
				throw new coreException("Couldn't load order item object #{$objectId}");
			}
			
			$element = $hierarchy->getElement($elementId);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException("Page #{$elementId} not found");
			}
			$price = $emarket->getPrice($element, true);
			
			$object->item_price = $price;
			$object->item_amount = 0;
			
			$itemTypeId = self::getItemTypeId($element->getObjectTypeId());
			$object->item_type_id = $itemTypeId;
			
			$object->item_link = $element;
			$object->name = $element->name; 
			
			return self::get($object->getId());
		}
		

		/**
			* Удалить объект, представляющий товар в заказе
		*/
		public function remove() {
			$objects = umiObjectsCollection::getInstance();
			if($this->object instanceof umiObject) {
				$objects->delObject($this->object->getId());
			}
		}


		/**
			* Получить название наименования
			* @return String название наименования заказа
		*/		
		public function getName() {
			return $this->object->getName();
		}


		/**
			* Получить количество товара, которое добавлено к заказу
			* @return Integer количество товаров в заказе
		*/
		public function getAmount() {
			return $this->amount;
		}


		/**
			* Изменить количество товаров в заказе
			* @param Integer $amount новое значение количества товаров в заказе
		*/
		public function setAmount($amount) {
			$this->amount = (int) $amount;
		}


		/**
			* Получить стоимость всего наименования без учета скидок
			* @return Float стоимость наименования без учета скидок
		*/
		public function getTotalOriginalPrice() {
			return $this->totalOriginalPrice;
		}


		/**
			* Получить стоимость всего наименования с учетом скидок
			* @return Float стоимость наименования с учетом скидок
		*/
		public function getTotalActualPrice() {
			return $this->totalActualPrice;
		}


		/**
			* Получить стоимость 1 единицы товара (без скидок)
			* @return Float стоимость 1 единицы товара без скидки
		*/
		public function getItemPrice() {
			return $this->price;
		}


		/**
			* Узнать, является ли товар цифровым.
			* @return Boolean в классе orderItem всегда возвращает false. Может быть перегружен ниже.
		*/
		public function getIsDigital() {
			return $this->isDigital;
		}


		/**
			* Получить страницу-объект каталога, которая является товаром этого наименования
			* @return iUmiHierarchyElement объект в каталоге
		*/
		public function getItemElement() {
			$symlink = $this->object->item_link;
			if(is_array($symlink) && sizeof($symlink)) {
				list($item) = $symlink;
				return $item;
			} else $this->delete();
			return null;
		}


		/**
			* Получить скидку, которая действует на наименование
			* @return itemDiscount скидка на наименование
		*/
		public function getDiscount() {
			return $this->discount;
		}


		/**
			* Установить скидку на наименование в заказе
			* @param itemDiscount $discount
		*/
		public function setDiscount(itemDiscount $discount = null) {
			$this->discount = $discount;
		}


		/**
			* Пересчитать параметры наименования и обновить свойства наименования в БД
		*/
		public function refresh() {
			$element = $this->getItemElement();
			if (!$element) {
				return false;
			}
			
			$eventPoint = new umiEventPoint("orderItem_refresh");
			$eventPoint->setMode('before');
			$eventPoint->addRef("orderItem", $this);
			$eventPoint->call();

				$discount = itemDiscount::search($element);
				$this->setDiscount($discount);
			
			$totalOriginalPrice = $this->getItemPrice() * $this->amount;
			if($discount instanceof itemDiscount) {
				$totalActualPrice = $discount->recalcPrice($totalOriginalPrice);
			} else {
				$totalActualPrice = $totalOriginalPrice;
			}
			
			$eventPoint->setMode('after');
			$eventPoint->setParam("totalOriginalPrice", $totalOriginalPrice);
			$eventPoint->addRef("totalActualPrice", $totalActualPrice);
			$eventPoint->call();


			$this->totalOriginalPrice = $totalOriginalPrice;
			$this->totalActualPrice = $totalActualPrice;
			
			$this->commit();
			
			return true;
		}


		/**
			* Применить внесенные изменения
		*/
		public function commit() {
			$object = $this->object;
			
			$object->item_price = $this->price;
			$object->item_total_original_price = $this->totalOriginalPrice;
			$object->item_total_price = $this->totalActualPrice;
			$object->item_amount = $this->amount;
			$object->item_discount_id = ($this->discount ? $this->discount->getId() : false);
			$object->item_link = $this->itemElement;
			
			parent::commit();
		}

		
		/**
			* Конструктор класса. Косвенно вызывается через orderItem::get() и orderItem::create()
			* @param umiObject $object объект наименования (для работы с ним мы наследуем umiObjectProxy)
		*/
		protected function __construct(umiObject $object) {
			parent::__construct($object);
			$discount = $this->getDiscount();
			
			$this->price = (float) $object->item_price;
			$this->totalOriginalPrice = (float) $object->item_total_original_price;
			$this->totalActualPrice = (float) $object->item_total_price;
			$this->amount = (int) $object->item_amount;
			$this->discount = itemDiscount::get($object->item_discount_id);
			$this->itemElement = $object->item_link;
		}

		/**
			* Найти скидку, применимую к этому наименованию в заказе
		*/		
		protected function searchDiscount() {
			$element = $this->getItemElement();
			if($element instanceof iUmiHierarchyElement) {
				$discount = itemDiscount::search($element);
				if($discount instanceof discount) {
					return $discount;
				}
			}
			return null;
		}
		
		/**
			* Получить оригинальную стоимость наименования
		*/
		protected function getElementPrice() {
			$element = $this->object->item_link;
			if(sizeof($element) && $element[0] instanceof iUmiHierarchyElement) {
				$emarket = cmsController::getInstance()->getModule('emarket');
				return $emarket->getPrice($element[0], true);
			} else return null;
		}
		
		private static function getItemTypeId($objectTypeId) {
			if($prefix = self::getClassPrefix($objectTypeId))  {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'item_type');
				$sel->where('class_name')->equals($prefix);
				return $sel->first ? $sel->first->id : null;
			} else return null;
		}
		
		private static function getClassPrefix($objectTypeId) {
			static $cache = array();
			if(isset($cache[$objectTypeId])) return $cache[$objectTypeId];
			
			$objectType = selector::get('object-type')->id($objectTypeId);
			$prefixes = self::getClassPrefixes();
			
			foreach($prefixes as $prefix => $conds) {
				foreach($conds as $type => $values) {
					foreach($values as $value) {
						if($type == 'fields' && $objectType->getFieldId($value)) {
							return $cache[$objectTypeId] = $prefix;
						}
						
						if($type == 'groups' && $objectType->getFieldsGroupByName($value)) {
							return $cache[$objectTypeId] = $prefix;
						}
					}
				}
			}
			
			return $cache[$objectTypeId] = '';
		}
		
		private static function getClassPrefixes() {
			static $result = null;
			if(is_array($result)) {
				return $result;
			}

			$result = array();
			$req = 'emarket.order-types.';
			$l = strlen($req);
			
			$config = mainConfiguration::getInstance();
			$options = $config->getList('modules');
			foreach($options as $option) {
				if(substr($option, 0, $l) != $req) continue;
				$optionArr = explode(".", substr($option, $l));
				if(sizeof($optionArr) != 2) continue;
				list($classPrefix, $valueType) = $optionArr;
				
				$value = $config->get('modules', $option);
				
				$result[$classPrefix][$valueType] = $value;
			}
			return $result;
		}
	};	
?>