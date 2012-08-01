<?php
/**
 * $Id: openstatCampaigns.php 47 2007-08-27 08:55:14Z zerkms $
 *
 * ����� ��������� ���������� � ��������� ��������� openstat
 *
 */

class openstatCampaigns extends simpleStat
{
    /**
     * ������ ����������
     *
     * @var array
     */
    protected $params = array('source_id' => 0);

    public function get()
    {
        l_mysql_query("SET @cnt := (SELECT COUNT(*) FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . ((int)$this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int)$this->params['source_id'] : '') . ")");

	$result = $this->simpleQuery("SELECT COUNT(*) AS `total` FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                      INNER JOIN `cms_stat_sources_openstat_campaign` `c` ON `c`.`id` = `os`.`campaign_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . ((int)$this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int)$this->params['source_id'] : ''));
        $i_total = (int) $result[0]['total'];

        $res = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @cnt * 100 AS `rel`, `c`.`name`, `c`.`id` as 'campaign_id' FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                      INNER JOIN `cms_stat_sources_openstat_campaign` `c` ON `c`.`id` = `os`.`campaign_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . ((int)$this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int)$this->params['source_id'] : '') . "
                                        GROUP BY `c`.`id`
                                         ORDER BY `abs` DESC
                                          LIMIT " . $this->offset . ", " . $this->limit, true);

	return array("all"=>$res['result'], "summ"=>$i_total, "total"=>$res['FOUND_ROWS'], 'source_id'=>(isset($this->params['source_id']) ? intval($this->params['source_id']) : 0));

    }
}

?>