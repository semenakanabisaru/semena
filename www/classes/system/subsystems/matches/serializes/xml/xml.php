<?php
	class xmlSerialize extends baseSerialize {
		public function execute($xmlString, $params) {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');
			
			$this->sendHTTPHeaders($params);
			
			$buffer->push($xmlString);
			$buffer->end();
		}
	}
?>