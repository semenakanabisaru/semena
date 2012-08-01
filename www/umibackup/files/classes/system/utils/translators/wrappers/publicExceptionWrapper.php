<?php
	class publicExceptionWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(publicException $exception) {
			return array(
				'error' => array(
					'node:msg' => $exception->getMessage()
				)
			);
		}
	};
?>