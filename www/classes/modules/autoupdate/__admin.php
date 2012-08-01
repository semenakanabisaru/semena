<?php
	abstract class __autoupdate extends baseModuleAdmin {

		public function versions() {
			$regedit = regedit::getInstance();
			$systemEdition = $regedit->getVal("//modules/autoupdate/system_edition");
			$systemEditionStatus = "%autoupdate_edition_" . $systemEdition . "%";
			
			if($systemEdition == "commerce_trial" && $_SERVER['HTTP_HOST'] != 'localhost' && $_SERVER['HTTP_HOST'] != 'subdomain.localhost' && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
				$daysLeft = $regedit->getDaysLeft();
				$systemEditionStatus .= " ({$daysLeft} " . getLabel('label-days-left') . ")";
			}

			$systemEditionStatus = def_module::parseTPLMacroses($systemEditionStatus);

			$params = Array(
				"autoupdate" => Array(
					"status:system-edition"		=> NULL,
					"status:last-updated"		=> NULL,
					"status:system-version"		=> NULL,
					"status:system-build"		=> NULL,
					"status:db-driver"			=> NULL,
					"boolean:disabled"			=> NULL,
					"boolean:patches-disabled"	=> NULL
				)
			);


			$params['autoupdate']['status:system-version'] = $regedit->getVal("//modules/autoupdate/system_version");
			$params['autoupdate']['status:system-build'] = $regedit->getVal("//modules/autoupdate/system_build");
			$params['autoupdate']['boolean:patches-disabled'] = (int) $patches_disabled = $regedit->getVal("//modules/autoupdate/disable_patches");
			$params['autoupdate']['status:system-edition'] = $systemEditionStatus;
			$params['autoupdate']['status:last-updated'] = date("Y-m-d H:i:s", $regedit->getVal("//modules/autoupdate/last_updated"));
			
			$db_driver = "mysql";
			if(defined("DB_DRIVER")) {
				$db_driver = DB_DRIVER;
			}
			$params['autoupdate']['status:db-driver'] = $db_driver;



			$autoupdates_disabled = false;
			if(defined("CURRENT_VERSION_LINE")) {
				if(in_array(CURRENT_VERSION_LINE, array("start", "demo"))) {
					$autoupdates_disabled = true;
				}
			}

			$params['autoupdate']['boolean:disabled'] = (int) $autoupdates_disabled;


			$this->setDataType("settings");
			$this->setActionType("view");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		public function patches($mode = false) {
			if (isset($_GET['mode'])) $mode = $_GET['mode'];
			$regedit = regedit::getInstance();
			$patches_disabled = $regedit->getVal("//modules/autoupdate/disable_patches");
			$version = $regedit->getVal("//modules/autoupdate/system_version");
			$build = $regedit->getVal("//modules/autoupdate/system_build");
			$applied = $regedit->getList("//modules/autoupdate/applied_patches//");
			if (!$patches_disabled == true) {
				$array = array();
				$a = '';
				if ($applied!==false) {
					foreach ($applied as &$value) {
						array_push($array, $value[0]);
					}
					$a = "<item>";
					$a .= implode('</item><item>', $array);
					$a .= "</item>";
				}
				$data['xml:applied'] = "<applied>{$a}</applied>";
				
				if ($mode === "all") {
					$url = "http://hub.umi-cms.ru/patches/xml/";
					$data['xml:caution'] = "<caution>all</caution>";
				} else {
					$data['xml:caution'] = "<caution>normal</caution>";
				}
				
				if ($version && $build) {
					if ($mode === false) $url = "http://hub.umi-cms.ru/patches/xml/?version=" . $version . "&revision=" . $build;
					$data['xml:info'] = umiRemoteFileGetter::get($url);
				}
	
				$this->setDataType("settings");
				$this->setActionType("view");
	
				$this->setData($data);
				return $this->doData();
			}
		}

		/**
		* Функция применяет или откатывает патч
		* 
		*/
		public function getDiff() {
			if (CURRENT_VERSION_LINE=='demo') {
				$message = getLabel('label-stop-in-demo');
				$code = "failed";
			} else {
				$regedit = regedit::getInstance();
				if (isset($_GET['id'])) $id = $_GET['id'];
				if (isset($_GET['type'])) $type = $_GET['type'];
				if (isset($_GET['repository'])) $repository = $_GET['repository'];
				if (isset($_GET['link'])) $url = $_GET['link'];
				$code = "";
				$message = "";

				if ($id != false) {
					$dir = "./sys-temp/diffs/" . $repository . "/";
					$filename = $dir . $id . ".patch";
				
					if ($type === 'apply') {

						if(is_dir($dir) == false) {
							if (!mkdir($dir, 0777, true)) {
								$message = getLabel('label-diff-dir-create-failed');
								$code = "failed";
							}
						}

						umiRemoteFileGetter::get($url, $filename);

						if (!file_exists($filename) || !filesize($filename) > 50) {
							$message = getLabel('label-diff-get-failed');
							$code = "failed";
						}
					}

					$root = $_SERVER['DOCUMENT_ROOT'];

					$tryPatch = shell_exec("patch -v");
					if (!stristr($tryPatch,'Copyright')) {
						$message = getLabel('label-diff-not-patch');
						$code = "failed";
						} else {
							switch ($type) {
								case "apply":
									$shell = "cd " . $root . "; patch -p0 -b -i " . $filename;
									$output = shell_exec($shell);
									if (stristr($output,'failed') || !$output) { 
										$message = getLabel('label-diff-applying-failed');
										$code = "failed";
									} else {
										$regedit->setVar("//modules/autoupdate/applied_patches/". $id."/", NULL);
										$message = getLabel('label-diff-applied');
										$code = "ok";
									}
									break;
								case "revert":
										$shell = "cd " . $root . "; patch -p0 -b -R -i " . $filename;
										$output = shell_exec($shell);
										if (stristr($output,'failed') || !$output) { 
											$message = getLabel('label-diff-reverting-failed');
											$code = "failed";
										} else {
											$regedit->delVar("//modules/autoupdate/applied_patches/". $id."/");
											$message = getLabel('label-diff-reverted');
											$code = "ok";
										}
									break;
								default:
									$message = getLabel('label-diff-notype');
									$code = "failed";
								break;
							}
						}
					}
				}

			$data['xml:response'] = '<response message="'.$message.'" code="'.$code.'"/>';

			$this->setDataType("settings");
			$this->setActionType("view");

			$this->setData($data);
			return $this->doData();
		}

	};
?>
