<?php
	abstract class __mails_config extends baseModuleAdmin {

		public function mails() {
			$regedit = regedit::getInstance();

			$params = Array(
				"mails" => Array(
					"string:email_from"	=> NULL,
					"string:fio_from"	=> NULL
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);

				if(!is_demo()) {
					$regedit->setVar("//settings/email_from", $params['mails']['string:email_from']);
					$regedit->setVar("//settings/fio_from", $params['mails']['string:fio_from']);
				}

				$this->chooseRedirect();
			}

			$params['mails']['string:email_from'] = $regedit->getVal("//settings/email_from");
			$params['mails']['string:fio_from'] = $regedit->getVal("//settings/fio_from");


			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

	};
?>