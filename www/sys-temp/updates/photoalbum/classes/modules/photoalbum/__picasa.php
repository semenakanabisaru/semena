<?php
	abstract class __picasa_photoalbum extends baseModuleAdmin	 {
		public function onInit() {
			if(strstr(getRequest('path'), "photoalbum/picasa")) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isAdmin() == false) {
					$rss = getRequest('rss');
					if($rss) {
						$cacheDirectory = mainConfiguration::getInstance()->includeParam('system.runtime-cache');
						file_put_contents($cacheDirectory . "picasa", serialize($rss));
					}
				}
			}
		}

		
		public function picasa() {
			global $_FILES;
			
			$rss = getRequest('rss');
			$_SESSION['picasa_rss'] = getRequest('rss');
			if(!$rss) {
				$cacheDirectory = mainConfiguration::getInstance()->includeParam('system.runtime-cache');
				if(is_file($cacheDirectory . "picasa")) {
					$rss = unserialize(file_get_contents($cacheDirectory . "picasa"));
				}
			}
			
			$mode = (string) getRequest("param0");

			if($mode == "files") {
				$targetFolder = (string) getRequest('folder-name');
				$targetFolder = "./images/cms/data/" . $targetFolder;
				
				$folderExists = umiDirectory::requireFolder($targetFolder, "./images/cms/data/");
				$this->setDataType("list");
				$data = Array(
					'target-folder' => $targetFolder
				);
				$this->setActionType("view");
				$this->setData($data);
				return $this->doData();
			}

			if($mode == "do") {
				header("Content-type: text/html");

				$targetFolder = "picasa";
				switch($action = getRequest('action-mode')) {
					case "new": {
						$title = (string) getRequest('new-title');
						$body = (string) getRequest('new-body');
						$elementId = (int) $this->addNewPicasaPhotoalbum($title, $body);
						break;
					}
					
					case "add": {
						$elementId = (int) getRequest('photoalbum-id');
						break;
					}
					
					case "put": {
						$elementId = false;
						$targetFolder = (string) getRequest('folder-name');
						break;
					}
					
					default: {
						throw new publicAdminException("Unkown action \"{$action}\"");
					}
				}
				
				if($elementId) {
					$element = selector::get('page')->id($elementId);
					if($element) {
						$targetFolder = $element->getAltName();
					}
				}
				
				$targetFolder = "./images/cms/data/" . $targetFolder;
				
				$folderExists = umiDirectory::requireFolder($targetFolder, "./images/cms/data/");
				if(!$folderExists) {
					throw new publicAdminException("Folder \"{$targetFolder}\" doesn't exists");
				}
				
				$titles = getRequest('title');

				$i = 0;
				foreach($_FILES as $key => $info) {
					if($info['error']) continue;
					
					$name = $info['name'];
					$key = str_replace("?size=640", "", $key);
					$key = str_replace("_jpg", ".jpg", $key);
					
					$title = $titles[$i++];

					$file = umiFile::manualUpload($name, $info['tmp_name'], $info['size'], $targetFolder);
					
					if($elementId && $file instanceof umiFile) {
						if($file->getIsBroken()) {
							throw new publicAdminException("Image is broken");
						}
						$this->addPicasaPhoto($elementId, $info['name'], $title, $file);
					}
				}
				
				switch($action) {
					case "put": {
						header("Content-type: text/plain");
						$folderName = (string) getRequest('folder-name');
						echo "http://" . getServer('HTTP_HOST') . "/admin/photoalbum/picasa/files/?folder-name=" . $folderName;
						exit();
						break;
					}
					
					
					default: {
						header("Content-type: text/plain");
						
						if($elementId) {
							$force = umiHierarchy::getInstance()->forceAbsolutePath(true);
							$link = umiHierarchy::getInstance()->getPathById($elementId);
							echo $link;
							umiHierarchy::getInstance()->forceAbsolutePath($force);
						}
						exit();
					}
				}
			}
			
			$this->setDataType("list");
			$this->setActionType("create");
			
			$data = Array(
				'xml:picasa-rss' => $rss
			);
			
			$this->setData($data);
			return $this->doData();
		}
		
		public function addPicasaPhoto($parentId, $name, $title, umiFile $file) {
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			
			$object_type_id = (int) getRequest('param1');
			$title = htmlspecialchars(trim($title));
			
			$parentElement = $hierarchy->getElement($parentId);
			$tpl_id     = $parentElement->getTplId();
			$domain_id	= $parentElement->getDomainId();
			$lang_id	= $parentElement->getLangId();
			
			$hierarchy_type_id = $hierarchyTypes->getTypeByName("photoalbum", "photo")->getId();
			if(!$object_type_id) {
				$object_type_id = $objectTypes->getBaseType("photoalbum", "photo");
			}
			
			$object_type = $objectTypes->getType($object_type_id);
			if($object_type->getHierarchyTypeId() != $hierarchy_type_id) {
				$this->errorNewMessage("Object type and hierarchy type doesn't match");
				$this->errorPanic();
			}
			
			
			
			$element_id = $hierarchy->addElement($parentId, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);
			
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);
			
			$element = $hierarchy->getElement($element_id, true);
			
			$element->setIsActive(true);
			$element->setIsVisible(false);
			$element->setName($title);
			$element->setValue("h1", $title);
			$element->setValue("photo", $file);
			$element->setValue("create_time", time());
			
			$element->commit();
			$parentElement->setUpdateTime(time());
			$parentElement->commit();
		}
		
		public function addNewPicasaPhotoalbum($title, $body) {
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			
			$hierarchy_type_id = $hierarchyTypes->getTypeByName("photoalbum", "album")->getId();
			
			$element_id = $hierarchy->addElement(0, $hierarchy_type_id, $title, $title);
			
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);
			
			$element = $hierarchy->getElement($element_id, true);
			
			$element->setIsActive(true);
			$element->setIsVisible(false);
			$element->setName($title);
			$element->setValue("h1", $title);
			$element->setValue("descr", $body);
			$element->setValue("create_time", time());
			$element->commit();
			
			return $element_id;
		}
		
		public function getPicasaLink() {
			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain()->getHost();
			return 'http://' . $domain . '/classes/modules/photoalbum/picasa-button/umicms-' . sha1($domain) . '.pbz';
		}
		
		public function generatePicasaButton() {
			$config = mainConfiguration::getInstance();
			$sourceDir = SYS_MODULES_PATH . 'photoalbum/picasa-button/';
			$tempDir = $config->includeParam('system.runtime-cache') . 'picasa-button/';
			
			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain()->getHost();
			
			$rand = sha1($domain);
			$pbzFilename = 'umicms-' . $rand;
			$pbzPath = $sourceDir . $pbzFilename . '.pbz';
			
			if(is_file($pbzPath)) {
				return true;
			}
			
			if(!is_writable($sourceDir) || !is_writable($config->includeParam('system.runtime-cache'))) {
				return false;
			}
			
			$uuid = uuid();
			
			if(!is_dir($tempDir)) {
				mkdir($tempDir, 0777);
			}
			
			$pbfPath = $tempDir . '{' . $uuid . '}.pbf';
			$psdPath = $tempDir . '{' . $uuid . '}.psd';
			copy($sourceDir . 'pbf.orign', $pbfPath);
			copy($sourceDir . 'icon.psd', $psdPath);
			
			$cont = file_get_contents($pbfPath);
			$cont = str_replace('{domain}', $domain, $cont);
			$cont = str_replace('{uuid}', $uuid, $cont);
			$cont = str_replace('{filename}', $pbzFilename, $cont);
			file_put_contents($pbfPath, $cont);
			
			$files = array($pbfPath, $psdPath);
			$zip = new PclZip($pbzPath);
			$result = $zip->create($files, PCLZIP_OPT_REMOVE_PATH, $tempDir);
			
			unlink($pbfPath);
			unlink($psdPath);
			rmdir($tempDir);
			
			return $result;
		}

		public function picasa_files() {
			$targetFolder = (string) getRequest('folder-name');
			$targetFolder = "./images/cms/data/" . $targetFolder;
			$folderExists = umiDirectory::requireFolder($targetFolder, "./images/cms/data/");
			

		}
	};
?>