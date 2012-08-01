<?php
	class vkontakteSocialCallbackHandler extends socialCallbackHandler {
		
		protected $items = array();

		public static $vkontakte_item_keys = array(
			'item_id', 'item_price', 'item_quantity', 'item_currency_str', 'item_currency'
		);

		public function response() {
			$reg = regedit::getInstance();
			
			$merchant_id = (int) getRequest('merchant_id');
			$reg_merchant_id = (int) $reg->getVal('//modules/emarket/social_vkontakte_merchant_id');
			
			if( $reg_merchant_id != $merchant_id )  { 
				$this->vkontakte_err(10, 'несовпадение вычисленной и переданной подписи', 'true');
			}
			$this->vkontakte_check_key();
			$this->vkontakte_item_array();
			$notification_type = (string) getRequest('notification_type');
			
			$this->test_mode = strpos($notification_type, 'test') != false;
			$notification_type = str_replace('-test','',$notification_type);
			
			file_put_contents(CURRENT_WORKING_DIR . "/vkontakte.log", $notification_type, FILE_APPEND);

			switch($notification_type) {
				case "item-reservation": {
				$this->_vkontakte_callbacks_reserve();
					break;
			}
				case "check-items-availability": {
				$this->_vkontakte_callbacks_availability();
					break;
			}
				case "order-state-change": {
				$this->_vkontakte_callbacks_orderstatus();
					break;
			}
				case "calculate-shipping-cost": {
				$this->_vkontakte_callbacks_delivery();
					break;
			}
				case "cancel-item-reservation": {
					$this->_vkontakte_callbacks_cancel();
					break;
				}
				default: {
					$this->vkontakte_err(100, 'Неизвестный тип уведомлений', 'true');
					break;
				}
			}
			
			return true;
		}

		public function vkontakte_check_key() {
			
			$sig = (string) getRequest('sig');
			unset($_REQUEST['sig']); 
			unset($_REQUEST['path']);
			$input = $_REQUEST;
			
			ksort($input); 
			
			$str = ''; 
			foreach ($input as $k => $v) { 
				$str .= $k.'='.$v; 
			} 			
			
			$reg = regedit::getInstance();
			$reg_key = (string) $reg->getVal('//modules/emarket/social_vkontakte_key');
			
			if ($sig != md5($str.$reg_key)) {
				$this->vkontakte_err(10, 'несовпадение вычисленной и переданной подписи', 'true');
			}
		}
		
		public function vkontakte_item_array() {

			$items = array();
			$input = $_REQUEST;

			foreach ($input as $k=>$v) {
				foreach (self::$vkontakte_item_keys as $key) {
					if ( strpos($k, $key) === 0 ) {
						$id = (int) str_replace( $key.'_', '', $k);
						if($id) {
							if(!isset($items[$id])) $items[$id] = array();
							$items [$id] [$key] = $v;
						}
						break;
					}
				}
			}
			
			return $this->items  = $items;
		}
		
		
		public function _vkontakte_get_order() {
		
		}
		
		public function _vkontakte_callbacks_orderstatus() {
	
			$state = (string) getRequest('new_state');
			if(!strlen($state)) {
				$this->vkontakte_err(15, 'Передан неверный статус заказа', 'true');
			}
			
			$currency 		= (int) getRequest('currency');
			$currency_str 	= (string) getRequest('currency');
			$amount	 		= (float) getRequest('amount');
			
			$order_date = (string)  getRequest('order_date') ;
			$order_id 	= (int) getRequest('order_id');
			
			//$order_comment = isset($_POST['order_comment']) ? (string) $_POST['order_comment']:'';
			
			$sel = new selector('objects');
			$sel->types('object-type')->name( 'emarket','order' ); 
			$sel->where('social_order_id')->equals($order_id); 
			$sel->limit(0, 1);
		
			$order = ($sel->result());
			
			$our_order_id = (int) getRequest('custom_3');
			
			if (!empty($order)) {
				$order = reset($order);
				$our_order_id = $order->getId();
			}
			
			try {
				if($our_order_id) {
					$order = order::get( $our_order_id );
				}
				else {
					$order = order::create(  ); 
					$this->_set_order_items($order);
				}
			}
			catch(Exception $e) { 
				$this->vkontakte_err(2, 'временная ошибка базы данных', 'false');
			}
			
			setcookie('customer-id', $order->customer_id, time() + 3600, '/');

			$this->_set_order_customer($order);

			$this->_set_order_delivery($order);
				
			$this->_set_order_payment($order);

			$order->social_order_id = $order_id;
			
			$order->refresh();
			$order->order(); 
			
			if( !empty($_REQUEST['custom_1'])  ) {
				if($_REQUEST['custom_1'] == 'basket.clear') {
					foreach ($order->getItems() as $orderItem) {
						$order->removeItem($orderItem); 
					}
					$order->refresh();
				}
			}
		

			$merchant_order_id = $order->getId();
$response = '<?xml version="1.0" encoding="UTF-8" ?>
<success> 
  <order-id>'.$order_id.'</order-id> 
  <merchant-order-id>'.$merchant_order_id.'</merchant-order-id> 
</success>'; 

			$this->flushMessage($response);

		}
		
		
		public function _get_shipping_country() {
			
			$shipping_country = (string) getRequest('shipping_country');
			
			$country_guide = umiObjectTypesCollection::getinstance()->getTypeByGUID('d69b923df6140a16aefc89546a384e0493641fbe');

			if(!$country_guide) {
				$this->vkontakte_err(2, 'временная ошибка базы данных', 'true');
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id( $country_guide->getId() ); 
			$sel->where('country_iso_code')->equals($shipping_country); 
			$sel->limit(0, 1);
			
			$countryName = '';
			
			$country = $sel->first;
			if ($country instanceof umiObject) $countryName = $country->getName();
			return $countryName;

		}

		public function _set_order_customer( &$order) {
			
			$customer   = customer::get(); 
			
			$name = (string) getRequest('user_name');
			if(!strlen($name)) $name = "Тестовый заказчик";
			$email = (string) getRequest('shipping_email');
			$userId = (int) getRequest('user_id');
			$phone = (string) getRequest('shipping_phone');

			$customer->setValue('fname', $name);
			$customer->setValue('email', $email);
			$customer->setValue('phone', $phone);

			//$customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
			$customer->commit();
			
			if(!$order->customer_id)  {
			$order->customer_id = $customer->getId();
			}

		}

		public function _set_order_delivery( &$order) {
			$country = $this->_get_shipping_country();

			$recipient_name = isset($_POST['recipient_name']) ? (string) $_POST['recipient_name']:'';

			$typeId     = umiObjectTypesCollection::getInstance()->getBaseType("emarket", "delivery_address");
			$customer   = customer::get(); 
			$addressId  = umiObjectsCollection::getInstance()->addObject("Address for customer #".$customer->id, $typeId);
			
			
			$delivery_data = array(
				'country' => $country,
				'index'=> (string) getRequest('shipping_code'),
				'city'=> (string) getRequest('shipping_city'),
				'street'=> (string) getRequest('shipping_street'),
				'house'=> (string) getRequest('shipping_house'),
				'flat'=> (string) getRequest('shipping_flat'),
			);
			
			$_REQUEST['data']['new']=$delivery_data;
	
			$deliveryId= (string) getRequest('shipping_method');

			$dataModule = cmsController::getInstance()->getModule("data");
			if($dataModule) {
				$dataModule->saveEditedObject($addressId, true, true);
			}

			$order->setValue('delivery_address', $addressId);
			$order->setValue('delivery_id', $deliveryId);
			
			$deliveryIds = delivery::getList();
		
			$deliveryPrice = false;
			foreach($deliveryIds as $delivery) {
		
				if($deliveryId != $delivery->getId()) continue;

				$delivery = delivery::get($delivery);
				
				if($delivery->validate($order) == false) {
					continue;
				}

				$deliveryPrice  = $delivery->getDeliveryPrice($order);
			}
			
			if($deliveryPrice===false) {
				$this->vkontakte_err(2, 'временная ошибка базы данных (не найден способ доставки)', 'true');
			}
			
			$order->setValue('delivery_price', $deliveryPrice);
		}

		public function _set_order_payment( &$order) {

			$paymentTypeId = umiObjectsCollection::getInstance()->getObjectIdByGUID('emarket-paymenttype-social');
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'payment');
			$sel->where('payment_type_id')->equals($paymentTypeId);
			$sel->limit(0,1);

			$payment = $sel->first;
			
			if(!$payment instanceof umiObject) {
				$this->vkontakte_err(2, 'временная ошибка базы данных (не найдена оплата)', 'true');
			}
		
			$order->setValue('payment_id', $payment->getId());

			$newStatusId = $order->getStatusByCode('accepted', 'order_payment_status');
			
			$order->payment_status_id = $newStatusId;
			$order->payment_date = new umiDate();
		}


		public function _set_order_items(&$order) {

			foreach($this->items as $k => $item) {
				try { 
					 $oitem = orderItem::create($item['item_id']);
					 $oitem-> setAmount($item['item_quantity'] ? (int) $item['item_quantity'] : 1);
					 $order-> appendItem( $oitem );
				}
				catch(Exception $e) { 
					$this->vkontakte_err( (1100+$k), 'для позиции '.$k.' передан некорректный идентификатор товара', 'true');
				}
			}
			$order->refresh();
		}

		public function _vkontakte_callbacks_reserve() {
			$order_id 	= (int) getRequest('order_id');
			
			$this->flushMessage('<?xml version="1.0" encoding="UTF-8" ?>
<reservation-success />');
			
		}
		
		public function _vkontakte_callbacks_cancel() {

			$this->flushMessage('<?xml version="1.0" encoding="UTF-8" ?>
<reservation-cancelled />');

		}

		public function _vkontakte_callbacks_delivery() {
			
			$order_id = (int) getRequest('order_id');
			
			$sel = new selector('objects');
			$sel->types('object-type')->name( 'emarket','order' ); 
			$sel->where('social_order_id')->equals($order_id); 
			$sel->limit(0, 1);
			
			$order = $sel->first;
			
			if(!$order instanceof umiObject) {
			
			try {
					$order = order::create();
			}
			catch(Exception $e) { 
				$this->vkontakte_err(2, 'временная ошибка базы данных', 'true');
			}
				$order->social_order_id = $order_id;
		
			}

			$this->_set_order_items($order);
			$order->refresh();
			
			$deliveryIds = delivery::getList();

			$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<shipping-methods>';
			
			// socialTODO currency
			foreach($deliveryIds as $delivery) {
				$delivery = delivery::get($delivery);
				if($delivery->validate($order) == false) {
					continue;
				}

				$deliveryPrice  = $delivery->getDeliveryPrice($order);
				
				$xml .= '<merchant-calculated id="'.$delivery->getId().'" name="'.$delivery->getName().'">
							<price currency="RUB">'.$deliveryPrice .'</price>
						 </merchant-calculated>';
			}

			$xml .= '</shipping-methods>';
			
			$this->flushMessage($xml);
			
		}
		
		public function _vkontakte_callbacks_availability() {
			$response = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
			
			if( empty( $this->items ) ) {
				$this->flushMessage($response.'<failure/>');
			}
			
			$response .= '<items>';
			
			foreach($this->items as $item) {
				
				$object = umiHierarchy::getInstance()->getElement($item['item_id'])->getObject();
				$storesState = $object->getValue('stores_state');
				$total = 0;
				foreach ($storesState  as $store) {
						$total += (int) getArrayKey($store, 'int');
				}
			
				// socialTODO
				$response .= '
<item id="'.$item['item_id'].'">
	<price currency="643">'.((string) $object->getValue('price')).'</price>
	<quantity>'.$total.'</quantity>
</item>
';
			}
			
			$response .= '</items>';
			$this->flushMessage($response);
			
		}
		
		public function vkontakte_err($errno, $errmsg, $is_critical) {

			$response = '<?xml version="1.0" encoding="UTF-8" ?>
<failure>
    <error-code>'.$errno.'</error-code>
    <error-description>'.$errmsg.'</error-description>
    <critical>'.$is_critical.'</critical>
</failure>';
$f = fopen('log.txt', 'a');
fwrite($f, print_R($response,true));
//fwrite($f, print_R($this->Items,true));
fclose($f);
	
		$this->flushMessage($response, "text/xml");
		}		
	};
?>