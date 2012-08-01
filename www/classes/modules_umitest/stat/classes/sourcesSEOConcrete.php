<?php
/**
 * $Id: sourcesSEOConcrete.php 46 2007-04-14 11:32:26Z zerkms $
 *
 * Класс получения информации о ключевых словах в пределах конкретного поисковика
 *
 */

class sourcesSEOConcrete extends simpleStat
{
	/**
	* массив параметров
	*
	* @var array
	*/
	protected $params = array('engine_id' => 0);

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
									INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
										WHERE `s`.`src_type` = 2 AND `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " AND `ss`.`engine_id` = " . (int)$this->params['engine_id'] . " " . $this->getHostSQL("p") . "
										ORDER BY `cnt` DESC");
		$i_summ = (int) $resSumm[0]['cnt'];

		$all = $this->simpleQuery("SELECT COUNT(*) AS `cnt`, `ssq`.`text` FROM `cms_stat_sources` `s`
									INNER JOIN `cms_stat_sources_search` `ss` ON `s`.`concrete_src_id` = `ss`.`id`
									INNER JOIN `cms_stat_sources_search_queries` `ssq` ON `ssq`.`id` = `ss`.`text_id`
									INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
										WHERE `s`.`src_type` = 2 AND `p`.`date` BETWEEN " . $this->getQueryInterval() . $this->getUserFilterWhere('p')  . " AND `ss`.`engine_id` = " . (int)$this->params['engine_id'] . " " . $this->getHostSQL("p") . "
										GROUP BY `ssq`.`id`
										ORDER BY `cnt` DESC");
		$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
		$i_total = (int) $res[0]['total'];
		return array("all"=>$all, "summ"=>$i_summ, "total"=>$i_total);
	}
}

?>