<?php
	class htmlSerialize extends baseSerialize {
		const signature = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

		public function execute($xmlString, $params) {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			
			$this->sendHTTPHeaders($params);
			
			$buffer->push($this->removeSignature($xmlString));
			$buffer->end();
		}
		
		private function removeSignature($str) {
			$l = strlen(self::signature);

			if(substr($str, 0, $l) === self::signature) {
				return substr($str, $l, strlen($str) - $l);
			} else {
				return $str;
			}
		}
	}
?>