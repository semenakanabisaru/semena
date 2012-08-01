<?php
	class umiFieldWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		public function translateData(iUmiField $field) {
			$resultArray = array(
				'attribute:id'				=> $field->getId(),
				'attribute:name'			=> $field->getName(),
				'attribute:title'			=> $field->getTitle(),
				'attribute:field-type-id'	=> $field->getFieldTypeId()
			);
			

			if($field->getIsVisible()) {
				$resultArray['attribute:visible'] = "visible";
			}

			if($field->getIsInheritable()) {
				$resultArray['attribute:inheritable'] = "inheritable";
			}

			if($field->getIsLocked()) {
				$resultArray['attribute:locked'] = "locked";
			}

			if($field->getIsInFilter()) {
				$resultArray['attribute:filterable'] = "filterable";
			}

			if($field->getIsInSearch()) {
				$resultArray['attribute:indexable'] = "indexable";
			}

			if($guide_id = $field->getGuideId()) {
				$resultArray['attribute:guide-id'] = $guide_id;
			}

			if($tip = $field->getTip()) {
				$resultArray['tip'] = $tip;
			}

			if($field->getIsRequired()) {
				$resultArray['attribute:required'] = "required";
			}

			if($restrictionId = $field->getRestrictionId()) {
				$resultArray['restriction'] = baseRestriction::get($restrictionId);
			}
			
			$resultArray['type'] = $field->getFieldType();

			return $resultArray;
		}
	};
?>