<?php
	class umiDump20Splitter extends umiImportSplitter {


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

		protected function readDataBlock() {
			$r = new XMLReader;
			$r->open($this->file_path/*, "utf-8",  LIBXML_COMPACT ^ LIBXML_NOEMPTYTAG*/);


			// set scheme, if exists
			$config = mainConfiguration::getInstance();
			$scheme_file = $config->includeParam('system.kernel') . 'subsystems/import/schemes/' . $this->type . '.xsd';
			if (is_file($scheme_file)) {
				$r->setSchema($scheme_file);
			}

			$doc = new DomDocument("1.0", "utf-8");

			$entities = array(
				'umidump/registry/key',
				'umidump/files/file',
				'umidump/directories/directory',
				'umidump/langs/lang',
				'umidump/domains/domain',
				'umidump/templates/template',
				'umidump/datatypes/datatype',
				'umidump/types/type',
				'umidump/pages/page',
				'umidump/objects/object',
				'umidump/relations/relation',
				'umidump/options/entity',
				'umidump/restrictions/restriction',
				'umidump/permissions/permission',
				'umidump/hierarchy/relation'
			);

			$collected = 0;
			$position = 0;
			$container = $doc;
			$continue = $r->read();


			while ($continue && $collected <= $this->block_size) {
				switch ($r->nodeType) {
					case XMLReader::ELEMENT: {
						$node_path = $this->__getNodePath($container);
						if (in_array($node_path . "/" . $r->name, $entities)) {
							if ($position++ < $this->offset) {
								$continue = $r->next(); continue(2);
							}
							if (($collected + 1) > $this->block_size) break(2);
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

		public function translate(DomDocument $doc) {
			// do nothing
			return $doc->saveXML();
		}

	}
?>