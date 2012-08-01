<?php
/**
 * $Id: openstatServices.php 47 2007-08-27 08:55:14Z zerkms $
 *
 * ����� ��������� ���������� � ��������� ��������� openstat
 *
 */

class openstatServices extends simpleStat
{
    /**
     * ������ ����������
     *
     * @var array
     */
    protected $params = array('source_id' => 0, 'campaign_id' => 0);

    public function get()
    {
        $cond = array();
        foreach (array('source_id', 'campaign_id') as $val) {
            if ((int)$this->params[$val] > 0) {
                $cond[] = '`os`.`' . $val . '` = ' . (int)$this->params[$val];
            }
        }
        $cond_str = '';
        if (sizeof($cond)) {
            $cond_str = ' AND ' . implode(' AND ', $cond);
        }

        l_mysql_query("SET @cnt := (SELECT COUNT(*) FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . $cond_str . ")");

	$result = $this->simpleQuery("SELECT COUNT(*) AS `abs` FROM `cms_stat_sources_openstat` `os`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                     INNER JOIN `cms_stat_sources_openstat_service` `s` ON `s`.`id` = `os`.`service_id`
                                      WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . $cond_str);
        $i_total = (isset($result[0]['total'])) ? (int) $result[0]['total'] : 0;

        $res = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @cnt * 100 AS `rel`, `s`.`name`, `s`.`id` AS 'service_id' FROM `cms_stat_sources_openstat` `os`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                     INNER JOIN `cms_stat_sources_openstat_service` `s` ON `s`.`id` = `os`.`service_id`
                                      WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . $cond_str . "
                                       GROUP BY `s`.`id`
                                        ORDER BY `abs` DESC
                                         LIMIT " . $this->offset . ", " . $this->limit, true);

	return array("all"=>$res['result'], "summ"=>$i_total, "total"=>$res['FOUND_ROWS']);
    }
}

?>