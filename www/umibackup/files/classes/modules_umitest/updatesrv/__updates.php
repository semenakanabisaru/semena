<?php
	abstract class __updates_updatesrv {
		public function sendSiteUpdate($license_id) {
			clearstatcache();

			$license = umiObjectsCollection::getInstance()->getObject($license_id);

			$domain_name = $license->getValue("domain_name");

			if(!$domain_name) return false;

			$service_url = "http://{$domain_name}/autoupdate/service/";



			if($license_type_id = $license->getValue("license_type")) {
				$license_type = umiObjectsCollection::getInstance()->getObject($license_type_id);
				$license_line = $license_type->getValue("version_line");
			} else {
				return false;
			}

			echo "\nUpdating site {$domain_name} ({$license_line})...\t";


			$revision = $this->getDomainLastRevision($service_url);

			if($revision === false) {
				echo "\tAccess denied";
				return false;
			}

			if(!$revision) {
				$revision = 777;

				echo "Site returned zero-revision. Skipping.";
				return false;
			}
			
			if($revision < 1722 || true) {
				$revision = 1722;
			}

			$r = $this->prepareUpdatePackage($license_line, $revision);

			if(!$r) {
				echo "\tUnkown error occured. Skipping.";
				return false;
			}

			list($package_file_path, $new_revision) = $r;


			if($new_revision <= $revision) {
				echo "\tSite is alredy updated. Skipping.";
				return false;
			}

			$this->sendUpdatePackage($license_id, $service_url, $package_file_path);
			$this->commitUpdatePackage($service_url, $new_revision);

			$license->setValue("autoupdate_last_time", time());
			$license->commit();
		}


		public function getDomainLastRevision($service_url) {
			$cont = @file_get_contents($service_url . "version/");
			if($cont === false) return false;

			list($version, $revision) = explode("\n", $cont);

			return (int) trim($revision);
		}


		public function prepareUpdatePackage($license_line, $revision) {
			switch($license_line) {
				case "business":
					$doc_path = "/home/umi-cms.ru/production-business.umi-cms.ru";
					break;
					
				case "business_enc":
					$doc_path = "/home/umi-cms.ru/production-business-encoded.umi-cms.ru";
					break;

				case "corporate":
					$doc_path = "/home/umi-cms.ru/production-corporate.umi-cms.ru";
					break;
					
				case "soho":
					$doc_path = "/home/umi-cms.ru/production-lite.umi-cms.ru";
					break;

				default:
					return false;
			}

			$cmd = "{$doc_path}/svn_get_revision.sh";
			$new_revision = explode(" ", `$cmd`);
			list(, $new_revision) = $new_revision;
			$new_revision = trim($new_revision);

			error_reporting(E_ALL);

			$package_file_path = "{$doc_path}/output/update_{$new_revision}.ucp";

//			if(!file_exists($package_file_path)) {
				$cmd = "{$doc_path}/packer_updates.php {$revision} {$new_revision}";
//				echo `$cmd`;
				`$cmd`;
//			}

			return Array($package_file_path, $new_revision);
		}


		public function sendUpdatePackage($license_id, $service_url, $package_file_path) {
			$service_url .= "package/";

			$postfields = Array	(
							"package" => "@$package_file_path"
						);


			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $service_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

			$result = curl_exec($ch);
			curl_close($ch);

			if(file_exists($package_file_path)) {
				unlink($package_file_path);
			}
		}


		public function commitUpdatePackage($service_url, $revision) {
			$service_url .= "commit/" . $revision;
			$cont = file_get_contents($service_url);

			if(strlen($cont) < 1024) {
				echo "\tOk";
			} else {
				echo "\tFailed";
			}
		}


		public function sendAllUpdates() {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
			list($type_id) = array_keys(umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($hierarchy_type_id));

			$autoupdate_is_disabled_field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId("autoupdate_is_disabled");

			$sel = new umiSelection;

			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$sel->setPropertyFilter();
			$sel->addPropertyFilterEqual($autoupdate_is_disabled_field_id, 0);

			$result = umiSelectionsParser::runSelection($sel);
			$total = umiSelectionsParser::runSelectionCounts($sel);

			foreach($result as $license_id) {
				echo $this->sendSiteUpdate($license_id);
			}

			echo "\n\n";
		}
	};
?>
