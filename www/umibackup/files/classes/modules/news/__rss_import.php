<?php
	abstract class __rss_import_news {

		public function rss_list() {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();

			$type_id = $typesCollection->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$result = $objectsCollection->getGuidedItems($type_id);

			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$params = Array(
					"type_id" => $type_id
				);
				$this->saveEditedList("objects", $params);

				try {
					$this->import_feeds();
				}catch(publicException $e) {}

				$this->chooseRedirect();
			}

//			$type_id = 21;

			$result = array_keys($result);
			$total = sizeof($result);

			$this->setDataType("list");
			$this->setActionType("modify");
			$this->setDataRange($total, 0);

			$data = $this->prepareData($result, "objects");
			$this->setData($data, $total);
			return $this->doData();
		}



		public function import_feeds() {
			$type_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$result = umiObjectsCollection::getInstance()->getGuidedItems($type_id);

			foreach($result as $id => $name) {
				$object = umiObjectsCollection::getInstance()->getObject($id);
				$url = $object->getValue("url");
				$type = $object->getValue("rss_type");
				$target = $object->getValue("news_rubric");

				$this->import_feed($url, $type, $target, $name);
			}
		}


		public function import_feed($url, $type_id, $target, $source = false) {
			$typeObj = umiObjectsCollection::getInstance()->getObject($type_id);
			$typeName = $typeObj->getName();

			$feed = new RSSFeed($url);

			$feed->loadContent();

			switch($typeName) {
				case "RSS": {
					$feed->loadRSS();
					break;
				}

				case "ATOM": {
					$feed->loadAtom();
					break;
				}

				default: {
					return false;
				}
			}

			$relations = umiImportRelations::getInstance();

			$source_id = $relations->getSourceId($url);
			if($source_id === false) {
				$source_id = $relations->addNewSource($url);
			}

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName("news", "item");
			$hierarchy_type_id = $hierarchy_type->getId();

			$result = $feed->returnItems();

			foreach($result as $item) {
				$item_title = $item->getTitle();
				$item_url = $item->getUrl();

				if($relations->getNewIdRelation($source_id, $item_url)) {
					continue;
				}

				$item_content = $item->getContent();
				$item_date = $item->getDate();
				$item_date = strtotime($item_date);
				if(!isset($element_id) || $element_id === false) {
					if(!isset($target[0])) {
						continue;
					}
					$parents = umiHierarchy::getInstance()->getObjectInstances($target[0]);
					if(count($parents)) {
						list($parent_id) = $parents;
						$element_id = umiHierarchy::getInstance()->addElement($parent_id, $hierarchy_type_id, $item_title, $item_title);
						$relations->setIdRelation($source_id, $item_url, $element_id);

						permissionsCollection::getInstance()->setDefaultPermissions($element_id);
					} else {
						return false;
					}
				}

				if ($element = umiHierarchy::getInstance()->getElement($element_id, true)) {
					$element->getObject()->setName($item_title);
					$element->setAltName($item_title);
					$element->setIsActive(true);
					$element->setValue("title", $item_title);
					$element->setValue("h1", $item_title);
					$element->setValue("publish_time", $item_date);
					$element->setValue("anons", $item_content);
					$element->setValue("content", $item_content);
					$element->setValue("source", $source);
					$element->setValue("source_url", $item_url);
					$element->commit();
					$element_id = false;
				}
			}

			return true;
		}


		public function feedsImportListener($event) {
			$counter = &$event->getRef("counter");
			$buffer = &$event->getRef("buffer");
			$counter++;

			$buffer[ __METHOD__] = $this->import_feeds();
		}
	};
?>
