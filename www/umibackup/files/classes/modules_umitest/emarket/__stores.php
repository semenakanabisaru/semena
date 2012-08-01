<?php
	abstract class __emarket_stores {
		
		public function stores($elementId, $template = 'default') {
			if(!$template) $tempate = 'default';
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();
			
			list($tpl_block, $tpl_block_empty, $tpl_item) = def_module::loadTemplates("emarket/stores/".$template,
				'stores_block', 'stores_block_empty', 'stores_item');
			
			$elementId = $this->analyzeRequiredPath($elementId);

			if($elementId == false) {
				throw new publicException("Wrong element id given");
			}
			
			$element = $hierarchy->getElement($elementId);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException("Wrong element id given");
			}
			
			$storesInfo = $element->stores_state;
			
			$items_arr = array(); $stores = array(); $total = 0;
			if(is_array($storesInfo)) foreach($storesInfo as $storeInfo) {
				$object = $objects->getObject(getArrayKey($storeInfo, 'rel'));
				
				if($object instanceof iUmiObject) {
					$amount = (int) getArrayKey($storeInfo, 'int');
					$total += $amount;
					
					$store = array('attribute:amount' => $amount);
					if($object->primary) {
						$reserved = (int) $element->reserved;
						$store['attribute:amount'] -= $reserved;
						$store['attribute:reserved'] = $reserved;
						$store['attribute:primary'] = 'primary';
					}
					$store['item'] = $object;
					
					$stores[] = $store;
					
					$items_arr[] = def_module::parseTemplate($tpl_item, array(
						'store_id' => $object->id,
						'amount' => $amount,
						'name' => $object->name
					), false, $object->id);
				}
			}
			
			$result = array(
				'stores' => array(
					'attribute:total-amount' => $total,
					'nodes:store' => $stores
				)
			);
			
			
			$result['void:total-amount'] = $total;
			$result['void:items'] = $items_arr;
			
			if (!$total) $tpl_block = $tpl_block_empty;

			return def_module::parseTemplate($tpl_block, $result);
		}
	};
?>