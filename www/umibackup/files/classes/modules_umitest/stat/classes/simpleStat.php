<?php
/**
 * $Id: simpleStat.php 36 2006-12-21 12:03:39Z zerkms $
 *
 * Абстрактный класс для всех отчётов
 *
 */

abstract class simpleStat
{
    /**
     * константа, хранящая формат для даты mysql
     *
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * стартовая дата для анализа
     *
     * @var integer
     */
    protected $start;

    /**
     * конечная дата для анализа
     *
     * @var integer
     */
    protected $finish;

    /**
     * Id хоста, для которого производятся выборки
     *
     * @var unknown_type
     */
    protected $host_id;
	
	/**
     * Id пользователей, для которого производятся выборки
     *
     * @var unknown_type
     */
	protected $user_id;
    
    /**
     * Id пользователей, для которого производятся выборки
     *
     * @var unknown_type
     */
    protected $user_login;

    /**
     * Интервал по умолчанию
     * задаётся в насоедниках, если нужно
     *
     * @var string
     */
    protected $interval = '-10 days';

    /**
     * абстрактный метод получения отчёта
     * должен быть переопределён в наследниках
     *
     */
    abstract public function get();

    /**
     * массив разрешённых параметров
     *
     * @var array
     */
    protected $params = array();

    protected $limit = 10;
    
    protected $offset = 0;

    /**
     * Конструктор
     *
     * @param integer $finish конечная дата анализа
     * @param string $interval анализируемый интервал
     */
    public function __construct($finish = null, $interval = null)
    {
        if (!empty($finish)) {
            $this->setFinish($finish);
        } else {
            $this->setFinish(time());
        }

        $this->setDomain($_SERVER['HTTP_HOST']);

        if (empty($interval)) {
            $interval = $this->interval;
        } else {
            $this->interval = $interval;
        }

        $this->setInterval($interval);
		
		$this->setUserIDs();
		$this->limit  = 10;
		$this->offset = 0;
    }
	
	/**
     * Установка ID пользователей, для которых производится выборка
     *
     * @param array $user_id_ar Массив id пользователей     
     */	
	public function setUserIDs($user_id_ar = array()){
		if(is_array($user_id_ar))
			$this->user_login = array_map("intval",$user_id_ar);
		else
			$this->user_login = array(intval($user_id_ar));			
	}
    
    /**
    * @desc Определяет id пользователя по его имени
    * @param String $_sUserName 
    */
    public function setUser($_sUserName) {
        if(intval($_sUserName) == 0) {
            $this->user_id = array();
            return;
        }
        $this->user_id = array();
        $sQuery = "SELECT `id` FROM `cms_stat_users` WHERE `login`='".mysql_escape_string($_sUserName)."'";
        $result = l_mysql_query($sQuery);
        while($aRow = mysql_fetch_row($result)) $this->user_id[] = $aRow[0];
        if(empty($this->user_id)) $this->user_id[] = 0;
    }

	/**
	 * Метод установки имени домена, для которого производятся выборки
	 *
	 * @param string $domain
	 *
	 * NOTE : !!! метод вызывается в конструкторе, устанавливая выборку по текущему домену,
	 * при необходимости выборки по всем доменам, следует вызвать явно !!!
	 */
	public function setDomain($v_domain) {		
		$s_domain = strval($v_domain);
		$i_domain = intval($v_domain);
		//
		if ($i_domain === -1 || strtoupper($s_domain) === "ALL" || $s_domain == "Все") { // clear
			$this->host_id = -1;
		} elseif ($i_domain && (strval($i_domain) === strval($v_domain))) { // CMS domain id
			$o_domains = domainsCollection::getInstance();
			$o_dom = $o_domains->getDomain($i_domain);
			if ($o_dom) {
				$s_query = "SELECT   `group_id` AS 'id' FROM `cms_stat_sites` WHERE `name` = '".mysql_real_escape_string($o_dom->getHost())."'";
				$rs_res = l_mysql_query($s_query);
				while ($arr_row = mysql_fetch_assoc($rs_res)) {
					$this->host_id = intval($arr_row['id']);
					break;
				}
			}
		} else { // host name
			$this->host_id = $this->searchHostIdByHostname($s_domain);		
		}
		// RETURN :		
		return $this->host_id;
	}
	
	/**
	 * Метод установки массива доменов, для которых производятся выборки
	 *
	 * @param array $domain
	 */
	public function setCmsDomainsArray($arr_domains = array()) {
		$arr_tmp = array();
		$o_domains = domainsCollection::getInstance();
		//
		foreach($arr_domains as $i_dom_id) {
			$o_dom = $o_domains->getDomain($i_dom_id);
			if ($o_dom) $arr_tmp[] = mysql_real_escape_string($o_dom->getHost());			
		}
		//
		if (count($arr_tmp)) {
			$s_hosts = "'".implode("','", $arr_tmp)."'";
			$s_query = "SELECT   `group_id` AS 'id' FROM `cms_stat_sites` WHERE `name` IN (".$s_hosts.")";
			$rs_res = l_mysql_query($s_query);
			$arr_tmp = array();
			while ($arr_row = mysql_fetch_assoc($rs_res)) $arr_tmp[] = intval($arr_row['id']);
			//
			$this->host_id = $arr_tmp;
		} else {
			$this->host_id = 0; // will find nothing
		}
		// RETURN :
		return $this->host_id;
	}
	
	/**
	* метод генерации куска SQL-запроса для выборки по конкретным доменам
	*
	* @param string $table
	* @param string $field
	*/
	protected function getHostSQL($table = "", $field="host_id") {
		if(!is_array($this->host_id)&&($this->host_id < 0)) return "";	
		$sSQL = " AND ".(($table!="")?"`".$table."`.":"")."`".$field."` ";
		if(is_array($this->host_id)) {
			$sSQL .= " IN ('".implode("','", $this->host_id)."') ";
		} else {
			$sSQL .= " = ".$this->host_id;
		}		
		return $sSQL;
	}

    /**
     * метод установки конечной даты анализа
     *
     * @param integer $finish unix timestamp для конечной даты
     */
    public function setFinish($finish)
    {
        if (!is_integer($finish)) {
            throw new invalidParameterException('Значение свойства finish должно быть целочисленного типа и > 0', $finish);
        }

        $this->finish = $finish + 86400;

        //$this->setInterval($this->interval);
    }
    
    /**
    * метод установки начальной даты интервала
    *
    * @param integer $start
    */
    public function setStart($start)
    {
        if (!is_integer($start)) {
            throw new invalidParameterException('Значение свойства start должно быть целочисленного типа и > 0', $start);
        }
        
        $this->start = $start;
    }

    /**
     * метод установки анализируемого интервала
     *
     * @param string $interval интервал. значение должно быть корректным для передачи первым аргументом в функцию strtotime
     */
    public function setInterval($interval)
    {
        $start = strtotime($interval, $this->finish);

        if (!is_integer($start)) {
            throw new invalidParameterException('Интервал должен задаваться в соответствии с требованиями к входным параметрам функции strtotime', $interval);
        }

        $this->start = $start;
    }

    /**
     * Метод для поиска Id домена по его имени
     *
     * @param string $hostname
     * @return integer
     */
    protected function searchHostIdByHostname($hostname)
    {    	
    	$name = $hostname;
            
        $qry = "SELECT   `rel` FROM `cms3_domain_mirrows` WHERE `host` = '".$name."'";
        $res = l_mysql_query($qry);
        $row = mysql_fetch_assoc($res);
        if (isset($row['rel']) && ($row['rel'] > 0)) {
            $qry = "SELECT   `host` FROM `cms3_domains` WHERE `id`='". $row['rel'] ."'";
            $res = l_mysql_query($qry);
            $row = mysql_fetch_assoc($res);
            if(isset($row['host']) && ($row['host']!='')) $name = $row['host'];
        } else {
            $qry = "SELECT   `id` FROM `cms3_domains` WHERE `host`='". $name ."'";
            $res = l_mysql_query($qry);
            $row = mysql_fetch_assoc($res);
            if(!isset($row['id']) || ($row['id']==0)) {
                $qry = "SELECT   `host` FROM `cms3_domains` WHERE `is_default`='1'";
                $res = l_mysql_query($qry);
                $row = mysql_fetch_assoc($res);
                if(isset($row['host']) && ($row['host']!='')) $name = $row['host'];
            }                
        }
        
        
        $qry = "SELECT   `group_id` FROM `cms_stat_sites` WHERE `name` = '" . $name . "'";
        $res = l_mysql_query($qry);
        $row = mysql_fetch_assoc($res);
        if (isset($row['group_id'])) {
            return $row['group_id'];            
        }        
        
        $qry = "INSERT INTO `cms_stat_sites_groups` (`name`) VALUES ('" . $name . "')";
        l_mysql_query($qry);
        $id = l_mysql_insert_id();
        $qry = "INSERT INTO `cms_stat_sites` (`name`, `group_id`) VALUES ('" . $name . "', " . $id . ")";
        l_mysql_query($qry);

        return $id;     	
    }

    /**
     * Метод для форматирования даты из unix timestamp в формат mysql
     *
     * @param integer $date искомый timestamp
     * @return string
     */
    protected function formatDate($date)
    {
        return date(self::DATE_FORMAT, $date);
    }

    protected function getQueryInterval()
    {
        return "'" . $this->formatDate($this->start) . "' AND '" . $this->formatDate($this->finish) . "'";
    }
    
     protected function getUserFilterTable($_sPathField, $_sCompareTable) {
         if(!is_array($this->user_id)||empty($this->user_id)) return "";
         return "\n INNER JOIN `cms_stat_paths` ON `cms_stat_paths`.`".$_sPathField."`=".$_sCompareTable." \n";
     }
     
     protected function getUserFilterWhere($_sTable = 'cms_stat_paths') {
         if(!is_array($this->user_id)||empty($this->user_id)) return "";
         return "\n AND `".$_sTable."`.`user_id` IN (".implode(", ", $this->user_id).") \n";         
     }

    /**
     * Общий метод для получения всех данных из запроса
     *
     * @param string $query искомый запрос
     * @return array массив с результатами
     */
    protected function simpleQuery($query, $bNeedFoundRows = false)
    {                         
	    if ($bNeedFoundRows) $vana = ini_set('mysql.trace_mode','Off');

        $res = l_mysql_query($query);

        if (!is_resource($res)) {
//            die('<i>' . $query . '</i><br>' . l_mysql_error());
        }

        $result = array();
        
        if($res) {
	        while ($row = mysql_fetch_assoc($res)) {
	            $result[] = $row;
	
	        }
        }

	$iFoundRows = count($result);
	if ($bNeedFoundRows) { // NOTE !!! you need to set SQL_CALC_FOUND_ROWS in your request !!!
		$iFoundRows = 0;
		$res2 = l_mysql_query("SELECT FOUND_ROWS() as 'cnt'");
		if (is_resource($res)) {
			 while ($row2 = mysql_fetch_assoc($res2)) {
				$iFoundRows = $row2['cnt'];
				break;
			}
		}
		//
		$result = array('result'=>$result, 'FOUND_ROWS'=>$iFoundRows);
		//
		ini_set('mysql.trace_mode', $vana);
	}

        return $result;
    }

    /**
     * Метод установки параметров для выборок
     *
     * @param array $array
     */
    public function setParams($array = array())
    {		
        foreach($this->params as $key => $val) {			
            if (isset($array[$key])) {			
                $this->params[$key] = $array[$key];
            }
        }		
    }

    public function setLimit($limit)
    {
        if ((int)$limit > 0) {
            $this->limit = $limit;
        }
    }
    
    public function setOffset($offset)
    {
        if ((int)$offset > 0) {
            $this->offset = $offset;
        }
    }
}

?>