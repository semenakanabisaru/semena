<?php
/**
	* Базовый класс для скидок. Далее скидки делятся на скидки для заказа и скидки для наименований/категорий
*/
	abstract class discount extends umiObjectProxy {
		protected $object, $modificator, $rules = Array();
		
		/**
			* Конструктор скидки
			* @param umiObject $object объект данных скидки
		*/
		protected function __construct(umiObject $object) {
			parent::__construct($object);

			if($modificatorId = $object->getValue('discount_modificator_id')) {
				$this->modificator = discountModificator::get($modificatorId, $this);
			}
			
			if($rules = $object->getValue('discount_rules_id')) {
				foreach($rules as $ruleId) {
					$this->rules[] = discountRule::get($ruleId, $this);
				}
			}
		}


		/**
			* Получить название скидки
			* @return String название скидки
		*/
		public function getName() {
			return $this->object->getName();
		}


		/**
			* Изменить название скидки
			* @param String $name новое название скидки
		*/
		public function setName($name) {
			$this->object->setName($name);
		}


		/**
			* Получить список правил скидки
			* @return Array список правил скидки (массив объектов класса discountRule)
		*/
		public function getDiscountRules() {
			return $this->rules;
		}


		/**
			* Добавить правило скидки
			* @param discountRule $discountRule правило скидки
		*/
		public function appendDiscountRule(discountRule $discountRule) {
			foreach($this->rules as $rule) {
				if($rule->getId() == $discountRule->getId()) {
					return;
				}
			}
			$this->rules[] = $discountRule;
			
			$value = array();
			foreach($this->rules as $rule) {
				$value[] = $rule->getId();
			}
			
			$this->object->discount_rules_id = $value;
			$this->commit();
		}


		/**
			* 
			* @param discountRule $discountRule
		*/
//		public function removeDiscountRule(discountRule $discountRule);


		/**
			* Получить модификатор цены скидки
			* @return discountModificator модификатор цены
		*/
		public function getDiscountModificator() {
			return $this->modificator;
		}


		/**
			* Установить модификатор цены скидки
			* @param discountModificator $discountModificator модификатор цены скидки
		*/
		public function setDiscountModificator(discountModificator $discountModificator) {
			$this->modificator = $discountModificator;
			$this->object->discount_modificator_id = $discountModificator->getId();
			$this->commit();
		}
		
		/**
			* Выполнить пересчет цены $price
			* @param Float $price цена
			* @return Float пересчитанная цена с учетом скидки
		*/
		public function recalcPrice($price) {
			$modificator = $this->getDiscountModificator();
			if($modificator instanceof discountModificator) {
				return $modificator->recalcPrice($price);
			} else {
				throw new coreException("Discount modificator couldn't be loaded");
			}
		}


		/**
			* Получить экземпляр скидки по ее id
			* @param Integer $discountId id скидки
			* @return discount объект скидки, экземпляр класса-потомка discount
		*/
		public static function get($discountId) {
			static $cache = array();
			
			if(!$discountId) {
				return null;
			}

			if(isset($cache[$discountId])) {
				return $cache[$discountId];
			}
			
			$objects = umiObjectsCollection::getInstance();
			$discountObject = $objects->getObject($discountId);
			if($discountObject instanceof iUmiObject == false) {
				return false;
			}
			
			$discountTypeId = $discountObject->discount_type_id;
			$discountTypeObject = $objects->getObject($discountTypeId);
			if($discountTypeObject instanceof iUmiObject == false) return null;
			
			$className = $discountTypeObject->codename . 'Discount';
			self::includeDiscount($className);
			
			return $cache[$discountId] = new $className($discountObject);
		}
		

		/**
			* Создать новую скидку
			* @param String $discountName название скидки
			* @param Integer $discountTypeId тип скидки
			* @return discount скидка
		*/
		public static function add($discountName, $discountTypeId) {
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			
			$objectTypeId = $objectTypes->getBaseType('emarket', 'discount');
			$objectId = $objects->addObject($discountName, $objectTypeId);
			$object = $objects->getObject($objectId);
			
			try {
				$object->discount_type_id = $discountTypeId;
			} catch (valueRequiredException $e) {
				$object->delete();
				throw $e;
			}
			$object->commit();
			return self::get($objectId);
		}
		
		/**
			* Получить id типа скидки по ее идентификатору
			* @param String $discountCode строковой идентификатор типа скидки
			* @return Integer|Boolean
		*/
		public static function getTypeId($discountCode) {
			static $typeId = array();
			if(isset($typeId[$discountCode])) return $typeId[$discountCode];			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'discount_type');
			$sel->where('codename')->equals($discountCode);
			return $typeId[$discountCode] = (($sel->first) ? $sel->first->id : false);
		}
		
		/**
			* Инициализировать систему скидок
		*/
		public static function init() {
			self::includeDiscount('itemDiscount');
			self::includeDiscount('orderDiscount');
		}
		
		/**
			* Загрузить файл, содержащий класс-реализацию скидки $name
			* @param String $name название скидки
			* @return Boolean true, либо false в зависимости от успешности операции
		*/
		private static function includeDiscount($name) {
			static $cache = Array();
			if(isset($cache[$name])) {
				return $cache[$name];
			}
			
			$filePath = CURRENT_WORKING_DIR . '/classes/modules/emarket/classes/discounts/discounts/' . $name . '.php';
			if(is_file($filePath)) {
				require $filePath;
				return $cache[$name] = true;
			} else {
				return $cache[$name] = false;
			}
		}
	};
?>