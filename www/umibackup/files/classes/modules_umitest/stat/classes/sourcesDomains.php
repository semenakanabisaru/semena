<?php
/**
 * $Id: sourcesDomains.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о ссылающихся доменах
 *
 */

class sourcesDomains extends simpleStat
{
	/**
	* массив параметров
	*
	* @var array
	*/
	//protected $params = array('section' => '');

	/**
	* Метод получения отчёта
	*
	* @return array
	*/
	  public function get()
	{
		$resSumm = $this->simpleQuery("SELECT COUNT(*) AS `cnt`  FROM `cms_stat_sources_sites` `ss`
									INNER JOIN `cms_stat_sources_sites_domains` `ssd` ON `ssd`.`id` = `ss`.`domain`
									 INNER JOIN `cms_stat_sources` `s` ON `s`.`concrete_src_id` = `ss`.`id` AND `s`.`src_type` = 1
									  INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
									   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
										 ORDER BY `cnt` DESC
										  LIMIT " . $this->offset . ", " . $this->limit);
		$i_summ = (int) $resSumm[0]['cnt'];

		$all = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `cnt`, `ssd`.`name`, `ss`.`domain` AS `domain_id`  FROM `cms_stat_sources_sites` `ss`
									INNER JOIN `cms_stat_sources_sites_domains` `ssd` ON `ssd`.`id` = `ss`.`domain`
									 INNER JOIN `cms_stat_sources` `s` ON `s`.`concrete_src_id` = `ss`.`id` AND `s`.`src_type` = 1
									  INNER JOIN `cms_stat_paths` `p` ON `p`.`source_id` = `s`.`id`
									   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p')  . "
										GROUP BY `ss`.`domain`
										 ORDER BY `cnt` DESC
										  LIMIT " . $this->offset . ", " . $this->limit);
		$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
		$i_total = (int) $res[0]['total'];
		return array("all"=> $all, "summ"=>$i_summ, "total"=>$i_total);
	}
}

?>