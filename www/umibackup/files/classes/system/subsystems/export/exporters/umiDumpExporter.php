<?php

	class umiDumpExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($stems) {
			$sel = new selector('pages');
			if (is_array($stems) && count($stems)) {
				foreach ($stems as $stem) $sel->where('hierarchy')->page($stem->getId())->childs(100);
			} else {
				$sel->where('hierarchy')->page(0)->childs(100);
			}
			$elements = array_merge($sel->result, $stems);

			$umiDump = $this->getUmiDump($elements);

			
			return $umiDump;
		}

	}
?>