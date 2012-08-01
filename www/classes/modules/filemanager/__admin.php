<?php
	abstract class __filemanager extends baseModuleAdmin {

		public function directory_list() {
			$this->upload_files();	
			$mode = (string) getRequest('param0');
			if($mode == "do") {
				$this->upload_files();
				$this->chooseRedirect();
			}

			$inputData = Array(
				'directory' => $this->getCurrentPath()
			);

			$this->setDataType("list");
			$this->setActionType("view");
			
			$this->setData($inputData);
			return $this->doData();
		}

		public function getCurrentPath() {
			$s_path = $this->s_root_path . "/files/"; // def path

			if (strlen(getRequest('dir'))) {
				$s_path = base64_decode(getRequest('dir'));
				$_SESSION['umi_fs_path'] = $s_path;
			} elseif (isset($_SESSION['umi_fs_path'])) {
				$s_path = $_SESSION['umi_fs_path'];
			}

			$s_path = realpath($s_path);
			$s_path = str_replace("\\", "/", $s_path);
			$s_path = str_replace("//", "/", $s_path);

			if(strpos($s_path, $this->s_root_path) === false || strpos($s_path, "..") !== false || strpos($s_path, "./") !== false) {
				$s_path = $this->s_root_path;
			}

			
			while (substr($s_path, -1)=="/") $s_path=substr($s_path, 0, (strlen($s_path)-1));

			$s_path = is_dir($s_path) ? $s_path : $this->s_root_path;

			if($s_path == $this->s_root_path) {
				$s_path = ".";
			} else {
				$s_path = substr($s_path, strlen($this->s_root_path) + 1);
			}

			$s_path = str_replace("\\", DIRECTORY_SEPARATOR, $s_path);

			return $s_path;
		}
		
		
		public function upload_files() {
			$s_path = $this->getCurrentPath();
			if (!defined("CURRENT_VERSION_LINE") || CURRENT_VERSION_LINE != "demo") {
				if (isset($_FILES['fs_upl_files']) && count($_FILES['fs_upl_files'])) {
					$arr_files = $_FILES['fs_upl_files'];
					foreach ($arr_files['name'] as $i_id => $s_name) {
						umiFile::upload("fs_upl_files", $i_id, $s_path);
					}
				}
			
			}
		}

		
		public function make_directory() {
			$s_path = $this->getCurrentPath();

			if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE == "demo") {
				$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_path));
				return false;
			}

			$s_dir_name = translit::convert(getRequest('newdir'));

			$s_new_dir_path = $s_path."/".$s_dir_name;
			if (strlen($s_path) && !is_dir($s_new_dir_path)) {
				// try md
				if (false === mkdir($s_new_dir_path)) {
					throw new publicAdminException(getLabel("error-can-not-create-directory"));
				}
			}

			$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_path));
		}

		public function del() {
			$s_obj_path = base64_decode(getRequest('param0'));
			$s_dir_path = dirname($s_obj_path);


			if(!$this->checkIsAllowedPath($s_obj_path)) {
				throw new publicAdminException(getLabel('error-fs-not-allowed'));
			}

			if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE == "demo") {
				$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_dir_path));
				return false;
			}

			if (is_dir($s_obj_path)) {
				removeDirectory($s_obj_path);
			} elseif (is_file($s_obj_path)) {
				if(@unlink($s_obj_path)) {
					$typesCollection   = umiObjectTypesCollection::getInstance();
					$objectsCollection = umiObjectsCollection::getInstance();
					$selection = new umiSelection();
					$typeId    = $typesCollection->getBaseType("filemanager", "shared_file");
					$type 	   = $typesCollection->getType($typeId);						
					$selection->addObjectType($typeId);
					$selection->addPropertyFilterLike($type->getFieldId('fs_file'), './'.$s_obj_path);
					$sfiles    = umiSelectionsParser::runSelection($selection);
					foreach($sfiles as $sfileId) {
						if($file = $objectsCollection->getObject($sfileId)) {
							$file->setValue('fs_file', '');
						}
					}
				}
			}
			$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_dir_path));
		}
		
		public function rename() {
			$s_path = $this->getCurrentPath();
			
			if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE == "demo") {
				$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_path));
				return false;
			}

			$s_old_name = getRequest('old_name');
			$s_new_name = getRequest('new_name');
			
			if(!$this->checkIsAllowedPath($s_path . "/" . $s_old_name)) {
				throw new publicAdminException(getLabel('error-fs-not-allowed'));
			}
			
			$s_new_name_arr = explode(".", $s_new_name);
			foreach($s_new_name_arr as &$sn) {
				$sn = translit::convert($sn);
			}
			$s_new_name = implode(".", $s_new_name_arr);

			if (strlen($s_path) && strlen($s_old_name) && strlen($s_new_name)) {
				if (file_exists($s_path."/".$s_old_name) && !file_exists($s_path."/".$s_new_name)) {
					// try rename
					if (@rename($s_path."/".$s_old_name, $s_path."/".$s_new_name) === false) {
						throw new publicAdminException(getLabel('error-cant-rename-dir'));
					} else {
						$typesCollection   = umiObjectTypesCollection::getInstance();
						$objectsCollection = umiObjectsCollection::getInstance();
						$selection = new umiSelection();
						$typeId    = $typesCollection->getBaseType("filemanager", "shared_file");
						$type 	   = $typesCollection->getType($typeId);						
						$selection->addObjectType($typeId);
						$selection->addPropertyFilterLike($type->getFieldId('fs_file'), './'.$s_path."/".$s_old_name);
						$sfiles    = umiSelectionsParser::runSelection($selection);
						foreach($sfiles as $sfileId) {
							if($file = $objectsCollection->getObject($sfileId)) {
								$file->setValue('fs_file', new umiFile('./'.$s_path."/".$s_new_name));
							}
						}
					}
				}
			}
			
			$this->chooseRedirect('/admin/filemanager/directory_list/?dir=' . base64_encode($s_path));
		}

		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'filemanager', '#__name'=>'shared_files'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'filemanager', '#__name'=>'del_shared_file', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'filemanager', '#__name'=>'shared_file_activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => 'shared_file')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'rate_voters', 'rate_sum'),
					'default' => 'downloads_counter[140px]'
				);
		}

		public function checkIsAllowedPath($checkPath) {
			$paths = Array(
				'/classes', '/css', '/dtd', '/errors', '/js', '/man', '/manifest',
				'/pwindows', '/scriptaculous', '/smu', '/styles', '/tinymce',
				'/tpls', '/xmldb', '/xsl',
				'/autothumbs.php', '/cacheControl.php', '/captcha.php', '/clusterCacheSync.php',
				'/comile.php', '/config.php', '/cron.php', '/def_macroses.php', '/errors.php',
				'/index.php',  '/lib.php', '/mysql.php', '/releaseStreams.php', '/sbots.php',
				'/security.php', '/standalone.php', '/streams.php', '/system.php'
			);
			
			$checkPath = substr(realpath($checkPath), strlen(CURRENT_WORKING_DIR));
			
			foreach($paths as $path) {
				if(substr($checkPath, 0, strlen($path)) == $path) {
					return false;
				}
			}
			return true;
		}

	};
?>