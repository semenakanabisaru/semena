<?php
	class umiDistrFile extends umiDistrInstallItem {
		protected $filePath, $permissions, $content;

		public function __construct($filePath = false) {
			if($filePath !== false) {
				$this->filePath = $filePath;
				$this->permissions = fileperms($filePath) & 0x1FF;
				$this->content = file_get_contents($filePath);
			}
		}

		public function pack() {
			return base64_encode(serialize($this));
		}

		public static function unpack($data) {
			return base64_decode(unserialize($data));
		}

		public function restore() {
			if(is_file($this->filePath)) {
				if(is_writable($this->filePath)) {
					@file_put_contents($this->filePath, $this->content);
				} else {
					return false;
				}
				return true;
			} else {
				$bytes = @file_put_contents($this->filePath, $this->content);
				if(strlen($this->content)) {
					return (bool) $bytes;
				} else {
					return true;
				}
			}
		}
	};
?>