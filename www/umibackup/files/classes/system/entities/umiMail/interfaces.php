<?php
	interface iUmiMail {
		public function __construct($template = "default");
		public function __destruct();

		public function addRecipient($recipientEmail, $recipientName = false);
		public function setFrom($fromEmail, $fromName = false);

		public function setContent($contentString);
		public function setTxtContent($sTxtContent);
		public function setSubject($subjectString);
		public function setPriorityLevel($priorityLevel = "normal");
		public function setImportanceLevel($importanceLevel = "normal");

		public function getHeaders($arrXHeaders = array(), $bOverwrite = false);

		public function attachFile(umiFile $file);

		public function commit();
		public function send();

		public static function clearFilesCache();
		public static function checkEmail($emailString);
	};

	interface iUmiMimePart {
		public function __construct($sBody, $arrParams);

		public static function quotedPrintableEncode($sData , $iMaxLineSize = 76);

		public function addMixedPart();
		public function addAlternativePart();
		public function addRelatedPart();
		public function addTextPart($sText);
		public function addHtmlPart($sHtml);
		public function addHtmlImagePart($arrImgData);
		public function addAttachmentPart($arrAttachmentData);

		public function encodePart();
	};
?>