<?php
/**
 * $Id: visitTime.php 29 2006-10-14 00:51:26Z zerkms $
 *
 * Класс получения информации о времени просмотра за период
 *
 */

class visitTime extends simpleStat
{
    /**
     * Имя поля, по которому происходит группировка данных
     *
     * @var string
     */
    private $groupby;

    /**
     * интервал по умолчанию
     *
     * @var string
     */
    protected $interval = '-30 days';

    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        $this->groupby = $this->calcGroupby($this->start, $this->finish);

        return array('detail' => $this->getDetail(), 'dynamic' => $this->getDynamic(), 'groupby' => $this->groupby);
    }

    private function getDetail()
    {
        l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_visit_time`");
        l_mysql_query("CREATE TEMPORARY TABLE `tmp_visit_time` (`mins` FLOAT) ENGINE = MEMORY");

        l_mysql_query("INSERT INTO `tmp_visit_time` SELECT (UNIX_TIMESTAMP(MAX(`h`.`date`)) - UNIX_TIMESTAMP(MIN(`h`.`date`))) / 60 AS `minutes` FROM `cms_stat_hits` `h`
                        INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                         WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . " 
                          GROUP BY `h`.`path_id`");

        return $this->simpleQuery("SELECT COUNT(*) AS `cnt`, IF(`mins` > 10, IF(`mins` > 20, IF(`mins` > 30, IF(`mins` > 40, IF(`mins` > 50, 51, 41), 31), 21), 11), ROUND(`mins`)) `minutes`
                                     FROM `tmp_visit_time`
                                      GROUP BY `minutes` ORDER BY `cnt` DESC");
    }

    private function getDynamic()
    {
        return $this->simpleQuery("SELECT AVG(`minutes`) AS `minutes_avg`, `tmp`.`" . $this->groupby . "`, UNIX_TIMESTAMP(`tmp`.`date`) AS `ts` FROM

                                    (SELECT `h`.`date`, `h`.`week`, `h`.`month`, `h`.`year`, (UNIX_TIMESTAMP(MAX(`h`.`date`)) - UNIX_TIMESTAMP(MIN(`h`.`date`))) / 60 AS `minutes` FROM `cms_stat_hits` `h`
                                      INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . " 
                                        GROUP BY `h`.`path_id`) `tmp`

                                   GROUP BY `tmp`.`" . $this->groupby . "`, `tmp`.`year`
                                    ORDER BY `minutes_avg` DESC");
    }

    /**
     * Метод получения поля, по которому будет производиться группировка в зависимости от величины интервала
     *
     * @param integer $start
     * @param integer $finish
     * @return string
     *
     * @see auditoryVolumeGrowth::__construct()
     */
    private function calcGroupby($start, $finish)
    {
        $daysInterval = ceil(($finish - $start) / (3600 * 24));

        if ($daysInterval > 180) {
            return 'month';
        }
        return 'week';
    }
}

?>