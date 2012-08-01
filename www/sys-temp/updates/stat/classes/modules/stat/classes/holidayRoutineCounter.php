<?php
/**
 * $Id: holidayRoutineCounter.php 1 2006-08-04 13:01:21Z zerkms $
 *
 * Класс для подсчёта числа будних и выходных дней в интервале
 *
 */

class holidayRoutineCounter
{
    /**
     * Массив для кеширования результатов в памяти
     *
     * @var array
     */
    public static $results = array();

    /**
     * Метод, вычисляющий число будних и выходных дней
     *
     * @param integer $start начальная дата в формате unix timestamp
     * @param integer $finish конечная дата в формате unix timestamp
     * @return array
     */
    public static function count($start, $finish)
    {
        if (empty(self::$results[md5($start . $finish)])) {
            $st = $start;
            $res = array('holidays' => 0, 'routine' => 0);
            while ($st <= strtotime('-1 day', $finish)) {
                $weekday = date('w', $st);
                if ($weekday >= 1 && $weekday <= 5) {
                    $res['routine']++;
                } else {
                    $res['holidays']++;
                }
                $st = strtotime('+1 day', $st);
            }

            $r = l_mysql_query($q = "SELECT DATE_FORMAT(CONCAT('" . date('Y', $start) . "-', `month`, '-', `day`), '%w') AS `day_of_week` FROM `cms_stat_holidays` WHERE (`day` >= " . date('d', $start) . " AND `month` = " . date('m', $start) . " AND `day` <= " . date('d', $finish) . " AND `month` = " . date('m', $finish) . ") OR (`month` > " . date('m', $start) . " AND `month` < " . date('m', $finish) . ") HAVING `day_of_week` BETWEEN 1 AND 5");
            $holidays_in_routine = mysql_num_rows($r);

            self::$results[md5($start . $finish)] = array('holidays' => $res['holidays'] + $holidays_in_routine,
            'routine' =>$res['routine'] - $holidays_in_routine);
        }

        return self::$results[md5($start . $finish)];
    }
}

?>