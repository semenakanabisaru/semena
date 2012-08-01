<?php

	class catalogCommerceMLExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($branches) {
			if (!count($branches)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->childs(0);
				$branches = $sel->result();
			}

			$umiDump = $this->getUmiDump($branches, "commerceML2");

			$style_file = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($style_file)) {
				throw new publicException("Can't load exporter {$style_file}");
			}


			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump);

			$templater = umiTemplater::create('XSLT', $style_file);
			return $templater->parse($doc);
		}

	}
?>