<?php
	class commerceML2Splitter extends umiImportSplitter {

		public $ignoreParentGroups = false;
		public $autoGuideCreation = true;
		public $renameFiles = true;

		protected function __getNodeParents(DOMNode $element) {
			$parents = array();
			$parents[] = $element->nodeName;
			if (($parent = $element->parentNode) instanceof DOMElement) {
				$parents = array_merge($this->__getNodeParents($element->parentNode), $parents);
			}

			return $parents;
		}


		protected function __getNodePath(DOMNode $element) {
			return implode("/", $this->__getNodeParents($element));
		}


		protected function __collectGroup(DOMDocument $doc, DOMNode $groups, DOMNode $group) {
			$xpath = new DOMXPath($doc);

			$id_nl = $group->getElementsByTagName("Ид");
			$name_nl = $group->getElementsByTagName("Наименование");

			$id = $id_nl->item(0)->nodeValue;
			$name = $name_nl->item(0)->nodeValue;

			$found_nl = $xpath->evaluate(".//Группа[Ид='{$id}']", $groups);
			if ($found_nl instanceof DOMNodeList && $found_nl->length) {
				return $found_nl->item(0);
			}

			$new_group = $doc->createElement('Группа');
			$new_group->appendChild($doc->createElement('Ид', $id));
			$new_group->appendChild($doc->createElement('Наименование', $name));

			$parent = $group->parentNode ? $group->parentNode->parentNode : false;
			if ($parent && $parent->nodeName == "Группа") {
				$cparent = $this->__collectGroup($doc, $groups, $parent);
				$cgroups = $cparent->childNodes->item(2);
				if (!$cgroups) $cgroups = $cparent->appendChild($doc->createElement("Группы"));
				$cgroups->appendChild($new_group);
			} else {
				$groups->appendChild($new_group);
			}

			return $new_group;

		}

		protected function __getOffersCompare(DOMDocument $doc, DOMDocument $offer, $collected) {

			$xpath = new DOMXPath($doc);
			$result = $xpath->evaluate("/КоммерческаяИнформация/ПакетПредложений/Предложения/Предложение/Ид")->item($collected - 1);
			if (!$result) return true;

			$previousOfferIds = explode("#", $result->nodeValue);
			$previousOfferId = $previousOfferIds[0];

			$offerXpath = new DOMXPath($offer);
			$offerResult = $offerXpath->evaluate("/Предложение/Ид")->item(0);
			$offerIds = explode("#", $offerResult->nodeValue);
			$offerId = $offerIds[0];

			if ($previousOfferId != $offerId){
				return true;
			} else {
				return false;
			}
		}

		protected function readDataBlock() {
			$r = new XMLReader;
			$r->open($this->file_path/*, "utf-8", 1<<19*/);

			// set scheme, if exists
			$config = mainConfiguration::getInstance();
			$scheme_file = $config->includeParam('system.kernel') . 'subsystems/import/schemes/' . $this->type . '.xsd';
			if (is_file($scheme_file)) {
				$r->setSchema($scheme_file);
			}

			$doc = new DomDocument("1.0", "utf-8");

			$entities = array(
				"Группа",
				"Товар",
				"Предложение"
			);

			$collected = 0;
			$position = 0;
			$container = $doc;
			$continue = $r->read();

			while ($continue) {
				switch ($r->nodeType) {
					case XMLReader::ELEMENT: {

						if (in_array($r->name, $entities)) {
							if ($position++ < $this->offset) {
								$continue = $r->next(); continue(2);
							}
							if (($collected + 1) > $this->block_size) {
								if ($r->name == "Предложение"){
									$offer = DOMDocument::loadXML($r->readOuterXML());
									if($this->__getOffersCompare($doc, $offer, $collected)){
										break(2);
									}
								} else {
									break(2);
								}
							}
							$collected++;
						}

						$el = $doc->createElement($r->name, $r->value);
						$container->appendChild($el);
						if (!$r->isEmptyElement) {
							$container = $el;
						}

						// create attributes
						if ($r->attributeCount) {
							while ($r->moveToNextAttribute()) {
								$attr = $doc->createAttribute($r->name);
								$attr->appendChild($doc->createTextNode($r->value));
								$el->appendChild($attr);
							}
						}

						$node_path = $this->__getNodePath($container);
						if ($node_path == "КоммерческаяИнформация/Классификатор/Группы") {
							$groupsXML = $r->readOuterXML();
							$groups = DOMDocument::loadXML($groupsXML);
							$groups_nl = $groups->getElementsByTagName('Группа');
							foreach ($groups_nl as $group) {
								if ($position++ < $this->offset) continue;
								if (($collected + 1) > $this->block_size) break;
								$this->__collectGroup($doc, $el, $group);
								$collected++;
							}
							$container = $container->parentNode;
							$continue = $r->next();
							continue(2);
						}
					} break;

					case XMLReader::END_ELEMENT: {
						$container = $container->parentNode;
					} break;

					case XMLReader::ATTRIBUTE: {
						$attr = $doc->createAttribute($r->name);
						$attr->appendChild($doc->createTextNode($r->value));
						$container->appendChild($attr);
					} break;

					case XMLReader::TEXT: {
						$txt =  $doc->createTextNode($r->value);
						$container->appendChild($txt);
					} break;

					case XMLReader::CDATA: {
						$cdata =  $doc->createCDATASection($r->value);
						$container->appendChild($cdata);
					} break;

					case XMLReader::NONE:
					default:

				}

				$continue = $r->read();
			}

			$this->offset += $collected;

			if (!$continue) $this->complete = true;

			return $doc;
		}

		public function getRenameFiles() {
			$config = mainConfiguration::getInstance();
			$renameFiles = $config->get("modules", "exchange.commerceML.renameFiles") !== null ? $config->get("modules", "exchange.commerceML.renameFiles") : $this->renameFiles;
			return (bool) $renameFiles;
		}

	}
?>