<?php
	class xmlExporter implements iXmlExporter {

		const VERSION = "2.0";

		protected static $ROOT_PAGE_TYPE_ID; // id корневого типа "Раздел сайта"

		protected $source_id, $source_name;
		protected $files, $types, $langs, $domains, $templates, $elements, $branches, $objects, $restrictions, $registry, $data_types, $directories;
		protected $exported_files = array(), $exported_types = array(), $exported_langs = array(), $exported_domains = array(), $exported_templates = array(), $exported_elements = array(), $exported_objects = array(), $exported_restrictions = array(), $exported_registry_items = array(), $restricted_fields = array(), $exported_data_types = array(), $exported_dirs = array();
		protected $limit, $position = 0, $break = false;
		protected $translator;
		protected $destination;
		protected $completed = false;

		protected $doc;
		protected $root;
		protected $meta_container, $files_container, $types_container, $data_types_container, $pages_container, $objects_container, $relations_container, $restrictions_container, $registry_container, $dirs_container, $hierarchy_container;

		protected	$export_log = array();

		protected $showAllFields = false;

		protected $ignoreRelations = false;
		protected $saveRelations = array(); //files, langs, domains, templates, objects, fields_relations, restrictions, permissions, hierarchy
		protected $oldGetLinks = NULL;

		public function __construct($source_name, $entities_limit = false) {
			$this->relations = umiImportRelations::getInstance();
			$this->source_name = $source_name;
			$this->source_id = $this->relations->addNewSource($source_name);
			$this->limit = is_numeric($entities_limit) ? $entities_limit : false;

			self::$ROOT_PAGE_TYPE_ID = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('root-pages-type');
		}

		public function setIgnoreRelations($saveRelations = array()) {
			$this->ignoreRelations = true;
			$this->saveRelations = $saveRelations;
		}

		public function setShowAllFields($showAllFields = false) {
			$this->showAllFields = $showAllFields;
		}

		public function getExportLog() {
			return $this->export_log;
		}

		protected function writeLog($message) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $message . "\n\r";
			else $this->export_log[] = $message;
		}

		protected function reportError($error) {
			if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $error . "\n\r";
			else $this->export_log[] = "<font style='color:red''>" . $error . "</font>";
		}

		protected function saveState () {

			if (file_exists(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->source_name)) && $this->break === false) {
				unlink(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->source_name));
			}

			if ($this->break === true) {

				$keys = array_keys($this->exported_types, 'found');
				foreach ($keys as $key => $value) {
					unset($this->exported_types[$value]);
				}

				$keys = array_keys($this->exported_elements, 'found');
				foreach ($keys as $key => $value) {
					unset($this->exported_elements[$value]);
				}

				$keys = array_keys($this->exported_objects, 'found');
				foreach ($keys as $key => $value) {
					unset($this->exported_objects[$value]);
				}

				$array = array(
					'exported_files' => $this->exported_files,
					'exported_types' => $this->exported_types,
					'exported_langs' => $this->exported_langs,
					'exported_domains' => $this->exported_domains,
					'exported_templates' => $this->exported_templates,
					'exported_elements' => $this->exported_elements,
					'exported_objects' => $this->exported_objects,
					'restricted_fields' =>$this->restricted_fields,
					'restrictions' => $this->restrictions,
					'exported_restrictions' => $this->exported_restrictions,
					'exported_registry_items'=> $this->exported_registry_items,
					'exported_data_types'=> $this->exported_data_types,
					'exported_dirs'=> $this->exported_dirs
				);

				file_put_contents(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->source_name),  serialize($array));
			}
		}

		public function addElements($elements) {
			foreach ($elements as $el) {
				if ($el instanceof umiHierarchyElement) $el = $el->getId();
				$this->elements[] = $el;
			}
		}

		public function addBranches($branches) {
			foreach ($branches as $el) {
				if ($el instanceof umiHierarchyElement) $el = $el->getId();
				$this->branches[] = $el;
			}
		}

		public function addObjects($objects) {
			foreach ($objects as $obj) {
				if ($obj instanceof umiObject) $obj = $obj->getId();
				if ($obj) $this->objects[] = $obj;
			}
		}

		public function addTypes($types) {
			foreach ($types as $type) {
				if ($type instanceof umiObjectType) $type = $type->getId();
				$this->types[] = $type;
			}
		}

		public function addRestrictions($restrictions) {
			foreach ($restrictions as $restriction) {
				if ($restriction instanceof baseRestriction) $restriction = $restriction->getId();
				$this->restrictions[] = $restriction;
			}
		}

		public function addRegistry($paths = array()) {
			foreach ($paths as $path) {
				$this->registry[] = $path;
			}
		}

		public function setDestination($destination) {
			if (!is_dir($destination)) {
				$this->reportError("Destination folder does not exist");
				return false;
			}
			$this->destination = $destination;
		}

		public function addFiles($fsObjects = array()) {
			foreach ($fsObjects as $fsObject) {
				if (is_file($fsObject)) $this->files[] = new umiFile($fsObject);
				else {
				$this->reportError("File {$fsObject} doesn't exist");
				}
			}
		}

		public function addDirs($fsObjects = array()) {
			foreach ($fsObjects as $fsObject) {
				if (is_dir($fsObject)) $this->directories[] = new umiDirectory($fsObject);
				else {
				$this->reportError("Folder {$fsObject} doesn't exist");
				}
			}
		}

		public function addDomains($domains = array()) {
			foreach ($domains as $domain) {
				if($domain instanceof domain) $this->domains[] = $domain;
			}
		}

		public function addLangs($langs = array()) {
			foreach ($langs as $lang) {
				if($lang instanceof lang) $this->langs[] = $lang;
			}
		}

		public function addTemplates($templates = array()) {
			foreach ($templates as $template) {
				if($template instanceof template) $this->templates[] = $template;
			}
		}

		public function addDataTypes($data_types = array()) {
			foreach ($data_types as $data_type) {
				if($data_type instanceof umiFieldType) $this->data_types[] = $data_type->getId();
			}
		}

		public function isCompleted() {
			return $this->completed;
		}

		public function execute() {

			if(!is_null(getRequest('links'))) {
				$this->oldGetLinks = getRequest('links');
				unset($_REQUEST['links']);
			}

			$this->position = 0;
			$this->break = false;
			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$root = $doc->createElement("umidump");
			$root->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$a = $doc->createAttribute("version");
			$a->appendChild($doc->createTextNode(self::VERSION));
			$root->appendChild($a);

			$doc->appendChild($root);

			$this->translator = new xmlTranslator($doc);

			if ($this->showAllFields) {
				$oldshowHiddenFieldGroups = xmlTranslator::$showHiddenFieldGroups;
				xmlTranslator::$showHiddenFieldGroups = true;
				$oldshowUnsecureFields = xmlTranslator::$showUnsecureFields;
				xmlTranslator::$showUnsecureFields = true;
			}

			$oldIgnoreCache = umiObjectProperty::$IGNORE_CACHE;
			umiObjectProperty::$IGNORE_CACHE = true;

			$this->doc = $doc;
			$this->root = $root;

			if (file_exists(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->source_name))) {
				$array = unserialize(file_get_contents(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->source_name)));
				$this->exported_files = $array['exported_files'];
				$this->exported_types = $array['exported_types'];
				$this->exported_langs = $array['exported_langs'];
				$this->exported_domains = $array['exported_domains'];
				$this->exported_templates = $array['exported_templates'];
				$this->exported_elements = $array['exported_elements'];
				$this->exported_objects = $array['exported_objects'];
				$this->exported_restrictions = $array['exported_restrictions'];
				$this->restricted_fields = $array['restricted_fields'];
				$this->restrictions = $array['restrictions'];
				$this->exported_registry_items = $array['exported_registry_items'];
				$this->exported_data_types = $array['exported_data_types'];
				$this->exported_dirs = $array['exported_dirs'];
			}

			$this->createGrid();

			if ($this->directories && !$this->break) $this->exportDirs();
			if ($this->files && !$this->break) $this->exportFiles();
			if ($this->langs && !$this->break) $this->exportLangs();
			if ($this->domains && !$this->break) $this->exportDomains();
			if ($this->templates && !$this->break) $this->exportTemplates();
			if ($this->data_types && !$this->break) $this->exportDataTypes();
			if ($this->types && !$this->break) $this->exportTypes();
			if ($this->objects && !$this->break) $this->exportObjects();
			if ($this->elements && !$this->break) $this->exportElements();
			if ($this->branches && !$this->break) $this->exportBranches();
			if ($this->restrictions && !$this->break) $this->exportRestrictions();
			if ($this->registry && !$this->break) $this->exportRegs();

			$d = $this->doc;
			$m = $this->meta_container;

			$this->completed = !$this->break;

			if (count($this->branches) && $this->completed) {
				// check branch id by relation
				$branches = array();
				foreach ($this->branches as $branch_id) {
					if (isset($this->exported_elements[$branch_id]) && $this->exported_elements[$branch_id] != 'found') {
						$branch_id = $this->exported_elements[$branch_id];
					}
					$branches[] = $branch_id;
				}
				$n = $d->createElement('branches');
				$this->translateEntity(array('nodes:id' => $branches), $n);
				$m->appendChild($n);
			}

			$this->saveState();

			if ($this->showAllFields) {
				xmlTranslator::$showHiddenFieldGroups = $oldshowHiddenFieldGroups;
				xmlTranslator::$showUnsecureFields = $oldshowUnsecureFields;
			}

			umiObjectProperty::$IGNORE_CACHE = $oldIgnoreCache;

			if(!is_null($this->oldGetLinks)) $_REQUEST['links'] = $this->oldGetLinks;

			return $this->doc;

		}


		protected function createDateSection($timestamp, DOMElement $container) {
			$d = $this->doc;
			$date = new umiDate($timestamp);

			$n = $d->createElement('timestamp');
			$n->appendChild($d->createTextNode($date->getFormattedDate("U")));
			$container->appendChild($n);

			$n = $d->createElement('rfc');
			$n->appendChild($d->createTextNode($date->getFormattedDate("r")));
			$container->appendChild($n);

			$n = $d->createElement('utc');
			$n->appendChild($d->createTextNode($date->getFormattedDate(DATE_ATOM)));
			$container->appendChild($n);

			return $container;
		}

		protected function createGrid() {
			// meta container
			$d = $this->doc;
			$m = $d->createElement("meta");

			$cmsController = cmsController::getInstance();
			$regedit = regedit::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$lang = $cmsController->getCurrentLang();

			$n = $d->createElement('site-name');
			$n->appendChild($d->createCDATASection($regedit->getVal("//settings/site_name")));
			$m->appendChild($n);

			$n = $d->createElement('domain');
			$n->appendChild($d->createCDATASection($domain->getHost()));
			$m->appendChild($n);

			$n = $d->createElement('lang');
			$n->appendChild($d->createCDATASection($lang->getPrefix()));
			$m->appendChild($n);

			$n = $d->createElement('source-name');
			$val = strlen($this->source_name) ? $this->source_name : md5($domain->getId() . $lang->getId());
			$n->appendChild($d->createCDATASection($val));
			$m->appendChild($n);

			$n = $d->createElement('generate-time');
			$this->createDateSection(time(), $n);
			$m->appendChild($n);

			$this->root->appendChild($m);
			$this->meta_container = $m;

			// registry container
			$this->registry_container = $d->createElement('registry');
			$this->root->appendChild($this->registry_container);
			// directories container
			$this->dirs_container = $d->createElement('directories');
			$this->root->appendChild($this->dirs_container);
			// files container
			$this->files_container = $d->createElement('files');
			$this->root->appendChild($this->files_container);
			// langs container
			$this->langs_container = $d->createElement('langs');
			$this->root->appendChild($this->langs_container);
			// domains container
			$this->domains_container = $d->createElement('domains');
			$this->root->appendChild($this->domains_container);
			// templates container
			$this->templates_container = $d->createElement('templates');
			$this->root->appendChild($this->templates_container);
			// data_types container
			$this->data_types_container = $d->createElement('datatypes');
			$this->root->appendChild($this->data_types_container);
			// types container
			$this->types_container = $d->createElement('types');
			$this->root->appendChild($this->types_container);
			// objects container
			$this->objects_container = $d->createElement('objects');
			$this->root->appendChild($this->objects_container);
			// pages container
			$this->pages_container = $d->createElement('pages');
			$this->root->appendChild($this->pages_container);
			// relations container
			$this->relations_container = $d->createElement('relations');
			$this->root->appendChild($this->relations_container);
			// options container
			$this->options_container = $d->createElement('options');
			$this->root->appendChild($this->options_container);
			// restrictions container
			$this->restrictions_container = $d->createElement('restrictions');
			$this->root->appendChild($this->restrictions_container);
			// permissions container
			$this->permissions_container = $d->createElement('permissions');
			$this->root->appendChild($this->permissions_container);
			// hierarchy container
			$this->hierarchy_container = $d->createElement('hierarchy');
			$this->root->appendChild($this->hierarchy_container);

		}

		protected function translateEntity($entity, $container) {
			$result = $this->translator->chooseTranslator($container, $entity, true);
		}

		protected function exportFile(umiFile $file) {

			$path = $file->getFilePath();

			if (isset($this->exported_files[$path])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$fileName = $file->getFileName();

			$c = $this->doc->createElement('file');
			$this->files_container->appendChild($c);
			$this->translateEntity($file, $c);

			$hash = md5_file($path);
			//set hash
			$hashAttribute = $this->doc->createAttribute("hash");
			$c->appendChild($hashAttribute);
			$hashText = $this->doc->createTextNode("{$hash}");
			$hashAttribute->appendChild($hashText);

			$fileName = $file->getFileName();
			$nameAttribute = $this->doc->createAttribute("name");
			$c->appendChild($nameAttribute);
			$nameText = $this->doc->createTextNode("{$fileName}");
			$nameAttribute->appendChild($nameText);

			if ($this->destination) {
				$filePath = $this->destination . $file->getFilePath(true);
				$filePathDir = dirname($filePath);

				if (!file_exists($filePathDir)) mkdir($filePathDir, 0777, true);
				if (copy($path, $filePath)) {
					chmod($filePath, 0777);
				} else {
					$this->reportError("File \"{$path} \" cannot be copied to \"{$filePath}\"");
				}
			} else {
				$this->reportError('Files cannot be copied because destination folder isn\'t defined');
			}

			$this->exported_files[$path] = $path;
			$this->position++;
			return true;
		}

		protected function exportDir(umiDirectory $directory) {

			$path = $directory->getPath();
			$nodeValue = ltrim($path, '.');

			if (isset($this->exported_dirs[$path])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$c = $this->doc->createElement('directory', $nodeValue);
			$this->dirs_container->appendChild($c);

			$c->setAttribute('path', $path);
			$c->setAttribute('name', $directory->getName());

			$this->exported_dirs[$path] = $path;
			$this->position++;
			return true;
		}

		protected function exportFiles() {
			foreach ($this->files as $file) {
				$this->exportFile($file);
			}

			if ($this->destination) {
				$newDirectory = new umiDirectory($this->destination);
				$newDirectoryDirs = $newDirectory->getFSObjects(2);
				foreach ($newDirectoryDirs as $key => $dir) {
					chmod($dir, 0777);
				}
			}
		}

		protected function exportDirs() {
			foreach ($this->directories as $directory) {
				$this->exportDir($directory);
			}
		}

		protected function exportLang(lang $lang) {

			$langId = $lang->getId();
			if (isset($this->exported_langs[$langId])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$c = $this->doc->createElement('lang');
			$this->translateEntity($lang, $c);

			$rel_lang_id = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			if ($rel_lang_id === false) {
				$this->relations->setLangIdRelation($this->source_id, $langId, $langId);
				$rel_lang_id = $langId;
			} else {
				$c->setAttribute('id', $rel_lang_id);
			}

			$this->langs_container->appendChild($c);
			$this->exported_langs[$langId] = $rel_lang_id;
			$this->position++;
			return true;
		}

		protected function exportLangs() {
			foreach ($this->langs as $lang) {
				$this->exportLang($lang);
			}
		}

		protected function exportDomain(domain $domain) {
			$domainId = $domain->getId();
			if (isset($this->exported_domains[$domainId])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$c = $this->doc->createElement('domain');
			$this->translateEntity($domain, $c);

			$rel_domain_id = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			if ($rel_domain_id === false) {
				$this->relations->setDomainIdRelation($this->source_id, $domainId, $domainId);
				$rel_domain_id = $domainId;
			} else {
				$c->setAttribute('id', $rel_domain_id);
			}

			//set lang
			$langId = $domain->getDefaultLangId();
			$lang = langsCollection::getInstance()->getLang($langId);

			if ($this->exportLang($lang)) {
				if ($this->limit && $this->position >= $this->limit) {
					$this->break = true;
					return true;
				}
			}
			$rel_lang_id = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			$c->setAttribute('lang-id', $rel_lang_id);

			$domainMirrors = $domain->getMirrowsList();
			foreach ($domainMirrors as $domainMirror) {

				$m = $this->doc->createElement('domain-mirror');
				$c->appendChild($m);
				$this->translateEntity($domainMirror, $m);

				$mirror_id = $domainMirror->getId();

				$rel_mirror_id = $this->relations->getOldDomainMirrorIdRelation($this->source_id, $mirror_id);
				if ($rel_mirror_id === false) {
					$this->relations->setDomainMirrorIdRelation($this->source_id, $mirror_id, $mirror_id);
					$rel_mirror_id = $mirror_id;
				} else {
					$m->setAttribute('id', $rel_mirror_id);
				}
			}

			$this->domains_container->appendChild($c);
			$this->exported_domains[$domainId] = $rel_domain_id;
			$this->position++;
			return true;
		}

		protected function exportDomains() {
			foreach ($this->domains as $domain) {
				$this->exportDomain($domain);
			}
		}

		protected function exportTemplate(template $template) {

			$templateId = $template->getId();

			if (isset($this->exported_templates[$templateId])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$c = $this->doc->createElement('template');
			$this->translateEntity($template, $c);

			$rel_template_id = $this->relations->getOldTemplateIdRelation($this->source_id, $templateId);
			if ($rel_template_id === false) {
				$this->relations->setTemplateIdRelation($this->source_id, $templateId, $templateId);
				$rel_template_id = $templateId;
			} else {
				$c->setAttribute('id', $rel_template_id);
			}

			//set lang
			$langId = $template->getLangId();
			$lang = langsCollection::getInstance()->getLang($langId);
			if($this->exportLang($lang)) {
				if ($this->limit && $this->position >= $this->limit) {
					$this->break = true;
					return true;
				}
			}
			$rel_lang_id = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			$c->setAttribute('lang-id', $rel_lang_id);

			//set domain
			$domainId = $template->getDomainId();
			$domain = domainsCollection::getInstance()->getDomain($domainId);
			if ($this->exportDomain($domain)) {
				if ($this->limit && $this->position >= $this->limit) {
					$this->break = true;
					return true;
				}
			}
			$rel_domain_id = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			$c->setAttribute('domain-id', $rel_domain_id);

			$this->templates_container->appendChild($c);
			$this->exported_templates[$templateId] = $rel_template_id;
			$this->position++;
			return true;
		}

		protected function exportTemplates() {
			foreach ($this->templates as $template) {
				$this->exportTemplate($template);
			}
		}

		protected function exportDataType($type_id) {
			$type = umiFieldTypesCollection::getInstance()->getFieldType($type_id);
			if (!$type instanceof umiFieldType) return false;

			if (isset($this->exported_data_types[$type_id])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$c = $this->doc->createElement('datatype');
			$this->data_types_container->appendChild($c);
			$this->translateEntity($type, $c);

			$c->removeAttribute('id');

			$this->exported_data_types[$type_id] = $type_id;
		}

		protected function exportType($type_id) {
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);
			if (!$type instanceof umiObjectType) return false;

			if (isset($this->exported_types[$type_id])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$this->exported_types[$type_id] = 'found';

			// export parent type
			$parent_type_id = $type->getParentId();
			if ($parent_type_id) {
				if ($this->exportType($parent_type_id)){
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
			}

			$c = $this->doc->createElement('type');
			$this->translateEntity($type, $c);

			// check type relation
			$rel_type_id = $this->relations->getOldTypeIdRelation($this->source_id, $type_id);
			if (!$rel_type_id) {
				$rel_type_id = ($type_id == self::$ROOT_PAGE_TYPE_ID) ? '{root-pages-type}' : $type_id;
				$this->relations->setTypeIdRelation($this->source_id, $rel_type_id, $type_id);
			}
			$c->setAttribute('id', $rel_type_id);

			// check parent type relations
			$parent_type_id = $type->getParentId();
			if ($parent_type_id) {
				$rel_parent_type_id = $this->relations->getOldTypeIdRelation($this->source_id, $parent_type_id);
				if ($rel_parent_type_id === false) {
					$rel_parent_type_id = ($parent_type_id == self::$ROOT_PAGE_TYPE_ID) ? '{root-pages-type}' : $parent_type_id;
					$this->relations->setTypeIdRelation($this->source_id, $rel_parent_type_id, $parent_type_id);
				}
				$c->setAttribute('parent-id', $rel_parent_type_id);
			}

			$parser = new DOMXPath($this->doc);

			if($parser->evaluate("base", $c)->length) {
				$base = $parser->evaluate("base", $c)->item(0);
				$base->removeAttribute('id');
			}

			// check fields relations
			if($parser->evaluate("fieldgroups/group", $c)->length) {
				foreach($parser->evaluate("fieldgroups/group", $c) as $group) {
					$groupId = $group->getAttribute('id');
					$typeGroup = $type->getFieldsGroup($groupId, true);
					if ($typeGroup->getIsActive()) {
						$group->setAttribute('active', 'active');
					} else {
						$group->setAttribute('active', '0');
					}
					if(!$typeGroup->getIsVisible()) {
						$group->setAttribute('visible', '0');
					}
					$group->removeAttribute('id');
				}
			}

			$relationsToExport = array();
			$fieldsCollection = umiFieldsCollection::getInstance();
			$nl = $parser->evaluate("fieldgroups/group/field", $c);
			foreach ($nl as $field) {
				$field_id = intval($field->getAttribute('id'));
				$field_name = $field->getAttribute('name');
				$rel_field_name = $this->relations->getOldFieldName($this->source_id, $type_id, $field_id);

				if ($rel_field_name === false) {
					$this->relations->setFieldIdRelation($this->source_id, $type_id, $field_name, $field_id);
					$rel_field_name = $field_name;
				} else {
					$field->setAttribute('name', $rel_field_name);
				}

				if($field->getElementsByTagName('restriction')->length) {
					$field_restriction = $field->getElementsByTagName('restriction')->item(0);
					$restriction_id = $field_restriction->getAttribute('id');

					$this->restrictions[] = $restriction_id;
					$this->restricted_fields[] = array(
						'restriction-id' => $restriction_id,
						'field-name' => $rel_field_name,
						'type-id' => $rel_type_id
					);
					$field_restriction->removeAttribute('field-type-id');
				}

				$guide_id = $field->hasAttribute('guide-id') ? $field->getAttribute('guide-id') : false;

				if ($guide_id && (!$this->ignoreRelations || in_array('guides', $this->saveRelations))) {
					if ($this->exportType($guide_id)){
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return true;
						}
					}

					$sel = new selector('objects');
					$sel->types('object-type')->id($guide_id);
					foreach ($sel->result as $object) {
						if ($this->exportObject($object->id)) {
							if ($this->limit && $this->position >= $this->limit) {
								$this->break = true;
								return true;
							}
						}
					}

					$new_guide_id = $this->relations->getOldTypeIdRelation($this->source_id, $guide_id);
					$field->setAttribute('guide-id', $new_guide_id);

					$r = $this->doc->createElement('relation');
					$r->setAttribute('type-id', $rel_type_id);
					$r->setAttribute('field-name', $rel_field_name);
					$g = $this->doc->createElement('guide');
					$g->setAttribute('id', $new_guide_id);
					$r->appendChild($g);
					$relationsToExport[] = $r;
				}

				if ($field->getElementsByTagName('type')->length){
					$field_type = $field->getElementsByTagName('type')->item(0);
					$field_type->removeAttribute('id');
				}

				$typeField = $fieldsCollection->getField($field_id);
				if ($typeField->getIsSystem()) {
					$field->setAttribute('system', 'system');
				}
				//$field->removeAttribute('id');
				$field->removeAttribute('field-type-id');

			}

			foreach ($relationsToExport as $r) {
				$this->relations_container->appendChild($r);
			}

			$this->types_container->appendChild($c);
			$this->exported_types[$type_id] = $rel_type_id;
			$this->position++;
			return true;
		}

		protected function exportRestriction($restriction_id) {

			if (isset($this->exported_restrictions[$restriction_id])) return false;

			$restriction = baseRestriction::get($restriction_id);
			if (!$restriction instanceof baseRestriction) return false;

			$restriction_prefix = $restriction->getClassName();
			$restriction_title = $restriction->getTitle();
			$type_id = $restriction->getFieldTypeId();
			$type = umiFieldTypesCollection::getInstance()->getFieldType($type_id);
			$data_type = $type->getDataType();
			$is_multiple = $type->getIsMultiple();

			$rel_restriction_id = $this->relations->getOldRestrictionIdRelation($this->source_id, $restriction_id);
			if (!$rel_restriction_id) {
				$this->relations->setRestrictionIdRelation($this->source_id, $restriction_id, $restriction_id);
				$rel_restriction_id = $restriction_id;
			}

			$o = $this->doc->createElement('restriction');
			$o->setAttribute('id', $rel_restriction_id);
			$o->setAttribute('prefix', $restriction_prefix);
			$o->setAttribute('title', $restriction_title);
			$o->setAttribute('field-type', $data_type);
			$o->setAttribute('is-multiple', $is_multiple);

			foreach ($this->restricted_fields as $key => $value) {
				if ($value['restriction-id'] == $restriction_id) {
					$f = $this->doc->createElement('field');
					$f->setAttribute('field-name', $value['field-name']);
					$f->setAttribute('type-id', $value['type-id']);
					$o->appendChild($f);
				}
			}

			$this->restrictions_container->appendChild($o);
			$this->exported_restrictions[$restriction_id] = $rel_restriction_id;
			$this->position++;
			return true;
		}

		protected function exportReg($path) {

			if (isset($this->exported_registry_items[$path])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$regedit = regedit::getInstance();
			$path = trim($path, "/");

			$key = $regedit->getKey($path);
			$val = $regedit->getVal($path);
			if (!$key) return false;

			if(strrpos($path, '/') != false) {
				$parent_path = substr_replace($path, '', strrpos($path, '/'));
				if ($this->exportReg($parent_path)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
			}

			$i = $this->doc->createElement('key');
			$this->registry_container->appendChild($i);
			$i->setAttribute('path', $path);
			$i->setAttribute('val', $val);

			$this->exported_registry_items[$path] = $path;
			$this->position++;
			return true;

		}

		protected function exportRestrictions() {
			if (!$this->ignoreRelations || in_array('restrictions', $this->saveRelations)){
			foreach ($this->restrictions as $restriction) {
				$this->exportRestriction($restriction);
			}
		}
		}

		protected function exportTypes() {
			foreach ($this->types as $type) {
				$this->exportType($type);
			}
		}

		protected function exportBranches() {

			foreach ($this->branches as $branch) {
				if ($this->break) break;
				$this->exportBranch($branch);
			}
		}

		protected function exportBranch($element_id) {
			$this->exportElement($element_id);
			$childs = umiHierarchy::getInstance()->getChilds($element_id, true, true, 1);
			foreach ($childs as $child_id => $tmp) {
				if ($this->break) return false;
				$this->exportElement($child_id);
				$this->exportBranch($child_id);
			}
		}

		protected function exportElement($element_id) {

			umiHierarchy::getInstance()->clearCache();

			if (isset($this->exported_elements[$element_id])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$this->exported_elements[$element_id] = 'found';

			$element = umiHierarchy::getInstance()->getElement($element_id, true, true);
			if (!$element instanceof umiHierarchyElement) return false;

			$type_id = $element->getObjectTypeId();
			if ($this->exportType($type_id)) {
				if ($this->limit && $this->position >= $this->limit) {
					$this->break = true;
					return true;
				}
			}

			$c = $this->doc->createElement('page');
			$this->translateEntity($element, $c);

			$c->removeAttribute('update-time');

			//set lang
			$langId = $element->getLangId();
			if (!$this->ignoreRelations || in_array('langs', $this->saveRelations)) {
				$lang = langsCollection::getInstance()->getLang($langId);
				if ($this->exportLang($lang)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
				$rel_lang_id = $this->relations->getOldLangIdRelation($this->source_id, $langId);
			} else {
				$rel_lang_id = $this->relations->getOldLangIdRelation($this->source_id, $langId);
				if ($rel_lang_id === false) {
					$this->relations->setLangIdRelation($this->source_id, $langId, $langId);
					$rel_lang_id = $langId;
				}
			}
			$c->setAttribute('lang-id', $rel_lang_id);

			//set domain
			$domainId = $element->getDomainId();

			if (!$this->ignoreRelations || in_array('domains', $this->saveRelations)) {
				$domain = domainsCollection::getInstance()->getDomain($domainId);
				if ($this->exportDomain($domain)){
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
				$rel_domain_id = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
			} else {
				$rel_domain_id = $this->relations->getOldDomainIdRelation($this->source_id, $domainId);
				if ($rel_domain_id === false) {
					$this->relations->setDomainIdRelation($this->source_id, $domainId, $domainId);
					$rel_domain_id = $domainId;
				}
			}
			$c->setAttribute('domain-id', $rel_domain_id);


			// export template
			$tpl_id			= $element->getTplId();

			if (!$this->ignoreRelations || in_array('templates', $this->saveRelations)) {
				$template = templatesCollection::getInstance()->getTemplate($tpl_id);
				if ($this->exportTemplate($template)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
				$rel_template_id = $this->relations->getOldTemplateIdRelation($this->source_id, $tpl_id);
			} else {
				$rel_template_id = $this->relations->getOldTemplateIdRelation($this->source_id, $tpl_id);
				if ($rel_template_id === false) {
					$this->relations->setTemplateIdRelation($this->source_id, $tpl_id, $tpl_id);
					$rel_template_id = $tpl_id;
				}
			}

			$tpl_path		= templatesCollection::getInstance()->getTemplate($tpl_id)->getFilename();
			$tpl_node = $this->doc->createElement('template');
			$tpl_node->setAttribute("id", $rel_template_id);
			$tpl_node->appendChild($this->doc->createTextNode($tpl_path));
			$c->appendChild($tpl_node);

			// check relation
			$rel_element_id = $this->relations->getOldIdRelation($this->source_id, $element_id);
			if ($rel_element_id === false) {
				$this->relations->setIdRelation($this->source_id, $element_id, $element_id);
				$rel_element_id = $element_id;
			} else {
				$c->setAttribute('id', $rel_element_id);
			}

			// check parent relation
			$parent_id = $element->getParentId();
			if ($parent_id) {
				$rel_parent_id = $this->relations->getOldIdRelation($this->source_id, $parent_id);
				if ($rel_parent_id === false) {
					$this->relations->setIdRelation($this->source_id, $parent_id, $parent_id);
					$rel_parent_id = $parent_id;
				}
					$c->setAttribute('parentId', $rel_parent_id);
			}

			$parser = new DOMXPath($this->doc);

			if($parser->evaluate("basetype", $c)->length) {
				$base = $parser->evaluate("basetype", $c)->item(0);
				$base->removeAttribute('id');
			}

			// set type-id by releation
			$rel_type_id = $this->relations->getOldTypeIdRelation($this->source_id, $type_id);
			$c->setAttribute('type-id', $rel_type_id);

			// set object-id by releation
			$object_id = $c->getAttribute('object-id');

			if (!$this->ignoreRelations || in_array('objects', $this->saveRelations)) {

				if ($this->exportObject($object_id)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return true;
					}
				}
				$rel_object_id = $this->relations->getOldObjectIdRelation($this->source_id, $object_id);

			} else {
				$rel_object_id = $this->relations->getOldObjectIdRelation($this->source_id, $object_id);
				if ($rel_object_id === false) {
					$this->relations->setObjectIdRelation($this->source_id, $object_id, $object_id);
					$rel_object_id = $object_id;
				}
			}

			$c->setAttribute('object-id', $rel_object_id);

			// set fields id by relations
			if($parser->evaluate("properties/group", $c)->length) {

				foreach($parser->evaluate("properties/group", $c) as $group)
					if($group->hasAttribute('id')) $group->removeAttribute('id');
			}
			$nl = $parser->evaluate("properties/group/property", $c);

			$relationsToExport = array();
			$optionsToExport = array();
			foreach ($nl as $field) {
				$field_id = intval($field->getAttribute('id'));
				$rel_field_name = $this->relations->getOldFieldName($this->source_id, $type_id, $field_id);

				if ($rel_field_name) {
					$field->setAttribute('name', $rel_field_name);
				}

				$field_type = $field->getAttribute('type');

				if (!$this->ignoreRelations || in_array('fields_relations', $this->saveRelations)) {

					if ($field_type == 'relation') {
						$r = $this->doc->createElement('relation');
						$r->setAttribute('page-id', $rel_element_id );
						$r->setAttribute('field-name', $rel_field_name);
						if (!$this->exportRelation($r, $field)) return true;
						$relationsToExport[] = $r;
					}
					if ($field_type == 'symlink') {
						$r = $this->doc->createElement('relation');
						$r->setAttribute('page-id', $rel_element_id);
						$r->setAttribute('field-name', $rel_field_name);
						if (!$this->exportRelation($r, $field)) return true;
						$relationsToExport[] = $r;
					}
					if($field_type == 'optioned') {
						$e  = $this->doc->createElement('entity');
						$e->setAttribute('page-id', $rel_element_id);
						$e->setAttribute('field-name', $rel_field_name);
						if (!$this->exportOption($field, $e)) return true;
						$optionsToExport[] = $e;
					}
				}

				if (!$this->ignoreRelations || in_array('files', $this->saveRelations)) {

				if($field_type == 'file' || $field_type == 'swf_file' || $field_type == 'img_file') {
					$file_path = $field->getElementsByTagName('value')->item(0)->nodeValue;
					if (file_exists(CURRENT_WORKING_DIR . $file_path)) {
						$file = new umiFile(CURRENT_WORKING_DIR . $file_path);
						$this->exportFile($file);
					} elseif (file_exists('./' . $file_path)) {
						$file = new umiFile('./' . $file_path);
						$this->exportFile($file);
					}
				}

			}

				//$field->removeAttribute('id');
			}

			$permissionsToExport = array();

			if (!$this->ignoreRelations || in_array('permissions', $this->saveRelations)) {
				$permissions = permissionsCollection::getInstance()->getRecordedPermissions($element_id);
				if(is_array($permissions)) {
					$p = $this->doc->createElement('permission');
					$p->setAttribute('page-id', $rel_element_id);
					foreach ($permissions as $key => $value) {
						$o = $this->doc->createElement('owner');
						if ($this->exportObject($key)){
							if ($this->limit && $this->position >= $this->limit) {
								$this->break = true;
								return true;
							}
						}
						$rel_key = $this->relations->getOldObjectIdRelation($this->source_id, $key);
						$o->setAttribute('id', $rel_key);
						$o->setAttribute('level', $value);
						$p->appendChild($o);
					}
					$permissionsToExport[] = $p;
				}
			}

			foreach ($relationsToExport as $r) {
				$this->relations_container->appendChild($r);
			}
			foreach ($optionsToExport as $e) {
				$this->options_container->appendChild($e);
			}
			foreach ($permissionsToExport as $p) {
				$this->permissions_container->appendChild($p);
			}

			if (!$this->ignoreRelations || in_array('hierarchy', $this->saveRelations)) {

				$ord = $element->getOrd();
				$h = $this->doc->createElement('relation');
				$h->setAttribute('id', $rel_element_id);
				$h->setAttribute('ord', $ord);
				if($parent_id) {
					$h->setAttribute('parent-id', $rel_parent_id);
				} else {
					$h->setAttribute('parent-id', 0);
				}
				$this->hierarchy_container->appendChild($h);
			}

			$this->pages_container->appendChild($c);
			$this->exported_elements[$element_id] = $rel_element_id;
			$this->position++;

			return true;

		}

		protected function exportRelation($r, $field) {

			$pages = $field->getElementsByTagName('page');
			foreach ($pages as $page) {
				if ($this->exportElement($page->getAttribute('id'))){
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}

				$page_id = $this->relations->getOldIdRelation($this->source_id, $page->getAttribute('id'));
				$g = $this->doc->createElement('page');
				$g->setAttribute('id', $page_id);
				$page->setAttribute('id', $page_id);
				if ($this->exportType($page->getAttribute('type-id'))){
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}
				$type_id = $this->relations->getOldTypeIdRelation($this->source_id, $page->getAttribute('type-id'));
				$page->setAttribute('type-id', $type_id);

				$parent_id = $page->getAttribute('parentId');
				if ($this->exportElement($parent_id)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}
				$rel_parent_id = $this->relations->getOldIdRelation($this->source_id, $parent_id);
				$page->setAttribute('parentId', $rel_parent_id);

				$object_id = $page->getAttribute('object-id');
				if ($this->exportObject($object_id)){
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}
				$rel_object_id = $this->relations->getOldObjectIdRelation($this->source_id, $object_id);
				$page->setAttribute('object-id', $rel_object_id);

				$page->removeAttribute('xlink:href');
				$page->removeAttribute('update-time');
				if($page->getElementsByTagName('basetype')->length){
					$base = $page->getElementsByTagName('basetype')->item(0);
					$base->removeAttribute('id');
				}

				$r->appendChild($g);
			}

			$items = $field->getElementsByTagName('item');
			foreach ($items as $item) {
				$item_id = $item->getAttribute('id');
				if ($this->exportObject($item_id)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}
				$rel_item_id = $this->relations->getOldObjectIdRelation($this->source_id, $item_id);
				$item->setAttribute('id', $rel_item_id);

				$item_type = $item->getAttribute('type-id');
				if ($this->exportType($item_type)) {
					if ($this->limit && $this->position >= $this->limit) {
						$this->break = true;
						return false;
					}
				}
				$rel_item_type = $this->relations->getOldTypeIdRelation($this->source_id, $item_type);
				$item->setAttribute('type-id', $rel_item_type);
				if ($item->hasAttribute('ownerId')) {
					$item_owner_id = $item->getAttribute('ownerId');
					if ($this->exportObject($item_owner_id)) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return false;
						}
					}
					$rel_item_owner_id = $this->relations->getOldObjectIdRelation($this->source_id, $item_owner_id);
					$item->setAttribute('ownerId', $rel_item_owner_id);
				}

				$item->removeAttribute('xlink:href');
				$g = $this->doc->createElement('object');
				$g->setAttribute('id', $rel_item_id);
				$r->appendChild($g);
			}
			return true;
		}

		protected function exportObject($object_id) {

			if (isset($this->exported_objects[$object_id])) return false;

			if ($this->limit) {
				if ($this->position >= $this->limit) {
					 $this->break = true;
					 return false;
				}
			}

			$this->exported_objects[$object_id] = 'found';

			$object = umiObjectsCollection::getInstance()->getObject($object_id);
			if (!$object instanceof umiObject) return false;

			$type_id = $object->getTypeId();
			if ($this->exportType($type_id)) {
				if ($this->limit && $this->position >= $this->limit) {
					$this->break = true;
					return true;
				}
			}

			$c = $this->doc->createElement('object');
			$this->translateEntity($object, $c);

			// check object relation
			$rel_object_id = $this->relations->getOldObjectIdRelation($this->source_id, $object_id);
			if ($rel_object_id === false) {
				$this->relations->setObjectIdRelation($this->source_id, $object_id, $object_id);
				$rel_object_id = $object_id;
			} else {
				$c->setAttribute('id', $rel_object_id);
			}

			$parser = new DOMXPath($this->doc);

			// set type-id by releation
			$rel_type_id = $this->relations->getOldTypeIdRelation($this->source_id, $type_id);
			$c->setAttribute('type-id', $rel_type_id);

			// set fields id by relations
			if($parser->evaluate("properties/group", $c)->length) {

				foreach($parser->evaluate("properties/group", $c) as $group)
					if($group->hasAttribute('id')) $group->removeAttribute('id');
			}

			$relationsToExport = array();
			$optionsToExport = array();

			$nl = $parser->evaluate("properties/group/property", $c);
			foreach ($nl as $field) {
				$field_id = intval($field->getAttribute('id'));
				$rel_field_name = $this->relations->getOldFieldName($this->source_id, $type_id, $field_id);

				if ($rel_field_name) {
					$field->setAttribute('name', $rel_field_name);
				}

				if(!$this->ignoreRelations || in_array('fields_relations', $this->saveRelations)) {

					if ($field->getAttribute('type') == 'relation') {
						$r = $this->doc->createElement('relation');
						$r->setAttribute('object-id', $rel_object_id);
						$r->setAttribute('field-name', $rel_field_name);
						if (!$this->exportRelation($r, $field)) return true;
						$relationsToExport[] = $r;

					}
					if ($field->getAttribute('type') == 'symlink') {
						$r = $this->doc->createElement('relation');
						$r->setAttribute('object-id', $rel_object_id);
						$r->setAttribute('field-name', $rel_field_name);
						if (!$this->exportRelation($r, $field)) return true;
						$relationsToExport[] = $r;
					}

					if($field->getAttribute('type') == 'optioned') {
						$e = $this->doc->createElement('entity');
						$e->setAttribute('object-id', $rel_object_id);
						$e->setAttribute('field-name', $rel_field_name);
						if (!$this->exportOption($field, $e)) return true;
						$optionsToExport[] = $e;
					}
				}

				//$field->removeAttribute('id');
			}

			$permissionsToExport = array();
			if(!$this->ignoreRelations || in_array('permissions', $this->saveRelations)) {

				$p = $this->doc->createElement('permission');

				$sql = "SELECT `module`, `method`, `allow` FROM cms_permissions WHERE owner_id = '{$object_id}'";
				$result = l_mysql_query($sql);
				while(list($module, $method, $allow) = mysql_fetch_row($result)) {
					$m = $this->doc->createElement('module');
					$m->setAttribute('name', $module);
					$m->setAttribute('method', $method);
					$m->setAttribute('allow', $allow);
					$p->appendChild($m);
				}

				$p->setAttribute('object-id', $rel_object_id);

				if ($c->hasAttribute('ownerId')) {

					$owner_id = $object->getOwnerId();

					if ($this->exportObject($owner_id)) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return true;
						}
					}

					$rel_owner_id = $this->relations->getOldObjectIdRelation($this->source_id, $owner_id);
					$c->setAttribute('ownerId', $rel_owner_id);
					$o = $this->doc->createElement('owner');
					$o->setAttribute('id', $rel_owner_id);
					$p->appendChild($o);
				}

				$permissionsToExport[] = $p;
			}

			foreach ($relationsToExport as $r) {
				$this->relations_container->appendChild($r);
			}
			foreach ($optionsToExport as $e) {
				$this->options_container->appendChild($e);
			}
			foreach ($permissionsToExport as $p) {
				$this->permissions_container->appendChild($p);
			}

			$this->objects_container->appendChild($c);
			$this->exported_objects[$object_id] = $object_id;
			$this->position++;
			return true;
		}

		protected function exportOption($field, $e) {
			$options = $field->getElementsByTagName('option');
			foreach ($options as $option) {
				$o = $this->doc->createElement('option');
				if($option->hasAttributes()) {
					$attributes = $option->attributes;
					if(!is_null($attributes)) {
						foreach ($attributes as $index => $attribute) {
						    $o->setAttribute($attribute->name, $attribute->value);
						}
					}
				}
				if($option->getElementsByTagName('object')->length) {
					$object = $option->getElementsByTagName('object')->item(0);
					if ($this->exportObject($object->getAttribute('id'))) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return false;
						}
					}
					$id = $this->relations->getOldObjectIdRelation($this->source_id, $object->getAttribute('id'));
					$o->setAttribute('object-id' , $id);
					$object->setAttribute('id', $id);
					if ($this->exportType($object->getAttribute('type-id'))) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return false;
						}
					}
					$type_id = $this->relations->getOldTypeIdRelation($this->source_id, $object->getAttribute('type-id'));
					$object->setAttribute('type-id', $type_id);
					if ($object->hasAttribute('ownerId')) {
						if ($owner_id = $object->getAttribute('ownerId')){
							if ($this->limit && $this->position >= $this->limit) {
								$this->break = true;
								return false;
							}
						}
						$this->exportObject($owner_id);
						$rel_owner_id = $this->relations->getOldObjectIdRelation($this->source_id, $owner_id);
						$object->setAttribute('ownerId', $rel_owner_id);
						$object->removeAttribute('xlink:href');
					}

				}
				if($option->getElementsByTagName('page')->length) {
					$page = $option->getElementsByTagName('page')->item(0);
					if ($this->exportPage($page->getAttribute('id'))) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return false;
						}
					}
					$id = $this->relations->getOldIdRelation($this->source_id, $page->getAttribute('id'));
					$o->setAttribute('page-id' , $id);
					$page->setAttribute('id', $id);
					if ($this->exportType($page->getAttribute('type-id'))) {
						if ($this->limit && $this->position >= $this->limit) {
							$this->break = true;
							return false;
						}
					}
					$type_id = $this->relations->getOldTypeIdRelation($this->source_id, $page->getAttribute('type-id'));
					$page->setAttribute('type-id', $type_id);
					$page->removeAttribute('xlink:href');
					$page->removeAttribute('update-time');
				}
				$e->appendChild($o);
			}
			return true;
		}

		protected function exportElements() {
			foreach ($this->elements as $element_id) {
				if ($this->break) return;
				$this->exportElement($element_id);
			}

		}

		protected function exportObjects() {
			foreach ($this->objects as $object_id) {
				if ($this->break) return;
				$this->exportObject($object_id);
			}
		}

		protected function exportRegs() {
			foreach ($this->registry as $reg) {
				if ($this->break) return;
				$this->exportReg($reg);
			}
		}

		protected function exportDataTypes() {
			foreach ($this->data_types as $data_type) {
				if ($this->break) return;
				$this->exportDataType($data_type);
			}
		}

	}

?>
