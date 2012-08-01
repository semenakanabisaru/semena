<?php
/**
	* Предоставляет доступ к страницам сайта (класс umiHierarchyElement) и методы для управления структурой сайта.
	* Синглтон, экземпляр коллекции можно получить через статический метод getInstance()
*/
	class umiHierarchy extends singleton implements iSingleton, iUmiHierarchy {
		private $elements = array(),
			$objects, $langs, $domains, $templates;

		private $updatedElements = Array();
		private $autocorrectionDisabled = false;
		private $elementsLastUpdateTime = 0;
		private $bForceAbsolutePath = false;
		private $symlinks = Array();
		private $misc_elements = Array();
		private $pathCache = array();
		private $pathPiecesCache = array();
		private $defaultCache = array();
		private $parentsCache = array();
		private $idByPathCache = array();

		public static $ignoreSiteMap = false;

		/**
			* Конструктор
		*/
		protected function __construct() {
			$this->objects		=	umiObjectsCollection::getInstance();
			$this->langs		=	langsCollection::getInstance();
			$this->domains		=	domainsCollection::getInstance();
			$this->templates	=	templatesCollection::getInstance();

			if(regedit::getInstance()->getVal("//settings/disable_url_autocorrection")) {
				$this->autocorrectionDisabled = true;
			}
		}

		/**
			* Получить экземпляр коллекции
			* @return umiHierarchy экземпляр класса umiHierarchy
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Проверяет, существует ли страница (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id странциы
			* @return Boolean true если существует
		*/
		public function isExists($element_id) {
			if($this->isLoaded($element_id)) {
				return true;
			} else {
				$element_id = (int) $element_id;

				$sql = "SELECT id FROM cms3_hierarchy WHERE id = '{$element_id}'";
				$result = l_mysql_query($sql);

				list($count) = mysql_fetch_row($result);
				return (bool) $count;
			}
		}

		/**
			* Проверяет, загружена ли в память страница (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id странциы
			* @return Boolean true если экземпляр класса umiHierarchyElement с id $element_id уже загружен в память
		*/
		public function isLoaded($element_id) {
			if($element_id === false) {
				return false;
			}

			if(is_array($element_id)) {
				$is_loaded = true;

				foreach($element_id as $celement_id) {
					if(!array_key_exists($celement_id, $this->elements)) {
						$is_loaded = false;
						break;
					}
				}

				return $is_loaded;
			} else {
				return (bool) array_key_exists($element_id, $this->elements);
			}
		}

		/**
			* Получить экземпляр страницы (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id страницы
			* @param Boolean $ignorePermissions=false игнорировать права доступа при получении экземпляра страницы
			* @param Boolean $ignoreDeleted=false игнорировать состояние удаленности (т.е. возможность получить удаленную страницу)
			* @return umiHierarchyElement экземпляр страницы, либо false если нельзя получить экземпляр
		*/
		public function getElement($element_id, $ignorePermissions = false, $ignoreDeleted = false, $row = false) {
			if(!$element_id) {
				return false;
			}
			if($row === false && !$ignorePermissions && !$this->isAllowed($element_id)) return false;
			$cacheFrontend = cacheFrontend::getInstance();

			if($this->isLoaded($element_id)) {
				return $this->elements[$element_id];
			} else {
				$element = $cacheFrontend->load($element_id, "element");
				if($element instanceof iUmiHierarchyElement == false) {
					try {
						$element = new umiHierarchyElement($element_id, $row);

						$cacheFrontend->save($element, "element");
					} catch (privateException $e) {
						return false;
					}
				}
				$this->misc_elements[] = $element_id;


				if(is_object($element)) {

					if($element->getIsBroken()) return false;
					if($element->getIsDeleted() && !$ignoreDeleted) return false;

					$this->pushElementsLastUpdateTime($element->getUpdateTime());
					$this->elements[$element_id] = $element;
					return $this->elements[$element_id];
				} else return false;
			}
		}

		/**
			* Удалить страницу с id $element_id
			* @param Integer $element_id id страницы
			* @return Boolean true, если удалось удалить страницу
		*/
		public function delElement($element_id) {
			$this->disableCache();
			$cacheFrontend = cacheFrontend::getInstance();
			$permissions = permissionsCollection::getInstance();

			$this->addUpdatedElementId($element_id);
			$this->forceCacheCleanup();

			if(!$permissions->isAllowedObject($permissions->getUserId(), $element_id)) return false;

			if($element = $this->getElement($element_id)) {
				$sql = "SELECT id FROM cms3_hierarchy FORCE INDEX(rel) WHERE rel = '{$element_id}'";
				$result = l_mysql_query($sql);

				while(list($child_id) = mysql_fetch_row($result)) {
					$child_element = $this->getElement($child_id, true, true);
					$this->delElement($child_id);
					$cacheFrontend->del($child_id, "element");
				}


				$element->setIsDeleted(true);
				$element->commit();
				unset($this->elements[$element_id]);

				$cacheFrontend->del($element_id, "element");
				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать виртуальную копию (подобие symlink в файловых системах) страницы $element_id
			* @param Integer $element_id id страницы, которую необходимо скопировать
			* @param Integer $rel_id id страницы, которая будет являться родителем созданной копии
			* @param Boolean $copySubPages=false если у копируемой страницы есть потомки, то если true они будут скопированы рекурсивно
			* @return Integer id новой виртуальной копии страницы, либо false
		*/
		public function copyElement($element_id, $rel_id, $copySubPages = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			$this->misc_elements[] = $rel_id;
			$this->misc_elements[] = $element_id;

			$this->forceCacheCleanup();

			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				$rel_id = (int) $rel_id;
				$timestamp = self::getTimeStamp();

				if($element = $this->getElement($element_id)) {
					$this->misc_elements[] = $element->getParentId();
				}

				$res = mysql_fetch_array(l_mysql_query('SELECT MAX(ord) FROM cms3_hierarchy', true));
				$ord = $res[0]+1;
				$sql = <<<SQL

INSERT INTO cms3_hierarchy
	(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
		SELECT '{$rel_id}', type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, '{$timestamp}', '{$ord}'
				FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1
SQL;
				l_mysql_query($sql);

				$old_element_id = $element_id;
				$element_id = l_mysql_insert_id();

				//Copy permissions

				$sql = <<<SQL

INSERT INTO cms3_permissions
	(level, owner_id, rel_id)
		SELECT level, owner_id, '{$element_id}' FROM cms3_permissions WHERE rel_id = '{$old_element_id}'

SQL;
				l_mysql_query($sql);


				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();

					$this->buildRelationNewNodes($element_id);

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->copyElement($child_id, $element_id, true);
						}
					}

					$this->misc_elements[] = $element_id;

					return $element_id;
				} else return false;
			} else return false;
		}


		/**
			* Создать копию страницы $element_id вместе со всеми данными
			* @param Integer $element_id id страницы, которую необходимо скопировать
			* @param Integer $rel_id id страницы, которая будет являться родителем созданной копии
			* @param Boolean $copySubPages=false если у копируемой страницы есть потомки, то если true они будут скопированы рекурсивно
			* @return Integer id новой копии страницы, либо false
		*/
		public function cloneElement($element_id, $rel_id, $copySubPages = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			$this->misc_elements[] = $rel_id;
			$this->misc_elements[] = $element_id;

			$this->forceCacheCleanup();

			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				if($element = $this->getElement($element_id)) {
					$ord = (int) $element->getOrd();
				}



				$this->misc_elements[] = $element->getParentId();

				$res = mysql_fetch_array(l_mysql_query('SELECT MAX(ord) FROM cms3_hierarchy', true));
				$ord = $res[0] + 1;

				$object_id = $element->getObject()->getId();

				$sql = <<<SQL
INSERT INTO cms3_objects
	(name, is_locked, type_id, owner_id)
		SELECT name, is_locked, type_id, owner_id
			FROM cms3_objects
				WHERE id = '{$object_id}'
SQL;
				l_mysql_query($sql);

				$new_object_id = l_mysql_insert_id();

				$object_type = umiObjectsCollection::getInstance()->getObject($object_id)->getTypeId();
				$table_content = umiBranch::getBranchedTableByTypeId($object_type);

				$sql = <<<SQL
INSERT INTO {$table_content}
	(field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, obj_id)
		SELECT field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, '{$new_object_id}'
			FROM {$table_content}
				WHERE obj_id = '{$object_id}'
SQL;
				l_mysql_query($sql);

				$timestamp = self::getTimeStamp();

				$sql = <<<SQL

INSERT INTO cms3_hierarchy
	(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
		SELECT '{$rel_id}', type_id, lang_id, domain_id, tpl_id, '{$new_object_id}', alt_name, is_active, is_visible, is_deleted, '{$timestamp}', '{$ord}'
				FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1
SQL;
				l_mysql_query($sql);


				$old_element_id = $element_id;

				$element_id = l_mysql_insert_id();


				//Copy permissions
				$sql = <<<SQL

INSERT INTO cms3_permissions
	(level, owner_id, rel_id)
		SELECT level, owner_id, '{$element_id}' FROM cms3_permissions WHERE rel_id = '{$old_element_id}'

SQL;
				l_mysql_query($sql);

				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();

					$this->buildRelationNewNodes($element_id);

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->cloneElement($child_id, $element_id, true);
						}
					}

					$this->misc_elements[] = $element_id;

					return $element_id;
				} else  return false;
			}
		}

		/**
			* Получить список удаленных страниц (страниц в корзине)
			* @return Array массив, состоящий из id удаленных страниц
		*/
		public function getDeletedList() {

			$res = array();
			$tmp = array();

			$sql = <<<SQL
SELECT id, rel FROM cms3_hierarchy WHERE is_deleted = '1' ORDER BY updatetime DESC
SQL;
			$result = l_mysql_query($sql);

			while(list($id, $rel) = mysql_fetch_row($result)) {

				$tmp[$id] = $rel;

				$keys = array_keys($tmp, $id);
				if (count($keys)) {
					foreach ($keys as $key) {
						if (array_key_exists($key, $res)) {
							unset($res[$key]);
						}
					}
				}

				if(array_key_exists($rel, $tmp)) {
					continue;
				}

				$res[$id] = $id;

			}

			return array_values($res);
		}

		/**
			* Восстановить страницу из корзины
			* @param id $element_id страницы, которую необходимо восстановить из корзины
			* @return Boolean true, если удалось
		*/
		public function restoreElement($element_id) {
			$this->disableCache();

			if($element = $this->getElement($element_id, false, true)) {
				$element->setIsDeleted(false);
				$element->setAltName($element->getAltName());
				$element->commit();

				$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$element_id}'";
				$result = l_mysql_query($sql);

				while(list($child_id) = mysql_fetch_row($result)) {
					$child_element = $this->getElement($child_id, true, true);
					$this->restoreElement($child_id);
				}
				return true;
			} else return false;
		}

		/**
			* Удалить из корзины страницу $element_id (и из БД)
			* @param Integer $element_id id страницы, которую будем удалять
			* @return Boolean true в случае успеха
		*/
		public function removeDeletedElement($element_id, &$deleted_count = 0) {
			$this->disableCache();

			if($element = $this->getElement($element_id, true, true)) {
				if($element->getIsDeleted()) {
					$element_id = (int) $element_id;
					$object_id = $element->getObjectId();
					$objects = umiObjectsCollection::getInstance();

					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$element_id}'";
					$result = l_mysql_query($sql);

					while(list($child_id) = mysql_fetch_row($result)) {
						$child_element = $this->getElement($child_id, true, true);
						$child_element->setIsDeleted(true);
						$child_element->commit();
						$this->removeDeletedElement($child_id, $deleted_count);
					}

					$sql = "DELETE FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1";
					l_mysql_query($sql);

					unset($element);
					unset($this->elements[$element_id]);

					//TODO: Make object delete here, if no hierarchy links exist.
					$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE obj_id = '{$object_id}'";
					$result = l_mysql_query($sql, true);

					if(list($c) = mysql_fetch_row($result)) {
						if($c == 0) {
							$objects->delObject($object_id);
						}
					}

					$this->earseRelationNodes($element_id);

					$deleted_count ++;

					return true;
				} else return false;
			} else return false;
		}

		/**
			* Удалить страницы из корзины (т.е. и из БД)
			* @return Boolean true в случае успеха
		*/
		public function removeDeletedAll() {
			$this->disableCache();

			l_mysql_query("START TRANSACTION /* umiHierarchy::removeDeletedAll() */");

			$sql = "SELECT id FROM cms3_hierarchy WHERE is_deleted = '1'";
			$result = l_mysql_query($sql);

			while(list($element_id) = mysql_fetch_row($result)) {
				$this->removeDeletedElement($element_id);
			}

			l_mysql_query("COMMIT");

			return true;
		}


		/**
			* Удалить страницы из корзины (т.е. и из БД). Аналог removeDeletedAll, но с ограничением $limit
			* @return Int количество элементов вместе с детьми
		*/
		public function removeDeletedWithLimit($limit = false) {
			if(empty($limit)) {
				$limit = 100;
			}

			$this->disableCache();

			l_mysql_query("START TRANSACTION /* umiHierarchy::removeDeletedWithLimit() */");

			$sql = "SELECT id FROM cms3_hierarchy WHERE is_deleted = '1' LIMIT ".$limit;
			$result = l_mysql_query($sql);

			$deleted_count = 0;
			while(list($element_id) = mysql_fetch_row($result)) {
				$this->removeDeletedElement($element_id, $deleted_count);
			}

			l_mysql_query("COMMIT");

			return $deleted_count;
		}

		/**
			* Получить id родительской страницы для $element_id
			* @param Integer $element_id id страницы
			* @return Integer id родительской страницы, либо false
		*/
		public function getParent($element_id) {
			$element_id = (int) $element_id;

			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$element_id}'";
			$result = l_mysql_query($sql, true);

			if(mysql_num_rows($result)) {
				list($parent_id) = mysql_fetch_row($result);

				$this->misc_elements[] = $parent_id;
				mysql_freeresult($result);
				return (int) $parent_id;
			} else {
				mysql_freeresult($result);
				return false;
			}
		}

		/**
			* Получить список всех родительских страниц
			* @param Integer $element_id id страницы, родителей которой необходимо получить
			* @param Boolean $include_self=false включить в результат саму страницу $element_id
			* @param Boolean $ignoreCache = false не использовать микрокеширование
			* @return Array массив, состоящий из id родиткельских страниц
		*/
		public function getAllParents($element_id, $include_self = false, $ignoreCache = false) {
			$cacheFrontend = cacheFrontend::getInstance();
			$element_id = (int) $element_id;
			$parents = array();

			$cacheData = $cacheFrontend->loadSql('hierarchy_parents');
			if(is_array($cacheData) && sizeof($cacheData)) {
				$this->parentsCache = $cacheData;
			}

			if(!$ignoreCache && isset($this->parentsCache[$element_id])) {
				$parents = $this->parentsCache[$element_id];
			} else {
				$sql = "SELECT rel_id FROM cms3_hierarchy_relations WHERE child_id = '{$element_id}' ORDER BY id";
				$result = l_mysql_query($sql);

				while(list($parent_id) = mysql_fetch_row($result)) {
					$parents[] = (int) $parent_id;
				}
				$this->parentsCache[$element_id] = $parents;
				$cacheFrontend->saveSql('hierarchy_parents', $this->parentsCache, 120);
			}
			if($include_self) {
				$parents[] = (int) $element_id;
			}
			return $parents;
		}

		/**
			* Получить список дочерних страниц по отношению к $element_id
			* @param Integer $element_id id страницы, у которой нужно взять всех потомков
			* @param Boolean $allow_unactive=true если true, то в результат будут включены неактивные страницы
			* @param Boolean $allow_unvisible=true если true, то в результат будут включены невидимые в меню страницы
			* @param Integer $depth=0 глубина поиска
			* @param Boolean $hierarchy_type_id=false включить в результат только страницы с указанным id базового типа (umiHierarchyType)
			* @param Integer $domainId=false указать id домена (актуально если ишем от корня: $element_id = 0)
			* @return Array рекурсивный ассоциотивный массив, где ключ это id страницы, значение - массив детей
		*/
		public function getChilds($element_id, $allow_unactive = true, $allow_unvisible = true, $depth = 0, $hierarchy_type_id = false, $domainId = false) {
			$cacheFrontend = cacheFrontend::getInstance();
			$cmsController = cmsController::getInstance();

			$element_id = (int) $element_id;
			$allow_unactive = (int) $allow_unactive;
			$allow_unvisible = (int) $allow_unvisible;
			$hierarchy_type_id = (int) $hierarchy_type_id;

			$lang_id = $cmsController->getCurrentLang()->getId();
			$domain_id = ($domainId) ? $domainId : $cmsController->getCurrentDomain()->getId();
			$domain_cond = ($element_id > 0) ? "" : " AND h.domain_id = '{$domain_id}'";

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$isUserSuperVisor = $permissions->isSv($userId);
			$permissionsSql = $permissions->makeSqlWhere($userId);

			$res = array();

			$s_element_id = ($element_id) ? "= '{$element_id}'" : "IS NULL";
			$sql = "SELECT hr.child_id, h.rel, cp.level FROM cms3_hierarchy_relations hr, cms3_permissions cp, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			if($isUserSuperVisor) {
				$sql = "SELECT hr.child_id, h.rel, 2 FROM cms3_hierarchy_relations hr, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			}


			if(!$allow_unactive)	$sql .= " AND h.is_active = '1'";
			if(!$allow_unvisible)	$sql .= " AND h.is_visible = '1'";
			if($hierarchy_type_id) $sql .= " AND h.type_id = '{$hierarchy_type_id}'";

			if(!$isUserSuperVisor) {
				$sql .= " AND (cp.rel_id = h.id AND {$permissionsSql} AND cp.level > 0)";
			}

			if($depth) {
				if($element_id) {
					$result = l_mysql_query("SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$element_id}");
					if(mysql_num_rows($result)) {
						list($level) = mysql_fetch_row($result);
						$level += $depth;
					} else {
						return false;
					}
				} else {
					$level = $depth;
				}
				$sql .= " AND hr.level <= '{$level}'";
			}

			$sql .= " ORDER BY hr.level, h.ord";

			if($res = $cacheFrontend->loadSql($sql)) {
				foreach($res[1] as $elementId => $level) {
					$permissions->pushElementPermissions($elementId, $level);
				}
				return $res[0];
			}

			$result = l_mysql_query($sql);

			$flat_childs = $perms_list = $res = array();
			while(list($child_id, $rel_id, $level) = mysql_fetch_row($result)) {
				$permissions->pushElementPermissions($child_id, $level);

				$flat_childs[$child_id] = array();
				if($rel_id == $element_id) {
					$res[$child_id] = &$flat_childs[$child_id];
				}
				if(isset($flat_childs[$rel_id])) {
					$flat_childs[$rel_id][$child_id] = &$flat_childs[$child_id];
				}
				$perms_list[$child_id] = $level;
			}

			$cacheFrontend->saveSql($sql, array($res, $perms_list), 60);
			return $res;
		}

		public function getChildIds($element_id, $allow_unactive = true, $allow_unvisible = true, $hierarchy_type_id = false, $domainId = false, $include_self = false) {

			$childIds = array();
			if($include_self) $childIds[] = $element_id;
			$childs = $this->getChilds($element_id, $allow_unactive, $allow_unvisible, 1, $hierarchy_type_id, $domainId);
			foreach ($childs as $childId => $value) {
				$childIds = array_merge($childIds, $this->getChildIds($childId, $allow_unactive, $allow_unvisible, $hierarchy_type_id, $domainId, true));
			}
			$childIds = array_unique($childIds);
			return $childIds;

		}

		/**
			* Получить количество дочерних страниц по отношению к $element_id
			* @param Integer $element_id id страницы, у которой нужно взять всех потомков
			* @param Boolean $allow_unactive=true если true, то в результат будут включены неактивные страницы
			* @param Boolean $allow_unvisible=true если true, то в результат будут включены невидимые в меню страницы
			* @param Integer $depth=0 глубина поиска
			* @param Boolean $hierarchy_type_id=false включить в результат только страницы с указанным id базового типа (umiHierarchyType)
			* @param Integer $domainId=false указать id домена (актуально если ишем от корня: $element_id = 0)
			* @return Integer количество детей
		*/
		public function getChildsCount($element_id, $allow_unactive = true, $allow_unvisible = true, $depth = 0, $hierarchy_type_id = false, $domainId = false) {
			$element_id = (int) $element_id;
			$allow_unactive = (int) $allow_unactive;
			$allow_unvisible = (int) $allow_unvisible;
			$hierarchy_type_id = (int) $hierarchy_type_id;

			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = ($domainId) ? $domainId : cmsController::getInstance()->getCurrentDomain()->getId();
			if($element_id) {
				$element = $this->getElement($element_id, true);
				if($element instanceof umiHierarchyElement) {
					$lang_id = $element->getLangId();
					$domain_id = $element->getDomainId();
				}
			}
			$domain_cond = ($element_id > 0) ? "" : " AND h.domain_id = '{$domain_id}'";

			$res = array();

			$s_element_id = ($element_id) ? "= '{$element_id}'" : "IS NULL";
			$sql = "SELECT COUNT(hr.child_id) FROM cms3_hierarchy_relations hr, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			if(!$allow_unactive)	$sql .= " AND h.is_active = '1'";
			if(!$allow_unvisible)	$sql .= " AND h.is_visible = '1'";
			if($hierarchy_type_id) $sql .= " AND h.type_id = '{$hierarchy_type_id}'";
			if($depth) {
				if($element_id) {
					list($level) = mysql_fetch_row(l_mysql_query("SELECT level FROM cms3_hierarchy_relations WHERE child_id = '{$element_id}'"));
					$level = $depth + $level;
				} else {
					$level = 1;
				}
				$sql .= " AND hr.level <= '{$level}'";
			}

			$sql .= " ORDER BY hr.level, h.ord";

			$result = l_mysql_query($sql);

			if(mysql_num_rows($result)) {
				list($count) = mysql_fetch_row($result);
				return $count;
			} else {
				return false;
			}
		}


		/**
			* Переключить режим генерации урлов между относительным и полным (влючать адрес домена даже если он совпадает с текущим доменом)
			* @param Boolean $bIsForced=true true - режим полных урлов, false - обычный режим
			* @return Boolean предыдущее значение
		*/
		public function forceAbsolutePath($bIsForced = true) {
			$bOldValue = $this->bForceAbsolutePath;
			$this->bForceAbsolutePath = (bool) $bIsForced;
			return $bOldValue;
		}

		/**
			* Получить адрес страницы по ее id
			* @param id $element_id страницы, путь которой нужно получить
			* @param Boolean $ignoreLang=false не подставлять языковой префикс к адресу страницы
			* @param Boolean $ignoreIsDefaultStatus=false игнорировать статус страницы "по умолчанию" и сформировать для не полный путь
			* @param Boolean $ignoreCache игнорировать кеш
			* @return String адрес страницы
		*/
		public function getPathById($element_id, $ignoreLang = false, $ignoreIsDefaultStatus = false, $ignoreCache = false) {
			$element_id = (int) $element_id;

			if(!$ignoreCache && isset($this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath])) return $this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath];
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$cacheFrontend = cacheFrontend::getInstance();
			$domains = domainsCollection::getInstance();
			$langs = langsCollection::getInstance();

			$pre_lang = $cmsController->pre_lang;

			$url_prefix = $cmsController->getUrlPrefix();

			if($element = $hierarchy->getElement($element_id, true)) {
				$current_domain = $cmsController->getCurrentDomain();
				$element_domain_id = $element->getDomainId();

				if(!$this->bForceAbsolutePath && $current_domain->getId() == $element_domain_id) {
					$domain_str = "";
				} else {
					$domain_str = "http://" . $domains->getDomain($element_domain_id)->getHost();
				}

				$element_lang_id = intval($element->getLangId());
				$element_lang = $langs->getLang($element_lang_id);

				$b_lang_default = ($element_lang_id === intval($cmsController->getCurrentDomain()->getDefaultLangId()));

				if(!$element_lang || $b_lang_default || $ignoreLang == true) {
					$lang_str = "";
				} else {
					$lang_str = "/" . $element_lang->getPrefix();
				}

				if($element->getIsDefault() && !$ignoreIsDefaultStatus) {
					return $this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = $domain_str . $lang_str . $url_prefix . '/';
				}
			} else {
				return $this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = "";
			}

			if($parents = $this->getAllParents($element_id, false, $ignoreCache)) {
				$path = $domain_str . $lang_str.$url_prefix;

				$parents[] = $element_id;

				$toLoad = array();

				foreach($parents as $parentId) {
					if($parentId == 0) continue;
					if(isset($this->pathPiecesCache[$parentId])) continue;
					if($this->isLoaded($parentId) && $parent = $this->getElement($parentId, true)) {
						$this->pathPiecesCache[$parentId] = $parent->getAltName();
					} else {
						$toLoad[] = $parentId;
					}
				}

				if(count($toLoad)) {
					$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE id IN (" . implode(", ", $toLoad) . ")";

					$altNames = !$ignoreCache ? $cacheFrontend->loadSql($sql) : null;
					if(!is_array($altNames)) {
						$result = l_mysql_query($sql);

						$altNames = array();
						while(list($id, $altName) = mysql_fetch_row($result)) {
							$altNames[$id] = $altName;
							$this->pathPiecesCache[$id] = $altName;
						}
						$cacheFrontend->saveSql($sql, $altNames, 600);
					}
					else {
						$this->pathPiecesCache = $this->pathPiecesCache + $altNames;
				}
				}

				$sz = sizeof($parents);
				for($i = 0; $i < $sz; $i++) {
					if(!$parents[$i]) continue;

					if(isset($this->pathPiecesCache[$parents[$i]])) {
						$path .= "/" . $this->pathPiecesCache[$parents[$i]];
					}
				}
				$path .= "/";

				return $this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = $path;
			} else {
				return $this->pathCache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = false;
			}

		}

		/**
			* Получить id страницы по ее адресу
			* @param String $element_path
			* @param Boolean $show_disabled = false
			* @param Integer $errors_count = 0 ссылка на переменную, в которую записывается количество несовпадений при разборе адреса
			* @param Integer $domain_id = false идентификатор домена
			* @param Integer $lang_id = false идентификатор языка
			* @return Integer id страницы, либо false
		*/
		public function getIdByPath($element_path, $show_disabled = false, &$errors_count = 0, $domain_id = false, $lang_id = false) {
			$lang_id = (int) $lang_id;
			$domain_id = (int) $domain_id;
			$element_path = trim($element_path, "\/ \n");

			$cmsController = cmsController::getInstance();
			if(empty($lang_id)) {
			$lang_id = $cmsController->getCurrentLang()->getId();
			}

			if(empty( $domain_id)) {
			$domain_id = $cmsController->getCurrentDomain()->getId();
			}

			$element_hash = md5( $domain_id.":".$lang_id.":".$element_path );

			if(isset($this->idByPathCache[$element_hash])) {
				return $this->idByPathCache[$element_hash];
			}

			$cacheFrontend = cacheFrontend::getInstance();
			if($id = $cacheFrontend->loadSql($element_hash . "_path")) {
				return $id;
			}

			if($element_path == "") {
				return $this->idByPathCache[$element_hash] = $this->getDefaultElementId($lang_id, $domain_id);
			}

			$domains = domainsCollection::getInstance();
			$paths = explode("/", $element_path);
			$sz = sizeof($paths);
			$id = 0;
			for($i = 0; $i < $sz; $i++) {
				$alt_name = $paths[$i];
				$alt_name = l_mysql_real_escape_string($alt_name);

				if($i == 0) {
					if($element_domain_id = $domains->getDomainId($alt_name)) {
						$domain_id = $element_domain_id;
						continue;
					}
				}


				if($show_disabled) {
					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}' AND alt_name = '{$alt_name}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				} else {
					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}' AND alt_name = '{$alt_name}' AND is_active='1' AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				}

				$result = l_mysql_query($sql);

				if(!mysql_num_rows($result)) {
					if($show_disabled) {
						$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE rel = '{$id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
					} else {
						$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE rel = '{$id}' AND is_active = '1' AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
					}
					$result = l_mysql_query($sql);

					$max = 0;
					$temp_id = 0;
					$res_id = 0;
					while(list($temp_id, $cstr) = mysql_fetch_row($result)) {
						if($this->autocorrectionDisabled) {
							if($alt_name == $cstr) {
								$res_id = $temp_id;
							}
						} else {
							$temp = umiHierarchy::compareStrings($alt_name, $cstr);
							if($temp > $max) {
								$max = $temp;
								$res_id = $temp_id;

								++$errors_count;
							}
						}
					}

					if($max > 75) {
						$id = $res_id;
					} else {
						return $this->idByPathCache[$element_hash] = false;
					}
				} else {
					if(!(list($id) = mysql_fetch_row($result))) {
						return $this->idByPathCache[$element_hash] = false;
					}
				}
			}

			$cacheFrontend->saveSql($element_hash . "_path", $id, 3600);

			return $this->idByPathCache[$element_hash] = $id;
		}

		/**
			* Добавить новую страницу
			* @param Interget $rel_id id родительской страницы
			* @param Integer $hierarchy_type_id id иерархического типа (umiHierarchyType)
			* @param String $name название старницы
			* @param String $alt_name псевдостатический адрес (если не передан, то будет вычислен из $name)
			* @param Integer $type_Id = false id типа данных (если не передан, то будет вычислен из $hierarchy_type_id)
			* @param Integer $domain_id = false id домена (имеет смысл только если $rel_id = 0)
			* @param Integer $lang_id = false id языковой версии (имеет смысл только если $rel_id = 0)
			* @param Integer $tpl_id = false id шаблона, по которому будет выводится страница
			* @return Integer id созданной страницы, либо false
		*/
		public function addElement($rel_id, $hierarchy_type_id, $name, $alt_name, $type_id = false, $domain_id = false, $lang_id = false, $tpl_id = false) {
			$this->disableCache();

			if($type_id === false) {
				if($hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id)) {
					$type_id = umiObjectTypesCollection::getInstance()->getBaseType($hierarchy_type->getName(), $hierarchy_type->getExt());

					if(!$type_id) {
						throw new coreException("There is no base object type for hierarchy type #{$hierarchy_type_id}");
						return false;
					}
				} else {
					throw new coreException("Wrong hierarchy type id given");
					return false;
				}
			} else {
				$object_type = umiObjectTypesCollection::getInstance()->getType($type_id);
				if (!$object_type) {
					throw new coreException("Wrong object type id given");
 					return false;
 				}
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
				$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);
			}

			$parent = null;

			if($domain_id === false) {
				if($rel_id == 0) {
					$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
				} else {
					$parent = $this->getElement($rel_id, true, true);
					$domain_id = $parent->getDomainId();
				}
			}

			if($lang_id === false) {
				if($rel_id == 0) {
					$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
				} else {
					if(!$parent) $parent = $this->getElement($rel_id, true, true);
					$lang_id = $parent->getLangId();
				}
			}

			if($tpl_id === false) {
				$tpl_id = templatesCollection::getInstance()->getHierarchyTypeTemplate($hierarchy_type->getName(), $hierarchy_type->getExt());
				if($tpl_id === false) {
					$tpl_id = $this->getDominantTplId($rel_id);
					if(!$tpl_id) {
						$tpl = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
						if (!$tpl instanceof template) throw new coreException("Failed to detect default template");
						$tpl_id = $tpl->getId();

					}
				}
			}

			if($rel_id) {
				$this->addUpdatedElementId($rel_id);
			} else {
				$this->addUpdatedElementId($this->getDefaultElementId());
			}

			if($object_id = $this->objects->addObject($name, $type_id)) {


				$sql = "INSERT INTO cms3_hierarchy (rel, type_id, domain_id, lang_id, tpl_id, obj_id) VALUES('{$rel_id}', '{$hierarchy_type_id}', '{$domain_id}', '{$lang_id}', '{$tpl_id}', '{$object_id}')";
				l_mysql_query($sql);

				$element_id = l_mysql_insert_id();

				$element = $this->getElement($element_id, true);

				$element->setAltName($alt_name);

				$sql = "SELECT MAX(ord) FROM cms3_hierarchy WHERE rel = '{$rel_id}'";
				$result = l_mysql_query($sql);

				if(list($ord) = mysql_fetch_row($result)) {
					$element->setOrd( ($ord + 1) );
				}

				$element->commit();

				$this->elements[$element_id] = $element;

				$this->addUpdatedElementId($rel_id);
				$this->addUpdatedElementId($element_id);

				if($rel_id) {

					$parent_element = $this->getElement($rel_id);
					if($parent_element instanceof umiHierarchyElement) {
						$object_instances = $this->getObjectInstances($parent_element->getObject()->getId());

						if(sizeof($object_instances) > 1) {
							foreach($object_instances as $symlink_element_id) {
								if($symlink_element_id == $rel_id) continue;
								$this->symlinks[] = array($element_id, $symlink_element_id);
							}
						}
					}
				}
				$this->misc_elements[] = $element_id;

				$this->buildRelationNewNodes($element_id);
				return $element_id;
			} else {
				throw new coreException("Failed to create new object for hierarchy element");
				return false;
			}
		}


		/**
			* Получить идентификатор страницы со статусом "по умолчан0ию" (главная страница) для указанного домена и языка
			* @param Integer $lang_id = false id языковой версии, если не указан, берется текущий язык
			* @param Integer $domain_id = false id домена, если не указан, берется текущий домен
			* @return Integer id страницы по умолчанию, либо false
		*/

		public function getDefaultElementId($lang_id = false, $domain_id = false) {
			$cacheFrontend = cacheFrontend::getInstance();
			$cmsController = cmsController::getInstance();

			if(empty($this->defaultCache)) {
				$cacheData = $cacheFrontend->loadData('default_pages');
				if(is_array($cacheData)) {
					$this->defaultCache = $cacheData;
				}
			}

			if($lang_id === false) $lang_id = $cmsController->getCurrentLang()->getId();
			if($domain_id === false) $domain_id = $cmsController->getCurrentDomain()->getId();

			if(isset($this->defaultCache[$lang_id][$domain_id])) {
				return $this->defaultCache[$lang_id][$domain_id];
			}

			$sql = "SELECT id FROM cms3_hierarchy WHERE is_default = '1' AND is_deleted='0' AND is_active='1' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
			$result = l_mysql_query($sql);

			if(list($element_id) = mysql_fetch_row($result)) {
				$this->defaultCache[$lang_id][$domain_id] = $element_id;

				$cacheFrontend->saveData('default_pages', $this->defaultCache, 3600);

				return $this->defaultCache[$lang_id][$domain_id];
			} else {
				return false;
			}
		}


		public static function compareStrings($str1, $str2) {
			return	100 * (
				similar_text($str1, $str2) / (
					(strlen($str1) + strlen($str2))
				/ 2)
			);
		}

		/**
			* Конвертирует псевдостатический адрес в транслит и убирает недопостимые символы
			* @param String $alt_name псевдостатический url
			* @return String результат транслитерации
		*/
		public static function convertAltName($alt_name, $separator = false) {
			$config = mainConfiguration::getInstance();
			if (!$separator) $separator = $config->get('seo', 'alt-name-separator') ? $config->get('seo', 'alt-name-separator') : "_";
			$alt_name = translit::convert($alt_name, $separator);
			$alt_name = preg_replace("/[\?\\\\&=]+/", "_", $alt_name);
			$alt_name = preg_replace("/[_\/]+/", "_", $alt_name);
			return $alt_name;
		}

		/**
			* Получить текущий UNIX TIMESTAMP
			* @return Integer текущий unix timestamp
		*/
		public static function getTimeStamp() {
			return time();
		}


		/**
			* Переместить страницу $element_id в страницу $rel_id перед страницей $before_id
			* @param Integer $element_id id перемещаемой страницы
			* @param Integer $rel_id id новой родительской страницы
			* @param Integer $before_id = false id страницы, перед которой нужно разместить страницу $element_id. Если false, поместить страницу в конец списка
			* @return Boolean true, если успешно
		*/
		public function moveBefore($element_id, $rel_id, $before_id = false) {
			$this->disableCache();

			if(!$this->isExists($element_id)) return false;

			$element = umiHierarchy::getInstance()->getElement($element_id);

			$lang_id = $element->getLangId();
			$domain_id = $element->getDomainId();
			$oldElementParentId = $element->getRel();

			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;

			$element->setRel($rel_id);
			$element->commit();

			// apply default template if need for all descendants
			$iCurrTplId = $element->getTplId();
			$arrTpls = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
			$bNeedChangeTpl = true;
			foreach($arrTpls as $oTpl) {
				if ($oTpl->getId() == $iCurrTplId) {
					$bNeedChangeTpl = false;
					break;
				}
			}

			if ($bNeedChangeTpl) {
				$oDefaultTpl = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
				if ($oDefaultTpl) {
					$iDefaultTplId = $oDefaultTpl->getId();

					// get all descendants id's
					$oSel = new umiSelection;
					$oSel->addHierarchyFilter($element_id, 100);

					$arrDescendantsIds = umiSelectionsParser::runSelection($oSel);
					$arrDescendantsIds[] = $element_id;
					$sDIds = implode(",", $arrDescendantsIds);

					$sql = "UPDATE cms3_hierarchy SET tpl_id = '{$iDefaultTplId}' WHERE id IN (".$sDIds.")";
				}
			}

			if($before_id) {
				$before_id = (int) $before_id;

				$sql = "SELECT ord FROM cms3_hierarchy WHERE id = '{$before_id}'";
				$result = l_mysql_query($sql, true);

				if(list($ord) = mysql_fetch_row($result)) {
					$ord = (int) $ord;
					$sql = "UPDATE cms3_hierarchy SET ord = (ord + 1) WHERE rel = '{$rel_id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND ord >= {$ord}";
					l_mysql_query($sql);

					$sql = "UPDATE cms3_hierarchy SET ord = '{$ord}', rel = '{$rel_id}' WHERE id = '{$element_id}'";
					l_mysql_query($sql);

					$this->rewriteElementAltName($element_id);
					$this->rebuildRelationNodes($element_id);

					$this->addUpdatedElementId($element_id);

					return true;
				} else return false;
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_hierarchy WHERE rel = '{$rel_id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				$result = l_mysql_query($sql);

				if(list($ord) = mysql_fetch_row($result)) {
					++$ord;
				} else {
					$ord = 1;
				}

				$sql = "UPDATE cms3_hierarchy SET ord = '{$ord}', rel = '{$rel_id}' WHERE id = '{$element_id}'";
				l_mysql_query($sql);

				$this->rewriteElementAltName($element_id);
				$this->rebuildRelationNodes($element_id);

				$this->addUpdatedElementId($element_id);

				return true;
			}

		}


		/**
			* Переместить страницу $element_id под страницу с $rel_id в начало списка детей
			* @param Integer $element_id id перемещаемой страницы
			* @param Integer $rel_id id новой родительской страницы
			* @return Boolean true в случае успеха
		*/
		public function moveFirst($element_id, $rel_id) {
			$this->disableCache();

			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;

			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$rel_id}' ORDER BY ord ASC";
			$result = l_mysql_query($sql, true);

			list($before_id) = mysql_fetch_row($result);
			return $this->moveBefore($element_id, $rel_id, $before_id);
		}

		/**
			* Проверить, есть ли права на чтение страницы $elementId для текущего пользователя
			* @param Integer $element_id id страницы, которую нужно проверить
			* @return Boolean true если есть доступ на чтение, false если доступа нету
		*/
		protected function isAllowed($elementId) {
			$permissions = permissionsCollection::getInstance();
			list($r) = $permissions->isAllowedObject($permissions->getUserId(), $elementId);
			return $r;
		}

		/**
			* Определить id типа данных, которому принадлежат больше всего страниц под $element_id
			* @param Integer $element_id id страницы
			* @param Integer $hierarchy_type_id = null
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getDominantTypeId($element_id, $depth = 1, $hierarchy_type_id = null) {
			if($this->isExists($element_id) || $element_id === 0) {
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

				$element_id = (int) $element_id;
				$depth      = (int) $depth;

				if($hierarchy_type_id) {
					$htype_cond = " AND h.type_id = '" . ((int) $hierarchy_type_id) . "'";
				} else {
					$htype_cond = '';
				}

				if($depth > 1) {
					$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
	FROM cms3_hierarchy h, cms3_objects o, cms3_hierarchy_relations hr
		WHERE hr.rel_id = '{$element_id}' AND h.id=hr.child_id AND h.is_deleted = '0' AND o.id = h.obj_id AND h.lang_id = '{$lang_id}' AND h.domain_id = '{$domain_id}'
			{$htype_cond}
			GROUP BY o.type_id
				ORDER BY c DESC
					LIMIT 1
SQL;
				} else {
					$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
	FROM cms3_hierarchy h, cms3_objects o
		WHERE h.rel = '{$element_id}' AND h.is_deleted = '0' AND o.id = h.obj_id AND h.lang_id = '{$lang_id}' AND h.domain_id = '{$domain_id}'
		{$htype_cond}
			GROUP BY o.type_id
				ORDER BY c DESC
					LIMIT 1
SQL;
				}

				if($type_id = (int) cacheFrontend::getInstance()->loadSql($sql)) {
					return $type_id;
				}

				$result = l_mysql_query($sql);

				if(mysql_num_rows($result)) {
					list($type_id) = mysql_fetch_row($result);
					$type_id = (int) $type_id;

					cacheFrontend::getInstance()->saveSql($sql, $type_id);

					return $type_id;
				} else {
					return NULL;
				}
			} else {
				return false;
			}
		}

		/**
			* Пометить страницу с id $element_id как измененную в рамках текущей сессии. Используется самой системой
			* @param id $element_id страницы
		*/
		public function addUpdatedElementId($element_id) {
			if(!in_array($element_id, $this->updatedElements)) {
				$this->updatedElements[] = $element_id;
			}
		}

		/**
			* Получать список страниц, измененных в рамках текущей сессии
			* @return Array массив, состоящий из id страниц
		*/
		public function getUpdatedElements() {
			return $this->updatedElements;
		}

		/**
			* Запустить очистку кеша по измененным страницам
		*/
		protected function forceCacheCleanup() {
			if(sizeof($this->updatedElements)) {
				if(function_exists("deleteElementsRelatedPages")) {
					deleteElementsRelatedPages();
				}
			}
		}

		/**
			* Деструктор
		*/
		public function __destruct() {
			if(defined('SMU_PROCESS') && SMU_PROCESS) {
				return;
			}

			$this->forceCacheCleanup();

			if(sizeof($this->symlinks)) {
				foreach($this->symlinks as $i => $arr) {
					list($element_id, $symlink_id) = $arr;
					$this->copyElement($element_id, $symlink_id);
					unset($this->symlinks[$i]);
				}
				$this->symlinks = Array();
			}

			if(class_exists('staticCache')) {
				$staticCache = new staticCache;
				$staticCache->cleanup();
				unset($staticCache);
			}
		}

		/**
			* Получить список страниц, которые были запрошены в текущей сессии
			* @return Array массив, состоящий из id страниц
		*/
		public function getCollectedElements() {
			return array_merge(array_keys($this->elements), $this->misc_elements);
		}

		/**
			* Выгрузить экземпляр страницы $element_id из памяти коллекции
			* @param Integer $element_id id страницы
		*/
		public function unloadElement($element_id) {
			static $pid;

			if($pid === NULL) {
				$pid = cmsController::getInstance()->getCurrentElementId();
			}

			if($pid == $element_id) return false;

			if(array_key_exists($element_id, $this->elements)) {
				unset($this->elements[$element_id]);
			} else {
				return false;
			}
		}

		/**
			* Выгрузить все экземпляры страниц из памяти коллекции
		*/
		public function unloadAllElements() {

			static $pid;

			if($pid === NULL) {
				$pid = cmsController::getInstance()->getCurrentElementId();
			}


			foreach($this->elements as $element_id=>$v)
			{
                    if($pid == $element_id)
                    {
                         continue;
                    }

                    unset($this->elements[$element_id]);
			}

		}

		/**
			* Deprecated: устаревший метод
		*/
		public function getElementsCount($module, $method = "") {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method)->getId();

			$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE type_id = '{$hierarchy_type_id}'";
			$result = l_mysql_query($sql);

			if(list($count) = mysql_fetch_row($result)) {
				return $count;
			} else {
				return false;
			}
		}

		/**
			* Добавить время последней модификации страницы максимальное для текущей сессии
			* @param Integer $update_time=0 время в формате UNIX TIMESTAMP
		*/
		private function pushElementsLastUpdateTime($update_time = 0) {
			if($update_time > $this->elementsLastUpdateTime) {
				$this->elementsLastUpdateTime = $update_time;
			}
		}

		/**
			* Получить максимальное значениея атрибута "дата последней модификации" для всех страниц, загруженных в текущей сессии
			* @return Integer дата в формате UNIX TIMESTAMP
		*/
		public function getElementsLastUpdateTime() {
			return $this->elementsLastUpdateTime;
		}

		/**
			* Получить все страницы, использующие объект (класс umiObject) в качестве источника данных
			* @param Integer $object_id id объекта
			* @return Array массив, состоящий из id страниц
		*/
		public function getObjectInstances($object_id, $bIgnoreDomain = false, $bIgnoreLang = false) {
			$object_id = (int) $object_id;
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

			$sql = "SELECT id FROM cms3_hierarchy WHERE obj_id = '{$object_id}'";
			if(!$bIgnoreDomain) $sql .= " AND domain_id = '{$domain_id}'";
			if(!$bIgnoreLang)   $sql .= " AND lang_id = '{$lang_id}'" ;
			$result = l_mysql_query($sql);

			$res = array();
			while(list($element_id) = mysql_fetch_row($result)) {
				$res[] = $element_id;
			}

			return $res;
		}

		/**
			* Определить id шаблона, который выставлен у большинства страниц под $element_id
			* @param Integer $elementId id страницы
			* @return Integer id шаблона дизайна (класс template)
		*/
		public function getDominantTplId($elementId) {
			$elementId = (int) $elementId;

			$sql = "SELECT `tpl_id`, COUNT(*) AS `cnt` FROM cms3_hierarchy WHERE rel = '{$elementId}' AND is_deleted = '0' GROUP BY tpl_id ORDER BY `cnt` DESC";
			$result = l_mysql_query($sql);
			if($row = mysql_fetch_row($result)) {
				list($tpl_id) = $row;
				return $tpl_id;
			} else {
				$element = $this->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					return $element->getTplId();
				}
			}
		}

		/**
			* Получить список страниц, измененных с даты $timestamp
			* @param Integer $limit ограничение на количество результатов
			* @param Integer $timestamp=0 дата в формате UNIX TIMESTAMP
			* @return Array массив, состоящий из id страниц
		*/
		public function getLastUpdatedElements($limit, $timestamp = 0) {
			$limit = (int) $limit;
			$timestamp = (int) $timestamp;

			$sql = "SELECT id FROM cms3_hierarchy WHERE updatetime >= {$timestamp} LIMIT {$limit}";
			$result = l_mysql_query($sql);

			$res = Array();
			while(list($id) = mysql_fetch_row($result)) {
				$res[] = $id;
			}
			return $res;
		}

		/**
			* Проверить список страниц на предмет того, имеют ли они виртуальные копии
			* @param Integer $arr массив, где ключ это id страницы, а значение равно false(!)
			* @return Array преобразует параметр $arr таким образом, что false поменяется на количество виртуальных копий там, где они есть
		*/
		public function checkIsVirtual($arr) {
			if(sizeof($arr) == 0) return $arr;

			foreach($arr as $element_id => $nl) {
				$element = $this->getElement($element_id);
				$arr[$element_id] = (string) $element->getObjectId();
			}

			$sql = "SELECT obj_id, COUNT(*) FROM cms3_hierarchy WHERE obj_id IN (" . implode(", ", $arr) . ") AND is_deleted = '0' GROUP BY obj_id";
			$result = l_mysql_query($sql);

			while(list($obj_id, $c) = mysql_fetch_row($result)) {
				$is_virtual = ($c > 1) ? true : false;

				foreach($arr as $i => $v) {
					if($v === $obj_id) {
						$arr[$i] = $is_virtual;
					}
				}
			}

			return $arr;
		}

		/**
			* Перепроверить псевдостатичесик URL страницы $element_id на предмет коллизий
			* @param Integer $element_id id страницы
			* @return false если страница $element_id не доступна
		*/
		protected function rewriteElementAltName($element_id) {
			$element = $this->getElement($element_id, true, true);
			if($element instanceof iUmiHierarchyElement) {
				$element->setAltName($element->getAltName());
				$element->commit();

				return true;
			} else {
				return false;
			}
		}


		//Write here methods to rebuild cms3_hierarchy_relations subnodes

		/**
			* Стереть все записи, связанные со страницой $element_id из таблицы cms3_hierarchy_relations
			* @param Integer $element_id id страницы
		*/
		protected function earseRelationNodes($element_id) {
			$element_id = (int) $element_id;

			$sql = "DELETE FROM cms3_hierarchy_relations WHERE rel_id = '{$element_id}' OR child_id = '{$element_id}'";
			l_mysql_query($sql);
		}

		/**
			* Перестроить дерево зависимостей для узла $element_id
		*/
		public function rebuildRelationNodes($elementId) {		//TODO: public - временно. должен быть protected
			$elementId = (int) $elementId;

			//Earse all hierarchy relations
			$this->earseRelationNodes($elementId);

			//Put new relations data for this single element as for a new one
			$this->buildRelationNewNodes($elementId);

			//Get all childs and apply this methods to 'em
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$elementId}'";
			$result = l_mysql_query($sql);

			while(list($childElementId) = mysql_fetch_row($result)) {
				$this->rebuildRelationNodes($childElementId);
			}
		}


		/**
			* Построить дерево зависимостей для $element_id относительно родителей
			* @param $element_id id страницы
		*/
		public function buildRelationNewNodes($element_id) {		//TODO: public - временно. должен быть protected
			$element_id = (int) $element_id;
			$this->earseRelationNodes($element_id);

			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$element_id}'";
			$result = l_mysql_query($sql, true);

			if(mysql_num_rows($result)) {
				list($parent_id) = mysql_fetch_row($result);
				$parent_id_cond = ($parent_id > 0) ? " = '{$parent_id}'" : " IS NULL";

				$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
SELECT rel_id, '{$element_id}', (level + 1) FROM cms3_hierarchy_relations WHERE child_id {$parent_id_cond}
SQL;
				l_mysql_query($sql);
				$parents = $this->getAllParents($parent_id, true, true);

				$parents = array_extract_values($parents);
				$level = sizeof($parents);


				$parent_id_val = ($parent_id > 0) ? "'{$parent_id}'" : "NULL";

				$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
VALUES ({$parent_id_val}, '{$element_id}', '{$level}')
SQL;
				l_mysql_query($sql);
				return true;
			} else return false;
		}

		/**
			* Является ли указанная страница родителем для $hierarchy
			* @param umiHierarchyElement $hierarchy
			* @param umiHierarchyElement $hierarchy_parent
			* @return Boolean true если hierarchy_parent родитель для hierarchy
		*/
		public function hasParent($hierarchy, $hierarchy_parent) {
			if( ! $hierarchy ) return false;


			if(is_numeric($hierarchy)) {
				$hierarchy = umiHierarchy::getInstance()->getElement ($hierarchy);
			}

			if(is_numeric($hierarchy_parent)) {
				$hierarchy_parent = umiHierarchy::getInstance()->getElement ($hierarchy_parent);
			}

			if( ! $hierarchy instanceof umiHierarchyElement) return false;
			if( ! $hierarchy_parent instanceof umiHierarchyElement) return false;



			if($hierarchy->getRel() == $hierarchy_parent->getId()) {
				return true;
			}

			return $this->hasParent( $hierarchy->getRel(), $hierarchy_parent);
		}

		public function clearCache() {
			$keys = array_keys($this->elements);
			foreach($keys as $key) unset($this->elements[$key]);
			$this->elements = array();
			$this->symlinks = array();
			$this->misc_elements = array();
			$this->pathCache = array();
			$this->pathPiecesCache = array();
			$this->defaultCache = array();
			$this->parentsCache = array();
			$this->idByPathCache = array();
		}

		public function getRightAltName($alt_name, $element, $b_fill_cavities = false, $ignore_cur_element = false) {

			if (empty($alt_name)) $alt_name = '1';

			if ($element->getRel() == 0 && !IGNORE_MODULE_NAMES_OVERWRITE) {
				// если элемент непосредственно под корнем и снята галка в настройках -
				// корректировать совпадение с именами модулей и языков
				$modules_keys = regedit::getInstance()->getList("//modules");
				foreach($modules_keys as $module_name) {
					if ($alt_name == $module_name[0]) {
							$alt_name .= '1';
							break;
					}
				}
				if (langsCollection::getInstance()->getLangId($alt_name)) {
					$alt_name .= '1';
				}
			}

			$exists_alt_names =  array();

			preg_match("/^([a-z0-9_.-]*)(\d*?)$/U", $alt_name, $regs);
			$alt_digit = isset($regs[2]) ? $regs[2] : NULL;
			$alt_string = isset($regs[1]) ? $regs[1] : NULL;

			$lang_id = $element->getLangId();
			$domain_id = $element->getDomainId();

			if($ignore_cur_element) {
				$sql = "SELECT alt_name FROM cms3_hierarchy WHERE rel={$element->getRel()}  AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND alt_name LIKE '{$alt_string}%';";
			}
		 	else {
				$sql = "SELECT alt_name FROM cms3_hierarchy WHERE rel={$element->getRel()} AND id <> {$element->getId()} AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND alt_name LIKE '{$alt_string}%';";
			}

			$result = l_mysql_query($sql);

			while(list($item) = mysql_fetch_row($result)) $exists_alt_names[] = $item;

			if (!empty($exists_alt_names) and in_array($alt_name,$exists_alt_names)){ //print_R($exists_alt_names);
				foreach($exists_alt_names as $next_alt_name){
					preg_match("/^([a-z0-9_.-]*)(\d*?)$/U", $next_alt_name, $regs);
					if (!empty($regs[2])) $alt_digit = max($alt_digit,$regs[2]);
				}
				++$alt_digit;
				//
				if ($b_fill_cavities) {
					$j = 0;
					for ($j = 1; $j<$alt_digit; $j++) {
						if (!in_array($alt_string . $j, $exists_alt_names)) {
							$alt_digit = $j;
							break;
						}
					}
				}
			}
			return $alt_string . $alt_digit;
		}

	};
?>
