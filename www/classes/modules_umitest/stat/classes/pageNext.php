<?php
/**
 * $Id: pageNext.php 42 2007-03-12 12:45:05Z zerkms $
 *
 * Класс получения информации о переходах на другие страницы с конкретной страницы
 *
 */

class pageNext extends simpleStat
{
    /**
     * массив параметров
     *
     * @var array
     */
    protected $params = array('page_id' => '', 'page_uri' => '');
    
    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {

	if(!$this->params['page_uri']) {
		$page_id = $this->params['page_id'];
	} else {

		$sql = "SELECT id FROM cms_stat_pages WHERE uri = '" . mysql_real_escape_string($this->params['page_uri']) . "' " . $this->getHostSQL("") . " LIMIT 1";
		list($page_id) = mysql_fetch_row(l_mysql_query($sql));
	}


        return $this->simpleQuery("SELECT COUNT(*) AS `abs`, `p`.`uri`, `p`.`id` FROM `cms_stat_hits` `h`
                                                     INNER JOIN `cms_stat_hits` `h2` ON `h2`.`prev_page_id` = `h`.`page_id` AND `h2`.`number_in_path` = `h`.`number_in_path` + 1 AND `h2`.`path_id` = `h`.`path_id`
                                                      INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h2`.`page_id`
                                                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " AND `h`.`page_id` = " . (int) $page_id . " " . $this->getHostSQL("p") . "
                                                        GROUP BY `h2`.`page_id`
                                                         ORDER BY `abs` DESC");
    }
}

?>