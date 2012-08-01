<?php
	class fileSerialize extends baseSerialize {
		public function execute($xmlString, $params) {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');
			
			$this->sendHTTPHeaders($params);
			
			$buffer->push($xmlString);
			
			$filepath = getArrayKey($params, 'output');
			if($filepath) {
				file_put_contents($filepath, $xmlString);
			} else {
				//TODO: Maybe, throw exception, or other kind of notice for developer?
			}
			$buffer->end();
		}
	}
?>