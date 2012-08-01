<?php

	abstract class __exchange_import extends baseModuleAdmin {

		public function import_do() {
			$this->setDataType("list");
			$this->setActionType("view");

			$id = getRequest('param0');
			$objects = umiObjectsCollection::getInstance();

			$settings = $objects->getObject($id);
			if (!$settings instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-settings_notfound"));
			}

			$importFile = $settings->file;
			if (!($importFile instanceof umiFile) || ($importFile->getIsBroken())) {
				throw new publicException(getLabel("exchange-err-importfile"));
			}

			$format_id = $settings->format;
			$importFormat = $objects->getObject($format_id);
			if (!$importFormat instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-format_undefined"));
			}

			$suffix = $importFormat->sid;
			$import_offset = (int) getSession("import_offset_" . $id);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") : 25;

			$splitter = umiImportSplitter::get($suffix);
			$splitter->load($importFile->getFilePath(), $blockSize, $import_offset);
			$doc = $splitter->getDocument();
			$dump = $splitter->translate($doc);

			$oldIgnoreSiteMap =  umiHierarchy::$ignoreSiteMap;
			umiHierarchy::$ignoreSiteMap = true;

			$importer = new xmlImporter();
			$importer->loadXmlString($dump);

			$elements = $settings->elements;
			if (is_array($elements) && count($elements)) {
				$importer->setDestinationElement($elements[0]);
			}

			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->setRenameFiles($splitter->getRenameFiles());

			$importer->execute();

			umiHierarchy::$ignoreSiteMap = $oldIgnoreSiteMap;

			$_SESSION["import_offset_" . $id] = $splitter->getOffset();

			if ($splitter->getIsComplete()) {
				unset($_SESSION["import_offset_" . $id]);
			}

			$data = array(
				"attribute:complete" => (int) $splitter->getIsComplete(),
				"attribute:created" => $importer->created_elements,
				"attribute:updated" => $importer->updated_elements,
				"attribute:deleted" => $importer->deleted_elements,
				"attribute:errors" => $importer->import_errors,
				"nodes:log" => $importer->getImportLog()
			);

			$this->setData($data);
			return $this->doData();
		}

	}
?>