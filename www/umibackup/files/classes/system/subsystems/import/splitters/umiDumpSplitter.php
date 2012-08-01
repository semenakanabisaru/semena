<?php
	class umiDumpSplitter extends umiImportSplitter {
		protected function readDataBlock() {
			// TODO: split umiDump
			$doc = DomDocument::load($this->file_path);
			$this->offset = 0;
			$this->complete = true;
			return $doc;
		}

		public function translate(DomDocument $doc) {
			// do nothing
			return $doc->saveXML();
		}

	}
?>