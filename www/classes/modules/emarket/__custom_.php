<?php
	abstract class __emarket_custom {
		//TODO: Write here your own macroses
	    public function fast_delivery(){
		 $order = $this->getBasketOrder();
		  $orderId = $order->id;
		  $customer = selector::get('object')->id($order->customer_id);
		 
		  $result = array(
		  'attribute:id'	=> ($orderId),
		  'xlink:href'	=> ('uobject://' . $orderId));
		 
		  if(!permissionsCollection::getInstance()->isAuth()){
		    $result['customer']	= array('full:object' => $customer);
		  }
		 
		  $result['delivery']	= $this->customerDeliveryList('notemplate');
		  $result['delivery_choose']	= $this->renderDeliveryList($order, 'notemplate');
		  $result['payment']	= $this->renderPaymentsList_custom($order, 'notemplate');
		  return  $result;
		}


		public function fast_payment(){
		  $order = $this->getBasketOrder();
		  $orderId = $order->id;
		  $customer = selector::get('object')->id($order->customer_id);
		 
		  $result = array(
		  'attribute:id'	=> ($orderId),
		  'xlink:href'	=> ('uobject://' . $orderId));
		 
		  if(!permissionsCollection::getInstance()->isAuth()){
		    $result['customer']	= array('full:object' => $customer);
		  }
		  $result['payment']	= $this->renderPaymentsList_custom($order, 'notemplate');
		  return  $result;
		}
		 
		public function renderPaymentsList_custom(order $order, $template) {
		  list($tpl_block, $tpl_item) = def_module::loadTemplates("./tpls/emarket/payment/{$template}.tpl", 'payment_block', 'payment_item');
		 
		  $payementIds = payment::getList(); $items_arr = array();
		  $currentPaymentId = $order->getValue('payment_id');
		 
		  foreach($payementIds as $paymentId) {
		    $payment = payment::get($paymentId);
		    if($payment->validate($order) == false) continue;
		    $paymentObject = $payment->getObject();
		    $paymentTypeId = $paymentObject->getValue('payment_type_id');
		    $paymentTypeName = umiObjectsCollection::getInstance()->getObject($paymentTypeId)->getValue('class_name');
		 
		    if( $paymentTypeName == 'social') continue;
		 
		    $item_arr = array(
		    'attribute:id'			=> $paymentObject->id,
		    'attribute:name'		=> $paymentObject->name,
		    'attribute:type-name'	=> $paymentTypeName,
		    'xlink:href'			=> $paymentObject->xlink
		    );
		 
		    if($paymentId == $currentPaymentId) {
		      $item_arr['attribute:active'] = 'active';
		    }
		 
		    $items_arr[] = def_module::parseTemplate($tpl_item, $item_arr, false, $paymentObject->id);
		  }
		 
		    return array('items' => array('nodes:item'	=> $items_arr));
		 
		}
		 
		 
		public function save_delivery(){
		  $order = $this->getBasketOrder(false);
		  $order = $this->getBasketOrder(false);
		  //сохранение регистрационных данных
		  $cmsController = cmsController::getInstance();
		  $data = $cmsController->getModule('data');
		  $data->saveEditedObject(customer::get()->id, false, true);
		 
		  //сохранение способа доставки
		  $deliveryId = getRequest('delivery-id');
		  if($deliveryId){
		    $delivery = delivery::get($deliveryId);
		    $deliveryPrice = (float) $delivery->getDeliveryPrice($order);
		    $order->setValue('delivery_id', $deliveryId);
		    $order->setValue('delivery_price', $deliveryPrice);
		  }
		  //сохранение адреса доставки
		  $addressId = getRequest('delivery-address');
		  if($addressId == 'new') {
		    $collection = umiObjectsCollection::getInstance();
		    $types      = umiObjectTypesCollection::getInstance();
		    $typeId     = $types->getBaseType("emarket", "delivery_address");
		    $customer   = customer::get();
		    $addressId  = $collection->addObject("Address for customer #".$customer->id, $typeId);
		    $dataModule = $cmsController->getModule("data");
		    if($dataModule) {
		      $dataModule->saveEditedObject($addressId, true, true);
		    }
		    $customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
		  }
		  $order->delivery_address = $addressId;
		  $this->redirect("/emarket/fast_payment"); 
		}

		public function save_payment(){
		  $order = $this->getBasketOrder(false);
		  //сохранение регистрационных данных
		  $cmsController = cmsController::getInstance();
		  $data = $cmsController->getModule('data');
		  $data->saveEditedObject(customer::get()->id, false, true);
		 
		  //сохранение способа оплаты и редирект на итоговую страницу, либо страницу подтверждения оплаты.
		  $order->setValue('payment_id', getRequest('payment-id'));
		  $order->refresh();
		 
		  $paymentId = getRequest('payment-id');
		  if(!$paymentId) {
		    $this->errorNewMessage(getLabel('error-emarket-choose-payment'));
		    $this->errorPanic();
		  }
		  $payment = payment::get($paymentId);
		 
		  if($payment instanceof payment) {
		    $paymentName = $payment->getCodeName();
		    $url = "{$this->pre_lang}/".cmsController::getInstance()->getUrlPrefix()."emarket/purchase/payment/{$paymentName}/";
		  } else {
		    $url = "{$this->pre_lang}/".cmsController::getInstance()->getUrlPrefix()."emarket/cart/";
		  }
		  $this->redirect($url);
		}
	};
?>
