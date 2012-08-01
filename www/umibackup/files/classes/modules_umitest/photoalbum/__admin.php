<?php
	abstract class __photoalbum extends baseModuleAdmin {
		public function albums_list() {
			//Deprecated method
			regedit::getInstance()->setVar("//modules/photoalbum/default_method_admin", "lists");
			$this->redirect($this->pre_lang . "/admin/photoalbum/lists/");
		}

		public function config() {
			$regedit = regedit::getInstance();

			$params = Array(
				"config" => Array(
					"int:per_page" => NULL
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/photoalbum/per_page", $params['config']['int:per_page']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regedit->getVal("//modules/photoalbum/per_page");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
			$this->generatePicasaButton();

			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('photoalbum', 'album');
			$sel->types('hierarchy-type')->name('photoalbum', 'photo');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}

		public function upload_arhive () {
			global $_FILES;

			$allowedTypes = array ("jpg", "jpeg", "gif", "bmp", "png");

			$referer = getRequest('referer');
			if(!$referer) $referer = $_SERVER['HTTP_REFERER'];

			$parentId  = getRequest("parent_id");
			$hierarchy = umiHierarchy::getInstance();
			$element   = $hierarchy->getElement($parentId);
			if(!$element) throw new publicAdminException("Can't find parent album");
			$altName   = $element->getAltName();
			if (strlen($altName)) {
				$folder = "./images/cms/data/{$altName}/";
			} else {
				$folder = "./images/cms/data/";
			}

			$addWaterMark = (bool) getRequest("watermark");

			if((isset($_FILES['zip_arhive']) && is_uploaded_file($_FILES['zip_arhive']['tmp_name']))) {
				$originalName = $_FILES['zip_arhive']['name'];
				$extension = substr($originalName, strrpos($originalName, ".") + 1);

				if($extension != "zip" && $file == "") {
					throw new publicAdminException("It's not arhive!");
				} else {
					$unzipedArray = umiFile::upload_zip($_FILES['zip_arhive'], "", $folder, $addWaterMark);
				}
				if (is_array($unzipedArray)) {
					foreach ($unzipedArray as $item) {
						$file = $folder . basename($item['filename']);
						$info = getPathInfo($file);
						if(in_array(strtolower($info['extension']), $allowedTypes)) {
							$this->addPhotoFromZip($file);
						}
					}
					$this->redirect($referer);
					return;
				}
				else throw new publicAdminException('Zip extracting error! '.$unzipedArray);
			}
			$file = getRequest('zip_arhive_src');
			if(!strlen($file)) {
				throw new publicAdminException(getLabel('zip-file-upload-error'));
			} else {
				$unzipedArray = umiFile::upload_zip ("", $file, $folder, $addWaterMark);
				if (is_array($unzipedArray)) {
					foreach($unzipedArray as $item)	{
						$file = $folder . basename($item['filename']);
						$info = getPathInfo($file);
						if (in_array(strtolower($info['extension']), $allowedTypes)) {
							$this->addPhotoFromZip($file);
						}
					}
					$this->redirect($referer);
				} else {
					throw new publicAdminException($unzipedArray);
				}
			}

		}

		public function addPhotoFromZip($filename) {
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$parent_id = (int) getRequest("parent_id");
			$basename = basename ($filename);
			$title = substr($basename, 0, strrpos($basename, '.'));
			$parentElement = $hierarchy->getElement($parent_id);
			if ($parentElement) {
				$tpl_id		= $parentElement->getTplId();
				$domain_id	= $parentElement->getDomainId();
				$lang_id	= $parentElement->getLangId();

				$hierarchy_type_id = $hierarchyTypes->getTypeByName("photoalbum", "photo")->getId();
				$object_type_id = $objectTypes->getBaseType("photoalbum", "photo");

				$object_type = $objectTypes->getType($object_type_id);
				if($object_type->getHierarchyTypeId() != $hierarchy_type_id) {
					$this->errorNewMessage("Object type and hierarchy type doesn't match");
					$this->errorPanic();
				}

				$file = new umiFile($filename);
				if($file->getIsBroken()) return false;

				$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);

				permissionsCollection::getInstance()->setDefaultPermissions($element_id);

				$element = $hierarchy->getElement($element_id, true);

				$element->setIsActive(true);
				$element->setIsVisible(false);
				$element->setName($title);
				$element->setValue("photo", $file);
				$element->setValue("create_time", time());

				$element->commit();
				$parentElement->setUpdateTime(time());
				$parentElement->commit();
			}
			return true;
		}

		public function add() {
			$parent = $this->expectElement("param0");
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");

			$this->setHeaderLabel("header-photoalbum-add-" . $type);

			$inputData = Array(	"type" => $type,
						"parent" => $parent,
						'type-id' => getRequest('type-id'),
						"allowed-element-types" => Array('album', 'photo'));

			$this->_checkFolder($parent );
			if($mode == "do") {

				if ($type == "album") {
					umask (0000);
				}

				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}




		public function edit() {
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();

			$elementId = (int) getRequest('param0');
			$element = $this->expectElement("param0");
			$mode = (String) getRequest('param1');

			if($this->getObjectTypeMethod($element->getObject()) == 'photo') {
				$parent = $this->expectElement( $element->getRel() , false, 1);
			}
			else {
				$parent = $element;
			}

			$this->setHeaderLabel("header-photoalbum-edit-" . $this->getObjectTypeMethod($element->getObject()));

			$inputData = Array(
						"element" => $element,
						"allowed-element-types" => Array('album', 'photo')
			);


			$this->_checkFolder($parent );

			if($mode == "do") {

				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}


		public function _checkFolder($parent ) {
			if(!$parent) return;
			$folder = "./images/cms/data";

			if (getRequest("param0") == 0) {
				@mkdir ($folder."/".translit::convert(getRequest('alt-name')), 0777);
			} else {

				$hierarchy = umiHierarchy::getInstance();

				$curEl = $parent;
				$altDirs = array();
				while(true) {
					$altDir = $curEl->getAltName ();

					$type = umiHierarchyTypesCollection::getInstance()->getType( $curEl->getTypeId() );

					if($type->getExt() == 'photoalbum' &&  $type -> getName() == 'photo') {
						continue;
					}

					if($altDir) {
						array_unshift($altDirs, $altDir);
					}

					$curEl  = $hierarchy->getElement ($curEl->getRel());

					if(empty($curEl)) {
						break;
					}
				}

				foreach($altDirs as $alt) {
					$folder .= '/'. $alt ;
					if (!file_exists($folder)) {
						@mkdir ($folder, 0777);
					}
				}

				//@mkdir ($folder.'/'.translit::convert(getRequest('alt-name')), 0777);
			}
		}

		public function del() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('album', 'photo')
				);

				$this->deleteElement($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}


		public function activity() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}
			$is_active = getRequest('active');

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('album', 'photo'),
					"activity" => $is_active
				);

				$this->switchActivity($params);
				$element->commit();
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}


		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'news', '#__name'=>'lists'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'photoalbum', '#__name'=>'del', 'aliases' => 'tree_delete_element,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'photoalbum', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
						),
					'types' => array(
						array('common' => 'true', 'id' => 'photo')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'descr', 'rate_voters', 'rate_sum'),
					'default' => 'photo[80px]'
				);
		}

		public function uploadImages () {
			$parentId = getRequest("param0");
			$parentElement = umiHierarchy::getInstance()->getElement ($parentId);
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			if ($parentElement) {



				$folder = './images/cms/data/';

				$curEl = $parentElement;
				$altDirs = array();
				while(true) {
					$altDir = $curEl->getAltName ();

					if($altDir) {
						array_unshift($altDirs, $altDir);
					}

					$curEl  = $hierarchy->getElement ($curEl->getRel());

					if(empty($curEl)) {
						break;
					}


				}

				foreach($altDirs as $alt) {
					$folder .= $alt .'/';
					if (!file_exists($folder)) {
						mkdir ($folder);
					}
				}

				$allowedTypes = array ("jpg","jpeg","gif","bmp","png");
				$tplId		= $parentElement->getTplId();
				$domainId	= $parentElement->getDomainId();
				$langId	= $parentElement->getLangId();

				$hierarchyTypeId = $hierarchyTypes->getTypeByName("photoalbum", "photo")->getId();
				$objectTypeId = $objectTypes->getBaseType("photoalbum", "photo");

				$objectType = $objectTypes->getType($objectTypeId);
				if($objectType->getHierarchyTypeId() != $hierarchyTypeId) {
					$this->errorNewMessage("Object type and hierarchy type doesn't match");
					$this->errorPanic();
				}
				if (isset($_FILES['fs_upl_files']) && is_array($_FILES['fs_upl_files']))  {
					$uploadedFiles = $_FILES['fs_upl_files'];
					foreach ($uploadedFiles['name'] as $id=>$pathName) {
						if ($fileUploaded = umiImageFile::upload("fs_upl_files", $id, $folder)) {
							$fileName = $fileUploaded->getFileName();
							$filePath = $fileUploaded->getFilePath();
							$fileExt = $fileUploaded->getExt();
							if (in_array(strtolower($fileExt), $allowedTypes)) {
								$pathInfo = getPathInfo($fileName);
								$title = $pathInfo['filename'];
								$elementId = $hierarchy->addElement($parentId, $hierarchyTypeId, $title, $title, $objectTypeId, $domainId, $langId, $tplId);
								permissionsCollection::getInstance()->setDefaultPermissions($elementId);

								$element = $hierarchy->getElement($elementId, true);

								$element->setIsActive(true);
								$element->setIsVisible(false);
								$element->setName($title);
								$element->setValue("photo", $fileUploaded);
								$element->setValue("create_time", time());

								$element->commit();
								$parentElement->setUpdateTime(time());
								$parentElement->commit();
							} else {
								$fileUploaded->delete();
							}
						}
					}

					$this->chooseRedirect();
				} else {
					throw new publicAdminException(getLabel("error-expect-files-array"));
				}
			} else {
				throw new publicAdminException(getLabel("error-expect-parent-id"));
			}
		}
	};
?>
