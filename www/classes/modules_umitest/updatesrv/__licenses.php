<?php
	abstract class __licenses_updatesrv extends baseModuleAdmin {
/*
		public function license_edit_do() {
			$license_id = (int) getRequest('param0');

			$license_object = umiObjectsCollection::getInstance()->getObject($license_id);

			if(cmsController::getInstance()->getModule('data')) {
				cmsController::getInstance()->getModule('data')->saveEditedGroups($license_id, true);
			}		
			$license_object->commit();

			$license_object->update();

			$license_type_id = $license_object->getValue("license_type");
			$license_type = umiObjectsCollection::getInstance()->getObject($license_type_id);

			if($license_type) {
				$license_codename = $license_type->getValue("codename");
			} else  {
				$license_codename = "";
			}

			$domain_name = $license_object->getValue("domain_name");
			$ip = $license_object->getPropByName("ip")->getValue();
			$ip = ($ip) ? $ip : false;

			$license_info = updatesrv::generateLicense($license_codename, $domain_name, $ip);

			if($domain_name && $ip && !$license_object->getValue("domain_keycode")) {
				if($license_codename == "old_free" || $license_codename == "old_lite" || $license_codename == "old_lite_plus") {
					$domain_keycode = $license_info['keycode'];
					$license_object->setValue("domain_keycode", $domain_keycode);
				}
			}


			$license_object->commit();
		}

*/
		
		public function json_check_updates() {
			$res = "";
			header("Content-type: text/javascript; charset=utf-8");
			$domain_keycode = getRequest("param0");
			
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
			list($type_id) = array_keys(umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($hierarchy_type_id));
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$sel = new umiSelection;

			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$sel->setPropertyFilter();
			$field_id = $type->getFieldId("domain_keycode");

			$sel->addPropertyFilterLike($field_id, $domain_keycode, true);

			$result = umiSelectionsParser::runSelection($sel);
			
			list($version, $revision) = explode("\n", file_get_contents("./status.txt"));


			if(sizeof($result) && (($revision > getRequest('param1') && $revision != (getRequest('param1') + 1000)) || getRequest('param1') == "download")) {
//if(false) {
				list($license_id) = $result;
				$license = umiObjectsCollection::getInstance()->getObject($license_id);
				
				
				$license_type_id = $license->getValue("license_type");
				$license_type = umiObjectsCollection::getInstance()->getObject($license_type_id);
				
				$version_line = $license_type->getValue("version_line");


			if($domain_keycode == "D884CCFA924-F39541AA22D-76CA70C0CAB" || true) {
				list($license_id) = $result;
				$license = umiObjectsCollection::getInstance()->getObject($license_id);

				$date = $license->getValue("support_time");
				if(is_object($date)) {
					$time = $date->getFormattedDate("U");
					if(time() > ($time)) {
					    $res .= <<<JS
var result = {};
result['status'] = false;
result['revision'] = "{$revision}";
returnResult(result);

alert("Обновления отключены, так как закончился срок поддержки");
JS;
					    $this->flush($res);
					}
				}
			}





				if(!getRequest("param0")) $version_line = "commerce_enc";

				if(getRequest("param1") == "download") {
					$license->setValue("autoupdate_last_time", time());
					$license->commit();

					$this->provideUpdate($version_line, "4335t45tfe3ed34t45g");
				}
				
				

			
				$res .= <<<JS
var result = {};
result['status'] = true;
result['version_line'] = "{$version_line}";
result['version'] = "{$version}";
result['revision'] = "{$revision}";
returnResult(result);

alert("После первого запуска обновления может возникнуть необходимость обновить страницу в браузере и нажать кнопку обновления системы еще раз.\\nПосле первого обновления до 2.7 может понадобиться переиндексировать поиск вручную.");
//alert("В версии 2.6 изменен API административного интерфейса. \\nЕсли вы используете сторонние модули, убедитесь, что они используют новый API. \\nВ противном случае их администрирование будет недоступно.");

JS;
			} else {
				$res .= <<<JS
var result = {};
result['status'] = false;
result['revision'] = "{$revision}";
returnResult(result);

JS;
			}
			$this->flush($res);
		}
		
		
		public function provideUpdate($version_line, $c) {
			$res = "";
			$part_id = (string) getRequest('param2');
			if(!is_null(getRequest('param3'))) {
				$part_id = (string) getRequest('param3');
			}
			$part_size = 524288;

			if($c != "4335t45tfe3ed34t45g") {
				return false;
			}
			

			
			$res = $update_path = "./stable-updates/{$version_line}/update.ucp";

			if(file_exists($update_path)) {
				header("Content-type: text/plain");
				
				if($part_id == "") {
					echo file_get_contents($update_path);
				} else {
					$part_id = (int) $part_id;
					
					$cont = file_get_contents($update_path);
					$cont = substr($cont, (($part_id - 1) * $part_size), $part_size);
					
					if(strlen($cont) == 0) {
						echo "[EOF]";
					} else {
						echo $cont;
					}
				}
				exit();
			}
			
			$this->flush($res);
		}
	}
?>