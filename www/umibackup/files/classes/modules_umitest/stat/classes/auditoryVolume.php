<?php
/**
 * $Id: auditoryVolume.php 16 2006-09-17 13:59:49Z zerkms $
 *
 * Класс получения информации об объёме аудитории за период
 *
 */

class auditoryVolume extends simpleStat
{
	/**
     * Имя поля, по которому происходит группировка данных
     *
     * @var string
     */
	private $groupby;

	/**
     * интервал по умолчанию
     *
     * @var string
     */
	protected $interval = '-1 year';

	/**
     * Метод получения отчёта
     *
     * @return array
     */
	public function get()
	{
		$this->groupby = $this->calcGroupby($this->start, $this->finish);

		$qry = "SELECT COUNT(DISTINCT(`p`.`user_id`)) AS `cnt`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`.`date`, `h`.`" . $this->groupby . "` AS `period` FROM `cms_stat_hits` `h`
                     INNER JOIN `cms_stat_pages` `pg` ON `pg`.`id` = `h`.`page_id`
                      INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
                       WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") .$this->getUserFilterWhere('p'). "
                        GROUP BY `h`.`" . $this->groupby . "`
                         ORDER BY `h`.`date` ASC";

		$res = l_mysql_query($qry);

		$result = array();

		while ($row = mysql_fetch_assoc($res)) {
			$result[] = array('ts' => $row['ts'], 'cnt' => $row['cnt'], 'period' => $row['period']);

		}

		return array('detail' => $result, 'groupby' => $this->groupby);
	}

	/**
     * Метод получения поля, по которому будет производиться группировка в зависимости от величины интервала
     *
     * @param integer $start
     * @param integer $finish
     * @return string
     */
	private function calcGroupby($start, $finish)
	{
		$daysInterval = ceil(($finish - $start) / (3600 * 24));

		if ($daysInterval > 30) {
			return 'week';
		} elseif ($daysInterval > 7) {
			return 'day';
		}
		return 'hour';
	}
}

?>