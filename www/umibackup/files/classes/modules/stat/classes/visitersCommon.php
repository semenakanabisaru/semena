<?php
/**
 * $Id: visitersCommon.php 19 2006-09-25 11:54:23Z zerkms $
 *
 * Класс получения обобщённой информации о посетителях
 *
 */

require_once dirname(__FILE__) . '/holidayRoutineCounter.php';

class visitersCommon extends simpleStat
{
	/**
	 * Число выходных дней за период
	 *
	 * @var integer
	 */
	private $holidays_count = 0;

	/**
	 * Число будних дней за период
	 *
	 * @var integer
	 */
	private $routine_count = 0;

	protected $interval = '-10 days';

	public function __construct($finish = null, $interval = null)
	{
		$finish = time();
		parent::__construct($finish);
	}

	/**
	 * Метод получения отчёта
	 *
	 * @return array
	 */
	public function get()
	{
		$arrDetail = $this->getDetail();
		return array('detail' => $arrDetail['all'], 'avg' => $this->getAvg(), 'summ' => $this->getSumm(), 'total' => $arrDetail['total']);
	}

	private function getSumm() {
		$this->setUpVars();

		$sQueryInterval = $this->getQueryInterval();
		$sQrHost = $this->host_id;

		$sHostSQL = $this->getHostSQL();
		$sQr = "
			SELECT
				COUNT(*) AS `cnt`
			FROM
				`cms_stat_paths`
			WHERE
				`date` BETWEEN ".$sQueryInterval."
				".$sHostSQL . $this->getUserFilterWhere()."
			ORDER BY
				`date` ASC
			LIMIT ".$this->offset.", ".$this->limit;
        	$resSumm = $this->simpleQuery($sQr);
		$i_summ = (int) $resSumm[0]['cnt'];
		return $i_summ;
	}

	/**
	 * метод получения сводной информации о числе посещений за каждый из дней выбранного интервала
	 *
	 * @return array
	 */
	private function getDetail()
	{
		$this->setUpVars();

		$sQueryInterval = $this->getQueryInterval();
		$sQrHost = $this->host_id;

		$sHostSQL = $this->getHostSQL("p");
		$sQr = "
			SELECT SQL_CALC_FOUND_ROWS
				COUNT(*) AS `cnt`,
				UNIX_TIMESTAMP(`p`.`date`) AS `ts`
			FROM
				`cms_stat_paths` `p`
			WHERE
				`p`.`date` BETWEEN ".$sQueryInterval." 
				".$sHostSQL . $this->getUserFilterWhere('p')."
			GROUP BY
				DATE_FORMAT(`p`.`date`, '%d')
			ORDER BY
				`ts` ASC
			LIMIT ".$this->offset.", ".$this->limit;
			

		$all = $this->simpleQuery($sQr);
		$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
		$i_total = (int) $res[0]['total'];
		return array("all"=> $all, "total"=>$i_total);
	}

	/**
	 * метод получения среднего числа посещений за выходные и будни
	 *
	 * @return array
	 */
	private function getAvg()
	{
		$this->setUpVars();

		$qry = "(SELECT 'routine' AS `type`, COUNT(*) / " . $this->routine_count . ".0 AS `avg` FROM `cms_stat_paths` `p`
				INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
				LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
				WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . " " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
					AND `day_of_week` BETWEEN 1 AND 5 AND `holidays`.`id` IS NULL)
				UNION
				(SELECT 'weekend' AS `type`, COUNT(*) / " . $this->holidays_count . ".0 AS `avg` FROM `cms_stat_paths` `p`
				INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
				LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
				WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
					AND (`day_of_week` NOT BETWEEN 1 AND 5 OR `holidays`.`id` IS NOT NULL))";

		$res = l_mysql_query($qry);

		$result = array();

		while ($row = mysql_fetch_assoc($res)) {
			$result[$row['type']] = $row['avg'];
		}

		return $result;
	}

	/**
	 * метод установки необходимых для работы класса переменных
	 *
	 */
	private function setUpVars()
	{
		$res = holidayRoutineCounter::count($this->start, $this->finish);
		$this->holidays_count = $res['holidays'];
		$this->routine_count = $res['routine'];
	}
}

?>