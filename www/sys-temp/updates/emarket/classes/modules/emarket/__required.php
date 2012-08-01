<?php
	abstract class __emarket_required {
		public static $steps = array('personal');
		
		public function required(order $order, $step, $mode, $template) {
			switch($step) {
				case 'personal': {
					return ($mode == 'do') ? $this->savePersonalInfo($order) : $this->editPersonalInfo($order, $template);
				}
			}
		}
		
		public function requiredCheckStep(order $order, $step) {
			if(!$step) return self::$steps[0];
			if(in_array($step, self::$steps)) {
				return $step;
			} else {
				throw new privateException("Unkown order delivery step \"{$step}\"");
			}
		}
		
		public function editPersonalInfo(order $order, $template) {
			list($tpl_block) = def_module::loadTemplates("emarket/required/".$template, 'required_block');
			$customerId = customer::get()->id;
			return def_module::parseTemplate($tpl_block, array(
				'customer_id' => $customerId,
				'customer-id' => $customerId
			));
		}

		public function savePersonalInfo($order) {
			$cmsController = cmsController::getInstance();
			$data = $cmsController->getModule('data');
			$data->saveEditedObject(customer::get()->id, false, true);
			$this->redirect($this->pre_lang . '/'.cmsController::getInstance()->getUrlPrefix() . 'emarket/purchase/delivery/address/');
		}
	}
?>