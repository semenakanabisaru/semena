<?php
/**
	* Класс для управления резервными копиями страниц
*/
	class backupModel extends singleton implements iBackupModel {

		protected function __construct() {}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Получить список изменений для страницы $cparam
			* @param Integer $cparam = false id страницы
			* @return Array список изменений
		*/
		public function getChanges($cparam = false) {

			$regedit = regedit::getInstance();

			if (!$regedit->getVal("modules/backup/enabled")) {
				return false;
			}

			$limit = (int) $regedit->getVal("//modules/backup/max_save_actions");
			$time_limit = (int) $regedit->getVal("//modules/backup/max_timelimit");
			$end_time = $time_limit * 3600 * 24;

			$cparam = (int) $cparam;

			$limit = ($limit > 2) ? $limit : 2;

			$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $cparam . "' AND (" . time() . "-ctime)<" . $end_time . " ORDER BY ctime DESC LIMIT {$limit}";
			$result = l_mysql_query($sql);

			if (mysql_num_rows($result) < 2) {
				$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $cparam . "' ORDER BY ctime DESC LIMIT 2";
				$result = l_mysql_query($sql);
			}

			$params = array();
			$rows = array();
			while(list($revision_id, $ctime, $changed_module, $user_id, $is_active) = mysql_fetch_row($result)) {
				
				$revision_info = $this->getChangeInfo($revision_id, $ctime, $changed_module, $cparam, $user_id, $is_active);
				if (count($revision_info)) $rows[] = $revision_info;				
			}

			$params['nodes:revision'] = $rows;
			return $params;

		}
		
		protected function getChangeInfo($revision_id, $ctime, $changed_module, $cparam, $user_id, $is_active) {
			
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			
			$revision_info = array();	
			
			$element = $hierarchy->getElement($cparam);
			if ($element instanceof umiHierarchyElement) {

				$revision_info['attribute:changetime'] = $ctime;
				$revision_info['attribute:user-id'] = $user_id;
				if (strlen($changed_module) == 0) {
					$revision_info['attribute:is-void'] = true;
				}
				if ($is_active) {
					$revision_info['attribute:active'] = "active";
				}
				$revision_info['date'] = new umiDate($ctime);
				$revision_info['author'] = selector::get('object')->id($user_id);
				$revision_info['link'] = "/admin/backup/rollback/{$revision_id}/";

				$module_name = $element->getModule();
				$method_name = $element->getMethod();

				$module = $cmsController->getModule($module_name);
				if($module instanceof def_module) {
					$links = $module->getEditLink($cparam, $method_name);
					if(isset($links[1])) {
						$revision_info['page'] = array();
						$revision_info['page']['attribute:name'] = $element->getName();
						$revision_info['page']['attribute:edit-link'] = $links[1];
						$revision_info['page']['attribute:link'] = $element->link;
					}
				}
			}
			
			return $revision_info;
			
		}

		/**
			* Получить список изменений для всех страниц
			* @return Array список изменений
		*/
		public function getAllChanges() {
			if (!regedit::getInstance()->getVal("modules/backup/enabled")) {
				return false;
			}

			$sql = "SELECT id, ctime, changed_module, param, user_id, is_active FROM cms_backup ORDER BY ctime DESC LIMIT 100";
			$result = l_mysql_query($sql);
			
			$params = array();
			$rows = array();
			
			while(list($revision_id, $ctime, $changed_module, $cparam, $user_id, $is_active) = mysql_fetch_row($result)) {
				$revision_info = $this->getChangeInfo($revision_id, $ctime, $changed_module, $cparam, $user_id, $is_active);
				if (count($revision_info)) $rows = array_merge($rows, array($revision_info));
			}

			$params['nodes:revision'] = $rows;
			return $params;

		}

		/**
			* Сохранить как точку восстановления текущие изменения для страницы $cparam
			* @param Integer $cparam = false id страницы
			* @param String $changed_module = "" не используется более
			* @param String $changed_method = "" не используется более
		*/
		public function save($cparam = "", $cmodule = "", $cmethod = "") {

			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) return false;
			if(getRequest('rollbacked')) return false;

			$this->restoreIncrement();

			$cmsController = cmsController::getInstance();
			if(!$cmodule) $cmodule = $cmsController->getCurrentModule();
			$cmethod = $cmsController->getCurrentMethod();

			$cuser_id = ($cmsController->getModule('users')) ? $cuser_id = $cmsController->getModule('users')->user_id : 0;


			$ctime = time();

			if(!$cmodule) {
				$cmodule = getRequest('module');
			}

			if(!$cmethod) {
				$cmethod = getRequest('method');
			}

			foreach($_REQUEST as $cn => $cv) {
				if($cn == "save-mode") continue;
				$_temp[$cn] = (!is_array($cv)) ? base64_encode($cv) : $cv;
			}


			if(isset($_temp['data']['new'])) {
				$element = umiHierarchy::getInstance()->getElement($cparam);
				if($element instanceof umiHierarchyElement) {
					$_temp['data'][$element->getObjectId()] = $_temp['data']['new'];
					unset($_temp['data']['new']);
				}

			}

			$req = serialize($_temp);
			$req = l_mysql_real_escape_string($req);

			$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $cparam . "'";
			l_mysql_query($sql);

			$sql = <<<SQL
INSERT INTO cms_backup (ctime, changed_module, changed_method, param, param0, user_id, is_active)
				VALUES('{$ctime}', '{$cmodule}', '{$cmethod}', '{$cparam}', '{$req}', '{$cuser_id}', '1')
SQL;
			l_mysql_query($sql);

			$limit = regedit::getInstance()->getVal("//modules/backup/max_save_actions");
			$sql = "SELECT COUNT(*) FROM cms_backup WHERE param='" . $cparam . "' ORDER BY ctime DESC";
			$result = l_mysql_query($sql);
			list($total_b) = mysql_fetch_row($result);

			$td = $total_b - $limit;
			if($td < 0) {
				$td = 0;
			}

			$sql = "SELECT id FROM cms_backup WHERE param='" . $cparam . "' ORDER BY ctime DESC LIMIT 2";
			$result = l_mysql_query($sql);
			$backupIds = array();
			while(list($backupId) = mysql_fetch_row($result)) {
				$backupIds[] = $backupId;
			}
			$notId = "";
			if (count($backupIds)) $notId = "AND id NOT IN (" . implode(", ", $backupIds) . ")";


			$sql = "DELETE FROM cms_backup WHERE param='" . $cparam . "' {$notId} ORDER BY ctime ASC LIMIT " . ($td);
			l_mysql_query($sql);

			$time_limit = regedit::getInstance()->getVal("//modules/backup/max_timelimit");
			$end_time = $time_limit*3600*24;
			$sql="DELETE FROM cms_backup WHERE param='" . $cparam . "' AND (" . time() . "-ctime)>" . $end_time . " {$notId} ORDER BY ctime ASC";
			l_mysql_query($sql);

			return true;
		}

		/**
			* Восстановить данные из резервной точки $revision_id
			* @param Integer $revision_id id резервное копии
			* @return Boolean false, если восстановление невозможно
		*/
		public function rollback($revision_id) {
			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) {
				return false;
			}

			$revision_id = (int) $revision_id;

			$sql = "SELECT param, param0, changed_module, changed_method FROM cms_backup WHERE id='$revision_id' LIMIT 1";
			$result = l_mysql_query($sql);

			if(list($element_id, $data, $changed_module, $changed_method) = mysql_fetch_row($result)) {
				$changed_param = $element_id;

				$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $changed_param . "'";
				l_mysql_query($sql);

				$sql = "UPDATE cms_backup SET is_active='1' WHERE id='" . $revision_id . "'";
				l_mysql_query($sql);

				$_temp = unserialize($data);
				$_REQUEST = Array();

				foreach($_temp as $cn => $cv) {
					if(!is_array($cv)) {
						$cv = base64_decode($cv);
					} else {
						foreach($cv as $i => $v) {
							$cv[$i] = $v;
						}
					}
					$_REQUEST[$cn] = $cv;
					$_POST[$cn] = $cv;
				}
				$_REQUEST['rollbacked'] = true;
				$_REQUEST['save-mode'] = getLabel('label-save');

				if($changed_module_inst = cmsController::getInstance()->getModule($changed_module)) {
					$element = umiHierarchy::getInstance()->getElement($element_id);

					if($element instanceof umiHierarchyElement) {
						$links = $changed_module_inst->getEditLink($element_id, $element->getMethod());
						if(sizeof($links) >= 2) {
							$edit_link = $links[1];
							$_REQUEST['referer'] = $edit_link;

							$edit_link = trim($edit_link, "/") . "/do";

							if(preg_match("/admin\/[A-z]+\/([^\/]+)\//", $edit_link, $out)) {
								if(isset($out[1])) {
									$changed_method = $out[1];
								}
							}
							$_REQUEST['path'] = $edit_link;
							$_REQUEST['param0'] = $element_id;
							$_REQUEST['param1'] = "do";
						}
					}

					return $changed_module_inst->cms_callMethod($changed_method, Array());
				} else {
					throw new requreMoreAdminPermissionsException("You can't rollback this action. No permission to this module.");
				}
			}

		}

		/**
			* Добавить сообщение в список изменений страницы $elementId без занесения самих изменений
			* @param Integer $elementId id страницы
		*/
		public function addLogMessage($elementId) {
			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) {
				return false;
			}

			$this->restoreIncrement();

			$cmsController = cmsController::getInstance();
			$cuser_id = ($cmsController->getModule('users')) ? $cmsController->getModule('users')->user_id : 0;

			$time = time();
			$param = (int) $elementId;

			$sql = "INSERT INTO cms_backup (ctime, param, user_id, param0) VALUES('{$time}', '{$param}', '{$cuser_id}', '{$time}')";
			l_mysql_query($sql);
		}

		public function fakeBackup($elementId) {
			$element = selector::get('page')->id($elementId);
			if(is_null($element)) return false;
			$originalRequest = $_REQUEST;

			$object = $element->getObject();
			$type = selector::get('object-type')->id($object->getTypeId());

			$_REQUEST['name'] = $element->name;
			$_REQUEST['alt-name'] = $element->altName;
			$_REQUEST['active'] = $element->isActive;
			foreach($type->getAllFields() as $field) {
				$fieldName = $field->getName();
				$value = $this->fakeBackupValue($object, $field);
				if(is_null($value)) continue;
				$_REQUEST['data'][$object->id][$fieldName] = $value;
			}

			$this->save($elementId, $element->getModule());
			$_REQUEST = $originalRequest;
		}

		protected function fakeBackupValue(iUmiObject $object,  iUmiField $field) {
			$value = $object->getValue($field->getName());

			switch($field->getDataType()) {
				case 'file':
				case 'img_file':
				case 'swf_file':
					return ($value instanceof iUmiFile) ? $value->getFilePath() : '';

				case 'boolean':
					return $value ? '1' : '0';

				case 'date':
					return ($value instanceof umiDate) ? $value->getFormattedDate('U') : NULL;

				case 'tags':
					return is_array($value) ? implode(", ", $value) : NULL;

				default:
					return (string) $value;
			}
		}

		protected function restoreIncrement() {

			$result1 = l_mysql_query("SELECT max( id ) FROM `cms_backup`");
			$row1 = mysql_fetch_row($result1);
			$incrementToBe = $row1[0] + 1;

			$result = l_mysql_query("SHOW TABLE STATUS LIKE 'cms_backup'");
       		$row = mysql_fetch_array($result);
       		$increment = isset($row['Auto_increment']) ? (int) $row['Auto_increment'] : false;
			if($increment !== false && $increment != $incrementToBe){
				l_mysql_query("ALTER TABLE `cms_backup` AUTO_INCREMENT={$incrementToBe}");
			}
		}
	};
?>