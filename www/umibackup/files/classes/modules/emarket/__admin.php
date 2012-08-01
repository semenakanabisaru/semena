<?php
	abstract class __emarket_admin extends baseModuleAdmin {
		
		public function dashboard() {
			throw new publicAdminException("Not yet implemented for 2.8.x");
		}
		
		
		public function config() {
			$config = mainConfiguration::getInstance();
			$regedit = regedit::getInstance();


			$params = Array(
				'emarket-options' => Array(
					'int:max_compare_items'		=> NULL,
					'boolean:currency'		=> NULL,
					'boolean:currency'		=> NULL,
					'boolean:stores'		=> NULL,
					'boolean:payment'		=> NULL,
					'boolean:delivery'		=> NULL,
					'boolean:discounts'		=> NULL,
				),
				'emarket-mail' => Array(
					'string:email' => NULL,
					'string:name'  => NULL,
					'string:manageremail'  => NULL
				),
				
			);


			$mode = (string) getRequest('param0');
			if($mode == "do") {

				$params = $this->expectParams($params);

				$max_comp = &$params['emarket-options']['int:max_compare_items'];
				$max_comp = floor($max_comp);
				
				if(!$max_comp || $max_comp<=1)  {
					$config->set('modules', 'emarket.compare.max-items', 2);
					def_module::errorNewMessage("%error-compare-wrong-data%");
					def_module::errorPanic();
				}

				$config->set('modules', 'emarket.compare.max-items', $params['emarket-options']['int:max_compare_items']);
				$regedit->setVar('//modules/emarket/enable-discounts', $params['emarket-options']['boolean:discounts']);
				$regedit->setVar('//modules/emarket/enable-currency', $params['emarket-options']['boolean:currency']);
				$regedit->setVar('//modules/emarket/enable-stores', $params['emarket-options']['boolean:stores']);
				$regedit->setVar('//modules/emarket/enable-payment', $params['emarket-options']['boolean:payment']);
				$regedit->setVar('//modules/emarket/enable-delivery', $params['emarket-options']['boolean:delivery']);

				$regedit->setVar('//modules/emarket/from-email', $params['emarket-mail']['string:email']);
				$regedit->setVar('//modules/emarket/from-name', $params['emarket-mail']['string:name']);
				$regedit->setVar('//modules/emarket/manager-email', $params['emarket-mail']['string:manageremail']);

				self::switchGroupsActivity('order_delivery_props', $params['emarket-options']['boolean:delivery']);
				self::switchGroupsActivity('order_discount_props', $params['emarket-options']['boolean:discounts']);
				self::switchGroupsActivity('order_payment_props', $params['emarket-options']['boolean:payment']);

				$this->chooseRedirect();
			}

			$params['emarket-options']['int:max_compare_items'] =  $config->get('modules', 'emarket.compare.max-items');
			$params['emarket-options']['boolean:discounts'] = $regedit->getVal('//modules/emarket/enable-discounts');
			$params['emarket-options']['boolean:currency'] = $regedit->getVal('//modules/emarket/enable-currency');
			$params['emarket-options']['boolean:stores'] = $regedit->getVal('//modules/emarket/enable-stores');
			$params['emarket-options']['boolean:payment'] = $regedit->getVal('//modules/emarket/enable-payment');
			$params['emarket-options']['boolean:delivery'] = $regedit->getVal('//modules/emarket/enable-delivery');

			$params['emarket-mail']['string:email'] = $regedit->getVal('//modules/emarket/from-email');
			$params['emarket-mail']['string:name'] = $regedit->getVal('//modules/emarket/from-name');
			$params['emarket-mail']['string:manageremail'] = $regedit->getVal('//modules/emarket/manager-email');


			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}

		public function social_networks() {
			$regedit = regedit::getInstance();

			$params = Array(
				'emarket-social_networks-vkontakte' => Array(
					'string:social_vkontakte_merchant_id' => NULL,
					'string:social_vkontakte_key' 		  => NULL,
					/*'boolean:social_vkontakte_wishlist'   => NULL,*/
					'boolean:social_vkontakte_order'      => NULL,
					'boolean:social_vkontakte_testmode'   => NULL,
				)
				
			);

			$mode = (string) getRequest('param0');
			if($mode == "do") {
				$params = $this->expectParams($params);

				$regedit->setVar('//modules/emarket/social_vkontakte_merchant_id', $params['emarket-social_networks-vkontakte']['string:social_vkontakte_merchant_id']);
				$regedit->setVar('//modules/emarket/social_vkontakte_key', $params['emarket-social_networks-vkontakte']['string:social_vkontakte_key']);
				/*$regedit->setVar('//modules/emarket/social_vkontakte_wishlist', $params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_wishlist']);*/
				$regedit->setVar('//modules/emarket/social_vkontakte_order', $params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_order']);
				$regedit->setVar('//modules/emarket/social_vkontakte_testmode', $params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_testmode']);

				$this->chooseRedirect();
			}


			$params['emarket-social_networks-vkontakte']['string:social_vkontakte_merchant_id'] = $regedit->getVal('//modules/emarket/social_vkontakte_merchant_id');
			$params['emarket-social_networks-vkontakte']['string:social_vkontakte_key'] 		= $regedit->getVal('//modules/emarket/social_vkontakte_key');
			/*$params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_wishlist']	= $regedit->getVal('//modules/emarket/social_vkontakte_wishlist');*/
			$params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_order']		= $regedit->getVal('//modules/emarket/social_vkontakte_order');
			$params['emarket-social_networks-vkontakte']['boolean:social_vkontakte_testmode'] 	= $regedit->getVal('//modules/emarket/social_vkontakte_testmode');

			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}


		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			
			$config = mainConfiguration::getInstance();
			$defaultCurrencyCode = $config->get('system', 'default-currency');
			
			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				
				if($object->getMethod() == 'currency') {
					if($object->codename == $defaultCurrencyCode) {
						throw new publicAdminException(getLabel('error-delete-default-currency'));
					}
				}
				
				$params = Array(
					'object'		=> $object
				);
				
				$this->deleteObject($params);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}
		
		public function ordersList($customerId) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customerId);
			$sel->where('status_id')->isNull(false);
			
			return array('items' => array('nodes:item' => $sel->result));
		}
		
		public function getDatasetConfiguration($param = '') {
			switch ($param) {
				case 'discounts': {
					$loadMethod = 'discounts';
					$objectType = 'discount';
					break;
				}
				
				case 'orders': {
					$loadMethod = 'orders';
					$objectType = 'order';
					break;
				}
				
				case 'delivery': {
					$loadMethod = 'delivery';
					$objectType = 'delivery';
					break;
				}
				
				case 'payment': {
					$loadMethod = 'payment';
					$objectType = 'payment';
					break;
				}
				
				case 'stores': {
					$loadMethod = 'stores';
					$objectType = 'store';
					break;
				}
				
				default: {
					$loadMethod = $objectType = $param;
				}
			}
			
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'emarket', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'emarket', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'emarket', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => $objectType)
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'rate_voters', 'rate_sum', 'total_count', 'discount_rules_id', 'discount_modificator_id', 'delivery_address'),
					'default' => 'question[170px]'
				);
		}
	};
?>