<?php

	abstract class __config_users extends baseModuleAdmin {

		public function config() {
			$regedit = regedit::getInstance();
			$objectTypesColl = umiObjectTypesCollection::getInstance();

			$params = Array(
				"config" => Array(
					"guide:def_group" => Array('type-id' => $objectTypesColl->getTypeIdByGUID('users-users'), 'value' => NULL),
					"guide:guest_id" => Array('type-id' => $objectTypesColl->getTypeIdByGUID('users-user'), 'value' => NULL),
					"boolean:without_act" => NULL
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);

				$regedit->setVar("//modules/users/def_group",   $params['config']['guide:def_group']);
				$regedit->setVar("//modules/users/guest_id",    $params['config']['guide:guest_id']);
				$regedit->setVar("//modules/users/without_act", $params['config']['boolean:without_act']);

				$this->chooseRedirect();
			}


			$params['config']['boolean:without_act'] = $regedit->getVal("//modules/users/without_act");
			$params['config']['guide:def_group']['value'] = $regedit->getVal("//modules/users/def_group");
			$params['config']['guide:guest_id']['value'] = $regedit->getVal("//modules/users/guest_id");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

	};

?>