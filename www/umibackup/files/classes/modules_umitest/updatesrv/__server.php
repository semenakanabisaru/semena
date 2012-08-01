<?php
	abstract class __server_updatesrv {
		public function initInstallation() {
			$domain = getRequest('domain');
			$ip = getRequest('ip');
			$keycode = getRequest('keycode');

			$status = 'FAILED';
			
			$msg = "";

			if(!$domain || !$ip) {
				$msg = "Не передан ip и домен";
			}


			$sel = new umiSelection;

			$type_id = umiObjectTypesCollection::getInstance()->getBaseType("updatesrv", "license");

			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$keycode_field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId("keycode");

			$sel->setPropertyFilter();
			$sel->addPropertyFilterEqual($keycode_field_id, $keycode);
			$result = umiSelectionsParser::runSelection($sel);

			$fname = "";
			$lname = "";
			$mname = "";
			$email = "";
			
			$domain_keycode = "";
			$license_type = "";
			$license_codename_out = "";



			if(sizeof($result)) {
				list($license_id) = $result;
				$license = umiObjectsCollection::getInstance()->getObject($license_id);

				if($license->getValue("domain_keycode") == "" || ($license->getValue("ip") == $ip && $license->getValue("domain_name") == $domain)) {

					$fname = $license->getValue("owner_fname");
					$lname = $license->getValue("owner_lname");
					$mname = $license->getValue("owner_mname");
					$email = $license->getValue("owner_email");


					$license_type_id_tmp = $license_type_id = $license->getValue("license_type");
					if(!$license_type_id_tmp) {
						$license_type_id_tmp = 27053;
					}
					
					if($license_type = umiObjectsCollection::getInstance()->getObject($license_type_id)) {
						$status = "OK";
						
						$license_type_obj = $license_type;
						$license_type = $license_type->getName();

						$is_unlimited = (bool) $license_type_obj->getValue("is_unlimited");
						
						$license_codename_out = $license_codename = $license_type_obj->getValue("codename");
						if($license_codename == "pro") {
							$license_codename_out = $license_type_obj->getValue("version_line");
						}

						$domain_keycode = $license->getValue("domain_keycode");
						if(!$domain_keycode || $keycode == "TRIAL-LICENSE-TEST") {
							$domain_keycode = $this->generateLicense($license_codename, $domain, $ip);
							$domain_keycode = $domain_keycode['keycode'];

							if(!$is_unlimited) {
								$license->setValue("activate_time", time());
								$license->setValue("support_time", time() + 3600*24*365);
								$license->setValue("domain_keycode", $domain_keycode);
								$license->setValue("domain_name", $domain);
								$license->setValue("ip", $ip);
								$license->setValue("license_type", $license_type_id_tmp);
								$license->commit();
							}
						}
					} else {
						$msg = "Сотрудники UMI-CMS допустили ошибку при генерации лицензии. Обязательно сообщите об этой проблеме в техническую поддержку для немедленного исправления. Приносим свои извинения.";
					}
				} else {
					$msg = "Эта лицензия уже была использована. Обратитесь в техническую поддержку.";
				}
			} else {
				$msg = "Неверный лицензионный ключ";
			}

			

			$requestId = $_REQUEST['requestId'];

			$res = <<<END

var response = {
	'status'		: 	'{$status}',
	'msg'			:	'{$msg}',
	'first_name'		: 	'{$fname}',
	'second_name'		: 	'{$mname}',
	'last_name'		: 	'{$lname}',
	'email'			: 	'{$email}',
	'domain_keycode'	: 	'{$domain_keycode}',
	'license_type'		:	'{$license_type}',
	'license_codename'	:	'{$license_codename_out}'
};

requestsController.getSelf().reportRequest('{$requestId}', response);
END;


			header("Content-type: text/javascript; charset=utf-8");
			$this->flush($res);
		}

	};
?>