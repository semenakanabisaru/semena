<?php

class dbSchemeConverter {

	private $connection;
	private $dbname;
	private $dom;

	private $destinationFile = false;
	private $sourceFile = false;
	private $mode = false;
	private $completed = false;
	private $state = array();
	private $inParts = false;
	private $limit = 1000;

	private	$converterLog = array();

	public function __construct (iConnection $connection) {
		$this->connection = $connection;
		$connectionInfo = $connection->getConnectionInfo();
		$this->dbname = $connectionInfo['dbname'];
	}

	public function setDestinationFile($path) {
		$this->destinationFile = $path;
	}

	public function setSourceFile($path) {
		$this->sourceFile = $path;
	}

	public function setMode($mode = false, $inParts = false, $limit = 1000) {
		$this->mode = $mode;
		$this->inParts = $inParts;
		if ((int) $limit > 0) $this->limit = (int) $limit;
	}

	public function run() {
		$this->converterLog = array();
		switch ($this->mode) {
			case 'save': {
				if (!$this->destinationFile) throw new coreException("Please set destination file name");
				$this->saveXmlToFile();
				return true;
			}
			case 'restore': {
				$this->getState();
				$this->restoreDataBase();
				$this->saveState();
				return $this->completed;
			}
			default: {
				throw new coreException("Don't know what to do. Please set any appropriate mode.");
			}
		}
	}

	public function getConverterLog() {
		return $this->converterLog;
	}

	protected function writeLog($message) {
		if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $message . "\n\r";
		else $this->converterLog[] = $message;
	}

	protected function reportError($error) {
		if (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) echo $error . "\n\r";
		else $this->converterLog[] = $error;
	}

	protected function getState() {
		if (!$this->inParts) return;

		if (!$this->sourceFile || !$this->destinationFile) {
			throw new coreException("Please set destination and source file name");
		}

		if (file_exists(CURRENT_WORKING_DIR . "/sys-temp/updates/" . md5($this->destinationFile))) {
			$this->state = unserialize(file_get_contents(CURRENT_WORKING_DIR . "/sys-temp/updates/" . md5($this->destinationFile)));
		} else {
			$docNew = new DOMDocument();
			if (!$docNew->load($this->sourceFile)) {
				throw new coreException("Can't load xml: " . $this->sourceFile);
			}

			$this->state = array();
			$tablesNew = $docNew->getElementsByTagName('table');
			foreach ($tablesNew as $tableNew) {
				$this->state[$tableNew->getAttribute('name')] = array();
			}
		}
	}

	protected function saveState() {
		if (!$this->inParts) return;

		if ($this->completed && file_exists(CURRENT_WORKING_DIR . "/sys-temp/updates/" . md5($this->destinationFile))) {
			unlink(CURRENT_WORKING_DIR . "/sys-temp/updates/" . md5($this->destinationFile));
		} else {
			file_put_contents(CURRENT_WORKING_DIR . "/sys-temp/updates/" . md5($this->destinationFile), serialize($this->state));
		}
	}

	private function dumpToXml() {

		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = XML_FORMAT_OUTPUT;

		$tablesElement = $dom->createElement('tables', '');
		$dom->appendChild($tablesElement);

		$tables = $this->connection->queryResult("SHOW TABLES");
		foreach ($tables as $table)	{
			$result = $this->connection->queryResult("SHOW CREATE TABLE `{$table[0]}`");
			foreach ($result as $row) {

				$tableElement = $dom->createElement('table', '');
				$tablesElement->appendChild($tableElement);

				$nameAttribute = $dom->createAttribute('name');
				$tableElement->appendChild($nameAttribute);
				$nameText = $dom->createTextNode("{$table[0]}");
				$nameAttribute->appendChild($nameText);

				preg_match("/DEFAULT\s+CHARSET=(.*)/is", $row[1], $charset); // находит charset
				if(isset($charset[1])){
					$charsetAttribute = $dom->createAttribute('charset');
					$tableElement->appendChild($charsetAttribute);
					$charsetText = $dom->createTextNode("{$charset[1]}");
					$charsetAttribute->appendChild($charsetText);
					$row[1] = preg_replace("/DEFAULT\s+CHARSET=.*/is", "", $row[1]); // обрезает charset
				}

				preg_match("/ENGINE=(.*)\s+/s", $row[1], $engine); // находит engine
				$engine[1] = preg_replace("/\s+(.*)/", "", $engine[1]);

				$engineAttribute = $dom->createAttribute('engine');
				$tableElement->appendChild($engineAttribute);
				$engineText = $dom->createTextNode("{$engine[1]}");
				$engineAttribute->appendChild($engineText);

				$row[1] = preg_replace("/\)\s+ENGINE=(.*)/is", "", $row[1]);
				$row[1] = preg_replace("/CREATE\s+TABLE\s+`(.*)`\s+\(/i", "", $row[1]); // оставляем только внутренности таблицы

				preg_match("/(CONSTRAINT\s.*)/is", $row[1], $constraints); // находит constraints

				if (isset($constraints[1]))	{

					$constraintsElement = $dom->createElement('constraints', '');
					$tableElement->appendChild($constraintsElement);

					$constraints = explode(',',$constraints[1]);
					foreach ($constraints as $constraint) {

						$constraintElement = $dom->createElement('constraint', '');
						$constraintsElement->appendChild($constraintElement);

						preg_match("/`([a-zA-Z0-9_\s+])+`/", $constraint, $matches);
						preg_match("/`(.*)`/", $matches[0], $name);
						preg_match("/FOREIGN\s+KEY\s+\(`([a-zA-Z0-9_])+`\)/i", $constraint, $matches);
						preg_match("/`(.*)`/", $matches[0], $foreignKey);
						preg_match("/REFERENCES\s+`(.*)`\s+\(/i", $constraint, $refTable);
						preg_match("/REFERENCES\s+`.*`\s+\(`(.*)`/i", $constraint, $refField);

						$nameAttribute = $dom->createAttribute('name');
						$constraintElement->appendChild($nameAttribute);
						$nameText = $dom->createTextNode("{$name[1]}");
						$nameAttribute->appendChild($nameText);

						$fieldAttribute = $dom->createAttribute('field');
						$constraintElement->appendChild($fieldAttribute);
						$fieldText = $dom->createTextNode("{$foreignKey[1]}");
						$fieldAttribute->appendChild($fieldText);

						$refTableAttribute = $dom->createAttribute('ref-table');
						$constraintElement->appendChild($refTableAttribute);
						$refTableText = $dom->createTextNode("{$refTable[1]}");
						$refTableAttribute->appendChild($refTableText);

						$refFieldAttribute = $dom->createAttribute('ref-field');
						$constraintElement->appendChild($refFieldAttribute);
						$refFieldText = $dom->createTextNode("{$refField[1]}");
						$refFieldAttribute->appendChild($refFieldText);

						preg_match("/ON\s+DELETE\s+(CASCADE|SET\s+NULL)/i", $constraint, $onDelete);
						if (isset($onDelete[1])) {
							$deleteAttribute = $dom->createAttribute('on-delete');
							$constraintElement->appendChild($deleteAttribute);
							$deleteText = $dom->createTextNode("{$onDelete[1]}");
							$deleteAttribute->appendChild($deleteText);
						}

						preg_match("/ON\s+UPDATE\s+(CASCADE|SET\s+NULL)/i", $constraint, $onUpdate);
						if (isset($onUpdate[1])) {
							$updateAttribute = $dom->createAttribute('on-update');
							$constraintElement->appendChild($updateAttribute);
							$updateText = $dom->createTextNode("{$onUpdate[1]}");
							$updateAttribute->appendChild($updateText);
						}
					}
					$row[1] = preg_replace("/(CONSTRAINT\s.*)/is", '', $row[1]); // отсекает constraints
				}

				preg_match("/PRIMARY\s+KEY\s+\(`(.*)`\)/i", $row[1], $primaryKey); // находит primary key
				if (isset($primaryKey[1])) {
					$row[1] = preg_replace("/PRIMARY\s+KEY\s+\(`.*`\)/i", '', $row[1]); // отсекает primary key
				}

				preg_match("/UNIQUE\s+KEY\s+(.*)\)/i", $row[1], $uniqueKey); // находит unique key
				if (isset($uniqueKey[1])) {
					$row[1] = preg_replace("/UNIQUE\s+KEY\s+.*\)/i", '', $row[1]); // отсекает unique key
				}

				preg_match("/(KEY\s.*)/is", $row[1], $keys); // находит key

				if(isset($primaryKey[1]) || isset($keys[1]) || isset($uniqueKey[1])) {
					$indexesElement = $dom->createElement('indexes', '');
					$tableElement->appendChild($indexesElement);
				}

				if (isset($primaryKey[1])) {

					$indexElement = $dom->createElement('index', '');
					$indexesElement->appendChild($indexElement);

					$typeAttribute = $dom->createAttribute('type');
					$indexElement->appendChild($typeAttribute);
					$typeText = $dom->createTextNode("PRIMARY");
					$typeAttribute->appendChild($typeText);

					$fieldElement = $dom->createElement('field', "{$primaryKey[1]}");
					$indexElement->appendChild($fieldElement);

				}

				if (isset($uniqueKey[1])) {

					$indexElement = $dom->createElement('index', '');
					$indexesElement->appendChild($indexElement);

					$typeAttribute = $dom->createAttribute('type');
					$indexElement->appendChild($typeAttribute);
					$typeText = $dom->createTextNode("UNIQUE");
					$typeAttribute->appendChild($typeText);

					preg_match("/`(.*)`\s+\(/", $uniqueKey[1], $name);

					$nameAttribute = $dom->createAttribute('name');
					$indexElement->appendChild($nameAttribute);
					$nameText = $dom->createTextNode("{$name[1]}");
					$nameAttribute->appendChild($nameText);

					preg_match("/\((.*)/", $uniqueKey[1], $matches);
					$fields = explode(',', $matches[1]);
					foreach ($fields as $field) {
						preg_match("/\((\d+)\)/", $field, $length);
						preg_match("/`(.*)`/", $field, $fieldName);
						$fieldElement = $dom->createElement('field', "{$fieldName[1]}");
						$indexElement->appendChild($fieldElement);
						if(isset($length[1])) {
							$lengthAttribute = $dom->createAttribute('length');
							$fieldElement->appendChild($lengthAttribute);
							$lengthText = $dom->createTextNode("{$length[1]}");
							$lengthAttribute->appendChild($lengthText);
						}
					}
				}

				if (isset($keys[1])) {

					$keys[1] = preg_replace("/`\),/", "` ),", $keys[1]);
					$keys[1] = preg_replace("/\)\),/", ") ),", $keys[1]);
					$keys = preg_split("/\s+\),/",$keys[1]);
					foreach ($keys as $key) {
						$key = trim($key);
						if (strlen($key)) {
							preg_match("/`(.*)`\s+\(/", $key, $name);

							$indexElement = $dom->createElement('index', '');
							$indexesElement->appendChild($indexElement);

							$nameAttribute = $dom->createAttribute('name');
							$indexElement->appendChild($nameAttribute);
							$nameText = $dom->createTextNode("{$name[1]}");
							$nameAttribute->appendChild($nameText);

							preg_match("/\((.*)/", $key, $matches);
							$fields = explode(',', $matches[1]);
							foreach ($fields as $field) {
								preg_match("/\((\d+)\)/", $field, $length);
								preg_match("/`(.*)`/", $field, $fieldName);
								$fieldElement = $dom->createElement('field', "{$fieldName[1]}");
								$indexElement->appendChild($fieldElement);

								if(isset($length[1])) {
									$lengthAttribute = $dom->createAttribute('length');
									$fieldElement->appendChild($lengthAttribute);
									$lengthText = $dom->createTextNode("{$length[1]}");
									$lengthAttribute->appendChild($lengthText);
								}
							}
						}
					}
					$row[1] = preg_replace("/(KEY\s.*)/is", '', $row[1]); // отсекает key
				}


				$tableFields = preg_split('/\n/',$row[1]);

				$fieldsElement = $dom->createElement('fields', '');
				$tableElement->appendChild($fieldsElement);

				foreach ($tableFields as $field) {
					$field = preg_replace("/,$/s", "", $field);
					$field = trim($field);
					if (strlen($field)>1) {
						$fieldElement = $dom->createElement('field');
						$fieldsElement->appendChild($fieldElement);

						preg_match("/COMMENT\s+'(.*)'/i", $field, $comment);
						if (isset($comment[1])) {
							$commentAttribute = $dom->createAttribute('comment');
							$fieldElement->appendChild($commentAttribute);
							$commentText = $dom->createTextNode("{$comment[1]}");
							$commentAttribute->appendChild($commentText);
							$field = preg_replace("/COMMENT\s+'.*'/i", "", $field);
						}

						preg_match("/\s+(BINARY|UNSIGNED\s+ZEROFILL|UNSIGNED|on\s+update\s+CURRENT_TIMESTAMP)/i", $field, $attribute);
						if(isset($attribute[1])) {
							$attributeAttribute = $dom->createAttribute('attributes');
							$fieldElement->appendChild($attributeAttribute);
							$attributeText = $dom->createTextNode("{$attribute[1]}");
							$attributeAttribute->appendChild($attributeText);
							$field = preg_replace("/\s+BINARY|\s+UNSIGNED\s+ZEROFILL|\s+UNSIGNED|\s+on\s+update\s+CURRENT_TIMESTAMP/i", "", $field);
						}

						preg_match("/\s+(NOT\s+NULL)/i", $field, $notNull);
						if(isset($notNull[1])) {
							$nullAttribute = $dom->createAttribute('not-null');
							$fieldElement->appendChild($nullAttribute);
							$nullText = $dom->createTextNode("1");
							$nullAttribute->appendChild($nullText);
							$field = preg_replace("/\s+NOT\s+NULL/i", "", $field);
						}

						preg_match("/\s+(AUTO_INCREMENT)/i", $field, $increment);
						if(isset($increment[1])) {
							$incAttribute = $dom->createAttribute('increment');
							$fieldElement->appendChild($incAttribute);
							$incText = $dom->createTextNode("1");
							$incAttribute->appendChild($incText);
							$field = preg_replace("/\s+AUTO_INCREMENT/i", "", $field);
						}

						preg_match("/\s+DEFAULT\s+(.*)/i", $field, $default);
						if(isset($default[1])) {
							preg_match("/'(.*)'/", $default[1], $match);
							if (isset($match[1])) $default[1] = $match[1];
							$defaultAttribute = $dom->createAttribute('default');
							$fieldElement->appendChild($defaultAttribute);
							$defaultText = $dom->createTextNode("{$default[1]}");
							$defaultAttribute->appendChild($defaultText);
							$field = preg_replace("/\s+DEFAULT\s+.*/i", "", $field);
						}

						preg_match("/`(.*)`/", $field, $name);
						if (isset($name[1])) {
							$nameAttribute = $dom->createAttribute('name');
							$fieldElement->appendChild($nameAttribute);
							$nameText = $dom->createTextNode("{$name[1]}");
							$nameAttribute->appendChild($nameText);
							$field = preg_replace("/`.*`/", "", $field);
						}

						preg_match("/\((\d+|\d+,\d+)\)/", $field, $size);
						if (isset($size[1])) {
							$sizeAttribute = $dom->createAttribute('size');
								$fieldElement->appendChild($sizeAttribute);
								$sizeText = $dom->createTextNode("{$size[1]}");
								$sizeAttribute->appendChild($sizeText);
								$field = preg_replace("/\({$size[1]}\)/", "", $field);
						}

						preg_match("/\((.*)\)/", $field, $size);
						if (isset($size[1])) {

							$options = preg_replace("/'/", '', $size[1]);
							$options = explode(',', $options);
							foreach ($options as $option) {
								$optionElement = $dom->createElement('option', $option);
								$fieldElement->appendChild($optionElement);
							}
							$field = preg_replace("/\((.*)\)/", "", $field);
						}

						$field = preg_replace("/,/", "", $field);
						$field = trim($field);

						if ($field) {
							$typeAttribute = $dom->createAttribute('type');
							$fieldElement->appendChild($typeAttribute);
							$typeText = $dom->createTextNode("{$field}");
							$typeAttribute->appendChild($typeText);
						}
					}
				}
			}
		}
		return $dom->saveXML($dom->documentElement, DOM_LOAD_OPTIONS);
	}

	public function saveXmlToFile() {

		if (!file_put_contents($this->destinationFile, $this->dumpToXml())) {
			throw new coreException("Cannot create new xml file " . $this->destinationFile);
		}
	}

	public function restoreShowCreateTable($tableName) {

		$doc = new DOMDocument();

		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		$xpath = new DOMXPath($doc);
		$table = $xpath->query("//table[@name='{$tableName}']")->item(0);

		$createTable = "CREATE TABLE `{$tableName}` (\n";
		$fieldsRoot = $table->getElementsByTagName('fields');
		if(is_object($fieldsRoot->item(0))) {
			$fields = $fieldsRoot->item(0)->getElementsByTagName('field');
			foreach ($fields as $field) {
				$createTable .="\t";
				if($fieldName = $field->getAttribute('name')) $createTable .= "`{$fieldName}`";
				if($fieldType = $field->getAttribute('type')) $createTable .=" {$fieldType}";
				if ($fieldSize = $field->getAttribute('size')) $createTable .="({$fieldSize})";
				if($field->getElementsByTagName('option')->length) {
					$options = $field->getElementsByTagName('option');
					$optionsValue = '';
					$i = 1;
					foreach ($options as $option) {
						if($i == $options->length) $optionsValue .= "'{$option->nodeValue}'";
						else $optionsValue .= "'{$option->nodeValue}',";
						$i++;
					}
					$createTable .="({$optionsValue})";
				}
				if ($fieldAttributes = $field->getAttribute('attributes')) $createTable .=" {$fieldAttributes}";
				if ($fieldNull = $field->getAttribute('not-null')) $createTable .=" NOT NULL";
				if($field->hasAttribute('default')){
					$fieldDefault=$field->getAttribute('default');
					if($fieldDefault !='NULL') $fieldDefault = "'{$fieldDefault}'";
					$createTable .=" DEFAULT {$fieldDefault}";
				}
				if ($fieldIncrement = $field->getAttribute('increment')) $createTable .=" AUTO_INCREMENT";
				if ($fieldComment = $field->getAttribute('comment')) $createTable .=" COMMENT '{$fieldComment}'";
				$createTable .= ",\n";
			}
		}

		$indexesRoot = $table->getElementsByTagName('indexes');
		if(is_object($indexesRoot->item(0))) {
			$indexes = $indexesRoot->item(0)->getElementsByTagName('index');
			foreach ($indexes as $index) {
				$createTable .="\t";
				if($indexType = $index->getAttribute('type')) $createTable .= "{$indexType} ";
				$createTable .= "KEY";
				if($indexName = $index->getAttribute('name')) $createTable .= " `{$indexName}`";
				if($index->getElementsByTagName('field')->length) {
					$fields = $index->getElementsByTagName('field');
					$fieldsValue = '';
					$i = 1;
					foreach ($fields as $field) {
						$fieldValue = "`{$field->nodeValue}`";
						if($fieldLength = $field->getAttribute('length')) $fieldValue .= "({$fieldLength})";
						if($i == $fields->length) $fieldsValue .= $fieldValue;
						else $fieldsValue .= "{$fieldValue},";
						$i++;
					}
					$createTable .=" ({$fieldsValue})";
				}
				$createTable .= ",\n";
			}
		}

		$constraintsRoot = $table->getElementsByTagName('constraints');
		if(is_object($constraintsRoot->item(0))) {
			$constraints = $constraintsRoot->item(0)->getElementsByTagName('constraint');
			foreach ($constraints as $constraint) {
				$createTable .= "\tCONSTRAINT";
				if($constraintName = $constraint->getAttribute('name')) $createTable .= " `{$constraintName}`";
				if ($constraintField = $constraint->getAttribute('field')) $createTable .= " FOREIGN KEY (`{$constraintField}`)";
				if ($constraintRefTable = $constraint->getAttribute('ref-table')) $createTable .= " REFERENCES `{$constraintRefTable}`";
				if($constraintRefField = $constraint->getAttribute('ref-field')) $createTable .= " (`{$constraintRefField}`)";
				if($constraintOnDelete = $constraint->getAttribute('on-delete')) $createTable .= " ON DELETE {$constraintOnDelete}";
				if($constraintOnUpdate = $constraint->getAttribute('on-update')) $createTable .= " ON UPDATE {$constraintOnUpdate}";
				$createTable .= ",\n";
			}
		}
		$createTable = preg_replace("/,$/s", "", $createTable);
		if($tableEngine = $table->getAttribute('engine')) $createTable .= ") ENGINE={$tableEngine}";
		if($tableCharset = $table->getAttribute('charset')) $createTable .= " DEFAULT CHARSET={$tableCharset}";
		$createTable .= "\n";
		return $createTable;
	}

	private function createDataBaseTable($tableName) {
		$createTable = $this->restoreShowCreateTable($tableName);
		$success = true;
		try {
			$this->connection->queryResult($createTable);
		} catch (Exception $e) {
			$this->reportError($e->getMessage());
			$success = false;
		}

		if ($success) $this->writeLog("Data base table {$tableName} has been created");
	}

	private function changeTableEngine($tableName, $tableEngine){

		$success = true;
		try {
			$this->connection->queryResult("ALTER TABLE `{$tableName}` ENGINE={$tableEngine}");
		} catch (Exception $e) {
			$this->reportError($e->getMessage());
			$success = false;
		}

		if ($success) $this->writeLog("Data base table ({$tableName}) engine has been changed");

	}

	private function changeTableCharset($tableName, $tableCharset){

		$success = true;
		try {
			$this->connection->queryResult("ALTER TABLE `{$tableName}` DEFAULT CHARACTER SET {$tableCharset}");
		} catch (Exception $e) {
			$this->reportError($e->getMessage());
			$success = false;
		}

		if ($success) $this->writeLog("Data base table ({$tableName}) character set has been changed");

		$doc = new DOMDocument();
		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}
		$xpath = new DOMXPath($doc);

		$table = $xpath->query("//table[@name='{$tableName}']")->item(0);
		$fieldsRoot = $table->getElementsByTagName('fields');
		if(is_object($fieldsRoot->item(0))) {
			$fields = $fieldsRoot->item(0)->getElementsByTagName('field');
			foreach ($fields as $field) {
				if($fieldName = $field->getAttribute('name')) $this->createTableField($tableName, $fieldName, 'modify');
			}
		}
	}

	private function createTableField($tableName, $fieldName, $param) {

		$doc = new DOMDocument();
		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		$xpath = new DOMXPath($doc);
		if (!$field = $xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->item(0)) {
			throw new coreException("Cannot change {$tableName}.{$fieldName}");
		}

		if ($param =='add') $createField = "ALTER TABLE `{$tableName}` ADD ";
		else $createField = "ALTER TABLE `{$tableName}` MODIFY ";

		$createField .= "`{$fieldName}`";
		if ($fieldType = $field->getAttribute('type')) $createField .=" {$fieldType}";
		if ($fieldSize = $field->getAttribute('size')) $createField .="({$fieldSize})";
		if ($field->getElementsByTagName('option')->length) {
			$options = $field->getElementsByTagName('option');
			$optionsValue = '';
			$i = 1;
			foreach ($options as $option) {
				if($i == $options->length) $optionsValue .= "'{$option->nodeValue}'";
				else $optionsValue .= "'{$option->nodeValue}',";
				$i++;
			}
			$createField .="({$optionsValue})";
		}
		if ($fieldAttributes = $field->getAttribute('attributes')) $createField .=" {$fieldAttributes}";
		if ($fieldNull = $field->getAttribute('not-null')) $createField .=" NOT NULL";
		if ($field->hasAttribute('default')){
			$fieldDefault=$field->getAttribute('default');
			if ($fieldDefault !='NULL') $fieldDefault = "'{$fieldDefault}'";
			$createField .=" DEFAULT {$fieldDefault}";
		}
		if ($fieldIncrement = $field->getAttribute('increment')) $createField .=" AUTO_INCREMENT";
		if ($fieldComment = $field->getAttribute('comment')) $createField .=" COMMENT '{$fieldComment}'";



		$success = true;
		try {
			$this->connection->queryResult($createField);
		} catch (Exception $e) {
			$this->reportError($e->getMessage());
			$success = false;
		}

		if ($success) {
			if ($param == 'add') $this->writeLog("Data base table ({$tableName}) field ({$fieldName}) has been created");
			else $this->writeLog("Data base table ({$tableName}) field ({$fieldName}) has been changed");
		}
	}

	private function createTableIndex($tableName, $indexName, $param) {

		$doc = new DOMDocument();
		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}
		$xpath = new DOMXPath($doc);

		if(!$index = $xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->item(0)) throw new coreException("Cannot change index {$indexName} in table {$tableName}");

		$createIndex = "ALTER TABLE `{$tableName}` ADD ";
		if ($indexType = $index->getAttribute('type')) {
			if ($indexType == "UNIQUE") $createIndex .= "UNIQUE";
		}

		else $createIndex .="INDEX";
		if ($indexName) $createIndex .=" `{$indexName}`";
		if ($index->getElementsByTagName('field')->length) {
			$fields = $index->getElementsByTagName('field');
			$fieldsValue = '';
			$i = 1;
			foreach ($fields as $field) {
				$fieldValue = "`{$field->nodeValue}`";
				if($fieldLength = $field->getAttribute('length')) $fieldValue .="({$fieldLength})";
				if($i == $fields->length) $fieldsValue .= $fieldValue;
				else $fieldsValue .= "{$fieldValue},";
				$i++;
			}
			$createIndex .=" ({$fieldsValue})";
		}
		if ($param =='modify') {
			if ($index->getElementsByTagName('field')->length) {
				$fields = $index->getElementsByTagName('field');
				foreach ($fields as $field) {
					$fieldValue = $field->nodeValue;
					if($constraint = $xpath->query("//table[@name='{$tableName}']/constraints/constraint[@field='{$fieldValue}']")->item(0)) {
						if($constraintName = $constraint->getAttribute('name')) $this->connection->queryResult("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
					}
				}
			}
			$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
			$success = true;
			try {
				$this->connection->queryResult($createIndex);
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) $this->writeLog("Data base table ({$tableName}) index ({$indexName}) has been changed");
			if ($index->getElementsByTagName('field')->length) {
				$fields = $index->getElementsByTagName('field');
				foreach ($fields as $field) {
					$fieldValue = $field->nodeValue;
					if($constraint = $xpath->query("//table[@name='{$tableName}']/constraints/constraint[@field='{$fieldValue}']")->item(0)) {
						if($constraintName = $constraint->getAttribute('name')) $this->createTableConstraint($tableName, $constraintName, 'add');
					}
				}
			}
		} else {
			$success = true;
			try {
				$this->connection->queryResult($createIndex);
			} catch (Exception $e) {
				$this->reportError($e->getMessage());
				$success = false;
			}

			if ($success) $this->writeLog("Data base table ({$tableName}) index ({$indexName}) has been created");
		}
	}

	private function createTableConstraint($tableName, $constraintName, $param) {

		$doc = new DOMDocument();
		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		$xpath = new DOMXPath($doc);
		if (!$constraint = $xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")->item(0)) throw new coreException("Cannot change constraint {$constraintName} in table {$tableName}");

		if ($param =='modify') $this->connection->queryResult("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");

		$createConstraint = "ALTER TABLE `{$tableName}` ADD CONSTRAINT";

		$createConstraint .=" `{$constraintName}`";
		if ($constraintField = $constraint->getAttribute('field')) $createConstraint .=" FOREIGN KEY (`{$constraintField}`)";
		if ($constraintRefTable = $constraint->getAttribute('ref-table')) $createConstraint .=" REFERENCES `{$constraintRefTable}`";
		if($constraintRefField = $constraint->getAttribute('ref-field')) $createConstraint .=" (`{$constraintRefField}`)";
		if($constraintOnDelete = $constraint->getAttribute('on-delete')) $createConstraint .=" ON DELETE {$constraintOnDelete}";
		if($constraintOnUpdate = $constraint->getAttribute('on-update')) $createConstraint .=" ON UPDATE {$constraintOnUpdate}";

		$success = true;
		try {
			$this->connection->queryResult($createConstraint);
		} catch (Exception $e) {
			$this->reportError($e->getMessage());
			$success = false;
		}

		if ($success) {
			if ($param == 'add') $this->writeLog("Data base table ({$tableName}) constraint ({$constraintName}) has been created");
			else $this->writeLog("Data base table ({$tableName}) constraint ({$constraintName}) has been changed");
		}

		if($constraintOnDelete) {
			if ($constraintOnDelete == "SET NULL") {
				$sql = <<<END
					UPDATE `{$tableName}`
					SET {$constraintField} = null
					WHERE {$constraintField} NOT IN (
						SELECT {$constraintRefField}
						FROM {$constraintRefTable}
					)
END;
			}
			if ($constraintOnDelete == "CASCADE") {
				$sql = <<<END
					DELETE FROM `{$tableName}`
					WHERE {$constraintField} NOT IN (
						SELECT {$constraintRefField}
						FROM {$constraintRefTable}
					)
END;
			}
			$this->connection->queryResult($sql);
		}
	}

	public function restoreDataBase() {

		//структура, которая должна быть
		$docNew = new DOMDocument();
		if (!$docNew->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		//имеющаяся структура
		$docOld = new DOMDocument();
		if (!$docOld->load($this->destinationFile)) {
			throw new coreException("Can't load xml: " . $this->destinationFile);
		}

		$xpath = new DOMXPath($docOld);

		$tablesNew = $docNew->getElementsByTagName('table');
		foreach ($tablesNew as $table) {

			$tableName = $table->getAttribute('name');
			if ($this->inParts && isset($this->state[$tableName]['complete']) && $this->state[$tableName]['complete'] == true) continue;

			$this->connection->queryResult("SET foreign_key_checks = 0");
			$this->writeLog("Start checking table {$tableName}");

			if (!$xpath->query("//table[@name='{$tableName}']")->length) $this->createDataBaseTable($tableName);
			else {

				$countResult = l_mysql_query("SELECT count(*) FROM `{$tableName}`");
				list($countRows) = mysql_fetch_row($countResult);

				if ($countRows > 10000) {

					$tableRestored = $this->checkTableRestore($tableName);

					if($this->inParts) {
						end($this->state);
						$this->completed = ((key($this->state) == $tableName) && $tableRestored);
						return $this->completed;
					}

					continue;
				}

				$tableEngine = $table->getAttribute('engine');
				if(!$xpath->query("//table[@name='{$tableName}' and @engine='{$tableEngine}']")->item(0)) $this->changeTableEngine($tableName, $tableEngine);

				$tableCharset = $table->getAttribute('charset');
				if(!$xpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']")->item(0)) $this->changeTableCharset($tableName, $tableCharset);

				$fieldsRoot = $table->getElementsByTagName('fields');
				if (is_object($fieldsRoot->item(0))) {
					$fields = $fieldsRoot->item(0)->getElementsByTagName('field');

					$indexesRoot = $table->getElementsByTagName('indexes');
					if(is_object($indexesRoot->item(0))) {
						$indexes = $indexesRoot->item(0)->getElementsByTagName('index');
						foreach ($indexes as $index){
							if($index->getAttribute('type') == 'PRIMARY') {
								if($fieldNameNew = $index->getElementsByTagName('field')->item(0)->nodeValue) {
									if ($xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0)) {
										$fieldNameOld = $xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0)->nodeValue;
										if ($fieldNameNew != $fieldNameOld) {
											if($fieldOld = $xpath->query("//table[@name='{$tableName}']/fields/field[@name = '{$fieldNameOld}' and @increment='1']")->item(0)) $this->createTableField($tableName, $fieldNameOld, 'modify');
											if($constraints = $xpath->query("//table/constraints/constraint[@ref-table='{$tableName}' and @ref-field='{$fieldNameOld}']")) {
											foreach($constraints as $constraint) {
												$constraintName = $constraint->getAttribute('name');
												$tableNameC = $constraint->parentNode->parentNode->getAttribute('name');
												$this->connection->queryResult("ALTER TABLE `{$tableNameC}` DROP FOREIGN KEY `{$constraintName}`");
												}
											}
											$this->connection->queryResult("ALTER TABLE `{$tableName}` DROP PRIMARY KEY");
											$this->connection->queryResult("ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$fieldNameNew})");
										}
									}
									else $this->connection->queryResult("ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$fieldNameNew})");
								}
							}
						}
					}

					foreach ($fields as $field) {
						if ($fieldName = $field->getAttribute('name')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->length) $this->createTableField($tableName, $fieldName, 'add');
							else {
								if ($fieldType = $field->getAttribute('type')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @type='{$fieldType}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($fieldSize = $field->getAttribute('size')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @size='{$fieldSize}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($field->getElementsByTagName('option')->length) {
									$options = $field->getElementsByTagName('option');
									foreach ($options as $option) {
										if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}'][option ='{$option->nodeValue}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
									}
								}
								if ($fieldAttributes = $field->getAttribute('attributes')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @attributes='{$fieldAttributes}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($fieldNull = $field->getAttribute('not-null')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @not-null='{$fieldNull}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($field->hasAttribute('default')){
									$fieldDefault=$field->getAttribute('default');
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @default='{$fieldDefault}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($fieldIncrement = $field->getAttribute('increment')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @increment='{$fieldIncrement}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
								if ($fieldComment = $field->getAttribute('comment')) {
									if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @comment='{$fieldComment}']")->length) $this->createTableField($tableName, $fieldName, 'modify');
								}
							}
						}
					}
				}
				$indexesRoot = $table->getElementsByTagName('indexes');
				if(is_object($indexesRoot->item(0))) {
					$indexes = $indexesRoot->item(0)->getElementsByTagName('index');
					foreach ($indexes as $index) {
						if($indexName = $index->getAttribute('name')) {
							if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->length) $this->createTableIndex($tableName, $indexName, 'add');
							else {
								if($indexType = $index->getAttribute('type')) {
									if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}' and @type='{$indexType}']")->length) $this->createTableIndex($tableName, $indexName, 'modify');
								}
								if($index->getElementsByTagName('field')->length) {
									$fields = $index->getElementsByTagName('field');
									foreach ($fields as $field) {
										if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field = '{$field->nodeValue}']")->length) $this->createTableIndex($tableName, $indexName, 'modify');
										else {
											if($fieldLength = $field->getAttribute('length')) {
												if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field[@length='{$fieldLength}'] ='{$field->nodeValue}']")->length) $this->createTableIndex($tableName, $indexName, 'modify');
											}
										}
									}
								}
							}
						}
					}
				}

				$constraintsRoot = $table->getElementsByTagName('constraints');
				if(is_object($constraintsRoot->item(0))) {
					$constraints = $constraintsRoot->item(0)->getElementsByTagName('constraint');
					foreach ($constraints as $constraint) {
						if ($constraintName = $constraint->getAttribute('name')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'add');
							else {
								if ($constraintField = $constraint->getAttribute('field')) {
									if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @field='{$constraintField}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'modify');
								}
								if ($constraintRefTable = $constraint->getAttribute('ref-table')) {
									if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-table='{$constraintRefTable}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'modify');
								}
								if ($constraintRefField = $constraint->getAttribute('ref-field')) {
									if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-field='{$constraintRefField}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'modify');
								}
								if ($constraintOnDelete = $constraint->getAttribute('on-delete')) {
									if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-delete='{$constraintOnDelete}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'modify');
								}
								if ($constraintOnUpdate = $constraint->getAttribute('on-update')) {
									if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-update='{$constraintOnUpdate}']")->item(0)) $this->createTableConstraint($tableName, $constraintName, 'modify');
								}
							}
						}
					}
				}
			}

			$this->connection->queryResult("SET foreign_key_checks = 1");

			if($this->inParts) {
				$this->state[$tableName]['complete'] = true;
				end($this->state);
				$this->completed = (key($this->state) == $tableName);
				return $this->completed;
			}
		}

	}

	protected function prepareTable($tableName) {

		if ($this->inParts) {
			if (isset($this->state[$tableName]['info'])) {
				return $this->state[$tableName]['info'];
			}
		}

		$doc = new DOMDocument();
		$doc->load($this->sourceFile);

		$xpath = new DOMXPath($doc);

		$createSql = $this->restoreShowCreateTable($tableName);
		$tableNameNew = $tableName . '_temp';

		while(true) {
			$success = false;
			try {
				$this->connection->queryResult("SHOW CREATE TABLE `{$tableNameNew}`");
			} catch (Exception $e) {
				$success = true;
			}

			if (!$success) {
				$result = $this->connection->queryResult("SHOW CREATE TABLE `{$tableNameNew}`");
				foreach ($result as $row) {
					$newSql = str_replace($tableNameNew, $tableName, $row[1]);
					$newSql = preg_replace("/\s/", '', $newSql);
					$oldSql = preg_replace("/\s/", "", $createSql);
					if (stripos($oldSql, $newSql) !== false) {
						l_mysql_query("DROP TABLE `{$tableNameNew}`");
						$success = true;
					}
				}
			}

			if ($success) {
				break;
			} else {
				$tableNameNew .= '1';
			}
		}

		if ($xpath->query("//table[@name='{$tableName}']/constraints/constraint")->length) {
			$constraints = $xpath->query("//table[@name='{$tableName}']/constraints/constraint");

			foreach ($constraints as $constraint){
				$constraintName = $constraint->getAttribute('name');

				try {
					l_mysql_query("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
				} catch (Exception $e){

				}

				$constraintOnDelete = $constraint->getAttribute('on-delete');

				if($constraintOnDelete) {

					$constraintField = $constraint->getAttribute('field');
					$constraintRefTable = $constraint->getAttribute('ref-table');
					$constraintRefField = $constraint->getAttribute('ref-field');

					if ($constraintOnDelete == "SET NULL") {
						$sql = <<<END
							UPDATE `{$tableName}`
							SET {$constraintField} = null
							WHERE {$constraintField} NOT IN (
								SELECT {$constraintRefField}
								FROM {$constraintRefTable}
							)
END;
					}
					if ($constraintOnDelete == "CASCADE") {
						$sql = <<<END
							DELETE FROM `{$tableName}`
							WHERE {$constraintField} NOT IN (
								SELECT {$constraintRefField}
								FROM {$constraintRefTable}
							)
END;
					}

					try {
						l_mysql_query($sql);
					} catch (Exception $e){

					}
				}
			}
		}

		$createSql = str_replace("CREATE TABLE `{$tableName}`", "CREATE TABLE `{$tableNameNew}`", $createSql);
		l_mysql_query($createSql);

		$countResult = l_mysql_query("SELECT count(*) FROM `{$tableName}`", true);
		list($countRows) = mysql_fetch_row($countResult);

		$info = array(
			'temp_table' => $tableNameNew,
			'count_rows' => $countRows
		);

		if ($this->inParts) {
			$this->state[$tableName]['info'] = $info;
		}

		return $info;

	}


	protected function restoreTableInParts($tableName) {



		$info = $this->prepareTable($tableName);
		$tableNameNew = $info['temp_table'];
		$countRows = $info['count_rows'];


		$fields = $this->getNecessaryFields($tableName);
		$fields = implode(', ', $fields);

		if ($this->inParts) {
			$offset = isset($this->state[$tableName]['info']['offset']) ? $this->state[$tableName]['info']['offset'] : 0;
			l_mysql_query("INSERT INTO `{$tableNameNew}` ({$fields}) (SELECT {$fields} FROM {$tableName} LIMIT {$this->limit} OFFSET {$offset})");
		} else {
			$step = ceil($countRows / $this->limit);
			for($i = 0; $i < $step; $i++) {
				$offset = $i * $this->limit;
				l_mysql_query("INSERT INTO `{$tableNameNew}` ({$fields}) (SELECT {$fields} FROM {$tableName} LIMIT {$this->limit} OFFSET {$offset})");
			}
		}

		$countResultNew = l_mysql_query("SELECT count(*) FROM `{$tableNameNew}`", true);
		list($countRowsNew) = mysql_fetch_row($countResultNew);

		if ($countRows == $countRowsNew) {
			l_mysql_query("DROP TABLE `{$tableName}`");
			l_mysql_query("RENAME TABLE `{$tableNameNew}` TO `{$tableName}`");
			$this->writeLog("Data base table ({$tableName}) structure has been updated");
			if ($this->inParts) {
				$this->state[$tableName]['complete'] = true;
			}
			return true;

		} else {
			if ($this->inParts) {
				$this->state[$tableName]['info']['offset'] = $offset + $this->limit;
				$this->writeLog("{$countRowsNew}({$countRows}) rows have been updated in table `({$tableName})`");
			} else {
				$this->reportError(getLabel("label-errors-13059") . $tableName . "/");
			}

			return false;
		}
	}



	protected function checkTableRestore($tableName) {


		if($this->inParts && isset($this->state[$tableName]['info'])) {
			return $this->restoreTableInParts($tableName);
		}


		//структура, которая должна быть
		$docNew = new DOMDocument();
		if (!$docNew->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		//имеющаяся структура
		$docOld = new DOMDocument();
		if (!$docOld->load($this->destinationFile)) {
			throw new coreException("Can't load xml: " . $this->destinationFile);
		}

		$xpath = new DOMXPath($docOld);

		$tablesNew = $docNew->getElementsByTagName('table');
		foreach ($tablesNew as $tableNew) {
			if ($tableNew->getAttribute('name') == $tableName){
				$table = $tableNew;
				break;
			}
		}

		$fieldsRoot = $table->getElementsByTagName('fields');
		if (is_object($fieldsRoot->item(0))) {
			$fields = $fieldsRoot->item(0)->getElementsByTagName('field');

			foreach ($fields as $field) {
				if ($fieldName = $field->getAttribute('name')) {
					if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}']")->length) $this->createTableField($tableName, $fieldName, 'add');
					else {
						if ($fieldType = $field->getAttribute('type')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @type='{$fieldType}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($fieldSize = $field->getAttribute('size')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @size='{$fieldSize}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($field->getElementsByTagName('option')->length) {
							$options = $field->getElementsByTagName('option');
							foreach ($options as $option) {
								if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}'][option ='{$option->nodeValue}']")->length) {
									return $this->restoreTableInParts($tableName);
								}
							}
						}
						if ($fieldAttributes = $field->getAttribute('attributes')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @attributes='{$fieldAttributes}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($fieldNull = $field->getAttribute('not-null')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @not-null='{$fieldNull}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($field->hasAttribute('default')){
							$fieldDefault=$field->getAttribute('default');
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @default='{$fieldDefault}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($fieldIncrement = $field->getAttribute('increment')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @increment='{$fieldIncrement}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($fieldComment = $field->getAttribute('comment')) {
							if (!$xpath->query("//table[@name='{$tableName}']/fields/field[@name='{$fieldName}' and @comment='{$fieldComment}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
					}
				}
			}
		}

		$indexesRoot = $table->getElementsByTagName('indexes');
		if(is_object($indexesRoot->item(0))) {
			$indexes = $indexesRoot->item(0)->getElementsByTagName('index');
			foreach ($indexes as $index) {
				if($indexName = $index->getAttribute('name')) {
					if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}']")->length) {
						return $this->restoreTableInParts($tableName);
					} else {
						if($indexType = $index->getAttribute('type')) {
							if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}' and @type='{$indexType}']")->length) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if($index->getElementsByTagName('field')->length) {
							$fields = $index->getElementsByTagName('field');
							foreach ($fields as $field) {
								if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field = '{$field->nodeValue}']")->length) {
									return $this->restoreTableInParts($tableName);
								} else {
									if($fieldLength = $field->getAttribute('length')) {
										if (!$xpath->query("//table[@name='{$tableName}']/indexes/index[@name='{$indexName}'][field[@length='{$fieldLength}'] ='{$field->nodeValue}']")->length) {
											return $this->restoreTableInParts($tableName);
										}
									}
								}
							}
						}
					}
				}
				if($index->getAttribute('type') == 'PRIMARY') {
					if($fieldNameNew = $index->getElementsByTagName('field')->item(0)->nodeValue) {
						if ($xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0)) {
							$fieldNameOld = $xpath->query("//table[@name='{$tableName}']/indexes/index[@type='PRIMARY']/field")->item(0)->nodeValue;
							if ($fieldNameNew != $fieldNameOld) {
								return $this->restoreTableInParts($tableName);
							}
						}
						else {
							return $this->restoreTableInParts($tableName);
						}
					}
				}
			}
		}

		$constraintsRoot = $table->getElementsByTagName('constraints');
		if(is_object($constraintsRoot->item(0))) {
			$constraints = $constraintsRoot->item(0)->getElementsByTagName('constraint');
			foreach ($constraints as $constraint) {
				if ($constraintName = $constraint->getAttribute('name')) {
					if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}']")->item(0)) {
						return $this->restoreTableInParts($tableName);
					} else {
						if ($constraintField = $constraint->getAttribute('field')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @field='{$constraintField}']")->item(0)) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($constraintRefTable = $constraint->getAttribute('ref-table')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-table='{$constraintRefTable}']")->item(0)) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($constraintRefField = $constraint->getAttribute('ref-field')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @ref-field='{$constraintRefField}']")->item(0)) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($constraintOnDelete = $constraint->getAttribute('on-delete')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-delete='{$constraintOnDelete}']")->item(0)) {
								return $this->restoreTableInParts($tableName);
							}
						}
						if ($constraintOnUpdate = $constraint->getAttribute('on-update')) {
							if (!$xpath->query("//table[@name='{$tableName}']/constraints/constraint[@name='{$constraintName}' and @on-update='{$constraintOnUpdate}']")->item(0)) {
								return $this->restoreTableInParts($tableName);
							}
						}
					}
				}
			}
		}

		$tableEngine = $table->getAttribute('engine');
		if(!$xpath->query("//table[@name='{$tableName}' and @engine='{$tableEngine}']")->item(0)) $this->changeTableEngine($tableName, $tableEngine);

		$tableCharset = $table->getAttribute('charset');
		if(!$xpath->query("//table[@name='{$tableName}' and @charset='{$tableCharset}']")->item(0)) $this->changeTableCharset($tableName, $tableCharset);

		if ($this->inParts) {
			$this->state[$tableName]['complete'] = true;
		}
		return true;

	}

	protected function getNecessaryFields($tableName) {

		$fields = array();

		$doc = new DOMDocument();
		if (!$doc->load($this->sourceFile)) {
			throw new coreException("Can't load xml: " . $this->sourceFile);
		}

		$xpath = new DOMXPath($doc);
		$tableFields = $xpath->query("//table[@name='{$tableName}']/fields/field");

		if($tableFields->length) {
			foreach($tableFields as $field) {
				$fields[] = $field->getAttribute('name');
			}
		}

		return $fields;

	}

}

?>
