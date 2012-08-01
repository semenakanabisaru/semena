<?php

	abstract class __banners_admin extends baseModuleAdmin {
		public function config() {

			$regedit = regedit::getInstance();
			$params = Array (
				"config" => Array (
					"int:days-before-notification" => null,
					"int:clicks-before-notification" => null
				)
			);

			$mode = getRequest("param0");
			if ($mode == "do"){
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/banners/days-before-notification", (int) $params["config"]["int:days-before-notification"]);
				$regedit->setVar("//modules/banners/clicks-before-notification", (int) $params["config"]["int:clicks-before-notification"]);
				$regedit->setVal("//modules/banners/last-check-date", "");
				$this->chooseRedirect();
			}
			$params["config"]["int:days-before-notification"] = (int) $regedit->getVal("//modules/banners/days-before-notification");
			$params["config"]["int:clicks-before-notification"] = (int) $regedit->getVal("//modules/banners/clicks-before-notification");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();

		}
	};
?>
