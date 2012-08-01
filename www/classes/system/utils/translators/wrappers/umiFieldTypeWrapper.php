<?php
	class umiFieldTypeWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiFieldType $fieldType) {
			$resultArray = array(
				'attribute:id'			=> $fieldType->getId(),
				'attribute:name'		=> $fieldType->getName(),
				'attribute:data-type'	=> $fieldType->getDataType()
			);

			if($fieldType->getIsMultiple()) {
				$resultArray['attribute:multiple'] = "multiple";
			}

			return $resultArray;
		}
	};
?>