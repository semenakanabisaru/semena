<?php
	interface iQuickCsvExporter {
		public function __construct(selector $sel);
		public function setResultsMode($resultsMode);
		public function exportToFile($filePath);

		public static function autoExport(selector $sel, $forceHierarchy = false);
	};


	class quickCsvExporter implements iQuickCsvExporter {
		protected
			$sel,
			$filepath,
			$fileHandler,
			$resultsMode = "element",
			$fields = array(),
			$foundFields = array(),
			$objectTypes = array();

		const columnSeparator = ";";

		public function __construct(selector $sel) {
			$innerSel = $sel;

			$innerSel->limit(0, 1000000);	//4 realloc: один миллион записей максимум
			$this->sel = $innerSel;
		}

		public function setResultsMode($resultsMode) {
			if(in_array($resultsMode, Array('object', 'element'))) {
				$this->resultsMode = $resultsMode;
				return true;
			} else {
				return false;
			}
		}

		public function exportToFile($filepath) {
			if($this->checkFilePath($filepath)) {
				touch($filepath);
				$this->filepath = realpath($filepath);
			} else {
				throw new coreException("Can't access store file \"{$filepath}\"");
			}
			$selectionResults = $this->getSelectionResults();

			$this->exportResults($selectionResults);
			return new umiFile($filepath);
		}

		public function setObjectTypeId($objectTypeId) {
			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			if($objectType instanceof umiObjectType) {
				$this->objectTypes[] = $objectType;
			} else {
				throw new coreException("Object type #{$objectTypeId} doesn't exists");
			}
		}

		public function setUsedFields($fields) {
			if(is_array($fields)) {
				$this->fields = $fields;
				return true;
			} else {
				return false;
			}
		}

		public static function autoExport(selector $sel, $forceHierarchy = false) {
			$csvExporter = new quickCsvExporter($sel);
			$objectTypes = umiObjectTypesCollection::getInstance();

			foreach($sel->types as $type) {
				if(is_null($type->objectType) == false) {
					$objectTypeId = $type->objectType->getId();
					$csvExporter->setObjectTypeId($objectTypeId);
				}

				if(is_null($type->hierarchyType) == false) {
					$hierarchyTypeId = $type->hierarchyType->getId();
					$objectTypeId = $objectTypes->getTypeByHierarchyTypeId($hierarchyTypeId);
					$csvExporter->setObjectTypeId($objectTypeId);
				}
			}

			if($sel->mode == "pages" || $sel->hierarchy || $forceHierarchy) {
				$csvExporter->setResultsMode("element");
			} else {
				$csvExporter->setResultsMode("object");
			}

			$moduleName = cmsController::getInstance()->getCurrentModule();
			$config = mainConfiguration::getInstance();
			$filename = ($moduleName) ? $moduleName . "-" . date("Y-m-d_H.i.s") : "csv-export-" . uniqid();
			$exportFilePath = $config->includeParam('system.runtime-cache') . $filename . ".csv";

			$csvExporter->setUsedFields(getRequest('used-fields'));
			$csvExporter->exportToFile($exportFilePath);

			$file = new umiFile($exportFilePath);
			$file->download(true);
		}

		protected function checkFilePath($filepath) {
			if(is_file($filepath)) {
				return is_writable($filepath);
			} else {
				$dirname = dirname($filepath);
				if(is_dir($dirname)) {
					return is_writable($dirname);
				} else {
					return false;
				}
			}
		}

		protected function getSelectionResults() {
			return $this->sel->result;
		}

		protected function exportResults($results) {
			$data = array();
			if($this->resultsMode == "object") {
				$objects = umiObjectsCollection::getInstance();
				foreach($results as $object) {
					$data[] = $this->storeObjectData($object);
					$objects->unloadObject($object->id);
				}
			}

			if($this->resultsMode == "element") {
				$hierarchy = umiHierarchy::getInstance();
				foreach($results as $element) {
					$data[] = $this->storeElementData($element);
					$hierarchy->unloadElement($element->id);
				}
			}

			$this->openFile();
			$this->writeHeader();
			foreach($data as $row) {
				foreach($row as $fieldName => $column) {
					if(!is_numeric($fieldName) && !in_array($fieldName, $this->foundFields)) {
						unset($row[$fieldName]);
					}
				}
				$this->writeFileLine($row);
			}
			$this->closeFile();
		}

		protected function openFile() {
			$this->fileHandler = fopen($this->filepath, "w");
		}

		protected function writeHeader() {
			$data = Array(
				Array('string', 'Id'),
				Array('string', getLabel('label-name'))
			);

			$types = $this->objectTypes;
			$fieldsCollection = umiFieldsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			$typesList = Array();
			foreach($types as $objectType) {
				$objectTypeId = $objectType->getId();
				$typesList += $objectTypes->getChildClasses($objectTypeId);
				$typesList[] = $objectTypeId;
			}
			$fieldTitles = Array();
			foreach($this->fields as $fieldName) {
				if(!in_array($fieldName, $this->foundFields)) continue;

				$fieldTitle = $fieldName;
				foreach($typesList as $objectTypeId) {
					$type = $objectTypes->getType($objectTypeId);
					if($type instanceof iUmiObjectType) {
						if($fieldId = $type->getFieldId($fieldName)) {
							$field = $fieldsCollection->getField($fieldId);
							if($field instanceof iUmiField) {
								$fieldTitle = $field->getTitle();
								break;
							}
						}
					}
				}
				$fieldTitles[] = $fieldTitle;
			}

			if($this->resultsMode == "element") {
				$data[] = array('string', getLabel('label-alt-name'));
				$data[] = array('string', getLabel('field-is_active'));
			}

			foreach($fieldTitles as $fieldTitle) {
				$data[] = array('string', $fieldTitle);
			}

			$this->writeFileLine($data);
		}

		protected function writeFileLine($data) {
			$str = "";

			foreach($data as $fieldName => $valueInfo) {
				if(is_array($valueInfo)) {
					$str .= $this->prepareCsvColumn($valueInfo[0], $valueInfo[1]);
				}
				$str .= self::columnSeparator;
			}
			$str .= "\n";

			fwrite($this->fileHandler, $str);
		}

		protected function prepareCsvColumn($dataType, $value) {
			switch($dataType) {
				case "relation": {
					$value = $this->getRelationValue($value);
					break;
				}

				case "tags": {
					$value = implode(", ", $value);
					break;
				}

				case "date": {
					if($value instanceof umiDate) {
						$value = $value->getFormattedDate("Y-m-d H:i");
					}
					break;
				}
			}

			$from = Array('\n', '"');
			$to = Array('\\n', '""');
			$value = str_replace($from, $to, $value);
			if(!is_numeric($value)) {
				$value = iconv("UTF-8", "CP1251//IGNORE", $value);
				$value = '"' . $value . '"';
			}
			return $value;
		}

		protected function closeFile() {
			fclose($this->fileHandler);
		}

		protected function getObject($itemId) {
			$objects = umiObjectsCollection::getInstance();

			switch($this->resultsMode) {
				case "object": {
					return $objects->getObject($itemId);
					break;
				}

				case "element": {
					$hierarchy =  umiHierarchy::getInstance();
					$element = $hierarchy->getElement($itemId);
					if($element instanceof umiHierarchyElement) {
						return $element;
					} else {
						return false;
					}
					break;
				}

				default: {
					throw new coreException("Unknown results type \"{$this->resultsMode}\"");
				}
			}
		}

		protected function storeObjectData(umiObject $object) {
			$data = array(
				array("int", $object->getId()),
				array("string", $object->getName())
			);

			foreach($this->fields as $fieldName) {
				$prop = $object->getPropByName($fieldName);
				if($prop instanceof umiObjectProperty) {
					$dataType = $prop->getDataType();
					$value = $object->getValue($fieldName);
					$data[$fieldName] = array($dataType, $value);

					if(!in_array($fieldName, $this->foundFields)) {
						$this->foundFields[] = $fieldName;
					}
				} else {
					$data[$fieldName] = NULL;
				}
			}

			return $data;
		}

		protected function storeElementData(umiHierarchyElement $element) {
			$data = array(
				array("int", $element->getId()),
				array("string", $element->getName()),
				array("string", $element->getAltName()),
				array("int", $element->getIsActive())
			);

			$object = $element->getObject();
			foreach($this->fields as $fieldName) {
				$prop = $object->getPropByName($fieldName);
				if($prop instanceof umiObjectProperty) {
					$dataType = $prop->getDataType();
					$value = $object->getValue($fieldName);
					$data[$fieldName] = Array($dataType, $value);

					if(!in_array($fieldName, $this->foundFields)) {
						$this->foundFields[] = $fieldName;
					}
				} else {
					$data[$fieldName] = NULL;

				}
			}

			return $data;
		}

		protected function getRelationValue($value) {
			$objects = umiObjectsCollection::getInstance();
			if(is_array($value)) {
				$tmp = Array();
				foreach($value as $objectId) {
					$object = $objects->getObject($objectId);
					if($object instanceof umiObject) {
						$tmp[] = $object->getName();
					}
				}
				return implode(", ", $tmp);
			} else if(is_numeric($value)) {
				$object = $objects->getObject($value);
				if($object instanceof umiObject) {
					return $object->getName();
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	};
?>