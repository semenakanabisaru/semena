<?php
	interface iQuickCsvImporter {
		public function __construct(umiFile $csvFile);
		public function importAsElements($hierarchyTypeId = false, $parentElementId = false);
		public function importAsObjects($objectTypeId = false);

		public static function autoImport(selector $sel, $forceHierarchy = false);
	};

	class quickCsvImporter implements iQuickCsvImporter {
		public $allowNewItemsCreation = true, $forceHierarchy = false, $errors = array();
		protected $csvFile, $fileHandler, $mode = "object", $fields = false;
		protected $forceObjectCreation = false;

		public function __construct(umiFile $csvFile) {
			if($csvFile->getIsBroken()) {
				throw new coreException("CSV file doesn't exists: \"" . $csvFile->getFilePath() . "\"");
			}

			$this->forceObjectCreation = umiObjectProperty::$USE_FORCE_OBJECTS_CREATION;
			if (getRequest('ignore-id')) umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = true;

			$this->csvFile = $csvFile;
			$this->openFile();
		}

		public function __destruct() {
			umiObjectProperty::$USE_FORCE_OBJECTS_CREATION = $this->forceObjectCreation;
			$this->closeFile();
		}

		public function importAsElements($hierarchyTypeId = false, $parentElementId = false) {
			$this->mode = "element";

			$objectTypeId = false;
			$objectTypes = umiObjectTypesCollection::getInstance();
			if($parentElementId) {
				$objectTypeId = umiHierarchy::getInstance()->getDominantTypeId($parentElementId);

			}
			if(!$objectTypeId) {
				$objectTypeId = $objectTypes->getTypeByHierarchyTypeId($hierarchyTypeId);
			}

			$objectType = $objectTypes->getType($objectTypeId);
			if(!$hierarchyTypeId) {
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
			}
			$this->importElements($objectType, $hierarchyTypeId, $parentElementId);

		}

		public function importAsObjects($objectType = false) {
			$this->importObjects($objectType);
		}

		public static function autoImport(selector $sel, $forceHierarchy = false) {
			$buffer = outputBuffer::current();
			$buffer->contentType('text/html');

			$buffer->push("<script type='text/javascript'>\n");
			if(isset($_FILES['csv-file'])) {
				$fileInfo = getArrayKey($_FILES, 'csv-file');

				$name = getArrayKey($fileInfo, 'name');
				$tempPath = getArrayKey($fileInfo, 'tmp_name');
				$error = getArrayKey($fileInfo, 'error');
				$size = getArrayKey($fileInfo, 'size');

				if($error) {
					$buffer->push("alert('Failed to upload file');\n");
				} else {
					$config = mainConfiguration::getInstance();
					$file = umiFile::manualUpload($name, $tempPath, $size, $config->includeParam('system.runtime-cache'));

					if(!($file instanceof iUmiFile) || $file->getIsBroken()) {
						$buffer->push("alert('Upload file is broken');\n");
					} else {
						$import = new quickCsvImporter($file);
						$import->forceHierarchy = $forceHierarchy;

						$objectTypes = array();
						$hierarchyTypes = array();
						foreach($sel->types as $type) {
							if(!is_null($type->objectType)) $objectTypes[] = $type->objectType;
							if(!is_null($type->hierarchyType)) $hierarchyTypes[] = $type->hierarchyType;
						}

						if(!$forceHierarchy && !sizeof($sel->hierarchy)) {
							if(sizeof($objectTypes)) {
								$import->importAsObjects($objectTypes[0]);
								$buffer->push("//Upload completed\n");
							}
						} else {
							if(sizeof($hierarchyTypes) > 1) {
								$hierarchyTypeId = false;
								for($i = sizeof($hierarchyTypes) - 1; $i >= 0; $i--) {
									if($hierarchyTypes[$i]->getId() == umiHierarchyTypesCollection::getInstance()->getTypeByName('comments', 'comment')->getId()) {
										continue;
									}
									$hierarchyTypeId = $hierarchyTypes[$i]->getId();
									break;
								}
								$parentElementId = false;

								if(is_array($sel->hierarchy) && sizeof($sel->hierarchy)) {
									$parentElementId = $sel->hierarchy[0]->elementId;
								}

								if($parentElementId) {
									$dominantObjectTypeId = umiHierarchy::getInstance()->getDominantTypeId($parentElementId);
									if($dominantObjectTypeId) {
										$objectType = umiObjectTypesCollection::getInstance()->getType($dominantObjectTypeId);
										if($dominantHierarchyTypeId = $objectType->getHierarchyTypeId()) {
											$hierarchyTypeId = $dominantHierarchyTypeId;
										}
									}
								}
								$import->importAsElements($hierarchyTypeId, $parentElementId);
							}
						}
						$file->delete();
					}
				}
			} else {
				$buffer->push("alert('File is not posted');\n");
			}

			$buffer->push("window.parent.csvQuickImportCallback();\n");
			$buffer->push("</script>\n");
			$buffer->end();
		}

		protected function importElements(umiObjectType $objectType, $hierarchyTypeId, $parentElementId) {
			$buffer = outputBuffer::current();

			$headers = $this->readNextRow();
			$this->fields = count($headers);
			$hierarchy = umiHierarchy::getInstance();
			$permissions = permissionsCollection::getInstance();

			def_module::$noRedirectOnPanic = true;
			$errorsList = Array();
			$headers = $this->analyzeHeaders($objectType, $headers);

			while($cols = $this->readNextRow()) {
				echo str_repeat(" ", 1024);
				flush();
				$cols = $this->analyzeColumns($headers, $cols);

				if(!isset($cols['id'])) continue;
				$elementId = $cols['id'];

				if($elementId) {
					$eventId = "systemModifyElement";
					$requestKey = $elementId;
				} else if($parentElementId && !$this->forceHierarchy) {
					$eventId = "systemCreateElement";
					$requestKey = "new";

					//Create new element if we have correct parentElementId
					if(!$cols['alt-name']) {
						$cols['alt-name'] = $cols['name'];
					}

					$elementId = $hierarchy->addElement($parentElementId, $hierarchyTypeId, $cols['name'], $cols['alt-name'], $objectType->getId());
					$cols['id'] = $elementId;

					//Set default permissions
					$permissions->setDefaultPermissions($elementId);
				} else {
					continue;
				}

				$element = $hierarchy->getElement($elementId, ($elementId == "new"));
				if($element instanceof umiHierarchyElement == false) {
					$errorsList[] = Array(
						'id' => $elementId,
						'name' => $cols['name'],
						'error' => getLabel('csv-error-not-found')
					);
					continue;
				}

				if($requestKey != "new") {
					//Check if element hierarchy type id is correct according to hierarchyTypeId param
					if($element->getTypeId() != $hierarchyTypeId) {
						$errorsList[] = Array(
							'id' => $elementId,
							'name' => $cols['name'],
							'error' => getLabel('csv-error-wrong-type')
						);
						continue;
					}
				}

				//Set request params
				if(isset($cols['name'])) $_REQUEST['name'] = $cols['name'];
				if(isset($cols['alt-name'])) $_REQUEST['alt_name'] = $cols['alt-name'];
				if(isset($cols['is-active'])) $_REQUEST['is_active'] = $cols['is-active'];
				foreach($cols as $fieldName => $value) {
					$_REQUEST['data'][$requestKey][$fieldName] = $value;
				}


				//Call "before" event point listeners
				try {
					$event = new umiEventPoint($eventId);
					$event->addRef("element", $element);
					$event->setMode("before");
					$event->call();
				} catch (errorPanicException $e) {
					//Collect exceptions into errorsList array
					$errorsList[] = Array(
						"id" => $requestKey,
						"error" => $e->getMessage(),
						"name" => $cols['name']
					);

					//Delete new page, if specific exception catched
					if($requestKey == "new") {
						$hierarchy->delElement($element);
					}
					continue;
				}

				if($requestKey == 'new') {
					$isVisible = ($element->getModule() == 'content');
					$element->setIsVisible($isVisible);
				}

				foreach($cols as $fieldName => $value) {
					switch($fieldName) {
						case "id": continue;
						case "name": {
							$element->setName($value);
							break;
						}
						case "alt-name": {
							$element->setAltName($cols['alt-name']);
							break;
						}
						case "is-active": {
							$element->setIsActive($cols['is-active']);
							break;
						}
						default: {
							try {
								$this->modifyProperty($element, $fieldName, $value);
							} catch (fieldRestrictionException $e) {
								$errorsList[] = Array(
									"id" => $requestDataKey,
									"error" => $e->getMessage(),
									"name" => $cols[$fieldName]
								);
							}
							break;
						}
					}
				}

				//Call "after" event point listeners
				try {
					$event->setMode("after");
					$event->call();
				} catch (errorPanicException $e) {
					//Collect exceptions into errorsList array
					$errorsList[] = Array(
						"id" => $requestKey,
						"error" => $e->getMessage(),
						"name" => $cols['name']
					);

					if($requestDataKey == "new") {
						$hierarchy->delElement($elementId);
					}
					continue;
				}


				//Delete request params
				unset($_REQUEST['name']);
				unset($_REQUEST['alt_name']);
				unset($_REQUEST['is_active']);
				$_REQUEST['data'][$requestKey];

				$element->commit();
				unset($element);
				$hierarchy->unloadElement($elementId);
			}
			def_module::$noRedirectOnPanic = true;

			//Output errors list
			if(sizeof($errorsList)) {
				$buffer->push("var err = '" . getLabel('csv-error-import-list') . "\\n';\n");
				foreach($errorsList as $errorInfo) {
					if($errorInfo['id'] == "new") {
						$buffer->push("err += '{$errorInfo['name']} (" . getLabel('csv-new-item') . ") - {$errorInfo['error']}\\n';\n");
					} else {
						$buffer->push("err += '{$errorInfo['name']} (#{$errorInfo['id']}) - {$errorInfo['error']}\\n';\n");
					}
				}
				$buffer->push("alert(err);\n\n");
			}
		}

		protected function importObjects(umiObjectType $objectType) {
			$headers = $this->readNextRow();
			$this->fields = count($headers);
			$objects = umiObjectsCollection::getInstance();
			$types = umiObjectTypesCollection::getInstance();

			$subscriber = false;
			if ($objectType->getId() == $types->getTypeIdByGUID('dispatches-subscriber')) $subscriber = true;

			$buffer = outputBuffer::current();

			def_module::$noRedirectOnPanic = true;
			$headers = $this->analyzeHeaders($objectType, $headers);

			$errorsList = Array();

			while($cols = $this->readNextRow()) {
				echo str_repeat(" ", 1024);
				flush();
				$cols = $this->analyzeColumns($headers, $cols);

				if(!isset($cols['id'])) continue;
				$objectId = $cols['id'];

				if ($subscriber && !$objectId) {
					$sel = new selector('objects');
					$sel->types("object-type")->name("dispatches", "subscriber");
					$sel->where("name")->equals($cols['name']);
					$sel->option('return')->value('id');
					$result = $sel->first;
					if (is_array($result) && count($result)) $objectId = $result['id'];
				}

				if($objectId) {
					$requestDataKey = $objectId;
					$eventId = "systemModifyObject";
				} else {
					$eventId = "systemCreateObject";

					$objectId = $objects->addObject("Temporary object name", $objectType->getId());
					$buffer->push("//Create new object \"{$cols['name']}\" of type {$objectType->getId()}, id #{$objectId}\n");

					$requestDataKey = "new";
				}

				$object = $objects->getObject($objectId);
				if($object instanceof umiObject == false) {
					$errorsList[] = Array(
						"id" => $objectId,
						"error" => getLabel('csv-error-not-found'),
						"name" => $cols['name']
					);
					continue;
				}

				if($object->getTypeId() != $objectType->getId()) {
					$errorsList[] = Array(
						"id" => $objectId,
						"error" => getLabel('csv-error-wrong-type'),
						"name" => $cols['name']
					);
					continue;
				}

				//Store props to _REQUEST global array
				$fields = umiFieldsCollection::getInstance();
				$_REQUEST['data'][$requestDataKey] = Array();
				foreach($headers as $i => $propName) {
					if($i == 'id') continue;
					if($i == 'name' && isset($cols[$i])) {
						$_REQUEST['name'] = $cols[$i];
					}

					if(isset($cols[$i])) {
						$_REQUEST['data'][$requestDataKey][$i] = $cols[$i];
					}
				}

				try {
					$event = new umiEventPoint($eventId);
					$event->addRef("object", $object);
					$event->setMode("before");
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = Array(
						"id" => $requestDataKey,
						"error" => $e->getMessage(),
						"name" => $cols['name']
					);

					if($requestDataKey == "new") {
						$objects->delObject($objectId);
					}
					continue 1;
				}

				//Modify properties
				foreach($cols as $fieldName => $value) {
					switch($fieldName) {
						case "id": continue;
						case "name": {
							$object->setName($value);
							break;
						}
						default: {
							try {
								$this->modifyProperty($object, $fieldName, $value);
							} catch (fieldRestrictionException $e) {
								$errorsList[] = Array(
									"id" => $requestDataKey,
									"error" => $e->getMessage(),
									"name" => $cols[$fieldName]
								);
							}
							break;
						}
					}
				}
				$object->commit();

				try {
					$event->setMode("after");
					$event->call();
				} catch (errorPanicException $e) {
					$errorsList[] = Array(
						"id" => $requestDataKey,
						"error" => $e->getMessage(),
						"name" => $cols['name']
					);

					if($requestDataKey == "new") {
						$objects->delObject($objectId);
					}
					continue 1;
				}

				unset($_REQUEST['data'][$requestDataKey]);
				unset($object);
			}

			def_module::$noRedirectOnPanic = false;

			if(sizeof($errorsList)) {
				$buffer->push("var err = '" . getLabel('csv-error-import-list') . ":\\n';\n");
				foreach($errorsList as $errorInfo) {
					if($errorInfo['id'] == "new") {
						$buffer->push("err += '{$errorInfo['name']} (" . getLabel('csv-new-item') . ") - {$errorInfo['error']}\\n';\n");
					} else {
						$buffer->push("err += '{$errorInfo['name']} (#{$errorInfo['id']}) - {$errorInfo['error']}\\n';\n");
					}
				}
				$buffer->push("alert(err);\n\n");
			}
		}

		protected function readNextRow() {
			$result = "";
			$handler = $this->getFileHandler();
			if(feof($handler)) {
				return false;
			} else {
				$string = fgets($handler);
				if (!$string) {
					return $this->readNextRow();
				} else {
					if (substr_count($string, '"') % 2 != 0) {
						$isRecord = false;
						while (!feof($handler) && !$isRecord) {
							  $string .= fgets($handler);
							if (substr_count($string, '"') % 2 == 0) {
								$isRecord = true;
							}
						}
					}
				}
				$string = html_entity_decode($string, ENT_QUOTES, 'cp1251');
				$row = preg_replace("/([^;])\"\"/s", "$1'*//*'", $string);
				preg_match_all("/\"(.*?)\"/s", $row, $matches);
				foreach($matches[0] as $quotes) {
					$newQuotes = str_replace(";", "'////'", $quotes);
					$row = str_replace($quotes, $newQuotes, $row);
				}
				$row = preg_replace("/(.+);$/s", "$1", trim($row));

				$row = explode(";", $row);

				foreach ($row as &$cell) {
					$cell = iconv("CP1251", "UTF-8//IGNORE", $cell);
					$cell = str_replace("'////'", ";", $cell);
					$cell = str_replace("'*//*'", '"', $cell);
					$cell = preg_replace("/^\"(.*)\"$/s", "$1", $cell);
					$cell = trim($cell);
				}
				return $row;
			}
		}


		/**
		* Splits csv string into array
		* @deprecated
		* @param mixed $stringRow
		* @return array
		*/
		protected function splitRow($stringRow) {
			$cols = Array();
			if(substr($stringRow, -1) != ";") $stringRow .= ";";
			$len = strlen($stringRow);

			$char = "";
			$prevChar = "";
			$colValue = "";
			for($i = 0; $i < $len; $i++) {
				$char = substr($stringRow, $i, 1);

				switch($char) {
					case ";": {
						if($prevChar != "\\") {
							$cols[] = $colValue;
							$colValue = "";
							break;
						} else {
							$colValue = substr($colValue, 0, strlen($colValue) - 1) . ";";
							break;
						}
					}


					case "\"": {
						if(substr($stringRow, $i, 2) != "\"\"") {
							break;
						} else {
							if(substr($stringRow, $i, 3) != "\"\";") {
								$colValue = $colValue . "\"";
							}
							$i++;
							break;
						}
					}

					default: {
						$colValue .= $char;
					}
				}
				$prevChar = $char;
			}

			return $cols;
		}

		protected function modifyProperty(umiEntinty $object, $fieldName, $stringValue) {
			if($object instanceof umiObject) {
				$objectTypeId = $object->getTypeId();
			} else {
				$objectTypeId = $object->getObject()->getTypeId();
			}

			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			if($objectType instanceof umiObjectType == false) {
				throw new coreException("Object type #{$objectTypeId} not found");
			}

			$fieldId = $objectType->getFieldId($fieldName);
			$field = umiFieldsCollection::getInstance()->getField($fieldId);


			if($field instanceof umiField) {
				$value = $this->prepareValue($field, $stringValue);
				return $object->setValue($field->getName(), $value);
			} else return false;
		}

		protected function getFieldId(umiObjectType $objectType, $propName) {
			foreach($objectType->getAllFields() as $field) {
				if($field->getTitle() == $propName) {
					return $field->getId();
				}

				if($this->getFieldAlias($propName)) {
					if($this->getFieldAlias($field->getTitle()) == $this->getFieldAlias($propName)) {
						return $field->getId();
					}
				}
			}
			return false;
		}

		protected function getFieldAlias($propName) {
			$propName = getI18N($propName);
			$arr = Array('photo' => Array('field-photo', 'field-image', 'field-izobrazhenie', 'field-photo-s'));

			if(substr($propName, 0, 6) == "i18n::") {
				$propName = substr($propName, 6);
			}

			foreach($arr as $i => $v) {
				if(in_array($propName, $v)) {
					return $i;
				}
			}

			return false;
		}

		protected function prepareValue(umiField $field, $stringValue) {
			$fieldType = $field->getFieldType();
			switch($fieldType->getDataType()) {
				case "relation": {
					$result = preg_split("/, ?/", $stringValue);
					foreach($result as $i => $val) {
						if($val) {
							$i18n = ulangStream::getI18n($val);
							$result[$i] = $i18n ? $i18n : $val;
						}
					}
					return $result;
				}
				case "tags": {
					return preg_split("/, ?/", $stringValue);
				}

				case "int": {
					return (int) $stringValue;
				}

				case "float":
				case "price": {
					return (float) $stringValue;
					break;
				}

				case "date": {
					if($stringValue) {
						return umiDate::getTimeStamp($stringValue);
					} else {
						return false;
					}
				}

				case "file":
				case "img_file": {
					if(preg_match("/[а-яА-Я ]/", $stringValue)) {
						$oldStringValue = iconv("UTF-8", "CP1251//IGNORE", $stringValue);
						$file1 = CURRENT_WORKING_DIR.$stringValue;
						$file2 = CURRENT_WORKING_DIR.$oldStringValue;
						$file = false;
						if (file_exists($file1)) {
							$file = $stringValue;
						} 
						elseif (file_exists($file2)) {
							$file = $oldStringValue;
						}

						if($file) {
							$stringValue = str_replace('\\', '/', $stringValue);
							$paths = explode('/', $stringValue);
							// Обрабатываем пути к файлам
							$newPaths = array();
							if (count($paths) > 1) {
								// Запоминаем имя файла
								$fileName = $paths[count($paths)-1];
								unset($paths[count($paths)-1]);
								// Обрабатываем каждую часть пути на случай, если там тоже русские буквы
								foreach($paths as $part) {
									if (preg_match("/[а-яА-Я]/", $part)) {
										$newPaths[] = translit::convert($part);
									}
									else {
										$newPaths[] = $part;
									}
								}
							}
							else {
								// Было только имя файла
								$fileName = $paths[0];
							}

							// Обрабатываем имя файла
							$partsFileName = explode('.', $fileName);
							// Последяя часть - расширение
							$ext = $partsFileName[count($partsFileName)-1];
							unset($partsFileName[count($partsFileName)-1]);

							$mainPartName = implode('.', $partsFileName);

							if (preg_match("/[а-яА-Я]/", $mainPartName)) {
								$mainPartName = translit::convert($mainPartName);
							}

							$mainPartName .= '.'.$ext;

							$newPaths[] = $mainPartName;

							$stringValue = implode('/', $newPaths);

							if ( !(file_exists(dirname(CURRENT_WORKING_DIR.$stringValue)) && is_dir(dirname(CURRENT_WORKING_DIR.$stringValue))) ) {
								mkdir(dirname(CURRENT_WORKING_DIR.$stringValue), 0777, true);
							}

							rename(CURRENT_WORKING_DIR.$file, CURRENT_WORKING_DIR.$stringValue);
						}
					}

					if($stringValue && substr($stringValue, 0, 1) == "/") {
						$stringValue = "." . $stringValue;
					}

					return $stringValue;
				}

				case "swf_file": {
					if($stringValue && substr($stringValue, 0, 1) == "/") {
						$stringValue = "." . $stringValue;
					}
					return $stringValue;
				}

				default: {
					return $stringValue;
				}
			}

		}

		protected function openFile() {
			$this->fileHandler = fopen($this->csvFile->getFilePath(), "r");
		}

		protected function getFileHandler() {
			return $this->fileHandler;
		}

		protected function closeFile() {
			if($handler = $this->getFileHandler()) {
				fclose($handler);
			}
			if($this->csvFile instanceof umiFile) {
				$this->csvFile->delete();
			}
		}

		protected function analyzeColumns($headers, $cols) {
			$result = Array();

			$fieldNames = array_keys($headers);

			for($i = 0; $i < sizeof($fieldNames); $i++) {
				$result[$fieldNames[$i]] = isset($cols[$i]) ? $cols[$i] : NULL;
			}
			return $result;
		}

		protected function analyzeHeaders(umiObjectType $objectType, $headers) {
			$result = Array();
			$fields = umiFieldsCollection::getInstance();

			$i = 0;
			foreach($headers as $title) {
				switch(wa_strtolower($title)) {
					case "id":
						$result['id'] = $title;
						break;
					case wa_strtolower(getLabel('label-name')):
						$result['name'] = $title;
						break;
					case wa_strtolower(getLabel('label-alt-name')):
						if($this->mode == "element") {
							$result['alt-name'] = $title;
							break;
						}
					case wa_strtolower(getLabel('field-is_active')):
						if($this->mode == "element") {
							$result['is-active'] = $title;
							break;
						}
					default: {
						$fieldId = $this->getFieldId($objectType, $title);
						$field = $fields->getField($fieldId);
						if($field instanceof umiField) {
							$result[$field->getName()] = $title;
						} else {
							$result['unkonwn-field-' . (++$i)] = $title;
						}
					}
				}
			}
			return $result;
		}
	};
?>
