<?php

class reportBuilder
{
    /**
     * константа, хранящая формат для даты mysql
     *
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * стартовая дата для анализа
     *
     * @var integer
     */
    protected $start;

    /**
     * конечная дата для анализа
     *
     * @var integer
     */
    protected $finish;

    /**
     * Интервал по умолчанию
     * задаётся в насоедниках, если нужно
     *
     * @var string
     */
    protected $interval = '-10 days';

    /**
     * Id хоста, для которого производятся выборки
     *
     * @var unknown_type
     */
    protected $host_id;

    protected $graphType = 'graphic';

    protected $groupby;
    protected $groupByValid = array('day' => '`day`, `month`', 'week' => '`week`', 'month' => '`month`');



    private $filters = array();
    private $index;
    private $index2;

    public function __construct()
    {
        $this->setDomain($_SERVER['SERVER_NAME']);
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function setIndex2($index)
    {
        $this->index2 = $index;
    }

    public function addFilter($name, $value)
    {
        $this->filters[] = array('name' => $name, 'value' => $value);
    }

    public function build()
    {
        $result = $this->doQuery($this->index);

        if (!empty($this->index2)) {
            $result2 = $this->doQuery($this->index2);

            $tmp = array();
            foreach ($result as $val) {
                $tmp[$val['period']] = $val;
            }
            foreach ($result2 as $val) {
                $tmp[$val['period']]['cnt2'] = $val['cnt'];
            }

            $result = $tmp;
        }

        echo '<pre>';
        var_dump($result);
        echo '</pre>';
    }

    public function doQuery($index)
    {
        if ($index == 'visitersCount' || $index == 'refusesCount') {
            $pre = 'SELECT `p`.`id` FROM `cms_stat_paths` `p`';
        } else {
            $pre = 'SELECT `h`.`id` FROM `cms_stat_paths` `p`
                     INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id`';
        }

        if (empty($this->filters)) {
            $tmp_table = 'tmp_filtered0';
            l_mysql_query($qry1 = "DROP TEMPORARY TABLE IF EXISTS `" . $tmp_table . "`");
            l_mysql_query($qry2 = "CREATE TEMPORARY TABLE `" . $tmp_table . "` (`id` INT, KEY `id` (`id`)) ENGINE = MEMORY");
            l_mysql_query($qry3 = "INSERT INTO `" . $tmp_table . "` " . $pre . " WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . "");
            //echo $qry2 . "\r\n" . $qry3;
        }

        foreach ($this->filters as $key => $val) {
            $tmp_table = 'tmp_filtered' . $key;

            $values = '';
            if (is_array($val['value'])) {
                foreach ($val['value'] as $v) {
                    $values .= "'" . mysql_real_escape_string($v) . "', ";
                }
                $values = substr($values, 0, -2);
            } else {
                $values = "'" . mysql_real_escape_string($val['value']) . "'";
            }

            l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `" . $tmp_table . "`");
            l_mysql_query("CREATE TEMPORARY TABLE `" . $tmp_table . "` (`id` INT, KEY `id` (`id`)) ENGINE = MEMORY");
            if ($val['name'] == 'searchEngine') {
                $qry = 'INSERT INTO `' . $tmp_table . '` ' . $pre . " INNER JOIN `cms_stat_sources` `s` ON `p`.`source_id` = `s`.`id`
                         INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                          INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id` " .
                          ($key > 0 ? ' INNER JOIN `tmp_filtered' . ($key - 1) . '` `t` ON `t`.`id` = `p`.`id`' : '') .
                          "WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . " AND `s`.`src_type` = 2  AND `sse`.`name` IN (" . $values . ")";
            } elseif ($val['name'] == 'searchQuery') {
                $qry = 'INSERT INTO `' . $tmp_table . '` ' . $pre . " INNER JOIN `cms_stat_sources` `s` ON `p`.`source_id` = `s`.`id`
                         INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                          INNER JOIN `cms_stat_sources_search_queries` `ssq` ON `ssq`.`id` = `ss`.`text_id` " .
                          ($key > 0 ? ' INNER JOIN `tmp_filtered' . ($key - 1) . '` `t` ON `t`.`id` = `p`.`id`' : '') .
                          "WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `s`.`src_type` = 2  AND `ssq`.`text` IN (" . $values . ")";
            } elseif ($val['name'] == 'linkedSites') {
                $qry = "INSERT INTO `' . $tmp_table . '` " .
                $pre .
                " INNER JOIN `cms_stat_sources` `s` ON `p`.`source_id` = `s`.`id`
                              INNER JOIN `cms_stat_sources_sites` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                               INNER JOIN `cms_stat_sources_sites_domains` `ssd` ON `ssd`.`id` = `ss`.`domain`
                                WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `s`.`src_type` = 1 AND `ssd`.`name` IN (" . $values . ")";
            } elseif ($val['name'] == 'goals') {
                $qry = 'INSERT INTO `' . $tmp_table . '` ' . $pre . " INNER JOIN `cms_stat_hits` `h2` ON `h2`.`path_id` = `p`.`id`
                          INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `h2`.`id`
                           INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                            WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `e`.`name` = (" . $values . ") AND `e`.`type` = 1";
            }
            //echo $qry, '<br>';
            l_mysql_query($qry) or die(l_mysql_error());
        }

        //$onerow = false;

        if ($this->graphType != 'histogramVertical') {
            if ($index == 'visitersCount') {
                $qry = "SELECT COUNT(*) AS `cnt`, UNIX_TIMESTAMP(`date`) AS `ts`, DATE_FORMAT(`date`, '%d') AS `day`, DATE_FORMAT(`date`, '%c') AS `month`, DATE_FORMAT(`date`, '%Y') AS `year`, DATE_FORMAT(`date`, '%u') AS `week`, DATE_FORMAT(`date`, '" . $this->getGroupBySign() . "') AS `period`
                     FROM `" . $tmp_table . "` `tmp`
                      INNER JOIN `cms_stat_paths` `p` ON `tmp`.`id` = `p`.`id`
                       GROUP BY " . $this->calcGroupby() . ", `year`
                        ORDER BY " . $this->getOrderByField();
            } elseif ($index == 'refusesCount') {
                $qry = "SELECT COUNT(*) AS `cnt`, `ts`, `period` FROM (
                     SELECT MAX(`number_in_path`) AS `level`, `h`.`day`, `h`.`month`, `h`.`year`, `h`.`week`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`." . $this->calcGroupby() . " AS `period` FROM `" . $tmp_table . "` `tmp`
                      INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `tmp`.`id`
                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . "
                        GROUP BY `tmp`.`id`
                         HAVING `level` = 1
                         ) AS `h`
                    GROUP BY " . $this->calcGroupby() . ", `year`
                     ORDER BY " . $this->getOrderByField();
            } elseif ($index == 'events') {
                $qry = "SELECT COUNT(*) AS `cnt`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`." . $this->calcGroupby() . " AS `period` FROM `" . $tmp_table . "` `tmp`
                     INNER JOIN `cms_stat_hits` `h` ON `h`.`id` = `tmp`.`id`
                      INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `tmp`.`id`
                       INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                        GROUP BY " . $this->calcGroupby() . ", `year`
                         ORDER BY " . $this->getOrderByField();
            } elseif ($index == 'profit') {
                $qry = "SELECT SUM(`profit`) AS `cnt`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`." . $this->calcGroupby() . " AS `period` FROM `" . $tmp_table . "` `tmp`
                     INNER JOIN `cms_stat_hits` `h` ON `h`.`id` = `tmp`.`id`
                      INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `tmp`.`id`
                       INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                        GROUP BY " . $this->calcGroupby() . ", `year`
                         ORDER BY " . $this->getOrderByField();
            } else {
                throw new Exception('Недопустимый параметр');
            }
        } else {
            if ($index == 'visitersCount') {
                $qry = "SELECT COUNT(*) AS `cnt`, IF(`count` > 10, IF(`count` > 20, IF(`count` > 30, IF(`count` > 40, IF(`count` > 50, 51, 41), 31), 21), 11), `count`) AS `count`, DATE_FORMAT(`date`, '" . $this->getGroupBySign() . "') AS `period`
                         FROM

                        (SELECT COUNT(*) AS `count`, `date`  FROM `" . $tmp_table . "` `tmp`
                         INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `tmp`.`id`
                          GROUP BY `user_id`) `x`

                          GROUP BY `x`.`count`";
            } elseif ($index == 'refusesCount') {
                $qry = "SELECT COUNT(*) AS `cnt`, IF(`count` > 10, IF(`count` > 20, IF(`count` > 30, IF(`count` > 40, IF(`count` > 50, 51, 41), 31), 21), 11), `count`) AS `count`, DATE_FORMAT(`date`, '" . $this->getGroupBySign() . "') AS `period`
                        FROM
                        (
                        SELECT COUNT(*) AS `count`, `date` FROM

                        (
                        SELECT MAX(`h`.`number_in_path`) AS `level`, `p`.`id`, `p`.`user_id`, `p`.`date` FROM `" . $tmp_table . "` `tmp`
                         INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `tmp`.`id`
                          INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id`
                           GROUP BY `p`.`id`
                            HAVING `level` = 1
                        ) `inn`
                        GROUP BY `inn`.`user_id`
                        ) `out`
                        GROUP BY `out`.`count`";
            }
        }
        //var_dump($qry);
        $res = l_mysql_query($qry);
        /*
        if ($onerow) {
        $result = mysql_fetch_assoc($res);
        } else {*/
        $result = array();
        while ($row = mysql_fetch_assoc($res)) {
            $result[] = $row;
        }
        // }
        return $result;
    }






























    public function setGraphType($type)
    {
        $validTypes = array('graphic', 'histogramHorizontal', 'histogramVertical', 'pieChart');
        $this->graphType = in_array($type, $validTypes) ? $type : $validTypes[0];
    }

    /**
     * метод установки конечной даты анализа
     *
     * @param integer $finish unix timestamp для конечной даты
     */
    public function setFinish($finish)
    {
        if (!is_integer($finish)) {
            throw new invalidParameterException('Значение свойства finish должно быть целочисленного типа и > 0', $finish);
        }

        $this->finish = $finish;

        $this->setInterval($this->interval);
    }

    /**
     * метод установки анализируемого интервала
     *
     * @param string $interval интервал. значение должно быть корректным для передачи первым аргументом в функцию strtotime
     */
    public function setInterval($interval)
    {
        $start = strtotime($interval, $this->finish);

        if (!is_integer($start)) {
            throw new invalidParameterException('Интервал должен задаваться в соответствии с требованиями к входным параметрам функции strtotime', $interval);
        }

        $this->start = $start;
    }

    /**
     * Метод установки имени домена, для которого производятся выборки
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->host_id = $this->searchHostIdByHostname($domain);
    }

    /**
     * Метод для поиска Id домена по его имени
     *
     * @param string $hostname
     * @return integer
     */
    protected function searchHostIdByHostname($hostname)
    {
        $res = l_mysql_query("SELECT `group_id` AS `id` FROM `cms_stat_sites`
                             WHERE `name` = '" . mysql_real_escape_string($hostname) . "'");
        $row = mysql_fetch_assoc($res);
        return (int)$row['id'];
    }

    /**
     * Метод для форматирования даты из unix timestamp в формат mysql
     *
     * @param integer $date искомый timestamp
     * @return string
     */
    protected function formatDate($date)
    {
        return date(self::DATE_FORMAT, $date);
    }

    protected function getQueryInterval()
    {
        return "'" . $this->formatDate($this->start) . "' AND '" . $this->formatDate($this->finish) . "'";
    }

    /**
     * Метод получения полей, по которым будет производиться группировка в зависимости от величины интервала
     *
     * @return string
     *
     * @see auditoryVolumeGrowth::__construct()
     */
    private function calcGroupby()
    {
        if (!empty($this->groupby)) {
            return $this->groupByValid[$this->groupby];
        }
        $daysInterval = ceil(($this->finish - $this->start) / (3600 * 24));

        if ($daysInterval > 180) {
            return $this->groupByValid['month'];
        } elseif ($daysInterval > 30) {
            return $this->groupByValid['week'];
        }

        return $this->groupByValid['day'];
    }

    private function getGroupBySign()
    {
        $groupby = $this->calcGroupby();
        return $groupby == '`day`' ? '%d' : $groupby == '`week`' ? '%u' : '%c';
    }

    public function setGroupBy($groupby)
    {
        if (isset($this->groupByValid[$groupby])) {
            $this->groupby = $groupby;
        }
    }

    private function getOrderByField()
    {
        if ($this->graphType == 'histogramHorizontal') {
            return '`cnt` DESC';
        } elseif ($this->graphType == 'graphic') {
            return '`ts` ASC';
        } else {
            return '`cnt` ASC';
        }
    }
}

?>