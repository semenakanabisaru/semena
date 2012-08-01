<?php

	class exchange extends def_module {

		protected $currency_aliases = array(
			'RUR' => array('руб', 'руб.', 'р', 'rub'),
			'USD' => array('$', 'у.е.'),
			'EUR' => array('є', 'евро')
		);

		public function __construct () {
			parent::__construct ();

			$commonTabs = $this->getCommonTabs();
			if($commonTabs) {
				$commonTabs->add('import');
				$commonTabs->add('export');
			}

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__exchange");

				$this->__loadLib("__import.php");
				$this->__implement("__exchange_import");
			}

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_exchange");

			$this->__loadLib("__export.php");
			$this->__implement("__exchange_export");

			// 1C Auto integration
			$this->__loadLib("__auto.php");
			$this->__implement("__exchange_auto");
		}


		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . "/admin/exchange/edit/" . $objectId . "/";
		}

		public function getCurrencyCodeByAlias() {
			$alias = getRequest('alias');

			foreach ($this->currency_aliases as $code => $aliases) {
				for($i = 0; $i < count($aliases); $i++) if ($alias == $code ||	$alias == $aliases[$i]) return $code;
			}
			
			if ($emarket = cmsController::getInstance()->getModule('emarket')) {
				if ($def = $emarket->getDefaultCurrency()) {
					return $def->codename;
				}
			}

			return "RUR";
		}

		public function getTranslatorSettings() {
			$cfg = mainConfiguration::getInstance();
			$arr_settings = $cfg->getList('modules');
			$translator_settings = array();
			for ($i = 0; $i < count($arr_settings); $i++) {
				$key = $arr_settings[$i];
				if (strpos($key, 'exchange.translator') !== false) {
					$translator_settings[] = array(
						'attribute:key' => $key,
						'node:value' => $cfg->get('modules', $key)
					);
				}
			}

			return array(
				'subnodes:settings' => $translator_settings
			);
		}



	};

?>