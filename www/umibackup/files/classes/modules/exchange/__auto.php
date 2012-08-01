<?php
	abstract class __exchange_auto {
		protected function saveIncomingFile() {
			$file_name = getRequest('filename');
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$content = $buffer->getHTTPRequestBody();

			if (!strlen($file_name)) return "failure\nEmpty filename.";
			list($dir_name, , $extension) = array_values(getPathInfo($file_name));
			if (!strlen($extension)) return "failure\nUnknown file type.";

			if (!isset($_SESSION['1c_latest_catalog-file'])) {
				$_SESSION['1c_latest_catalog-file'] = "";
			}
			$i_flag = ($_SESSION['1c_latest_catalog-file'] == $file_name ? FILE_APPEND : 0);

			$base_name = substr($file_name, 0, strlen($file_name) - strlen($extension) - 1);

			$temp_dir = "./sys-temp/1c_import/";
			if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);

			if (strtolower($extension) == "xml") {
				file_put_contents($temp_dir . $base_name . "." . $extension, $content, $i_flag);
			} else {

				$files_size = getDirSize(CURRENT_WORKING_DIR.'/files/');
				$images_size = getDirSize(CURRENT_WORKING_DIR.'/images/');
				$all_size = $files_size + $images_size;
				$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );

				if($quota_byte && $all_size + strlen($content) >= $quota_byte) {
					return "failure\n max dirsize in /files and /images summary.";
				}

				$images_dir = "./images/cms/data/" . $dir_name . "/";
				if (!is_dir($images_dir)) mkdir($images_dir, 0777, true);
				file_put_contents("./images/cms/data/" . $file_name, $content, $i_flag);
			}

			$_SESSION['1c_latest_catalog-file'] = $file_name;

			return "success";
		}

		protected function importCommerceML() {
			$file_name = getRequest('filename');
			$file_path = "./sys-temp/1c_import/" . $file_name;

			if (!is_file($file_path)) return "failure\nFile $file_path not exists.";
			$import_offset = (int) getSession("1c_import_offset");

			$blockSize = (int) mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit");
			if($blockSize < 0) $blockSize = 25;

			$splitterName = (string) mainConfiguration::getInstance()->get("modules", "exchance.commerceML.splitter");
			if(!trim(strlen($splitterName))) $splitterName = "commerceML2";

			$splitter = umiImportSplitter::get($splitterName);
			$splitter->load($file_path, $blockSize, $import_offset);
			$doc = $splitter->getDocument();
			$xml = $splitter->translate($doc);

			$oldIgnoreSiteMap =  umiHierarchy::$ignoreSiteMap;
			umiHierarchy::$ignoreSiteMap = true;

			$importer = new xmlImporter();
			$importer->loadXmlString($xml);
			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->setRenameFiles($splitter->getRenameFiles());
			$importer->execute();

			umiHierarchy::$ignoreSiteMap = $oldIgnoreSiteMap;

			$_SESSION['1c_import_offset'] = $splitter->getOffset();
			if ($splitter->getIsComplete()) {
				$_SESSION['1c_import_offset'] = 0;
				return "success\nComplete. Imported elements: " . $splitter->getOffset();
			}

			return "progress\nImported elements: " . $splitter->getOffset();
		}

		protected function exportOrders() {
			$exporter = umiExporter::get("ordersCommerceML");
			$exporter->setOutputBuffer();
			$result = $exporter->export(array());
			return $result;
		}

		protected function markExportedOrders() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('need_export')->equals(1);
			$orders = $sel->result;
			foreach ($orders as $order) {
				$order->need_export = 0;
			}

			return "success";
		}

		protected function importOrders() {
			self::saveIncomingFile();
			$file_name = getRequest('filename');
			$file_path = "./sys-temp/1c_import/" . $file_name;

			if (!is_file($file_path)) return "failure\nFile $file_path not exists.";

			$splitterName = (string) mainConfiguration::getInstance()->get("modules", "exchange.commerceML.splitter");
			if(!trim(strlen($splitterName))) $splitterName = "commerceML2";

			$splitter = umiImportSplitter::get($splitterName);
			$splitter->load($file_path);
			$doc = $splitter->getDocument();
			$xml = $splitter->translate($doc);

			$importer = new xmlImporter();
			$importer->loadXmlString($xml);
			$importer->setIgnoreParentGroups($splitter->ignoreParentGroups);
			$importer->setAutoGuideCreation($splitter->autoGuideCreation);
			$importer->execute();


			return "success";
		}


		public function auto() {

			$timeOut = (int) mainConfiguration::getInstance()->get("modules", "exchange.commerceML.timeout");
			if ($timeOut < 0) $timeOut = 0;

			sleep($timeOut);

			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset('utf-8');
			$buffer->contentType('text/plain');

			$type = getRequest("type");
			$mode = getRequest("mode");

			if (!permissionsCollection::getInstance()->isSv()) {
				$buffer->push("failure\nNot authorized as supervisor.");
				$buffer->end();
				exit();
			}

			switch($type . "-" . $mode) {
				case "catalog-checkauth":
					// clear temp
					removeDirectory("./sys-temp/1c_import/");
				case "sale-checkauth": {
					$buffer->push("success\nPHPSESSID\n" . session_id());
				} break;
				case "catalog-init":
				case "sale-init": {
					removeDirectory("./sys-temp/1c_import/");
					$buffer->push("zip=no\nfile_limit=102400");
				} break;
				case "catalog-file": {
					$buffer->push(self::saveIncomingFile());
				} break;
				case "catalog-import" : {
					$buffer->push(self::importCommerceML());
				} break;

				case "sale-query" : {
					$buffer->push(self::exportOrders());
				} break;

				case "sale-success" : {
					$buffer->push(self::markExportedOrders());
				} break;

				case "sale-file" : {
					$buffer->push(self::importOrders());
				} break;

				default:
					$buffer->push("failure\nUnknown import type ($type) or mode ($mode).");
			}

			$buffer->end();
		}

	}

?>