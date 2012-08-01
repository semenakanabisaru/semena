<?php
	interface iUmiOpenSSL {

		public function setCertificateFilePath($keyFilePath);
		public function getCertificateFilePath();
		
		public function setPrivateKeyFilePath($keyFilePath);
		public function getPrivateKeyFilePath();
		
		public function setPublicKeyFilePath($keyFilePath);
		public function getPublicKeyFilePath();
		
		public function loadSettingsFromRegedit();
		
		public function createPrivateKeyFile($keyFilePath);
		public function createCertificateFile($keyFilePath);

		public function createPublicKeyFile($keyFilePath);
		
		public function encrypt($data, $out = true);
		public function decrypt($enc, $out = true);

		public function supplyDefaultKeyFiles();
	};
?>