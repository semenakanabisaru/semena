<?php
/**
 * $Id: userStat.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о пользователе
 *
 */

class allTags extends simpleStat
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
		
		$sCacheId = md5( ((is_array($this->host_id)) ? implode("", $this->host_id) : $this->host_id ).
						 ":".(is_array($this->user_id) ? implode("", $this->user_id) : $this->user_id));
		if( isset($_GLOBALS['stat']['allTags'][$sCacheId]) )
			return $_GLOBALS['stat']['allTags'][$sCacheId];

		$hids = array();
		if(is_array($this->user_id) && !empty($this->user_id)) {
            $sql = "SELECT hit.id as `hid` FROM cms_stat_hits hit,
                   cms_stat_paths path WHERE 
                   hit.path_id=path.id AND path.user_id IN (".implode(",",$this->user_id).") AND 
                   path.date BETWEEN ".$this->getQueryInterval();
            $sql_result = l_mysql_query($sql);
            while($row = mysql_fetch_assoc($sql_result))
                $hids[] = $row['hid'];            
        } else if(is_array($this->user_login) && !empty($this->user_login)) {
			$sql = "SELECT hit.id as `hid` FROM cms_stat_hits hit,
			       cms_stat_paths path, cms_stat_users user WHERE 
				   hit.path_id=path.id AND path.user_id=user.id AND
                   path.date BETWEEN ".$this->getQueryInterval()." AND 
				   user.login IN (".implode(",",$this->user_login).")";
			$sql_result = l_mysql_query($sql);
			while($row = mysql_fetch_assoc($sql_result))
				$hids[] = $row['hid'];			
		} else {
            $sql = "SELECT hit.id as `hid` FROM cms_stat_hits hit,
                   cms_stat_paths path, cms_stat_users user WHERE 
                   hit.path_id=path.id AND path.user_id=user.id AND
                   path.date BETWEEN ".$this->getQueryInterval(). " ORDER BY hid DESC LIMIT 300";
            $sql_result = l_mysql_query($sql);
            if( $sql_result )
            while($row = mysql_fetch_assoc($sql_result))
                $hids[] = $row['hid'];
        }
	
	$sql = "SELECT se.id as `id`, se.name as `tag`, COUNT(*) as `cnt` ".
		   "FROM cms_stat_events se, cms_stat_events_collected sec ".
		   "WHERE se.type = 2 AND sec.event_id = se.id ".
		   $this->getHostSQL("se")." ".
		   ((!empty($hids)||!empty($this->user_id)) ? " AND sec.hit_id IN (".implode(",",$hids).") " : "").
		   " GROUP BY sec.event_id LIMIT 50";
    
	$sql_result = l_mysql_query($sql);
	
	$result['labels'] = Array();
	$max = 0;
	$sum = 0;
	while($row = @mysql_fetch_assoc($sql_result)) {
		$result['labels'][] = $row;
		
		if($row['cnt'] > $max) {
			$max = $row['cnt'];
		}
		$sum += $row['cnt'];
	}
	
	$result['max'] = $max;
	$result['sum'] = $sum;
		
		$_GLOBALS['stat']['allTags'][$sCacheId] = $result;
	                       
        return $result;     
    }
}

?>