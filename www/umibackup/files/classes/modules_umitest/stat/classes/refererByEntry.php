<?php
/**
 * $Id: refererByEntry.php 2 2008-01-31 18:24:39Z leeb $
 *
 * Класс получения информации о связи точек входа и рефереров
 *
 */
 
 class refererByEntry extends simpleStat {
	//----------------------------------------------
	// Variables section	
	protected $params  = array('page_id' => 0);	
	//----------------------------------------------
	// Functions section
	public function get() {		
		$result = array();		
		$sQuery = "SELECT COUNT( * ) AS `count` , `domain`.`name` , `site`.`uri` 
					FROM `cms_stat_sources_sites` AS `site` , 
						 `cms_stat_sources_sites_domains` AS `domain` , 
						 `cms_stat_hits` AS `hit` , 
						 `cms_stat_paths` AS `path` , 
						 `cms_stat_sources` AS `source` 
					WHERE `domain`.`id` = `site`.`domain` 
					  AND `source`.`concrete_src_id` = `site`.`id` 
					  AND `source`.`src_type` =1
					  AND `path`.`source_id` = `source`.`id` 					  
					  AND `hit`.`path_id` = `path`.`id` 
					  AND `hit`.`number_in_path` =1
					  AND `hit`.`page_id`=" . $this->params['page_id'] . " 
					  AND `hit`.`date` BETWEEN ".$this->getQueryInterval().
					  $this->getHostSQL('page') . $this->getUserFilterWhere('path').
					"GROUP BY `site`.`id`";		
		$rQueryResult = l_mysql_query($sQuery);
		while($aRow = mysql_fetch_array($rQueryResult))
			$result[] = $aRow;		
		return $result;
	}
 }

?>