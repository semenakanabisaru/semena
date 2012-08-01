<?php

	if (!class_exists("umiDump20Splitter")) include_once(dirname(__FILE__) . "/umiDump20Splitter.php");

	class transferSplitter extends umiDump20Splitter{

		protected function readDataBlock() {

			$doc = parent::readDataBlock();

			if ($doc->getElementsByTagName('domains')->length) {
				$domains = $doc->getElementsByTagName('domains')->item(0);
				if ($domains->getElementsByTagName('domain')->length) {
					$domain = $domains->getElementsByTagName('domain')->item(0);

					$newDomain = false;
					$domainId = false;
					$importId = getRequest('param0');
					if ($importId) {
						$elements = umiObjectsCollection::getInstance()->getObject($importId)->elements;
						if (is_array($elements) && count($elements)) {
							$domainId = $elements[0]->getDomainId();
						}
					}

					if ($domainId) {
						$newDomain = domainsCollection::getInstance()->getDomain($domainId);
					} else {
						$newDomain = domainsCollection::getInstance()->getDefaultDomain();
					}
					if ($newDomain instanceof domain) {
					$newHost = $newDomain->getHost();
					$domain->setAttribute('host', $newHost);
				}
			}
			}

			return $doc;

		}
	}
?>
