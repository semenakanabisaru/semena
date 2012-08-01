<?php
	abstract class __domains_config extends baseModuleAdmin {

		public function domains() {
			$mode = getRequest("param0");

			if($mode == "do") {
				if (!is_demo()) {
					$this->saveEditedList("domains");
				}
				$this->chooseRedirect($this->pre_lang . '/admin/config/domains/');
			}

			$domains = domainsCollection::getInstance()->getList();

			$this->setDataType("list");
			$this->setActionType("modify");

			$data = $this->prepareData($domains, "domains");

			$this->setData($data, sizeof($domains));
			return $this->doData();
		}


		public function domain_mirrows() {
			$domain_id = getRequest('param0');
			$mode = getRequest("param1");

			$regedit = regedit::getInstance();
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			//Domain seo settings edit
			$seo_info = Array();
			$seo_info['string:seo-title'] = $regedit->getVal("//settings/title_prefix/{$lang_id}/{$domain_id}");
			$seo_info['string:seo-keywords'] = $regedit->getVal("//settings/meta_keywords/{$lang_id}/{$domain_id}");
			$seo_info['string:seo-description'] = $regedit->getVal("//settings/meta_description/{$lang_id}/{$domain_id}");
			$seo_info['string:ga-id'] = $regedit->getVal("//settings/ga-id/{$domain_id}");

			$params = Array(
				'seo' => $seo_info
			);


			if($mode == "do") {
				if(!is_demo())  {
					$this->saveEditedList("domain_mirrows");

					$params = $this->expectParams($params);

					$title = $params['seo']['string:seo-title'];
					$keywords = $params['seo']['string:seo-keywords'];
					$description = $params['seo']['string:seo-description'];
					$ga_id = $params['seo']['string:ga-id'];

					$regedit->setVal("//settings/title_prefix/{$lang_id}/{$domain_id}", $title);
					$regedit->setVal("//settings/meta_keywords/{$lang_id}/{$domain_id}", $keywords);
					$regedit->setVal("//settings/meta_description/{$lang_id}/{$domain_id}", $description);
					$regedit->setVal("//settings/ga-id/{$domain_id}", $ga_id);
				}

				$this->chooseRedirect($this->pre_lang . '/admin/config/domain_mirrows/' . $domain_id . '/');
			}

			$domains = domainsCollection::getInstance()->getDomain($domain_id);

			$mirrows = $domains->getMirrowsList();

			$this->setDataType("list");
			$this->setActionType("modify");

			$seoData = $this->prepareData($params, 'settings');
			$mirrorsData = $this->prepareData($mirrows, "domain_mirrows");

			$data = $seoData + $mirrorsData;

			$this->setData($data, sizeof($domains));
			return $this->doData();
		}


		public function domain_mirrow_del() {
			$domain_id = (int) getRequest('param0');
			$domain_mirrow_id = (int) getRequest('param1');

			if(!is_demo())  {
				$domain = domainsCollection::getInstance()->getDomain($domain_id);
				$domain->delMirrow($domain_mirrow_id);
				$domain->commit();
			}

			$this->chooseRedirect($this->pre_lang . "/admin/config/domain_mirrows/{$domain_id}/");
		}

		public function update_sitemap() {

			$domainId = (int) getRequest('param0');
			$domain = domainsCollection::getInstance()->getDomain($domainId);

			$complete = false;
			$elements = array();

			$hierarchy = umiHierarchy::getInstance();

			$dirName = CURRENT_WORKING_DIR . "/sys-temp/sitemap/{$domainId}/";
			if (!is_dir($dirName)) mkdir($dirName, 0777, true);

			$filePath = $dirName . "domain";

			if(!file_exists($filePath)) {
				$elements = array();
				$langsCollection = langsCollection::getInstance();
				$langs = $langsCollection->getList();
				foreach($langs as $lang) {
					$elements = array_merge($elements, $hierarchy->getChildIds(0, false, true, false, $domainId, false, $lang->getId()));
				}
				sort($elements);
				file_put_contents($filePath, serialize($elements));
			}

			$offset = (int) getSession("sitemap_offset_" . $domainId);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") : 25;

			$elements = unserialize(file_get_contents($filePath));

			for ($i = $offset; $i <= $offset + $blockSize -1; $i++) {
				if(!array_key_exists($i, $elements)) {
					$complete = true;
					break;
				}
				$element = $hierarchy->getElement($elements[$i], true, true);
				if($element instanceof umiHierarchyElement) $element->updateSiteMap(true);
			}

			$_SESSION["sitemap_offset_" . $domainId] = $offset + $blockSize;
			if ($complete) {
				unset($_SESSION["sitemap_offset_" . $domainId]);
				unlink($filePath);
			}

			$data = array(
				"attribute:complete" => (int) $complete
			);

			$this->setData($data);
			return $this->doData();

		}


		public function domain_del() {
			$domain_id = (int) getRequest('param0');

			if (!is_demo()) {
				domainsCollection::getInstance()->delDomain($domain_id);
			}
			$this->chooseRedirect($this->pre_lang . '/admin/config/domains/');
		}
	};
?>