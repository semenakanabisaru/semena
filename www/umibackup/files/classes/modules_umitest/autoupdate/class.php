<?php
	class autoupdate extends def_module {
		public function __construct() {
			parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$regedit = regedit::getInstance();
				$systemEdition = $regedit->getVal("//modules/autoupdate/system_edition");
				
				$patches_disabled = false;
				if(strpos($systemEdition, 'trial') !== false || (strpos($systemEdition, 'commerce_enc') !== false) || (strpos($systemEdition, 'start') !== false)) $patches_disabled = true;
				
				if ($patches_disabled) {
					$regedit->setVar("//modules/autoupdate/disable_patches", "1");
				} else {
					$regedit->setVar("//modules/autoupdate/disable_patches", "0");
				}
				
				$commonTabs = $this->getCommonTabs();
				if($commonTabs && !$patches_disabled) {
					$commonTabs->add('versions');
					$commonTabs->add('patches');
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__autoupdate");

				$this->__loadLib("__json.php");
				$this->__implement("__json_autoupdate");
			}
		}


		public function service () {
			$event = strtoupper(getRequest('param0'));

			$autoupdates_disabled = (bool) regedit::getInstance()->getVal("//modules/autoupdate/autoupdates_disabled");
			if($autoupdates_disabled) {
				$this->flush('DISABLED', "text/plain");
			}

			$this->checkIsValidSender();

			switch($event) {
				case "STATUS":
					$result = $this->returnStatus();
					break;

				case "VERSION":
					$result = $this->returnVersions();
					break;

				case "LAST_UPDATED":
					$result = $this->returnLastUpdated();
					break;

				case "MODULES": {
					$result = $this->getModules();
					break;
				}

				case "DOMAINS": {
					$result = $this->getDomains();
					break;
				}

				default:
					$result = "UNHANDLED_EVENT";
					break;
			}

			$this->flush($result, "text/plain");
		}


		protected function returnStatus () {
			return (string) regedit::getInstance()->getVal("//modules/autoupdate/status");
		}


		protected function returnVersions() {
			$regedit = regedit::getInstance();
			return (string) $regedit->getVal("//modules/autoupdate/system_version") . "\n" . $regedit->getVal("//modules/autoupdate/system_build");
		}


		public function returnLastUpdated() {
			return (string) $this->getLastUpdated();
		}


		protected function checkIsValidSender () {
			//TODO
		}


		protected function getModules() {
			$regedit = regedit::getInstance();
			$ml = $regedit->getList("//modules");

			$res = "";
			foreach($ml as $m) {
				$res .= $m[0] . "\n";
			}
			return $res;
		}


		protected function getDomains() {
			$domainsCollection = domainsCollection::getInstance();
			$domains = $domainsCollection->getList();

			$res = "";
			foreach($domains as $domain) {
				$res .= $domain->getHost() . "\n";
			}
			return $res;
		}


		public function getCurrentVersion () {
			return (string) regedit::getInstance()->getVal("//modules/autoupdate/system_version");
		}


		public function setCurrentVersion ($version) {
			regedit::getInstance()->setVal("//modules/autoupdate/system_version", (string) $version);
		}


		public function getLastUpdated () {
			return (string) (int) regedit::getInstance()->getVal("//modules/autoupdate/last_updated");
		}


		public function setLastUpdated ($time) {
			regedit::getInstance()->setVal("//modules/autoupdate/last_updated", (int) $time);
		}

		public function getDaysLeft () {

			if(($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'subdomain.localhost') && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
				return;
			}

			$regedit = regedit::getInstance();
			$systemEdition = $regedit->getVal("//modules/autoupdate/system_edition");

			if(strpos($systemEdition, 'trial') !== false|| (strpos($systemEdition, 'commerce_enc') !== false)) {
				$daysLeft = $regedit->getDaysLeft();
				return array('trial'=>array('attribute:daysleft'=>$daysLeft));
			}
		}
	};
?>
