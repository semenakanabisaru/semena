<?php
	abstract class __emarket_admin_discounts extends baseModuleAdmin {
		public function discounts () {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$objectTypes = umiObjectTypesCollection::getInstance();
			$type_id = $objectTypes->getBaseType("emarket", "discount");
			
			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'discount');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);
			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}
		
		public function activity() {
			$objects = getRequest('object');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			$is_active = (bool) getRequest('active');
			
			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$object->setValue("is_active", $is_active);
				$object->commit();
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}



		public function discount_add() {
			$inputData = Array("type" => "discount");
			$mode = (string) getRequest('param0');
			
			if($mode == "do") {
				$data = getArrayKey(getRequest('data'), 'new');

				//Create new dicsount
				$discountName = getRequest('name');
				$discountTypeId = getArrayKey($data, 'discount_type_id');
				
				try {
					$discount = discount::add($discountName, $discountTypeId);
					
					//Apply modificator
					$modificatorId = getArrayKey($data, 'discount_modificator_id');
					
					try {
						$modificatorTypeObject = $this->expectObject($modificatorId, true, true);
					} catch (publicAdminException $e) {
						if($discount) $discount->delete();
						$this->errorNewMessage(getLabel('error-modificator-required'));
						$this->errorPanic();
					}
					
					$modificatorObject = discountModificator::create($discount, $modificatorTypeObject);
					$discount->setDiscountModificator($modificatorObject);
					
					//Apply rules
					$rulesId = getArrayKey($data, 'discount_rules_id');
					foreach($rulesId as $ruleId) {
						$ruleTypeObject = $this->expectObject($ruleId, true, true);
						$ruleObject = discountRule::create($discount, $ruleTypeObject);
						if($ruleObject instanceof discountRule == false ) {
							$discount->delete();
							throw new publicAdminException("discountRule #{$ruleId} \"{$ruleTypeObject->name}\" class not found");
						}
						$discount->appendDiscountRule($ruleObject);
					}
					$discount->commit();
				} catch (valueRequiredException $e) {
					$this->errorNewMessage($e->getMessage());
					$this->errorPanic();
				}
				$this->chooseRedirect($this->pre_lang . "/admin/emarket/discount_edit/" . $discount->getId() . "/");
			}
			
			$this->setDataType("form");
			$this->setActionType("create");
			
			$data = $this->prepareData($inputData, "object");
			
			$this->setData($data);
			return $this->doData();
		}
		
		public function discount_edit() {
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');
	 
			if($mode == "do") {
				$this->saveEditedObjectData($object);
				
				//Save subobjects
				unset($_REQUEST['type-id']);
				unset($_REQUEST['name']);
				$subObjectsId = Array();
				if($object->discount_modificator_id) {
					$subObjectsId[] = $object->discount_modificator_id;
				}
				if($object->discount_rules_id) {
					$subObjectsId = array_merge($subObjectsId, $object->discount_rules_id);
				}
				foreach($subObjectsId as $subObjectsId) {
					$subObject = $this->expectObject($subObjectsId, true, true);
					$this->saveEditedObjectData($subObject);
				}
				$this->chooseRedirect();
			}
	 
			$this->setDataType("form");
			$this->setActionType("modify");
	 
			$data = $this->prepareData($object, "object");
	 
			$this->setData($data);
			return $this->doData();
		}

		public function getModificators($discountTypeId = false, $discountId = false) {
			$items = discountModificator::getList($discountTypeId);
			
			if($discountId) {
				$discount = discount::get($discountId);
				$discountModId = $discount->getDiscountModificator()->getObject()->modificator_type_id;
				
				foreach($items as $i => $mod) {
					$items[$i] = array(
						'attribute:id' => $mod->id,
						'attribute:name' => $mod->name
					);
					
					if($mod->id == $discountModId) {
						$items[$i]['attribute:selected'] = 'selected';
					}
				}
			}
		
			return array(
				'items' => array('nodes:item' => $items)
			);
		}
		
		public function getRules($discountTypeId = false, $discountId = false) {
			$items = discountRule::getList($discountTypeId);
			
			if($discountId) {
				$discount = discount::get($discountId);
				$discountRules = $discount->getDiscountRules();
				$discountRulesId = array();
				foreach($discountRules as $rule) {
					$discountRulesId[] = $rule->getObject()->rule_type_id;
				}
				
				foreach($items as $i => $rule) {
					$items[$i] = array(
						'attribute:id' => $rule->id,
						'attribute:name' => $rule->name
					);
					
					if(in_array($rule->id, $discountRulesId)) {
						$items[$i]['attribute:selected'] = 'selected';
					}
				}
			}
			
			return array(
				'items' => array('nodes:item' => $items)
			);
		}
	};
?>