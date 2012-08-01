<?php
	class umiFieldsGroupWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		public function translateProperties(iUmiFieldsGroup $group, iUmiObject $object) {
			$groupId = $group->getId();
			$groupName = $group->getName();
			$groupTitle = $group->getTitle();

			$groupArray = array();
			$groupArray['attribute:id'] = $groupId;
			$groupArray['attribute:name'] = $groupName;
			$groupArray['title'] = $groupTitle;

			$fields = $group->getFields();
			$groupArray['nodes:property'] = Array();

			$i = 0;
			$hasFilledProps = false;
			foreach($fields as $fieldId => $field) {
				$fieldName = $field->getName();
				$property = $object->getPropByName($fieldName);

				if(is_null($property)) continue;

				$propArray = translatorWrapper::get($property)->translate($property);

				if(!empty($propArray)) {
					$hasFilledProps = true;
					$groupArray['nodes:property'][(getRequest('jsonMode') == "force" ? $i++ : ++$i)] = $propArray;
				}
			}

			return ($hasFilledProps) ? $groupArray : array();
		}
		
		protected function translateData(iUmiFieldsGroup $group) {
			$resultArray = array(
				'attribute:id'		=> $group->getId(),
				'attribute:name'	=> $group->getName(),
				'attribute:title'	=> $group->getTitle()
			);

			if($group->getIsVisible()) {
				$resultArray['attribute:visible'] = "visible";
			}

			if($group->getIsLocked()) {
				$resultArray['attribute:locked'] = "locked";
			}

			$resultArray['nodes:field'] = $group->getFields();

			return $resultArray;
		}
	};
?>