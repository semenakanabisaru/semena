<?php

	abstract class __exchange_export extends def_module {

		public function prepareElementsToExport() {

			$objectId = getRequest('param0');

			$complete = false;
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);
			$formatId = $object->format;
			$format = $objects->getObject($formatId);
			$suffix = $format->sid;
			if($suffix != 'YML') {
				$data = array(
					"attribute:complete" => (int) $complete,
					"attribute:preparation" => (int) !$complete,
				);

				baseModuleAdmin::setData($data);
				return baseModuleAdmin::doData();
			}

			$offset = (int) getSession("export_offset_" . $objectId);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") : 25;

			if(!file_exists(CURRENT_WORKING_DIR . "/sys-temp/yml/" . $objectId . 'el')) {
				throw new publicException('<a href="' . getLabel("label-errors-no-information") . '" target="blank">' . getLabel("label-errors-no-information") .'</a>');
			}

			$elementsToExport = unserialize(file_get_contents(CURRENT_WORKING_DIR . "/sys-temp/yml/" . $objectId . 'el'));
			$elements = umiHierarchy::getInstance();

			$errors = array();
			for ($i = $offset; $i <= $offset + $blockSize -1; $i++) {
				if(!array_key_exists($i, $elementsToExport)) {
					$complete = true;
					break;
				}
				$element = $elements->getElement($elementsToExport[$i]);
				if($element instanceof umiHierarchyElement) {

					try {
					$element->updateYML();
					} catch (Exception $e) {
						$errors[] = $e->getMessage() . " #{$elementsToExport[$i]}";
					}
				}
			}

			$_SESSION["export_offset_" . $objectId] = $offset + $blockSize;
			if ($complete) {
				unset($_SESSION["export_offset_" . $objectId]);
			}

			$data = array(
				"attribute:complete" => (int) $complete,
				"nodes:log" => $errors
			);

			baseModuleAdmin::setData($data);
			return baseModuleAdmin::doData();
		}

		public function get_export() {
			$id = getRequest('param0');
			$as_file = getRequest('as_file');

			$objects = umiObjectsCollection::getInstance();

			$settings = $objects->getObject($id);
			if (!$settings instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-settings_notfound"));
			}

			$format_id = $settings->format;
			$exportFormat = $objects->getObject($format_id);
			if (!$exportFormat instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-format_undefined"));
			}

			$suffix = $exportFormat->sid;

			$exporter = umiExporter::get($suffix);

			if ($settings->source_name) {
				$exporter->setSourceName($settings->source_name);
			}

			// check cache
			$cache_time = (int) $settings->cache_time;
			$temp_dir = "./sys-temp/export/";
			if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
			$cache_file_path = $temp_dir . $id . "." . $exporter->getFileExt();

			if ($as_file === '1') {

				$temp_folder = $temp_dir . $id;

				if(is_dir($temp_folder)) {

					if (file_exists($temp_dir . $id . ".zip")) unlink($temp_dir . $id . ".zip");

					$archive = new PclZip($temp_dir . $id . ".zip");
					$archive->add(array($temp_dir . $id . ".xml", $temp_folder), PCLZIP_OPT_REMOVE_PATH, 'sys-temp/export');
  						shell_exec("rm -rf {$temp_folder}");

					$zipFile = new umiFile($temp_dir . $id . ".zip");
					$zipFile->download();
						return;

					}

				$cache_file = new umiFile($cache_file_path);
				$cache_file->download();
				return;
			}

			if ($as_file === '0') {

				if (!file_exists($cache_file_path) || !$cache_time || ($cache_time && (time() > filectime($cache_file_path) + $cache_time * 60))) {
					$result = $exporter->export($settings->elements);
					if ($result) file_put_contents($cache_file_path, $result);
				}
				$buffer = $exporter->setOutputBuffer();
				$buffer->push(file_get_contents($cache_file_path));
				$buffer->end();
				return;
			}

			if ($as_file === null && (!file_exists($cache_file_path) || !$cache_time || ($cache_time && (time() > filectime($cache_file_path) + $cache_time * 60)))) {
					$result = $exporter->export($settings->elements);
					if ($result) file_put_contents($cache_file_path, $result);
			}


			$data = array(
				"attribute:complete" => (int) $exporter->getIsCompleted()
			);

			baseModuleAdmin::setData($data);
			return baseModuleAdmin::doData();
		}
	}
?>