<?php
	class ymlSplitter extends umiImportSplitter {
		protected function prepareData() {
			$r = $this->reader;
			$doc = new DomDocument;
			$doc_root = $doc->createElement("yml_catalog");
			$doc->appendChild($doc_root);
			$root = $doc->createElement("shop");
			$doc_root->appendChild($root);

			$offset = $this->offset;

			while($r->read() && $r->name != 'categories') {
				if ($r->nodeType != XMLReader::ELEMENT) continue;
				if ($r->name == 'yml_catalog' || $r->name == 'shop') continue;
				$element = $r->expand();
				$root->appendChild($element);
			}

			$position = 0;
			// set data offset
			while($r->read() && $position < $offset) {
				if ($r->nodeType != XMLReader::ELEMENT) continue;
				if ($r->name == 'category' || $r->name == 'offer') $position++;
			}
			$this->offset += $position;

			$this->doc = $doc;
		}

		protected function readDataBlock() {
			$r = $this->reader;
			$doc = clone $this->doc;

			$bz = $this->block_size;

			$nl_doc_container = $doc->getElementsByTagName("shop");
			if (!$nl_doc_container->length) {
				throw new coreException("Data container not found in default document.");
			}

			$doc_container = $nl_doc_container->item(0);

			$categories = $doc->createElement("categories");
			$offers = $doc->createElement("offers");
			
			$position = 0;
			while($r->read() && $position < $bz) {
				if ($r->nodeType != XMLReader::ELEMENT) continue;
				if ($r->name == 'category' || $r->name == 'offer') $position++;

				if ($r->name == 'category') {
					$categories->appendChild($r->expand());
				}
				if ($r->name == 'offer') {
					$offers->appendChild($r->expand());
				}
			}

			$this->offset += $position;

			if (!$categories->hasChildNodes() && !$offers->hasChildNodes()) {
				return false;
			}

			$doc_container->appendChild($categories);
			$doc_container->appendChild($offers);

			return $doc;
		}
	}
?>