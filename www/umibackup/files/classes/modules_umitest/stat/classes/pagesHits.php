<?php
/**
 * $Id: pagesHits.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о количестве просмотров страниц за период
 *
 */

class pagesHits extends simpleStat
{

    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        return $this->getAll();
    }

    private function getAll()
    {
        $result = $this->simpleQuery("SELECT   COUNT(*) as `total` FROM `cms_stat_hits` `h` FORCE INDEX(`date`)
                                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p'));
        $i_total = (int) $result[0]['total']; // количество найденных записей, это нельзя использовать как количество элементов статичтики, т.к. далее каждому эл-ту будет сопоставлено несколько записей; количество элементов - только из следующего запроса можно вытянуть (SQL_CALC_FOUND_ROWS)

	$arrQr = $this->simpleQuery("SELECT   SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / ".$i_total." * 100 AS `rel`, `h`.`page_id`, `p`.`uri` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                    ".$this->getUserFilterTable('id', 'h.path_id')."
                                     WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere() . "
                                      GROUP BY `page_id`
                                       ORDER BY `abs` DESC
                                        LIMIT " . $this->offset . ", " . $this->limit, true);

        return array("all"=>$arrQr['result'], "summ"=>$i_total, "total"=>$arrQr['FOUND_ROWS']);
    }
}

?>