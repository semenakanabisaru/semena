<?php
/**
 * $Id: auditoryVolumeGrowth.php 16 2006-09-17 13:59:49Z zerkms $
 *
 * Класс получения информации о приросте объёма аудитории за период
 *
 */

class auditoryVolumeGrowth extends simpleStat
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

		$qry = "SELECT COUNT(*) AS `cnt`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`.`" . $this->groupby . "` AS `period` FROM `cms_stat_paths` `p`
                 INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
                  INNER JOIN `cms_stat_users` `u` ON `u`.`id` = `p`.`user_id` AND `u`.`first_visit` = `h`.`date`
                   INNER JOIN `cms_stat_pages` `pg` ON `h`.`page_id` = `pg`.`id`
                    WHERE `h`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("pg") .$this->getUserFilterWhere('p') . "
                     GROUP BY `h`.`" . $this->groupby . "`
                      ORDER BY `h`.`date` ASC";

		$result = $this->simpleQuery($qry);

		return array('detail' => $result, 'groupby' => $this->groupby);
	}

	/**
     * Метод получения поля, по которому будет производиться группировка в зависимости от величины интервала
     *
     * @param integer $start
     * @param integer $finish
     * @return string
     *
     * @see auditoryVolumeGrowth::__construct()
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