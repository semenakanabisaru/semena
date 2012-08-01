<?php

abstract class __stat_visits extends baseModuleAdmin {
    public function visits_hits() {
        $this->updateFilter();
        $sReturnMode = getRequest('param0');
        //
        $curr_page = (int) getRequest('p');
        //
        
        
        
        //
        $thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
        $thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
        $thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
        $thisUrlTail = '';
        //
        $factory = new statisticFactory(dirname(__FILE__) . '/classes');
        //
        if ($sReturnMode === 'xml') {
        } elseif ($sReturnMode === 'xml1') {
            $factory->isValid('visitCommon');
            $report = $factory->get('visitCommon');
            //
            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);
            //
            $result = $report->get();
            // =================================
            $iHoveredAbs = 0; $iTotalAbs = $result['summ'];
            $iTotalRecs = $result['total'];
            $sAnswer = "";
            $sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
            $sAnswer .= <<<END
                <statistics> 
                 <report name="visitCommon" title="Динамика хитов по дням" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
                <table>
                    <column field="timestamp" title="День" showas="date" valueSuffix="" prefix="" />
                    <column field="count"    title="Хитов (абсолютное значение)" valueSuffix="" prefix="" />
                    <column field="rel"      title="Хитов (относительное значение)" valueSuffix="%" prefix="" />
                </table>
                <chart type="column" drawTrendLine="true">
                    <argument field="timestamp" />
                    <value field="count" description="Количество хитов" axisTitle="Количество хитов" />
                    <caption field="date" />
                </chart>
                <data>\n
END;
                $iOldTimeStamp = $this->from_time;                
                foreach($result['detail']['result'] as $info) {
                		if(!isset($info['ts'])) {
                			$info['ts'] = NULL;
                		}
                    $sThisDate = date('d M', $info['ts']);
                    while( ($iOldTimeStamp < $info['ts']) && 
                           (date('d M', $iOldTimeStamp) != $sThisDate) ) {
                        $attr_page_uri   = htmlspecialchars( '' );
                        $sAnswer .= "<row ".
                                    "timestamp=\"".$iOldTimeStamp."\" ".
                                    "count=\"0\" ".
                                    "date=\"".__stat_admin::makeDate('d M', $iOldTimeStamp)."\" ".
                                    "uri=\"".$attr_page_uri."\" rel=\"0\" />\n";
                        $iOldTimeStamp += 86400;  
                    }
                    $iOldTimeStamp = $info['ts'] + 86400;
                    $iAbs = (isset($info['cnt'])) ? $info['cnt'] : 0;
                    $iHoveredAbs += $iAbs;
                    $page_uri = '';                    
                    $attr_uri= htmlspecialchars($page_uri);                    
                    $sAnswer .= "<row ";
                        $sAttrs = '';
                        $sAttrs .= 'timestamp="'.$info['ts'].'" ';
                        $sAttrs .= 'count="'.$iAbs.'" ';
                        $sAttrs .= 'date="'.__stat_admin::makeDate('d M', $info['ts']).'" ';
                        $sAttrs .= 'uri="'.$attr_uri.'" ';
                        $sAttrs .= 'rel="'.round($iAbs/($iTotalAbs/100), 1).'" ';
                        $sAnswer .= $sAttrs;
                    $sAnswer .= "/>\n";
                }
                $sThisDate = date('d M', $this->to_time+86400);
                while( ($iOldTimeStamp < $this->to_time+86400) && 
                       (date('d M', $iOldTimeStamp) != $sThisDate) ) {
                    $attr_page_uri   = htmlspecialchars( '' );
                    $sAnswer .= "<row ".
                                "timestamp=\"".$iOldTimeStamp."\" ".
                                "count=\"0\" ".
                                "date=\"".__stat_admin::makeDate('d M', $iOldTimeStamp)."\" ".
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
            $factory->isValid('visitCommonHours');
            $report = $factory->get('visitCommonHours');
            //
            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);
            //
            $result = $report->get();
            // =================================
            $iHoveredAbs = 0; $iTotalAbs = $result['summ']?$result['summ']:1;
            $iTotalRecs = $result['total'];
            $sAnswer = "";
            $sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
            $sAnswer .= <<<END
                <statistics>
                <report name="visitCommonHours" title="Распеределение хитов по часам суток" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
                <table>
                    <column field="hourint"  title="Часы" valueSuffix="" prefix="" />
                    <column field="count" title="Хитов (абсолютное значение)" valueSuffix="" prefix="" />
                    <column field="rel"   title="Хитов (относительное значение)" valueSuffix="%" prefix="" />
                </table>
                <chart type="line" drawTrendLine="true">
                    <argument field="hour" />
                    <value field="count" description="Количество хитов" axisTitle="Количество хитов" />
                    <caption field="hourint" />
                </chart>
                <data>\n
END;
                $iHour = 0;
                for ($iHour = 0; $iHour < 24; $iHour++) {
                    if (isset($result['detail'][$iHour])) {
                        $info = $result['detail'][$iHour];
                    } else {
                        $info = array('ts'=>mktime($iHour), 'cnt'=>0);
                    }                    
                    $iAbs = $info['cnt'];
                    $iHoveredAbs += $iAbs;
                    $page_uri = '';
                    $iTtlHour = intval(date('G', $info['ts']));                    
                    $page_title = $iTtlHour."..".($iTtlHour+1);                    
                    $attr_page_title = htmlspecialchars($page_title);
                    $attr_uri= htmlspecialchars($page_uri);                    
                    $sAnswer .= "<row ";
                        $sAttrs = '';
                        $sAttrs .= 'count="'.$iAbs.'" ';
                        $sAttrs .= 'hourint="'.$attr_page_title.'" ';
                        $sAttrs .= 'uri="'.$attr_uri.'" ';
                        $sAttrs .= 'timestamp="'.$info['ts'].'" ';
                        $sAttrs .= 'rel="'.round($iAbs/($iTotalAbs/100), 1).'" ';
                        $sAttrs .= 'hour="'.$iTtlHour.'" ';
                        $sAnswer .= $sAttrs;
                    $sAnswer .= "/>\n";
                }
                $iRest = ($iTotalAbs - $iHoveredAbs);
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
            $params['ReportHitsByDays']['flash:report1']  = "url=".$thisUrl."/xml1/".$thisUrlTail;
            $params['ReportHitsByHours']['flash:report2'] = "url=".$thisUrl."/xml2/".$thisUrlTail;
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();
        }
    }
    public function visits_visitors() {
        return $this->auditory(); // RETURN
    }
    public function visits_sessions() {
        return $this->visitors(); // RETURN
    }
    public function visits() {
        return $this->visits_hits(); // RETURN
    }    
    public function visitsByDate() {
        return "under construction";
    }
}

?>