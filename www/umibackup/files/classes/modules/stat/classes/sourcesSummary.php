<?php
/**
 * $Id: sourcesSummary.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения сводной информации о источниках
 *
 */

class sourcesSummary extends simpleStat
{
    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
		$sHostSQL = $this->getHostSQL("p");
        l_mysql_query("SET @all = (SELECT COUNT(*) AS `cnt` FROM `cms_stat_paths` `p`
                      WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $sHostSQL . ")");

        $result['detail'] = $this->simpleQuery("(SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'sites' AS `type`, `d`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                  INNER JOIN `cms_stat_sources_sites` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
                                                   INNER JOIN `cms_stat_sources_sites_domains` `d` ON `d`.`id` = `ss`.`domain`
                                                    WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQLd . " AND `s`.`src_type` = 1
                                                     GROUP BY `d`.`id`)

                                                     UNION
                                                (
                                                SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'search' AS `type`, `sse`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                  INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                                                   INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
                                                    WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 2
                                                     GROUP BY `sse`.`id`)

                                                     UNION
                                                (
                                                SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'pr' AS `type`, `pr`.`name` AS `name`  FROM `cms_stat_paths` `p`
                                                 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                  INNER JOIN `cms_stat_sources_pr` `pr` ON `pr`.`id` = `s`.`concrete_src_id`
                                                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 3
                                                    GROUP BY `pr`.`id`)

                                                    UNION
                                                (
                                                SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'ticket' AS `type`, `t`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                  INNER JOIN `cms_stat_sources_ticket` `t` ON `t`.`id` = `s`.`concrete_src_id`
                                                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 4
                                                    GROUP BY `t`.`id`)

                                                    UNION
                                                (
                                                SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'coupon' AS `type`, `c`.`descript` AS `name` FROM `cms_stat_paths` `p`
                                                 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                  INNER JOIN `cms_stat_sources_coupon` `c` ON `c`.`id` = `s`.`concrete_src_id`
                                                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 5
                                                    GROUP BY `c`.`id`)

                                                    UNION
                                                (
                                                SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'direct' AS `type`, 'direct' AS `name` FROM `cms_stat_paths` `p`
                                                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `p`.`source_id` = 0)

                                                ORDER BY `cnt` DESC
                                                 LIMIT " . $this->offset . ", " . $this->limit);

        $result['segments'] = $this->simpleQuery("SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `cnt`, `type` FROM (
                                                    (SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'sites' AS `type`, `d`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                      INNER JOIN `cms_stat_sources_sites` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
                                                       INNER JOIN `cms_stat_sources_sites_domains` `d` ON `d`.`id` = `ss`.`domain`
                                                        WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 1
                                                         GROUP BY `d`.`id`)

                                                         UNION
                                                    (
                                                    SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'search' AS `type`, `sse`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                      INNER JOIN `cms_stat_sources_search` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
                                                       INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
                                                        WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . "  " . $sHostSQL . " AND `s`.`src_type` = 2
                                                         GROUP BY `sse`.`id`)

                                                         UNION
                                                    (
                                                    SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'pr' AS `type`, `pr`.`name` AS `name`  FROM `cms_stat_paths` `p`
                                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                      INNER JOIN `cms_stat_sources_pr` `pr` ON `pr`.`id` = `s`.`concrete_src_id`
                                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval()  . $this->getUserFilterWhere('p') . " " . $sHostSQL . " AND `s`.`src_type` = 3
                                                        GROUP BY `pr`.`id`)

                                                        UNION
                                                    (
                                                    SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'ticket' AS `type`, `t`.`name` AS `name` FROM `cms_stat_paths` `p`
                                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                      INNER JOIN `cms_stat_sources_ticket` `t` ON `t`.`id` = `s`.`concrete_src_id`
                                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " " . $sHostSQL . " AND `s`.`src_type` = 4
                                                        GROUP BY `t`.`id`)

                                                        UNION
                                                    (
                                                    SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'coupon' AS `type`, `c`.`descript` AS `name` FROM `cms_stat_paths` `p`
                                                     INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
                                                      INNER JOIN `cms_stat_sources_coupon` `c` ON `c`.`id` = `s`.`concrete_src_id`
                                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . "  " . $sHostSQL . " AND `s`.`src_type` = 5
                                                        GROUP BY `c`.`id`)

                                                        UNION
                                                    (
                                                    SELECT COUNT(*) AS `cnt`, COUNT(*) / @all * 100 AS `abs`, 'direct' AS `type`, 'direct' AS `name` FROM `cms_stat_paths` `p`
                                                       WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . "  " . $sHostSQL . " AND `p`.`source_id` = 0)
                                                    ) `tmp`
                                                    GROUP BY `tmp`.`type`
                                                     ORDER BY `cnt` DESC
                                                      LIMIT " . $this->offset . ", " . $this->limit);
        /*
        l_mysql_query("DROP TEMPORARY TABLE IF EXISTS `tmp_pages_refuse`");
        l_mysql_query("CREATE TEMPORARY TABLE `tmp_pages_refuse` (`path_id` INT, `level` INT, KEY `path_id_level` (`path_id`, `level`)) ENGINE = MEMORY");

        l_mysql_query("INSERT INTO `tmp_pages_refuse` SELECT `path_id`, MAX(`number_in_path`) AS `mnum` FROM `cms_stat_hits` `h`
        INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
        WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`host_id` = " . $this->host_id . "
        GROUP BY `path_id`
        HAVING `mnum` = 1");
        l_mysql_query("SET @all_refuses = (SELECT COUNT(*) FROM tmp_pages_refuse)");

        l_mysql_query("SET @all_visits = (SELECT COUNT(*) FROM `cms_stat_paths` `p`
        WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " AND `p`.`host_id` = " . $this->host_id . " )");

        return $this->simpleQuery("SELECT COUNT(DISTINCT(`h`.`path_id`)) AS `refuse`, COUNT(*) AS `entry`, COUNT(DISTINCT(`h`.`path_id`)) / COUNT(*) * 100 AS `refuse_percent`, COUNT(DISTINCT(`h`.`path_id`)) / @all_refuses * 100 AS `all_refuses_percent`, COUNT(DISTINCT(`h`.`path_id`)) /  @all_visits * 100 AS `traffic_lost`, `p`.`uri`, `p`.`id` FROM `tmp_pages_refuse` `t`
        INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `t`.`path_id` AND `h`.`number_in_path` = `t`.`level`
        INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
        INNER JOIN `cms_stat_hits` `h2` ON `h2`.`page_id` = `p`.`id` AND `h2`.`number_in_path` = 1
        WHERE `p`.`host_id` = " . $this->host_id . "
        GROUP BY `h`.`page_id`
        LIMIT 10");
        */
        return $result;
    }
}

?>