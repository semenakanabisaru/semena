<?php
/**
	* Управляет правами доступа на страницы и ресурсы модулей.
	* Синглтон. Экземпляр класса можно получить через статичесик метод getInstance.
*/
	class permissionsCollection extends singleton implements iSingleton, iPermissionsCollection {
		protected $methodsPermissions = array(), $user_id = 0, $tempElementPermissions = array();
		protected $elementsCache = array();

		// Some permissions constants
		const E_READ_ALLOWED   = 0;
		const E_EDIT_ALLOWED   = 1;
		const E_CREATE_ALLOWED = 2;
		const E_DELETE_ALLOWED = 3;
		const E_MOVE_ALLOWED   = 4;

		const E_READ_ALLOWED_BIT   = 1;
		const E_EDIT_ALLOWED_BIT   = 2;
		const E_CREATE_ALLOWED_BIT = 4;
		const E_DELETE_ALLOWED_BIT = 8;
		const E_MOVE_ALLOWED_BIT   = 16;

		/**
			* Конструктор
		*/
		public function __construct() {
			if(is_null(getRequest('guest-mode')) == false) {
				$this->user_id = self::getGuestId();
				return;
			}

			$users = cmsController::getInstance()->getModule("users");

			if($users instanceof def_module) {
				$this->user_id = $users->user_id;

				if($this->isAllowedMethod($this->user_id, "content", "sitetree")){
					   $_SESSION['_umi_opaf_disabled_html']=1;
			    }
			    else{
					   $_SESSION['_umi_opaf_disabled_html']=0;
				}
			}


		}

		/**
			* Получить экземпляр коллекци
			* @return permissionsCollection экземпляр класса permissionsCollection
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Внутрисистемный метод, не является частью публичного API
			* @param Integer $owner_id id пользователя или группы
			* @return Integer|array
		*/
		public function getOwnerType($owner_id) {
			if($owner_object = umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($groups = $owner_object->getPropByName("groups")) {
					return $groups->getValue();
				} else {
					return $owner_id;
				}
			} else {
				return false;
			}
		}

		/**
			* Внутрисистемный метод, не является частью публичного API
			* @param Integer $owner_id id пользователя или группы
			* @return String фрагмент SQL-запроса
		*/
		public function makeSqlWhere($owner_id, $ignoreSelf = false) {
			static $cache = array();
			if(isset($cache[$owner_id])) return $cache[$owner_id];

			$owner = $this->getOwnerType($owner_id);

			if(is_numeric($owner)) {
				$owner = array();
			}

			if($owner_id) {
				$owner[] = $owner_id;
			}
			$owner[] = self::getGuestId();

			$owner = array_unique($owner);

			if(sizeof($owner) > 2) {
				foreach($owner as $i => $id) {
					if($id == $owner_id && $ignoreSelf) {
						unset($owner[$i]);
					}
				}
				$owner = array_unique($owner);
				sort($owner);
			}

			$sql = "";
			$sz = sizeof($owner);
			for($i = 0; $i < $sz; $i++) {
				$sql .= "cp.owner_id = '{$owner[$i]}'";
				if($i < ($sz - 1)) {
					$sql .= " OR ";
				}
			}
			$sql = "({$sql})";

			return $cache[$owner_id] = $sql;
		}


		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ к модулю $module
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param String $module название модуля
			* @return Boolean true если доступ разрешен
		*/
		public function isAllowedModule($owner_id, $module) {
			static $cache = array();


			if($owner_id == false) {
				$owner_id = $this->getUserId();
			}

			if($this->isSv($owner_id)) return true;
			if(isset($cache[$owner_id][$module])) {
				return $cache[$owner_id][$module];
			}

			$sql_where = $this->makeSqlWhere($owner_id);
			$module = l_mysql_real_escape_string($module);

			if(substr($module, 0, 7) == "macros_") return false;

			$sql = "SELECT module, MAX(cp.allow) FROM cms_permissions cp WHERE method IS NULL AND {$sql_where} GROUP BY module";
			$result = l_mysql_query($sql);
			while(list($m, $allow) = mysql_fetch_row($result)) {

				$cache[$owner_id][$m] = $allow;
			}
			return isset($cache[$owner_id][$module]) ? (bool) $cache[$owner_id][$module] : false;
		}

		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ к методу $method модуля $module
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param String $module название модуля
			* @param String $method название метода
			* @return Boolean true если доступ на метод разрешен
		*/
		public function isAllowedMethod($owner_id, $module, $method, $ignoreSelf = false) {
			if($module == "content" && !strlen($method)) return 1;
			if($module == "config" && $method == "menu") return 1;
			if($module == "eshop" && $method == "makeRealDivide") return 1;

			if($this->isAdmin($owner_id)) {
				if($this->isAdminAllowedMethod($module, $method)) {
					return 1;
				}
			}

			if($this->isSv($owner_id)) return true;
			if(!$module) return false;

			$method = $this->getBaseMethodName($module, $method);

			$methodsPermissions = &$this->methodsPermissions;
			if(!isset($methodsPermissions[$owner_id]) || !is_array($methodsPermissions[$owner_id])) {
				$methodsPermissions[$owner_id] = array();
			}
			$cache = &$methodsPermissions[$owner_id];

			$sql_where = $this->makeSqlWhere($owner_id, $ignoreSelf);

			if($module == "backup" && $method == "rollback") return true;
			if($module == "autoupdate" && $method == "service") return true;
			if($module == "config" && ($method == "lang_list" || $method == "lang_phrases")) return true;
			if($module == "users" && ($method == "auth" || $method == "login_do" || $method == "login")) return true;

			$cache_key = $module;
			if(!array_key_exists($cache_key, $cache)) {
				$cacheData = cacheFrontend::getInstance()->loadData('module_perms_' . $owner_id . '_' . $cache_key);
				if(is_array($cacheData)) {
					$cache[$module] = $cacheData;
				} else {
					$sql = "SELECT cp.method, MAX(cp.allow) FROM cms_permissions cp WHERE module = '{$module}' AND {$sql_where} GROUP BY module, method";
					$result = l_mysql_query($sql);

					$cache[$module] = array();
					while(list($cmethod) = mysql_fetch_row($result)) {
						$cache[$cache_key][] = $cmethod;
					}

					cacheFrontend::getInstance()->saveData('module_perms_' . $owner_id . '_' . $cache_key, $cache[$module], 3600);
				}
			}

			if (in_array($method, $cache[$cache_key]) || in_array(strtolower($method), $cache[$cache_key])) {
				return true;
			} else {
				return false;
			}
		}

		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ на чтение страницы $object_id (класс umiHierarchyElement)
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $object_id id страницы, доступ к которой проверяется
			* @return Boolean true если есть доступ хотя бы на чтение
		*/
		public function isAllowedObject($owner_id, $object_id, $resetCache = false) {
			$object_id = (int) $object_id;
			if($object_id == 0) return array(false, false, false, false, false);

			if($this->isSv($owner_id)) {
				return array(true, true, true, true, true);
			}

			if(array_key_exists($object_id, $this->tempElementPermissions)) {
				$level = $this->tempElementPermissions[$object_id];
				return array((bool)($level&1), (bool)($level&2), (bool)($level&4), (bool)($level&8), (bool)($level&16) );
			}

			$cache = &$this->elementsCache;

			if(!$resetCache && isset($cache[$object_id]) && isset($cache[$object_id][$owner_id])) {
				return $cache[$object_id][$owner_id];
			}

			$sql_where = $this->makeSqlWhere($owner_id);

			$sql = "SELECT BIT_OR(cp.level) FROM cms3_permissions cp WHERE rel_id = '{$object_id}' AND {$sql_where}";
			$level = false;
			cacheFrontend::getInstance()->loadSql($sql);

			if(!$level || $resetCache) {
				$result = l_mysql_query($sql);
				list($level) = mysql_fetch_row($result);
				$level = array((bool)($level&1), (bool)($level&2), (bool)($level&4), (bool)($level&8), (bool)($level&16) );

			}

			if($level) {
				cacheFrontend::getInstance()->saveSql($sql, $level, 600);
			}

			if(!isset($cache[$object_id])) $cache[$object_id] = array();
			$cache[$object_id][$owner_id] = $level;
			return $level;
		}

		/**
			* Узнать, является ли пользователь или группа пользователей $user_id супервайзером
			* @param Integer $user_id id пользователя (по умолчанию используется id текущего пользователя)
			* @return Boolean true, если пользователь является супервайзером
		*/
		public function isSv($user_id = false) {
			static $is_sv = array();

			if($user_id === false) {
				$user_id = $this->getUserId();
			}

			if(isset($is_sv[$user_id])) {
				return $is_sv[$user_id];
			}

			if(is_null(getRequest('guest-mode')) == false) {
				return $is_sv[$user_id] = false;
			}

			$sv_group_id = umiObjectsCollection::getInstance()->getObjectIdByGUID('users-users-15');
			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				$user_groups = $user->getValue('groups');
				if((is_array($user_groups) && in_array($sv_group_id, $user_groups)) || $user_id == $sv_group_id) {
					return $is_sv[$user_id] = true;
				}
			}

			return $is_sv[$user_id] = false;
		}

		/**
			* Узнать, является ли пользователь $user_id администратором, т.е. есть ли у него доступ
			* к администрированию хотя бы одного модуля
			* @param Integer $user_id = false id пользователя (по умолчанию используется id текущего пользователя)
			* @return Boolean true, если пользователь является администратором
		*/
		public function isAdmin($user_id = false, $ignoreCache = false) {
			static $is_admin = array();
			if($user_id === false) $user_id = $this->getUserId();
			if(isset($is_admin[$user_id])) return $is_admin[$user_id];
			if($this->isSv($user_id)) return $is_admin[$user_id] = true;

			if(!$ignoreCache && is_array(getSession('is_admin'))) {
				$is_admin = getSession('is_admin');
				if(isset($is_admin[$user_id])) return $is_admin[$user_id];
			}

			$sql_where = $this->makeSqlWhere($user_id);
			$sql = <<<SQL
SELECT COUNT(cp.allow)
	FROM cms_permissions cp
	WHERE method IS NULL AND {$sql_where} AND cp.allow IN (1, 2) GROUP BY module
SQL;
			$result = l_mysql_query($sql);

			list($cnt) = mysql_fetch_row($result);
			$is_admin[$user_id] = (bool) $cnt;
			$_SESSION['is_admin'] = $is_admin;
			return $is_admin[$user_id];
		}

		/**
			* Узнать, является ли пользователь $user_id владельцем объекта (класс umiObject) $object_id
			* @param Integer $object_id id объекта (класс umiObject)
			* @param $user_id id пользователя
			* @return Boolean true, если пользователь является владельцем
		*/
		public function isOwnerOfObject($object_id, $user_id = false) {
			if($user_id == false) {
				$user_id = $this->getUserId();
			}

			if($user_id == $object_id) {	//Objects == User, that's ok
				return true;
			} else {
				$object = umiObjectsCollection::getInstance()->getObject($object_id);
				if($object instanceof umiObject) {
					$owner_id = $object->getOwnerId();
				} else {
					$owner_id = 0;
				}

				if($owner_id == 0 || $owner_id == $user_id) {
					return true;
				} else {
					$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
					if($owner_id == $guestId && class_exists('customer')) {
						$customer = customer::get();
						if($cusotmer && ($customer->id == $owner_id)) {
							return true;
						}
					}
					return false;
				}
			}
		}

		/**
			* Сбросить настройки прав до дефолтных для страницы (класс umiHierarchyElement) $element_id
			* @param Integer $element_id id страницы (класс umiHierarchyElement)
			* @return Boolean false если произошла ошибка
		*/
		public function setDefaultPermissions($element_id) {
			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return false;
			}

			l_mysql_query("START TRANSACTION");


			$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql);


			$element = umiHierarchy::getInstance()->getElement($element_id, true, true);
			$hierarchy_type_id = $element->getTypeId();
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);

			$module = $hierarchy_type->getName();
			$method = $hierarchy_type->getExt();


			//Getting outgroup users
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");

			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$group_field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId("groups");
			$sel->setPropertyFilter();
			$sel->addPropertyFilterIsNull($group_field_id);

			$users = umiSelectionsParser::runSelection($sel);


			//Getting groups list
			$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "users");

			$sel = new umiSelection;

			$sel->setObjectTypeFilter();
			$sel->addObjectType($object_type_id);
			$groups = umiSelectionsParser::runSelection($sel);

			$objects = array_merge($users, $groups);


			//Let's get element's ownerId and his groups (if user)
			$owner_id = $element->getObject()->getOwnerId();
			if($owner = umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($owner_groups = $owner->getValue("groups")) {
					$owner_arr = $owner_groups;
				} else {
					$owner_arr = array($owner_id);
				}
			} else {
				$owner_arr = array();
			}


			foreach($objects as $ugid) {
				if($ugid == SV_GROUP_ID) continue;
				if($module == "content") $method == "page";

				if($this->isAllowedMethod($ugid, $module, $method)) {
					if(in_array($ugid, $owner_arr) || $ugid == SV_GROUP_ID || $this->isAllowedMethod($ugid, $module, $method . ".edit")) {
						$level = permissionsCollection::E_READ_ALLOWED_BIT +
								 permissionsCollection::E_EDIT_ALLOWED_BIT +
								 permissionsCollection::E_CREATE_ALLOWED_BIT +
								 permissionsCollection::E_DELETE_ALLOWED_BIT +
								 permissionsCollection::E_MOVE_ALLOWED_BIT;
					} else {
						$level = permissionsCollection::E_READ_ALLOWED_BIT;
					}

					$sql = "INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES('{$element_id}', '{$ugid}', '{$level}')";
					l_mysql_query($sql);
				}
			}

			l_mysql_query("COMMIT");
			l_mysql_query("SET AUTOCOMMIT=1");

			$this->cleanupElementPermissions($element_id);

			if(isset($this->elementsCache[$element_id])) unset($this->elementsCache[$element_id]);

			$cache_key = $this->user_id . "." . $element_id;
			cacheFrontend::getInstance()->saveSql($cache_key, array(true, true));
		}

		/**
		 * Копирует права с родительского элемента
		 * @param Integer $elementId идентификатор элемента, на который устанавливаем права
		 */
		public function setInheritedPermissions($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$parentId = false;
			if($element = $hierarchy->getElement($elementId, true)) {
				$parentId = $element->getParentId();
			}
			if($parentId) {
				$records = $this->getRecordedPermissions($parentId);
				$values  = array();
				foreach($records as $ownerId => $level) {
					$values[] = "('{$elementId}', '{$ownerId}', '{$level}')";
				}
				if(empty($values)) return;
				l_mysql_query("START TRANSACTION");
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";
				l_mysql_query($sql);
				$sql = "INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES ".implode(", ", $values);
				l_mysql_query($sql);
				l_mysql_query("COMMIT");
				l_mysql_query("SET AUTOCOMMIT=1");
				return true;
			} else {
				return $this->setDefaultPermissions($elementId);
			}
		}

		/**
			* Удалить все права на странциу $elementId для ползователя или группы $ownerId
			* @param Integer $elementId id страницы (класс umiHierarchyElement)
			* @param Integer $ownerId=false id пользователя или группы, чьи права сбрасываются. Если false, то права сбрасываются для всех пользователей
		*/
		public function resetElementPermissions($elementId, $ownerId = false) {
			$elementId = (int) $elementId;


			if($ownerId === false) {
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";
				if(isset($this->elementsCache[$elementId]))
					unset($this->elementsCache[$elementId]);
			} else {
				$ownerId = (int) $ownerId;
				$sql = "DELETE FROM cms3_permissions WHERE owner_id = '{$ownerId}' AND rel_id = '{$elementId}'";
				if(isset($this->elementsCache[$elementId]) && isset($this->elementsCache[$elementId][$ownerId]) )
					unset($this->elementsCache[$elementId][$ownerId]);
			}

			l_mysql_query($sql);
			return true;
		}

		/**
			* Сбросить все права на модули и методы для пользователя или группы $ownerId
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param array $modules=NULL массив, который указывает модули, для которых сбросить права. По умолчанию, сбрасываются права на все модули
		*/
		public function resetModulesPermissions($ownerId, $modules = NULL) {
			$ownerId = (int) $ownerId;

			$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}'";

			if(is_array($modules)) {
				if(sizeof($modules)) {
					$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}' AND module IN ('" . implode("', '", $modules) . "')";
				}
			}

			l_mysql_query($sql);

			$cacheFrontend = cacheFrontend::getInstance();
			foreach($modules as $module) {
				$cacheFrontend->deleteKey('module_perms_' . $ownerId . '_' . $module, true);
			}

			return true;
		}

		/**
			* Установить определенные права на страница $elementId для пользователя или группы $ownerId
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param Integer $elementId id страницы (класс umiHierarchyElement), для которой меняются права
			* @param Integer $level уровень выставляемых прав то "0" до "2". "нет доступа" (0), "только чтение" (1), "чтение и запись" (2)
			* @return Boolean true если не произошло ошибки
		*/
		public function setElementPermissions($ownerId, $elementId, $level) {
			$ownerId = (int) $ownerId;
			$elementId = (int) $elementId;
			$level = (int) $level;

			if($elementId == 0 || $ownerId == 0) {
				return false;
			}

			if(isset($this->elementsCache[$elementId]) && isset($this->elementsCache[$elementId][$ownerId])) {
				unset($this->elementsCache[$elementId][$ownerId]);
            }

			$sql_reset = "DELETE FROM cms3_permissions WHERE owner_id = '".$ownerId."' AND rel_id = '".$elementId."'";
			l_mysql_query($sql_reset);

			$sql = "INSERT INTO cms3_permissions (owner_id, rel_id, level) VALUES('{$ownerId}', '{$elementId}', '{$level}')";
			l_mysql_query($sql);

			$this->cleanupElementPermissions($elementId);

			$this->isAllowedObject($ownerId, $elementId, true);

			return true;
		}


		/**
			* Разрешить пользователю или группе $owner_id права на $module/$method
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param String $module название модуля
			* @param String $method=false название метода
		*/
		public function setModulesPermissions($ownerId, $module, $method = false, $cleanupPermissions = true) {
			$ownerId = (int) $ownerId;
			$module = l_mysql_real_escape_string($module);

			if($method !== false) {
				return $this->setMethodPermissions($ownerId, $module, $method);
			} else {
				$sql = "INSERT INTO cms_permissions (owner_id, module, method, allow) VALUES('{$ownerId}', '{$module}', NULL, '1')";
				l_mysql_query($sql);

				if($cleanupPermissions) $this->cleanupBasePermissions();
				return true;
			}
		}

		protected function setMethodPermissions($ownerId, $module, $method, $cleanupPermissions = true) {
			$method = l_mysql_real_escape_string($method);

			$sql = "INSERT INTO cms_permissions (owner_id, module, method, allow) VALUES('{$ownerId}', '{$module}', '{$method}', '1')";
			l_mysql_query($sql);

			$this->methodsPermissions[$ownerId][$module][] = $method;

			if($cleanupPermissions) $this->cleanupBasePermissions();
			return true;
		}

		/**
			* Узнать, имеет ли пользователь или группа в принципе права на какие-нибудь страницы
			* @param Integer $ownerId id пользователя или группы
			* @return Boolean false, если записей нет
		*/
		public function hasUserPermissions($ownerId) {
			$sql = "SELECT COUNT(*) FROM cms3_permissions WHERE owner_id = '{$ownerId}'";
			$result = l_mysql_query($sql);

			list($cnt) = mysql_fetch_row($result);
			return $cnt;
		}

		/**
			* Скопировать права на все страницы из $fromUserId в $toUserId
			* @param Integer $fromUserId id пользователя или группы пользователей, из которых копируются права
			* @param Integer $fromUserId id пользователя или группы пользователей, в которые копируются права
		*/
		public function copyHierarchyPermissions($fromUserId, $toUserId) {
			if($fromUserId == self::getGuestId()) {
				return false;		//No need in cloning guest permissions now
			}

			$fromUserId = (int) $fromUserId;
			$toUserId = (int) $toUserId;

			$sql = "INSERT INTO cms3_permissions (level, rel_id, owner_id) SELECT level, rel_id, '{$toUserId}' FROM cms3_permissions WHERE owner_id = '{$fromUserId}'";
			l_mysql_query($sql);

			return true;
		}

		/**
			* Системный метод. Получить массив прав из permissions.php и permissions.custom.php
			* @return array
		*/
		public function getStaticPermissions($module, $templater = false) {
			static $cache = array();

			if (isset($cache[$module]) && !$templater) {
				return $cache[$module];
			}

			$static_file = CURRENT_WORKING_DIR . "/classes/modules/" . $module . "/permissions.php";
			if(file_exists($static_file)) {
				require $static_file;
				if(isset($permissions)) {
					$static_permissions = $permissions;

					$static_file_custom = CURRENT_WORKING_DIR . "/classes/modules/" . $module . "/permissions.custom.php";
					if(file_exists($static_file_custom)) {
						unset($permissions);
						require $static_file_custom;
						if(isset($permissions)) {
							$static_permissions = array_merge_recursive($static_permissions, $permissions);
						}
					}

					// подключаем права из ресурсов шаблона
					// TODO: refactoring
					if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
						$static_file_custom = $resourcesDir . '/classes/modules/' . $module . "/permissions.php";
						if (file_exists($static_file_custom)) {
							unset($permissions);
							require $static_file_custom;
							if(isset($permissions)) {
								$static_permissions = array_merge_recursive($static_permissions, $permissions);
							}
						}
					}

					$cache[$module] = $static_permissions;
					unset($static_permissions);
					unset($permissions);
				}
				else $cache[$module] = array();
			}
			else $cache[$module] = array();

			return $cache[$module];
		}

		/**
			* Получить название корневого метода в системе приритета прав для $module::$method
			* @param String $module название модуля
			* @param String $method название метода
			* @return String название корневого метода
		*/
		protected function getBaseMethodName($module, $method) {
			//TODO: WTF
			//$methods = $this->getStaticPermissions($module, cmsController::getInstance()->getCurrentTemplater());
			$methods = $this->getStaticPermissions($module);

			if($method && is_array($methods)) {
				if(array_key_exists($method, $methods)) {
					return $method;
				} else {
					foreach($methods as $base_method => $sub_methods) {
						if(is_array($sub_methods)) {
							if(in_array($method, $sub_methods) || in_array(strtolower($method), $sub_methods)) {
								return $base_method;
							}
						}
					}
					return $method;
				}
			} else {
				return $method;
			}
		}

		/**
			* Получить id текущего пользователя
			* @return Integer id текущего пользователя
		*/
		public function getUserId() {
			return $this->user_id;
		}


		/**
			* Удалить все записи о правах на модули и методы для пользователей, если они ниже, чем у гостя
		*/
		public function cleanupBasePermissions() {
			$guestId = self::getGuestId();

			$sql    = "SELECT module, method FROM cms_permissions WHERE owner_id = '{$guestId}' AND allow = 1";
			$result = l_mysql_query($sql);

			$sql = array();
			while(list($module, $method) = mysql_fetch_row($result)) {
				if($method) {
					$sql[] = "(module = '{$module}' AND method = '{$method}')";
				} else {
					$sql[] = "(module = '{$module}' AND method IS NULL)";
				}
			}
			if(!empty($sql))
				l_mysql_query("DELETE FROM cms_permissions WHERE owner_id != '{$guestId}' AND (" . implode(' OR ', $sql) . ")");
		}

		/**
			* Удалить для страницы  с id $rel_id записи о правах пользователей, которые ниже, чем у гостя
			* @param Integer $rel_id id страница (класс umiHierarchyElement)
		*/
		protected function cleanupElementPermissions($rel_id) {
			$rel_id = (int) $rel_id;
			$guestId = self::getGuestId();

			$sql = "SELECT level FROM cms3_permissions WHERE owner_id = '{$guestId}' AND rel_id = {$rel_id}";
			$result = l_mysql_query($sql);
			$maxLevel = 0;
			while(list($level) = mysql_fetch_row($result)) {
				if($level>$maxLevel) $maxLevel = $level;
			}
			l_mysql_query("DELETE FROM cms3_permissions WHERE owner_id != '{$guestId}' AND level <= {$maxLevel} AND rel_id = {$rel_id}");
		}

		/**
			* Узнать, разрешено ли пользователю или группе $owner_id администрировать домен $domain_id
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $domain_id id домена (класс domain)
			* @return Integer 1, если доступ разрешен, 0 если нет
		*/
		public function isAllowedDomain($owner_id, $domain_id) {
			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;

			if($this->isSv($owner_id)) {
				return 1;
			}

			$sql_where_owners = $this->makeSqlWhere($owner_id);

			$sql = "SELECT MAX(cp.allow) FROM cms_permissions cp WHERE cp.module = 'domain' AND cp.method = '{$domain_id}' AND " . $sql_where_owners;
			$result = l_mysql_query($sql);

			if($row = mysql_fetch_row($result)) {
				list($level) = $row;
				return (int) $level;
			} else return 0;
		}

		/**
			* Установить права пользователю или группе $owner_id на администрирование домена $domain_id
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $domain_id id домена (класс domain)
			* @param Boolean $allow=true если true, то доступ разрешен
		*/
		public function setAllowedDomain($owner_id, $domain_id, $allow = 1) {
			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;
			$allow = (int) $allow;

			$sql = "DELETE FROM cms_permissions WHERE module = 'domain' AND method = '{$domain_id}' AND owner_id = '{$owner_id}'";
			$result = l_mysql_query($sql);

			$sql = "INSERT INTO cms_permissions (module, method, owner_id, allow) VALUES('domain', '{$domain_id}', '{$owner_id}', '{$allow}')";
			$result = l_mysql_query($sql);

			return true;
		}

		/**
			* Установить права по умолчанию для страницы $element по отношению к пользователю $owner_id
			* @param umiHierarchyElement $element экземпляр страницы
			* @param Integer $owner_id id пользователя или группы пользователей
			* @return Integer уровен доступа к странице, который был выбран системой
		*/
		public function setDefaultElementPermissions(iUmiHierarchyElement $element, $owner_id) {
			$module = $element->getModule();
			$method = $element->getMethod();

			$level = 0;
			if($this->isAllowedMethod($owner_id, $module, $method, true)) {
				$level = permissionsCollection::E_READ_ALLOWED_BIT;
			}

			if($this->isAllowedMethod($owner_id, $module, $method . ".edit", true)) {
				$level = permissionsCollection::E_READ_ALLOWED_BIT +
						 permissionsCollection::E_EDIT_ALLOWED_BIT +
						 permissionsCollection::E_CREATE_ALLOWED_BIT +
						 permissionsCollection::E_DELETE_ALLOWED_BIT +
						 permissionsCollection::E_MOVE_ALLOWED_BIT;
			}

			$this->setElementPermissions($owner_id, $element->getId(), $level);

			return $level;
		}

		/**
			* Сбросить для пользователя или группы $owner_id права на все страницы на дефолтные
			* @param Integer $owner_id id пользователя или группы пользователей
		*/
		public function setAllElementsDefaultPermissions($owner_id) {
			$owner_id = (int) $owner_id;
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();

			$this->elementsCache = array();

			$owner = $this->getOwnerType($owner_id);
			if(is_numeric($owner)) {
				$owner = array();
			}

			$owner[] = self::getGuestId();
			$owner = array_unique($owner);

			l_mysql_query("START TRANSACTION");

			$read = array();
			$write = array();

			foreach($hierarchyTypes->getTypesList() as $hierarchyType) {
				$module = $hierarchyType->getName();
				$method = $hierarchyType->getExt();

				if($this->isAllowedMethod($owner_id, $module, $method . ".edit", true)) {
					foreach($owner as $gid) {
						if($this->isAllowedMethod($gid, $module, $method . ".edit", true)) {
							continue 2;
						}
					}
					$write[] = $hierarchyType->getId();
					$level = 2;
				} else if($this->isAllowedMethod($owner_id, $module, $method, true)) {
					foreach($owner as $gid) {
						if($this->isAllowedMethod($gid, $module, $method, true)) {
							continue 2;
						}
					}

					$read[] = $hierarchyType->getId();
					$level = 1;
				} else {
					$level = 0;
				}
			}

			if(sizeof($read)) {
				$types = implode(", ", $read);

				$sql = <<<SQL
INSERT INTO cms3_permissions (level, owner_id, rel_id)
	SELECT 1, '{$owner_id}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
				l_mysql_query($sql);
			}

			if(sizeof($write)) {
				$types = implode(", ", $write);

				$sql = <<<SQL
INSERT INTO cms3_permissions (level, owner_id, rel_id)
	SELECT 31, '{$owner_id}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
				l_mysql_query($sql);
			}

			l_mysql_query("COMMIT");
		}

		/**
			* Получить список всех пользователей или групп, имеющих права на страницу $elementId
			* @param Integer $elementId id страницы
			* @param Integer $level = 1 искомый уровень прав
			* @return array массив id пользователей или групп, имеющих права на страницу
		*/
		public function getUsersByElementPermissions($elementId, $level = 1) {
			$elementId = (int) $elementId;
			$level = (int) $level;

			$sql = "SELECT owner_id FROM cms3_permissions WHERE rel_id = '{$elementId}' AND level >= '{$level}'";
			$result = l_mysql_query($sql);

			$owners = array();
			while(list($ownerId) = mysql_fetch_row($result)) {
				$owners[] = (int) $ownerId;
			}

			return $owners;
		}

		/**
		 * Получить список сохраненных прав для страницы $elementId
		 * @param Integer $elementId
		 * @return array $ownerId => $permissionsLevel
		 */

		public function getRecordedPermissions($elementId) {
			$elementId = (int) $elementId;

			$sql = "SELECT owner_id, level FROM cms3_permissions WHERE rel_id = '{$elementId}'";

			$result = l_mysql_query($sql);

			$records = array();
			while(list($ownerId, $level) = mysql_fetch_row($result)) {
				$records[$ownerId] = (int) $level;
			}

			return $records;
		}

		/**
			* Указать права на страницу. Влияет только на текущую сессию, данные в базе изменены не будут
			* @param Integer $elementId id страницы
			* @param Integer $level = 1 уровень прав доступа (0-3).
		*/
		public function pushElementPermissions($elementId, $level = 1) {
			//if(false && array_key_exists($elementId, $this->tempElementPermissions) == false) {
				$this->tempElementPermissions[$elementId] = (int) $level;
			//}
		}

		/**
			* Узнать, авторизован ли текущий пользователь
			* @return Boolean true, если авторизован
		*/
		public function isAuth() {
			return ($this->getUserId() != self::getGuestId());
		}

		/**
			* Позволяет узнать id пользователя "Гостя"
			* @return Integer $guestId id пользователя "Гость"
		*/
		public static function getGuestId() {
			static $guestId;
			if(!$guestId) {
				$guestId = (int) umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
			}
			return $guestId;
		}

		/**
			* Авторизовать клиента как пользователя $userId
			* @param Int|umiObject id пользователя, либо объект пользователя
			* @return Boolean успешность операции
		*/
		public function loginAsUser($userId) {
			if(is_null($userId)) return false;
			if(is_array($userId) && sizeof($userId)) {
				list($userId) = $userId;
			}

			if($userId instanceof iUmiObject) {
				$user = $userId;
				$userId = $user->id;
			} else $user = selector::get('object')->id($userId);
			$this->user_id = $userId;

			$login = $user->login;
			$passwordHash = $user->password;

			if(getRequest('u-login-store')) {
				$time = time() + 31536000;
				setcookie("u-login", $user->login, $time, "/");
				setcookie("u-password-md5", $passwordHash, $time, "/");
			}

			$_SESSION['cms_login'] = $login;
			$_SESSION['cms_pass'] = $passwordHash;
			$_SESSION['user_id'] = $userId;

			return true;
		}

		/**
			* Проверить параметры авторизации
			* @param String login логин
			* @param String password пароль
			* @return NULL|umiObject null, либо пользователь
		*/
		public function checkLogin($login, $password) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('login')->equals($login);
			$sel->where('password')->equals(md5($password));
			$sel->where('is_activated')->equals(true);

			if ($sel->first) return $sel->first;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('e-mail')->equals($login);
			$sel->where('password')->equals(md5($password));
			$sel->where('is_activated')->equals(true);

			return $sel->first;
		}

		public function getPrivileged($perms) {
			if(!sizeof($perms)) return array();

			$sql = 'SELECT owner_id FROM cms_permissions WHERE ';
			$sqls = array();
			foreach($perms as $perm) {
				$module = l_mysql_real_escape_string(getArrayKey($perm, 0));
				$method = l_mysql_real_escape_string($this->getBaseMethodName($module, getArrayKey($perm, 1)));
				$sqls[] = "(module = '{$module}' AND method = '{$method}')";
			}
			$sql .= implode(' OR ', $sqls);

			$result = l_mysql_query($sql);

			$owners = array();
			while(list($ownerId) = mysql_fetch_row($result)) {
				$owners[] = $ownerId;
			}
			$owners = array_unique($owners);
			return $owners;
		}

		protected function isAdminAllowedMethod($module, $method) {
			$methods = array(
			'content' =>    array('json_mini_browser', 'old_json_load_files', 'json_load_files',
							'json_load_zip_folder', 'load_tree_node', 'get_editable_region',
							'save_editable_region', 'widget_create', 'widget_delete',
							'getObjectsByTypeList', 'getObjectsByBaseTypeList',
							'json_get_images_panel', 'json_create_imanager_object',
							'domainTemplates', 'json_unlock_page', 'tree_unlock_page'),
			'backup' =>     array('backup_panel'),
			'data'   =>     array('guide_items', 'guide_items_all', 'json_load_hierarchy_level'),
			'webo' => array('show'),
			'users'  => array('getFavourites', 'json_change_dock', 'saveUserSettings', 'loadUserSettings'),
			'*'		 =>		array('dataset_config')
			);

			if(isset($methods[$module])) {
				if(in_array($method, $methods[$module])) {
					return true;
				}
			}
			if(isset($methods['*'])) {
				if(in_array($method, $methods['*'])) {
					return true;
				}
			}
			return false;
		}

		public function clearCache() {
			$this->elementsCache = array();
			$this->methodsPermissions = array();
		}

	};
?>