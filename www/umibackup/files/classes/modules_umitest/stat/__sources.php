<?php
	abstract class __sources_stat extends baseModuleAdmin {

		public function sources() {
			$this->updateFilter();
			$sReturnMode = getRequest('param0');

			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__;
			$thisUrlTail = '';		

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('sourcesDomains');
			$report = $factory->get('sourcesDomains');

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
					<report name="sourcesDomains" title="Источники переходов" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Ссылающийся домен" valueSuffix="" prefix="" />
						<column field="cnt" title="Переходов абс." valueSuffix="" prefix="" />
						<column field="rel" title="Переходов отн." valueSuffix="%" prefix="" />
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
						$attr_uri = htmlspecialchars($thisMdlUrl."sources_domain/".$info['domain_id']);
						$attr_name= htmlspecialchars($info['name']);
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

			} else {
				$params = array();
				$params['filter'] = $this->getFilterPanel();            
				$params['ReportSources']['flash:report1'] = "url=".$thisUrl."/xml/".$thisUrlTail;                
				$this->setDataType("settings");
				$this->setActionType("view");
				$data = $this->prepareData($params, 'settings');
				$this->setData($data);                        
				return $this->doData();
			}			
		}


		public function sources_domain() {
			$this->updateFilter();
			$sReturnMode = getRequest('param1'); // !!!
			$domain_id = (int) $_REQUEST['param0'];
			
			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__."/".$domain_id;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('sourcesDomainsConcrete');
			$report = $factory->get('sourcesDomainsConcrete');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit($this->items_per_page);
			
			$report->setParams(Array("domain_id" => $domain_id));
			$report->setDomain($this->domain); $report->setUser($this->user);


			if ($sReturnMode === 'xml') {
				$result = $report->get();

				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesDomainsConcrete" title="Источники переходов с выбранного домена" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name"  title="Ссылающаяся страница" valueSuffix="" prefix=""/>
						<column field="cnt"   title="Переходов абс." valueSuffix="" prefix="" />
						<column field="rel"   title="Переходов отн." valueSuffix="%" prefix="" />
						<column field="entry" title="Точки входа" uriField="entryUri" />
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
						$attr_uri = htmlspecialchars("http://".$info['name'].$info['uri']);
						$attr_name = $attr_uri;
						$targ_uri = htmlspecialchars($thisMdlUrl."sources_entry/".$info['id']);
						//
						$fRel = round($iAbs/($iTotalAbs/100), 1);
						$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" entry="[Двойной щелчок для просмотра]" entryUri="{$targ_uri}" />
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
			$params['ReportSourcesDomains']['flash:report1']       = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
		public function sources_entry() {
			$this->updateFilter();
			//
			$sReturnMode = getRequest('param1'); // !!!
			$source_id = (int) $_REQUEST['param0'];			
			
			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__."/".$source_id;
			$thisUrlTail = '';

			if($sReturnMode == 'xml') {				
				$factory = new statisticFactory(dirname(__FILE__) . '/classes');
				$factory->isValid('entryByReferer');
				$report = $factory->get('entryByReferer');
				$report->setStart($this->from_time);
				$report->setFinish($this->to_time);			
				$report->setParams(Array("source_id" => $source_id));
				$report->setDomain($this->domain); $report->setUser($this->user);
				$aRet = $report->get();				
				$sAnswer = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= '<statistics>					
					<report name="sourcesEntry" title="Точки входа для выбранного источника" 
							host="'.$thisHost.'" lang="'.$thisLang.'" timerange_start="'.$this->from_time.'" timerange_finish="'.$this->to_time.'">
					<table>
						<column field="name"  title="Точка входа" datatipField="uri" />
						<column field="count" title="Переходов"  />
					</table>
					<chart type="pie">
						<argument />
						<value field="count" />
						<caption field="name" />
					</chart>					
					<data>';			

				foreach($aRet as $aRow) {
					$sName  = $aRow['section'];
					$sURI   = htmlspecialchars($aRow['uri']);
					$iCount = $aRow['count'];
					$sAnswer .= "<row name=\"".$sName."\" count=\"".$iCount."\" uri=\"".$sURI."\" />";					
				}
				$sAnswer .= "</data></report></statistics>";
				
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";
			}
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportSourcesEntry']['flash:report1']       = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();			
		}

	};
?>