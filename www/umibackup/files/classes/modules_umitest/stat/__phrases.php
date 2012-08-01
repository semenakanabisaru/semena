<?php
	abstract class __phrases_stat extends baseModuleAdmin {

		public function phrases() {
			$sReturnMode = getRequest('param0');

			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__;
			$thisUrlTail = '';

			

			$this->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('sourcesSEOKeywords');
			$report = $factory->get('sourcesSEOKeywords');

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
					<report name="sourcesSEOKeywords" title="Поисковые фразы" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Поисковая фраза" valueSuffix="" prefix="" />
						<column field="cnt" title="Переходы абс." valueSuffix="" prefix="" />
						<column field="rel" title="Переходы отн." valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
					foreach($result['all'] as $info) {
						$iAbs = $info['cnt'];
						$iHoveredAbs += $iAbs;
						$attr_uri = htmlspecialchars($thisMdlUrl."phrase/".$info['query_id']);
						$attr_name= htmlspecialchars($info['text']);
						//
						$fRel = round($iAbs/($iTotalAbs/100), 1);
						$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
					}
					$iRest = ($iTotalAbs - $iHoveredAbs);
					if ($iRest > 0) {
						$sAnswer .= "<row cnt=\"{$iRest}\" name=\"Прочее\" uri=\"\" rel=\"".round($iRest/($iTotalAbs/100), 1)."\" />";
					}
					$sAnswer .= "</data>\n";
				$sAnswer .= "</report></statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";

			} 
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportPhrases']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}


		public function phrase() {
			$sReturnMode = getRequest('param1'); // !!!
			$query_id = $_REQUEST['param0'];

			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__."/".$query_id;
			$thisUrlTail = '';

			$params = Array();

			

			$this->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('sourcesSEOKeywordsConcrete');
			$report = $factory->get('sourcesSEOKeywordsConcrete');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit($this->items_per_page);
			
			$report->setParams(Array("query_id" => $query_id));
			$report->setDomain($this->domain); $report->setUser($this->user);

			
			if ($sReturnMode === 'xml') {
				$result = $report->get();

				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesSEOKeywordsConcrete" title="Поисковые системы по выбранной фразе" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Поисковая система" valueSuffix="" prefix="" />
						<column field="cnt" title="Переходы абс." units="" prefix="" />
						<column field="rel" title="Переходы отн." valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					
					<data>
END;
					foreach($result['all'] as $info) {
						$iAbs = $info['cnt'];
						$iHoveredAbs += $iAbs;
						$attr_uri = '';
						$attr_name = htmlspecialchars($info['name']);
						//
						$fRel = round($iAbs/($iTotalAbs/100), 1);
						$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
					}
					$iRest = ($iTotalAbs - $iHoveredAbs);
					if ($iRest > 0) {
						$sAnswer .= "<row cnt=\"{$iRest}\" name=\"Прочее\" uri=\"\" rel=\"".round($iRest/($iTotalAbs/100), 1)."\" />";
					}
					$sAnswer .= "</data>\n";
				$sAnswer .= "</report></statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";

			} 
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportPhrase']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	};
?>