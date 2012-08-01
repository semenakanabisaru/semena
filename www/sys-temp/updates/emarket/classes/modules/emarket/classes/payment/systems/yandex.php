<?php
	class yandexPayment extends payment {
		/**
		 * Статусные коды Яндекс.Денег (см. документацию Яндекс.Денег)
		 */
		const STATUS_SUCCESS	   = 0;
		const STATUS_AUTHERROR     = 1;
		const STATUS_DECLINE	   = 100;
		const STATUS_REQUESTERROR  = 200;
		const STATUS_INTERNALERROR = 1000;
		/**
		* IP-адреса биллинга
		*/
		private static $BILLING_IP = array('77.75.152.36', '77.75.157.172', '77.75.157.163', '77.75.152.36', '77.75.157.168', '77.75.157.169', '77.75.159.166', '77.75.159.170');

		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$shopId = $this->object->shop_id;
			$bankId = $this->object->bank_id;
			$scid   = $this->object->scid;
			if(!strlen($shopId) || !strlen($scid)) {
				throw new publicException(getLabel('error-payment-wrong-settings'));
			}
			$productPrice = (float) $this->order->getActualPrice();
			$param = array();
			$param['shopId'] = $shopId;
			$param['Sum']    = $productPrice;
			$param['BankId'] = $bankId;
			$param['scid']   = $scid;
			$param['CustomerNumber'] = $this->order->getId();
			$param['formAction']     = $this->object->demo_mode ? 'https://demomoney.yandex.ru/eshop.xml' : 'https://money.yandex.ru/eshop.xml';
			$param['orderId']		 = $this->order->getId();
			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/yandex/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType('text/xml');
			$action    = getRequest('action');
			$shopId	   = getRequest('shopId');
			$invoiceId = getRequest('invoiceId');
			$responseCode = yandexPayment::STATUS_SUCCESS;
			if(!$this->checkSignature()) {
				$responseCode = yandexPayment::STATUS_AUTHERROR;
			} else if(is_null($shopId) || is_null($invoiceId)) {
				$responseCode = yandexPayment::STATUS_REQUESTERROR;
			} else {				
				switch(strtolower($action)) {
					case 'check'		  : $responseCode = $this->checkDetails();		  break;
					case 'paymentsuccess' : $responseCode = $this->acceptPaymentResult(); break;
					default				  : $responseCode = yandexPayment::STATUS_REQUESTERROR;
				}
			}
			$this->order->payment_document_num = $invoiceId;
			$buffer->push( $this->getResponseXML($action, $responseCode, $shopId, $invoiceId) );
			$buffer->end();
		}
		/**
		 * Производит проверку платежных данных
		 * @return Int статус проверки
		 */
		private function checkDetails() {
			$resultCode     = yandexPayment::STATUS_SUCCESS;
			$orderSumAmount = (float) getRequest('orderSumAmount');
			try {
				$actualPrice = (float) $this->order->getActualPrice();
				if($orderSumAmount != $actualPrice) {
					$this->order->setPaymentStatus('declined');
					$resultCode = yandexPayment::STATUS_DECLINE;
				} else {					
					$this->order->setPaymentStatus('validated');
					$resultCode = yandexPayment::STATUS_SUCCESS;
				}
			} catch (Exception $e) {
				$resultCode = yandexPayment::STATUS_INTERNALERROR;
			}
			return $resultCode;
		}
		/**
		 * Принимает результат платежной транзакции
		 * @return Int статус
		 */
		private function acceptPaymentResult() {
			$resultCode = yandexPayment::STATUS_SUCCESS;
			try {				
				$this->order->setPaymentStatus('accepted');
			} catch(Exception $e) {
				$resultCode = yandexPayment::STATUS_INTERNALERROR;
			}
			return $resultCode;
		}
		/**
		 * Проверяет подпись в запросе
		 * @return Boolean true - если запрос валиден, false в противном случае
		 */
		public function checkSignature() {
			if(!in_array(getServer('REMOTE_ADDR'), yandexPayment::$BILLING_IP)) return false;
			$password = (string) $this->object->shop_password;
			if(!strlen($password)) return false;
			$hashPieces   = array();
			$hashPieces[] = getRequest('orderIsPaid');
			$hashPieces[] = getRequest('orderSumAmount');
			$hashPieces[] = getRequest('orderSumCurrencyPaycash');
			$hashPieces[] = getRequest('orderSumBankPaycash');
			$hashPieces[] = getRequest('shopId');
			$hashPieces[] = getRequest('invoiceId');
			$hashPieces[] = getRequest('customerNumber');
			$hashPieces[] = $password;
			$hashString   = md5(implode(';', $hashPieces));
			if(strcasecmp($hashString, getRequest('md5') ) == 0) {
				return true;
			}
			return false;
		}
		/**
		 * Формирует xml для ответа на сервер Яндекс денег
		 * @param String $action    Код запроса, на которое выполняется ответ
		 * @param Int    $code      Код результата
		 * @param Int    $shopId    Идентификатор магазина
		 * @param Int    $invoiceId Идентификатор транзакции
		 * @return String
		 */
		public function getResponseXML($action, $code, $shopId, $invoiceId) {
			$dateTime = date('c');
			$result   = "<"."?xml version=\"1.0\" encoding=\"windows-1251\" ?".">" . <<<XML
<response performedDatetime="{$dateTime}" >
    <result code="{$code}" action="{$action}" shopId="{$shopId}" invoiceId="{$invoiceId}" />
</response>
XML;
			return $result;
		}
	};
?>