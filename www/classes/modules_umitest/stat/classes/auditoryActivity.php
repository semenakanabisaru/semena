<?php
/**
 * $Id: auditoryActivity.php 9 2006-08-23 13:50:06Z zerkms $
 *
 * Класс получения информации об активности аудитории за период
 *
 */

class auditoryActivity extends simpleStat
{
    /**
     * Имя поля, по которому происходит группировка данных
     *
     * @var string
     */
    private $groupby;

    private $groupby_key;

    /**
     * Интервал по умолчанию
     * задаётся в насоедниках, если нужно
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

    public function getDetail()
    {
        l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_activity`");
        l_mysql_query("CREATE TEMPORARY TABLE `tmp_activity` (`days` INT) ENGINE = MEMORY");

        l_mysql_query("INSERT INTO `tmp_activity` SELECT FLOOR( ( UNIX_TIMESTAMP(MAX(`date`)) - UNIX_TIMESTAMP(MIN(`date`)) ) / (COUNT(*) - 1) / 3600 / 24 ) AS `days` FROM `cms_stat_paths`
                     WHERE `date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL() . $this->getUserFilterWhere() . "
                      GROUP BY `user_id`");

        return $this->simpleQuery("SELECT COUNT(*) AS `cnt`, IF(`days` > 10, IF(`days` > 20, IF(`days` > 30, IF(`days` > 40, IF(`days` > 50, 51, 41), 31), 21), 11), `days`) AS `days` FROM `tmp_activity` GROUP BY `days`");
    }

    public function getDynamic()
    {

        l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_activity`");
        l_mysql_query("CREATE TEMPORARY TABLE `tmp_activity` (`days` INT, `" . $this->groupby . "` INT, `year` INT, `date` DATETIME) ENGINE = MEMORY");
        l_mysql_query("INSERT INTO `tmp_activity` SELECT FLOOR( ( UNIX_TIMESTAMP(MAX(`date`)) - UNIX_TIMESTAMP(MIN(`date`)) ) / (COUNT(*) - 1) / 3600 / 24 ) AS `days`, DATE_FORMAT(`date`, '%" . $this->groupby_key . "') AS `" . $this->groupby . "`, DATE_FORMAT(`date`, '%Y') AS `year`, `date` FROM `cms_stat_paths`
                     WHERE `date` BETWEEN '" . $this->formatDate($this->start) . "' AND '" . $this->formatDate($this->finish) . "' " . $this->getHostSQL() . $this->getUserFilterWhere() . "
                      GROUP BY `user_id`");
        return $this->simpleQuery("SELECT AVG(`days`) AS `avg`, `" . $this->groupby . "` AS `period`, UNIX_TIMESTAMP(`date`) AS `ts` FROM `tmp_activity`
                                     GROUP BY `" . $this->groupby . "`, `year` ORDER BY `date`");

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

        if ($daysInterval > 30) {
            $this->groupby_key = 'u';
            return 'week';
        } elseif ($daysInterval > 7) {
            $this->groupby_key = 'j';
            return 'day';
        }
        $this->groupby_key = 'k';
        return 'hour';
    }
}

?>