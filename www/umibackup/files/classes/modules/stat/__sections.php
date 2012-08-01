<?php
abstract class __stat_sections extends baseModuleAdmin {
	public function sectionHits() {
		$sReturnMode = getRequest('param0');
			
		

		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';

		

		$this->updateFilter();

		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('sectionHits');
		$report = $factory->get('sectionHits');

		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setLimit($this->items_per_page);
		$report->setDomain($this->domain); $report->setUser($this->user);
		


		if ($sReturnMode === 'xml') {
			$result = $report->get();

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="pagesHits" title="Популярность разделов" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Раздел" prefix="" valueSuffix="" datatipField="tip" />
					<column field="count" title="Запросов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Запросов отн." prefix="" valueSuffix="%" />
				</table>                
				<chart type="pie">
					<argument />
					<value field="count" />
					<caption field="name" />
				</chart>
				<data>
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['section'];
					$page_title = ''; 
					//    
					if ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
					} elseif ( $page_uri == "/") {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}
					if($element = umiHierarchy::getInstance()->getElement($element_id)) {
						$page_title = $element->getName();
					}
					if (!strlen($page_title)) $page_title = $info['section'];
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars('/'.$thisLang.'/admin/stat/sectionHitsIncluded/'.$info['section']);
					$attr_tip= htmlspecialchars('/'.(($info['section']!='index')?$info['section'].'/':''));
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'count="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'tip="'.$attr_tip.'" ';
						$sAttrs .= 'rel="'.round($fRel, 1).'" ';
						foreach ($info as $sName=>$sVal) {
							if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
								$sAttrs .= $sName.'="'.htmlspecialchars($sVal, ENT_COMPAT).'" ';
							}
						}                        
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row count=\"{$iRest}\" name=\"Прочее\" uri=\"\" rel=\"".round($iRest/($iTotalAbs/100), 1)."\" />";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} 
		$params = array();
		$params['filter'] = $this->getFilterPanel();            
		$params['ReportSectionPopularity']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
		$this->setDataType("settings");
		$this->setActionType("view");
		$data = $this->prepareData($params, 'settings');
		$this->setData($data);                        
		return $this->doData();
		
	}
	public function sectionHitsIncluded() {
		$sReturnMode = getRequest('param1'); // !!!
		$sSectionId = getRequest('param0');
		
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__."/".$sSectionId;
		$thisUrlTail = '';

		

		$this->updateFilter();

		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('sectionHits');
		$report = $factory->get('sectionHits');

		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setLimit($this->items_per_page);
		$report->setDomain($this->domain); $report->setUser($this->user);
		


		if ($sReturnMode === 'xml') {
			$result = $report->getIncluded($sSectionId);

			$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
			$iTotalRecs = $result['total'];
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="pagesHits" title="Популярность подразделов" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Раздел" prefix="" valueSuffix="" datatipField="uri" />
					<column field="cnt" title="Показов абс." prefix="" valueSuffix="" />
					<column field="rel" title="Показов отн." prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value field="cnt" />
					<caption field="name" />
				</chart>
				<data lcol="Раздел" rcol="Запросов">
END;
				foreach($result['all'] as $info) {
					$iAbs = $info['abs']; $fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = ''; 
					//    
					if ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
					} elseif ( $page_uri == "/") {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}
					if($element = umiHierarchy::getInstance()->getElement($element_id)) {
						$page_title = $element->getName();
					}
					if (!strlen($page_title)) $page_title = $info['section'];
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'rel="'.round($fRel, 1).'" ';
						foreach ($info as $sName=>$sVal) {
							if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
								$sAttrs .= $sName.'="'.htmlspecialchars($sVal, ENT_COMPAT).'" ';
							}
						}
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"Прочее\" uri=\"\" rel=\"".round($iRest/($iTotalAbs/100), 1)."\" />";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} 
		$params = array();
		$params['filter'] = $this->getFilterPanel();            
		$params['ReportSubsectionsPopularity']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
		$this->setDataType("settings");
		$this->setActionType("view");
		$data = $this->prepareData($params, 'settings');
		$this->setData($data);                        
		return $this->doData();
		
	}
}
?>