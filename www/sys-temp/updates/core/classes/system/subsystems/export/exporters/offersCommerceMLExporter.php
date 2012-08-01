<?php

	class offersCommerceMLExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($branches) {
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('catalog', 'object');

			if (count($branches)) {
				foreach ($branches as $branch) {
					$sel->where('hierarchy')->page($branch->id)->childs(1000);
				}
			}


			$exporter = new xmlExporter("CommerceML2");
			$exporter->addElements($sel->result());
			$exporter->setIgnoreRelations();
			$umiDump = $exporter->execute();

			$style_file = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($style_file)) {
				throw new publicException("Can't load exporter {$style_file}");
			}


			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump->saveXML());

			$templater = umiTemplater::create('XSLT', $style_file);
			return $templater->parse($doc);
		}

	}
?>