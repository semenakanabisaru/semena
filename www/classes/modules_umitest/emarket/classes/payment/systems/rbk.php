<?php
	class rbkPayment extends payment {
		/**
		* Статусы платежа (см. документацию по подключению RBK Money)
		*/
		const STATUS_INPROCESS = 3;
		const STATUS_ACCEPTED  = 5;

		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$currency = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency') );
			$amount = number_format($this->order->getActualPrice(), 2, '.', '');
			$param = array();
			$param["formAction"] = "https://rbkmoney.ru/acceptpurchase.aspx";
			$param["eshopId"] = $this->object->eshopId;
			$param["orderId"] = $this->order->id;
			$param["recipientAmount"] = $amount;
			$param["recipientCurrency"] = $currency;
			$param["version"] = "2"; // May be 1 or 2, see documentation
			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/rbk/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType("text/plain");
			if($this->checkSignature()) {
				$status = getRequest("paymentStatus");
				switch($status) {
					case rbkPayment::STATUS_INPROCESS : {
						$recipientAmount = (float) getRequest("recipientAmount");
						$checkAmount = (float) $this->order->getActualPrice();
						if(($recipientAmount - $checkAmount) < (float)0.001) { // Almost equals
							$this->order->setPaymentStatus('validated');
							$buffer->push("OK");
						} else {
							$this->order->setPaymentStatus('declined');
							$buffer->push("failed");
						}
						break;
					}
					case rbkPayment::STATUS_ACCEPTED  : {
						$this->order->setPaymentStatus('accepted');
						$buffer->push("OK");
						break;
					}
				}
			} else {
				$buffer->push("failed");
			}
			$buffer->end();
		}

		private function checkSignature() {
			$eshopId = getRequest('eshopId');
			$orderId = getRequest('orderId');
			$serviceName = getRequest('serviceName');
			$eshopAccount = getRequest('eshopAccount');
			$recipientAmount = getRequest('recipientAmount');
			$recipientCurrency = getRequest('recipientCurrency');
			$paymentStatus = getRequest('paymentStatus');
			$userName = getRequest('userName');
			$userEmail = getRequest('userEmail');
			$paymentDate = getRequest('paymentData');
			$secretKey = $this->object->secretKey;
			$hash  = getRequest("hash");
			$check = md5("{$eshopId}::{$orderId}::{$serviceName}::{$eshopAccount}::{$recipientAmount}::{$recipientCurrency}::{$paymentStatus}::{$userName}::{$userEmail}::{$paymentDate}::{$secretKey}");
			return (bool)(strcasecmp($hash, $check) == 0);
		}
	};
?>
