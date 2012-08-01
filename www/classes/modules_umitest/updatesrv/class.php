<?php

class updatesrv extends def_module {

	protected $allow_ip = array(
		'vik_theo' => '178.130.35.192',
		'baklan'   => '77.221.157.146',
		'office'   => '178.16.152.254'
	);

	public function __construct() {
                parent::__construct();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__updatesrv");

			$this->__loadLib("__licenses.php");
			$this->__implement("__licenses_updatesrv");

			$this->sheets_add("Лицензии", "licenses");
		} else {
			$this->__loadLib("__server.php");
			$this->__implement("__server_updatesrv");

			$this->__loadLib("__updates.php");
			$this->__implement("__updates_updatesrv");
		}
	}

	public static function generateLicense($licenseCodeName, $domainName, $ipAddr = false) {
		if($ipAddr === false) {
			if(!($ipAddr = gethostbyname($domainName)) || $ipAddr == $domainName) {
				trigger_error("Failed to generate license: can't get ip by hostname", E_USER_WARNING);
				return false;
			}
		}

		$cs1 = md5($time = time());
		$cs2 = md5($ipAddr);
		$cs3 = NULL;
		
		switch($licenseCodeName) {
			case "old_free":
				$cs3 = md5($domainName);
				break;

			case "start":
			case "free":
				$cs3 = md5(md5(md5($domainName)));
				break;
				
			case "old_lite":
				$cs3 = md5(md5(md5(md5($domainName))));
				break;
				
			case "lite":
				$cs3 = md5(md5(md5(md5(md5($domainName)))));
				break;
				
			case "lite_plus":
			case "liteplus":
			case "freelance":
				$cs3 = md5(md5(md5(md5(md5(md5(md5($domainName)))))));
				break;

			case "business":
			case "business_enc":
			case "commerce":
			case "commerce_enc":
			case "corporate":
			case "corporate_enc":
			case "pro":
			case "gov":
				$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($domainName))))))))));
				break;

			case "trial":
				$cs3 = md5(md5(md5(md5(md5(md5($domainName))))));
				break;

			case "shop":
				$cs3 = md5(md5($domainName . "shop"));
			break;

			default:
				trigger_error("Failed to generate license: don't know license codename \"{$licenseCodeName}\"", E_USER_WARNING);
				return false;
				break;
		}

		$licenseKeyCode = strtoupper(substr($cs1, 0, 11) . "-" . substr($cs2, 0, 11) . "-" . substr($cs3, 0, 11));

		$res = Array	(
					"keycode"	=> $licenseKeyCode,
					"timestamp"	=> $time,
					"ip"		=> $ipAddr,
					"host"		=> $domainName
		);

		return $res;
	}

	public function getLicenseInfoByKeycode($keycode) {
		if (!$keycode) $keycode = getRequest('keycode');
		$object_id = false;
		$block_arr = array();
		if (strlen($keycode)) {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
			$object_type_id =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hierarchy_type_id);
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			$sel = new umiSelection;
			$sel->addObjectType($object_type_id);
			$sel->addPropertyFilterEqual($object_type->getFieldId('keycode'), $keycode);
			$sel->addLimit(1);

			$matches = umiSelectionsParser::runSelection($sel);
			if (isset($matches[0])) {
				$object_id = $matches[0];
				$object = umiObjectsCollection::getInstance()->getObject($object_id);
				$block_arr['license'] = $object;
			}
		}
		xmlTranslator::$FULL_INFO_MODE = true;
		$res =  self::parseTemplate("", $block_arr, false, $object_id);
		return $res;
	}

	public function generatePrimaryKeycode() {
		return strtoupper(substr(md5(uniqid()), 0, 11) . "-" . substr(md5(uniqid()), 0, 11) . "-" . substr(md5(uniqid()), 0, 11));
	}


	public function test() {
//		return $this->sendSiteUpdate(26345);
//		return $this->sendSiteUpdate(26359);
//		return $this->sendAllUpdates();
//		return $this->sendSiteUpdate(26345);
//		return $this->sendSiteUpdate(26363);
//		return $this->sendSiteUpdate(26337);	//bonanzaland.ru
//		return $this->sendSiteUpdate(26345);	//lyxsus.ru
//		return $this->sendAllUpdates();
//		return $this->sendSiteUpdate(30495);	//umi-cms.pl.ru
//		return $this->sendSiteUpdate(31059);	//rsport.ru
//		return $this->sendSiteUpdate(31202);	//umitest.kunstkamera.ru
//		return $this->sendSiteUpdate(31206);	//aquatorygroup.ru
//		return $this->sendSiteUpdate(31233);	//i-kids.ru
//		return $this->sendSiteUpdate(27076);	//test.umi-cms.ru
//		return $this->sendSiteUpdate(27107);	//wwwerh.ru
//		return $this->sendSiteUpdate(31206);	//aquatorygroup.ru
//		return $this->sendSiteUpdate(31919);	//install-pro-commerce.umi-cms.ru
//		return $this->sendSiteUpdate(27080);	//install-pro-corporate.umi-cms.ru
//		return $this->sendSiteUpdate(31214);	//test.webmaster.spb.ru
//		return $this->sendSiteUpdate(27078);	//www.umistudio.com
//		return $this->sendAllUpdates();
//		return $this->sendSiteUpdate(27099);	//www.timetolive.ru
//		return $this->sendSiteUpdate(31805);	//mekong-com.1gb.ru
//		return $this->sendAllUpdates();
//		return $this->sendSiteUpdate(31914);	//sam-site.ru
//		return $this->sendAllUpdates();
//		return $this->sendSiteUpdate(32517);	//papanet.ru
//		return $this->sendSiteUpdate(27111);	//production-corporate.umi-cms.ru
		return $this->sendSiteUpdate(33172);
	}

		
		public function getUpdateHistory($objectId = false) {
			$sel = new selector("objects");
			$sel->types('object-type')->name('updatesrv', 'history_updates');
			$sel->where('license')->equals($objectId);
			$sel->order("date_update");
			
			$block_arr = array();			
			foreach($sel->result() as $object) {				
				$item_arr = array(
					'attribute:id' => $object->getId(),
					'attribute:date' => $object->date_update,
					'attribute:old_system' => umiObjectsCollection::getInstance()->getObject($object->old_revision)->name,					
					'attribute:new_system' => umiObjectsCollection::getInstance()->getObject($object->new_revision)->name
				);
				
				$items[] = self::parseTemplate('', $item_arr);
	}
	
			$block_arr['subnodes:items'] = $items;			
			return def_module::parseTemplate('', $block_arr);			
		}	

	public function setVipSupport($license_id, $support_period, $mode = false) {
		if ($license_id && $support_period) {
			if (!is_numeric($license_id)) {
				$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
				$umiObjectsCollection = umiObjectsCollection::getInstance();
				$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
				$object_type_id = $umiObjectTypesCollection->getTypeByHierarchyTypeId($hierarchy_type_id);
				$object_type = $umiObjectTypesCollection->getType($object_type_id);
				$sel = new umiSelection;
				$sel->addObjectType($object_type_id);
				$sel->setConditionModeOR();
				$sel->addPropertyFilterEqual($object_type->getFieldId('keycode'), $license_id);
				$sel->addPropertyFilterEqual($object_type->getFieldId('domain_keycode'), $license_id);
				$sel->addLimit(1);
				$result = umiSelectionsParser::runSelection($sel);
				if (isset($result[0])) $license_id = $result[0];
			}
			$license = umiObjectsCollection::getInstance()->getObject($license_id);

			$block_arr = array();
			if ($license instanceof umiObject) {
				$current_time = time();
				$vipsupport_start = $license->getValue('vipsupport_start');
				$vipsupport_start = ($vipsupport_start instanceof umiDate) ? $vipsupport_start->getFormattedDate('U') : 0;
				$vipsupport_end   = $license->getValue('vipsupport_end');
				$vipsupport_end   = ($vipsupport_end instanceof umiDate) ? $vipsupport_end->getFormattedDate('U') : 0;

				if ($current_time > $vipsupport_end) {
					$vipsupport_start = $current_time;
					$vipsupport_end = $current_time + $support_period;
				}
				else {
					$vipsupport_start = $vipsupport_start ? $vipsupport_start : $vipsupport_end;
					$vipsupport_end = $vipsupport_end + $support_period;
				}
				if ($mode && $mode === 'set') {
					$license->setValue('vipsupport_start', $vipsupport_start);
					$license->setValue('vipsupport_end', $vipsupport_end);
					$license->commit();
				}
				$block_arr['keycode']           = $license->getValue('keycode');
				$block_arr['activated_date']    = $vipsupport_start;
				$block_arr['completion_date']   = $vipsupport_end;
			}
			else $block_arr['error'] = "error";
		}
		else $block_arr['error'] = "error";

		return self::parseTemplate('', $block_arr);
	}

	public function getObjectEditLink($objectId, $type) {
		return $this->pre_lang . "/admin/updatesrv/edit/" . $objectId . "/";
	}

	public function createLicense() {
		$block_arr = array();
		if (in_array($_SERVER['REMOTE_ADDR'], $this->allow_ip)) {
			if (isset($_REQUEST['data']) && is_array($_REQUEST['data'])) {
				$data = $_REQUEST['data'];
				$objectsCollection = umiObjectsCollection::getInstance();
				$typesCollection = umiObjectTypesCollection::getInstance();
				$typeId = $typesCollection->getBaseType('updatesrv', 'license');
				$name = isset($data['owner_company']) ? $data['owner_company'] : '(Без названия)';
				$objectId = $objectsCollection->addObject($name, $typeId);
				$license = $objectsCollection->getObject($objectId);
				if ($license instanceof umiObject) {
					$current_time = $data['gen_time'];
					$object_type = $typesCollection->getType($typeId);
					foreach($data as $field_name => $field_value) {
						$license->setValue($field_name, $field_value);
					}
					if ($domain_name = $license->getValue("domain_name")) {
						$license->setName($domain_name);
					}
					$license->setValue("keycode", $this->generatePrimaryKeycode());
					$support_time = false;
					if (isset($data['support_ts'])) $support_time = $data['support_ts'];
					if (!$support_time) $support_time = ($current_time + (3600 * 24 * 365));
					$license->setValue("support_time", $support_time);

					if ($data['license_type'] == '27062') {
						$license->setValue('vipsupport_start', $current_time);
						$license->setValue('vipsupport_end', ($current_time + 7776000));
					}

					$license->commit();
					$block_arr['id'] = $license->getId();
					$block_arr['keycode'] = $license->getValue('keycode');
				}
				else $block_arr['error'] = "Can't create license";
			}
			else $block_arr['error'] = "Empty data";
		}
		else $block_arr['error'] = "Incorrect address";

		return self::parseTemplate('', $block_arr);
	}

	public function updateLicense() {
		$block_arr = array();
		if (in_array($_SERVER['REMOTE_ADDR'], $this->allow_ip)) {
			if (isset($_REQUEST['data']) && is_array($_REQUEST['data'])) {
				$data = $_REQUEST['data'];
				$objectsCollection = umiObjectsCollection::getInstance();
				foreach($data as $licenseId => $fields) {
					if ($objectsCollection->isExists($licenseId)) {
						$license = $objectsCollection->getObject($licenseId);
						foreach($fields as $fieldName => $fieldValue) {
							$license->setValue($fieldName, $fieldValue);
						}
						$license->commit();
					}
					else $block_arr['error'] = "Incorrect license id";
				}
			}
			else $block_arr['error'] = "Empty data";
		}
		else $block_arr['error'] = "Incorrect address";

		return self::parseTemplate('', $block_arr);
	}

	public function googleAdwords($keycode, $google_adwords_key = false) {
		$block_arr = array();
		if (!$keycode) {
			$block_arr['status'] = 'false';
			return self::parseTemplate('', $block_arr);
		}
		$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
		$umiObjectsCollection = umiObjectsCollection::getInstance();
		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
		$object_type_id = $umiObjectTypesCollection->getTypeByHierarchyTypeId($hierarchy_type_id);
		$object_type = $umiObjectTypesCollection->getType($object_type_id);
		$sel = new umiSelection;
		$sel->addObjectType($object_type_id);
		$sel->setConditionModeOR();
		$sel->addPropertyFilterEqual($object_type->getFieldId('keycode'), $keycode);
		$sel->addPropertyFilterEqual($object_type->getFieldId('domain_keycode'), $keycode);
		$sel->addLimit(1);
		$sel->setPermissionsFilter();
		$result = umiSelectionsParser::runSelection($sel);
		$license_id = false;
		if (isset($result[0])) $license_id = $result[0];
		if ($license_id) {
			$license = $umiObjectsCollection->getObject($license_id);
			$license_type = $license->getValue('license_type');
			$allowed_types = array(
				"103236" => "Start",
				"27055" => "Lite",
				"27064" => "Corporate",
				"103237" => "Shop",
				"27060" => "Business",
				"27062" => "Commerce",
				"113226" => "Gov"
			);
			if (is_array($license_type)) $license_type = $license_type[0];
			$license_type = (isset($allowed_types[$license_type])) ? $allowed_types[$license_type] : false;
			if ($owner_email = $license->getValue('owner_email')) {
				if ($license_type) {
					if ($license->getValue('google_adwords_key')) {
						$google_adwords_key = $license->getValue('google_adwords_key');
						$status = 'restore';
					}
					else {
						if ($google_adwords_key !== false) {
							$license->setValue('google_adwords_key', $google_adwords_key);
							$status = 'complete';
						}
					}
					$block_arr['email'] = $license->getValue('owner_email');
					$block_arr['key'] = $google_adwords_key;
					$block_arr['status'] = $status;
					$block_arr['type'] = $license_type;
				}
				else $block_arr['status'] = 'denied';
			}
			else {
				$block_arr['type'] = $license_type;
				$block_arr['status'] = 'email';
			}
		}
		else $block_arr['status'] = 'false';
		return self::parseTemplate('', $block_arr);
	}

	public function getDownloadDistribution($test = 111) {
		$block_arr = array();
		if (in_array($_SERVER['REMOTE_ADDR'], $this->allow_ip)) {
			if (isset($_REQUEST['data']) && is_array($_REQUEST['data'])) {
				$data = $_REQUEST['data'];
				$time = time();
				$license_arr = array();
				$objectsColl = umiObjectsCollection::getInstance();
				foreach ($data as $license_id) {
					$license = $objectsColl->getObject($license_id);
					if ($license instanceof umiObject) {
						if ($license->getValue('support_time')->timestamp > $time) {
							$license_arr[$license->getValue('license_type')] = $license->getValue('license_type');
						}
					}
				}
				$block_arr['types'] = implode('-', $license_arr);
			}
			else $block_arr['error'] = "Empty data";
		}
		else $block_arr['error'] = "Incorrect address";
		return self::parseTemplate('', $block_arr);
	}

};
?>
