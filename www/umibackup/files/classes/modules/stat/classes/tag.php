<?php
/**
 * $Id: tag.php 1 2008-04-28 11:11:00Z Leeb $
 *
 * Класс получения информации о теге
 *
 */
class tag extends simpleStat {
    /**
    * @desc Report paramteters
    * @var  Array
    */
    protected $params = array('tag_id' => 0);
    /**
    * @desc Returns the report on tag choosen
    * @return Array
    */
    public function get() {
        $aResult = array();        
        $sSQL    = "SELECT e.name, p.uri, COUNT(*) as 'count' 
                    FROM `cms_stat_events` e, `cms_stat_events_collected` ec, `cms_stat_hits` h, `cms_stat_pages` p, `cms_stat_paths` pth 
                    WHERE e.id = " . $this->params['tag_id'] . " 
                      AND ec.event_id = e.id 
                      AND ec.hit_id = h.id 
                      AND h.page_id = p.id
                      AND h.path_id = pth.id
                      AND pth.date BETWEEN " . $this->getQueryInterval() . "
                      " . $this->getHostSQL('e') . $this->getUserFilterWhere('pth')  . " 
                    GROUP BY p.id 
                    ORDER BY count DESC";
        $rQuery  = l_mysql_query($sSQL);
        $iTotal  = 0;
        while($aRow = mysql_fetch_assoc($rQuery)) {
            $iTotal    += $aRow['count'];
            $aResult[]  = $aRow;
        }
        foreach($aResult as &$aRow) {
            $aRow['rel'] = (float)$aRow['count'] / (float)$iTotal;
        }
        return $aResult;
    }
}
?>
