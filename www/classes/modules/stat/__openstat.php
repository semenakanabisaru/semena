<?php

abstract class __stat_openstat extends baseModuleAdmin {
	/*
	Openstat предназначен для проведения post-click анализа, а именно, для точной идентификации переходов на сайт Рекламодателя по каждому рекламному Сообщению (текстовому объявлению, баннеру,..).
	Openstat имеет одинаковый формат для каждого рекламного Ресурса и содержит данные: о наименовании Ресурса, идентификаторе рекламной кампании, идентификаторе рекламного Сообщения, идентификаторе места размещения рекламы.


	Формат метки выглядит следующим образом:

	* http://www.site.ru/?_openstat=service-name;campaign-id;ad-id;source-id

	Где:

    	* www.site.ru — адрес сайта или раздела сайта рекламодателя;
	* _openstat — идентификатор универсальной метки
	* service-name  — название рекламного ресурса (например: begun, direct.yandex.ru,subscribe, mail.ru, bannerbank);
	* campaign-id — идентификатор рекламной кампании (например, «1228», a8765b8);
	* ad-id — идентификатор рекламного объявления (например, «b123», 991b8);
	* source-id — идентификатор площадки, раздела, страницы, места на странице, на котором было показано соответствующее рекламное объявление (например, site166212, mail.ru, yandex.ru:upper-left-corner).


	Параметры service-name и ad-id являются обязательными.

	// =====================================================================
	Итого основные сущности:
	- рекламный Ресурс (Service)
	- рекламное Объявление (Ad)
	- рекламная Кампания (Campaign)
	- рекламное Место Объявления - площадка, раздел, страница, место на странице - (Source)

	*/

	public function openstatCampaigns() {
		/*
		рекламные Кампании

		возможные переходы:
		openstatServicesByCampaign
		openstatAdsByCampaign
		*/
		//
		$sReturnMode = getRequest('param0');
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatCampaigns');
		$report = $factory->get('openstatCampaigns');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//
		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatCampaigns" title="Все рекламные кампании" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Имя кампании" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['campaign_id'];
					// TODO : select one !!!
					$sUri = '/'.$thisLang.'/admin/stat/openstatServicesByCampaign/'.$iId;
					//$sUri = 'http://'.$thisHost.'/'.$thisLang.'/admin/stat/openstatAdsByCampaign/'.$iId;
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatCampaigns']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}
	public function openstatCampaignsBySource() {
		//
		$sSourceId = getRequest('param0');
		$sReturnMode = getRequest('param1'); // !!!
		//
		//
		//
		$report->setParams(array('source_id'=>$sSourceId));
	}

	// =====================================================================

	public function openstatServices() {
		/*
		рекламные Ресурсы

		возможные переходы:
		openstatAdsByService
		*/
		//
		$sReturnMode = getRequest('param0');
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatServices');
		$report = $factory->get('openstatServices');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//

		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatServices" title="Все рекламные ресурсы" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламный ресурс" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/'.$thisLang.'/admin/stat/openstatAdsByService/'.$iId;
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatServices']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}
	public function openstatServicesByCampaign() {
		//
		$sCampaignId = getRequest('param0');
		$sReturnMode = getRequest('param1'); // !!!
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatServices');
		$report = $factory->get('openstatServices');
		//
		$report->setParams(array('campaign_id'=>intval($sCampaignId)));
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//

		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatServicesByCampaign" title="Рекламные ресурсы кампании" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламный ресурс" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/'.$thisLang.'/admin/stat/openstatAdsByService/'.$iId;
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatServicesByCampaig']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}
	public function openstatServicesBySource() {
		//
		$sSourceId = getRequest('param0');
		$sReturnMode = getRequest('param1'); // !!!
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatServices');
		$report = $factory->get('openstatServices');
		//
		$report->setParams(array('source_id'=>intval($sSourceId)));
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//
		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();


			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatServicesBySource" title="Рекламные ресурсы место объявления" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламный ресурс" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/'.$thisLang.'/admin/stat/openstatAdsByService/'.$iId;
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatServicesBySource']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}

	// =====================================================================

	public function openstatSources() {
		/*
		рекламные Места Объявлений

		возможные переходы:
		openstatCampaignsBySource
		openstatServicesBySource
		openstatAdsBySource
		*/
		//
		$sReturnMode = getRequest('param0');
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatSources');
		$report = $factory->get('openstatSources');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatSources" title="Все рекламные места объявлений" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламное место" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['source_id'];
					// TODO : select one !!!
					//$sUri = 'http://'.$thisHost.'/'.$thisLang.'/admin/stat/openstatCampaignsBySource/'.$iId;
					$sUri = '/'.$thisLang.'/admin/stat/openstatServicesBySource/'.$iId;
					//$sUri = 'http://'.$thisHost.'/'.$thisLang.'/admin/stat/openstatAdsBySource/'.$iId;
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatSources']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}

	// =====================================================================

	public function openstatAds() {
		/*
		рекламные Объявления

		возможные переходы:
		нет
		*/
		//
		$sReturnMode = getRequest('param0');
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatAds');
		$report = $factory->get('openstatAds');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//
		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatAds" title="Все рекламные объявления" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламное объявление" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$sUri = "";
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatAds']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}
	public function openstatAdsByCampaign() {
		//
	}
	public function openstatAdsByService() {
		//
		$iServiceId = intval(getRequest('param0'));
		$sReturnMode = getRequest('param1'); // !!!
		
		//
        $this->updateFilter();
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('openstatAds');
		$report = $factory->get('openstatAds');
		//
		$report->setParams(array('service_id'=>$iServiceId));
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		$report->setLimit($this->items_per_page);
		
		//
		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics><report name="openstatAdsByService" title="Рекламные объявления ресурса" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Рекламное объявление" prefix="" valueSuffix="" />
					<column field="cnt" title="Переходов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Переходов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$sUri = "";
					//
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri= htmlspecialchars($sUri);
					//
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest/($iTotalAbs/100), 1);
					$sAnswer .= <<<END
						<row name="Прочее" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportOpenstatAdsByService']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
	}
	public function openstatAdsBySource() {
		//
	}

	// =====================================================================
}

?>