<?php
/**
 * $Id: userStat.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о пользователе
 *
 */

class userStat extends simpleStat
{
    /**
     * массив параметров
     *
     * @var array
     */
    protected $params = array('user_id' => 0);

    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        $result = array();
		
		$sHostSQL = $this->getHostSQL("p");

        if ((int)$this->params['user_id']) {
            $tmp = $this->simpleQuery("SELECT `login`, UNIX_TIMESTAMP(`first_visit`) AS `first_visit`, `o`.`name` AS `os`, `b`.`name` AS `browser`, `location`, `js_version` FROM `cms_stat_users` `u`
                                             INNER JOIN `cms_stat_users_os` `o` ON `o`.`id` = `u`.`os_id`
                                              INNER JOIN `cms_stat_users_browsers` `b` ON `b`.`id` = `u`.`browser_id`
                                               WHERE `u`.`id` = " . (int)$this->params['user_id']);

            if (!isset($tmp[0])) {
                $tmp[0] = array('first_visit' => 0, 'name' => '', 'os' => 'browser', 'location' => '', 'js_version' => '');
            }
            $result = $tmp[0];
            unset($tmp);


            l_mysql_query("SET @visits_count = (SELECT COUNT(*) AS `visits_cnt` FROM `cms_stat_paths` `p`
                         INNER JOIN `cms_stat_users` `u` ON `p`.`user_id` = `u`.`id`
                          WHERE " . $sHostSQL . " AND `p`.`user_id` = " . (int)$this->params['user_id'] . ")");

            $tmp = $this->simpleQuery("SELECT @visits_count AS `cnt`");
            $result['visit_count'] = $tmp[0]['cnt'];
            unset($tmp);

            l_mysql_query("SET @first_path = (SELECT MIN(`id`) FROM `cms_stat_paths` WHERE `user_id` = " . (int)$this->params['user_id'] . ")");

            $tmp = $this->simpleQuery("SELECT `src_type`, `concrete_src_id` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `p`.`source_id` = `s`.`id`
                                          WHERE `p`.`id` = @first_path" . $this->getUserFilterWhere('p') );
            if (isset($tmp[0])) {
                $tmp = $tmp[0];
                $tmp = $this->simpleQuery("(SELECT 'sites' AS `type`, `d`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_sites` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
                                           INNER JOIN `cms_stat_sources_sites_domains` `d` ON `d`.`id` = `ss`.`domain`
                                            WHERE `s`.`src_type` = 1 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id']  . $this->getUserFilterWhere('p') . "
                                             )

                                             UNION
                                        (
                                        SELECT 'search' AS `type`, `sse`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                                           INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
                                            WHERE `s`.`src_type` = 2  AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id']  . $this->getUserFilterWhere('p') . "
                                             )

                                             UNION
                                        (
                                        SELECT 'pr' AS `type`, `pr`.`name` AS `name`  FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_pr` `pr` ON `pr`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 3 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id']  . $this->getUserFilterWhere('p') . "
                                            )

                                            UNION
                                        (
                                        SELECT 'ticket' AS `type`, `t`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_ticket` `t` ON `t`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 4 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id']  . $this->getUserFilterWhere('p') . "
                                            )

                                            UNION
                                        (
                                        SELECT 'coupon' AS `type`, `c`.`descript` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_coupon` `c` ON `c`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 5 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id']  . $this->getUserFilterWhere('p') . "
                                            )

                                            UNION
                                        (
                                        SELECT 'direct' AS `type`, 'direct' AS `name` FROM `cms_stat_paths` `p`
                                           WHERE `p`.`source_id` = 0 AND 0 = " . (int)$tmp['src_type'] . $this->getUserFilterWhere('p')  . ")");

                $result['source'] = $tmp[0];
                unset($tmp);
            }

            $tmp = $this->simpleQuery("SELECT IF(MAX(`number_in_path`) > 1, 0, 1) AS `first_visit_refuse` FROM `cms_stat_hits` `h`
                                         WHERE `h`.`path_id` = @first_path;");
            $result['first_visit_refuse'] = (string)$tmp[0]['first_visit_refuse'];
            unset($tmp);

            $result['first_path'] = $this->simpleQuery("SELECT `p`.`id`, `p`.`uri` FROM `cms_stat_hits` `h`
                                         INNER JOIN `cms_stat_pages` `p` ON `h`.`page_id` = `p`.`id`
                                          WHERE `h`.`path_id` = @first_path
                                           ORDER BY `h`.`id`");

            $tmp = $this->simpleQuery("SELECT  ( UNIX_TIMESTAMP(MAX(`date`)) - UNIX_TIMESTAMP(MIN(`date`)) ) / (COUNT(*) - 1) / 3600 / 24  AS `days` FROM `cms_stat_paths`
                                         WHERE `date` BETWEEN " . $this->getQueryInterval() . " AND " . $this->getHostSQL() . " AND `user_id` = " . (int)$this->params['user_id']);
            $result['visit_frequency'] = $tmp[0]['days'];
            unset($tmp);

            $tmp = $this->simpleQuery("SELECT COUNT(*) AS `abs`, COUNT(*) / @visits_count * 100 AS `rel`  FROM `cms_stat_hits` `h`
                                         INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                          LEFT JOIN `cms_stat_hits` `h2` ON `h2`.`path_id` = `h`.`path_id` AND `h2`.`number_in_path` = 2
                                           WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`number_in_path` = 1 AND `h2`.`id` IS NULL AND `p`.`user_id` = " . (int)$this->params['user_id'] . "  " . $sHostSQL);
            $result['refuse_frequency'] = $tmp[0];
            unset($tmp);

            l_mysql_query("SET @hits_count = (SELECT COUNT(*) FROM `cms_stat_hits` `h`
                         INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                          INNER JOIN `cms_stat_pages` `pg` ON `pg`.`id` = `h`.`page_id`
                           WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`user_id` = " . (int)$this->params['user_id'] . " " . $sHostSQL . ")");

            $result['top_pages'] = $this->simpleQuery("SELECT COUNT(*) AS `abs`, COUNT(*) / @hits_count * 100 AS `rel`, `pg`.`id`, `pg`.`uri` FROM `cms_stat_hits` `h`
                                         INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                                          INNER JOIN `cms_stat_pages` `pg` ON `pg`.`id` = `h`.`page_id`
                                           WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`user_id` = " . (int)$this->params['user_id'] . "
                                            GROUP BY `pg`.`id`
                                             ORDER BY `abs` DESC
                                              LIMIT " . $this->offset . ", " . $this->limit);

            $tmp = $this->simpleQuery("SELECT UNIX_TIMESTAMP(`p`.`date`) AS `last_visit`, `s`.`concrete_src_id`, `s`.`src_type` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `p`.`source_id` = `s`.`id`
                                          WHERE `p`.`user_id` = " . (int)$this->params['user_id'] . " " . $sHostSQL . "
                                           ORDER BY `p`.`id` DESC
                                            LIMIT 1");

            if (isset($tmp[0])) {
                $result['last_visit'] = $tmp[0]['last_visit'];

                $tmp = $tmp[0];
                $tmp = $this->simpleQuery("(SELECT 'sites' AS `type`, `d`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_sites` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
                                           INNER JOIN `cms_stat_sources_sites_domains` `d` ON `d`.`id` = `ss`.`domain`
                                            WHERE `s`.`src_type` = 1 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id'] . "
                                             )

                                             UNION
                                        (
                                        SELECT 'search' AS `type`, `sse`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                                           INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
                                            WHERE `s`.`src_type` = 2  AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id'] . "
                                             )

                                             UNION
                                        (
                                        SELECT 'pr' AS `type`, `pr`.`name` AS `name`  FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_pr` `pr` ON `pr`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 3 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id'] . "
                                            )

                                            UNION
                                        (
                                        SELECT 'ticket' AS `type`, `t`.`name` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_ticket` `t` ON `t`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 4 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id'] . "
                                            )

                                            UNION
                                        (
                                        SELECT 'coupon' AS `type`, `c`.`descript` AS `name` FROM `cms_stat_paths` `p`
                                         INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                          INNER JOIN `cms_stat_sources_coupon` `c` ON `c`.`id` = `s`.`concrete_src_id`
                                           WHERE `s`.`src_type` = 5 AND `s`.`src_type` = " . (int)$tmp['src_type'] . " AND `s`.`concrete_src_id` = " . (int)$tmp['concrete_src_id'] . "
                                            )

                                            UNION
                                        (
                                        SELECT 'direct' AS `type`, 'direct' AS `name` FROM `cms_stat_paths` `p`
                                           WHERE `p`.`source_id` = 0 AND 0 = " . (int)$tmp['src_type'] . ")");
                $result['last_source'] = $tmp[0];
                unset($tmp);
            }

            l_mysql_query("SET @last_path = (SELECT `p`.`id` FROM `cms_stat_paths` `p`
                         WHERE `p`.`user_id` = " . (int)$this->params['user_id'] . "  " . $sHostSQL . "
                          ORDER BY `p`.`id` DESC
                           LIMIT 1)");

            $tmp = $this->simpleQuery("SELECT IF(MAX(`number_in_path`) > 1, 0, 1) AS `last_visit_refuse` FROM `cms_stat_hits` `h`
                                 WHERE `h`.`path_id` = @last_path");
            $result['last_visit_refuse'] = (string)$tmp[0]['last_visit_refuse'];
            unset($tmp);

            $result['last_path'] = $this->simpleQuery("SELECT `p`.`id`, `p`.`uri` FROM `cms_stat_hits` `h`
                                                         INNER JOIN `cms_stat_pages` `p` ON `h`.`page_id` = `p`.`id`
                                                          WHERE `h`.`path_id` = @last_path
                                                           ORDER BY `h`.`id`");

            l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_collected_events`");
            l_mysql_query("CREATE TEMPORARY TABLE `tmp_collected_events` (`id` INT, `name` CHAR(255), `description` CHAR(255), `profit` float(9,2), KEY `id` (`id`)) ENGINE = MEMORY");

            l_mysql_query("INSERT INTO `tmp_collected_events` SELECT IFNULL(`e2`.`id`, `e`.`id`), IFNULL(`e2`.`name`, `e`.`name`), IFNULL(`e2`.`description`, `e`.`description`), IFNULL(`e2`.`profit`, `e`.`profit`) FROM `cms_stat_hits` `h`
                             INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                              INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `h`.`id`
                               INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                                LEFT JOIN `cms_stat_events_rel` `er` ON `er`.`metaevent_id` = `e`.`id`
                                 LEFT JOIN `cms_stat_events` `e2` ON `e2`.`id` = `er`.`event_id`
                                  WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`user_id` = " . (int)$this->params['user_id'] . " AND `e`.`type` = 1  " . $sHostSQL);

            $result['collected_events'] = $this->simpleQuery("SELECT COUNT(*) AS `cnt`, `name`, `description` FROM `tmp_collected_events`
                                                                 GROUP BY `id`
                                                                  ORDER BY `cnt` DESC");

            $tmp = $this->simpleQuery("SELECT SUM(`profit`) AS `profit` FROM `tmp_collected_events`");
            $result['profit'] = $tmp[0]['profit'];
            unset($tmp);

            l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_collected_labels`");
            l_mysql_query("CREATE TEMPORARY TABLE `tmp_collected_labels` (`id` INT, `name` CHAR(255), `description` CHAR(255), KEY `id` (`id`)) ENGINE = MEMORY");

            l_mysql_query("INSERT INTO `tmp_collected_labels` SELECT IFNULL(`e2`.`id`, `e`.`id`), IFNULL(`e2`.`name`, `e`.`name`), IFNULL(`e2`.`description`, `e`.`description`) FROM `cms_stat_hits` `h`
                             INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                              INNER JOIN `cms_stat_events_collected` `ec` ON `ec`.`hit_id` = `h`.`id`
                               INNER JOIN `cms_stat_events` `e` ON `e`.`id` = `ec`.`event_id`
                                LEFT JOIN `cms_stat_events_rel` `er` ON `er`.`metaevent_id` = `e`.`id`
                                 LEFT JOIN `cms_stat_events` `e2` ON `e2`.`id` = `er`.`event_id`
                                  WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`user_id` = " . (int)$this->params['user_id'] . " AND `e`.`type` = 2");

            $result['labels']['top'] = $this->simpleQuery("SELECT COUNT(*) AS `cnt`, `name`, `description` FROM `tmp_collected_labels`
                                         GROUP BY `id`
                                          ORDER BY `cnt` DESC");
            unset($tmp);

            /*$result['labels']['collected'] = $this->simpleQuery("SELECT `name`, `description` FROM `tmp_collected_labels`
                                                                   GROUP BY `id`");*/

        }
        return $result;
    }
}

?>