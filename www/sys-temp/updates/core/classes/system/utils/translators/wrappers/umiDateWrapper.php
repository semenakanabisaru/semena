<?php
	class umiDateWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}

		protected function translateData(iUmiDate $date) {
			return array(
				'attribute:unix-timestamp'	=> $date->getFormattedDate('U'),
				'attribute:rfc'				=> $date->getFormattedDate('r'),
				'attribute:formatted-date' 	=> $date->getFormattedDate("d.m.Y H:i"),
				'node:std'					=> $date->getFormattedDate()
			);
		}
	};
?>
