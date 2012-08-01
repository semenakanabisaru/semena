<?php
	class xmlImporter implements iXmlImporter {
		const VERSION = "2.0";
		const ROOT_PAGE_TYPE_ID = 3; // id корневого типа "Раздел сайта"


		protected $doc, $parser;
		protected $relations, $source_id;
		protected $destination_element_id = 0;

		protected $update_ignore = false;
		protected $demosite_mode = false;

		protected $meta = array();

		// counters
		public		$updated_types = 0, $created_types = 0,
					$updated_languages = 0, $created_languages = 0,
					$updated_domains = 0, $created_domains = 0,
					$updated_domain_mirrors = 0, $created_domain_mirrors = 0,
					$updated_templates = 0, $created_templates = 0,
					$updated_objects = 0, $created_objects = 0, $deleted_objects = 0,
					$updated_elements = 0, $created_elements = 0, $deleted_elements = 0,
					$copied_files = 0,
					$update_relations = 0,
					$created_restrictions = 0,
					$created_registry_items = 0,
					$created_permissions = 0,
					$created_field_types = 0,
					$created_dirs = 0,
					$import_errors = 0;

		public $filesSource = false;

		public $ignoreParentGroups = true;
		public $auto_guide_creation = false;
		public $renameFiles = false;

		protected	$import_log = array();
		protected	$imported_elements = array();

		public function __construct($source_name = false) {
			if ($source_name) $this->meta['source-name'] = $source_name;
			//umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
			$this->doc = new DomDocument("1.0", "utf-8");
			$this->relations = umiImportRelations::getInstance();
		}


		/**
		* Установить/откючить такой режим работы импортера, при котором обновление существующих объектов системы будет игнорироваться. Объекты системы только создаются.
		*
		* @param boolean $update_ignore
		*/
		public function setUpdateIgnoreMode($update_ignore = true) {
			$this->update_ignore = (bool) $update_ignore;
		}

		public function setAutoGuideCreation($auto_guide_creation = false) {
			$this->auto_guide_creation = (bool) $auto_guide_creation;
		}

		public function setRenameFiles($renameFiles = false) {
			$this->renameFiles = (bool) $renameFiles;
		}

		public function setDemositeMode ($demosite_mode = true){
			$this->demosite_mode = $demosite_mode;
		}

		public function loadXmlString($xml_string) {
			return $this->doc->loadXML($xml_string, DOM_LOAD_OPTIONS);
		}

		protected function getLabel($i18n) {
			$label = getLabel($i18n);
			if (!$label || $label == $i18n) {
				$label = str_replace('label-', '', $i18n);
				$label = preg_replace("/(.*?)-[m,f,n]+$/", "$1", $label);
				$label = str_replace('-', ' ', $label);
			}
			return $label;
		}

		public function loadXmlFile($xml_filepath) {
			if(!is_file($xml_filepath)) {
				throw new publicException($this->getLabel('label-cannot-read-file') . ' ' . $xml_filepath);
			}

			return $this->doc->load($xml_filepath, DOM_LOAD_OPTIONS);
		}

		public function loadXmlDocument(DOMDocument $doc) {
			$this->doc = $doc;
		}

		/**
			* Устанавливает элемент, в который будут попадать элементы, у которых в дампе не существует родителя.
			* По умолчанию такие элементы попадают в корень сайта
			* @param Variant Id элемента, либо сам элемент
			* @return Boolean true, если удалось установить значение
		*/
		public function setDestinationElement($element) {
			if ($element instanceof umiHierarchyElement) {
				$this->destination_element_id = $element->getId();
				return true;
			}
			if (umiHierarchy::getInstance()->getElement($element, true, true) instanceof umiHierarchyElement) {
				$this->destination_element_id = $element;
				return true;
			}
			return false;
		}

		public function execute() {
			$OLD_CACHE_STATE = cmsController::$IGNORE_MICROCACHE;
			$OLD_IGNORE_FILTER_INPUT_STRING = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
			cmsController::$IGNORE_MICROCACHE = true;
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;

			$config = mainConfiguration::getInstance();
			if(!$config->get('kernel', 'import-auto-index')) {
				if(!defined('DISABLE_SEARCH_REINDEX')) {
					define('DISABLE_SEARCH_REINDEX', 1);
				}
			}

			$old_creation = umiObjectProperty::$USE_FORCE_OBJECTS_CREATION;
			if ($this->auto_guide_creation) {
				umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;
			} else {
				umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = false;
			}


			$this->parser = new DOMXPath($this->doc);
			// check version
			$nl = $this->parser->evaluate("/umidump/@version");
			$version = $nl->length ? $nl->item(0)->nodeValue : "";
			if ($version != self::VERSION) {
				throw new publicException($this->getLabel("label-unknown-umidump-version"));
			}

			$this->parseMetaData();

			// set relations
			$this->source_id = $this->relations->getSourceId($this->meta['source-name']);
			if (!$this->source_id) {
				$this->source_id = $this->relations->addNewSource($this->meta['source-name']);
			}

			$this->importRegistry();
			$this->importDirs();
			if ($this->filesSource) $this->importFiles();
			$this->importLangs();
			$this->importDomains();
			$this->importTemplates();
			$this->importDataTypes();
			$this->importTypes();
			$this->importObjects();
			//$this->importBranches();
			$this->importElements();
			$this->importRelations();
			$this->importOptions();
			$this->importRestrictions();
			$this->setDefaultPermissions();
			$this->importPermissions();
			$this->importHierarchy();

			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = $old_creation;
			cmsController::$IGNORE_MICROCACHE = $OLD_CACHE_STATE;
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $OLD_IGNORE_FILTER_INPUT_STRING;

		}

		public function setFilesSource($filesSource) {
			if(!is_dir($filesSource)) {
				throw new coreException($this->getLabel("label-cannot-find-files-source"));

			}
			$this->filesSource = $filesSource;
		}

		public function setIgnoreParentGroups($ignoreParentGroups) {
			$this->ignoreParentGroups = $ignoreParentGroups;
		}

		protected function reportError($error) {
			$this->import_errors++;
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $error . "\n\r";
			else $this->import_log[] = "<font style='color:red''>" . $error . "</font>";
		}

		protected function writeLog($message) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $message . "\n\r";
			else $this->import_log[] = $message;
		}

		public function getImportLog() {

			return $this->import_log;
		}

		protected function parseMetaData() {
			$m = $this->meta;

			$nl = $this->parser->evaluate("/umidump/meta/site-name");
			$m['site-name'] =  $nl->length ? $nl->item(0)->nodeValue : "";

			$nl = $this->parser->evaluate("/umidump/meta/domain");
			$m['domain'] =  $nl->length ? $nl->item(0)->nodeValue : "";

			$nl = $this->parser->evaluate("/umidump/meta/lang");
			$m['lang'] =  $nl->length ? $nl->item(0)->nodeValue : "";

			if (!isset($m['source-name'])) {
				$nl = $this->parser->evaluate("/umidump/meta/source-name");
				$m['source-name'] =  $nl->length ? $nl->item(0)->nodeValue : md5($m['domain']);
			}

			$nl = $this->parser->evaluate("/umidump/meta/generate-time/timestamp");
			$m['generated'] =  $nl->length ? $nl->item(0)->nodeValue : "";

			$nl = $this->parser->evaluate("/umidump/meta/branches/id");
			$m['branches'] = array();
			foreach ($nl as $node) {
				$m['branches'][] = $node->nodeValue;
			}

			$this->meta = $m;
		}

		protected function importHierarchyType($base_module, $base_method, $base_title) {
			$collection = umiHierarchyTypesCollection::getInstance();
			$hierarchyType = $collection->getTypeByName($base_module, $base_method);
			if (!$hierarchyType instanceof umiHierarchyType) {
				$id = $collection->addType($base_module, $base_title, $base_method);
				$hierarchyType = $collection->getTypeByName($base_module, $base_method);
			}

			if (!$hierarchyType instanceof umiHierarchyType) {
				throw new coreException($this->getLabel("label-cannot-create-hierarchy-type") . "{$base_module}/{$base_method} ({$base_title})");
			}

			return $hierarchyType;
		}

		protected function importTypes() {
			$nl_types = $this->parser->evaluate("/umidump/types/type");
			foreach ($nl_types as $type_info) {
				$this->importType($type_info);
			}
		}

		protected function importType(DOMElement $type_info) {
			$old_id = $type_info->getAttribute('id');
			$type_name = $type_info->hasAttribute('title') ? $type_info->getAttribute('title') : null;

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-type') . " \"{$type_name}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$old_parent_id = $type_info->getAttribute('parent-id');
			$is_guidable = $type_info->hasAttribute('guide') ? $type_info->getAttribute('guide') : null;
			$is_public = $type_info->hasAttribute('public') ? $type_info->getAttribute('public') : null;
			$is_locked = $type_info->hasAttribute('locked') ? $type_info->getAttribute('locked') : null;
			$guid = $type_info->hasAttribute('guid') ? $type_info->getAttribute('guid') : null;

			$collection = umiObjectTypesCollection::getInstance();

			$created = false;
			$new_type_id = false;

			if (!is_null($guid)) {
				$new_type_id = $collection->getTypeIdByGUID($guid);
				if ($new_type_id && $new_type_id != $this->relations->getNewTypeIdRelation($this->source_id, $old_id)) {
					$this->relations->setTypeIdRelation($this->source_id, $old_id, $new_type_id);
				}
			}
			if (!$new_type_id) $new_type_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_id);

			if ($new_type_id && $this->update_ignore) {
				$this->writeLog($this->getLabel("label-datatype") . " \"" . $type_name . "\" ". $this->getLabel('label-already-exists'));
				return $collection->getType($new_type_id);
			}

			if(!$new_type_id) {

				$new_parent_type_id = (trim($old_parent_id, "{}") == 'root-pages-type') ? $collection->getTypeIdByGUID('root-pages-type') : $this->relations->getNewTypeIdRelation($this->source_id, $old_parent_id);

				if (is_null($type_name)) {
					$type_name = "Type #" . $old_id;
				}

				// import hierarchy type
				$nl = $this->parser->evaluate("base", $type_info);
				$base = $nl->length ? $nl->item(0) : false;

				$base_module = $base ? $base->getAttribute('module') : false;
				$base_method = $base ? $base->getAttribute('method') : false;
				$base_title = $base->nodeValue;

				$hierarchyType = false;
				if (strlen($base_module)) {
					$hierarchyType = $this->importHierarchyType($base_module, $base_method, $base_title);
					$main_object_type_id = $collection->getTypeByHierarchyTypeId($hierarchyType->getId());
					$mainObjectType = $collection->getType($main_object_type_id);

					if (trim($old_parent_id, "{}") == 'root-pages-type' && $mainObjectType instanceof umiObjectType) {
						$new_type_id = $mainObjectType->getId();
					} elseif (!$new_parent_type_id && $mainObjectType instanceof umiObjectType) {
						$new_parent_type_id = $mainObjectType->getId();
					}

				}

				if (!$new_type_id) $new_type_id = $collection->addType(intval($new_parent_type_id), trim($type_name), false, $this->ignoreParentGroups);
				$type = $collection->getType($new_type_id);
				if ($hierarchyType) $type->setHierarchyTypeId($hierarchyType->getId());

				$created = true;

				if (!is_null($guid)) $collection->getType($new_type_id)->setGUID($guid);
				$this->relations->setTypeIdRelation($this->source_id, $old_id, $new_type_id);

			}

			// import type groups and fields
			$type = $collection->getType($new_type_id);
			if (!$type instanceof umiObjectType) {
				$this->reportError($this->getLabel('label-cannot-detect-type') . $this->getLabel('label-datatype') . "{$type_name} ({$old_id})");
				return false;
			}

			if (!is_null($type_name)) $type->setName(trim($type_name));
			if (!is_null($is_public)) $type->setIsPublic($is_public == 'public' || $is_public == "1");
			if (!is_null($is_guidable)) $type->setIsGuidable($is_guidable == 'guide' || $is_guidable == "1");
			if (!is_null($is_locked)) $type->setIsLocked($is_locked == 'locked' || $is_locked == "1");
			$type->commit();


			if ($created) {
				$this->created_types++;
				$this->writeLog($this->getLabel('label-datatype') . " \"" . $type_name . "\" (" . $old_id . ") ". $this->getLabel('label-has-been-created-m'));
			} else {
				$this->updated_types++;
				$this->writeLog($this->getLabel('label-datatype') . " \"" . $type_name . "\" (" . $old_id . ") ". $this->getLabel('label-has-been-updated-m'));
			}

			$this->importTypeGroups($type, $type_info);

			return $type;
		}

		protected function importTypeGroups(umiObjectType $type, DOMElement $type_info) {
			$nl = $this->parser->evaluate("fieldgroups/group", $type_info);
			foreach ($nl as $group_info) {
				$this->importTypeGroup($type, $group_info);
			}
		}


		protected function importTypeGroup(umiObjectType $type, DOMElement $group_info, $import_fields = true) {
			$old_group_name = $group_info->getAttribute('name');
			if (!strlen($old_group_name)) return false;

			$new_group_name = self::translateName($old_group_name);

			$title = $group_info->hasAttribute('title') ? $group_info->getAttribute('title') : null;
			$is_visible = $group_info->hasAttribute('visible') ? $group_info->getAttribute('visible') : null;
			$is_locked = $group_info->hasAttribute('locked') ? $group_info->getAttribute('locked') : null;
			$is_active = $group_info->hasAttribute('active') ? $group_info->getAttribute('active') : null;

			$group = null;

			$group_id = $this->relations->getNewGroupId($this->source_id, $type->getId(), $old_group_name);
			if ($group_id) $group = $type->getFieldsGroup($group_id, true);

			if (!$group_id) {
				$group = $type->getFieldsGroupByName($new_group_name, true);
				if ($group) $this->relations->setGroupIdRelation($this->source_id, $type->getId(), $old_group_name, $group->getId());
			}

			if (!$group instanceof umiFieldsGroup) {
				if (is_null($title)) {
					$title = "Group #" . $old_group_name;
				}
				$group_id = $type->addFieldsGroup($new_group_name, trim($title), true, false);
				$this->relations->setGroupIdRelation($this->source_id, $type->getId(), $old_group_name, $group_id);
				$group = $type->getFieldsGroup($group_id, true);
			}

			if (!$group instanceof umiFieldsGroup) {
				$this->reportError($this->getLabel('label-cannot-import-group') . "{$old_group_name}:" . $this->getLabel('label-cannot-detect-group'));
				return false;
			}

			if (!is_null($title)) $group->setTitle(trim($title));
			if (!is_null($is_visible)) $group->setIsVisible($is_visible == 'visible' || $is_visible == '1');
			if (!is_null($is_active)) $group->setIsActive($is_active == 'active' || $is_active == '1');
			if (!is_null($is_locked)) $group->setIsLocked($is_locked == 'locked' || $is_locked == '1');
			$group->commit();

			if ($import_fields) $this->importGroupFields($group, $group_info);

			return $group;
		}

		protected function importGroupFields(umiFieldsGroup $group, DOMElement $group_info) {
			$nl = $this->parser->evaluate("field", $group_info);
			foreach ($nl as $field_info) {
				$this->importField($group, $field_info);
			}
		}

		protected function getFieldByName(umiFieldsGroup $group, $name) {
			$fields = $group->getFields();
			foreach ($fields as $field) {
				if ($field->getName() == $name) return $field;
			}
			return false;
		}

		protected function importFieldType($field_type_info) {
			$name = $field_type_info->getAttribute('name');
			$data_type = $field_type_info->getAttribute('data-type');
			$multiple = $field_type_info->getAttribute('multiple') == 1 || $field_type_info->getAttribute('multiple') == "multiple";
			if (!strlen($data_type)) return false;

			$collection = umiFieldTypesCollection::getInstance();
			$field_type = $collection->getFieldTypeByDataType($data_type, $multiple);

			$created = false;
			if (!$field_type instanceof  umiFieldType) {
				$field_type_id = $collection->addFieldType($name, $data_type);
				$field_type = $collection->getFieldType($field_type_id);
				$field_type->setIsMultiple($multiple);
				$field_type->commit();
				$created = true;
			}

			if ($created) {
				$this->created_field_types++;
				$this->writeLog($this->getLabel('label-field-type') . "\"" . $field_type->getName() . "\"" . $this->getLabel('label-has-been-created-m'));
			}

			return $field_type;
		}


		public function getAutoGuideId($title) {
			$guide_name = "Справочник для поля \"{$title}\"";

			$collection = umiObjectTypesCollection::getInstance();
			$parentTypeId = $collection->getTypeIdByGUID('root-guides-type');
			$child_types = $collection->getChildClasses($parentTypeId);
			foreach($child_types as $child_type_id) {
				$child_type = $collection->getType($child_type_id);
				$child_type_name = $child_type->getName();

				if($child_type_name == $guide_name) {
					$child_type->setIsGuidable(true);
					return $child_type_id;
				}
			}

			$guide_id = $collection->addType($parentTypeId, $guide_name);
			$guide = $collection->getType($guide_id);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();

			return $guide_id;
		}

		protected function importField(umiFieldsGroup $group, DOMElement $field_info) {
			$old_field_name = $field_info->getAttribute('name');
			if (!strlen($old_field_name)) {
				$this->reportError($this->getLabel('label-cannot-import-field-with-empty-name'));
				return false;
			}

			$title = $field_info->hasAttribute('title') ? $field_info->getAttribute('title') : null;
			$tip = $field_info->hasAttribute('tip') ? $field_info->getAttribute('tip') : null;
			$is_visible = $field_info->hasAttribute('visible') ? $field_info->getAttribute('visible') : null;
			$is_locked = $field_info->hasAttribute('locked') ? $field_info->getAttribute('locked') : null;
			$is_inheritable = $field_info->hasAttribute('inheritable') ? $field_info->getAttribute('inheritable') : null;
			$is_indexable = $field_info->hasAttribute('indexable') ? $field_info->getAttribute('indexable') : null;
			$is_filterable = $field_info->hasAttribute('filterable') ? $field_info->getAttribute('filterable') : null;
			$is_required = $field_info->hasAttribute('required') ? $field_info->getAttribute('required') : null;
			$is_system = $field_info->hasAttribute('system') ? $field_info->getAttribute('system') : null;

			$new_field_name = self::translateName($old_field_name);
			$object_type_id = $group->getTypeId();

			$collection = umiFieldsCollection::getInstance();
			$types_collection = umiObjectTypesCollection::getInstance();

			$field = null;

			$nl = $this->parser->evaluate("type", $field_info);
			$field_type_info = $nl->length ? $nl->item(0): false;
			if (!$field_type_info) {
				$this->reportError($this->getLabel('label-cannot-import-field') . " {$old_field_name}: " . $this->getLabel('label-cannot-detect-datatype'));
				return false;
			}

			$field_type = $this->importFieldType($field_type_info);
			if (!$field_type instanceof umiFieldType) {
				$this->reportError($this->getLabel('label-cannot-detect-field-type-for') . " {$old_field_name}");
				return false;
			}

			$field_type_id = $field_type->getId();

			$object_type = $types_collection->getType($object_type_id);

			$field_id = $object_type->getFieldId($new_field_name, false);
			if ($field_id) {
				$field = $collection->getField($field_id);
				if($field instanceof umiField && $field_id != $this->relations->getNewFieldId($this->source_id, $object_type_id, $old_field_name)){
					$this->relations->setFieldIdRelation($this->source_id, $object_type_id, $old_field_name, $field_id);
				}
			}

			if(!$field instanceof umiField) {
				$parent_type_id = $object_type->getParentId();
				if ($parent_type_id) {
					$parent_type = $types_collection->getType($parent_type_id);
					$parent_field_id = $parent_type->getFieldId($new_field_name, false);

					if ($parent_field_id) {
						$parentField = $collection->getField($parent_field_id, false);
						if ($parentField->getFieldTypeId() == $field_type_id && $parentField->getTitle() == $title) {
							$field = $parentField;
							$group->attachField($parent_field_id);
							$this->relations->setFieldIdRelation($this->source_id, $object_type_id, $old_field_name, $field->getId());
						}

					}

					if (!$field instanceof umiField) {

						$horisontalTypes = $types_collection->getSubTypesList($parent_type_id);
						foreach($horisontalTypes as $horisontalTypeId) {
							if($horisontalTypeId == $object_type_id) continue;
							$horisontalType = $types_collection->getType($horisontalTypeId);
							if($horisontalType instanceof umiObjectType == false) continue;

							if($horisontalFieldId = $horisontalType->getFieldId($new_field_name, false)) {
								$horisontalField = $collection->getField($horisontalFieldId);
								if($horisontalField instanceof umiField == false) continue;
								if ($horisontalField->getFieldTypeId() == $field_type_id && $horisontalField->getTitle() == $title) {
									$field = $horisontalField;
									$group->attachField($horisontalFieldId);
									$this->relations->setFieldIdRelation($this->source_id, $object_type_id, $old_field_name, $field->getId());
									break;
								}
							}
						}
					}
				}
			}

			if (!$field instanceof umiField) {
				if (is_null($title)) {
					$title = $old_field_name;
				}
				$field_id = $collection->addField($new_field_name, trim($title), $field_type_id, false, false, false);
				$this->relations->setFieldIdRelation($this->source_id, $object_type_id, $old_field_name, $field_id);

				$group->attachField($field_id);
				$field = $collection->getField($field_id);
				if (is_null($is_visible)) $field->setIsVisible($is_visible);
				if (is_null($is_filterable)) $field->setIsInFilter($is_filterable);
				if (is_null($is_indexable)) $field->setIsInSearch($is_indexable);
			}

			if (($field_type->getDataType() == 'relation' || $field_type->getDataType() == 'optioned') && $this->auto_guide_creation) {
				$field->setGuideId($this->getAutoGuideId($title));
			}

			if ($field->getFieldTypeId() != $field_type_id) $field->setFieldTypeId($field_type_id);

			if (!is_null($title)) $field->setTitle(trim($title));
			if (!is_null($is_visible)) $field->setIsVisible($is_visible == 'visible' || $is_visible == "1");
			if (!is_null($is_indexable)) $field->setIsInSearch($is_indexable == 'indexable' || $is_indexable == "1");
			if (!is_null($is_filterable)) $field->setIsInFilter($is_filterable == 'filterable' || $is_filterable == "1");
			if (!is_null($is_required)) $field->setIsRequired($is_required == 'required' || $is_required == "1");
			if (!is_null($is_system)) $field->setIsSystem($is_system == 'system' || $is_system == "1");
			if (!is_null($tip)) $field->setTip(trim($tip));
			if (!is_null($is_locked)) $field->setIsLocked($is_locked == 'locked' || $is_locked == "1");
			if (!is_null($is_inheritable)) $field->setIsInheritable($is_inheritable == 'inheritable' || $is_inheritable == "1");
			$tips = $this->parser->evaluate("tip", $field_info);
			$tip = $tips->length ? $tips->item(0): false;
			if ($tip) $field->setTip($tip->nodeValue);

			$field->commit();

			return $field;
		}


		protected function importBranches() {
			foreach ($this->meta['branches'] as $id) {
				$this->importBranch($id);
			}
		}

		protected function importElements() {
			$nl = $this->parser->evaluate("/umidump/pages/page");
			foreach ($nl as $info) {
				$this->importElement($info);
			}
		}


		protected function importBranch($id) {
			$nl = $this->parser->evaluate("/umidump/pages/page[@id = '" . $id . "']");
			if (!$nl->length) return false; //TODO: write log
			$this->importElement($nl->item(0));
			$nl = $this->parser->evaluate("/umidump/pages/page[@parentId = '" . $id . "']");
			foreach ($nl as $info) {
				$child_id = $info->getAttribute('id');
				if ($child_id) $this->importBranch($child_id);
			}
		}

		protected function detectTemplateId($filepath, $domain_id = false, $lang_id = false) {
			if($filepath) {
				if ($domain_id === false) $domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
				if ($lang_id === false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();

				$templates = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
				foreach($templates as $ctpl) {
					if($ctpl->getFilename() == $filepath) {
						return $ctpl->getId();
					}
				}
			}

			return false;
		}

		protected function importHierarchyRelation(DOMElement $info) {
			$old_id = $info->getAttribute('id');
			$old_parent_id = $info->getAttribute('parent-id');
			$ord = $info->getAttribute('ord');

			$element_id = $this->relations->getNewIdRelation($this->source_id, $old_id);

			if ($old_parent_id) {
				$parent_id = $this->relations->getNewIdRelation($this->source_id, $old_parent_id);
			} else {
				 $parent_id = $this->destination_element_id;
			}

			if($element_id) {

				$collection = umiHierarchy::getInstance();
				$element = $collection->getElement($element_id, true, true);
				if (!$element instanceof umiHierarchyElement) return false;

				if ($parent_id) $element->setRel($parent_id);
				if ($ord) $element->setOrd($ord);
				$element->commit();
			}
		}

		protected function importElement(DOMElement $info) {
			$old_id = $info->getAttribute('id');
			$old_type_id = $info->getAttribute('type-id');
			$update_only = $info->getAttribute('update-only') == '1';

			$nl = $info->getElementsByTagName('name');
			$name = $nl->length ? $nl->item(0)->nodeValue : false;

			$nl = $info->getElementsByTagName('template');
			$template = $nl->length ? $nl->item(0)->nodeValue : null;

			if (!strlen($old_id)) {
				$this->reportError("Can't create element \"{$name}\" with empty id");
				return false;
			}

			$alt_name = $info->getAttribute('alt-name');
			if (!strlen($alt_name)) $alt_name = $name;
			$is_active = $info->hasAttribute('is-active') ? $info->getAttribute('is-active') : null;
			$old_parent_id = $info->hasAttribute('parentId') ? $info->getAttribute('parentId') : null;
			$is_visible = $info->hasAttribute('is-visible') ? $info->getAttribute('is-visible') : null;
			$is_deleted = $info->hasAttribute('is-deleted') ? $info->getAttribute('is-deleted') : null;
			$lang_id = $info->hasAttribute('lang-id') ? $info->getAttribute('lang-id') : false;
			$domain_id = $info->hasAttribute('domain-id') ? $info->getAttribute('domain-id') : false;
			$is_default = $info->hasAttribute('is-default') ? $info->getAttribute('is-default') : false;

			if($domain_id) $domain_id = $this->relations->getNewDomainIdRelation($this->source_id, $domain_id);
			if($lang_id) $lang_id = $this->relations->getNewLangIdRelation($this->source_id, $lang_id);

			$collection = umiHierarchy::getInstance();
			$types_collection = umiObjectTypesCollection::getInstance();

			$created = false;
			$element_id = $this->relations->getNewIdRelation($this->source_id, $old_id);

			if ($element_id && $this->update_ignore) {
				$this->writeLog("Element \"" . $name . "\" (#{$old_id}) already exists");
				return $collection->getElement($element_id);
			}

			if (!$element_id) {
				if ($update_only) {
					return false;
				}

				if (!$name) $name = $old_id;
				if (!strlen($old_type_id)) {
					$this->reportError($this->getLabel('label-cannot-create-element') . ": \"{$name}\" (#{$old_id}) ." . $this->getLabel('label-cannot-detect-type'));
					return false;
				}
				$type_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_type_id);
				$type = $types_collection->getType($type_id);

				if (!$type instanceof umiObjectType) {
					$this->reportError($this->getLabel('label-cannot-create-element') . "\"{$name}\" ($old_id): " . $this->getLabel('label-cannot-detect-type') . " #{$old_type_id}");
					return false;
				}

				$hierarchyTypeId = $type->getHierarchyTypeId();
				if ($hierarchyTypeId) {
					$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
					if ($hierarchyType instanceof umiHierarchyType) {
						$module = $hierarchyType->getModule();
						if (!regedit::getInstance()->getVal("//modules/{$module}")) return false;
					}
				}

				$parent_id = false;
				if ($old_parent_id) $parent_id = $this->relations->getNewIdRelation($this->source_id, $old_parent_id);
				if ($parent_id === false) $parent_id = $this->destination_element_id;

				// call event on before add element
				$oEventPoint = new umiEventPoint("exchangeOnAddElement");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("before");
				$oEventPoint->setParam("parent_id", $parent_id);
				$oEventPoint->setParam("old_element_id", $old_id);
				$oEventPoint->setParam("type", $type);
				$oEventPoint->setParam("element_info", $info);

				umiEventsController::getInstance()->callEvent($oEventPoint);

				$element_id = $collection->addElement($parent_id, $type->getHierarchyTypeId(), $name, $alt_name, $type->getId(), $domain_id, $lang_id);

				$this->imported_elements[] = $element_id;
				$this->relations->setIdRelation($this->source_id, $old_id, $element_id);

				// default activity
				if (is_null($is_active)) {
					$nl = $info->getElementsByTagName('default-active');
					if ($nl->length) $is_active = $nl->item(0)->nodeValue;
				}
				// default visible
				if (is_null($is_visible)) {
					$nl = $info->getElementsByTagName('default-visible');
					if ($nl->length) $is_visible = $nl->item(0)->nodeValue;
				}

				// default template
				if (is_null($template)) {
					$nl = $info->getElementsByTagName('default-template');
					if ($nl->length) $template = $nl->item(0)->nodeValue;
				}

				$created = true;
			}

			$element = $collection->getElement($element_id, true, true);
			if (!$element instanceof umiHierarchyElement) return false;


			if (!$created) {
				// call on before update element
				$oEventPoint = new umiEventPoint("exchangeOnUpdateElement");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("before");
				$oEventPoint->addRef("element", $element);
				$oEventPoint->setParam("element_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			}

			if ($name) $element->setName($name);
			if (!is_null($is_active)) $element->setIsActive($is_active == 'active' || $is_active == '1');
			if (!is_null($is_visible)) $element->setIsVisible($is_visible == 'visible' || $is_visible == '1');
			if ($is_default) $element->setIsDefault($is_default == 'default' || $is_default == '1');

			if (!is_null($template) && $tpl_id = $this->detectTemplateId($template, $domain_id, $lang_id)) {
			$element->setTplId($tpl_id);
			}

			if ($created) {
				$old_object_id = $info->hasAttribute('object-id') ? $info->getAttribute('object-id') : null;
				if (!is_null($old_object_id)) {
					$object_id = $element->getObjectId();
					$new_object_id = $this->relations->getNewObjectIdRelation($this->source_id, $old_object_id);
					if ($new_object_id) {
						$object = umiObjectsCollection::getInstance()->getObject($new_object_id);
						if ($element->getObjectTypeId() == $object->getTypeId()) {
							$element->setObject($object);
							$element->commit();
							umiObjectsCollection::getInstance()->delObject($object_id);
						} else {
							$this->relations->setObjectIdRelation($this->source_id, $old_object_id, $object_id);
						}
					} else {
						$this->relations->setObjectIdRelation($this->source_id, $old_object_id, $object_id);
					}
				}
			}

			if ($is_deleted == 'deleted' || $is_deleted == '1') $element->setIsDeleted(true);

			$this->importPropValues($element, $info, $created);

			if ($is_deleted && ($is_deleted == 'deleted' || $is_deleted == '1')) {
				$this->deleted_elements++;
				$this->writeLog($this->getLabel("label-page") . " \"" . $element->getName() . "\" (" . $old_id . ") ". $this->getLabel("label-has-been-deleted-m"));
			} elseif ($created) {
				$this->created_elements++;
				$this->writeLog($this->getLabel("label-page") . " \"" . $element->getName() . "\" (" . $old_id . ") " . $this->getLabel("label-has-been-created-f"));
			} elseif($element->getIsUpdated()) {
				$this->updated_elements++;
				$this->writeLog($this->getLabel("label-page") . " \"" . $element->getName() . "\" (" . $old_id . ") " . $this->getLabel("label-has-been-updated-f"));
			}

			if ($created) {
				// call event on after add element
				$oEventPoint = new umiEventPoint("exchangeOnAddElement");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("after");
				$oEventPoint->addRef("element", $element);
				$oEventPoint->setParam("element_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			} else {
				// call event on after update element
				$oEventPoint = new umiEventPoint("exchangeOnUpdateElement");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("after");
				$oEventPoint->addRef("element", $element);
				$oEventPoint->setParam("element_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			}

			$element->commit();

			$collection->unloadElement($element_id);

			return $element;
		}

		protected function importLang(DOMElement $info) {
			$old_id = $info->getAttribute('id');
			$title = $info->nodeValue;

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-language') . " \"{$title}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$is_default = $info->hasAttribute('is-default') ? $info->getAttribute('is-default') : null;
			$prefix = $info->hasAttribute('prefix') ? $info->getAttribute('prefix') : null;

			$collection = langsCollection::getInstance();

			$created = false;
			$language_id = $this->relations->getNewLangIdRelation($this->source_id, $old_id);

			if (!$language_id) {
				if($prefix) {
					$language_id = $collection->getLangId($prefix);
					if ($language_id) $this->relations->setLangIdRelation($this->source_id, $old_id, $language_id);
				}
			}

			if (!$language_id) {

				if (!$title) $title = $old_id;
				$language_id = $collection->addLang($prefix, $title);

				$this->relations->setLangIdRelation($this->source_id, $old_id, $language_id);
				$created = true;
			}

			$language = $collection->getLang($language_id);

			if (!$language instanceof lang) {
				$this->reportError($this->getLabel('label-cannot-detect-language') . " \"{$title}\" ");
				return false;
			}

			if ($title) $language->setTitle($title);
			if (!is_null($is_default) && $is_default) {
				$collection->setDefault($language_id);
			}
			if (!is_null($prefix)) $language->setPrefix($prefix);

			$language->commit();

			if ($created) {
				$this->created_languages++;
				$this->writeLog($this->getLabel('label-language') . " \"" . $language->getTitle() . "\" (" . $old_id . ") " . $this->getLabel('label-has-been-updated-m'));
			} elseif($language->getIsUpdated()) {
				$this->updated_languages++;
				$this->writeLog($this->getLabel('label-language') . " \"" . $language->getTitle() . "\" (" . $old_id . ") " . $this->getLabel('label-has-been-created-m'));
			}

			return $language;
		}

		protected function importDomain(DOMElement $info) {
			$old_id = $info->getAttribute('id');

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-domain') . " \"{$host}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$host = $info->hasAttribute('host') ? $info->getAttribute('host') : null;
			$old_lang_id = $info->hasAttribute('lang-id') ? $info->getAttribute('lang-id') : null;
			$is_default = $info->hasAttribute('is-default') ? $info->getAttribute('is-default') : null;

			$collection = domainsCollection::getInstance();

			$created = false;
			$domain_id = $this->relations->getNewDomainIdRelation($this->source_id, $old_id);
			if (!$domain_id) {
				if($host) {
					$domain_id = $collection->getDomainId($host);
					if ($domain_id) $this->relations->setDomainIdRelation($this->source_id, $old_id, $domain_id);
				}
			}

			if ($domain_id && $this->update_ignore) {
				$this->writeLog($this->getLabel('label-domain') . " \"" . $host . "\" (#{$old_id}) " . $this->getLabel('label-already-exists'));
				return $collection->getDomain($domain_id);
			}

			if (!$domain_id) {
				if (!$host) $host = $old_id;
				$lang_id = false;
				if (!is_null($old_lang_id)) $lang_id = $this->relations->getNewLangIdRelation($this->source_id, $old_lang_id);
				if (!$lang_id) $lang_id = langsCollection::getInstance()->getDefaultLang()->getId();

				$domain_id = $collection->addDomain($host, $lang_id);
				$this->relations->setDomainIdRelation($this->source_id, $old_id, $domain_id);
				$created = true;
			}

			$domain = $collection->getDomain($domain_id);
			if (!$domain instanceof domain) {
				$this->reportError($this->getLabel('label-cannot-detect-domain') . " \"{$host}\" ");
				return false;
			}

			if (!is_null($is_default) && $is_default) {
				if(!$collection->getDefaultDomain()) $collection->setDefaultDomain($domain_id);
			}

			if ($created) {
				$this->created_domains++;
				$this->writeLog($this->getLabel('label-domain') . " \"" . $host . "\" (#" . $old_id . ") " . $this->getLabel('label-has-been-created-m'));
			} elseif($domain->getIsUpdated()) {
				$this->updated_domains++;
				$this->writeLog($this->getLabel('label-domain') . " \"" . $host . "\" (#" . $old_id . ") has been " . $this->getLabel('label-has-been-updated-m'));
			}

			$nl = $info->getElementsByTagName('domain-mirror');
			foreach ($nl as $info) {
				$this->importDomainMirror($info, $domain);
			}

			$domain->commit();

			return $domain;
		}

		protected function importDomainMirror(DOMElement $info, domain $domain) {
			$old_id = $info->getAttribute('id');
			$host = $info->hasAttribute('host') ? $info->getAttribute('host') : null;

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-domain-mirror') . " \"{$host}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$created = false;
			$mirror_id = $this->relations->getNewDomainMirrorIdRelation($this->source_id, $old_id);

			if (!$mirror_id) {
				if (!is_null($host)) {
					$mirror_id = $domain->getMirrowId($host);
					if ($mirror_id) $this->relations->setDomainMirrorIdRelation($this->source_id, $old_id, $mirror_id);
				}
			}

			if ($mirror_id && $this->update_ignore) {
				$this->writeLog($this->getLabel('label-domain') . " \"" . $host . "\" (#{$old_id}) " . $this->getLabel('label-already-exists'));
				return $domain->getMirrow($mirror_id);
			}

			if (!$mirror_id) {
				if (is_null($host)) $host = $old_id;
				$mirror_id = $domain->addMirrow($host);
				$this->relations->setDomainMirrorIdRelation($this->source_id, $old_id, $mirror_id);
				$created = true;
			}

			$domainMirror = $domain->getMirrow($mirror_id);
			if (!$domainMirror instanceof domainMirrow) {
				$this->reportError($this->getLabel('label-cannot-detect-domain-mirror') . " \"{$host}\"");
				return false;
			}

			if ($created) {
				$this->created_domain_mirrors++;
				$this->writeLog($this->getLabel('label-domain-mirror') . " \"" . $host . "\" (#" . $old_id . ") " . $this->getLabel('label-has-been-created-n'));
			} elseif($domainMirror->getIsUpdated()) {
				$this->updated_domain_mirrors++;
				$this->writeLog($this->getLabel('label-domain-mirror') . " \"" . $host . "\" (#" . $old_id . ") " . $this->getLabel('label-has-been-updated-n'));
			}


			return $domainMirror;
		}

		protected function importTemplate(DOMElement $info) {

			$old_id = $info->getAttribute('id');
			$title = $info->hasAttribute('title') ? $info->getAttribute('title') : null;

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-template') . " \"{$title}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$collection = templatesCollection::getInstance();

			$filename = $info->hasAttribute('filename') ? $info->getAttribute('filename') : null;
			$old_domain_id = $info->hasAttribute('domain-id') ? $info->getAttribute('domain-id') : null;
			$old_lang_id = $info->hasAttribute('lang-id') ? $info->getAttribute('lang-id') : null;
			$is_default = $info->hasAttribute('is-default') ? $info->getAttribute('is-default') : null;

			$lang_id = false;
			$domain_id = false;
			if (!is_null($old_lang_id)) $lang_id = $this->relations->getNewLangIdRelation($this->source_id, $old_lang_id);
			if (!is_null($old_domain_id)) $domain_id = $this->relations->getNewDomainIdRelation($this->source_id, $old_domain_id);
			if(!$lang_id) $lang_id = langsCollection::getInstance()->getDefaultLang()->getId();
			if(!$domain_id) $domain_id = domainsCollection::getInstance()->getDefaultTemplate()->getId();


			$created = false;
			$template_id = $this->relations->getNewTemplateIdRelation($this->source_id, $old_id);

			if ($template_id && $this->update_ignore) {
				$this->writeLog($this->getLabel('label-template') . " \"" . $title . "\" (#{$old_id}) " . $this->getLabel('label-already-exists'));
				return $collection->getTemplate($template_id);
			}

			if (!$template_id) {
				if (!$title) $title = $old_id;

				$template_id = $this->detectTemplateId($filename, $domain_id, $lang_id);
				if(!$template_id) $template_id = $collection->addTemplate($filename, $title);
				$this->relations->setTemplateIdRelation($this->source_id, $old_id, $template_id);
				$created = true;
			}

			$template = $collection->getTemplate($template_id);
			if (!$template instanceof template) {
				$this->reportError($this->getLabel('label-cannot-detect-template') . "\"{$title}\"");
				return false;
			}


			if (!is_null($is_default)) $template->setIsDefault($is_default);

			$template->setLangId($lang_id);
			$template->setDomainId($domain_id);

			if ($created) {
				$this->created_templates++;
				$this->writeLog($this->getLabel('label-template') . " \"" . $title . "\" (" . $old_id . ") " .  $this->getLabel('label-has-been-created-m'));
			} elseif($template->getIsUpdated()) {
				$this->updated_templates++;
				$this->writeLog($this->getLabel('label-template') . " \"" . $title . "\" (" . $old_id . ") " .  $this->getLabel('label-has-been-updated-m'));
			}

			$template->commit();

			return $template;
		}

		protected function importRestriction(DOMElement $info) {

			$old_id = $info->getAttribute('id');
			$title = $info->hasAttribute('title') ? $info->getAttribute('title') : null;
			$prefix = $info->hasAttribute('prefix') ? $info->getAttribute('prefix') : null;
			$data_type = $info->hasAttribute('field-type') ? $info->getAttribute('field-type') : null;
			$multiple = $info->getAttribute('is-multiple') == 1 || $info->getAttribute('is-multiple') == "multiple";

			if (!strlen($old_id)) {
				$this->reportError($this->getLabel('label-cannot-create-restriction') . " \"{$title}\" " . $this->getLabel('label-with-empty-id'));
				return false;
			}

			$collection = umiFieldTypesCollection::getInstance();
			$field_type = $collection->getFieldTypeByDataType($data_type, $multiple);

			$type_id = $field_type->getId();
			$created = false;
			$restriction_id = false;
			if (!$title) $title = $old_id;

			if(baseRestriction::find($prefix, $type_id)) {
				$restriction_id = baseRestriction::find($prefix, $type_id)->getId();
				if ($restriction_id != $this->relations->getNewRestrictionIdRelation($this->source_id, $old_id)) {
					$this->relations->setRestrictionIdRelation($this->source_id, $old_id, $restriction_id);
				}
			}

			if (!$restriction_id) $restriction_id = $this->relations->getNewRestrictionIdRelation($this->source_id, $old_id);

			if(!$restriction_id) {
				$restriction_id = baseRestriction::add($prefix, $title, $type_id);
				$this->relations->setRestrictionIdRelation($this->source_id, $old_id, $restriction_id);
				$created = true;
			}

			$restriction = baseRestriction::get($restriction_id);
			if (!$restriction instanceof baseRestriction) {
				$this->reportError($this->getLabel('label-cannot-detect-restriction') . " \"{$title}\"");
				return false;
			}

			if ($created) {
				$this->created_restrictions++;
				$this->writeLog($this->getLabel('label-restriction') . " \"" . $restriction->getTitle() . "\" (" . $old_id . ") " . $this->getLabel('label-has-been-created-n'));
			}

			$fields = $info->getElementsByTagName('field');
			foreach ($fields as $fld) {
				$old_field_name = $fld->getAttribute('field-name');
				$old_object_type_id = $fld->getAttribute('type-id');
				$object_type_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_object_type_id);

				$field_id = umiObjectTypesCollection::getInstance()->getType($object_type_id)->getFieldId(self::translateName($old_field_name), false);
			if (!$field_id) umiObjectTypesCollection::getInstance()->getType($object_type_id)->getFieldId($old_field_name, false);

				$field = umiFieldsCollection::getInstance()->getField($field_id);
					if (!$field instanceof umiField) {
						$this->reportError($this->getLabel('label-cannot-set-restriction-for-field') . " \"{$old_field_name}\": " . $this->getLabel('label-cannot-detect-field'));
						continue;
					}

				$field->setRestrictionId($restriction_id);
				$field->setIsUpdated();
				$this->writeLog($this->getLabel('label-restriction') . " \"" . $restriction->getTitle() . "\" " . $this->getLabel('label-has-been-set-for-field') . " \"{$old_field_name}\"");
			}

			return $restriction;
		}

		protected function importReg(DOMElement $info) {

			$path = $info->hasAttribute('path') ? $info->getAttribute('path') : null;
			$val = $info->hasAttribute('val') ? $info->getAttribute('val') : null;
			$need_update = $info->hasAttribute('update') ? true : false;

			if (!strlen($path)) {
				$this->reportError($this->getLabel('label-cannot-create-registry-item-with-empty-path'));
				return false;
			}

			$created = false;
			$updated = false;
			$regedit = regedit::getInstance();

			if(!$regedit->getKey($path)){
				$regedit->setVal($path, $val);
				$created = true;
				$new_key = $regedit->getKey($path);
			}
			elseif ($need_update) {
				$regedit->setVal($path, $val);
				$updated = true;
				$new_key = $regedit->getKey($path);
			}

			if ($created) {
				$this->created_registry_items++;
				$this->writeLog($this->getLabel('label-registry-item') . " \"" . $path . "\" (" . $new_key . ") " . $this->getLabel('label-has-been-created-f'));
			} elseif ($updated) {
				$this->writeLog("Registry item \"" . $path . "\" (" . $new_key . ") has been updated");
			}
		}

		protected function importTypeRelation(umiField $field, DOMElement $info, umiObjectType $type) {
			$old_guide_ids = $info->getElementsByTagName('guide');
			$old_guide_id = $old_guide_ids->length ? $old_guide_ids->item(0)->getAttribute('id') : false;

			$guide_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_guide_id);
			if(!$guide_id) return false;

			if($field->getGuideId() != $guide_id) {
				$field->setGuideId($guide_id);
				$this->updated_relations++;
				$this->writeLog($this->getLabel('label-relation') . ': ' . $this->getLabel('label-datatype') . " (" . $type->getName() . ") - " . $this->getLabel('label-field') . " (" . $field->getName() . ") - " . $this->getLabel('label-guide') . " ({$guide_id}) " . $this->getLabel('label-has-been-updated-f'));
				$field->commit();
			}

			return true;
		}

		protected function importEntityRelation(umiField $field, DOMElement $info,  $entity) {

			$field_name = $field->getName();

			$n = $info->getElementsByTagName('object');
			$obj_ids = array();
			foreach ($n as $obj) {
				$old_obj_id = $obj->getAttribute('id');
				$obj_id = (int) $this->relations->getNewObjectIdRelation($this->source_id, $old_obj_id);
				if ($obj_id) $obj_ids[] = $obj_id;
			}

			$p = $info->getElementsByTagName('page');
			$pg_ids = array();
			foreach ($p as $pg) {
				$old_pg_id = $pg->getAttribute('id');
				$pg_id = (int) $this->relations->getNewIdRelation($this->source_id, $old_pg_id);
				if ($pg_id) $pg_ids[] = $pg_id;
			}

			$updated = false;
			$entity_type = ($entity instanceof umiObject) ? "object" : "page";
			$entity_id = $entity->getId();
			if (count($obj_ids)) {
				$value = $entity->getValue($field_name);
				if(!is_array($value)) $value = array($value);
				if(count(array_diff($obj_ids, $value))) {
					$entity->setValue($field_name, $obj_ids);
					$this->updated_relations++;
					$updated = true;
				}
			} elseif (count($pg_ids)) {
				$value = $entity->getValue($field_name);
				if(!is_array($value)) $value = array($value);
				if(count(array_diff($pg_ids, $value))) {
					$entity->setValue($field_name, $pg_ids);
					$this->updated_relations++;
					$updated = true;

				}
			} else {
				 $entity->setValue($field_name, array());
			}

			if ($updated) {
				if ($entity instanceof umiObject) {
					$this->writeLog($this->getLabel('label-values-for-field') . " ({$field_name}) " . $this->getLabel('label-of-object') . " ({$entity_id}) " . $this->getLabel('label-have-been-updated'));
				} else {
					$this->writeLog($this->getLabel('label-values-for-field') . " ({$field_name}) " . $this->getLabel('label-of-object') . " ({$entity_id}) " . $this->getLabel('label-have-been-updated'));
				}
			}

			$entity->commit();

			if ($entity instanceof umiObject) {
				umiObjectsCollection::getInstance()->unloadObject($entity_id);
			} else {
				umiHierarchy::getInstance()->unloadElement($entity_id);
			}
			return true;
		}

		protected function importRelation(DOMElement $info) {
			$old_type_id = $info->hasAttribute('type-id') ? $info->getAttribute('type-id') : null;
			$old_page_id = $info->hasAttribute('page-id') ? $info->getAttribute('page-id') : null;
			$old_object_id = $info->hasAttribute('object-id') ? $info->getAttribute('object-id') : null;

			$old_field_name = $info->hasAttribute('field-name') ? $info->getAttribute('field-name') : null;

			if (!strlen($old_type_id) && !strlen($old_page_id) && !strlen($old_object_id)) {
				$this->reportError($this->getLabel('label-cannot-create-relation-for-field') . " \"{$old_field_name}\":" . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}

			$type_id = null;
			$entity = null;
			if (!is_null($old_type_id)) {
				$type_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_type_id);
				$entity = umiObjectTypesCollection::getInstance()->getType($type_id);
			} elseif (!is_null($old_page_id)) {
				$page_id = $this->relations->getNewIdRelation($this->source_id, $old_page_id);
				$entity = umiHierarchy::getInstance()->getElement($page_id, true, true);
			} elseif(!is_null($old_object_id)) {
				$object_id = $this->relations->getNewObjectIdRelation($this->source_id, $old_object_id);
				$entity = umiObjectsCollection::getInstance()->getObject($object_id);
			}

			if (!$entity) {
				$this->reportError($this->getLabel('label-cannot-create-relation-for-field') . " \"{$old_field_name}\": " . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}
			if ($entity instanceof umiHierarchyElement) $type_id = $entity->getObjectTypeId();
			if ($entity instanceof umiObject) $type_id = $entity->getTypeId();

			$field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId(self::translateName($old_field_name), false);
			if (!$field_id) umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId($old_field_name, false);

			if (!$field_id) {
				$this->reportError($this->getLabel('label-cannot-create-relation-for-field') . " \"{$old_field_name}\":" . $this->getLabel('label-cannot-detect-field'));
				return false;
			}

			$field = umiFieldsCollection::getInstance()->getField($field_id);

			if ($entity instanceof umiObjectType) {
				return $this->importTypeRelation($field, $info, $entity);
			} else {
				return $this->importEntityRelation($field, $info, $entity);
			}
		}

		protected function importPermission(DOMElement $info) {

			$old_page_id = $info->hasAttribute('page-id') ? $info->getAttribute('page-id') : null;
			$old_object_id = $info->hasAttribute('object-id') ? $info->getAttribute('object-id') : null;

			if (!strlen($old_page_id) && !strlen($old_object_id)) {
				$this->reportError($this->getLabel('label-cannot-create-permission') . ": " . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}

			$permissions = permissionsCollection::getInstance();

			$entity = null;
			if(!is_null($old_page_id)) {
				$page_id = $this->relations->getNewIdRelation($this->source_id, $old_page_id);
				$entity = umiHierarchy::getInstance()->getElement($page_id, true, true);

			} elseif(!is_null($old_object_id)) {
				$object_id = $this->relations->getNewObjectIdRelation($this->source_id, $old_object_id);
				$entity = umiObjectsCollection::getInstance()->getObject($object_id);
			}

			if (!$entity) {
				$this->reportError($this->getLabel('label-cannot-create-permission') . ": " . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}

			$entity_id = $entity->getId();

			$o = $info->getElementsByTagName('owner');
			foreach ($o as $owner) {
				$old_owner_id = $owner->getAttribute('id');
				$owner_id = (int) $this->relations->getNewObjectIdRelation($this->source_id, $old_owner_id);
				$level = $owner->hasAttribute('level') ? $owner->getAttribute('level') : null;
				if(!is_null($level)) {
					$permissions->setElementPermissions($owner_id, $entity_id, $level);
					$created = true;
					$this->created_permissions++;
				} else {
					$entity->setOwnerId($owner_id);
					$entity->setIsUpdated();
					$entity->commit();
					$this->writeLog($this->getLabel('label-owner-for-entity') . " (" . $entity_id . ") ". $this->getLabel('label-has-been-updated-m'));
					$this->created_permissions++;
				}
			}

			$m = $info->getElementsByTagName('module');
			foreach ($m as $module) {
				$module_name = $module->getAttribute('name');
				$method = $module->getAttribute('method');
				$allow = $module->getAttribute('allow');

				if ($method == '' || is_null($method)) {
					if (!$permissions->isAllowedModule($entity_id, $module_name)) {
						$permissions->setModulesPermissions($entity_id, $module_name);
						$this->writeLog($this->getLabel('label-permissions-for') . " " . $this->getLabel('label-module') . " \"{$module_name}\" " . $this->getLabel('label-of-object') . " (" . $entity_id . ") " . $this->getLabel('label-have-been-updated'));
						$this->created_permissions++;
					}
				} else {

					if(!$permissions->isAllowedMethod($entity_id, $module_name, $method)) {
						$permissions->setModulesPermissions($entity_id, $module_name, $method);
						$this->writeLog($this->getLabel('label-permissions-for') . " " . $this->getLabel('label-module') . " \"{$module_name}\" - " . $this->getLabel('label-method') . " \"{$method}\" " . $this->getLabel('label-of-object') . " (" . $entity_id . ") " . $this->getLabel('label-have-been-updated'));
						$this->created_permissions++;
					}
				}
			}
		}

		protected function importOption(DOMElement $info) {

			$old_page_id = $info->hasAttribute('page-id') ? $info->getAttribute('page-id') : null;
			$old_object_id = $info->hasAttribute('object-id') ? $info->getAttribute('object-id') : null;
			$old_field_name = $info->hasAttribute('field-name') ? $info->getAttribute('field-name') : null;

			if (!strlen($old_page_id) && !strlen($old_object_id)) {
				$this->reportError($this->getLabel('label-cannot-create-options-for-field') . " {$old_field_name} " . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}

			$entity = null;
			if(!is_null($old_page_id)) {
				$page_id = $this->relations->getNewIdRelation($this->source_id, $old_page_id);
				$entity = umiHierarchy::getInstance()->getElement($page_id, true, true);

			} elseif(!is_null($old_object_id)) {
				$object_id = $this->relations->getNewObjectIdRelation($this->source_id, $old_object_id);
				$entity = umiObjectsCollection::getInstance()->getObject($object_id);
			}

			if (!$entity) {
				$this->reportError($this->getLabel('label-cannot-create-options-for-field') . " {$old_field_name} " . $this->getLabel('label-cannot-detect-entity'));
				return false;
			}
			if ($entity instanceof umiHierarchyElement) $type_id = $entity->getObjectTypeId();
			if ($entity instanceof umiObject) $type_id = $entity->getTypeId();


			$field_id = $this->relations->getNewFieldId($this->source_id, $type_id, $old_field_name);

			if (!$field_id) {
				$this->reportError($this->getLabel('label-cannot-create-options-for-field') . " {$old_field_name} " . $this->getLabel('label-cannot-detect-field'));
				return false;
			}

			$field = umiFieldsCollection::getInstance()->getField($field_id);

			$field_name = $field->getName();

			$o =  $this->parser->evaluate("option", $info);
			$values = array();
			foreach ($o as $option) {
				if($option->hasAttributes()) {
					$attributes = $option->attributes;
					if(!is_null($attributes)) {
						foreach ($attributes as $index => $attribute) {
							if($attribute->name == 'object-id') {
								$object_id = $this->relations->getNewObjectIdRelation($this->source_id, $attribute->value);
								if (!$object_id) {
									$this->reportError('ошибка');
									continue;
								}
								$name = umiObjectsCollection::getInstance()->getObject($object_id)->getName();
								$value['rel'] = $name;
							}
							elseif($attribute->name == 'page-id') {
								$page_id = $this->relations->getNewIdRelation($this->source_id, $attribute->value);
								if (!$page_id) {
									$this->reportError('ошибка');
									continue;
								}
								$name = umiHierarchy::getInstance()->getElement($page_id, true, true)->getName();
								$value['rel'] = $name;
							}
							else {
								$value[$attribute->name] = $attribute->value;
							}
						}
						$values[] = $value;
					}
				}
			}
			$entity->setValue($field_name, $values);
			$entity->commit();
		}

		protected function importFile(DOMElement $info) {

			$filename = $info->hasAttribute('name') ? $info->getAttribute('name') : null;
			$old_hash = $info->hasAttribute('hash') ? $info->getAttribute('hash') : null;

			$destinationPath = $info->nodeValue;
			if(!($destinationPath)) {
				$this->reportError($this->getLabel('label-cannot-create-file-with-empty-path'));
				return false;
			}

			$destinationPath = CURRENT_WORKING_DIR . $destinationPath;

			$destinationPathFolder = dirname($destinationPath);
			if(!file_exists($destinationPathFolder)) mkdir($destinationPathFolder, 0777, true);
			$sourcePath = $this->filesSource . $info->nodeValue;

			if (!file_exists($sourcePath)) {
				$this->reportError($this->getLabel('label-file') . " {$filename} " . $this->getLabel('label-does-not-exist'));
				return false;
			}

			if (copy($sourcePath, $destinationPath)) {
				$new_hash = md5_file($destinationPath);
				if ($old_hash != $new_hash) {
					$this->reportError($this->getLabel('label-file') . " {$filename} " . $this->getLabel('label-is-broken'));
				} else {
					if (defined("PHP_FILES_ACCESS_MODE") && strtolower(substr($destinationPath, -4, 4))==='.php') {
						chmod($destinationPath, PHP_FILES_ACCESS_MODE);
					}
					$this->copied_files++;
					$this->writeLog($this->getLabel('label-file') . " \"" . $filename . "\" (" . $destinationPath . ") " . $this->getLabel('label-has-been-copied-m'));
				}
			}
			else $this->reportError($this->getLabel('label-cannot-copy-file') ." \"{$filename}\"");
		}

		protected function importDir(DOMElement $info) {

			$name = $info->hasAttribute('name') ? $info->getAttribute('name') : null;
			$path = $info->hasAttribute('path') ? $info->getAttribute('path') : null;

			if(is_null($path)) {
				$this->reportError($this->getLabel('label-cannot-create-folder-with-empty-path'));
				return false;
			} else {
				$path = CURRENT_WORKING_DIR . $info->nodeValue;
			}

			if(!file_exists($path)) {
				mkdir($path, 0777, true);
				$this->created_dirs++;
				$this->writeLog($this->getLabel('label-folder') . " \"" . $name . "\" (" . $path . ") " . $this->getLabel('label-has-been-created-f'));
			}

		}


		protected function importObject(DOMElement $info) {
			$old_id = $info->getAttribute('id');

			if (!strlen($old_id)) {
				$this->reportError("Can't create object {$name} with empty id");
				return false;
			}

			$guid = $info->hasAttribute('guid') ? $info->getAttribute('guid') : null;
			$name = $info->hasAttribute('name') ? $info->getAttribute('name') : null;
			$old_type_id = $info->getAttribute('type-id');
			$update_only = $info->getAttribute('update-only') == '1';
			$is_locked = $info->getAttribute('locked');

			$collection = umiObjectsCollection::getInstance();
			$types_collection = umiObjectTypesCollection::getInstance();

			$created = false;
			$object_id = false;

			if (!is_null($guid)) {
				$object_id = $collection->getObjectIdByGUID($guid);
				if ($object_id && $object_id != $this->relations->getNewObjectIdRelation($this->source_id, $old_id)) {
					$this->relations->setObjectIdRelation($this->source_id, $old_id, $object_id);
				}
			}

			if (!$object_id) $object_id = $this->relations->getNewObjectIdRelation($this->source_id, $old_id);

			if ($object_id && $this->update_ignore) {
				$this->writeLog($this->getLabel('label-object') .  " \"" . $name . "\" (#{$old_id}) " . $this->getLabel('label-already-exists'));
				return $collection->getObject($object_id);
			}

			if (!$object_id) {
				if ($update_only) {
					return false;
				}
				if (!$name) $name = $old_id;
				if (!strlen($old_type_id)) {
					$this->reportError($this->getLabel('label-cannot-create-object') . " \"{$name}\" (#{$old_id}): " . $this->getLabel('label-cannot-detect-type'));
					return false;
				}
				$type_id = $this->relations->getNewTypeIdRelation($this->source_id, $old_type_id);
				$type = $types_collection->getType($type_id);
				if (!$type instanceof umiObjectType) {
					$this->reportError($this->getLabel('label-cannot-create-object') . " \"{$name}\" (#{$old_id}): " . $this->getLabel('label-cannot-detect-type'). " #{$old_type_id}");
					return false;
				}

				if ($this->demosite_mode) {
					$hierarchyTypeId = $type->getHierarchyTypeId();
					if ($hierarchyTypeId) {
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if ($hierarchyType instanceof umiHierarchyType) {
							$module = $hierarchyType->getModule();
							if (!regedit::getInstance()->getVal("//modules/{$module}")) return false;
						}
					}
				}

				// call event on before add object
				$oEventPoint = new umiEventPoint("exchangeOnAddObject");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("before");
				$oEventPoint->setParam("old_object_id", $old_id);
				$oEventPoint->setParam("object_info", $info);
				$oEventPoint->setParam("type", $type);
				umiEventsController::getInstance()->callEvent($oEventPoint);

				$object_id = $collection->addObject($name, $type_id, ($is_locked == 'locked' || $is_locked == '1'));
				$this->relations->setObjectIdRelation($this->source_id, $old_id, $object_id);
				$created = true;
			}
			$object = $collection->getObject($object_id);
			if (!$object instanceof umiObject) return false; //TODO: write log

			if (!is_null($guid)) $object->setGUID($guid);

			if (!$created) {
				// call on before update object
				$oEventPoint = new umiEventPoint("exchangeOnUpdateObject");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("before");
				$oEventPoint->addRef("object", $object);
				$oEventPoint->setParam("object_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			}

			if (!is_null($name)) $object->setName($name);

			$this->importPropValues($object, $info, $created);

			if ($created) {
				$this->created_objects++;
				$this->writeLog($this->getLabel('label-object') . " \"" . $object->getName() . "\" (" . $old_id . ") ". $this->getLabel('label-has-been-created-m'));
			} elseif($object->getIsUpdated()) {
				$this->updated_objects++;
				$this->writeLog($this->getLabel('label-object') . " \"" . $object->getName() . "\" (" . $old_id . ") ". $this->getLabel('label-has-been-updated-m'));
			}

			if ($created) {
				// call event on after add object
				$oEventPoint = new umiEventPoint("exchangeOnAddObject");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("after");
				$oEventPoint->addRef("object", $object);
				$oEventPoint->setParam("object_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			} else {
				// call event on after update object
				$oEventPoint = new umiEventPoint("exchangeOnUpdateObject");
				$oEventPoint->setParam("source_id", $this->source_id);
				$oEventPoint->setMode("after");
				$oEventPoint->addRef("object", $object);
				$oEventPoint->setParam("object_info", $info);
				umiEventsController::getInstance()->callEvent($oEventPoint);
			}

			$object->commit();

			$collection->unloadObject($object_id);
			return $object;
		}

		protected function importPropValues(umiEntinty $entity, DOMElement $info, $is_new_entity = false) {
			$nl = $this->parser->evaluate("properties/group/property", $info);
			foreach ($nl as $prop_info) {
				$this->importPropValue($entity, $prop_info, $is_new_entity);
			}
		}

		protected static function translateName($name) {
			$name = umiHierarchy::convertAltName($name, "_");
			$name = umiObjectProperty::filterInputString($name);
			if(!strlen($name)) $name = '_';
			$name = substr($name, 0, 64);
			return $name;
		}

		protected function importPropValue(umiEntinty $entity, DOMElement $info, $is_new_entity = false) {
			$old_name = $info->getAttribute('name');
			$name = self::translateName($old_name);

			$nl =  $this->parser->evaluate("value", $info);
			if (!$nl->length && $is_new_entity) {
				$nl =  $this->parser->evaluate("default-value", $info);
			}
			if (!$nl->length) {
				if ($is_new_entity) $this->reportError($this->getLabel('label-property') . " \"{$name}\" " . $this->getLabel('label-has-no-values'));
				return false;
			}

			$value_node = $nl->item(0);

			$type_id = ($entity instanceof umiHierarchyElement) ? $entity->getObjectTypeId() : $entity->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);
			$field_id = $type->getFieldId($name, false);
			$field = umiFieldsCollection::getInstance()->getField($field_id);

			if (!$field instanceof umiField && $info->getAttribute('allow-runtime-add') == '1')  {
				//  try add runtime group
				$group_info = $info->parentNode;
				$group = $this->importTypeGroup($type, $group_info, false);
				if (!$group instanceof umiFieldsGroup) return false;
				//  try add runtime field
				$field = $this->importField($group, $info);
				($entity instanceof umiHierarchyElement) ? $entity->getObject()->update() : $entity->update();
			}

			if (!$field instanceof umiField) return false;

			switch($field->getDataType()) {
				// ignored types
				case "optioned":
				case "symlink":  {
					return false; //import relations();
				}
				case "date": {
					$timestamp = intval($value_node->getAttribute('unix-timestamp'));
					$uDate = new umiDate();
					if ($timestamp) {
						$uDate->setDateByTimeStamp($timestamp);
					} else {
						$uDate->setDateByString($value_node->nodeValue);
					}
					$entity->setValue($name, $uDate);

					break;
				}
				case "price": {
					$emarket = cmsController::getInstance()->getModule('emarket');
					$price = $value_node->nodeValue;
					$price = str_replace(',', '.', $price);
					$price = floatval(preg_replace("/[^0-9.,]/", "", $price));
					$currency_code = $value_node->hasAttribute('currency-code') ? $value_node->getAttribute('currency-code') : $value_node->getAttribute('currency_code');
					if (strlen($currency_code) && $emarket) {
						$currency = $emarket->getCurrency($currency_code);
						if ($currency) {
							$result = $emarket->formatCurrencyPrice(array($price), $emarket->getDefaultCurrency(), $currency);
							$price = $result[0];
						}
					}
					$entity->setValue($name, $price);

					break;
				}

				/* files */
				case "file":
				case "img_file":
				case "video_file":
				case "swf_file": {

					if ($this->renameFiles) {

						$oldFileName = false;
						$oldFile = $entity->getValue($name);
						if ($oldFile instanceof umiFile) $oldFileName = $oldFile->getFilePath();

						$origFilePath = ltrim(trim($value_node->nodeValue, "\r\n"), ".");

						$filename = basename($origFilePath);
						$dir 	  = dirname ($origFilePath);

						$ext = explode(".", $filename);
						$ext = end($ext);

						$filename_translit = translit::convert(trim($entity->getName(), "\r\n"));
						$filename = $filename_translit;

						$count = 0;
						$old = error_reporting(0);
						while(true) {
							if(!file_exists(CURRENT_WORKING_DIR.'/'.$origFilePath)) {
								break(2);
							} else {
								if($oldFileName) {

									$oldFilePath = CURRENT_WORKING_DIR . ltrim($oldFileName, ".");
									if (file_exists($oldFilePath)) unlink($oldFilePath);
							}
							}

							if(!file_exists(CURRENT_WORKING_DIR.'/'.$dir  . '/' .  $filename.'.'.$ext)) {
								break;
							}

							$count++;
							$filename = $filename_translit . '_'.$count;

						}
						$filename .= '.'.$ext;

						rename(CURRENT_WORKING_DIR.'/'.$origFilePath, CURRENT_WORKING_DIR.'/'.$dir  . '/' .  $filename);
						error_reporting($old);

						$origFilePath = '.' . $dir  . '/' .  $filename;
						$entity->setValue($name, $origFilePath);

					} else {
						$filePath = ltrim(trim($value_node->nodeValue, "\r\n"), ".");
						$entity->setValue($name, "." . $filePath);
					}


					break;
				}

				case "relation": {

					if($this->auto_guide_creation) {

						/* for emarket */
						if ($name == 'payment_status_id' && $type->getMethod() == 'order') {
							$emarket = cmsController::getInstance()->getModule('emarket');
							if ($emarket) {
								$codename = $value_node->nodeValue;
								$order = order::get($entity->id);
								$order->setPaymentStatus($codename);
								$order->commit();
							}
						} elseif ($name == 'status_id' && $type->getMethod() == 'order') {
							$emarket = cmsController::getInstance()->getModule('emarket');
							if ($emarket) {
								$codename = $value_node->nodeValue;
								$order = order::get($entity->id);
								$old_status_id = $order->getOrderStatus();
								$old_status_code = $order->getCodeByStatus($old_status_id);
								if (!in_array($old_status_code, array('ready', 'canceled', 'rejected'))) {
									$order->setOrderStatus($codename);
									$order->commit();
								}
							}

						} else {
							$items = array();
							$nl = $value_node->getElementsByTagName("item");
							foreach ($nl as $item_info) {
								$items[] = $item_info->getAttribute('name');
							}
							$entity->setValue($name, $items);
						}
					}
					break;
				}
				case "tags": {
					$nl =  $this->parser->evaluate("combined", $info);
					if ($value_node = $nl->item(0))	$entity->setValue($name, trim($value_node->nodeValue, "\r\n"));
					break;
				}

				// simple props save
				case "string":
				case "text":
				case "wysiwyg":
				case "boolean":
				case "counter":
				case "float":
				case "int": {
					if ($name == 'payment_type_id' || $name == 'modificator_type_id' || $name == 'rule_type_id' || $name == 'delivery_type_id') {
						$newValue = $this->relations->getNewTypeIdRelation($this->source_id, $value_node->nodeValue);
						if ($newValue) $entity->setValue($name, $newValue);
						break;
					}
				}
				default: {
					$entity->setValue($name, trim($value_node->nodeValue, "\r\n"));
					break;
				}
			}

		}

		protected function importLangs() {
			$nl = $this->parser->evaluate("/umidump/langs/lang");
			foreach ($nl as $info) {
				$this->importLang($info);
			}
		}


		protected function importObjects() {
			$nl = $this->parser->evaluate("/umidump/objects/object");
			foreach ($nl as $info) {
				$this->importObject($info);
			}
		}

		protected function importDomains() {
			$nl = $this->parser->evaluate("/umidump/domains/domain");
			foreach ($nl as $info) {
				$this->importDomain($info);
			}
		}

		protected function importTemplates() {
			$nl = $this->parser->evaluate("/umidump/templates/template");
			foreach ($nl as $info) {
				$this->importTemplate($info);
			}
		}

		protected function importFiles() {

			$nl = $this->parser->evaluate("/umidump/files/file");

			foreach ($nl as $info) {
				$this->importFile($info);
			}
		}

		protected function importDirs() {

			$nl = $this->parser->evaluate("/umidump/directories/directory");

			foreach ($nl as $info) {
				$this->importDir($info);
			}
		}

		protected function importRelations () {
			$nl = $this->parser->evaluate("/umidump/relations/relation");
			foreach ($nl as $info) {
				$this->importRelation($info);
			}
		}

		protected function importRestrictions () {
			$nl = $this->parser->evaluate("/umidump/restrictions/restriction");
			foreach ($nl as $info) {
				$this->importRestriction($info);
			}
		}

		protected function importRegistry () {
			$nl = $this->parser->evaluate("/umidump/registry/key");
			foreach ($nl as $info) {
				$this->importReg($info);
			}
		}

		protected function setDefaultPermissions () {
			if(!count($this->imported_elements)) return;
			$collection = permissionsCollection::getInstance();
			foreach ($this->imported_elements as $element_id) {
				$collection->setDefaultPermissions($element_id);
			}
		}

		protected function importPermissions () {
			$nl = $this->parser->evaluate("/umidump/permissions/permission");
			foreach ($nl as $info) {
				$this->importPermission($info);
			}
		}

		protected function importOptions () {
			$nl = $this->parser->evaluate("/umidump/options/entity");
			foreach ($nl as $info) {
				$this->importOption($info);
			}
		}

		protected function importDataTypes () {
			$nl = $this->parser->evaluate("/umidump/datatypes/datatype");
			foreach ($nl as $info) {
				$this->importFieldType($info);
			}
		}

		protected function importHierarchy() {
			$nl = $this->parser->evaluate("/umidump/hierarchy/relation");
			foreach ($nl as $info) {
				$this->importHierarchyRelation($info);
			}
			if($nl->length) {
				umiHierarchy::getInstance()->rebuildRelationNodes($this->destination_element_id);
			}
		}

	};
?>