<?php

	class umiDump20Exporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($branches) {
			set_time_limit(0);
			if (!count($branches)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->childs(0);
				$branches = $sel->result;
			}

			$temp_dir = CURRENT_WORKING_DIR . "/sys-temp/export/";
			$id = getRequest('param0');
			$file_path = $temp_dir . $id . "." . parent::getFileExt();

			if(getRequest('as_file') === '0') {
				$exporter = new xmlExporter($this->getSourceName());
				$exporter->addBranches($branches);
				$result = $exporter->execute();
				return $result->saveXML();
			}

			if(file_exists($file_path) && !file_exists(CURRENT_WORKING_DIR . '/sys-temp/runtime-cache/' . md5($this->getSourceName()))) unlink($file_path);

			$new_file_path = $file_path . '.tmp';

			$blockSize = (int) mainConfiguration::getInstance()->get("modules", "exchange.export.limit");
			if($blockSize <= 0) $blockSize = 25;

			$exporter = new xmlExporter($this->getSourceName(), $blockSize);
			$exporter->addBranches($branches);
			$dom = $exporter->execute();

			if(file_exists($file_path)) {
				$reader = new XMLReader;
				$writer = new XMLWriter;

				$reader->open($file_path/*, "utf-8",  LIBXML_COMPACT ^ LIBXML_NOEMPTYTAG*/);
				$writer->openURI($new_file_path);
				$writer->startDocument('1.0', 'utf-8');

				// start root node
				$writer->startElement('umidump');
				$writer->writeAttribute('version', '2.0');
				$writer->writeAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

				$continue = $reader->read();
				while($continue){
					if ($reader->nodeType == XMLReader::ELEMENT) {
						$node_name = $reader->name;
						if($node_name != 'umidump') {
							$writer->startElement($node_name);

							if ($node_name != 'meta'){
								if (!$reader->isEmptyElement) {
									$child_continue = $reader->read();
									while($child_continue) {
										if ($reader->nodeType == XMLReader::ELEMENT) {
											$child_node_name = $reader->name;
											$writer->writeRaw($reader->readOuterXML());
											$child_continue = $reader->next();
										} elseif ($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == $node_name) {
											$child_continue = false;
										} else {
											$child_continue = $reader->next();
										}
									}
								}

								if($dom->getElementsByTagName($node_name)->item(0)->hasChildNodes()) {
									$children = $dom->getElementsByTagName($node_name)->item(0)->childNodes;
									foreach ($children as $child) {
										$newdoc = new DOMDocument;
										$newdoc->formatOutput = true;
										$node = $newdoc->importNode($child, true);
										$newdoc->appendChild($node);
										$writer->writeRaw($newdoc->saveXML($node, LIBXML_NOXMLDECL));
									}
								}
							} elseif($node_name == 'meta') {
								$writer->writeRaw($reader->readInnerXML());
								$branches = $dom->getElementsByTagName('branches');
								if ($branches->item(0)) {
									$writer->writeRaw($dom->saveXML($branches->item(0), LIBXML_NOXMLDECL));
								}
							}

							$writer->fullEndElement();
							$continue = $reader->next();
							continue;
						}

					}
					$continue = $reader->read();
				}

				// finish root node
				$writer->fullEndElement();

				$reader->close();
				$writer->endDocument();
				$writer->flush();
				unlink($file_path);
				rename($new_file_path, $file_path);

			} else {
				file_put_contents($file_path, $dom->saveXML());
			}

			$this->completed = $exporter->isCompleted();
			return false;
		}
	}
?>