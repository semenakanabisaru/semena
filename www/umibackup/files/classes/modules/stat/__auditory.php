<?php
abstract class __stat_auditory extends baseModuleAdmin {
	public function auditory() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0');       
		
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('auditoryVolume');
		//
		if ($sReturnMode === 'xml1') {
			$factory->isValid('auditoryVolume');
			$report = $factory->get('auditoryVolume');
			//
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setDomain($this->domain); $report->setUser($this->user);
			$report->setLimit(PHP_INT_MAX);
			$report->setOffset(0);
			//
			$result = $report->get();
			$sGroupBy = $result['groupby'];
			//
			$iPeriodAdd = 86400;
			$sCmpFmt    = 'md';
			$sFormat    = 'M-d';
			$sFormatPre = 'от';
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
				$sCmpFmt    = 'md';
				$iPeriodAdd = 86400*7*30;
			} elseif ($sGroupBy === 'week') {
				$sPeriod   = "Неделя";
				$sPeriodOf = "неделям";
				$sCmpFmt   =  "W";
				$iPeriodAdd = 86400*7;
			} elseif($sGroupBy === 'hour') {
				$sPeriod = "Час";
				$sPeriodOf = "часам";
				$sFormat    = 'G';
				$sFormatPre = 'час';
				$iPeriodAdd = 3600;
				$sCmpFmt    = 'H';
			} 
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="auditoryVolume" title="Динамика изменения объема аудитории сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="period" title="{$sPeriod}" units="" prefix="" />
					<column field="count" title="Посетителей" units="" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument/>
					<value field="count" description="Количество посетителей" axisTitle="Количество посетителей" />
					<caption field="period" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->from_time;
				foreach($result['detail'] as $info) {
					$sThisDate = date($sCmpFmt, $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date($sCmpFmt, $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".
									"timestamp=\"".$iOldTimeStamp."\" ".
									"count=\"0\" ".
									"period=\"".$sFormatPre." ".__stat_admin::makeDate($sFormat, $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += $iPeriodAdd;
					}
					$iOldTimeStamp = $info['ts'] + $iPeriodAdd;
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $sFormatPre." ".__stat_admin::makeDate($sFormat, intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'count="'.$iAbs.'" ';
						$sAttrs .= 'period="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'timestamp="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date($sCmpFmt, $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date($sCmpFmt, $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".
								"timestamp=\"".$iOldTimeStamp."\" ".
								"count=\"0\" ".
								"period=\"".$sFormatPre." ".__stat_admin::makeDate($sFormat, $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
					$iOldTimeStamp += $iPeriodAdd;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml2') {
			$factory->isValid('auditoryVolumeGrowth');
			$report = $factory->get('auditoryVolumeGrowth');
			//
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setDomain($this->domain); $report->setUser($this->user);
			$report->setLimit(PHP_INT_MAX);
			$report->setOffset(0);
			//
			$result = $report->get();
			$sGroupBy = $result['groupby'];
			//
			$iPeriodAdd = 86400;
			$sFormat    = 'M-d';
			$sFormatPre = 'от';
			$sCmpFmt    = 'md';
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod    = "Месяц";
				$sPeriodOf  = "месяцам";
				$iPeriodAdd = 86400*7*30;
			} elseif ($sGroupBy === 'week') {
				$sPeriod    = "Неделя";
				$sPeriodOf  = "неделям";                
				$iPeriodAdd = 86400*7;
				$sCmpFmt    = 'W';
			} elseif($sGroupBy === 'hour') {
				$sPeriod    = "Час";
				$sPeriodOf  = "часам";
				$sFormat    = 'G';
				$sFormatPre = 'час';
				$iPeriodAdd = 3600;
				$sCmpFmt    = 'H';
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="auditoryVolumeGrowth" title="Динамика прироста объема аудитории сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="period" title="{$sPeriod}" units="" prefix="" />
					<column field="count" title="Новых посетителей" units="" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument />
					<value field="count" description="Количество новых посетителей" axisTitle="Количество новых посетителей"  />
					<caption field="period" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->from_time;
				foreach($result['detail'] as $info) {
					$sThisDate = date($sCmpFmt, $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date($sCmpFmt, $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".
									"timestamp=\"".$iOldTimeStamp."\" ".
									"count=\"0\" ".
									"period=\"".$sFormatPre." ".__stat_admin::makeDate($sFormat, $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += $iPeriodAdd;
					}
					$iOldTimeStamp = $info['ts'] + $iPeriodAdd;
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $sFormatPre." ".__stat_admin::makeDate($sFormat, intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'count="'.$iAbs.'" ';
						$sAttrs .= 'period="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'timestamp="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date($sCmpFmt, $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date($sCmpFmt, $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".
								"timestamp=\"".$iOldTimeStamp."\" ".
								"count=\"0\" ".
								"period=\"".$sFormatPre." ".__stat_admin::makeDate($sFormat, $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
					$iOldTimeStamp += $iPeriodAdd;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {                       
			$params = array();
			$params['filter']                             = $this->getFilterPanel();
			$params['ReportAuditory']['flash:report1']    = "url=".$thisUrl."/xml1/".$thisUrlTail;
			$params['ReportAuditoryNew']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}

	public function auditoryVolume() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0');
		//
		
		//
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('auditoryVolume');
		$report = $factory->get('auditoryVolume');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		//$report->setLimit($this->items_per_page);
		//
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistic report="auditoryVolume" title="Динамика изменения объема аудитории сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<cols>
					<col name="name" title="{$sPeriod}" units="" prefix="" />
					<col name="cnt" title="Посетителей" units="" prefix="" />
				</cols>
				<reports>
					<report type="xml" title="xml" uri="{$thisUrl}/xml/{$thisUrlTail}" />
					<report type="txt" title="txt" uri="{$thisUrl}/txt/{$thisUrlTail}" />
					<report type="rfccsv" title="csv" uri="{$thisUrl}/rfccsv/{$thisUrlTail}" />
					<report type="mscsv" title="xls" uri="{$thisUrl}/mscsv/{$thisUrlTail}" />
				</reports>
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</details>\n";
			$sAnswer .= "</statistic>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportAuditoryVolume']['flash:report1'] = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}

	public function auditoryVolumeGrowth() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0');
		//
		
		//
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('auditoryVolumeGrowth');
		$report = $factory->get('auditoryVolumeGrowth');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		//$report->setLimit($this->items_per_page);
		//
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistic report="auditoryVolumeGrowth" title="Динамика прироста объема аудитории сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<cols>
					<col name="name" title="{$sPeriod}" units="" prefix="" />
					<col name="cnt" title="Увеличение количества посетителей" units="" prefix="" />
				</cols>
				<reports>
					<report type="xml" title="xml" uri="{$thisUrl}/xml/{$thisUrlTail}" />
					<report type="txt" title="txt" uri="{$thisUrl}/txt/{$thisUrlTail}" />
					<report type="rfccsv" title="csv" uri="{$thisUrl}/rfccsv/{$thisUrlTail}" />
					<report type="mscsv" title="xls" uri="{$thisUrl}/mscsv/{$thisUrlTail}" />
				</reports>
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</details>\n";
			$sAnswer .= "</statistic>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
			$params['filter'] = $this->getFilterPanel();                        
			$params['ReportAuditoryVolumeGrowth']['flash:report1'] = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}

	public function auditoryLoyality() {
		$this->updateFilter();
		/*
		1.Лояльность посетителей (распределение посетителей по количеству посещений на каждого человека) за предыдущие 30 дней.

		a.Временной промежуток можно менять. Гистограмма из абсолютных значений посещений, группировки: 1-2-3-4-5-6-7-8-9-10 11-20 21-30 31-40 41-50 >50 возвратов
		b.Круговая диаграмма (доли приведенных выше групп).
		c.Динамика во времени
		*/
		$sReturnMode = getRequest('param0');
		
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('auditoryLoyality');
		$report = $factory->get('auditoryLoyality');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain);
		$report->setUser($this->user);        
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistic report="auditoryLoyality" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = intval($info['visits_count']);
					if ($page_title > 50) {
						$page_title = "более 50";
					} elseif ($page_title > 40) {
						$page_title = "41 ... 50";
					} elseif ($page_title > 30) {
						$page_title = "31 ... 40";
					} elseif ($page_title > 20) {
						$page_title = "21 ... 30";
					} elseif ($page_title > 10) {
						$page_title = "11 ... 20";
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
			$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach($result['dynamic'] as $info) {
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.round($fAbs, 2).'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml1') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="auditoryLoyality1" title="Количество посетителей с повторными визитами" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Повторных посещений" units="" prefix="" />
					<column field="cnt" title="Посетителей абс." units="" prefix="" />
					<column field="rel" title="Посетителей отн." valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value   field="cnt" description="Количество посетителей с повторными визитами"/>
					<caption field="name"/>
				</chart>
				<data>
END;
				$iTotalAbs = 0;
				foreach($result['detail'] as $info) {
					if (isset($info['cnt'])) $iTotalAbs += intval($info['cnt']);
				}
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = intval($info['visits_count']);
					if ($page_title > 50) {
						$page_title = "более 50";
					} elseif ($page_title > 40) {
						$page_title = "41 ... 50";
					} elseif ($page_title > 30) {
						$page_title = "31 ... 40";
					} elseif ($page_title > 20) {
						$page_title = "21 ... 30";
					} elseif ($page_title > 10) {
						$page_title = "11 ... 20";
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'rel="'.($iTotalAbs ? round($iAbs/($iTotalAbs/100), 1) : '0').'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml2') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="auditoryLoyality2" title="Динамика изменения среднего количества повторных посещений" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" prefix="" />
					<column field="cnt" title="Повторных посещений" units="" prefix="" />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value   field="cnt"  description="Повторных посещений"/>
					<caption field="name"/>
				</chart>                
				<data>
END;
				$iOldTimeStamp = $this->from_time;
				foreach($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date('W', $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".
									"ts=\"".$iOldTimeStamp."\" ".
									"cnt=\"0\" ".
									"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400*7;
					}
					$iOldTimeStamp = $info['ts'] + 86400*7;
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.round($fAbs, 2).'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date('d M', $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".
								"ts=\"".$iOldTimeStamp."\" ".
								"cnt=\"0\" ".
								"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400*7;
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
			$params['ReportAuditoryLoyality']['flash:report1']       = "url=".$thisUrl."/xml1/".$thisUrlTail;
			$params['ReportAuditoryLoyalityCahnge']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}

	public function auditoryActivity() {
		$this->updateFilter();
		//
		/*
		1.Активность посетителей. Средний промежуток между возвратами каждого посетителя за предыдущие 30 дней. Временной промежуток можно менять.

		a.Гистограмма из абсолютных значений промежутков между посещениями, группировки  <1 1-2-3-4-5-6-7-8-9-10 11-20 21-30 31-40 41-50 >50 дней на возврат.
		b.Круговая диаграмма (доли приведенных выше групп).
		c.Динамика во времени
		*/
		$sReturnMode = getRequest('param0');
		//
		
		//
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('auditoryActivity');
		$report = $factory->get('auditoryActivity');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		//$report->setLimit($this->items_per_page);
		//
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics report="auditoryActivity" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['days'];
					switch ($page_title) {
						case "":
							$page_title = "не возвращались";
							break;
						case "0":
						case 0:
							$page_title = "в тот же день";
							break;
						default:
							$page_title = intval($page_title);
							if ($page_title > 50) {
								$page_title = "более 50";
							} elseif ($page_title > 40) {
								$page_title = "41 ... 50";
							} elseif ($page_title > 30) {
								$page_title = "31 ... 40";
							} elseif ($page_title > 20) {
								$page_title = "21 ... 30";
							} elseif ($page_title > 10) {
								$page_title = "11 ... 20";
							}
							break;
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
			$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach($result['dynamic'] as $info) {
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					$attr_cnt = round($fAbs, 2);
					if ($attr_cnt === 0.0) $attr_cnt = "в тот же день";
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$attr_cnt.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml1') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics> 
				<report name="auditoryActivity1" title="Количество дней между возвратами посетителей" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Дней между возвратами" />
					<column field="cnt" title="Посетителей абс." />
					<column field="rel" title="Посетителей отн." valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value field="cnt" description="Количество посетителей" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iTotalAbs = 0;
				foreach($result['detail'] as $info) {
					if (isset($info['cnt'])) $iTotalAbs += intval($info['cnt']);
				}
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['days'];
					switch ($page_title) {
						case "":
							$page_title = "не возвращались";
							break;
						case "0":
						case 0:
							$page_title = "в тот же день";
							break;
						default:
							$page_title = intval($page_title);
							if ($page_title > 50) {
								$page_title = "более 50 дн.";
							} elseif ($page_title > 40) {
								$page_title = "41 ... 50 дн.";
							} elseif ($page_title > 30) {
								$page_title = "31 ... 40 дн.";
							} elseif ($page_title > 20) {
								$page_title = "21 ... 30 дн.";
							} elseif ($page_title > 10) {
								$page_title = "11 ... 20 дн.";
							} else {
								$page_title = $page_title." дн.";
							}
							break;
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'rel="'.($iTotalAbs ? round($iAbs/($iTotalAbs/100), 1) : '0').'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml2') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="auditoryActivity2" title="Динамика изменения среднего промежутка между возвратами посетителей" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" />
					<column field="cnt" title="Промежуток между возвратами" valueSuffix=" дн." />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value field="cnt" description="Количество дней между возвратами" axisTitle="Количество дней между возвратами"  />
					<caption field="name" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->from_time;
				foreach($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date('W', $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".
									"ts=\"".$iOldTimeStamp."\" ".
									"cnt=\"0\" ".
									"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400*7;
					}
					$iOldTimeStamp = $info['ts'] + 86400*7;
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.round($fAbs, 2).'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date('d M', $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".
								"ts=\"".$iOldTimeStamp."\" ".
								"cnt=\"0\" ".
								"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400*7;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report>\n</statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportVisitorReturnDayReturn']['flash:report1']   = "url=".$thisUrl."/xml1/".$thisUrlTail;
			$params['ReportVisitorReturnReturnRange']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}
	public function visitDeep() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0');
		//
		
		//
		
		
		
		//
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('visitDeep');
		$report = $factory->get('visitDeep');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		//$report->setLimit($this->items_per_page);
		//
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistic report="visitDeep" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['level'];
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
			$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach($result['dynamic'] as $info) {
					$fAbs = $info['level_avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$fAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml1') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="visitDeep1" title="Распределение посещений по глубине просмотра сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Глубина" units="страниц" prefix="" />
					<column field="cnt" title="Посещений абс." units="" prefix="" />
					<column field="rel" title="Посещений отн." valueSuffix="%" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument />
					<value field="cnt" description="Количество посещений" axisTitle="Количество посещений" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iTotalAbs = 0;
				foreach ($result['detail'] as $info) {
					if (isset($info['cnt'])) $iTotalAbs += intval($info['cnt']);
				}
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = intval($info['level']);
					if ($page_title > 50) {
						$page_title = "более 50";
					} elseif ($page_title > 40) {
						$page_title = "41 ... 50";
					} elseif ($page_title > 30) {
						$page_title = "31 ... 40";
					} elseif ($page_title > 20) {
						$page_title = "21 ... 30";
					} elseif ($page_title > 10) {
						$page_title = "11 ... 20";
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						if ($iTotalAbs) {
							$sAttrs .= 'rel="'.round($iAbs/($iTotalAbs/100), 1).'" ';
						} else {
							$sAttrs .= 'rel="0" ';
						}
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml2') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
			}
			//
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="visitDeep2" title="Динамика средней глубины просмотра сайта" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" prefix="" />
					<column field="cnt" title="Средняя глубина" valueSuffix=" страниц" prefix="" />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value field="cnt" description="Средняя глубина (страниц)" axisTitle="Страниц" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->from_time;
				foreach($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date('W', $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".
									"ts=\"".$iOldTimeStamp."\" ".
									"cnt=\"0\" ".
									"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400*7;
					}
					$iOldTimeStamp = $info['ts'] + 86400*7;
					$fAbs = $info['level_avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.round($fAbs, 2).'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date('d M', $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".
								"ts=\"".$iOldTimeStamp."\" ".
								"cnt=\"0\" ".
								"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400*7;
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
			$params['ReportVisitDeep']['flash:report1']       = "url=".$thisUrl."/xml1/".$thisUrlTail;
			$params['ReportVisitDeepChange']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}

	}
	public function visitTime() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0');        
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
		$thisUrlTail = '';
		//
		$factory = new statisticFactory(dirname(__FILE__) . '/classes');
		$factory->isValid('visitTime');
		$report = $factory->get('visitTime');
		//
		$report->setStart($this->from_time);
		$report->setFinish($this->to_time);
		$report->setDomain($this->domain); $report->setUser($this->user);
		//$report->setLimit($this->items_per_page);
		//
		//
		$result = $report->get();
		$sGroupBy = $result['groupby'];
		//
		if ($sReturnMode === 'xml') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistic report="visitTime" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['minutes'];
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
			$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach($result['dynamic'] as $info) {
					$fAbs = $info['minutes_avg'];
					$page_uri = '';
					$page_title = $info['ts'];
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<detail ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$fAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml1') {
			$sAnswer = "";
			$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= <<<END
				<statistics>
				<report name="visitTime1" title="Распределение посещений по времени нахождения на сайте" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
				<table>
					<column field="name" title="Продолжительность" units="минут" prefix="" />
					<column field="cnt" title="Посещений абс." units="" prefix="" />
					<column field="rel" title="Посещений отн." valueSuffix="%" prefix="" />
				</table>
				<chart type="pie">
					<argument  />
					<value field="cnt" description="Количество посещений" axisTitle="Количество посещений" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iAbsTotal = 0;
				foreach($result['detail'] as $info) {
					if (isset($info['cnt'])) $iAbsTotal += intval($info['cnt']);
				}
				foreach($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = intval($info['minutes']);
					if ($page_title > 50) {
						$page_title = "более 50";
					} elseif ($page_title > 40) {
						$page_title = "41 ... 50";
					} elseif ($page_title > 30) {
						$page_title = "31 ... 40";
					} elseif ($page_title > 20) {
						$page_title = "21 ... 30";
					} elseif ($page_title > 10) {
						$page_title = "11 ... 20";
					}
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.$iAbs.'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'rel="'.($iAbsTotal ? round($iAbs/($iAbsTotal/100), 1) : '0').'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} elseif ($sReturnMode === 'xml2') {
			$sPeriod = "Период"; $sPeriodOf = "периодам";        
			if ($sGroupBy === 'month') {
				$sPeriod = "Месяц";
				$sPeriodOf = "месяцам";
				$sAxisTitle = "месяцы";
			} elseif ($sGroupBy === 'week') {
				$sPeriod = "Неделя";
				$sPeriodOf = "неделям";
				$sAxisTitle = "недели";
			}
			//
			$sAnswer = "<data>\n";            
				$sort_arr = array();
				foreach($result['dynamic'] AS $uniqid => $row){
					foreach($row AS $key=>$value){
						$sort_arr[$key][$uniqid] = $value;
					}
				} 
				if(isset($sort_arr['ts']) &&  !empty($sort_arr['ts']))
					array_multisort($sort_arr['ts'], SORT_ASC, $result['dynamic']);                
				$iResponseRowCount = 0;
				$iOldTimeStamp = $this->from_time;          
				foreach($result['dynamic'] as $info) {                    
					$iNewTS = strtotime('-7 day', $info['ts']);
					if($iOldTimeStamp > $iNewTS)  $info['ts'] = $iOldTimeStamp;
					$sThisDate = date('W', $info['ts']);                    
					while( ($iOldTimeStamp < $info['ts']) && 
						   (date('W', $iOldTimeStamp) != $sThisDate) ) {
						$attr_page_uri   = '';
						$sAnswer .= "<row ".                                    
									"cnt=\"0\" ".
									"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" ".
									"ts=\"".$iOldTimeStamp."\" />\n";
						$iOldTimeStamp += 86400*7;
						$iResponseRowCount++;
					}                    
					$iOldTimeStamp = $info['ts'] + 86400*7;                    
					$fAbs = $info['minutes_avg'];
					$page_uri = '';
					$page_title = "от ".__stat_admin::makeDate('M-d', intval($info['ts']));
					//
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri= htmlspecialchars($page_uri);
					//
					$sAnswer .= "<row ";
						$sAttrs = '';
						$sAttrs .= 'cnt="'.round($fAbs, 1).'" ';
						$sAttrs .= 'name="'.$attr_page_title.'" ';
						$sAttrs .= 'uri="'.$attr_uri.'" ';
						$sAttrs .= 'ts="'.$info['ts'].'" ';
						$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
					$iResponseRowCount++;
				}                
				$sThisDate = date('d M', $this->to_time+86400);
				while( ($iOldTimeStamp < $this->to_time+86400) && 
					   (date('d M', $iOldTimeStamp) != $sThisDate) ) {
					$attr_page_uri   = htmlspecialchars( '' );
					$sAnswer .= "<row ".                                
								"cnt=\"0\" ".
								"name=\"от ".__stat_admin::makeDate('M-d', $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" ".
								"ts=\"".$iOldTimeStamp."\" />\n";
					$iOldTimeStamp += 86400*7;
					$iResponseRowCount++;
				}
				$sAnswer .= "</data>\n";
			$sAnswer .= "</report></statistics>";
			if($iResponseRowCount > 1)
				$sChartType = "line";
			else
				$sChartType = "column";
			$sAnswerHdr = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswerHdr.= <<<END
				<statistics>
				<report name="visitTime2" title="Динамика средней продолжительности нахождения посетителей на сайте" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="ts" title="{$sPeriod}" showas="date" units="" prefix="" />
					<column field="cnt" title="Средняя продолжительность" units="" prefix="" />
				</table>
				<chart type="{$sChartType}" drawTrendLine="true">
					<argument fiels="ts" axisTitle="{$sAxisTitle}"  />
					<value field="cnt" description="Средняя продолжительность" axisTitle="Минут"  />
					<caption field="name" />
				</chart>                                
END;
			$sAnswer = $sAnswerHdr.$sAnswer;
			//
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		} else {
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportVisitTime']['flash:report1']       = "url=".$thisUrl."/xml1/".$thisUrlTail;
			$params['ReportVisitTimeChange']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
		}
	}
	public function auditoryLocation() {
		$this->updateFilter();
		//
		$sReturnMode = getRequest('param0'); // !!!        
		$curr_page = 0;//(int) isset($_REQUEST['p'])?;
		$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
		$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$thisMdlUrl = '/'.$thisLang.'/admin/stat/';
		$thisUrl = $thisMdlUrl.__FUNCTION__."/";
		$thisUrlTail = '';        
				
				
		if($sReturnMode == 'xml') {                
			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('cityStat');
			$report = $factory->get('cityStat');
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);            
			$report->setDomain($this->domain); $report->setUser($this->user);
			$aRet = $report->get();                            
			$sAnswer = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			$sAnswer .= '<statistics>                    
				<report name="auditoryLocation" title="Распределение аудитории по городам" 
						host="'.$thisHost.'" lang="'.$thisLang.'" timerange_start="'.$this->from_time.'" timerange_finish="'.$this->to_time.'">
				<table>
					<column field="name"  title="Город" />
					<column field="count" title="Количество посетителей"  />
				</table>
				<chart type="pie">
					<argument />
					<value field="count" />
					<caption field="name" />
				</chart>                    
				<data>';            

			foreach($aRet as $aRow) {                
				$sName  = $aRow['location'];                
				$iCount = $aRow['count'];
				$sAnswer .= "<row name=\"".$sName."\" count=\"".$iCount."\" />";                    
			}
			$sAnswer .= "</data></report></statistics>";            
			
			header("Content-type: text/xml; charset=utf-8");
			header("Content-length: ".strlen($sAnswer));
			$this->flush($sAnswer);
			return "";
		}
		if(!(cmsController::getInstance()->getModule("geoip") === false)) {                               
			$params = array();
			$params['filter'] = $this->getFilterPanel();            
			$params['ReportLocation']['flash:report1']       = "url=".$thisUrl."/xml/".$thisUrlTail;            
			$this->setDataType("settings");
			$this->setActionType("view");
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);                        
			return $this->doData();
//END;
		} else {            
			throw new publicAdminException(getLabel('error-no-geoip'));                                    
			return null;
		}        
	}    
}

?>