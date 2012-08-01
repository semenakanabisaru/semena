<?php

	class YMLExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("windows-1251");
			$buffer->contentType("text/xml");
			return $buffer;
		}

		public function export($branches) {

		$id = getRequest('param0');
		$dirName = CURRENT_WORKING_DIR . "/sys-temp/yml/";

		if(!file_exists($dirName . $id . 'el')) {
				throw new publicException('<a href="' . getLabel("label-errors-no-information") . '" target="blank">' . getLabel("label-errors-no-information") .'</a>');
		}

		$elementsToExport = unserialize(file_get_contents($dirName . $id . 'el'));
		$xml = $dirName . $id . ".xml";
		if(file_exists($xml)) unlink ($xml);

			file_put_contents($xml, '<?xml version="1.0" encoding="windows-1251"?><!DOCTYPE yml_catalog SYSTEM "shops.dtd"><yml_catalog date="' . date('Y-m-d H:i') . '"><shop>');
			if(file_exists($dirName . 'shop' . $id)) file_put_contents($xml, file_get_contents($dirName . 'shop' . $id), FILE_APPEND);

		if (file_exists($dirName . 'currencies')) file_put_contents($xml, file_get_contents($dirName . 'currencies'), FILE_APPEND);

		if (file_exists($dirName . 'categories' . $id)) {
			file_put_contents($xml, '<categories>', FILE_APPEND);
			$categories = unserialize(file_get_contents($dirName . 'categories' . $id));
			foreach ($categories as $categoryId => $name) {
				file_put_contents($xml, $name, FILE_APPEND);
			}
			file_put_contents($xml, '</categories>', FILE_APPEND);
		}

		file_put_contents($xml, '<offers>', FILE_APPEND);

			foreach($elementsToExport as $fileId){
				$filePath = $dirName . $fileId . '.txt';
				if(is_file($filePath)) {
					file_put_contents($xml, file_get_contents($filePath), FILE_APPEND);
			}
		}


		file_put_contents($xml, '</offers></shop></yml_catalog>', FILE_APPEND);
		return file_get_contents($xml);

		}

	}
?>