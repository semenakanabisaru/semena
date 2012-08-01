<?php
/**
 * $Id: entryByReferer.php 1 2008-01-30 13:00:00Z leeb $
 *
 * Класс получения информации о связи точек входа и рефереров
 *
 */
 
 class entryByReferer extends simpleStat {
	//----------------------------------------------
	// Variables section	
	protected $params  = array('source_id' => 0);	
	//----------------------------------------------
	// Functions section
	public function get() {		
		$result = array();		
		$sQuery = "SELECT COUNT(*) AS `count` , `page`.`uri`, `page`.`section`  
				   FROM `cms_stat_pages` AS `page`, `cms_stat_hits` AS `hit`, 
				        `cms_stat_paths` AS `path`, `cms_stat_sources` AS `source` 
				   WHERE `source`.`concrete_src_id`=".$this->params['source_id']."
					 AND `source`.`src_type` = 1
					 AND `path`.`source_id` = `source`.`id`
					 AND `hit`.`path_id`=`path`.`id` 
					 AND `hit`.`number_in_path`=1
					 AND `page`.`id`=`hit`.`page_id`
					 AND `hit`.`date` BETWEEN ".$this->getQueryInterval()." 
					 ".$this->getHostSQL('page') . $this->getUserFilterWhere('path') ." 
				   GROUP BY `page`.`id`";		
		$rQueryResult = l_mysql_query($sQuery);
		while($aRow = mysql_fetch_array($rQueryResult))
			$result[] = $aRow;		
		return $result;
	}
 }

?>