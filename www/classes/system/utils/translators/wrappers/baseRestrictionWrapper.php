<?php
	class baseRestrictionWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(baseRestriction $restriction) {
			return array(
				'attribute:id'				=> $restriction->getId(),
				'attribute:name'			=> $restriction->getClassName(),
				'attribute:field-type-id'	=> $restriction->getFieldTypeId(),
				'node:title'				=> $restriction->getTitle()
			);
		}
	};
?>