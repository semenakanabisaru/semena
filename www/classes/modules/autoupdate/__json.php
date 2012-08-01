<?php
	abstract class __json_autoupdate {
		public function json_check_updates() {
			header("Content-type: text/javascript; charset=utf-8");
			$res = <<<JS
window.location = "/smu/index.php";
JS;
			$this->flush($res);
		}


		public function json_install_updates() {
			header("Content-type: text/javascript; charset=utf-8");
			$res = <<<JS
window.location = "/smu/index.php";
JS;
			$this->flush($res);
		}


		public function saveUpdateFile($res) {
			if(!is_dir("./updates")) {
				mkdir("./updates");
			}

			file_put_contents("./updates/update.ucp", $res);
			chmod("./updates/update.ucp", 0600);
		}

		public function commitUpdateFile() {
			list($version, $revision) = explode("\n", umiRemoteFileGetter::get("http://udod.umihost.ru:82/status.txt"));

			if($version && $revision) {
				include "classes/umiDistr/umiDistrReader.php";
				include "classes/umiDistr/umiDistrInstallItem.php";
				include "classes/umiDistr/umiDistrFile.php";
				include "classes/umiDistr/umiDistrFolder.php";

				$distr = new umiDistrReader("./updates/update.ucp");
				unset($distr);

				if(file_exists("./updates/update.ucp")) {
					unlink("./updates/update.ucp");
				}
				
				$this->updateTypes();

				$last_updated_time = time();
				
				$regedit = regedit::getInstance();

				$regedit->setVar("//modules/autoupdate/system_version", $version);
				$regedit->setVar("//modules/autoupdate/system_build", $revision);
				$regedit->setVar("//modules/autoupdate/last_updated", $last_updated_time);


				if(file_exists("./cache/reg")) {
					unlink("./cache/reg");
				}
				
				$modules = $regedit->getList("//modules");
				foreach($modules as $md)  {
					list($module_name) = $md;
					
					$fpath = "classes/modules/" . $module_name . "/update.php";
					if(file_exists($fpath)) {
						include_once $fpath;
					}
				}

				return $last_updated_time;
			} else {
				return false;
			}
		}
		
		
		public function updateTypes() {
			$modules = regedit::getInstance()->getList("//modules");
			foreach($modules as $module) {
				list($module) = $module;
				
				$filename = "./classes/modules/{$module}/types.xml";
				
				if(file_exists($filename)) {
					$importer = new umiModuleDataImporter();
					$importer->loadXmlFile($filename);
					$importer->import();
				}
			}
		}
		
		
		public function cleanupUpdateFiles() {
			$updatedir = "./updates";
		
			if(is_dir($updatedir)) {
				$dir = opendir($updatedir);
				while($obj = readdir($dir)) {
					if(substr($obj, 0, 1) == ".") continue;
					$objpath = $updatedir . "/" . $obj;
					if(file_exists($objpath)) {
						unlink($objpath);
					}
				}
			}
		}
		
		/*
		public function loadPartialUpdate($url, $part_id) {
			$updatedir = "./updates";
			$url = $url . $part_id . "/";
			
			$filename = $updatedir . "/update.{$part_id}.ucp";
			
			$cont = umiRemoteFileGetter::get($url);
			
			if($cont) {
				if($cont == "[EOF]") return false;

				file_put_contents($filename, $cont);
				unset($cont);
				return true;
			} else {
				return false;
			}
		}
		
		
		public function mergePartialUpdate() {
			$updatedir = "./updates";

			$merged_filename = $updatedir . "/update.ucp";
			
			if(file_exists($merged_filename)) {
				unlink($merged_filename);
			}
			touch($merged_filename);
			chmod($merged_filename, 0600);
			
			for($i = 1; true; $i++) {
				$filename = $updatedir . "/update.{$i}.ucp";
				
				if(file_exists($filename)) {
					$cont = file_get_contents($filename);
					
					$f = fopen($merged_filename, "a");
					fwrite($f, $cont);
					fclose($f);
					
					unset($cont);
				} else {
					break;
				}
			}
			
			return;
		}
		*/
	};
?>