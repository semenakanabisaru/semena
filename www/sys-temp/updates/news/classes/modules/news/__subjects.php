<?php
	abstract class __subjects_news extends baseModuleAdmin {
		public function subjects() {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();

			$type_id = $objectTypesCollection->getBaseType("news", "subject");


			$mode = (String) getRequest('param0');

			if($mode == "do") {
				$params = Array(
					"type_id" => $type_id
				);

				$this->saveEditedList("objects", $params);
				$this->chooseRedirect();
			}


			$per_page = 25;
			$curr_page = getRequest('p');

			$subjects_guide = $objectsCollection->getGuidedItems($type_id);

			$subjects = array_keys($subjects_guide);
			$total = sizeof($subjects);

			$this->setDataType("list");
			$this->setActionType("modify");
			$this->setDataRange($per_page, $curr_page * $per_page);

			$data = $this->prepareData($subjects, "objects");

			$this->setData($data, $total);
			return $this->doData();
		}
	};
?>