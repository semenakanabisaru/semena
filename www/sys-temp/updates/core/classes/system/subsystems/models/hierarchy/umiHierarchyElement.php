<?php
	/**
	* Реализует доступ и управление свойствами страниц. Страницы это то, что в системе фигурирует в структуре сайта.
	 */
	class umiHierarchyElement extends umiEntinty implements iUmiEntinty, iUmiHierarchyElement {
		private	$rel, $alt_name, $ord, $object_id,
			$type_id, $domain_id, $lang_id, $tpl_id,
			$is_deleted = false, $is_active = true, $is_visible = true, $is_default = false, $name,
			$update_time,
			$object,
			$is_broken = false;

		protected $store_type = "element";

		/**
			* Узнать, удалена ли страница в корзину или нет
			* @return Boolean true, если страница помещена в мусорную корзину, либо false если нет
		*/
		public function getIsDeleted() {
			return $this->is_deleted;
		}

		/**
			* Узнать, активна страница или нет
			* @return Boolean true если активна
		*/
		public function getIsActive() {
			return $this->is_active;
		}

		/**
			* Узнать, видима ли страница в меню или нет
			* @return Boolean true если страница может отображаться в меню сайта
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Получить id языка (класс lang), к которому привязана страница
			* @return Integer id языка
		*/
		public function getLangId() {
			return $this->lang_id;
		}

		/**
			* Получить id домена (класс domain), к которому привязана страница
			* @return Integer id домена
		*/
		public function getDomainId() {
			return $this->domain_id;
		}

		/**
			* Получить id шаблона дизайана (класс template), по которому отображаеся страница
			* @return Integer id шаблона дизайна (класс template)
		*/
		public function getTplId() {
			return $this->tpl_id;
		}

		/**
			* Получить id базового типа (класс umiHierarchyType), который определяет поведение страницы на сайте
			* @return Integer id базового типа (класс umiHierarchyType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}

		/**
			* Получить время последней модификации страницы
			* @return Integer дата в формате UNIX TIMESTAMP
		*/
		public function getUpdateTime() {
			return $this->update_time;
		}

		/**
			* Получить порядок страницы отосительно соседних страниц
			* @return Integer порядок страницы ()
		*/
		public function getOrd() {
			return $this->ord;
		}

		/**
			* Получить id родительской страницы. Deprecated: используйте метод umiHierarchyElement::getParentId()
			* @return Integer id страницы
		*/
		public function getRel() {
			return $this->rel;
		}

		/**
			* Получить псевдостатический адрес страницы, по которому строится ее адрес
			* @return String псевдостатический адрес
		*/
		public function getAltName() {
			return $this->alt_name;
		}

		/**
			* Получить флаг "по умолчанию" у страницы
			* @return Boolean флаг "по умолчанию"
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Получить объект (класс umiObject), который является источником данных для страницы
			* @return umiObject объект страницы (ее источник данных)
		*/
		public function getObject() {
			if(isset($this->object) && $this->object) {
				return $this->object;
			} else if(isset($this->object_id)) {
				$this->object = umiObjectsCollection::getInstance()->getObject($this->object_id);
				return $this->object;
			} else {
				return null;
			}
		}

		/**
			* Получить id родительской страницы.
			* @return Integer id страницы
		*/
		public function getParentId() {
			return $this->rel;
		}

		/**
			* Получить название страницы
			* @return String название страницы
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Изменить название страницы
			* @param String $name новое название страницы
		*/
		public function setName($name) {
			$res = $this->getObject()->setName($name);
			$this->name = $this->object->getName(true);
			$this->setIsUpdated(true);
			return $res;
		}

		/**
			* Получить значение свойства $prop_name
			* @param String $prop_name строковой идентификатор свойства, значение которого нужно получить
			* @param Array $params = NULL дополнительные параметры (обычно не используется)
			* @return Mixed значение свойства. Тип возвращаемого значения зависит от типа поля
		*/
		public function getValue($prop_name, $params = NULL) {
			$object = $this->getObject();
			return $object ? $object->getValue($prop_name, $params) : false;
		}

		/**
			* Изменить значение свойства $prop_name на $prop_value
			* @param String $prop_name строковой идентификатор свойства, значение которого нужно изменить
			* @param Mixed $prop_value новое значение свойства. Тип аргумента зависит от типа поля
			* @return Boolean true, если не произошло ошибок
		*/
		public function setValue($prop_name, $prop_value) {
			if($object = $this->getObject()) {
				$result = $object->setValue($prop_name, $prop_value);
				$this->setIsUpdated(true);
				return $result;
			} else {
				return false;
			}
		}

		/**
			* Утановить флаг, означающий, что страница может быть видима в меню
			* @param Boolean $is_visible=true новое значение флага видимости
		*/
		public function setIsVisible($is_visible = true) {
			if ($this->is_visible !== ((bool)$is_visible)) {
				$this->is_visible = (bool) $is_visible;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить флаг активности
			* @param Boolean $is_active=true значение флага активности
		*/
		public function setIsActive($is_active = true) {
			if ($this->is_active !== ((bool)$is_active)) {
				$this->is_active = (bool) $is_active;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить флаг "удален", который сигнализирует о том, что страница помещена в корзину
			* @param Boolean $is_deleted=false значение флага удаленности
		*/
		public function setIsDeleted($is_deleted = false) {
			if ($this->is_deleted !== ((bool)$is_deleted)) {
				$this->is_deleted = (bool) $is_deleted;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить id базового типа (класс umiHierarchyType), который определяет поведение страницы на сайте
			* @param Integer $type_id id базового типа (класс umiHierarchyType)
		*/
		public function setTypeId($type_id) {
			if ($this->type_id !== ((int)$type_id)) {
				$this->type_id = (int) $type_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить id языка (класс lang), к которому привязана страница
			* @param Integer $lang_id id языка
		*/
		public function setLangId($lang_id) {
			if ($this->lang_id !== ((int)$lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить шаблон дизайна, по которому отображается страница на сайте
			* @param Integer $tpl_id id шаблона дизайна (класс template)
		*/
		public function setTplId($tpl_id) {
			if ($this->tpl_id !== ((int)$tpl_id)) {
				$this->tpl_id = (int) $tpl_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить домен (класс domain), к которому привязана страница
			* @param Integer $domain_id id домена (класс domain)
		*/
		public function setDomainId($domain_id) {
			$hierarchy = umiHierarchy::getInstance();
			$childs = $hierarchy->getChilds($this->id, true, true);

			foreach($childs as $child_id => $nl) {
				$child = $hierarchy->getElement($child_id, true, true);
				$child->setDomainId($domain_id);
				$hierarchy->unloadElement($child_id);
				unset($child);
			}

			if ($this->domain_id !== ((int)$domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить время последней модификации страницы
			* @param Integer $update_time=0 время последнего изменения страницы в формате UNIX TIMESTAMP. Если аргумент не передан, берется текущее время.
		*/
		public function setUpdateTime($update_time = 0) {
			if($update_time == 0) {
				$update_time = umiHierarchy::getTimeStamp();
			}
			if ($this->update_time !== ((int)$update_time)) {
				$this->update_time = (int) $update_time;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить номер порядка следования страницы в структуре относительно других страниц
			* @param Integer $ord порядковый номер
		*/
		public function setOrd($ord) {
			if ($this->ord !== ((int)$ord)) {
				$this->ord = (int) $ord;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить родителя страницы
			* @param Integer $rel id родительской страницы
		*/
		public function setRel($rel) {
			if ($this->rel !== ((int)$rel)) {
				$this->rel = (int) $rel;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить объект-источник данных страницы
			* @param umiObject $object экземпляр класса umiObject
			* @param $bNeedSetUpdated=true если true, то на объекте $object будет выполнен метод setIsUpdated() без параметров
		*/
		public function setObject(umiObject $object, $bNeedSetUpdated = true) {
			$this->object = $object;
			$this->object_id = $object->getId();
			if ($bNeedSetUpdated) $this->setIsUpdated();
		}

		/**
			* Изменить псевдостатический адрес, который участвует в формировании адреса страницы
			* @param $alt_name новый псевдостатический адрес
			* @param Boolean $auto_convert не указывайте этот параметр
		*/
		public function setAltName($alt_name, $auto_convert = true) {
			if(!$alt_name) {
				$alt_name = $this->getName();
			}

			if($auto_convert) {
				$alt_name = umiHierarchy::convertAltName($alt_name);
				if(!$alt_name) $alt_name = "_";
			}

			$sPrevAltname = $this->alt_name;

			$this->alt_name = $this->getRightAltName(umiObjectProperty::filterInputString($alt_name));
			if(!$this->alt_name) {
				$this->alt_name = $alt_name;
			}

			$sNewAltname = $this->alt_name;
			if ($sNewAltname !== $sPrevAltname) $this->setIsUpdated();
		}

		/**
			* При выгрузке страницы нужно выгружать связанный объект.
			* Вся память там.
		*/
		public function __destruct() {
			$objectId = $this->object_id;
			parent::__destruct();
			unset($this->object_id);
			unset($this->object);
			umiObjectsCollection::getInstance()->unloadObject($objectId);
		}

		/**
			* Разрешить коллизии в псевдостатическом адресе страницы
			* @param String $alt_name псевдостатический адрес страницы
			* @return String откорректированный результат
		*/
		private function getRightAltName($alt_name, $b_fill_cavities = false) {
			/*
				Не совсем предсказуемо для оператора
				работает с адресами-цифрами.
				При правках необходимо учитывать возможность наличия
				цифр в адресе (в частности - в его начале)
			*/
			if (empty($alt_name)) $alt_name = '1';

			if ($this->getRel() == 0 && !IGNORE_MODULE_NAMES_OVERWRITE) {
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

			$lang_id = $this->getLangId();
			$domain_id = $this->getDomainId();

			$sql = "SELECT alt_name FROM cms3_hierarchy WHERE rel={$this->getRel()} AND id <> {$this->getId()} AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND alt_name LIKE '{$alt_string}%';";
			$result = l_mysql_query($sql);

			while(list($item) = mysql_fetch_row($result)) $exists_alt_names[] = $item;
			if (!empty($exists_alt_names) and in_array($alt_name,$exists_alt_names)){
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

		/**
			* Изменить значение флаг "по умолчанию"
			* @param Boolean $is_default=true значение флага "по умолчанию"
		*/
		public function setIsDefault($is_default = true) {
			if ($this->is_default !== ((int)$is_default)) {
				umiHierarchy::getInstance()->clearDefaultElementCache();
				cacheFrontend::getInstance()->flush();

				$this->is_default = (int) $is_default;
				$this->setIsUpdated();
			}
		}

		/**
			* Получить id поля по его строковому идентификатору
			* @param String $field_name строковой идентификатор поля
			* @return Integer id поля, либо false
		*/
		public function getFieldId($field_name) { //TODO: дезинтегрировать следующую строчку (c) lyxsus
			return umiObjectTypesCollection::getInstance()->getType($this->getObject()->getTypeId())->getFieldId($field_name);
		}

		/**
			* Загрузить информацию о страницы из БД
			* @return Boolean true если не возникло ошибок
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT h.rel, h.type_id, h.lang_id, h.domain_id, h.tpl_id, h.obj_id, h.ord, h.alt_name, h.is_active, h.is_visible, h.is_deleted, h.updatetime, h.is_default, o.name FROM cms3_hierarchy h, cms3_objects o WHERE h.id = '{$this->id}' AND o.id = h.obj_id";
				$result = l_mysql_query($sql, true);
				$row = mysql_fetch_row($result);
			}

			if(list($rel, $type_id, $lang_id, $domain_id, $tpl_id, $obj_id, $ord, $alt_name, $is_active, $is_visible, $is_deleted, $updatetime, $is_default, $name) = $row) {
				if(!$obj_id) {	//Really bad, foregin check didn't worked out :(, let's delete it itself
					umiHierarchy::getInstance()->delElement($this->id);
					$this->is_broken = true;
					return false;
				}

				$this->rel = (int) $rel;
				$this->type_id = (int) $type_id;
				$this->lang_id = (int) $lang_id;
				$this->domain_id = (int) $domain_id;
				$this->tpl_id = (int) $tpl_id;
				$this->object_id = (int) $obj_id;
				$this->ord = (int) $ord;
				$this->alt_name = $alt_name;
				$this->is_active = (bool) $is_active;
				$this->is_visible = (bool) $is_visible;
				$this->is_deleted = (bool) $is_deleted;
				$this->is_default = (bool) $is_default;

				$this->name = $name;	//read-only

				if (!$updatetime) {
					$updatetime = umiHierarchy::getTimeStamp();
				}
				$this->update_time = (int)$updatetime;

				return true;
			} else {
				$this->is_broken = true;
				return false;
			}
		}

		/**
			* Сохранить изменения в БД
			* @return Boolean true в случае успеха
		*/
		protected function save() {
			$rel = (int) $this->rel;
			$type_id = (int) $this->type_id;
			$lang_id = (int) $this->lang_id;
			$domain_id = (int) $this->domain_id;
			$tpl_id = (int) $this->tpl_id;
			$object_id = (int) $this->object_id;
			$ord = (int) $this->ord;
			$alt_name = self::filterInputString($this->alt_name);
			$is_active = (int) $this->is_active;
			$is_visible = (int) $this->is_visible;
			$is_deleted = (int) $this->is_deleted;
			$update_time = (int) $this->update_time;
			$is_default = (int) $this->is_default;


			if($is_default) {
				$sql ="UPDATE cms3_hierarchy SET is_default = '0' WHERE is_default = '1' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				l_mysql_query($sql);
			}

			$sql = "UPDATE cms3_hierarchy SET rel = '{$rel}', type_id = '{$type_id}', lang_id = '{$lang_id}', domain_id = '{$domain_id}', tpl_id = '{$tpl_id}', obj_id = '{$object_id}', ord = '{$ord}', alt_name = '{$alt_name}', is_active = '{$is_active}', is_visible = '{$is_visible}', is_deleted = '{$is_deleted}', updatetime = '{$update_time}', is_default = '{$is_default}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);

			if ($this->is_updated) {
				$search = searchModel::getInstance();
				if(PAGES_AUTO_INDEX) {
					if($this->getIsActive() && $this->getIsDeleted() == false) {
						$search->index_item($this->id);
					} else {
						$search->unindex_items($this->id);
					}
				}
			}

			if (!umiHierarchy::$ignoreSiteMap) $this->updateSiteMap(true);
			try {
				$this->updateYML();
			} catch (Exception $e) {}

			return true;
		}

		/**
		 * @deprecated
		 * TODO: Вынести из umiHierarchyElement
		 */
		public function updateYML() {
			
			$dirName = CURRENT_WORKING_DIR . "/sys-temp/yml/";

			$dirName = CURRENT_WORKING_DIR . "/sys-temp/yml/";

			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$hierarchyCatalogObjectType = $hierarchyTypes->getTypeByName("catalog", "object");
			$hierarchyCatalogCategoryType = $hierarchyTypes->getTypeByName("catalog", "category");
			$hierarchy = umiHierarchy::getInstance();

			if (!$hierarchyCatalogObjectType || !$hierarchyCatalogCategoryType) return false;

			if ($this->getHierarchyType()->getId() == $hierarchyCatalogCategoryType->getId()) {
				$this->checkYMLinclude();

				if (!$this->is_active || $this->is_deleted) {
					$childsIds = $hierarchy->getChildIds($this->getId(), false);
					foreach($childsIds as $childId) {
						$xml = $dirName . $childId . ".txt";
						if(file_exists($xml)) unlink($xml);
					}
				}

				return true;
			}

			if ($this->getHierarchyType()->getId() != $hierarchyCatalogObjectType->getId()) return false;


			if (!is_dir($dirName)) mkdir($dirName, 0777, true);
			$xml = $dirName . "{$this->id}.txt";
			if(file_exists($xml)) unlink($xml);

			if ($this->is_active && !$this->is_deleted) {

				$matches = $this->checkYMLinclude();
				if (!count($matches)) return false;

				$parentId = $this->getParentId();
				if ($parentId) {
					$parent = umiHierarchy::getInstance()->getElement($parentId, true, true);
					if ($parent->getHierarchyType()->getId() != $hierarchyCatalogCategoryType->getId()) {
						$parentId = false;
						$parents = $hierarchy->getAllParents($this->id, true, true);
						for ($i = count($parents)-1; $i>=0 ; $i--) {
							$newParentId = $parents[$i];
							$newParent = $hierarchy->getElement($newParentId, true);
							if ($newParent instanceof umiHierarchyElement && $newParent->getHierarchyType()->getId() == $hierarchyCatalogCategoryType->getId()) {
								$parentId = $newParentId;
								break;
					}
						}
					}
				}
				if (!$parentId) {
					throw new publicAdminException(getLabel('error-update-yml'));
				}

				$exporter = new xmlExporter('yml');
				$exporter->addElements(array($this->id));
				$exporter->setIgnoreRelations();
				$umiDump = $exporter->execute();

				$style_file = CURRENT_WORKING_DIR . '/xsl/export/YML.xsl';
				if (!is_file($style_file)) {
					throw new publicException("Can't load exporter {$style_file}");
				}

				$doc = new DOMDocument("1.0", "utf-8");
				$doc->formatOutput = XML_FORMAT_OUTPUT;
				$doc->loadXML($umiDump->saveXML());

				$templater = umiTemplater::create('XSLT', $style_file);
				$result = $templater->parse($doc);

				$dom = new DOMDocument();
				$dom->loadXML($result);
				$offer = $dom->getElementsByTagName('offer')->item(0);
				if ($offer) {
					$category = $offer->getElementsByTagName('categoryId')->item(0);
					if ($category) $category->nodeValue = $parentId;
					$content = iconv("UTF-8", "CP1251//IGNORE", $dom->saveXML($offer));
					file_put_contents($xml, $content);
				}

				$currencies = $dom->getElementsByTagName('currencies')->item(0);
				$curr = iconv("UTF-8", "CP1251//IGNORE", $dom->saveXML($currencies));
				file_put_contents($dirName . 'currencies', $curr);

				$shopName = $dom->getElementsByTagName('name')->item(0);
				$name = $shopName->nodeValue;
				$company = $dom->getElementsByTagName('company')->item(0);
				$companyName = $company->nodeValue;

				foreach ($matches as $exportId) {
					file_put_contents($dirName . 'shop' . $exportId, '<name>' . iconv("UTF-8", "CP1251//IGNORE", $name) . '</name><company>' . iconv("UTF-8", "CP1251", $companyName) . '</company><url>http://' . domainsCollection::getInstance()->getDomain($this->getDomainId())->getHost() . '</url>');
				}
			}
		}

		/**
		 * @deprecated
		 * TODO: Вынести из umiHierarchyElement
		 */
		protected function checkYMLinclude() {
			$dirName = CURRENT_WORKING_DIR . "/sys-temp/yml/";
			if (!is_dir($dirName)) return false;
			$dir = dir($dirName);

			$matches = array();
			$hierarchy = umiHierarchy::getInstance();
			$parents = $hierarchy->getAllParents($this->id, true, true);

			while (false !== ($file = $dir->read())) {
				if(strpos($file, "cat")) {
					$id = trim($file, 'cat');
					$parentsArray = unserialize(file_get_contents($dirName . $file));
					$childsArray = unserialize(file_get_contents($dirName . $id . "el"));
					$intersect = array_keys(array_intersect($parents, $parentsArray));
					$exportId = trim($file, 'cat');
					$categories = array();
					if (file_exists($dirName . 'categories' . $exportId)) {
						$categories = unserialize(file_get_contents($dirName . 'categories' . $exportId));
					}
					
					if (count($intersect)) {

						$firstParentKey = $intersect[0];
						if ($parents[$firstParentKey] == $this->getId() && $this->getHierarchyType()->getMethod() == 'object') {
							if (isset($parents[$firstParentKey-1])) $firstParentKey--;
						}

						for ($i = $firstParentKey; $i < count($parents); $i++) {

							$parentId = $parents[$i];
							$parent = $hierarchy->getElement($parentId);
							if (!$parent instanceof umiHierarchyElement) continue;
							if (!$parent->getIsActive() || $parent->getIsDeleted()) {
								if ($this->getHierarchyType()->getMethod() == 'object') return $matches;
							}
							if ($parent->getHierarchyType()->getMethod() != 'category') continue;

							if ($parent->getIsActive() && !$parent->getIsDeleted()) {

								$categoryName = $parent->getName();
								$categoryName = iconv("UTF-8", "CP1251//IGNORE", $categoryName);
								$categoryName = strtr($categoryName, array("&" => "&amp;", "<" => "&lt;", ">" => "&gt;"));

								$parentCategoryId = $parent->getParentId();
								if ($parentCategoryId && isset($categories[$parentCategoryId])) {
									$categories[$parentId] = '<category id="' . $parentId . '" parentId="' . $parentCategoryId . '">' . $categoryName . '</category>';
								} else {
									$categories[$parentId] = '<category id="' . $parentId . '">' . $categoryName . '</category>';
								}

							} else {
								if(isset($categories[$parentId])) unset($categories[$parentId]);
							}
						}
						
						if(!in_array($this->id, $childsArray) && $this->getHierarchyType()->getMethod() == 'object') {
							$childsArray[] = $this->id;
							file_put_contents($dirName . $id . "el", serialize($childsArray));
						}
						$matches[] = $exportId;
								
						

					} elseif($this->getHierarchyType()->getMethod() == 'category' &&(!$this->getIsActive() || $this->getIsDeleted())) {
						
						$childs = $hierarchy->getChildIds($this->getId(), false, true);
						$intersect = array_intersect($childs, $parentsArray);
						if (count($intersect)) {
							foreach($childs as $key => $childId) {
								if(isset($categories[$childId])) unset($categories[$childId]);
							}
						}
						
					} else {
						if($key =  array_search($this->id, $childsArray) && $this->getHierarchyType()->getMethod() == 'object') {
							unset($childsArray[$key]);
							sort($childsArray);
							file_put_contents($dirName . $id . "el", serialize($childsArray));
						}
					}
					file_put_contents($dirName . 'categories' . $exportId, serialize($categories));
				}
			}
			$dir->close();
			return $matches;
		}

		public function updateSiteMap($ignoreChilds = false) {

			$hierarchy = umiHierarchy::getInstance();

			if(!$ignoreChilds) {
				$childs = $hierarchy->getChilds($this->id, true, true, 1);

				if (is_array($childs)){
					foreach ($childs as $childId => $value) {
						$child = $hierarchy->getElement($childId)->updateSiteMap($ignoreChilds);
					}
				}
			}

			$oldForce = $hierarchy->forceAbsolutePath();
			$link = $hierarchy->getPathById($this->id, false, false, true);

			$update_time = date('c', $this->update_time);

			$sql = "SELECT level FROM cms3_hierarchy_relations WHERE (rel_id = '' or rel_id is null) and child_id={$this->id}";
			$result = l_mysql_query($sql);

			$pagePriority = 0.5;
			while(list($level) = mysql_fetch_row($result)) {
				$pagePriority = round(1 / ($level + 1), 1);
				if($pagePriority < 0.1) $pagePriority = 0.1;
			}

			$dirName = CURRENT_WORKING_DIR . "/sys-temp/sitemap/{$this->domain_id}/";
			if (!is_dir($dirName)) mkdir($dirName, 0777, true);
			$xml = $dirName . "{$this->id}.xml";
			if(file_exists($xml)) unlink($xml);

			if ($this->is_active && !$this->robots_deny && !$this->is_deleted) {
				$dom = new DOMDocument();
				$url = $dom->createElement('url');
				$loc = $dom->createElement('loc', $link);
				$priority = $dom->createElement('priority', $pagePriority);

				$lastmod = $dom->createElement('lastmod', $update_time);
				$dom->appendChild($url);
				$url->appendChild($loc);
				$url->appendChild($lastmod);
				$url->appendChild($priority);

				file_put_contents($xml, $dom->saveXML($url));
			}

			$hierarchy->forceAbsolutePath($oldForce);

		}

		/**
			* Изменить флаг измененности. Если экземпляр не помечен как измененный, метод commit() блокируется.
			* @param Boolean $is_updated=true значение флага измененности
		*/
		public function setIsUpdated($is_updated = true) {
			parent::setIsUpdated($is_updated);
			$this->update_time = time();
			umiHierarchy::getInstance()->addUpdatedElementId($this->id);
			if($this->rel) {
				umiHierarchy::getInstance()->addUpdatedElementId($this->rel);
			}
		}

		/**
			* Узнать, все ли впорядке с этим экземпляром
			* @return Boolean true, если все в порядке
		*/
		public function getIsBroken() {
			return $this->is_broken;
		}

		/**
			* Применить все изменения сделанные с этой страницей
		*/
		public function commit() {
			$object = $this->getObject();
			if($object instanceof umiObject) {
				$object->commit();

				$objectId = $object->getId();
				$hierarchy = umiHierarchy::getInstance();
				cacheFrontend::getInstance()->del($objectId, "object");

				$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
				foreach($virtuals as $virtualElementId) {
					cacheFrontend::getInstance()->del($virtualElementId, "element");
				}
			}
			parent::commit();
		}

		/**
			* Получить id типа данных (класс umiObjectType), к которому относится объект (класс umiObject) источник данных.
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getObjectTypeId() {
			return $this->getObject()->getTypeId();
		}

		/**
			* Получить базовый тип, к которому относится страница
			* @return umiHierarchyType базовый тип страницы
		*/
		public function getHierarchyType() {
			return umiHierarchyTypesCollection::getInstance()->getType($this->type_id);
		}

		/**
			* Получить id объекта (класс umiObject), который служит источником данных для страницы
			* @return Integer id объекта (класс umiObject)
		*/
		public function getObjectId() {
			return $this->object_id;
		}

		/**
			* Синоним метода getHierarchyType(). Этот метод является устаревшим.
			* @return umiHierarchyType
		*/
		protected function getType() {
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			return $hierarchyTypesCollection->getType($this->getTypeId());
		}

		/**
			* Получить название модуля базового типа страницы
			* @return String название модуля
		*/
		public function getModule() {
			return $this->getType()->getName();
		}

		/**
			* Получить название метода базового типа страницы
			* @return String название метода
		*/
		public function getMethod() {
			return $this->getType()->getExt();
		}

		/**
			* Удалить страницу
		*/
		public function delete() {
			umiHierarchy::getInstance()->delElement($this->id);
		}

		public function __sleep() {
			$vars = get_class_vars(get_class($this));
			$vars['object'] = NULL;
			return array_keys($vars);
		}


		public function __get($varName) {
			switch($varName) {
				case "id":			return $this->id;
				case "objectId":	return $this->object_id;
				case "name":		return $this->getName();
				case "altName":		return $this->getAltName();
				case "isActive":	return $this->getIsActive();
				case "isVisible":	return $this->getIsVisible();
				case "isDeleted":	return $this->getIsDeleted();
				case "xlink":		return 'upage://' . $this->id;
				case "link": {
					$hierarchy = umiHierarchy::getInstance();
					return $hierarchy->getPathById($this->id);
				}

				default:			return $this->getValue($varName);
			}
		}

		public function __set($varName, $value) {
			switch($varName) {
				case "id":			throw new coreException("Object id could not be changed");
				case "name":		return $this->setName($value);
				case "altName":		return $this->setAltName($value);
				case "isActive":	return $this->setIsActive($value);
				case "isVisible":	return $this->setIsVisible($value);
				case "isDeleted":	return $this->setIsDeleted($value);

				default:			return $this->setValue($varName, $value);
			}
		}

		public function beforeSerialize($reget = false) {
			static $object = null;
			if($reget && !is_null($object)) {
				$this->object = $object;
			}
			else {
				$object = $this->object;
				$this->object = null;
			}
		}

		public function afterSerialize() {
			$this->beforeSerialize(true);
		}

		public function afterUnSerialize() {
			$this->getObject();
		}
	};
?>
