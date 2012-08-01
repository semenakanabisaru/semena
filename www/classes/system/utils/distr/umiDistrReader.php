<?php
	class umiDistrReader {
		protected $distrFilePath, $fh;

		public		$signature = "ucp", $author, $comment, $timestamp, $totalSize;
		protected	$version = "1.0.0";


		public function __construct($distrFilePath) {
			if(!is_file($distrFilePath)) {
				trigger_error("Distributive file \"{$distrFilePath}\" doesn't exists", E_USER_ERROR);
			}

			$this->distrFilePath = $distrFilePath;

			$this->readHeader();
			
			while($obj = $this->getNextResource()) {
				$obj->restore();
				unset($obj);
			}
			fclose($this->fh);
		}


		public function __destruct() {
			if(is_resource($this->fh)) {
				trigger_error("Resource \"{$this->fh}\" is not closed.", E_USER_NOTICE);
				fclose($this->fh);
			}
		}


		protected function readHeader() {
			if(!is_readable($this->distrFilePath)) {
				trigger_error("Distributive file \"{$this->distrFilePath}\" is not readable", E_USER_ERROR);
			}

			$this->fh = $f = fopen($this->distrFilePath, "r");

			fseek($f, 0);
			if(stream_get_line($f, 5, "\0") != $this->signature) {
				trigger_error("Distributive file corrupted: wrong signature", E_USER_ERROR);
				return false;
			}

			fseek($f, 5);
			if(version_compare($needle_version = stream_get_line($f, 5, "\0"), $this->version, "<=") != 1) {
				trigger_error("You need installer at least version {$needle_version} to read this distribute file", E_USER_ERROR);
				return false;
			}

			fseek($f, 10);
			$this->timestamp = (int) stream_get_line($f, 15, "\0");

			fseek($f, 25);
			$this->totalSize = (int) stream_get_line($f, 25, "\0");

			fseek($f, 50);
			$this->author = (string) stream_get_line($f, 25, "\0");

			fseek($f, 75);
			$this->comment = (string) stream_get_line($f, 330, "\0");

			fseek($f, 331);
		}


		public function getNextResource() {
			$f = $this->fh;

			$p = ftell($f);

			$blockSize = (int) stream_get_line($f, 25, "\0");

			fseek($f, $p + 25);
			$blockData = (string) stream_get_line($f, $blockSize);

			if(strlen($blockData) == $blockSize) {
				$obj = unserialize(base64_decode($blockData));
				return $obj;
			} else {
				return false;
			}
		}
	};
?>