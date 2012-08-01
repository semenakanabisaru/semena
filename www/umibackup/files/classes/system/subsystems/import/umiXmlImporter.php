<?php
/**
	* Класс, обеспечивающий механизм импорта данных в CMS.
	* Способен принимать данные, сгенерированные классом umiXmlExporter.
	* Не смотря на то, что формат XML-файла предусматривает передачу и страниц, и объектов,
	* этот класс умеет импортировать только страницы (класс umiHierarchyElement).
*/
	class umiXmlImporter implements iUmiXmlImporter {
		protected	$ignore_new_fields = false,
				$ignore_new_items = false,
				$is_xml_analyzed = false;
		protected $xml;

		protected	$xml_elements = Array(),
				$xml_objects = Array(),
				$xml_stores = Array(),
				$xml_types = Array();

		protected	$source_id = 1;

		protected	$importedElements = 0,
					$importedElementsArr = Array(),
					$createdElements = 0,
					$deletedElements = 0,
					$updatedElements = 0,
					$importErrors = 0;

		protected	$importLog = array();

		protected $destination_element_id = 0;

		public function __construct() {
			//umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
		}


		/**
			* Устанавливает элемент, в который будут попадать элементы, у которых в дампе не существует родителя.
			* По умолчанию такие элементы попадают в корень сайта
			* @param Variant Id элемента, либо сам элемент
			* @return Boolean true, если удалось установить значение
		*/
		public function setDestinationElementId($element) {
			if ($element instanceof umiHierarchyElement) {
				$this->destination_element_id = $element->getId();
				return true;
			}
			if (umiHierarchy::getInstance()->getElement($element) instanceof umiHierarchyElement) {
				$this->destination_element_id = $element;
				return true;
			}
			return false;
		}

		/**
			* Игнорировать создание новых полей при импорте. В текущей версии не используется.
			* @param Boolean если true, то новые поля при импорте создаваться не будут
			* @return Boolean предыдущее значение этого флага
		*/
		public function ignoreNewFields($ignore_new_fields = NULL) {
			$old_value = $this->ignore_new_fields;

			if(!is_null($ignore_new_fields)) {
				$this->ignore_new_fields = (bool) $ignore_new_fields;
			}

			return $old_value;
		}

		/**
			* Метод в текущей версии не используется
		*/
		public function ignoreNewItems($ignore_new_items = NULL) {
			$old_value = $this->ignore_new_items;

			if(!is_null($ignore_new_items)) {
				$this->ignore_new_items = (bool) $ignore_new_items;
			}

			return $old_value;
		}


		/**
			* Загрузить XML с данными из строки, переданной в качестве параметра
			* @param String $xml_string XML с данными для импорта
			* @return Boolean true, если при чтении данных не возникло ошибок
		*/
		public function loadXmlString($xml_string) {
			$xml = simplexml_load_string($xml_string);
			return $this->loadXml($xml);
		}

		/**
			* Загрузить XML с данными для импорта из указанного файла
			* @param String $xml_filepath путь до файла с данными
			* @return Boolean true, если при чтении данных не возникло ошибок
		*/
		public function loadXmlFile($xml_filepath) {
			if(!is_file($xml_filepath)) {
				trigger_error("XML file {$xml_filepath} not found", E_USER_WARNING);
				return false;
			}


			if(!is_readable($xml_filepath)) {
				trigger_error("XML file {$xml_filepath} is not readable", E_USER_WARNING);
				return false;
			}


			$xml = simplexml_load_file($xml_filepath);
			return $this->loadXml($xml);
		}


		protected function loadXml($xml) {
			if(is_object($xml)) {
				$this->xml = $xml;
				return true;
			} else {
				trigger_error("Failed to read xml-content", E_USER_WARNING);
				return false;
			}
		}

		/**
			* Проанализировать загруженный XML непосредственно перед импортом данных.
		*/
		public function analyzeXml() {
			$source_id_name = (string) $this->xml->sourceId;
			$this->source_id = umiImportRelations::getInstance()->addNewSource($source_id_name);

			foreach($this->xml->element as $currentNode) {
				$this->analyzeElementNode($currentNode);
			}


			foreach($this->xml->object as $currentNode) {
				$old_object_id = (string) $currentNode->attributes()->id;

				if(array_key_exists($old_object_id, $this->xml_objects)) {
					$this->analyzeObjectNode($currentNode);
				}
			}
		}


		protected function analyzeElementNode(SimpleXMLElement $elementNode) {
			$element_id =		(string) $elementNode->attributes()->id;
			$element_parent_id =	(string) $elementNode->attributes()->parentId;
			$element_object_id =	(string) $elementNode->attributes()->objectId;
			$element_alt_name =	(string) $elementNode->altName;
			$element_is_visible =	is_object($elementNode->attributes()->is_visible) ? (string) $elementNode->attributes()->is_visible : NULL;
			$element_is_active =	is_object($elementNode->attributes()->is_active) ? (string) $elementNode->attributes()->is_active : NULL;
			$element_is_deleted =	is_object($elementNode->attributes()->is_deleted) ? (string) $elementNode->attributes()->is_deleted : NULL;

			if(in_array($element_id, $this->importedElementsArr)) {
				return false;
			}

			$module = $elementNode->behaviour->module;
			$method = $elementNode->behaviour->method;

			$element_hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName((string) $module, (string) $method);

			if($element_hierarchy_type === false) {
				trigger_error("Unknown element's module/method", E_USER_ERROR);
				return false;
			}

			$element_filepath = (string) $elementNode->templatePath;

			$element_hierarchy_type_id = $element_hierarchy_type->getId();

			$this->xml_elements[$element_id] = Array(
							"old_element_id" => $element_id,
							"old_parent_id" => $element_parent_id,
							"old_element_object_id" => $element_object_id,

							"element_hierarchy_type_id" => $element_hierarchy_type_id,
							"element_filepath" => $element_filepath,

							"old_element_alt_name" => $element_alt_name,

							"element_is_visible" => $element_is_visible,
							"element_is_active" => $element_is_active,
							"element_is_deleted" => $element_is_deleted
							);

			$this->xml_objects[$element_object_id] = Array(
							"old_element_id" => $element_id,
							"element_hierarcy_type_id" => $element_hierarchy_type_id,
							"is_linked" => true
			);

		}


		protected function analyzeObjectNode(SimpleXMLElement $objectNode) {
			$object_id = (string) $objectNode->attributes()->id;
			$object_type_id = (string) $objectNode->attributes()->typeId;
			$object_type_name = (string) $objectNode->attributes()->typeName;

			$object_info = $this->xml_objects[$object_id];

			$object_info['old_object_id'] = $object_id;
			$object_info['old_type_id'] = $object_type_id;
			$object_info['type_name'] = $object_type_name;
			$object_info['old_name'] = (string) $objectNode->name;

			$object_info['props'] = $this->analyzeObjectPropertiesBlockNode($objectNode->propertiesBlock, $object_type_id);

			if ($objectNode->storesBlock->store) {
				$this->analyzeObjectSoresInfo($object_id, $objectNode->storesBlock->store);
			}

			$this->xml_objects[$object_id] = $object_info;
		}



		protected function analyzeObjectPropertiesBlockNode(SimpleXMLElement $object_properties_block_nodes, $object_type_id) {
			if(!array_key_exists($object_type_id, $this->xml_types)) {
				$this->xml_types[$object_type_id] = Array();
				$this->xml_types[$object_type_id]['is_base'] = true;
				$this->xml_types[$object_type_id]['props'] = Array();
			}

			$obj_props = Array();

			foreach($object_properties_block_nodes as $object_properties_block_node) {
				$props_block_title = (string) $object_properties_block_node->title;
				$props_block_name = (string) $object_properties_block_node->name;
				$props_block_is_public = (string) $object_properties_block_node->isPublic;

				foreach($object_properties_block_node->property as $object_property_node) {
					$prop_title = (string) $object_property_node->title;
					$prop_name = (string) $object_property_node->name;
					$prop_tip = (string) $object_property_node->tip;

					$prop_is_multiple = (string) $object_property_node->isMultiple;
					$prop_is_indexed = (string) $object_property_node->isIndexed;
					$prop_is_filterable = (string) $object_property_node->isFilterable;
					$prop_is_public = (string) $object_property_node->isPublic;
					$prop_is_public = (bool)  $object_property_node->isPublic;
					$prop_guide_id = (string) $object_property_node->guideId;

					$prop_field_type = (string) $object_property_node->fieldType;

					$prop_values = $this->extractValues($object_property_node->values);
					$currency_code = (string) $object_property_node->values->attributes()->currency_code;


					if(!$prop_name) {
						$prop_name = translit::convert($prop_title);
					}

					$prop_info = Array();
					$prop_info['title'] = $prop_title;
					$prop_info['name'] = $prop_name;
					$prop_info['tip'] = $prop_tip;
					$prop_info['is_multiple'] = $prop_is_multiple;
					$prop_info['is_filterable'] = $prop_is_filterable;
					$prop_info['guide_id'] = $prop_guide_id;
					$prop_info['field_type'] = $prop_field_type;
					$prop_info['values'] = $prop_values;
					$prop_info['currency_code'] = $currency_code;
					$prop_info['is_public'] = $prop_is_public;

					$prop_info['prop_block_title'] = $props_block_title;
					$prop_info['prop_block_name'] = $props_block_name;
					$prop_info['prop_block_is_public'] = $props_block_is_public;

					$this->xml_types[$object_type_id]['props'][$prop_name] = $prop_info;

					$obj_props[$prop_name] = $prop_info;
				}
			}

			return $obj_props;
		}

		protected function analyzeObjectSoresInfo($iObjectId, SimpleXMLElement $oObjectStoresInfoNodes) {
			foreach ($oObjectStoresInfoNodes as $oStoreInfoXml) {
				$sStoreId = (string) $oStoreInfoXml->attributes()->id;
				$iAmount = (int) $oStoreInfoXml->amount;
				if (strlen($sStoreId)) {
					$sStoreId = $sStoreId;
					if (!isset($this->xml_stores[$iObjectId])) $this->xml_stores[$iObjectId] = array();
					$arrStoreInfo = array();
					$arrStoreInfo['old_store_id'] = $sStoreId;
					$arrStoreInfo['amount'] = $iAmount;
					$this->xml_stores[$iObjectId][] = $arrStoreInfo;
				}
			}
		}

		protected function extractValues($values_node) {
			$res = Array();

			if (!$values_node->value) return array();

			foreach($values_node->value as $value_node) {
				$timestamp = ((string) $value_node->timestamp[0]);

				$val = ((string) $value_node);

				if($timestamp) {
					$val = new umiDate();
					$val->setDateByTimeStamp($timestamp);
				}

				if($val) {
					$res[] = $val;
				}
			}

			return $res;
		}



		protected function detectBetterFieldType($value) {
			//TODO: Определять наибоее подходящий тип данных.
		}


		protected function detectBetterObjectType($hierarchy_type_id, $old_type_id, $new_type_name = "") {
			//TODO: Определять наиболее подходящий тип данных.
			$fields = array_keys($this->xml_types[$old_type_id]['props']);

			$new_type_id = umiImportRelations::getInstance()->getNewTypeIdRelation($this->source_id, $old_type_id);

			if($new_type_id) {
				return $new_type_id;
			}

			$types = umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($hierarchy_type_id);
			$base_type_id = umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hierarchy_type_id);

			foreach($types as $type_id => $type_name) {
				$diff_count = $this->compareObjectTypeFields($type_id, $fields);

				if($diff_count == 0) {
					$new_type_id = $type_id;
					break;
				}
			}
			if(!$new_type_id) {
				$base_type_name = umiObjectTypesCollection::getInstance()->getType($base_type_id)->getName();
				if (!$new_type_name || !strlen($new_type_name)) $new_type_name = "Подтип \"{$base_type_name}\" #{$old_type_id}";
				$new_type_id = umiObjectTypesCollection::getInstance()->addType($base_type_id, $new_type_name);

				$new_type = umiObjectTypesCollection::getInstance()->getType($new_type_id);
				$new_type->setHierarchyTypeId($hierarchy_type_id);
				$new_type->commit();
			}

			umiImportRelations::getInstance()->setTypeIdRelation($this->source_id, $old_type_id, $new_type_id);

			return $new_type_id;
		}


		protected function compareObjectTypeFields($object_type_id, $fields) {
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			if($object_type === false) {
				trigger_error("Object type #{$object_type_id} not found", E_USER_ERROR);
				return false;
			}


			$diff_count = 0;
			foreach($fields as $field_name) {
				if($object_type->getFieldId($field_name) == false) {
					++$diff_count;
				}
			}

			return $diff_count;
		}



		protected function detectBetterTemplateId($filepath) {
			if($filepath) {
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

				$templates = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
				foreach($templates as $ctpl) {
					if($ctpl->getFilename() == $filepath) {
						return $ctpl->getId();
					}
				}
			}
			return templatesCollection::getInstance()->getDefaultTemplate()->getId();
		}


		/**
			* Импортировать данные в систему.
		*/
		public function importXml() {
			foreach($this->xml_elements as $element_info) {
				$hierarchy_type_id = $element_info['element_hierarchy_type_id'];
				$old_object_id = $element_info['old_element_object_id'];
				$old_object_type_id = $this->xml_objects[$old_object_id]['old_type_id'];
				$type_name =  $this->xml_objects[$old_object_id]['type_name'];

				$element_info['old_type_id'] = $old_object_type_id;
				$element_info['new_type_id'] = $new_type_id = $this->detectBetterObjectType($hierarchy_type_id, $old_object_type_id, $type_name);
				$element_info['new_tpl_id'] = $new_tpl_id = $this->detectBetterTemplateId($element_info['element_filepath']);

				$element_info['new_lang_id'] = cmsController::getInstance()->getCurrentLang()->getId() ;
				$element_info['new_domain_id'] = cmsController::getInstance()->getCurrentDomain()->getId();

				$element_info['element_name'] = $this->xml_objects[$old_object_id]['old_name'];


				$this->importElement($element_info);
			}
		}


		protected function importElement($element_info) {
			$this->importedElements += 1;

			$old_element_id = $element_info['old_element_id'];
			$old_element_object_id = $element_info['old_element_object_id'];

			$new_element_id = umiImportRelations::getInstance()->getNewIdRelation($this->source_id, $old_element_id);

			$oEventPoint = new umiEventPoint("import_element");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("new_element_id", $new_element_id);
			$oEventPoint->setParam("old_element_id", $old_element_id);
			$oEventPoint->addRef("element_info", $element_info);
			$oEventPoint->addRef("props", $this->xml_objects[$old_element_object_id]['props']);
			umiEventsController::getInstance()->callEvent($oEventPoint);

			$old_element_parent_id = $element_info['old_parent_id'];

			$this->importedElementsArr[] = $old_element_id;

			$new_name = $element_info['element_name'];
			$old_element_alt_name = $element_info['old_element_alt_name'];

			$old_object_type_id = $element_info['old_type_id'];

			$element_is_active = $element_info['element_is_active'];
			$element_is_visible = $element_info['element_is_visible'];
			$element_is_deleted = $element_info['element_is_deleted'];

			if($old_element_alt_name) {
				$alt_name = $old_element_alt_name;
			} else {
				$alt_name = $new_name;
			}

			$alt_name = translit::convert($alt_name);

			if($element_is_deleted !== NULL) {
				if($element_is_deleted) {
					umiHierarchy::getInstance()->delElement($new_element_id);
					$this->importLog[] = "Element \"" . $new_name . "\" (" . $old_element_id . ") has been deleted";
					$this->deletedElements++;
					return true;
				}
			}
			if($old_element_parent_id === "0") {
				$new_parent_id = $old_element_parent_id;
			} else {
				$new_parent_id = umiImportRelations::getInstance()->getNewIdRelation($this->source_id, $old_element_parent_id);
			}

			if ($new_parent_id === false) {
				$new_parent_id = $this->destination_element_id;
			}

			$b_created = false;
			if ($new_element_id === false && $new_parent_id !== false) {
				$new_domain_id = $element_info['new_domain_id'];
				$new_lang_id = $element_info['new_lang_id'];
				$new_hierarchy_type_id = $element_info['element_hierarchy_type_id'];
				$new_tpl_id = $element_info['new_tpl_id'];
				$new_type_id = $element_info['new_type_id'];

				$new_element_parent_id = umiImportRelations::getInstance()->getNewIdRelation($this->source_id, $old_element_parent_id);
				$new_element_id = umiHierarchy::getInstance()->addElement($new_parent_id, $new_hierarchy_type_id, $new_name, $alt_name, $new_type_id, $new_domain_id, $new_lang_id, $new_tpl_id);
				umiImportRelations::getInstance()->setIdRelation($this->source_id, $old_element_id, $new_element_id);
				if ($new_element_id) $b_created = true;
			}

			permissionsCollection::getInstance()->setDefaultPermissions($new_element_id);

			$new_element = umiHierarchy::getInstance()->getElement($new_element_id, true, true);

			if (!$new_element instanceof umiHierarchyElement) {
				$this->importLog[] = "Can't create element \"{$new_name}\" ({$old_element_id})";
				$this->importErrors++;
			    return false;
			}

			$oEventPoint = new umiEventPoint("import_element");
			$oEventPoint->setMode("process");
			$oEventPoint->setParam("new_element", $new_element);
			$oEventPoint->setParam("old_element_id", $old_element_id);
			$oEventPoint->addRef("element_info", $element_info);
			$oEventPoint->addRef("props", $this->xml_objects[$old_element_object_id]['props']);
			umiEventsController::getInstance()->callEvent($oEventPoint);

			if($element_is_active !== NULL) {
				$new_element->setIsActive($element_is_active);
			}

			if($element_is_visible !== NULL) {
				$new_element->setIsVisible($element_is_visible);
			}

			if($alt_name) {
				$new_element->setAltName($alt_name);
			}

			if ($new_name) {
				$new_element->setName($new_name);
			}

			$missed_props = Array();
			$props = $this->xml_objects[$old_element_object_id]['props'];

			foreach($props as $prop_name => $prop_info) {
				$prop_value = $prop_info['values'];
				$field_type = $prop_info['field_type'];
				if($field_type == "img_file") {
					if(isset($prop_value[0])) {
						$prop_value[0] = new umiImageFile($prop_value[0]);
					}
				}

				$prop_name = translit::convert($prop_name);

				if($new_element->getObject()->getPropByName($prop_name)) {
					if ($field_type == 'price' && strlen($prop_info['currency_code'])) {
						$emarket = cmsController::getInstance()->getModule('emarket');
						if ($emarket) {
							$price = isset($prop_value[0]) ? floatval($prop_value[0]) : 0;

							$currency = $emarket->getCurrency($prop_info['currency_code']);
							if ($currency) {
								$f_price = $emarket->formatCurrencyPrice(array($price), $emarket->getDefaultCurrency(), $currency);
								$prop_value = isset($f_price[0]) ? floatval($f_price[0]) : 0;
							}
						}
					}
					$new_element->setValue($prop_name, $prop_value);
				} else {

					$missed_props[] = $prop_info;
				}
			}
			$this->addMissedProps($new_element, $missed_props, $old_object_type_id);
			if(count($missed_props)) {
				// reloading object propertyes
				/*
				$element_id = $new_element->getId();
				$object_id = $new_element->getObject()->getId();
				umiHierarchy::getInstance()->unloadElement($element_id);
				umiObjectsCollection::getInstance()->unloadObject($object_id);
				$new_element = umiHierarchy::getInstance()->getElement($element_id, true, true);
				*/

				$new_element->getObject()->update();
			}
			foreach($missed_props as $prop_info) {
				$prop_value = $prop_info['values'];

				$field_type = $prop_info['field_type'];
				if($field_type == "img_file") {
					if($prop_value[0]) {
						$prop_value[0] = new umiImageFile($prop_value[0]);
					}
				}


				if(!$prop_info['name']) $prop_info['name'] = translit::convert($prop_info['title']);
				$prop_info['name'] = translit::convert($prop_info['name']);

				if(!$new_element->setValue($prop_info['name'], $prop_value)) {
					//trigger_error("Value for property '{$prop_info['name']}' not set", E_USER_WARNING);
					continue;
				}

			}

			$new_element->commit();
			if ($b_created) {
				$this->importLog[] = "Element \"" . $new_name . "\" (" . $old_element_id . ") has been created";
				$this->createdElements++;
			} else {
				$this->importLog[] = "Element \"" . $new_name . "\" (" . $old_element_id . ") has been updated";
				$this->updatedElements++;
			}


			$bOldCreateFlag = umiObjectProperty::$USE_FORCE_OBJECTS_CREATION;
			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = false;
			// update store info
			$iStoreTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "store");
			$iStoreRelType = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "store_relation");
			$oEshopInstance = cmsController::getInstance()->getModule("eshop");
			if ($oEshopInstance && $iStoreTypeId && $iStoreRelType) {
				if (isset($this->xml_stores[$old_element_object_id])) {
					foreach ($this->xml_stores[$old_element_object_id] as $arrStoreInfo) {
						$sOldStoreId = $arrStoreInfo['old_store_id'];
						$iAmount = $arrStoreInfo['amount'];
						$iStoreId = $this->getStoreIdByName($sOldStoreId);

						if ($iStoreId === false) {
							$iStoreId = umiObjectsCollection::getInstance()->addObject($sOldStoreId, $iStoreTypeId);
						}

						$oStore = umiObjectsCollection::getInstance()->getObject($iStoreId);
						if ($oStore instanceof umiObject) {
							$oStore->setName($sOldStoreId);
							$oStore->commit();
							$oEshopInstance->setStoreAmount($new_element->getId(), $iStoreId, $iAmount);
						}
					}
				}
			}
			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = $bOldCreateFlag;

			$oEventPoint = new umiEventPoint("import_element");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("new_element_id", $new_element_id);
			$oEventPoint->setParam("old_element_id", $old_element_id);
			$oEventPoint->setParam("element_info", $element_info);
			umiEventsController::getInstance()->callEvent($oEventPoint);

			umiHierarchy::getInstance()->unloadElement($new_element_id);
		}


		protected function getStoreIdByName($sName) {
			$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "store");

			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$sel->addObjectType($object_type_id);

			$sel->setPropertyFilter();
			$sel->addNameFilterEquals($sName);

			$result = umiSelectionsParser::runSelection($sel);

			return isset($result[0]) ? (int) $result[0] : false;
		}

		protected function addMissedProps(&$new_element, $missed_props, $old_object_type_id) {
			if (strlen($old_object_type_id)) {
				$object_type_id = umiImportRelations::getInstance()->getNewTypeIdRelation($this->source_id, $old_object_type_id);
			} else {
				$object_type_id = $new_element->getObject()->getTypeId();
			}

			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);


			foreach($missed_props as $missed_prop) {
				$prop_block_title = $missed_prop['prop_block_title'];
				$prop_block_name = $missed_prop['prop_block_name'];
				$prop_block_is_public = $missed_prop['prop_block_is_public'];

				if(!$prop_block_name) {
					if($prop_block_title) {
						$prop_block_name = translit::convert($prop_block_title);
					} else {
						$prop_block_title = "Imported fields group";
						$prop_block_name = "imported";
					}
				}


				if($prop_group_block = $object_type->getFieldsGroupByName($prop_block_name)) {
				} else {
					$prop_group_block_id = $object_type->addFieldsGroup($prop_block_name, $prop_block_name, true, $prop_block_is_public);
					$prop_group_block = $object_type->getFieldsGroup($prop_group_block_id);
					$prop_group_block->setTitle($prop_block_title);
					$prop_group_block->commit();
				}

				if(!$missed_prop['field_type']) {
					$missed_prop['field_type'] = "string";
				}

				$field_type_id = $this->getFieldTypeId($missed_prop['field_type'], $missed_prop['is_multiple']);

				if($field_type_id === false) continue;


				$missed_prop['name'] = (string) $missed_prop['name'];

				if(!$missed_prop['name']) {
					$missed_prop['name'] = translit::convert($missed_prop['title']);
				}

				$missed_prop['name'] = translit::convert($missed_prop['name']);

				if($object_type_id) {
					if(umiImportRelations::getInstance()->getNewFieldId($this->source_id, $object_type_id, $missed_prop['name'])) {
						continue;
					}
				}


				if($missed_prop['field_type'] == "relation") {
					$guide_id = self::getAutoGuideId($missed_prop['title']);
				} else {
					$guide_id = false;
				}

				$field_id = umiFieldsCollection::getInstance()->addField($missed_prop['name'], $missed_prop['title'], $field_type_id, $missed_prop['is_public'], false);
				$field = umiFieldsCollection::getInstance()->getField($field_id);
				$field->setTip($missed_prop['tip']);

				if($guide_id) {
					$field->setGuideId($guide_id);
				}

				$field->commit();

				$prop_group_block->attachField($field_id);

				if($object_type_id) {
					umiImportRelations::getInstance()->setFieldIdRelation($this->source_id, $object_type_id, $missed_prop['name'], $field_id);
				}
			}
		}


		protected function getFieldTypeId($data_type, $is_multiple = false) {
			$field_types = umiFieldTypesCollection::getInstance()->getFieldTypesList();

			foreach($field_types as $field_type) {
				if($field_type->getDataType() == $data_type && $field_type->getIsMultiple() == $is_multiple) {
					return $field_type->getId();
				}
			}

			return false;
		}


		public function getAutoGuideId($title) {
			$guide_name = "Справочник для поля \"{$title}\"";

			$typesCollection = umiObjectTypesCollection::getInstance();
			$rootGuideId = $typesCollection->getTypeIdByGUID('root-guides-type');

			$child_types = $typesCollection->getChildClasses($rootGuideId);
			foreach($child_types as $child_type_id) {
				$child_type = umiObjectTypesCollection::getInstance()->getType($child_type_id);
				$child_type_name = $child_type->getName();

				if($child_type_name == $guide_name) {
					$child_type->setIsGuidable(true);
					return $child_type_id;
				}
			}

			$guide_id = umiObjectTypesCollection::getInstance()->addType($rootGuideId, $guide_name);
			$guide = umiObjectTypesCollection::getInstance()->getType($guide_id);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();

			return $guide_id;
		}

		/**
			* Получить количество импортированных в систему страниц
			* @return Integer количество импортированных страниц
			* !Deprecated не используется, т.к. не учитывает ошибки. Следует использовать getCreatedElemensCount, getUpdatedElementsCount
		*/
		public function getImportedElementsCount() {
			return $this->importedElements;
		}

		/**
			* Получить количество созданных импортером элементов
			* @return Integer количество созданных элементов
		*/
		public function getCreatedElementsCount() {
			return $this->createdElements;
		}

		/**
			* Получить количество обновленных импортером элементов
			* @return Integer количество обновленных элементов
		*/
		public function getUpdatedElementsCount() {
			return $this->updatedElements;
		}

		/**
			* Получить количество удаленных импортером элементов
			* @return Integer количество удаленных элементов
		*/
		public function getDeletedElementsCount() {
			return $this->deletedElements;
		}

		/**
			* Получить количество ошибок, при которых элементы не были созданы
			* @return Integer количество ошибок
		*/
		public function getImportErrorsCount() {
			return $this->importErrors;
		}

		/**
			* Получить лог импотра ввиде массива строк
		*/
		public function getImportLog() {
			return $this->importLog;
		}
	};
?>