<?php
/**
 * $Id: visitsByDate.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Класс получения информации о посетителях за день
 *
 */

class visitsByDate extends simpleStat
{
    /**
     * массив параметров
     *
     * @var array
     */
    //protected $params = array('section' => '');

    /**
     * Метод получения отчёта
     *
     * @return array
     */
    public function get()
    {
        $all = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS DISTINCT(`user_id`) FROM `cms_stat_paths`
                                     WHERE `date` BETWEEN '" . $this->formatDate(strtotime('-1 day', $this->finish)) . "' AND '" . $this->formatDate($this->finish) . $this->getUserFilterWhere('p') . "'
                                       ORDER BY `date` DESC LIMIT " . $this->offset . ", " . $this->limit);
        $res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
        $i_total = (int) $res[0]['total'];

        return array("all"=>$all, "total"=>$i_total);
    }
}

?>