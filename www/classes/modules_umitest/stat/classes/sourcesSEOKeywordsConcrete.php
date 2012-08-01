<?php
/**
 * $Id: sourcesSEOKeywordsConcrete.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о числе запросов с разных поисковиков по конкретному слову
 *
 */

class sourcesSEOKeywordsConcrete extends simpleStat
{
	/**
	 * массив параметров
	 *
	 * @var array
	 */
	protected $params = array('query_id' => 0);

	/**
	 * Метод получения отчёта
	 *
	 * @return array
	 */
	public function get()
	{

		$resSumm = $this->simpleQuery("SELECT COUNT(*) AS `cnt` FROM `cms_stat_sources` `s`
									INNER JOIN `cms_stat_sources_search` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
									INNER JOIN `cms_stat_sources_search_queries` `ssq` ON `ssq`.`id` = `ss`.`text_id`
									INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
										INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
										WHERE `s`.`src_type` = 2 AND `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " AND `ssq`.`id` = " . (int)$this->params['query_id'] . " " . $this->getHostSQL("p") . "
										ORDER BY `cnt` DESC");
		$i_summ = (int) $resSumm[0]['cnt'];

		$all = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `cnt`, `sse`.`name`, `sse`.`id` FROM `cms_stat_sources` `s`
									INNER JOIN `cms_stat_sources_search` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
									INNER JOIN `cms_stat_sources_search_queries` `ssq` ON `ssq`.`id` = `ss`.`text_id`
									INNER JOIN `cms_stat_sources_search_engines` `sse` ON `sse`.`id` = `ss`.`engine_id`
										INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
										WHERE `s`.`src_type` = 2 AND `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " AND `ssq`.`id` = " . (int)$this->params['query_id'] . " " . $this->getHostSQL("p") . "
										GROUP BY `sse`.`name`
										ORDER BY `cnt` DESC");
		$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
		$i_total = (int) $res[0]['total'];
		return array("all"=>$all, "summ"=>$i_summ, "total"=>$i_total);
	}
}

?>