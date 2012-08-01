<?php

class statisticFactory
{
    private $libsPath;
    private $validReports = array('auditoryActivity', 'auditoryLoyality', 'auditoryVolume', 'auditoryVolumeGrowth', 'entryPoints', 'exitPoints', 'hostsCommon', 'hostsCommonHours', 'pageInfo', 'pageNext', 'pagesHits', 'paths', 'refuses', 'sectionHits', 'sectionHitsIncluded', 'sourcesDomains', 'sourcesDomainsConcrete', 'sourcesPages', 'sourcesPR', 'sourcesSEO', 'sourcesSEOConcrete', 'sourcesSummary', 'sourcesSEOKeywords', 'sourcesSEOKeywordsConcrete', 'sourcesTop', 'userStat', 'visitCommon', 'visitCommonHours', 'visitDeep', 'visitersCommon', 'visitersCommonHours', 'visitsByDate', 'visitTime', 'entryByReferer', 'refererByEntry', 'cityStat', 'tag');

    public function __construct($libsPath)
    {
        $this->libsPath = $libsPath;
    }

    public function isValid($reportName)
    {
        return in_array($reportName, $this->validReports);
    }

    public function get($reportName)
    {
        require_once $this->libsPath . '/' . $reportName . '.php';

        //return new $reportName;

        require_once $this->libsPath . '/xml/' . $reportName . 'Xml.php';

        $xml = $reportName . 'Xml';

        return new $xml(new $reportName);
    }
}

?>