<?php
	class payanywayPayment extends payment {
		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$currency = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency'));
			if ($currency == 'RUR'){
				$currency = 'RUB';
			}
			$amount			= number_format($this->order->getActualPrice(), 2, '.', '');
			$orderId		= $this->order->getId();
			$merchantId		= $this->object->mnt_id;
			$dataIntegrityCode	= $this->object->mnt_data_integrity_code;
			$successUrl		= $this->object->mnt_success_url;
			$failUrl		= $this->object->mnt_fail_url;
			$testMode		= $this->object->mnt_test_mode;
			$systemUrl		= $this->object->mnt_system_url;
			if (empty($testMode)){
				$testMode = 0;
			}
			$signature	 = md5("{$merchantId}{$orderId}{$amount}{$currency}{$testMode}{$dataIntegrityCode}");
			$param = array();
			$param['formAction'] 		= "https://{$systemUrl}/assistant.htm";
			$param['mntId'] 			= $merchantId;
			$param['mnTransactionId']	= $orderId;
			$param['mntCurrencyCode'] 	= $currency;
			$param['mntAmount'] 	 	= $amount;
			$param['mntTestMode'] 	 	= $testMode;
			$param['mntSignature'] 		= $signature;
			$param['mntSuccessUrl'] 	= $successUrl;
			$param['mntFailUrl'] 	 	= $failUrl;

			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/payanyway/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType("text/plain");
			if (!is_null(getRequest('MNT_ID')) && !is_null(getRequest('MNT_TRANSACTION_ID')) && !is_null(getRequest('MNT_OPERATION_ID')) && !is_null(getRequest('MNT_AMOUNT')) && !is_null(getRequest('MNT_CURRENCY_CODE')) && !is_null(getRequest('MNT_TEST_MODE')) && !is_null(getRequest('MNT_SIGNATURE'))) {
				$dataIntegrityCode  = $this->object->mnt_data_integrity_code;
				$signature	 = md5(getRequest('MNT_ID') . getRequest('MNT_TRANSACTION_ID') . getRequest('MNT_OPERATION_ID') . getRequest('MNT_AMOUNT') . getRequest('MNT_CURRENCY_CODE') . getRequest('MNT_TEST_MODE') . $dataIntegrityCode);
				if (getRequest('MNT_SIGNATURE') == $signature){
					$this->order->setPaymentStatus('accepted');
					$buffer->push("SUCCESS");
				} else {
					$buffer->push("FAIL");
				}
			} else {
				$buffer->push("FAIL");
			}
			$buffer->end();
		}
	};
?>