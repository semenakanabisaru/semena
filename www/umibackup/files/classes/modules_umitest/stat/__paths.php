<?php
	abstract class __stat_paths extends baseModuleAdmin {
		public function entryPoints() {
			//
			$sReturnMode = getRequest('param0');

			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__;
			$thisUrlTail = '';

			
			
			

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('entryPoints');
			$report = $factory->get('entryPoints');

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
					<statistics><report name="entryPoints" title="Точки входа" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Страница" />
						<column field="cnt" title="Заходов абс." />
						<column field="rel" title="Заходов отн." valueSuffix="%" />
						<column field="ref" title="Источники" uriField="refURI" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
					foreach($result['all'] as $info) {
						$iAbs = $info['abs']; $fRel = $info['rel'];
						$iHoveredAbs += $iAbs;
						$page_uri = $info['uri'];
						$page_title = ''; 
						$page_id = intval($info['id']);
						//	
						if ($element_id = umiHierarchy::getInstance()->getElement($page_id)) {
						} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
						} elseif( $page_uri == "/") {
							$element_id = umiHierarchy::getInstance()->getDefaultElementId();
						}
						if($element = umiHierarchy::getInstance()->getElement($element_id)) {
							$page_title = $element->getName();
						}
						if (!strlen($page_title)) $page_title = $info['uri'];
						//
						$attr_page_title = htmlspecialchars($page_title);
						$attr_uri= htmlspecialchars($thisMdlUrl."paths/?nextpath=".$page_id);
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
							$sAttrs .= 'ref="[Двойной щелчок для просмотра]" ';
							$sAttrs .= 'refURI="'.htmlspecialchars($thisMdlUrl."refererByEntry/".$page_id).'" ';
							$sAnswer .= $sAttrs;
						$sAnswer .= "/>\n";
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
            $params['ReportEntryPoints']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}

		public function exitPoints() {
			//
			$sReturnMode = getRequest('param0');

			$this->updateFilter();

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__;
			$thisUrlTail = '';	

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('exitPoints');
			$report = $factory->get('exitPoints');

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
					<statistics><report name="exitPoints" title="Точки выхода" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Страница" prefix="" valueSuffix="" datatipField="uri"  />
						<column field="cnt" title="Выходов абс." prefix="" valueSuffix="" />
						<column field="rel" title="Выходов отн." prefix="" valueSuffix="%" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
					foreach($result['all'] as $info) {
						$iAbs = $info['abs']; $fRel = $info['rel'];
						$iHoveredAbs += $iAbs;
						$page_uri = $info['uri'];
						$page_title = ''; 
						$page_id = (isset($info['id'])) ? intval($info['id']) : false;
						//	
						if ($element_id = umiHierarchy::getInstance()->getElement($page_id)) {
						} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
						} elseif( $page_uri == "/") {
							$element_id = umiHierarchy::getInstance()->getDefaultElementId();
						}
						if($element = umiHierarchy::getInstance()->getElement($element_id)) {
							$page_title = $element->getName();
						}
						if (!strlen($page_title)) $page_title = $info['uri'];
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
				$sAnswer .= "</report></statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";

			} 
            $params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportExitPoints']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}

		public function paths() {
			$sParamPath = getRequest('nextpath');
			$sReturnMode = getRequest('param0'); // !!!

			

			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl = $thisMdlUrl.__FUNCTION__;
			$thisUrlTail = '';		

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('paths');
			$report = $factory->get('paths');

			$report->setParams(array('path'=>$sParamPath));
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit($this->items_per_page);
			

			$report->setDomain($this->domain); $report->setUser($this->user);

			//$result = $report->get();

			//
			if ($sReturnMode === 'xml') {
				$result = $report->get();

				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistics><report name="paths" title="Пути" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Страница" prefix="" valueSuffix=""  />
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
					foreach($result['detail'] as $info) {
						$iAbs = $info['abs']; $fRel = $info['rel'];
						$iHoveredAbs += $iAbs;
						$page_uri = $info['uri'];
						$page_title = ''; 
						$page_id = intval($info['id']);
						//	
						if ($element_id = umiHierarchy::getInstance()->getElement($page_id)) {
						} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
						} elseif( $page_uri == "/") {
							$element_id = umiHierarchy::getInstance()->getDefaultElementId();
						}
						if($element = umiHierarchy::getInstance()->getElement($element_id)) {
							$page_title = $element->getName();
						}
						if (!strlen($page_title)) $page_title = $info['uri'];
						//
						$attr_page_title = htmlspecialchars($page_title);
						if (intval($page_id)) {
							$attr_uri= htmlspecialchars($thisMdlUrl."paths/?nextpath=".$sParamPath."/".$page_id);
						} else {
							$attr_uri="";
						}
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
				$sAnswer .= "</report></statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";

			} 
            $params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportPaths']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}
		public function refererByEntry() {			
			$sReturnMode = getRequest('param1');
			$page_id   = (int) $_REQUEST['param0'];			
			
			$thisHost  = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang  = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
			$thisUrl   = $thisMdlUrl.__FUNCTION__."/".$page_id;
			$thisUrlTail = '';
									
			if($sReturnMode == 'xml') {				
				$factory = new statisticFactory(dirname(__FILE__) . '/classes');
				$factory->isValid('refererByEntry');
				$report = $factory->get('refererByEntry');
				$report->setStart($this->from_time);
				$report->setFinish($this->to_time);			
				$report->setParams(Array("page_id" => $page_id));
				$report->setDomain($this->domain); $report->setUser($this->user);
				$aRet = $report->get();				
				$sAnswer = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= '<statistics>					
					<report name="entryReferers" title="Источники для выбраной точки входа" 
					        host="'.$thisHost.'" lang="'.$thisLang.'" timerange_start="'.$this->from_time.'" timerange_finish="'.$this->to_time.'">
					<table>
						<column field="name"  title="Источник"  />
						<column field="count" title="Переходов"  />
					</table>
					<chart type="pie">
						<argument />
						<value field="count" />
						<caption field="name" />
					</chart>					
					<data>';			

				foreach($aRet as $aRow) {
					$sName  = $aRow['name'].$aRow['uri'];
					$sURI   = htmlspecialchars('http://'.$sName);
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
            $params['ReportRefererByEntry']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
		}		
	}
?>