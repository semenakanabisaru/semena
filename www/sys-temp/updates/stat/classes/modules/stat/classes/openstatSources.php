<?php
/**
 * $Id: openstatSources.php 47 2007-08-27 08:55:14Z zerkms $
 *
 * ����� ��������� ���������� � ��������� �������� openstat
 *
 */

class openstatSources extends simpleStat
{
    public function get()
    {
	$sQr = "SET @cnt := (SELECT COUNT(*) FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . ")";

        l_mysql_query($sQr);

	$result = $this->simpleQuery("SELECT COUNT(*) AS `total` FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                      INNER JOIN `cms_stat_sources_openstat_source` `s` ON `s`.`id` = `os`.`source_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p'));
        $i_total = (int) $result[0]['total'];

	$sQr = "SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @cnt * 100 AS `rel`, `s`.`name` AS 'name', `s`.`id` AS `source_id` FROM `cms_stat_sources_openstat` `os`
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
                                      INNER JOIN `cms_stat_sources_openstat_source` `s` ON `s`.`id` = `os`.`source_id`
                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
                                        GROUP BY `s`.`id`
                                         ORDER BY `abs` DESC
                                          LIMIT " . $this->offset . ", " . $this->limit;

        $res = $this->simpleQuery($sQr, true);

	return array("all"=>$res['result'], "summ"=>$i_total, "total"=>$res['FOUND_ROWS']);
    }
}

?>