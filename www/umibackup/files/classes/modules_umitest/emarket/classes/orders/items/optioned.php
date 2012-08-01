<?php
/**
	* Расширенная версия класса orderItem с учетом возможных опций
*/

	class optionedOrderItem extends orderItem {
		protected $options = array();
		
		public function __construct(umiObject $object) {
			parent::__construct($object);
			$this->reloadOptions();
		}
		
		/**
			* Получить список опций, которые выбраны для данного товара: цвет, размер, длина...
			* Некоторые опции могут влиять на стоимость товара в заказе.
			* @return Array список опций (массив из id объектов)
		*/
		public function getOptions() {
			return $this->options;
		}


		/**
			* Подключтить опцию к товару в заказе.
			* Опция применяется к определенному свойству (тип поля должен быть relation)
			* и определяется id объекта в справочнике
			* @param String $propertyName название свойства, к которому применяется опция
			* @param Integer $optionId id объекта, который соответствует значению опции
			* @return Boolean true, если применение опции прошло без ошибок
		*/
		public function appendOption($propertyName, $optionId, $price = false) {
			$options = $this->object->options;
			
			
			if(!$price) {
				$price = $this->getOptionPrice($propertyName, $optionId);
			}
			
			$options[] = array(
				'varchar' => $propertyName,
				'rel' => (string) $optionId,
				'float' => $price
			);
			$this->object->options = $options;
			$this->reloadOptions();
		}


		/**
			* Удалить опцию из описания товара в заказе.
			* @param String $propertyName название свойства, к которому привязана опция
			* @return Boolean true, если опция успешно удалена
		*/
		public function removeOption($propertyName) {
			if(isset($this->options[$propertyName])) {
				$optionId = $this->options[$propertyName];
				$options = $this->object->options;
				foreach($options as $i => $optionInfo) {
					if($optionInfo['varchar'] == $propertyName) {
						unset($options[$i]);
					}
				}
				$this->object->options = $options;
				$this->reloadOptions();
			}
		}
		
		public function getItemPrice() {
			$price = parent::getItemPrice();
			
			$options = $this->getOptions();
			
			foreach($options as $optionInfo) {
				$optionPrice = getArrayKey($optionInfo, 'price');
				if($optionPrice) {
					$price += (float) $optionPrice;
				}
			}
			return $price;
		}
		
		/**
			* 
			* @param String $propertyName название свойства, к которому применяется опция
			* @param Float $price = false стоимость опции
			* @return Boolean true, если применение цены опции прошло без ошибок
		*/
		public function setOptionPrice($propertyName, $price) {
			if(isset($this->options[$propertyName])) {
				$optionId = $this->options[$propertyName]['option-id'];
				$this->removeOption($propertyName);
				$this->appendOption($propertyName, $optionId, $price);
				
				return true;
			} else {
				return false;
			}
		}
		
		
		public function refresh() {
			$this->price = $this->getElementPrice();
			
			$element = $this->getItemElement();
			if($element instanceof iUmiHierarchyElement) {
				$name = $element->getName();
				
				$options = array();
				$objects = umiObjectsCollection::getInstance();
				foreach($this->getOptions() as $optionInfo) {
					$optionId = $optionInfo['option-id'];
					$option = $objects->getObject($optionId);
					if($option instanceof iUmiObject) {
						$options[] = $option->getName();
					}
				}
				
				if(sizeof($options)) {
					$name .= ' (' . implode(", ", $options) . ')';
				}
				$this->object->setName($name);
			}
			return parent::refresh();
		}
		
		protected function reloadOptions() {
			$options = array();
			$objectOptions = $this->object->options;
			
			foreach($objectOptions as $optionInfo) {
				$options[$optionInfo['varchar']] = array(
					'option-id'		=> getArrayKey($optionInfo, 'rel'),
					'price'			=> getArrayKey($optionInfo, 'float'),
					'field-name'	=> getArrayKey($optionInfo, 'varchar')
				);
			}
			$this->options = $options;
		}
		
		protected function getOptionPrice($propertyName, $optionId) {
			$itemLinks = $this->object->item_link;
			if(is_array($itemLinks) && sizeof($itemLinks)) {
				list($element) = $itemLinks;
				
				$params = array(
					'filter' => array('rel' => $optionId)
				);
				
				$value = $element->getValue($propertyName, $params);
				if(is_array($value) && sizeof($value)) {
					return $price = getArrayKey($value[0], 'float');
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	};
?>