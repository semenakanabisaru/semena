<?php
	abstract class __visitors_stat extends baseModuleAdmin {

		public function visitors() {
			$this->updateFilter();
			//
			$sReturnMode = getRequest('param0');
			//
						
			//
			$thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
			$thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
			$thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
			$thisUrlTail = '';
			//
			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			//
			if ($sReturnMode === 'xml1') {
				$factory->isValid('visitersCommon');
				$report = $factory->get('visitersCommon');
				//
				$report->setStart($this->from_time);
				$report->setFinish($this->to_time);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$report->setDomain($this->domain); $report->setUser($this->user);
				//
				$result = $report->get();
				//
				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="visitCommon" title="Динамика захода посетителей за выбранный период по дням" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="День" valueSuffix="" prefix="" />
						<column field="cnt" title="Сессий" valueSuffix="" prefix="" />
					</table>
					<chart type="column" drawTrendLine="true">
						<argument />
						<value field="cnt" description="Количество сессий" axisTitle="Количество сессий" />
						<caption field="name" />
					</chart>                    
					<data>
END;
					$iOldTimeStamp = $this->from_time;
					foreach($result['detail'] as $info) {
						$sThisDate = date('d M', $info['ts']);                    
						while( ($iOldTimeStamp < $info['ts']) && 
						   (date('d M', $iOldTimeStamp) != $sThisDate) ) {
							$attr_page_uri   = '';
							$sAnswer .= "<row ".
									"ts=\"".$iOldTimeStamp."\" ".
									"cnt=\"0\" ".
									"name=\"".__stat_admin::makeDate('d M', $iOldTimeStamp)."\" ".
									"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
							$iOldTimeStamp += 86400;
						}
						$iOldTimeStamp = $info['ts'] + 86400;
						$iAbs = $info['cnt'];
						$iHoveredAbs += $iAbs;
						$page_uri = '/'.$thisLang.'/admin/stat/visitors_by_date/'.$info['ts'];
						$page_title = date('d M', $info['ts']);
						//
						$attr_page_title = htmlspecialchars($page_title);
						$attr_uri= htmlspecialchars($page_uri);
						//
						$sAnswer .= "<row ";
							$sAttrs = '';
							$sAttrs .= 'cnt="'.$iAbs.'" ';
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
								"name=\"".__stat_admin::makeDate('d M', $iOldTimeStamp)."\" ".
								"uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400;
					}
					$iRest = ($iTotalAbs - $iHoveredAbs);
					$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";
			} elseif ($sReturnMode === 'xml2') {
				$factory->isValid('visitersCommonHours');
				$report = $factory->get('visitersCommonHours');
				//
				$report->setStart($this->from_time);
				$report->setFinish($this->to_time);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$report->setDomain($this->domain); $report->setUser($this->user);
				//
				$result = $report->get();
				//
				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="visitCommonHours" title="Динамика захода посетителей за выбранный период по часам суток" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<table>
						<column field="name" title="Час" valueSuffix="" prefix="" />
						<column field="cnt" title="Сессий" valueSuffix="" prefix="" />
					</table>
					<chart type="line" drawTrendLine="true">
						<argument />
						<value field="cnt" description="Количество сессий" axisTitle="Количество сессий" />
						<caption field="name" />
					</chart>                    
					<data>
END;
					$iHour = 0;
					for ($iHour = 0; $iHour < 24; $iHour++) {
						if (isset($result['detail'][$iHour])) {
							$info = $result['detail'][$iHour];
						} else {
							$info = array('ts'=>mktime($iHour), 'cnt'=>0);
						}
						//
						$iAbs = $info['cnt'];
						$iHoveredAbs += $iAbs;
						$page_uri = '';
						$iTtlHour = intval(date('G', $info['ts']));
						$page_title = $iTtlHour."..".($iTtlHour+1);
						//
						$attr_page_title = htmlspecialchars($page_title);
						$attr_uri= htmlspecialchars($page_uri);
						//
						$sAnswer .= "<row ";
							$sAttrs = '';
							$sAttrs .= 'cnt="'.$iAbs.'" ';
							$sAttrs .= 'name="'.$attr_page_title.'" ';
							$sAttrs .= 'uri="'.$attr_uri.'" ';
							$sAttrs .= 'ts="'.$info['ts'].'" ';
							$sAttrs .= 'hour="'.$iTtlHour.'" ';
							$sAnswer .= $sAttrs;
						$sAnswer .= "/>\n";
					}
					$iRest = ($iTotalAbs - $iHoveredAbs);
					if ($iRest > 0) {
						$sAnswer .= "<row cnt=\"{$iRest}\" name=\"Прочее\" uri=\"\" />";
					}
					$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";
				//
				header("Content-type: text/xml; charset=utf-8");
				header("Content-length: ".strlen($sAnswer));
				$this->flush($sAnswer);
				return "";
			} else {
				$factory->isValid('visitersCommon');
				$report = $factory->get('visitersCommon');
				//
				$report->setStart($this->from_time);
				$report->setFinish($this->to_time);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				//
				$result = $report->get();
				//
				$params = array();
				$params['filter'] = $this->getFilterPanel();
				//$params['total']['int:sessions_routine']      = strlen($result['avg']['routine'])?$result['avg']['routine']:"-";
				//$params['total']['int:sessions_weekend']      = strlen($result['avg']['weekend'])?$result['avg']['weekend']:"-";
				$params['ReportSessionsByDays']['flash:report1']  = "url=".$thisUrl."/xml1/".$thisUrlTail;
				$params['ReportSessionsByHours']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
				$this->setDataType("settings");
				$this->setActionType("view");
				$data = $this->prepareData($params, 'settings');
				$this->setData($data);                          
				//
				return $this->doData();
			}
		}

		public function visitors_by_date() {
			$this->updateFilter();

			$ts = (int) $_REQUEST['param0'];

			

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('visitsByDate');
			$report = $factory->get('visitsByDate');

			$report->setFinish(strtotime('+1 day', $ts));
			$report->setLimit($this->items_per_page);
			
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();
			
			$rows = "";

			$c = $curr_page*$this->items_per_page;

			foreach($result['all'] as $info) {
				$user_id = $info['user_id'];

				$factory->isValid('userStat');
				$report = $factory->get('userStat');

				$report->setParams($info);
				$user_info = $report->get();

				$first_visit = ($user_info['first_visit']) ? date("Y-m-d | H:i", $user_info['first_visit']) : "-";
				$last_visit = ($user_info['last_visit']) ? date("Y-m-d | H:i", $user_info['last_visit']) : "-";



				++$c;

				$rows .= <<<ROW

<row>
	<col>{$c}</col>

	<col>
		<a href="%pre_lang%/admin/stat/visitor/{$user_id}/"><![CDATA[{$user_info['browser']} ({$user_info['os']})]]></a>
	</col>

	<col>
		<![CDATA[{$first_visit}]]>
	</col>

	<col>
		<![CDATA[{$last_visit}]]>
	</col>
</row>


ROW;
			}


			$params['rows'] = $rows;
			//$params['pages'] = $this->generateNumPage($result['total'], $this->items_per_page, $curr_page);
			
			if(!(cmsController::getInstance()->getModule("geoip") === false))
				$params['city_report'] = '[<a href="%pre_lang%/admin/stat/auditoryLocation/"><b><![CDATA[Города]]></b></a>]&nbsp;';

			return $this->parse_form("visitors_by_date", $params);
		}

		public function visitor() {
			$this->updateFilter();
			$params = Array();
			
			$user_id = $_REQUEST['param0'];

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('userStat');
			$report = $factory->get('userStat');
			$fromTS = $this->ts;
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
//            $report->setFinish($fromTS);
			$report->setLimit($this->per_page);
			$report->setParams(Array("user_id" => $user_id));
			$report->setDomain($this->domain); $report->setUser($this->user);
			$user_info = $report->get();


			$params['first_visit'] = ($user_info['first_visit']) ? date("Y-m-d | H:i", $user_info['first_visit']) : "-";
			$params['last_visit'] = ($user_info['last_visit']) ? date("Y-m-d | H:i", $user_info['last_visit']) : "-";
			$params['visit_count'] = $user_info['visit_count'];
			$params['os'] = $user_info['os'];
			$params['browser'] = $user_info['browser'];
			$params['js_version'] = $user_info['js_version'];

			$params['source_link'] = $user_info['source']['name'];
			$params['last_source_link'] = $user_info['last_source']['name'];
			
			$login_id = $user_info['login'];
			$params['user_info'] = ($login_id) ? "%users get_user_info('{$login_id}', '%login% - %last_name% %first_name% %father_name%')%" : "Посетитель не зарегистрировался на сайте";

			$tags = Array();
			foreach($user_info['labels']['top'] as $label) {
				$tags[] = $label['name'] . " (" . $label['cnt'] . ")";
			}

			$rows = "";
			$c = 0;
			foreach($user_info['last_path'] as $uri) {
				++$c;
				$page_uri = $uri['uri'];
				$page_uri = str_replace("&", "&amp;", $page_uri);

				if($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
				} else if($page_uri == "/") {
					$element_id = umiHierarchy::getInstance()->getDefaultElementId();
				}

				if($element = umiHierarchy::getInstance()->getElement($element_id)) {
					$page_title = $element->getName();
				}


				$rows .= <<<END
	<row>
		<col>
			$c.
		</col>

		<col>
			<a href="$page_uri"><![CDATA[{$page_title}]]></a>
		</col>


		<col>
			<![CDATA[$page_uri]]>
		</col>
	</row>

END;
			}


			$params['tags'] = implode(", ", $tags);
			$params['rows'] = $rows;
			
			if(!(cmsController::getInstance()->getModule("geoip") === false))
				$params['city_report'] = '[<a href="%pre_lang%/admin/stat/auditoryLocation/"><b><![CDATA[Города]]></b></a>]&nbsp;';

			return $this->parse_form("visitor", $params);
		}

		public function visitersCommonHours() {
			$this->updateFilter();
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
			$factory->isValid('visitersCommonHours');
			$report = $factory->get('visitersCommonHours');
			//
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setDomain($this->domain); $report->setUser($this->user);
			//$report->setLimit($this->items_per_page);
			//
			//
			//
			if ($sReturnMode === 'xml') {
				$result = $report->get();

				$iHoveredAbs = 0; $iTotalAbs = $result['summ'];
				$iTotalRecs = $result['total'];
				$sAnswer = "";
				$sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
				$sAnswer .= <<<END
					<statistic report="visitCommonHours" title="Динамика захода посетителей за выбранный период по часам суток" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
					<cols>
						<col name="name" title="Час" valueSuffix="" prefix="" />
						<col name="cnt" title="Посетителей" valueSuffix="" prefix="" />
					</cols>
					<reports>
						<report type="xml" title="xml" uri="{$thisUrl}/xml/{$thisUrlTail}" />
						<report type="txt" title="txt" uri="{$thisUrl}/txt/{$thisUrlTail}" />
						<report type="rfccsv" title="csv" uri="{$thisUrl}/rfccsv/{$thisUrlTail}" />
						<report type="mscsv" title="xls" uri="{$thisUrl}/mscsv/{$thisUrlTail}" />
					</reports>
					<details>
END;
					$iHour = 0;
					for ($iHour = 0; $iHour < 24; $iHour++) {
						if (isset($result['detail'][$iHour])) {
							$info = $result['detail'][$iHour];
						} else {
							$info = array('ts'=>mktime($iHour), 'cnt'=>0);
						}
						//
						$iAbs = $info['cnt'];
						$iHoveredAbs += $iAbs;
						$page_uri = '';
						$iTtlHour = intval(date('G', $info['ts']));
						$page_title = $iTtlHour."..".($iTtlHour+1);
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
							$sAttrs .= 'hour="'.$iTtlHour.'" ';
							$sAnswer .= $sAttrs;
						$sAnswer .= "/>\n";
					}
					$iRest = ($iTotalAbs - $iHoveredAbs);
					if ($iRest > 0) {
						$sAnswer .= "<detail cnt=\"{$iRest}\" name=\"Прочее\" uri=\"\" />";
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
				$params['swf'] = <<<END
		<statgraph w="100%" h="530" id="linear" align="middle" src="/images/cms/stat/line.swf" quality="high" bgcolor="#ffffff">
			<flashvar disable-output-escaping="yes"><![CDATA[xmlswf=/images/cms/stat/xml.swf&xmlini=/images/cms/stat/ini/ini.xml&xmlstat={$thisUrl}/xml/{$thisUrlTail}]]></flashvar>
		</statgraph>
END;
				$params['stat_param'] = $this->returnParamPanel();
				
				if(!(cmsController::getInstance()->getModule("geoip") === false))
				$params['city_report'] = '[<a href="%pre_lang%/admin/stat/auditoryLocation/"><b><![CDATA[Города]]></b></a>]&nbsp;';
				
				//
				return $this->parse_form("visitersCommonHours", $params);
			}
		}
	};
?>