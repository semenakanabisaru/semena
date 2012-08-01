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
					'int:max_compare_items'			=> NULL,
					'boolean:currency'				=> NULL,
					'boolean:currency'				=> NULL,
					'boolean:stores'				=> NULL,
					'boolean:payment'				=> NULL,
					'boolean:delivery'				=> NULL,
					'boolean:discounts'				=> NULL,
					'boolean:delivery-with-address'	=> NULL,
				)
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
				$regedit->setVar('//modules/emarket/delivery-with-address', $params['emarket-options']['boolean:delivery-with-address']);

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
			$params['emarket-options']['boolean:delivery-with-address'] = $regedit->getVal('//modules/emarket/delivery-with-address');

			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}

		public function mail_config() {
			$regedit = regedit::getInstance();
			$domains = domainsCollection::getInstance()->getList();

			$params = Array();
			
			$params['status-notifications'] = array(
				'boolean:no-order-status-notification' => $regedit->getVal('//modules/emarket/no-order-status-notification'),
				'boolean:no-payment-status-notification' => $regedit->getVal('//modules/emarket/no-payment-status-notification'),
				'boolean:no-delivery-status-notification' => $regedit->getVal('//modules/emarket/no-delivery-status-notification')
			);

			foreach($domains as $domain) {
				$domain_id = $domain->getId();
				$domain_name = $domain->getHost();

				$seo_info = Array();
				$seo_info['status:domain'] = $domain_name;

				if ($domain->getIsDefault() && !$regedit->getVal("//modules/emarket/from-email/{$domain_id}") && !$regedit->getVal("//modules/emarket/from-name/{$domain_id}") && !$regedit->getVal("//modules/emarket/manager-email/{$domain_id}")) {
					$seo_info['string:email-' . $domain_id] = $regedit->getVal("//modules/emarket/from-email");
					$seo_info['string:name-' . $domain_id] = $regedit->getVal("//modules/emarket/from-name");
					$seo_info['string:manageremail-' . $domain_id] = $regedit->getVal("//modules/emarket/manager-email");
				} else {
					$seo_info['string:email-' . $domain_id] = $regedit->getVal("//modules/emarket/from-email/{$domain_id}");
					$seo_info['string:name-' . $domain_id] = $regedit->getVal("//modules/emarket/from-name/{$domain_id}");
					$seo_info['string:manageremail-' . $domain_id] = $regedit->getVal("//modules/emarket/manager-email/{$domain_id}");
				}

				$params[$domain_name] = $seo_info;
			}

			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$params = $this->expectParams($params);

				foreach($domains as $domain) {
					$domain_id = $domain->getId();
					$domain_name = $domain->getHost();

					$email = $params[$domain_name]['string:email-' . $domain_id];
					$name = $params[$domain_name]['string:name-' . $domain_id];
					$manageremail = $params[$domain_name]['string:manageremail-' . $domain_id];

					$regedit->setVar("//modules/emarket/from-email/{$domain_id}", $email);
					$regedit->setVar("//modules/emarket/from-name/{$domain_id}", $name);
					$regedit->setVar("//modules/emarket/manager-email/{$domain_id}", $manageremail);

				}
				
				$regedit->setVar('//modules/emarket/no-order-status-notification', $params['status-notifications']['boolean:no-order-status-notification']);
				$regedit->setVar('//modules/emarket/no-payment-status-notification', $params['status-notifications']['boolean:no-payment-status-notification']);
				$regedit->setVar('//modules/emarket/no-delivery-status-notification', $params['status-notifications']['boolean:no-delivery-status-notification']);

				$this->chooseRedirect();
			}


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

		public function actAsUser($userId = false) {

			if (!$userId) $userId = getRequest('param0');

			$objects = umiObjectsCollection::getInstance();
			$user = $objects->getObject($userId);
			if (!$user instanceof umiObject) {
				return false;
			}

			session_destroy();

			setcookie('u-login', "", time() - 3600, "/");
			setcookie('u-password', "", time() - 3600, "/");
			setcookie('u-password-md5', "", time() - 3600, "/");
			setcookie ('eip-panel-state-first', "", time() - 3600, "/");
			setcookie('customer-id', "", time() - 3600, "/");

			session_start();

			if ($user->getTypeGUID() == 'users-user') {

				$login = $user->getValue("login");
				$password = $user->getValue("password");

				$_SESSION['user_id'] = $userId;
				$_SESSION['cms_login'] = $login;
				$_SESSION['cms_pass'] = $password;

				setcookie('u-login', $login, time() - 3600, "/");
				setcookie('u-password-md5', $password, time() - 3600, "/");
			}
			$_SESSION['fake-user'] = true;

			setcookie('customer-id', $userId, (time() + customer::$defaultExpiration), '/');

			$this->chooseRedirect("/");
		}

		public function editOrderAsUser($orderId = false) {

			if (!$orderId) $orderId = getRequest('param0');

			$objects = umiObjectsCollection::getInstance();
			$order = $objects->getObject($orderId);

			if (!$order instanceof umiObject) return false;
			if ($order->getTypeGUID() !== 'emarket-order') return false;

			$statusId = order::getStatusByCode('editing');
			$order->setValue('status_id', $statusId);
			$order->commit();

			$this->actAsUser($order->getValue('customer_id'));

		}
	};
?>