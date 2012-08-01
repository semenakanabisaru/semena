<?php
	class csvSplitter extends umiImportSplitter {

		const VERSION = "2.0";
		protected $sourceName;
		protected $relations;
		protected $names = array();
		protected $types = array();
		protected $titles = array();

		public $autoGuideCreation = true;

		protected function createGrid($doc) {

			$root = $doc->createElement("umidump");
			$root->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$a = $doc->createAttribute("version");
			$a->appendChild($doc->createTextNode(self::VERSION));
			$root->appendChild($a);

			$doc->appendChild($root);

			$m = $doc->createElement("meta");

			$cmsController = cmsController::getInstance();
			$regedit = regedit::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$lang = $cmsController->getCurrentLang();

			$n = $doc->createElement('site-name');
			$n->appendChild($doc->createCDATASection($regedit->getVal("//settings/site_name")));
			$m->appendChild($n);

			$n = $doc->createElement('domain');
			$n->appendChild($doc->createCDATASection($domain->getHost()));
			$m->appendChild($n);

			$n = $doc->createElement('lang');
			$n->appendChild($doc->createCDATASection($lang->getPrefix()));
			$m->appendChild($n);

			$n = $doc->createElement('source-name');
			$val = strlen($this->sourceName) ? $this->sourceName : md5($domain->getId() . $lang->getId());
			$n->appendChild($doc->createCDATASection($val));
			$m->appendChild($n);

			$n = $doc->createElement('generate-time');

			$date = new umiDate(time());

			$t = $doc->createElement('timestamp');
			$t->appendChild($doc->createTextNode($date->getFormattedDate("U")));
			$n->appendChild($t);

			$t = $doc->createElement('rfc');
			$t->appendChild($doc->createTextNode($date->getFormattedDate("r")));
			$n->appendChild($t);

			$t = $doc->createElement('utc');
			$t->appendChild($doc->createTextNode($date->getFormattedDate(DATE_ATOM)));
			$n->appendChild($t);

			$m->appendChild($n);

			$root->appendChild($m);

			return $root;

		}

		public function readDataBlock() {

			$file = new umiFile ($this->file_path);
			if ($file) $this->sourceName = $file->getFileName();

			$this->relations = umiImportRelations::getInstance();
			$this->relations->addNewSource($this->sourceName);

			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;

			$root = $this->createGrid($doc);

			$pages = $doc->createElement('pages');
			$root->appendChild($pages);

			$handle = fopen($this->file_path, "r");
			if ($handle) $continue = true;
			else $continue = false;

			$position = 0;
			$collected = 0;

			while ($continue && ($string = fgets($handle))) {

				if (substr_count($string, '"') % 2 != 0) {
					$isRecord = false;
					while (!feof($handle) && !$isRecord) {
					    $string .= fgets($handle);
						if (substr_count($string, '"') % 2 == 0) {
	        				$isRecord = true;
						}
				    }
				}
				$string = html_entity_decode($string, ENT_QUOTES, 'cp1251');
				$string = preg_replace("/([^;])\"\"/s", "$1'*//*'", $string);
				preg_match_all("/\"(.*?)\"/s", $string, $matches);
				foreach($matches[0] as $quotes) {
					$newQuotes = str_replace(";", "'////'", $quotes);
					$string = str_replace($quotes, $newQuotes, $string);
				}
				$string = preg_replace("/(.+);$/s", "$1", trim($string));

				$buffer = explode(";", $string);

				$position++;

				foreach ($buffer as $key =>$value) {
					$value = iconv('windows-1251', 'utf-8//IGNORE', $value);
					$value = str_replace("'////'", ";", $value);
					$value = str_replace("'*//*'", '"', $value);
					$value = preg_replace("/^\"(.*)\"$/s", "$1", $value);
					$value = trim($value);
					$buffer[$key] = $value;
				}

				if ($position < 4) {
					foreach ($buffer as $key => $value ) {

						if ($position == 1) {
							$this->names[$key] = $value;
						}
						elseif ($position == 2) {
							$this->titles[$key] = $value;
						}

						elseif ($position == 3) {
							$this->types[$key] = $value;
						}
					}

				} else {

					if (($position - 4)  < $this->offset) {
						continue;
					}

					if (($collected + 1) > $this->block_size) break;
					$collected++;
					$this->addElementInfo($doc, $buffer);
				}

			}

			if (feof($handle)) $continue = false;
			$this->offset += $collected;
			if (!$continue) $this->complete = true;

			return $doc;

		}

		protected function addElementInfo ($doc, $info) {
			$page = $doc->createElement('page');
			$pages = $doc->getElementsByTagName('pages')->item(0);
			$pages->appendChild($page);

			$sourceId = $this->relations->getSourceId($this->sourceName);

			$typeId = false;

			$key = array_search('type-id', $this->names);
			if ($key) {
				$typeIdentifier = $info[$key];
				if(is_numeric($typeIdentifier)) {
					$typeId = $this->relations->getNewTypeIdRelation($sourceId, $typeIdentifier);
					if (!$typeId) {
						$type = umiObjectTypesCollection::getInstance()->getType($typeIdentifier);
						if ($type instanceof umiObjectType) {
							$typeId = $typeIdentifier;
							$this->relations->setTypeIdRelation($sourceId, $typeId, $typeId);
						}
					}
				}
			}

			$parentId = 0;
			$importId = getRequest('param0');

			$key = array_search('parent-id', $this->names);
			if ($key) {
				$parentId = $info[$key];
			}
			$page->setAttribute('parentId', $parentId);

			if (!$typeId) {

				if(!$parentId){
					if ($importId) {
						$elements = umiObjectsCollection::getInstance()->getObject($importId)->elements;

						if (is_array($elements) && count($elements)) {
							$parentId = $elements[0]->getId();
						}
					}
				}

				if ($parentId) $typeId = umiHierarchy::getInstance()->getDominantTypeId($parentId);
			}

			if (!$typeId) {
				$typeId = umiObjectTypesCollection::getInstance()->getBaseType('content');
			}

			if ($typeId != $this->relations->getNewTypeIdRelation($sourceId, $typeId)) $this->relations->setTypeIdRelation($sourceId, $typeId, $typeId);

			$page->setAttribute('type-id', $typeId);

			$this->addPropertiesInfo($info, $page, $doc);
		}

		protected function addPropertiesInfo($info, $entity, $doc) {

			$properties = $doc->createElement('properties');
			$entity->appendChild($properties);

			$group = $doc->createElement('group');
			$properties->appendChild($group);
			$group->setAttribute('name', 'newGroup');

			foreach ($info as $key => $value) {

				if(!strlen($value)) continue;
				$value = strtr($value, array("&" => "&amp;", "<" => "&lt;", ">" => "&gt;"));

				if ($this->names[$key] == 'id') {
					$entity->setAttribute('id', $value);
					continue;
				}

				if ($this->names[$key] == 'is-active') {
					$entity->setAttribute('is-active', $value);
					continue;
				}

				if ($this->names[$key] == 'is-visible') {
					$entity->setAttribute('is-visible', $value);
					continue;
				}

				if ($this->names[$key] == 'is-deleted') {
					$entity->setAttribute('is-deleted', $value);
					continue;
				}

				if ($this->names[$key] == 'name') {
					$name = $doc->createElement('name', $value);
					$entity->appendChild($name);
					continue;
				}

				if ($this->names[$key] == 'type-id' || $this->names[$key] == 'parent-id') {
					continue;
				}

				if ($this->names[$key] == 'template-id' ) {

					$template = templatesCollection::getInstance()->getTemplate($value);
					if ($template instanceof template) {
						$tpl = $doc->createElement('template', $template->getFilename());
						$entity->appendChild($tpl);
						$tpl->setAttribute('id', $value);
					}

					continue;
				}

				$property = $doc->createElement('property');
				$group->appendChild($property);

				$dataType = $this->types[$key];
				$multiple = false;

				if ($dataType == 'multiple-relation') {
					$dataType = 'relation';
					$multiple = true;
				}
				if ($dataType == "tags") {
					$multiple = true;
				}

				$fieldType = umiFieldTypesCollection::getInstance()->getFieldTypeByDataType($dataType, $multiple);

				if ($fieldType instanceof umiFieldType) {
					$typeName = $fieldType->getName();
				} else {
					throw new coreException('Wrong datatype "' . $dataType .'" is given for property "'. $this->names[$key] .'"');
				}

				$property->setAttribute('name', $this->names[$key]);
				$property->setAttribute('title', $this->titles[$key]);
				$property->setAttribute('type', $dataType);
				if ($multiple) $property->setAttribute('multiple', 'multiple');
				$property->setAttribute('visible', '1');
				$property->setAttribute('allow-runtime-add', '1');

				$type = $doc->createElement('type');
				$property->appendChild($type);
				$type->setAttribute('data-type', $dataType);

				$type->setAttribute('name', $typeName);

				$title = $doc->createElement('title', $this->titles[$key]);
				$property->appendChild($title);

				if ($dataType == 'relation') {
					$propertyValue = $doc->createElement('value');
					$property->appendChild($propertyValue);
					$values = explode(',', $value);
					foreach ($values as $valueItem) {
						$item = $doc->createElement('item');
						$item->setAttribute('name', $valueItem);
						$propertyValue->appendChild($item);
					}
				} elseif($dataType == 'tags') {
					$values = explode(',', $value);
					foreach ($values as $valueItem) {
						$item = $doc->createElement('value', trim($valueItem));
						$property->appendChild($item);
					}
					$propertyValue = $doc->createElement('combined', $value);
					$property->appendChild($propertyValue);
				} else {
					$propertyValue = $doc->createElement('value', $value);
					$property->appendChild($propertyValue);
				}
			}
		}


		public function translate(DomDocument $doc) {
			// do nothing
			return $doc->saveXML();
		}

	}
?>
