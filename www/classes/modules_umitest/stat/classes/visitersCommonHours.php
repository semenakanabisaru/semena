<?php
/**
 * $Id: visitersCommonHours.php 19 2006-09-25 11:54:23Z zerkms $
 *
 * Класс получения обобщённой информации о посетителях, срез по часам
 *
 */

//require_once 'classes/holidayRoutineCounter.php';
require_once dirname(__FILE__).'/holidayRoutineCounter.php';

class visitersCommonHours extends simpleStat
{
    /**
     * Число выходных дней за период
     *
     * @var integer
     */
    private $weekends_count = 0;

    /**
     * Число будних дней за период
     *
     * @var integer
     */
    private $routine_count = 0;

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

		$sQrInterval = $this->getQueryInterval();
		$sQrHost = $this->getHostSQL("p");
        $sQrUsr  =  $this->getUserFilterWhere('p');

		$sQr = <<<END
			SELECT
				COUNT(*) AS `cnt`
			FROM
				`cms_stat_paths` `p`
				INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
			WHERE
				`p`.`date` BETWEEN {$sQrInterval}
				 {$sQrHost}
                 {$sQrUsr}
			ORDER BY
				p.`date` ASC

END;

        	$resSumm = $this->simpleQuery($sQr);
		$i_summ = (int) isset($resSumm[0]['cnt'])?$resSumm[0]['cnt']:0;
		return $i_summ;
	}

    /**
     * метод получения почасовой информации о числе посещений в выбранном интервале
     *
     * @return array
     */
    private function getDetail()
    {
        $this->setUpVars();
         
        $all = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `cnt`, `hour`, UNIX_TIMESTAMP(`p`.`date`) AS `ts` FROM `cms_stat_paths` `p`
                                     INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
                                      WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
                                       GROUP BY `hour` ORDER BY `ts` ASC");
	//
	$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
	$i_total = (int) $res[0]['total'];
	//
	$all2 = array();
	foreach ($all as $iRec=>$arrRec) {
		$all2[intval(date('G', $arrRec['ts']))] = $arrRec;
	}
	ksort($all2);
	//
	return array("all"=>$all2, "total"=>$i_total);
    }

    /**
     * метод получения почасовой информации за выходные и будни
     *
     * @return array
     */
    private function getAvg()
    {
        $this->setUpVars();

        $qry = "(SELECT 'routine' AS `type`, COUNT(*) / " . $this->routine_count . ".0 AS `avg`, `h`.`hour` FROM `cms_stat_paths` `p`
                 INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
                  LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
                    AND `day_of_week` BETWEEN 1 AND 5 AND `holidays`.`id` IS NULL
                     GROUP BY `h`.`hour`)
                UNION
                (SELECT 'weekend' AS `type`, COUNT(*) / " . $this->holidays_count . ".0 AS `avg`, `h`.`hour` FROM `cms_stat_paths` `p`
                 INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
                  LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
                   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . "  " . $this->getHostSQL("p") . $this->getUserFilterWhere('p') . "
                    AND (`day_of_week` NOT BETWEEN 1 AND 5 OR `holidays`.`id` IS NOT NULL)
                     GROUP BY `h`.`hour`)";

        $res = l_mysql_query($qry);

        $result = array();

        while ($row = mysql_fetch_assoc($res)) {
            $result[$row['type']][$row['hour']] = $row['avg'];

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