<?php
	class umiDateWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiDate $date) {
			return array(
				'attribute:unix-timestamp'	=> $date->getFormattedDate('U'),
				'attribute:rfc'				=> $date->getFormattedDate('r'),
				'node:std'					=> $date->getFormattedDate()
			);
		}
	};
?>