<?php
/**
 * $Id: userStat.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о пользователе
 *
 */

class fastUserTags extends simpleStat
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
	
	$user_id = (int) $this->params['user_id'];

    $sql = <<<SQL
SELECT DISTINCT
STRAIGHT_JOIN se.name AS `tag` , COUNT( se.name ) AS `cnt` , sec.hit_id
FROM cms_stat_paths sp, cms_stat_hits sh, cms_stat_events_collected sec, cms_stat_events se
WHERE sp.user_id = '{$user_id}'
AND sh.path_id = sp.id
AND sec.hit_id = sh.id
AND se.id = sec.event_id
AND se.type =2
GROUP BY se.name
ORDER BY cnt DESC
SQL;
	$sql_result = l_mysql_query($sql);	
	$result['labels'] = Array();
	while($row = mysql_fetch_assoc($sql_result)) {
		$result['labels'][] = $row;
	}

        return $result;
    }
}

?>