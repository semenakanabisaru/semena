<?php

	class umiMimePart {

		const UMI_MIMEPART_CRLF = "\n";
		const UMI_MIMEPART_CHARSET = 'utf-8';


		private $arrHeaders = array();
		private $sBody = "";
		private $sEncoding = '7bit';
		private $arrSubParts = array();
		private $sEncBody = "";
		private $bEncoded = false;

		public function __construct($sBody, $arrParams) {
			// def header
			$this->arrHeaders['Content-Type'] = 'text/plain; charset='.self::UMI_MIMEPART_CHARSET.'';

			$this->sBody = $sBody;
			foreach ($arrParams as $sParamName => $sParamValue) {
				switch ($sParamName) {
					case "content-type":
							$this->arrHeaders['Content-Type'] = $sParamValue . (isset($arrParams['charset'])? "; charset=" . $arrParams['charset'] . "" : "");
							break;
					case "encoding":
							$this->sEncoding = $sParamValue;
							$this->arrHeaders['Content-Transfer-Encoding'] = $sParamValue;
							break;
					case 'cid':
							$this->arrHeaders['Content-ID'] = "<" . $sParamValue . ">";
							break;
					case 'disposition':
							$this->arrHeaders['Content-Disposition'] = $sParamValue . (isset($arrParams['']) ? '; filename="' . $dfilename . '"' : '');
				}
			}
		}

		private function addSubPart($sBody, $arrParams) {
			$oNewPart = new self($sBody, $arrParams);
			$this->arrSubParts[] = $oNewPart;
			return $oNewPart;
		}

		public function addMixedPart() {
			return $this->addSubPart('', array('content-type' => 'multipart/mixed'));
		}

		public function addAlternativePart() {
			return $this->addSubPart('', array('content-type' => 'multipart/alternative'));
		}

		public function addRelatedPart() {
			return $this->addSubPart('', array('content-type' => 'multipart/related'));
		}

		public function addTextPart($sText) {
			$arrParams = array();
			$arrParams['content-type'] = 'text/plain';
			$arrParams['encoding'] = '7bit';
			$arrParams['charset'] = self::UMI_MIMEPART_CHARSET;
			return $this->addSubPart($sText, $arrParams);
		}

		public function addHtmlPart($sHtml) {
			$arrParams = array();
			$arrParams['content-type'] = 'text/html';
			$arrParams['encoding'] = 'base64';
			$arrParams['charset'] = self::UMI_MIMEPART_CHARSET;
			return $this->addSubPart($sHtml, $arrParams);
		}

		public function addHtmlImagePart($arrImgData) {
			$arrParams = array();
			$arrParams['content-type'] = (isset($arrImgData['content-type'])? $arrImgData['content-type'] : "image/jpeg"). '; ' . 'name="' . (isset($arrImgData['name'])? $arrImgData['name']: "undefined.jpg" ). '"';
			$arrParams['encoding'] = 'base64';
			$arrParams['disposition'] = 'inline';
			$arrParams['dfilename'] = isset($arrImgData['name'])? $arrImgData['name']: "undefined.jpg";
			$arrParams['cid'] = isset($arrImgData['cid'])? $arrImgData['cid'] : "";
			return $this->addSubPart(isset($arrImgData['data'])? $arrImgData['data'] : "", $arrParams);
		}

		public function addAttachmentPart($arrAttachmentData) {
			$arrParams = array();
			$arrParams['dfilename'] = isset($arrAttachmentData['name'])? $arrAttachmentData['name']: "undefined.jpg";
			$arrParams['encoding'] = isset($arrAttachmentData['encoding'])? $arrAttachmentData['encoding']: "base64";
			$arrParams['content-type'] = (isset($arrAttachmentData['content-type'])? $arrAttachmentData['content-type'] : "image/jpeg"). '; ' . 'name="' . (isset($arrAttachmentData['name'])? $arrAttachmentData['name']: "undefined.jpg" ). '"';
			
			$arrParams['disposition'] = 'attachment';
			return $this->addSubPart(isset($arrAttachmentData['data'])? $arrAttachmentData['data'] : "", $arrParams);
		}

		public static function quotedPrintableEncode($sData , $iMaxLineSize = 76) {
			$sResult = '';

			$arrLines  = preg_split("/\r?\n/", $sData);
			$sEscape = '=';

			while(@list(, $sLine) = each($arrLines)){

				$arrLine = preg_split('||', $sLine, -1, PREG_SPLIT_NO_EMPTY);

				$sEncLine = '';

				for ($iI = 0; $iI < count($arrLine); $iI++) {
					$sChar = $arrLine[$iI];
					$iDec  = ord($sChar);

					if (($iDec == 32) AND ($iI == (count($arrLine) - 1))) {
						$sChar = '=20';

					} elseif(($iDec == 9) AND ($iI == (count($arrLine) - 1))) {
						$sChar = '=09';
					} elseif($iDec == 9) {
						;
					} elseif(($iDec == 61) OR ($iDec < 32 ) OR ($iDec > 126)) {
						$sChar = $sEscape . strtoupper(sprintf('%02s', dechex($iDec)));
					} elseif (($iDec == 46) AND ($sEncLine == '')) { 
						//Bug #9722: convert full-stop at bol
						//Some Windows servers need this, won't break anything (cipri)
						$sChar = '=2E';
					}

					if ((strlen($sEncLine) + strlen($sChar)) >= $iMaxLineSize) {
						$sResult  .= $sEncLine . $sEscape . self::UMI_MIMEPART_CRLF;
						$sEncLine  = '';
					}
					$sEncLine .= $sChar;
				}
				$sResult .= $sEncLine . self::UMI_MIMEPART_CRLF;
			}
			$sResult = substr($sResult, 0, -1 * strlen(self::UMI_MIMEPART_CRLF)); // Don't want last crlf

			return $sResult;
		}

		private static function encodeData($sData, $sEncoding = '7bit') {
			$sResult = "";

			switch ($sEncoding) {
				case 'quoted-printable':
						$sResult = self::quotedPrintableEncode($sData , 76);
						break;
				case 'base64':
						$sResult = rtrim(chunk_split(base64_encode($sData), 76, self::UMI_MIMEPART_CRLF));
						break;
				//case '8bit':
				//case '7bit':
				default:
					$sResult = $sData;
					break;
			}
			
			return $sResult;
		}

		public function encodePart() {

			if (count($this->arrSubParts)) {
				srand((double)microtime()*1000000);
				$sBoundary = '=_' . md5(rand() . microtime());

				$this->arrHeaders['Content-Type'] .= ';' . self::UMI_MIMEPART_CRLF . "\t" . 'boundary="' . $sBoundary . '"';
				$arrSubParts = array();
				for ($iI = 0; $iI < count($this->arrSubParts); $iI++) {
					//
					$arrHdrs = array();
					$oNextPart = $this->arrSubParts[$iI];
					if ($oNextPart instanceof self) {
						$arrNextPart = $oNextPart->encodePart();
						foreach ($arrNextPart['headers'] as $sName => $sValue) {
							$arrHdrs[] = $sName . ": " . $sValue;
						}
						$arrSubParts[] = implode(self::UMI_MIMEPART_CRLF, $arrHdrs) . self::UMI_MIMEPART_CRLF . self::UMI_MIMEPART_CRLF . $arrNextPart['body'];
					}
				}

				$this->sEncBody = '--' . $sBoundary . self::UMI_MIMEPART_CRLF . implode('--' . $sBoundary . self::UMI_MIMEPART_CRLF, $arrSubParts) . '--' . $sBoundary. '--' . self::UMI_MIMEPART_CRLF . self::UMI_MIMEPART_CRLF;
			} else {
				$this->sEncBody = self::encodeData($this->sBody, $this->sEncoding) . self::UMI_MIMEPART_CRLF;
			}
			return array('body'=>$this->sEncBody, 'headers'=>$this->arrHeaders);
		}

		public function __toString() {
			return $this->encodePart();
		}


	}


?>