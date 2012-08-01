<?php
    abstract class __popular_stat extends baseModuleAdmin {
        public function popular_pages() {
            $sReturnMode = getRequest('param0');
            
            

            $thisHost = cmsController::getInstance()->getCurrentDomain()->getHost();
            $thisLang = cmsController::getInstance()->getCurrentLang()->getPrefix();
            $thisUrl = '/'.$thisLang.'/admin/stat/'.__FUNCTION__;
            $thisUrlTail = '';

            

            $this->updateFilter();

            if ($sReturnMode === 'xml') {
                $factory = new statisticFactory(dirname(__FILE__) . '/classes');
                $factory->isValid('pagesHits');
                $report = $factory->get('pagesHits');

            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setLimit($this->items_per_page);
            $report->setDomain($this->domain); $report->setUser($this->user);
            


                $result = $report->get();


                $iHoveredAbs = 0; $iTotalAbs = $result['summ'];
                $iTotalRecs = $result['total'];
                $sAnswer = "";
                $sAnswer .= "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
                $sAnswer .= <<<END
                    <statistics>
                    <report name="pagesHits" title="Популярность страниц" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->from_time}" timerange_finish="{$this->to_time}">
                    <table>
                        <column field="name" title="Страница" prefix="" valueSuffix=""  datatipField="uri"  />
                        <column field="cnt" title="Показов абс." prefix="" valueSuffix="" />
                        <column field="rel" title="Показов отн." prefix="" valueSuffix="%" />
                    </table>
                    <chart type="pie">
                        <argument />
                        <value field="cnt"/>
                        <caption field="name" />
                    </chart>                    
                    <data>
END;
                    foreach($result['all'] as $info) {
                        $iAbs = $info['abs']; $fRel = $info['rel'];
                        $iHoveredAbs += $iAbs;
                        $page_uri = $info['uri'];
                        $page_title = ''; 
                        //    
                        if ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
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
                $sAnswer .= "</report>\n</statistics>";
                //
                header("Content-type: text/xml; charset=utf-8");
                header("Content-length: ".strlen($sAnswer));
                $this->flush($sAnswer);
                return "";
            } 
            $params = array();
            $params['filter'] = $this->getFilterPanel();            
            $params['ReportPagePopularity']['flash:report']  = "url=".$thisUrl."/xml/".$thisUrlTail;            
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);                        
            return $this->doData();            
        }
    };
?>