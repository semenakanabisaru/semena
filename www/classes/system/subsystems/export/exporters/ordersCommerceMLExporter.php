<?php

	class ordersCommerceMLExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("windows-1251");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($stems) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('need_export')->equals(1);

			$umiDump = $this->getUmiDumpObjects($sel->result, "CommerceML2");

			$style_file = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($style_file)) {
				throw new publicException("Can't load exporter {$style_file}");
			}


			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump);

			$templater = umiTemplater::create('XSLT', $style_file);
			$result = $templater->parse($doc);

			// convert to CP1251
			// TODO: это можно решить xsl-шаблоном, метод output
			$result = str_replace('<?xml version="1.0" encoding="utf-8"?>', '<?xml version="1.0" encoding="windows-1251"?>', $result);
			$result = iconv("UTF-8", "CP1251//IGNORE", $result);
			return $result;
		}

	}
?>