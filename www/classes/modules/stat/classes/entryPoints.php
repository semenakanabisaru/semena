<?php
/**
 * $Id: entryPoints.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о точках входа за период
 *
 */

class entryPoints extends simpleStat
{
    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        l_mysql_query("SET @all = (SELECT COUNT(*) FROM `cms_stat_hits` `h`
                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `number_in_path` = 1 " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . ")");

	$resSumm = $this->simpleQuery("SELECT COUNT(*) AS 'cnt' FROM `cms_stat_hits` `h`
                                     INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`number_in_path` = 1 " . $this->getHostSQL("p"));
	$i_summ = (int) $resSumm[0]['cnt'];

        $res = $this->simpleQuery($qry = "SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel`, `p`.`uri`, `p`.`id`, UNIX_TIMESTAMP(`h`.`date`) AS `ts` FROM `cms_stat_hits` `h`
                                     INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`number_in_path` = 1 " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
                                       GROUP BY `h`.`page_id`
                                        ORDER BY `abs` DESC
                                         LIMIT " . $this->offset . ", " . $this->limit, true);

	return array("all"=> $res['result'], "summ"=>$i_summ, "total"=>$res['FOUND_ROWS']);

    }
}

?>