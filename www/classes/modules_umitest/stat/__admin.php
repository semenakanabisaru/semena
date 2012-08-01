<?php
	abstract class __stat_admin extends baseModuleAdmin {

        public function config() {
            $regedit = regedit::getInstance();
			$params = array(
				'config' => array(
					'boolean:enabled' 		=> null,
					"string:yandex-token" 	=> null
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/stat/collect", $params['config']['boolean:enabled']);
				$regedit->setVar("//modules/stat/yandex-token", $params["config"]["string:yandex-token"]);
				$this->chooseRedirect();
			}

			$params['config']['boolean:enabled'] = (boolean) $regedit->getVal("//modules/stat/collect");
			$params["config"]["string:yandex-token"] = (string) $regedit->getVal("//modules/stat/yandex-token");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
        }
        public function clear() {
            $mode      = (string) getRequest('param0');
            if($mode == 'do') {
                $aTables = array('cms_stat_domains', 'cms_stat_entry_points', 'cms_stat_entry_points_events', 'cms_stat_events',
                                 'cms_stat_events_collected', 'cms_stat_events_rel', 'cms_stat_events_urls', 'cms_stat_finders',
                                 'cms_stat_hits', 'cms_stat_holidays', 'cms_stat_pages', 'cms_stat_paths', 'cms_stat_phrases',
                                 'cms_stat_sites', 'cms_stat_sites_groups', 'cms_stat_sources', 'cms_stat_sources_coupon',
                                 'cms_stat_sources_coupon_events', 'cms_stat_sources_openstat', 'cms_stat_sources_openstat_ad',
                                 'cms_stat_sources_openstat_campaign', 'cms_stat_sources_openstat_service',
                                 'cms_stat_sources_openstat_source', 'cms_stat_sources_pr', 'cms_stat_sources_pr_events',
                                 'cms_stat_sources_pr_sites', 'cms_stat_sources_search', 'cms_stat_sources_search_queries',
                                 'cms_stat_sources_sites', 'cms_stat_sources_sites_domains', 'cms_stat_sources_ticket', 'cms_stat_users');
                foreach($aTables as $sTable) l_mysql_query('TRUNCATE `'.$sTable.'`');
                $this->chooseRedirect();
            }
            $params    = array('clear' => array('button:clear' => null));
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
        }

		public function total() {
            $this->updateFilter();
            $params = array();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');

            $params['tagss']['tags:tags_cloud'] = $this->tags_cloud();

			$factory->isValid('visitersCommon');
			$report = $factory->get('visitersCommon');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setDomain($this->domain);
            $report->setUser($this->user);


			$result = $report->get();

			$params['visits']['int:routine'] = (int) $result['avg']['routine'];
			$params['visits']['int:weekend'] = (int) $result['avg']['weekend'];
            $params['visits']['int:sum']     = (int) $result['summ'];

            $factory->isValid('hostsCommon');
            $report = $factory->get('hostsCommon');

            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);

            $result = $report->get();

            $params['visits']['int:hosts_total'] = (int) $result['summ'];

            $factory->isValid('visitCommon');
            $report = $factory->get('visitCommon');

            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);

            $result = $report->get();

            $params['visits']['int:hits_total'] = (int) $result['summ'];


			$factory->isValid('visitTime');
			$report = $factory->get('visitTime');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$visit_time = array_pop($result['dynamic']);
			$params['visits']['int:time'] = round($visit_time['minutes_avg'], 2);

			$factory->isValid('visitDeep');
			$report = $factory->get('visitDeep');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$visit_deep = array_pop($result['dynamic']);
			$params['visits']['int:deep'] = round($visit_deep['level_avg'], 2);


			$factory->isValid('sourcesTop');
			$report = $factory->get('sourcesTop');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			if (isset($result[0]['cnt'])) {
				$params['sources']['string:top_source'] =  ($result[0]['type'] == "direct" ? getLabel('label-direct-enter') : $result[0]['name']) . " (" . $result[0]['cnt'] . ")";
			}


			$factory->isValid('sourcesSEOKeywords');
			$report = $factory->get('sourcesSEOKeywords');
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$params['sources']['string:top_keyword'] = (isset($result['all'][0]['text'])&&strlen($result['all'][0]['text'])? $result['all'][0]['text']." (" . $result['all'][0]['cnt'] . ")" : "-");


			$factory->isValid('sourcesSEO');
			$report = $factory->get('sourcesSEO');
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$params['sources']['string:top_searcher'] =
                                (isset($result['all'][0]['name'])&&strlen($result['all'][0]['name'])? $result['all'][0]['name']." (" . $result['all'][0]['cnt'] . ")" : "-");

            $params['filter'] = $this->getFilterPanel();

            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
		}

        public function tag() {
            $this->updateFilter();
            $sReturnMode = getRequest('param1');
            $iTagId      = (int) getRequest('param0');
            $thisHost   = cmsController::getInstance()->getCurrentDomain()->getHost();
            $thisLang   = cmsController::getInstance()->getCurrentLang()->getPrefix();
            $thisMdlUrl = '/'.$thisLang.'/admin/stat/';
            $thisUrl    = $thisMdlUrl.__FUNCTION__.'/'.$iTagId;
            //----------------------------------------------------------------------------------
            if($sReturnMode == 'xml') {
                $factory = new statisticFactory(dirname(__FILE__) . '/classes');
                $factory->isValid('tag');
                $report  = $factory->get('tag');
                $report->setStart($this->from_time);
                $report->setFinish($this->to_time);
                $report->setParams(array("tag_id" => $iTagId));
                $report->setDomain($this->domain); $report->setUser($this->user);
                $aRet  = $report->get();
                $sXML  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
                $sXML .= "<statistics>\n";
                $sXML .= "  <report name=\"Tag\" title=\"\" lang=\"".$thisLang."\" host=\"".$thisHost."\">
                              <chart type=\"pie\">
                                <argument field=\"uri\" />
                                <value    field=\"count\" />
                                <caption  field=\"uri\" />
                              </chart>
                              <table>
                                <column field=\"uri\"   title=\"Страница\" />
                                <column field=\"count\" title=\"Показов тега (всего)\" />
                                <column field=\"rel\"   title=\"Показов тега (относительно других страниц)\" valueSuffix=\"%\" />
                              </table>
                              <data>";
                foreach($aRet as $aRow) {
                    $sXML .= "      <row uri=\"".$aRow['uri']."\" count=\"".$aRow['count']."\" rel=\"".number_format($aRow['rel']*100, 2, '.', '')."\" />";
                }
                $sXML .= "    </data>\n  </report>\n</statistics>";
                header("Content-type: text/xml; charset=utf-8");
                header("Content-length: ".strlen($sXML));
                $this->flush($sXML);
                return "";
            }
            //----------------------------------------------------------------------------------
            $params = array();
            $params['filter'] = $this->getFilterPanel();
            $params['ReportTag']['flash:report1'] = "url=".$thisUrl."/xml/";
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
        }

        public function getFilterPanel() {
            // Some preparings for writing
            $sCurrentURI    = $_SERVER['REQUEST_URI'];
            $sCurrentDomain = ($this->domain)    ? $this->domain    : 'all';
            $sCurrentUser   = ($this->user)      ? $this->user      : '0';
            $iFromTime      = ($this->from_time) ? $this->from_time : time();
            $iToTime        = ($this->to_time)   ? $this->to_time   : time();
            $aDays          = array();
            $aMonths        = array();
            $aYears         = array();

            $aMonthLetters  = array(	getLabel('month-jan'),
					getLabel('month-feb'),
					getLabel('month-mar'),
					getLabel('month-apr'),
					getLabel('month-may'),
					getLabel('month-jun'),
					getLabel('month-jul'),
					getLabel('month-aug'),
					getLabel('month-sep'),
					getLabel('month-oct'),
					getLabel('month-nov'),
					getLabel('month-dec')
				);

            foreach(range(1, 31) as $i) $aDays[]   = array('attribute:id' => $i, 'node:name' => $i);
            foreach(range(1, 12) as $i) $aMonths[] = array('attribute:id' => $i, 'node:name' => $aMonthLetters[$i-1]);
            foreach(range((int)date('Y') - 2, (int)date('Y')) as $iYear) $aYears[] = array('attribute:id' => $iYear, 'node:name' => $iYear);
            // Write in proper way
            $aFP = array();
            $aFP['domain:domain'] = array( 'nodes:item' => array() , 'attribute:id' => $sCurrentDomain );
            $aDomainItems         = &$aFP['domain:domain']['nodes:item'];
            foreach($this->domainArray as $sHost => $sTitle)    $aDomainItems[] = array( 'attribute:id' => $sHost, 'node:name' => $sTitle);
            $aFP['users:user']    = array( 'attribute:id' => $sCurrentUser, 'nodes:item' => array());
            $aUsersItems          = &$aFP['users:user']['nodes:item'];
            foreach($this->usersArray as $sUserId => $sUserName) $aUsersItems[] = array( 'attribute:id' => $sUserId, 'node:name' => $sUserName);
            $aFP['period:start']  = array( 'nodes:entity' => array(
                                            array( 'attribute:type' => 'day',   'attribute:id' => (int)date('d', $iFromTime), 'nodes:item' => $aDays ),
                                            array( 'attribute:type' => 'month', 'attribute:id' => (int)date('m', $iFromTime), 'nodes:item' => $aMonths ),
                                            array( 'attribute:type' => 'year',  'attribute:id' => (int)date('Y', $iFromTime), 'nodes:item' => $aYears )
                                            ));
            $aFP['period:end']    = array( 'nodes:entity' => array(
                                            array( 'attribute:type' => 'day',   'attribute:id' => (int)date('d', $iToTime), 'nodes:item' => $aDays ),
                                            array( 'attribute:type' => 'month', 'attribute:id' => (int)date('m', $iToTime), 'nodes:item' => $aMonths ),
                                            array( 'attribute:type' => 'year',  'attribute:id' => (int)date('Y', $iToTime), 'nodes:item' => $aYears )
                                            ));
            return $aFP;
        }

        public function updateFilter() {
            try {
                $aParam = array('config' => array(
                                        'string:domain'   => null,
                                        'int:user'        => null,
                                        'int:start_day'   => null,
                                        'int:start_month' => null,
                                        'int:start_year'  => null,
                                        'int:end_day'     => null,
                                        'int:end_month'   => null,
                                        'int:end_year'    => null,
                            ));
                $aParam = $this->expectParams($aParam);
                // Setup domian
                if(in_array($aParam['config']['string:domain'], $this->domainArray) || $aParam['config']['string:domain']=='all')
                {
                    $this->domain = $aParam['config']['string:domain'];
                    setcookie('stat_domain', $this->domain, 0, '/');
                } else {
                    if(isset($_COOKIE['stat_domain']))
                    if(in_array($_COOKIE['stat_domain'], $this->domainArray) || $_COOKIE['stat_domain'] == 'all')
                        $this->domain = $_COOKIE['stat_domain'];
                }
                // Setup user
                if(in_array($aParam['config']['int:user'], array_keys($this->usersArray)) || $aParam['config']['int:user']==0)
                {
                    $this->user = $aParam['config']['int:user'];
                    setcookie('stat_user', $this->user, 0, '/');
                } else {
                    if(isset($_COOKIE['stat_user']))
                    if(in_array($_COOKIE['stat_user'], $this->usersArray) || $_COOKIE['stat_user'] == 'all')
                        $this->user = $_COOKIE['stat_user'];
                }
                // Setup start of period
                $fd = (int) $aParam['config']['int:start_day'];
                $fm = (int) $aParam['config']['int:start_month'];
                $fy = (int) $aParam['config']['int:start_year'];
                $this->from_time = (int) strtotime($fy . "-" . $fm . "-" . $fd);
                setcookie("from_time", $this->from_time, 0, "/");
                // Setup end of period
                $td = (int) $aParam['config']['int:end_day'];
                $tm = (int) $aParam['config']['int:end_month'];
                $ty = (int) $aParam['config']['int:end_year'];
                $this->to_time = (int) strtotime($ty . "-" . $tm . "-" . $td);
                if ($this->to_time < $this->from_time) {
                    $this->to_time = strtotime('+1 day', $this->from_time);
                }
                setcookie("to_time", $this->to_time, 0, "/");
            } catch(Exception $e) {
                if(isset($_COOKIE['from_time']))   $this->from_time = (int) $_COOKIE['from_time'];
                if(isset($_COOKIE['to_time']))     $this->to_time   = (int) $_COOKIE['to_time'];
                if(isset($_COOKIE['stat_domain'])) $this->domain    = (in_array($_COOKIE['stat_domain'], $this->domainArray)  || $_COOKIE['stat_domain'] == 'all' )
                                                                ? $_COOKIE['stat_domain'] : 'all';
                if(!$this->domain)          $this->domain    = 'all';
                if(isset($_COOKIE['stat_user'])) $this->user    = (in_array($_COOKIE['stat_user'], array_keys($this->usersArray))  || $_COOKIE['stat_user'] == 0 )
                                                                ? $_COOKIE['stat_user'] : 0;
                if(!$this->user)          $this->user    = 0;
            }
        }

		public static function makeDate($_sFormat, $_iTimeStamp = -1) {
			$aMonthLong = array("Январь","Февраль","Март","Апрель","Май",
								"Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь");
			$aMonthShort = array("Янв","Фев","Мар","Апр","Май",
								 "Июнь","Июль","Авг","Сен","Окт","Ноя","Дек");
			if($_iTimeStamp == -1) $_iTimeStamp = time();
			$iFormatLength = strlen($_sFormat);
			$sDate = "";
			for($i=0; $i<$iFormatLength; $i++) {
				switch($_sFormat[$i]) {
					case 'F': $sDate .=  $aMonthLong[intval(date("n", $_iTimeStamp))]; break;
					case 'M': $sDate .= $aMonthShort[intval(date("n", $_iTimeStamp))-1]; break;
					default:  $sDate .= date($_sFormat[$i], $_iTimeStamp);
				}
			}
			return $sDate;
		}

	};

?>