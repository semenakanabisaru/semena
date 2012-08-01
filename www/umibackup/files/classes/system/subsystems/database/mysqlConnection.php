<?php
/**
 * Класс соединения с базой данных
 * Пример использования:
 *		$connection = new Connection('localhost', 'root', '', 'umi');
 *		$connetcion->open();
 *		$connection->query('SHOW TABLES');
 *		$connection->close();
 */
class mysqlConnection implements IConnection {
	private $host		= null;
	private $username	= null;
	private $password	= null;
	private $dbname		= null;
	private $port		= false;
	private $persistent = false;
	private $critical   = true;
	private $conn		= null;
	private $queryCache = array();
	private $isOpen     = false;
	/**
	 * Конструктор соединения
	 * @param String  $host хост СУБД
	 * @param String  $login имя пользователя БД
	 * @param String  $password пароль к БД
	 * @param String  $dbname имя БД
	 * @param Integer $port порт
	 * @param Boolean $persistent true - для сохранения подключения открытым
	 * @param Boolean $critical true - если функционирование подключения критично для системы
	 */
	public function __construct($host, $login, $password, $dbname, $port = false, $persistent = false, $critical = true) {
		$this->host       = $host;
		$this->username   = $login;
		$this->password   = $password;
		$this->dbname     = $dbname;
		$this->port       = $port;
		$this->persistent = $persistent;
		$this->critical   = $critical;
	}

	/**
	 * Открывает соединение
	 * @return Boolean
	 */
	public function open() {
		if($this->isOpen) return true;

		try {
			$server = $this->host . ($this->port ? ':' . $this->port : '');
			if($this->persistent) {
				$this->conn = mysql_pconnect($server, $this->username, $this->password);
			} else {
				$this->conn = mysql_connect($server, $this->username, $this->password, true);
			}
			if($this->errorOccured()) throw new Exception();
			if(!mysql_select_db($this->dbname, $this->conn)) throw new Exception();


			mysql_query("SET NAMES utf8", $this->conn);
			mysql_query("SET CHARSET utf8", $this->conn);
			mysql_query("SET CHARACTER SET utf8", $this->conn);
			mysql_query("SET character_set_client = 'utf8'", $this->conn);
			mysql_query("SET SESSION collation_connection = 'utf8_general_ci'", $this->conn);
			mysql_query("SET SQL_BIG_SELECTS=1", $this->conn);
		} catch(Exception $e) {
			if($this->critical)
				mysql_fatal();
			else
				return false;
		}
		$this->isOpen = true;
		return true;
	}
	/**
	 * Закрывает текущее соединение
	 */
	public function close() {
		if($this->isOpen) {
			mysql_close($this->conn);
			$this->isOpen = false;
		}
	}
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return Resource результат выполнения запроса
	 */
	public function query($queryString, $noCache = false) {
		if(!$this->open()) return false;

		$queryString = trim($queryString, " \t\n");

		if(defined('SQL_QUERY_DEBUG') && SQL_QUERY_DEBUG) {
			echo $queryString, "\r\n";
		}

		if(strtoupper(substr($queryString, 0, 6)) != "SELECT" || defined('MYSQL_DISABLE_CACHE')) {
			$result = mysql_query($queryString, $this->conn);

			if($this->errorOccured()) {
				throw new databaseException($this->errorDescription($queryString));
			}

			return $result;
		}
		$hash = md5($queryString);
		if(isset($this->queryCache[$hash]) && $noCache == false) {
			$result = $this->queryCache[$hash][0];
			if($this->queryCache[$hash][1]) {
				mysql_data_seek($result, 0);
			}
		} else {
			$result = mysql_query($queryString, $this->conn);
			if( $this->errorOccured() ) {
				$this->queryCache[$hash] = false;
				throw new databaseException( $this->errorDescription($queryString) );
			} else {
				if(SQL_QUERY_CACHE) {
					$this->queryCache[$hash] = array($result, mysql_num_rows($result));
				}
			}
		}
		return $result;
	}
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return IQueryResult результат выполнения запроса
	 */
	public function queryResult($queryString, $noCache = false) {
		$result = $this->query($queryString, $noCache);
		return $result ? new mysqlQueryResult($result) : null;
	}
	/**
	 * Проверяет, успешно ли завершен последний запрос
	 * @return Boolean true в случае возникновения ошибки, иначе false
	 */
	public function errorOccured() {
		return (strlen(mysql_error($this->conn)) != 0);
	}
	/**
	 * Возвращает описание последней возникшей ошибки
	 * @return String
	 */
	public function errorDescription($sqlQuery = null) {
		$descr = mysql_error($this->conn);
		if($sqlQuery) $descr .= " in query: " . $sqlQuery;
		return $descr;
	}
	/**
	 * Возвращает признак открыто соединение или нет
	 * @return Boolean
	 */
	public function isOpen() {
		return $this->isOpen;
	}
	/**
	* Экранирует входящую строку
	* @param String $input строка для экранирования
	* @return экранированная строка
	*/
	public function escape($input) {
		if($this->isOpen) {
			return mysql_real_escape_string($input);
		} else {
			return addslashes($input);
		}
	}

	public function getConnectionInfo() {

		return array (
			'host' => $this->host,
			'user' => $this->username,
			'password' => $this->password,
			'dbname' => $this->dbname,
			'link' => $this->conn
		);
	}
};
?>