<?php
/**
 * $Id: cityStat.php 1 2008-01-29 18:21:39Z leeb $
 *
 * Класс получения информации о наиболее активных городах
 *
 */
 
 class cityStat extends simpleStat {
	public function get() {
		$result = array();		
		$sQuery = "SELECT COUNT(*) AS `count`, `location` 
		           FROM `cms_stat_users` 
				   WHERE 1 ".$this->getHostSQL() . 
                   (!empty($this->user_id)?' AND id IN '.implode(', ', $this->user_id):'') ."  
				   GROUP BY `location` 
				   ORDER BY `count` DESC LIMIT 15";		
		$rQueryResult = l_mysql_query($sQuery);
		while($aRow = mysql_fetch_assoc($rQueryResult))
			$result[] = $aRow;		
		return $result;
	}
 }

?>