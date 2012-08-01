<?php
	abstract class __emarket_custom {
		//TODO: Write here your own macroses

		//
		// данные для оформления заказа
		//
		public function fast_purchasing(){
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
		 
		//
		// сохранение этапа "оформление заказа"
		// 
		public function save_forming_stage(){
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
		  $this->redirect("/order_payment/"); 
		}

		//
		// сохранение этапа "способ оплаты"
		// 
		public function save_payment_stage(){
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
		
		//
		// замена order
		// 
		public function orderMy($orderId = false, $template = 'default') {	
			if($this->breakMe()) return;
			if(!$template) $template = 'default';
			$permissions = permissionsCollection::getInstance();

			$orderId = (int) ($orderId ? $orderId : getRequest('param0'));
			if(!$orderId) {
				throw new publicException("You should specify order id");
			}

			$order = order::get($orderId);
			if($order instanceof order == false) {
				throw new publicException("Order #{$orderId} doesn't exists");
			}

			if(!$permissions->isSv() && ($order->getName() !== 'dummy') &&
			   (customer::get()->getId() != $order->customer_id) &&
			   !$permissions->isAllowedMethod($permissions->getUserId(), "emarket", "control")) {
				throw new publicException(getLabel('error-require-more-permissions'));
			}

			list($tpl_block, $tpl_block_empty) = def_module::loadTemplates("emarket/".$template,
				'order_block', 'order_block_empty');

			$discount = $order->getDiscount();

			$totalAmount = $order->getTotalAmount();
			$originalPrice = $order->getOriginalPrice();
			$actualPrice = $order->getActualPrice();
			$deliveryPrice = $order->getDeliveryPrice();

			if($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			$discountAmount = ($originalPrice) ? $originalPrice + $deliveryPrice - $actualPrice : 0;


			// полчаем дополнительные нужные поля
			$order_date = (string)$order->getValue('order_date');
			$post_id = $order->getValue('post_id');


			$result = array(
				'attribute:id'	=> ($orderId),
				'xlink:href'	=> ('uobject://' . $orderId),
				'customer'		=> ($order->getName()  == 'dummy') ? null : $this->renderOrderCustomer($order, $template),
				'subnodes:items'=> ($order->getName()  == 'dummy') ? null : $this->renderOrderItems($order, $template),
				'summary'		=> array(
					'amount'		=> $totalAmount,
					'price'			=> $this->formatCurrencyPrice(array(
						'original'		=> $originalPrice,
						'delivery'		=> $deliveryPrice,
						'actual'		=> $actualPrice,
						'discount'		=> $discountAmount
					))
				),
				'order_date' 	=> urlencode($order_date),
				'post_id'		=> $post_id
			);

			if ($order->number) {
				$result['number'] = $order->number;
				$result['status'] = selector::get('object')->id($order->status_id);
			}

			if(sizeof($result['subnodes:items']) == 0) {
				$tpl_block = $tpl_block_empty;
			}

			$result['void:total-price'] = $this->parsePriceTpl($template, $result['summary']['price']);
			$result['void:delivery-price'] = $this->parsePriceTpl($template, $this->formatCurrencyPrice(array('actual' => $deliveryPrice)));
			$result['void:total-amount'] = $totalAmount;

			if($discount instanceof discount) {
				$result['discount'] = array(
					'attribute:id'		=> $discount->id,
					'attribute:name'	=> $discount->getName(),
					'description'		=> $discount->getValue('description')
				);
				$result['void:discount_id'] = $discount->id;
			}
			return def_module::parseTemplate($tpl_block, $result, false, $order->id);
		}

		//
		// функции для управления адресами
		//
		public function delivery_edit($template = "edit") {
			if(!$template) $template = "default";
			$object_id = (int) $_REQUEST['param0'];
			list($template_block) = def_module::loadTemplates("tpls/emarket/delivery/{$template}.tpl", "delivery_edit_block");
			$block_arr = Array();
			$block_arr['id'] = $object_id;
			return def_module::parseTemplate($template_block, $block_arr);
		}
		public function delivery_edit_do($template = "default") {
			$objectsCollection = umiObjectsCollection::getInstance();
			$cmsController = cmsController::getInstance();
 
			$object_id = (int) getRequest('param0');
			if(!permissionsCollection::getInstance()->isOwnerOfObject($object_id)) {
				return "%data_edit_foregin_object%";
			}
			$object = $objectsCollection->getObject($object_id);
			$data_module = $cmsController->getModule('data');
			$data_module->saveEditedObject($object_id);
			$object->commit();
			$s_redirect_url = getServer('HTTP_REFERER');
			if (strlen($s_redirect_url) && false) {
				$this->redirect($s_redirect_url);
			} else {
				$this->redirect($this->pre_lang . "/users/settings/");
			}
		}
 
		public function delivery_add_do() {
 
				$controller = cmsController::getInstance();
				$collection = umiObjectsCollection::getInstance();
				$types      = umiObjectTypesCollection::getInstance();
				$typeId     = $types->getBaseType("emarket", "delivery_address");
				$customer   = customer::get();
				$addressId  = $collection->addObject("Address for customer #".$customer->id, $typeId);
				$dataModule = $controller->getModule("data");
				if($dataModule) {
					$dataModule->saveEditedObject($addressId, true, true);
				}
				$customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
 
			$this->redirect($this->pre_lang . '/users/settings/');
		}
 
 
		public function delivery_del() {
			$object_id = (int) getRequest('param0');
 
			if (permissionsCollection::getInstance()->isOwnerOfObject($object_id)) {
				umiObjectsCollection::getInstance()->delObject($object_id);
			}
			$s_redirect_url = getServer('HTTP_REFERER');
			if (strlen($s_redirect_url)) {
				$this->redirect($s_redirect_url);
			} else {
				$this->redirect($this->pre_lang . "/users/settings/");
			}
		}
 
		public function customerDeliveryList($template = 'default') {
		  $order = $this->getBasketOrder();
			list($tpl_block, $tpl_item) = def_module::loadTemplates("./tpls/emarket/delivery/{$template}.tpl",
				'delivery_address_block', 'delivery_address_item');
 
			$customer  = customer::get();
			$addresses = $customer->delivery_addresses;
			$items_arr = array();
 
			$collection = umiObjectsCollection::getInstance();
 
			if(is_array($addresses)) foreach($addresses as $address) {
				$addressObject = $collection->getObject($address);
 
				$item_arr = array(
					'attribute:id'		=> $address,
					'attribute:name'	=> $addressObject->name
				);
				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr, false, $address);
			}
 
			$types  = umiObjectTypesCollection::getInstance();
			$typeId = $types->getBaseType("emarket", "delivery_address");
 
			if($tpl_block) {
				return def_module::parseTemplate($tpl_block, array('items' => $items_arr, 'type_id' => $typeId));
			} else {
				return array(
					'attribute:type-id'	=> $typeId,
					'xlink:href'		=> 'udata://data/getCreateForm/' . $typeId,
					'items'				=> array('nodes:item'	=> $items_arr)
				);
			}
		}


		/**
			* Получить список всех заказов текущего пользователя
		*/
		public function parentMy($iid) {
			// return $iid;
			$inst = umiObjectsCollection::getInstance();
			$obj = $inst->getObject((int) $iid);
			if ($obj instanceof umiObject) {
				$val = $obj->getValue('item_link');
				return (string) $val[0]; 
			} return 'q';
		}

		public function ordersListMy($template = 'default') {
			list($tpl_block, $tpl_block_empty, $tpl_item) = def_module::loadTemplates("emarket/".$template, 'orders_block', 'orders_block_empty', 'orders_item');

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$domainId = $domain->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals(customer::get()->id);
			$sel->where('name')->isNull(false);
			$sel->where('domain_id')->equals($domainId);

			// сортировка
			$sel->order('order_date')->desc();

			if($sel->length == 0) $tpl_block = $tpl_block_empty;

			$items_arr = array();
			foreach($sel->result as $order) {
				$item_arr['attribute:id'] = $order->id;
				$item_arr['attribute:name'] = $order->name;
				$item_arr['attribute:type-id'] = $order->typeId;
				$item_arr['attribute:guid'] = $order->GUID;
				$item_arr['attribute:type-guid'] = $order->typeGUID;
				$item_arr['attribute:ownerId'] = $order->ownerId;
				$item_arr['xlink:href'] = $order->xlink;

				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr, false, $order->id);
			}
			return def_module::parseTemplate($tpl_block, array('subnodes:items' => $items_arr));
		}


		public function onStatusChangedMy(iUmiEventPoint $event) {
			if($event->getMode() == "after" &&
				$event->getParam("old-status-id") != $event->getParam("new-status-id")) {
				$order = $event->getRef("order");
				$this->notifyOrderStatusChangeMy($order, "status_id");
				
			}
		}
		public function notifyOrderStatusChangeMy(order $order, $changedProperty) {
			
			$statusId = $order->getValue($changedProperty);
			$codeName = order::getCodeByStatus($statusId);
			if($changedProperty == 'status_id' && $codeName == 'waiting') {
				$this->sendManagerNotificationMy($order);
			}
			
			
		}
		public function sendManagerNotificationMy(order $order) {
			$inst = umiObjectsCollection::getInstance();
			$hierarhy = umiHierarchy::getInstance();
			$regedit  = regedit::getInstance();
			$emails	  = $regedit->getVal('//modules/emarket/manager-email');
			$letter = new umiMail();
			$recpCount = 0;
			foreach (explode(',' , $emails) as $recipient) {
				$recipient = trim($recipient);
				if (strlen($recipient)) {
					$letter->addRecipient($recipient);
					$recpCount++;
				}
			}
			if(!$recpCount) return;
			$content='<head>
				<style>
					p {color:#f00;}
				</style>
			</head>';
			$content.= 'Добрый день! Спасибо что выбрали магазин "Семяныч"
			<h3>Ваш заказ: </h3>
			<table style="width:600px">
				<tr>
					<td>
						<strong>Сорт</strong>
					</td>
					<td>
						<strong>Упаковка</strong>
					</td>
					<td>
						<strong>Количество</strong>
					</td>
					<td>
						<strong>Цена</strong>
					</td>
					<td>
						<strong>Сумма</strong>
					</td>
				</tr>';
			foreach ($order->order_items as $item) {
				$content.='<tr>';
				$it = $inst->getObject($item);
				$pg = $it->item_link;
				$parentId = $pg[0]->getParentId();
				$sort = $hierarhy->getElement($parentId);
				$content.='<td>'.$sort->getName().'</td>';
				$content.='<td>'.$it->name.'</td>';
				$content.='<td>'.$it->item_amount.'</td>';
				$content.='<td>'.$it->item_price.'</td>';
				$content.='<td>'.$it->item_total_price.'</td>';				
				$content.='</tr>';
			}
			$content.='</table>';

			$extra = 0; //Стоимость почтовой страховки наложенного платежа
			$pay = $order->payment_id;
			$deliv = $order->delivery_id;
			$content.='</table>';
			$content.= '<strong>Общая сумма: </strong> '.$order->total_original_price.' руб<br/>';
			if ($order->order_discount_id) {
				$sal = $order->order_discount_id;
				$obj_inst = $inst->getObject($sal);
				$sale_summ = $order->total_original_price - $order->total_price;
				$content.='<strong>'.$obj_inst->description.' Сумма скидки:</strong> '.(int) $sale_summ.' руб<br/>';
				$content.='<strong>Сумма с учётом скидки: </strong>'.(int)$order->total_price.' руб<br/>';
			}
			$content.= '<p><strong>Способ оплаты: </strong> '.$inst->getObject($pay)->getName().'<br/>';
			$content.= '<strong>Способ доставки: </strong> '.$inst->getObject($deliv)->getName().'<br/>';
			$content.= '<strong>Стоимость доставки: </strong>250 руб<br/>';
			if ($pay == '882') {
				$extra = (int)$order->total_price * 0.1;
				$content.= '<strong>Стоимость почтовой страховки наложенного платежа +10%: </strong> '.(int) $extra.' руб<br/>';	
			}
			$end = (int)$order->total_price + 250 + (int) $extra;
			$content.= '</p><h4>Итого:  '.$end.' руб</h4>';
			switch ($pay) {
				case '882':
				$content.='<p>Мы подготавливаем и упаковываем Ваш заказ. В ближайшее время он будет отправлен.</p>
			<p>Вы сможете оплатить данную сумму по факту получения заказа  в почтовом отделении, который Вы указали при оформлении заказа.';
					break;
				case '883'://web money
					$content.='<p>Поскольку Вы выбрали оплату через WebMoney, мы упакуем и отправим заказ после поступления денежных средств на наш счет. Обращаем внимание на то, что Ваш заказ будет находиться в резерве в течении 2-х суток с момента отправки Вам письма на электронную почту.
					Терминалы оплаты берут комиссию за перевод денежных средств, поэтому необходимо, чтобы "сумма к зачислению" была не менее оплачиваемой суммы заказа. </p>

					<p>Номер счёта кошелька для перевода : R340962466436<br/>
					В комментарии к переводу укажите номер Вашего заказа(он указан в теме письма).</p>';

				break;
				case '988': //yandex
					$content.='<p>Поскольку Вы выбрали оплату через Яндекс Деньги, мы упакуем и отправим заказ после поступления денежных средств на наш счет.
					Обращаем внимание на то, что Ваш заказ будет находиться в резерве в течении 2-х суток с момента отправки Вам письма на электронную почту.
					Терминалы оплаты берут комиссию за перевод денежных средств, поэтому необходимо, чтобы "сумма к зачислению" была не менее оплачиваемой суммы заказа.</p> 

					<p>Номер счёта кошелька для перевода : 410011316145790<br/>
					В комментарии к переводу укажите номер Вашего заказа(он указан в теме письма).</p>';
				break;
				case '989': //qiwi
					$content.='<p>Поскольку Вы выбрали оплату через Qiwi кошелек, мы упакуем и отправим заказ после поступления денежных средств на наш счет. Обращаем внимание на то, что Ваш заказ будет находиться в резерве в течении 2-х суток с момента отправки Вам письма на электронную почту.
					Терминалы оплаты берут комиссию за перевод денежных средств, поэтому необходимо, чтобы "сумма к зачислению" была не менее оплачиваемой суммы заказа. </p>

					<p>Номер Qiwi кошелька для перевода : 9196283738<br/>
					В комментарии к переводу укажите номер Вашего заказа(он указан в теме письма).</p>';

				break;
				case '990': //Click 
					$content.='<p>Поскольку Вы выбрали оплату через Альфа клик, мы упакуем и отправим заказ после поступления денежных средств на наш счет.
					Обращаем внимание на то, что Ваш заказ будет находиться в резерве в течении 2-х суток с момента отправки Вам письма на электронную почту.
					Терминалы оплаты берут комиссию за перевод денежных средств, поэтому необходимо, чтобы "сумма к зачислению" была не менее оплачиваемой суммы заказа. </p>

					<p><span style="text-decoration:underline;">Информация, необходимая для оплаты:</span><br />
					Наименование получателя: Писаренко Глеб Юрьевич<br />
					Номер счета получателя: 40817810008480005203<br />
					Назначение платежа(комментарий к платежу): номер Вашего заказа (указан в теме письма)</p>';
				break;
			}
			
			

			 

			$content.='
			<p>После отправления заказа, мы сообщим в письме почтовый идентификатор, по которому Вы сможете отслеживать состояние посылки. 
			 Сроки доставки в Ваш город зависят от работы Почты России. Мы, в свою очередь, делаем всё возможное для того, чтобы максимально 
			 быстро обработать заказ и передать его почте.</p>
			<p>Актуальная информация по поводу обновления ассортимента и работы нашего магазина доступна на официальной странице Вконтакте (<a href="http://vk.com/semyanich">http://vk.com/semyanich</a>)</p>
				Ждём Вас в друзьях, друзья!';

			$content.='<p>Во избежании отправления Вашего заказа "не туда", будьте добры, ещё раз проверьте введенные Вами данные. Если Вы обнаружите ошибку, напишите об этом нам, пожалуйста.</p>
			<table style="width:600px;">';

			$fieldsCollection = umiFieldsCollection::getInstance();
			$adress = $inst->getObject($order->delivery_address);
			//$adress_group
			$adress_fileds_group = $adress->getPropGroupByName('common');
			foreach ($adress_fileds_group as $value) {
				$field = $fieldsCollection->getField($value);
				$type = $field->getFieldType();
				if ($type->getDataType() == 'relation') {
					$val = $adress->getValue($field->getName());
					$nobj = $inst->getObject($val);
					$content.='<tr><td>'.$field->getTitle().'</td><td>'.$nobj->getName().'</td></tr>';
				}	else {
					$content.='<tr><td>'.$field->getTitle().'</td><td>'.$adress->getValue($field->getName()).'</td></tr>';
				}
			}
			$content.='</table>';
			
			$letter->setFrom('noreply@semena-kanabisa.ru','Семяныч Семена');
			$letter->setSubject('Заказ #'.$order->number);
			$letter->addRecipient('basurovav@gmail.com');
			$letter->setContent($content);
			$letter->commit();
			$letter->send();
			

		
		}

	};
?>
