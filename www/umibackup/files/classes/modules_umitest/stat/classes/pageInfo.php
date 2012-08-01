<?php
/**
 * $Id: pageInfo.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о конкретной странице
 *
 */

class pageInfo extends simpleStat
{
    /**
     * массив параметров
     *
     * @var array
     */
    protected $params = array('page_id' => '');

    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        $result = array();
        $result['source'] = $this->simpleQuery("(SELECT COUNT(*) AS `cnt`, 'sites' AS `type`, `d`.`name` AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                      INNER JOIN `cms_stat_sources_sites` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
                                       INNER JOIN `cms_stat_sources_sites_domains` `d` ON `d`.`id` = `ss`.`domain`
                                        WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p') . " AND `s`.`src_type` = 1 AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1
                                         GROUP BY `d`.`id`)

                                         UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'search' AS `type`, `sse`.`name` AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                      INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                                       INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
                                        WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p') . " AND `s`.`src_type` = 2 AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1
                                         GROUP BY `sse`.`id`)

                                         UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'pr' AS `type`, `pr`.`name` AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                      INNER JOIN `cms_stat_sources_pr` `pr` ON `pr`.`id` = `s`.`concrete_src_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p') . " AND `s`.`src_type` = 3 AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1
                                        GROUP BY `pr`.`id`)

                                        UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'ticket' AS `type`, `t`.`name` AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                      INNER JOIN `cms_stat_sources_ticket` `t` ON `t`.`id` = `s`.`concrete_src_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p') . " AND `s`.`src_type` = 4 AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1
                                        GROUP BY `t`.`id`)

                                        UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'coupon' AS `type`, `c`.`descript` AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                      INNER JOIN `cms_stat_sources_coupon` `c` ON `c`.`id` = `s`.`concrete_src_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `s`.`src_type` = 5 AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1
                                        GROUP BY `c`.`id`)

                                        UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'direct' AS `type`, 'direct' AS `name` FROM `cms_stat_hits` `h`
                                    INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                     WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p') . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`number_in_path` = 1 AND `p`.`source_id` = 0
                                      GROUP BY `h`.`page_id`)

                                        UNION
                                    (
                                    SELECT COUNT(*) AS `cnt`, 'page' AS `type`, `p`.`uri` AS `name` FROM `cms_stat_hits` `h`
                                     INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`prev_page_id`
                                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . "
                                       GROUP BY `p`.`uri`)

                                    ORDER BY `cnt` DESC
                                     LIMIT " . $this->offset . ", " . $this->limit);

        l_mysql_query("SET @all = (SELECT COUNT(*) FROM `cms_stat_hits` `h`
                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . ")");

        $tmp = $this->simpleQuery("SELECT COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel` FROM `cms_stat_hits` `h`
                                                 INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                                  WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " " . $this->getHostSQL("p") . "
                                                   GROUP BY `p`.`id`");
        if (!isset($tmp[0])) {
            $tmp[0] = array('abs' => 0, 'rel' => 0);
        }
        $result['visits'] = $tmp[0];
        unset($tmp);

        $result['next'] = $this->simpleQuery("SELECT COUNT(*) AS `abs`, `p`.`uri`, `p`.`id` FROM `cms_stat_hits` `h`
                                                     INNER JOIN `cms_stat_hits` `h2` ON `h2`.`prev_page_id` = `h`.`page_id` AND `h2`.`number_in_path` = `h`.`number_in_path` + 1 AND `h2`.`path_id` = `h`.`path_id`
                                                      INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h2`.`page_id`
                                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " " . $this->getHostSQL("p") . "
                                                        GROUP BY `h2`.`page_id`
                                                         ORDER BY `abs` DESC
                                                          LIMIT " . $this->offset . ", " . $this->limit);

        $tmp = $this->simpleQuery("SELECT COUNT(*) AS `abs` FROM `cms_stat_hits` `h`
                                                 INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                                  WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`number_in_path` = 1 " . $this->getHostSQL("p") . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . "
                                                   GROUP BY `p`.`id`");
        if (!isset($tmp[0])) {
            $tmp[0] = array('abs' => 0);
        }
        $result['entry'] = $tmp[0];
        unset($tmp);

        l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_paths_out`");
        l_mysql_query("CREATE TEMPORARY TABLE `tmp_paths_out` (`level`INT, `path_id` INT, KEY `path_id_level` (`path_id`, `level`)) ENGINE = MEMORY");

        l_mysql_query("INSERT INTO `tmp_paths_out` (SELECT MAX(`number_in_path`) AS `level`, `path_id` FROM `cms_stat_hits` `h`
                     INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . "
                       GROUP BY `path_id`)");

        $tmp = $this->simpleQuery("SELECT COUNT(*) AS `abs` FROM `cms_stat_hits` `h`
                                     INNER JOIN `tmp_paths_out` `t` ON `h`.`path_id` = `t`.`path_id` AND `h`.`number_in_path` = `t`.`level`
                                      INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . "
                                        GROUP BY `h`.`page_id`");

        if (!isset($tmp[0])) {
            $tmp[0] = array('abs' => 0);
        }
        $result['exit'] = $tmp[0];
        unset($tmp);

        $tmp = $this->simpleQuery("SELECT COUNT(*) AS `abs` FROM `cms_stat_hits` `h`
                                     LEFT JOIN `cms_stat_hits` `h2` ON `h2`.`path_id` = `h`.`path_id` AND `h2`.`number_in_path` = `h`.`number_in_path` + 1
                                      INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h2`.`id` IS NULL AND `h`.`number_in_path` = 1");

        if (!isset($tmp[0])) {
            $tmp[0] = array('abs' => 0);
        }
        $result['refuse'] = $tmp[0];
        unset($tmp);

        list($result['profit']['direct']) = $this->simpleQuery("SELECT COUNT(*) AS `abs`, (IFNULL(SUM(`e2`.`profit`), 0) + IFNULL(SUM(`e`.`profit`), 0)) AS `profit` FROM `cms_stat_hits` `h`
                                                                 INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `h`.`id`
                                                                  INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                                                                   LEFT JOIN `cms_stat_events_rel` `er` ON `er`.`metaevent_id` = `e`.`id`
                                                                    LEFT JOIN `cms_stat_events` `e2` ON `e2`.`id` = `er`.`event_id`
                                                                     INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
                                                                      WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `e`.`type` = 1");

        list($result['profit']['nonDirect']) = $this->simpleQuery("SELECT COUNT(*) AS `abs`, (IFNULL(SUM(`e2`.`profit`), 0) + IFNULL(SUM(`e`.`profit`), 0)) AS `profit` FROM `cms_stat_hits` `h`
                                                                     INNER JOIN `cms_stat_hits` `h2` ON `h2`.`path_id` = `h`.`path_id` AND `h2`.`number_in_path` > `h`.`number_in_path` AND `h2`.`page_id` != `h`.`page_id`
                                                                      INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `h2`.`id`
                                                                       INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                                                                        LEFT JOIN `cms_stat_events_rel` `er` ON `er`.`metaevent_id` = `e`.`id`
                                                                         LEFT JOIN `cms_stat_events` `e2` ON `e2`.`id` = `er`.`event_id`
                                                                          INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h2`.`page_id`
                                                                           WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`page_id` = " . (int)$this->params['page_id'] . " AND `h`.`id` = (SELECT MIN(`h2`.`id`) FROM `cms_stat_hits` `h2` WHERE `h2`.`page_id` = `h`.`page_id` AND `path_id` = `h`.`path_id`) AND `e`.`type` = 1 " . $this->getHostSQL("p"));

        return $result;
    }
}

?>