<?php
define("PACKAGEINSTALLER_VERSION", '$Rev: 15717 $');
class PHException extends Exception {
	// Filesystem errors
	const ERR_CANTCREATEPACKAGEFOLDER = 8101;
	const MSG_CANTCREATEPACKAGEFOLDER = "Can not create folder for package extraction";
	const ERR_CANTEXTRACTPACKAGE	  = 8102;
	const MSG_CANTEXTRACTPACKAGE	  = "Can not extract package";
    // Database errors
    const ERR_NOINNODBSUPPORT         = 8201;
    const MSG_NOINNODBSUPPORT         = "No innodb support";
    const ERR_NOINNODBTABLES          = 8202;
    const MSG_NOINNODBTABLES          = "Some tables does not run under InnoDB engine";
	// Time errors
	const ERR_TIMELIMITEXCEED = 8888;
	const MSG_TIMELIMITEXCEED = "Time limit exceed";
	public function __construct($_message, $_code) {
		parent::__construct($_message, $_code);
	}	
};
abstract class packageHandlerBase {
	protected $packageFile 	 = null;
	protected $rootFolder  	 = null;
	protected $extractFolder = null;
	protected $manifest      = null;
	protected $timeCounter 	 = null;
	const TIME_LIMIT     	 = 0.5;	
	public function __construct($fileName, $rootFolder) {
		$this->packageFile   = $fileName;
		$this->rootFolder    = $rootFolder;
		$this->extractFolder = dirname($this->packageFile) . '/' . basename($this->packageFile, ".zip");
		$this->timeCounter   = microtime(true);		
	}
	public function __destruct() {
		if($this->manifest instanceof umiSimpleXML) {
			file_put_contents($this->extractFolder . "/index.xml", $this->manifest->saveXML());		
		}
	}
	protected function loadManifest() {
		$manifestXML 	= file_get_contents($this->extractFolder . "/index.xml");
		$this->manifest = new umiSimpleXML( $manifestXML );
	}
	public function install (&$status) {
		// nothing to do		
	}
	protected function unpackPackageZIP() {
		if(!file_exists($this->extractFolder . '/index.xml')) {
			__dbg("Extracting...");
			if(!is_dir($this->extractFolder)) {
                if(!is_writable(dirname($this->extractFolder))) {
                    shell_exec("chmod -R 0755 ".dirname($this->extractFolder));
                }
				if(!mkdir($this->extractFolder)) {
					throw new PHException(PHException::MSG_CANTCREATEPACKAGEFOLDER, PHException::ERR_CANTCREATEPACKAGEFOLDER);
				}
			}
			$oldFolder = getcwd();	
			chdir($this->extractFolder);
			shell_exec("unzip -x ".$this->packageFile);
			chdir($oldFolder);
			if(!file_exists($this->extractFolder . '/index.xml')) {			
				$zip = new PclZip($this->packageFile);
				if($zip->extract(PCLZIP_OPT_PATH, $this->extractFolder) <= 0) throw new PHException(PHException::MSG_CANTEXTRACTPACKAGE, PHException::ERR_CANTEXTRACTPACKAGE);
				unset($zip);
			}
		}				
	}
	protected function checkTimeLimitExceeded($noThrow = false) {
		if((microtime(true) - $this->timeCounter) > packageHandlerBase::TIME_LIMIT) {
			if($noThrow) {
				return true;
			}
			throw new PHException(PHException::MSG_TIMELIMITEXCEED, PHException::ERR_TIMELIMITEXCEED);
		}
		return false;
	}		
};
class packageChecker extends packageHandlerBase {		
	private static $errorList = array('files' => array(), 'folders' => array());
	private $repeatCounter    = 0;		
	public function __construct($fileName, $rootFolder){
		parent::__construct($fileName, $rootFolder);		
	}
	public function check(&$status) {
        if(!isset($status['database_check']) || $status['database_check'] != $status['check_repeat']) {
           $this->checkDatabase($status);
           $status['database_check'] = $status['check_repeat'];
		}
		$status['errors']    = &packageChecker::$errorList;
		$this->repeatCounter = isset($status['check_repeat']) ? $status['check_repeat'] : 0;		
		$status['result']    = $this->checkResult($status);
		$this->unpackPackageZIP();
		if($this->checkTimeLimitExceeded(true)) return false;		
		$this->loadManifest();
		$components = $this->manifest->xpath("//component[not(@check-status='{$this->repeatCounter}')]");
		if($components) {
			if(!is_array($components)) $components = array($components);
			foreach($components as $component) {
				try {
					$this->checkComponent($component, $status);
				} catch(Exception $e) {
					if($e->getCode() == PHException::ERR_TIMELIMITEXCEED) {						
						$status['result'] = $this->checkResult($status);
						return false;
					} else {
						throw $e;
					}
				}
				$component->attribute("check-status", (string)$this->repeatCounter);
			}
		}		
		$status['result'] = $this->checkResult($status);
		return true;
	}
	private function checkComponent(umiSimpleXML $component, &$status) {		
		$status['component'] = $component->attribute("name");		
		$packages		 = $component->package;
		$filesystemImage = $component->files;		
		if($packages) {			
			if(!is_array($packages)) $packages = array($packages);
			foreach($packages as $package) {
				$packageFile 		   = ltrim($package->attribute("src"), "~");				
				if(is_file($this->extractFolder . $packageFile)) {					
					$innerPackageChecker = new packageChecker($this->extractFolder . $packageFile, $this->rootFolder);
					if(!$innerPackageChecker->check($status)) {
						throw new PHException(PHException::MSG_TIMELIMITEXCEED, PHException::ERR_TIMELIMITEXCEED);
					}
				}
			}
		}		
		if($filesystemImage) {			
			$this->checkFolders($filesystemImage->xpath("folder[not(@check-status='{$this->repeatCounter}')]"));			
			$this->checkFiles($filesystemImage->xpath("file[not(@check-status='{$this->repeatCounter}')]"));
		}		
	}
	private function checkFolders($folderNames) {
		if($folderNames instanceof umiSimpleXML) { $folderNames = array($folderNames); }
		if(is_array($folderNames) && !empty($folderNames)) {
			foreach($folderNames as $folderName) {
				$path = $this->rootFolder . "/" . $folderName->value();
				if(is_dir($path)) {
					if(!is_writeable($path)) {
						packageChecker::$errorList['folders'][] = $folderName->value();
					}
				} else if(is_dir(dirname($path)) && !is_writeable(dirname($path))) {
					packageChecker::$errorList['folders'][] = dirname($folderName->value());					
				}
				$folderName->attribute("check-status", (string)$this->repeatCounter);
				$this->checkTimeLimitExceeded();
			}
		}
	}
	private function checkFiles($fileNames) {
		if($fileNames instanceof umiSimpleXML) { $fileNames = array($fileNames); }
		if(is_array($fileNames) && !empty($fileNames)) {			
			foreach($fileNames as $fileName) {
				$path = $this->rootFolder."/".$fileName->value();
				if(is_file($path) && !is_writeable($path)) {
					packageChecker::$errorList['files'][] = $fileName->value();										
				}
				$fileName->attribute("check-status", (string)$this->repeatCounter);
				$this->checkTimeLimitExceeded();
			}
		}
	}

	private function checkDatabase(&$status) {
	        if(!defined("DB_DRIVER") || DB_DRIVER == "mysql") {
	            $result = l_mysql_query("SHOW VARIABLES LIKE 'have_innodb'");
	            list(,$have_innodb) = mysql_fetch_row($result);
	            if(strtoupper($have_innodb) != "YES") {
	                $status['result'] = false;
	                throw new PHException(PHException::MSG_NOINNODBSUPPORT, PHException::ERR_NOINNODBSUPPORT);
	            }
		}
	}

	private function checkResult($status) {
		return ($status['result'] && empty(packageChecker::$errorList['files']) && empty(packageChecker::$errorList['folders']));
	}
};
class packageInstaller extends packageHandlerBase {		
	public function __construct($fileName, $rootFolder){
		parent::__construct($fileName, $rootFolder);
	}
	public function install(&$status) {
		__dbg("Installing package "  .$this->packageFile);
		$this->unpackPackageZIP();
		if($this->checkTimeLimitExceeded(true)) return false;				
		$this->loadManifest();
		$components = $this->manifest->xpath("//component[not(@update-status)]");
		if($components) {
			if(!is_array($components)) $components = array($components);
			foreach($components as $component) {
				try {
					$this->installComponent($component, $status);
				} catch(Exception $e) {
					if($e->getCode() == PHException::ERR_TIMELIMITEXCEED) {						
						return false;
					} else {
						throw $e;
					}
				}
				$component->attribute("update-status", "done");
			}
		}		
		__dbg("Done package "  .$this->packageFile);
		return true;
	}
	private function installComponent(umiSimpleXML $component, &$status) {
		__dbg("- Installing component " . $component->attribute("name"));
		$status['component'] = $component->attribute("name");
		$dependencies	 = $component->require;
		$packages		 = $component->package;
		$filesystemImage = $component->files;
		$databaseImage   = $component->xpath("database-image");
		__dbg("---- Packages");
		if($packages) {						
			if(!is_array($packages)) $packages = array($packages);
			foreach($packages as $package) {
				$packageFile 		   = ltrim($package->attribute("src"), "~");
				if(is_file($this->extractFolder . $packageFile)) {					
					$innerPackageInstaller = new packageInstaller($this->extractFolder . $packageFile, $this->rootFolder);
					if(!$innerPackageInstaller->install($status)) {
						throw new PHException(PHException::MSG_TIMELIMITEXCEED, PHException::ERR_TIMELIMITEXCEED);
					}
				}
			}
		}
		$a = (float)($component->count("/component/files/*[@update-status]") + $component->count("/component/database-image/*/*[@update-status]"));
		$b = (float)($component->count("/component/files/*") + $component->count("/component/database-image/*/*"));
		$status['percent'] = ($b != 0) ? ($a / $b * 100.0) : 0.0;
		$status['percent'] = round($status['percent']);
		if($databaseImage) {
			if(!defined("DB_DRIVER")) {
				define("DB_DRIVER", "mysql");
			}
			if(DB_DRIVER == 'mysql') {
				@mysql_query("SET SESSION sql_mode = 'ANSI'");				
			}
			if($rules = $databaseImage->xpath("rule[@driver='".DB_DRIVER."' or @driver='*']")) {
				$status['part'] = 'custom';
                $customActions  = array();
				if(is_array($rules)) {					
					foreach($rules as $r) {
						$tmp = $rules->xpath("*[not(@update-status)]");
						if($tmp)
							$customActions = array_merge($customActions, is_array($tmp) ? $tmp : array($tmp));
					}
				} else {
					$tmp = $rules->xpath("*[not(@update-status)]");
					if($tmp)
						$customActions = is_array($tmp) ? $tmp : array($tmp);
				}
				$this->performCustomActions($customActions);
			}
		}
		if($filesystemImage) {
			__dbg("---- Folders");
			$status['part'] = 'folders';
			$this->installFolders($filesystemImage->xpath("folder[not(@update-status)]"));
			__dbg("---- Files");
			$status['part'] = 'files';
			$this->installFiles($filesystemImage->xpath("file[not(@update-status)]"));
		}
		if($databaseImage) {
			if($restrictions = $databaseImage->xpath("restrictions")) {
				__dbg("---- Update restrictions");
				$status['part'] = 'restrictions';
				$this->installRestrictionsUpdate($restrictions->xpath("restriction[not(@update-status)]"));
			}
			
			if($hierarchyTypes = $databaseImage->xpath("hierarchy-types")) {
				__dbg("---- Update hierarchy types");
				$status['part'] = 'hierarchy types';
				$this->installHierarchyTypesUpdate($hierarchyTypes->xpath("hierarchy-type[not(@update-status)]"));
			}
			if($objectTypes = $databaseImage->xpath("object-types")) {
				__dbg("---- Update object types");
				$status['part'] = 'object types';
				$this->installObjectTypesUpdate($objectTypes->xpath("object-type[not(@update-status)]"));
			}
			if($objects = $databaseImage->xpath("objects")) {
				__dbg("---- Update objects");
				$status['part'] = 'objects';
				$this->installObjectsUpdate($objects->xpath("object[not(@update-status)]"));
			}
			if($pages = $databaseImage->xpath("pages")) {
				__dbg("---- Update pages");
				$status['part'] = 'pages';
				$this->installPagesUpdate($pages->xpath("page[not(@update-status)]"));				
			}
			if($registry = $databaseImage->regedit) {
				__dbg("---- Update registry");
				$status['part'] = 'registry';
				$this->installRegistryUpdate($registry->xpath("key[not(@update-status)]"));
			}
		}
	}	
	private function installFolders($folderNames) {
		if($folderNames instanceof umiSimpleXML) { $folderNames = array($folderNames); }
		if(is_array($folderNames) && !empty($folderNames)) {
			foreach($folderNames as $folderName) {
				$path = $this->rootFolder . "/" . $folderName->value();
				if(!is_dir($path)) {
					if(!mkdir($path)) {
						__dbg("-------------- Folder creation error: ".$folderName->value());
					}
				}				
				$folderName->attribute("update-status", "done");
				$this->checkTimeLimitExceeded();
			}
		}
	}
	private function installFiles($fileNames) {
		if($fileNames instanceof umiSimpleXML) { $fileNames = array($fileNames); }
		if(is_array($fileNames) && !empty($fileNames)) {
			$sourceFolder = $this->extractFolder . "/files";
			foreach($fileNames as $fileName) {
				if(file_exists($sourceFolder."/".$fileName->value()) && !copy($sourceFolder."/".$fileName->value(), $this->rootFolder."/".$fileName->value())) {
					__dbg("-------------- File error: ".$fileName->value());
				}
				$fileName->attribute("update-status", "done");
				$this->checkTimeLimitExceeded();
			}
		}
	}
	private function performCustomActions($customActions) {
		if(!defined("CMS_DOCUMENT_ROOT"))
			define("CMS_DOCUMENT_ROOT", $this->rootFolder."/");		
		foreach($customActions as $action) {
			try {
				switch($action->name()) {
					case 'sql': mysql_query($action->value()); break;
					case 'php': 
								if($sourceFile = $action->attribute("src")) {
									include dirname($this->packageFile) . "/" . basename($this->packageFile, ".zip") . "/" . $sourceFile;								
								} else {
									@eval($action->value());
								}
								break;
				}
			} catch(Exception $e) {
				if($e->getCode() == 10001) {
					$action->attribute("update-status", "done");					
				}
				throw $e;
			}
			$action->attribute("update-status", "done");
			$this->checkTimeLimitExceeded();			
		}				
	}
	private function installRegistryUpdate($updateKeys) {
		if($updateKeys instanceof umiSimpleXML) { $updateKeys = array($updateKeys); }
		if(is_array($updateKeys) && !empty($updateKeys)) {			
			$regedit = regedit::getInstance();
			foreach($updateKeys as $keyInfo) {
				if($keyInfo->attribute("status") == "deleted") {
					$regedit->delVar($keyInfo->attribute("id"));
				} else {
					$regedit->setVar($keyInfo->attribute("id"), $keyInfo->value());
				}
				$keyInfo->attribute("update-status", "done");
				$this->checkTimeLimitExceeded();
			}						
		}
	}
	
	
	private function installRestrictionsUpdate($updateRestrictions) {
		if($updateRestrictions instanceof umiSimpleXML) { $updateRestrictions = array($updateRestrictions); }
		if(is_array($updateRestrictions) && !empty($updateRestrictions)) {
			foreach($updateRestrictions as $restrictionInfo) {
				$fieldTypeId = $restrictionInfo->attribute('field-type-id');
				$title = $restrictionInfo->attribute('title');
				$classPrefix = $restrictionInfo->attribute('class-prefix');
				
				if(!baseRestriction::find($classPrefix, $fieldTypeId)) {
					baseRestriction::add($classPrefix, $title, $fieldTypeId);
				}
				
				$restrictionInfo->attribute("update-status", "done");
				$this->checkTimeLimitExceeded();
			}
		}
	}
	
	private function installHierarchyTypesUpdate($updateTypes) {
		if($updateTypes instanceof umiSimpleXML) { $updateTypes = array($updateTypes); }		
		if(is_array($updateTypes) && !empty($updateTypes)) {			
			$hTypesCollection = umiHierarchyTypesCollection::getInstance();
			foreach($updateTypes as $typeInfo) {
				switch($typeInfo->attribute("status")) {
					case "new" 		:   $hTypesCollection->addType($typeInfo->attribute("module"), 
																   $typeInfo->attribute("title"), 
																   $typeInfo->attribute("method"));
										break;
					case "changed" 	:   $type = $hTypesCollection->getTypeByName($typeInfo->attribute("module"), $typeInfo->attribute("method"));
										$type->setTitle($typeInfo->attribute("title"));
										$type->commit();
										break;
					case "deleted"	:	$type = $hTypesCollection->getTypeByName($typeInfo->attribute("module"), $typeInfo->attribute("method"));
										$hTypesCollection->delType($type->getId());										
										break;
				}
				$typeInfo->attribute("update-status", "done");
				$this->checkTimeLimitExceeded();
			}			
		}				
	}
	private function installObjectTypesUpdate($updateTypes) {
		if($updateTypes instanceof umiSimpleXML) { $updateTypes = array($updateTypes); }
		if(is_array($updateTypes) && !empty($updateTypes)) {			
			foreach($updateTypes as $typeInfo) {
				$this->installSingleObjectTypeUpdate($typeInfo);
				$this->checkTimeLimitExceeded();
			}
		}
	}
	private function installSingleObjectTypeUpdate(umiSimpleXML $typeInfo) {		
		$id = $this->findObjectTypeId($typeInfo);
		
		if($typeInfo->attribute("update-status") =="done") return $id;
		
		$typesCollection = umiObjectTypesCollection::getInstance();			
		$hierarchyTypes  = umiHierarchyTypesCollection::getInstance();
				
		if(!$id) {
			$parentId = $this->findObjectTypeParentId($typeInfo);
			$id = $typesCollection->addType($parentId, $typeInfo->attribute("title"));		
		} else {
			$parentId = 0;
		}
		$typeInfo->attribute("id",     $id);
		$typeInfo->attribute("parent", $parentId);
					
		$type = $typesCollection->getType($id);
		if(!$type) return false;
		$type->setIsLocked(false);
		if($typeInfo->attribute("status") == "deleted") {					
			$typesCollection->delType($id);					
		} else {
			$this->updateTypeGroups($type, $typeInfo->group);
			if($hierarchyTypeInfo = $typeInfo->xpath("hierarchy-type")) {
				$htype = $hierarchyTypes->getTypeByName($hierarchyTypeInfo->attribute("module"), $hierarchyTypeInfo->attribute("method"));					
				$type->setHierarchyTypeId( $htype ? $htype->getId() : 0 );
			}
			$type->setIsGuidable($typeInfo->attribute("guidable"));
			$type->setIsPublic($typeInfo->attribute("public"));
			$type->setIsLocked($typeInfo->attribute("locked"));
			
			$guid = $typeInfo->attribute("guid");
			if(strlen($guid)) {
				$type->setGUID($guid);
			}
			
			$type->commit();
		}				
		$typeInfo->attribute("update-status", "done");
		return $id;		
	}
	private function installObjectsUpdate($updateObjects) {
		if($updateObjects instanceof umiSimpleXML) { $updateObjects = array($updateObjects); }
		if(is_array($updateObjects) && !empty($updateObjects)) {			
			$collection = umiObjectsCollection::getInstance();
			foreach($updateObjects as $objectInfo) {				
				$this->installSingleObjectUpdate($objectInfo);
				$this->checkTimeLimitExceeded();			
			}			
		}		
	}
	private function installSingleObjectUpdate(umiSimpleXML $objectInfo) {		
		if($objectInfo->attribute('status') == 'deleted') {
			return false;
		}	
		$collection = umiObjectsCollection::getInstance();
		$objectTypes = umiObjectTypesCollection::getInstance();
		$objectTypeIdx   = $objectInfo->attribute("type-index");
		$objectTypeInfo  = $objectInfo->xpath("/component/database-image/object-types/object-type[@index='{$objectTypeIdx}']");
		if(!$objectTypeInfo) { return false; }
		$typeId   = $objectTypeInfo->attribute("id");
		$name = $objectInfo->attribute('name');
		$guid = $objectInfo->attribute('guid');
		$objectType = $objectTypes->getType($typeId);
		
		$sel = new umiSelection;
		$sel->addObjectType($typeId);
		$sel->addNameFilterEquals($name);
		$result = umiSelectionsParser::runSelection($sel);
		if(sizeof($result)) {
			$objectId = $result[0];
		} else {
			$objectId = $collection->addObject($name, $typeId);
		}
		
		$object = $collection->getObject($objectId);
		if(!$object) return false;
		
		if(strlen($guid)) {
			$object->setGUID($guid);
		}
		
		$props = $objectInfo->xpath('property', true);
		if($props) $this->updateProps($props, $objectType, $object);
		$object->commit();		
		
		$objectInfo->attribute("update-status", "done");
		return $objectId;
	}
	private function installPagesUpdate($updatePages) {
		if($updatePages instanceof umiSimpleXML) { $updatePages = array($updatePages); }
		if(is_array($updatePages) && !empty($updatePages)) {
			$domainId = ($tmp = domainsCollection::getInstance()->getDefaultDomain()) ? $tmp->getId() : 0;
			$langId   = ($tmp = langsCollection::getInstance()->getDefaultLang()) ? $tmp->getId() : 0;			
			$hierarchy 		= umiHierarchy::getInstance();			
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();			
			$templates = templatesCollection::getInstance();
			foreach($updatePages as $pageInfo) {
				if($pageInfo->attribute('status') != 'new') {
					continue;					
				}
				$baseTypeInfo    = $pageInfo->basetype;
				$hierarchyType = $hierarchyTypes->getTypeByName($baseTypeInfo->attribute('module'), $baseTypeInfo->attribute('method'));
				$objectTypeIdx   = $pageInfo->attribute("type-index");
				$objectTypeInfo  = $pageInfo->xpath("/component/database-image/object-types/object-type[@index='{$objectTypeIdx}']");
				if(!$objectTypeInfo) { continue; }
				$typeId   = $objectTypeInfo->attribute("id");
				$link     = $pageInfo->link->value();
				$altName  = substr($link, strrpos($link, '/', -2) + 1, -1);
				$path     = substr($link, 0, strrpos($link, '/', -2) + 1);
				$parentId = $hierarchy->getIdByPath($path);
				$templateInfo = $pageInfo->template;
				if($templateInfo->attribute('default')) {
					$templateId = false;
				} else {
					$templateId   = false; 
					$templateList = $templates->getTemplatesList($domainId, $langId);
					foreach($templateList as $template) {
						if($template->getFilename() == $templateInfo->value()) {
							$templateId = $template->getId();
							break;
						}
					}
					if($templateId == false) {
						$templateId = $templates->addTemplate($templateInfo->value(), $templateInfo->attribute('title'), $domainId, $langId);
					}
				}								

				$id = $hierarchy->addElement((int) $parentId, $hierarchyType->getId(), $pageInfo->attribute('name'), $altName, $typeId, false, false, $templateId);
				$element = $hierarchy->getElement($id, true);
				$element->setIsActive($pageInfo->attribute('active') == 'active');
				$element->setIsDefault($pageInfo->attribute('default') == 'default');
				$element->setIsVisible($pageInfo->attribute('visible') == 'visible');
				
				$objectIndex = $pageInfo->attribute("object-index");
				if($objectInfo = $pageInfo->xpath("/component/database-image/objects/object[@index='{$objectIndex}']")) {
					$objectId = $objectInfo->attribute("id");
					$oldObjectId = $element->getObjectId();
					$object = $objects->getObject($objectId);
					if($object) {
						$element->setObject($object);
						$element->commit();
						$objects->delObject($oldObjectId);						
						$pageInfo->attribute("update-status", "done");
						$this->checkTimeLimitExceeded();
						continue;
					}
				}
				
				$objectTypeId = $element->getObjectTypeId();
				$objectType = $objectTypes->getType($objectTypeId);
				
				$props = $pageInfo->xpath('property', true);
				if($props) $this->updateProps($props, $objectType, $element);
				
				$element->commit();
				$pageInfo->attribute("update-status", "done");
				$pageInfo->attribute("id", $objectId);
				$this->checkTimeLimitExceeded();
			}
		}		
	}
	private function updateProps ($props, $objectType, $ent) {
		$hierarchy 		= umiHierarchy::getInstance();			
		$hierarchyTypes = umiHierarchyTypesCollection::getInstance();			
		$templates = templatesCollection::getInstance();
		$objectTypes = umiObjectTypesCollection::getInstance();
		$fields = umiFieldsCollection::getInstance();
		$objects = umiObjectsCollection::getInstance();
			
		foreach($props as $propertyInfo) {
			if($propertyInfo->attribute("status") == "deleted") {
				$ent->setValue($propertyInfo->attribute("name"), null);
				continue;
			}
			if ($propertyInfo->object) {
				$fieldId = $objectType->getFieldId($propertyInfo->attribute('name'));
				$field = $fields->getField($fieldId);
								
				if($field->getFieldType()->getDataType() == "relation") {					
					if($guideId = $field->getGuideId()) {						
						$value = array();
						foreach($propertyInfo->xpath('object', true) as $item) {							
							$name = $item->attribute('name');
							if(!$name) $name = $item->value();
							if($name == 'lyxsus') continue;					

							$sel = new umiSelection;
							$sel->addObjectType($guideId);
							$sel->addNameFilterEquals($name);
							$result = umiSelectionsParser::runSelection($sel);
							
							if(sizeof($result)) {
								list($itemId) = $result;
							} else {
								$objectTypeInfo = $propertyInfo->xpath("/component/database-image/object-types/object-type[@id='{$guideId}']");
								if(!$objectTypeInfo) { continue; }
								$typeIdx = $objectTypeInfo->attribute("index");
								list($objectInfo) = $propertyInfo->xpath("./../../object[@name='{$name}' and @type-index='{$typeIdx}']", true);
								if($objectInfo) {
									$itemId = $this->installSingleObjectUpdate($objectInfo);
								} else {
									$itemId = $objects->addObject($name, $guideId);
								}
							}
							$value[] = $itemId;
						}						
						$ent->setValue($propertyInfo->attribute('name'), $value);
					}
				}
				
				$ent->commit();
			} else {
				if($propertyInfo->attribute('temp') == "symlink") continue;
				if($propertyInfo->attribute('temp') == "done") continue;
				if($propertyInfo->attribute('temp') == 'optioned') {
					$value = array();
					foreach($propertyInfo->xpath('.//option', true) as $optionInfo) {
						$value[] = array(
						    'float' => (string) $optionInfo->float,
						    'rel' => (string) $optionInfo->rel,
						    'int' => (string) $optionInfo->int,
						    'varchar' => (string) $optionInfo->varchar,
						    'symlink' => (string) $optionInfo->symlink
						);
					}
					$ent->setValue($propertyInfo->attribute('name'), $value);
				} else {
					$typeIdx = $propertyInfo->attribute('type-index');
					if($typeIdx) {
						$objectTypeInfo = $propertyInfo->xpath("/component/database-image/object-types/object-type[@index='{$typeIdx}']");
						$ent->setValue($propertyInfo->attribute('name'), $objectTypeInfo->attribute('id'));						
					} else {
						$ent->setValue($propertyInfo->attribute('name'), $propertyInfo->value());
					}
				}				
			}
		}
	}
	private function updateTypeGroups(umiObjectType $type, $groups) {
		if($groups instanceof umiSimpleXML) { $groups = array($groups); }
		if(is_array($groups) && !empty($groups)) {			
			foreach($groups as $groupInfo) {
				$group = $type->getFieldsGroupByName($groupInfo->attribute("name"));
				if(!$group && $groupInfo->attribute("status") == "new") {
					$groupId = $type->addFieldsGroup($groupInfo->attribute("name"), $groupInfo->attribute("title"));					
					$group   = $type->getFieldsGroup($groupId);
				}
				if($group instanceof umiFieldsGroup) {
					if($groupInfo->attribute("status") == "deleted") {
						$type->delFieldsGroup($group->getId());
						continue;
					}
					$group->setTitle($groupInfo->attribute("title"));
					$group->setIsLocked((bool)$groupInfo->attribute("locked"));
					$group->setIsVisible((bool)$groupInfo->attribute("visible"));
					$this->updateGroupFields($type, $group, $groupInfo->field);
					$group->commit();
				}
			}
		}
	}
	private function updateGroupFields(umiObjectType $type, umiFieldsGroup $group, $fields) {
		if($fields instanceof umiSimpleXML) { $fields = array($fields); }
		if(is_array($fields) && !empty($fields)) {			
			$fieldsCollection = umiFieldsCollection::getInstance();
			foreach($fields as $fieldInfo) {
				$fieldId = $type->getFieldId($fieldInfo->attribute("name"));
				$multiple    = ($fieldInfo->attribute("multiple") == "multiple");
				if(!$fieldId && ($fieldInfo->attribute("status") == "new")) {
					$name        = $fieldInfo->attribute("name");
					$title       = $fieldInfo->attribute("title");
					
					$fieldTypeId = $this->getFieldTypeIdByDataType($fieldInfo->attribute("type"), $multiple);
					$fieldId 	 = $fieldsCollection->addField($name, $title, $fieldTypeId);
					$group->attachField($fieldId);
				} else {
					
				}				
				$field 	 = $fieldsCollection->getField($fieldId);
				if(!$field) continue;
				$field->setIsLocked(false);
				if($fieldInfo->attribute("status") == "deleted") {
					$fieldsCollection->delField($fieldId);
					continue;
				}
				$field->setTitle($fieldInfo->attribute("title"));
				$field->setFieldTypeId($this->getFieldTypeIdByDataType($fieldInfo->attribute("type"), $multiple));
				$field->setIsVisible($fieldInfo->attribute("visible"));				
				$field->setIsInheritable($fieldInfo->attribute("inheritable"));
				$guideIdx = $fieldInfo->attribute("guide-index");
				if($guideIdx) {
					$guideInfo = $fieldInfo->xpath("//object-type[@index='{$guideIdx}']");
					$guideId   = $this->findObjectTypeId( $guideInfo );
					if(!$guideId && $guideInfo) {
						$guideId = $this->installSingleObjectTypeUpdate($guideInfo);
					}
					$field->setGuideId( $guideId );
				}
				$field->setIsInSearch($fieldInfo->attribute("in-search"));
				$field->setIsInFilter($fieldInfo->attribute("in-filter"));
				$field->setTip($fieldInfo->attribute("tip"));
				$field->setIsRequired($fieldInfo->attribute("required"));
				$field->setIsLocked($fieldInfo->attribute("locked"));
				$field->setIsSortable($fieldInfo->attribute("sortable"));
				$field->setRestrictionId($fieldInfo->attribute("setRestrictionId"));
				$field->setIsSystem($fieldInfo->attribute("system"));
				
				$field->commit();				
			}
		}
	}
	private function findObjectTypeId($typeInfo, $parentId = 0) {
		if(!$typeInfo) return 0;
		$id	   = 0;
		$title = $typeInfo->attribute("title");		
		$hierarchyTypeId = null;
		if($hierarchyTypeInfo = $typeInfo->xpath("hierarchy-type")) {
			$hType = umiHierarchyTypesCollection::getInstance()->getTypeByName( $hierarchyTypeInfo->attribute("module"), $hierarchyTypeInfo->attribute("method"));
			if($hType) $hierarchyTypeId = $hType->getId();
		}
		$parentId =	$this->findObjectTypeParentId($typeInfo, $parentId);
		$typesCollection = umiObjectTypesCollection::getInstance();
		$typeIDs  = $typesCollection->getSubTypesList($parentId);
		foreach($typeIDs as $typeId) {
			$type = $typesCollection->getType($typeId);
			if(strcmp($type->getName(), $title) == 0 && 
				($hierarchyTypeId == null || $type->getHierarchyTypeId() == $hierarchyTypeId) ) {
				$id = $typeId;
				$typeInfo->attribute("id", $id);
				$typeInfo->attribute("parent", $parentId);
				break;
			}
		}
		return $id;
	}
	private function findObjectTypeParentId($typeInfo, $parentId = 0) {
		if($attribute = $typeInfo->attribute("parent")) return $attribute;
		if($typeInfo->legend && $parents = $typeInfo->legend->xpath("object-type")) {
			if(!is_array($parents)) $parents = array($parents);
			foreach($parents as $parentType) {
				$parentId = $this->findObjectTypeId($parentType, $parentId);
			}
		}
		return $parentId;		
	}
	private function getFieldTypeIdByDataType($_dataType, $_multiple) {		
		static $fieldTypes = null;
		if(!$fieldTypes) {			
			$fieldTypes = umiFieldTypesCollection::getInstance()->getFieldTypesList();
		}
		foreach($fieldTypes as $type) {
			if(strcmp(strtolower($type->getDataType()), strtolower($_dataType)) == 0 && $type->getIsMultiple() == $_multiple) {
				return $type->getId();				
			}
		}
		return 0;
	}
	private function removeFolder($folderName) {
		$removeList = glob($folderName . "/*");
		foreach($removeList as $name) {			
			if(is_dir($name)) {
				if(!$this->removeFolder($name)) return false;
				rmdir($name);
			} else {
				unlink($name);
			}
			if($this->checkTimeLimitExceeded(true)) return false;
		}
		rmdir($folderName);
		return true;
	}	
};
?>