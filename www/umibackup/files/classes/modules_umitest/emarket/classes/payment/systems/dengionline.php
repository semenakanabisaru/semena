<?php
	class dengionlinePayment extends payment {

		public function validate() {
			return true;
		}

		public function process($template = null) {

			$currency = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency') );
			if ($currency == 'RUR'){
				$currency = 'RUB';
			}

			list($templateString, $modeItem) = def_module::loadTemplates("emarket/payment/dengionline/" . $template, 'form_block', 'mode_type_item');

			$modeTypeItems = array();

			$xml = '<request>
				<action>get_project_paymodes</action>
				<projectId>' . $this->object->project . '</projectId>
			</request>';

			$headers = array ("Content-type" => "application/x-www-form-urlencoded");
			$paymentsXML = umiRemoteFileGetter::get('http://www.onlinedengi.ru/dev/xmltalk.php', false, $headers, array('xml' => $xml));

			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->loadXML($paymentsXML);
			if ($dom->getElementsByTagName('paymentMode')->length) {
				foreach($dom->getElementsByTagName('paymentMode') as $payment) {
					$modeTypeItems[] = def_module::parseTemplate($modeItem, array('id' => $payment->getAttribute('id'), 'label' => $payment->getAttribute('title')));
				}
			}

			$order = $this->order;
			$order->order();

			$orderId = $order->getId();

			$param = array();
			$param['formAction'] = "http://www.onlinedengi.ru/wmpaycheck.php?priznak=UMI";
			$param['project'] = $this->object->project; // Задаётся пользователем в настройках магазина, поле "ID проекта"
			$param['amount'] = $order->getActualPrice();
			$param['nickname'] = $orderId;
			$param['order_id'] = $orderId;
			$param['source'] = $this->object->source; // Задаётся пользователем в настройках магазина, поле "Source (если есть)"
			$param['comment'] = "Payment for order " . $orderId;
			$param['paymentCurrency'] = $currency;
			$param['subnodes:items'] = $param['void:mode_type_list'] = $modeTypeItems;

			$order->setPaymentStatus('initialized');
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$amount  = getRequest('amount');
			$userId = (int) getRequest('userid');
			$paymentId = (int) getRequest('paymentid');
			$orderId = (int) getRequest('orderid');
			$key = getRequest('key');

			$success = false;

			if (!$orderId && $userId) {
				$key = getRequest('key');
				$checkSign = md5('0' . $userId . '0' . $this->object->key);
				if ($checkSign == $key) {
					$success = true;
				}

			} elseif ($orderId && $paymentId) {

				$checkSign = md5($amount . $userId . $paymentId . $this->object->key);
				if ($checkSign == $key && ($this->order->getActualPrice() - $amount) < (float) 0.001) {
					$this->order->setPaymentStatus('accepted');
					$this->order->payment_document_num = $paymentId;
					$success = true;
				}
			}

			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType("text/xml");

			if ($success) {
				$buffer->push('<?xml version="1.0" encoding="UTF-8"?>
								<result>
									<id>' . $orderId . '</id>
									<code>YES</code>
								</result>');
			} else {
				$this->order->setPaymentStatus('declined');
				$buffer->push('<?xml version="1.0" encoding="UTF-8"?>
								<result>
									<id>' . $orderId . '</id>
									<code>NO</code>
								</result>');
			}
			$buffer->end();
		}
	};
?>