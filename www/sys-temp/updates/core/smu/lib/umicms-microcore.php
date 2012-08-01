<?php
	if(!defined("CURRENT_VERSION_LINE")) define("CURRENT_VERSION_LINE", "");
	if(!defined("CURRENT_WORKING_DIR")) define("CURRENT_WORKING_DIR", dirname(dirname(dirname(__FILE__))));
	
	$interface_langs = array('ru');


/**
	* Классы, реализующие интерфейс iConnectionPool предназначены для управления соединениями с различными базами данных.
	* Задачей этого класса является возможность при необходимости распределить нагрузку по различным базам данных,
	* которые в теории можно лучше оптимизировать под отведенные им задачи.
	* В минимальной версии предполагается поддержка только MySQL. В дальнейшем, при протировании системы на
	* PostgreSQL, MSSQL, либо Oracle для этой версии должен быть реализован соответствующий класс с этим интерфейсом.
	* Предполагается, что у нас есть несколько классов соединений для различных подсистем. Например:
	* 1. core
	* 2. backup
	* 3. stat
	* 4. counters
	* Если заданы параметры соединения только для первого класса ("core"), то все остальные классы соединения используют его.
	*
	* Пример инициализации (в файле mysql.php):
	* <?php
	* 		$connectionPool = ConnectionPool::getInstance();
	*		$connectionPool->addConnection("core", "localhost", "root", "", "umi");
	*		$connectionPool->addConnection("stat", "192.168.0.39", "asd", "ddd", "ggg", false, true);
	*		$connectionPool->init();
	* ?>
	*
	* Пример использования:
	* $connectionPool = ConnectionPool::getInstance();
	* $connection     = $connectionPool->getConnenction('stat');
	* $connection->query("SHOW TABLES");
*/
class ConnectionPool {
	private static $instance = null;
	private $pool = array('core' => null);
	private $connectionClassName = 'mysqlConnection';
	/**
	 * Private constructor for preventing outer instantiation
	 */
	private function __construct() {
		return;
	}
	/**
		* Статический метод, который позволяет получить экземпляр класса
		* @return ConnectionPool экземпляр класса
	*/
	static public function getInstance($c = NULL) {
		if(!self::$instance) {
			self::$instance = new ConnectionPool();
		}
		return self::$instance;
	}

	/**
		* Инициализировать основные соединения (например, только "core").
		* Выбор того, какие соединения являются основными определеяется внутренней логикой класса.
	*/
	public function init() {
		foreach($this->pool as $connection) {
			if($connection instanceof Connection) {
				$connection->open();
			}
		}
	}
	/**
	 * Устанавливает класс для объекта-соединения
	 * @param String $className
	 */
	public function setConnectionObjectClass($className = 'mysqlConnection') {
		if(class_exists($className) && in_array('IConnection', class_implements($className)) ) {
			$this->connectionClassName = $className;
		}
	}

	/**
		* Получить список всех доступных классов соединения
		* @return Array список всех соединений
	*/
	public function getConnectionClasses() {
		return array_keys($this->pool);
	}

	/**
		* Добавить новый класс соединения
		* @param String $className id класса соединения
		* @return Boolean true, если добавление нового класса прошло успешно
	*/
	public function addConnectionClass($className) {
		if(!array_key_exists($className, $this->pool)) {
			$this->pool[$className] = null;
		}
		return true;
	}
	/**
		* Удалить класс соединения
		* @param String $className id класса соединения
		* @return Boolean true, если удаление класса прошло успешно
	*/
	public function delConnectionClass($className){
		if($className == 'core') return false;
		if(isset($this->pool[$className])){
			$connection = $this->pool[$className];
			if($connection instanceof Connection) {
						$connection->close();
			}
			unset($this->pool[$className]);
			return true;
		}
		return false;
	}
	/**
		* Добавить новое соединение с базой данных
		* @param String $className id класса соединения
		* @param String $host хост базы данных
		* @param String $login логин к базе данных
		* @param String $password пароль к базе данных
		* @param String $dbname название базы данных
		* @param Integer|Boolean $port = false порт базы данных
		* @param Boolean $persistent = false постоянное соединение, если true
		* @return Boolean
	*/
	public function addConnection($className, $host, $login, $password, $dbname, $port = false, $persistent = false) {
		$connClassName = $this->connectionClassName;
		$connection    = new $connClassName($host, $login, $password, $dbname, $port, $persistent);		
		if(isset($this->pool[$className])) {
			if($this->pool[$className] instanceof Connection) {
				$this->pool[$className]->close();
			}
		}
		$this->pool[$className] = $connection;
		return true;
	}

	/**
		* Удалить соединение. При открытом соединении, метод вызывает процедуру закрытия соединения.
		* @param String $className id класса соединения
		* @param Boolean результат операции
	*/
	public function delConnection($className) {
		if(isset($this->pool[$className])){
			$connection = $this->pool[$className];
			if($connection instanceof Connection) {
				$connection->close();
			}
			$this->pool[$className] = null;
			return true;
		}
		return false;
	}

	/**
		* Получить ресурс соединения. Может выбрасывать исключение "databaseException" в случае неудачи.
		* Если соединение еще не активировано, метод запускает процедуру его подключения.
		* @param String $className = "core" id класса соединения
		* @return IConnection ресурс подключения к базе данных, либо false в случае неудачи
	*/
	public function getConnection($className = 'core') {
		if(!isset($this->pool[$className])) {
			$className = 'core';
		}
		$connection = $this->pool[$className];
		if(!($connection instanceof IConnection)) {
			throw new Exception("No suitable connection found");
		}
		return $connection;
	}

	/**
		* Закрыть соединение
		* @param String $className id класса соединения
		* @return Boolean результат операции
	*/
	public function closeConnection($className) {
		if(isset($this->pool[$className])){
			$connection = $this->pool[$className];
			if($connection instanceof Connection) {
				$connection->close();
			}
			return true;
		}
		return false;
	}
};


/**
 * Интерфейс соединения с базой данных
 * Пример использования:
 *		$connection = new Connection('localhost', 'root', '', 'umi');
 *		$connetcion->open();
 *		$connection->query('SHOW TABLES');
 *		$connection->close();
 */
interface IConnection {
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
	function __construct($host, $login, $password, $dbname, $port = false, $persistent = false, $critical = true);
	/**
	 * Открывает соединение
	 * @return Boolean
	 */
	function open();
	/**
	 * Закрывает текущее соединение
	 */
	function close();
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return Resource результат выполнения запроса
	 */
	function query($queryString, $noCache = false);
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return IQueryResult результат выполнения запроса
	 */
	function queryResult($queryString, $noCache = false);
	/**
	 * Проверяет, успешно ли завершен последний запрос
	 * @return Boolean true в случае возникновения ошибки, иначе false
	 */
	function errorOccured();
	/**
	 * Возвращает описание последней возникшей ошибки
	 * @return String
	 */
	function errorDescription();
	/**
	 * Возвращает признак открыто соединение или нет
	 * @return Boolean
	 */
	function isOpen();
};


/**
 * Класс, содержащий результат
 */
interface IQueryResult extends IteratorAggregate {
	// Константы, определяющие тип выборки результата
	const FETCH_ARRAY  = 0;
	const FETCH_ROW    = 1;
	const FETCH_ASSOC  = 2;
	const FETCH_OBJECT = 3;
	/**
	 * Конструктор
	 * @param Resource $_ResultResource Ресурс результата mysql запроса
	 * @param Int      $_fetchType		Тип выборки (см. константы)
	 */
	public function __construct($_ResultResource, $_fetchType = self::FETCH_ARRAY);
	/**
	 * Устанавливает тип выборки из результата (см. константы)
	 * @param Int $newType
	 */
	public function setFetchType($newType);
	/**
	 * Возвращает тип выборки
	 * @return Int
	 */
	public function getFetchType();
	/**
	 * Выбирает строку значений из результата
	 * и возвращает ее в соответствии с заданым типом выборки
	 */
	public function fetch();
};
/**
 *
 */
interface IQueryResultIterator extends Iterator {	
};


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
				$this->conn = mysql_connect($server, $this->username, $this->password);
			}
			if($this->errorOccured()) throw new Exception();
			if(!mysql_select_db($this->dbname, $this->conn)) throw new Exception();
			
			
			mysql_query("SET NAMES utf8_general_ci", $this->conn);
			mysql_query("SET CHARSET utf8", $this->conn);
			mysql_query("SET CHARACTER SET utf8", $this->conn);
			mysql_query("SET SESSION collation_connection = 'utf8_general_ci'", $this->conn);
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
				throw new Exception($this->errorDescription($queryString));
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
};


/**
 * Класс, содержащий результат
 */
class mysqlQueryResult implements IQueryResult {	
	// Закрытые данные
	private $resource  = null;
	private $fetchType = IQueryResult::FETCH_ARRAY;
	/**
	 * Конструктор
	 * @param Resource $_mysqlResultResource Ресурс результата mysql запроса
	 * @param Int      $_fetchType			 Тип выборки (см. константы)
	 */
	public function __construct($_mysqlResultResource, $_fetchType = IQueryResult::FETCH_ARRAY) {
		$this->resource  = $_mysqlResultResource;
		$this->fetchType = $_fetchType;
	}
	/**
	 * Часть интерфейса IteratorAggregate, возвращает итератор для обхода результата
	 * return Iterator
	 */
	public function getIterator() {
		return new mysqlQueryResultIterator($this->resource);
	}
	/**
	 * Устанавливает тип выборки из результата (см. константы)
	 * @param Int $newType
	 */
	public function setFetchType($newType) {
		if($newType > 3) $this->fetchType = IQueryResult::FETCH_ARRAY;
		else $this->fetchType = $newType;
	}
	/**
	 * Возвращает тип выборки
	 * @return Int
	 */
	public function getFetchType() {
		return $this->fetchType;
	}
	/**
	 * Выбирает строку значений из результата
	 * и возвращает ее в соответствии с заданым типом выборки
	 */
	public function fetch() {
		$result = null;
		switch($this->fetchType) {
			case IQueryResult::FETCH_ARRAY  : $result = mysql_fetch_array($this->resource);  break;
			case IQueryResult::FETCH_ROW    : $result = mysql_fetch_row($this->resource);    break;
			case IQueryResult::FETCH_ASSOC  : $result = mysql_fetch_assoc($this->resource);  break;
			case IQueryResult::FETCH_OBJECT : $result = mysql_fetch_object($this->resource); break;
		}
		return result;
	}
};
/**
 *
 */
class mysqlQueryResultIterator implements IQueryResultIterator {
	private $resource = null;
	private $number   = 0;
	private $rowcount = 0;
	function __construct($_mysqlResultResource) {
        $this->resource = $_mysqlResultResource;
		$this->rowcount = $this->resource ? mysql_num_rows($this->resource) : 0;
    }
    function rewind() {
		if($this->resource && (mysql_num_rows($this->resource) > 0)) {
			mysql_data_seek($this->resource, 0);
		}
		$this->number  = 0;
	}
    function valid() {
        return $this->number < $this->rowcount;
    }
    function key() {
        return $this->number;
    }
    function current() {
        return mysql_fetch_array($this->resource);
    }
    function next() {
        $this->number++;
    }
};


	if(isset($_REQUEST['p'])) {
		$p = $_REQUEST['p'];
		if($p < 0 && $p != 'all') $p = 0;
		if($p != 'all') $p = (int) $p;
		$_REQUEST['p'] = $p;
		unset($p);
	}

	define('MB_ENCODING_SUPPORTED', function_exists('mb_internal_encoding'));
	if(MB_ENCODING_SUPPORTED) {
		define('MB_INTERNAL_ENCODING', mb_internal_encoding());
	} else {
		define('MB_INTERNAL_ENCODING', false);
	}

	function wa_strtolower($str) {
		$strtolower_func = function_exists("mb_strtolower") ? "mb_strtolower" : "strtolower";
		return $strtolower_func($str);
	}
	
	
	function wa_substr($str, $pos, $offset) {
		$substr_func = function_exists("mb_strtolower") ? "mb_substr" : "substr";
		return $substr_func($str, $pos, $offset);
	}
	
	function wa_strlen($str) {
		$strlen_func = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
		return $strlen_func($str);
	}
	
	function wa_strpos($str, $seek) {
		$strpos_func = function_exists("mb_strpos") ? "mb_strpos" : "strpos";
		return $strpos_func($str, $seek);
	}


	function getArrayKey($array, $key) {
		if(!is_array($array)) {
			return false;
		}
		
		if($key === false) return NULL;

		if(array_key_exists($key, $array)) {
			return $array[$key];
		} else {
			return NULL;
		}
	}

	function getRequest($key) {
		return getArrayKey($_REQUEST, $key);
	}
	
	function getSession($key) {
		if(!isset($_SESSION)) return NULL;
		return getArrayKey($_SESSION, $key);
	}
	
	function getServer($key) {
		return getArrayKey($_SERVER, $key);
	}
	
	function getCookie($key) {
		return getArrayKey($_COOKIE, $key);
	}

	function getLabel($key, $path = false) {
		$args = func_get_args();
		return ulangStream::getLabel($key, $path, $args);
	}

	function getI18n($key, $pattern = "") {
		return ulangStream::getI18n($key, $pattern);
	}
	
	function l_mysql_query($sql, $no_cache = false, $className = 'core') {
		static $pool, $i = 0;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}
		
		$conn = $pool->getConnection($className);
		$result = $conn->query($sql, $no_cache);
		
		return $result;
	}

	function l_mysql_real_escape_string($inputString, $className = 'core') {
		static $pool = null;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$conn = $pool->getConnection($className);
		if($conn->isOpen()) {
			$result = mysql_real_escape_string($inputString);
		} else {
			$result = addslashes($inputString);
		}

		return $result;
	}
	
	function bytes_strlen($string) {
		if(MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("latin1");
			$iResult = strlen($string);
			mb_internal_encoding(MB_INTERNAL_ENCODING);
		} else {
			return strlen($string);
		}		
	}
	
	function bytes_substr($string, $start, $length = false) {
		if(MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("latin1");
			$sResult = '';
			if($length !== false) {
				$sResult = substr($string, $start, $length);
			} else {
				$sResult = substr($string, $start);
			}
			mb_internal_encoding(MB_INTERNAL_ENCODING);
			return $sResult;
		} else {
			if($length !== false)
				return substr($string, $start, $length);
			else
				return substr($string, $start);
		}
	}
	
	function removeDirectory($dir) {
		if(!$dh = @opendir($dir)) {
			return false;
		}
		while (($obj = readdir($dh)) !== false) {
			if($obj=='.' || $obj=='..') continue;
				if (!@unlink($dir.'/'.$obj)) {
					removeDirectory($dir.'/'.$obj);
				}
		}
		@rmdir($dir);
		return true;
	}
	
	function getInterfaceLangs() {
		global $interface_langs;
		return $interface_langs;
	}
	
	function checkInterfaceLang($prefix) {
		$config = mainConfiguration::getInstance();
		$langs = $config->get('system', 'interface-langs');
		
		return in_array($prefix, $langs) ? $prefix : "ru";
	}
	
	
	function check_session() {
		$ip = getServer('REMOTE_ADDR');
		if(is_null(getSession('session-owner-ip'))) {
			$_SESSION['session-owner-ip'] = $ip;
			return true;
		}
		
		if(!session_id()) {
			session_start();
		}
		
		if(getSession('session-owner-ip') == $ip) {
			return true;
		} else {
			session_destroy();
			session_start();
			return false;
		}
	}
	
	function enableOutputCompression() {
		if(extension_loaded('zlib') && !defined('DEBUG')) {
			$buffer = ob_get_contents();
			while(@ob_end_clean());
			ob_start('ob_gzhandler');
			ob_start('criticalErrorsBufferHandler');
			echo $buffer;
		}
	}
	
	function disableOutputCompression() {
		static $called = false;
		
		if($called) {
			return false;
		}
		
		while(@ob_end_clean());
		ob_start();
		
		header("Content-Encoding:");
		header("Content-Length:");
		header("Vary:");
		
		
		$called = true;
		return true;
	}

	function array_extract_values($array, &$result = NULL, $ignoreVoidValues = false) {
		if(is_array($array) == false) {
			return Array();
		}
		
		if(is_array($result) == false) {
			$result = Array();
		}
		
		foreach($array as $value) {
			if(is_array($value) == false) {
				if($value || $ignoreVoidValues == true) {
					$result[] = $value;
				}
			} else {
				array_extract_values($value, $result, $ignoreVoidValues);
			}
		}
		return $result;
	}

	function array_unique_arrays($array, $key) {
		$result = Array();
		$keys = Array();
		
		foreach($array as $arr) {
			$currKey = isset($arr[$key]) ? $arr[$key] : NULL;
			if(in_array($currKey, $keys)) {
				continue;
			} else {
				$keys[] = $currKey;
				$result[] = $arr;
			}
		}
		return $result;
	}
	
	function array_distinct($array) {
		$result = $hashes = array();
		
		foreach($array as $subArray) {
			$key = sha1(serialize($subArray));
			
			if(in_array($key, $hashes)) {
				continue;
			}
			$result[] = $subArray;
			$hashes[] = $key;
		}
		return $result;
	}

	function array_positive_values($arr, $recursion = true) {
		if(is_array($arr) == false) {
			return Array();
		}
		
		$result = Array();
		foreach($arr as $key => $value) {
			if($value) {
				if(is_array($value)) {
					if($recursion) {
						$value = array_positive_values($value, $recursion);
						if(sizeof($value) == 0) {
							continue;
						}
					}
				}
				$result[$key] = $value;
			}
		}
		return $result;
	}

	function set_timebreak($time_end = false) {
		global $time_start;
		
		if($time_end == false) {
			$time_end = microtime(true);
		}
		$time = $time_end - $time_start;
		return "\r\n<!-- This page generated in {$time} secs -->\r\n";
	}

	// Thanks, Anton Timoshenkov 
	function makeThumbnailFullUnsharpMask($img, $amount, $radius, $threshold) {

		if (function_exists('UnsharpMask')){return UnsharpMask($img, $amount, $radius, $threshold);}
			else{

			// Attempt to calibrate the parameters to Photoshop:
			if ($amount > 500) $amount = 500;
			$amount = $amount * 0.016;
			if ($radius > 50) $radius = 50;
			$radius = $radius * 2;
			if ($threshold > 255) $threshold = 255;

			$radius = abs(round($radius)); 	// Only integers make sense.
			if ($radius == 0) {	return $img; imagedestroy($img); break;	}
			$w = imagesx($img); $h = imagesy($img);
			$imgCanvas = $img;
			$imgCanvas2 = $img;
			$imgBlur = imagecreatetruecolor($w, $h);

			// Gaussian blur matrix:
			//	1	2	1
			//	2	4	2
			//	1	2	1

			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++)
				{
				imagecopy	  ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
				}
			$imgCanvas = $imgBlur;

			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for ($x = 0; $x < $w; $x++)
				{ // each row
				for ($y = 0; $y < $h; $y++)
					{ // each pixel
					$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);
					$rgbBlur = ImageColorAt($imgCanvas, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
						{
						$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
						ImageSetPixel($img, $x, $y, $pixCol);
						}
					}
				}
			return $img;
		}

	}


	function makeThumbnailFullPlaceLogo($image) {
		$logo_full_file_name="./images/cms/thumbs_logo.png";  // ���� �������
		$type_placment=3; // ��� ���������� (1 - ����� ������, 2 - ������ ������, 3 - ������ ������, 4 - ����� ������, 5 - �����)
		$x=5; // ���������� ���������� - �
		$y=5; // ���������� ���������� - Y

		$image_width=imagesx($image);
		$image_height=imagesy($image);


		if (file_exists($logo_full_file_name)) {

			$logoFile = new umiImageFile($logo_full_file_name);
			if(($image_width < ($logoFile->getWidth()+$x)) || ($image_height < ($logoFile->getHeight()+$y)) ){return $image;}

			$logo=@imagecreatefrompng($logo_full_file_name);
			if ($logo) {
				$logo_width=imagesx($logo);
				$logo_height=imagesy($logo);
				switch ($type_placment) {
					case 2:
						$x=$image_width-$x-$logo_width;
						break;
					case 3:
						$x=$image_width-$x-$logo_width;
						$y=$image_height-$y-$logo_height;
						break;
					case 4:
						$y=$image_height-$y-$logo_height;
						break;
					case 5:
						$x=floor($image_width/2-$logo_width/2);
						$y=floor($image_height/2-$logo_height/2);
						break;
					default:
				}
				imagecopy($image,$logo,$x,$y,0,0,$logo_width,$logo_height);
			}
		}

		return $image;
		}


	function makeThumbnailFull($path, $thumbs_path, $width, $height, $crop = true, $cropside = 5, $isLogo = false) {

		$quality = 80;
		$isSharpen=true;

		$image = new umiImageFile($path);
		$file_name = $image->getFileName();
		$file_ext = $image->getExt();

		$file_ext = strtolower($file_ext);
		$allowedExts = Array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		if(!in_array($file_ext, $allowedExts)) return "";

		$file_modified 	= filemtime($path);
		$file_name_new = md5($path.$width.$height.$crop.$cropside.$isLogo)."." . $file_ext;
		$path_new = $thumbs_path . $file_name_new;


		if(!is_file($path_new)) {
			$width_src = $image->getWidth();
			$height_src = $image->getHeight();

				if($height == "auto") {
					$real_height = (int) round($height_src * ($width / $width_src));
					//change
					$height=$real_height;
					$real_width = (int) $width;
				} else {
					if($width == "auto") {
							$real_width = (int) round($width_src * ($height / $height_src));
							//change
							$width=$real_width;
					} else {
						$real_width = (int) $width;
					}

					$real_height = (int) $height;
				}

				$offset_h=0;
				$offset_w=0;

				// realloc: devision by zero fix
				if (!intval($width) || !intval($height)) {
					$crop = false;
				}

				if($crop)
					{
					$width_ratio = $width_src/$width;
					$height_ratio = $height_src/$height;

					if ($width_ratio > $height_ratio)
						{
						$offset_w = round(($width_src-$width*$height_ratio)/2);
						$width_src = round($width*$height_ratio);
						}
					elseif ($width_ratio < $height_ratio)
						{
						$offset_h = round(($height_src-$height*$width_ratio)/2);
						$height_src = round($height*$width_ratio);
						}


				if($cropside)
					{
						//defore all it was cropside work like as - 5
						//123
						//456
						//789
						switch ($cropside):
							case 1:
								$offset_w = 0;
								$offset_h = 0;
								break;
							case 2:
								$offset_h = 0;
								break;
							case 3:
								$offset_w += $offset_w;
								$offset_h = 0;
								break;
							case 4:
								$offset_w = 0;
								break;
							case 5:
								break;
							case 6:
								$offset_w += $offset_w;
								break;
							case 7:
								$offset_w = 0;
								$offset_h += $offset_h;
								break;
							case 8:
								$offset_h += $offset_h;
								break;
							case 9:
								$offset_w += $offset_w;
								$offset_h += $offset_h;
								break;
						endswitch;

					}



					}



				$thumb = imagecreatetruecolor($real_width, $real_height);

				if ($image->getExt() == "gif")	{ $source = imagecreatefromgif($path);}
				else if ($image->getExt() == "png")	{ $source = imagecreatefrompng($path);}
				else { 	$source = imagecreatefromjpeg($path); }

				if ($width*4 < $width_src AND $height*4 < $height_src) {
					$_TMP=array();
					$_TMP['width'] = round($width*4);
					$_TMP['height'] = round($height*4);

					$_TMP['image'] = imagecreatetruecolor($_TMP['width'], $_TMP['height']);
					imagecopyresized($_TMP['image'], $source, 0, 0, $offset_w, $offset_h, $_TMP['width'], $_TMP['height'], $width_src, $height_src);
					$source = $_TMP['image'];
					$width_src = $_TMP['width'];
					$height_src = $_TMP['height'];

					$offset_w = 0;
					$offset_h = 0;
					unset($_TMP);
				}

				imagecopyresampled($thumb, $source, 0, 0, $offset_w, $offset_h, $width, $height, $width_src, $height_src);

				if($isLogo) {$thumb = makeThumbnailFullPlaceLogo($thumb);}
				if($isSharpen) $thumb = makeThumbnailFullUnsharpMask($thumb,80,.5,3);


				if($image->getExt() == "png") {
					imagepng($thumb, $path_new);
				} else if($image->getExt() == "gif") {
					imagegif($thumb, $path_new);
				} else {
					imagejpeg($thumb, $path_new, $quality);
				}
			}


		$value = new umiImageFile($path_new);

		$arr = Array();
		$arr['size'] = $value->getSize();
		$arr['filename'] = $value->getFileName();
		$arr['filepath'] = $value->getFilePath();
		$arr['src'] = $value->getFilePath(true);
		$arr['ext'] = $value->getExt();

		$arr['width'] = $value->getWidth();
		$arr['height'] = $value->getHeight();


		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$arr['src'] = str_replace("&", "&amp;", $arr['src']);
		}

		return $arr;
	}



	class mainConfiguration {
		private static $instance = null;
		private $ini    = array();
		private $edited = false;
		/**
		 *
		 */
		private function __construct() {
			if(!is_readable(CONFIG_INI_PATH)) {
				throw new Exception("Can't find configuration file");
			}		
			$this->ini = parse_ini_file(CONFIG_INI_PATH, true);
		}
		/**
		 *
		 */
		public function __destruct() {
			if($this->edited) {
				$this->writeIni();
			}
		}
		/**
		 * Возвращает экземпляр конфигурации
		 * return mainConfiguration
		 */
		public static function getInstance($c = NULL) {
			if(!self::$instance) {
				self::$instance = new mainConfiguration();
			}
			return self::$instance;
		}
		/**
		 * Возвращает конфигурацию в виде массива
		 * return Array
		 */
		public function getParsedIni() {
			return $this->ini;
		}
		/**
		 * Возвращает значение переменной
		 * @param String $section
		 * @param String $variable
		 * @return String
		 */
		public function get($section, $variable) {
			if(isset($this->ini[$section]) &&
			   isset($this->ini[$section][$variable])) {
				$value = $this->ini[$section][$variable];
				$value = $this->unescapeValue($value);
				return $value;
			} else return null;
		}
		/**
		 * Устанавливает или стирает значение переменной
		 * @param String $section
		 * @param String $variable
		 * @param Mixed $value
		 */
		public function set($section, $variable, $value) {
			if(!isset($this->ini[$section])) {
				$this->ini[$section] = array();
			}
			if($value === null && isset($this->ini[$section][$variable])) {
				unset($this->ini[$section][$variable]);
			} else {
				$this->ini[$section][$variable] = $value;
			}
			$this->edited = true;
		}
		
		/**
			* Возвращает список переметров в секции
			* @param String $section
			* @return Array
		*/
		public function getList($section) {
			if(isset($this->ini[$section]) && is_array($this->ini[$section])) {
				return array_keys($this->ini[$section]);
			} return null;
		}
		
		public function includeParam($key, array $params =  null) {
			static $defaultParams = Array();
			
			$path = $this->get('includes', $key);
			if(strpos($path, "{") !== false) {
				if(class_exists('cmsController') && !sizeof($defaultParams)) {
					$cmsController = cmsController::getInstance();
					
					if($lang = $cmsController->getCurrentLang()) {
						$defaultParams['lang'] = $cmsController->getCurrentLang()->getPrefix();
					}
					if($lang = $cmsController->getCurrentLang()) {
						$defaultParams['domain'] = $cmsController->getCurrentDomain()->getHost();
					}
				}
				
				$params = (is_null($params)) ? $defaultParams : array_merge($params, $defaultParams);
				foreach($params as $i => $v) $path = str_replace('{' . $i . '}', $v,  $path);
			}
			
			
			if(substr($path, 0, 2) == "~/") {
				$path = CURRENT_WORKING_DIR . substr($path, 1);
			}
			
			return $path;
		}
		
		/**
		 * 
		 */
		private function writeIni() {
			$iniString = "";
			foreach($this->ini as $sname => $section) {
				if(empty($section)) continue;
				$iniString .= "[{$sname}]\n";
				foreach($section as $name => $value) {
					if(is_array($value)) {
						foreach($value as $sval) {
							$sval = ($sval !== '') ? '"' . $sval . '"' : '';
							$iniString .= "{$name}[] = {$sval}\n";
						}
					} else {
						$value = ($value !== '') ? '"' . $value . '"' : '';
						$iniString .= "{$name} = {$value}\n";
					}
				}
				$iniString .= "\n";
			}
			file_put_contents(CONFIG_INI_PATH, $iniString);
		}
		
		private function unescapeValue($value) {
			if(is_array($value)) {
				foreach($value as $i => $v) {
					$value[$i] = $this->unescapeValue($v);
				}
				return $value;
			}
			
			if(strlen($value) >= 2 && substr($value, 0, 1) == "'" && substr($value, -1, 1) == "'") {
				$value = substr($value, 1, strlen($value) - 2);
			}			
			return $value;
		}
	};


	if(!defined('CONFIG_INI_PATH')) {
		define('CONFIG_INI_PATH', CURRENT_WORKING_DIR . '/config.ini');
	}

	if(!class_exists('mainConfiguration')) {
		require CURRENT_WORKING_DIR . '/libs/configuration.php';
	}

	try {
		$config = mainConfiguration::getInstance();
	} catch (Exception $e) {
		echo 'Critical error: ', $e->getMessage();
		exit;
	}

	$ini = $config->getParsedIni();
	
	initConfigConstants($ini);

	define("SYS_KERNEL_PATH", $config->includeParam('system.kernel'));
	define("SYS_KERNEL_ASM", $config->includeParam('system.kernel.assebled'));
	define("SYS_LIBS_PATH", $config->includeParam('system.libs'));
	define("SYS_DEF_MODULE_PATH", $config->includeParam('system.default-module'));
	define("SYS_TPLS_PATH", $config->includeParam('templates.tpl'));
	define("SYS_XSLT_PATH", $config->includeParam('templates.xsl'));
	define("SYS_SKIN_PATH", $config->includeParam('templates.skins'));
	define("SYS_ERRORS_PATH", $config->includeParam('system.error'));
	define("SYS_MODULES_PATH", $config->includeParam('system.modules'));
	define("SYS_CACHE_RUNTIME", $config->includeParam('system.runtime-cache'));
	define("SYS_MANIFEST_PATH", $config->includeParam('system.manifest'));
	define("SYS_KERNEL_STREAMS", $config->includeParam('system.kernel.streams'));
	
	define("KEYWORD_GRAB_ALL", $config->get('kernel', 'grab-all-keyword'));
	
	$cacheSalt = $config->get('system', 'salt');
	if(!$cacheSalt) {
		$cacheSalt = sha1(rand());
		$config->set('system', 'salt', $cacheSalt);
	}
	define("SYS_CACHE_SALT", $cacheSalt);

	if(!defined('_C_REQUIRES')) {
		require SYS_LIBS_PATH . 'requires.php';
	}

	// [debug]
	$debug = false;
	if($config->get('debug', 'enabled')) {
		$ips = $config->get('debug', 'filter.ip');
		if(is_array($ips)) {
			if(in_array(getServer('REMOTE_ADDR'), $ips)) {
				$debug = true;
			}
		} else {
			$debug = true;
		}
	}
	define('DEBUG', $debug);
	
	if(!defined('_C_ERRORS')) {
		require SYS_LIBS_PATH . 'errors.php';
	}

	initConfigConnections($ini);

	if(defined("LIBXML_VERSION")) {
		define("DOM_LOAD_OPTIONS", (LIBXML_VERSION < 20621) ? 0 : LIBXML_COMPACT);
	} else {
		define("DOM_LOAD_OPTIONS", LIBXML_COMPACT);
	}
	if (!defined("PHP_INT_MAX")) define("PHP_INT_MAX", 4294967296 / 2 - 1);
	

	if(!isset($_ENV['OS']) || strtolower(substr($_ENV['OS'], 0, 3)) != "win") {
		setlocale(LC_NUMERIC, 'en_US.utf8');
	}
	
	if(function_exists("mb_internal_encoding")) {
		mb_internal_encoding('UTF-8');
	}
	
	// system.session-lifetime
	ini_set("session.gc_maxlifetime", SESSION_LIVETIME * 60);
	ini_set("session.cookie_lifetime", "0");
	ini_set("session.use_cookies", "1");
	ini_set("session.use_only_cookies", "1");
	
	// kernel:cluster-cache-correction
	if(CLUSTER_CACHE_CORRECTION) {
		cacheFrontend::getInstance();
		clusterCacheSync::getInstance();
	}
	
	
	function __autoload($className) {
		global $includes;

		if ($className == "XSLTProcessor" && !class_exists("XSLTProcessor")){
			xslt_fatal();
		}
		
		//Debug section
		if(defined('INTERRUPT_DEPRECATED_CALL') && INTERRUPT_DEPRECATED_CALL) {
			$deprecatedClasses = array('umiSelection', 'umiSelectionsParser');
			if(in_array($className, $deprecatedClasses)) {
				$e = new coreException("Deprecated class \"{$className}\" called");
				traceException($e);
			}
		}

		if(isset($includes[$className])) {
			$files = $includes[$className];
			if(is_array($files)) foreach($files as $filePath) require $filePath;
		}
	}


	function initConfigConstants($ini) {
		$defineConstants = array(
			'system:db-driver' => array('DB_DRIVER', '%value%'),
			'system:version-line' => array('CURRENT_VERSION_LINE', '%value%'),
			'system:session-lifetime' => array('SESSION_LIVETIME', '%value%'),
			'system:default-date-format' => array('DEFAULT_DATE_FORMAT', '%value%'),
			'kernel:use-reflection-extension' => array('USE_REFLECTION_EXT', '%value%'),
			'kernel:cluster-cache-correction' => array('CLUSTER_CACHE_CORRECTION', '%value%'),
			'kernel:xslt-nested-menu' => array('XLST_NESTED_MENU', '%value%'),
			'kernel:pages-auto-index' => array('PAGES_AUTO_INDEX', '%value%'),
			'kernel:enable-pre-auth' => array('PRE_AUTH_ENABLED', '%value%'),
			'kernel:ignore-module-names-overwrite' => array('IGNORE_MODULE_NAMES_OVERWRITE', '%value%'),
			'kernel:xml-format-output' => array('XML_FORMAT_OUTPUT', '%value%'),
			'kernel:selection-max-joins' => array('MAX_SELECTION_TABLE_JOINS', '%value%'),
			'kernel:property-value-mode' => array('XML_PROP_VALUE_MODE', '%value%'),
			'kernel:xml-macroses-disable' => array('XML_MACROSES_DISABLE', '%value%'),
			'kernel:selection-calc-found-rows-disable' => array('DISABLE_CALC_FOUND_ROWS', '%value%'),
			'kernel:sql-query-cache' => array('SQL_QUERY_CACHE', '%value%'),
			'seo:calculate-e-tag' => array('CALC_E_TAG', '%value%'),
			'seo:calculate-last-modified' => array('CALC_LAST_MODIFIED', '%value%')
		);

		foreach($defineConstants as $name => $const) {
			list($section, $variable) = explode(':', $name);
			$value = $const[1];
			
			if(is_string($value)) {
				$iniValue = isset($ini[$section][$variable]) ? $ini[$section][$variable] : "";
				$value = str_replace('%value%', $iniValue, $value);
			} else if (!$value && isset($const[2])) {
				$value = $const[2];
			}
			
			if(!defined($const[0])) {
				if($const[0] == 'CURRENT_VERSION_LINE' && !$value) {
					continue;
				}
				define($const[0], $value);
			}
		}
	}


	function initConfigConnections($ini) {
		$connections = array();

		foreach($ini['connections'] as $name => $value) {
			list($class, $pname) = explode('.', $name);
			if(!isset($connections[$class])) {
				$connections[$class] = array(
										'type'        => 'mysql',
										'host'		  => 'localhost',
										'login'       => 'root',
										'password'    => '',
										'dbname'      => 'umi',
										'port'	      => false,
										'persistent'  => false,
										'compression' => false);
			}
			$connections[$class][$pname] = $value;
		}

		$pool = ConnectionPool::getInstance();
		foreach($connections as $class => $con) {
				switch($con['type']) {
						default:
								$pool->setConnectionObjectClass();
				}
				
				if($con['dbname'] == '-=demo=-' || $con['dbname'] == '-=custom=-') {
					if($con['dbname'] == '-=demo=-') {
						require './demo-center.php';
					}
					
					$con['host'] = MYSQL_HOST;
					$con['login'] = MYSQL_LOGIN;
					$con['password'] = MYSQL_PASSWORD;
					$con['dbname'] = ($con['dbname'] == '-=custom=-') ? MYSQL_DB_NAME : DEMO_DB_NAME;
				}
				
				$pool->addConnection($class, $con['host'], $con['login'], $con['password'], $con['dbname'],
					($con['port'] !== false) ? intval($con['port']) : false,
					(bool) intval($con['persistent']) );
		}

		if(DB_DRIVER == "mysql") {
			$connection = ConnectionPool::getInstance()->getConnection();
			ini_set('mysql.trace_mode', false);
		}
	}
	
	function mysql_fatal() {
		require "./errors/mysql_failed.html";
		exit();
	}

	function xslt_fatal(){
		require ("./errors/xslt_failed.html");
		exit();	
	}


	define('_C_ERRORS', true);

	error_reporting(DEBUG ? ~E_STRICT : E_ERROR);
	
	ini_set("display_errors", "1");

	function traceException($e) {
		global $message, $traceAsString;

		$message = $e->getMessage();
		$traceAsString = $e->getTraceAsString();

		header("HTTP/1.1 500 Internal Server Error");
		header("Content-type: text/html; charset=utf-8");
		header("Status: 500 Internal Server Error");
		require SYS_ERRORS_PATH . "exception.php";
		exit();
	}
	
	set_exception_handler('traceException');

	function criticalErrorsBufferHandler($buffer) {
		if(isset($GLOBALS['memoryReserve'])) unset($GLOBALS['memoryReserve']);
		$errors = Array('Fatal', 'Parse');
		
		foreach($errors as $error) {
			if(strstr($buffer, "<br />\n<b>{$error} error</b>:") !== false) {
				$message = substr(trim(strip_tags($buffer)), strlen($error) + 9);
				$traceAsString  = "Backtrace can't be displayed";
				$e = new coreException($message);
				require SYS_ERRORS_PATH . 'exception.php';
				
				$errorBuffer = ob_get_contents();
				$buffer = substr($errorBuffer, strlen($buffer));
				break;
			}
		}
		return $buffer;
	}
	
	$GLOBALS['memoryReserve'] = str_repeat(" ", 1024);
	
	if(!defined("DEBUG") && function_exists("libxml_use_internal_errors")) {
	    libxml_use_internal_errors(true);
	}
	
	function checkXmlError($dom) {
	    if(defined("DEBUG") || !function_exists("libxml_get_last_error")) return;

		if($dom === false) {
			$error = libxml_get_last_error();
			libxml_clear_errors();
			
			$message = $error->message;
			$traceAsString = $error->file . "<br />in line " . $error->line . " column " . $error->column;
			
			require SYS_ERRORS_PATH . "exception.php";
			exit();
		}
	}
	
	function xsltErrorsHandler($errno, $errstr, $errfile, $errline, $e) {
	    if(defined("DEBUG") || !function_exists("libxml_get_last_error")) return;
		$message = $errfile;

		if($errline != 0 || $errno != 2) return;

		$message = "XSLT template in not correct.";
		$errors = libxml_get_errors();
			
		$traceAsString = "";
		foreach($errors as $error) {
			$traceAsString .= "<li>XSLT error: " . $error->message . "</li>";
		}
			
		require SYS_ERRORS_PATH . "exception.php";
		exit();
	}
	
	function errorsXsltListen() {
	    if(defined("DEBUG")) return;
		set_error_handler("xsltErrorsHandler");
		return error_reporting(~E_STRICT);
	}
	
	function errorsXsltCheck($er) {
	    if(defined("DEBUG")) return;
		error_reporting($er);
		restore_error_handler();
	}


	interface iSingleton {
		public static function getInstance($c = NULL);
	};

	interface iUmiEntinty {
		public function getId();
		public function commit();
		public function update();

		public static function filterInputString($string);
	};


	interface iUmiField {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getIsInheritable();
		public function setIsInheritable($isInheritable);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getFieldTypeId();
		public function setFieldTypeId($fieldTypeId);

		public function getFieldType();

		public function getGuideId();
		public function setGuideId($guideId);

		public function getIsInSearch();
		public function setIsInSearch($isInSearch);

		public function getIsInFilter();
		public function setIsInFilter($isInFilter);

		public function getTip();
		public function setTip($tip);
		
		public function getIsRequired();
		public function setIsRequired($isRequired = false);

		public function getIsSortable();
		public function setIsSortable($sortable = false);
		
		public function getRestrictionId();
		public function setRestrictionId($restrictionId = false);
		
		public function getIsSystem();
		public function setIsSystem($isSystem = false);
		
		public function getDataType();
	};
	
	interface iUmiFieldType {
		public function getName();
		public function setName($name);

		public function getIsMultiple();
		public function setIsMultiple($isMultiple);

		public function getIsUnsigned();
		public function setIsUnsigned($isUnsigned);

		public function getDataType();
		public function setDataType($dataTypeStr);

		public static function getDataTypes();
		public static function getDataTypeDB($dataType);
		public static function isValidDataType($dataTypeStr);
	};
	
	interface iUmiFieldTypesCollection {
		public function addFieldType($name, $dataType = "string", $isMultiple = false, $isUnsigned = false);
		public function delFieldType($fieldTypeId);
		public function getFieldType($fieldTypeId);

		public function getFieldTypesList();
	};
	
	interface iUmiFieldsCollection {
		public function addField($name, $title, $fieldTypeId, $isVisible = true, $isLocked = false, $isInheritable = false);
		public function delField($field_id);
		public function getField($fieldId);
	};
	
	interface iUmiFieldsGroup {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getOrd();
		public function setOrd($ord);

		public function getIsActive();
		public function setIsActive($isActive);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getFields();

		public function attachField($fieldId);
		public function detachField($fieldId);

		public function moveFieldAfter($fieldId, $beforeFieldId, $group_id, $is_last);
		
		public static function getAllGroupsByName($fieldName);
	};
	
	interface iUmiObject {
		public function getName();
		public function setName($name);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getPropGroupId($groupName);
		public function getPropGroupByName($groupName);
		public function getPropGroupById($groupId);

		public function getPropByName($propName);
		public function getPropById($propId);

		public function isPropertyExists($id);

		public function isFilled();

		public function getValue($propName);
		public function setValue($propName, $propValue);

		public function setOwnerId($ownerId);
		public function getOwnerId();
	};
	
	interface iUmiObjectProperty {
		public function getValue();
		public function setValue($value);
		public function resetValue();

		public function getName();
		public function getTitle();

		public function getIsMultiple();
		public function getIsUnsigned();
		public function getDataType();
		public function getIsLocked();
		public function getIsInheritable();
		public function getIsVisible();

		public static function filterOutputString($string);
		public static function filterCDATA($string);
		
		public function getObject();
		public function getField();
	};
	
	interface iUmiObjectType {
		public function addFieldsGroup($name, $title, $isActive = true, $isVisible = true);
		public function delFieldsGroup($fieldGroupId);

		public function getFieldsGroupByName($fieldGroupName);

		public function getFieldsGroup($fieldGroupId);
		public function getFieldsGroupsList($showDisabledGroups = false);

		public function getName();
		public function setName($name);

		public function setIsLocked($isLocked);
		public function getIsLocked();

		public function setIsGuidable($isGuidable);
		public function getIsGuidable();

		public function setIsPublic($isPublic);
		public function getIsPublic();

		public function setHierarchyTypeId($hierarchyTypeId);
		public function getHierarchyTypeId();

		public function getParentId();

		public function setFieldGroupOrd($groupId, $newOrd, $isLast);


		public function getFieldId($fieldName);

		public function getAllFields($returnOnlyVisibleFields = false);
		
		public function getModule();
		public function getMethod();
	};
	
	interface iUmiObjectTypesCollection {
		public function addType($parentId, $name, $isLocked = false);
		public function delType($typeId);

		public function getType($typeId);
		public function getSubTypesList($typeId);

		public function getParentClassId($typeId);
		public function getChildClasses($typeId);

		public function getGuidesList($publicOnly = false);

		public function getTypesByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);
		public function getTypeByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);

		public function getBaseType($typeName, $typeExt = "");
	};
	
	interface iUmiObjectsCollection {
		public function getObject($objectId);
		public function addObject($name, $typeId, $isLocked = false);
		public function delObject($objectId);

		public function cloneObject($iObjectId);

		public function getGuidedItems($guideId);

		public function unloadObject($objectId);
	};


	interface iDomain {
		public function getIsDefault();
		public function setIsDefault($isDefault);

		public function addMirrow($mirrowHost);
		public function delMirrow($mirrowId);

		public function getMirrowId($mirrowHost);
		public function getMirrow($mirrowId);

		public function getMirrowsList();
		public function delAllMirrows();


		public function isMirrowExists($mirrowId);

		public function getDefaultLangId();
		public function setDefaultLangId($langId);
	};
	
	interface iDomainMirrow {
		public function getHost();
		public function setHost($host);
	};
	
	interface iDomainsCollection {
		public function addDomain($host, $defaultLangId, $isDefault = false);
		public function delDomain($domainId);
		public function getDomain($domainId);

		public function getDefaultDomain();
		public function setDefaultDomain($domainId);

		public function getDomainId($host, $useMirrows = true);

		public function getList();
	};
	
	interface iLang {
		public function getTitle();
		public function setTitle($title);

		public function getPrefix();
		public function setPrefix($prefix);

		public function getIsDefault();
		public function setIsDefault($isDefault);
	};
	
	interface iLangsCollection {
		public function addLang($prefix, $title, $isDefault = false);
		public function delLang($langId);

		public function getDefaultLang();
		public function setDefault($langId);

		public function getLangId($prefix);
		public function getLang($langId);

		public function getList();

		public function getAssocArray();
	};
	
	interface iTemplate {
		public function getFilename();
		public function setFilename($filename);

		public function getTitle();
		public function setTitle($title);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getLangId();
		public function setLangId($langId);

		public function getIsDefault();
		public function setIsDefault($isDefault);
		
		public function getUsedPages();
		public function setUsedPages($elementIdArray);
	};
	
	interface iTemplatesCollection {
		public function addTemplate($filename, $title, $domainId = false, $langId = false, $isDefault = false);
		public function delTemplate($templateId);


		public function getDefaultTemplate($domain_id = false, $lang_id = false);
		public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false);

		public function getTemplatesList($domainId, $langId);

		public function getTemplate($templateId);
	};
	
	interface iUmiHierarchy {
		public function addElement($relId, $hierarchyTypeId, $name, $alt_name, $objectTypeId = false, $domainId = false, $langId = false, $templateId = false);
		public function getElement($elementId, $ignorePermissions = false, $ignoreDeleted = false);
		public function delElement($elementId);

		public function copyElement($elementId, $newRelId, $copySubPages = false);
		public function cloneElement($elementId, $newRelId, $copySubPages = false);


		public function getDeletedList();

		public function restoreElement($elementId);
		public function removeDeletedElement($elementId);
		public function removeDeletedAll();


		public function getParent($elementId);
		public function getAllParents($elementsId, $selfInclude = false);

		public function getChilds($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);
		public function getChildsCount($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);

		public function getPathById($elementId, $ignoreLang = false, $ignoreIsDefaultStatus = false);
		public function getIdByPath($elementPath, $showDisabled = false, &$errorsCount = 0);

		public static function compareStrings($string1, $string2);
		public static function convertAltName($alt_name);
		public static function getTimeStamp();

		public function getDefaultElementId($langId = false, $domainId = false);

		public function moveBefore($elementId, $relId, $beforeId = false);
		public function moveFirst($elementId, $relId);

		public function getDominantTypeId($elementId);

		//public function applyFilter(umiHierarchyFilter);
		
		public function addUpdatedElementId($elementId);
		public function getUpdatedElements();
		
		public function unloadElement($elementId);
		
		public function getElementsCount($module, $method = "");

		public function forceAbsolutePath($bIsForced = true);
		
		public function getObjectInstances($objectId, $bIgnoreDomain = false, $bIgnoreLang = false);
		
		public function getLastUpdatedElements($limit, $updateTimeStamp = 0);
		
		public function checkIsVirtual($elementIds);
	};
	
	interface iUmiHierarchyElement {
		public function getIsDeleted();
		public function setIsDeleted($isDeleted = false);

		public function getIsActive();
		public function setIsActive($isActive = true);

		public function getIsVisible();
		public function setIsVisible($isVisible = true);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getLangId();
		public function setLangId($langId);

		public function getTplId();
		public function setTplId($tplId);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getUpdateTime();
		public function setUpdateTime($timeStamp = 0);

		public function getOrd();
		public function setOrd($ord);

		public function getRel();
		public function setRel($rel_id);

		public function getObject();
		public function setObject(umiObject $object);

		public function setAltName($altName, $autoConvert = true);
		public function getAltName();

		public function setIsDefault($isDefault = true);
		public function getIsDefault();

		public function getParentId();

		public function getValue($propName, $params = NULL);
		public function setValue($propName, $propValue);

		public function getFieldId($FieldName);

		public function getName();
		public function setName($name);
		
		public function getObjectTypeId();
		
		public function getHierarchyType();
		
		public function getObjectId();
		
		
		public function getModule();
		public function getMethod();
	};
	
	interface iUmiHierarchyType {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getExt();
		public function setExt($ext);
	};
	
	interface iUmiHierarchyTypesCollection {
		public function addType($name, $title, $ext = "");
		public function getType($typeId);
		public function delType($typeId);
		public function getTypeByName($typeName, $extName = false);

		public function getTypesList();
	};


	interface iXmlTranslator {

		public function __construct(DOMDocument $dom);
		
		public function translateToXml(DOMElement $rootNode, $userData);
		
		public function getSubKey($key);
		public function getRealKey($key);

	};


	interface iOutputBuffer {
		public function push($data);
		public function content();
		public function length();
		public function clear();
		public function send();
		public function end();
	};


	abstract class outputBuffer implements iOutputBuffer {
		private static $buffers = array(), $current = false;
		
		final static public function current($bufferClassName = false) {
			$buffers = &self::$buffers;

			if(!$bufferClassName) {
				if(self::$current) {
					$bufferClassName = self::$current;
				} else {
					throw new coreException('No output buffer selected');
				}
			}
			self::$current = $bufferClassName;

			if(isset($buffers[$bufferClassName]) == false) {
				if(class_exists($bufferClassName)) {
					$buffer = new $bufferClassName;
					if($buffer instanceof iOutputBuffer) {
						$buffers[$bufferClassName] = $buffer;
					} else {
						throw new coreException("Output buffer class \"{$bufferClassName}\" must implement iOutputBuffer");
					}
				} else {
					throw new coreException("Output buffer of class \"{$bufferClassName}\" not found");
				}
			}

			return $buffers[$bufferClassName];
		}
		
		
		//Methods useful for extending
		protected $buffer = "", $invokeTime;
		
		public function __construct() { $this->invokeTime = microtime(true); }

		public function clear() { $this->buffer = ""; }

		public function length() { return strlen($this->buffer); }

		public function content() { return $this->buffer; }

		public function push($data) { $this->buffer .= $data; }
		
		public function end() { $this->send(); }
		
		public function __call($method, $params) { return null; }
		
		public function __destruct() {
			$this->send();
		}
	};


/**
	* Базовый класс синглетон
*/
	abstract class singleton {
		private static $instances = Array();

		/**
			* Конструктор, который необходимо перегрузить в дочернем классе
		*/
		abstract protected function __construct();

		/**
			* Получить экземпляр класса, необходимо перегрузить в дочернем классе:
			* parent::getInstance(__CLASS__)
			* @param String имя класса
			* @return singleton экземпляр класса
		*/
		public static function getInstance($c) {
			if (!isset(singleton::$instances[$c])) {
				singleton::$instances[$c] = new $c;
			}
			return singleton::$instances[$c];
		}

		/**
			* Запрещаем копирование
		*/
		public function __clone() {
			throw new coreException('Singletone clonning is not permitted. Just becase it\'s non-sense.');
		}
		
		/**
			* Отключить кеширование повторных sql-запросов
		*/
		protected function disableCache() {
			if(!defined('MYSQL_DISABLE_CACHE')) {
				define('MYSQL_DISABLE_CACHE', '1');
			}
		}

		/**
			* Получить языкозависимую строку по ее ключу
			* @param String $label ключ строки
			* @return String значение строки в текущей языковой версии
		*/
		protected function translateLabel($label) {
			$prefix = "i18n::";
			if(substr($label, 0, strlen($prefix)) == $prefix) {
				$str = getLabel(substr($label, strlen($prefix)));
			} else {
				$str = getLabel($label);
			}
			return (is_null($str)) ? $label : $str;
		}

	};


/**
	* Базовый класс для классов, которые реализуют ключевые сущности ядра системы.
	* Реализует основные интерфейсы, которые должна поддерживать любая сущность.
*/
	abstract class umiEntinty {
		protected $id, $is_updated = false;

		protected $bNeedUpdateCache = false;

		/**
			* Конструктор сущности, должен вызываться из коллекций
			* @param Integer $id id сущности
			* @param Array $row=false массив значений, который теоретически может быть передан в конструктор для оптимизации
		*/
		public function __construct($id, $row = false) {
			$this->setId($id);
			$this->is_updated = false;
			if($this->loadInfo($row) === false) {
				throw new privateException("Failed to load info for {$this->store_type} with id {$id}");
			}
		}

		/**
			* Запрещаем копирование
		*/
		public function __clone() {
				throw new coreException('umiEntinty must not be cloned');
    	}

		/**
			* Деструктор сущности проверят, были ли внесены изменения. Если да, то они сохраняются
		*/
		public function __destruct() {
			if ($this->is_updated) {
				$this->save();
				$this->setIsUpdated(false);
				$this->updateCache();
			} elseif ($this->bNeedUpdateCache) {
				// В memcached кидаем только при деструкте и только если были какие-то изменения
				$this->updateCache();
			}
		}

		/**
			* Вернуть id сущности
			* @return Integer $id
		*/
		public function getId() {
			return $this->id;
		}

		/**
			* Изменить id сущности
			* @param Integer $id новый id сущности
		*/
		protected function setId($id) {
			$this->id = (int) $id;
		}

		/**
			* Узнать, есть ли несохраненные модификации
			* @return Boolean true если есть несохраненные изменения
		*/
		public function getIsUpdated() {
			return $this->is_updated;
		}

		/**
			* Установить флаг "изменен"
			* @param Boolean $is_updated=true значение флага "изменен"
		*/
		public function setIsUpdated($is_updated = true) {
			$this->is_updated 	    = (bool) $is_updated;
			$this->bNeedUpdateCache = $this->is_updated;
		}
		
		public function beforeSerialize() {}
		public function afterSerialize() {}
		
		public function afterUnSerialize() {}

		/**
			* Загрузить необходимую информацию о сущности из БД. Требует реализации в дочернем классе.
		*/
		abstract protected function loadInfo();

		/**
			* Сохранить в БД информацию о сущности. Требует реализации в дочернем классе.
		*/
		abstract protected function save();

		/**
			* Применить совершенные изменения, если они есть. Если нет, вернет false
			* @return Boolean true если изменения примененые и при этом не возникло ошибок
		*/
		public function commit() {
			if ($this->is_updated) {
				$this->disableCache();
				$res = $this->save();

				if (cacheFrontend::getInstance()->getIsConnected()) {
					// обновляем инфу об объекте из базы для корректного сохранения не применившихся свойств в memcached
					$this->update();
				} else {
					$this->setIsUpdated(false);
				}

				return $res;
			} else {
				return false;
			}
		}

		/**
			* Заново прочитать все данные сущности из БД. Внесенные изменения скорее всего будут утеряны
			* @return Boolean результат операции зависит от реализации loadInfo() в дочернем классе
		*/
		public function update() {
			$res = $this->loadInfo();
			$this->setIsUpdated(false);
			$this->updateCache();
			return $res;
		}

		/**
			* Отфильтровать значения, попадающие в БД
			* @param String $string значение
			* @return String отфильтрованное значение
		*/
		public static function filterInputString($string) {
			$string = mysql_real_escape_string($string);
			return $string;

		}
		
		/**
			* Magic method
			* @return id объекта
		*/
		public function __toString() {
			return (string) $this->getId();
		}
		
		/**
			* Обновить версию сущности, которая находится в кеше
		*/
		protected function updateCache() {
			cacheFrontend::getInstance()->save($this, $this->store_type);
		}
		
		/**
			* Отключить каширование повторных sql-запросов
		*/
		protected function disableCache() {
			if(!defined('MYSQL_DISABLE_CACHE')) {
				if(get_class($this) === "umiObjectProperty") {
					return;
				}
				define('MYSQL_DISABLE_CACHE', '1');
			}
		}

		/**
			* Перевести строковую константу по ее ключу
			* @param String $label ключ строковой константы
			* @return String значение константы в текущей локали
		*/
		protected function translateLabel($label) {
			$prefix = "i18n::";
			if(substr($label, 0, strlen($prefix)) == $prefix) {
				$str = getLabel(substr($label, strlen($prefix)));
			} else {
				$str = getLabel($label);
			}
			return (is_null($str)) ? $label : $str;
		}

		/**
			* Получить ключ строковой константы, если она определена, либо вернуть саму строку
			* @param String $str строка, для которых нужно определить ключ
			* @param String $pattern="" префикс ключа, используется внутри системы
			* @return String ключ константы, либо параметр $str, если такого значение нет в списке констант
		*/
		protected function translateI18n($str, $pattern = "") {
			$label = getI18n($str, $pattern);
			return (is_null($label)) ? $str : $label;
		}
	};


	abstract class baseException extends Exception {
		protected $strcode, $id;

		public static $catchedExceptions = Array();
		
		public function __construct ($message, $code = 0, $strcode = "") {
			$message = templater::putLangs($message);
		
			baseException::$catchedExceptions[$this->getId()] = $this;
			$this->strcode = $strcode;
			parent::__construct($message, $code);
		}
		
		
		public function getStrCode() {
			return (string) $this->strcode;
		}
		
		public function unregister() {
			$catched = &baseException::$catchedExceptions;
			$id = $this->getId();
			
			if(isset($catched[$id])) {
				unset($catched[$id]);
			}
		}
		
		protected function getId() {
			static $id = 0;
			if(is_null($this->id))  {
				$this->id = $id++;
			}
			return $this->id;
		}
	};


	class coreException extends baseException {};
	
	class coreBreakEventsException extends coreException {};

	class selectorException extends coreException {};


/**
 * Класс исключения, связаного с бд
 * Фатально по своей природе
 */
class databaseException extends coreException {};


	class privateException extends baseException {};

	class wrongParamException extends privateException {};
	
	class errorPanicException extends Exception {};
	
	class breakException extends Exception {};
	
	
	abstract class fieldRestrictionException extends privateException {};
	
	class wrongValueException extends fieldRestrictionException {};
	
	class valueRequiredException extends fieldRestrictionException {};


	class publicException extends baseException {};

	class publicAdminException extends publicException {};

	class expectElementException extends publicAdminException {};
	class expectObjectException extends publicAdminException {};
	class expectObjectTypeException extends publicAdminException {};
	
	class requireAdminPermissionsException extends publicAdminException {};
	class requreMoreAdminPermissionsException extends publicAdminException {};
	class requireAdminParamException extends publicAdminException {};
	class wrongElementTypeAdminException extends publicAdminException {};
	class publicAdminPageLimitException extends publicAdminException {};
	class publicAdminLicenseLimitException extends publicAdminException {};
	
	class maxIterationsExeededException extends publicException {};
	
	class umiRemoteFileGetterException extends publicException {};
	
	class xsltOnlyException extends publicException {
		public function __construct ($message = "", $code = 0, $strcode = "") {
			parent::__construct(getLabel('error-only-xslt-method'));
		}
	};
	
	class tplOnlyException extends publicException {
		public function __construct ($message = "", $code = 0, $strcode = "") {
			parent::__construct(getLabel('error-only-tpl-method'));
		}
	};


	interface iRegedit {
		public function getKey($keyPath, $rightOffset = 0);

		public function getVal($keyPath);
		public function setVar($keyPath, $value);
		public function setVal($keyPath, $value);

		public function delVar($keyPath);

		public function getList($keyPath);
	};



class regedit extends singleton implements iRegedit {
	protected $cacheFilePath, $cache = Array(), $cacheSaved = false;
	
	
	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}

	
	public function getKey($path, $rightOffset = 0) {
		static $cache = array();
		$path = trim($path, "/");
		
		if(isset($this->cache['keys'][$path])) {
			return $this->cache['keys'][$path];
		}
		
		$keyId = 0; $previousPaths = array();
		foreach(split("\/", $path) as $key) {
			$key = l_mysql_real_escape_string($key);
			$previousPaths[] = $key;
			$currentKey = implode('/', $previousPaths);
			
			if(isset($cache[$currentKey])) {
				$keyId = $cache[$currentKey];
				continue;
			}
			
			$sql = "SELECT id FROM cms_reg WHERE rel = '$keyId' AND var = '{$key}'";
			$result = l_mysql_query($sql, true);
			if(mysql_num_rows($result)) {
				list($keyId) = mysql_fetch_row($result);
				$cache[$currentKey] = $keyId;
			} else {
				return $this->cache['keys'][$path] = false;
			}
		}
		return $this->cache['keys'][$path] = (int) $keyId;
	}
	
	public function getVal($path) {
		$keyId = $this->getKey($path);
		
		if(isset($this->cache['values'][$path])) {
			return $this->cache['values'][$path];
		}
		
		if($keyId) {
			if(isset($this->cache['values'][$keyId])) {
				return $this->cache['values'][$keyId];
			}
			$this->cacheSaved = false;
			
			$sql = "SELECT val FROM cms_reg WHERE id = '{$keyId}'";
			$result = l_mysql_query($sql, true);
		
			list($value) = mysql_fetch_row($result);
			return $this->cache['values'][$keyId] = $value;
		} else {
			return $this->cache['values'][$path] = false;
		}
	}
	
	public function setVar($path, $value) {
		return $this->setVal($path, $value);
	}
	
	public function setVal($path, $value) {
		if(defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo') {
			return false;
		}
		
		$this->resetCache();
		$keyId = $this->getKey($path);
		if($keyId == false) {
			$keyId = $this->createKey($path);
		}
		
		$value = l_mysql_real_escape_string($value);
		$sql = "UPDATE cms_reg SET val = '{$value}' WHERE id = '{$keyId}'";
		l_mysql_query($sql);
		
		$this->resetCache();
	}

	public function delVar($path) {
		if(defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo') {
			return false;
		}
	
		$keyId = $this->getKey($path);
		if($keyId) {
			$sql = "DELETE FROM cms_reg WHERE rel = '{$keyId}' OR id = '{$keyId}'";
			l_mysql_query($sql, true);
			$this->resetCache();
			return true;
		} else {
			return false;
		}
	}

	public function getList($path) {
		if(isset($this->cache['lists'][$path])) {
			return $this->cache['lists'][$path];
		}
		
		$keyId = $this->getKey($path);
		
		if($path == "//") {
			$keyId = 0;
		}
		
		if($keyId || $path == "//") {
			if(isset($this->cache['lists'][$keyId])) {
				return $this->cache['lists'][$keyId];
			}
			$this->cacheSaved = false;
			
			$sql = "SELECT id, var, val FROM cms_reg WHERE rel = '{$keyId}' ORDER BY id ASC";
			$result = l_mysql_query($sql, true);
			
			$values = Array();
			while(list($id, $var, $val) = mysql_fetch_array($result)) {
				$values[] = Array($var, $val);
			}
			return $this->cache['lists'][$keyId] = $values;
		} else {
			return $this->cache['lists'][$path] = false;
		}
	}
	
	final public static function checkSomething($a, $b) {
		$trial_lifetime = 3600*24*45;

		if(($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'subdomain.localhost') && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
			return true;
		}
		
		if(substr($_SERVER['HTTP_HOST'], strlen($_SERVER['HTTP_HOST']) - 4, 4) == "cvs5") {
			return true;
		}
		
		foreach($b as $version_line => $c3) {
			$is_valid = (bool) (substr($a, 12, strlen($a) - 12) == $c3);
			
			if($is_valid === true) {
				define("CURRENT_VERSION_LINE", $version_line);
				
				if($version_line == "trial") {
					$create_time = filectime(__FILE__);
					$current_time = time();
					
					if(file_exists(SYS_CACHE_RUNTIME . "trash")) {
						unlink(SYS_CACHE_RUNTIME . "trash");
					}
					
					if(($current_time - $create_time) > $trial_lifetime){
						include CURRENT_WORKING_DIR . "/errors/trial_expired.html";
						exit();
					}
				}
				return true;
			}
		}
	}

	
	public function getDaysLeft() {
		return 45 - floor((time() - filectime(__FILE__)) / 3600*24);
	}

	
	protected function __construct() {
		$config = mainConfiguration::getInstance();
		$this->cacheFilePath = $config->includeParam('system.runtime-cache') . 'registry';
		$this->loadCache();
	}
	
	public function __destruct() {
		if(!$this->cacheSaved) {
			$this->saveCache();
		}
	}
	
	protected function loadCache() {
		$cacheFrontend = cacheFrontend::getInstance();
		
		if($cacheFrontend->getIsConnected()) {
				if($cache = $cacheFrontend->loadSql("registry")) {
					$this->cache = unserialize($cache);
					$this->cacheSaved = true;
					return;
				}
		}
		
		if(file_exists($this->cacheFilePath)) {
			$cache = unserialize(file_get_contents($this->cacheFilePath));
			if(is_array($cache)) {
				$this->cacheSaved = true;
				$this->cache = $cache;
			}
		}
	}
	
	protected function saveCache() {
		if(is_array($this->cache)) {
			if(is_dir(dirname($this->cacheFilePath))) {
				file_put_contents($this->cacheFilePath, serialize($this->cache));
			}
			if(cacheFrontend::getInstance()->getIsConnected()) {
				cacheFrontend::getInstance()->saveSql("registry", serialize($this->cache));
			}
		}
		$this->cacheSaved = true;
	}
	
	protected function createKey($path) {
		$path = trim($path, "/");
		$subKeyPath = "//";
		
		$relId = 0;
		foreach(split("\/", $path) as $key) {
			$subKeyPath .= $key . "/";
			
			
			if($keyId = $this->getKey($subKeyPath)) {
				$relId = $keyId;
			} else {
				$sql = "INSERT INTO cms_reg (rel, var, val) VALUES ('$relId', '{$key}', '')";
				l_mysql_query($sql, true);
				$relId = $keyId = (int) mysql_insert_id();
			}
		}
		return $keyId;
	}
	
	protected function resetCache($keys = false) {
		if(is_array($keys)) {
			foreach($keys as $key) {
				if(isset($this->cache[$key])) {
					unset($this->cache[$key]);
				}
			}
		} else {
			$this->cache = Array();
		}
		
		$this->saveCache();
	}
};



	interface iSearchModel {
		public function runSearch($searchString, $searchTypesArray = NULL);
		public function getContext($elementId, $searchString);
		public function getIndexPages();
		public function getAllIndexablePages();
		public function getIndexWords();
		public function getIndexWordsUniq();
		public function getIndexLast();
		public function truncate_index();
		public function index_all($limit = false);
		public function index_item($elementId);

		public function index_items($elementId);
		public function unindex_items($elementId);
		
		public function suggestions($string, $limit = 10);
	};


/**
	* ����� ��� ������ � ��������� ����� �� �����.
*/
	class searchModel extends singleton implements iSingleton, iSearchModel {
		public function __construct() {
		}
		
		/**
			* �������� ��������� ������
			* @return searchModel ��������� ������
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* ���������������� ��� ��������, ��� ���� ��������� ����������� ������ ���� ��������� ����������
			* @param Integer $limit = false ���������� ���������� ������������� �������
			* @return Integer ���������� ������������������ �������
		*/
		public function index_all($limit = false) {
			$total = 0;

			$sql = "SELECT id, updatetime FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' ORDER BY id LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($element_id, $updatetime) = mysql_fetch_row($result)) {
				++$total;
				$sql = "SELECT id, updatetime FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' and id > '{$element_id}' ORDER BY id LIMIT 1";
				$result = l_mysql_query($sql, true);
			
				if(!$this->elementIsReindexed($element_id, $updatetime)) {
					$indexResult = $this->index_item($element_id, true);
					
					if($indexResult === false) {
						continue;
					}
					
					if(($limit !== false) && (--$limit == 0)) {
						break;
					}
				} else {
				}
			}
			return $total;
		}

		/**
			* ���������������� ������������ ��������
			* @param Integer $element_id id ��������
			* @param Boolean $is_manual = false ���������� ��������, ������ �� ������������
		*/
		public function index_item($element_id, $is_manual = false) {
			if(defined("UMICMS_CLI_MODE") || defined("DISABLE_SEARCH_REINDEX")) {
				return false;
			}
			
			l_mysql_query("START TRANSACTION /* Reindexing element #{$element_id} */", true);
			$index_data = $this->parseItem($element_id);
			l_mysql_query("COMMIT", true);

			return $index_data;
		}

		/**
			* ������, �������������� �� �������� $element_id ����� ���� $updatetime
			* @param Integer $element_id id ��������
			* @param Integer $updatetime ��������� ����� ����������
			* @return Boolean ��������� ��������
		*/
		public function elementIsReindexed($element_id, $updatetime) {
			$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}' AND indextime > '{$updatetime}'";
			$result = l_mysql_query($sql, true);
			list($c) = mysql_fetch_row($result);

			return (bool) $c;
		}

		public function parseItem($element_id) {
			if(!($element = umiHierarchy::getInstance()->getElement($element_id, true, true))) {
				return false;
			}

			if($element->getValue("is_unindexed")) {
				$domain_id = $element->getDomainId();
				$lang_id = $element->getLangId();
				$type_id = $element->getTypeId();

				$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}'";
				list($c) = mysql_fetch_row(l_mysql_query($sql, true));

		    		if(!$c) {
					$sql = "INSERT INTO cms3_search (rel_id, domain_id, lang_id, type_id) VALUES('{$element_id}', '{$domain_id}', '{$lang_id}', '{$type_id}')";
					l_mysql_query($sql, true);
				}
				return false;
			}

			$index_fields = Array();

			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();
			foreach($field_groups as $field_group_id => $field_group) {
				foreach($field_group->getFields() as $field_id => $field) {
					if($field->getIsInSearch() == false) continue;

					$field_name = $field->getName();

					$val = $element->getValue($field_name);

					$data_type = $field->getFieldType()->getDataType();
					if($data_type) {
						if(is_array($val)) {
							$valt = "";

							foreach($val as $obj_id) {
								$obj = umiObjectsCollection::getInstance()->getObject($obj_id);
									if(is_object($obj)) {
										$valt .= $obj->getName() . " ";
								} else if (is_string($obj_id)) {
									$valt .= $obj_id . " ";
								}

							}
							$val = $valt;
							unset($valt);
						} else {
							if(is_object($val)) {
								continue;
							}
							$obj = umiObjectsCollection::getInstance()->getObject($val);
								if(is_object($obj)) {
									$val = $obj->getName();
							}
						}
					}

					if(is_null($val) || !$val) continue;


					// kill macroses
					$val = preg_replace("/%([A-z_]*)%/m", "", $val);
					$val = preg_replace("/%([A-z�-�А-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*)%/m", "", $val);

					$index_fields[$field_name] = $val;
				}
			}

			$index_image = $this->buildIndexImage($index_fields);
			$this->updateSearchIndex($element_id, $index_image);
		}

		public function buildIndexImage($indexFields) {
			$img = Array();
			
			$weights = Array(
				'h1' => 5,
				'title' => 5,
				'meta_keywords' => 3,
				'meta_descriptions' => 3,
				'tags' => 3
			);

			foreach($indexFields as $fieldName => $str) {
				$arr = $this->splitString($str);
				
				if(isset($weights[$fieldName])) {
					$weight = (int) $weights[$fieldName];
				} else {
					$weight = 1;
				}

				foreach($arr as $word)  {
					if(array_key_exists($word, $img)) {
						$img[$word] += $weight;
					} else {
						$img[$word] = $weight;
					}
				}
			}
			return $img;
		}

		public static function splitString($str) {
			if(is_object($str)) {    //TODO: Temp
				return NULL;
			}

			$to_space = Array("&nbsp;", "&quote;", ".", ",", "?", ":", ";", "%", ")", "(", "/", 0x171, 0x187, "<", ">", "-");

			$str = str_replace(">", "> ", $str);
			$str = str_replace("\"", " ", $str);
			$str = strip_tags($str);
			$str = str_replace($to_space, " ", $str);
			$str = preg_replace("/([ \t\r\n]{1-100})/u", " ", $str);
			//$str = wa_strtolower($str);
			$tmp = explode(" ", $str); 

			$res = Array();
			foreach($tmp as $v) {
				$v = trim($v);

				if(wa_strlen($v) <= 2) continue;

				$res[] = $v;
			}

			return $res;
		}

		public function updateSearchIndex($element_id, $index_image) {
			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			$domain_id = $element->getDomainId();
			$lang_id = $element->getLangId();
			$type_id = $element->getTypeId();

			$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}'";
			list($c) = mysql_fetch_row(l_mysql_query($sql, true));

			if(!$c) {
				$sql = "INSERT INTO cms3_search (rel_id, domain_id, lang_id, type_id) VALUES('{$element_id}', '{$domain_id}', '{$lang_id}', '{$type_id}')";
				l_mysql_query($sql, true);
			}

			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);

			$sql = "INSERT INTO cms3_search_index (rel_id, weight, word_id, tf) VALUES ";
			$n = 0;
			
			$total_weight = array_sum($index_image);
			foreach($index_image as $word => $weight) {
				if(($word_id = $this->getWordId($word)) == false) continue;
				$TF = $weight / $total_weight;
				$sql .= "('{$element_id}', '{$weight}', '{$word_id}', '{$TF}'), ";
				++$n;
			}

			if($n) {
				$sql = substr($sql, 0, wa_strlen($sql) - 2);
				l_mysql_query($sql, true);
			}

			$time = time();

			$sql = "UPDATE cms3_search SET indextime = '{$time}' WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);
			
			umiHierarchy::getInstance()->unloadElement($element_id);

			return true;
		}

		/**
			* �������� id ����� $word � ��������� ����
			* @param String $word �����
			* @return Integer|Boolean id �����, ���� false
		*/
		public static function getWordId($word) {
			$word = str_replace("037", "", $word);
			$word = trim($word, "\r\n\t? ;.,!@#$%^&*()_+-=\\/:<>{}[]'\"`~|");
			$word = wa_strtolower($word);
			
			if(wa_strlen($word) < 3) {
				return false;
			}
			
			$word = mysql_real_escape_string($word);

			$sql = "SELECT id FROM cms3_search_index_words WHERE word = '{$word}'";
			$result = l_mysql_query($sql, true);

			if(list($word_id) = mysql_fetch_row($result)) {
				return $word_id;
			} else {
				$sql = "INSERT INTO cms3_search_index_words (word) VALUES('{$word}')";
				$result = l_mysql_query($sql, true);

				return (int) mysql_insert_id();
			}
		}

		/**
			* �������� ���������� ������������������ �������
			* @return Integer ���-�� ������������������ �������
		*/
		public function getIndexPages() {
			$sql = "SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}
		
		/**
			* �������� ����� ���������� �������, ������� ����� ����������������
			* @return Integer ���-�� �������, ������ � ����������
		*/
		public function getAllIndexablePages() {
			$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' ORDER BY id LIMIT 1";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* �������� ���������� ������������������ ����
			* @return Integer ���������� ����
		*/
		public function getIndexWords() {
			$sql = "SELECT SQL_SMALL_RESULT SUM(weight) FROM cms3_search_index";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* �������� ���������� ������������������ ���������� ����
			* @return Integer ���������� ���������� ����
		*/
		public function getIndexWordsUniq() {
			$sql = "SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search_index_words";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* �������� ���� ��������� ����������
			* @return Integer ���� ��������� ����������
		*/
		public function getIndexLast() {
			$sql = "SELECT SQL_SMALL_RESULT indextime FROM cms3_search ORDER BY indextime DESC LIMIT 1";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* �������� ��������� ������
		*/
		public function truncate_index () {
			$sql = "TRUNCATE TABLE cms3_search_index_words";
			l_mysql_query($sql, true);

			$sql = "TRUNCATE TABLE cms3_search_index";
			l_mysql_query($sql, true);

			$sql = "TRUNCATE TABLE cms3_search";
			l_mysql_query($sql, true);

			return true;
		}

		/**
			* ������ �� ���������� �������
			* @param String $str ��������� ������
			* @param Array $search_types = NULL ���� ������, �� ����� ������ ������ �������� � ����������� hierarchy-type-id
			* @param Array $hierarchy_rels = NULL ���� ������, �� ������ ������ � ������������ ������� �����
			* @param Boolean $orMode = false ���� true, �� ������ � ������ OR, ����� � ������ AND
			* @return Array ������, ���������� �� id �������� �������
		*/
		public function runSearch($str, $search_types = NULL, $hierarchy_rels = NULL, $orMode = false) {
			$words_temp = preg_split("/[ \-\_]/", $str);    //TODO
			$words = Array();

			foreach($words_temp as $word) {
				if(wa_strlen($word) >= 3) {
					$words[] = $word;
				}
			}

			$elements = $this->buildQueries($words, $search_types, $hierarchy_rels, $orMode);

			return $elements;
		}
		
		public function buildQueries($words, $search_types = NULL, $hierarchy_rels = NULL, $orMode = false) {
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

			$words_conds = Array();
			foreach($words as $i => $word) {
				if(wa_strlen($word) < 3) {
					unset($words[$i]);
					continue;
				}

				$word = mysql_real_escape_string($word);
				$word = str_replace(Array("%", "_"), Array("\\%", "\\_"), $word);
				$word_base = language_morph::get_word_base($word);
				
				$word_subcond = "siw.word LIKE '{$word}%' OR ";
		
				if(wa_strlen($word_base) >= 3) {
					$word_base = mysql_real_escape_string($word_base);
					$word_subcond .= "siw.word LIKE '{$word_base}%'";
				} else {
					$word_subcond = trim($word_subcond, " OR ");
				}
				
				$words_conds[] = "(" . $word_subcond . ")";
			}

			$words_cond = implode(" OR ", $words_conds);

			$users = cmsController::getInstance()->getModule("users");
			$user_id = $users->user_id;
			$user = umiObjectsCollection::getInstance()->getObject($user_id);
			$groups = $user->getValue("groups");
			$groups[] = $user_id;
			$groups[] = regedit::getInstance()->getVal("//modules/users/guest_id");
			$groups = array_extract_values($groups);

			$perms_sql = "";
			$sz = sizeof($groups);
			for($i = 0; $i < $sz; $i++) {
				if($i == 0) {
					$perms_sql .= " AND (";
				}

				$perms_sql .= "(c3p.owner_id = '{$groups[$i]}' AND c3p.rel_id = h.id AND level >= 1)";

				if($i == ($sz - 1)) {
					$perms_sql .= ")";
				} else {
					$perms_sql .= " OR ";
				}
			}
			$perms_table = ", cms3_permissions c3p";
			
			if(cmsController::getInstance()->getModule('users')->isSv()) {
				$perms_table = "";
				$perms_sql = "";
			}
			
			$types_sql = "";
			if(is_array($search_types)) {
				if(sizeof($search_types)) {
					if($search_types && $search_types[0]) {
						$types_sql = " AND s.type_id IN (" . implode(", ", $search_types) . ")";
					}
				}
			}

			$hierarchy_rels_sql = "";
			if (is_array($hierarchy_rels) && count($hierarchy_rels)) {
				$hierarchy_rels_sql = " AND h.rel IN (" . implode(", ", $hierarchy_rels) . ")";
			}
			
			if($words_cond == false) {
				return Array();
			}
			
			mysql_query("CREATE TEMPORARY TABLE temp_search (rel_id int unsigned, tf float, word varchar(64))");

			$sql = <<<SQL

INSERT INTO temp_search SELECT SQL_SMALL_RESULT HIGH_PRIORITY  s.rel_id, si.tf, siw.word

	FROM    cms3_search_index_words siw,
		cms3_search_index si,
		cms3_search s,
		cms3_hierarchy h
		{$perms_table}

			WHERE    ({$words_cond}) AND
				si.word_id = siw.id AND
				s.rel_id = si.rel_id AND
				s.domain_id = '{$domain_id}' AND
				s.lang_id = '{$lang_id}' AND
				h.id = s.rel_id AND
				h.is_deleted = '0' AND
				h.is_active = '1'
				{$types_sql}
				{$hierarchy_rels_sql}
				{$perms_sql}


SQL;

			$res = Array();

			l_mysql_query($sql);
			
			if($orMode) {
				$sql = <<<SQL
SELECT rel_id, (SUM(tf) / AVG(tf)) AS x 
	FROM temp_search 
		GROUP BY rel_id 
			ORDER BY x DESC
SQL;

			} else {
				$wordsCount = sizeof($words);
				
				$sql = <<<SQL
SELECT rel_id, (SUM(tf) / AVG(tf)) AS x, COUNT(word) AS wc 
	FROM temp_search 
		GROUP BY rel_id 
			HAVING wc >= '{$wordsCount}'
				ORDER BY x DESC
SQL;
			}
			$result = l_mysql_query($sql);
			
			while(list($element_id) = mysql_fetch_row($result)) {
				$res[] = $element_id;
			}

			mysql_query("DROP TEMPORARY TABLE IF EXISTS temp_search");

			return $res;
		}

		public function prepareContext($element_id, $uniqueOnly = false) {
			if(!($element = umiHierarchy::getInstance()->getElement($element_id))) {
				return false;
			}

			if($element->getValue("is_unindexed")) return false;

			$context = Array();

			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();
			foreach($field_groups as $field_group_id => $field_group) {
				foreach($field_group->getFields() as $field_id => $field) {
					if($field->getIsInSearch() == false) continue;

					$field_name = $field->getName();

					$val = $element->getValue($field_name);
					if(is_null($val) || !$val) continue;
					
					if(is_object($val)) {
						continue;
					}

					$context[] = $val;
				}
			}
			
			if($uniqueOnly) {
			    $context = array_unique($context);
			}
			
			$res = "";
			foreach($context as $val) {
				if(is_array($val)) {
					continue;
				}
				$res .= $val . " ";
			}
			
			$res = preg_replace("/%[A-z0-9_]+ [A-z0-9_]+\([^\)]+\)%/im", "", $res);
			
			
			$res = str_replace("%", "&#037", $res);
			return $res;
		}

		/**
			* �������� ��������, � ������� ����������� ��������� ����� �� ������� $element_id
			* @param Integer $element_id id ��������
			* @param String $search_string ��������� ������
			* @return String �������� ��������� ������
		*/
		public function getContext($element_id, $search_string) {
			$content = $this->prepareContext($element_id, true);

			$content = preg_replace("/%content redirect\((.*)\)%/im", "::CONTENT_REDIRECT::\\1::", $content);
			$content = preg_replace("/(%|&#037)[A-z0-9]+ [A-z0-9]+\((.*)\)(%|&#037)/im", "", $content);

			$bt = "<b>";
			$et = "</b>";


			$words_arr = split(" ", $search_string);


			$content = preg_replace("/([A-z�-�0-9])\.([A-z�-�0-9])/im", "\\1&#46;\\2", $content);

			$context = str_replace(">", "> ", $content);
			$context = str_replace("<br>", " ", $context);
			$context = str_replace("&nbsp;", " ", $context);
			$context = str_replace("\n", " ", $context);
			$context = strip_tags($context);


			if(preg_match_all("/::CONTENT_REDIRECT::(.*)::/i", $context, $temp)) {
				$sz = sizeof($temp[1]);

				for($i = 0; $i < $sz; $i++) {
					if(is_numeric($temp[1][$i])) {
						$turl = cmsController::getInstance()->getModule('content')->get_page_url($temp[1][$i]);
						$turl = umiHierarchy::getInstance()->getPathById($temp[1][$i]);
						$turl = trim($turl, "'");
						$res = str_replace($temp[0][$i], "<p>%search_redirect_text% \"<a href='$turl'>$turl</a>\"</p>", $context);
					} else {
						$turl = strip_tags($temp[1][$i]);
						$turl = trim($turl, "'");
						$context = str_replace($temp[0][$i], "<p>%search_redirect_text% <a href=\"" . $turl . "\">" . $turl . "</a></p>", $context);
					}
				}
			}

			$context .= "\n";


			$res_out = "";

			$lines = Array();
			foreach($words_arr as $cword) {
				if(wa_strlen($cword) <= 1)    continue;

				$tres = $context;
				$sword = language_morph::get_word_base($cword);

				$pattern_sentence = "/([^\.^\?^!^<^>.]*)$sword([^\.^\?^!^<^>.]*)[!\.\?\n]/imu";
				$pattern_word = "/([^ ^[\.[ ]*]^!^\?^\(^\).]*)($sword)([^ ^\.^!^\?^\(^\).]*)/imu";

				if (preg_match($pattern_sentence, $tres, $tres)) {
					$lines[] = $tres[0];
				}
			}

			$lines = array_unique($lines);

			$res_out = "";
			foreach($lines as $line) {
				foreach($words_arr as $cword) {
					$sword = language_morph::get_word_base($cword);
					$pattern_word = "/([^ ^.^!^\?.]*)($sword)([^ ^.^!^\?.]*)/imu";
					$line = preg_replace($pattern_word, $bt . "\\1\\2\\3" . $et, $line);
				}

				if($line) {
					$res_out .= "<p>" . $line . "</p>";
				}
			}

			if(!$res_out) {
				preg_match("/([^\.^!^\?.]*)([\.!\?]*)/im", $context, $res_out);
				$res_out = $res_out[0];
				$res_out = "<p></p>";
			}
			return $res_out;
		}
	
		/**
			* ������� ������ ��� �������� $element_id
			* @param Integer $element_id id ��������
		*/
		public function unindex_items($element_id) {
			$element_id = (int) $element_id;
			
			$sql = "DELETE FROM cms3_search WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);
			
			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);
			
			return true;
		}

		/**
			* ���������������� �������� $element_id � ���� �� �����
			* @param Integer $element_id id ��������
		*/
		public function index_items($element_id) {
			$hierarchy = umiHierarchy::getInstance();
			$childs = $hierarchy->getChilds($element_id, true, true, 99);
			$elements = array($element_id);
			$this->expandArray($childs, $elements);
	
			foreach($elements as $element_id) {
				$this->index_item($element_id);
			}		
		}

		/**
			* ��������� IDF ����� $wordId
			* @param Integer $wordId id ����� � ��������� ����
		*/
		public function calculateIDF($wordId) {
			static $IDF = false;
			
			if($IDF === false) {
				$sql = "SELECT COUNT(*) FROM cms3_search";
				$result = l_mysql_query($sql);
				list($d) = mysql_fetch_row($result);
				
				$sql = "SELECT COUNT(*) FROM cms3_search_index WHERE word_id = {$wordId}";
				$result = l_mysql_query($sql);
				list($dd) = mysql_fetch_row($result);
				
				$IDF = log($d / $dd);
			}
			return $IDF;
		}
		
		public function suggestions($string, $limit = 10) {
			$string = trim($string);
			if(!$string) return false;
			$string = wa_strtolower($string);
			
			$rus = str_split('��������������������������������');
			$eng = str_split('qwertyuiop[]asdfghjkl;\'zxcvbnm,.');
			
			$string_cp1251 = iconv("UTF-8", "CP1251", $string);
			$mirrowed_rus = iconv("CP1251", "UTF-8", str_replace($rus, $eng, $string_cp1251));
			$mirrowed_eng = iconv("CP1251", "UTF-8", str_replace($eng, $rus, $string_cp1251));
			
			$mirrowed = ($mirrowed_rus != $string) ? $mirrowed_rus : $mirrowed_eng;
			
			$string = mysql_real_escape_string($string);
			$mirrowed = mysql_real_escape_string($mirrowed);
			$limit = (int) $limit;
			
			$sql = <<<SQL
SELECT `siw`.`word` as `word`, COUNT(`si`.`word_id`) AS `cnt`
	FROM
		`cms3_search_index_words` `siw`,
		`cms3_search_index` `si`
	WHERE
		(
			`siw`.`word` LIKE '{$string}%' OR
			`siw`.`word` LIKE '{$mirrowed}%'
		) AND
		`si`.`word_id` = `siw`.`id`
	GROUP BY
		`siw`.`id`
	ORDER BY SUM(`si`.`tf`) DESC
	LIMIT {$limit}
SQL;
			
			$connection = ConnectionPool::getInstance()->getConnection('search');
			return $connection->queryResult($sql);
		}

		private function expandArray($arr, &$result) {
			if(is_null($result)) $result = array();
			
			foreach($arr as $id => $childs) {
				$result[] = $id;
				$this->expandArray($childs, $result);
			}
		}
	};


	interface iPermissionsCollection {

		public function getOwnerType($ownerId);
		public function makeSqlWhere($ownerId);

		public function isAllowedModule($ownerId, $module);
		public function isAllowedMethod($ownerId, $module, $method);
		public function isAllowedObject($ownerId, $objectId);
		public function isSv($userId = false);
		public function isAdmin($userId = false);
		public function isOwnerOfObject($objectId, $userId = false);

		public function resetElementPermissions($elementId, $ownerId = false);
		public function resetModulesPermissions($ownerId);
		
		public function setElementPermissions($ownerId, $elementId, $level);
		public function setModulesPermissions($ownerId, $module, $method = false);

		public function setDefaultPermissions($elementId);

		public function hasUserPermissions($ownerId);
		
		public function copyHierarchyPermissions($fromOwnerId, $toOwnerId);
		
		public function getUserId();
		
		public function setAllElementsDefaultPermissions($ownerId);
		
		public function getUsersByElementPermissions($elementId, $level = 1);
		
		public function pushElementPermissions($elementId, $level = 1);
		
		public function cleanupBasePermissions();
		
		public function isAuth();
	};


/**
	* Управляет правами доступа на страницы и ресурсы модулей.
	* Синглтон. Экземпляр класса можно получить через статичесик метод getInstance.
*/
	class permissionsCollection extends singleton implements iSingleton, iPermissionsCollection {
		protected $methodsPermissions = array(), $user_id = 0, $tempElementPermissions = array();

		// Some permissions constants
		const E_READ_ALLOWED   = 0;
		const E_EDIT_ALLOWED   = 1;
		const E_CREATE_ALLOWED = 2;
		const E_DELETE_ALLOWED = 3;
		const E_MOVE_ALLOWED   = 4;

		const E_READ_ALLOWED_BIT   = 1;
		const E_EDIT_ALLOWED_BIT   = 2;
		const E_CREATE_ALLOWED_BIT = 4;
		const E_DELETE_ALLOWED_BIT = 8;
		const E_MOVE_ALLOWED_BIT   = 16;

		/**
			* Конструктор
		*/
		public function __construct() {
			if(is_null(getRequest('guest-mode')) == false) {
				$this->user_id = self::getGuestId();
				return;
			}
			
			$users = cmsController::getInstance()->getModule("users");
			if($users instanceof def_module) {
				$this->user_id = $users->user_id;
			}
		}

		/**
			* Получить экземпляр коллекци
			* @return permissionsCollection экземпляр класса permissionsCollection
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Внутрисистемный метод, не является частью публичного API
			* @param Integer $owner_id id пользователя или группы
			* @return Integer|array
		*/
		public function getOwnerType($owner_id) {
			if($owner_object = umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($groups = $owner_object->getPropByName("groups")) {
					return $groups->getValue();
				} else {
					return $owner_id;
				}
			} else {
				return false;
			}
		}

		/**
			* Внутрисистемный метод, не является частью публичного API
			* @param Integer $owner_id id пользователя или группы
			* @return String фрагмент SQL-запроса
		*/
		public function makeSqlWhere($owner_id, $ignoreSelf = false) {
			static $cache = array();
			if(isset($cache[$owner_id])) return $cache[$owner_id];

			$owner = $this->getOwnerType($owner_id);

			if(is_numeric($owner)) {
				$owner = array();
			}
			
			if($owner_id) {
				$owner[] = $owner_id;
			}
			$owner[] = self::getGuestId();
			
			$owner = array_unique($owner);
			
			if(sizeof($owner) > 2) {
				foreach($owner as $i => $id) {
					if($id == $owner_id && $ignoreSelf) {
						unset($owner[$i]);
					}
				}
				$owner = array_unique($owner);
				sort($owner);
			}
			
			$sql = "";
			$sz = sizeof($owner);
			for($i = 0; $i < $sz; $i++) {
				$sql .= "cp.owner_id = '{$owner[$i]}'";
				if($i < ($sz - 1)) {
					$sql .= " OR ";
				}
			}
			$sql = "({$sql})";

			return $cache[$owner_id] = $sql;
		}


		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ к модулю $module
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param String $module название модуля
			* @return Boolean true если доступ разрешен
		*/
		public function isAllowedModule($owner_id, $module) {
			static $cache = array();
			
			if($owner_id == false) {
				$owner_id = $this->getUserId();
			}
			
			if($this->isSv($owner_id)) return true;
			if(isset($cache[$owner_id][$module])) return $cache[$owner_id][$module];

			$sql_where = $this->makeSqlWhere($owner_id);
			$module = mysql_real_escape_string($module);

			if(substr($module, 0, 7) == "macros_") return false;

			$sql = "SELECT module, MAX(cp.allow) FROM cms_permissions cp WHERE method IS NULL AND {$sql_where} GROUP BY module";
			$result = l_mysql_query($sql);
			while(list($module, $allow) = mysql_fetch_row($result)) {
				$cache[$owner_id][$module] = $allow;
			}

			return isset($cache[$owner_id][$module]) ? (bool) $cache[$owner_id][$module] : false;
		}

		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ к методу $method модуля $module
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param String $module название модуля
			* @param String $method название метода
			* @return Boolean true если доступ на метод разрешен
		*/
		public function isAllowedMethod($owner_id, $module, $method, $ignoreSelf = false) {
			if($module == "content" && !strlen($method)) return 1;
			if($module == "config" && $method == "menu") return 1;
			if($module == "eshop" && $method == "makeRealDivide") return 1;
			
			if($this->isAdmin($owner_id)) {
				if($this->isAdminAllowedMethod($module, $method)) {
					return 1;
				}
			}
			
			if($this->isSv($owner_id)) return true;
			if(!$module) return false;
			
			$method = $this->getBaseMethodName($module, $method);

			$methodsPermissions = &$this->methodsPermissions;
			if(!isset($methodsPermissions[$owner_id]) || !is_array($methodsPermissions[$owner_id])) {
				$methodsPermissions[$owner_id] = array();
			}
			$cache = &$methodsPermissions[$owner_id];

			$sql_where = $this->makeSqlWhere($owner_id, $ignoreSelf);

			if($module == "backup" && $method == "rollback") return true;
			if($module == "autoupdate" && $method == "service") return true;
			if($module == "config" && ($method == "lang_list" || $method == "lang_phrases")) return true;
			if($module == "users" && ($method == "auth" || $method == "login_do" || $method == "login")) return true;

			$cache_key = $module;
			if(!array_key_exists($cache_key, $cache)) {
				$cacheData = cacheFrontend::getInstance()->loadData('module_perms_' . $owner_id . '_' . $cache_key);
				if(is_array($cacheData)) {
					$cache[$module] = $cacheData;
				} else {
					$sql = "SELECT cp.method, MAX(cp.allow) FROM cms_permissions cp WHERE module = '{$module}' AND {$sql_where} GROUP BY module, method";
					$result = l_mysql_query($sql);
		
					$cache[$module] = array();
					while(list($cmethod) = mysql_fetch_row($result)) {
						$cache[$cache_key][] = $cmethod;
					}
					
					cacheFrontend::getInstance()->saveData('module_perms_' . $owner_id . '_' . $cache_key, $cache[$module], 3600);
				}
			}

			if (in_array($method, $cache[$cache_key]) || in_array(strtolower($method), $cache[$cache_key])) {
				return true;
			} else {
				return false;
			}
		}

		/**
			* Узнать, разрешен ли пользователю или группе $owner_id доступ на чтение страницы $object_id (класс umiHierarchyElement)
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $object_id id страницы, доступ к которой проверяется
			* @return Boolean true если есть доступ хотя бы на чтение
		*/
		public function isAllowedObject($owner_id, $object_id, $resetCache = false) {
			$object_id = (int) $object_id;
			if($object_id == 0) return array(false, false, false, false, false);

			if($this->isSv($owner_id)) {
				return array(true, true, true, true, true);
			}
			
			if(array_key_exists($object_id, $this->tempElementPermissions)) {
				$level = $this->tempElementPermissions[$object_id];
				return array((bool)($level&1), (bool)($level&2), (bool)($level&4), (bool)($level&8), (bool)($level&16) );
			} 

			static $cache;
			if(!is_array($cache)) {
				$cache = array();
			}


			$cache_key = $owner_id . "." . $object_id;

			if(isset($cache[$cache_key]) && is_array($cache[$cache_key])) {
				return $cache[$cache_key];
			}

			$sql_where = $this->makeSqlWhere($owner_id);

			$sql = "SELECT BIT_OR(cp.level) FROM cms3_permissions cp WHERE rel_id = '{$object_id}' AND {$sql_where}";
			$level = false;
			cacheFrontend::getInstance()->loadSql($sql);
			
			if(!$level || $resetCache) {
				$result = l_mysql_query($sql);
				list($level) = mysql_fetch_row($result);
				$level = array((bool)($level&1), (bool)($level&2), (bool)($level&4), (bool)($level&8), (bool)($level&16) );
				
			}
			
			if($level) {
				cacheFrontend::getInstance()->saveSql($sql, $level, 600);
			}

			$cache[$cache_key] = $level;
			return $level;
		}

		/**
			* Узнать, является ли пользователь $user_id супервайзером
			* @param Integer $user_id id пользователя (по умолчанию используется id текущего пользователя)
			* @return Boolean true, если пользователь является супервайзером
		*/
		public function isSv($user_id = false) {
			static $is_sv = array();
			
			if($user_id === false) {
				$user_id = $this->getUserId();
			}
			
			if(isset($is_sv[$user_id])) {
				return $is_sv[$user_id];
			}
			
			if(is_null(getRequest('guest-mode')) == false) {
				return $is_sv[$user_id] = false;
			}

			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				if($user_id == 15) {
					return $is_sv[$user_id] = true;
				}
			
				if($groups = $user->getPropByName("groups")) {
					if(in_array(15, $groups->getValue())) {
						return $is_sv[$user_id] = true;
					} else {
						return $is_sv[$user_id] = false;
					}
				} else {
					return $is_sv[$user_id] = false;
				}
			} else {
				return $is_sv[$user_id] = false;
			}
		}
		
		/**
			* Узнать, является ли пользователь $user_id администратором, т.е. есть ли у него доступ
			* к администрированию хотя бы одного модуля
			* @param Integer $user_id = false id пользователя (по умолчанию используется id текущего пользователя)
			* @return Boolean true, если пользователь является администратором
		*/
		public function isAdmin($user_id = false) {
			static $is_admin = array();
			if($user_id === false) $user_id = $this->getUserId();
			if(isset($is_admin[$user_id])) return $is_admin[$user_id];
			if($this->isSv($user_id)) return $is_admin[$user_id] = true;
			
			if(is_array(getSession('is_admin'))) {
				$is_admin = getSession('is_admin');
				if(isset($is_admin[$user_id])) return $is_admin[$user_id];
			}
			
			$sql_where = $this->makeSqlWhere($user_id);
			$sql = <<<SQL
SELECT COUNT(cp.allow) 
	FROM cms_permissions cp 
	WHERE method IS NULL AND {$sql_where} AND cp.allow IN (1, 2) GROUP BY module
SQL;
			$result = l_mysql_query($sql);
			
			list($cnt) = mysql_fetch_row($result);
			$is_admin[$user_id] = (bool) $cnt;
			$_SESSION['is_admin'] = $is_admin;
			return $is_admin[$user_id];
		}

		/**
			* Узнать, является ли пользователь $user_id владельцем объекта (класс umiObject) $object_id
			* @param Integer $object_id id объекта (класс umiObject)
			* @param $user_id id пользователя
			* @return Boolean true, если пользователь является владельцем
		*/
		public function isOwnerOfObject($object_id, $user_id = false) {
			if($user_id == false) {
				$user_id = $this->getUserId();
			}
			
			if($user_id == $object_id) {	//Objects == User, that's ok
				return true;
			} else {
				$object = umiObjectsCollection::getInstance()->getObject($object_id);
				if($object instanceof umiObject) {
					$owner_id = $object->getOwnerId();
				} else {
					$owner_id = 0;
				}

				if($owner_id == 0 || $owner_id == $user_id) {
					return true;
				} else {
					if($owner_id == 2373 && class_exists('customer')) {
						$customer = customer::get();
						if($cusotmer && ($customer->id == $owner_id)) {
							return true;
						}
					}
					return false;
				}
			}
		}

		/**
			* Сбросить настройки прав до дефолтных для страницы (класс umiHierarchyElement) $element_id
			* @param Integer $element_id id страницы (класс umiHierarchyElement)
			* @return Boolean false если произошла ошибка
		*/
		public function setDefaultPermissions($element_id) {
			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return false;
			}

			l_mysql_query("START TRANSACTION");


			$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql);


			$element = umiHierarchy::getInstance()->getElement($element_id, true, true);
			$hierarchy_type_id = $element->getTypeId();
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);

			$module = $hierarchy_type->getName();
			$method = $hierarchy_type->getExt();


			//Getting outgroup users
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");

			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$group_field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId("groups");
			$sel->setPropertyFilter();
			$sel->addPropertyFilterIsNull($group_field_id);

			$users = umiSelectionsParser::runSelection($sel);


			//Getting groups list
			$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "users");

			$sel = new umiSelection;

			$sel->setObjectTypeFilter();
			$sel->addObjectType($object_type_id);
			$groups = umiSelectionsParser::runSelection($sel);

			$objects = array_merge($users, $groups);


			//Let's get element's ownerId and his groups (if user)
			$owner_id = $element->getObject()->getOwnerId();
			if($owner = umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($owner_groups = $owner->getValue("groups")) {
					$owner_arr = $owner_groups;
				} else {
					$owner_arr = array($owner_id);
				}
			} else {
				$owner_arr = array();
			}


			foreach($objects as $ugid) {
				if($ugid == SV_GROUP_ID) continue;
				if($module == "content") $method == "page";
				
				if($this->isAllowedMethod($ugid, $module, $method)) {
					if(in_array($ugid, $owner_arr) || $ugid == SV_GROUP_ID || $this->isAllowedMethod($ugid, $module, $method . ".edit")) {
						$level = permissionsCollection::E_READ_ALLOWED_BIT +
								 permissionsCollection::E_EDIT_ALLOWED_BIT +
								 permissionsCollection::E_CREATE_ALLOWED_BIT +
								 permissionsCollection::E_DELETE_ALLOWED_BIT +
								 permissionsCollection::E_MOVE_ALLOWED_BIT;
					} else {
						$level = permissionsCollection::E_READ_ALLOWED_BIT;
					}

					$sql = "INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES('{$element_id}', '{$ugid}', '{$level}')";
					l_mysql_query($sql);
				}
			}

			l_mysql_query("COMMIT");
			l_mysql_query("SET AUTOCOMMIT=1");
			
			$this->cleanupElementPermissions($element_id);

			$cache_key = $this->user_id . "." . $element_id;
			cacheFrontend::getInstance()->saveSql($cache_key, array(true, true));
		}

		/**
		 * Копирует права с родительского элемента
		 * @param Integer $elementId идентификатор элемента, на который устанавливаем права
		 */
		public function setInheritedPermissions($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			if(!($element = $hierarchy->getElement($elementId))) return false;
			$parentId = $element->getParentId();
			// Retrieve permissions
			$records = $this->getRecordedPermissions($parentId);
			$values  = array();
			foreach($records as $ownerId => $level) {
				$values[] = "('{$elementId}', '{$ownerId}', '{$level}')";
			}
			if(empty($values)) return;
			// Store all records
			l_mysql_query("START TRANSACTION");
			$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";
			l_mysql_query($sql);
			$sql = "INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES ".implode(", ", $values);
			l_mysql_query($sql);
			l_mysql_query("COMMIT");
			l_mysql_query("SET AUTOCOMMIT=1");
			return true;
		}

		/**
			* Удалить все права на странциу $elementId для ползователя или группы $ownerId
			* @param Integer $elementId id страницы (класс umiHierarchyElement)
			* @param Integer $ownerId=false id пользователя или группы, чьи права сбрасываются. Если false, то права сбрасываются для всех пользователей
		*/
		public function resetElementPermissions($elementId, $ownerId = false) {
			$elementId = (int) $elementId;


			if($ownerId === false) {
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";
			} else {
				$ownerId = (int) $ownerId;
				$sql = "DELETE FROM cms3_permissions WHERE owner_id = '{$ownerId}' AND rel_id = '{$elementId}'";
			}

			l_mysql_query($sql);
			return true;
		}

		/**
			* Сбросить все права на модули и методы для пользователя или группы $ownerId
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param array $modules=NULL массив, который указывает модули, для которых сбросить права. По умолчанию, сбрасываются права на все модули
		*/
		public function resetModulesPermissions($ownerId, $modules = NULL) {
			$ownerId = (int) $ownerId;

			$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}'";
			
			if(is_array($modules)) {
				if(sizeof($modules)) {
					$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}' AND module IN ('" . implode("', '", $modules) . "')";
				}
			}
			
			l_mysql_query($sql);
			
			$cacheFrontend = cacheFrontend::getInstance();
			foreach($modules as $module) {
				$cacheFrontend->deleteKey('module_perms_' . $ownerId . '_' . $module, true);
			}
			
			return true;
		}

		/**
			* Установить определенные права на страница $elementId для пользователя или группы $ownerId
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param Integer $elementId id страницы (класс umiHierarchyElement), для которой меняются права
			* @param Integer $level уровень выставляемых прав то "0" до "2". "нет доступа" (0), "только чтение" (1), "чтение и запись" (2)
			* @return Boolean true если не произошло ошибки
		*/
		public function setElementPermissions($ownerId, $elementId, $level) {
			$ownerId = (int) $ownerId;
			$elementId = (int) $elementId;
			$level = (int) $level;
			
			if($elementId == 0 || $ownerId == 0) {
				return false;
			}

			$sql_reset = "DELETE FROM cms3_permissions WHERE owner_id = '".$ownerId."' AND rel_id = '".$elementId."'";
			l_mysql_query($sql_reset);

			$sql = "INSERT INTO cms3_permissions (owner_id, rel_id, level) VALUES('{$ownerId}', '{$elementId}', '{$level}')";
			l_mysql_query($sql);

			$this->cleanupElementPermissions($elementId);
			
			$this->isAllowedObject($ownerId, $elementId, true);
			
			return true;
		}


		/**
			* Разрешить пользователю или группе $owner_id права на $module/$method
			* @param Integer $ownerId id пользователя или группы пользователей
			* @param String $module название модуля
			* @param String $method=false название метода
		*/
		public function setModulesPermissions($ownerId, $module, $method = false, $cleanupPermissions = true) {
			$ownerId = (int) $ownerId;
			$module = mysql_real_escape_string($module);
			
			if($method !== false) {
				return $this->setMethodPermissions($ownerId, $module, $method);
			} else {
				$sql = "INSERT INTO cms_permissions (owner_id, module, method, allow) VALUES('{$ownerId}', '{$module}', NULL, '1')";
				l_mysql_query($sql);

				if($cleanupPermissions) $this->cleanupBasePermissions();
				return true;
			}
		}

		protected function setMethodPermissions($ownerId, $module, $method, $cleanupPermissions = true) {
			$method = mysql_real_escape_string($method);

			$sql = "INSERT INTO cms_permissions (owner_id, module, method, allow) VALUES('{$ownerId}', '{$module}', '{$method}', '1')";
			l_mysql_query($sql);
			
			if($cleanupPermissions) $this->cleanupBasePermissions();
			return true;
		}

		/**
			* Узнать, имеет ли пользователь или группа в принципе права на какие-нибудь страницы
			* @param Integer $ownerId id пользователя или группы
			* @return Boolean false, если записей нет
		*/
		public function hasUserPermissions($ownerId) {
			$sql = "SELECT COUNT(*) FROM cms3_permissions WHERE owner_id = '{$ownerId}'";
			$result = l_mysql_query($sql);
			
			list($cnt) = mysql_fetch_row($result);
			return $cnt;
		}
		
		/**
			* Скопировать права на все страницы из $fromUserId в $toUserId
			* @param Integer $fromUserId id пользователя или группы пользователей, из которых копируются права
			* @param Integer $fromUserId id пользователя или группы пользователей, в которые копируются права
		*/
		public function copyHierarchyPermissions($fromUserId, $toUserId) {
			if($fromUserId == self::getGuestId()) {
				return false;		//No need in cloning guest permissions now
			}
		
			$fromUserId = (int) $fromUserId;
			$toUserId = (int) $toUserId;
		
			$sql = "INSERT INTO cms3_permissions (level, rel_id, owner_id) SELECT level, rel_id, '{$toUserId}' FROM cms3_permissions WHERE owner_id = '{$fromUserId}'";
			l_mysql_query($sql);
			
			return true;
		}
		
		/**
			* Системный метод. Получить массив прав из permissions.php и permissions.custom.php
			* @return array
		*/
		public function getStaticPermissions($module) {
			static $cache = array();
			
			if(isset($cache[$module])) {
				return $cache[$module];
			}
			
			$static_file = CURRENT_WORKING_DIR . "/classes/modules/" . $module . "/permissions.php";
			if(file_exists($static_file)) {
				require $static_file;
				if(isset($permissions)) {
					$static_permissions = $permissions;
				
					$static_file_custom = CURRENT_WORKING_DIR . "/classes/modules/" . $module . "/permissions.custom.php";
					if(file_exists($static_file_custom)) {
						unset($permissions);
						require $static_file_custom;
						if(isset($permissions)) {
							$static_permissions = array_merge_recursive($static_permissions, $permissions);
						}
					}

					$cache[$module] = $static_permissions;
					unset($static_permissions);
					unset($permissions);
				} else {
					$cache[$module] = false;
				}
			} else {
				$cache[$module] = false;
			}
			return $cache[$module];
		}
		
		/**
			* Получить название корневого метода в системе приритета прав для $module::$method
			* @param String $module название модуля
			* @param String $method название метода
			* @return String название корневого метода
		*/
		protected function getBaseMethodName($module, $method) {
			$methods = $this->getStaticPermissions($module);
			
			if($method && is_array($methods)) {
				if(array_key_exists($method, $methods)) {
					return $method;
				} else {
					foreach($methods as $base_method => $sub_methods) {
						if(is_array($sub_methods)) {
							if(in_array($method, $sub_methods) || in_array(strtolower($method), $sub_methods)) {
								return $base_method;
							}
						}
					}
					return $method;
				}
			} else {
				return $method;
			}
		}
		
		/**
			* Получить id текущего пользователя
			* @return Integer id текущего пользователя
		*/
		public function getUserId() {
			return $this->user_id;
		}
		
		
		/**
			* Удалить все записи о правах на модули и методы для пользователей, если они ниже, чем у гостя
		*/
		public function cleanupBasePermissions() {
			$guestId = self::getGuestId();
			
			$sql    = "SELECT module, method FROM cms_permissions WHERE owner_id = '{$guestId}' AND allow = 1";
			$result = mysql_query($sql);
			
			$sql = array();
			while(list($module, $method) = mysql_fetch_row($result)) {
				if($method) {
					$sql[] = "(module = '{$module}' AND method = '{$method}')";
				} else {
					$sql[] = "(module = '{$module}' AND method IS NULL)";
				}				
			}
			if(!empty($sql)) 
				mysql_query("DELETE FROM cms_permissions WHERE owner_id != '{$guestId}' AND (" . implode(' OR ', $sql) . ")");
		}
		
		/**
			* Удалить для страницы  с id $rel_id записи о правах пользователей, которые ниже, чем у гостя
			* @param Integer $rel_id id страница (класс umiHierarchyElement)
		*/
		protected function cleanupElementPermissions($rel_id) {
			$rel_id = (int) $rel_id;
			$guestId = self::getGuestId();
			
			$sql = "SELECT level FROM cms3_permissions WHERE owner_id = '{$guestId}' AND rel_id = {$rel_id}";
			$result = l_mysql_query($sql);
			$maxLevel = 0;
			while(list($level) = mysql_fetch_row($result)) {
				if($level>$maxLevel) $maxLevel = $level;	
			}
			mysql_query("DELETE FROM cms3_permissions WHERE owner_id != '{$guestId}' AND level <= {$maxLevel} AND rel_id = {$rel_id}");			
		}
		
		/**
			* Узнать, разрешено ли пользователю или группе $owner_id администрировать домен $domain_id
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $domain_id id домена (класс domain)
			* @return Integer 1, если доступ разрешен, 0 если нет
		*/
		public function isAllowedDomain($owner_id, $domain_id) {
			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;
			
			if($this->isSv($owner_id)) {
				return 1;
			}
			
			$sql_where_owners = $this->makeSqlWhere($owner_id);
		
			$sql = "SELECT MAX(cp.allow) FROM cms_permissions cp WHERE cp.module = 'domain' AND cp.method = '{$domain_id}' AND " . $sql_where_owners;
			$result = l_mysql_query($sql);

			if($row = mysql_fetch_row($result)) {
				list($level) = $row;
				return (int) $level;
			} else return 0;
		}
		
		/**
			* Установить права пользователю или группе $owner_id на администрирование домена $domain_id
			* @param Integer $owner_id id пользователя или группы пользователей
			* @param Integer $domain_id id домена (класс domain)
			* @param Boolean $allow=true если true, то доступ разрешен
		*/
		public function setAllowedDomain($owner_id, $domain_id, $allow = 1) {
			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;
			$allow = (int) $allow;
		
			$sql = "DELETE FROM cms_permissions WHERE module = 'domain' AND method = '{$domain_id}' AND owner_id = '{$owner_id}'";
			$result = l_mysql_query($sql);
			
			$sql = "INSERT INTO cms_permissions (module, method, owner_id, allow) VALUES('domain', '{$domain_id}', '{$owner_id}', '{$allow}')";
			$result = l_mysql_query($sql);
			
			return true;
		}
		
		/**
			* Установить права по умолчанию для страницы $element по отношению к пользователю $owner_id
			* @param umiHierarchyElement $element экземпляр страницы
			* @param Integer $owner_id id пользователя или группы пользователей
			* @return Integer уровен доступа к странице, который был выбран системой
		*/
		public function setDefaultElementPermissions(iUmiHierarchyElement $element, $owner_id) {
			$module = $element->getModule();
			$method = $element->getMethod();

			$level = 0;
			if($this->isAllowedMethod($owner_id, $module, $method, true)) {
				$level = 1;
			}
			
			if($this->isAllowedMethod($owner_id, $module, $method . ".edit", true)) {
				$level = 2;
			}
			
			$this->setElementPermissions($owner_id, $element->getId(), $level);

			return $level;
		}
		
		/**
			* Сбросить для пользователя или группы $owner_id права на все страницы на дефолтные
			* @param Integer $owner_id id пользователя или группы пользователей
		*/
		public function setAllElementsDefaultPermissions($owner_id) {
			$owner_id = (int) $owner_id;
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			
			$owner = $this->getOwnerType($owner_id);
			if(is_numeric($owner)) {
				$owner = array();
			}
			
			$owner[] = self::getGuestId();
			$owner = array_unique($owner);
			
			l_mysql_query("START TRANSACTION");
			
			$read = array();
			$write = array();
			
			foreach($hierarchyTypes->getTypesList() as $hierarchyType) {
				$module = $hierarchyType->getName();
				$method = $hierarchyType->getExt();
				
				if($this->isAllowedMethod($owner_id, $module, $method . ".edit", true)) {
					foreach($owner as $gid) {
						if($this->isAllowedMethod($gid, $module, $method . ".edit", true)) {
							continue 2;
						}
					}
					$write[] = $hierarchyType->getId();
					$level = 2;
				} else if($this->isAllowedMethod($owner_id, $module, $method, true)) {
					foreach($owner as $gid) {
						if($this->isAllowedMethod($gid, $module, $method, true)) {
							continue 2;
						}
					}
					
					$read[] = $hierarchyType->getId();
					$level = 1;
				} else {
					$level = 0;
				}
			}
			
			if(sizeof($read)) {
				$types = implode(", ", $read);
				
				$sql = <<<SQL
INSERT INTO cms3_permissions (level, owner_id, rel_id) 
	SELECT 1, '{$owner_id}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
				l_mysql_query($sql);
			}
			
			if(sizeof($write)) {
				$types = implode(", ", $write);
				
				$sql = <<<SQL
INSERT INTO cms3_permissions (level, owner_id, rel_id) 
	SELECT 31, '{$owner_id}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
				l_mysql_query($sql);
			}
			
			l_mysql_query("COMMIT");
		}
		
		/**
			* Получить список всех пользователей или групп, имеющих права на страницу $elementId
			* @param Integer $elementId id страницы
			* @param Integer $level = 1 искомый уровень прав
			* @return array массив id пользователей или групп, имеющих права на страницу
		*/
		public function getUsersByElementPermissions($elementId, $level = 1) {
			$elementId = (int) $elementId;
			$level = (int) $level;
			
			$sql = "SELECT owner_id FROM cms3_permissions WHERE rel_id = '{$elementId}' AND level >= '{$level}'";
			$result = l_mysql_query($sql);
			
			$owners = array();
			while(list($ownerId) = mysql_fetch_row($result)) {
				$owners[] = (int) $ownerId;
			}
			
			return $owners;
		}

		/**
		 * Получить список сохраненных прав для страницы $elementId
		 * @param Integer $elementId
		 * @return array $ownerId => $permissionsLevel
		 */

		public function getRecordedPermissions($elementId) {
			$elementId = (int) $elementId;

			$sql = "SELECT owner_id, level FROM cms3_permissions WHERE rel_id = '{$elementId}'";

			$result = l_mysql_query($sql);

			$records = array();
			while(list($ownerId, $level) = mysql_fetch_row($result)) {
				$records[$ownerId] = (int) $level;
			}

			return $records;
		}
		
		/**
			* Указать права на страницу. Влияет только на текущую сессию, данные в базе изменены не будут
			* @param Integer $elementId id страницы
			* @param Integer $level = 1 уровень прав доступа (0-3).
		*/
		public function pushElementPermissions($elementId, $level = 1) {
			//if(false && array_key_exists($elementId, $this->tempElementPermissions) == false) {
				$this->tempElementPermissions[$elementId] = (int) $level;
			//}
		}
		
		/**
			* Узнать, авторизован ли текущий пользователь
			* @return Boolean true, если авторизован
		*/
		public function isAuth() {
			return ($this->getUserId() != self::getGuestId());
		}
		
		/**
			* Позволяет узнать id пользователя "Гостя"
			* @return Integer $guestId id пользователя "Гость"
		*/
		public static function getGuestId() {
			static $guestId;
			if(!$guestId) {
				$guestId = (int) regedit::getInstance()->getVal("//modules/users/guest_id");
			}
			return $guestId;
		}
		
		/**
			* Авторизовать клиента как пользователя $userId
			* @param Int|umiObject id пользователя, либо объект пользователя
			* @return Boolean успешность операции
		*/
		public function loginAsUser($userId) {
			if(is_null($userId)) return false;
			if(is_array($userId) && sizeof($userId)) {
				list($userId) = $userId;
			}
			
			if($userId instanceof iUmiObject) {
				$user = $userId;
				$userId = $user->id;
			} else $user = selector::get('object')->id($userId);
			$this->user_id = $userId;
			
			$login = $user->login;
			$passwordHash = $user->password;
			
			if(getRequest('u-login-store')) {
				$time = time() + 31536000;
				setcookie("u-login", $user->login, $time, "/");
				setcookie("u-password-md5", $passwordHash, $time, "/");
			}
			
			$_SESSION['cms_login'] = $login;
			$_SESSION['cms_pass'] = $passwordHash;
			$_SESSION['user_id'] = $userId;
			
			return true;
		}
		
		/**
			* Проверить параметры авторизации
			* @param String login логин
			* @param String password пароль
			* @return NULL|umiObject null, либо пользователь
		*/
		public function checkLogin($login, $password) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('login')->equals($login);
			$sel->where('password')->equals(md5($password));
			$sel->where('is_activated')->equals(true);
			return $sel->first;
		}
		
		public function getPrivileged($perms) {
			if(!sizeof($perms)) return array();
			
			$sql = 'SELECT owner_id FROM cms_permissions WHERE ';
			$sqls = array();
			foreach($perms as $perm) {
				$module = mysql_real_escape_string(getArrayKey($perm, 0));
				$method = mysql_real_escape_string($this->getBaseMethodName($module, getArrayKey($perm, 1)));
				$sqls[] = "(module = '{$module}' AND method = '{$method}')";
			}
			$sql .= implode(' OR ', $sqls);
			
			$result = l_mysql_query($sql);
			
			$owners = array();
			while(list($ownerId) = mysql_fetch_row($result)) {
				$owners[] = $ownerId;
			}
			return $owners;
		}
		
		protected function isAdminAllowedMethod($module, $method) {
			$methods = array(
			'content' =>    array('json_mini_browser', 'old_json_load_files', 'json_load_files', 
							'json_load_zip_folder', 'load_tree_node', 'get_editable_region',
							'save_editable_region', 'widget_create', 'widget_delete',
							'getObjectsByTypeList', 'getObjectsByBaseTypeList', 
							'json_get_images_panel', 'json_create_imanager_object',
							'domainTemplates', 'json_unlock_page', 'tree_unlock_page'),
			'backup' =>     array('backup_panel'),
			'data'   =>     array('guide_items', 'guide_items_all', 'json_load_hierarchy_level'),
			'webo' => array('show'),
			'users'  => array('getFavourites', 'json_change_dock', 'saveUserSettings', 'loadUserSettings'),
			'*'		 =>		array('dataset_config')
			);

			if(isset($methods[$module])) {
				if(in_array($method, $methods[$module])) {
					return true;
				}
			}
			if(isset($methods['*'])) {
				if(in_array($method, $methods['*'])) {
					return true;
				}
			}
			return false;
		}
	};


	interface iTranslit {
		public static function convert($string);
	}


/**
	* Работа с транслитом
*/
	class translit implements iTranslit {
		public static	$fromUpper = Array("Э/g", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "Я", "Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ъ", "Ы", "Ь");
		public static	$fromLower = Array("э", "ч", "ш", "ё", "ё", "ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь");
		public static	$toLower   = Array("e\'", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s",  "t", "u", "f", "h", "c", "w", "~", "y", "\'");
		
		/**
			* Конвертировать строку в транслит
			* @param String $str входная строка
			* @return String транслитерированная строка
		*/
		public static function convert($str) {

			$str = umiObjectProperty::filterInputString($str);

			$str = str_replace(self::$fromLower, self::$toLower, $str);
			$str = str_replace(self::$fromUpper, self::$toLower, $str);
			$str = strtolower($str);

			$str = preg_replace("/([^A-z^0-9^_^\-]+)/", "_", $str);
			$str = preg_replace("/[\/\\\',\t`\^\[\]]*/", "", $str);
			$str = str_replace(chr(8470), "", $str);
			$str = preg_replace("/[ \.]+/", "_", $str);

			$str = preg_replace("/([_]+)/", "_", $str);
			$str = trim(trim($str), "_");

			return $str;
		}
	}


	interface iTemplater {
		public function init($input);

		public function loadLangs();
		public function putLangs($input);

		public function parseInput($input);

		public function parseMacros($macrosStr);
		public function executeMacros($macrosArr);

		public static function pushEditable($module, $method, $id);
		
		public function cleanUpResult($input);
	}



class templater extends singleton implements iTemplater {
	public $defaultMacroses = Array(Array("%content%", "macros_content"),
		Array("%menu%", "macros_menu"),
		Array("%header%", "macros_header"),
		Array("%pid%", "macros_returnPid"),
		Array("%parent_id%", "macros_returnParentId"),
		Array("%pre_lang%", "macros_returnPreLang"),
		Array("%curr_time%", "macros_curr_time"),
		Array("%domain%", "macros_returnDomain"),
		Array("%domain_floated%", "macros_returnDomainFloated"),
	
		Array("%title%", "macros_title"),
		Array("%keywords%", "macros_keywords"),
		Array("%describtion%", "macros_describtion"),
		Array("%description%", "macros_describtion"),
		Array("%adm_menu%", "macros_adm_menu"),
		Array("%adm_navibar%", "macros_adm_navibar"),
		Array("%skin_path%", "macros_skin_path"),
		Array("%ico_ext%", "macros_ico_ext"),
	
		Array("%current_user_id%", "macros_current_user_id"),
		Array("%current_version_line%", "macros_current_version_line"),
		Array("%context_help%", "macros_help"),
		Array("%current_alt_name%", "macros_current_alt_name"));
	public $cacheMacroses = Array();
    public $processingCache = array(); // For macrosess in process (infinite recursion preventing)
	public $cachePermitted = false;
	public $LANGS = Array();
	public $cacheEnabled = 0;

	protected function __construct() {
	}

	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}



	public function init($input) {
		$this->loadLangs();
		

		$this->cacheMacroses["%content%"] = $this->parseInput(cmsController::getInstance()->parsedContent);

		$res = $this->parseInput($input);
		$res = $this->putLangs($res);

		$this->output = system_parse_short_calls($res);
	}

	public function loadLangs() {
		$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang." . cmsController::getInstance()->getLang()->getPrefix() . ".php";
		if(!file_exists($try_path)) {
			$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
		}

		include_once $try_path;

		if(isset($LANG_EXPORT)) {
			$cmsControllerInstance = cmsController::getInstance();
			$cmsControllerInstance->langs = array_merge($cmsControllerInstance->langs, $LANG_EXPORT);
			unset($LANG_EXPORT);
		}
		return true;
	}

	public function putLangs($input) {
		$res = $input;
		
		if(($p = strpos($res, "%")) === false) return $res;

		$langs = cmsController::getInstance()->langs;

		foreach($langs as $cv => $cvv) {
			if(is_array($cvv)) continue;
			
			$m = "%" . $cv . "%";
			
			if(($mp = strpos($res, $m, $p)) !== false) {
				$res = str_replace($m, $cvv, $res, $mp);
			}
		}

		return $res;
	}

	public function parseInput($input) {
		$res = $input;

		if(is_array($res)) {
			return $res;
		}

		$pid = cmsController::getInstance()->getCurrentElementId();
		$input = str_replace("%pid%", $pid, $input);
		
		if(strrpos($res, "%") === false) {
			return $res;
		}

		$input = str_replace("%%", "%\r\n%", $input);

		if(preg_match_all("/%([A-z_]{3,})%/m", $input, $temp)) {
			$temp = $temp[0];

			$sz = sizeof($temp);
			
			
			for($i = 0; $i < $sz; $i++) {
				try {
					$r = $this->parseMacros($temp[$i]);
				} catch (publicException $e) {
				}
			}
		}

		if(preg_match_all("/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu", $input, $temp)) {
			$temp = $temp[0];

			$sz = sizeof($temp);
			
			for($i = 0; $i < $sz; $i++) {
				try {
					$r = $this->parseMacros($temp[$i]);
				} catch (publicException $e) {
				}
			}
		}
        if(is_array($res)) implode('',$res);
		$cache = $this->cacheMacroses;
		$cache = array_reverse($cache);
		foreach($cache as $ms => $mr) {
			if(($p = strpos($res, $ms)) !== false) {                
				$res = str_replace($ms, $mr, $res);
			}
		}

		$res = $this->cleanUpResult( $this->putLangs($res) );
		
		if($pid) {
			$res = system_parse_short_calls($res, $pid);
		}
		
		return $res;
	}

	public function parseMacros($macrosStr) {	//????????? ????????? ?????? ?? ????????????: ???????? ??????, ??????        
		$macrosArr = Array();			// ? ?????? ??????????
//echo $macrosStr, "\n";
		if(strrpos($macrosStr, "%") === false)
			return $macrosArr;
            
        // Set up processing cache
        if(isset($this->processingCache[$macrosStr])) return $macrosStr;
        $this->processingCache[$macrosStr] = true;
        //--------------------------------------
			
		$preg_pattern = "/%([A-z0-9]+)\s+([A-z0-9]+)\((.*)\)%/m";
		if(defined("TPL_MODE")) {
			if(TPL_MODE == "SIMPLE") {
				$preg_pattern = "/%([A-z0-9]+)\s+([A-z0-9]+)\((.*)\)%/Um";
			}
		}
		

		if(preg_match($preg_pattern, $macrosStr, $pregArr)) {
			$macrosArr['str']    = $pregArr[0];
			$macrosArr['module'] = $pregArr[1];
			$macrosArr['method'] = $pregArr[2];
			$macrosArr['args']   = $pregArr[3];

			if(array_key_exists($macrosArr['str'], $this->cacheMacroses)) {
                unset($this->processingCache[$macrosStr]);
				return $this->cacheMacroses[$macrosArr['str']];
            }

			//????????? ?????? ?????????? ?? ??????
			$params = split(",", $macrosArr['args']);

			$sz = sizeof($params);
			for($i = 0; $i < $sz; $i++) {
				$cparam = $params[$i];

				if(strpos($cparam, "%") !== false) {
					$cparam = $this->parseInput($cparam);
				}
				$params[$i] = trim($cparam, "'\" ");
			}
			$macrosArr['args'] = $params;

			$res = $macrosArr['result'] = $this->executeMacros($macrosArr);
			$this->cacheMacroses[$macrosArr['str']] = $macrosArr['result'];	//? ???
            unset($this->processingCache[$macrosStr]);
			return $res;

		} else {

			//????????. ????? ????, ??? ?????-?? ?????????? ??????...
			$defMs = $this->defaultMacroses;

			$sz = sizeof($defMs);
			for($i = 0; $i < $sz; $i++)
				if(stripos($macrosStr, $defMs[$i][0]) !== false) {
						if(array_key_exists($defMs[$i][0], $this->cacheMacroses)) {
                            unset($this->processingCache[$macrosStr]);
							return $this->cacheMacroses[$defMs[$i][0]];
                        }
							
						if(!isset($defMs[$i][2])) {
							$defMs[$i][2] = NULL;
						}

						$res = $this->executeMacros(
										Array(
											"module" => $defMs[$i][1],
											"method" => $defMs[$i][2],
											"args"   => Array()
											)
									);
						$res = $this->parseInput($res);
						$this->cacheMacroses[$defMs[$i][0]] = $res;	//? ???
                        unset($this->processingCache[$macrosStr]);
						return $res;
					}

			$this->cacheMacroses[$macrosStr] = $macrosStr;
            unset($this->processingCache[$macrosStr]);
			return $macrosStr;
		}
	}

	public function executeMacros($macrosArr) {
		$module = $macrosArr['module'];
		$method = $macrosArr['method'];

		if($module == "current_module")
			$module = cmsController::getInstance()->getCurrentModule();
		$res = "";

		if(!$method) {
			$cArgs = $macrosArr['args'];
			$res = call_user_func_array($macrosArr['module'], $cArgs);
		}

		if($module == "core" || $module == "system" || $module == "custom") {
			$pk = &system_buildin_load($module);

			if($pk) {
				$res = $pk->cms_callMethod($method, $macrosArr['args']);
			}
		}

		if($module != "core" && $module != "system") {
			if(system_is_allowed($module, $method)) {
				if($module_inst = cmsController::getInstance()->getModule($module)) {
					$res = $module_inst->cms_callMethod($method, $macrosArr['args']);
				}
			}
		}

        if(is_array($res)) {
        	$tmp = "";
        	foreach($res as $s) {
        		if(!is_array($s)) {
        			$tmp .= $s;
        		}
        	}
        	$res = $tmp;
        }
		if(strpos($res, "%") !== false) {
			$res = $this->parseInput($res);
		}

		return $res;
	}


	public function __destruct() {
	}

	public static $blocks = Array();

	public static function pushEditable($module, $method, $id) {
		if($module === false && $method === false) {

			if($element = umiHierarchy::getInstance()->getElement($id)) {
				$elementTypeId = $element->getTypeId();

				if($elementType = umiObjectTypesCollection::getInstance()->getType($elementTypeId)) {
					$elementHierarchyTypeId = $elementType->getHierarchyTypeId();

					if($elementHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($elementHierarchyTypeId)) {
						$module = $elementHierarchyType->getName();
						$method = $elementHierarchyType->getExt();
					} else {
						return false;
					}
				}
			}
		}

		templater::$blocks[] = array($module, $method, $id);
	}

	public static function prepareQuickEdit() {
		$toFlush = templater::$blocks;
		
		if(sizeof($toFlush) == 0) return;
		
		$key = md5("http://" . getServer('HTTP_HOST') . getServer('REQUEST_URI'));
		$_SESSION[$key] = $toFlush;
	}


	final public static function getSomething($version_line = "pro") {
		$default_domain = domainsCollection::getInstance()->getDefaultDomain();
		$serverAddr = getServer('SERVER_ADDR');

		if($serverAddr) {
			$cs2 = md5($serverAddr);
		} else {
			$cs2 = md5(str_replace("\\", "", getServer('DOCUMENT_ROOT')));
		}
		
		switch($version_line) {
			case "pro":
				$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($default_domain->getHost()))))))))));
				break;

			case "free":
				$cs3 = md5(md5(md5($default_domain->getHost())));
				break;

			case "lite":
				$cs3 = md5(md5(md5(md5(md5($default_domain->getHost())))));
				break;

			case "freelance":
				$cs3 = md5(md5(md5(md5(md5(md5(md5($default_domain->getHost())))))));
				break;
				
			case "trial": {
				$cs3 = md5(md5(md5(md5(md5(md5($default_domain->getHost()))))));
			}
		}

		$licenseKeyCode = strtoupper(substr($cs2, 0, 11) . "-" . substr($cs3, 0, 11));
		return $licenseKeyCode;
	}
	
	
	public function cleanUpResult($input) {
		return $input;
		
		$input = str_replace("%pid%", cmsController::getInstance()->getCurrentElementId(), $input);
		
	    if(!regedit::getInstance()->getVal("//settings/show_macros_onerror")) {
    		$input = preg_replace("/%([A-z?-?А-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*)%/m", "", $input);
	    }

		return $input;
	}
};


	interface iCmsController {

		public function loadBuildInModule($moduleName);

//		public function loadModule($moduleName);

		public function getModule($moduleName);

		public function installModule($moduleName);

		public function getSkinPath();


		public function getCurrentModule();
		public function getCurrentMethod();
		public function getCurrentElementId();
		public function getCurrentMode();
		public function getCurrentDomain();
		public function getCurrentLang();

		public function getLang();

		public function setCurrentModule($moduleName);
		public function setCurrentMethod($methodName);
		
		public function getRequestId();
		
		public function getPreLang();
		
		public function calculateRefererUri();
		public function getCalculatedRefererUri();
	}


	class cmsController extends singleton implements iSingleton, iCmsController {
		private	$modules = array(),
				$current_module = false,
				$current_method = false,
				$current_mode = false,
				$current_element_id = false,
				$current_lang = false,
				$current_domain = false,
				$calculated_referer_uri = false,
				$modulesPath;

		public	
				$parsedContent = false,
				$currentTitle = false,
				$currentHeader = false,
				$currentMetaKeywords = false,
				$currentMetaDescription = false,

				$langs = array(),
				$langs_export = array(),
				$pre_lang = "",
				$errorUrl, $headerLabel = false;
				
		public		$isContentMode = false;


		protected function __construct() {
			$config = mainConfiguration::getInstance();
			$this->modulesPath = $config->includeParam('system.modules');
			$this->init();
		}

		/**
		* @desc
		* @return cmsController
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		private function loadModule($module_name) {
			$xpath = "//modules/" . $module_name;
			
			if(!defined("CURRENT_VERSION_LINE")) {
				define("CURRENT_VERSION_LINE", "");
			}
			
			if(CURRENT_VERSION_LINE == "free" || CURRENT_VERSION_LINE == "lite") {
				if($module_name == "forum" || $module_name == "vote" || $module_name == "webforms") {
					return false;
				}
			}
			
			if(ulangStream::getLangPrefix() != "ru" && $module_name == "seo") {
				return false;
			}

			if(regedit::getInstance()->getVal($xpath) == $module_name) {
				$module_path = $this->modulesPath . $module_name . "/class.php";
				if(file_exists($module_path)) {
					require_once $module_path;

					if(class_exists($module_name)) {
						$new_module = new $module_name();
						$new_module->pre_lang = $this->pre_lang;
						$new_module->pid = $this->getCurrentElementId();
						$this->modules[$module_name] = $new_module;

						return $new_module;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}


		public function loadBuildInModule($moduleName) {
			//TODO
		}

		public function getModule($module_name) {
			if(!$module_name) return false;

			if(array_key_exists($module_name, $this->modules)) {
				return $this->modules[$module_name];
			} else {
				return $this->loadModule($module_name);
			}
		}

		public function installModule($installPath) {
			if(!file_exists($installPath)) {
				throw new publicAdminException("No such file");
			}
			require_once $installPath;
			def_module::install($INFO);
		}

		public function getSkinPath() {
			//TODO
		}


		public function getCurrentModule() {
			return $this->current_module;
		}

		public function getCurrentMethod() {
			return $this->current_method;
		}

		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		public function getLang() {
			return $this->current_lang;
		}

		public function getCurrentLang() {
			return $this->getLang();
		}

		public function getCurrentMode() {
			return $this->current_mode;
		}

		public function getCurrentDomain() {
			return $this->current_domain;
		}

		private function init() { 
			$this->detectDomain();
			$this->detectLang();
			$this->detectMode();
			$this->loadLangs();


			$LANG_EXPORT = array();
			$lang_file = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			if (file_exists($lang_file)) {
				require $lang_file;
			}
			$this->langs = array_merge($this->langs, $LANG_EXPORT);


			$ext_lang = CURRENT_WORKING_DIR . "/classes/modules/lang." . $this->getCurrentLang()->getPrefix() . ".php";
			if(file_exists($ext_lang)) {
				require $ext_lang;
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
			}

			$this->errorUrl = getServer('HTTP_REFERER');
			$this->doSomething();
			$this->calculateRefererUri();
		}

		private function detectDomain() {
			$domains = domainsCollection::getInstance();
			$host = getServer('HTTP_HOST');
			if($domain_id = $domains->getDomainId($host)) {
				$domain = $domains->getDomain($domain_id);
			} else {
				$domain = $domains->getDefaultDomain()->getId();
			}
			
			if(is_numeric($domain)) {
				$domain = $domains->getDomain($domain);
			}
			
			if(getServer('HTTP_HOST') != $domain->getHost()) {
				$config = mainConfiguration::getInstance();

				if($config->get('seo', 'primary-domain-redirect')) {
					$uri = 'http://' . $domain->getHost() . getServer('REQUEST_URI');
					
					$buffer = outputBuffer::current();
					$buffer->header('Location', $uri);
					$buffer->clear();
					$buffer->end();
				}
			}

			if(is_object($domain)) {
				$this->current_domain = $domain;
				return true;
			} else {
				$domain = $domains->getDefaultDomain();
				if($domain instanceof domain) {
					$this->current_domain = $domain;
					$domain->addMirrow($host);
					return false;
				} else {
					throw new coreException("Current domain could not be found");
				}
			}
		}

		private function detectLang() {
			$LangIDs = getRequest('lang_id');

			$lang_id = false;
			if($LangIDs != null) {
				if(is_array($LangIDs)) list($LangIDs) = $LangIDs;
				$lang_id = intval($LangIDs);
			} else if (!is_null(getRequest('links')) && is_array($rel = getRequest('rel'))) {
				if(sizeof($rel) && ($elementId = array_pop($rel))) {
					$element = umiHierarchy::getInstance()->getElement($elementId, true);
					if($element instanceof umiHierarchyElement) {
						$lang_id = $element->getLangId();
					}
				}
			} else {
				list($sub_path) = $this->getPathArray();
				$lang_id = langsCollection::getInstance()->getLangId($sub_path);				
			}
			
			if(($this->current_lang = langsCollection::getInstance()->getLang($lang_id)) === false ) {
				if($this->current_domain) {
					if($lang_id = $this->current_domain->getDefaultLangId()) {
						$this->current_lang = langsCollection::getInstance()->getLang($lang_id);
					} else {
						$this->current_lang = langsCollection::getInstance()->getDefaultLang();
					}
				} else {
					$this->current_lang = langsCollection::getInstance()->getDefaultLang();
				}
			}

			if($this->current_lang->getId() != $this->current_domain->getDefaultLangId()) {
				$this->pre_lang = "/" . $this->current_lang->getPrefix();
				$_REQUEST['pre_lang'] = $this->pre_lang;
			}
		}

		private function getPathArray() {
			$path = getRequest('path');
			$path = trim($path, "/");

			return explode("/", $path);
		}

		private function detectMode() {
			$path_arr = $this->getPathArray();
			
			if(sizeof($path_arr) < 2) {
				$path_arr[1] = NULL;
			}
			
			list($sub_path1, $sub_path2) = $path_arr;

			if($sub_path1 == "admin" || $sub_path2 == "admin") {
				$this->current_mode = "admin";
			} else {
				$this->current_mode = "";
				cacheFrontend::$cacheMode = true;
			}
		}


		private function getSubPathType($sub_path) {
			$regedit = regedit::getInstance();

			if(!$this->current_module) {
				if($sub_path == "seo") {
					if(ulangStream::getLangPrefix() != "ru") {
						return "UNKNOWN";
					}
				}
				
				if($sub_path == "trash") {
					def_module::redirect($this->pre_lang . "/admin/data/trash/");
				}
				
				if($regedit->getVal("//modules/" . $sub_path)) {
					$this->setCurrentModule($sub_path);
					return "MODULE";
				}
			}

			if($this->current_module && !$this->current_method) {
				$this->setCurrentMethod($sub_path);
				return "METHOD";
			}

			if($this->current_module && $this->current_method) {
				return "PARAM";
			}

			return "UNKNOWN";
		}


		public function analyzePath() {
			$regedit = regedit::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$config = mainConfiguration::getInstance();
			$buffer = outputBuffer::current();
			
			if($config->get('seo', 'folder-redirect')) {
				def_module::requireSlashEnding();
			}
			
			if($config->get('seo', 'watch-redirects-history')) {
				redirects::getInstance()->init();
			}
			
			$path_arr = $this->getPathArray();

			$path = getRequest('path');
			$path = trim($path, "/");


			$sz = sizeof($path_arr);
			$url_arr = Array();
			$p = 0;
			for($i = 0; $i < $sz; $i++) {
				$sub_path = $path_arr[$i];

				if($i <= 1) {
					if(($sub_path == $this->current_mode) || ($sub_path == $this->current_lang->getPrefix())) {
						continue;
					}
				}
				$url_arr[] = $sub_path;

				$sub_path_type = $this->getSubPathType($sub_path);

				if($sub_path_type == "PARAM") {
					$_REQUEST['param' . $p++] = $sub_path;
				}
			}

			if(!$this->current_module) {
				if($this->current_mode == "admin") {
					$module_name = $regedit->getVal("//settings/default_module_admin");
					$this->autoRedirectToMethod($module_name);
				} else {
					$module_name = $regedit->getVal("//settings/default_module");
				}
				$this->setCurrentModule($module_name);
			}

			if(!$this->current_method) {
				if($this->current_mode == "admin") {
					return $this->autoRedirectToMethod($this->current_module);
				} else {
					$method_name = $regedit->getVal("//modules/" . $this->current_module . "/default_method");
				}
				$this->setCurrentMethod($method_name);
			}


			if($this->getCurrentMode() == "admin") {
				return;
			}

			$element_id = false;
			$sz = sizeof($url_arr);
			$sub_path = "";
			for($i = 0; $i < $sz; $i++) {
				$sub_path .= "/" . $url_arr[$i];

				if(!($tmp = $hierarchy->getIdByPath($sub_path, false, $errors_count))) {
					$element_id = false;
					break;
				} else {
					$element_id = $tmp;
				}
			}
			
			if($element_id) {
				if($errors_count > 0 && !defined("DISABLE_AUTOCORRECTION_REDIRECT")) {
					$path = $hierarchy->getPathById($element_id);
					
					if($i == 0) {
						if($this->isModule($url_arr[0])) {
							$element_id = false;
							break;
						}
					}
					
					$buffer->status('301 Moved Permanently');
					$buffer->redirect($path);
				}
				
				$element = $hierarchy->getElement($element_id);
				if($element instanceof umiHierarchyElement) {
					if($element->getIsDefault()) {
						$path = $hierarchy->getPathById($element_id);

						$buffer->status('301 Moved Permanently');
						$buffer->redirect($path);
					}
				}
			}

			if(($path == "" || $path == $this->current_lang->getPrefix()) && $this->current_mode != "admin") {
				if($element_id = $hierarchy->getDefaultElementId($this->getCurrentLang()->getId(), $this->getCurrentDomain()->getId())) {
					$this->current_element_id = $element_id;
				}
			}


			if($element = $hierarchy->getElement($element_id, true)) {
				$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());
				
				if(!$type) return false;

				$this->current_module = $type->getName();
				
				if($ext = $type->getExt()) {
					$this->setCurrentMethod($ext);
				} else {
					$this->setCurrentMethod("content");	//Fixme: content "constructor". Maybe, fix in future?
				}

				$this->current_element_id = $element_id;
			}
			
			if($this->current_module && "content" && $this->current_method == "content" && !$element_id) {
				redirects::getInstance()->redirectIfRequired($path);
			}
		}


		public function setCurrentModule($module_name) {
			$this->current_module = $module_name;
		}


		public function setCurrentMethod($method_name) {
			if(defined("CURRENT_VERSION_LINE")) {
				if(CURRENT_VERSION_LINE == "free" || CURRENT_VERSION_LINE == "lite" || CURRENT_VERSION_LINE == "freelance") {
					if(cmsController::getInstance()->getCurrentMode() == "admin") {
						if($this->current_module == "data" 
							&& substr($method_name, 0, strlen("trash")) != "trash"							
							&& !in_array($method_name, array("json_load_hierarchy_level", "getfilelist", "getfolderlist", "uploadfile", "createfolder", "deletefolder", "getimagepreview", "rename"))) {
							$this->current_module = "content";
							$this->current_method = "sitetree";
							return false;
						}
					}
				}
			}

			$this->current_method = $method_name;
		}


		public function loadLangs() {
			$modules = regedit::getInstance()->getList("//modules");
			foreach($modules as $module) {
				$module_name = $module[0];

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang.php";

				if (file_exists($lang_path)) {
					require $lang_path;
				}

				if(isset($C_LANG)) {
					if(is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}
				}
				
				if(isset($LANG_EXPORT)) {
					if(is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang." . $this->getCurrentLang()->getPrefix() .".php";

				if(file_exists($lang_path)) {
					require $lang_path;

					if(is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}

					if(is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}
			}
		}
		
		public function getModulesList() {
			$regedit = regedit::getInstance();
			$list = $regedit->getList('//modules');
			$result = array();
			foreach($list as $arr) {
				$result[] = getArrayKey($arr, 0);
			}
			return $result;
		}


		final private function doSomething () { return false; 
			if(defined("CRON") && (constant('CRON') == 'CLI')) {
				return true;
			}

			if(defined("CURRENT_VERSION_LINE")) {
				if(CURRENT_VERSION_LINE != "demo") {
					require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
					exit();
				} else {
					return true;
				}
			}

			$keycode = regedit::getInstance()->getVal("//settings/keycode");

			if($this->doStrangeThings($keycode)) {
				return true;
			}


			$comp_keycode = Array();
			$comp_keycode['pro'] = templater::getSomething("pro");
			$comp_keycode['free'] = templater::getSomething("free");
			$comp_keycode['lite'] = templater::getSomething("lite");
			$comp_keycode['freelance'] = templater::getSomething("freelance");
			$comp_keycode['trial'] = templater::getSomething("trial");

			if(regedit::checkSomething($keycode, $comp_keycode)) {
				return true;
			} else {
				require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
				exit();
			}
		}


		
		
		
		final private function doStrangeThings($keycode) {
			$license_file = SYS_CACHE_RUNTIME . 'trash';
			$cmp_keycode = false;
			$expire = 604800;

			if(file_exists($license_file)) {
				if((time() - filemtime($license_file)) > $expire) {
					$cmp_keycode = base64_decode(file_get_contents($license_file));
				}
			} else {
				file_put_contents($license_file, base64_encode($keycode));
			}
			
			if($cmp_keycode !== false && $keycode) {
				if($keycode === $cmp_keycode) {
					return true;
				}
			}
			return false;
		}
		
		
		public function getRequestId() {
			static $requestId = false;
			if($requestId === false) $requestId = time();
			return $requestId;
		}
		
		public function getPreLang() {
			return $this->pre_lang;
		}
		

		protected function autoRedirectToMethod($module) {
			$pre_lang = $this->pre_lang;
			$method = regedit::getInstance()->getVal("//modules/" . $module . "/default_method_admin");
			
			$url = $pre_lang . "/admin/" . $module . "/" . $method . "/";
			
			outputBuffer::current()->redirect($url);
		}
		
		
		public function calculateRefererUri() {
			if($referer = getRequest('referer')) {
				$_SESSION['referer'] = $referer;
			} else {
				if($referer = getSession('referer')) {
					unset($_SESSION['referer']);
				} else {
					$referer = getServer('HTTP_REFERER');
				}
			}
			$this->calculated_referer_uri = $referer;
		}
		
		
		public function getCalculatedRefererUri() {
			if($this->calculated_referer_uri === false) {
				$this->calculateRefererUri();
			}
			return $this->calculated_referer_uri;
		}
		
		
		public function isModule($module_name) {
			$regedit = regedit::getInstance();
			
			if($regedit->getVal('//modules/' . $module_name)) {
				return true;
			} else {
				return false;
			}
			
		}
	};


	interface iUmiDate {
		public function __construct($timeStamp = false);

		public function getFormattedDate($formatString = false);
		public function getCurrentTimeStamp();
		public function getDateTimeStamp();

		public function setDateByTimeStamp($timeStamp);
		public function setDateByString($dateString);

		public static function getTimeStamp($dateString);
	}


	/**
	* @desc Класс-обертка для внутреннего представления типа данных "Дата"
	*/
	class umiDate implements iUmiDate {
		public $timestamp;
		public static $defaultFormatString = DEFAULT_DATE_FORMAT;
        /**
        * @desc Публичный конструктор
        * @param Int $timestamp Количество секунд с начала эпохи Unix (TimeStamp)
        */
		public function __construct($timestamp = false) {
			if($timestamp === false) {
				$timestamp = self::getCurrentTimeStamp();
			}
			$this->setDateByTimeStamp($timestamp);
		}

		/**
		* @desc Возвращяет текущий Time Stamp
		* @return Int Time Stamp
		*/
		public function getCurrentTimeStamp() {
			return time();
		}
        /**
        * @desc Возвращает Time Stamp для сохраненной даты
        * @return Int Time Stamp
        */
		public function getDateTimeStamp() {
			return intval($this->timestamp);
		}
        /**
        * @desc Возвращает сохраненную дату в отформатированом виде
        * @param String $formtString Форматная строка (см. описание функции date на php.net)
        * @return String отформатированная дата 
        */
		public function getFormattedDate($formatString = false) {
			if($formatString === false) {
				$formatString = self::$defaultFormatString;
			}
			return date($formatString, $this->timestamp);
		}
        /**
        * @desc Устанавливает дату по Time Stamp
        * @param Int $timestamp Time Stamp желаемой даты
        * @return Boolean false - если $timestamp не число, true - в противном случае
        */
		public function setDateByTimeStamp($timestamp) {
			if(!is_numeric($timestamp)) {
				return false;
			}
			$this->timestamp = $timestamp;
			return true;
		}
		/**
		* @desc Устанавливает дату по переданой строке
		* @param String $dateString Строка с датой
		* @return Boolean true - если переданная строка может быть интерпретирована, как дата, false - в противном случае
		*/
		public function setDateByString($dateString) {
			$dateString = umiObjectProperty::filterInputString($dateString);
			$timestamp  = strlen($dateString) ? self::getTimeStamp($dateString) : 0;
			return $this->setDateByTimeStamp($timestamp);
		}
		/**
		* @desc Преобразует строку с датой в Time Stamp
		* @param String $dateString Строка с датой
		* @return Int Time Stamp
		*/
		public static function getTimeStamp($dateString) {
			return toTimeStamp($dateString);
		}
		
		public function __toString() {
			return $this->getFormattedDate();
		}
	}


	interface iUmiFile {
		public function __construct($filePath);
		public function delete();

		public static function upload($variableGroupName, $variableName, $targetFolder);

		public function getSize();
		public function getExt();
		public function getFileName();
		public function getDirName();
		public function getModifyTime();
		public function getFilePath($webMode = false);

		public function getIsBroken();

		public function __toString();
		
		public static function getUnconflictPath($path);
		
		public function download($deleteAfterDownload = false);
	}


/**
	* Класс для работы с файлами в системе
*/
	class umiFile implements iUmiFile {
		protected	$filepath,
				$size, $ext, $name, $dirname, $modify_time,
				$is_broken = false;
		public static $mask = 0777;

		protected static $class_name = 'umiFile';
		protected static $allowedFileTypes = array(
			'txt', 'doc', 'docx', 'xsl', 'xslx', 'pdf', 'csv',
			'html', 'js', 'tpl', 'xsl', 'xml', 'css',
			'zip', 'rar', '7z', 'tar', 'gz', 'tar.gz', 'exe', 'msi',
			'rtf', 'chm', 'ico', 'jpg', 'jpeg', 'gif', 'png', 'bmp', 
			'psd', 'flv', 'mp4'
		);

		/**
			* Конструктор
			* @param String $filepath путь до файла
		*/
		public function __construct($filepath) {
			$filepath = str_replace("//", "/", $filepath);
			$filepath = str_replace("\\\\", "\\", $filepath);
			
			if(!@is_file($filepath)) {
				$this->is_broken = true;
				return false;
			}

			$this->filepath = $filepath;
			$this->filepath = str_replace("\\", "/", $filepath);
			$this->loadInfo();
		}

		/**
			* Удалить файл из файловой системы
		*/
		public function delete() {
			if(is_writable($this->filepath)) {
				return unlink($this->filepath);
			} else {
				return false;
			}
		}
		
		
		/**
			* Послать HTTP заголовки для того, чтобы браузер начал скачивать файл
			* @param Boolean $deleteAfterDownload = false удалить файл после скачивания
		*/
		public function download($deleteAfterDownload = false) {
			while (@ob_end_clean());

			header('HTTP/1.1 200 OK');
			header("Cache-Control: public, must-revalidate");
			header("Pragma: no-cache");
			header("Content-type: application/force-download");
			header("Content-Length: " . $this->getSize());
			header('Accept-Ranges: bytes');
			header("Content-Encoding: None");
			header("Vary:");
			header('Content-Transfer-Encoding: Binary');
			header("Content-Disposition: attachment; filename=" . $this->getFileName());
			
			readfile(realpath($this->getFilePath()));
			
			if($deleteAfterDownload) {
				$this->delete();
			}
			exit();
		}
		
		public static function manualUpload($name, $temp_path, $size, $target_folder) {
		    if(!$size || !$name || $name == ".htaccess" || !is_uploaded_file($temp_path)) return 1;
		    

		    if( !in_array(substr($name, strrpos($name, '.') + 1), self::$allowedFileTypes) ) return 2;

		    
		    list(,, $extension) = array_values(pathinfo($name));
			$name = substr($name, 0, strlen($name) - strlen($extension));
			$name = translit::convert($name);
			$name .= "." . strtolower($extension);

			$new_path = $target_folder . "/" . $name;

			if($name == ".htaccess") {
				return 3;
			}

			$extension = strtolower($extension);

			
			if(is_uploaded_file($temp_path)) {
				$new_path = umiFile::getUnconflictPath($new_path);
				
				if(move_uploaded_file($temp_path, $new_path)) {
					chmod($new_path, self::$mask);

					$new_path = self::getRelPath($new_path);
					return new self::$class_name($new_path);
				} else {
					return 5;
				}
			} else {
				return 6;
			}
		}


		public static function upload($group_name, $var_name, $target_folder, $id = false) {
			global $_FILES;
			$files_array = &$_FILES;

			$target_folder_input = $target_folder;
			if(substr($target_folder_input, strlen($target_folder_input) - 1, 1) != "/") $target_folder_input .= "/";

			$target_folder = realpath($target_folder);

			if(!is_dir($target_folder)) {
				return false;
			}

			if(!is_writable($target_folder)) {
				return false;
			}

			if(!is_array($files_array)) {
				return false;
			}
			
			if(!isset($files_array[$group_name]) && isset($files_array['pics'])) {
				$files_array[$group_name] = $files_array['pics'];
				$group_name = "pics";
			}

			if(array_key_exists($group_name, $files_array)) {
				$file_info = $files_array[$group_name];
				
				if(isset($file_info['size'][$var_name])) {
					$id = false;
				}

				$size = ($id === false) ? 
                                    (isset($file_info['size'][$var_name])? $file_info['size'][$var_name] : 0)
                                        : 
                                    (isset($file_info['size'][$id][$var_name])? $file_info['size'][$id][$var_name] : 0);

				if($size == 0) {
					return false;
				} else {
					$temp_path = ($id === false) ? $file_info['tmp_name'][$var_name] : $file_info['tmp_name'][$id][$var_name];
					$name = ($id === false) ? $file_info['name'][$var_name] : $file_info['name'][$id][$var_name];	//TODO: make cyrilic to translit conversion

					$aForbiddenTypes = array("php", "php3", "php4", "php5", "phtml");

					if( in_array(substr($name, strrpos($name, '.')), $aForbiddenTypes) ) return false;

					list(,, $extension) = array_values(pathinfo($name));
					$name = substr($name, 0, strlen($name) - strlen($extension));
					$name = translit::convert($name);
					$name .= "." . strtolower($extension);

					$new_path = $target_folder . "/" . $name;

					if($name == ".htaccess") {
						return false;
					}

					$extension = strtolower($extension);

					if( !in_array( $extension, self::$allowedFileTypes ) ) {
						return false;
					}


					if(is_uploaded_file($temp_path)) {
						$new_path = umiFile::getUnconflictPath($new_path);
						if(move_uploaded_file($temp_path, $new_path)) {
							chmod($new_path, self::$mask);

							$new_path = self::getRelPath($new_path);
							return new self::$class_name($new_path);
						} else {
							return false;
						}
					} else {
						return false;
					}
				}
			} else {
				return false;
			}
		}
		
		// Ф-я распаковки zip-архива
		public static function upload_zip ($var_name, $file = "", $folder = "./images/cms/data/")  {
			
			if ($file == "") {
				$temp_path = $var_name['tmp_name'];
				$name = $var_name['name'];
				
				list(,, $extension) = array_values(pathinfo($name));
				$name = substr($name, 0, strlen($name) - strlen($extension));
				$name = translit::convert($name);
				$name .= "." . $extension;

				$new_path = $folder.$name;
				$upload_path = CURRENT_WORKING_DIR . "/sys-temp/uploads";
				if(!is_dir($upload_path)) {
					mkdir($upload_path);
				}
				$new_zip_path = $upload_path.'/'.$name;
				
				if ($var_name['size'] == 0) {
					return false;
				}
				
				if(is_uploaded_file($temp_path)) {
						
						$new_path = umiFile::getUnconflictPath($new_path);
						if(move_uploaded_file($temp_path, $new_zip_path)) {
							chmod($new_zip_path, self::$mask);
						} else {
							return false;
						}
				} else {
					return false;
				}
			
			} else {
				
				$file = CURRENT_WORKING_DIR . "/" . $file;
				
				if (!file_exists ($file) || !is_writable($file)) return "File not exists!";
				
				$path_parts = pathinfo ($file);
				
				if ($path_parts['extension'] != "zip") {
					return "It's not zip-file!";
				}
				
				$new_path = $file;
				$new_zip_path = $file;
			}
			
			$archive = new PclZip($new_zip_path);
		
			$list = $archive->extract(PCLZIP_OPT_PATH, $folder,
				PCLZIP_CB_PRE_EXTRACT, "callbackPreExtract",
				PCLZIP_CB_POST_EXTRACT, "callbackPostExtract",
				PCLZIP_OPT_REMOVE_ALL_PATH);

			if (!is_array ($list)) {				
				throw new coreException ("Zip extracting error: ".$archive->errorInfo(true));
			} 
				
			// unlink zip
			if(is_writable($new_zip_path)) {
				unlink($new_zip_path);
			}

			return $list;
		}

		/**
			* Получить название файла
			* @return String название файла
		*/
		public function getFileName() {
			return $this->name;
		}

		/**
			* Получить путь директорию, в которой лежит файл
			* @return String адрес директории, в которой лежит файл относительно UNIX TIMESTAMP
		*/
		public function getDirName() {
			return $this->dirname;
		}

		/**
			* Получить время последней модификации файла
			* @return Integer время последней модификации файла в UNIX TIMESTAMP
		*/
		public function getModifyTime() {
			return $this->modify_time;
		}

		/**
			* Получить расширение файла
			* @return String расширение файла
		*/
		public function getExt() {
			return $this->ext;
		}

		/**
			* Получить размер файла
			* @return Integer размер файла в байтах
		*/
		public function getSize() {
			return $this->size;
		}

		/**
			* Получить путь до файла в файловой системе
			* @param Boolean $web_mode если true, то путь будет указан относительно DOCUMENT_ROOT'а
			* @return String путь до файла
		*/
		public function getFilePath($web_mode = false) {
			if($web_mode) {
				$sIncludePath = ini_get("include_path");
				if (substr($this->filepath, 0, strlen($sIncludePath)) === $sIncludePath) {
					return "/" . substr($this->filepath, strlen($sIncludePath));
				}
				$sIncludePath = CURRENT_WORKING_DIR;
				if (substr($this->filepath, 0, strlen($sIncludePath)) === $sIncludePath) {
					return substr($this->filepath, strlen($sIncludePath));
				}
				return (substr($this->filepath, 0, 2) == "./") ? ("/" . substr($this->filepath, 2, strlen($this->filepath) - 2)) : $this->filepath;
			} else {
				return $this->filepath;
			}
		}

		private function loadInfo() {
			if(!is_file($this->filepath)) {
				$this->is_broken = true;
				return false;
			}

			if(!is_readable($this->filepath)) {
				$this->is_broken = true;
				return false;
			}
			
			$pathinfo = pathinfo($this->filepath);

			$this->modify_time = filemtime($this->filepath);
			$this->size = filesize($this->filepath);
			$this->dirname = $pathinfo['dirname'];
			$this->name = $pathinfo['basename'];
			$this->ext = strtolower(getArrayKey($pathinfo, 'extension'));

			if($this->ext == "php" || $this->ext == "php5" || $this->ext == "phtml") {
				$this->is_broken = true;
			}

			if($this->name == ".htaccess") {
				$this->is_broken = true;
			}
		}

		public function __toString() {
			$filepath = $this->getFilePath(true);
			return is_null($filepath) ? "" : $filepath;
		}

		/**
			* Узнать, все ли в порядке с файлом, на который ссылается объект umiFile
			* @return Boolean true, если нет ошибок
		*/
		public function getIsBroken() {
			return (bool) $this->is_broken;
		}

		
		public static function getUnconflictPath($new_path) {
			if(!file_exists($new_path)) {
				return $new_path;
			} else {
				$info = pathinfo($new_path);
				$dirname = $info['dirname'];
				$filename = $info['filename'];
				$ext = $info['extension'];

				for($i = 1; $i < 257; $i++) {
					$new_path = $dirname . "/" . $filename . $i . "." . $ext;
					if(!file_exists($new_path)) {
						return $new_path;
					}
				}
				throw new coreException("This is really hard to happen");
			}
		}


		protected static function getRelPath($path) {
			$cwd = realpath(getcwd());
			return "." . substr(realpath($path), strlen($cwd));
		}
	};
	
	
// Контроль извлекаемых из zip-архива файлов
function callbackPreExtract ($p_event, &$p_header) {
	
	$info = pathinfo($p_header['filename']);
	$basename = substr($info['basename'], 0, (strlen($info['basename']) - strlen($info['extension']))-1);
	$basename = translit::convert($basename);
	$p_header['filename'] = $info['dirname']."/".$basename.".".$info['extension'];
	
	$p_header['filename'] = umiFile::getUnconflictPath($p_header['filename']);
	
	return 1;

}

function callbackPostExtract ($p_event, &$p_header) {
	
	$info = pathinfo($p_header['stored_filename']);
	
	$allowedTypes = array ("jpg","jpeg","gif","bmp","png");
	
	if ( !in_array ($info['extension'], $allowedTypes)) {
		unlink ($p_header['filename']);
	}
	
	return 1;

}


	interface iUmiImageFile {
		public function getWidth();
		public function getHeight();
	}


/**
	* Класс для работы с файлами изображений, наследуется от класса umiFile
*/
	class umiImageFile extends umiFile implements iUmiImageFile {
		private static $aSupportedTypes = null;
		private static $useWatermark = false;

		/**
			* Конструктор, принимает в качестве аргумента путь до файла в локальной файловой системе.
			* @param String $filepath путь до файла в локальной файловой системе
		*/
		public function __construct($filepath) {
			parent::__construct($filepath);
			if (!$this->is_broken) {
				$this->is_broken = ! self::getIsImage($this->name);
			}

		}

		/**
			* Получить список поддерживаемых расширений файлов
			* @return Array массив, стостоящий из допустимых расширений файлов изображений
		*/
		public static function getSupportedImageTypes() {
			if (is_null(self::$aSupportedTypes)) {
				self::$aSupportedTypes = array();
				self::$aSupportedTypes[] = "GIF";
				self::$aSupportedTypes[] = "JPG";
				self::$aSupportedTypes[] = "JPEG";
				self::$aSupportedTypes[] = "PNG";
				self::$aSupportedTypes[] = "WBMP";
				self::$aSupportedTypes[] = "BMP";
				self::$aSupportedTypes[] = "SWF";
			}

			return self::$aSupportedTypes;
		}
		
		/**
			* Указывает на необходимость добавления водного знака к следующей загружаемой картинке
		*/
		public static function setWatermarkOn () {
			self::$useWatermark = true;
		}
		/**
			* Отключает водный знак
		*/
		public static function setWatermarkOff () {
			self::$useWatermark = false;
		}

		/**
			* Загрузить файл из запроса и сохранить локально. Информация о файле берется из массива $_FILES[$group_name]["size"][$var_name]
			* @param String $group_name
			* @param String $var_name
			* @param String $target_folder локальная папка, в которую необходимо сохранить файл
			* @return Boolean true в случае успеха
		*/
		public static function upload($group_name, $var_name, $target_folder, $id = false) {
			self::$class_name = __CLASS__;		
			$filepath = parent::upload($group_name, $var_name, $target_folder, $id);
			
			$regedit = regedit::getInstance();
			$max_img_filesize = (int) $regedit->getVal("//settings/max_img_filesize");
			$upload_max_filesize = (int) ini_get("upload_max_filesize");
			$max_img_filesize = ($max_img_filesize < $upload_max_filesize) ? $max_img_filesize : $upload_max_filesize;

			$filesize = (int) filesize("." . $filepath);
			$max_img_filesize = (int) $max_img_filesize*1024*1024;
			
			if($max_img_filesize > 0) {
				if($max_img_filesize < $filesize) {
					unlink("." . $filepath);
					return false;
				}
			}
			
			$info = @getimagesize(".".$filepath);
			if(!is_array($info)) {
				@unlink("." . $filepath);
				return false;				
			}
			
			// Если нужно добавляем водяной знак и отключаем его для следующих изображений
			if (self::$useWatermark) {
				self::addWatermark ("./".$filepath);
			}
			self::setWatermarkOff ();
			

			return $filepath;
		}
		
		/**
			* Проверить, является ли файл допустимым изображением
			* @param String $sFilePath путь до файла, который необходимо проверить
			* @return Boolean true, если файл является изображением
		*/
		public static function getIsImage($sFilePath) {
			$arrFParts = explode(".", $sFilePath);
			$sFileExt = strtoupper(array_pop($arrFParts));
			return in_array($sFileExt, self::getSupportedImageTypes());
		}

		public function getWidth() {
			list($width, $height) = getimagesize($this->filepath);
			return $width;
		}

		public function getHeight() {
			list($width, $height) = getimagesize($this->filepath);
			return $height;
		}
		
		/**
			* Добавляет водный знак на изображение
			* @param string $filePath путь до изображения
			* @return boolean
		*/
		public static function addWatermark ($filePath) {
		
			$regedit = regedit::getInstance ();
		
			$srcWaterImage = $regedit->getVal ("//settings/watermark/image");
			$scaleWaterImage = 80;//$regedit->getVal ("//settings/watermark/scale");
			$alphaWaterImage = $regedit->getVal ("//settings/watermark/alpha");
			$valignWaterImage = $regedit->getVal ("//settings/watermark/valign");
			$halignWaterImage = $regedit->getVal ("//settings/watermark/halign");
			
			if (!file_exists ($srcWaterImage)) {
				return false;
			}
			if (!$alphaWaterImage) {
				$alphaWaterImage = 70;
			}
			if (!$valignWaterImage) {
				$valignWaterImage = "bottom";
			}
			if (!$halignWaterImage) {
				$halignWaterImage = "right";
			}

			$waterImgParam = self::createImage ($srcWaterImage);
			$srcImgParam = self::createImage ($filePath);
			$imageFileInfo = pathinfo ($filePath);
			
			if (!$waterImgParam || !$srcImgParam) {
				return false;
			}

			$hscale = $waterImgParam["height"] / $srcImgParam["height"];
			$wscale = $waterImgParam["width"] / $srcImgParam["width"];

			if (($hscale > $scaleWaterImage/100) || ($wscale > $scaleWaterImage/100)) {
				$scale = ($scaleWaterImage / 100) / (($hscale > $wscale) ? $hscale : $wscale);
				$newheight = floor($waterImgParam["height"] * $scale);
				$newwidth = floor($waterImgParam["width"] * $scale);
			} else {
				$newheight = $waterImgParam["height"];
				$newwidth = $waterImgParam["width"];
			}

			$tmpImg = imagecreatetruecolor ($newwidth, $newheight);
			$whiteColor = imagecolorallocate ($tmpImg, 255, 255, 255);

			imagefilledrectangle ($tmpImg, 0, 0, $newwidth, $newheight, $whiteColor);

			imagecopyresized ($tmpImg, $waterImgParam["im"], 0, 0, 0, 0, $newwidth, $newheight, $waterImgParam["width"], $waterImgParam["height"]);
			imagedestroy ($waterImgParam["im"]);

			$waterImgParam = array (
				"im" => $tmpImg,
				"width" => $newwidth,
				"height" => $newheight
			);

			$tmpImg = imagecreatetruecolor ($srcImgParam["width"], $srcImgParam["height"]);
			$whiteColor = imagecolorallocate ($tmpImg, 255, 255, 255);

			imagecopy ($tmpImg, $srcImgParam["im"], 0,0,0,0, $srcImgParam["width"], $srcImgParam["height"]);

			$x_ins = 0;
			$y_ins = 0;
			
			switch ($halignWaterImage){
				case "center" : {
					$x_ins = floor (($srcImgParam["width"] - $waterImgParam["width"]) / 2);
					break;
				}
				case "right" : {
					$x_ins = $srcImgParam["width"] - $waterImgParam["width"];
				}
			}
			switch ($valignWaterImage) {
				case "center" : {
					$y_ins = floor (($srcImgParam["height"] - $waterImgParam["height"]) / 2);
					break;
				}
				case "bottom" : {
					$y_ins = $srcImgParam["height"] - $waterImgParam["height"];
				}
			}
			
			imagecopymerge ($tmpImg, $waterImgParam["im"], $x_ins, $y_ins, 0, 0, $waterImgParam["width"], $waterImgParam["height"], $alphaWaterImage);

			switch ($imageFileInfo['extension']) {
				case "jpeg" :
				case "jpg"  :
				case "JPEG" :
				case "JPG"  : {
					imagejpeg ($tmpImg, $filePath, 90);
					break;
				}
				case "png" :
				case "PNG" : {
					imagepng ($tmpImg, $filePath);
				}
				case "gif" :
				case "GIF" : {
					imagegif ($tmpImg, $filePath);
					break;
				}
				case "bmp" :
				case "BMP" : { /* TODO */ }
			}

			imagedestroy ($srcImgParam["im"]);
			imagedestroy ($waterImgParam["im"]);
			imagedestroy ($tmpImg);
			
			return true;

		}
		
		/**
			* Создает и возвращает индентификатор изображения
			* @param string $imageFilePath путь до изображения
			* @return array массив: индентификатор (im), ширина (width), высота (height)
		*/
		private static function createImage ($imageFilePath) {
			
			$image_identifier = 0;
			$pathinfo = parse_url ($imageFilePath);

			$imageFilePath = (substr ($pathinfo["path"], 0, 1) == "/")
								? substr ($pathinfo["path"], 1)
								: $pathinfo["path"];

			list ($width, $height, $type, $attr) = getimagesize ($imageFilePath);
			
			$types = array (
				"GIF" => "1",
				"JPG" => "2",
				"PNG" => "3",
				"WBMP"=> "15",
				"XBM" => "16"
			);
			
			switch($type){
				case $types["GIF"] : {
					$image_identifier = imagecreatefromgif ($imageFilePath);
					break;
				}
				case $types["JPG"] : {
					$image_identifier = imagecreatefromjpeg ($imageFilePath);
					break;
				}
				case $types["PNG"] : {
					$image_identifier = imagecreatefrompng ($imageFilePath);
					break;
				}
				case $types["WBMP"] : {
					$image_identifier = imagecreatefromwbmp ($imageFilePath);
					break;
				}
				case $types["XBM"]: {
					$image_identifier = imagecreatefromxbm ($imageFilePath);
				}
	
			}
				
			if (!$image_identifier) {
				return false;
			}
				
			return array (
				"im"     => $image_identifier,
				"width"  => $width,
				"height" => $height
			);
		}
	}


/**
	* Предоставляет досуп к свойствам языка. Язык в системе в основном обозначает языковую версию.
*/
	class lang extends umiEntinty implements iUmiEntinty, iLang {
		private $prefix, $is_default, $title;
		protected $store_type = "lang";

		/**
			* Загрузить информацию о свойствах языка из БД
			* @return Boolean true, если не возникло никаких ошибок
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, prefix, is_default, title FROM cms3_langs WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $prefix, $is_default, $title) = $row) {
				$this->prefix = $prefix;
				$this->title = $title;
				$this->is_default = (bool) $is_default;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Получить название языка
			* @return String название языка
		*/
		public function getTitle() {
			return $this->title;
		}

		/**
			* Получить префикс языка (его 2х или 3х символьный код)
			* @return String префикс языка
		*/
		public function getPrefix() {
			return $this->prefix;
		}

		/**
			* Узнать, является ли этот язык языком по умолчанию (больше не используется)
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Установить новое название языка
			* @param String $title название языка
		*/
		public function setTitle($title) {
			$this->title = $title;
			$this->setIsUpdated();
		}

		/**
			* Установить новый префикс для языка
			* @param $prefix префикс языка
		*/
		public function setPrefix($prefix) {
			$this->prefix = $prefix;
			$this->setIsUpdated();
		}

		/**
			* Установить флаг "по умолчанию" (больше не используется)
			* @param Boolean $is_default флаг "по умолчанию"
		*/
		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}

		/**
			* Сохранить изменения в БД
			* @return Boolean true, если не произошло ошибок
		*/
		protected function save() {
			$title = self::filterInputString($this->title);
			$prefix = self::filterInputString($this->prefix);
			$prefix = $this->filterPrefix($prefix);
			$is_default = (int) $this->is_default;

			$sql = "UPDATE cms3_langs SET prefix = '{$prefix}', is_default = '{$is_default}', title = '{$title}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);
			return true;
		}
		
		/**
			* Убрать символы, недопустимые в префиксе языка
			* @param String $prefix префикс языка
			* @return String отфильтрованный результат
		*/
		protected function filterPrefix($prefix) {
			return preg_replace("/[^A-z0-9_\-]+/", "", $prefix);
		}
	}


/**
	* Используется для управления языками (класс lang), которые функционально представляют языковыми версиями сайта.
	* Класс является синглтоном, получить экземпляр класса можно через статический метод getInstance().
*/
	class langsCollection extends singleton implements iSingleton, iLangsCollection {
		private $langs = Array(),
			$def_lang;

		/**
			* Конструктор, подгружает список языков
		*/
		protected function __construct() {
			$this->loadLangs();
		}

		/**
			* Получить экземпляр коллекции
			* @return langsCollection экземпляр класса langsCollection
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Загрузить список языков из БД
		*/
		private function loadLangs() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$langIds = $cacheFrontend->loadData('langs_list');
			if(!is_array($langIds)) {
				$sql = "SELECT id, prefix, is_default, title FROM cms3_langs ORDER BY id";
				$result = l_mysql_query($sql);
				$langIds = array();
				while(list($lang_id) = $row = mysql_fetch_row($result)) {
					$langIds[$lang_id] = $row;
				}
				$cacheFrontend->saveData('langs_list', $langIds, 3600);
			} else $row = false;
			
			foreach($langIds as $lang_id => $row) {
				$lang = $cacheFrontend->load($lang_id, 'lang');
				if($lang instanceof lang == false) {
					try {
						$lang = new lang($lang_id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($lang, 'lang');
				}
				
				$this->langs[$lang_id] = $lang;
				if($this->langs[$lang_id]->getIsDefault()) {
					$this->def_lang = $this->langs[$lang_id];
				}
			}
		}

		/**
			* Получить id языка (класс lang) по его префиксу
			* @param String $prefix префикс языка (его 2х-3х символьный код)
			* @return lang язык, либо false если языка с таким префиксом не существует
		*/
		public function getLangId($prefix) {
			foreach($this->langs as $lang) {
				if($lang->getPrefix() == $prefix) {
					return $lang->getId();
				}
			}
			return false;
		}

		/**
			* Создать новый язык
			* @param String $prefix префикс языка
			* @param String $title название языка
			* @param Boolean $is_default=false сделать языком по умолчанию (на данный момент не должно возникнуть необходимости ставить в true)
			* @return Integer id созданного языка, либо false
		*/
		public function addLang($prefix, $title, $is_default = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			if($lang_id = $this->getLangId($prefix)) {
				return $lang_id;
			}

			$sql = "INSERT INTO cms3_langs VALUES()";
			l_mysql_query($sql);
			$lang_id = mysql_insert_id();

			$lang = new lang($lang_id);

			$lang->setPrefix($prefix);
			$lang->setTitle($title);
			$lang->setIsDefault($is_default);

			$lang->commit();

			$this->langs[$lang_id] = &$lang;

			return $lang_id;
		}

		/**
			* Удалить язык с id $lang_id
			* @param id $lang_id языка, который необходимо удалить
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delLang($lang_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			$lang_id = (int) $lang_id;

			if(!$this->isExists($lang_id)) return false;

			$sql = "DELETE FROM cms3_langs WHERE id = '{$lang_id}'";
			l_mysql_query($sql);
			unset($this->langs[$lang_id]);
		}

		/**
			* Получить язык (экземпляр касса lang) по его id
			* @param Integer $lang_id id языка
			* @return lang язык (экземпляр касса lang), либо false
		*/
		public function getLang($lang_id) {
			$lang_id = (int) $lang_id;
			return ($this->isExists($lang_id)) ? $this->langs[$lang_id] : false;
		}

		/**
			* Узнать, существует ли в системе язык с id $lang_id
			* @param Integer $lang_id id языка
			* @return Boolean true, если язык существует
		*/
		public function isExists($lang_id) {
			return (bool) array_key_exists($lang_id, $this->langs);
		}

		/**
			* Получить список всех языков в системе
			* @return Array массив, значением которого являются экземпляры класса lang
		*/
		public function getList() {
			return $this->langs;
		}

		/**
			* Установить язык по умолчанию
			* @param Integer $lang_id id языка
		*/
		public function setDefault($lang_id) {
			if(!$this->isExists($lang_id)) return false;

			if($this->def_lang) {
				$this->def_lang->setIsDefault(false);
				$this->def_lang->commit();
			}

			$this->def_lang = $this->getLang($lang_id);
			$this->def_lang->setIsDefault(true);
			$this->def_lang->commit();
		}

		/**
			* Получить язык по умолчанию
			* @return lang экземпляр класса lang, либо false в случае неудачи
		*/
		public function getDefaultLang() {
			return ($this->def_lang) ? $this->def_lang : false;
		}

		/**
			* Получить список всех языков в системе в виде ассоциотивного массива
			* @return Array массив, где ключ это id языка, а значение - его название
		*/
		public function getAssocArray() {
			$res = array();

			foreach($this->langs as $lang) {
				$res[$lang->getId()] = $lang->getTitle();
			}

			return $res;
		}
	}


/**
	* Предоставляет доступ к свойствам зеркала для домена (класс domain). Зеркало домена используется для создания алиасов.
	* ps. да, мы знаем про опечатку :(
*/
	class domainMirrow extends umiEntinty implements iUmiEntinty, iDomainMirrow {
		private $host;

		/**
			* Изменить хост (адрес) зеркала
			* @param String $host адрес домена
		*/
		public function setHost($host) {
			$this->host = domain::filterHostName($host);
			$this->setIsUpdated();
		}

		/**
			* Получить хост (адрес) зеркала
			* @return String адрес домена
		*/
		public function getHost() {
			return $this->host;
		}

		/**
			* Загрузить информацию о зеркале из БД
			* @return Boolean true, если не произошло никаких ошибок
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, host FROM cms3_domain_mirrows WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $host) = $row) {
				$this->host = $host;
				return true;
			} else return false;
		}

		/**
			* Сохранить внесенные изменения в БД
			* @return Boolean true, если не произошло никаких ошибок
		*/
		protected function save() {
			$host = self::filterInputString($this->host);

			$sql = "UPDATE cms3_domain_mirrows SET host = '{$host}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			return true;
		}
	};


/**
	* Предоставляет доступ свойствам домена и зеркалам доменов в системе
*/
	class domain extends umiEntinty implements iUmiEntinty, iDomainMirrow, iDomain {
		private	$host, $default_lang_id, $mirrows = Array();
		protected $store_type = "domain";

		/**
			* Загружает свойства домена из БД
			* @return Boolean true, если все прошло нормально
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, host, is_default, default_lang_id FROM cms3_domains WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				$row = mysql_fetch_row($result);
			}

			if(list($id, $host, $is_default, $default_lang_id) = $row) {
				$this->host = $host;
				$this->is_default = (bool) $is_default;
				$this->default_lang_id = (int) $default_lang_id;

				return $this->loadMirrows();
			} else {
				return false;
			}
		}

		/**
			* Получить адрес домена (хост)
			* @return String адрес домена
		*/
		public function getHost() {
			return $this->host;
		}

		/**
			* Узнать, является ли этот домен доменом по умолчанию
			* @return Boolean true, если установлен флаг "по умолчанию"
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Изменить хост (адрес) домена
			* @param String $host адрес домена
		*/
		public function setHost($host) {
			$this->host = $this->filterHostName($host);
			$this->setIsUpdated();
		}

		/**
			* Установить флаг домена "по умолчанию".
			* Используется системой, в пользовательском коде нужно воспользоваться методом domainsCollection::setDefaultDomain()
			* @param Boolean $is_default флаг "по умолчанию"
		*/
		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}

		/**
			* Получить id языка (класс lang), который используется по умолчанию для этого домена
			* @return Integer id языка
		*/
		public function getDefaultLangId() {
			return $this->default_lang_id;
		}

		/**
			* Установить для домена язык по умолчанию
			* @param Integer $lang_id id языка (класс lang) по умолчанию
			* @return Boolean true, если операция прошла успешно
		*/
		public function setDefaultLangId($lang_id) {
			if(langsCollection::getInstance()->isExists($lang_id)) {
				$this->default_lang_id = $lang_id;
				$this->setIsUpdated();

				return true;
			} else throw new coreException("Language #{$lang_id} doesn't exists");
		}

		/**
			* Добавить зеркало (класс domainMirrow) для домена
			* @param String $mirrow_host хост (адрес) зеркала
			* @return Integer id созданного зеркала
		*/
		public function addMirrow($mirrow_host) {
			if($mirrow_id = $this->getMirrowId($mirrow_host)) {
				return $mirrow_id;
			} else {
				$this->setIsUpdated();
				
				$sql = "INSERT INTO cms3_domain_mirrows (rel) VALUES('{$this->id}')";
				l_mysql_query($sql);

				$mirrow_id = mysql_insert_id();

				$mirrow = new domainMirrow($mirrow_id);
				$mirrow->setHost($mirrow_host);
				$mirrow->commit();

				$this->mirrows[$mirrow_id] = $mirrow;

				return $mirrow_id;
			}
		}

		/**
			* Удалить зеркало домена, используея его id
			* @param Integer $mirrow_id id зеркала
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
				$this->setIsUpdated();
				
				$sql = "DELETE FROM cms3_domain_mirrows WHERE id = '{$mirrow_id}'";
				l_mysql_query($sql);

				unset($this->mirrows[$mirrow_id]);
				return true;
			} else return false;
		}

		/**
			* Удалить все зеркала домена
			* @return Boolean true, если удаление прошло без ошибок
		*/
		public function delAllMirrows() {
			$this->setIsUpdated();
			
			$sql = "DELETE FROM cms3_domain_mirrows WHERE rel = '{$this->id}'";
			l_mysql_query($sql);

			return true;
		}

		/**
			* Определить id зеркала домена по его хосту (адресу)
			* @param String $mirrow_host хост (адрес)
			* @return domainMirrow экземпляр класса domainMirrow, либо false, если зеркало не найдено
		*/
		public function getMirrowId($mirrow_host) {
			foreach($this->mirrows as $mirrow) {
				if($mirrow->getHost() == $mirrow_host) {
					return $mirrow->getId();
				}
			}
			return false;
		}

		/**
			* Получить зеркало домена (экземпляр класса domainMirrow) по его id
			* @param Integer $mirrow_id id зеркала домена
			* @return domainMirrow, либо false
		*/
		public function getMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
				return $this->mirrows[$mirrow_id];
			} else {
				return false;
			}
		}

		/**
			* Проверить, существует ли у домена зеркало с id $mirrow_id
			* @param $mirrow_id
			* @return Boolean true если существует
		*/
		public function isMirrowExists($mirrow_id) {
			return (bool) array_key_exists($mirrow_id, $this->mirrows);
		}

		/**
			* Получить список всех зеркал домена
			* @return Array массив, состоящий их экземпляров класса domainMirrow
		*/
		public function getMirrowsList() {
			return $this->mirrows;
		}

		/**
			* Загрузить все зеркала из БД
		*/
		private function loadMirrows() {
			$sql = "SELECT id, host FROM cms3_domain_mirrows WHERE rel = '{$this->id}'";
			$result = l_mysql_query($sql);

			while(list($mirrow_id) = $row = mysql_fetch_row($result)) {
				try {
					$this->mirrows[$mirrow_id] = new domainMirrow($mirrow_id, $row);
				} catch(privateException $e) {
					continue;
				}
			}

			return true;
		}

		/**
			* Сохранить изменения, сделанные с этим доменом
		*/
		protected function save() {
			$host = self::filterInputString($this->host);
			$is_default = (int) $this->is_default;
			$default_lang_id = (int) $this->default_lang_id;

			$sql = "UPDATE cms3_domains SET host = '{$host}', is_default = '{$is_default}', default_lang_id = '{$default_lang_id}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);
			return true;
		}
		
		public static function filterHostName($host) {
			return preg_replace("/([^A-z0-9\-А-я\.:]+)|[\^_\\\\]/u", "", $host);
		}
	};


/**
	* Служит для управления доменами (класс domain) в системе. Синглтон, экземпляр коллекции можно получить через статический метод getInstance.
	* Участвует в роутинге урлов в условиях мультидоменности.
*/
	class domainsCollection extends singleton implements iSingleton, iDomainsCollection {
		private $domains = Array(), $def_domain;

		/**
			* Конструктор, подгружает список доменов
		*/
		protected function __construct() {
			$this->loadDomains();
		}

		/**
			* Получить экземпляр коллекции
			* @return domainsCollection экземпляр коллекции
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Добавить в систему новый домен
			* @param String $host адрес домен (хост)
			* @param Integer $lang_id id языка (класс lang) по умолчанию для этого домена
			* @param Boolean $is_default=false если true, то этот домен станет основным. Будтье осторожны, при этом может испортиться лицензия
			* @return Integer id созданного домена
		*/
		public function addDomain($host, $lang_id, $is_default = false) {
			if($domain_id = $this->getDomainId($host)) {
				return $domain_id;
			} else {
				cacheFrontend::getInstance()->flush();
				
				$sql = "INSERT INTO cms3_domains VALUES()";
				l_mysql_query($sql);

				$domain_id = mysql_insert_id();

				$this->domains[$domain_id] = $domain = new domain($domain_id);
				$domain->setHost($host);
				$domain->setIsDefault($is_default);
				$domain->setDefaultLangId($lang_id);
				if($is_default) $this->setDefaultDomain($domain_id);
				$domain->commit();

				return $domain_id;
			}
		}

		/**
			* Установить домен по умолчанию
			* @param Integer $domain_id id домена, который нужно сделать доменом по умолчанию
		*/
		public function setDefaultDomain($domain_id) {
			if($this->isExists($domain_id)) {
				cacheFrontend::getInstance()->flush();
				
				$sql = "UPDATE cms3_domains SET is_default = '0' WHERE is_default = '1'";
				l_mysql_query($sql);

				if($def_domain = $this->getDefaultDomain()) {
					$def_domain->setIsDefault(false);
					$def_domain->commit();
				}

				$this->def_domain = $domain_id;
				$this->def_domain->setIsDefault(true);
				$this->def_domain->commit();
			} else return false;
		}

		/**
			* Удалить домен из системы
			* @param Integer $domain_id id домена, который необходимо удалить
			* @return Boolean true, если удалось удалить домен
		*/
		public function delDomain($domain_id) {
			if($this->isExists($domain_id)) {
				$domain = $this->getDomain($domain_id);
				$domain->delAllMirrows();
				cacheFrontend::getInstance()->flush();

				if($domain->getIsDefault()) {
					$this->def_domain = false;
				}

				unset($domain);
				unset($this->domains[$domain_id]);


				$sql = "DELETE FROM cms3_hierarchy WHERE domain_id = '{$domain_id}'";
				l_mysql_query($sql);

				$sql = "DELETE FROM cms3_domains WHERE id = '{$domain_id}'";
				l_mysql_query($sql);
				
				return true;
			} else throw new coreException("Domain #{$domain_id} doesn't exists.");
		}

		/**
			* Получить экземпляр домена (класс domain)
			* @param Integer $domain_id id домена, который необходимо получить
			* @return domain экземпляр домен или false в случае неудачи
		*/
		public function getDomain($domain_id) {
			return $this->isExists($domain_id) ? $this->domains[$domain_id] : false;
		}

		/**
			* Получить домен по умолчанию
			* @return domain экземпляр класса domain или false
		*/
		public function getDefaultDomain() {
			return ($this->def_domain) ? $this->def_domain : false;
		}

		/**
			* Получить список доменов в системе
			* @return Array массив, состоящий из экземпляров класса domain
		*/
		public function getList() {
			return $this->domains;
		}

		/**
			* Проверить, существует ли домен $domain_id в системе
			* @param id $domain_id домена
			* @return Boolean true, если домен существует
		*/
		public function isExists($domain_id) {
			return (bool) array_key_exists($domain_id, $this->domains);
		}

		/**
			* Получть id домена по его хосту (адресу домена)
			* @param String $host адрес домена
			* @param Boolean $user_mirrow=true если параметр равен true, то поиск будет осуществляться в т.ч. и во всех зеркалах домена
			* @return Integer id домена, либо false если домен с таким хостом не найден
		*/
		public function getDomainId($host, $use_mirrows = true) {
			foreach($this->domains as $domain) {
				if($domain->getHost() == $host) {
					return $domain->getId();
				} else {
					if($use_mirrows) {
						$mirrows = $domain->getMirrowsList();
						foreach($mirrows as $domainMirrow) {
							if($domainMirrow->getHost() == $host) {
								return $domain->getId();
							}
						}
					}
				}
			}
			return false;
		}

		/**
			* Загружает список доменов из БД в коллекцию
			* @return Boolean true, если операция прошла успешно
		*/
		private function loadDomains() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$domainIds = $cacheFrontend->loadData('domains_list');
			if(!is_array($domainIds)) {
				$sql = "SELECT id, host, is_default, default_lang_id FROM cms3_domains";
				$result = l_mysql_query($sql);

				$domainIds = array();
				while(list($domain_id) = $row = mysql_fetch_row($result)) {
				    $domainIds[$domain_id] = $row;
				}
				$cacheFrontend->saveData('domains_list', $domainIds, 3600);
			} else $row = false;
			
			foreach($domainIds as $domain_id => $row) {
				$domain = $cacheFrontend->load($domain_id, 'domain');
				if($domain instanceof domain == false) {
					try {
						$domain = new domain($domain_id, $row);
					} catch(privateException $e) {
						continue;
					}

					$cacheFrontend->save($domain, 'domain');
				}
				$this->domains[$domain_id] = $domain;

				if($domain->getIsDefault()) {
					$this->def_domain = $domain;
				}
			}

			return true;
		}
	}


/**
	* Предоставляет доступ к свойствам шаблона дизайна
*/
	class template extends umiEntinty implements iUmiEntinty, iTemplate {
		private $filename, $title, $domain_id, $lang_id, $is_default;
		protected $store_type = "template";

		/**
			* Получить название файла шаблона дизайна
			* @return String название файла шаблона дизайна
		*/
		public function getFilename() {
			return $this->filename;
		}

		/**
			* Получить название шаблона дизайна
			* @return String название шаблона дизайна
		*/
		public function getTitle() {
			return $this->title;
		}

		/**
			* Получить id домена, к которому привязан шаблон
			* @return Integer id домена (класс domain)
		*/
		public function getDomainId() {
			return $this->domain_id;
		}

		/**
			* Получить id языка, к которому привязан шаблон
			* @return Integer id язык (класс lang)
		*/
		public function getLangId() {
			return $this->lang_id;
		}

		/**
			* Узнать, является ли данный шаблон шаблоном по умолчанию
			* @return Boolean true, если шаблон является шаболоном по умолчанию
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Изменить название файла шаблона
			* @param String $filename название файла шаблона
		*/
		public function setFilename($filename) {
			$this->filename = $filename;
			$this->setIsUpdated();
		}

		/**
			* Изменить название шаблона дизайна
			* @param String $title название шаблона
		*/
		public function setTitle($title) {
			$this->title = $title;
			$this->setIsUpdated();
		}

		/**
			* Изменить домен, к которому привязан шаблон дизайна
			* @param Integer $domain_id id домена (класс domain)
			* @return Boolean true в случае успеха
		*/
		public function setDomainId($domain_id) {
			$domains = domainsCollection::getInstance();
			if($domains->isExists($domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		/**
			* Изменить язык, к которому привязан шаблон
			* @param Integer $lang_id id языка (класс lang)
			* @return Boolean true в случае успеха
		*/
		public function setLangId($lang_id) {
			$langs = langsCollection::getInstance();
			if($langs->isExists($lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		/**
			* Изменить флаг "по умолчанию"
			* @param Boolean $is_default значение флага "по умолчанию"
		*/
		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}
		
		/**
			* Получить список страниц, которые используют этот шаблон
			* @return Array массив, в котором каждое значение тоже массив, где 0 индекс - id страницы (класс umiHierarchyElement), 1 индекс - название страницы
		*/
		public function getUsedPages() {
			$sql = "SELECT h.id, o.name FROM cms3_hierarchy h, cms3_objects o WHERE h.tpl_id = '{$this->id}' AND o.id = h.obj_id AND h.is_deleted = '0' AND h.domain_id = '{$this->domain_id}'";
			$result = l_mysql_query($sql);

			$res = array();
			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[] = Array($id, $name);
			}
			return $res;
		}
		
		/**
			* Привязать страницы сайта к шаблону
			* @param Array $pages массив, в котором каждое значение тоже массив, где 0 индекс - id страницы (класс umiHierarchyElement), 1 индекс - название страницы
			* @return Boolean true в случае, если не возникло ошибок
		*/
		public function setUsedPages($pages) {
			if(is_null($pages)) return false;

			$default_tpl_id = templatesCollection::getInstance()->getDefaultTemplate($this->domain_id, $this->lang_id)->getId();

			$sql = "UPDATE cms3_hierarchy SET tpl_id = '{$default_tpl_id}' WHERE tpl_id = '{$this->id}' AND is_deleted = '0' AND domain_id = '{$this->domain_id}'";
			l_mysql_query($sql);
			
			$cacheFrontend = cacheFrontend::getInstance();
			$cacheFrontend->flush();
			
			$hierarchy = umiHierarchy::getInstance();
			
			if(!is_array($pages)) return false;
			
			if(is_array($pages)&&!empty($pages)) {
                foreach($pages as $element_id) {
				    $page = $hierarchy->getElement($element_id);
				    if($page instanceof iUmiHierarchyElement) {
					    $page->setTplId($this->id);
					    $page->commit();
					    unset($page);
					    $hierarchy->unloadElement($element_id);
				    }
			    }
            }
			return true;
		}

		/**
			* Загрузить информацию о шаблоне из БД
			* @return Boolean true, если не произошло ошибки
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, filename, title, domain_id, lang_id, is_default FROM cms3_templates WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				$row = mysql_fetch_row($result);
			}

			if(list($id, $filename, $title, $domain_id, $lang_id, $is_default) = $row) {
				$this->filename = $filename;
				$this->title = $title;
				$this->domain_id = (int) $domain_id;
				$this->lang_id = (int) $lang_id;
				$this->is_default = (bool) $is_default;

				return true;
			} else return false;
		}

		/**
			* Сохранить изменения в БД
			* @return Boolean true, если не возникло ошибки
		*/
		protected function save() {
			$filename = self::filterInputString($this->filename);
			$title = self::filterInputString($this->title);
			$domain_id = (int) $this->domain_id;
			$lang_id =  (int) $this->lang_id;
			$is_default = (int) $this->is_default;

			$sql = "UPDATE cms3_templates SET filename = '{$filename}', title = '{$title}', domain_id = '{$domain_id}', lang_id = '{$lang_id}', is_default = '{$is_default}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			return true;
		}
	}


/**
	* Управляет шаблонами дизайна (класс template) в системе.
	* Синглтон, экземпляр коллекции можно получить через статический метод getInstance()
*/
	class templatesCollection extends singleton implements iSingleton, iTemplatesCollection {
		private $templates = Array(), $def_template;

		/**
			* Конструктор, при вызове загружает список шаблонов
		*/
		protected function __construct() {
			$this->loadTemplates();
		}

		/**
			* Получить экземпляр коллекции
			* @return templatesCollection экземпляр класса templatesCollection
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Добавить новый шаблон дизайна (класс template)
			* @param String $filename название файла, который содержит шаблон дизайна
			* @param String $title название шаблона
			* @param Integer $domain_id=false id домена (класс domain), для которого создается шаблон. Если не указан, используется домен по умолчанию
			* @param Integer $lang_id=false id языка (класс lang), для которого создается шаблон. Если не указан, используется язык по умолчанию
			* @param Boolean $is_default=false если true, то шаблон станет шаблоном по умолчанию для комбинации домена /языка $domain_id/$lang_id
			* @return Integer id созданного шаблона
		*/
		public function addTemplate($filename, $title, $domain_id = false, $lang_id = false, $is_default = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
		
			$domains = domainsCollection::getInstance();
			$langs = langsCollection::getInstance();

			if(!$domains->isExists($domain_id)) {
				if($domains->getDefaultDomain()) {
					$domain_id = $domains->getDefaultDomain()->getId();
				} else {
					return false;
				}
			}

			if(!$langs->isExists($lang_id)) {
				if($langs->getDefaultLang()) {
					$lang_id = $langs->getDefaultLang()->getId();
				} else {
					return false;
				}
			}

			$sql = "INSERT INTO cms3_templates VALUES()";
			$result = l_mysql_query($sql);

			$template_id = mysql_insert_id();

			$template = new template($template_id);
			$template->setFilename($filename);
			$template->setTitle($title);
			$template->setDomainId($domain_id);
			$template->setLangId($lang_id);
			$template->setIsDefault($is_default);

			if($is_default) {
				$this->setDefaultTemplate($template_id);
			}
			$template->commit();


			$this->templates[$template_id] = $template;

			return $template_id;
		}

		/**
			* Установить шаблон шаблоном по умолчанию для комбинации домена/языка
			* @param Integer $domain_id=false id домена (класс domain)
			* @param Integer $lang_id=false id языка (класс lang)
			* @return Boolean true, если не возникло ошибок
		*/
		public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false) {
			if($domain_id == false) $domain_id = domainsCollection::getInstance()->getDefaultDomain()->getId();	
			if($lang_id ==false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			
			if(!$this->isExists($template_id)) {
				return false;
			}
			
			$templates = $this->getTemplatesList($domain_id,$lang_id);
			foreach ($templates as $template) {
				if($template_id == $template->getId()) {
					$template->setIsDefault(true);					
				}
				else {
					$template->setIsDefault(false);
				}
				$template->commit();
			}
			return true;
			
			if(!($template = $this->getTemplate($templateId))) {
				return false;
			}

			if($this->def_template) {
				$this->def_template->setIsDefault(false);
				$this->def_template->commit();
			}

			$this->def_template = $template;
			$this->def_template->setIsDefault(true);
			$this->def_template->commit();

			return true;
		}

		/**
			* Удалить шаблон дизайна
			* @param Integer $template_id id шаблона дизайна
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delTemplate($template_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
		
			if($this->isExists($template_id)) {
				if($this->templates[$template_id]->getIsDefault()) {
					unset($this->def_template);
				}
				unset($this->templates[$template_id]);

				$o_deftpl = $this->getDefaultTemplate();
				if (!$o_deftpl || $o_deftpl->getId() == $template_id) return false;

				$upd_qry = "UPDATE cms3_hierarchy SET tpl_id = '".$o_deftpl->getId()."' WHERE tpl_id='{$template_id}'";
				l_mysql_query($upd_qry);

				$sql = "DELETE FROM cms3_templates WHERE id = '{$template_id}'";
				l_mysql_query($sql);
				
				return true;

			} else return false;
		}

		/**
			* Получить список всех шаблонов дизайна для комбинации домен/язык
			* @param Integer $domain_id id домена
			* @param Integer $lang_id id  языка
			* @return Array массив, состоящий из экземпляров класса template
		*/
		public function getTemplatesList($domain_id, $lang_id) {
			$res = array();

			foreach($this->templates as $template) {
				if($template->getDomainId() == $domain_id && $template->getLangId() == $lang_id) {
					$res[] = $template;
				}
			}

			return $res;
		}

		/**
			* Получить шаблон дизайна по умолчанию для комбинации домен/язык
			* @param Integer $domain_id=false id домена. Если не указан, берется домен по умолчанию.
			* @param Integer $lang_id=false id языка. Если не указан, берется язык по умолчанию.
			* @return template экземпляр класса template, либо false если шаблон дизайна не найден.
		*/
		public function getDefaultTemplate($domain_id = false, $lang_id = false) {
			if($domain_id == false) $domain_id = cmsController::getInstance()->getCurrentDomain()->getId();	
			if($lang_id == false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$templates = $this->getTemplatesList($domain_id, $lang_id);
			foreach($templates as $template) {
				if($template->getIsDefault() == true) {
					return $template;
				}
			}
			
			//We have no default template, but something must be shown
			if(sizeof($templates)) {
				$first_template = $templates[0];
				$this->setDefaultTemplate($first_template->getId(), $domain_id, $lang_id);
				return $first_template;
			}
			return false;
		}

		/**
			* Получить шаблон дизайна по его id
			* @param Integer $template_id id шаблона дизайна
			* @return template шаблон дизайна, экземпляр класса template, либо false если не существует шаблона с id $template_id
		*/
		public function getTemplate($template_id) {
			return ($this->isExists($template_id)) ? $this->templates[$template_id] : false;
		}

		/**
			* Проверить, существует ли шаблон дизайна с id $template_id
			* @param Integer $template_id id шаблона дизайна
			* @return Boolean true, если шаблон существует
		*/
		public function isExists($template_id) {
			return (bool) array_key_exists($template_id, $this->templates);
		}

		/**
			* Загрузить список всех шаблонов дизайна в системе из БД
			* @return Boolean false, если возникла ошибка
		*/
		private function loadTemplates() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$templateIds = $cacheFrontend->loadData('templates_list');
			if(!is_array($templateIds)) {
				$sql = "SELECT id, filename, title, domain_id, lang_id, is_default FROM cms3_templates";
				$result = l_mysql_query($sql);
				$templateIds = array();
				while(list($template_id) = $row = mysql_fetch_row($result)) {
					$templateIds[$template_id] = $row;
				}
				$cacheFrontend->saveData('templates_list', $templateIds, 3600);
			} else $row = false;

			foreach($templateIds as $template_id => $row) {
				 $template = $cacheFrontend->load($template_id, "template");
				if($template instanceof template == false) {
					try {
						$template = new template($template_id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($template, "template");
				}
				$this->templates[$template_id] = $template;

				if($template->getIsDefault()) {
					$this->def_template = $template;
				}
			}
			return true;
		}
	}


/**
	* Базовый тип, используется:
	* 1. Для связывание страниц с соответствующим обработчиком (модуль/метод)
	* 2. Для категоризации типов данных
	* В новой терминологии getName()/getExt() значило бы getModule()/getMethod() соответственно
*/
	class umiHierarchyType extends umiEntinty implements iUmiEntinty, iUmiHierarchyType {
		private $name, $title, $ext;
		protected $store_type = "element_type";

		/**
			* Получить название модуля, отвечающего за этот базовый тип
			* @return String название модуля
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название базового типа
			* @return String название типа
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}
		
		public function getModule() {
			return $this->getName();
		}
		
		public function getMethod() {
			return $this->getExt();
		}

		/**
			* Получить название метода, отвечающего за этот базовый тип
			* @return String название метода
		*/
		public function getExt() {
			return $this->ext;
		}

		/**
			* Изменить название модуля, отвечающего за этот базовый тип
			* @param String $name название модуля
		*/
		public function setName($name) {
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Изменить название базового типа
			* @param String $title название типа
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "hierarchy-type-");
			$this->title = $title;
			$this->setIsUpdated();
		}

		/**
			* Изменить название метода, отвечающего за этот базовый тип
			* @param String $ext название метода
		*/
		public function setExt($ext) {
			$this->ext = $ext;
			$this->setIsUpdated();
		}

		/**
			* Загрузить информацию о базовом типа из БД
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, ext FROM cms3_hierarchy_types WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $ext) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->ext = $ext;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Сохранить внесенные изменения в БД
		*/
		protected function save() {
			$name = self::filterInputString($this->name);
			$title = self::filterInputString($this->title);
			$ext = self::filterInputString($this->ext);

			$sql = "UPDATE cms3_hierarchy_types SET name = '{$name}', title = '{$title}', ext = '{$ext}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);

			return true;
		}
	}


/**
 * Класс-коллекция, который обеспечивает управление иерархическими типами
*/
	class umiHierarchyTypesCollection extends singleton implements iSingleton, iUmiHierarchyTypesCollection {
		private $types;

		protected function __construct() {
			$this->loadTypes();
		}

		/**
			* Получить экземпляр коллекции
			* @return umiHierarchyTypesCollection экземпляр коллекции
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Получить тип поего id
			* @param Integer $type_id id типа
			* @return umiHierarchyType иерархический тип (класс umiHierarchyType), либо false
		*/
		public function getType($type_id) {
			if($this->isExists($type_id)) {
				return $this->types[$type_id];
			} else {
				return false;
			}
		}

		/**
			* Получить иерархический тип по его модулю/методу
			* @param String $name модуль типа
			* @param String $ext = false метод типа
			* @return umiHierarchyType иерархический тип (класс umiHierarchyType), либо false
		*/
		public function getTypeByName($name, $ext = false) {
			if($name == 'content' and $ext == 'page') $ext = false;
			foreach($this->types as $type) {
				if($type->getName() == $name && !$ext) return $type;
				if($type->getName() == $name && $type->getExt() == $ext && $ext) return $type;
			}
			return false;
		}

		/**
			* Добавить новый иерархический тип
			* @param String $name модуль типа
			* @param String $title название типа
			* @param String $ext метод типа
			* @return Integer id иерархического типа (класс umiHierarchyType)
		*/
		public function addType($name, $title, $ext = "") {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			if($hierarchy_type  = $this->getTypeByName($name, $ext)) {
				$hierarchy_type->setTitle($title);
				return $hierarchy_type->getId();
			}

			$nameTemp = mysql_real_escape_string($name);
			$sql = "INSERT INTO cms3_hierarchy_types (name) VALUES('{$nameTemp}')";
			l_mysql_query($sql);

			$type_id = mysql_insert_id();

			$type = new umiHierarchyType($type_id);
			$type->setName($name);
			$type->setTitle($title);
			$type->setExt($ext);
			$type->commit();

			$this->types[$type_id] = $type;


			return $type_id;
		}

		/**
			* Удалить тип
			* @param Integer $type_id id иерархического типа, который нужно удалить
			* @return Boolean true, если тип удален успешно
		*/
		public function delType($type_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
		
			if($this->isExists($type_id)) {
				unset($this->types[$type_id]);

				$type_id = (int) $type_id;
				$sql = "DELETE FROM cms3_hierarchy_types WHERE id = '{$type_id}'";
				l_mysql_query($sql);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Проверить, существует ли иерархический тип с таким id
			* @param Integer $typeId id типа
			* @return Boolean true если тип существует
		*/
		public function isExists($typeId) {
			if($typeId === false) return false;
			return (bool) array_key_exists($typeId, $this->types);
		}


		private function loadTypes() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$hierarchyTypeIds = $cacheFrontend->loadData('hierarchy_types');
			if(!is_array($hierarchyTypeIds)) {
				$sql = "SELECT `id`, `name`, `title`, `ext` FROM `cms3_hierarchy_types` ORDER BY `name`, `ext`";
				$result = l_mysql_query($sql);
				$hierarchyTypeIds = array();
				while(list($id) = $row = mysql_fetch_row($result)) {
					$hierarchyTypeIds[$id] = $row;
				}
				$cacheFrontend->saveData('hierarchy_types', $hierarchyTypeIds, 3600);
			}
			
			foreach($hierarchyTypeIds as $id => $row) {
				$type = $cacheFrontend->load($id, 'element_type');
				if($type instanceof iUmiHierarchyType == false) {
					try {
						$type = new umiHierarchyType($id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($type, 'element_type');
				}
				$this->types[$id] = $type;
			}
			
			return true;
		}

		/**
			* Получить список всех иерархических типов
			* @return Array массив, где ключ это id типа, а значение экземпляр класса umiHierarchyType
		*/
		public function getTypesList() {
			return $this->types;
		}
	}


/**
	* Реализует доступ и управление свойствами страниц. Страницы это то, что в системе фигурирует в структуре сайта.
*/
	class umiHierarchyElement extends umiEntinty implements iUmiEntinty, iUmiHierarchyElement {
		private	$rel, $alt_name, $ord, $object_id,
			$type_id, $domain_id, $lang_id, $tpl_id,
			$is_deleted = false, $is_active = true, $is_visible = true, $is_default = false, $name,
			$update_time,
			$object,
			$is_broken = false;

		protected $store_type = "element";

		/**
			* Узнать, удалена ли страница в корзину или нет
			* @return Boolean true, если страница помещена в мусорную корзину, либо false если нет
		*/
		public function getIsDeleted() {
			return $this->is_deleted;
		}

		/**
			* Узнать, активна страница или нет
			* @return Boolean true если активна
		*/
		public function getIsActive() {
			return $this->is_active;
		}

		/**
			* Узнать, видима ли страница в меню или нет
			* @return Boolean true если страница может отображаться в меню сайта
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Получить id языка (класс lang), к которому привязана страница
			* @return Integer id языка
		*/
		public function getLangId() {
			return $this->lang_id;
		}

		/**
			* Получить id домена (класс domain), к которому привязана страница
			* @return Integer id домена
		*/
		public function getDomainId() {
			return $this->domain_id;
		}

		/**
			* Получить id шаблона дизайана (класс template), по которому отображаеся страница
			* @return Integer id шаблона дизайна (класс template)
		*/
		public function getTplId() {
			return $this->tpl_id;
		}

		/**
			* Получить id базового типа (класс umiHierarchyType), который определяет поведение страницы на сайте
			* @return Integer id базового типа (класс umiHierarchyType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}

		/**
			* Получить время последней модификации страницы
			* @return Integer дата в формате UNIX TIMESTAMP
		*/
		public function getUpdateTime() {
			return $this->update_time;
		}

		/**
			* Получить порядок страницы отосительно соседних страниц
			* @return Integer порядок страницы ()
		*/
		public function getOrd() {
			return $this->ord;
		}

		/**
			* Получить id родительской страницы. Deprecated: используйте метод umiHierarchyElement::getParentId()
			* @return Integer id страницы
		*/
		public function getRel() {
			return $this->rel;
		}

		/**
			* Получить псевдостатический адрес страницы, по которому строится ее адрес
			* @return String псевдостатический адрес
		*/
		public function getAltName() {
			return $this->alt_name;
		}

		/**
			* Получить флаг "по умолчанию" у страницы
			* @return Boolean флаг "по умолчанию"
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Получить объект (класс umiObject), который является источником данных для страницы
			* @return umiObject объект страницы (ее источник данных)
		*/
		public function getObject() {
			if(isset($this->object) && $this->object) {
				return $this->object;
			} else if(isset($this->object_id)) {
				$this->object = umiObjectsCollection::getInstance()->getObject($this->object_id);
				return $this->object;
			} else {
				return null;
			}
		}

		/**
			* Получить id родительской страницы.
			* @return Integer id страницы
		*/
		public function getParentId() {
			return $this->rel;
		}

		/**
			* Получить название страницы
			* @return String название страницы
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}
		
		/**
			* Изменить название страницы
			* @param String $name новое название страницы
		*/
		public function setName($name) {
			$res = $this->getObject()->setName($name);
			$this->setIsUpdated(true);
			return $res;
		}

		/**
			* Получить значение свойства $prop_name
			* @param String $prop_name строковой идентификатор свойства, значение которого нужно получить
			* @param Array $params = NULL дополнительные параметры (обычно не используется)
			* @return Mixed значение свойства. Тип возвращаемого значения зависит от типа поля
		*/
		public function getValue($prop_name, $params = NULL) {
			$object = $this->getObject();
			return $object ? $object->getValue($prop_name, $params) : false;
		}

		/**
			* Изменить значение свойства $prop_name на $prop_value
			* @param String $prop_name строковой идентификатор свойства, значение которого нужно изменить
			* @param Mixed $prop_value новое значение свойства. Тип аргумента зависит от типа поля
			* @return Boolean true, если не произошло ошибок
		*/
		public function setValue($prop_name, $prop_value) {
			if($object = $this->getObject()) {
				$result = $object->setValue($prop_name, $prop_value);
				$this->setIsUpdated(true);
				return $result;
			} else {
				return false;
			}
		}

		/**
			* Утановить флаг, означающий, что страница может быть видима в меню
			* @param Boolean $is_visible=true новое значение флага видимости
		*/
		public function setIsVisible($is_visible = true) {
			if ($this->is_visible !== ((bool)$is_visible)) {
				$this->is_visible = (bool) $is_visible;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить флаг активности
			* @param Boolean $is_active=true значение флага активности
		*/
		public function setIsActive($is_active = true) {
			if ($this->is_active !== ((bool)$is_active)) {
				$this->is_active = (bool) $is_active;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить флаг "удален", который сигнализирует о том, что страница помещена в корзину
			* @param Boolean $is_deleted=false значение флага удаленности
		*/
		public function setIsDeleted($is_deleted = false) {
			if ($this->is_deleted !== ((bool)$is_deleted)) {
				$this->is_deleted = (bool) $is_deleted;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить id базового типа (класс umiHierarchyType), который определяет поведение страницы на сайте
			* @param Integer $type_id id базового типа (класс umiHierarchyType)
		*/
		public function setTypeId($type_id) {
			if ($this->type_id !== ((int)$type_id)) {
				$this->type_id = (int) $type_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить id языка (класс lang), к которому привязана страница
			* @param Integer $lang_id id языка
		*/
		public function setLangId($lang_id) {
			if ($this->lang_id !== ((int)$lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить шаблон дизайна, по которому отображается страница на сайте
			* @param Integer $tpl_id id шаблона дизайна (класс template)
		*/
		public function setTplId($tpl_id) {
			if ($this->tpl_id !== ((int)$tpl_id)) {
				$this->tpl_id = (int) $tpl_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить домен (класс domain), к которому привязана страница
			* @param Integer $domain_id id домена (класс domain)
		*/
		public function setDomainId($domain_id) {
			$hierarchy = umiHierarchy::getInstance();
			$childs = $hierarchy->getChilds($this->id, true, true);

			foreach($childs as $child_id => $nl) {
				$child = $hierarchy->getElement($child_id, true, true);
				$child->setDomainId($domain_id);
				$hierarchy->unloadElement($child_id);
				unset($child);
			}

			if ($this->domain_id !== ((int)$domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить время последней модификации страницы
			* @param Integer $update_time=0 время последнего изменения страницы в формате UNIX TIMESTAMP. Если аргумент не передан, берется текущее время.
		*/
		public function setUpdateTime($update_time = 0) {
			if($update_time == 0) {
				$update_time = umiHierarchy::getTimeStamp();
			}
			if ($this->update_time !== ((int)$update_time)) {
				$this->update_time = (int) $update_time;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить номер порядка следования страницы в структуре относительно других страниц
			* @param Integer $ord порядковый номер
		*/
		public function setOrd($ord) {
			if ($this->ord !== ((int)$ord)) {
				$this->ord = (int) $ord;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить родителя страницы
			* @param Integer $rel id родительской страницы
		*/
		public function setRel($rel) {
			if ($this->rel !== ((int)$rel)) {
				$this->rel = (int) $rel;
				$this->setIsUpdated();
			}
		}

		/**
			* Изменить объект-источник данных страницы
			* @param umiObject $object экземпляр класса umiObject
			* @param $bNeedSetUpdated=true если true, то на объекте $object будет выполнен метод setIsUpdated() без параметров
		*/
		public function setObject(umiObject $object, $bNeedSetUpdated = true) {
			$this->object = $object;
			$this->object_id = $object->getId();
			if ($bNeedSetUpdated) $this->setIsUpdated();
		}

		/**
			* Изменить псевдостатический адрес, который участвует в формировании адреса страницы
			* @param $alt_name новый псевдостатический адрес
			* @param Boolean $auto_convert не указывайте этот параметр
		*/
		public function setAltName($alt_name, $auto_convert = true) {
			if(!$alt_name) {
				$alt_name = $this->getName();
			}
		
			if($auto_convert) {
				$alt_name = umiHierarchy::convertAltName($alt_name);
				if(!$alt_name) $alt_name = "_";
			}

			$sPrevAltname = $this->alt_name;

			$this->alt_name = $this->getRightAltName(umiObjectProperty::filterInputString($alt_name));
			if(!$this->alt_name) {
				$this->alt_name = $alt_name;
			}

			$sNewAltname = $this->alt_name;
			if ($sNewAltname !== $sPrevAltname) $this->setIsUpdated();
		}
		
		/**
			* При выгрузке страницы нужно выгружать связанный объект.
			* Вся память там.
		*/
		public function __destruct() {
			$objectId = $this->object_id;
			parent::__destruct();
			unset($this->object_id);
			unset($this->object);
			umiObjectsCollection::getInstance()->unloadObject($objectId);
		}

		/**
			* Разрешить коллизии в псевдостатическом адресе страницы
			* @param String $alt_name псевдостатический адрес страницы
			* @return String откорректированный результат
		*/
		private function getRightAltName($alt_name, $b_fill_cavities = false) {
			/*
				Не совсем предсказуемо для оператора
				работает с адресами-цифрами.
				При правках необходимо учитывать возможность наличия
				цифр в адресе (в частности - в его начале)
			*/
			if (empty($alt_name)) $alt_name = '1';

			if ($this->getRel() == 0 && !IGNORE_MODULE_NAMES_OVERWRITE) {
				// если элемент непосредственно под корнем и снята галка в настройках -
				// корректировать совпадение с именами модулей и языков
				$modules_keys = regedit::getInstance()->getList("//modules");
				foreach($modules_keys as $module_name) {
					if ($alt_name == $module_name[0]) { 
							$alt_name .= '1';
							break;
					}
				}
				if (langsCollection::getInstance()->getLangId($alt_name)) {
					$alt_name .= '1';
				}
			}

			$exists_alt_names =  array();

			preg_match("/^([a-z0-9_.-]*)(\d*?)$/U", $alt_name, $regs);
			$alt_digit = isset($regs[2]) ? $regs[2] : NULL;
			$alt_string = isset($regs[1]) ? $regs[1] : NULL;

			$lang_id = $this->getLangId();
			$domain_id = $this->getDomainId();

			$sql = "SELECT alt_name FROM cms3_hierarchy WHERE rel={$this->getRel()} AND id <> {$this->getId()} AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND alt_name LIKE '{$alt_string}%';";
			$result = l_mysql_query($sql);

			while(list($item) = mysql_fetch_row($result)) $exists_alt_names[] = $item;
			if (!empty($exists_alt_names) and in_array($alt_name,$exists_alt_names)){
				foreach($exists_alt_names as $next_alt_name){
					preg_match("/^([a-z0-9_.-]*)(\d*?)$/U", $next_alt_name, $regs);
					if (!empty($regs[2])) $alt_digit = max($alt_digit,$regs[2]);
				}
				++$alt_digit;
				//
				if ($b_fill_cavities) {
					$j = 0;
					for ($j = 1; $j<$alt_digit; $j++) {
						if (!in_array($alt_string . $j, $exists_alt_names)) {
							$alt_digit = $j;
							break;
						}
					}
				}
			}
			return $alt_string . $alt_digit;
		}

		/**
			* Изменить значение флаг "по умолчанию"
			* @param Boolean $is_default=true значение флага "по умолчанию"
		*/
		public function setIsDefault($is_default = true) {
			if ($this->is_default !== ((int)$is_default)) {
				cacheFrontend::getInstance()->flush();
				
				$this->is_default = (int) $is_default;
				$this->setIsUpdated();
			}
		}

		/**
			* Получить id поля по его строковому идентификатору
			* @param String $field_name строковой идентификатор поля
			* @return Integer id поля, либо false
		*/
		public function getFieldId($field_name) { //TODO: дезинтегрировать следующую строчку (c) lyxsus
			return umiObjectTypesCollection::getInstance()->getType($this->getObject()->getTypeId())->getFieldId($field_name);
		}

		/**
			* Загрузить информацию о страницы из БД
			* @return Boolean true если не возникло ошибок
		*/
		protected function loadInfo() {
			$sql = "SELECT h.rel, h.type_id, h.lang_id, h.domain_id, h.tpl_id, h.obj_id, h.ord, h.alt_name, h.is_active, h.is_visible, h.is_deleted, h.updatetime, h.is_default, o.name FROM cms3_hierarchy h, cms3_objects o WHERE h.id = '{$this->id}' AND o.id = h.obj_id";
			$result = l_mysql_query($sql, true);

			if(list($rel, $type_id, $lang_id, $domain_id, $tpl_id, $obj_id, $ord, $alt_name, $is_active, $is_visible, $is_deleted, $updatetime, $is_default, $name) = mysql_fetch_row($result)) {
				if(!$obj_id) {	//Really bad, foregin check didn't worked out :(, let's delete it itself
					umiHierarchy::getInstance()->delElement($this->id);
					$this->is_broken = true;
					return false;
				}
			
				$this->rel = (int) $rel;
				$this->type_id = (int) $type_id;
				$this->lang_id = (int) $lang_id;
				$this->domain_id = (int) $domain_id;
				$this->tpl_id = (int) $tpl_id;
				$this->object_id = (int) $obj_id;
				$this->ord = (int) $ord;
				$this->alt_name = $alt_name;
				$this->is_active = (bool) $is_active;
				$this->is_visible = (bool) $is_visible;
				$this->is_deleted = (bool) $is_deleted;
				$this->is_default = (bool) $is_default;

				$this->name = $name;	//read-only

				if (!$updatetime) {
					$updatetime = umiHierarchy::getTimeStamp();
				}
				$this->update_time = (int)$updatetime;

				return true;
			} else {
				$this->is_broken = true;
				return false;
			}
		}

		/**
			* Сохранить изменения в БД
			* @return Boolean true в случае успеха
		*/
		protected function save() {
			$rel = (int) $this->rel;
			$type_id = (int) $this->type_id;
			$lang_id = (int) $this->lang_id;
			$domain_id = (int) $this->domain_id;
			$tpl_id = (int) $this->tpl_id;
			$object_id = (int) $this->object_id;
			$ord = (int) $this->ord;
			$alt_name = self::filterInputString($this->alt_name);
			$is_active = (int) $this->is_active;
			$is_visible = (int) $this->is_visible;
			$is_deleted = (int) $this->is_deleted;
			$update_time = (int) $this->update_time;
			$is_default = (int) $this->is_default;


			if($is_default) {
				$sql ="UPDATE cms3_hierarchy SET is_default = '0' WHERE is_default = '1' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				l_mysql_query($sql);
			}

			$sql = "UPDATE cms3_hierarchy SET rel = '{$rel}', type_id = '{$type_id}', lang_id = '{$lang_id}', domain_id = '{$domain_id}', tpl_id = '{$tpl_id}', obj_id = '{$object_id}', ord = '{$ord}', alt_name = '{$alt_name}', is_active = '{$is_active}', is_visible = '{$is_visible}', is_deleted = '{$is_deleted}', updatetime = '{$update_time}', is_default = '{$is_default}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);

			if ($this->is_updated) {
				$search = searchModel::getInstance();
				if(PAGES_AUTO_INDEX) {
					if($this->getIsActive() && $this->getIsDeleted() == false) {
						$search->index_item($this->id);
					} else {
						$search->unindex_items($this->id);
					}
				}
			}

			return true;
		}

		/**
			* Изменить флаг измененности. Если экземпляр не помечен как измененный, метод commit() блокируется.
			* @param Boolean $is_updated=true значение флага измененности
		*/
		public function setIsUpdated($is_updated = true) {
			parent::setIsUpdated($is_updated);
			$this->update_time = time();
			umiHierarchy::getInstance()->addUpdatedElementId($this->id);
			if($this->rel) {
				umiHierarchy::getInstance()->addUpdatedElementId($this->rel);
			}
		}
		
		/**
			* Узнать, все ли впорядке с этим экземпляром
			* @return Boolean true, если все в порядке
		*/
		public function getIsBroken() {
			return $this->is_broken;
		}
		
		/**
			* Применить все изменения сделанные с этой страницей
		*/
		public function commit() {
			$object = $this->getObject();
			if($object instanceof umiObject) {
				$object->commit();
				
				$objectId = $object->getId();
				$hierarchy = umiHierarchy::getInstance();
				cacheFrontend::getInstance()->del($objectId, "object");
				
				$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
				foreach($virtuals as $virtualElementId) {
					cacheFrontend::getInstance()->del($virtualElementId, "element");
				}
			}
			parent::commit();
		}
		
		/**
			* Получить id типа данных (класс umiObjectType), к которому относится объект (класс umiObject) источник данных.
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getObjectTypeId() {
			return $this->getObject()->getTypeId();
		}
		
		/**
			* Получить базовый тип, к которому относится страница
			* @return umiHierarchyType базовый тип страницы
		*/
		public function getHierarchyType() {
			return umiHierarchyTypesCollection::getInstance()->getType($this->type_id);
		}
		
		/**
			* Получить id объекта (класс umiObject), который служит источником данных для страницы
			* @return Integer id объекта (класс umiObject)
		*/
		public function getObjectId() {
			return $this->object_id;
		}
		
		/**
			* Синоним метода getHierarchyType(). Этот метод является устаревшим.
			* @return umiHierarchyType
		*/
		protected function getType() {
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			return $hierarchyTypesCollection->getType($this->getTypeId());
		}
		
		/**
			* Получить название модуля базового типа страницы
			* @return String название модуля
		*/
		public function getModule() {
			return $this->getType()->getName();
		}

		/**
			* Получить название метода базового типа страницы
			* @return String название метода
		*/
		public function getMethod() {
			return $this->getType()->getExt();
		}
		
		/**
			* Удалить страницу
		*/
		public function delete() {
			umiHierarchy::getInstance()->delElement($this->id);
		}
		
		public function __sleep() {
			$vars = get_class_vars(get_class($this));
			$vars['object'] = NULL;
			return array_keys($vars);
		}
		
		
		public function __get($varName) {
			switch($varName) {
				case "id":			return $this->id;
				case "objectId":	return $this->object_id;
				case "name":		return $this->getName();
				case "altName":		return $this->getAltName();
				case "isActive":	return $this->getIsActive();
				case "isVisible":	return $this->getIsVisible();
				case "isDeleted":	return $this->getIsDeleted();
				case "xlink":		return 'upage://' . $this->id;
				case "link": {
					$hierarchy = umiHierarchy::getInstance();
					return $hierarchy->getPathById($this->id);
				}
				
				default:			return $this->getValue($varName);
			}
		}
		
		public function __set($varName, $value) {
			switch($varName) {
				case "id":			throw new coreException("Object id could not be changed");
				case "name":		return $this->setName($value);
				case "altName":		return $this->setAltName($value);
				case "isActive":	return $this->setIsActive($value);
				case "isVisible":	return $this->setIsVisible($value);
				case "isDeleted":	return $this->setIsDeleted($value);

				default:			return $this->setValue($varName, $value);
			}
		}
		
		public function beforeSerialize($reget = false) {
			static $object = null;
			if($reget && !is_null($object)) {
				$result = $object;
				$object = null;
				return $result;
			}
			$object = $this->object;
			$this->object = null;
		}
		
		public function afterSerialize() {
			$this->beforeSerialize(true);
		}
	};


/**
	* Предоставляет доступ к страницам сайта (класс umiHierarchyElement) и методы для управления структурой сайта.
	* Синглтон, экземпляр коллекции можно получить через статический метод getInstance()
*/
	class umiHierarchy extends singleton implements iSingleton, iUmiHierarchy {
		private $elements = array(),
			$objects, $langs, $domains, $templates;
			
		private $updatedElements = Array();
		private $autocorrectionDisabled = false;
		private $elementsLastUpdateTime = 0;
		private $bForceAbsolutePath = false;
		private $symlinks = Array();
		private $misc_elements = Array();

		/**
			* Конструктор
		*/
		protected function __construct() {
			$this->objects		=	umiObjectsCollection::getInstance();
			$this->langs		=	langsCollection::getInstance();
			$this->domains		=	domainsCollection::getInstance();
			$this->templates	=	templatesCollection::getInstance();
			
			if(regedit::getInstance()->getVal("//settings/disable_url_autocorrection")) {
				$this->autocorrectionDisabled = true;
			}
		}

		/**
			* Получить экземпляр коллекции
			* @return umiHierarchy экземпляр класса umiHierarchy
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Проверяет, существует ли страница (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id странциы
			* @return Boolean true если существует
		*/
		public function isExists($element_id) {
			if($this->isLoaded($element_id)) {
				return true;
			} else {
				$element_id = (int) $element_id;

				$sql = "SELECT id FROM cms3_hierarchy WHERE id = '{$element_id}'";
				$result = l_mysql_query($sql);

				list($count) = mysql_fetch_row($result);
				return (bool) $count;
			}
		}

		/**
			* Проверяет, загружена ли в память страница (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id странциы
			* @return Boolean true если экземпляр класса umiHierarchyElement с id $element_id уже загружен в память
		*/
		public function isLoaded($element_id) {
			if($element_id === false) {
				return false;
			}

			if(is_array($element_id)) {
				$is_loaded = true;
				
				foreach($element_id as $celement_id) {
					if(!array_key_exists($celement_id, $this->elements)) {
						$is_loaded = false;
						break;
					}
				}
				
				return $is_loaded;
			} else {
				return (bool) array_key_exists($element_id, $this->elements);
			}
		}

		/**
			* Получить экземпляр страницы (класс umiHierarchyElement) с id $element_id
			* @param Integer $element_id id страницы
			* @param Boolean $ignorePermissions=false игнорировать права доступа при получении экземпляра страницы
			* @param Boolean $ignoreDeleted=false игнорировать состояние удаленности (т.е. возможность получить удаленную страницу)
			* @return umiHierarchyElement экземпляр страницы, либо false если нельзя получить экземпляр
		*/
		public function getElement($element_id, $ignorePermissions = false, $ignoreDeleted = false) {
			if(!$element_id) return false;
			if(!$ignorePermissions && !$this->isAllowed($element_id)) return false;
			$cacheFrontend = cacheFrontend::getInstance();

			if($this->isLoaded($element_id)) {
				return $this->elements[$element_id];
			} else {
				$element = $cacheFrontend->load($element_id, "element");
				if($element instanceof iUmiHierarchyElement == false) {
					try {
						$element = new umiHierarchyElement($element_id);

						$cacheFrontend->save($element, "element");
					} catch (privateException $e) {
						return false;
					}
				}
				$this->misc_elements[] = $element_id;

				
				if(is_object($element)) {
					if($element->getIsBroken()) return false;
					if($element->getIsDeleted() && !$ignoreDeleted) return false;

					$this->pushElementsLastUpdateTime($element->getUpdateTime());
					$this->elements[$element_id] = $element;
					return $this->elements[$element_id];
				} else return false;
			}
		}

		/**
			* Удалить страницу с id $element_id
			* @param Integer $element_id id страницы
			* @return Boolean true, если удалось удалить страницу
		*/
		public function delElement($element_id) {
			$this->disableCache();
			$cacheFrontend = cacheFrontend::getInstance();
			$permissions = permissionsCollection::getInstance();
			
			$this->addUpdatedElementId($element_id);
			$this->forceCacheCleanup();
			
			if(!$permissions->isAllowedObject($permissions->getUserId(), $element_id)) return false;

			if($element = $this->getElement($element_id)) {
				$sql = "SELECT id FROM cms3_hierarchy FORCE INDEX(rel) WHERE rel = '{$element_id}'";
				$result = l_mysql_query($sql);
					
				while(list($child_id) = mysql_fetch_row($result)) {
					$child_element = $this->getElement($child_id, true, true);
					$this->delElement($child_id);
					$cacheFrontend->del($child_id, "element");
				}


				$element->setIsDeleted(true);
				$element->commit();
				unset($this->elements[$element_id]);
				
				$cacheFrontend->del($element_id, "element");
				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать виртуальную копию (подобие symlink в файловых системах) страницы $element_id
			* @param Integer $element_id id страницы, которую необходимо скопировать
			* @param Integer $rel_id id страницы, которая будет являться родителем созданной копии
			* @param Boolean $copySubPages=false если у копируемой страницы есть потомки, то если true они будут скопированы рекурсивно
			* @return Integer id новой виртуальной копии страницы, либо false
		*/
		public function copyElement($element_id, $rel_id, $copySubPages = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			$this->misc_elements[] = $rel_id;
			$this->misc_elements[] = $element_id;
			
			$this->forceCacheCleanup();
		
			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				$rel_id = (int) $rel_id;
				$timestamp = self::getTimeStamp();
				
				if($element = $this->getElement($element_id)) {
					$this->misc_elements[] = $element->getParentId();
				}
				
				$res = mysql_fetch_array(l_mysql_query('SELECT MAX(ord) FROM cms3_hierarchy', true));
				$ord = $res[0]+1;
				$sql = <<<SQL

INSERT INTO cms3_hierarchy
	(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
		SELECT '{$rel_id}', type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, '{$timestamp}', '{$ord}'
				FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1
SQL;
				l_mysql_query($sql);

				$old_element_id = $element_id;
				$element_id = mysql_insert_id();

				//Copy permissions

				$sql = <<<SQL

INSERT INTO cms3_permissions
	(level, owner_id, rel_id)
		SELECT level, owner_id, '{$element_id}' FROM cms3_permissions WHERE rel_id = '{$old_element_id}'

SQL;
				l_mysql_query($sql);


				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();

					$this->buildRelationNewNodes($element_id);

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->copyElement($child_id, $element_id, true);
						}
					}
					
					$this->misc_elements[] = $element_id;

					return $element_id;
				} else return false;
			} else return false;
		}


		/**
			* Создать копию страницы $element_id вместе со всеми данными
			* @param Integer $element_id id страницы, которую необходимо скопировать
			* @param Integer $rel_id id страницы, которая будет являться родителем созданной копии
			* @param Boolean $copySubPages=false если у копируемой страницы есть потомки, то если true они будут скопированы рекурсивно
			* @return Integer id новой копии страницы, либо false
		*/
		public function cloneElement($element_id, $rel_id, $copySubPages = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			$this->misc_elements[] = $rel_id;
			$this->misc_elements[] = $element_id;
			
			$this->forceCacheCleanup();
		
			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				if($element = $this->getElement($element_id)) {
					$ord = (int) $element->getOrd();
				}
				$this->misc_elements[] = $element->getParentId();
				
				$res = mysql_fetch_array(l_mysql_query('SELECT MAX(ord) FROM cms3_hierarchy', true));
				$ord = $res[0] + 1;

				$object_id = $element->getObject()->getId();

				$sql = <<<SQL
INSERT INTO cms3_objects
	(name, is_locked, type_id, owner_id)
		SELECT name, is_locked, type_id, owner_id
			FROM cms3_objects
				WHERE id = '{$object_id}'
SQL;
				l_mysql_query($sql);

				$new_object_id = mysql_insert_id();

				$object_type = umiObjectsCollection::getInstance()->getObject($object_id)->getTypeId();
				$table_content = umiBranch::getBranchedTableByTypeId($object_type);

				$sql = <<<SQL
INSERT INTO {$table_content}
	(field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, obj_id)
		SELECT field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, '{$new_object_id}'
			FROM {$table_content}
				WHERE obj_id = '{$object_id}'
SQL;
				l_mysql_query($sql);

				$timestamp = self::getTimeStamp();

				$sql = <<<SQL

INSERT INTO cms3_hierarchy
	(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
		SELECT '{$rel_id}', type_id, lang_id, domain_id, tpl_id, '{$new_object_id}', alt_name, is_active, is_visible, is_deleted, '{$timestamp}', '{$ord}'
				FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1
SQL;
				l_mysql_query($sql);


				$old_element_id = $element_id;

				$element_id = mysql_insert_id();


				//Copy permissions
				$sql = <<<SQL

INSERT INTO cms3_permissions
	(level, owner_id, rel_id)
		SELECT level, owner_id, '{$element_id}' FROM cms3_permissions WHERE rel_id = '{$old_element_id}'

SQL;
				l_mysql_query($sql);

				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();
					
					$this->buildRelationNewNodes($element_id);

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->cloneElement($child_id, $element_id, true);
						}
					}
					
					$this->misc_elements[] = $element_id;

					return $element_id;
				} else  return false;
			}
		}

		/**
			* Получить список удаленных страниц (страниц в корзине)
			* @return Array массив, состоящий из id удаленных страниц
		*/
		public function getDeletedList() {
			$res = array();

			$sql = <<<SQL
SELECT id, rel FROM cms3_hierarchy WHERE is_deleted = '1' ORDER BY updatetime DESC
SQL;
			$result = l_mysql_query($sql);


			$tmp = array();
			while(list($id, $rel) = mysql_fetch_row($result)) {
				if(array_key_exists($rel, $tmp)) {
					continue;
				}

				if(array_key_exists($id, $res)) {
					unset($res[$tmp[$id]]);
				}

				$res[$id] = $id;

				$tmp[$id] = $rel;
			}

			return array_values($res);
		}

		/**
			* Восстановить страницу из корзины
			* @param id $element_id страницы, которую необходимо восстановить из корзины
			* @return Boolean true, если удалось
		*/
		public function restoreElement($element_id) {
			$this->disableCache();
		
			if($element = $this->getElement($element_id, false, true)) {
				$element->setIsDeleted(false);
				$element->setAltName($element->getAltName());
				$element->commit();

				$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$element_id}'";
				$result = l_mysql_query($sql);
					
				while(list($child_id) = mysql_fetch_row($result)) {
					$child_element = $this->getElement($child_id, true, true);
					$this->restoreElement($child_id);
				}
				return true;
			} else return false;
		}

		/**
			* Удалить из корзины страницу $element_id (и из БД)
			* @param Integer $element_id id страницы, которую будем удалять
			* @return Boolean true в случае успеха
		*/
		public function removeDeletedElement($element_id) {
			$this->disableCache();
		
			if($element = $this->getElement($element_id, true, true)) {
				if($element->getIsDeleted()) {
					$element_id = (int) $element_id;
					$object_id = $element->getObjectId();
					$objects = umiObjectsCollection::getInstance();
					
					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$element_id}'";
					$result = l_mysql_query($sql);
					
					while(list($child_id) = mysql_fetch_row($result)) {
						$child_element = $this->getElement($child_id, true, true);
						$child_element->setIsDeleted(true);
						$this->removeDeletedElement($child_id);
					}

					$sql = "DELETE FROM cms3_hierarchy WHERE id = '{$element_id}' LIMIT 1";
					l_mysql_query($sql);

					unset($element);
					unset($this->elements[$element_id]);
					
					//TODO: Make object delete here, if no hierarchy links exist.
					$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE obj_id = '{$object_id}'";
					$result = l_mysql_query($sql, true);
					
					if(list($c) = mysql_fetch_row($result)) {
						if($c == 0) {
							$objects->delObject($object_id);
						}
					}
					
					$this->earseRelationNodes($element_id);
					
					return true;
				} else return false;
			} else return false;
		}

		/**
			* Удалить страницы из корзины (т.е. и из БД)
			* @return Boolean true в случае успеха
		*/
		public function removeDeletedAll() {
			$this->disableCache();
			
			mysql_query("START TRANSACTION /* umiHierarchy::removeDeletedAll() */");
		
			$sql = "SELECT id FROM cms3_hierarchy WHERE is_deleted = '1'";
			$result = l_mysql_query($sql);
			
			while(list($element_id) = mysql_fetch_row($result)) {
				$this->removeDeletedElement($element_id);
			}
			
			mysql_query("COMMIT");
			
			return true;
		}

		/**
			* Получить id родительской страницы для $element_id
			* @param Integer $element_id id страницы
			* @return Integer id родительской страницы, либо false
		*/
		public function getParent($element_id) {
			$element_id = (int) $element_id;

			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$element_id}'";
			$result = l_mysql_query($sql);

			if(mysql_num_rows($result)) {
				list($parent_id) = mysql_fetch_row($result);
				
				$this->misc_elements[] = $parent_id;
				return (int) $parent_id;
			} else {
				return false;
			}
		}

		/**
			* Получить список всех родительских страниц
			* @param Integer $element_id id страницы, родителей которой необходимо получить
			* @param Boolean $include_self=false включить в результат саму страницу $element_id
			* @param Boolean $ignoreCache = false не использовать микрокеширование
			* @return Array массив, состоящий из id родиткельских страниц
		*/
		public function getAllParents($element_id, $include_self = false, $ignoreCache = false) {
			static $cache = Array();
			$cacheFrontend = cacheFrontend::getInstance();
			$element_id = (int) $element_id;
			$parents = array();
			
			$cacheData = $cacheFrontend->loadSql('hierarchy_parents');
			if(is_array($cacheData) && sizeof($cacheData)) {
				$cache = $cacheData;
			}
			
			if(!$ignoreCache && isset($cache[$element_id])) {
				$parents = $cache[$element_id];
			} else {
				$sql = "SELECT rel_id FROM cms3_hierarchy_relations WHERE child_id = '{$element_id}' ORDER BY id";
				$result = l_mysql_query($sql);
				
				while(list($parent_id) = mysql_fetch_row($result)) {
					$parents[] = (int) $parent_id;
				}
				$cache[$element_id] = $parents;
				$cacheFrontend->saveSql('hierarchy_parents', $cache, 120);
			}
			if($include_self) {
				$parents[] = (int) $element_id;
			}
			return $parents;
		}

		/**
			* Получить список дочерних страниц по отношению к $element_id
			* @param Integer $element_id id страницы, у которой нужно взять всех потомков
			* @param Boolean $allow_unactive=true если true, то в результат будут включены неактивные страницы
			* @param Boolean $allow_unvisible=true если true, то в результат будут включены невидимые в меню страницы
			* @param Integer $depth=0 глубина поиска
			* @param Boolean $hierarchy_type_id=false включить в результат только страницы с указанным id базового типа (umiHierarchyType)
			* @param Integer $domainId=false указать id домена (актуально если ишем от корня: $element_id = 0)
			* @return Array рекурсивный ассоциотивный массив, где ключ это id страницы, значение - массив детей
		*/
		public function getChilds($element_id, $allow_unactive = true, $allow_unvisible = true, $depth = 0, $hierarchy_type_id = false, $domainId = false) {
			$cacheFrontend = cacheFrontend::getInstance();
			$cmsController = cmsController::getInstance();
			
			$element_id = (int) $element_id;
			$allow_unactive = (int) $allow_unactive;
			$allow_unvisible = (int) $allow_unvisible;
			$hierarchy_type_id = (int) $hierarchy_type_id;
			
			$lang_id = $cmsController->getCurrentLang()->getId();
			$domain_id = ($domainId) ? $domainId : $cmsController->getCurrentDomain()->getId();
			$domain_cond = ($element_id > 0) ? "" : " AND h.domain_id = '{$domain_id}'";
			
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$isUserSuperVisor = $permissions->isSv($userId);
			$permissionsSql = $permissions->makeSqlWhere($userId);
		
			$res = array();
			
			$s_element_id = ($element_id) ? "= '{$element_id}'" : "IS NULL";
			$sql = "SELECT hr.child_id, h.rel, cp.level FROM cms3_hierarchy_relations hr, cms3_permissions cp, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			if($isUserSuperVisor) {
				$sql = "SELECT hr.child_id, h.rel, 2 FROM cms3_hierarchy_relations hr, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			}
			
			
			if(!$allow_unactive)	$sql .= " AND h.is_active = '1'";
			if(!$allow_unvisible)	$sql .= " AND h.is_visible = '1'";
			if($hierarchy_type_id) $sql .= " AND h.type_id = '{$hierarchy_type_id}'";
			
			if(!$isUserSuperVisor) {
				$sql .= " AND (cp.rel_id = h.id AND {$permissionsSql} AND cp.level IN (1, 2))";
			}
			
			if($depth) {
				if($element_id) {
					$result = l_mysql_query("SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$element_id}");
					if(mysql_num_rows($result)) {
						list($level) = mysql_fetch_row($result);
						++$level;
					} else {
						return false;
					}
				} else {
					$level = $depth;
				}
				$sql .= " AND hr.level <= '{$level}'";
			}
			
			$sql .= " ORDER BY hr.level, h.ord";
			
			if($res = $cacheFrontend->loadSql($sql)) {
				foreach($res[1] as $elementId => $level) {
					$permissions->pushElementPermissions($elementId, $level);
				}
				return $res[0];
			}
			
			$result = l_mysql_query($sql);
			
			$flat_childs = $perms_list = $res = array();
			while(list($child_id, $rel_id, $level) = mysql_fetch_row($result)) {
				$permissions->pushElementPermissions($child_id, $level);
				
				$flat_childs[$child_id] = array();
				if($rel_id == $element_id) {
					$res[$child_id] = &$flat_childs[$child_id];
				}
				if(isset($flat_childs[$rel_id])) {
					$flat_childs[$rel_id][$child_id] = &$flat_childs[$child_id];
				}
				$perms_list[$child_id] = $level;
			}
			
			$cacheFrontend->saveSql($sql, array($res, $perms_list), 60);
			return $res;
		}
		
		/**
			* Получить количество дочерних страниц по отношению к $element_id
			* @param Integer $element_id id страницы, у которой нужно взять всех потомков
			* @param Boolean $allow_unactive=true если true, то в результат будут включены неактивные страницы
			* @param Boolean $allow_unvisible=true если true, то в результат будут включены невидимые в меню страницы
			* @param Integer $depth=0 глубина поиска
			* @param Boolean $hierarchy_type_id=false включить в результат только страницы с указанным id базового типа (umiHierarchyType)
			* @param Integer $domainId=false указать id домена (актуально если ишем от корня: $element_id = 0)
			* @return Integer количество детей
		*/
		public function getChildsCount($element_id, $allow_unactive = true, $allow_unvisible = true, $depth = 0, $hierarchy_type_id = false, $domainId = false) {
			$element_id = (int) $element_id;
			$allow_unactive = (int) $allow_unactive;
			$allow_unvisible = (int) $allow_unvisible;
			$hierarchy_type_id = (int) $hierarchy_type_id;
			
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = ($domainId) ? $domainId : cmsController::getInstance()->getCurrentDomain()->getId();
			if($element_id) {
				$element = $this->getElement($element_id, true);
				if($element instanceof umiHierarchyElement) {
					$lang_id = $element->getLangId();
					$domain_id = $element->getDomainId();
				}
			}
			$domain_cond = ($element_id > 0) ? "" : " AND h.domain_id = '{$domain_id}'";
		
			$res = array();
			
			$s_element_id = ($element_id) ? "= '{$element_id}'" : "IS NULL";
			$sql = "SELECT COUNT(hr.child_id) FROM cms3_hierarchy_relations hr, cms3_hierarchy h WHERE hr.rel_id {$s_element_id} AND h.id = hr.child_id {$domain_cond} AND h.lang_id = '{$lang_id}' AND h.is_deleted = '0'";
			if(!$allow_unactive)	$sql .= " AND h.is_active = '1'";
			if(!$allow_unvisible)	$sql .= " AND h.is_visible = '1'";
			if($hierarchy_type_id) $sql .= " AND h.type_id = '{$hierarchy_type_id}'";
			if($depth) {
				if($element_id) {
					list($level) = mysql_fetch_row(l_mysql_query("SELECT level FROM cms3_hierarchy_relations WHERE child_id = '{$element_id}'"));
					$level = $depth + $level;
				} else {
					$level = 1;
				}
				$sql .= " AND hr.level <= '{$level}'";
			}
			
			$sql .= " ORDER BY hr.level, h.ord";
			
			$result = l_mysql_query($sql);
			
			if(mysql_num_rows($result)) {
				list($count) = mysql_fetch_row($result);
				return $count;
			} else {
				return false;
			}
		}

		/**
			* Переключить режим генерации урлов между относительным и полным (влючать адрес домена даже если он совпадает с текущим доменом)
			* @param Boolean $bIsForced=true true - режим полных урлов, false - обычный режим
			* @return Boolean предыдущее значение
		*/
		public function forceAbsolutePath($bIsForced = true) {
			$bOldValue = $this->bForceAbsolutePath;
			$this->bForceAbsolutePath = (bool) $bIsForced;
			return $bOldValue;
		}

		/**
			* Получить адрес страницы по ее id
			* @param id $element_id страницы, путь которой нужно получить
			* @param Boolean $ignoreLang=false не подставлять языковой префикс к адресу страницы
			* @param Boolean $ignoreIsDefaultStatus=false игнорировать статус страницы "по умолчанию" и сформировать для не полный путь
			* @param Boolean $ignoreCache игнорировать кеш
			* @return String адрес страницы
		*/
		public function getPathById($element_id, $ignoreLang = false, $ignoreIsDefaultStatus = false, $ignoreCache = false) {
			static $cache = array();
			$element_id = (int) $element_id;
			
			if(!$ignoreCache && isset($cache[$element_id . $ignoreLang . $this->bForceAbsolutePath])) return $cache[$element_id . $ignoreLang . $this->bForceAbsolutePath];
			
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$cacheFrontend = cacheFrontend::getInstance();
			$domains = domainsCollection::getInstance();
			$langs = langsCollection::getInstance();

			$pre_lang = $cmsController->pre_lang;

			if($element = $hierarchy->getElement($element_id, true)) {
				$current_domain = $cmsController->getCurrentDomain();
				$element_domain_id = $element->getDomainId();

				if(!$this->bForceAbsolutePath && $current_domain->getId() == $element_domain_id) {
					$domain_str = "";
				} else {
					$domain_str = "http://" . $domains->getDomain($element_domain_id)->getHost();
				}
				
				$element_lang_id = intval($element->getLangId());
				$element_lang = $langs->getLang($element_lang_id);

				$b_lang_default = ($element_lang_id === intval($cmsController->getCurrentDomain()->getDefaultLangId()));

				if(!$element_lang || $b_lang_default || $ignoreLang == true) {
					$lang_str = "";
				} else {					
					$lang_str = "/" . $element_lang->getPrefix();
				}
			
				if($element->getIsDefault() && !$ignoreIsDefaultStatus) {
					return $cache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = $domain_str . $lang_str . "/";
				}
			} else {
				return $cache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = "";
			}
			
			if($parents = $this->getAllParents($element_id, false, $ignoreCache)) {
				$path = $domain_str . $lang_str;
				$parents[] = $element_id;
				
				$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE id IN (" . implode(", ", $parents) . ")";
				
				$altNames = !$ignoreCache ? $cacheFrontend->loadSql($sql) : null;
				if(!is_array($altNames)) {
					$result = l_mysql_query($sql);
					
					$altNames = array();
					while(list($id, $altName) = mysql_fetch_row($result)) {
						$altNames[$id] = $altName;
					}
					$cacheFrontend->saveSql($sql, $altNames, 600);
				}
				
				$sz = sizeof($parents);
				for($i = 0; $i < $sz; $i++) {
					if(!$parents[$i]) continue;
					
					if(isset($altNames[$parents[$i]])) {
						$path .= "/" . $altNames[$parents[$i]];
					}
				}
				$path .= "/";
				return $cache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = $path;
			} else {
				return $cache[$element_id . $ignoreLang . $this->bForceAbsolutePath] = false;
			}
			
		}

		/**
			* Получить id страницы по ее адресу
			* @param String $element_path
			* @param Boolean $show_disabled = false
			* @param Integer $errors_count = 0 ссылка на переменную, в которую записывается количество несовпадений при разборе адреса
			* @return Integer id страницы, либо false
		*/
		public function getIdByPath($element_path, $show_disabled = false, &$errors_count = 0) {
			static $cache = array();
			$cacheFrontend = cacheFrontend::getInstance();
			$cmsController = cmsController::getInstance();
			$domains = domainsCollection::getInstance();

			if(isset($cache[$element_path])) return $cache[$element_path];
			
			if($id = $cacheFrontend->loadSql($element_path . "_path")) {
				return $id;
			}

			if($element_path == "/") {
				return $cache[$element_path] = $this->getDefaultElementId();
			}
			
			$element_path = trim($element_path, "\/ \n");
			$paths = split("/", $element_path);

			$lang_id = $cmsController->getCurrentLang()->getId();
			$domain_id = $cmsController->getCurrentDomain()->getId();

			$sz = sizeof($paths);
			$id = 0;
			for($i = 0; $i < $sz; $i++) {
				$alt_name = $paths[$i];
				$alt_name = l_mysql_real_escape_string($alt_name);
				
				if($i == 0) {
					if($element_domain_id = $domains->getDomainId($alt_name)) {
						$domain_id = $element_domain_id;
						continue;
					}
				}


				if($show_disabled) {
					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}' AND alt_name = '{$alt_name}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				} else {
					$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}' AND alt_name = '{$alt_name}' AND is_active='1' AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				}

				$result = l_mysql_query($sql);

				if(!mysql_num_rows($result)) {
					if($show_disabled) {
						$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE rel = '{$id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
					} else {
						$sql = "SELECT id, alt_name FROM cms3_hierarchy WHERE rel = '{$id}' AND is_active = '1' AND is_deleted = '0' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
					}
					$result = l_mysql_query($sql);

					$max = 0;
					$temp_id = 0;
					$res_id = 0;
					while(list($temp_id, $cstr) = mysql_fetch_row($result)) {
						if($this->autocorrectionDisabled) {
							if($alt_name == $cstr) {
								$res_id = $temp_id;
							}
						} else {
							$temp = umiHierarchy::compareStrings($alt_name, $cstr);
							if($temp > $max) {
								$max = $temp;
								$res_id = $temp_id;
								
								++$errors_count;
							}
						}
					}

					if($max > 75) {
						$id = $res_id;
					} else {
						return $cache[$element_path] = false;
					}
				} else {
					if(!(list($id) = mysql_fetch_row($result))) {
						return $cache[$element_path] = false;
					}
				}
			}
			
			$cacheFrontend->saveSql($element_path . "_path", $id, 3600);
			
			return $cache[$element_path] = $id;
		}

		/**
			* Добавить новую страницу
			* @param Interget $rel_id id родительской страницы
			* @param Integer $hierarchy_type_id id иерархического типа (umiHierarchyType)
			* @param String $name название старницы
			* @param String $alt_name псевдостатический адрес (если не передан, то будет вычислен из $name)
			* @param Integer $type_Id = false id типа данных (если не передан, то будет вычислен из $hierarchy_type_id)
			* @param Integer $domain_id = false id домена (имеет смысл только если $rel_id = 0)
			* @param Integer $lang_id = false id языковой версии (имеет смысл только если $rel_id = 0)
			* @param Integer $tpl_id = false id шаблона, по которому будет выводится страница
			* @return Integer id созданной страницы, либо false
		*/
		public function addElement($rel_id, $hierarchy_type_id, $name, $alt_name, $type_id = false, $domain_id = false, $lang_id = false, $tpl_id = false) {
			$this->disableCache();
		
			if($type_id === false) {
				if($hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id)) {
					$type_id = umiObjectTypesCollection::getInstance()->getBaseType($hierarchy_type->getName(), $hierarchy_type->getExt());
					
					if(!$type_id) {
						throw new coreException("There is no base object type for hierarchy type #{$hierarchy_type_id}");
						return false;
					}
				} else {
					throw new coreException("Wrong hierarchy type id given");
					return false;
				}
			}
			
			$parent = null;
			
			if($domain_id === false) {
				if($rel_id == 0) {
					$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
				} else {
					$parent = $this->getElement($rel_id, true, true);
					$domain_id = $parent->getDomainId();
				}
			}
			
			if($lang_id === false) {
				if($rel_id == 0) {
					$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
				} else {
					if(!$parent) $parent = $this->getElement($rel_id, true, true);
					$lang_id = $parent->getLangId();
				} 
			}
			
			if($tpl_id === false) {
				$tpl_id = $this->getDominantTplId($rel_id);
				if(!$tpl_id) {
					$tpl_id = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id)->getId();
				}
			}
			
			if($rel_id) {
				$this->addUpdatedElementId($rel_id);
			} else {
				$this->addUpdatedElementId($this->getDefaultElementId());
			}

			if($object_id = $this->objects->addObject($name, $type_id)) {

				$sql = "INSERT INTO cms3_hierarchy (rel, type_id, domain_id, lang_id, tpl_id, obj_id) VALUES('{$rel_id}', '{$hierarchy_type_id}', '{$domain_id}', '{$lang_id}', '{$tpl_id}', '{$object_id}')";
				l_mysql_query($sql);

				$element_id = mysql_insert_id();

				$element = $this->getElement($element_id, true);

				$element->setAltName($alt_name);


				$sql = "SELECT MAX(ord) FROM cms3_hierarchy WHERE rel = '{$rel_id}'";
				$result = l_mysql_query($sql);

				if(list($ord) = mysql_fetch_row($result)) {
					$element->setOrd( ($ord + 1) );
				}


				$element->commit();

				$this->elements[$element_id] = $element;

				$this->addUpdatedElementId($rel_id);
				$this->addUpdatedElementId($element_id);
				
				if($rel_id) {

					$parent_element = $this->getElement($rel_id);
					if($parent_element instanceof umiHierarchyElement) {
						$object_instances = $this->getObjectInstances($parent_element->getObject()->getId());
						
						if(sizeof($object_instances) > 1) {
							foreach($object_instances as $symlink_element_id) {
								if($symlink_element_id == $rel_id) continue;
								$this->symlinks[] = array($element_id, $symlink_element_id);
							}
						}
					}
				}
				$this->misc_elements[] = $element_id;
				
				$this->buildRelationNewNodes($element_id);
				return $element_id;
			} else {
				throw new coreException("Failed to create new object for hierarchy element");
				return false;
			}
		}


		/**
			* Получить идентификатор страницы со статусом "по умолчанию" (главная страница) для указанного домена и языка
			* @param Integer $lang_id = false id языковой версии, если не указан, берется текущий язык
			* @param Integer $domain_id = false id домена, если не указан, берется текущий домен
			* @return Integer id страницы по умолчанию, либо false
		*/
		
		public function getDefaultElementId($lang_id = false, $domain_id = false) {
			static $cache = array();
			$cacheFrontend = cacheFrontend::getInstance();
			$cmsController = cmsController::getInstance();
			
			if(empty($cache)) {
				$cacheData = $cacheFrontend->loadData('default_pages');
				if(is_array($cacheData)) {
					$cache = $cacheData;
				}
			}
			
			if($lang_id === false) $lang_id = $cmsController->getCurrentLang()->getId();
			if($domain_id === false) $domain_id = $cmsController->getCurrentDomain()->getId();
			
			if(isset($cache[$lang_id][$domain_id])) {
				return $cache[$lang_id][$domain_id];
			}
			
			$sql = "SELECT id FROM cms3_hierarchy WHERE is_default = '1' AND is_deleted='0' AND is_active='1' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
			$result = l_mysql_query($sql);

			if(list($element_id) = mysql_fetch_row($result)) {
				$cache[$lang_id][$domain_id] = $element_id;
				
				$cacheFrontend->saveData('default_pages', $cache, 3600);
				
				return $cache[$lang_id][$domain_id];
			} else {
				return false;
			}
		}


		public static function compareStrings($str1, $str2) {
			return	100 * (
				similar_text($str1, $str2) / (
					(strlen($str1) + strlen($str2))
				/ 2)
			);
		}

		/**
			* Конвертирует псевдостатический адрес в транслит и убирает недопостимые символы
			* @param String $alt_name псевдостатический url
			* @return String результат транслитерации
		*/
		public static function convertAltName($alt_name) {
			$alt_name = translit::convert($alt_name);
			$alt_name = preg_replace("/[\?\\\\&=]+/", "_", $alt_name);
			$alt_name = preg_replace("/[_\/]+/", "_", $alt_name);
			return $alt_name;
		}

		/**
			* Получить текущий UNIX TIMESTAMP
			* @return Integer текущий unix timestamp
		*/
		public static function getTimeStamp() {
			return time();
		}


		/**
			* Переместить страницу $element_id в страницу $rel_id перед страницей $before_id
			* @param Integer $element_id id перемещаемой страницы
			* @param Integer $rel_id id новой родительской страницы
			* @param Integer $before_id = false id страницы, перед которой нужно разместить страницу $element_id. Если false, поместить страницу в конец списка
			* @return Boolean true, если успешно
		*/
		public function moveBefore($element_id, $rel_id, $before_id = false) {
			$this->disableCache();
			
			if(!$this->isExists($element_id)) return false;
			
			$element = umiHierarchy::getInstance()->getElement($element_id);

			$lang_id = $element->getLangId();
			$domain_id = $element->getDomainId();
			$oldElementParentId = $element->getRel();

			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;

			$element->setRel($rel_id);
			$element->commit();

			// apply default template if need for all descendants
			$iCurrTplId = $element->getTplId();
			$arrTpls = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
			$bNeedChangeTpl = true;
			foreach($arrTpls as $oTpl) {
				if ($oTpl->getId() == $iCurrTplId) {
					$bNeedChangeTpl = false;
					break;
				}
			}

			if ($bNeedChangeTpl) {
				$oDefaultTpl = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
				if ($oDefaultTpl) {
					$iDefaultTplId = $oDefaultTpl->getId();

					// get all descendants id's
					$oSel = new umiSelection;
					$oSel->addHierarchyFilter($element_id, 100);

					$arrDescendantsIds = umiSelectionsParser::runSelection($oSel);
					$arrDescendantsIds[] = $element_id;
					$sDIds = implode(",", $arrDescendantsIds);

					$sql = "UPDATE cms3_hierarchy SET tpl_id = '{$iDefaultTplId}' WHERE id IN (".$sDIds.")";
				}
			}

			if($before_id) {
				$before_id = (int) $before_id;

				$sql = "SELECT ord FROM cms3_hierarchy WHERE id = '{$before_id}'";
				$result = l_mysql_query($sql, true);

				if(list($ord) = mysql_fetch_row($result)) {
					$ord = (int) $ord;
					$sql = "UPDATE cms3_hierarchy SET ord = (ord + 1) WHERE rel = '{$rel_id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}' AND ord >= {$ord}";
					l_mysql_query($sql);

					$sql = "UPDATE cms3_hierarchy SET ord = '{$ord}', rel = '{$rel_id}' WHERE id = '{$element_id}'";
					l_mysql_query($sql);

					$this->rewriteElementAltName($element_id);
					$this->rebuildRelationNodes($element_id);
					return true;
				} else return false;
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_hierarchy WHERE rel = '{$rel_id}' AND lang_id = '{$lang_id}' AND domain_id = '{$domain_id}'";
				$result = l_mysql_query($sql);

				if(list($ord) = mysql_fetch_row($result)) {
					++$ord;
				} else {
					$ord = 1;
				}

				$sql = "UPDATE cms3_hierarchy SET ord = '{$ord}', rel = '{$rel_id}' WHERE id = '{$element_id}'";
				l_mysql_query($sql);

				$this->rewriteElementAltName($element_id);
				$this->rebuildRelationNodes($element_id);
				return true;
			}

		}
		

		/**
			* Переместить страницу $element_id под страницу с $rel_id в начало списка детей
			* @param Integer $element_id id перемещаемой страницы
			* @param Integer $rel_id id новой родительской страницы
			* @return Boolean true в случае успеха
		*/
		public function moveFirst($element_id, $rel_id) {
			$this->disableCache();
		
			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;
			
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$rel_id}' ORDER BY ord ASC";
			$result = l_mysql_query($sql, true);

			list($before_id) = mysql_fetch_row($result);
			return $this->moveBefore($element_id, $rel_id, $before_id);
		}

		/**
			* Проверить, есть ли права на чтение страницы $elementId для текущего пользователя
			* @param Integer $element_id id страницы, которую нужно проверить
			* @return Boolean true если есть доступ на чтение, false если доступа нету
		*/
		protected function isAllowed($elementId) {
			$permissions = permissionsCollection::getInstance();
			list($r) = $permissions->isAllowedObject($permissions->getUserId(), $elementId);
			return $r;
		}

		/**
			* Определить id типа данных, которому принадлежат больше всего страниц под $element_id
			* @param Integer $element_id id страницы
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getDominantTypeId($element_id, $depth = 1) {
			if($this->isExists($element_id) || $element_id === 0) {
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

				$element_id = (int) $element_id;
				$depth      = (int) $depth;
				if($depth > 1) {
					$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
	FROM cms3_hierarchy h, cms3_objects o, cms3_hierarchy_relations hr
		WHERE hr.rel_id = '{$element_id}' AND h.id=hr.child_id AND h.is_deleted = '0' AND o.id = h.obj_id AND h.lang_id = '{$lang_id}' AND h.domain_id = '{$domain_id}'
			GROUP BY o.type_id
				ORDER BY c DESC
					LIMIT 1
SQL;
				} else {
					$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
	FROM cms3_hierarchy h, cms3_objects o
		WHERE h.rel = '{$element_id}' AND h.is_deleted = '0' AND o.id = h.obj_id AND h.lang_id = '{$lang_id}' AND h.domain_id = '{$domain_id}'
			GROUP BY o.type_id
				ORDER BY c DESC
					LIMIT 1
SQL;
				}
				if($type_id = (int) cacheFrontend::getInstance()->loadSql($sql)) {
					return $type_id;
				}

				$result = l_mysql_query($sql);

				if(mysql_num_rows($result)) {
					list($type_id) = mysql_fetch_row($result);
					$type_id = (int) $type_id;

					cacheFrontend::getInstance()->saveSql($sql, $type_id);

					return $type_id;
				} else {
					return NULL;
				}
			} else {
				return false;
			}
		}
		
		/**
			* Пометить страницу с id $element_id как измененную в рамках текущей сессии. Используется самой системой
			* @param id $element_id страницы
		*/
		public function addUpdatedElementId($element_id) {
			if(!in_array($element_id, $this->updatedElements)) {
				$this->updatedElements[] = $element_id;
			}
		}
		
		/**
			* Получать список страниц, измененных в рамках текущей сессии
			* @return Array массив, состоящий из id страниц
		*/
		public function getUpdatedElements() {
			return $this->updatedElements;
		}
		
		/**
			* Запустить очистку кеша по измененным страницам
		*/
		protected function forceCacheCleanup() {
			if(sizeof($this->updatedElements)) {
				if(function_exists("deleteElementsRelatedPages")) {
					deleteElementsRelatedPages();
				}
			}
		}
		
		/**
			* Деструктор
		*/
		public function __destruct() {
			if(defined('SMU_PROCESS') && SMU_PROCESS) {
				return;
			}

			$this->forceCacheCleanup();
			
			if(sizeof($this->symlinks)) {
				foreach($this->symlinks as $i => $arr) {
					list($element_id, $symlink_id) = $arr;
					$this->copyElement($element_id, $symlink_id);
					unset($this->symlinks[$i]);
				}
				$this->symlinks = Array();
			}
			
			if(class_exists('staticCache')) {
				$staticCache = new staticCache;
				$staticCache->cleanup();
				unset($staticCache);
			}
		}
		
		/**
			* Получить список страниц, которые были запрошены в текущей сессии
			* @return Array массив, состоящий из id страниц
		*/
		public function getCollectedElements() {
			return array_merge(array_keys($this->elements), $this->misc_elements);
		}
		
		/**
			* Выгрузить экземпляр страницы $element_id из памяти коллекции
			* @param Integer $element_id id страницы
		*/
		public function unloadElement($element_id) {
			static $pid;

			if($pid === NULL) {
				$pid = cmsController::getInstance()->getCurrentElementId();
			}
			
			if($pid == $element_id) return false;

			if(array_key_exists($element_id, $this->elements)) {
				unset($this->elements[$element_id]);
			} else {
				return false;
			}
		}
		
		/**
			* Deprecated: устаревший метод
		*/
		public function getElementsCount($module, $method = "") {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method)->getId();

			$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE type_id = '{$hierarchy_type_id}'";
			$result = l_mysql_query($sql);
			
			if(list($count) = mysql_fetch_row($result)) {
				return $count;
			} else {
				return false;
			}
		}
		
		/**
			* Добавить время последней модификации страницы максимальное для текущей сессии
			* @param Integer $update_time=0 время в формате UNIX TIMESTAMP
		*/
		private function pushElementsLastUpdateTime($update_time = 0) {
			if($update_time > $this->elementsLastUpdateTime) {
				$this->elementsLastUpdateTime = $update_time;
			}
		}
		
		/**
			* Получить максимальное значениея атрибута "дата последней модификации" для всех страниц, загруженных в текущей сессии
			* @return Integer дата в формате UNIX TIMESTAMP
		*/
		public function getElementsLastUpdateTime() {
			return $this->elementsLastUpdateTime;
		}
		
		/**
			* Получить все страницы, использующие объект (класс umiObject) в качестве источника данных
			* @param Integer $object_id id объекта
			* @return Array массив, состоящий из id страниц
		*/
		public function getObjectInstances($object_id, $bIgnoreDomain = false, $bIgnoreLang = false) {
			$object_id = (int) $object_id;
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			
			$sql = "SELECT id FROM cms3_hierarchy WHERE obj_id = '{$object_id}'";
			if(!$bIgnoreDomain) $sql .= " AND domain_id = '{$domain_id}'";
			if(!$bIgnoreLang)   $sql .= " AND lang_id = '{$lang_id}'" ;
			$result = l_mysql_query($sql);
			
			$res = array();
			while(list($element_id) = mysql_fetch_row($result)) {
				$res[] = $element_id;
			}

			return $res;
		}
		
		/**
			* Определить id шаблона, который выставлен у большинства страниц под $element_id
			* @param Integer $elementId id страницы
			* @return Integer id шаблона дизайна (класс template)
		*/
		public function getDominantTplId($elementId) {
			$elementId = (int) $elementId;
			
			$sql = "SELECT `tpl_id`, COUNT(*) AS `cnt` FROM cms3_hierarchy WHERE rel = '{$elementId}' AND is_deleted = '0' GROUP BY tpl_id ORDER BY `cnt` DESC";
			$result = l_mysql_query($sql);
			if($row = mysql_fetch_row($result)) {
				list($tpl_id) = $row;
				return $tpl_id;
			} else {
				$element = $this->getElement($elementId);
				if(get_class($element) == "umiHierarchyElement") {
					return $element->getTplId();
				}
			}
		}
		
		/**
			* Получить список страниц, измененных с даты $timestamp
			* @param Integer $limit ограничение на количество результатов
			* @param Integer $timestamp=0 дата в формате UNIX TIMESTAMP
			* @return Array массив, состоящий из id страниц
		*/
		public function getLastUpdatedElements($limit, $timestamp = 0) {
			$limit = (int) $limit;
			$timestamp = (int) $timestamp;
			
			$sql = "SELECT id FROM cms3_hierarchy WHERE updatetime >= {$timestamp} LIMIT {$limit}";
			$result = l_mysql_query($sql);
			
			$res = Array();
			while(list($id) = mysql_fetch_row($result)) {
				$res[] = $id;
			}
			return $res;
		}
		
		/**
			* Проверить список страниц на предмет того, имеют ли они виртуальные копии
			* @param Integer $arr массив, где ключ это id страницы, а значение равно false(!)
			* @return Array преобразует параметр $arr таким образом, что false поменяется на количество виртуальных копий там, где они есть
		*/
		public function checkIsVirtual($arr) {
			if(sizeof($arr) == 0) return $arr;
			
			foreach($arr as $element_id => $nl) {
				$element = $this->getElement($element_id);
				$arr[$element_id] = (string) $element->getObjectId();
			}
			
			$sql = "SELECT obj_id, COUNT(*) FROM cms3_hierarchy WHERE obj_id IN (" . implode(", ", $arr) . ") AND is_deleted = '0' GROUP BY obj_id";
			$result = l_mysql_query($sql);

			while(list($obj_id, $c) = mysql_fetch_row($result)) {
				$is_virtual = ($c > 1) ? true : false;

				foreach($arr as $i => $v) {
					if($v === $obj_id) {
						$arr[$i] = $is_virtual;
					}
				}
			}
			
			return $arr;
		}
		
		/**
			* Перепроверить псевдостатичесик URL страницы $element_id на предмет коллизий
			* @param Integer $element_id id страницы
			* @return false если страница $element_id не доступна
		*/
		protected function rewriteElementAltName($element_id) {
			$element = $this->getElement($element_id, true, true);
			if($element instanceof iUmiHierarchyElement) {
				$element->setAltName($element->getAltName());
				$element->commit();

				return true;
			} else {
				return false;
			}
		}
		
		
		//Write here methods to rebuild cms3_hierarchy_relations subnodes
		
		/**
			* Стереть все записи, связанные со страницой $element_id из таблицы cms3_hierarchy_relations
			* @param Integer $element_id id страницы
		*/
		protected function earseRelationNodes($element_id) {
			$element_id = (int) $element_id;
			
			$sql = "DELETE FROM cms3_hierarchy_relations WHERE rel_id = '{$element_id}' OR child_id = '{$element_id}'";
			l_mysql_query($sql);
		}
		
		/**
			* Перестроить дерево зависимостей для узла $element_id
		*/
		public function rebuildRelationNodes($elementId) {		//TODO: public - временно. должен быть protected
			$elementId = (int) $elementId;
			
			//Earse all hierarchy relations
			$this->earseRelationNodes($elementId);

			//Put new relations data for this single element as for a new one
			$this->buildRelationNewNodes($elementId);
			
			//Get all childs and apply this methods to 'em
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$elementId}'";
			$result = l_mysql_query($sql);
			
			while(list($childElementId) = mysql_fetch_row($result)) {
				$this->rebuildRelationNodes($childElementId);
			}
		}
		
		
		/**
			* Построить дерево зависимостей для $element_id относительно родителей
			* @param $element_id id страницы
		*/
		public function buildRelationNewNodes($element_id) {		//TODO: public - временно. должен быть protected
			$element_id = (int) $element_id;
			$this->earseRelationNodes($element_id);
			
			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$element_id}'";
			$result = l_mysql_query($sql, true);
			
			if(mysql_num_rows($result)) {
				list($parent_id) = mysql_fetch_row($result);
				$parent_id_cond = ($parent_id > 0) ? " = '{$parent_id}'" : " IS NULL";
				
				$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
SELECT rel_id, '{$element_id}', (level + 1) FROM cms3_hierarchy_relations WHERE child_id {$parent_id_cond}
SQL;
				l_mysql_query($sql);
				$parents = $this->getAllParents($parent_id, true, true);

				$parents = array_extract_values($parents);
				$level = sizeof($parents);
				
				
				$parent_id_val = ($parent_id > 0) ? "'{$parent_id}'" : "NULL";
				
				$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
VALUES ({$parent_id_val}, '{$element_id}', '{$level}')
SQL;
				l_mysql_query($sql);
				return true;
			} else return false;
		}
	};


	interface iUmiSelection {
		public function forceHierarchyTable($isForced = true);

		public function addObjectType($objectTypeId);
		public function addElementType($elementTypeId);

		public function addLimit($resultsPerQueryPage, $resultsPage = 0);

		public function setOrderByProperty($fieldId, $asc = true);
		public function setOrderByOrd();
		public function setOrderByRand();
		public function setOrderByName($asc = true);
		public function setOrderByObjectId($asc = true);

		public function addHierarchyFilter($elementId, $depth = 0, $ignoreIsDefault = false);

		public function addPropertyFilterBetween($fieldId, $minValue, $maxValue);
		public function addPropertyFilterEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterNotEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterLike($fieldId, $likeValue, $caseInsencetive = true);
		public function addPropertyFilterMore($fieldId, $val);
		public function addPropertyFilterLess($fieldId, $val);
		public function addPropertyFilterIsNull($fieldId);
		public function addActiveFilter($active);
		public function addOwnerFilter($owner);
		public function addObjectsFilter($vOids);
		public function addElementsFilter($vEids);

		public function addNameFilterEquals($exactValue);
		public function addNameFilterLike($likeValue);

		public function addPermissions($userId = false);
		public function setPermissionsLevel($level = 1);
		
		public function setDomainId($domainId = false);
		public function setLangId($langId = false);

		public function setConditionModeOR();
		
		public function setIsDomainIgnored($isDomainIgnored = false);
		public function setIsLangIgnored($isLangIgnored = false);
		
		public function resetTextSearch();
		
		public function result();
		public function count();
	}


/**
	* Класс, который предоставляет средства для создания шаблонов выборок данных из базы данных.
*/
	class umiSelection implements iUmiSelection {
		private	$order = Array(),
			$limit = Array(),
			$object_type = Array(),
			$element_type = Array(),
			$props = Array(),
			$hierarchy = Array(),
			$perms = Array(),
			$names = Array(),
			$active = Array(),
			$owner = Array(),
			$objects_ids = Array(),
			$elements_ids = Array(),

			$is_order = false,  $is_limit = false, $is_object_type = false, $is_element_type = false, $is_props = false, $is_hierarchy = false, $is_permissions = false, $is_forced = false, $is_names = false, $is_active = false,
			$condition_mode_or = false, $is_owner = false,
			$is_objects_ids = false, $is_elements_ids = false,
			$is_domain_ignored = false, $isDomainIgnored = false, $isLangIgnored = false, $langId = false, $domainId = false,
			$permissionsLevel = 1,
			$searchStrings = Array();
			
		public	$result = false, $count = false, $switchIllegalBetween = true;

		// ========

		public $optimize_root_search_query = false;
		public $sql_part__hierarchy = "";
		public $sql_part__element_type = "";
		public $sql_part__owner = "";
		public $sql_part__objects = "";
		public $sql_part__elements = "";
		public $sql_part__perms = "";
		public $sql_part__perms_tables = "";
		public $sql_part__content_tables = "";
		public $sql_part__object_type = "";
		public $sql_part__props_and_names = "";
		public $sql_part__lang_cond = "";
		public $sql_part__domain_cond = "";
		public $sql_part__unactive_cond = "";

		public $sql_cond__total_joins = 0;
		public $sql_cond__content_tables_loaded = 0;
		public $sql_cond__need_content = false;
		public $sql_cond__need_hierarchy = false;
		public $sql_cond__domain_ignored = false;
		public $sql_cond_auto_domain = false;

		public $sql_arr_for_mark_used_fields = array();
		public $sql_arr_for_and_or_part = array();

		// ==

		public $sql_kwd_distinct = "";
		public $sql_kwd_distinct_count = "";
		public $sql_kwd_straight_join = "";

		public $sql_select_expr = "";
		public $sql_table_references = "";
		public $sql_where_condition_required = "";
		public $sql_where_condition_common = "";
		public $sql_where_condition_additional = "";

		public $sql_order_by = "";
		public $sql_limit = "";
		
		public $objectTableIsRequired = false;
		public $excludeNestedPages = false;
		
		public $usedContentTables = Array();

		// ========

		public function result() { return umiSelectionsParser::runSelection($this); }
		public function count() { return umiSelectionsParser::runSelectionCounts($this); }


		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectTypeFilter($is_enabled = true) {
			$this->is_object_type = (bool) $is_enabled;
			if (!$is_enabled) $this->object_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу елементов иерархии
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setElementTypeFilter($is_enabled = true) {
			$this->is_element_type = (bool) $is_enabled;
			if (!$is_enabled) $this->element_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по свойствам объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setPropertyFilter($is_enabled = true) {
			$this->is_props = (bool) $is_enabled;
			if (!$is_enabled) $this->props = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает ограничение по количество элементов
		* @param Boolean $is_enabled Разрешить ограничение (true) или запретить (false)
		*/
		public function setLimitFilter($is_enabled = true) {
			$this->is_limit = (bool) $is_enabled;
			if (!$is_enabled) $this->limit = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id элементов иерархии
		* @param Boolean $is_enabled  Разрешить фильтрацию (true) или запретить (false) 
		*/
		public function setHierarchyFilter($is_enabled = true) {
			$this->is_hierarchy = (bool) $is_enabled;
			if (!$is_enabled) $this->hierarchy = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает сортировку
		* @param Boolean $is_enabled Разрешить сортировку (true) или запретить (false)
		*/
		public function setOrderFilter($is_enabled = true) {
			$this->is_order = (bool) $is_enabled;
			if (!$is_enabled) $this->order = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по правам
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/ 
		public function setPermissionsFilter($is_enabled = true) {
			

			$this->is_permissions = $is_enabled;

			$user_id = $this->getCurrentUserId();
			if(cmsController::getInstance()->getModule("users")->isSv($user_id)) {
				$this->is_permissions = false;
			}
			if (!$is_enabled) $this->perms = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по активности элемента
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setActiveFilter($is_enabled = true) {
			$this->is_active = (bool) $is_enabled;
			if (!$is_enabled) $this->is_active = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по владельцу
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setOwnerFilter($is_enabled = true) {
			$this->is_owner = (bool) $is_enabled;
			if (!$is_enabled) $this->is_owner = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id объектов
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectsFilter($is_enabled = true) {
			$this->is_objects_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_objects_ids = Array();
		}
		
		/**
		* @deprecated
		* @desc 
		*/
		public function setElementsFilter($is_enabled = true) {
			$this->is_elements_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_elements_ids = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по имени объекта
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setNamesFilter($is_enabled = true) {
			$this->is_names = (bool) $is_enabled;
			if (!$is_enabled) $this->names = Array();
		}

		public function forceHierarchyTable($isForced = true) {
			$this->is_forced = (bool) $isForced;
		}

		/**
		* @desc Добавляет тип объекта к критерию фильтрации
		* @param Int $object_type Id типа объекта
		*/
		public function addObjectType($object_type_id) {
			$this->setObjectTypeFilter();

			if(is_array($object_type_id)) {
				foreach($object_type_id as $sub_object_type_id) {
					if(!$this->addObjectType($sub_object_type_id)) {
						return false;
					}
				}
				return true;
			}

			if(umiObjectTypesCollection::getInstance()->isExists($object_type_id)) {
				if(in_array($object_type_id, $this->object_type) === false) {
					$this->object_type[] = $object_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Добавляет тип элемента к критерию фильтрации
		* @param Int $object_type Id типа элемента
		*/
		public function addElementType($element_type_id) {
			/*
			Не принимает массив !!! вызывайте несколько раз (TODO: переписать)
			*/
			$this->setElementTypeFilter();
		
			if(umiHierarchyTypesCollection::getInstance()->isExists($element_type_id)) {
				if(in_array($element_type_id, $this->element_type) === false) {
					$this->element_type[] = $element_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает количественные ограничения на выборку
		* @param Int $per_page	Количество объектов на странице
		* @param Int $page 		Номер выбираемой страницы
		*/
		public function addLimit($per_page, $page = 0) {
			$this->setLimitFilter();
		
			$per_page = (int) $per_page;
			$page = (int) $page;

			if($page < 0) {
				$page = 0;
			}
			
			$this->limit = Array($per_page, $page);
		}

		/**
		* @desc Устанавливает признак активности елемента
		* @param Boolean $active True - выбрать активные алементы, False - выбрать неактивные элементы
		*/
		public function addActiveFilter($active) {
			$this->setActiveFilter();
			$this->active = Array($active);
		}

		/**
		* @desc Устанавливает владельцев объекта/элемента
		* @param Array $vOwners Возможные id владельцев
		*/
		public function addOwnerFilter($vOwners) {
			$this->setOwnerFilter();
			$this->owner = $this->toIntsArray($vOwners);
		}

		/**
		* @desc Устанавливает возможные id объектов
		* @param Array $vOids возможные id объектов
		*/
		public function addObjectsFilter($vOids) {
			$this->setObjectsFilter();
			$this->objects_ids = $this->toIntsArray($vOids);
		}

		/**
		* @desc Устанавливает возможные id елементов иерархии
		* @param Array $vOids возможные id елементов иерархии
		*/
		public function addElementsFilter($vEids) {
			$this->setElementsFilter();
			$this->elements_ids = $this->toIntsArray($vEids);
		}

		/**
		* @desc Устанавливает поле и вид сортировки
		* @param Int 		$field_id 	id поля, по которому будет произведена сортировка
		* @param Boolean 	$asc 		порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByProperty($field_id, $asc = true) {
			if(!$field_id) return false;
			$this->setOrderFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("field_id" => $field_id, "asc" => $asc, "type" => $data_type, "native_field" => false);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по расположению в иерархии
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByOrd($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "ord", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает выборку случайных ID
		*/
		public function setOrderByRand() {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "rand", "asc" => true);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по имени
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByName($asc = true) {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "name", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает сортировку по id объекта
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByObjectId($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "object_id", "asc" => $asc);
			
			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает параметры выбора элементов иерархии
        * @param Int 	 	$element_id 		Id корня выборки
        * @param Int 	 	$depth				Глубина выборки элементов от корня
        * @param Boolean	$ignoreIsDefault	игнорировать элемент по-умолчанию
        */
		public function addHierarchyFilter($element_id, $depth = 0, $ignoreIsDefault = true) {
			$this->setHierarchyFilter();
			
			if(is_array($element_id)) {
				foreach($element_id as $id) {
					$this->addHierarchyFilter($id, $depth);
				}
				return;
			}

			if(umiHierarchy::getInstance()->isExists($element_id) || (is_numeric($element_id) && $element_id == 0)) {
				if($element_id == umiHierarchy::getInstance()->getDefaultElementId() && $ignoreIsDefault == false) {
					$element_id = Array(0, 0);
				}
			
				if(in_array($element_id, $this->hierarchy) === false || $element_id == 0) {
					$this->hierarchy[] = Array((int) $element_id, $depth);
				}

				if($depth > 0) {
					$this->hierarchy[] = Array($element_id, $depth);
				}
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает проверку попадания значения поля в интервал
        * @param Int 	$field_id 	Id поля
        * @param Mixed 	$min 		Минимальное значение
        * @param Mixed	$max		Максимальное значение
        */
		public function addPropertyFilterBetween($field_id, $min, $max) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
			
			$data_type = $this->getDataByFieldId($field_id);
			
			if($this->switchIllegalBetween && $min > $max) {
				$tmp = $min;
				$min = $max;
				$max = $tmp;
				unset($tmp);
			}

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "between", "min" => $min, "max" => $max);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на равенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр
		*/
		public function addPropertyFilterEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id || !sizeof($value)) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на неравенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterNotEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "not_equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

        /**
		* @desc Устанавливает проверку значения поля на включение поисковой строки
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для поиска
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterLike($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "like", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "больше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterMore($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "more", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "меньше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterLess($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "less", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "null");
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNotNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "notnull");
			$this->props[] = $filter;
		}

        /**
        * @desc Устанавливает пользователя или группу для проверки прав на элемент
        * @param Int $user_id ID пользователя или группы
        */
		public function addPermissions($user_id = false) {
			$this->setPermissionsFilter();
		
			if($user_id === false) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isSv()) return;
				$user_id = $permissions->getUserId();
				
			}
			$owners = $this->getOwnersByUser($user_id);
			$this->perms = $owners;
		}
		
		/**
			* Устанавливает уровень прав, который должен быть у искомых страниц
		*/
		public function setPermissionsLevel($level = 1) {
			$this->permissionsLevel = (int) $level;
		}

		/**
		* @desc Устанавливает значение для проверки имени поля на равенство
		* @param Mixed $value Значение для проверки
		*/
		public function addNameFilterEquals($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "exact");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}
		
		/**
		* @desc Устанавливает значение для поиска в имени
		* @param Mixed $value значение для поиска
		*/
		public function addNameFilterLike($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "like");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}

		/**
		* @desc Возвращает параметры сортировки
		* @return Array | Boolean(False) 
		*/
		public function getOrderConds() {
			return ($this->is_order) ? $this->order : false;
		}

		/**
		* @desc Возвращает количественные ограничения на выборку
		* @return Array | Boolean(False) 
		*/
		public function getLimitConds() {
			return ($this->is_limit) ? $this->limit : false;
		}

		/**
		* @desc Возвращает признак активности
		* @return Boolean 
		*/
		public function getActiveConds() {
			return ($this->is_active) ? $this->active : false;
		}

		/**
		* @desc Возвращает список возможных владельцев
		* @return Array | Boolean(False) 
		*/
		public function getOwnerConds() {
			$arrAnswer = array();
			if (is_array($this->owner) && count($this->owner)) {
				$arrAnswer = array_map('intval', $this->owner);
			}
			return ($this->is_owner) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectsConds() {
			$arrAnswer = array();
			if (is_array($this->objects_ids) && count($this->objects_ids)) {
				$arrAnswer = array_map('intval', $this->objects_ids);
			}
			return ($this->is_objects_ids) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id элементов иерархии
		* @return Array | Boolean(False) 
		*/
		public function getElementsConds() {
			$arrAnswer = array();
			if (is_array($this->elements_ids) && count($this->elements_ids)) {
				$arrAnswer = array_map('intval', $this->elements_ids);
			}
			return ($this->is_elements_ids) ? $arrAnswer : false;
		}

		/**
		* @desc Возвращает список условий на выборку по значению полей
		* @return Array | Boolean(False)
		*/
		public function getPropertyConds() {
			return ($this->is_props) ? $this->props : false;
		}

		/**
		* @desc Возвращает список возможных id типов объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectTypeConds() {
			return ($this->is_object_type) ? $this->object_type : false;
		}

		/**
		* @desc Возвращает список возможных id типов элементов иерархии
		* @return Array  | Boolean(False)
		*/
		public function getElementTypeConds() {
			if($this->getObjectTypeConds() !== false) {
				return false;
			}
			
			if($this->optimize_root_search_query) {
				if(is_array($this->element_type)) {
					if(sizeof($this->element_type) > 1) {
						reset($this->element_type);
						$this->element_type = Array(current($this->element_type));
					}
				}
			}

			return ($this->is_element_type) ? $this->element_type : false;
		}

		public function getHierarchyConds() {
			$this->hierarchy = array_unique_arrays($this->hierarchy, 0);
			return ($this->is_hierarchy && !$this->optimize_root_search_query) ? $this->hierarchy : false;
		}

		/**
		* @desc Возвращает список пользователей и/или групп с правами на элемент иерархии
		* @return Array | Boolean(False) 
		*/
		public function getPermissionsConds() {
			return ($this->is_permissions) ? $this->perms : false;
		}

		public function getForceCond() {
			return $this->is_forced;
		}

		/**
		* @desc Возвращает условия проверки имени
		* @return Array | Boolean(False)
		*/
		public function getNameConds() {
			return ($this->is_names) ? $this->names : false;
		}
		
		private function getDataByFieldId($field_id) {
			if($field = umiFieldsCollection::getInstance()->getField($field_id)) {
				$field_type_id = $field->getFieldTypeId();

				if($field_type = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id)) {
					if($data_type = $field_type->getDataType()) {
						return umiFieldType::getDataTypeDB($data_type);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		private function getCurrentUserId() {
			if($users = cmsController::getInstance()->getModule("users")) {
				return $users->user_id;
			} else {
				return false;
			}
		}

		private function getOwnersByUser($user_id) {
			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				$groups = $user->getValue("groups");
				$groups[] = $user_id;
				return $groups;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает флаг "ИЛИ" группировки результатов выборки по значению полей.
		* 		Если этот флаг установлен, то выбираются объекты/элементы иерархии,
		* 		удовлетворяющие хотя бы одному условию, из указаных. В противном случае
		* 		требуется соблюдение всех указаных условий.
		*/
		public function setConditionModeOr() {
			$this->condition_mode_or = true;
		}
		
		/**
		* @desc Возвращает значение флага группировки результатов выборки по значению полей
		* @return Boolean
		*/
		public function getConditionModeOr() {
			return $this->condition_mode_or;
		}
		
		
		/**
		* @desc Устанавливает значение флага игнорирования текущего домена
		* @param Boolean $isDomainIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsDomainIgnored($isDomainIgnored = false) {
			$this->isDomainIgnored = (bool) $isDomainIgnored;
		}
		
		/**
		* @desc Устанавливает значение флага игнорирования текущей языковой версии
		* @param Boolean $isLangIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsLangIgnored($isLangIgnored = false) {
			$this->isLangIgnored = (bool) $isLangIgnored;
		}
		
		/**
		* @desc Возвращает значение  флага игнорирования текущего домена
		* @return Boolean
		*/
		public function getIsDomainIgnored() {
			return $this->isDomainIgnored;
		}

		/**
		* @desc Возвращает значение  флага игнорирования текущей языковой версии
		* @return Boolean
		*/
		public function getIsLangIgnored() {
			return $this->isLangIgnored;
		}
		
		/**
			* Искать только по указанному домену
			* @param Integer $domainId = false id домена, либо false, если поиск будет по всем доменам
		*/
		public function setDomainId($domainId = false) {
			$this->domainId = ($domainId === false) ? false : (int) $domainId;
		}
		
		/**
			* Искать только в указанной языковой версии
			* @param Integer $langId = false id языка, либо false
		*/
		public function setLangId($langId = false) {
			$this->langId = ($langId === false) ? false : (int) $langId;
		}
		
		/**
			* Поиск по строке в любом тектовом поле
			* @param String $searchString строка поиска
		*/
		public function searchText($searchString) {
			if(is_string($searchString)) {
				if(strlen($searchString) > 0 && !in_array($searchString, $this->searchStrings)) {
					$this->searchStrings[] = $searchString;
					return true;
				}
			}
			return false;
		}
		
		public function getDomainId() {
			return $this->domainId;
		}
		
		public function getLangId() {
			return $this->langId;
		}
		
		public function getRequiredPermissionsLevel() {
			return $this->permissionsLevel;
		}
		
		public function getSearchStrings() {
			return $this->searchStrings;
		}
		
		public function resetTextSearch() {
			$this->searchStrings = Array();
		}

		//

		private function toIntsArray($vValue) {
			$arrAnswer = Array();
			if (is_string($vValue)) {
				$arrAnswer = preg_split("/[^\d]/is", $vValue);
			} elseif (is_numeric($vValue)) {
				$arrAnswer = array(intval($vValue));
			} elseif (!is_array($vValue)) {
				$arrAnswer = array();
			} else {
			    $arrAnswer = $vValue;
			}
			return array_map('intval', $arrAnswer);
		}
	};


	interface iUmiSelectionsParser {
		public static function runSelection(umiSelection $selectionObject);
		public static function runSelectionCounts(umiSelection $selectionObject);
		public static function parseSelection(umiSelection $selectionObject);
	}


/**
	* Производит выборки по параметрам, переданным через класс umiSelection.
	* Содержит только статические публичные методы, сами по себе экземпляры этого класса бесполезны.
*/
	class umiSelectionsParser implements iUmiSelectionsParser {
		/*
		public static function runSelection(umiSelection $selection)
		public static function runSelectionCounts(umiSelection $selection)
		public static function parseSelection(umiSelection $selection)
		*/
		private function __construct() {}

		/**
		* @desc Выбирает id объектов (umiObject) или елементов иерархии (umiHierarchyElement), соответсвующих указаным критериям
		* @param umiSelection $selection Критерии выборки
		* @return Array id элементов иерархии или объектов
		*/
		public static function runSelection(umiSelection $selection) {
		    static $permissions;
			if ($selection->result !== false) return $selection->result; // RETURN

			$sqls = self::parseSelection($selection);

			if (!$sqls['result']) return false; // RETURN

			// ====
			$result = l_mysql_query($sqls['result']);

			$res = Array();
			while ($row = mysql_fetch_row($result)) {
				list($element_id) = $row;
				if(isset($row[1])) {
				    if(!$permissions) {
				        $permissions = permissionsCollection::getInstance();
				    }
					$permissions->pushElementPermissions($element_id, $row[1]);
				}
				$element_id = intval($element_id);
				if(in_array($element_id, $res) == false) {
					$res[] = $element_id;
				}
			}
			
			if($selection->excludeNestedPages) {
				$res = self::excludeNestedPages($res);
			}

			$selection->result = $res;

			if(defined("DISABLE_CALC_FOUND_ROWS")) {
				if(DISABLE_CALC_FOUND_ROWS) {
					$sql = "SELECT FOUND_ROWS()";
					$result = l_mysql_query($sql, true);
	
					list($count) = mysql_fetch_row($result);
					$selection->count = $count;
				}
			}
			if ($selection->optimize_root_search_query) {
				$selection->count = false;
			}
			// RETURN
			return $selection->result;
		}

		/**
		* @desc Выполняет подсчет элементов/объктов, соответствующих критериям выборки
		* @param umiSelection $selection Критерии выборки
		* @return Int количество выбранных объектов или элементов
		*/
		public static function runSelectionCounts(umiSelection $selection) {
			if ($selection->count !== false) return $selection->count; // RETURN

			$sqls = self::parseSelection($selection);

			if (!$sqls['count']) return false; // RETURN


			if ($count = cacheFrontend::getInstance()->loadSql($sqls['count'])) {
				// RETURN
				return $count;
			}

			// ====

			$result = l_mysql_query($sqls['count']);

			if (list($count) = mysql_fetch_row($result)) {
				$selection->count = intval($count);
				// RETURN
				cacheFrontend::getInstance()->saveSql($sqls['count'], $selection->count);
				return $selection->count;
			} else {
				// RETURN
				return false;
			}
		}

		/**
		* @desc Производит подготовку запросов к выборке
		* @param umiSelection $selection Критерии выборки
		* @return Array ID объектов (umiObject) или элементов иерархии (umiHierarchyElement)
		*/
		public static function parseSelection(umiSelection $selection) {

			/*

			Метод формирует запросы для использования методами
			runSelection и runSelectionCounts
			на основании данных из $selection

			Вот что мы должны получить в итоге :

			SELECT
				[ALL | DISTINCT | DISTINCTROW ]
					[HIGH_PRIORITY]
					[STRAIGHT_JOIN]
					[SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
					[SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
				select_expr, ...
				[FROM table_references
				[WHERE where_condition]
				[GROUP BY {col_name | expr | position}
					[ASC | DESC], ... [WITH ROLLUP]]
				[HAVING where_condition]
				[ORDER BY {col_name | expr | position}
					[ASC | DESC], ...]
				[LIMIT {[offset,] row_count | row_count OFFSET offset}]
				[PROCEDURE procedure_name(argument_list)]
				[INTO OUTFILE 'file_name' export_options
					| INTO DUMPFILE 'file_name'
					| INTO var_name [, var_name]]
				[FOR UPDATE | LOCK IN SHARE MODE]]

			*/
			
			if(!defined('MAX_SELECTION_TABLE_JOINS')) {
				define('MAX_SELECTION_TABLE_JOINS', 10);
			}

			/*
			I. Хранить промежуточные результаты мы будем в $selection'е (так как сами мы static),
			а перед работой их обдефолтим :
			*/

			// это промежуточные строки и триггеры, на основании которых соберутся итоговые запросы

			$selection->sql_cond__need_content = false;
			$selection->sql_cond__need_hierarchy = $selection->getForceCond();
			$selection->sql_cond__domain_ignored = $selection->getIsDomainIgnored();
			$selection->sql_cond__lang_ignored = $selection->getIsLangIgnored();
			$selection->sql_cond__total_joins = 0;
			$selection->sql_cond__content_tables_loaded = 0; // for tables naming in the query
			$selection->sql_arr_for_mark_used_fields = array();
			$selection->sql_arr_for_and_or_part = array();

			$selection->sql_part__hierarchy = "";			// условие на родителя элемента иерархии
			$selection->sql_part__element_type = "";		// условие на тип элементов иерархии
			$selection->sql_part__object_type = "";			// условие на тип объектов данных
			$selection->sql_part__owner = "";				// условие на владельца
			$selection->sql_part__objects = "";				// условие на конкретные объекты данных
			$selection->sql_part__elements = "";			// условие на конкретные элементы иерархии
			$selection->sql_part__perms = "";				// учесть разрешения
			$selection->sql_part__props_and_names = "";		// условия на значения свойств и имена
			$selection->sql_part__lang_cond = "";			// условие на языки
			$selection->sql_part__domain_cond = "";			// условие на домены
			$selection->sql_part__unactive_cond = "";		// условие на активность элемента

			$selection->sql_part__perms_tables = "";
			$selection->sql_part__content_tables = "";

			// это основные части итоговых запросов

			$selection->sql_kwd_distinct = "";
			$selection->sql_kwd_distinct_count = "";
			$selection->sql_kwd_straight_join = "";

			$selection->sql_select_expr = "";
			$selection->sql_table_references = "";
			$selection->sql_where_condition_required = "";
			$selection->sql_where_condition_common = "";
			$selection->sql_where_condition_additional = "";

			$selection->sql_order_by = "";
			$selection->sql_limit = ""; // ограничение на количество рядов в выборке

			/*
			II. Далее формируем промежуточные результаты
			!!! порядок вызова важен, не меняй не думая !!! // WARNING
			*/

			/*
				---- makeLimitPart ----

				формирует "ограничение на количество рядов в выборке", т.е.
				limit-offset часть запроса, согласно $selection->getLimitConds()

				условия задаются в umiSelection последовательным вызовом
				setLimitFilter и addLimit
				(или просто addLimit, т.к. он сам вызывает setLimitFilter)

				----

				Влияет на:
				- $selection->sql_limit
			*/
			self::makeLimitPart($selection);

			/*
				---- makeHierarchyPart ----

				формирует часть запроса "условие на родителя", то есть
				"выбрать только такие элементы иерархии,
				которые являются непосредственными потомками указанных элементов"

				получить "указанные элементы" - $selection->getHierarchyConds()

				задаются "указанные элементы" в umiSelection последовательным вызовом
				setHierarchyFilter и addHierarchyFilter
				(или просто addHierarchyFilter, т.к. он сам вызывает setHierarchyFilter)

				NOTE: в addHierarchyFilter указывается глубина, но в запросе, генерируемом self::makeHierarchyPart
				глубина не участвует, так как addHierarchyFilter сам пробегается по всей глубине,
				и включает всех потомков в массив, возвращаемый $selection->getHierarchyConds()
				(глубина и не может участвовать, так как таблица предполагает связь только с непосредственным родителем (rel))

				----

				Влияет на:
				- $selection->sql_part__hierarchy
				- $selection->sql_cond__domain_ignored
				- $selection->sql_cond__need_hierarchy
			*/
			self::makeHierarchyPart($selection);


			/*
				---- makeElementTypePart ----

				формирует часть запроса "условие на тип элементов иерархии", то есть
				"выбрать только те элементы иерархии, которые имеют один из указанных типов"

				получить "указанные типы элементов" - $selection->getElementTypeConds()

				задаются "указанные типы элементов" в umiSelection последовательным вызовом
				setElementTypeFilter и addElementType
				(или просто addElementType, т.к. он сам вызывает setElementTypeFilter)

				----

				Влияет на:
				$selection->sql_part__element_type
				$selection->sql_cond__need_hierarchy
			*/
			self::makeElementTypePart($selection);


			/*
				---- makeOwnerPart ----

				формирует часть запроса "условие на владельца", то есть
				"выбрать только те объекты данных / элементы иерархии,
				которые имеют одного из указанных владельцев"

				получить "указанных владельцев" - $selection->getOwnerConds()

				задаются "указанные владельцы" в umiSelection последовательным вызовом
				setOwnerFilter и addOwnerFilter
				(или просто addOwnerFilter, т.к. он сам вызывает setOwnerFilter)

				----

				Влияет на:
				$selection->sql_part__owner
			*/
			self::makeOwnerPart($selection);

			/*
				---- makeObjectsPart ----

				формирует часть запроса "условие на конкретные объекты данных", то есть
				"выбрать только те объекты данных,
				которые имеют один из указанных идентификаторов"
				(например, получены предыдущим запросом umiSelection'а)

				получить "указанные идентификаторы" - $selection->getObjectsConds()

				задаются "указанные идентификаторы" в umiSelection последовательным вызовом
				setObjectsFilter и addObjectsFilter
				(или просто addObjectsFilter, т.к. он сам вызывает setObjectsFilter)

				----

				Влияет на:
				$selection->sql_part__objects
			*/
			self::makeObjectsPart($selection);

			/*
				---- makeElementsPart ----

				формирует часть запроса "условие на конкретные элементы иерархии", то есть
				"выбрать только те элементы иерархии,
				которые имеют один из указанных идентификаторов"
				(например, получены предыдущим запросом umiSelection'а)

				получить "указанные идентификаторы" - $selection->getElementsConds()

				задаются "указанные идентификаторы" в umiSelection последовательным вызовом
				setElementsFilter и addElementsFilter
				(или просто addElementsFilter, т.к. он сам вызывает setElementsFilter)

				----

				Влияет на:
				$selection->sql_part__elements
				$selection->sql_cond__need_hierarchy
			*/
			self::makeElementsPart($selection);

			/*
				---- makePermsParts ----

				формирует часть запроса "учесть разрешения", то есть
				"выбрать только те элементы иерархии, на которые у указанного пользователя
				и групп, в которые он входит, есть разрешения на чтение"

				получить "указанного пользователя и его группы" - $selection->getPermissionsConds()

				задаются "указанный пользователь и его группы" в umiSelection последовательным вызовом
				setPermissionsFilter и addPermissions
				(или просто addPermissions, т.к. он сам вызывает setPermissionsFilter)

				----

				Влияет на:
				$selection->sql_part__perms
				$selection->sql_part__perms_tables
				$selection->sql_cond__need_hierarchy
			*/
			self::makePermsParts($selection);

			/*
				---- makePropPart ----

				формирует условия в зависимости от
				заданных "фильтров по полям объектов/элементов"

				получить "фильтры по полям объектов/элементов" - $selection->getPropertyConds()

				задаются "фильтры по полям объектов/элементов" в umiSelection последовательным вызовом
				setPropertyFilter и методов
					- addPropertyFilterBetween
					- addPropertyFilterEqual
					- addPropertyFilterNotEqual
					- addPropertyFilterLike
					- addPropertyFilterMore
					- addPropertyFilterLess
					- addPropertyFilterIsNull
				(или просто этими методами, т.к. они сами вызывают setPropertyFilter)

				----

				Влияет на:
				$selection->sql_part__props_and_names
				$selection->sql_part__content_tables
				$selection->sql_cond__need_content
				$selection->sql_cond__total_joins
			*/
			self::makePropPart($selection);

			/*
				---- makeObjectTypePart ----

				формирует часть запроса "условие на тип объектов данных", то есть
				"выбрать только те объектов (соотнесенные с ними элементы иерархии),
				которые имеют один из указанных типов"

				получить "указанные типы объектов" - $selection->getObjectTypeConds()

				задаются "указанные типы объектов" в umiSelection последовательным вызовом
				setObjectTypeFilter и addObjectType
				(или просто addObjectType, т.к. он сам вызывает setObjectTypeFilter)

				----

				Влияет на:
				$selection->sql_part__object_type
			*/
			self::makeObjectTypePart($selection);

			/*
				---- makeOrderPart ----

				формирует часть запроса "ORDER BY"

				получить "ORDER BY" - $selection->getOrderConds()

				задаются "ORDER BY" в umiSelection последовательным вызовом
				setOrderFilter и методов
					- setOrderByProperty
					- setOrderByOrd
					- setOrderByRand
					- setOrderByName
					- setOrderByObjectId
				(или просто этими методами, т.к. они сами вызывают setOrderFilter)

				----

				Влияет на:
				$selection->sql_order_by
				$selection->sql_cond__need_content
				$selection->sql_cond__content_tables_loaded
				$selection->sql_part__content_tables
				$selection->sql_arr_for_and_or_part
				$selection->sql_cond__total_joins
			*/
			self::makeOrderPart($selection);

			/*
				---- makeNamesPart ----

				формирует часть запроса
				"взять только объекты с указанными/похожими именами"

				получить "указанные имена" - $selection->getNameConds

				задаются "указанные имена" в umiSelection последовательным вызовом
				setNamesFilter и методов
					- addNameFilterEquals
					- addNameFilterLike
				(или просто этими методами, т.к. они сами вызывают setNamesFilter)

				----

				Влияет на:
				$selection->sql_arr_for_and_or_part
			*/
			self::makeNamesPart($selection);

			/*
				---- makePropsAndNames ----

				Сводит массив sql_arr_for_and_or_part в подстроку запроса

				----

				Влияет на:
				$selection->sql_part__props_and_names
			*/
			self::makePropsAndNames($selection);

			/*
				---- makeHierarchySpecificConds ----

				вводит в запрос условия, специфичные для
				элементов иерархии (в отличие от объектов данных)

				----

				Влияет на:
				$selection->sql_part__lang_cond
				$selection->sql_part__domain_cond
				$selection->sql_part__unactive_cond
			*/
			self::makeHierarchySpecificConds($selection);

			// ==== если получилось слишком много таблиц - уходим
			// RETURN

			if ($selection->sql_cond__total_joins >= 59) {
				return Array("result" => false, "count" => false);
			}

			/*
			III. Теперь формируем основные части запроса

			Эти вызовы уже ни на что не влияют,
			просто собирают основные части запроса
			из строк и в соответствии с условиями,
			сформированными на предыдущем этапе
			*/

			self::makeDistinctKeywords($selection);

			self::makeStraitJoinKeyword($selection);

			self::makeSelectExpr($selection);

			self::makeTables($selection);

			self::makeWhereConditions($selection);

			$sql_join_content_tables = "";
			$sz = sizeof($selection->usedContentTables);
			if($sz > 1) {
				for($i = 0; $i < $sz - 1; $i++) {
					$current = $selection->usedContentTables[$i];
					$next = $selection->usedContentTables[$i + 1];
					$sql_join_content_tables .= " AND {$current}.obj_id = {$next}.obj_id";
				}
				
			}
			

			/*
			IV. Формируем и возвращаем запросы
			*/

			// RETURN :
			$sql_calc_found_rows = "";
			if(defined("DISABLE_CALC_FOUND_ROWS")) {
				if(DISABLE_CALC_FOUND_ROWS) {
					$sql_calc_found_rows = "SQL_CALC_FOUND_ROWS";
				}
			}
			
			if($selection->sql_part__perms_tables) {
				$selection->sql_kwd_distinct = "";
				$selection->sql_group_by = " GROUP BY h.id";
			} else {
				$selection->sql_group_by = "";
			}

			$sql = <<<SQL
				SELECT {$selection->sql_kwd_straight_join} {$sql_calc_found_rows} {$selection->sql_kwd_distinct}
					{$selection->sql_select_expr}
				FROM
					{$selection->sql_table_references}
				WHERE
					{$selection->sql_where_condition_required}
					{$selection->sql_where_condition_common}
					{$selection->sql_where_condition_additional}
					{$sql_join_content_tables}
				{$selection->sql_group_by}
				{$selection->sql_order_by}
				{$selection->sql_limit}
SQL;

			$sql_count = <<<SQL
				SELECT {$selection->sql_kwd_straight_join} 
					COUNT({$selection->sql_kwd_distinct_count}{$selection->sql_select_count_expr})
				FROM
					{$selection->sql_table_references}
				WHERE
					{$selection->sql_where_condition_required}
					{$selection->sql_where_condition_common}
					{$selection->sql_where_condition_additional}
					{$sql_join_content_tables}
SQL;

			if($selection->optimize_root_search_query
			&& sizeof($selection->getElementTypeConds())
			&& !sizeof(array_positive_values($selection->getHierarchyConds()))) {
				$types_in_clause = implode(", ", $selection->getElementTypeConds());

				if($selection->sql_table_references) {
					$selection->sql_table_references = "," . $selection->sql_table_references;
				}
				
				if($selection->sql_where_condition_required) {
					$selection->sql_where_condition_required = " AND " . $selection->sql_where_condition_required;
				}

				$sql = <<<SQL
SELECT DISTINCT h.id 
	FROM cms3_hierarchy hp 
		{$selection->sql_table_references}
		WHERE h.type_id IN ({$types_in_clause}) 
			AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$types_in_clause}))) {$selection->sql_part__domain_cond} {$selection->sql_part__lang_cond} 
			AND h.is_deleted = '0' 
			{$selection->sql_where_condition_required}
			{$selection->sql_where_condition_common}
			{$selection->sql_where_condition_additional}
				{$selection->sql_order_by}
				{$selection->sql_limit}
SQL;

				$sql_count = <<<SQL
SELECT COUNT(DISTINCT h.id) FROM cms3_hierarchy h, cms3_hierarchy hp WHERE h.type_id IN ({$types_in_clause}) AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$types_in_clause}))) {$selection->sql_part__domain_cond} {$selection->sql_part__lang_cond} AND h.is_deleted = '0'
SQL;
			}

			return array(
				'result' => $sql,
				'count' => $sql_count
			);

		}

		// ====================================================================
		// ====================================================================
		// ====================================================================
		/*
		Методы для формирования основных частей запроса
		- makeDistinctKeywords
		- makeStraitJoinKeyword
		- makeSelectExpr
		- makeTables
		- makeWhereConditions
		*/

		private static function makeDistinctKeywords(umiSelection $selection) {
			$selection->sql_kwd_distinct = '';
			$selection->sql_kwd_distinct_count = '';
			if ($selection->sql_cond__need_content || ($selection->sql_cond__need_hierarchy && $selection->sql_part__perms)) {
				$selection->sql_kwd_distinct = ' DISTINCT';
				$selection->sql_kwd_distinct_count = 'DISTINCT ';
			}
		}

		private static function makeStraitJoinKeyword(umiSelection $selection) {
			if ($selection->sql_cond__total_joins > MAX_SELECTION_TABLE_JOINS) {
				$selection->sql_kwd_straight_join = "STRAIGHT_JOIN";
			} else {
				$selection->sql_kwd_straight_join = "";
			}
		}

		private static function makeSelectExpr(umiSelection $selection) {
			if ($selection->sql_cond__need_hierarchy) {
				if($selection->sql_part__perms_tables) {
					$selection->sql_select_expr = "h.id, MAX(c3p.level)";
				} else {
					$selection->sql_select_expr = "h.id";
				}
				$selection->sql_select_count_expr = "h.id";
			} else {
				$selection->sql_select_expr = "o.id";
				$selection->sql_select_count_expr = "o.id";
			}
		}

		private static function makeTables(umiSelection $selection) {
			$s_other_tables = '';
			if ($selection->sql_cond__need_content) {
				$s_other_tables = $selection->sql_part__content_tables;
			}

			if ($selection->sql_cond__need_hierarchy) {
				if($selection->sql_part__content_tables || $selection->objectTableIsRequired) {
					$objects_table = "cms3_objects o,";
				} else {
					$objects_table = "";
				}

				$selection->sql_table_references .= <<<SQL
					{$objects_table}
					cms3_hierarchy h
					{$s_other_tables}
					{$selection->sql_part__perms_tables}
SQL;
			} else {
				$selection->sql_table_references .= <<<SQL
					cms3_objects o
					{$s_other_tables}
SQL;
			}
		}

		private static function makeWhereConditions(umiSelection $selection) {
		    if($selection->sql_part__owner) {
		        $selection->sql_part__owner = " AND " . $selection->sql_part__owner;
		    }
		    
			// common
			$selection->sql_where_condition_common = <<<SQL
					{$selection->sql_part__object_type}
					{$selection->sql_part__props_and_names}
					{$selection->sql_part__owner}
					{$selection->sql_part__objects}
					{$selection->sql_part__elements}
SQL;

			// required and additional
			if ($selection->sql_cond__need_hierarchy) {

				if($selection->sql_cond__need_hierarchy
				 && !$selection->sql_part__content_tables
				 && !$selection->objectTableIsRequired) {
					$objectsToHierarchyRelation = "";
				} else {
					$objectsToHierarchyRelation = "h.obj_id = o.id AND ";
				}

				$selection->sql_where_condition_required = <<<SQL
					{$objectsToHierarchyRelation}
					h.is_deleted = '0'
SQL;
				$selection->sql_where_condition_additional = <<<SQL
					{$selection->sql_part__hierarchy}
					{$selection->sql_part__unactive_cond}
					{$selection->sql_part__element_type}
					{$selection->sql_part__perms}
					{$selection->sql_part__lang_cond}
					{$selection->sql_part__domain_cond}
SQL;
			} else {
				$selection->sql_where_condition_required = "1";
				$selection->sql_where_condition_additional = "";
			}
		}

		// ====================================================================
		// ====================================================================
		// ====================================================================
		/*
		Методы для формирования промежуточных частей запроса
		и условий их сборки
		*/

		private static function makeLimitPart(umiSelection $selection) {
			$selection->sql_limit = "";
			//
			$limit_cond = $selection->getLimitConds();
			if ($limit_cond !== false) {
				if (is_array($limit_cond) && count($limit_cond) > 1 && is_numeric($limit_cond[0]) && is_numeric($limit_cond[1])) {

					$i_limit = intval($limit_cond[0]);
					$i_page = intval($limit_cond[1]);

					$i_offset = $i_page * $i_limit;

					// [LIMIT {[offset,] row_count | row_count OFFSET offset}]
					$selection->sql_limit = " LIMIT ".$i_offset.", ".$i_limit;

				}
			}
			//
			return $selection->sql_limit;
		}

		private static function makeHierarchyPart(umiSelection $selection) {
			$hierarchy_cond = $selection->getHierarchyConds();

			if (!empty($hierarchy_cond)) {
				$HierarchyRootCounter = 0;
				$HierarchyRelationsCounter = 0;
				$HierarchyRelationsConds = Array();
				foreach($hierarchy_cond as $parentFilter) {
					list($parentId, $depth) = $parentFilter;

					if($parentId == 0) {
						$HierarchyRootCounter++;
						--$depth;
					}

					$sql = "SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$parentId}";
					$result = l_mysql_query($sql);

					list($level) = mysql_fetch_row($result);

					$sqlRelPart = ($parentId > 0) ? "hr.rel_id = '{$parentId}'" : "hr.rel_id IS NULL";
					if($HierarchyRelationsCounter == 0) {
						$selection->sql_table_references .= "cms3_hierarchy_relations hr, ";
						$HierarchyRelationsCounter++;
					}
					$seekDepth = $depth + $level + 1;

					$HierarchyRelationsConds[] = <<<SQL
({$sqlRelPart} AND hr.level <= '{$seekDepth}') AND hr.child_id = h.id
SQL;
				}
				
				if(sizeof($HierarchyRelationsConds) > 0) {
					$selection->sql_part__hierarchy .= " AND ((" . implode(") OR (", $HierarchyRelationsConds) . ")) ";
				}

				$selection->sql_cond__domain_ignored = ($HierarchyRootCounter) ? false : true; // ?
				$selection->sql_cond__lang_ignored = ($HierarchyRootCounter) ? false : true; // ?
				
				$selection->sql_cond__need_hierarchy = true;

				if(sizeof($hierarchy_cond) == 1) {
					if($hierarchy_cond[0] != 0) {
						$selection->sql_cond_auto_domain = true;
					}
				} else {
					$selection->sql_cond_auto_domain = true;
				}
			}
		}

		private static function makeElementTypePart(umiSelection $selection) {
			$element_type_cond = $selection->getElementTypeConds();
			if ($element_type_cond && count($element_type_cond)) {
				$selection->sql_part__element_type = " AND h.type_id IN ('".implode("', '", $element_type_cond)."')";
				$selection->sql_cond__need_hierarchy = true;
			}
		}

		private static function makeOwnerPart(umiSelection $selection) {
			$owner_cond = $selection->getOwnerConds();
			if (is_array($owner_cond) && count($owner_cond)) {
				$selection->sql_part__owner = " o.owner_id IN ('".implode("', '", $owner_cond)."')";
				$selection->objectTableIsRequired = true;
			}
		}

		private static function makeObjectsPart(umiSelection $selection) {
			$objects_cond = $selection->getObjectsConds();
			if (is_array($objects_cond) && count($objects_cond)) {
				$selection->sql_part__objects = " AND o.id IN ('".implode("', '", $objects_cond)."')";
			}
		}

		private static function makeElementsPart(umiSelection $selection) {
			$elements_cond = $selection->getElementsConds();
			if (is_array($elements_cond) && count($elements_cond)) {
				$selection->sql_part__elements = " AND h.id IN ('".implode("', '", $elements_cond)."')";
				$selection->sql_cond__need_hierarchy = true;
			}
		}

		private static function makePermsParts(umiSelection $selection) {
			if ($perms_cond = $selection->getPermissionsConds()) {
				$perms_cond[] = 2373;
				if ($sz = sizeof($perms_cond)) {
					$selection->sql_part__perms_tables = ",cms3_permissions c3p";
					$selection->sql_cond__need_hierarchy = true;

					$permissionsLevel = $selection->getRequiredPermissionsLevel();

					for ($i = 0; $i < $sz; $i++) {

						$selection->sql_part__perms .= ($i === 0 ? " AND (" : "");
						$selection->sql_part__perms .= "(c3p.owner_id = '".$perms_cond[$i]."' AND c3p.rel_id = h.id AND c3p.level & '{$permissionsLevel}')";
						$selection->sql_part__perms .= ($i === ($sz - 1) ? ")" : " OR ");

					}
				}
			}
		}

		private static function makePropPart(umiSelection $selection) {
			if($searchStrings = $selection->getSearchStrings()) {
				$tableName = "cms3_object_content";
				
				$elements = $selection->getHierarchyConds();
				$elements = array_extract_values($elements);
				if(sizeof($elements)) {
					$objectTypeId = umiHierarchy::getInstance()->getDominantTypeId(array_pop($elements));
					$tableName = umiBranch::getBranchedTableByTypeId($objectTypeId);
				} else {
					$types = $selection->getElementTypeConds();
					if(is_array($types) && sizeof($types)) {
						$hierarchyTypeId = array_pop($types);
						if($hierarchyTypeId == 21 && sizeof($types)) {
							$hierarchyTypeId = array_pop($types);
						}
						
						if(umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId)) {
							$tableName .= "_" . $hierarchyTypeId;
						}
					}
				}


				$cname = "ct";

				$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
				$selection->usedContentTables[] = $cname;
				
				$fileFields = self::getFileFields();
				if(sizeof($fileFields) > 0) {
					$fileFieldsCond = " AND ct.field_id NOT IN (" . implode(", ", $fileFields) . ")";
				} else {
					$fileFieldsCond = "";
				}
				
				$searchConds = Array();
				foreach($searchStrings as $searchString) {
					$searchString = l_mysql_real_escape_string($searchString);
					$intCond = (is_numeric($searchString)) ? " OR ct.float_val = '{$searchString}' OR ct.int_val = '{$searchString}'" : "";
					$searchConds[] = "o.name LIKE '%{$searchString}%' OR ct.varchar_val LIKE '%{$searchString}%' OR ct.text_val LIKE '%{$searchString}%' {$intCond}" . $fileFieldsCond;
				}
				
				$selection->sql_arr_for_and_or_part['where'][] = "ct.obj_id = o.id AND (" . implode(" OR ", $searchConds) . ")";
				$selection->sql_cond__need_content = true;
			}
			
			if ($arr_propconds = $selection->getPropertyConds()) {

				$prop_cond = array();
				foreach ($arr_propconds as $arr_cond) {
					if ($arr_cond['type'] !== false) $prop_cond[] = $arr_cond;
				}
				unset($arr_propconds);
				
				if ($sz = sizeof($prop_cond)) {

					$i = 0;
					for ($i = 0; $i < $sz; $i++) {
						$arr_next_cond = $prop_cond[$i];
						$s_filter_type = (isset($arr_next_cond['filter_type']) ? $arr_next_cond['filter_type'] : '');
						$v_value = (isset($arr_next_cond['value']) ? $arr_next_cond['value'] : null);
						$i_field_id = (isset($arr_next_cond['field_id']) ? $arr_next_cond['field_id'] : 0);
						$s_type = (isset($arr_next_cond['type']) ? $arr_next_cond['type'] : '');

						
						
						if($s_type == 'optioned') {
							if(!is_array($v_value)) continue;
							$keys = array_keys($v_value);
							if(sizeof($keys) == 0) continue;
							list($s_type) = $keys;
							
							if(in_array($s_type, array('int', 'float', 'varchar', 'tree', 'rel')) == false) continue;
							$s_type .= "_val";
						}

						/* ? */
						if (!$selection->getConditionModeOr() || $selection->sql_cond__content_tables_loaded == 0) {
							$cname = "c" . (++$selection->sql_cond__content_tables_loaded); // имя очередной таблицы

							$tableName = self::chooseContentTableName($selection, $prop_cond[$i]['field_id']);
							$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
							$selection->usedContentTables[] = $cname;
						}
						
						$s_common = $cname.".obj_id = o.id AND ".$cname.".field_id = '".$i_field_id."'";
						if($s_type != 'optioned') {
							$s_field = $cname.".".$s_type;
						}

						switch ($s_filter_type) {

							case 'equal':
									if($v_value) {
										if (!is_array($v_value)) $v_value = array($v_value);
										$s_values = "'".(implode("', '", array_map('l_mysql_real_escape_string', $v_value)))."'";
										$s_next_cond = "(".$s_common." AND (".$s_field." IN (".$s_values.")))";
									} else {
										$v_value = l_mysql_real_escape_string($v_value);
										$s_next_cond = "({$s_common} AND ({$s_field} = '{$v_value}' OR {$s_field} IS NULL))";
									}
									
								break;

							case 'not_equal':
									if (!is_array($v_value)) $v_value = array($v_value);
									$s_values = "'".(implode("', '", array_map('l_mysql_real_escape_string', $v_value)))."'";

								$s_next_cond = "(".$s_common." AND ((".$s_field." IS NULL) OR (".$s_field." NOT IN (".$s_values."))))";
								break;

							case 'like':
									$b_need_percents = true;
									if (substr($v_value, 0, 1) === '%' || substr($v_value, -1) === '%') $b_need_percents = false;

									$s_value = l_mysql_real_escape_string($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." LIKE '".($b_need_percents ? "%" : "").$s_value.($b_need_percents ? "%" : "")."')";
								break;

							case 'between':
									$f_min = (isset($arr_next_cond['min']) ? floatval($arr_next_cond['min']) : 0);
									$f_max = (isset($arr_next_cond['max']) ? floatval($arr_next_cond['max']) : 0);

								$s_next_cond = "(".$s_common." AND ".$s_field." BETWEEN '".$f_min."' AND '".$f_max."')";
								break;

							case 'more':
									$f_value = floatval($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." >= '".$f_value."')";
								break;

							case 'less':
									$f_value = floatval($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." <= '".$f_value."')";
								break;

							case 'null':
								$s_next_cond = "(".$s_common." AND ".$s_field." IS NULL)";
								break;
							
							case 'notnull':
								$s_next_cond = "(".$s_common." AND ".$s_field." IS NOT NULL)";
								break;

							default:
								$s_next_cond = "";
								break;
						}

						if (strlen($s_next_cond)) {
							$selection->sql_arr_for_and_or_part['where'][] = $s_next_cond;
						}

					}

					if (count($selection->sql_arr_for_and_or_part)) {
						$selection->sql_cond__need_content = true;
						$selection->sql_cond__total_joins += $i;
					}
				}
			}
		}

		private static function makeObjectTypePart(umiSelection $selection) {
			$object_type_cond = $selection->getObjectTypeConds();
			if ($object_type_cond && count($object_type_cond)) {
				$selection->sql_part__object_type = " AND o.type_id IN ('".implode("', '", $object_type_cond)."')";
				$selection->objectTableIsRequired = true;
			}
		}

		private static function makeOrderPart(umiSelection $selection) {
			$order_cond = $selection->getOrderConds();
			if ($order_cond) {
				$i = 0;
				$selection->sql_order_by = " ORDER BY ";
				$sz = sizeof($order_cond);

				for ($i = 0; $i < $sz; $i++) {
					if ($native_field = $order_cond[$i]['native_field']) {
						switch($native_field) {
							case "name": {
								$selection->sql_order_by .= "o.name " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								$selection->sql_need_object_table = true;
								$selection->objectTableIsRequired = true;
								break;
							}

							case "object_id": {
								$selection->sql_order_by .= "o.id " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								$selection->objectTableIsRequired = true;
								break;
							}

							case "rand": {
								$selection->sql_order_by .= "RAND()";
								break;
							}
							
							case "ord": {
								if($selection->objectTableIsRequired && !$selection->sql_cond__need_hierarchy) {
									$selection->sql_order_by .= "o.ord " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								} else {
									$selection->sql_order_by .= "h.ord " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								}
								break;
							}
						}

						if ($i !== ($sz - 1)) {
							$selection->sql_order_by .= ", ";
						}

					} else {

						$selection->sql_cond__need_content = true;

						$cname = "c" . (++$selection->sql_cond__content_tables_loaded);

						$tableName = self::chooseContentTableName($selection, $order_cond[$i]['field_id']);
						$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
						$selection->usedContentTables[] = $cname;

						$selection->sql_arr_for_and_or_part['order'][] = "{$cname}.obj_id = o.id AND {$cname}.field_id = '{$order_cond[$i]['field_id']}'";


						$selection->sql_order_by .= "{$cname}.{$order_cond[$i]['type']} " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
						if ($i == ($sz - 1)) {
						} else {
							$selection->sql_order_by .= ", ";
						}
					}
				}

				if ($selection->sql_order_by == " ORDER BY ") {
					$selection->sql_order_by = "";
				}
				$selection->sql_cond__total_joins += $i;
			} elseif ($selection->sql_cond__need_hierarchy == true) {
				$selection->sql_order_by = " ORDER BY h.ord";
			}
		}

		private static function makeNamesPart(umiSelection $selection) {
			$arr_names_parts = array();
			if (($names_cond = $selection->getNameConds()) && count($names_cond)) {
				foreach ($names_cond as $arr_next_name) {

					$cname = $arr_next_name['value'];

					$b_need_percents = true;
					if (substr($cname, 0, 1) === '%' || substr($cname, -1) === '%') $b_need_percents = false;

					$cname = l_mysql_real_escape_string($cname);

					if ($arr_next_name['type'] == 'exact') {
						$arr_names_parts[] = "o.name = '".$cname."'";
					} else {
						$arr_names_parts[] = "o.name LIKE '".($b_need_percents ? "%" : "").$cname.($b_need_percents ? "%" : "")."'";
					}
					$selection->objectTableIsRequired = true;
				}
			}
			if (count($arr_names_parts)) {
				$selection->sql_arr_for_and_or_part['where'][] = "(".implode(' OR ', $arr_names_parts).")";
			}
		}

		private static function makePropsAndNames(umiSelection $selection) {
			$selection->sql_part__props_and_names = "";
			if($selection->sql_part__owner) {
			    $selection->sql_arr_for_and_or_part['where'][] = $selection->sql_part__owner;
			    $selection->sql_part__owner = "";
			}
			
			if (isset($selection->sql_arr_for_and_or_part['where'])) {
				$s_concat_mode = ($selection->getConditionModeOr() ? ' OR ' : ' AND ');
				$selection->sql_part__props_and_names = " AND (" . implode($s_concat_mode, $selection->sql_arr_for_and_or_part['where']) . ")";
			}
			
			if(isset($selection->sql_arr_for_and_or_part['order'])) {
					$selection->sql_part__props_and_names .= " AND (" . implode(" AND ", $selection->sql_arr_for_and_or_part['order']) . ")";
			}
		}

		private static function makeHierarchySpecificConds(umiSelection $selection) {
			if ($selection->sql_cond__need_hierarchy == true) {
				{
					if(!$selection->sql_cond__lang_ignored) {
						if(($langId = $selection->getLangId()) == false) {
							$langId = (int) cmsController::getInstance()->getCurrentLang()->getId();
						}
						$selection->sql_part__lang_cond = " AND h.lang_id = '" . $langId . "' ";
					}
				}

				if (!$selection->sql_cond__domain_ignored) {

					if(($domainId = $selection->getDomainId()) == false) {
						$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
					}
					$selection->sql_part__domain_cond = " AND h.domain_id = '" . (int) $domainId . "' ";
				} else {
					$selection->sql_part__domain_cond = "";
				}

				if ($active_cond = $selection->getActiveConds()) {
					$is_active = (isset($active_cond[0]) && (bool) $active_cond[0])? 1 : 0;
					$selection->sql_part__unactive_cond = " AND h.is_active = '".$is_active."' ";
				} else {
					$selection->sql_part__unactive_cond = (cmsController::getInstance()->getCurrentMode() == "") ? " AND h.is_active = '1' " : "";
				}
			}
		}


		protected static function chooseContentTableName(umiSelection $selection, $fieldId) {
			$hierarchyTypes = $selection->getElementTypeConds();
			$objectTypes = $selection->getObjectTypeConds();

			if(!is_array($hierarchyTypes)) {
				$hierarchyTypes = Array();
			} else {
				$hierarchyTypes = array_extract_values($hierarchyTypes);
			}

			if(!is_array($objectTypes)) {
				$objectTypes = Array();
			} else {
				$objectTypes = array_extract_values($objectTypes);
			}

			if(sizeof($hierarchyTypes) == 1) {
				reset($hierarchyTypes);
				$hierarchyTypeId = current($hierarchyTypes);
				$isBranched = umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId);
				return $isBranched ? "cms3_object_content_{$hierarchyTypeId}" : "cms3_object_content";
			}

			if(sizeof($hierarchyTypes) > 1) {
				$objectTypeId = self::getObjectTypeByFieldId($fieldId);
				return umiBranch::getBranchedTableByTypeId($objectTypeId);
			}

			if(sizeof($hierarchyTypes) == 0) {
				if(sizeof($objectTypes) == 1) {
					reset($objectTypes);
					$objectTypeId = current($objectTypes);
				} else {
					$objectTypeId = self::getObjectTypeByFieldId($fieldId);
				}
				return umiBranch::getBranchedTableByTypeId($objectTypeId);
			}

			return "cms3_object_content";
		}


		public static function getObjectTypeByFieldId($fieldId) {
			static $cache = Array();
			$fieldId = (int) $fieldId;

			if(isset($cache[$fieldId])) {
				return $cache[$fieldId];
			}

			$sql = <<<SQL
SELECT MIN(fg.type_id)
	FROM cms3_fields_controller fc, cms3_object_field_groups fg
	WHERE fc.field_id = {$fieldId} AND fg.id = fc.group_id
SQL;
			if($objectTypeId = cacheFrontend::getInstance()->loadSql($sql)) {
				return $cache[$fieldId] = $objectTypeId;
			}

			$result = l_mysql_query($sql);

			if(mysql_num_rows($result)) {
				list($objectTypeId) = mysql_fetch_row($result);
			} else {
				$objectTypeId = false;
			}
			$cache[$fieldId] = $objectTypeId;
			cacheFrontend::getInstance()->saveSql($sql, $objectTypeId, 60);
			return $objectTypeId;
		}
		
		protected static function excludeNestedPages($arr) {
			$hierarchy = umiHierarchy::getInstance();
			
			$result = Array();
			foreach($arr as $elementId) {
				$element = $hierarchy->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					if(in_array($element->getRel(), $arr)) {
						continue;
					} else {
						$result[] = $elementId;
					}
				}
			}
			return $result;
		}
		
		
		protected static function getFileFields() {
			static $cache = false;
			if($cache) return $cache;
			
			$sql = <<<SQL
SELECT of.id 
	FROM cms3_object_fields of, cms3_object_field_types oft
		WHERE of.field_type_id = oft.id 
		AND oft.data_type IN ('file', 'img_file', 'swf_file')
SQL;
			$result = l_mysql_query($sql);
			
			$res = array();
			while(list($fieldId) = mysql_fetch_row($result)) {
				$res[] = $fieldId;
			}
			
			return $cache = $res;
		}
	};


/**
	* Этот класс служит для управления свойствами типа поля
*/
	class umiFieldType extends umiEntinty implements iUmiEntinty, iUmiFieldType {
		private $name, $data_type, $is_multiple = false, $is_unsigned = false;
		protected $store_type = "field_type";

		/**
			* Получить описание типа
			* @return String описание типа
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Узнать, может ли значение поля данного типа состоять из массива значений (составной тип)
			* @return Boolean true, если тип составной
		*/
		public function getIsMultiple() {
			return $this->is_multiple;
		}


		/**
			* Узнать, может ли значение поля данного типа иметь знак.
			* Зарезервировано и пока не используется
			* @return Boolean true, если значение поля не будет иметь знак
		*/
		public function getIsUnsigned() {
			return $this->is_unsigned;
		}

		/**
			* Получить идентификатор типа
			* @return String идентификатор типа
		*/
		public function getDataType() {
			return $this->data_type;
		}

		/**
			* Получить список всех поддерживаемых идентификаторов типа
			* @return Array список идентификаторов
		*/
		public static function getDataTypes() {
			return

			Array	(
				"int",
				"string",
				"text",
				"relation",
				"file",
				"img_file",
				"video_file",
				"swf_file",
				"date",
				"boolean",
				"wysiwyg",
				"password",
				"tags",
				"symlink",
				"price",
				"formula",
				"float",
				"counter",
				"optioned"
				);
		}

		/**
			* Получить имя поля таблицы БД, где будут хранится данные по идентификатору типа
			* @param String $data_type идентификатор типа
			* @return String имя поля таблицы БД, либо false, если связь не обнаружена
		*/
		public static function getDataTypeDB($data_type) {
			$rels = Array	(
				"int"		 => "int_val",
				"string"	 => "varchar_val",
				"text"		 => "text_val",
				"relation"	 => "rel_val",
				"file"		 => "text_val",
				"img_file"	 => "text_val",
				"swf_file"	 => "text_val",
				"video_file" => "text_val",
				"date"		 => "int_val",
				"boolean"	 => "int_val",
				"wysiwyg"	 => "text_val",
				"password"	 => "varchar_val",
				"tags"		 => "varchar_val",
				"symlink"	 => "tree_val",
				"price"		 => "float_val",
				"formula"	 => "varchar_val",
				"float"		 => "float_val",
				"counter"	 => "counter",
				"optioned"	 => "optioned"
				);

			if(array_key_exists($data_type, $rels) === false) {
				return false;
			} else {
				return $rels[$data_type];
			}
		}

		/**
			* Узнать, поддерживается ли идентификатор типа 
			* @param String $data_type идентификатор типа
			* @return Boolean true, если идентификатор типа поддерживается
		*/
		public static function isValidDataType($data_type) {
			return in_array($data_type, self::getDataTypes());
		}



		/**
			* Задать новое описание типа
			* Устанавливает флаг "Модифицирован".
			* @param String $name
		*/
		public function setName($name) {
			$name = $this->translateI18n($name, "field-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли значение поля данного типа состоять из массива значений (составной тип)
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_multiple
		*/
		public function setIsMultiple($is_multiple) {
			$this->is_multiple = (bool) $is_multiple;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли значение поля данного типа иметь знак.
			* Зарезервировано и пока не используется
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_unsigned
		*/
		public function setIsUnsigned($is_unsigned) {
			$this->is_unsigned = (bool) $is_unsigned;
			$this->setIsUpdated();
		}

		/**
			* Установить идентификатор типа
			* Устанавливает флаг "Модифицирован".
			* @param String $data_type идентификатор типа
			* @return Boolean true, если удалось установить, false - если идентификатор не поддерживается
		*/
		public function setDataType($data_type) {
			if(self::isValidDataType($data_type)) {
				$this->data_type = $data_type;
				$this->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}


		/**
			* Загружает необходимые данные для формирования объекта umiFieldType из БД.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, data_type, is_multiple, is_unsigned FROM cms3_object_field_types WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
			
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $data_type, $is_multiple, $is_unsigned) = $row) {
				if(!self::isValidDataType($data_type)) {
					throw new coreException("Wrong data type given for filed type #{$this->id}");
					return false;
				}

				$this->name = $name;
				$this->data_type = $data_type;
				$this->is_multiple= (bool) $is_multiple;
				$this->is_unsigned = (bool) $is_unsigned;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Сохранить все модификации объекта в БД.
			* @return Boolean true в случае успеха
		*/
		protected function save() {
			$name = mysql_real_escape_string($this->name);
			$data_type = mysql_real_escape_string($this->data_type);
			$is_multiple = (int) $this->is_multiple;
			$is_unsigned = (int) $this->is_unsigned;

			$sql = "UPDATE cms3_object_field_types SET name = '{$name}', data_type = '{$data_type}', is_multiple = '{$is_multiple}', is_unsigned = '{$is_unsigned}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}
	}


/**
	* Этот класс служит для управления свойствами поля
*/
	class umiField extends umiEntinty implements iUmiEntinty, iUmiField {
		private $name, $title, $is_locked = false, $is_inheritable = false, $is_visible = true;
		private  $field_type_id, $guide_id, $isRequired, $restrictionId, $sortable = false;
		private $is_in_search = true, $is_in_filter = true, $tip = NULL, $is_system;
		protected $store_type = "field";


		/**
			* Получить имя поля (строковой идентификатор)
			* @return String имя поля (строковой идентификатор)
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название поля
			* @return String название поля
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/**
			* Узнать, заблокировано ли поле на изменение свойств
			* @return Boolean true если поле заблокировано
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Узнать, наследуется ли значение поля. Зарезервировано, но пока не используется.
			* @return Boolean true если поле наследуется
		*/
		public function getIsInheritable() {
			return $this->is_inheritable;
		}

		/**
			* Узнать видимость поля для пользователя
			* @return Boolean true если поле видимое для пользователя
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Получить id типа данных поля (см. класс umiFieldType)
			* @return Integer id типа данных поля
		*/
		public function getFieldTypeId() {
			return $this->field_type_id;
		}

		/**
			* Получить тип данных поля (экземпляр класса umiFieldType)
			* @return umiFieldType экземпляр класса umiFieldType, соответствующий полю, либо false в случае неудачи
		*/
		public function getFieldType() {
			return umiFieldTypesCollection::getInstance()->getFieldType($this->field_type_id);
		}

		/**
			* Получить id справочника, с которым связано поле (Справочник - это тип данных)
			* @return Integer id справочника, либо NULL, если полю не связано со справочником 
		*/
		public function getGuideId() {
			return $this->guide_id;
		}

		/**
			* Узнать, индексируется ли поле для поиска
			* @return Boolean true если поле индексируется
		*/
		public function getIsInSearch() {
			return $this->is_in_search;
		}

		/**
			* Узнать, может ли поле учавствать в фильтрах
			* @return Boolean true если поле может учавствать в фильтрах
		*/
		public function getIsInFilter() {
			return $this->is_in_filter;
		}

		/**
			* Получить подсказку (короткую справку) для поля.
			* @return String подсказка (короткая справка) для поля
		*/
		public function getTip() {
			return $this->tip;
		}
		
		/**
		* Узнать, является ли поле системным
		* 
		* @return Boolean
		*/
		public function getIsSystem() {
			return $this->is_system;
		}
		
		/**
		* Указать будет ли поле системным
		* 		
		* @param Boolean $isSystem true, если системное, иначе false
		*/
		public function setIsSystem($isSystem = false) {
			$this->is_system = (bool) $isSystem;
		}


		/**
			* Задать новое имя поля (строковой идентификатор).
			* Устанавливает флаг "Модифицирован".
			* @param String $name имя поля
		*/
		public function setName($name) {
			$name = str_replace("-", "_", $name);
			$name = umiHierarchy::convertAltName($name);
			$this->name = umiObjectProperty::filterInputString($name);
			if(!strlen($this->name)) $this->name = '_';
			$this->setIsUpdated();
		}


		/**
			* Задать новое описание поля.
			* Устанавливает флаг "Модифицирован".
			* @param String $title описание поля
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "field-");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		/**
			* Выставить полю статус "Заблокирован/Разблокирован".
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_locked true, если заблокировано, иначе false
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		/**
			* Указать наследуется ли значение поля.
			* Зарезервировано, но пока не используется.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_inheritable зарезервировано, не используется
		*/
		public function setIsInheritable($is_inheritable) {
			$this->is_inheritable = (bool) $is_inheritable;
			$this->setIsUpdated();
		}

		/**
			* Указать видимо ли поле для пользователя.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_visible true, если видимо, иначе false
		*/
		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		/**
			* Установить id типа поля (см. класс umiFieldType)
			* @param Integer $field_type_id идентификатор типа поля
		*/
		public function setFieldTypeId($field_type_id) {
			$this->field_type_id = (int) $field_type_id;
			$this->setIsUpdated();
			return true;
		}

		/**
			* Связать поле со указаным справочником (Справочник - это тип данных)
			* @param Integer $guide_id идентификтор справочника
		*/
		public function setGuideId($guide_id) {
			$this->guide_id = (int) $guide_id;
			$this->setIsUpdated();
		}

		/**
			* Указать будет ли поле индексироваться для поиска.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_in_search true, если использовать для поиска, иначе false
		*/
		public function setIsInSearch($is_in_search) {
			$this->is_in_search = (bool) $is_in_search;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли поле учавствовать в фильтрах.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_in_filter true, если использовать в фильтрах, иначе false
		*/
		public function setIsInFilter($is_in_filter) {
			$this->is_in_filter = (bool) $is_in_filter;
			$this->setIsUpdated();
		}

		/**
			* Установить новую подсказку (короткую справку) для поля.
			* Устанавливает флаг "Модифицирован".
			* @param String $tip подсказка
		*/
		public function setTip($tip) {
			$this->tip = umiObjectProperty::filterInputString($tip);
			$this->setIsUpdated();
		}
		
		/**
			* Проверить, является ли поле обязательным для заполнения
			* @return Boolean true, если поле обязательно для заполнения, иначе false
		*/
		public function getIsRequired() {
			return $this->isRequired;
		}
		
		/**
			* Установить, что поле является обязательным для заполнения
			* @param Boolean $isRequired true, если поле обязательно для заполнения, иначе false
		*/
		public function setIsRequired($isRequired = false) {
			$this->isRequired = (bool) $isRequired;
			$this->setIsUpdated();
		}
		
		/**
			* Получить идентификатор формата значение (restriction), по которому валидируется значение поля
			* @return Integer идентификатор формата значение (restriction) (класс baseRestriction и потомки)
		*/
		public function getRestrictionId() {
			return $this->restrictionId;
		}
		
		/**
			* Изменить id restriction'а, по которому валидируется значение поля
			* @param Integer|Boolean $restrictionId = false id рестрикшена, либо false
		*/
		public function setRestrictionId($restrictionId = false) {
			$this->restrictionId = (int) $restrictionId;
		}
		
		/**
			* Проверить, является ли поле сортируемым
			* @return Boolean состояние сортировки
		*/
		public function getIsSortable() {
			return $this->sortable;
		}
		
		/**
			* Установить поле сортируемым
			* @param Boolean $sortable = false флаг сортировки
		*/
		public function setIsSortable($sortable = false) {
			$this->sortable = (bool) $sortable;
		}
		
		/**
		* Получить идентификатор типа данных
		* @return String идентификатор типа данных
		*/
		public function getDataType() {
			$fieldTypes = umiFieldTypesCollection::getInstance();
			return $fieldTypes->getFieldType($this->field_type_id)->getDataType();
		}

		/**
			* Загружает необходимые данные для формирования объекта umiField из БД.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, is_locked, is_inheritable, is_visible, field_type_id, guide_id, in_search, in_filter, tip, is_required, sortable, is_system, restriction_id FROM cms3_object_fields WHERE id = '{$this->id}'";
				
				$result = l_mysql_query($sql);
			
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $sortable, $is_system, $restrictionId) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->is_locked = (bool) $is_locked;
				$this->is_inheritable = (bool) $is_inheritable;
				$this->is_visible = (bool) $is_visible;
				$this->field_type_id = (int) $field_type_id;
				$this->guide_id = (int) $guide_id;
				$this->is_in_search = (bool) $in_search;
				$this->is_in_filter = (bool) $in_filter;
				$this->tip = (string) $tip;
				$this->isRequired = (bool) $is_required;
				$this->sortable = (bool) $sortable;
				$this->is_system = (bool) $is_system;
				$this->restrictionId = (int) $restrictionId;
			} else {
				return false;
			}
		}

		/**
			* Сохранить все модификации объекта в БД.
			* @return Boolean true в случае успеха
		*/
		protected function save() {
			$name = mysql_real_escape_string($this->name);
			$title = mysql_real_escape_string($this->title);
			$is_locked = (int) $this->is_locked;
			$is_inheritable = (int) $this->is_inheritable;
			$is_visible = (int) $this->is_visible;
			$field_type_id = (int) $this->field_type_id;
			$guide_id = (int) $this->guide_id;
			$in_search = (int) $this->is_in_search;
			$in_filter = (int) $this->is_in_filter;
			$tip = (string) $this->tip;
			$isRequired = (int) $this->isRequired;
			$sortable = (int) $this->sortable;
			$restrictionId = (int) $this->restrictionId;
			$is_system = (int) $this->is_system;
			$restrictionSql = $restrictionId ? ", restriction_id = '{$restrictionId}'" : ", restriction_id = NULL";

			$sql = "UPDATE cms3_object_fields SET name = '{$name}', title = '{$title}', is_locked = '{$is_locked}', is_inheritable = '{$is_inheritable}', is_visible = '{$is_visible}', field_type_id = '{$field_type_id}', guide_id = '{$guide_id}', in_search = '{$in_search}', in_filter = '{$in_filter}', tip = '{$tip}', is_required = '{$isRequired}', sortable = '{$sortable}', is_system = '{$is_system}' {$restrictionSql} WHERE id = '{$this->id}'";
			
			l_mysql_query($sql);
			cacheFrontend::getInstance()->flush();
			
			return true;
		}
	}


/**
	* Этот класс реализует объединение полей в именованные группы.
*/
	class umiFieldsGroup extends umiEntinty implements iUmiEntinty, iUmiFieldsGroup {
		private	$name, $title,
			$type_id, $ord,
			$is_active = true, $is_visible = true, $is_locked = false,

			$autoload_fields = false,

			$fields = Array();
		
		protected $store_type = "fields_group";

		/**
			* Получить строковой id группы
			* @return String строковой id группы
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название группы
			* @return String название группы в текущей языковой версии
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/**
			* Получить id типа данных, к которому относится группа полей
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}

		/**
			* Получить порядковый номер группы, по которому она сортируется в рамках типа данных
			* @return Integer порядковый номер
		*/
		public function getOrd() {
			return $this->ord;
		}

		/**
			* Узнать, активна ли группа полей
			* @return Boolean значение флага активности
		*/
		public function getIsActive() {
			return $this->is_active;
		}

		/**
			* Узнать, видима ли группа полей
			* @return Boolean значение флага видимости
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Узнать, заблокирована ли группа полей (разработчиком)
			* @return Boolean значение флага блокировка
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Изменить строковой id группы на $name
			* @param String $name новый строковой id группы полей
		*/
		public function setName($name) {
			$this->name = umiObjectProperty::filterInputString($name);
			$this->setIsUpdated();
		}

		/**
			* Изменить название группы полей
			* @param $title новое название группы полей
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "fields-group");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		/**
			* Изменить тип данных, которому принадлежит группа полей
			* @param Integer $type_id id нового типа данных (класс umiObjectType)
			* @return Boolean true, если такое изменение возможно, иначе false
		*/
		public function setTypeId($type_id) {
			$types = umiObjectTypesCollection::getInstance();
			if($types->isExists($type_id)) {
				$this->type_id = $type_id;
				return true;
			} else {
				return false;
			}
		}

		/**
			* Установить новое значение порядка сортировки
			* @param Integer $ord новый порядковый номер
		*/
		public function setOrd($ord) {
			$this->ord = $ord;
			$this->setIsUpdated();
		}

		/**
			* Изменить активность группы полей
			* @param Boolean $is_active новое значение флага активности
		*/
		public function setIsActive($is_active) {
			$this->is_active = (bool) $is_active;
			$this->setIsUpdated();
		}

		/**
			* Изменить видимость группы полей
			* @param Boolean $is_visible новое значение флага видимости
		*/
		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		/**
			* Изменить стостояние блокировки группы полей
			* @param Boolean $is_locked новое значение флага блокировки
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, type_id, is_active, is_visible, is_locked, ord FROM cms3_object_field_groups WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $type_id, $is_active, $is_visible, $is_locked, $ord) = $row) {
				if(!umiObjectTypesCollection::getInstance()->isExists($type_id)) {
					return false;
				}

				$this->name = $name;
				$this->title = $title;
				$this->type_id = $type_id;
				$this->is_active = (bool) $is_active;
				$this->is_visible = (bool) $is_visible;
				$this->is_locked = (bool) $is_locked;
				$this->ord = (int) $ord;

				if($this->autoload_fields) {
					return $this->loadFields();
				} else {
					return true;
				}
			} else {
				return false;
			}
		}


		protected function save() {
			$name = mysql_real_escape_string($this->name);
			$title = mysql_real_escape_string($this->title);
			$type_id = (int) $this->type_id;
			$is_active = (int) $this->is_active;
			$is_visible = (int) $this->is_visible;
			$ord = (int) $this->ord;
			$is_locked = (int) $this->is_locked;

			$sql = "UPDATE cms3_object_field_groups SET name = '{$name}', title = '{$title}', type_id = '{$type_id}', is_active = '{$is_active}', is_visible = '{$is_visible}', ord = '{$ord}', is_locked = '{$is_locked}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}

		/**
			* Не используйте этот метод в прикладном коде.
		*/
		public function loadFields($rows = false) {
			$fields = umiFieldsCollection::getInstance();

			if($rows === false) {
				$sql = "SELECT cof.id, cof.name, cof.title, cof.is_locked, cof.is_inheritable, cof.is_visible, cof.field_type_id, cof.guide_id, cof.in_search, cof.in_filter, cof.tip, cof.is_required FROM cms3_fields_controller cfc, cms3_object_fields cof WHERE cfc.group_id = '{$this->id}' AND cof.id = cfc.field_id ORDER BY cfc.ord ASC";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				while(list($field_id) = $row = mysql_fetch_row($result)) {
					if($field = $fields->getField($field_id, $row)) {
						$this->fields[$field_id] = $field;
					}
				}

			} else {
				foreach($rows as $row) {
					list($field_id) = $row;
					if($field = $fields->getField($field_id, $row)) {
						$this->fields[$field_id] = $field;
					}
				}
			}
		}

		/**
			* Получить список всех полей в группе
			* @return Array массив из объектов umiField
		*/
		public function getFields() {
			return $this->fields;
		}

		private function isLoaded($field_id) {
			return (bool) array_key_exists($field_id, $this->fields);
		}

		/**
			* Присоединить к группе еще одно поле
			* @param Integer $field_id id присоединяемого поля
			* @param Boolean $ignor_loaded если true, то можно будет добавлять уже внесенные в эту группу поля
		*/
		public function attachField($field_id, $ignore_loaded = false) {
			if($this->isLoaded($field_id) && !$ignore_loaded) {
				return true;
			} else {
				$field_id = (int) $field_id;

				$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				list($ord) = mysql_fetch_row($result);
				$ord += 5;

				$sql = "INSERT INTO cms3_fields_controller (field_id, group_id, ord) VALUES('{$field_id}', '{$this->id}', '{$ord}')";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				cacheFrontend::getInstance()->flush();

				$fields = umiFieldsCollection::getInstance();
				$field = $fields->getField($field_id);
				$this->fields[$field_id] = $field;
				
				$this->fillInContentTable($field_id);
			}
		}
		
		
		protected function fillInContentTable($field_id) {
			$type_id = $this->type_id;
		
			$sql = "INSERT INTO cms3_object_content (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val) SELECT id, {$field_id}, NULL, NULL, NULL, NULL, NULL, NULL FROM cms3_objects WHERE type_id = {$type_id}";
			l_mysql_query($sql);
			
			if($err = mysql_error()) {
				throw new coreException($err);
			}
			
		}

		/**
			* Вывести поле из группы полей.
			* При этом поле физически не удаляется, так как может одновременно фигурировать в разный группах полей разных типов данных.
			* @param Integer $field_id id поля (класс umiField)
			* @return Boolean результат операции
		*/
		public function detachField($field_id) {
			if($this->isLoaded($field_id)) {
				$field_id = (int) $field_id;

				$sql = "DELETE FROM cms3_fields_controller WHERE field_id = '{$field_id}' AND group_id = '{$this->id}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->fields[$field_id]);

				$sql = "SELECT COUNT(*) FROM cms3_fields_controller WHERE field_id = '{$field_id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				cacheFrontend::getInstance()->flush();

				if(list($c) = mysql_fetch_row($result)) {
					return ($c == 0) ? umiFieldsCollection::getInstance()->delField($field_id) : true;
				} else return false;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Переместить поле $field_id после поля $after_field_id в группе $group_id
			* @param Integer $field_id id перемещаемого поля
		 	* @param Integer $after_field_id id поля, после которого нужно расположить перемещаемое поле
		 	* @param Integer $group_id id группы полей, в которой производятся перемещения
		 	* @param Boolean $is_last переместить в конец
		 	* @return Boolean результат операции
		*/
		public function moveFieldAfter($field_id, $after_field_id, $group_id, $is_last) {
			if($after_field_id == 0) {
				$neword = 0;
			} else {
				$sql = "SELECT ord FROM cms3_fields_controller WHERE group_id = '{$group_id}' AND field_id = '{$after_field_id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				} else {
					list($neword) = mysql_fetch_row($result);
				}
			}

			if($is_last) {
				$sql = "UPDATE cms3_fields_controller SET ord = (ord + 1) WHERE group_id = '{$this->id}' AND ord >= '{$neword}'";

				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$group_id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				list($neword) = mysql_fetch_row($result);
				++$neword;
			}

			$sql = "UPDATE cms3_fields_controller SET ord = '{$neword}', group_id = '$group_id' WHERE group_id = '{$this->id}' AND field_id = '{$field_id}'";
			l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}

		public function commit() {
			parent::commit();
			cacheFrontend::getInstance()->flush();
		}
		
		/**
			* Получить список всех групп с названием $name вне зависимости от типа данных
			* @param String $name название группы полей
			* @return Array массив из объектов umiFieldsGroup
		*/
		public static function getAllGroupsByName($name) {
			if($name) {
				$name = mysql_real_escape_string($name);
			} else {
				return false;
			}
			
			$sql = "SELECT `id` FROM `cms3_object_field_groups` WHERE `name` = '{$name}'";
			$result = l_mysql_query($sql);
			
			$groups = Array();
			while(list($groupId) = mysql_fetch_row($result)) {
				$group = new umiFieldsGroup($groupId);
				if($group instanceof umiFieldsGroup) {
					$groups[] = $group;
				}
			}
			return $groups;
		}
	};


/**
	* Этот класс служит для управления свойствами типа данных
*/
	class umiObjectType extends umiEntinty implements iUmiEntinty, iUmiObjectType {
		private $name, $parent_id, $is_locked = false;
		private $field_groups = Array(), $field_all_groups = Array();
		private $is_guidable = false, $is_public = false, $hierarchy_type_id;
		private $sortable = false;
		protected $store_type = "object_type";

		/**
			* Получить название типа.
			* @return String название типа
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Изменить название типа.
			* @param String $name новое название типа данных
		*/
		public function setName($name) {
			$name = $this->translateI18n($name, "object-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Узнать, заблокирован ли тип данных. Если тип данных заблокирован, то его нельзя удалить из системы.
			* @return Boolean true если тип данных заблокирован
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Изменить флаг блокировки у типа данных. Если тип данных заблокирован, его нельзя будет удалить.
			* @param Boolean $is_locked флаг блокировки
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		/**
			* Получить id родительского типа данных, от которого унаследованы группы полей и поля
			* @return Integer id родительского типа данных
		*/
		public function getParentId() {
			return $this->parent_id;
		}

		/**
			* Узнать, помечен ли тип данных как справочник.
			* @return Boolean true, если тип данных помечен как справочник
		*/
		public function getIsGuidable() {
			return $this->is_guidable;
		}

		/**
			* Изменить флаг "Справочник" у типа данных.
			* @param Boolean $is_guidable новое значение флага "Справочник"
		*/
		public function setIsGuidable($is_guidable) {
			$this->is_guidable = (bool) $is_guidable;
			$this->setIsUpdated();
		}

		/**
			* Установить флаг "Общедоступный" для справочника. Не имеет значение, если тип данных не является справочником.
			* @return Boolean true если тип данных общедоступен
		*/
		public function getIsPublic() {
			return $this->is_public;
		}

		/**
			* Изменить значение флага "Общедоступен" для типа данных. Не имеет значения, если тип данных не является справочником.
			* @param Boolean $is_public новое значение флага "Общедоступен"
		*/
		public function setIsPublic($is_public) {
			$this->is_public = (bool) $is_public;
			$this->setIsUpdated();
		}

		/**
			* Получить id базового типа, к которому привязан тип данных (класс umiHierarchyType).
			* @return Integer id базового типа данных (класс umiHierarchyType)
		*/
		public function getHierarchyTypeId() {
			return $this->hierarchy_type_id;
		}
		
		/**
			* Проверить, являются ли объекты этого типа сортируемыми
			* @return Boolean состояние сортировки
		*/
		public function getIsSortable() {
			return $this->sortable;
		}
		
		/**
			* Установить тип сортируемым
			* @param Boolean $sortable = false флаг сортировки
		*/
		public function setIsSortable($sortable = false) {
			$this->sortable = (bool) $sortable;
		}

		/**
			* Изменить базовый тип (класс umiHierarchyType), к которому привязан тип данных.
			* @param Integer $hierarchy_type_id новый id базового типа (класс umiHierarchyType)
		*/
		public function setHierarchyTypeId($hierarchy_type_id) {
			$this->hierarchy_type_id = (int) $hierarchy_type_id;
			$this->setIsUpdated();
		}

		/**
			* Добавить в тип данных новую группу полей (класс umiFieldsGroup)
			* @param String $name - строковой идентификатор группы полей
			* @param String $title - название группы полей
			* @param Boolean $is_active=true флаг активности группы полей (всегда true)
			* @param Boolean $is_visible=true видимость группы полей
			* @return Integer id созданной группы полей
		*/
		public function addFieldsGroup($name, $title, $is_active = true, $is_visible = true) {
			if($group = $this->getFieldsGroupByName($name)) {
				return $group->getId();
			}
			
			$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			if(list($ord) = mysql_fetch_row($result)) {
				$ord = ((int) $ord) + 5;
			} else {
				$ord = 1;
			}

			$sql = "INSERT INTO cms3_object_field_groups (type_id, ord) VALUES('{$this->id}', '{$ord}')";
			l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_group_id = mysql_insert_id();

			$field_group = new umiFieldsGroup($field_group_id);
			$field_group->setName($name);
			$field_group->setTitle($title);
			$field_group->setIsActive($is_active);
			$field_group->setIsVisible($is_visible);
			$field_group->commit();

			$this->field_groups[$field_group_id] = $field_group;
			$this->field_all_groups[$field_group_id] = $field_group;


			$child_types = umiObjectTypesCollection::getInstance()->getSubTypesList($this->id);
			$sz = sizeof($child_types);
			for($i = 0; $i < $sz; $i++) {
				$child_type_id = $child_types[$i];
					
				if($type = umiObjectTypesCollection::getInstance()->getType($child_type_id)) {
					$type->addFieldsGroup($name, $title, $is_active, $is_visible);
				} else {
					throw new coreException("Can't load object type #{$child_type_id}");
				}
			}
			
			cacheFrontend::getInstance()->flush();
			
			return $field_group_id;
		}

		/**
			* Удалить группу полей (класс umiFieldsGroup).
			* @param Integer $field_group_id id группы, которую необходимо удалить
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delFieldsGroup($field_group_id) {
			if($this->isFieldsGroupExists($field_group_id)) {
				$field_group_id = (int) $field_group_id;
				$sql = "DELETE FROM cms3_object_field_groups WHERE id = '{$field_group_id}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->field_groups[$field_group_id]);
				
				cacheFrontend::getInstance()->flush();
				return true;

			} else {
				return false;
			}
		}

		/**
			* Получить группу полей (класс umiFieldsGroup) по ее строковому идентификатору
			* @param String $field_group_name строковой идентификатор группы полей
			* @param String $allow_disabled разрешить получать не активные группы
			* @return umiFieldsGroup|Boolean группы полей (экземпляр класса umiFieldsGroup), либо false
		*/
		public function getFieldsGroupByName($field_group_name, $allow_disabled = false) {
			$groups = $this->getFieldsGroupsList($allow_disabled);
			foreach($groups as $group_id => $group) {
				if($group->getName()  == $field_group_name) {
					return $group;
				}
			}
			return false;
		}

		/**
			* Получить группу полей (класс umiFieldsGroup) по ее id
			* @param Integer $field_group_id id группы полей
			* @return umiFieldsGroup|Boolean группы полей (экземпляр класса umiFieldsGroup), либо false
		*/
		public function getFieldsGroup($field_group_id) {
			if($this->isFieldsGroupExists($field_group_id)) {
				return $this->field_groups[$field_group_id];
			} else {
				return false;
			}
		}

		/**
			* Получить список всех групп полей у типа данных
			* @param Boolean $showDisabledGroups = false включить в результат неактивные группы полей
			* @return Array массив состоящий из экземпляров класса umiFieldsGroup
		*/
		public function getFieldsGroupsList($showDisabledGroups = false) {
			return $showDisabledGroups ? $this->field_all_groups : $this->field_groups;
		}


		/**
			* Проверить, существует ли у типа данных группа полей с id $field_group_id
			* @param Integer $field_group_id id группы полей
			* @return Boolean true, если группа полей существует у этого типа данных
		*/
		private function isFieldsGroupExists($field_group_id) {
			if(!$field_group_id) {
				return false;
			} else {
				return (bool) array_key_exists($field_group_id, $this->field_all_groups);
			}
		}

		/**
			* Загрузить информацию о типе данных из БД
		*/
		protected function loadInfo() {
			$sql = "SELECT name, parent_id, is_locked, is_guidable, is_public, hierarchy_type_id, sortable FROM cms3_object_types WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if(list($name, $parent_id, $is_locked, $is_guidable, $is_public, $hierarchy_type_id, $sortable) = mysql_fetch_row($result)) {
				$this->name = $name;
				$this->parent_id = (int) $parent_id;
				$this->is_locked = (bool) $is_locked;
				$this->is_guidable = (bool) $is_guidable;
				$this->is_public = (bool) $is_public;
				$this->hierarchy_type_id = (int) $hierarchy_type_id;
				$this->sortable = (bool) $sortable;

				return $this->loadFieldsGroups();
			} else {
				return false;
			}
		}

		/**
			* Загрузить группы полей и поля для типа данных из БД
			* @return Boolean true, если не возникло ошибок
		*/
		private function loadFieldsGroups() {
			$sql = <<<SQL
SELECT 
   ofg.id as groupId, cof.id, cof.name, cof.title, cof.is_locked, cof.is_inheritable, cof.is_visible, cof.field_type_id, cof.guide_id, cof.in_search, cof.in_filter, cof.tip, cof.is_required, cof.sortable, cof.is_system, cof.restriction_id 
      FROM cms3_object_field_groups ofg, cms3_fields_controller cfc, cms3_object_fields cof 
         WHERE ofg.type_id = '{$this->id}' AND cfc.group_id = ofg.id AND cof.id = cfc.field_id 
            ORDER BY ofg.ord ASC, cfc.ord ASC
SQL;

			$result = l_mysql_query($sql);
			$fields = Array();
			while(list($group_id, $id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $is_system, $sortable, $restriction_id) = mysql_fetch_row($result)) {
				if(!isset($fields[$group_id]) || !is_array($fields[$group_id])) {
					$fields[$group_id] = Array();
				}
				$fields[$group_id][] = Array($id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $is_system, $sortable, $restriction_id);
			}

			$sql = "SELECT id, name, title, type_id, is_active, is_visible, is_locked, ord FROM cms3_object_field_groups WHERE type_id = '{$this->id}' ORDER BY ord ASC";
			$result = l_mysql_query($sql);

			while(list($field_group_id,,,,$isActive) = $row = mysql_fetch_row($result)) {
				$field_group = new umiFieldsGroup($field_group_id, $row);
				
				if(!isset($fields[$field_group_id])) {
					$fields[$field_group_id] = Array();
				}
				$field_group->loadFields($fields[$field_group_id]);
				$this->field_all_groups[$field_group_id] = $field_group;
				if($isActive) {
					$this->field_groups[$field_group_id] = $field_group;
				}
			}
			return true;
		}

		/**
			* Сохранить в БД внесенные изменения
		*/
		protected function save() {
			$name = umiObjectProperty::filterInputString($this->name);
			$parent_id = (int) $this->parent_id;
			$is_locked = (int) $this->is_locked;
			$is_guidable = (int) $this->is_guidable;
			$is_public = (int) $this->is_public;
			$hierarchy_type_id = (int) $this->hierarchy_type_id;
			$sortable = (int) $this->sortable;

			$sql = "UPDATE cms3_object_types SET name = '{$name}', parent_id = '{$parent_id}', is_locked = '{$is_locked}', is_guidable = '{$is_guidable}', is_public = '{$is_public}', hierarchy_type_id = '{$hierarchy_type_id}', sortable = '{$sortable}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return false;
			}
		}

		/**
			* Изменить порядок следования группы полей
			* @param Integer $group_id
			* @param Integer $neword
			* @param Integer $is_last
			* @return Boolean
		*/
		public function setFieldGroupOrd($group_id, $neword, $is_last) {
			$neword = (int) $neword;
			$group_id = (int) $group_id;

			if(!$is_last) {
				$sql = "SELECT type_id FROM cms3_object_field_groups WHERE id = '{$group_id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}


				if(!(list($type_id) = mysql_fetch_row($result))) {
					return false;
				}	

				$sql = "UPDATE cms3_object_field_groups SET ord = (ord + 1) WHERE type_id = '{$type_id}' AND ord >= '{$neword}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
			}

			$sql = "UPDATE cms3_object_field_groups SET ord = '{$neword}' WHERE id = '{$group_id}'";
			l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}
			return true;
		}

		/**
			* Получить список всех полей типа данных
			* @param Boolean $returnOnlyVisibleFields=false если флаг установлен true, то метод вернет только видимые поля
			* @return Array массив, состоящий из экземпляров класса umiField
		*/
		public function getAllFields($returnOnlyVisibleFields = false) {
			$fields = Array();

			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group) {
				if($returnOnlyVisibleFields) {
					if(!$group->getIsVisible()) {
						continue;
					}
				}

				$fields = array_merge($fields, $group->getFields());
			}

			return $fields;
		}

		/**
			* Получить id поля по его строковому идентификатору
			* @param String $field_name строковой идентификатор поля
			* @return Integer|Boolean id поля, либо false если такого поля не существует
		*/
		public function getFieldId($field_name) {
			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive()) continue;

				$fields = $group->getFields();

				foreach($fields as $field_id => $field) {
					if($field->getName() == $field_name) {
						return $field->getId();
					}
				}
			}
			return false;
		}
		
		/**
			* Получить название модуля иерархического типа, если такой есть у этого типа данных
			* @return String название модуля
		*/
		public function getModule() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getName();
			} else {
				return false;
			}
		}
		
		/**
			* Получить название метода иерархического типа, если такой есть у этого типа данных
			* @return String название метода
		*/
		public function getMethod() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getExt();
			} else {
				return false;
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	abstract class umiObjectProperty extends umiEntinty implements iUmiEntinty, iUmiObjectProperty {
		protected
			$object_id, $field_id, $field, $field_type,
			$value = array(), $tableName = "cms3_object_content", $is_updated = false;
		protected static $dataCache = array();

		public	$store_type = "property";
		public static $USE_FORCE_OBJECTS_CREATION = false;
		public static $IGNORE_FILTER_INPUT_STRING = false;
		public static $IGNORE_FILTER_OUTPUT_STRING = false;
		public static $USE_TRANSACTIONS = true;

		/**
			* Конструктор класса
			* @param $id Integer id свойства
			* @param $id Integer id поля (umiField), с которым связано свойство
		*/
		public function __construct($id, $field_id, $type_id) {
			$this->tableName = umiBranch::getBranchedTableByTypeId($type_id);
			
			$this->setId($id);
			$this->object_id = (int) $id;

			$this->field = umiFieldsCollection::getInstance()->getField($field_id);
			$this->field_id = $field_id;
			
			$this->loadInfo();
		}

		/**
			* Получить класс свойства (umiObjectProperty) для объекта $id, поля $field_id, типа данных $type_id
			* @param Integer $id id объекта
			* @param Integer $field_id id поля (класс umiField)
			* @param Integer $type_id id типа данных (класс umiObjectType)
			* @return umiObjectProperty объект свойства
		*/
		public static function getProperty($id, $field_id, $type_id) {
			$className = self::getClassNameByFieldId($field_id);
			return new $className($id, $field_id, $type_id);
		}

		/**
			* Получить уникальный идентификатор свойства
			* @return Integer id свойства
		*/
		public function getId() {
			return $this->id . "." . $this->field_id;
		}

		/**
			* Получить значение свойства
			* @param Array $params = NULL дополнительные параметры (обычно не используется)
			* @return Mixed значение поля. Тип значения зависит от типа поля, связанного с этим свойством. Вернет NULL, если значение свойства не выставленно.
		*/
		public function getValue(array $params = NULL) {
			if($this->getIsMultiple() === false) {
				if(sizeof($this->value) > 0) {
					list($value) = $this->value;
				} else {
					return NULL;
				}
			} else {
				$value = $this->value;
			}
			if(!is_null($params)) {
				$value = $this->applyParams($value, $params);
			}
			
			if($restrictionId = $this->field->getRestrictionId()) {
				$restriction = baseRestriction::get($restrictionId);
				if($restriction instanceof iNormalizeOutRestriction) {
					$value = $restriction->normalizeOut($value);
				}
			}
			
			return $value;
		}

		/**
			* Получить имя свойсива
			* @return String имя свойства.
		*/
		public function getName() {
			return $this->field->getName();
		}

		/**
			* Получить описание свойсива
			* @return String описание свойства.
		*/
		public function getTitle() {
			return $this->field->getTitle();
		}
		
		/**
			* Провалидировать значение согласно настройкам поля
			* @param String $value проверяемое начение
			* @return String проверенное (возможно, модифицированное) значение поля
		*/
		public function validateValue($value) {
			if(!$value && $this->field->getIsRequired()) {
				throw new valueRequiredException(getLabel('error-value-required', null, $this->getTitle()));
			}
		
			if($value && $restrictionId = $this->field->getRestrictionId()) {
				$restriction = baseRestriction::get($restrictionId);
				if($restriction instanceof baseRestriction) {
					if($restriction instanceof iNormalizeInRestriction) {
						$value = $restriction->normalizeIn($value);
					}
					
					if($restriction->validate($value) == false) {
						throw new wrongValueException(getLabel($restriction->getErrorMessage(), null, $this->getTitle()));
					}
				}
			}
			return $value;
		}

		/**
			* Установить значение свойства.
			* Устанавливает флаг "Модифицирован".
			* Значение в БД изменится только когда на экземпляре umiObjectProperty будет вызван темод commit(), либо в деструкторе экземпляра
			* @param $value Mixed новое значение для поля. Зависит от типа поля, связанного с этим свойством
			* @return Boolean true если прошло успешно
		*/
		public function setValue($value) {
			$value = $this->validateValue($value);
		
			if(!is_array($value)) {
				$value = Array($value);
			}

			$data_type = $this->getDataType();
			if ($data_type === 'date') {
				foreach ($value as $vKey=>$vVal) {
					if (!($vVal instanceof umiDate)) {
						$value[$vKey] = new umiDate(intval($vVal));
					}
				}
			}
			
			$this->value = $value;
			return $this->setIsUpdated();
//			return $this->setObjectIsUpdated();
		}

		/**
			* Сбросить значение свойства.
			* Устанавливает флаг "Модифицирован".
			* Значение в БД изменится только когда на экземпляре umiObjectProperty будет вызван темод commit(), либо в деструкторе экземпляра
			* @return Boolean true если прошло успешно
		*/
		public function resetValue() {
			$this->value = Array();
			$this->setIsUpdated();
		}

		/**
			* Загружает необходимые данные для формирования объекта umiObjectProperty из БД.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo() {
			$field = $this->field;
			$field_types = umiFieldTypesCollection::getInstance();

			$field_type_id = $field->getFieldTypeId();

			$field_type = $field_types->getFieldType($field_type_id);
			$this->field_type = $field_type;

			$this->value = $this->loadValue();
		}
		
		protected function save() {
			$object = umiObjectsCollection::getInstance()->getObject($this->id);
			if($object instanceof umiObject) {
				if($object->checkSelf() == false) {
					cacheFrontend::getInstance()->del($object->getId(), "object");
					return false;
				}
			}
			
			cacheFrontend::getInstance()->del($this->getId(), "property");
			
			if(self::$USE_TRANSACTIONS) {
				l_mysql_query("START TRANSACTION /* Saving property for object {$this->id} */");
			}
			
			$result = $this->saveValue();
			
			if(self::$USE_TRANSACTIONS) {
				l_mysql_query("COMMIT");
			}
			
			if(isset(umiObjectProperty::$dataCache[$this->object_id])) {
				unset(umiObjectProperty::$dataCache[$this->object_id]);
			}
			
			$this->loadInfo();
			
			return $result;
		}

		/**
			* Узнать, может ли значение данного свойства состоять из массива значений (составной тип)
			* @return Boolean true, если тип составной
		*/
		public function getIsMultiple() {
			return $this->field_type->getIsMultiple();
		}

		/**
			* Узнать, может ли значение данного свойства иметь знак.
			* Зарезервировано и пока не используется
			* @return Boolean true, если значение свойства не будет иметь знак
		*/
		public function getIsUnsigned() {
			return $this->field_type->getIsUnsigned();
		}

		/**
			* Получить идентификатор типа поля, связанного с данным свойством
			* @return String идентификатор типа
		*/
		public function getDataType() {
			return $this->field_type->getDataType();
		}

		/**
			* Узнать, заблокировано ли свойство на изменение
			* @return Boolean true если свойство заблокировано
		*/
		public function getIsLocked() {
			return $this->field->getIsLocked();
		}

		/**
			* Узнать, наследуется ли значение свойства. Зарезервировано, но пока не используется.
			* @return Boolean true если свойство наследуется
		*/
		public function getIsInheritable() {
			return $this->field->getIsInheritable();
		}

		/**
			* Узнать видимость свойства для пользователя
			* @return Boolean true если свойство видимое для пользователя
		*/
		public function getIsVisible() {
			return $this->field->getIsVisible();
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Тэги"
		*/
		public static function filterInputString($string) {
			$string = l_mysql_real_escape_string($string);
			$string = umiObjectProperty::filterCDATA($string);
			
			if(cmsController::getInstance()->getCurrentMode() != "admin" && !umiObjectProperty::$IGNORE_FILTER_INPUT_STRING) {
				$string = str_replace("%", "&#37;", $string);
				$string = htmlspecialchars($string);
			}
			
			return $string;
		}

		/**
			* Заменяет в строке символ "%" на "&#037;" и обратно, в зависимости от режима работы cms.
			* Используется ядром для защиты от иньекций макросов на клинтской стороне
			* @param String $string фильтруемая строка
			* @return String отфильтрованная строка
		*/
		public static function filterOutputString($string) {
			if(cmsController::getInstance()->getCurrentMode() == "admin" || self::$IGNORE_FILTER_OUTPUT_STRING) {
				$string = str_replace("%", "&#037;", $string);
			} else {
				$string = str_replace("&#037;", "%", $string);
			}
			return $string;
		}
		
		/**
			* Заменяет в строке символ закрывающую последовательность для CDATA (]]>) на "]]&gt;"
			* Используется ядром поддержания валидности XML-документов
			* @param String $string фильтруемая строка
			* @return String отфильтрованная строка
		*/
		public static function filterCDATA($string) {
			$string = str_replace("]]>", "]]&gt;", $string);
			return $string;
		}
		
		/**
			* Устанавливает маркер "модифицирован" у связанного с этим свойством объекта
			* @return Boolean false, в случае неудачи
		*/
		protected function setObjectIsUpdated() {
			if($object = $this->getObject()) {
				$object->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}
		
		protected function getPropData() {
			$cache = &umiObjectProperty::$dataCache;
			
			if(defined("DISABLE_GETVAL_OPT") && DISABLE_GETVAL_OPT) {
				return false;
			}
			
			$fieldId = $this->field_id;
			$objectId = $this->object_id;
			
			if(!isset($cache[$objectId])) {
				$data = array();
				
				$sql = "SELECT field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$this->tableName} WHERE obj_id = '{$this->id}'";
				$result = l_mysql_query($sql);
				while($row = mysql_fetch_assoc($result)) {
					$data[$row['field_id']]['int_val'][] = $row['int_val'];
					$data[$row['field_id']]['varchar_val'][] = $row['varchar_val'];
					$data[$row['field_id']]['text_val'][] = $row['text_val'];
					$data[$row['field_id']]['rel_val'][] = $row['rel_val'];
					$data[$row['field_id']]['tree_val'][] = $row['tree_val'];
					$data[$row['field_id']]['float_val'][] = $row['float_val'];
				}
				$cache[$objectId] = $data;
				
				if(sizeof($cache) >= 3) {
					foreach($cache as $i => $d) {
						unset($cache[$i]);
						break;
					}
				}
			} else {
				$data = $cache[$objectId];
			}
			
			if(isset($data[$fieldId])) {
				$result = $data[$fieldId];
				unset($cache[$objectId][$fieldId]);
				if(sizeof($cache[$objectId]) == 0){
					unset($cache[$objectId]);
				}
				return $result;
			} else {
				return false;
			}
		}
		
		/**
			* Возвращает связанный с этим свойством объект (umiObject)
			* @return umiObject
			* @see umiObject
		*/
		public function getObject() {
			return umiObjectsCollection::getInstance()->getObject($this->object_id);
		}

		
		/**
			* Возвращает id объекта (umiObject), связанного с этим свойством
			* @return umiObject
			* @see umiObject
		*/
		public function getObjectId() {
			return $this->object_id;
		}
		
		/**
			* Возвращает тип свойства (umiFieldType)
			* @return umiFieldType
			* @see umiFieldType
		*/
		public function getField() {
			return $this->field;
		}
		
		protected static function unescapeFilePath($filepath) {
			return str_replace("\\\\", "/", $filepath);
		}
		
		protected function deleteCurrentRows() {
			$sql = "DELETE FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND (field_id = '{$this->field_id}' OR field_id IS NULL)";
			l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}
		}

		/**
			* Заполнить все столбцы значений таблицы БД, соответствующие данному свойству NULL'ами
		*/
		protected function fillNull() {
			$sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				list($c) = mysql_fetch_row($result);
			}

			if($c == 0) {
				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id) VALUES('{$this->object_id}', '{$this->field_id}')";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);	//TODO: Ignore references, debug.
					return false;
				} else {
					return true;
				}
			}
			return true;
		}

		
		protected static function getClassNameByFieldId($field_id) {
			static $cache = Array();
			if(isset($cache[$field_id])) {
				return $cache[$field_id];
			}
			
			$field = umiFieldsCollection::getInstance()->getField($field_id);
			$fieldTypeId = $field->getFieldTypeId();
			$fieldType = umiFieldTypesCollection::getInstance()->getFieldType($fieldTypeId);
			$fieldDataType = $fieldType->getDataType();
			
			$propertyClasses = Array(
				'relation' => 'umiObjectPropertyRelation',
				'wysiwyg' => 'umiObjectPropertyWYSIWYG',
				'string' => 'umiObjectPropertyString',
				'file' => 'umiObjectPropertyFile',
				'img_file' => 'umiObjectPropertyImgFile',
				'swf_file' => 'umiObjectPropertyImgFile',
				'video_file' => 'umiObjectPropertyFile',
				'boolean' => 'umiObjectPropertyBoolean',
				'int' => 'umiObjectPropertyInt',
				'text' => 'umiObjectPropertyText',
				'date' => 'umiObjectPropertyDate',
				'symlink' => 'umiObjectPropertySymlink',
				'price' => 'umiObjectPropertyPrice',
				'float' => 'umiObjectPropertyFloat',
				'tags' => 'umiObjectPropertyTags',
				'password' => 'umiObjectPropertyPassword',
				'counter' => 'umiObjectPropertyCounter',
				'optioned' => 'umiObjectPropertyOptioned'
			);
			
			if(isset($propertyClasses[$fieldDataType])) {
				return $cache[$field_id] = $propertyClasses[$fieldDataType];
			} else {
				throw new coreException("Unhandled field of type \"{$fieldDataType}\"");
			}
		}

		/**
			* Не используйте этот метод, его поведение будет изменено в ближайших версиях
		*/
		public static function objectsByValue($i_field_id, $arr_value = NULL, $b_elements = false, $b_stat = true, $arr_domains = NULL) {
			$arr_answer = array();

			// ==== validate input : =======================

			if (!(is_null($arr_value) || is_array($arr_value) || intval($arr_value) === -1 || strval($arr_value) === 'all' || strval($arr_value) == 'Все')) {
				$arr_value = array($arr_value);
			}

			// h.domain_id
			$arr_domain_ids = NULL;
			if ($b_elements) {
				if (is_null($arr_domains)) { // current domain
					$arr_domain_ids = array(cmsController::getInstance()->getCurrentDomain()->getId());
				} elseif (intval($arr_domains) === -1 || strval($arr_domains) === 'all' || strval($arr_domains) == 'Все') {
					$arr_domain_ids = array();
				} elseif (is_array($arr_domains)) {
					$arr_domain_ids = array_map('intval', $arr_domains);
				} else {
					$arr_domain_ids = array(intval($arr_domains));
				}
			}
			
			$field = umiFieldsCollection::getInstance()->getField($i_field_id);
			if($field instanceof umiField) {
				$fieldDataType = $field->getFieldType()->getDataType();
				$s_col_name = umiFieldType::getDataTypeDB($fieldDataType);
			} else {
				throw new coreException("Field #{$fieldId} not found");
			}

			// ==== construct sql queries : ================
			
			$objectTypeId = umiSelectionsParser::getObjectTypeByFieldId($i_field_id);
			$tableName = umiBranch::getBranchedTableByTypeId($objectTypeId);

			$s_from = "{$tableName} `o`";
			if ($b_elements) $s_from .= ", cms3_hierarchy `h`";

			if ($b_elements) {
				$s_count_field = "h.id";
			} else {
				$s_count_field = "o.obj_id";
			}

			$s_where_tail = ($b_elements ? " AND h.obj_id = o.obj_id AND h.is_active=1 AND h.is_deleted=0" : "");

			if ($b_elements && is_array($arr_domain_ids) && count($arr_domain_ids)) {
				$s_where_tail .= " AND h.domain_id IN ('".implode("', '", $arr_domain_ids)."')";
			}

			$s_values_filter = "";
			if (!(intval($arr_value) === -1 || strval($arr_value) === 'all' || strval($arr_value) === 'Âñå')) {
				$s_values_filter = " AND o.{$s_col_name} ".(is_null($arr_value) ? "IS NULL" : "IN ('".implode("', '", $arr_value)."')");
			}

			if ($b_stat) {
				$s_query = "SELECT o.".$s_col_name." as `value`, COUNT(".$s_count_field.") as `items` FROM ".$s_from." WHERE o.field_id = ".$i_field_id.$s_values_filter.$s_where_tail." GROUP BY o.".$s_col_name." ORDER BY `items`";
			} else {
				$s_query = "SELECT DISTINCT ".$s_count_field." as `item` FROM ".$s_from." WHERE o.field_id = ".$i_field_id.$s_values_filter.$s_where_tail;
			}

			// ==== execute sql query : ====================

			$arr_query = array();
			$rs_query = l_mysql_query($s_query);
			$i_query_error = mysql_errno();
			$s_query_error = mysql_error();
			if ($rs_query === false || $i_query_error) {
				throw new coreException("Error executing db query (errno ".$i_query_error.", error ".$s_query_error.", query ".$s_query.")");
			} else {
				while ($arr_next_row = mysql_fetch_assoc($rs_query)) {
					$arr_query[] = $arr_next_row;
				}
			}

			// ==== construct returning answer : ===========

			if ($b_stat) {
				$arr_answer['values'] = array();
				$i_max = 0;
				$i_summ = 0;
				foreach ($arr_query as $arr_row) {
					$i_cnt = intval($arr_row['items']);

					$arr_answer['values'][] = array('value' => $arr_row['value'], 'cnt' => $i_cnt);
		
					if ($i_cnt > $i_max) $i_max = $i_cnt;
					$i_summ += $i_cnt;
				}
				$arr_answer['max'] = $i_max;
				$arr_answer['sum'] = $i_summ;
			} else {
				foreach ($arr_query as $arr_row) $arr_answer[] = $arr_row['item'];
			}

			// RETURN :
			return $arr_answer;

		}
		
		protected function applyParams($values, array $params = NULL) {
			return $values;
		}
		
		protected function prepareRelationValue($value) {
			if(!$value) {
				return false;
			}
			
			$objects = umiObjectsCollection::getInstance();
			$forceObjectsCreation = self::$USE_FORCE_OBJECTS_CREATION;
			
			if(is_object($value)) {
				return $value->getId();
			} else {
				if(is_numeric($value) && $objects->isExists($value) && !$forceObjectsCreation) {
					return (int) $value;
				} else {
					if($guide_id = $this->field->getGuideId()) {
						$val_name = self::filterInputString($value);

						$sql = "SELECT id FROM cms3_objects WHERE type_id = '{$guide_id}' AND name = '{$val_name}'";
						$result = l_mysql_query($sql);

						if(mysql_num_rows($result)) {
							list($value) = mysql_fetch_row($result);
							return $value;
						} else {
							if($value = $objects->addObject($value, $guide_id)) {
								return (int) $value;
							} else {
								throw new coreException("Can't create guide item");
							}
						}
					} else {
						return null;
					}
				}
			}
		}
	};


/**
 * Общий класс для взаимодействия с объектами системы.
 * @author lyxsus <sa@umisoft.ru>
 */
	class umiObject extends umiEntinty implements iUmiEntinty, iUmiObject {
		private $name, $type_id, $is_locked, $owner_id = false,
			$type, $properties = Array(), $prop_groups = Array();
		protected $store_type = "object";

		/**
			* Получить название объекта
			* @return String название объекта
		*/	
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Получить id типа объекта
			* @return Integer id типа объекта (для класса umiObjectType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}
		
		public function getType() {
			if(!$this->type) {
				$this->loadType();
			}
			return $this->type;
		}

		/**
			* Узнать, заблокирован ли объект. Метод зарезервирован, но не используется. Предполагается, что этот флаг будет блокировать любое изменение объекта
			* @return Boolean true если обект заблокирован
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Задать новое название объекта. Устанавливает флаг "Модифицирован".
			* @param String $name
		*/
		public function setName($name) {
			if ($this->name !== $name) {
				if(($this->translateLabel($this->name) != $this->name)) {
					$name = $this->translateI18n($name);
				}
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить новый id типа данных (класс umiObjectType) для объекта. 
			* Используйте этот метод осторожно, потому что он просто переключает id типа данных.
			* Уже заполненные значения остануться в БД, но станут недоступны через API, если не переключить тип данных для объекта назад.
			* Устанавливает флаг "Модифицирован".
			* @return Boolean true всегда
		*/
		public function setTypeId($type_id) {
			if ($this->type_id !== $type_id) {
				$this->type_id = $type_id;
				$this->setIsUpdated();
			}
			return true;
		}

		/**
			* Выставить объекту статус "Заблокирован". Этот метод зарезервирован, но в настоящее время не используется.
		*/
		public function setIsLocked($is_locked) {
			if ($this->is_locked !== ((bool) $is_locked)) {
				$this->is_locked = (bool) $is_locked;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить id владельца объекта. Это означает, что пользователь с id $ownerId полностью владеет этим объектом:
			* создал его, может модифицировать, либо удалить.
			* @param Integer $ownerId id нового владельца. Обязательно действительный id объекта (каждый пользователь это объект в umi)
			* @return Boolean true в случае успеха, false если $ownerId не является нормальным id для umiObject
		*/
		public function setOwnerId($ownerId) {
			if(!is_null($ownerId) and umiObjectsCollection::getInstance()->isExists($ownerId)) {
				if ($this->owner_id !== $ownerId) {
					$this->owner_id = $ownerId;
					$this->setIsUpdated();
				}
				return true;
			}
			else {
				if (!is_null($this->owner_id)) {
					$this->owner_id = NULL;
					$this->setIsUpdated();
				}
				return false;
			}
		}

		/**
			* Получить id пользователя, который владеет этим объектом
			* @return Integer id пользователя. Всегда действительный id для umiObject или NULL если не задан.
		*/
		public function getOwnerId() {
			return $this->owner_id;
		}
		
		/**
			* Проверить, заполены ли все необходимые поля у объекта
			* @return Boolean
		*/
		public function isFilled() {
			$fields = $this->type->getAllFields();
			foreach($fields as $field)
				if($field->getIsRequired() && is_null($this->getValue($field->getName())))
						return false;
			return true;
		}

		/**
			* Сохранить все модификации объекта в БД. Вызывает метод commit() на каждом загруженом свойстве (umiObjectProperty)
		*/
		protected function save() {
			if ($this->is_updated) {

				$name = umiObjectProperty::filterInputString($this->name);
				$type_id = (int) $this->type_id;
				$is_locked = (int) $this->is_locked;
				$owner_id = (int) $this->owner_id;
				
				$sql = "START TRANSACTION /* Updating object #{$this->id} info */";
				$result = l_mysql_query($sql);
				
				if($err = mysql_error()) {
					throw new coreException($err);
				}
				
				$nameSql = $name ? "'{$name}'" : "NULL";
				$sql = "UPDATE cms3_objects SET name = {$nameSql}, type_id = '{$type_id}', is_locked = '{$is_locked}', owner_id = '{$owner_id}' WHERE id = '{$this->id}'";
				l_mysql_query($sql);
				if($err = mysql_error()) {
					throw new coreException($err);
				}

				foreach($this->properties as $prop) {
					if(is_object($prop)) $prop->commit();
				}
				
				$sql = "COMMIT";
				l_mysql_query($sql);
				
				if($err = mysql_error()) {
					throw new coreException($err);
				}

				$this->setIsUpdated(false);

			}
			return true;
		}

		/**
			* Загружает необходимые данные для формирования объекта. Этот метод не подгружает значения свойств.
			* Значения свойств запрашиваются по требованию
			* В случае нарушения целостности БД, когда с загружаемым объектом в базе не связан ни один тип данных, объект удаляется.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo() {
			$sql = "SELECT name, type_id, is_locked, owner_id FROM cms3_objects WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql, true);

			if($err = mysql_error()) {
				cacheFrontend::getInstance()->del($object->getId(), "object");
				throw new coreException($err);
				return false;
			}

			if(list($name, $type_id, $is_locked, $owner_id) = mysql_fetch_row($result)) {
				if(!$type_id) {	//Foregin keys check failed, or manual queries made. Delete this object.
					umiObjectsCollection::getInstance()->delObject($this->id);
					return false;
				}
			
				$this->name = $name;
				$this->type_id = (int) $type_id;
				$this->is_locked = (bool) $is_locked;
				$this->owner_id = (int) $owner_id;
				return $this->loadType();
			} else {
				throw new coreException("Object #{$this->id} doesn't exists");
				return false;
			}
		}


		/**
			* Загрузить тип данных (класс umiObjectType), который описывает этот объект
		*/
		private function loadType() {
			$type = umiObjectTypesCollection::getInstance()->getType($this->type_id);

			if(!$type) {
				throw new coreException("Can't load type in object's init");
			}

			$this->type = $type;
			return $this->loadProperties();
		}

		/**
			* Подготовить внутреннеие массивы для свойств и групп свойств на основе структуры типа данных, с которым связан объект
		*/
		private function loadProperties() {
			$type = $this->type;
			$groups_list = $type->getFieldsGroupsList();
			foreach($groups_list as $group) {
				if($group->getIsActive() == false) continue;

				$fields = $group->getFields();

				$this->prop_groups[$group->getId()] = Array();

				foreach($fields as $field) {
					$this->properties[$field->getId()] = $field->getName();
					$this->prop_groups[$group->getId()][] = $field->getId();
				}
			}
		}

		/**
			* Получить свойство объекта по его строковому идентификатору
			* @param String $prop_name строковой идентификатор свойства
			* @return umiObjectProperty или NULL в случае неудачи
		*/
		public function getPropByName($prop_name) {
			$prop_name = strtolower($prop_name);

			foreach($this->properties as $field_id => $prop) {
				if(is_object($prop)) {
					if($prop->getName() == $prop_name) {
						return $prop;
					}
				} else {
					if(strtolower($prop) == $prop_name) {
					    $prop = cacheFrontend::getInstance()->load($this->id . "." . $field_id, "property");
						if($prop instanceof umiObjectProperty == false) {
							$prop = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
							cacheFrontend::getInstance()->save($prop, "property");
						}
						$this->properties[$field_id] = $prop;
						return $prop;
					}
				}
			}
			return NULL;
		}

		/**
			* Получить свойство объекта по его числовому идентификатору (просто id)
			* @param Integer $field_id id поля
			* @return umiObjectProperty или NULL в случае неудачи
		*/
		public function getPropById($field_id) {
			if(!$this->isPropertyExists($field_id)) {
				return NULL;
			} else {
				if(!is_object($this->properties)) {
					$this->properties[$field_id] = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
				}
				return $this->properties[$field_id];
			}
		}

		/**
			* Узнать, существует ли свойство с id $field_id
			* @param Integer $field_id id поля
			* @return Boolean true, если поле существует
		*/
		public function isPropertyExists($field_id) {
			return (bool) array_key_exists($field_id, $this->properties);
		}

		/**
			* Узнать, существует ли группа полей с id $prop_group_id у объекта
			* @param Integer $prop_group_id id группы полей
			* @return Boolean true, если группа существует
		*/
		public function isPropGroupExists($prop_group_id) {
			return (bool) array_key_exists($prop_group_id, $this->prop_groups);
		}

		/**
			* Получить id группы полей по ее строковому идентификатору
			* @param String $prop_group_name Строковой идентификатор группы полей
			* @return Integer id группы полей, либо false, если такой группы не существует
		*/
		public function getPropGroupId($prop_group_name) {
			$groups_list = $this->getType()->getFieldsGroupsList();
			
			foreach($groups_list as $group) {
				if($group->getName() == $prop_group_name) {
					return $group->getId();
				}
			}
			return false;
		}

		/**
			* Получить группу полей по ее строковому идентификатору
			* @param String $prop_group_name Строковой идентификатор группы полей
			* @return umiFieldsGroup, либо false, если такой группы не существует
		*/
		public function getPropGroupByName($prop_group_name) {
			$groups_list = $this->type->getFieldsGroupsList();

			if($group_id = $this->getPropGroupId($prop_group_name)) {
				return $this->getPropGroupById($group_id);
			} else {
				return false;
			}
		}

		/**
			* Получить группу полей по ее id
			* @param Integer $prop_group_id id группы полей
			* @return umiFieldsGroup, либо false, если такой группы не существует
		*/
		public function getPropGroupById($prop_group_id) {
			if($this->isPropGroupExists($prop_group_id)) {
				return $this->prop_groups[$prop_group_id];
			} else {
				return false;
			}
		}


		/**
			* Получить значение свойства $prop_name объекта
			* @param String $prop_name строковой идентификатор поля
			* @param Array $params = NULL дополнительные параметры (обычно не используется)
			* @return Mixed значение поле. Тип значения зависит от типа поля. Вернет false, если свойства не существует.
		*/
		public function getValue($prop_name, $params = NULL) {
			if($prop = $this->getPropByName($prop_name)) {
				return $prop->getValue($params);
			} else {
				return false;
			}
		}

		/**
			* Установить значение свойства с $prop_name данными из $prop_value. Устанавливает флаг "Модифицирован".
			* Значение в БД изменится только когда на объекте будет вызван темод commit(), либо в деструкторе объекта
			* @param String $prop_name строковой идентификатор поля
			* @param Mixed $prop_value новое значение для поля. Зависит от типа поля
			* @return Boolean true если прошло успешно
		*/
		public function setValue($prop_name, $prop_value) {
			if($prop = $this->getPropByName($prop_name)) {
				$this->setIsUpdated();
				return $prop->setValue($prop_value);
			} else {
				return false;
			}
		}
		
		/*
			* Сохранить все значения в базу, если объект модификирован
		*/
		public function commit() {
			l_mysql_query("START TRANSACTION /* Saving object {$this->id} */");
			$USE_TRANSACTIONS = umiObjectProperty::$USE_TRANSACTIONS;
			umiObjectProperty::$USE_TRANSACTIONS = false;
			
			if($this->checkSelf()) {
				foreach($this->properties as $prop) {
					if(is_object($prop)) {
						$prop->commit();
					}
				}
			}
			
			parent::commit();
			l_mysql_query("COMMIT");
			umiObjectProperty::$USE_TRANSACTIONS = $USE_TRANSACTIONS;
		}
		
		public function checkSelf() {
			static $res;
			if($res !== null) {
				return $res;
			}
			
			if(!cacheFrontend::getInstance()->getIsConnected()) {
				return $res = true;
			}
			
			$sql = "SELECT id FROM cms3_objects WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			if($err = mysql_error()) {
				throw new coreException($err);
			}
			$res = (bool) mysql_num_rows($result);
			if(!$res) {
				cacheFrontend::getInstance()->flush();
			}
			return $res;
		}
		
		
		/**
			* Вручную установить флаг "Модифицирован"
		*/
		public function setIsUpdated($isUpdated = true) {
			umiObjectsCollection::getInstance()->addUpdatedObjectId($this->id);
			return parent::setIsUpdated($isUpdated);
		}
		
		/**
			* Удалить объект
		*/
		public function delete() {
			umiObjectsCollection::getInstance()->delObject($this->id);
		}
		
		public function __get($varName) {
			switch($varName) {
				case "id":		return $this->id;
				case "name":	return $this->getName();
				case "ownerId":	return $this->getOwnerId();
				case "typeId":	return $this->getTypeId();
				case "xlink":	return 'uobject://' . $this->id;

				default:		return $this->getValue($varName);
			}
		}
		
		public function __set($varName, $value) {
			switch($varName) {
				case "id":		throw new coreException("Object id could not be changed");
				case "name":	return $this->setName($value);
				case "ownerId":	return $this->setOwnerId($value);
				
				default:		return $this->setValue($varName, $value);
			}
		}
		
		public function beforeSerialize($reget = false) {
			static $types = array();
			if($reget && isset($types[$this->type_id])) {
				$result = $types[$this->type_id];
				unset($types[$this->type_id]);
				return $result;
			}
			
			$types[$this->type_id] = $this->type;
			$this->type = null;
		}
		
		public function afterSerialize() {
			$this->beforeSerialize(true);
		}
		
		public function afterUnSerialize() {
			$this->getType();
		}

		public function getModule() {
			return $this->type->getModule();
		}
		
		public function getMethod() {
			return $this->type->getMethod();
		}
	}


/**
	* Этот класс-коллекция служит для управления/получения доступа к типам полей
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
*/
	class umiFieldTypesCollection extends singleton implements iSingleton, iUmiFieldTypesCollection {
		private $field_types = Array();

		protected function __construct() {
			$this->loadFieldTypes();
		}

		/**
			* Получить экземпляр коллекции
			* @return umiFieldTypesCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Создать новый тип поля
			* @param String $name описание типа
			* @param String $data_type тип данных
			* @param Boolean $is_multiple является ли тип составным (массив значений)
			* @param Boolean $is_unsigned зарезервировано и пока не используется, рекомендуется выставлять в false
			* @return Integer идентификатор созданного типа, либо false в случае неудачи
		*/
		public function addFieldType($name, $data_type = "string", $is_multiple = false, $is_unsigned = false) {
			if(!umiFieldType::isValidDataType($data_type)) {
				throw new coreException("Not valid data type given");
				return false;
			}
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			$sql = "INSERT INTO cms3_object_field_types (data_type) VALUES('{$data_type}')";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_type_id = mysql_insert_id();

			$field_type = new umiFieldType($field_type_id);

			$field_type->setName($name);
			$field_type->setDataType($data_type);
			$field_type->setIsMultiple($is_multiple);
			$field_type->setIsUnsigned($is_unsigned);
			$field_type->commit();

			$this->field_types[$field_type_id] = $field_type;

			return $field_type_id;
		}

		/**
			* Удалить тип поля с заданным идентификатором из коллекции
			* @param Integer $field_type_id идентификатор поля
			* @return Boolean true, если удаление удалось
		*/
		public function delFieldType($field_type_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
			
			if($this->isExists($field_type_id)) {
				$field_type_id = (int) $field_type_id;
				$sql = "DELETE FROM cms3_object_field_types WHERE id = '{$field_type_id}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->field_types[$field_type_id]);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Получтить экземпляр класса umiFieldType по идентификатору
			* @param Integer $field_type_id идентификатор поля
			* @return umiFieldType экземпляр класса umiFieldType, либо false в случае неудачи
		*/
		public function getFieldType($field_type_id) {
			if($this->isExists($field_type_id)) {
				return $this->field_types[$field_type_id];
			} else {
				return true;
			}
		}

		/**
			* Проверить, существует ли в БД тип поля с заданным идентификатором
			* @param Integer $field_type_id идентификатор типа
			* @return Boolean true, если тип поля существует в БД
		*/
		public function isExists($field_type_id) {
			return (bool) array_key_exists($field_type_id, $this->field_types);
		}

		/**
			* Загружает в коллекцию все типы полей, создает экземпляры класса umiFieldType для каждого типа
			* @return Boolean true, если удалось загрузить, либо строку - описание ошибки, в случае неудачи.
		*/
		private function loadFieldTypes() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$fieldTypeIds = $cacheFrontend->loadData("field_types");
			if(!is_array($fieldTypeIds)) {
				$sql = "SELECT id, name, data_type, is_multiple, is_unsigned FROM cms3_object_field_types ORDER BY name ASC";
				$result = l_mysql_query($sql);
				$fieldTypeIds = array();
				while(list($field_type_id) = $row = mysql_fetch_row($result)) {
					$fieldTypeIds[$field_type_id] = $row;
				}
				$cacheFrontend->saveData("field_types", $fieldTypeIds, 3600);
			} else $row = false;
			
			foreach($fieldTypeIds as $field_type_id => $row) {
				$field_type = $cacheFrontend->load($field_type_id, "field_type");
				if($field_type instanceof umiFieldType == false) {
					try {
						$field_type = new umiFieldType($field_type_id, $row);
					} catch(privateException $e) {
						continue;
					}

					$cacheFrontend->save($field_type, "field_type");
				}
				$this->field_types[$field_type_id] = $field_type;
			}

			return true;
		}

		/**
			* Возвращает список всех типов полей
			* @return Array список типов (экземпляры класса umiFieldType)
		*/
		public function getFieldTypesList() {
			return $this->field_types;
		}
	}


/**
	* Этот класс-коллекция служит для управления/получения доступа к полям
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
*/
	class umiFieldsCollection extends singleton implements iSingleton, iUmiFieldsCollection {
		private	$fields = Array();

		protected function __construct() {
		}

		/**
			* Получить экземпляр коллекции
			* @return umiFieldsCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Получтить экземпляр класса umiField, соответсвующший полю с id = $field_id
			* @param Integer $field_id id поля
			* @param Array $row=false или false информация о поле. Это служебный параметр и его передавать не нужно
			* @return umiField экземпляр класса umiField, соответсвующший полю с id = $field_id, либо false в случае неудачи
		*/
		public function getField($field_id, $row = false) {
			if($this->isExists($field_id)) {
				return $this->fields[$field_id];
			} else {
				return $this->loadField($field_id, $row);
			}
		}

		/**
			* Удалить поле с id $field_id из коллекции
			* @param Integer $field_id id поля
			* @return Boolean true, если удаление удалось
		*/
		public function delField($field_id) {
			$this->disableCache();
			
			if($this->isExists($field_id)) {
				$sql = "DELETE FROM cms3_object_fields WHERE id = '{$field_id}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->fields[$field_id]);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать новое поле
			* @param String $name имя поля (строковой идентификатор)
			* @param String $title название поля
			* @param Integer $field_type_id id типа данных поля (см. класс umiFieldType)
			* @param Boolean $is_visible=true видимость поля для пользователя
			* @param Boolean $is_locked=false указывает заблокировано ли поле на изменения
			* @param Boolean $is_inheritable=false указывает наследовать ли значение поля. Зарезервировано, но пока не используется, рекомендуется выставлять в false.
			* @return Integer id созданного поля, либо false в случае неудачи
		*/
		public function addField($name, $title, $field_type_id, $is_visible = true, $is_locked = false, $is_inheritable = false) {
			$this->disableCache();
		
			$sql = "INSERT INTO cms3_object_fields VALUES()";
			l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_id = mysql_insert_id();

			$field = new umiField($field_id);

			$field->setName($name);
			$field->setTitle($title);
			if(!$field->setFieldTypeId($field_type_id)) return false;
			$field->setIsVisible($is_visible);
			$field->setIsLocked($is_locked);
			$field->setIsInheritable($is_inheritable);

			if(!$field->commit()) return false;

			$this->fields[$field_id] = $field;

			return $field_id;
		}

		/**
			* Проверить, существует ли в БД поле с id = $field_id
			* @param Integer $field_id id поля
			* @return Boolean true, если поле существует в БД
		*/
		public function isExists($field_id) {
			if(!$field_id) return false;
			return (bool) array_key_exists($field_id, $this->fields);
		}


		/**
			* Загружает в коллекцию экземпляр класса umiField, соответсвующший полю с id = $field_id, и возвращает его
			* @param Integer $field_id id поля
			* @param Array $row=false или false информация о поле. Это служебный параметр и его передавать не нужно
			* @return umiField экземпляр класса umiField, соответсвующший полю c id = $field_id, либо false в случае неудачи
		*/
		private function loadField($field_id, $row) {
		    $field = cacheFrontend::getInstance()->load($field_id, "field");
		    
			if($field instanceof umiField == false) {
				try {
					$field = new umiField($field_id, $row);
				} catch(privateException $e) {
					return false;
				}

				cacheFrontend::getInstance()->save($field, "field");
			}
			
			if($field instanceof umiField) {			
				$this->fields[$field_id] = $field;
				return $this->fields[$field_id];
			} else {
				return false;
			}
		}
	}


/**
	* Коллекция для работы с типами данных (umiObjectType), синглтон.
*/
	class umiObjectTypesCollection extends singleton implements iSingleton, iUmiObjectTypesCollection {
		private $types = Array();

		/**
			* Конструктор, который ничего не делает
		*/
		protected function __construct() {
		}

		/**
			* Получить экземпляр коллекции
			* @return umiObjectTypesCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		/**
			* Полчить тип по его id
			* @param Integer $type_id id типа данных
			* @return umiObjectType тип данных (класс umiObjectType), либо false
		*/
		public function getType($type_id) {
			if(!$type_id) {
				return false;
			}
			
			if($this->isLoaded($type_id)) {
				return $this->types[$type_id];
			} else {
				if(true) { //if($this->isExists($type_id)) {	//doesn't matter any more
					$this->loadType($type_id);
					return getArrayKey($this->types, $type_id);
				} else {
					return false;
				}
			}
			throw new coreException("Unknow error");
		}

		/**
			* Создать тип данных с названием $name, дочерний от типа $parent_id
			* @param Integer $parent_id id родительского типа данных, от которого будут унаследованы поля и группы полей
			* @param String $name название создаваемого типа данных
			* @param Boolean $is_locked=false статус блокировки. Этот параметр указывать не надо
			* @return Integer id созданного типа данных, либо false в случае неудачи
		*/
		public function addType($parent_id, $name, $is_locked = false) {
			$this->disableCache();
		
			$parent_id = (int) $parent_id;

			$sql = "INSERT INTO cms3_object_types (parent_id) VALUES('{$parent_id}')";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$type_id = mysql_insert_id();

			//Making types inheritance...
			$sql = "SELECT * FROM cms3_object_field_groups WHERE type_id = '{$parent_id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			while($row = mysql_fetch_assoc($result)) {
				$sql = "INSERT INTO cms3_object_field_groups (name, title, type_id, is_active, is_visible, ord, is_locked) VALUES ('" . mysql_real_escape_string($row['name']) . "', '" . mysql_real_escape_string($row['title']) . "', '{$type_id}', '{$row['is_active']}', '{$row['is_visible']}', '{$row['ord']}', '{$row['is_locked']}')";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				$old_group_id = $row['id'];
				$new_group_id = mysql_insert_id();

				$sql = "INSERT INTO cms3_fields_controller SELECT ord, field_id, '{$new_group_id}' FROM cms3_fields_controller WHERE group_id = '{$old_group_id}'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}
			}
			
			$parent_hierarchy_type_id = false;
			if($parent_id) {
				$parent_type = $this->getType($parent_id);
				if($parent_type) {
					$parent_hierarchy_type_id = $parent_type->getHierarchyTypeId();
				}
			}



			$type = new umiObjectType($type_id);
			$type->setName($name);
			$type->setIsLocked($is_locked);
			if($parent_hierarchy_type_id) {
				$type->setHierarchyTypeId($parent_hierarchy_type_id);
			}
			$type->commit();

			$this->types[$type_id] = $type;
			
			umiBranch::saveBranchedTablesRelations();
			return $type_id;
		}

		/**
			* Удалить тип данных с id $type_id.
			* Все объекты этого типа будут автоматически удалены без возможности восстановления
			* Все дочерние типы от $type_id будут удалены рекурсивно.
			* @param Integer $type_id id типа данных, который будет удален
			* @return Boolean true, если удаление было успешным
		*/
		public function delType($type_id) {
			if($this->isExists($type_id)) {
				
				$type = $this->getType($type_id);
				if ($type->getIsLocked()) throw new publicAdminException (getLabel('error-object-type-locked'));
				
				$this->disableCache();
				
				$childs = $this->getChildClasses($type_id);

				$sz = sizeof($childs);
				for($i = 0; $i < $sz; $i++) {
					$child_type_id = $childs[$i];

					if($this->isExists($child_type_id)) {
						$sql = "DELETE FROM cms3_objects WHERE type_id = '{$child_type_id}'";
						l_mysql_query($sql);

						$sql = "DELETE FROM cms3_object_types WHERE id = '{$child_type_id}'";
						l_mysql_query($sql);
						
						$sql = "DELETE FROM cms3_import_types WHERE new_id = '{$child_type_id}';";
						l_mysql_query($sql);

						if($err = mysql_error()) {
							throw new coreException($err);
							return false;
						}
						unset($this->types[$child_type_id]);
					}
				}

				$type_id = (int) $type_id;

				$sql = "DELETE FROM cms3_objects WHERE type_id = '{$type_id}'";
				l_mysql_query($sql);

				$sql = "DELETE FROM cms3_object_types WHERE id = '{$type_id}'";
				l_mysql_query($sql);
				
				$sql = "DELETE FROM cms3_import_types WHERE new_id = '{$type_id}';";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->types[$type_id]);
				
				umiBranch::saveBranchedTablesRelations();
				return true;
			} else {
				return false;
			}
		}

		/**
			* Deprecated: Устаревший метод
			* @param Integer $type_id
			* @return Boolean вне зависимости от всего, всегда вернет true
		*/
		public function isExists($type_id) {
			return true;		//COMMENT: Deprecated. No need any more.
		}

		/**
			* Проверить, загружен ли тип данных $type_id в коллекцию
			* @param Integer $type_id id типа данных
			* @return Boolean true, если загружен
		*/
		private function isLoaded($type_id) {
			if($type_id === false) return false;
			return (bool) array_key_exists($type_id, $this->types);
		}

		/**
			* Загрузить тип данных в память
			* @param Integer $type_id id типа данных
			* @return Boolean true, если объект удалось загрузить
		*/
		private function loadType($type_id) {
			if($this->isLoaded($type_id)) {
				return true;
			} else {
			    $type = cacheFrontend::getInstance()->load($type_id, "object_type");
				if($type instanceof umiObjectType == false) {
					try {
						$type = new umiObjectType($type_id);
					} catch(privateException $e) {
						return false;
					}
					
					cacheFrontend::getInstance()->save($type, "object_type");
				}
				
				if(is_object($type)) {
					$this->types[$type_id] = $type;
					return true;
				} else {
					return false;
				}
			}
		}

		/**
			* Получить список дочерних типов по отношению к типу $type_id
			* @param Integer $type_id id родительского типа данных
			* @return Array массив, состоящий из id дочерних типов данных. Если не получилось, то false.
		*/
		public function getSubTypesList($type_id) {
			if(!is_numeric($type_id)) {
				throw new coreException("Type id must be numeric");
				return false;
			}

			$type_id = (int) $type_id;

			$sql = "SELECT id FROM cms3_object_types WHERE parent_id = '{$type_id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$res = Array();
			while(list($type_id) = mysql_fetch_row($result)) {
				$res[] = (int) $type_id;
			}
			return $res;
		}

		/**
			* Получить id типа данных, который является непосредственным родителем типа $type_id
			* @param Integer $type_id id типа данных
			* @return Integer id родительского типа, либо false
		*/
		public function getParentClassId($type_id) {
			if($this->isLoaded($type_id)) {
				return $this->getType($type_id)->getParentId();
			} else {
				$type_id = (int) $type_id;
				$sql = "SELECT parent_id FROM cms3_object_types WHERE id = '{$type_id}'";
				$result = l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				if(list($parent_type_id) = mysql_fetch_row($result)) {
					return (int) $parent_type_id;
				} else {
					return false;
				}
			}
		}

		/**
			* Получить список всех дочерних типов от $type_id на всю глубину наследования
			* @param Integer $type_id id типа данных
			* @return Array массив, состоящий из $id типов данных
		*/
		public function getChildClasses($type_id, $childs = false) {
			$res = Array();
			if(!$childs) $childs = Array();

			$type_id = (int) $type_id;

			$sql = "SELECT id FROM cms3_object_types WHERE parent_id = '{$type_id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			while(list($id) = mysql_fetch_row($result)) {
				$res[] = $id;

				if(!in_array($id, $childs)) $res = array_merge($res, $this->getChildClasses($id, $res));
			}
			$res = array_unique($res);
			return $res;
		}

		/**
			* Получить список типов данных, которые можно использовать в качестве справочников
			* @param Boolean $public_only=false искать только те типа данных, у которых стоит флаг "Публичный"
			* @param Integer $parentTypeId = null искать только в этом родителе
			* @return Array массив, где ключ это id типа данных, а значение это его название
		*/
		public function getGuidesList($public_only = false, $parentTypeId = null) {
			$res = Array();

			if($public_only) {
				$sql = "SELECT id, name FROM cms3_object_types WHERE is_guidable = '1' AND is_public = '1'";
			} else {
				$sql = "SELECT id, name FROM cms3_object_types WHERE is_guidable = '1'";
			}
			
			if($parentTypeId) {
				$parentTypeId = (int) $parentTypeId;
				 $sql .= " AND parent_id = '{$parentTypeId}'";
			}

			$result = l_mysql_query($sql);

			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}
			return $res;
		}

		/**
			* Получить список всех типов данных, связанных с базовым типом (umiHierarchyType) $hierarchy_type_id
			* @param Integer $hierarchy_type_id id базового типа
			* @param Boolean $ignoreMicroCache=false не использовать микрокеширование результата
			* @return Array массив, где ключ это id типа данных, а значние - название типа данных
		*/
		public function getTypesByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;

			if(isset($cache[$hierarchy_type_id]) && $ignoreMicroCache == false) return $cache[$hierarchy_type_id];

			$sql = "SELECT id, name FROM cms3_object_types WHERE hierarchy_type_id = '{$hierarchy_type_id}'";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$res = Array();
			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}

			return $cache[$hierarchy_type_id] = $res;
		}

		/**
			* Получить тип данных, связанный с базовым типом (umiHierarchyType) $hierarchy_type_id
			* @param Integer $hierarchy_type_id id базового типа
			* @param Boolean $ignoreMicroCache=false не использовать микрокеширование результата
			* @return Integer id типа данных, либо false
		*/
		public function getTypeByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;
			
			if(isset($cache[$hierarchy_type_id]) && $ignoreMicroCache == false) return $cache[$hierarchy_type_id];

			$sql = "SELECT id FROM cms3_object_types WHERE hierarchy_type_id = '{$hierarchy_type_id}' LIMIT 1";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			if(list($id) = mysql_fetch_row($result)) {
				return $cache[$hierarchy_type_id] = $id;
			} else {
				return $cache[$hierarchy_type_id] = false;
			}
		}


		/**
			* Получить тип данных, связанный с базовым типом (umiHierarchyType) $module/$method
			* @param String $module
			* @param String $method
			* @return Integer id типа данных, либо false
		*/
		public function getBaseType($name, $ext = "") {
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName($name, $ext);

			if($hierarchy_type) {
				$hierarchy_type_id = $hierarchy_type->getId();
				$type_id = $this->getTypeByHierarchyTypeId($hierarchy_type_id);
				return (int) $type_id;
			} else {
				return false;
			}
		}
	}


/**
	* Этот класс служит для управления/получения доступа к объектам.
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
*/
	class umiObjectsCollection extends singleton implements iSingleton, iUmiObjectsCollection {
		private	$objects = Array();
		private $updatedObjects = Array();

		protected function __construct() {
		}

		/**
			* Получить экземпляр коллекции
			* @return umiObjectsCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Проверить, загружен ли в память объект с id $object_id
			* @param Interger $object_id id объекта
			* @return Boolean true, если объект загружен
		*/
		private function isLoaded($object_id) {
			if(gettype($object_id) == "object") {
			throw new coreException("Object given!");
			}
			return (bool) array_key_exists($object_id, $this->objects);
		}

		/**
			* Проверить, существует ли в БД объект с id $object_id
			* @param Integer $object_id id объекта
			* @return Boolean true, если объект существует в БД
		*/
		public function isExists($object_id) {
			$object_id = (int) $object_id;
			$result = l_mysql_query("SELECT COUNT(*) FROM cms3_objects WHERE id = '{$object_id}'");

			if($err = mysql_error()) {
				throw new coreException($err);
			} else {
				list($count) = mysql_fetch_row($result);
			}
			return ($count > 0);
		}

		/**
			* Получтить экземпляр объекта с id $object_id
			* @param Integer $object_id id объекта
			* @return umiObject экземпляр объекта $object_id, либо false в случае неудачи
		*/
		public function getObject($object_id) {
			$object_id = (int) $object_id;

			if(!$object_id) {
				return false;
			}

			if($this->isLoaded($object_id)) {
				return $this->objects[$object_id];
			}

			$object = cacheFrontend::getInstance()->load($object_id, "object");
			if($object instanceof umiObject == false) {
				try {
					$object = new umiObject($object_id);
				} catch (baseException $e) {
					return false;
				}
				cacheFrontend::getInstance()->save($object, "object");
			}

			
			if(is_object($object)) {
				$this->objects[$object_id] = $object;
				return $this->objects[$object_id];
			} else {
				return false;
			}
		}

		/**
			* Удалить объект с id $object_id. Если объект заблокирован, он не будет удален.
			* При удалении принудительно вызывается commit() на удаляемом объекте
			* Нельзя удалить пользователей с id 14, 2373, нельзя удалить группу супервайзеров.
			* @param Integer $object_id id объекта
			* @return Boolean true, если удаление удалось
		*/
		public function delObject($object_id) {
			if($this->isExists($object_id)) {
				$this->disableCache();
			
				$object_id = (int) $object_id;
				
				if(defined("SV_USER_ID")) {
					if($object_id == SV_USER_ID || $object_id == SV_GROUP_ID || $object_id == 2373) {
						throw new coreException("You are not allowed to delete object #{$object_id}. Never. Don't even try.");
					}
				}
				
				
				//Make sure, we don't will not try to commit it later
				$object = $this->getObject($object_id);
				$object->commit();

				$sql = "DELETE FROM cms3_objects WHERE id = '{$object_id}' AND is_locked='0'";
				l_mysql_query($sql);

				if($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				if($this->isLoaded($object_id)) {
					unset($this->objects[$object_id]);
				}
				
				cacheFrontend::getInstance()->del($object_id, "object");

				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать новый объект в БД
			* @param String $name название объекта.
			* @param Integer $type_id id типа данных (класс umiObjectType), которому будет принадлежать объект.
			* @param Boolean $is_locked=false Состояние блокировки по умолчанию. Рекоммендуем этот параметр не указывать.
			* @return Integer id созданного объекта, либо false в случае неудачи
		*/
		public function addObject($name, $type_id, $is_locked = false) {
			$this->disableCache();
		
			$type_id = (int) $type_id;
			
			if(!$type_id) {
				throw new coreException("Can't create object without object type id (null given)");
			}

			$sql = "INSERT INTO cms3_objects (type_id) VALUES('$type_id')";
			l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$object_id = mysql_insert_id();
			$object = new umiObject($object_id);

			$object->setName($name);
			$object->setIsLocked($is_locked);

			//Set current user
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$user_id = cmsController::getInstance()->getModule("users")->user_id;
					$object->setOwnerId($user_id);
				}
			} else {
				$object->setOwnerId(NULL);
			}

			$object->commit();
			$this->objects[$object_id] = $object;

			try {
				$this->resetObjectProperties($object_id);
			} catch (valueRequiredException $e) {
				$e->unregister();
			}

			return $object_id;
		}

		/**
			* Сделать копию объекта и всех его свойств
			* @param id $iObjectId копируемого объекта
			* @return Integer id объекта-копии
		*/
		public function cloneObject($iObjectId) {
			$vResult = false;

			$oObject = $this->getObject($iObjectId);
			if ($oObject instanceof umiObject) {
				// clone object definition
				$sSql = "INSERT INTO cms3_objects (name, is_locked, type_id, owner_id) SELECT name, is_locked, type_id, owner_id FROM cms3_objects WHERE id = '{$iObjectId}'";
				l_mysql_query($sSql);

				if ($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				$iNewObjectId = mysql_insert_id();

				// clone object content
				$sSql = "INSERT INTO cms3_object_content (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val)  SELECT '{$iNewObjectId}' as obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val FROM cms3_object_content WHERE obj_id = '$iObjectId'";
				l_mysql_query($sSql);

				if ($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}

				$vResult = $iNewObjectId;
			}

			return $vResult;
		}
 
		/**
			* Получить отсортированный по имени список всех объектов в справочнике $guide_id (id типа данных).
			* Равнозначно если бы мы хотели получить этот список для всех объектов определенного типа данных
			* @param id $guide_id справочника (id типа данных)
			* @return Array массив, где ключи это id объектов, а значения - названия объектов
		*/
		public function getGuidedItems($guide_id) {
			$res = Array();

			$guide_id = (int) $guide_id;

			$ignoreSorting = intval(regedit::getInstance()->getVal("//settings/ignore_guides_sort")) ? true : false;
			if($ignoreSorting)
				$sql = "SELECT id, name FROM cms3_objects WHERE type_id = '{$guide_id}' ORDER BY id ASC";
			else
				$sql = "SELECT id, name FROM cms3_objects WHERE type_id = '{$guide_id}' ORDER BY name ASC";
			$result = l_mysql_query($sql);

			if($err = mysql_error()) {
				throw new coreException($err);
				return false;
			}

			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}
			
			if(!$ignoreSorting)
				natsort($res);

			return $res;
		}
		
		
		/**
			* Обнулить все свойства у объекта $object_id
			* @param Integer $object_id id объекта
		*/
		protected function resetObjectProperties($object_id) {
			$object = $this->getObject($object_id);
			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$object_fields = $object_type->getAllFields();
			foreach($object_fields as $object_field) {
				$object->setValue($object_field->getName(), Array());
			}
			
			if(sizeof($object_fields) == 0) {
				$tableName = umiBranch::getBranchedTableByTypeId($object_type_id);
				$sql = "INSERT INTO {$tableName} (obj_id, field_id) VALUES ('{$object_id}', NULL)";
				l_mysql_query($sql);
				if($err = mysql_error()) {
					throw new coreException($err);
				}
			}
		}
		
		
		/**
			* Выгрузить объект из коллекции
			* @param Integer $object_id id объекта
		*/
		public function unloadObject($object_id) {
			if($this->isLoaded($object_id)) {
				unset($this->objects[$object_id]);
			} else {
				return false;
			}
		}
		
		/**
			* Получить id всех объектов, загруженных в коллекцию
			* @return Array массив, состоящий из id объектов
		*/
		public function getCollectedObjects() {
			return array_keys($this->objects);
		}
		
		/**
			* Указать, что $object_id был изменен во время сессии. Используется внутри ядра.
			* Явный вызов этого метода клиентским кодом не нужен.
			* @param Integer $object_id id объекта
		*/
		public function addUpdatedObjectId($object_id) {
			if(!in_array($object_id, $this->updatedObjects)) {
				$this->updatedObjects[] = $object_id;
			}
		}
		
		/**
			* Получить список измененных объектов за текущую сессию
			* @return Array массив, состоящий из id измененных значений
		*/
		public function getUpdatedObjects() {
			return $this->updatedObjects;
		}
		
		/**
			* Деструктор коллекции. Явно вызывать его не нужно никогда.
		*/
		public function __destruct() {
			if(sizeof($this->updatedObjects)) {
				if(function_exists("deleteObjectsRelatedPages")) {
					deleteObjectsRelatedPages();
					
				}
			}
		}
	}


	interface iBackupModel {
		public function getChanges($param = "");
		public function save($cparam = "");
		public function rollback($revisionId);
		public function addLogMessage($elementId);
		public function fakeBackup($elementId);
	};


/**
	* Класс для управления резервными копиями страниц
*/
	class backupModel extends singleton implements iBackupModel {

		protected function __construct() {}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Получить список изменений для страницы $cparam
			* @param Integer $cparam = false id страницы
			* @param String $changed_module = "" не используется более
			* @param String $changed_method = "" не используется более
			* @return Array список изменений
		*/
		public function getChanges($cparam = false, $changed_module = "", $changed_method = "") {
			if(!regedit::getInstance()->getVal("modules/backup/enabled")) {
				return false;
			}

			$params = array();

			if(!$changed_module) {
				$changed_module = "content";
			}

			if(!$changed_method) {
				$changed_method = "edit_page_do";
			}

			$limit = (int) regedit::getInstance()->getVal("//modules/backup/max_save_actions");
			$limit -= 1;

			$changed_module = mysql_real_escape_string($changed_module);
			$changed_method = mysql_real_escape_string($changed_method);
			$cparam = (int) $cparam;

			$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $cparam . "' GROUP BY param0 ORDER BY ctime DESC";
			$result = l_mysql_query($sql);

			$c = 0;
			$rows = Array();
			while(list($revision_id, $ctime, $changed_module, $user_id, $is_active) = mysql_fetch_row($result)) {
				$revision_info = Array();
				$revision_info['attribute:changetime'] = $ctime;
				$revision_info['attribute:user-id'] = $user_id;
				if(strlen($changed_module) == 0) {
					$revision_info['attribute:is-void'] = true;
				}

				if($is_active) {
					$revision_info['attribute:active'] = "active";
				}
				
				$revision_info['date'] = new umiDate($ctime);
				$revision_info['author'] = selector::get('object')->id($user_id);
				$revision_info['link'] = "/admin/backup/rollback/{$revision_id}/";

				$rows[] = $revision_info;
			}

			$params['nodes:revision'] = $rows;
			return $params;

		}

		/**
			* Сохранить как точку восстановления текущие изменения для страницы $cparam
			* @param Integer $cparam = false id страницы
			* @param String $changed_module = "" не используется более
			* @param String $changed_method = "" не используется более
		*/
		public function save($cparam = "", $cmodule = "", $cmethod = "") {
			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) return false;
			if(getRequest('rollbacked')) return false;
			
			$cmsController = cmsController::getInstance();
			if(!$cmodule) $cmodule = $cmsController->getCurrentModule();
			$cmethod = $cmsController->getCurrentMethod();

			$cuser_id = ($cmsController->getModule('users')) ? $cuser_id = $cmsController->getModule('users')->user_id : 0;


			$ctime = time();

			if(!$cmodule) {
				$cmodule = getRequest('module');
			}

			if(!$cmethod) {
				$cmethod = getRequest('method');
			}

			foreach($_REQUEST as $cn => $cv) {
				if($cn == "save-mode") continue;
				$_temp[$cn] = (!is_array($cv)) ? base64_encode($cv) : $cv;
			}
			
			
			if(isset($_temp['data']['new'])) {
				$element = umiHierarchy::getInstance()->getElement($cparam);
				if($element instanceof umiHierarchyElement) {
					$_temp['data'][$element->getObjectId()] = $_temp['data']['new'];
					unset($_temp['data']['new']);
				}
				
			}

			$req = serialize($_temp);
			$req = mysql_real_escape_string($req);
			
			$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $cparam . "'";
			l_mysql_query($sql);

			$sql = <<<SQL
INSERT INTO cms_backup (ctime, changed_module, changed_method, param, param0, user_id, is_active) 
				VALUES('{$ctime}', '{$cmodule}', '{$cmethod}', '{$cparam}', '{$req}', '{$cuser_id}', '1')
SQL;
			l_mysql_query($sql);

			$limit = regedit::getInstance()->getVal("//modules/backup/max_save_actions");
			$sql = "SELECT COUNT(*) FROM cms_backup WHERE param='" . $cparam . "' ORDER BY ctime DESC";
			$result = l_mysql_query($sql);
			list($total_b) = mysql_fetch_row($result);
		
			$time_limit = regedit::getInstance()->getVal("//modules/backup/max_timelimit");
		
			$td = $total_b - $limit;
			if($td < 0) {
				$td = 0;
			}

			$sql = "DELETE FROM cms_backup WHERE param='" . $cparam . "' ORDER BY ctime ASC LIMIT " . ($td);
			l_mysql_query($sql);
		
			$end_time=$time_limit*3600*24;
			$sql="DELETE FROM cms_backup WHERE param='" . $cparam . "' AND (".time()."-ctime)>".$end_time." ORDER BY ctime ASC";
			l_mysql_query($sql);

			return true;
		}

		/**
			* Восстановить данные из резервной точки $revision_id
			* @param Integer $revision_id id резервное копии
			* @return Boolean false, если восстановление невозможно
		*/
		public function rollback($revision_id) {
			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) {
				return false;
			}

			$revision_id = (int) $revision_id;

			$sql = "SELECT param, param0, changed_module, changed_method FROM cms_backup WHERE id='$revision_id' LIMIT 1";
			$result = l_mysql_query($sql);

			if(list($element_id, $data, $changed_module, $changed_method) = mysql_fetch_row($result)) {
				$changed_param = $element_id;

				$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $changed_param . "'";
				l_mysql_query($sql);

				$sql = "UPDATE cms_backup SET is_active='1' WHERE id='" . $revision_id . "'";
				l_mysql_query($sql);

				$_temp = unserialize($data);
				$_REQUEST = Array();

				foreach($_temp as $cn => $cv) {
					if(!is_array($cv)) {
						$cv = base64_decode($cv);
					} else {
						foreach($cv as $i => $v) {
							$cv[$i] = $v;
						}
					}
					$_REQUEST[$cn] = $cv;
					$_POST[$cn] = $cv;
				}
				$_REQUEST['rollbacked'] = true;
				$_REQUEST['save-mode'] = getLabel('label-save');

				if($changed_module_inst = cmsController::getInstance()->getModule($changed_module)) {
					$element = umiHierarchy::getInstance()->getElement($element_id);
					
					if($element instanceof umiHierarchyElement) {
						$links = $changed_module_inst->getEditLink($element_id, $element->getMethod());
						if(sizeof($links) >= 2) {
							$edit_link = $links[1];
							$_REQUEST['referer'] = $edit_link;
							
							$edit_link = trim($edit_link, "/") . "/do";
							
							if(preg_match("/admin\/[A-z]+\/([^\/]+)\//", $edit_link, $out)) {
								if(isset($out[1])) {
									$changed_method = $out[1];
								}
							}
							$_REQUEST['path'] = $edit_link;
							$_REQUEST['param0'] = $element_id;
							$_REQUEST['param1'] = "do";
						}
					}

					return $changed_module_inst->cms_callMethod($changed_method, Array());
				} else {
					throw new requreMoreAdminPermissionsException("You can't rollback this action. No permission to this module.");
				}
			}

		}
	
		/**
			* Добавить сообщение в список изменений страницы $elementId без занесения самих изменений
			* @param Integer $elementId id страницы
		*/
		public function addLogMessage($elementId) {
			if(!regedit::getInstance()->getVal("//modules/backup/enabled")) {
				return false;
			}
			
			$cmsController = cmsController::getInstance();
			$cuser_id = ($cmsController->getModule('users')) ? $cmsController->getModule('users')->user_id : 0;
			
			$time = time();
			$param = (int) $elementId;
			
			$sql = "INSERT INTO cms_backup (ctime, param, user_id, param0) VALUES('{$time}', '{$param}', '{$cuser_id}', '{$time}')";
			mysql_query($sql);
		}
		
		public function fakeBackup($elementId) {
			$element = selector::get('page')->id($elementId);
			if(is_null($element)) return false;
			$originalRequest = $_REQUEST;
			
			$object = $element->getObject();
			$type = selector::get('object-type')->id($object->getTypeId());
			
			$_REQUEST['name'] = $element->name;
			$_REQUEST['alt-name'] = $element->altName;
			$_REQUEST['active'] = $element->isActive;
			foreach($type->getAllFields() as $field) {
				$fieldName = $field->getName();
				$value = $this->fakeBackupValue($object, $field);
				if(is_null($value)) continue;
				$_REQUEST['data'][$object->id][$fieldName] = $value;
			}
			
			$this->save($elementId, $element->getModule());
			$_REQUEST = $originalRequest;
		}
		
		protected function fakeBackupValue(iUmiObject $object,  iUmiField $field) {
			$value = $object->getValue($field->getName());
			
			switch($field->getDataType()) {
				case 'file':
				case 'img_file':
				case 'swf_file':
					return ($value instanceof iUmiFile) ? $value->getFilePath() : '';
				
				case 'boolean':
					return $value ? '1' : '0';
				
				case 'date':
					return ($value instanceof umiDate) ? $value->getFormattedDate('U') : NULL;
				
				case 'tags':
					return is_array($value) ? implode(", ", $value) : NULL;
				
				default:
					return (string) $value;
			}
		}
	};


	class baseModuleAdmin {
		protected	$dataTypes = array('list', 'message', 'form'),
				$actionTypes = array('modify', 'create', 'view');		
							
				
		public function setDataRange($limit, $offset = 0) {
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}
		
		public function setDataRangeByPerPage($per_page, $curr_page = 0) {
			$this->setDataRange($per_page, $curr_page * $per_page);
		}
		

		public function setDataType($dataType) {
			$this->limit = false;
			$this->offset = false;

			$this->dataType = $dataType;
		}
		
		
		public function setActionType($actionType) {
			$this->actionType = $actionType;
		}
		
		
		public function setData($data, $total = false) {
			$this->total = $total;
			$this->data = $data;
		}


		public function doData() {
			$dataSet = array();
			$dataSet['attribute:type'] = $this->dataType;
			$dataSet['attribute:action'] = $this->actionType;
			
			if($this->total) {
				$dataSet['attribute:total'] = $this->total;

				if(!is_null($this->offset)) {
					$dataSet['attribute:offset'] = $this->offset;
				}
			
				if(!is_null($this->limit)) {
					
					$dataSet['attribute:limit'] = $this->limit;
				}
			}
			
			$dataSet = array_merge($dataSet, $this->data);

			xslAdminTemplater::getInstance()->setDataSet($dataSet);
		}

		
		public function prepareData($inputData, $type) {
			$data = array();
			
			$this->requireSlashEnding();
			
			switch($type) {
				case "page": {
					$data = $this->prepareDataPage($inputData);
					break;
				}


				case "pages": {
					$data = $this->prepareDataPages($inputData);
					break;
				}


				case "object": {
					$data = $this->prepareDataObject($inputData);
					break;
				}


				case "objects": {
					$data = $this->prepareDataObjects($inputData);
					break;
				}
				
				case "type": {
					$data = $this->prepareDataType($inputData);
					break;
				}
				
				case "field": {
					$data = $this->prepareDataField($inputData);
					break;
				}
				
				case "group": {
					$data = $this->prepareDataGroup($inputData);
					break;
				}

				case "types": {
					$data = $this->prepareDataTypes($inputData);
					break;
				}
				
				case "hierarchy_types": {
					$data = $this->prepareDataHierarchyTypes($inputData);
					break;
				}


				case "domains": {
					$data = $this->prepareDataDomains($inputData);
					break;
				}

				case "domain_mirrows": {
					$data = $this->prepareDataDomainMirrows($inputData);
					break;
				}
				
				
				case "templates": {
					$data = $this->prepareDataTemplates($inputData);
					break;
				}
				
				case "template": {
					$data = $this->prepareDataTemplate($inputData);
					break;
				}


				case "settings": {
					$data = $this->prepareDataSettings($inputData);
					break;
				}
				
				case "modules": {
					$data = $this->prepareDataModules($inputData);
					break;
				}
				
				
				case "langs": {
					$data = $this->prepareDataLangs($inputData);
					break;
				}


			
				default: {
					throw new coreException("Data type \"{$type}\" is unknown.");
				}
			}
			
			return $data;
		}


		public function prepareDataPage($inputData) {
			$element = getArrayKey($inputData, "element");
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			if ($this->systemIsLocked($element, $oUsersMdl->user_id)){
				throw new wrongElementTypeAdminException(getLabel("error-element-locked"));	
			}
			$oEventPoint = new umiEventPoint("sysytemBeginPageEdit");
			
			
			
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("user_id", $oUsersMdl->user_id);
			$oEventPoint->setParam("lock_time", time());
			
			$oEventPoint->addRef("element", $element);
			
			$oEventPoint->call();
			$data = array();

			$dataModule = cmsController::getInstance()->getModule("data");
			$cmsController = cmsController::getInstance();
			
			$page = array();
			if($this->actionType == "create") {
				$module = get_class($this);
				if(getArrayKey($inputData, 'module')) $module = getArrayKey($inputData, 'module');
				
				if($this->checkAllowedElementType($inputData) == false) {
						throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}

				$method = $inputData['type'];
				if($method == "page" && $module == "content") {
					$method = "";
				}
				
				if(is_numeric($method)) {
					$base_type_id = $type_id = $method;
				} else {
					$base_type_id = $type_id = umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
				}

				$parent = $inputData['parent'];
				if($parent instanceof iUmiHierarchyElement) {
					$parent_id = $parent->getId();

					$this->checkDomainPermissions($parent->getDomainId());
					$this->checkElementPermissions($parent_id);

					xslAdminTemplater::getInstance()->currentEditElementId = $parent_id;

					$dominant_type_id = umiHierarchy::getInstance()->getDominantTypeId($parent_id);
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}


					$dominant_tpl_id = umiHierarchy::getInstance()->getDominantTplId($parent_id);
					if($dominant_tpl_id) {
						$tpl_id = $dominant_tpl_id;
					}
				} else {
					$parent_id = 0;
					
					$this->checkDomainPermissions();
					
					$dominant_type_id = umiHierarchy::getInstance()->getDominantTypeId(0);
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}
					
					$lang_id = $cmsController->getCurrentLang()->getId();
					$domain_id = $cmsController->getCurrentDomain()->getId();
					
					if($floated_domain_id = $this->getFloatedDomain()) {
						$domain_id = $floated_domain_id;
					}
					
					$default_template = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
					if($default_template instanceof iTemplate) {
						$tpl_id = $default_template->getId();
					} else {
						throw new publicAdminException(getLabel('error-require-default-template'));
					}
					
				}
				
				if($this->compareObjectTypeByHierarchy($module, $method, $type_id) == false) {
					$type_id = $base_type_id;
				}

				if(isset($inputData['type_id'])) {
					$type_id = $inputData['type_id'];
				} elseif(isset($inputData['type-id'])) {
					$type_id = $inputData['type-id'];
				}

				if($type_id > 0) {
					$page['attribute:name'] = "";
					$page['attribute:parentId'] = $parent_id;
					$page['attribute:type-id'] = $type_id;
					$page['attribute:tpl-id'] = $tpl_id;
					$page['attribute:active'] = "active";
					
					$page['basetype'] = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method);
					$page['properties'] = $dataModule->getCreateForm($type_id, false, false, true);
				} else {
					throw new coreException("Give me a normal type to create ;)");
				}
				
				if($module == 'content' && $method == '') {
					$page['attribute:visible'] = 'visible';
				}
			} else if ($this->actionType == "modify") {
				if($inputData instanceof umiHierarchyElement) {
					$element = $inputData;
				} else if (is_array($inputData)){
					$element = $inputData['element'];
				} else {
					throw new coreException("Unknown type of input data");
				}
				
				if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}
				
				$this->checkDomainPermissions($element->getDomainId());
				$this->checkElementPermissions($element->getId());
				
				xslAdminTemplater::getInstance()->currentEditElementId = $element->getId();

				$object_id = $element->getObject()->getId();

				$page['attribute:id'] = $element->getId();
				$page['attribute:parentId'] = $element->getParentId();
				$page['attribute:object-id'] = $object_id;
				$page['attribute:type-id'] = $element->getObject()->getTypeId();
				$page['attribute:alt-name'] = $element->getAltName();
				$page['attribute:tpl-id'] = $element->getTplId();


				if($element->getIsActive()) {
					$page['attribute:active'] = "active";
				}

				if($element->getIsVisible()) {
					$page['attribute:visible'] = "visible";
				}

				if($element->getIsDefault()) {
					$page['attribute:default'] = "default";
				}
				
				$page['basetype'] = $element->getHierarchyType();

				$page['name'] = $element->getName();

				$page['properties'] = $dataModule->getEditForm($object_id, false, false, true, true);
			}
			$data['page'] = $page;
			return $data;
		}


		public function prepareDataPages($inputData) {
			$data = array();
			$hierarchy = umiHierarchy::getInstance();
			$pages = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$element = $inputData[$i];
				if(is_numeric($element)) {
					$element = $hierarchy->getElement($element, false, true);
				}
				
				if($element instanceof umiHierarchyElement) {
					if(getRequest('viewMode') == 'full') {
						$pages[] = array('full:' => $element);
					} else {
						$pages[] = $element;
						$hierarchy->unloadElement($element->getId());
					}
				}
			}
			
			$data['nodes:page'] = $pages;
			
			return $data;
		}


		public function prepareDataObjects($inputData) {
			$data = array();
 
			$objectsCollection = umiObjectsCollection::getInstance();
			$objects = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$object = $inputData[$i];
				if(is_numeric($object)) {
					$object = $objectsCollection->getObject($object);
				}
				
				if($object instanceof umiObject) {
					if(getRequest('viewMode') == 'full') {
						$objects[] = array('full:' => $object);
					} else {
						$objects[] = $object;
						$objectsCollection->unloadObject($object->getId());
					}
				}
			}
			$data['nodes:object'] = $objects;

			return $data;
		}


		public function prepareDataObject($inputData) {
			$data = array();

			$dataModule = cmsController::getInstance()->getModule("data");

			if($this->checkAllowedElementType($inputData, true) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}


			$object = array();
			if($this->actionType == "create") {
				$typeId = false;
				$module = get_class($this);
				$method = getArrayKey($inputData, 'type');

				if($module && $method) {
					$typeId = umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
				}

				if(isset($inputData['type-id'])) {
					$typeId = $inputData['type-id'];
				}
				
				if($typeId == false) {
					throw new publicAdminException("Object type id is required to create new object");
				}

				$object['attribute:type-id'] = $typeId;
				$object['properties'] = $dataModule->getCreateForm($typeId, false, false, true);
			} else {
				if($inputData instanceof umiObject == false) {
					if(is_object($inputData = getArrayKey($inputData, 'object')) === false) {
						throw new publicAdminException(getLabel("error-expect-object"));
					}
				}
			
				$object['attribute:id'] = $inputData->getId();
				$object['attribute:name'] = $inputData->getName();
				$object['attribute:type-id'] = $inputData->getTypeId();
				$object['attribute:owner-id'] = $inputData->getOwnerId();
				$object['properties'] = $dataModule->getEditForm($inputData->getId(), false, false, true, true);
			}



			$data['object'] = $object;

			return $data;
		}
		
		
		public function prepareDataType($inputData) {
			$data = array();
			$data['full:type'] = $inputData;
			xmlTranslator::$showHiddenFieldGroups = true;
			return $data;
		}
		
		
		public function prepareDataField($inputData) {
			$data = array();
			
			if($this->actionType == "create") {
				$field = array();
				$field['attribute:visible'] = 'visible';
				$data['field'] = $field;
			} else {
				$data['full:field'] = $inputData;
			}
			return $data;
		}
		
		
		public function prepareDataGroup($inputData) {
			$data = array();
			
			if($this->actionType == "create") {
				$group_arr = array();
				$group_arr['attribute:visible'] = true;
				$data['group'] = $group_arr;
			} else {
				if($inputData instanceof umiFieldsGroup) {
					$data['group'] = $inputData;
				} else {
					throw new coreException("Expected instance of umiFieldsGroup");
				}
			}
			return $data;
		}


		public function prepareDataTypes($inputData) {
			$data = array();

			$typesCollection = umiObjectTypesCollection::getInstance();
			$types = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$type_id = $inputData[$i];
				$type = $typesCollection->getType($type_id);
				if($type instanceof umiObjectType) {
					$types[] = $type;
				}
			}
			$data['nodes:type'] = $types;
			return $data;
		}


		public function prepareDataHierarchyTypes($inputData) {
			$data = array();

			$typesCollection = umiHierarchyTypesCollection::getInstance();
			$types = array();
			
			foreach($inputData as $item) {
				if($item instanceof iUmiHierarchyType) {
					$types[] = $item;
				} else {
					$type_id = $item;
					$type = $typesCollection->getType($type_id);
					
					if($type instanceof iUmiHierarchyType) {
						$types[] = $type;
					}
				}
			}
			$data['nodes:basetype'] = $types;
			return $data;
		}


		public function prepareDataDomains($inputData) {
			$data = array();

			$domains = array();
			foreach($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}


		public function prepareDataDomainMirrows($inputData) {
			$data = array();

			$domains = array();
			foreach($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domainMirrow'] = $domains;
			return $data;
		}
		
		
		public function prepareDataTemplates($inputData) {
			$data = array();
			$domainsCollection = domainsCollection::getInstance();
			
			$domains = array();
			foreach($inputData as $host => $templates) {
				$domain = array();
				$domain['attribute:id'] = $domainsCollection->getDomainId($host);
				$domain['attribute:host'] = $host;
				$domain['nodes:template'] = $templates;
				$domains[] = $domain;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}
		
		
		public function prepareDataTemplate(iTemplate $template) {
			$hierarchy = umiHierarchy::getInstance();
		
			$data = array();
			$info = array();
			$info['attribute:id'] = $template->getId();
			$info['attribute:title'] = $template->getTitle();
			$info['attribute:filename'] = $template->getFileName();
			$info['attribute:lang-id'] = $template->getLangId();
			$info['attribute:domain-id'] = $template->getDomainId();
			
			$used_pages = $template->getUsedPages();
			
			$pages = array();
			foreach($used_pages as $element_info) {
				$element = $hierarchy->getElement($element_info[0]);
				if($element instanceof umiHierarchyElement) {
					$element_id = $element->getId();
					$page_arr['attribute:id'] = $element_id;
					$page_arr['xlink:href'] = "upage://" . $element_id;
					$page_arr['basetype'] = selector::get('hierarchy-type')->id($element->getTypeId());
					$page_arr['name'] = str_replace("\"", "\\\"", $element->getName());
					$pages[] = $page_arr;
				}
			}
			$info['used-pages']['nodes:page'] = $pages;
			$data['template'] = $info;
			return $data;
		}


		public function prepareDataSettings($inputData) {
			$data = array();
			$data['nodes:group'] = array();

			foreach($inputData as $group_name => $params) {
				if(!is_array($params)) {
					continue;
				}
				
				$group = array();
				$group['attribute:name'] = $group_name;
				$group['attribute:label'] = getLabel("group-" . $group_name);

				$options = array();
				foreach($params as $param_key => $param_value) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);

					$option = array();
					$option['attribute:name'] = $param_name;
					$option['attribute:type'] = $param_type;
					$option['attribute:label'] = getLabel("option-" . $param_name);

					switch($param_type) {
						case "select": {
							$items = array();
							$value = isset($param_value['value']) ? $param_value['value'] : false;
							foreach($param_value as $item_id => $item_name) {
								if($item_id === "value") continue;
							
								$item_arr = array();
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item_name;
								$items[] = $item_arr;
							}
							$option['value'] = array("nodes:item" => $items);
							
							if($value !== false) {
								$option['value']['attribute:id'] = $value;
							}
							break;
						}
						
						case "password": {
							if($param_value) {
								$param_value = "********";
							} else {
								$param_value = "";
							}
							
							break;
						}
						
						case "symlink": {
							$hierarchy = umiHierarchy::getInstance();
						
							$param_value = @unserialize($param_value);
							if(!is_array($param_value)) {
								$param_value = array();
							}
							$items = array();
							foreach($param_value as $item_id) {
								$item = $hierarchy->getElement($item_id);
								if($item instanceof umiHierarchyElement == false) {
									continue;
								}
								
								$item_arr = array();
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item->getName();
								$items[] = $item_arr;
							}
							$option['value'] = array('nodes:item' => $items);
							break;
						}
					
						default: {
							$option['value'] = $param_value;
							break;
						}
					}

					$options[] = $option;
				}

				$group['nodes:option'] = $options;
				$data['nodes:group'][] = $group;
			}
			return $data;
		}
		
		
		public function prepareDataModules($inputData) {
			$data = array();
			$modules = array_values($inputData);
			
			$items = array();
			foreach($modules as $module_name) {
				$item_arr = array();
				$item_arr['attribute:label'] = getLabel('module-' . $module_name);
				$item_arr['node:module'] = $module_name;
				$items[] = $item_arr;
			}
			
			$data['nodes:module'] = $items;
			return $data;
		}
		
		
		public function prepareDataLangs($inputData) {
			$data = array();
			
			$langs = array();
			
			foreach($inputData as $lang) {
				$lang_arr = array();
				$lang_arr['attribute:id'] = $lang->getId();
				$lang_arr['attribute:title'] = $lang->getTitle();
				$lang_arr['attribute:prefix'] = $lang->getPrefix();
				$langs[] = $lang_arr;
			}
			
			$data['nodes:lang'] = $langs;
			return $data;
		}


		public function expectParams($params) {
			foreach($params as $group_key => $group) {
				foreach($group as $param_key => $param) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);

					$params[$group_key][$param_key] = $this->getExpectedParam($param_name, $param_type, $param);
				}
			}
			return $params;
		}


		public function getExpectedParam($param_name, $param_type, $param = NULL) {
			global $_FILES;

			$value = getRequest($param_name);

			if($param_type == "status") {
				return NULL;
			}

			if(is_null($value) && !in_array($param_type, array('file', 'weak_guide'))) {
				throw new requireAdminParamException("I expect value in request for param \"" . $param_name . "\"");
			}

			switch($param_type) {
				case "float": {
					return (float) $value;
				}

				case "bool":
				case "boolean":
				case "templates":
				case "guide":
				case "weak_guide":
				case "int": {
					return (int) $value;
				}

				case "password": {
					$value = ($value == "********") ? NULL : (string) $value;
					if($value) {
						try {
							$oOpenSSL = new umiOpenSSL();
							$bFilesOk = $oOpenSSL->supplyDefaultKeyFiles();
							if ($bFilesOk) {
								$value = 'umipwd_b64::' . base64_encode($oOpenSSL->encrypt($value));
							} else {
								$value = NULL;
							}
						} catch(publicException $e) {
							$value = NULL;
						}
					}
					return $value;
				}

				case "email":
				case "status":
				case "string": {
					return (string) $value;
				}
				
				case "symlink": {
					return serialize($value);
				}
				
				case "file": {
					
					$destination_folder = $param['destination-folder'];
					$group = isset($param['group']) ? $param['group'] : "pics";
					
					if($value = umiFile::upload($group, $param_name, $destination_folder)) {
						return $value;
					} else {
						$path = $destination_folder . getRequest('select_' . $param_name);
						return new umiFile($path);
					}
					break;
				}
				
				case "select": {
					return $value;
					break;
				}
				
				default: {
					throw new wrongParamException("I don't expect param \"" . $param_type . "\"");
				}
			}
		}


		public function expectElement($var, $strict = false, $byValue = false, $ignoreDeleted = false) {
			$element_id = ($byValue) ? $var : (int) getRequest($var);
			$element = umiHierarchy::getInstance()->getElement((int) $element_id, false, $ignoreDeleted);

			if($element instanceof umiHierarchyElement) {
				return $element;
			} else {
				if($strict) {
					throw new expectElementException(getLabel("error-expect-element"));
				} else {
					return false;
				}
			}
		}


		public function expectObject($var, $strict = false, $byValue = false) {
			$object_id = ($byValue) ? $var : (int) getRequest($var);
			$object = umiObjectsCollection::getInstance()->getObject((int) $object_id);

			if($object instanceof umiObject) {
				return $object;
			} else {
				if($strict) {
					throw new expectObjectException(getLabel("error-expect-object"));
				} else {
					return false;
				}
			}
		}


		public function expectElementId($var, $strict = false) {
			$element_id = (int) getRequest($var);

			if($element_id === 0 || umiHierarchy::getInstance()->isExists($element_id)) {
				return $element_id;
			} else {
				if($strict) {
					throw new expectElementException(getLabel("error-expect-element"));
				} else {
					return false;
				}
			}
		}


		public function expectObjectId($var, $strict = false) {
			$object_id = (int) getRequest($var);

			if($object_id === 0 || umiObjectsCollection::getInstance()->isExists($object_id)) {
				return $object_id;
			} else {
				if($strict) {
					throw new expectObjectException(getLabel("error-expect-object"));
				} else {
					return false;
				}
			}
		}
		
		
		public function expectObjectType($var, $strict = false) {
			$object_type_id = (int) getRequest($var);
			
			if($object_type_id === 0 || umiObjectTypesCollection::getInstance()->isExists($object_type_id)) {
				return umiObjectTypesCollection::getInstance()->getType($object_type_id);
			} else {
				if($strict) {
					throw new expectObjectTypeException(getLabel("error-expect-object-type"));
				} else {
					return false;
				}
			}
		}


		public function expectObjectTypeId($var, $strict = false, $byValue = false) {
			$object_type_id = (int) getRequest($var);
			if($byValue) {
				$object_type_id = $var;
			}
			
			$objectTypes = umiObjectTypesCollection::getInstance();
			if($object_type_id === 0 || $objectTypes->getType($object_type_id)) {
				return $object_type_id;
			} else {
				if($strict) {
					throw new expectObjectTypeException(getLabel("error-expect-object-type"));
				} else {
					return false;
				}
			}
		}


		public function saveEditedElementData($inputData) {
			if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$element = getArrayKey($inputData, "element");
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			$event = new umiEventPoint("systemModifyElement");
			$event->addRef("element", $element);
			$event->addRef("inputData", $inputData);
			$event->setParam("user_id", $oUsersMdl->user_id);
			$event->setMode("before");
			$event->call();
			
			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel("error-expect-element"));
			}
			
			$this->checkDomainPermissions($element->getDomainId());
			$this->checkElementPermissions($element->getId());
		
			if(!is_null($alt_name = getRequest('alt-name'))) {
				$element->setAltName($alt_name);
			}
			
			$module_name = $element->getModule();
			$method_name = $element->getMethod();

			if(!is_null($is_active = getRequest('active'))) {
				$permissions = permissionsCollection::getInstance();
				$user_id = $permissions->getUserId();
				if($permissions->isAllowedMethod($user_id, $module_name, "publish") != false) {
					$element->setIsActive($is_active);
				}
			}

			if(!is_null($is_visible = getRequest('is-visible'))) {
				$element->setIsVisible($is_visible);
			}
			
			if(!is_null($is_default = getRequest('is-default'))) {
				$element->setIsDefault($is_default);
			}
			
			if(!is_null($tpl_id = getRequest('template-id'))) {
				$element->setTplId($tpl_id);
			}

			$users = cmsController::getInstance()->getModule('users');
			if($users instanceof users) {
				if(is_array(getRequest('perms_read'))
					|| is_array(getRequest('perms_edit'))
					|| is_array(getRequest('perms_create'))
					|| is_array(getRequest('perms_delete'))
					|| is_array(getRequest('perms_move'))) {
						$users->setPerms($element->getId());
					}
			}
			
			backupModel::getInstance()->save($element->getId());
			
			$object = $element->getObject();
			if($object instanceof umiObject) {
				$this->saveEditedObjectData($object);
			}

			$element->commit();
			
			$this->currentEditedElementId = $element->getId();
			
			$event->setMode("after");
			$event->call();
			
			return $element;
		}


		public function saveAddedElementData($inputData) {
			$cmsController = cmsController::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$templates = templatesCollection::getInstance();

			$module = get_class($this);
			if(isset($inputData['module'])) $module = $inputData['module'];
			$method = $inputData['type'];
			$parent = $inputData['parent'];
			
			if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}
			
			if($module == "content" && $method == "page") {
				$method = "";
			}

			if($parent) {
				$this->checkElementPermissions($parent->getId(), permissionsCollection::E_CREATE_ALLOWED);
			}


			$event = new umiEventPoint("systemCreateElement");
			$event->addRef("inputData", $inputData);
			$event->setMode("before");
			$event->call();
			
			if($parent instanceof iUmiHierarchyElement) {
				$parent_id = $parent->getId();
				$lang_id = $parent->getLangId();
				$domain_id = $parent->getDomainId();
				

				$dominant_tpl_id = umiHierarchy::getInstance()->getDominantTplId($parent_id);
				if($dominant_tpl_id) {
					$tpl_id = $dominant_tpl_id;
				} else {
					throw new coreException(getLabel('error-dominant-template-not-found'));
				}
			} else {
				$parent_id = 0;
				$lang_id = $cmsController->getCurrentLang()->getId();
				$domain_id = $cmsController->getCurrentDomain()->getId();

				if($floated_domain_id = $this->getFloatedDomain()) {
					$domain_id = $floated_domain_id;
				}

				$tpl_id = $templates->getDefaultTemplate()->getId();
			}
			
			$this->checkDomainPermissions($domain_id);

			if(getRequest('template-id')) {
				$tpl_id = getRequest('template-id');
			}

			$hierarchy_type = $hierarchyTypes->getTypeByName($module, $method);
			if($hierarchy_type instanceof iUmiHierarchyType) {
				$hierarchy_type_id = $hierarchy_type->getId();
			} else {
				throw new coreException(getLabel('error-element-type-detect-failed'));
			}

			if(is_null($name = getRequest('name'))) {
				throw new coreException(getLabel('error-require-name-param'));
			}

			if(is_null($alt_name = getRequest('alt-name'))) {
				$alt_name = $name;
			}
			
			$type_id = getArrayKey($inputData, 'type-id');
			
			if(!$type_id && !($type_id = getRequest("type-id"))) {
				$type_id = $objectTypes->getBaseType($module, $method);

				if($parent instanceof iUmiHierarchyElement) {
					$dominant_type_id = $hierarchy->getDominantTypeId($parent->getId());
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}
				}
			}

			if(!$type_id) {
				throw new coreException("Base type for {$module}::{$method} doesn't exists");
			}

			$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $name, $alt_name, $type_id, $domain_id, $lang_id, $tpl_id);

			$users = $cmsController->getModule('users');
			if($users instanceof users) {
			
				if(
					is_array(getRequest('perms_read')) ||
					is_array(getRequest('perms_edit')) ||
					is_array(getRequest('perms_create')) ||
					is_array(getRequest('perms_delete')) ||
					is_array(getRequest('perms_move'))
				) {
					$users->setPerms($element_id);
					backupModel::getInstance()->save($element_id);
				} else {
					permissionsCollection::getInstance()->setDefaultPermissions($element_id);
				}
			}

			$element = $hierarchy->getElement($element_id);

			if($element instanceof iUmiHierarchyElement) {
				$module_name = $element->getModule();
				$method_name = $element->getMethod();
				
				if(!is_null($is_active = getRequest('active'))) {
					$permissions = permissionsCollection::getInstance();
					$user_id = $permissions->getUserId();
					if($permissions->isAllowedMethod($user_id, $cmsController->getCurrentModule(), "publish") == false) {
						$is_active = false;
					}
					
					$element->setIsActive($is_active);
				}
				
				if(!is_null($is_visible = getRequest('is-visible'))) {
					$element->setIsVisible($is_visible);
				}
				
				if(!is_null($tpl_id = getRequest('template-id'))) {
					$element->setTplId($tpl_id);
				}
				
				if(!is_null($is_default = getRequest('is-default'))) {
					$element->setIsDefault($is_default);
				}
				
				if(!is_null($name = getRequest('name'))) {
					$element->setValue('h1', $name);
				}
				
				

				$object = $element->getObject();
				
				$this->saveAddedObject($object);

				$element->commit();
				$newObject = $element->getObject();
				//Set up "publish" status to new page
				if (!$newObject->getValue("publish_status")){
					$newObject->setValue("publish_status", $this->getPageStatusIdByStatusSid());
					$newObject->commit();
				}
				$event_after = new umiEventPoint("systemCreateElement");
				$event_after->addRef("element", $element);
				$event_after->setMode("after");
				$event_after->call();
				
				$this->currentEditedElementId = $element_id;
				return $element_id;
			} else {
				throw new coreException("Can't get created element instance");
			}
		}


		public function saveEditedObjectData($inputData) {
			if(is_array($inputData)) {
				$object = getArrayKey($inputData, 'object');
			} else {
				$object = $inputData;
			}

			if($object instanceof umiObject === false) {
				throw new coreException("Expected instance of umiObject in param");
			}
			
			if(is_array($inputData)) {
				$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'), $object->getId());
			}
			
			$event = new umiEventPoint("systemModifyObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();
		
			if(!is_null($name = getRequest('name'))) {
				$object->setName($name);
				$object->setValue('nazvanie', $name);
			}
			
			if(!is_null($type_id = getRequest('type-id'))) {
				$object->setTypeId($type_id);
			}

			$dataModule = cmsController::getInstance()->getModule("data");
			$dataModule->saveEditedObject($object->getId(), false, true, true);

			$object->commit();
			
			$event->setMode("after");
			$event->call();
			
			return $object;
		}


		public function saveAddedObject(umiObject $object) {
			$event = new umiEventPoint("systemCreateObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();
			
			$dataModule = cmsController::getInstance()->getModule("data");
			$dataModule->saveEditedObject($object->getId(), true, true, true);

			if(!is_null($name = getRequest('name'))) {
				$object->setValue('nazvanie', $object->getName());
			}


			$object->commit();
			
			$event->setMode("after");
			$event->call();
			
			return $object->getId();
		}
		
		
		public function saveAddedObjectData($inputData) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$typesCollection = umiObjectTypesCollection::getInstance();
			
			if($this->checkAllowedElementType($inputData, true) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			
			$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'));

			if(is_null($name = getArrayKey($inputData, 'name'))) {
				$name = getRequest('name');
			}
			
			if(is_null($name)) {
				throw new publicAdminException("Require 'name' param in _REQUEST array.");
			}
			
			$module = get_class($this);
			$method = getArrayKey($inputData, 'type');
			$typeId = getArrayKey($inputData, 'type-id');
			
			if(!$typeId) {
				$typeId = $typesCollection->getBaseType($module, $method);
			}

			$objectId = $objectsCollection->addObject($name, $typeId);
			$object = $objectsCollection->getObject($objectId);
			if($object instanceof umiObject) {
				$this->saveAddedObject($object);
				return $object;
			} else {
				throw new coreException("Can't create object #{$objectId} \"{$name}\" of type #{$typeId}");
			}
		}


		public function saveEditedList($type, $params = false) {
			$data = getRequest("data");
			$dels = getRequest("dels");

			switch($type) {
				case "objects": {
					return $this->saveEditedObjectsList($data, $dels, $params);
				}

				case "basetypes": {
					return $this->saveEditedBaseTypesList($data, $dels);
				}

				case "domains": {
					return $this->saveEditedDomains($data, $dels);
				}
				
				case "domain_mirrows": {
					return $this->saveEditedDomainMirrows($data, $dels);
				}
				
				case "langs": {
					return $this->saveEditedLangs($data, $dels);
				}
				
				case "templates": {
					return $this->saveEditedTemplatesList($data, $dels, $params);
				}

				default: {
					throw new coreException("Can't save edited list of type \"{$type}\"");
				}
			}
		}


		public function saveEditedObjectsList($data, $dels, $params) {
			$collection = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$new_item_id = false;

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$name = getArrayKey($info, 'name');
					$type_id = getArrayKey($params, 'type_id');
					$method = getArrayKey($params, 'type');
					if(!$type_id && $method) {
						$type_id = $objectTypes->getBaseType(get_class($this), $method);
					}

					if($id == "new") {
						if($name && $type_id) {
							$id = $collection->addObject($name, $type_id);
							$item = $collection->getObject($id);
							if($item instanceof umiObject) {
								$new_item_id = $this->saveAddedObject($item);
								$item->commit();
							}
						}
					} else {
						$item = $collection->getObject($id);

						if($item instanceof umiObject) {
							$item->setName($name);
							$this->saveEditedObjectData($item);
							$item->commit();
						} else {
							throw new coreException("Object #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delObject($id);
				}
			}
			
			return $new_item_id;
		}


		public function saveEditedBaseTypesList($data, $dels) {
			$collection = umiHierarchyTypesCollection::getInstance();

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$title = getArrayKey($info, 'title');
					$module = getArrayKey($info, 'module');
					$method = getArrayKey($info, 'method');

					if($id == "new") {
						if($module && $title) {
							$collection->addType($module, $title, $method);
						}
					} else {
						$item = $collection->getType($id);

						if($item instanceof iUmiHierarchyType) {
							$item->setTitle($title);
							$item->setName($module);
							$item->setExt($method);
							$item->commit();
						} else {
							throw new coreException("Hierarchy type #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delType($id);
				}
			}
		}


		public function saveEditedTemplatesList($data, $dels, $params) {
			$collection = templatesCollection::getInstance();
			$default = getArrayKey($data, 'default');
			
			foreach($params as $host => $templates) {
				$domain_id = domainsCollection::getInstance()->getDomainId($host);
				$host_data = getArrayKey($data, $host);
				
				$default_tpl_id = getArrayKey($default, $domain_id);

				foreach($templates as $template) {
					$template_data = getArrayKey($host_data, $template->getId());

					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');
					
					if(!$title || !$filename) {
						continue;
					}
					
					$template->setTitle($title);
					$template->setFileName($filename);
					
					if(is_numeric($default_tpl_id)) {
						if($template->getId() == $default_tpl_id) {
							$template->setIsDefault(true);
						} else {
							$template->setIsDefault(false);
						}
					}
					
					$template->commit();
				}
				
				if(!is_null($template_data = getArrayKey($host_data, 'new'))) {
					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');
					
					if($title && $filename) {
						$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
						$is_default = ($default_tpl_id == "new") ? true : false;
						$collection->addTemplate($filename, $title, $domain_id, $lang_id, $is_default);
					}
				}
			}
			
			if(is_array($dels)) {
				foreach($dels as $id) {
					$template = $collection->getTemplate($id);
					if($template->getIsDefault() == false) {
						unset($template);
						$collection->delTemplate($id);
					}
				}
			}
		}
		
		
		public function saveEditedTemplateData(iTemplate $template) {
			$title = getRequest('name');
			$filename = getRequest('filename');
			$used_pages = getRequest('used_pages');
			
			$template->setTitle($title);
			$template->setFilename($filename);
			$template->setUsedPages($used_pages);
			$template->commit();
		}


		public function saveEditedDomains($data, $dels) {
			$collection = domainsCollection::getInstance();

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$host = getArrayKey($info, 'host');
					$lang_id = getArrayKey($info, 'lang_id');

					if($id == "new") {
						$host = domain::filterHostName($host);
						if($host && $lang_id) {
							if(defined("CURRENT_VERSION_LINE") &&
							in_array(CURRENT_VERSION_LINE, array('free', 'lite', 'freelance'))) {
								throw new publicAdminException(getLabel('error-disabled-in-demo'));
							}
							
							if($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}
							
							$collection->addDomain($host, $lang_id);
						}
					} else {
						if(!$host) {
							$item = $collection->getDomain($id);
							$item->setDefaultLangId($lang_id);
							$item->commit();
							
							continue;
						}
						
						$item = $collection->getDomain($id);

						if($item instanceof iDomain) {
							if($item->getIsDefault() == false) {
								$item->setHost($host);
							}
							$item->setDefaultLangId($lang_id);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delDomain($id);
				}
			}
		}


		public function saveEditedDomainMirrows($data, $dels) {
			$collection = domainsCollection::getInstance();
			$domain = $collection->getDomain(getRequest('param0'));

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$host = getArrayKey($info, 'host');

					if($id == "new") {
						$host = domain::filterHostName($host);
						if($host) {
							if($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}
							$domain->addMirrow($host);
						}
					} else {
						if(!$host) {
							continue;
						}
						
						$item = $domain->getMirrow($id);

						if($item instanceof iDomainMirrow) {
							$item->setHost($host);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$domain->delMirrow($id);
				}
			}
			
			$domain->setIsUpdated();
			$domain->commit();
		}
		
		
		public function saveEditedLangs($data, $dels) {
			$collection = langsCollection::getInstance();
			
			if(is_array($data)) {
				foreach($data as $id => $info) {
					$title  = getArrayKey($info, 'title');
					$prefix = getArrayKey($info, 'prefix');
					
					if(!strlen($title) || !strlen($prefix)) continue;

					$title  = trim($title);
					$prefix = preg_replace("/[^A-z0-9]*/", "", $prefix);

					if(!strlen($title) || !strlen($prefix)) continue;
					
					if($id == "new") {
						$id = $collection->addLang($prefix, $title);
					}
					
					$item = $collection->getLang($id);
					
					if($item instanceof iLang) {
						$item->setTitle($title);
						$item->setPrefix($prefix);
						$item->commit();
					} else {
						throw new coreException("Lang #{$id} doesn't exists");
					}
				}
			}
		}
		
		
		public function saveEditedTypeData($data) {
			$info = getRequest('data');
		
			$name = getArrayKey($info, 'name');
			$is_guidable = getArrayKey($info, 'is_guidable');
			$is_public = getArrayKey($info, 'is_public');
			$hierarchy_type_id = getArrayKey($info, 'hierarchy_type_id');
			
			$type = $data;
			if($type instanceof umiObjectType) {
				$type->setName($name);
				$type->setIsGuidable($is_guidable);
				$type->setIsPublic($is_public);
				$type->setHierarchyTypeId($hierarchy_type_id);
				$type->commit();
			} else {
				throw new coreException("Expected instance of type umiObjectType");
			}
			
		}
		
		
		public function saveEditedGroupData($group) {
			$info = getRequest('data');

			$title = getArrayKey($info, 'title');			
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			
			if($group instanceof iUmiFieldsGroup) {
				$group->setName($name);
				$group->setTitle($title);
				$group->setIsVisible($is_visible);
				$group->setIsActive(true);
				$group->commit();
			} else {
				throw new coreException("Expected instance of type umiFieldsGroup");
			}
		}
		
		
		public function saveEditedFieldData($field) {
			$info = getRequest('data');
			
			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			$field_type_id = getArrayKey($info, 'field_type_id');
			$guide_id = getArrayKey($info, 'guide_id');
			$in_search = getArrayKey($info, 'in_search');
			$in_filter = getArrayKey($info, 'in_filter');
			$tip = getArrayKey($info, 'tip');
			$isRequired = getArrayKey($info, 'is_required');
			$restrictionId = getArrayKey($info, 'restriction_id');

			if($field instanceof umiField) {
				$field->setTitle($title);
				$field->setName($name);
				$field->setIsVisible($is_visible);
				$field->setFieldTypeId($field_type_id);
				$field->setIsInSearch($in_search);
				$field->setIsInFilter($in_filter);
				$field->setTip($tip);
				$field->setIsRequired($isRequired);
				$field->setRestrictionId($restrictionId);
				
				//Choose or create public guide for unlinked relation field
				$field_type_obj = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id);
				$field_data_type = $field_type_obj->getDataType();
			
				if($field_data_type == "relation" && $guide_id == 0) {
					$guide_id = self::getAutoGuideId($title);
				}
				
				if($field_data_type == "optioned" && $guide_id == 0) {
					$guide_id = self::getAutoGuideId($title, 757);
				}
				
				$field->setGuideId($guide_id);

				$field->commit();
			} else {
				throw new coreException("Expected instance of type umiField");
			}
		}
		
		
		public function saveAddedGroupData($inputData) {
			$info = getRequest('data');
			
			$name = getArrayKey($info, 'name');
			$title = getArrayKey($info, 'title');
			$is_visible = getArrayKey($info, 'is_visible');
			
			$type_id = getArrayKey($inputData, 'type-id');
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);
			
			if($type instanceof umiObjectType) {
				$fields_group_id = $type->addFieldsGroup($name, $title, true, $is_visible);
				$type->commit();
				return $fields_group_id;
			} else {
				throw new coreException("Expected instance of type umiObjectType");
			}
		}
		
		
		public function saveAddedFieldData($inputData) {
			$group_id = $inputData['group-id'];
			$type_id = $inputData['type-id'];
		
			$info = getRequest('data');
			
			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			$field_type_id = getArrayKey($info, 'field_type_id');
			$guide_id = getArrayKey($info, 'guide_id');
			$in_search = getArrayKey($info, 'in_search');
			$in_filter = getArrayKey($info, 'in_filter');
			$tip = getArrayKey($info, 'tip');
			$isRequired = getArrayKey($info, 'is_required');
			$restrictionId = getArrayKey($info, 'restriction_id');
			
			$objectTypes = umiObjectTypesCollection::getInstance();
			$fields = umiFieldsCollection::getInstance();
			$fieldTypes = umiFieldTypesCollection::getInstance();
			
			//Check for non-unique field name
			$type = $objectTypes->getType($type_id);
			if($type instanceof umiObjectType) {
				if($type->getFieldId($name)) {
					throw new publicAdminException(getLabel('error-non-unique-field-name'));
				}
			}
			
			$field_type_obj = $fieldTypes->getFieldType($field_type_id);
			$field_data_type = $field_type_obj->getDataType();
			
			if($field_data_type == "relation" && $guide_id == 0) {
				$guide_id = self::getAutoGuideId($title);
			}
			
			if($field_data_type == "optioned" && $guide_id == 0) {
				$guide_id = self::getAutoGuideId($title, 757);
			}

			//Ищем аналог в горизонтальной иерархии
			$field_id = false;
			$parentTypeId = $type->getParentId();
			$horisontalTypes = $objectTypes->getSubTypesList($parentTypeId);
			foreach($horisontalTypes as $horisontalTypeId) {
				if($horisontalTypeId == $type_id) continue;
				$horisontalType = $objectTypes->getType($horisontalTypeId);
				if($horisontalType instanceof umiObjectType == false) continue;
				
				if($horisontalFieldId = $horisontalType->getFieldId($name)) {
					$horisontalField = $fields->getField($horisontalFieldId);
					if($horisontalField instanceof umiField == false) continue;
					if(($horisontalField->getFieldTypeId() == $field_type_id) && ($horisontalField->getTitle() == $title)) {
						$field_id = $horisontalFieldId;
						break;
					}
				}
			}
			
			//Ищем аналоги в вертикальной иерархии
			$verticalTypes = $objectTypes->getSubTypesList($type_id);
			foreach($verticalTypes as $verticalTypeId) {
				$verticalType = $objectTypes->getType($verticalTypeId);
				if($verticalType instanceof umiObjectType == false) continue;
				
				if($verticalFieldId = $verticalType->getFieldId($name)) {
					$verticalField = $fields->getField($verticalFieldId);
					if($verticalField instanceof umiField == false) continue;
					if(($verticalField->getFieldTypeId() == $field_type_id) && ($verticalField->getTitle() == $title)) {
						$field_id = $verticalFieldId;
						break;
					}
				}
			}

			if($field_id === false) {
				//Содаем новое поле, если не нашли в вертикальной и горизонтальной иерархии аналогов
				$field_id = $fields->addField($name, $title, $field_type_id, $is_visible, false, false);
				
				$field = $fields->getField($field_id);
				$field->setGuideId($guide_id);
				$field->setIsInSearch($in_search);
				$field->setIsInFilter($in_filter);
				$field->setTip($tip);
				$field->setIsRequired($isRequired);
				$field->setRestrictionId($restrictionId);
				$field->commit();
			}
			
			if($type instanceof umiObjectType) {
				$group = $type->getFieldsGroup($group_id);
				if($group instanceof umiFieldsGroup) {
					$group->attachField($field_id);
					$group_name = $group->getName();
					
					$childs = $objectTypes->getChildClasses($type_id);
					$sz = sizeof($childs);
					
					for($i = 0; $i < $sz; $i++) {
						$child_type_id = $childs[$i];
						$child_type = $objectTypes->getType($child_type_id);
						
						if($child_type instanceof umiObjectType) {
							$child_group = $child_type->getFieldsGroupByName($group_name);
							if($child_group instanceof umiFieldsGroup) {
								$child_group->attachField($field_id, true);
							} else {
								throw new coreException("Can't find umiFieldsGroup #{$group_name} in umiObjectType #{$child_type_id}");
							}
						} else {
							throw new coreException("Can't find umiObjectType #{$child_type_id}");
						}
					}
					return $field_id;
				} else {
					throw new coreException("Can't find umiFieldsGroup #{$group_id}");
				}
			} else {
				throw new coreException("Can't find umiObjectType #{$type_id}");
			}
		}
		
		
		public function chooseRedirect($redirect_string = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$referer_uri = $cmsController->getCalculatedRefererUri();

			$save_mode_str = getRequest('save-mode');

			switch($save_mode_str) {
				case getLabel('label-save-exit'):
				case getLabel('label-save-add-exit'): {
					$save_mode = 1;
					break;
				}
				
				case getLabel('label-save-view'):
				case getLabel('label-save-add-view'): {
					$save_mode = 2;
					break;
				}
				
				case getLabel('label-save'):
				case getLabel('label-save-add'): {
					$save_mode = 3;
					break;
				}

				default: {
					$save_mode = false;
				}
			}

			if($forceRedirectUrl = getArrayKey($_GET, 'force-redirect')) {
				$this->redirect($forceRedirectUrl);
			}

			if($save_mode == 1) {
				$this->redirect($referer_uri);
			}
			
			if($save_mode == 2) {
				$element_id = $this->currentEditedElementId;
				if($element_id) {
					$element_path = $hierarchy->getPathById($element_id);
					$this->redirect($element_path);
				}
			}

			if($redirect_string !== false) {
				$this->redirect($redirect_string);
			}

			if($save_mode && $element_id = $this->currentEditedElementId) {
				$element = $hierarchy->getElement($element_id);
				
				if($element instanceof umiHierarchyElement) {
					$element_module = $element->getHierarchyType()->getName();
					$element_method = $element->getHierarchyType()->getExt();
					$module = cmsController::getInstance()->getModule($element_module);
					
					if($module instanceof def_module) {
						$links = $module->getEditLink($element_id, $element_method);
						$edit_link = isset($links[1]) ? $links[1] : false;
						
						if($edit_link) {
							$this->redirect($edit_link);
						}
					}
				}
			}
			$request_uri = $this->removeErrorParam(getServer('HTTP_REFERER'));
			outputBuffer::current()->redirect($request_uri);
		}
		
		
		public function checkAllowedElementType($inputData, $ignoreIfNull = true) {
			$element = getArrayKey($inputData, 'element');
			$object = getArrayKey($inputData, 'object');
			$type = getArrayKey($inputData, 'type');
			$allowed_types = getArrayKey($inputData, 'allowed-element-types');
			
			$commentsHierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName("comments", "comment");
			if($commentsHierarchyType) {
				$commentsHierarchyTypeId = $commentsHierarchyType->getId();
			} else {
				$commentsHierarchyTypeId = false;
			}
			
			if(is_array($allowed_types) === false) {
				if($ignoreIfNull === false) {
					throw new coreException("Allowed types expected to be array");
				} else {
					return true;
				}
			}
			

			if($type) {
				if(in_array($type, $allowed_types)) {
					return true;
				} else {
					return false;
				}
			}

			if($element instanceof umiHierarchyElement === true) {
				$hierarchy_type_id = $element->getTypeId();
			} else if($object instanceof umiObject === true) {
				$object_type_id = $object->getTypeId();
				$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
			} else {
				throw new coreException("If you are doing 'add' method, you should pass me 'type' key in 'inputData' array. If you have 'edit' method, pass me 'element' key in 'inputData' array.");
			}
			
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);
			
			if($hierarchy_type instanceof iUmiHierarchyType) {
				$method = $hierarchy_type->getExt();
				if(in_array($method, $allowed_types) || $hierarchy_type->getId() == $commentsHierarchyTypeId) {
					return true;
				} else {
					return false;
				}
			} else {
				throw new coreException("This should never happen");
			}
		}
		
		
		public function checkElementPermissions($element_id, $requiredPermission = permissionsCollection::E_EDIT_ALLOWED) {
			static $permissions = NULL, $user_id = NULL;
			if(is_null($permissions)) {
				$permissions = permissionsCollection::getInstance();
				$user_id = $permissions->getUserId();
			}
			
			$allow = $permissions->isAllowedObject($user_id, $element_id);

			if(!isset($allow[$requiredPermission]) || $allow[$requiredPermission] == false) {
				throw new requreMoreAdminPermissionsException(getLabel("error-require-more-permissions"));
			} else {
				return true;
			}
		}
		
		
		public function switchActivity($params) {
			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$element = getArrayKey($params, 'element');
			$activity = getArrayKey($params, 'activity');
			
			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}
			
			$this->checkElementPermissions($element->getId());
			
			if(is_null($activity)) {
				$activity = !$element->getIsActive();
			}
			
			$module_name = $element->getModule();
			$method_name = $element->getMethod();
			
			$permissions = permissionsCollection::getInstance();
			$user_id = $permissions->getUserId();
			if($permissions->isAllowedMethod($user_id, $module_name, "publish") == false) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-publication-permissions'));
			}
			
			if($activity == $element->getIsActive()) {	//Don't raise event, if no modifications planned
				return $activity;
			}
			
			$event = new umiEventPoint("systemSwitchElementActivity");
			$event->addRef("element", $element);
			$event->setParam("activity", $activity);
			$event->setMode("before");
			
			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}
			
			$element->setIsActive($activity);
			$element->commit();
			
			$event->setMode("after");
			
			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}

			return $element->getIsActive();
		}
		
		
		public function moveElement($params) {
			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}
			$hierarchy = umiHierarchy::getInstance();
			$domains = domainsCollection::getInstance();
			
			$element = getArrayKey($params, 'element');
			$parentId = getArrayKey($params, 'parent-id');
			$domainHost = getArrayKey($params, 'domain');
			$asSibling = getArrayKey($params, 'as-sibling');
			$beforeId = getArrayKey($params, 'before-id');

			$this->checkElementPermissions($element->getId(), permissionsCollection::E_MOVE_ALLOWED);

			$event = new umiEventPoint("systemMoveElement");
			$event->addRef("element", $element);
			$event->setParam("parent-id", $parentId);
			$event->setParam("domain-host", $domainHost);
			$event->setParam("as-sibling", $asSibling);
			$event->setParam("before-id", $beforeId);
			$event->setMode("before");
			
			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return false;
			}
			
			$domainId = $domains->getDomainId($domainHost);
			$oldParentId = $element->getParentId();
			
			if ($domainId) {
				$element->setDomainId($domainId);
			}
			$element->commit();

			if ($asSibling) {
				$hierarchy->moveBefore($element->getId(), $parentId, (($beforeId) ? $beforeId : false));
			} else {
				$hierarchy->moveFirst($element->getId(), $parentId);
			}
			$element->update();
			
			$event->setMode("after");
			try {
				$event->call();
				return true;
			} catch (coreBreakEventsException $e) {
				return false;
			}
		}
		

		public function deleteElement($params) {
			$element = getArrayKey($params, 'element');

			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}
			
			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}
		
			$this->checkElementPermissions($element->getId(), permissionsCollection::E_DELETE_ALLOWED);
			
			$event = new umiEventPoint("systemDeleteElement");
			$event->addRef("element", $element);
			$event->setMode("before");
			$event->call();

			umiHierarchy::getInstance()->delElement($element->getId());
			
			$event->setMode("after");
			$event->call();
		}
		
		
		public function deleteObject($params) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
		
			$object = getArrayKey($params, 'object');
			if($object instanceof umiObject == false) {
				throw new coreException("You should pass \"object\" key containing umiObject instance.");
			}
			$object_id = $object->getId();

			$object_type_id = $object->getTypeId();
			$object_type = $objectTypesCollection->getType($object_type_id);
			
			if($object_type instanceof umiObjectType == false) {
				throw new coreException("Object #{$object_id} hasn't type #{$object_type_id}. This should not happen.");
			}

			if(!is_null(getArrayKey($params, 'type'))) {
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
				$hierarchy_type = $hierarchyTypesCollection->getType($hierarchy_type_id);
				if($hierarchy_type instanceof iUmiHierarchyType == false) {
					throw new coreException("Object type #{$object_type_id} doesn't have hierarchy type #{$hierarchy_type_id}. This should not happen.");
				}
				$params['type'] = $hierarchy_type->getExt();
			
				if($this->checkAllowedElementType($params) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}
			}

			$event = new umiEventPoint("systemDeleteObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();

			$result = $objectsCollection->delObject($object_id);
			
			$event->setMode("after");
			$event->call();
			
			return $result;
		}
		
		
		public function getFloatedDomain() {
			if(!is_null($domain_floated = getRequest('domain'))) {
				$domain_floated_id = domainsCollection::getInstance()->getDomainId($domain_floated);
				if($domain_floated_id) {
					return $domain_floated_id;
				}
			}
			return false;
		}
		
		
		public static function getAutoGuideId($title, $baseGuideId = 7) {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$guide_name = getLabel('autoguide-for-field') . " \"{$title}\"";

			$child_types = $objectTypesCollection->getChildClasses($baseGuideId);
			foreach($child_types as $child_type_id) {
				$child_type = $objectTypesCollection->getType($child_type_id);
				$child_type_name = $child_type->getName();
			
				if($child_type_name == $guide_name) {
					$child_type->setIsGuidable(true);
					return $child_type_id;
				}
			}
			
			$guide_id = $objectTypesCollection->addType($baseGuideId, $guide_name);
			$guide = $objectTypesCollection->getType($guide_id);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();
			
			return $guide_id;
		}
		
		
		public function checkDomainPermissions($domain_id = false) {
			$permissions = permissionsCollection::getInstance();
			$domains = domainsCollection::getInstance();
			$cmsController = cmsController::getInstance();

			if($domain_id == false) {
				if(!is_null($domain_host = getRequest('domain'))) {
					$domain_id = $domains->getDomainId($domain_host);
				} else {
					$domain_id = $cmsController->getCurrentDomain()->getId();
				}
			}
			
			if(!$domain_id) {
				throw new coreException("Require domain id to check domain permissions");
			}
			
			$user_id = $permissions->getUserId();
			$is_allowed = $permissions->isAllowedDomain($user_id, $domain_id);
			
			if($is_allowed == 0) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-domain-permissions'));
			} else {
				return NULL;
			}
		}
		
		
		public function setRequestDataAlias($key1, $key2) {
			if(isset($_REQUEST[$key2])) {
				$_REQUEST[$key1] = &$_REQUEST[$key2];
				return true;
			} else {
				return false;
			}
		}
		
		
		public function setRequestDataAliases($aliases, $id = "new") {
			if(!is_array($aliases)) {
				return false;
			}
			
			foreach($aliases as $key1 => $key2) {
				if(isset($_REQUEST['data'][$id][$key2])) {
					$_REQUEST[$key1] = &$_REQUEST['data'][$id][$key2];
				}
				
			}
		}
		
		public function compareObjectTypeByHierarchy($module, $method, $type_id) {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$type = $typesCollection->getType($type_id);
			$hierarchy_type = $hierarchyTypesCollection->getTypeByName($module, $method);

			if($type instanceof umiObjectType && $hierarchy_type instanceof umiHierarchyType) {
				return $type->getHierarchyTypeId() == $hierarchy_type->getId();
			} else {
				return false;
			}
		}
		public function systemIsLocked($element, $user_id){
			if ($element){
				$oPage = $element->getObject();
				$lockTime = $oPage->getValue("locktime");
				$lockUser = $oPage->getValue("lockuser");
				if ($lockTime == null || $lockUser == null){
					return false;		
				}	
				$lockDuration = regedit::getInstance()->getVal("//settings/lock_duration");
				if (($lockTime->timestamp + $lockDuration) > time() && $lockUser!=$user_id){
					return true;
				}else{
					$oPage->setValue("lockuser", null);
					$oPage->setValue("locktime", null);
					$oPage->commit();
					$element->commit();
					return false;
				}
			}		
		}
		
		public function autoDetectAllFilters($a = null, $b = null, $c = null) {
			//Deprecated
		}
		
		public function getPageStatusIdByStatusSid($statusId = 'page_status_publish') {
			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$objectTypeId = $this->getGuideIdByFieldName('publish_status');
			if(!$objectTypeId) {
				return false;
			}
			$sel->addObjectType($objectTypeId);
		
			$result = umiSelectionsParser::runSelection($sel);
			foreach ($result as $objectId) {
				$statusStringId = umiObjectsCollection::getInstance()->getObject($objectId)->getValue("publish_status_id");
				if ($statusStringId == $statusId) {
					return $objectId;
				}	
			}
			return false;	
		}
		
		public function getGuideIdByFieldName($fieldName) {
			$fields = new umiObjectType(3);
			foreach ($fields->getAllFields() as $field) {
				if ($field->getName() == $fieldName) {
					return $field->getGuideId();
				}
			}
			return false;
		}
		
		public function setHeaderLabel($label) {
			$cmsController = cmsController::getInstance();
			$cmsController->headerLabel = $label;
		}
		
		public function getObjectTypeMethod($object) {
			if($object instanceof umiObject == false) {
				throw new coreException("Expected instance of umiObject as param.");
			}
			
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $object->getTypeId();
			$objectType = $objectTypes->getType($objectTypeId);
			if($objectType instanceof umiObjectType) {
				$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
				$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);
				
				if($hierarchyType instanceof umiHierarchyType) {
					return $hierarchyType->getExt();
				} else {
					throw new coreException("Can't get hierarchy type #{$hierarchyTypeId}");
				}
			} else {
				throw new coreException("Can't get object type #{$objectTypeId}");
			}
		}
		
		public function getDatasetConfiguration($param = '') {			
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'content', '#__name'=>'load_tree_node'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'content', '#__name'=>'tree_delete_element'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'content', '#__name'=>'tree_set_activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
					)					
				);
		}
		
		final public function dataset_config() {
			$param = getRequest('param');
			
			$childMap = array('methods'=>'method', 'types'=>'type', 'stoplist'=>'exclude', 'default'=>'column');
			
			$datasetConfig = $this->getDatasetConfiguration($param);
			
			$document = new DOMDocument();
			$document->encoding = "utf-8";
			
			$root	  = $document->createElement('dataset');
			$document->appendChild($root);
						
			if(is_array($datasetConfig)) {
				$objectTypes = umiObjectTypesCollection::getInstance();
				
				foreach($datasetConfig as $sectionName => $sectionRecords) {
					$section = $document->createElement($sectionName);
					$root->appendChild($section);
					if(is_array($sectionRecords)) {
						foreach($sectionRecords as $record) {
							$element = $document->createElement($childMap[$sectionName]);
							if(is_array($record)) {
								foreach($record as $propertyName => $propertyValue) {
									if($propertyName === "#__name") {
										$element->appendChild( $document->createTextNode($propertyValue) );
										continue;
									}
									
									if($propertyName == "id" && !is_numeric($propertyValue)) {
										$propertyValue = $objectTypes->getBaseType(get_class($this), $propertyValue);
									}
									$element->setAttribute($propertyName, is_bool($propertyValue) ? ($propertyValue ? "true" : "false") : $propertyValue );
								}
							} else {
								$element->appendChild( $document->createTextNode($record) );
							}
							$section->appendChild($element);
						}
					} else {
						$section->appendChild( $document->createTextNode($sectionRecords) );
						
					}
				}
			}
			
			$buffer = outputBuffer::current();
			$buffer->contentType('text/xml');
			$buffer->charset('utf-8');
			$buffer->push($document->saveXML());
			$buffer->end();
		}
		
		public function change_template() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = array($elements);
			}

			$element = $this->expectElement("element");
			$templateId = getRequest('template-id');

			if (!is_null($templateId)) {
				foreach($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);

					if ($element instanceof umiHierarchyElement) {
						$element->setTplId($templateId);
						$element->commit();
					} else {
						throw new publicAdminException(getLabel('error-expect-element'));
					}
				}

				$this->setDataType("list");
				$this->setActionType("view");
				$data = $this->prepareData($elements, "pages");
				$this->setData($data);

				return $this->doData();
			} else {
				throw new publicAdminException(getLabel('error-expect-action'));
			}
		}
		
		public function switchGroupsActivity($groupName, $activity) {
			$groups = umiFieldsGroup::getAllGroupsByName($groupName);
			foreach($groups as $group) {
				if($group instanceof umiFieldsGroup) {
					$group->setIsActive($activity);
					$group->commit();
				}
			}
		}
	};


	interface iBaseSerialize {
		public static function serializeDocument($type, $xmlString, $params);
	};



	abstract class baseSerialize implements iBaseSerialize {
		static $called = Array();
	
		final public static function serializeDocument($type, $buffer, $params) {
			$serializer = self::loadSerializer($type);
			return $serializer->execute($buffer, $params);
		}
		
		
		abstract public function execute($xmlString, $params);
		
		
		protected static function loadSerializer($type) {
			$filename = SYS_KERNEL_PATH . "subsystems/matches/serializes/{$type}/{$type}.php";
			if(is_file($filename)) {
				require $filename;
				
				$serializerClassName = strtolower($type) . "Serialize";
				
				if(class_exists($serializerClassName)) {
					return new $serializerClassName();
				} else {
					throw new coreException("Class {$serializerClassName} doesn't exsits");
				}
			} else {
				throw new coreException("Can't load serializer of type \"{$type}\"");
			}
		}
		
		
		protected function sendHTTPHeaders($params) {
			if(is_array($params)) {
				$buffer = outputBuffer::current();
				$headers = getArrayKey($params, 'headers');
				
				if(is_array($headers)) {
					foreach($headers as $i => $v) {
						if(strtolower($i) == 'content-type') {
							$buffer->contentType($v);
							continue;
						}
						$buffer->header($i, $v);
					}
				}
			} else {
				throw new coreException("First argument must be ad array in sendHTTPHeaders()");
			}
		}
	};



	interface iUmiLogger {

		
		public function __construct($logDir = "./logs/");

		public function pushGlobalEnviroment();

		public function push($mess, $enableTimer = true);

		public function log();

		public function save();

		public function get();
	};



	class umiLogger implements iUmiLogger {
		
		protected	$logDir = "./logs/",
					$log = "",
					$is_saved = false,
					$is_global_env_pushed = false,
					$start_time = false;
		
		public function __construct($logDir = "./logs/") {
			$this->runTimer();

			$this->logDir = $logDir;
			$this->checkDirectory();
		}
		
		public function pushGlobalEnviroment() {
			if($this->is_global_env_pushed == false) {
				$this->collectGlobalEnviroment();
				$this->is_global_env_pushed = true;
				
				return true;
			} else {
				return false;
			}
		}
		
		
		public function push($mess, $enableTimer = true) {
			if($enableTimer == true) {
				$mess = "[" . sprintf("%1.7f", $this->getTimer()) . "]\t" . $mess;
			}
			$this->log .= $mess . "\n";
		}
		
		
		public function log() {
			$this->pushGlobalEnviroment();
		}
		
		
		public function __destruct() {
			if($this->is_saved == false) {
				$this->save();
			}
		}

		
		public function save() {
			$store_dirpath = $this->prepareStoreDir();
			
			$filename = date("Y-m-d_H_i_s");
			
			$filepath = $store_dirpath . "/" . $filename . ".log";
			if(file_put_contents($filepath, $this->get())) {
				return $filepath;
			} else {
				throw new Exception("Can't save log in \"{$filepath}\"");
			}
			
			$this->is_saved = true;
		}
		
		
		public function get() {
			return $this->log;
		}
		
		
		protected function checkDirectory() {
			$dirpath = $this->logDir;
			
			if(is_dir($dirpath)) {
				if(is_writable($dirpath)) {
					return true;
				} else {
					throw new Exception("Directory \"{$dirpath}\" must be writable");
				}
			} else {
				throw new Exception("Directory \"{$dirpath}\" doesn't exists");
			}
		}
		
		protected function prepareStoreDir() {
			$dirpath = $this->logDir;
			$remote_addr = $_SERVER['REMOTE_ADDR'];
			$storepath = $dirpath . $remote_addr;
			
			if(file_exists($storepath)) {
				if(is_writable($storepath)) {
					return $storepath;
				} else {
					throw new Exception("Directory \"{$storepath}\" must be writable");
				}
			}
			
			if(mkdir($storepath)) {
				return $storepath . '/';
			} else {
				throw new Exception("Can't create directory \"{$storepath}\"");
			}
		}
		
		protected function collectGlobalEnviroment() {
			$this->collectGlobalArray('_COOKIE');
			$this->collectGlobalArray('_SESSION');
			$this->collectGlobalArray('_POST');
			$this->collectGlobalArray('_GET');
			$this->collectGlobalArray('_FILES');
			
			if(function_exists('apache_request_headers')) {
				$this->collectArray("Request headers", apache_request_headers());
			}
			
			if(function_exists('apache_response_headers')) {
				$this->collectArray("Response headers", apache_response_headers());
			}
		}
		
		
		protected function collectGlobalArray($varname) {
			global $$varname;
			
			if(isset($$varname)) {
				$this->collectArray($varname, $$varname);
			}
		}
		
		
		protected function collectArray($name, $arr) {
			if(!is_array($arr)) {
				return false;
			}
			
			if(sizeof($arr) == 0) {
				return true;
			}

			$msg = "[{$name}]\n";
			foreach($arr as $i => $v) {
				$msg .= "\t[" . $i . "]\n\t" . "(" . gettype($v) . ") ";
				
				if(is_array($v)) {
					$v = $this->serializeArray($v);
				}
				$msg .= $v . "\n\n";
			}
			$this->push($msg, false);
			return true;
		}
		
		protected function serializeArray($arr) {
			$res = "[";
			
			$sz = sizeof($arr);
			$c = 0;
			foreach($arr as $i => $v) {
				if(is_array($v)) {
					$v = $this->serializeArray($v);
				}
				
				$res .= "'" . $v . "'";
				if(++$c < $sz) {
					$res .= ", ";
				}
			}
			
			$res .= "]";
			return $res;
		}
		
		protected function runTimer() {
			$this->start_time = microtime(true);
		}
		
		protected function getTimer() {
			$time = microtime(true) - $this->start_time;
			return round($time, 7);
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Кнопка-флажок" (булевый тип)
*/
	class umiObjectPropertyBoolean extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['int_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = (int) $val;
				}
				return $res;
			}

			$sql = "SELECT  int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = (int) $val;
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if(!$val) continue;
				$val = (int)$this->boolval($val,true);
				
				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
		protected function boolval($in, $strict=false) {
			$out = null;
			// if not strict, we only have to check if something is false
			if (in_array($in,array('false', 'False', 'FALSE', 'no', 'No', 'n', 'N', '0', 'off',
                           'Off', 'OFF', false, 0, null), true)) {
				$out = false;
			} else if ($strict) {
				// if strict, check the equivalent true values
				if (in_array($in,array('true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1',
                               'on', 'On', 'ON', true, 1), true)) {
					$out = true;
				}
			} else {
				// not strict? let the regular php bool check figure it out (will
				//     largely default to true)
				$out = ($in?true:false);
			}
			return $out;
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Картинка"
*/
	class umiObjectPropertyImgFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Изображение"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$val = self::unescapeFilePath($val);
					
					$img = new umiImageFile(self::filterOutputString($val));
					if($img->getIsBroken()) continue;
					$res[] = $img;
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				
				$val = self::unescapeFilePath($val);
				
				$img = new umiImageFile(self::filterOutputString($val));
				if($img->getIsBroken()) continue;
				$res[] = $img;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Изображение"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$cnt = 0;
			foreach($this->value as $val) {
				if(!$val) continue;
				
				if(is_object($val)) {
					if(!@is_file($val->getFilePath())) {
						continue;
					}
					$val = mysql_real_escape_string($val->getFilePath());
				} else {
					$val = mysql_real_escape_string($val);
				}
				
				$val = self::unescapeFilePath($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Выпадающий список", т.е. свойства с использованием справочников.
*/
	class umiObjectPropertyRelation extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на объект"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['rel_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = $val;
				}
				return $res;
			}

			if($this->getIsMultiple()) {
				$sql = "SELECT  rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			} else {
				$sql = "SELECT  rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			}

			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = $val;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на объект"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$tmp = Array();
			foreach($this->value as $val) {
				if(!$val) continue;
				
				if(is_string($val) && strpos($val, "|") !== false) {
					$tmp1 = split("\|", $val);
					foreach($tmp1 as $v) {
						$v = trim($v);
						if($v) $tmp[] = $v;
						unset($v);
					}
					unset($tmp1);
					$this->getField()->setFieldTypeId(7);	//Check, if we can use it without fieldTypeId

				} else {
					$tmp[] = $val;
				}
			}
			$this->value = $tmp;
			unset($tmp);

			$cnt = 0;

			foreach($this->value as $key => $val) {
				if($val) {
					$val = $this->prepareRelationValue($val);
					$this->values[$key] = $val;
				}
				if(!$val) continue;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, rel_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Теги".
*/
	class umiObjectPropertyTags extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Тэги"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['varchar_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  varchar_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Тэги"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(sizeof($this->value) == 1) {
				$value =  split(",", preg_replace("/[^\A-z0-9А-я, ]/u", "", trim($this->value[0], ",")));
			} else {
				$value = array_map( create_function('$a', " return preg_replace(\"/[^\\A-z0-9А-я,][ ]?/u\", \"\", \$a); ") , $this->value);
			}

			$cnt = 0;
			foreach($value as $val) {
				$val = trim($val);
				if(strlen($val) == 0) continue;

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, varchar_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Дата"
*/
	class umiObjectPropertyDate extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Дата"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['int_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = new umiDate((int) $val);
				}
				return $res;
			}

			$sql = "SELECT  int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = new umiDate((int) $val);
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Дата"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			
			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") {
					continue;
				} else {
					$val = (is_object($val)) ? (int) $val->timestamp : (int) $val;
					if($val == false) {
						continue;
					}
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Число"
*/
	class umiObjectPropertyInt extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['int_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = (int) $val;
				}
				return $res;
			}

			$sql = "SELECT  int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = (int) $val;
			}

			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			
			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;
				$val = (int) $val;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Строка".
*/
	class umiObjectPropertyString extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Строка"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['varchar_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  varchar_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Строка"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if(strlen($val) == 0) continue;

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, varchar_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	}


/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Текст".
*/
	class umiObjectPropertyText extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Текст"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Текст"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if($val == "<p />" || $val == "&nbsp;") $val = "";

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
		
		public function __wakeup() {
			foreach($this->value as $i => $v) {
				if(is_string($v)) {
					$this->value[$i] = str_replace("&#037;", "%", $v);
				}
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Файл"
*/
	class umiObjectPropertyFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Файл"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;
			
			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					$val = self::unescapeFilePath($val);
					
					$file = new umiFile(self::filterOutputString($val));
					if($file->getIsBroken()) continue;
					$res[] = $file;
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$file = new umiFile($val);
				if($file->getIsBroken()) continue;
				$res[] = $file;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Файл"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$cnt = 0;
			foreach($this->value as $val) {
				if(!$val) continue;
				
				if(is_object($val)) {
					if(!@is_file($val->getFilePath())) {
						continue;
					}
					$val = mysql_real_escape_string($val->getFilePath());
				} else {
					$val = mysql_real_escape_string($val);
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Пароль"
*/
	class umiObjectPropertyPassword extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['varchar_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  varchar_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}


		/**
			* Сохраняет значение свойства в БД, если тип свойства "Пароль"
		*/
		protected function saveValue() {
			$cnt = 0;
			foreach($this->value as $val) {
				if(strlen($val) == 0) continue;

				$this->deleteCurrentRows();

				$val = self::filterInputString($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, varchar_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "WYSIWYG".
*/
	class umiObjectPropertyWYSIWYG extends umiObjectPropertyText {
		protected function saveValue() {
			foreach($this->value as $i => $value) {
				$value = str_replace(array('&lt;!--', '--&gt;'), array('<!--', '-->'),  $value);
				$value = preg_replace('/<\!\-\-\[if[^\]]*\]>(.*)<\!\[endif\]\-\->/mis', '', $value);
				$this->value[$i] = $value;
			}
			parent::saveValue();
		}

		/**
			* Загружает значение свойства из БД, если тип свойства "HTML-текст"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['text_val'] as $val) {
					if(is_null($val)) continue;
					if(str_replace("&nbsp;", "", trim($val)) == "") continue;
					$res[] = self::filterOutputString((string) $val);
				}
				return $res;
			}

			$sql = "SELECT  text_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				if(str_replace("&nbsp;", "", trim($val)) == "") continue;
				$res[] = self::filterOutputString((string) $val);
			}

			return $res;
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Число с точкой"
*/
	class umiObjectPropertyFloat extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "число с точкой"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['float_val'] as $val) {
					if(is_null($val)) continue;
					$res[] = (float) $val;
				}
				return $res;
			}

			$sql = "SELECT  float_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$res[] = (float) $val;
			}

			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число с точкой"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;

				if(strpos(".", $val) === false) $val = str_replace(",", ".", $val);
				$val = (float) $val;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, float_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) $this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта.
	* Обрабатывает тип поля "Цена". При загрузке данных вызывается событие "umiObjectProperty_loadPriceValue".
*/
	class umiObjectPropertyPrice extends umiObjectPropertyFloat {
		protected $dbValue;
		
		/**
			* Загружает значение свойства из БД, если тип свойства "Цена"
		*/
		protected function loadValue() {
			$res = parent::loadValue();
			
			if($eshop_inst = cmsController::getInstance()->getModule("eshop")) {
				if(isset($res[0])) {
					list($price) = $res;
				} else {
					$price = 0;
				}
				$price = $eshop_inst->calculateDiscount($this->object_id, $price);
				$res = Array($price);
			}
			

			if(is_array($res)) {
				if(isset($res[0])) {			
					list($price) = $res;
				} else {
					$price = 0;
				}
			} else {
				$price = 0;
			}
			
			$this->dbValue = $price;

			$oEventPoint = new umiEventPoint("umiObjectProperty_loadPriceValue");
			$oEventPoint->setParam("object_id", $this->object_id);
			$oEventPoint->addRef("price", $price);
			$oEventPoint->call();
			$res = Array($price);
			
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Цена"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;

				if(strpos(".", $val) === false) $val = str_replace(",", ".", $val);
				$val = abs((float) $val);
				if($val > 999999999.99) $val = 999999999.99;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, float_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}
			
			$this->dbValue = $this->value;
			
			if(!$cnt) $this->fillNull();
		}
		
		public function __wakeup() {
			if($this->dbValue) {
				$value = Array($this->dbValue);

				if($eshop_inst = cmsController::getInstance()->getModule("eshop")) {
					if(isset($value[0])) {
						list($price) = $value;
					} else {
						$price = 0;
					}
					$price = $eshop_inst->calculateDiscount($this->object_id, $price);
					$value = Array($price);
				}

				if(is_array($value)) {
					if(isset($value[0])) {
						list($price) = $value;
					} else {
						$price = 0;
					}
				} else {
					$price = 0;
				}


				$oEventPoint = new umiEventPoint("umiObjectProperty_loadPriceValue");
				$oEventPoint->setParam("object_id", $this->object_id);
				$oEventPoint->addRef("price", $price);
				$oEventPoint->call();
				$value = Array($price);


				$this->value = $value;
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта
	* Обрабатывает тип поля "Ссылка на дерево".
*/
	class umiObjectPropertySymlink extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на дерево"
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if($data = $this->getPropData()) {
				foreach($data['tree_val'] as $val) {
					if(is_null($val)) continue;
					$element = umiHierarchy::getInstance()->getElement( (int) $val );
					if($element === false) continue;
					if($element->getIsActive() == false) continue;
	
					$res[] = $element;
				}
				return $res;
			}

			$sql = "SELECT  tree_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			$result = l_mysql_query($sql, true);

			while(list($val) = mysql_fetch_row($result)) {
				if(is_null($val)) continue;
				$element = umiHierarchy::getInstance()->getElement( (int) $val );
				if($element === false) continue;
				if($element->getIsActive() == false) continue;

				$res[] = $element;
			}

			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на дерево"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			$hierarchy = umiHierarchy::getInstance();

			$cnt = 0;
			foreach($this->value as $i => $val) {
				if($val === false || $val === "") continue;

				if(is_object($val)) {
					$val = (int) $val->getId();
				} else {
					$val = intval($val);
				}
				
				if(!$val) continue;

				if(is_numeric($val)) {
					$val = (int) $val;
					$this->value[$i] = $hierarchy->getElement($val);
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, tree_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";

				l_mysql_query($sql);
				++$cnt;
			}
			
			if(!$cnt) {
				$this->fillNull();
			}
		}
	};


/**
	* ���� ����� ������ ��� ���������� ��������� �������.
	* ������������ ��� ���� "�������"
*/
	class umiObjectPropertyCounter extends umiObjectProperty {
		protected $oldValue;
		
		/**
			* ��������� �������� �������� �� ��
		*/
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			$sql = "SELECT cnt FROM `cms3_object_content_cnt` WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			if(list($val) = mysql_fetch_row($result)) {
				$cnt = (int) $val;
			} else {
				$cnt = 0;
			}
			$this->oldValue = $cnt;

			return Array($cnt);
		}

		/**
			* ��������� �������� �������� � ��, ���� ��� �������� "�����"
		*/
		protected function saveValue() {
			$value = sizeof($this->value) ? (int) $this->value[0] : 0;
			$lambda = $value - $this->oldValue;
			if((abs($lambda) == 1) && $value !== 0 && $this->oldValue) {
				$sql = "UPDATE `cms3_object_content_cnt` SET cnt = cnt + ({$lambda}) WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				l_mysql_query($sql);
			} else {
				$this->deleteCurrentRows();
				$sql = "INSERT INTO `cms3_object_content_cnt` (obj_id, field_id, cnt) VALUES('{$this->object_id}', '{$this->field_id}', '{$value}')";
				l_mysql_query($sql);
			}
		}
		
		protected function deleteCurrentRows() {
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			
			$sql = "DELETE FROM `cms3_object_content_cnt` WHERE `obj_id` = {$objectId} AND `field_id` = {$fieldId}";
			l_mysql_query($sql);
		}
		
		protected function fillNull() {
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			
			$sql = "SELECT COUNT(*) FROM `cms3_object_content_cnt` WHERE `obj_id` = {$objectId} AND `field_id` = {$fieldId}";
			$result = l_mysql_query($sql);
			list($count) = mysql_fetch_row($result);
			if($count == 0) {
				$sql = "INSERT INTO `cms3_object_content_cnt` (`obj_id`, `field_id`) VALUES ('{$objectId}', '{$fieldId}')";
				l_mysql_query($sql);
			}
		}
	};


/**
	* 
	* 
	* 
*/
	class umiObjectPropertyOptioned extends umiObjectProperty {
		public function setValue($value) {
			if(is_array($value)) {
				$value = array_distinct($value);
			}
			parent::setValue($value);
		}
		
		/**
			* 
		*/
		protected function loadValue() {
			$values = array();
			
			$data = $this->getPropData();
			if($data == false) {
				$data = array();
				$sql = "SELECT int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				$result = l_mysql_query($sql, true);
				while($row = mysql_fetch_assoc($result)) {
					foreach($row as $i => $v) {
						$data[$i][] = $v;
					}
				}
			}
			

			for($i = 0; true; $i++) {
				if($value = $this->parsePropData($data, $i)) {
					foreach($value as $t => $v) {
						$value[$t] = ($t == 'float') ? $this->filterFloat($v) : self::filterOutputString($v);
					}
					
					$values[] = $value;
					continue;
				} break;
			}
			
			return $values;
		}


		/**
			* 
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $key => $data) {
				$sql = "INSERT INTO `{$this->tableName}` (`obj_id`, `field_id`, `int_val`, `varchar_val`, `rel_val`, `tree_val`, `float_val`) VALUES ('{$this->object_id}', '{$this->field_id}', ";
				
				$cnt = 0;
				if($intValue = (int) getArrayKey($data, 'int')) {
					$sql .= "'{$intValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($varcharValue = (string) getArrayKey($data, 'varchar')) {
					$varcharValue = self::filterInputString($varcharValue);
					$sql .= "'{$varcharValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($relValue = (int) $this->prepareRelationValue(getArrayKey($data, 'rel'))) {
					$sql .= "'{$relValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				$this->values[$key]['rel'] = $relValue;
				
				if($treeValue = (int) getArrayKey($data, 'tree')) {
					$sql .= "'{$treeValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				
				if($floatValue = (int) getArrayKey($data, 'float')) {
					$sql .= "'{$floatValue}'";
					++$cnt;
				} else {
					$sql .= "NULL";
				}
				
				$sql .= ")";
				
				if($cnt < 2) {
					continue;
				}
				
				l_mysql_query($sql);
			}
		}
		
		
		protected function parsePropData($data, $index) {
			$result = Array();
			$hasValue = false;
			foreach($data as $contentType => $values) {
				if(isset($values[$index])) {
					$contentType = $this->decodeContentType($contentType);
					$result[$contentType] = $values[$index];
					$hasValue = true;
				}
			}
			return $hasValue ? $result : false;
		}
		
		protected function decodeContentType($contentType) {
			if(substr($contentType, -4) == '_val') {
				$contentType = substr($contentType, 0, strlen($contentType) - 4);
			}
			return $contentType;
		}
		
		protected function applyParams($values, $params = NULL) {
			$filter = getArrayKey($params, 'filter');
			$requireFieldType = getArrayKey($params, 'field-type');
			
			if(!is_null($filter)) {
				$result = Array();
				foreach($values as $index => $value) {
					foreach($filter as $fieldType => $filterValue) {
						if(isset($value[$fieldType]) && $value[$fieldType] == $filterValue) {
							$result[] = $value;
						}
					}
				}
				$values = $result;
			}
			
			if(!is_null($requireFieldType)) {
				foreach($values as $i => $value) {
					$values[$i] = getArrayKey($value, $requireFieldType);
				}
			}
			return $values;
		}
		
		protected function filterFloat($value) {
			return round($value, 2);
		}
	};


/**
	* Классы, дочерние от класса baseRestriction отвечают за валидацию полей.
	* В таблицу `cms3_object_fields` добавилось поле `restriction_id`
	* Список рестрикшенов хранится в таблице `cms3_object_fields_restrictions`:
	* +----+------------------+-------------------------------+---------------+
	* | id | class_prefix     | title                         | field_type_id |
	* +----+------------------+-------------------------------+---------------+
	* | 1  | email            | i18n::restriction-email-title | 4             |
	* +----+------------------+-------------------------------+---------------+
	*
	* При модификации значения поля, которое связано с restriction'ом, загружается этот restriction,
	* В метод validate() передается значение. Если метод вернет true, работа продолжается,
	* если false, то получаем текст ошибки и делаем errorPanic() на предыдущую страницу.
*/
	abstract class baseRestriction {
		protected	$errorMessage = 'restriction-error-common',
					$id, $title, $classPrefix, $fieldTypeId;


		/**
			* Загрузить restriction
			* @param Integer $restrictionId id рестрикшена
			* @return baseRestriction потомок класса baseRestriction
		*/
		final public static function get($restrictionId) {
			$restrictionId = (int) $restrictionId;
			
			$sql = "SELECT `class_prefix`, `title`, `field_type_id` FROM `cms3_object_fields_restrictions` WHERE `id` = '{$restrictionId}'";
			$result = l_mysql_query($sql);
			
			if(list($classPrefix, $title, $fieldTypeId) = mysql_fetch_row($result)) {
				$filePath = CURRENT_WORKING_DIR . '/classes/system/subsystems/models/data/restrictions/' . $classPrefix . '.php';
				$className = $classPrefix . 'Restriction';
				if(is_file($filePath) == false) {
					return false;
				}
				
				if(!class_exists($className)) {
					require $filePath;
				}
				
				if(class_exists($className)) {
					$restriction = new $className($restrictionId, $className, $title, $fieldTypeId);
					if($restriction instanceof baseRestriction) {
						return $restriction;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}


		/**
			* Получить список всех рестрикшенов
			* @return Array массив из наследников baseRestriction
		*/
		final public static function getList() {
			$sql = "SELECT `id` FROM `cms3_object_fields_restrictions`";
			$result = l_mysql_query($sql);
			
			$restrictions = array();
			while(list($id) = mysql_fetch_row($result)) {
				 $restriction= self::get($id);
				 if($restriction instanceof baseRestriction) {
				 	$restrictions[] = $restriction;
				 }
			}
			return $restrictions;
		}


		/**
			* Добавить новый restriction
			* @param String $classPrefix название класса рестрикшена
			* @param String $title название рестрикшена
			* @param Integer $fieldTypeId id типа полей, для которого допустим этот рестрикшен
			* @return Integer|Boolean id созданного рестрикшена, либо false
		*/
		final public static function add($classPrefix, $title, $fieldTypeId) {
			$classPrefix = mysql_real_escape_string($classPrefix);
			$title = mysql_real_escape_string($title);
			$fieldTypeId = (int) $fieldTypeId;
			
			$sql = <<<SQL
INSERT INTO `cms3_object_fields_restrictions`
	(`class_prefix`, `title`, `field_type_id`)
	VALUES ('{$classPrefix}', '{$title}', '{$fieldTypeId}')
SQL;
			l_mysql_query($sql);
			return mysql_insert_id();
		}


		/**
			* Провалидировать значение поля
			* @param Mixed &$value валидируемое значение поля
			* @return Boolean результат валидации
		*/
		abstract public function validate($value);

		/**
			* Получить текст сообщения об ошибке
			* @return String сообщение об ошибке
		*/
		public function getErrorMessage() {
			return getLabel($this->errorMessage);
		}


		/**
			* Получить название рестрикшена
			* @return String название рестрикшена
		*/
		public function getTitle() {
			return getLabel($this->title);
		}


		/**
			* Получить префикс класса рестрикшена
			* @return String префикс класса рестрикшена
		*/
		public function getClassName() {
			return $this->classPrefix;
		}


		/**
			* Получить id рестрикшена
			* @return Integer id рестрикшена
		*/
		public function getId() {
			return $this->id;
		}
		
		public function getFieldTypeId() {
			return $this->fieldTypeId;
		}
		
		public static function find($classPrefix, $fieldTypeId) {
			$restrictions = self::getList();
			
			foreach($restrictions as $restriction) {
				if($restriction->getClassName() == $classPrefix && $restriction->getFieldTypeId() == $fieldTypeId) {
					return $restriction;
				}
			}
		}


		/**
			* Конструктор класса
		*/
		protected function __construct($id, $classPrefix, $title, $fieldTypeId) {
			$this->id = (int) $id;
			$this->classPrefix = $classPrefix;
			$this->title = $title;
			$this->fieldTypeId = (int) $fieldTypeId;
		}
	};

	interface iNormalizeInRestriction {
		public function normalizeIn($value);
	};
	
	interface iNormalizeOutRestriction {
		public function normalizeOut($value);
	};


	interface iRedirects {
		public static function getInstance($c = NULL);
		public function add($source, $target, $status = 301);
		public function getRedirectsIdBySource($source);
		public function getRedirectIdByTarget($target);
		public function del($id);
		public function redirectIfRequired($currentUri);
		
		public function init();
	};


	class redirects implements iRedirects {
		/**
			* Получить экземпляр коллекции
			* @return iRedirects экземпляр коллекции
		*/
		public static function getInstance($c = NULL) {
			static $instance;
			if(is_null($instance)) {
				$instance = new redirects;
			}
			return $instance;
		}
		
		
		/**
			* Добавить новое перенаправление
			* @param String $source адрес страницы, с которой осуществляется перенаправление
			* @param String $target адрес целевой страницы
			* @param Integer $status = 301 статус перенаправления
		*/
		public function add($source, $target, $status = 301) {
			if($source == $target) return;
			
			$source = mysql_real_escape_string($this->parseUri($source));
			$target = mysql_real_escape_string($this->parseUri($target));
			$status = (int) $status;
			
			l_mysql_query("START TRANSACTION /* Adding new redirect records */");
			
			//Создать новые записи на тот случай, если у нас уже есть перенаправление на $target
			$sql = <<<SQL
INSERT INTO `cms3_redirects`
	(`source`, `target`, `status`)
	SELECT `source`, '{$target}', '{$status}' FROM `cms3_redirects`	
		WHERE `target` = '{$source}'
SQL;
			l_mysql_query($sql);
			
			//Удалить старые записи
			$sql = <<<SQL
DELETE FROM `cms3_redirects` WHERE `target` = '{$source}'
SQL;
			l_mysql_query($sql);
			
			$result = l_mysql_query("SELECT * FROM `cms3_redirects` WHERE `source` = '{$source}' AND `target` = '{$target}'", true);
			if(mysql_num_rows($result)) {
				return;
			}
			
			//Добавляем новую запись для перенаправления
			$sql = <<<SQL
INSERT INTO `cms3_redirects`
	(`source`, `target`, `status`)
	VALUES
	('{$source}', '{$target}', '{$status}')
SQL;
			l_mysql_query($sql);
			
			l_mysql_query("COMMIT");
		}
		
		
		/**
			* Получить список перенаправлений со страницы $source
			* @param String $source адрес страницы, с которой осуществляется перенаправление
			* @return Array массив перенаправлений
		*/
		public function getRedirectsIdBySource($source) {
			$sourceSQL = mysql_real_escape_string($this->parseUri($source));
			$redirects = array();
			
			$sql = "SELECT `id`, `target`, `status` FROM `cms3_redirects` WHERE `source` = '{$sourceSQL}'";
			$result = l_mysql_query($sql);
			while(list($id, $target, $status) = mysql_fetch_row($result)) {
				$redirects[$id] = Array($source, $target, (int) $status);
			}
			return $redirects;
		}
		
		
		/**
			* Получить перенаправление по целевому адресу
			* @param String $target адрес целевой страницы
			* @return массив перенаправления
		*/
		public function getRedirectIdByTarget($target) {
			$targetSQL = mysql_real_escape_string($this->parseUri($target));
			$redirects = array();
			
			$sql = "SELECT `id`, `source`, `status` FROM `cms3_redirects` WHERE `target` = '{$targetSQL}'";
			$result = l_mysql_query($sql);
			if(list($id, $source, $status) = mysql_fetch_row($result)) {
				return Array($source, $target, (int) $status);
			} else {
				return false;
			}
		}
		
		
		/**
			* Удалить перенаправление
			* @param Integer $id id перенаправления
		*/
		public function del($id) {
			$id = (int) $id;
			
			$sql = <<<SQL
DELETE FROM `cms3_redirects` WHERE `id` = '{$id}'
SQL;
			l_mysql_query($sql);
		}
		
		
		/**
			* Сделать перенаправление, если url есть в таблице перенаправлений
			* @param String $currentUri url для поиска
		*/
		public function redirectIfRequired($currentUri) {
			$currentUri = mysql_real_escape_string($this->parseUri($currentUri));
			
			$sql = <<<SQL
SELECT `target`, `status` FROM `cms3_redirects`
	WHERE `source` = '{$currentUri}'
	ORDER BY `id` DESC LIMIT 1
SQL;
			$result = l_mysql_query($sql);
			if(mysql_num_rows($result)) {
				list($target, $status) = mysql_fetch_row($result);
				return $this->redirect("/" . $target, (int) $status);
			}
			
			//Попробуем найти в перенаправление в подстраницах
			$uriParts = split("\/", trim($currentUri, "/"));
			do {
				array_pop($uriParts);
				$subUri = implode("/", $uriParts) . "/";
				$subUriSQL = mysql_real_escape_string($this->parseUri($subUri));
				
				$sql = <<<SQL
SELECT `source`, `target`, `status` FROM `cms3_redirects`
	WHERE `source` = '{$subUriSQL}'
	ORDER BY `id` DESC LIMIT 1
SQL;

				$result = l_mysql_query($sql);
				if(mysql_num_rows($result)) {
					list($source, $target, $status) = mysql_fetch_row($result);
					
					$sourceUriSuffix = substr($currentUri, strlen($source));
					$target .= $sourceUriSuffix;
					$this->redirect("/" . $target, $status);
				}
				
			} while (sizeof($uriParts) > 1);
		}
		
		
		/**
			* Инициализировать события
		*/
		public function init() {
			$config = mainConfiguration::getInstance();
			
			if($config->get('seo', 'watch-redirects-history')) {
				$listener = new umiEventListener("systemModifyElement", "content", "onModifyPageWatchRedirects");
				$listener = new umiEventListener("systemMoveElement", "content", "onModifyPageWatchRedirects");
			}
		}
		
		protected function redirect($target, $status) {
			$statuses = array(
				300 => 'Multiple Choices',
				'Moved Permanently', 'Found', 'See Other',
				'Not Modified', 'Use Proxy', 'Switch Proxy', 'Temporary Redirect'
			);
			
			if(!isset($statuses[$status])) return false;
			$statusMessage = $statuses[$status];
			
			$buffer = outputBuffer::current();
			if($referer = getServer('HTTP_REFERER')) {
				$buffer->header('Referrer', $referer);
			}
			$buffer->status($status . ' ' . $statusMessage);
			$buffer->redirect($target);
			$buffer->end();
		}
		
		protected function parseUri($uri) { return trim($uri, '/'); }
	};


/*
    stream_open
    stream_read
    stream_write
    stream_tell
    stream_eof
    stream_seek
    url_stat
    stream_flush
    stream_close
*/
	interface iUmiBaseStream {

		public function stream_open($path, $mode, $options, $opened_path);
		public function stream_read($count);
		public function stream_write($data);
		public function stream_tell();
		public function stream_eof();
		public function stream_seek($offset, $whence);
		public function stream_flush();
		public function stream_close();
		public function url_stat();
		
		public function getProtocol();

		public static function getCalledStreams();
	};


	abstract class umiBaseStream implements iUmiBaseStream {
	    public static $allowTimeMark = true;
		protected
				$position = 0,
				$length = 0,
				$data = "",
				$expire = 0,
				$transform = "",
				$path, $params = array(),
				$isJson = false;

		protected	$scheme;
		protected static $callLog = array();
		
		private		$start_time = false;
		
		
		public function __construct() {
			$this->start_time = microtime(true);
		}
		
		public function stream_flush() {
			return true;
		}
		
		
		public function stream_tell() {
			return $this->position;
		}
		
		
		public function stream_eof() {
			return $this->position >= $this->length;
		}
		
		
		public function stream_seek($offset, $whence) {
			switch($whence) {
				case SEEK_SET: {
					if($this->isValidOffset($offset)) {
						$this->position = $offset;
						return true;
					} else {
						return false;
					}
				}
				
				
				case SEEK_CUR: {
					if($offset >= 0) {
						$this->position += $offset;
						return true;
					} else {
						return false;
					}
				}
				
				
				case SEEK_END: {
					if($this->isValidOffset($this->position + $offset)) {
						$this->position = $this->length + $offset;
						return true;
					} else {
						return false;
					}
				}
				

				default: {
					return false;
				}
			}
		}
		
		
		public function url_stat() {
			return Array();
		}
		
		
		public function stream_stat() {
			return Array();
		}
		
		
		public function stream_close() {
			return true;
		}
		
		
		public function stream_read($count) {
			$result = bytes_substr($this->data, $this->position, $count);
			$this->position += $count;
			return $result;
		}
		
		
		public function stream_write($inputData) {
			$inputDataLength = bytes_strlen($inputData);

		        $dataLeft = bytes_substr($this->data, 0, $this->position);
			$dataRight = bytes_substr($this->data, $this->position + $inputDataLength);

			$this->data = $dataLeft . $inputData . $dataRight;

			$this->position += $inputData;
			return $inputDataLength;
		}
		
		
		public function getProtocol() {
			return $this->scheme . "://";
		}
		
		static public function registerStream($scheme) {
			$config = mainConfiguration::getInstance();
			$filepath = $config->includeParam('system.kernel.streams') . "{$scheme}/{$scheme}Stream.php";
			if(file_exists($filepath)) {
				require $filepath;
				if(!stream_wrapper_register($scheme, "{$scheme}Stream")) {
					throw new coreException("Failed to register stream \"{$scheme}\"");
				}
			} else {
				throw new coreException("Can't locate file \"{$filepath}\"");
			}
		}


		public static function protectParams($param) {
			return str_replace("/", "&#2F;", $param);
		}
		
		
		public static function unprotectParams($param) {
			return str_replace("&#2F;", "/", $param);
		}
		
		public static function getCalledStreams() {
			$lines_arr = Array();
			foreach(self::$callLog as $callInfo) {
				list($url, $time) = $callInfo;
				$lines_arr[] = array(
					'attribute:generation-time'	=> $time,
					'node:url'					=> $url
				);
			}
			$block_arr = array('nodes:call' => $lines_arr);
			
			$dom = new DOMDocument;
			$dom->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode = $dom->createElement("streams-call");
			$dom->appendChild($rootNode);
			
			$xmlTranslator = new xmlTranslator($dom);
			$xmlTranslator->translateToXml($rootNode, $block_arr);
			
			return $dom->saveXml();
		}
		
		public static function reportCallTime($path, $time) {
			foreach(self::$callLog as &$callInfo) {
				if($callInfo[0] == $path) {
					$callInfo[1] = $time;
				}
			}
		}
		
		
		
		protected function isValidOffset($offset) {
			return ($offset >= 0) && ($offset < $this->length);
		}
		
		
		protected function translateToXml($res = array()) {
			if($this->isJson) {
				return $this->translateToJSON($res);
			}
			
			$executionTime = number_format(microtime(true) - $this->start_time, 6);
			self::reportCallTime($this->getProtocol() . $this->path, $executionTime);
			
			if(isset($res['plain:result'])) {
				return $res['plain:result'];
			}

			$dom = new DOMDocument("1.0", "utf-8");
			$dom->formatOutput = XML_FORMAT_OUTPUT;
			
			$rootNode = $dom->createElement("udata");
			$dom->appendChild($rootNode);
			
			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');
			
			
			$res['attribute:generation-time'] = $executionTime;
		
			$xslTranslator = new xmlTranslator($dom);
			$xslTranslator->translateToXml($rootNode, $res);

			if($this->transform) {
				return $this->applyXslTransformation($dom, $this->transform);
			}

			return $dom->saveXml();
		}
		
		
		protected function applyXslTransformation(DOMDocument $dom, $xslFilePath) {
			$filePath = "./xsltTpls/" . $xslFilePath;
			if(is_file($filePath) == false) {
				throw new publicException("Udata trasform xsl-template was not found \"{$filePath}\"");
			}
			
			$xsltDom = DomDocument::load($filePath, DOM_LOAD_OPTIONS);
			checkXmlError($xsltDom);

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();
			$xslt->importStyleSheet($xsltDom);
			return $xslt->transformToXML($dom);
		}
		
		
		protected function parsePath($path) {
			$protocol = $this->getProtocol();
			$path = substr($path, strlen($protocol));
			
			$parsed_url = parse_url($path);
			$realPath = $parsed_url['path'];
			if(substr($realPath, -5) == '.json') {
				$realPath = substr($realPath, 0, strlen($realPath) - 5);
				$this->isJson = true;
			} else $this->isJson = false;
			
			$this->path = $realPath;
			
			self::$callLog[] = array($protocol . $path, false);
			
			if($params = getArrayKey($parsed_url, 'query')) {
				parse_str($params, $params_arr);
				$this->params = $params_arr;
				
				$_REQUEST = array_merge($_REQUEST, $params_arr);
				
				if(isset($params_arr['expire'])) {
					$this->expire = getArrayKey($params_arr, 'expire');
				}
				
				if(isset($params_arr['transform'])) {
					$this->transform = getArrayKey($params_arr, 'transform');
				}
				
			}
			return $this->path;
		}
		
		
		protected function normalizeString($str) {
			$str = urldecode($str);

			if(!preg_match("/[\x{0000}-\x{FFFF}]+/u", $str)) {
				$str = iconv("CP1251", "UTF-8//IGNORE", $str);
			}

			return $str;
		}
		
		protected function setData($data) {
		    if(!$data) {
		        return false;
		    }
		    
		    $this->data = $data;
		    $this->length = bytes_strlen($data);
		    return true;
		}
		
		protected function setDataError($errorCode) {
			$data = Array(
				'error' => Array(
					'attribute:code' => $errorCode,
					'node:message' => getLabel('error-' . $errorCode)
				)
			);
			$data = self::translateToXml($data);
			$this->setData($data);
			return true;
		}
		
		
		protected function translateToJSON($data) {
			$translator = new jsonTranslator;
			return $translator->translateToJson($data);
		}
	};



$i18n = Array(
	"header-data-trash"		=> "Корзина удаленных страниц",
	"label-empty-all"		=> "Очистить корзину",
	"label-type-add"		=> "Добавить тип данных",
	"header-data-config"		=> "Настройки модуля",
	"header-data-types"		=> "Типы данных",
	"header-data-type_group_add"	=> "Добавление группы полей",
	"header-data-type_edit"		=> "Редактирование типа данных",
	"header-data-type_field_add"	=> "Добавление поля",
	"header-data-type_field_edit"	=> "Редактирование поля",
	"header-data-type_group_edit"	=> "Редактирование группы полей",
	"header-data-guides"		=> "Справочники",
	"header-data-guide_item_edit" => "Редактирование элемента справочника",
	"header-data-guide_item_add"	=> "Добавление элемента справочника",

	"label-type-name"		=> "Название типа",
	"label-hierarchy-type"		=> "Назначение типа",
	"label-is-public"		=> "Общедоступный",
	"label-is-guide"		=> "Можно использовать как справочник",
	"label-edit-type-common"	=> "Свойства типа",
	"label-add-fields-group"	=> "Добавить группу полей",

	"label-group-title"		=> "Название группы",
	"label-group-name"		=> "Идентификатор",
	"label-group-is-visible"	=> "Видимое",

	"label-field-title"		=> "Название поля",
	"label-field-name"		=> "Идентификатор",
	"label-field-tip"		=> "Подсказка",
	"label-field-is-visible"	=> "Видимое",
	"label-field-in-search"		=> "Индексировать",
	"label-field-in-filter"		=> "Использовать в фильтрах",
	"label-field-type"		=> "Тип поля",
	"label-field-default-guide"	=> "Использовать справочник",
	"label-field-is-required"		=> "Обязательное",

	"field-is_active"		=> "Активность",

	"label-guide-add"		=> "Добавить справочник",
	"label-guide-item-add"	=> "Добавить наименование справочника",
	"label-module"          => "Модуль",
	"label-method"          => "Метод",

	"field-name"			=> "Название",

	"header-data-guide_items"	=> "Содержание справочника",

	'object-type-blogs-blog_message'	=>	'Cообщение блога',
	'object-type-rss-lenta'			=>	'RSS-лента',
	'object-type-users-author'		=>	'Автор',
	'object-type-eshop-address'		=>	'Адрес доставки',
	'object-type-banners-banner'		=>	'Баннер',
	'object-type-banners-banner-image'		=>	'Баннер с картинкой',
	'object-type-banners-banner-swf'		=>	'Баннер с флешкой',
	'object-type-banners-banner-html'		=>	'Баннер с HTML',
	'object-type-blogs-blog'		=>	'Блог',
	'object-type-valyuta'			=>	'Валюта',
	'object-type-vote-poll_item'		=>	'Вариант ответа на опрос',
	'object-type-vid_homyachka'		=>	'Вид хомячка',
	'object-type-faq-question'		=>	'Вопрос в FAQ',
	'object-type-catalog-question'		=>	'Вопрос в матрице подбора',
	'object-type-dispatches-release'	=>	'Выпуск рассылки',
	'object-type-users-users'		=>	'Группы пользователей',
	'object-type-eshop-discount_card'	=>	'Дисконтная карта',
	'object-type-druzhelyubnost_homyachka'	=>	'Дружелюбность хомячка',
	'object-type-eshop-order'		=>	'Заказ в интернет-магазине',
	'object-type-content-ticket'		=>	'Заметка',
	'object-type-integriruemost_homyachka_v_koleso'			=>	'Интегрируемость хомячка в колесо',
	'object-type-faq-category'		=>	'Категория в FAQ',
	'object-type-kachestvo_fleshki'		=>	'Качество флешки',
	'object-type-catalog-object'		=>	'Объект каталога',
	'object-type-catalog-object-good'		=>	'Товар в магазине',
	'object-type-catalog-object-good-hamster'		=>	'Хомячок',
	'object-type-catalog-object-good-kolesa'		=>	'Колеса для хомячков',
	'object-type-catalog-object-good-povodki'		=>	'Поводки',
	'object-type-comments-comment'		=>	'Комментарий',
	'object-type-blogs-blog_comment'	=>	'Комментарий к сообщению блога',
	'object-type-forum-conf'		=>	'Конференция форума',
	'object-type-kulinarnye_predpochteniya_homyachka'			=>	'Кулинарные предпочтения хомячка',
	'object-type-news-rubric'		=>	'Лента новостей',
	'object-type-updatesrv-license'		=>	'Лицензия UMI.CMS',
	'object-type-catalog-matrix'		=>	'Матрица подбора',
	'object-type-banners-place'		=>	'Места показов баннеров',
	'object-type-eshop-order_item'		=>	'Наименование в заказе',
	'object-type-news-item'			=>	'Новость',
	'object-type-okras_homyachka'		=>	'Окрас хомячка',
	'object-type-catalog-answer'		=>	'Ответ на вопрос в матрице подбора',
	'object-type-eshop-payment_transaction'	=>	'Платежная транзакция',
	'object-type-dispatches-subscriber'	=>	'Подписчик на рассылку',
	'object-type-pol'			=>	'Пол',
	'object-type-pol_homyachka'		=>	'Пол хомячка',
	'object-type-users-user'		=>	'Пользователь',
	'object-type-faq-project'		=>	'Проект в FAQ',
	'object-type-proizvoditeli'		=>	'Производители',
	'object-type-catalog-category'		=>	'Раздел каталога',
	'object-type-razdel_sajta'		=>	'Раздел сайта',
	'object-type-dispatches-dispatch'	=>	'Рассылка',
	'object-type-filemanager-shared_file'	=>	'Скачиваемый файл',
	'object-type-eshop-discount'		=>	'Скидка в интернет-магазине',
	'object-type-eshop-store'		=>	'Склад интернет-магазина',
	'object-type-forum-message'		=>	'Сообщение в форуме',
	'object-type-dispatches-message'	=>	'Сообщение рассылки',
	'object-type-webforms-address'		=>	'Список адресов',
	'object-type-spisok_gorodov_dlya_geo'	=>	'Список городов для geo',
	'object-type-spravochnik_dlya_polya_gorod'	=>	'Справочник для поля "Город"',
	'object-type-spravochnik_dlya_polya_zhanr'	=>	'Справочник для поля "Жанр"',
	'object-type-spravochnik_dlya_polya_nalichiya_na_skladah'	=>	'Справочник для поля "Наличия на складах"',
	'object-type-spravochnik_dlya_polya_ukazatel_na_sklad'		=>	'Справочник для поля "Указатель на склад"',
	'object-type-spravochnik_dlya_polya_format_nositelya'		=>	'Справочник для поля "Формат носителя"',
	'object-type-spravochniki'		=>	'Справочники',
	'object-type-vote-poll'			=>	'Стандартный опрос',
	'object-type-eshop-order_status'	=>	'Статус заказа',
	'object-type-eshop-payment_transaction_status'	=>	'Статус платежной транзакции',
	'object-type-blogs-blog_privacy'	=>	'Степень приватности (блоги)',
	'object-type-content-'			=>	'Страница контента',
	'object-type-strany'			=>	'Страны',
	'object-type-news-subject'		=>	'Сюжет публикации',
	'object-type-tip_rss'			=>	'Тип RSS',
	'object-type-updatesrv-license_type'	=>	'Тип лицензии UMI.CMS',
	'object-type-tip_povodka'		=>	'Тип поводка',
	'object-type-tip_shersti_homyachka'	=>	'Тип шерсти хомячка',
	'object-type-eshop-store_relation'	=>	'Товар на складе интернет-магазина',
	'object-type-forum-topic'		=>	'Топик в форуме',
	'object-type-webforms-form'		=>	'Форма обратной связи',
	'object-type-photoalbum-album'		=>	'Фотоальбом',
	'object-type-photoalbum-photo'		=>	'Фотография',
	'object-type-harakter_homyachka'	=>	'Характер хомячка',
	'object-type-cvet'			=>	'Цвет',
	'object-type-webforms-template'		=>	'Шаблон письма',
	'object-type-new-data-type' 		=>  'Новый тип данных',
	'object-type-new-guide' 			=> 	'Новый справочник',

	'hierarchy-type-content-page'		=>	'Страницы контента',
	'hierarchy-type-users-user'		=>	'Пользователи',
	'hierarchy-type-catalog-category'	=>	'Разделы каталога',
	'hierarchy-type-catalog-object'		=>	'Объекты каталога',
	'hierarchy-type-users-users'		=>	'Группы пользователей',
	'hierarchy-type-news-rubric'		=>	'Ленты новостей',
	'hierarchy-type-news-item'		=>	'Новости',
	'hierarchy-type-news-subject'		=>	'Сюжет публикации',
	'hierarchy-type-vote-poll'		=>	'Опрос',
	'hierarchy-type-vote-poll_item'		=>	'Ответ в опросе',
	'hierarchy-type-eshop-order'		=>	'Заказ в интернет-магазине',
	'hierarchy-type-eshop-order_item'	=>	'Наименование заказа в интернет-магазине',
	'hierarchy-type-eshop-order_status'	=>	'Статус заказа в интернет-магазине',
	'hierarchy-type-eshop-address'		=>	'Адрес доставки',
	'hierarchy-type-forum-conf'		=>	'Конференция форума',
	'hierarchy-type-forum-topic'		=>	'Топик в форуме',
	'hierarchy-type-forum-message'		=>	'Сообщение в форуме',
	'hierarchy-type-comments-comment'	=>	'Комментарий',
	'hierarchy-type-updatesrv-license'	=>	'Лицензия UMI.CMS',
	'hierarchy-type-updatesrv-license_type'	=>	'Тип лицензии UMI.CMS',
	'hierarchy-type-banners-banner'		=>	'Баннер',
	'hierarchy-type-banners-place'		=>	'Место показа баннера',
	'hierarchy-type-dispatches-dispatch'	=>	'Рассылка',
	'hierarchy-type-dispatches-release'	=>	'Выпуск рассылки',
	'hierarchy-type-dispatches-message'	=>	'Сообщение рассылки',
	'hierarchy-type-dispatches-subscriber'	=>	'Подписчик на рассылку',
	'hierarchy-type-catalog-matrix'		=>	'Матрица подбора',
	'hierarchy-type-catalog-question'	=>	'Вопрос в матрице подбора',
	'hierarchy-type-catalog-answer'		=>	'Ответ на вопрос в матрице подбора',
	'hierarchy-type-users-author'		=>	'Автор',
	'hierarchy-type-content-ticket'		=>	'Заметка на сайте',
	'hierarchy-type-photoalbum-album'	=>	'Фотоальбом',
	'hierarchy-type-photoalbum-photo'	=>	'Фотография',
	'hierarchy-type-faq-project'		=>	'Проекты в FAQ',
	'hierarchy-type-faq-category'		=>	'Категории в FAQ',
	'hierarchy-type-faq-question'		=>	'Вопросы в FAQ',
	'hierarchy-type-filemanager-shared_file'	=>	'Скачиваемый файл',
	'hierarchy-type-eshop-store'		=>	'Склад в интернет-магазине',
	'hierarchy-type-eshop-store_relation'	=>	'Товар на складе интернет-магазина',
	'hierarchy-type-eshop-discount_card'	=>	'Дисконтная карта',
	'hierarchy-type-eshop-discount'		=>	'Скидка в интернет-магазине',
	'hierarchy-type-blogs-blog'		=>	'Блог',
	'hierarchy-type-blogs-blog_privacy'	=>	'Степень приватности блога',
	'hierarchy-type-blogs-blog_comment'	=>	'Комментарий к сообщению блога',
	'hierarchy-type-blogs-blog_message'	=>	'Сообщение блога',
	'hierarchy-type-eshop-payment_transaction'	=>	'Платежная транзакция',
	'hierarchy-type-eshop-payment_transaction_status'	=>	'Статус платежной транзакции',
	'hierarchy-type-webforms-form'		=>	'Форма обратной связи',
	'hierarchy-type-webforms-template'	=>	'Шаблон письма',
	'hierarchy-type-webforms-address'	=>	'Список адресов',
	"hierarchy-type-eshop-global-discount" => "Глобальная скидка в интернет магазине",
	'hierarchy-type-blogs20-blog' => 'Блог',
	'hierarchy-type-blogs20-comment' => 'Комментарий блога',

	'field-min_discount_order_total'	=> 'Минимальный размер заказа',
	'field-max_discount_order_total'	=> 'Максимальный размер заказа',
	'field-preffered_currency'			=> 'Предпочитаемая валюта',
	'field-age'				=>	'Возраст',
	'field-gender'				=>	'Пол',
	'field-geo_targeting_is_active'		=>	'Гео-таргетинг включен',
	'field-avatar'				=>	'Аватарка',
	'field-url'				=>	'Url страницы',
	'field-rss_type'			=>	'Тип',
	'field-news_rubric'			=>	'Раздел публикаций',
	'field-anons_pic'			=>	'Картинка для анонса',
	'field-publish_pic'			=>	'Картинка для публикации',
	'field-customer_comments'		=>	'Пометки покупателя',
	'field-default_type_id'			=>	'default_type_id',
	'field-seo_prefix'			=>	'seo_prefix',
	'field-trans'				=>	'Транзитный Id',
	'field-proizvoditel'			=>	'Производитель',
	'field-predyduwaya_cena'		=>	'Предыдущая цена',
	'field-specialnaya_cena'		=>	'Специальная цена',
	'field-fiksirovannaya_cena'		=>	'Фиксированная цена',
	'field-opisanie'			=>	'Описание товара',
	'field-soputstvuyuwie_tovary'		=>	'Сопутствующие товары',
	'field-polosa_tehnologij'		=>	'Полоса технологий',
	'field-izobrazhenie'			=>	'Изображение',
	'field-bolshoe_izobrazhenie'		=>	'Большое изображение',
	'field-id_name'				=>	'ID статуса',
	'field-tip_igrovoj_pristavki'		=>	'Тип игровой приставки',
	'field-komplekt_postavki'		=>	'Комплект поставки',
	'field-tip_karty_pamyati'		=>	'Тип карты памяти',
	'field-razemy_tip'			=>	'Разъемы (тип)',
	'field-cvet_korpusa'			=>	'Цвет корпуса',
	'field-garantiya'			=>	'Гарантия',
	'field-tranzitnyj_id'			=>	'Транзитный ID',
	'field-special_naya_cena'		=>	'Специальная цена',
	'field-bol_shoe_izobrazhenie'		=>	'Большое изображение',
	'field-yes_or_no'			=>	'Да или нет?',
	'field-country'				=>	'Страна',
	'field-city'				=>	'Город',
	'field-post_index'			=>	'Почтовый индекс',
	'field-address'				=>	'Адрес',
	'field-phone'				=>	'Номер телефона для контрольного звонка',
	'field-codename'			=>	'Идентефикатор версии',
	'field-ot_baldi'			=>	'Какое-то поле',
	'field-my_house'			=>	'Фотка моего дома',
	'field-site_link'			=>	'Ссылка на сайт',
	'field-company_logo'			=>	'Логoтип фирмы',
	'field-elements_links'			=>	'Ссылки на элементы',
	'field-kartinochka'			=>	'Картиночка',
	'field-objektiki'			=>	'Объектики',
	'field-currency_id'			=>	'Код валюты',
	'field-currency'			=>	'Валюта',
	'field-rate'				=>	'Курс',
	'field-model'				=>	'Модель товара',
	'field-isbn'				=>	'ISBN',
	'field-author'				=>	'Автор',
	'field-publisher'			=>	'Издатель',
	'field-year'				=>	'Год',
	'field-series'				=>	'Серия',
	'field-nazvanie'			=>	'Название',
	'field-media'				=>	'Носитель',
	'field-starring'			=>	'Актеры',
	'field-director'			=>	'Режиссер',
	'field-original_name'			=>	'Оригинальное название',
	'field-store'				=>	'Количество на складе',
	'field-quality_value'			=>	'Значение',
	'field-to_order'			=>	'на заказ (при отсутствии на складе)',
	'field-typePrefiks'			=>	'префикс',
	'field-place'				=>	'Место показа',
	'field-hall'				=>	'Зал',
	'field-date'				=>	'Дата выпуска',
	'field-premier'				=>	'Премьера',
	'field-for-kids'			=>	'для детей',
	'field-hall_plan'			=>	'План зала',
	'field-hall_part'			=>	'Места',
	'field-worldRegion'			=>	'Часть света',
	'field-region'				=>	'Курорт или город',
	'field-days'				=>	'Количество дней',
	'field-dataTour'			=>	'Даты заездов',
	'field-hotel_stars'			=>	'Звезды',
	'field-room'				=>	'Тип комнаты',
	'field-meal'				=>	'Тип питания',
	'field-included'			=>	'Что включено в стоимость тура',
	'field-transport'			=>	'Транспорт',
	'field-plus'				=>	'Процент (+)',
	'field-is_cbrf'				=>	'Использовать CBRF',
	'field-deliveryIncluded'		=>	'Доставка',
	'field-price_min'			=>	'Минимальная цена',
	'field-price_max'			=>	'Максимальная цена',
	'field-sales_notes'			=>	'Особенности товара',
	'field-color'				=>	'Цвет',
	'field-weight'				=>	'Вес',
	'field-power'				=>	'мощность',
	'field-description'			=>	'Описание',
	'field-'				=>	'Продолжительность',
	'field-descr'				=>	'Описание',
	'field-order_price'			=>	'Сумма счета',
	'field-elements'			=>	'Разделы каталога',
	'field-hex'				=>	'Цветовой код',
	'field-zhanr'				=>	'Жанр',
	'field-kratkoe_opisanie'		=>	'Краткое описание',
	'field-format_nositelya'		=>	'Формат носителя',
	'field-god_vypuska'			=>	'Год выпуска',
	'field-vozrastnoe_ogranichenie_na_prosmotr'	=>	'Возрастное ограничение на просмотр',
	'field-vid'				=>	'Вид',
	'field-okras'				=>	'Окрас',
	'field-tip_shersti'			=>	'Тип шерсти',
	'field-ves'				=>	'Вес',
	'field-pol'				=>	'Пол',
	'field-kolichestvo_lap'			=>	'Количество лап',
	'field-dlina_usov'			=>	'Длина усов',
	'field-maksimalnaya_skorost'		=>	'Максимальная скорость',
	'field-obem_legkih'			=>	'Объем легких',
	'field-razmah_lap_v_bege'		=>	'Размах лап в беге',
	'field-harakter'			=>	'Характер',
	'field-druzhelyubnost'			=>	'Дружелюбность',
	'field-kulinarnye_predpochteniya'	=>	'Кулинарные предпочтения',
	'field-integriruemost_v_koleso'		=>	'Интегрируемость в колесо',
	'field-photo'				=>	'Фотография',
	'field-diametr_kolesa'			=>	'Диаметр колеса',
	'field-type'				=>	'Тип',
	'field-s_shipami'			=>	'С шипами',
	'field-recommend'			=>	'Рекомендуем',
	'field-lock_cancel'			=>	'Статус блокирует отмену заказа',
	'field-lock_payment'			=>	'Статус блокирует оплату заказа',
	'field-destination_address'		=>	'Адрес доставки',
	'field-sender_ip'			=>	'IP-адрес отправителя',
	'field-from_email_template'		=>	'Адрес от',
	'field-from_template'			=>	'Имя от',
	'field-subject_template'		=>	'Тема письма',
	'field-master_template'			=>	'Шаблон тела письма',
	'field-autoreply_from_email_template'	=>	'Адрес получателя',
	'field-autoreply_from_template'		=>	'Имя получателя',
	'field-autoreply_subject_template'	=>	'Тема',
	'field-autoreply_template'		=>	'Тело',
	'field-form_id'				=>	'Идентификатор формы',
	'field-address_description'		=>	'Описание',
	'field-address_list'			=>	'Адреса',
	'field-fname'				=>	'Имя',
	'field-father_name'			=>	'Отчество',
	'field-lname'				=>	'Фамилия',
	'field-e-mail'				=>	'E-mail',
	'field-time_targeting_is_active'	=>	'Time-таргетинг включен',
	'field-time_targeting_by_month_days'	=>	'По числам месяца',
	'field-time_targeting_by_month'		=>	'По месяцам',
	'field-time_targeting_by_week_days'	=>	'По дням недели',
	'field-time_targeting_by_hours'		=>	'По времени суток',
	'field-title'				=>	'Поле TITLE',
	'field-h1'				=>	'Поле H1',
	'field-meta_keywords'			=>	'Поле meta KEYWORDS',
	'field-meta_descriptions'		=>	'Поле meta DESCRIPTIONS',
	'field-content'				=>	'Контент',
	'label-field-content'				=>	'Контент',
	'field-menu_pic_ua'			=>	'Изображение неактивного раздела',
	'field-menu_pic_a'			=>	'Изображение активного раздела',
	'field-header_pic'			=>	'Изображение для заголовка',
	'field-robots_deny'			=>	'Запретить индексацию поисковиками',
	'field-show_submenu'			=>	'Показывать подменю',
	'field-is_expanded'			=>	'Меню всегда развернуто',
	'field-is_unindexed'			=>	"Исключить из поиска",
	'field-login'				=>	'Логин',
	'field-password'			=>	'Пароль',
	'field-groups'				=>	'Группы пользователей',
	'field-readme'				=>	'Описание',
	'field-anons'				=>	'Анонс',
	'label-field-anons'				=>	'Анонс',
	'field-source'				=>	'Источник',
	'field-source_url'			=>	'URL источника',
	'field-publish_time'			=>	'Дата публикации',
	'field-begin_time'			=>	'Дата начала активности',
	'field-finish_time'			=>	'Дата завершения активности',
	'field-end_time'			=>	'Дата завершения скидки',
	'field-subjects'			=>	'Входит в сюжеты',
	'field-price_item'			=>	'Цена за единицу товара',
	'field-price_total'			=>	'Цена итоговая',
	'field-count'				=>	'Количество ответов',
	'field-discount_size'			=>	'Размер скидки',
	'field-catalog_relation'		=>	'Ссылка на товар',
	'field-items'				=>	'Наименования',
	'field-status'				=>	'Состояние',
	'field-order_time'			=>	'Дата заказа',
	'field-admin_comments'			=>	'Пометки администратора',
	'field-is_closed'			=>	'Голосование закрыто',
	'field-question'			=>	'Вопрос',
	'field-answers'				=>	'Ответы',
	'field-total_count'			=>	'Всего проголосовало',
	'field-cena'				=>	'Цена',
	'field-orders_refs'			=>	'Заказы',
	'field-delivery_address'		=>	'Адрес доставки',
	'field-delivery_addresses'		=>	'Адреса доставки',
	'field-message'				=>	'Сообщение',
	'field-activate_code'			=>	'Код активации',
	'field-is_activated'			=>	'Активирован',
	'field-domain_name'			=>	'Домен',
	'field-ip'				=>	'ip',
	'field-license_type'			=>	'Тип лицензии',
	'field-owner_lname'			=>	'Фамилия',
	'field-owner_fname'			=>	'Имя',
	'field-owner_mname'			=>	'Отчество',
	'field-owner_email'			=>	'E-mail',
	'field-keycode'				=>	'Лицензионный ключ',
	'field-gen_time'			=>	'Дата создания',
	'field-id'				=>	'ID',
	'field-views_count'			=>	'Количество показов',
	'field-clicks_count'			=>	'Количество переходов',
	'field-max_views'			=>	'Максимальное количество показов',
	'field-tags'				=>	'Теги',
	'field-is_active'			=>	'Активен',
	'field-view_pages'			=>	'Страницы, на которых показывать баннер',
	'field-image'				=>	'Изображение',
	'field-open_in_new_window'		=>	'Открывать в новом окне',
	'field-width'				=>	'Ширина',
	'field-height'				=>	'Высота',
	'field-alt'				=>	'Альтернативный текст',
	'field-swf'				=>	'Флеш-ролик',
	'field-swf_quality'			=>	'Качество ролика',
	'field-html_content'			=>	'HTML-содержание',
	'field-show_till_date'			=>	'Дата окончания показа',
	'field-poll_rel'			=>	'Указатель на опрос',
	'field-is_show_rand_banner'		=>	'Показ случайного баннера',
	'field-show_start_date'			=>	'Дата начала показа',
	'field-disp_last_release'		=>	'Дата последнего выпуска',
	'field-disp_description'		=>	'Описание',
	'field-disp_reference'			=>	'Ссылка на рассылку',
	'field-header'				=>	'Заголовок',
	'field-body'				=>	'Тело сообщения',
	'field-attach_file'			=>	'Прикрепленный файл',
	'field-release_reference'		=>	'Ссылка на выпуск',
	'field-uid'					=>	'Пользователь',
	'field-subscriber_dispatches'		=>	'Подписан на рассылки',
	'field-subscribe_date'			=>	'Дата подписки',
	'field-related_items'			=>	'Подходящие товары',
	'field-question_txt'			=>	'Текст вопроса',
	'field-answers_rel'			=>	'Список ответов',
	'field-per_page'			=>	'Количество выводимых результатов',
	'field-goods'				=>	'Товары',
	'field-questions_rel'			=>	'Список вопросов',
	'field-is_registrated'			=>	'Зарегистрирован',
	'field-user_id'				=>	'Владелец',
	'field-nickname'			=>	'Ник',
	'field-email'				=>	'E-mail',
	'field-author_id'			=>	'Автор',
	'field-x'				=>	'X',
	'field-y'				=>	'Y',
	'field-create_time'			=>	'Дата создания',
	'field-answer'				=>	'Ответ на вопрос',
	'field-fs_file'				=>	'Скачиваемый файл',
	'field-downloads_counter'		=>	'Количество загрузок',
	'field-user_dock'			=>	'Пользовательская панель',
    'field-page_tags'           =>  'Показывать на страницах с тэгами',
	'field-user_tags'			=>	'Показывать пользователям с тэгами',
	'field-rate_voters'			=>	'Количество проголосовавших',
	'field-rate_sum'			=>	'Сумма баллов',
	'field-amount'				=>	'Количество на складе',
	'field-store_id'			=>	'Указатель на склад',
	'field-store_amounts'			=>	'Наличия на складах',
	'field-proc'				=>	'Процент скидки',
	'field-start_time'			=>	'Дата начала действия скидки',
	'field-code'				=>	'Код',
	'field-price'				=>	'Цена',
	'field-ignore_discounts'		=>	'Игнорировать скидки',
	'field-subscribed_pages'		=>	'Подписки на изменения',
	'field-is_online'			=>	'Пользователь on-line',
	'field-privacy'				=>	'Приватность',
	'field-sid'				=>	'sid',
	'field-prvlist_friends'			=>	'Мои друзья',
	'field-lmessage_time'			=>	'Дата добавления последнего сообщения',
	'field-lcomment_time'			=>	'Дата добавления последнего комментария',
	'field-privacy_forpostonly'		=>	'Приватность действует только на комментирование (не на просмотр)',
	'field-topics_count'			=>	'Количество топиков',
	'field-messages_count'			=>	'Количество сообщений',
	'field-last_post_time'			=>	'Дата последнего добавления',
	'field-last_request_time'		=>	'Время последнего обращения',
	'field-rated_pages'			=>	'Рейтингованные страницы',
	'field-sid_transaction'			=>	'Строковый идентификатор',
	'field-sid_eshoporder_status'		=>	'Соотнесенный статус заказа',
	'field-rel_transactionstatus'		=>	'Статус транзакции',
	'field-date_created'			=>	'Дата создания',
	'field-inited_whom'			=>	'Инициализатор транзакции',
	'field-rel_eshoporder'			=>	'Оплачиваемый заказ',
	'field-initprice'			=>	'Сумма заказа',
	'field-text_request'			=>	'Подробности запроса',
	'field-method_engine'			=>	'Transaction engine',
	'field-date_answered'			=>	'Дата получения результата',
	'field-text_answer'			=>	'Подробности получения результата',
	'field-date_validated'			=>	'Дата проверки результата',
	'field-text_validated'			=>	'Подробности проверки результата',
	'field-date_conseq'			=>	'Дата проверки',
	'field-text_conseq'			=>	'Подробности проверки',
	'field-city_targeting_city'		=>	'Город',
	'field-city_targeting_is_active'	=>	'Геотаргетинг включен',
	'field-news_relation'			=> 'Связано с лентой новостей',
	'field-ignore-banner-subpages'	=> 'Игнорировать подстраницы',
	'field-publish-status'		=>	'Статус публикации',



	'fields-group-short_info'		=>	'Персональная информация',
	'fields-group-more_info'		=>	'Дополнительная информация',
	'fields-group-common'			=>	'Основные параметры',
	'fields-group-view_params'		=>	'Параметры показа',
	'fields-group-time_targeting'		=>	'Time-таргетинг',
	'fields-group-menu_view'		=>	'Отображение в меню',
	'fields-group-more_params'		=>	'Дополнительные параметры',
	'fields-group-idetntify_data'		=>	'Идентификационные данные',
	'fields-group-item_props'		=>	'Свойства публикации',
	'fields-group-subjects_block'		=>	'Сюжеты',
	'fields-group-order_item_props'		=>	'Свойства наименования',
	'fields-group-order_props'		=>	'Свойства заказа',
	'fields-group-comments'			=>	'Пометки',
	'fields-group-common_props'		=>	'Общие свойства',
	'fields-group-poll_props'		=>	'Свойства опроса',
	'fields-group-status_props'		=>	'Свойства статусы',
	'fields-group-delivery_address'		=>	'Адрес для доставки',
	'fields-group-topic_props'		=>	'Свойства топика',
    'fields-group-message_props'        =>    'Свойства сообщения',
	'fields-group-comment_props'		=>	'Свойства комментария',
	'fields-group-license_info'		=>	'Свойства лицензии',
	'fields-group-owner_info'		=>	'Информация о клиенте',
	'fields-group-currency_props'		=>	'Свойства валюты',
	'fields-group-view_pages'		=>	'Разделы отображения',
	'fields-group-banner_custom_props'	=>	'Индивидуальные параметры баннера',
	'fields-group-redirect_props'		=>	'Параметры перехода',
	'fields-group-props'			=>	'Свойства',
	'fields-group-grp_disp_props'		=>	'Свойства рассылки',
	'fields-group-grp_disp_release_props'	=>	'Свойства выпуска рассылки',
	'fields-group-grp_disp_msg_props'	=>	'Свойства сообщения рассылки',
	'fields-group-grp_sbs_props'		=>	'Информация о подписчике',
	'fields-group-grp_disp_msg_extended'	=>	'Дополнительные свойства',
	'fields-group-grp_sbs_extended'		=>	'Параметры подписки',
	'fields-group-matrix_props'		=>	'Свойства матрицы',
	'fields-group-photo_props'		=>	'Свойства фотографии',
	'fields-group-album_props'		=>	'Свойства фотоальбома',
	'fields-group-svojstva_gruppy_polzovatelej'		=>	'Свойства группы пользователей',
	'fields-group-fs_file_props'		=>	'Свойства файла',
	'fields-group-news_images'		=>	'Изображения новости',
	'fields-group-dopolnitelno'		=>	'Дополнительно',
	'fields-group-rate_props'		=>	'Свойства рейтинга',
	'fields-group-rate_voters'		=>	'Количество проголосовавших',
	'fields-group-store_props'		=>	'Свойства склада',
	'fields-group-cenovye_svojstva'		=>	'Ценовые свойства',
	'fields-group-other_proerties'		=>	'Характеристики хомячка',
	'fields-group-pictures'			=>	'Изображения',
	'fields-group-descr_grp'		=>	'Описание товара',
	'fields-group-parametry_aksessuara'	=>	'Параметры аксессуара',
	'fields-group-recommend'		=>	'Рекомендуем',
	'fields-group-common_group'		=>	'Общие параметры',
	'fields-group-privatnost'		=>	'Приватность',
	'fields-group-identity'			=>	'identity',
	'fields-group-transaction_status_props'	=>	'Свойства',
	'fields-group-transaction_props'	=>	'Свойства транзакции',
	'fields-group-trans_init'		=>	'Инициализация',
	'fields-group-trans_deliver'		=>	'Доставка',
	'fields-group-prelim'			=>	'Результат',
	'fields-group-final'			=>	'Подтверждение',
	'fields-group-consequences'		=>	'Послесловие',
	'fields-group-city_targeting'		=>	'Геотаргетинг',
	'fields-group-SendingData'		=>	'Данные для отправки',
	'fields-group-Templates'		=>	'Письмо',
	'fields-group-Binding'			=>	'Привязка',
	'fields-group-list'			=>	'Список',
	'fields-group-short_user_info'		=> 'Краткая информация',
	'fields-group-advanced'			=> 'Дополнительные',

	'field-type-int'				=>	'Число',
	'field-type-string'				=>	'Строка',
	'field-type-text'				=>	'Простой текст',
	'field-type-relation'			=>	'Выпадающий список',
	'field-type-relation-multiple'	=>	'Выпадающий список с множественным выбором',
	'field-type-file'				=>	'Файл',
	'field-type-img_file'			=>	'Изображение',
	'field-type-swf_file'			=>	'Флеш-ролик',
	'field-type-date'				=>	'Дата',
	'field-type-boolean'			=>	'Кнопка-флажок',
	'field-type-wysiwyg'			=>	'HTML-текст',
	'field-type-password'			=>	'Пароль',
	'field-type-tags-multiple'		=>	'Теги',
	'field-type-symlink-multiple'	=>	'Ссылка на дерево',
	'field-type-price'				=>	'Цена',
	'field-type-float'				=>	'Число с точкой',
	'field-type-optioned'			=>	'Составное',
	'field-type-counter'			=>	'Счетчик',
	'field-type-video'				=>	'Видео',

	'field-expiration-date'		=>	'Дата окончания актуальности',
	'field-notification-date'	=>	'Дата предупреждения об окончании актуальности',
	'field-publish_comments'	=>	'Комментарий к публикации',
	'field-date-empty'			=>	'Никогда',
	'object-samka'				=>	'Самка',
	'object-samec'				=>	'Самец',
	'object-v_korzine'			=>	'В корзине',
	'object-ozhidaet_proverki'		=>	'Ожидает проверки',
	'object-prinyat'			=>	'Принят',
	'object-otklonen'			=>	'Отклонен',
	'object-otmenen'			=>	'Отменен',
	'object-gotov'				=>	'Готов',
	'object-v_processe_oplaty'		=>	'В процессе оплаты',
	'object-oplachen_uspeshno'		=>	'Оплачен успешно',
	'object-oshibki_v_oplate'		=>	'Ошибки в оплате',
	'object-proveren_mozhno_oplachivat'	=>	'Проверен, можно оплачивать',
	'object-proverka_oplaty'		=>	'Проверка оплаты',
	'object-1_inicializirovana'		=>	'1. Инициализирована',
	'object-2_dostavlena'			=>	'2. Доставлена',
	'object-3_ne_dostavlena'		=>	'3. Не доставлена',
	'object-4_predvaritel_no_prinyata_ozhidaetsya_podtverzhdenie'			=>	'4. Предварительно принята (ожидается подтверждение)',
	'object-5_predvaritel_no_prinyata_podtverzhdenie_ne_trebuetsya'			=>	'5. Предварительно принята (подтверждение не требуется)',
	'object-6_predvaritel_no_otklonena_ozhidaetsya_podtverzhdenie'			=>	'6. Предварительно отклонена (ожидается подтверждение)',
	'object-7_predvaritel_no_otklonena_podtverzhdenie_ne_trebuetsya'		=>	'7. Предварительно отклонена (подтверждение не требуется)',
	'object-8_prinyata_s_podtverzhdeniem'	=>	'8. Принята с подтверждением',
	'object-9_otklonena_s_podtverzhdeniem'	=>	'9. Отклонена с подтверждением',
	'object-moya_zhzh-lenta'		=>	'Моя ЖЖ-лента',
	'object-rss'				=>	'RSS',
	'object-atom'				=>	'ATOM',
	'object-dlya_vseh'			=>	'Для всех',
	'object-dlya_vladel_ca_i_druzej'	=>	'Для владельца и друзей',
	'object-tol_ko_dlya_vladel_ca_bloga'	=>	'Только для владельца блога',

	'object-male'   => 'Мужской',
	'object-female' => 'Женский',

	'object-height'				=> 'Высокое',
	'object-low'				=> 'Низкое',
	'object-medium'				=> 'Среднее',

	'object-status-preunpublish'	=>	'Готовится к снятию с публикации',
	'object-status-publish'		=>	'Опубликован',
	'object-status-unpublish'	=>	'Снят с публикации',

	'perms-data-main' => 'Просмотр объектов',
	'perms-data-guides' => 'Управление справочниками',
	'perms-data-trash' => 'Мусорная корзина',
	'perms-data-types' => 'Управление шаблонами данных',

	'field-form_sending_time' => 'Время отправки',
	'field-auto_reply'			=> 'Автоответ',

	'js-data-add-field'			=> 'Добавить поле',
	'js-view-guide-items'		=> 'Содержимое справочника',

	'group-currency-props'		=> 'Свойства валюты',

	'min-discount-order-total'	=>	'Минимальная сумма заказа',
	'max-discount-order-total'	=>	'Максимальная сумма заказа',
	'global-discount-order-size'	=> 'Сумма глобальной скидки',
	'global-discount-end'		=> 'Дата окончания действия',
	'global-discount-start'		=>	'Дата начала действия',
	'global-discount-active-time'	=> 'Время действия скидки',
	'order-total-range'		=> 	'Диапазон суммы заказа',
	'eshop-order-currency'	=> 'Валюта в интернет магазине',

	'eshop-currency'	=> 'Валюта в интернет магазине',

	'eshop-currency-letter-code'	=> 'Буквенный код',
	'eshop-currency-digit-code'		=>	'Цифровой код',
	'eshop-currency-exchange-rate'	=>	'Курс обмена',
	'eshop-order-currency-id'		=> 'Идентификатор валюты',
	'locktime'						=> 'Время начала блокировки',
	'lockuser'						=> 'Пользователь заблокировавший страницу',
	'label-section-description'     => 'Описание раздела',
	"eshop-order-currency-exchange_rate"	=> "Курс обмена",
	"eshop-order-currency-total"		=> "Сумма в валюте заказа",
	"date-empty-field"				=> "Никогда",


	'object-type-users-avatar'			=>	'Аватара',
	'object-type-blogs20-blog'			=>	'Блог 2.0',
	'object-type-eshop-currency'			=>	'Валюта в интернет магазине',
	'object-type-blogs20-comment'			=>	'Комментарий блога 2.0',
	'object-type-blogs20-post'			=>	'Пост блога 2.0',
	'object-type-status_stranicy'			=>	'Статус страницы',
	'object-type-webforms-page'			=>	'Страница с формой обратной связи',
	'hierarchy-type-users-avatar'			=>	'Аватар пользователя',
	'hierarchy-type-blogs20-post'			=>	'Пост блога',
	'hierarchy-type-eshop-currency'			=>	'Валюта в интернет магазине',
	'hierarchy-type-webforms-page'			=>	'Страница с формой обратной связи',
	'field-body'			=>	'Текст сообщения',
	'field-userpic'			=>	'Загрузить свой',
	'field-picture'			=>	'Картинка',
	'field-is_hidden'			=>	'Скрытая',
	'field-forced_subscribers'			=>	'Принудительно подписанные пользователи:',
	'field-msg_date'			=>	'Дата сообщения',
	'field-short_body'			=>	'Краткий текст сообщения',
	'field-new_relation'			=>	'Ссылка на новость',
	'field-publish_status_id'			=>	'id статуса',
	'field-locktime'			=>	'Время блокировки',
	'field-lockuser'			=>	'Блокирующий пользователь',
	'field-last_message'			=>	'Последнее сообщение',
	'field-friendlist'			=>	'Список друзей',
	'field-only_for_friends'			=>	'Видимо только друзьям',
	'field-user_settings_data'			=>	'Настройки пользователя',
	'field-opinion'			=>	'Как вам сайт?',
	'fields-group-userpic'			=>	'Юзерпик',
	'fields-group-auto_reply'			=>	'Автоответ',
	'fields-group-svojstva_statusa_stranicy'			=>	'Свойства статуса страницы',
	'fields-group-locks'			=>	'Блокировка',
	'fields-group-privacy'			=>	'Настройки отображения',
	'fields-group-osnovnoe'			=>	'Основное',
	'object-supervajzery'			=>	'Супервайзеры',
	'object-zaregistrirovannye_pol_zovateli'			=>	'Зарегистрированные пользователи',

	'js-trash-confirm-text'  => 'После очистки эти элементы нельзя будет восстановить.',
	'js-trash-confirm-title' => 'Очистить корзину?',
	'js-trash-confirm-ok' => 'Очистить',
	'js-trash-confirm-cancel' => 'Не очищать',
	
	'fields-group-svojstva_publikacii' => 'Актуальность публикации',
	'field-expiration_date' => 'Дата снятия с публикации',
	'field-notification_date' => 'Дата уведомления',
	'field-publish_status' => 'Статус публикации',

	'field-global-discount-size'		=> 'Сумма глобальной скидки',
	'field-eshop_order_currency'		=> 'Валюта в интернет магазине',
	'field-currency_exchange_rate'		=> 'Курс обмена',
	'field-eshop_order_currency_total'	=> 'Сумма в валюте заказа',
	'field-sending_time'				=> 'Дата отправки',

	'js-trash-confirm-title'			=> 'Очистка корзины',
	'js-trash-confirm-text'				=> 'Вы собираетесь очистить корзину. Это означает, что все страницы в корзине будут безвозвратно удалены.',
	'js-trash-confirm-cancel'			=> 'Отменить',
	'js-trash-confirm-ok'				=> 'Очистить',
	'js-trash-restore'					=> 'Восстановить',
	'eshop-global-discount-proc'		=> 'Процент глобальной скидки',

	'js-type-edit-edit'					=> 'изменить',
	'js-type-edit-remove'				=> 'удалить',
	'js-type-edit-add_group'			=> 'Добавить группу',
	'js-type-edit-add_field'			=> 'Добавить поле',
	'js-type-edit-title'				=> 'Название',
	'js-type-edit-name'					=> 'Идентификатор',
	'js-type-edit-tip'					=> 'Подсказка',
	'js-type-edit-type'					=> 'Тип',
	'js-type-edit-restriction'			=> 'Формат значения',
	'js-type-edit-guide'				=> 'Справочник',
	'js-type-edit-visible'				=> 'Видимое',
	'js-type-edit-required'				=> 'Обязательное',
	'js-type-edit-indexable'			=> 'Индексируемое',
	'js-type-edit-filterable'			=> 'Фильтруемое',
	'js-type-edit-saving'				=> 'Сохранение',
	'js-type-edit-new_group'			=> 'Новая группа',
	'js-type-edit-new_field'			=> 'Новое поле',
	'js-type-edit-confirm_title'        => 'Подтверждение удаления',
	'js-type-edit-confirm_text'			=> 'Если вы уверены, нажмите "Удалить" (действие необратимо).',

	
	
	'fields-group-currency-props'		=> 'Свойства валюты',
	'field-use_in_eshop'				=> 'Использовать в интернет-магазине',
	'field-eshop_currency_letter_code'	=> 'Код валюты',
	'field-eshop_currency_exchange_rate'	=> 'Курс обмена',
	'field-eshop-currency-symbol'				=> 'Сокращенное название валюты',
	'field-photo-s'						=> 'Фото',
	'label-field-restriction-id'		=> 'Проверка значений',
	'label-delete-time'					=> 'Время удаления',
	
	'field-strana_prozhivaniya'			=> 'Страна проживания',
	'field-gorod_prozhivaniya'			=> 'Город проживания',
	'field-adres_prozhivaniya'				=> 'Адрес проживания',
	
	'object-currency-australia-dollars' => 'Австралийский доллар',
	'object-currency-uk-pound' => 'Фунт стерлингов Соединенного королевства',
	'object-currency-belarus-rubles' => 'Белорусский рубль',
	'object-currency-us-dollar' => 'Доллар США',
	'object-currency-euro' => 'Евро',
	'object-currency-tenge' => 'Казахский тенге',
	'object-currency-can-dollar' => 'Канадский доллар',
	'object-currency-ukr-grivna' => 'Украинская гривна',
	'object-currency-rus-rubles' => 'Российский рубль',
	
	'field-restriction-email'			=> 'E-mail',
	'field-restriction-http-url'		=> 'Web-адрес',
	'field-restriction-system-domain'	=> 'Домен системы',
	'field-restriction-discount'		=> 'Размер скидки',
	'field-restriction-object-type'		=> 'Тип данных',

	'restriction-error-common'			=> 'Неверное значение',
	'restriction-error-discount-size'	=> 'Размер скидки должен находиться в диапозоне от 0 до 100%',
	'restriction-error-domain-id'		=> 'Домена с таким id нет в системе',
	'restriction-error-email'			=> 'Неверный e-mail',
	'restriction-error-object-type'		=> 'Должен быть передан существующий тип данных',
	
	'error-value-required'				=> 'Поле "%s" обязательно должно быть заполнено'
);


	class ulangStream extends umiBaseStream {
		protected $scheme = "ulang", $prop_name = NULL;
		protected static $i18nCache = Array();

		public function stream_open($path, $mode, $options, $opened_path) {
			static $cache = array();
			$info = parse_url($path);
			
			$path = trim(getArrayKey($info, 'host') . getArrayKey($info, 'path'), "/");
			$buffer = outputBuffer::current();
			
			if(substr($path, -5, 5) == ':file') {
				$dtdContent = $this->getExternalDTD(substr($path, 0, strlen($path) - 5));
				return $this->setData($dtdContent);
			}
			
			if(strpos(getArrayKey($info, 'query'), 'js') !== false) {
				$mode = 'js';
			} else if(strpos($path, 'js') !== false) {
				$mode = 'js';
				$path = substr($path, 0, strlen($path) - 3);
			} else $mode = 'dtd';
			
			if($mode == 'js') {
				$buffer->contentType('text/javascript');
				$data = $this->generateJavaScriptLabels($path);
				return $this->setData($data);
			}
			
			if(isset($cache[$path])) {
				$data = $cache[$path];
			} else {
				$i18nMixed = self::loadI18NFiles($path);
				$data = $cache[$path] = $this->translateToDTD($i18nMixed);
			}
			return $this->setData($data);
		}

		protected function translateToDTD($phrases) {
			$dtd = "<!ENTITY quote '&#34;'>\n";
			$dtd .= "<!ENTITY nbsp '&#160;'>\n";
			$dtd .= "<!ENTITY middot '&#183;'>\n";
			$dtd .= "<!ENTITY reg '&#174;'>\n";
			$dtd .= "<!ENTITY copy '&#169;'>\n";
			$dtd .= "<!ENTITY raquo '&#187;'>\n";
			$dtd .= "<!ENTITY laquo '&#171;'>\n";
			
			$request_uri = getServer('REQUEST_URI');
			$request_uri = htmlspecialchars($request_uri);

			foreach($phrases as $ref => $phrase) {
				if($this->isRestrictedRef($ref)) continue;
				$phrase = $this->protectEntityValue($phrase);
				$dtd .= "<!ENTITY {$ref} \"{$phrase}\">\n";
			}

			return $dtd;
		}
		
		protected function isRestrictedRef($ref) {
			$arr = Array('field-', 'object-type-', 'hierarchy-type-', 'fields-group-', 'field-type-');
			
			for($i = 0; $i < sizeof($arr); $i++) {
				if(bytes_substr($ref, 0, bytes_strlen($arr[$i])) == $arr[$i]) {
					return true;
				}
			}
			return false;
		}

		protected function protectEntityValue($val) {
			$from = array('&', '"', '%');
			$to = array('&amp;', '&quote;', '&#037;');

			$val = str_replace($from, $to, $val);

			return $val;
		}

		protected static function parseLangsPath($path) {
			$protocol = "ulang://";
			if(substr($path, 0, strlen($protocol)) == $protocol) {
				$path = substr($path, strlen($protocol));
			}
			$path = trim($path, "/");
			return split("\/", $path);
		}

		protected static function loadI18NFiles($path) {
global $i18n;
			static $current_module = "content", $c = 0;

			if(!$current_module) {
				$controller = cmsController::getInstance();
				$current_module = $controller->getCurrentModule();
			}

			$i18nCache = self::$i18nCache;

			$require_list = self::parseLangsPath($path);

			$lang_prefix = self::getLangPrefix();

			$i18nMixed = Array();

			if(!in_array($current_module, $require_list)) {
				$require_list[] = $current_module;
			}

			$sz = sizeof($require_list);
			for($i = 0; $i < $sz; $i++) {
				$require_name = $require_list[$i];
				
				if($require_name == false) continue;

				$filename_primary = "i18n." . $lang_prefix . ".php";
				$filename_secondary = "i18n.php";

				$folder = ($require_name == "common") ? "/classes/modules/" : "/classes/modules/" . $require_name . "/";
				$folder = CURRENT_WORKING_DIR . $folder;

				$path_primary = $folder . $filename_primary;
				$path_secondary = $folder . $filename_secondary;

				if(array_key_exists($require_name, $i18nCache)) {
					$i18n = $i18nCache[$require_name];
				} else {
					if(file_exists($path_primary)) {
						include $path_primary;
					} else if (file_exists($path_secondary)) {
						include $path_secondary;
					}
				}

				if(isset($i18n) && is_array($i18n)) {
					$i18nCache[$require_name] = $i18n;
					$i18nMixed = $i18n + $i18nMixed;
					unset($i18n);
				}
			}
			self::$i18nCache = $i18nCache;
			
			return $i18nMixed;
		}

		public static function getLabel($label, $path = false, $args = null) {
			static $cache = Array();
			static $langPrefix = false;
			if($langPrefix === false) {
				$langPrefix = self::getLangPrefix();
			}
			
			$lang_path = ($path == false) ? "common/data" : $path;

			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = &$i18nMixed;
			}
			if(isset($i18nMixed[$label])) {
				$res = $i18nMixed[$label];
			} else {
				$res = "{$label}";
			}

			if(is_array($args) && sizeof($args) > 2) {
				$res = vsprintf($res, array_slice($args, 2));
			}

			return $res;
		}

		public static function getI18n($key, $pattern = "") {
			static $cache = Array();
			
			if(!$key) {
				return $key;
			}
			
			$lang_path = "common/data";
			$prefix = "i18n::";
			
			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = $i18nMixed;
			}

			$result = NULL;
			foreach($i18nMixed as $i => $v) {
				if($v == $key) {
					if($pattern) {
						if(substr($i, 0, strlen($pattern)) == $pattern) {
							$result = $prefix . $i;
							break;
						}
					} else {
						$result = $prefix . $i;
						break;
					}
				}
			}

			if(!is_null($result)) {
				$allowedPrefixes = Array(
					'object-type-',
					'hierarchy-type-',
					'field-',
					'fields-group-',
					'field-type-',
					'object-'
				);
				$allowed = false;
				$tmp_result = str_replace("i18n::", "", $pattern);
				foreach($allowedPrefixes as $pattern) {
					$pattern = $pattern;

					if(substr($tmp_result, 0, strlen($pattern)) == $tmp_result) {
						$allowed = true;
					}
				}
				if($allowed == false) {
					return NULL;
				}
			}
			return $result;
		}
		
		public static function getLangPrefix() { return "ru";
			static $ilang;
			if(!is_null($ilang)) {
				return $ilang;
			}
			
			$cmsController = cmsController::getInstance();
			$prefix = $cmsController->getCurrentLang()->getPrefix();
			
			if($cmsController->getCurrentMode() != "admin" && !defined('VIA_HTTP_SCHEME')) {
				return $ilang = checkInterfaceLang($prefix);
			}
			
			if(!is_null($ilang = getArrayKey($_POST, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			if(!is_null($ilang = getArrayKey($_GET, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			
			if(!is_null($ilang = getCookie('ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}
			
			return $ilang = checkInterfaceLang($prefix);
		}
		
		public function __construct() {
			parent::__construct();
		}
		
		public function __destruct() {}
		
		protected function generateJavaScriptLabels($path) {
			$i18n = self::loadI18NFiles($path);
			
			$result = <<<INITJS
function getLabel(key, str) {if(setLabel.langLabels[key]) {var res = setLabel.langLabels[key];if(str) {res = res.replace("%s", str);}return res;} else {return "[" + key + "]";}}
function setLabel(key, label) {setLabel.langLabels[key] = label;}setLabel.langLabels = new Array();


INITJS;
			foreach($i18n as $i => $v) {
				if(substr($i, 0, 3) == "js-" || strpos($i, "module-") === 0) {
					$i = self::filterOutputString($i);
					$v = self::filterOutputString($v);
					$result .= "setLabel('{$i}', '{$v}');\n";
				}
			}
			umiBaseStream::$allowTimeMark = false;
			return $result;
		}
		
		protected function filterOutputString($string) {
			$from = array("\r\n", "\n", "'");
			$to = array("\\r\\n", "\\n", "\\'");
			$string = str_replace($from, $to, $string);
			return $string;
		}

		protected function getExternalDTD($path) {
			$cmsController = cmsController::getInstance();
			$prefix = $cmsController->getCurrentLang()->getPrefix();
			
			$info = pathinfo(SYS_XSLT_PATH . $path);
			$left = getArrayKey($info, 'dirname') . '/' . getArrayKey($info, 'filename');
			$right = getArrayKey($info, 'extension');
			
			$primaryPath = $left . '.' . $prefix . '.' . $right;
			$secondaryPath = $left . '.' . $right;

			if(is_file($primaryPath)) {
				return file_get_contents($primaryPath);
			}
			
			if(is_file($secondaryPath)) {
				return file_get_contents($secondaryPath);
			}
			
			return '';
		}
	};


	class umiObjectProxy {
		protected $object;
		
		protected function __construct(umiObject $object) {
			$this->object = $object;
		}
		
		public function getId() {
			return $this->object->getId();
		}
		
		public function setName($name) {
			$this->object->setName($name);
		}
		
		public function getName() {
			return $this->object->getName();
		}
		
		public function setValue($propName, $value) {
			return $this->object->setValue($propName, $value);
		}
		
		public function getValue($propName) {
			return $this->object->getValue($propName);
		}
		
		public function isFilled() {
			return $this->object->isFilled();
		}
		
		public function getObject() {
			return $this->object;
		}
		
		public function commit() {
			return $this->object->commit();
		}
		
		public function delete() {
			$objects = umiObjectsCollection::getInstance();
			return $objects->delObject($this->getId());
		}
		
		public function __get($prop) {
			switch($prop) {
				case 'id':		return $this->getId();
				case 'name':	return $this->getName();
				default:		return $this->getValue($prop);
			}
		}
		
		public function __set($prop, $value) {
			switch($prop) {
				case 'name':	return $this->setName($value);
				default:		return $this->setValue($prop, $value);
			}
		}
		
		public function __destruct() {
			$this->object->commit();
		}
	};


	interface iUmiObjectsExpiration {
		public function run();
		
		public function set($objectId, $expiration = false);
		public function clear($objectId);
	};


	class umiObjectsExpiration extends singleton implements iSingleton, iUmiObjectsExpiration {
		protected $defaultExpires = 86400;
		
		protected function __construct() {
			
		}
		
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}
		
		public function run() {
			$time = time();
			
			$sql = <<<SQL
DELETE FROM `cms3_objects`
	WHERE `id` = (
		SELECT `obj_id`
			FROM `cms3_objects_expiration`
				WHERE (`entrytime` + `expire`) >= '{$time}'
	)
SQL;
			l_mysql_query($sql);
		}
		
		public function set($objectId, $expires = false) {
			if($expires == false) {
				$expires = $this->defaultExpires;
			}
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();
			
			$sql = <<<SQL
REPLACE INTO `cms3_objects_expiration`
	(`obj_id`, `entrytime`, `expire`)
		VALUES ('{$objectId}', '{$time}', '{$expires}')
SQL;
			l_mysql_query($sql);
			
		}
		
		public function clear($objectId) {
			$objectId = (int) $objectId;
			
			$sql = <<<SQL
DELETE FROM `cms3_objects_expiration`
	WHERE `obj_id` = '{$objectId}'
SQL;
			l_mysql_query($sql);
		}
	};


	class HTTPOutputBuffer extends outputBuffer {
		protected
			$charset		= 'utf-8',
			$contentType	= 'text/html',
			$headers		= array(),
			$headersSended	= false,
			$status			= '200 Ok',
			$options		= array(
				'compression' => true,
				'quick-edit' => true,
				'generation-time' => true,
				'send-stat-id' => true
			);
		
		public function __construct() {
			parent::__construct();
			session_start();
			$this->checkHTTPAuth();
		}
		
		public function send() {
			$this->sendHeaders();
			echo $this->buffer;
			$this->clear();
		}
		
		public function status($status = false) {
			if($status) $this->status = $status;
			return $this->status;
		}
		
		public function charset($charset = false) {
			if($charset) $this->charset = $charset;
			return $this->charset;
		}
		
		public function contentType($contentType = false) {
			if($contentType) $this->contentType = $contentType;
			return $this->contentType;
		}
		
		public function getHTTPRequestBody() {
			$putdata = fopen("php://input", "r");
			$data = "";

			while (!feof($putdata)) {
					$data .= fread($putdata, 1024);
			}
			fclose($putdata);

			return $data;
		}
		
		public function sendHeaders() {
			if($this->headersSended) {
				return true;
			} else if (headers_sent()) {
				return false;
			}
			
			if(CALC_LAST_MODIFIED) $this->sendLastModified();
			$this->sendStatusHeader();
			
			$this->sendDefaultHeaders();
			
			foreach($this->headers as $header => $value) {
				$this->sendHeader($header, $value);
			}
			$this->headersSended = true;
		}
		
		public function end() {
			if(getArrayKey($this->options, 'quick-edit')) {
				templater::prepareQuickEdit();
			}
			
			if(CALC_E_TAG) $this->sendETag();
			$this->push($this->getCallTime());
			$this->send();
			exit;
		}
		
		public function option($key, $value = null) {
			if(is_null($value)) {
				return isset($this->options[$key]) ? $this->options[$key] : null;
			} else $this->options[$key] = $value;
		}
		
		public function redirect($url, $status = '301 Moved Permanently') {
			$this->status($status);
			$this->header('Location', $url);
			$this->end();
		}
		
		public function header($name, $value = false) {
			if($value === false) {
				unset($this->headers[$name]);
				return NULL;
			} else {
				return $this->headers[$name] = $value;
			}
		}
		
		protected function checkHTTPAuth() {
			$login = getServer('PHP_AUTH_USER');
			$password = getServer('PHP_AUTH_PW');
			
			
			if($login && $password) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isAuth() == false) {
					if($user = $permissions->checkLogin($login, $password)) {
						$permissions->loginAsUser($user->id);
					} else {
						$this->clear();
						$this->status('401 Unauthorized');
						$this->header('WWW-Authenticate: Basic realm="UMI.CMS"');
						$this->push('HTTP Authenticate failed');
						$this->end();
					}
				}
			}
		}
		
		protected function sendStatusHeader() {
			header("HTTP/1.1 " . $this->status);
			header("Status: " . $this->status);
		}
		
		protected function sendDefaultHeaders() {
			$this->sendHeader('Content-type', $this->contentType . '; charset=' . $this->charset);
			$this->sendHeader('Content-length', $this->length());
			$this->sendHeader('Date', (gmdate("D, d M Y H:i:s") . " GMT"));
			
			if(stristr(getServer('HTTP_USER_AGENT'), 'msie')) {
				$this->sendHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
				$this->sendHeader('Pragma', 'no-cache');
        		$this->sendHeader('Expires', gmdate('D, d M Y H:i:s') . ' GMT');
        		$this->sendHeader('X-XSS-Protection', '0');
			} else {
				$this->sendHeader('Cache-Control', 'max-age=3600, private, must-revalidate');
			}
			
			if($this->option('send-stat-id')) {
				if (!getCookie('stat_id')) {
					setcookie('stat_id', session_id(), strtotime('+10 years'), "/");
				}
			}
		}
		
		protected function sendHeader($header, $value) {
			header("{$header}: {$value}");
		}
		
		protected function sendETag() {
			$this->sendHeader('E-tag', sha1($this->content()));
		}
		
		protected function sendLastModified() {
			$hierarchy = umiHierarchy::getInstance();
			$updateTime = $hierarchy->getElementsLastUpdateTime();
			if($updateTime) {
				$this->sendHeader('Last-Modified', (gmdate('D, d M Y H:i:s', $updateTime) . ' GMT'));
				$this->sendHeader('Expires', (gmdate('D, d M Y H:i:s', time() + 24 * 3600) . ' GMT'));
				
				if(function_exists("apache_request_headers")) {
					$request = apache_request_headers();
					if(isset($request['If-Modified-Since']) && (strtotime($request['If-Modified-Since']) >= $updateTime)) {
						$this->status('304 Not Modified');
						$this->sendHeader('Connection',  'close');
					}
				}
			}
		}
		
		protected function getCallTime() {
			$generationTime = round(microtime(true) - $this->invokeTime, 6);
			if(!$this->option('generation-time')) {
				return;
			}
			
			switch($this->contentType) {
				case 'text/html':
				case 'text/xml':
					return "<!-- This page generated in {$generationTime} secs -->";
				
				case 'application/javascript':
				case 'text/javascript':
					return "/* This page generated in {$generationTime} secs */";

				default: "";
			}
		}
	};


	class CLIOutputBuffer extends outputBuffer {
		public function send() {
			echo $this->buffer;
			$this->clear();
		}
	};


	abstract class objectProxyHelper {
		/**
			* �������� ������� ������ ������ �� id ��� �������-����
			* @param Integer $objectId id ������� ���� ������
			* @return String ������� ������ ������
		*/		
		public static function getClassPrefixByType($objectId) {
			static $cache = array();
			if(isset($cache[$objectId])) {
				return $cache[$objectId];
			}
			$classPrefix = '';
			
			$object = selector::get('object')->id($objectId);
			if($object instanceof iUmiObject) {
				if($object->class_name) {
					$classPrefix = $object->class_name;
				}
			} else {
				throw new coreException("Can't get class name prefix from object #{$objectId}");
			}
			
			return $cache[$objectId] = $classPrefix;
		}


		/**
			* ���������� ����, ���������� ����� ������
			* @param String $classPrefix ������� ������ ������
		*/
		public static function includeClass($classPath, $classPrefix) {
			static $included = array();

			if(in_array($classPrefix, $included)) {
				return;
			} else {
				$included[] = $classPrefix;
			}
			
			$config = mainConfiguration::getInstance();
			$filePath = $config->includeParam('system.default-module') . $classPath . $classPrefix . '.php';
			
			if(is_file($filePath) == false) {
				throw new coreException("Required source file {$filePath} is not found");
			}
			
			require $filePath;
		}
	};


	class selector implements IteratorAggregate {
		protected
			$mode, $permissions = null, $limit, $offset,
			$types = array(), $hierarchy = null,
			$whereFieldProps = array(), $whereSysProps = array(),
			$orderSysProps = array(), $orderFieldProps = array(),
			$executor, $result = null, $length = null,
			$options = array();

		protected static
			$modes = array('objects', 'pages'),
			$sysPagesWhereFields = array('name', 'owner', 'domain', 'lang', 'is_deleted',
				'is_active', 'is_visible', 'updatetime', 'is_default', 'template_id', '*'),
			$sysObjectsWhereFields = array('name', 'owner', '*'),
			$sysOrderFields = array('name', 'ord', 'rand', 'updatetime', 'id');
		
		public static function get($requestedType) {
			return new selectorGetter($requestedType);
		}

		public function __construct($mode) {
			$this->setMode($mode);
		}
		
		public function types($typeClass = false) {
			$this->checkExecuted();
			if($typeClass === false)
				return $this->types;
			else
				return $this->types[] = new selectorType($typeClass, $this);
		}
		
		public function where($fieldName) {
			$this->checkExecuted();
			if($fieldName == 'hierarchy') {
				if($this->mode == 'objects')
					throw new selectorException("Hierarchy filter is not suitable for \"objects\" selector mode");
				return $this->hierarchy = new selectorWhereHierarchy;
			}
			
			if($fieldName == 'permissions') {
				if($this->mode == 'objects')
					throw new selectorException("Permissions filter is not suitable for \"objects\" selector mode");
				if(is_null($this->permissions)) $this->permissions = new selectorWherePermissions;
				return $this->permissions;
			}
			
			if(in_array($fieldName, ($this->mode == 'pages') ? self::$sysPagesWhereFields : self::$sysObjectsWhereFields)) {
				return $this->whereSysProps[$fieldName] = new selectorWhereSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);
				if($fieldId) {
					return $this->whereFieldProps[] = new selectorWhereFieldProp($fieldId);
				} else {
					throw new selectorException("Field \"{$fieldName}\" is not presented in selected object types");
				}
			}
		}
		
		public function order($fieldName) {
			$this->checkExecuted();
			if(in_array($fieldName, self::$sysOrderFields)) {
				return $this->orderSysProps[] = new selectorOrderSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);
				if($fieldId) {
					return $this->orderFieldProps[] = new selectorOrderFieldProp($fieldId);
				} else {
					throw new selectorException("Field \"{$fieldName}\" is not presented in selected object types");
				}
			}
		}
		
		public function limit($limit, $offset = 0) {
			$this->checkExecuted();
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}
		
		public function result() {
			if(is_null($this->result)) {
				if($this->mode == 'pages') {
					if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
					if(is_null($this->permissions)) $this->where('permissions');
				}
				$this->result = $this->executor()->result();
				$this->length = $this->executor()->length();
			}
			$this->unloadExecutor();
			return $this->result;
		}
		
		public function length() {
			if(is_null($this->length)) {
				if($this->mode == 'pages' && is_null($this->permissions)) {
					$this->where('permissions');
				}
				$this->result = $this->executor()->result();
				$this->length = $this->executor()->length();
			}
			$this->unloadExecutor();
			return $this->length;
		}
		
		public function option($name, $value = null) {
			$this->checkExecuted();
			$allowedOptions = array('root', 'exclude-nested');
			if(in_array($name, $allowedOptions)) {
				if(is_null($value) == false) {
					$this->options[$name] = $value;
				}
				return isset($this->options[$name]) ? $this->options[$name] : null;
			} else throw new selectorException("Unkown option \"{$name}\"");
		}
		
		public function flush() {
			$this->result = null;
			$this->length = null;
		}
		
		public function __get($prop) {
			switch($prop) {
				case 'length':
				case 'total':
					return $this->length();
				case 'result':
					return $this->result();
				case 'first':
					return (sizeof($this->result())) ? $this->result[0] : null;
				case 'last':
					return (sizeof($this->result())) ? $this->result[sizeof($this->result) - 1] : null;
			}
			
			$allowedProps = array('mode', 'offset', 'limit', 'whereFieldProps', 'orderFieldProps', 
				'whereSysProps', 'orderSysProps', 'types', 'permissions', 'hierarchy');
			
			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}
		
		public function getIterator() {
			$this->result();
			return new ArrayIterator($this->result);
		}
		
		public function query() {
			if($this->mode == 'pages') {
				if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
				if(is_null($this->permissions)) $this->where('permissions');
			}
			
			return $this->executor()->query();
		}
		
		public function searchField($fieldName) {
			foreach($this->types as $type) {
				$fieldId = $type->getFieldId($fieldName);
				if($fieldId) return $fieldId;
			}
			if($this->mode == 'pages') {
				$types = umiObjectTypesCollection::getInstance();
				$type = $types->getType(3);
				$fieldId = $type->getFieldId($fieldName);
				if($fieldId) return $fieldId;
			}
		}
		
		protected function checkExecuted() {
			if(!is_null($this->result) || !is_null($this->length)) {
				throw new selectorException("Selector has been already executed. You should create new one or use selector::flush() method instead.");
			}
		}
		
		protected function executor() {
			if(!$this->executor) $this->executor = new selectorExecutor($this);
			return $this->executor;
		}
		
		protected function unloadExecutor() {
			if(!is_null($this->length) && !is_null($this->result)) {
				unset($this->executor);
			}
		}
		
		protected function setMode($mode) {
			if(in_array($mode, self::$modes)) {
				$this->mode = $mode;
				if($mode == 'pages') {
					$this->setDefaultPagesWhere();
				}
			} else {
				throw new selectorException(
					"This mode \"{$mode}\" is not supported, choose one of these: " . implode(', ', self::$modes)
				);
			}
		}
		
		protected function setDefaultPagesWhere () {
			$cmsController = cmsController::getInstance();
			$this->where('domain')->equals($cmsController->getCurrentDomain());
			$this->where('lang')->equals($cmsController->getCurrentLang());
			$this->where('is_deleted')->equals(0);
			
			if($cmsController->getCurrentMode() != 'admin') {
				$this->where('is_active')->equals(1);
			}
		}
	};


	abstract class selectorWhereProp {
		protected $value, $mode,
		$modes = array('equals', 'notequals', 'ilike', 'like', 'more', 'eqmore', 'less', 'eqless', 'between', 'isnull', 'isnotnull');
		
		public function __call($method, $args) {
			$method = strtolower($method);
			if(in_array($method, $this->modes)) {
				$value = sizeof($args) ? $args[0] : null;
				
				if($value instanceof iUmiEntinty) {
					$value = $value->getId();
				}
				
				if(isset($this->fieldId)) {
					$field = selector::get('field')->id($this->fieldId);
					if($restrictionId = $field->getRestrictionId()) {
						$restriction = baseRestriction::get($restrictionId);
						if($restriction instanceof iNormalizeInRestriction) {
							$value = $restriction->normalizeIn($value);
						}
					}
					
					if(is_numeric($value)) {
						$value = (double) $value;
					}
					
					if($field->getDataType() == 'relation' && is_string($value)) {
						if($guideId = $field->getGuideId()) {
							$sel = new selector('objects');
							$sel->types('object-type')->id($guideId);
							$sel->where('*')->ilike($value);
							
							$length = sizeof($sel->result); //fast length
							if($length > 0 && $length < 100) {
								$value = $sel->result;
							}
						}
					}
					
					if($field->getDataType() == 'date' && is_string($value)) {
						$date = new umiDate;
						$date->setDateByString(trim($value, ' %'));
						$value = $date->getDateTimeStamp();
					}
				}
				
				$this->value = $value;
				$this->mode = $method;
			} else {
				throw new selectorException("This property doesn't support \"{$method}\" method");
			}
		}
		
		public function between($start, $end) {
			return $this->__call('between', array(array($start, $end)));
		}
		
		public function __get($prop) { return $this->$prop; }
	};

	class selectorWhereSysProp extends selectorWhereProp {
		protected $name;
		
		public function __construct($name) {
			$this->name = $name;
		}
	};

	class selectorWhereFieldProp extends selectorWhereProp {
		protected $fieldId;
		
		public function __construct($fieldId) {
			$this->fieldId = $fieldId;
		}
	};
	
	class selectorWhereHierarchy {
		protected $elementId, $level = 1, $selfLevel;
		
		public function page($elementId)  {
			$hierarchy = umiHierarchy::getInstance();
			if(is_numeric($elementId) == false) {
				$elementId = $hierarchy->getIdByPath($elementId);
			}

			if($elementId !== false) {
				$this->elementId = (int) $elementId;
			}
			return $this;
		}
		
		public function childs($level = 1) {
			if(is_null($this->selfLevel)) {
				$sql = "SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$this->elementId}";
				$result = l_mysql_query($sql);
				list($this->selfLevel) = mysql_fetch_row($result);
			}
			$this->level = ($level == 0) ? 0 : (int) $level + (int) $this->selfLevel;
		}
		
		public function __get($prop) { return $this->$prop; }
	};
	
	class selectorWherePermissions {
		protected $level = 0x1, $owners = array(), $isSv;
		
		public function __construct() {
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			
			$this->isSv = $permissions->isSv();
			if(!$this->isSv) {
				$this->owners = array($userId);
			}
		}
		
		public function level($level) {
			$this->level = (int) $level;
		}
		
		public function owners($owners) {
			if(is_array($owners)) {
				foreach($owners as $owner) $this->owners($owner);
			} else {
				$this->addOwner($owners);
			}
			return $this;
		}
		
		public function __get($prop) { return $this->$prop; }
		
		protected function addOwner($ownerId) {
			if(in_array($ownerId, $this->owners)) return;
			//if($this->isSv) return;
			$permissions = permissionsCollection::getInstance();
			
			
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($ownerId);
			
			if($object instanceof iUmiObject) {
				if($permissions->isSv($ownerId)) {
					$this->isSv = true;
					return;
				}
				
				$this->owners[] = $ownerId;
				if($object->groups) {
					$this->owners = array_merge($this->owners, $object->groups);
				}
			}
		}
	};


	class selectorType {
		protected $typeClass, $objectType, $hierarchyType, $selector;
		protected static $typeClasses = array('object-type', 'hierarchy-type');
		
		public function __construct($typeClass, $selector) {
			$this->setTypeClass($typeClass);
			$this->selector = $selector;
		}
		
		public function name($module, $method) {
			if(!$method && $module == 'content') $method = 'page';
			
			switch($this->typeClass) {
				case 'object-type': {
					$objectTypes = umiObjectTypesCollection::getInstance();
					$objectTypeId = $objectTypes->getBaseType($module, $method);
					return $this->setObjectType($objectTypes->getType($objectTypeId));
				}
				
				case 'hierarchy-type': {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					$hierarchyType = $hierarchyTypes->getTypeByName($module, $method);
					return $this->setHierarchyType($hierarchyType);
				}
			}
		}
		
		public function id($id) {
			if(is_array($id)) {
				$result = null;
				foreach($id as $iid) {
					$this->selector->types($this->typeClass)->id($iid);
				}
				return $result;
			}
			
			switch($this->typeClass) {
				case 'object-type': {
					$objectTypes = umiObjectTypesCollection::getInstance();
					return $this->setObjectType($objectTypes->getType($id));
				}
				
				case 'hierarchy-type': {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					return $this->setHierarchyType($hierarchyTypes->getType($id));
				}
			}
		}
		
		public function setTypeClass($typeClass) {
			if(in_array($typeClass, self::$typeClasses)) {
				$this->typeClass = $typeClass;
			} else {
				throw new selectorException(
					"Unkown type class \"{$typeClass}\". These types are only supported: " . implode(", ", self::$typeClasses)
				);
			}
		}
		
		public function getFieldId($fieldName) {
			if(is_null($this->objectType)) {
				if(is_null($this->hierarchyType)) {
					throw new selectorException("Object and hierarchy type prop can't be empty both");
				}
				$objectTypes = umiObjectTypesCollection::getInstance();
				$objectTypeId = $objectTypes->getTypeByHierarchyTypeId($this->hierarchyType->getId());
				if($objectType = $objectTypes->getType($objectTypeId)) {
					$this->setObjectType($objectType);
				} else {
					return false;
				}
			}
			return $this->objectType->getFieldId($fieldName);
		}
		
		public function __get($prop) {
			$allowedProps = array('objectType', 'hierarchyType');
			
			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}
		
		protected function setObjectType($objectType) {
			if($objectType instanceof iUmiObjectType) {
				$this->objectType = $objectType;
			} else {
				throw new selectorException("Wrong object type given");
			}
		}
		
		protected function setHierarchyType($hierarchyType) {
			if($hierarchyType instanceof iUmiHierarchyType) {
				$this->hierarchyType = $hierarchyType;
			} else {
				throw new selectorException("Wrong hierarchy type given");
			}
		}
	};


	abstract class selectorOrderField {
		protected $asc = true;
		
		public function asc() { $this->asc = true; }
		public function desc() { $this->asc = false; }
		
		public function __get($prop) { return $this->$prop; }
	};

	class selectorOrderFieldProp extends selectorOrderField {
		protected $fieldId;
		
		public function __construct($fieldId) {
			$this->fieldId = $fieldId;
		}
	};
	
	class selectorOrderSysProp extends selectorOrderField {
		protected $name;
		
		public function __construct($name) {
			$this->name = $name;
		}
	}


	class selectorExecutor {
		protected
			$selector,
			$queryColumns = array(),
			$queryTables = array(),
			$queryLimit = array(),
			$queryFields = array(),
			$queryOptions = array('SQL_CACHE'),
			
			$length = null;
		
		public function __construct(selector $selector) {
			$this->selector = $selector;
			$this->analyze();
		}
		
		public function query() {
			return $this->buildQuery('result');
		}
		
		
		public function result() {
			$sql = $this->buildQuery('result');
			
			if(defined('DEBUG_SQL_SELECTOR')) {
				echo $sql, "\n\n\n";
			}
			
			$result = l_mysql_query($sql);
			
			if(!DISABLE_CALC_FOUND_ROWS) {
				$countResult = l_mysql_query("SELECT FOUND_ROWS()", true);
				list($count) = mysql_fetch_row($countResult);
				mysql_free_result($countResult);
				$this->length = (int) $count;
			}
			
			if($this->selector->mode == 'objects') {
				$list = array();
				$objects = umiObjectsCollection::getInstance();
				while(list($objectId) = mysql_fetch_row($result)) {
					$object = $objects->getObject($objectId);
					if($object instanceof iUmiObject) {
						$list[] = $object;
					}
				}
				return $list;
			} else {
				$listIds = array(); $permissions = null;
				while(list($elementId) = $row = mysql_fetch_row($result)) {
					if(isset($row[1])) {
						if(is_null($permissions)) {
							$permissions = permissionsCollection::getInstance();
						}
						$permissions->pushElementPermissions($elementId, $row[1]);
					}
					$listIds[] = $elementId;
					
					
				}
				
				if($this->selector->option('exclude-nested')) {
					$listIds = $this->excludeNestedPages($listIds);
					$this->length = sizeof($listIds);
				}
				
				$hierarchy = umiHierarchy::getInstance(); $list = array();
				foreach($listIds as $elementId) {
					$element = $hierarchy->getElement($elementId);
					if($element instanceof iUmiHierarchyElement) {
						$list[] = $element;
					}
				}
				
				return $list;
			}
		}
		
		public function length() {
			if(!is_null($this->length)) {
				return $this->length;
			}
			
			$sql = $this->buildQuery('count');
			
			$result = l_mysql_query($sql);
			list($count) = mysql_fetch_row($result);
			return $this->length = (int) $count;
		}
		
		public static function getContentTableName(selector $selector, $fieldId) {
			if(!is_null($fieldId) && self::getFieldColumn($fieldId) == 'cnt') {
				return 'cms3_object_content_cnt';
			}
			
			$objectTypes = array();
			$hierarchyTypes = array();
			
			$types = $selector->types;
			foreach($types as $type) {
				if(is_null($type->objectType) == false) $objectTypes[] = $type->objectType->getId();
				
				if(is_null($type->hierarchyType) == false) {
					$hierarchyType = $type->hierarchyType;
					if($hierarchyType->getModule() == 'comments') continue;
					$hierarchyTypes[] = $hierarchyType->getId();
				}
			}
			
			if(sizeof($objectTypes)) {
				return umiBranch::getBranchedTableByTypeId(array_pop($objectTypes));
			}
			
			if(sizeof($hierarchyTypes)) {
				$hierarchyTypeId = array_pop($hierarchyTypes);
				if(umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId)) {
					return 'cms3_object_content_' . $hierarchyTypeId;
				}
			}
			
			return 'cms3_object_content';
		}
		
		protected function analyze() {
			$selector = $this->selector;
			switch($selector->mode) {
				case 'objects':
					$this->requireTable('o', 'cms3_objects');
					break;
				case 'pages':
					$this->requireTable('h', 'cms3_hierarchy');
					break;
			}
			$this->analyzeFields();
			$this->analyzeLimit();
		}
		
		protected function requireTable($alias, $tableName) {
			$this->queryTables[$alias] = $tableName;
		}
		
		protected function requireSysProp($propName) {
			$propTable = array();
			$propTable['name'] = array('o.name', 'table' => array('o', 'cms3_objects'));
			$propTable['owner'] = array('o.owner_id', 'table' => array('o', 'cms3_objects'));
			$propTable['domain'] = array('h.domain_id');
			$propTable['lang'] = array('h.lang_id');
			$propTable['is_deleted'] = array('h.is_deleted');
			$propTable['is_default'] = array('h.is_default');
			$propTable['is_visible'] = array('h.is_visible');
			$propTable['is_active'] = array('h.is_active');
			$propTable['domain'] = array('h.domain_id');
			$propTable['updatetime'] = array('h.updatetime');
			$propTable['rand'] = array('RAND()');
			$propTable['template_id'] = array('h.tpl_id');
			
			if($this->selector->mode == 'pages') {
				$propTable['ord'] = array('h.ord', 'table' => array('h', 'cms3_hierarchy'));
			}
			
			
			if($propName == 'id') {
				$propTable['id'] = array('o.id', 'table' => array('o', 'cms3_objects'));
			};
			
			if(isset($propTable[$propName])) {
				$info = $propTable[$propName];
				if(isset($info['table'])) {
					$this->requireTable($info['table'][0], $info['table'][1]);
				}
				return $info[0];
			} else {
				throw new selectorException("Not supported property \"{$propName}\"");
			}
		}
		
		protected function analyzeFields() {
			$selector = $this->selector;
			
			$selectorFields = array_merge($selector->whereFieldProps, $selector->orderFieldProps);
			$fields = array();
			foreach($selectorFields as $field) {
				$fields[] = $field->fieldId;
			}
			$fields = array_unique($fields);
			foreach($fields as $fieldId) {
				$tableName = self::getContentTableName($selector, $fieldId);
				
				$this->requireTable('oc_' . $fieldId, $tableName);
				$this->queryFields[] = $fieldId;
			}
			
			//TODO: Attach tables, required by sys props
			//$selectorSysProps = array_merge($selector->whereSysProps, $selector->orderSysProps);
		}
		
		protected function analyzeLimit() {
			$selector = $this->selector;
			
			if($selector->option('exclude-nested')) {
				return;
			}
			
			if($selector->limit || $selector->offset) {
				$this->queryLimit = array((int) $selector->limit, (int) $selector->offset);
			}
		}
		
		protected function buildQuery($mode) {
			if($mode == 'result') {
				if($this->selector->mode == 'objects') {
					$this->queryColumns = array('o.id');
				} else {
					$this->queryColumns = array('h.id');
				}
			} else {
				$this->queryColumns = ($this->selector->mode == 'objects') ? array('COUNT(o.id)') : array('COUNT(h.id)');
			}
			
			
			if($this->selector->option('root')) {
				return $this->buildRootQuery($mode);
			}
			
			$columnsSql = $this->buildColumns();
			$limitSql = $this->buildLimit();
			$orderSql = $this->buildOrder();
			$whereSql = $this->buildWhere();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			return <<<SQL
SELECT {$optionsSql} {$columnsSql}
	FROM {$tablesSql}
	{$whereSql}
	{$orderSql}
	{$limitSql}
SQL;
		}
		
		protected function buildOptions($mode) {
			$queryOptions = $this->queryOptions;
			
			if($mode == 'result') {
				if(!DISABLE_CALC_FOUND_ROWS) {
					$queryOptions[] = 'SQL_CALC_FOUND_ROWS';
				}
			}
			
			if(MAX_SELECTION_TABLE_JOINS > 0 && MAX_SELECTION_TABLE_JOINS > sizeof($this->queryTables)) {
				$queryOptions[] = 'STRAIGHT_JOIN';
			}
			
			return implode(' ', $queryOptions);
		}
		
		protected function buildColumns() { return implode(', ', $this->queryColumns); }
		
		protected function buildTables() {
			$tables = array();
			foreach($this->queryTables as $alias => $name) $tables[] = $name . ' ' . $alias;
			return implode(', ', $tables);
		}
		
		protected function buildLimit() {
			if(sizeof($this->queryLimit)) {
				return " LIMIT {$this->queryLimit[0]}, {$this->queryLimit[1]}";
			} else {
				return "";
			}
		}
		
		protected function buildWhere() {
			$sql = "";
			
			$conds = array();
			//Types
			$objectTypes = array(); $hierarchyTypes = array();
			foreach($this->selector->types as $type) {
				if(is_null($type->objectType) == false) $objectTypes[] = $type->objectType->getId();
				if(is_null($type->hierarchyType) == false) $hierarchyTypes[] = $type->hierarchyType->getId();
			}
			
			if(sizeof($objectTypes)) {
				$this->requireTable('o', 'cms3_objects');
				
				$typesCollection = umiObjectTypesCollection::getInstance();
				$subTypes = array();
				foreach($objectTypes as $objectTypeId) {
					$subTypes = array_merge($subTypes, $typesCollection->getChildClasses($objectTypeId));
				}
				
				$objectTypes = array_unique(array_merge($objectTypes, $subTypes));
				
				
				$conds[] = 'o.type_id IN (' . implode(', ', $objectTypes) . ')';
			}
			
			if(sizeof($hierarchyTypes)) {
				$hierarchyTypes = array_unique($hierarchyTypes);
				$conds[] = 'h.type_id IN (' . implode(', ', $hierarchyTypes) . ')';
			}
			
			if(sizeof($this->queryFields)) {
				$this->requireTable('o', 'cms3_objects');
			}
			
			//Field props
			foreach($this->queryFields as $fieldId) {
				$alias = 'oc_' . $fieldId;
				$valueSql = $this->buildWhereValue($fieldId);
				$subSql = "({$alias}.obj_id = o.id AND {$alias}.field_id = {$fieldId}{$valueSql})";
				$conds[] = $subSql;
			}
			
			//Sys props
			$sysProps = $this->selector->whereSysProps;
			foreach($sysProps as $sysProp) {
				if($cond = $this->buildSysProp($sysProp)) {
					$conds[] = $cond;
				}
			}
			
			if($this->selector->mode == 'pages') {
				if($permConds = $this->buildPermissions()) {
					$conds[] = $permConds;
				}
				
				if($hierarchyConds = $this->buildHierarchy()) {
					$conds[] = $hierarchyConds;
				}
				
				if(isset($this->queryTables['o'])) {
					$conds[] = "h.obj_id = o.id";
				}
			}
			
			$sql .= implode(' AND ', $conds);
			if($sql) $sql = "WHERE " . $sql;
			return $sql;
		}
		
		protected function buildWhereValue($fieldId) {
			$wheres = $this->selector->whereFieldProps;
			$current = array();
			foreach($wheres as $where) {
				if($where->fieldId == $fieldId) $current[] = $where;
			}
			
			$column = self::getFieldColumn($fieldId);
			
			$sql = ""; $conds = array();
			foreach($current as $where) {
				$condition = $this->parseValue($where->mode, $where->value);
				if($column === false) {
					if(sizeof($where->value) == 1) {
						$keys = array_keys($where->value);
						$column = array_pop($keys) . '_val';
					} else continue;
				}
				
				$conds[] = "(oc_{$fieldId}.{$column}{$condition})";
			}
			
			$sql = implode(" AND ", array_unique($conds));
			return $sql ? " AND " . $sql : "";
		}
		
		protected function parseValue($mode, $value) {
			switch($mode) {
				case 'equals':
					if(is_array($value) || is_object($value)) {
						$value = $this->escapeValue($value);
						if(sizeof($value)) {
							return ' IN(' . implode(', ', $value) . ')';
						} else {
							return ' = 0 = 1';	//Impossible value to reset query result to zero
						}
					}
					else
						return ' = ' . $this->escapeValue($value);
					break;

				case 'notequals':
					if(is_array($value) || is_object($value)) {
						$value = $this->escapeValue($value);
						if(sizeof($value)) {
							return ' NOT IN(' . implode(', ', $value) . ')';
						} else {
							return ' = 0 = 1';	//Impossible value to reset query result to zero
						}
					}
					else
						return ' != ' . $this->escapeValue($value);
					break;


				case 'like':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' LIKE ' . $this->escapeValue($value);
				
				case 'ilike':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' LIKE ' . $this->escapeValue($value);
				
				case 'more':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' > ' . $this->escapeValue($value);
				
				case 'eqmore':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' >= ' . $this->escapeValue($value);
				
				case 'less':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' < ' . $this->escapeValue($value);
				
				case 'eqless':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' <= ' . $this->escapeValue($value);
				
				case 'between':
					return ' BETWEEN ' . $this->escapeValue($value[0]) . ' AND ' . $this->escapeValue($value[1]);
				
				case 'isnotnull':
					$value != $value;
				case 'isnull':
					return ($value) ? ' IS NULL' : ' IS NOT NULL';
					
				default:
					throw new selectorException("Unsupported field mode \"{$mode}\"");
			}
		}
		
		protected function buildSysProp($prop) {
			if($prop->name == 'domain' || $prop->name == 'lang') {
				if($prop->value === false) return false;
			}
			
			if($prop->name == '*') {
				$this->requireTable('o_asteriks', 'cms3_object_content');
				$this->requireTable('o', 'cms3_objects');
				
				$alias = self::getContentTableName($this->selector, null);
				$tables = array('o_asteriks');
				if($alias != 'cms3_object_content') {
					$this->requireTable('o_asteriks_branched', $alias);
					$tables[] = 'o_asteriks_branched';
				}
				$this->queryOptions[] = 'DISTINCT';
				
				
				$conds = array();
				foreach($tables as $tableName) {
					
					
					$values = $prop->value;
					if(!is_array($values)) $values = array($values);
					$sconds = array();
					foreach($values as $value) {
						$evalue = $this->escapeValue('%' . $value . '%');
						
						$sconds[] = $tableName . '.varchar_val LIKE ' . $evalue;
						$sconds[] = $tableName . '.text_val LIKE ' . $evalue;
						$sconds[] = 'o.name LIKE ' . $evalue;
						
						if(is_numeric($value)) {
							$sconds[] = $tableName . '.float_val = ' . $evalue;
							$sconds[] = $tableName . '.int_val = ' . $evalue;
						}
					}
					
					$conds[] = '(' . $tableName . '.obj_id = o.id AND (' . implode(' OR ', $sconds) . '))';
				}
				
				$sql = '(' . implode(' OR ', $conds) . ')';
				return $sql;
				
			} else {
				$name = $this->requireSysProp($prop->name);
				return "{$name}" . $this->parseValue($prop->mode, $prop->value);
			}
		}
		
		protected function buildOrder() {
			$sql = "";
			$conds = array();
			foreach($this->selector->orderFieldProps as $order) {
				$fieldId = $order->fieldId;
				$column = self::getFieldColumn($fieldId);
				$conds[] = "oc_{$fieldId}.{$column} " . ($order->asc ? 'ASC' : 'DESC');
			}
			
			foreach($this->selector->orderSysProps as $order) {
				$name = $this->requireSysProp($order->name);
				$conds[] = $name . ' ' . ($order->asc ? 'ASC' : 'DESC');
			}
			
			$sql = implode(', ', $conds);
			return $sql ? "ORDER BY " . $sql : "";
		}
		
		protected function buildPermissions() {
			$permissions = $this->selector->permissions;
			$owners = $permissions->owners;
			if($permissions && sizeof($owners)) {
				$this->requireTable('p', 'cms3_permissions');
				$this->queryOptions[] = 'DISTINCT';

				$owners[] = 2373;
				$owners = implode(', ', $owners);
				return "(p.rel_id = h.id AND p.level & {$permissions->level} AND p.owner_id IN({$owners}))";
			} else return "";
		}
		
		protected function buildHierarchy() {
			$hierarchy = $this->selector->hierarchy;
			if(!$hierarchy) return "";
			
			$this->requireTable('hr', 'cms3_hierarchy_relations');
			
			$sql = "h.id = hr.child_id AND (hr.level <= {$hierarchy->level} AND hr.rel_id";
			
			$sql .= ($hierarchy->elementId > 0) ? " = '{$hierarchy->elementId}')" : " IS NULL)";
			return $sql;
		}
		
		protected static function getFieldColumn($fieldId) {
			static $cache = array();
			if(isset($cache[$fieldId])) return $cache[$fieldId];
			
			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			switch($field->getDataType()) {
				case 'string':
				case 'file':
				case 'img_file':
				case 'swf_file':
				case 'password':
				case 'tags':
					return $cache[$fieldId] = 'varchar_val';
				
				case 'int':
				case 'boolean':
				case 'date':
					return $cache[$fieldId] = 'int_val';

				case 'counter':
					return $cache[$fieldId] = 'cnt';
				
				case 'price':
				case 'float':
					return $cache[$fieldId] = 'float_val';
				
				case 'text':
				case 'wysiwyg':
					return $cache[$fieldId] = 'text_val';
				
				case 'relation':
					return $cache[$fieldId] = 'rel_val';
				
				case 'symlink':
					return $cache[$fieldId] = 'tree_val';

				case 'optioned': return false;

				default:
					throw new selectorException("Unsupported field type \"{$field->getDataType()}\"");
			}
		}
		
		protected function escapeValue($value) {
			if(is_array($value)) {
				foreach($value as $i => $val) $value[$i] = $this->escapeValue($val);
				return $value;
			} if ($value instanceof selector) {
				return $this->escapeValue($value->result());
			} if ($value instanceof iUmiObject || $value instanceof iUmiHierarchyElement) {
				return $value->id;
			} else {
				return "'" . mysql_real_escape_string($value) . "'";
			}
		}
		
		protected function buildRootQuery($mode) {
			$columnsSql = $this->buildColumns();
			$limitSql = $this->buildLimit();
			$orderSql = $this->buildOrder();
			$whereSql = $this->buildWhere();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			$types = array();
			foreach($this->selector->types as $type) {
				if($type->hierarchyType) $types[] = $type->hierarchyType->getId();
			}
			$typesSql = implode(', ', $types);

			$columnsSql = ($mode == 'result') ? 'DISTINCT h.id' : 'COUNT(DISTINCT h.id)';

			$sql = <<<SQL
SELECT $columnsSql 
	FROM cms3_hierarchy hp, {$tablesSql}
	{$whereSql}
	AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$typesSql})))
		{$orderSql}
		{$limitSql}
SQL;
			return $sql;
		}
		
		protected function excludeNestedPages($arr) {
			$hierarchy = umiHierarchy::getInstance();
			
			$result = array();
			foreach($arr as $elementId) {
				$element = $hierarchy->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					if(in_array($element->getRel(), $arr)) {
						continue;
					} else {
						$result[] = $elementId;
					}
				}
			}
			return $result;
		}
	};


	class selectorGetter {
		protected static $types = array('object', 'page', 'object-type', 'hierarchy-type', 'field', 'field-type', 'domain', 'lang');
		protected $requestedType;

		public function __construct($requestedType) {
			if(in_array($requestedType, self::$types) == false) {
				throw new selectorException("Wrong content type \"{$requestedType}\"");
			}
			$this->requestedType = $requestedType;
		}
		
		public function id($id) {
			if(is_array($id)) {
				$result = array();
				foreach($id as $i => $v) {
					$item = $this->id($v);
					if(is_object($item)) {
						$result[$i] = $item;
					}
					unset($item);
				}
				return $result;
			}
			if(!$id) return null;
		
			$collection = $this->collection();

			try {
				switch($this->requestedType) {
					case 'object':
						return $collection->getObject($id);
					case 'page':
						return $collection->getElement($id);
					case 'hierarchy-type':
					case 'object-type':
						return $collection->getType($id);
					case 'field':
						return $collection->getField($id);
					case 'field-type':
						return $collection->getFieldType($id);
					case 'domain':
						return $collection->getDomain($id);
					case 'lang':
						return $collection->getLang($id);
				}
			} catch (coreException $e) {
				return null;
			}
		}
		
		public function name($module, $method = '') {
			$collection = $this->collection();
		
			switch($this->requestedType) {
				case 'object-type': {
					$objectTypeId = $collection->getBaseType($module, $method);
					return $this->id($objectTypeId);
				}
				
				case 'hierarchy-type': {
					$hierarchyType = $collection->getTypeByName($module, $method);
					return ($hierarchyType instanceof iUmiHierarchyType) ? $hierarchyType : null;
				}
				
				default: throw new selectorException("Unsupported \"name\" method for \"{$this->requestedType}\"");
			}
		}
		
		public function prefix($prefix) {
			if($this->requestedType != 'lang') {
				throw new selectorException("Unsupported \"prefix\" method for \"{$this->requestedType}\"");
			}
			
			$collection = $this->collection();
			return $this->id($collection->getLangId($prefix));
		}
		
		public function host($host) {
			if($this->requestedType != 'domain') {
				throw new selectorException("Unsupported \"host\" method for \"{$this->requestedType}\"");
			}
			
			$collection = $this->collection();
			return $this->id($collection->getDomainId($host));
		}
		
		protected function collection() {
			switch($this->requestedType) {
				case 'object':
					return umiObjectsCollection::getInstance();
				case 'page':
					return umiHierarchy::getInstance();
				case 'object-type':
					return umiObjectTypesCollection::getInstance();
				case 'hierarchy-type':
					return umiHierarchyTypesCollection::getInstance();
				case 'field':
					return umiFieldsCollection::getInstance();
				case 'field-type':
					return umiFieldTypesCollection::getInstance();
				case 'domain':
					return domainsCollection::getInstance();
				case 'lang':
					return langsCollection::getInstance();
			}
		}
	};


	class selectorHelper {
		
		static function detectFilters(selector $sel) {
			if($sel->mode == 'pages') {
				$domains = (array) getRequest('domain_id');
				foreach($domains as $domainId) {
					$sel->where('domain')->equals($domainId);
				}
				
				$langs = (array) getRequest('lang_id');
				foreach($langs as $langId) {
					$sel->where('lang')->equals($langId);
				}
			}
			
			
			if($sel->mode == 'pages' && sizeof($sel->types) && is_array(getRequest('rel'))) {
				$sel->types('hierarchy-type')->name('comments', 'comment');
			}
			
			self::detectHierarchyFilters($sel);
			self::detectWhereFilters($sel);
			self::detectOrderFilters($sel);
			
			//$sel->option('exclude-nested', true);
			
			self::checkSyncParams($sel);
		}
		
		static function checkSyncParams(selector $sel) {
			if(getRequest('export')) {
				quickCsvExporter::autoExport($sel, (bool) getRequest('force-hierarchy'));
			}
			
			if(getRequest('import')) {
				quickCsvImporter::autoImport($sel, (bool) getRequest('force-hierarchy'));
			}
		}
		
		
		static function detectHierarchyFilters(selector $sel) {
			if(sizeof(getRequest('fields_filter'))) return;
			if(sizeof(getRequest('order_filter'))) return;
		
			$rels = (array) getRequest('rel');
			
			if(sizeof($rels) == 0 && $sel->mode == 'pages') {				
				//$rels[] = '0';
				$sel->option('exclude-nested', true);
			}
			
			foreach($rels as $id) {
				try {
					if($id || $id === '0') $sel->where('hierarchy')->page($id)->childs(1);
					if($id === '0') $sel->option('exclude-nested', true);
				} catch (selectorException $e) {}
			}
		}
		
		static function detectWhereFilters(selector $sel) {
			static $funcs = array('eq' => 'equals', 'ne' => 'notequals', 'like' => 'like', 'gt' => 'more', 'lt' => 'less' );
			
			
			$searchAllText = (array) getRequest('search-all-text');
			//fix for guide items without fields
			if(sizeof($sel->types) == 1 && ($sel->types[0]->objectType instanceof iUmiObjectType) && sizeof($sel->types[0]->objectType->getAllFields()) == 0) {
				foreach($searchAllText as $searchString) {
					$sel->where('name')->like('%' . $searchString . '%');
				}
				return;
			} else {
				foreach($searchAllText as $searchString) {
					try {
						if($searchString !== "") $sel->where('*')->like('%' . $searchString . '%');
					} catch (selectorException $e) {}
				}
			}

			$filters = (array) getRequest('fields_filter');
			foreach($filters as $fieldName => $info) {
				if(is_array($info)) {
					//Old-style between filter
					if(isset($info[0]) && isset($info[1])) {
						try {
							$sel->where($fieldName)->between($info[0], $info[1]);
						} catch (selectorException $e) {}
					}
					
					//Try new-style filter
					foreach($info as $i => $v) {
						if(isset($funcs[$i])) {
							try {
								if($funcs[$i] == 'like') {
									$v .= '%';
								}
								
								if($v !== "") $sel->where($fieldName)->$funcs[$i]($v);
							} catch(selectorException $e) { self::tryException($e); }
						}
					}
				} else {
					//Old-style strict equals filter
					try {
						if($info !== "") $sel->where($fieldName)->equals($info);
					} catch(selectorException $e) {}
				}
			}
		}
		
		static function detectOrderFilters(selector $sel) {
			$orders = (array) getRequest('order_filter');
			foreach($orders as $fieldName => $direction) {
				$func = (strtolower($direction) == 'desc') ? 'desc' : 'asc';
				
				try {
					$sel->order($fieldName)->$func();
				} catch (selectorException $e) { self::tryException($e); }
			}
		}
		
		static private function tryException(Exception $e) {
			//if(DEBUG) throw $e;
		}
	};



abstract class def_module {
	public static
		$templates_cache = array(), $noRedirectOnPanic = false, $defaultTemplateName = 'default';

	public $max_pages = 10, $isSelectionFiltered = false;
	public $pid, $FORMS_CACHE = array(), $FORMS = array(), $per_page = 20;
	
	public $dataType, $actionType, $currentEditedElementId = false;
	public $__classes = array(), $libsCalled = array();
	public $common_tabs = null, $config_tabs = null;

	protected function __implement($class_name) {
		$this->__classes[] = $class_name;

		$cm = get_class_methods($class_name);
		
		if(is_null($cm)) return;

		$fn = "onInit";
		if(in_array($fn, $cm)) $this->$fn();
		
		// invoke onImplement public method :
		$fn = "onImplement";
		if (in_array($fn, $cm) && class_exists('ReflectionClass') 
		&& class_exists('ReflectionMethod') && class_exists('ReflectionException')) {
			try {
				$oRfClass = new ReflectionClass($class_name);
				$oRfMethod = $oRfClass->getMethod($fn);
				if ($oRfMethod instanceof ReflectionMethod) {
					if ($oRfMethod->isPublic()) {
						eval('$res = ' . $class_name . '::' . $fn . '();');
					}
				}
			} catch (ReflectionException $e) {}
		}
	
	}

	public function __admin() {
		if(cmsController::getInstance()->getCurrentMode() == "admin" && !class_exists("__" . get_class($this))) {
			$this->__loadLib("__admin.php");
			$this->__implement("__" . get_class($this));
		}
	}

	public function __call($method, $args) {
		foreach($this->__classes as $className) {
			$classMethods = get_class_methods($className);
			if(is_null($classMethods)) continue;

			if(in_array($method, $classMethods)) {
				$params = "";
				if(is_array($args)) {
					$sz = sizeof($args);
					for($i = 0; $i < $sz; $i++) {
						$params .= '$args[' . $i . ']';
						if($i != $sz-1) $params .= ", ";
					}
				}
				eval('$result = ' . $className . '::' . $method . '(' . $params . ');');
				return $result;
			}
		}
		
		$cmsController = cmsController::getInstance();
		$cmsController->langs[get_class($this)][$method] = "Ошибка";

		if($cmsController->getModule("content")) {
			if($cmsController->getCurrentMode() == "admin") {
				return "Вызов несуществующего метода.";
			} else {
				if($cmsController->getCurrentModule() == get_class($this) && $cmsController->getCurrentMethod() == $method) {
					return $cmsController->getModule("content")->gen404();
				} else {
					return "";
				}
			}
		}
	}

	public function __construct() {
		$this->lang = cmsController::getInstance()->getCurrentLang()->getPrefix();
		$this->init();
	}
		

	public function getCommonTabs() {
		$cmsController = cmsController::getInstance();
		$currentModule = $cmsController->getCurrentModule();
		$selfModule = get_class($this);
	
		if (($currentModule != $selfModule) && ($currentModule != false && $selfModule != 'users')) return false;
		if (!$this->common_tabs instanceof adminModuleTabs) {
			$this->common_tabs = new adminModuleTabs("common");
		}
		return $this->common_tabs;
	}
	
	public function getConfigTabs() {
		if (cmsController::getInstance()->getCurrentModule() != get_class($this)) return false;
	
		if (!$this->config_tabs instanceof adminModuleTabs) {
			$this->config_tabs = new adminModuleTabs("config");
		}
		return $this->config_tabs;
	}


	public function cms_callMethod($method_name, $args) {
		if(!$method_name) return;
		
		$aArguments = array();
		if(USE_REFLECTION_EXT && class_exists('ReflectionMethod')) {
			try {
				$oReflection   = new ReflectionMethod($this, $method_name);
				$iNeedArgCount = max($oReflection->getNumberOfRequiredParameters(), count($args));
				if($iNeedArgCount) $aArguments = array_fill(0, $iNeedArgCount, 0);
			} catch(Exception $e) {}
		}
		for($i=0; $i<count($args); $i++) $aArguments[$i] = $args[$i];		
		if(count($aArguments) && !(empty($args[0]) && sizeof($args) == 1)) {
			return call_user_func_array(array($this, $method_name), $aArguments);
		} else {
			return $this->$method_name();
		}
	}

	//инициализация модуля
	public function init() {
		$includesFile = CURRENT_WORKING_DIR . '/classes/modules/' . get_class($this) . '/includes.php';
		if(is_file($includesFile) && !defined('SKIP_MODULES_INCLUDES')) {
			require_once $includesFile;
		}
	}

	public function install($INFO) {
		$xpath = '//modules/' . $INFO['name'];
		$regedit = regedit::getInstance();

		$regedit->setVar($xpath, $INFO['name']);

		if(is_array($INFO)) {
				foreach($INFO as $var => $module_param) {
						$val = $module_param;
						$regedit->setVar($xpath . "/" . $var, $val);
				}
		}
	}

	public function uninstall() {
		$regedit = regedit::getInstance();
		$className = get_class($this);

		$k = $regedit->getKey('//modules/' . $className);
		$regedit->delVar('//modules/' . $className);
	}

	/**
	* @desc Redirect to $url and terminate current execution
	* @param $url String Url of new location
	* @return void
	*/
	public function redirect($url, $ignoreErrorParam = true) {
		if(getRequest('redirect_disallow')) return;
		if(!$url) $url = $this->pre_lang . "/";
		if($ignoreErrorParam && ($this instanceof def_module)) $url = $this->removeErrorParam($url);
		
		umiHierarchy::getInstance()->__destruct();
		outputBuffer::current()->redirect($url);
	}
	
		
	public function requireSlashEnding() {
		if(getRequest('xmlMode') == "force" || sizeof($_POST) > 0) {
			return;
		}
		
		$uri = getServer('REQUEST_URI');
		
		$uriInfo = parse_url($uri);
		if(substr($uriInfo['path'], -1, 1) != "/") {
			$uri = $uriInfo['path'] . "/";
			if(isset($uriInfo['query']) && $uriInfo['query']) {
				$uri .= "?" . $uriInfo['query'];
			}
			self::redirect($uri);
		}
	}

	/**
	* @desc Подключает дополнительные файлы. Введена, чтобы подключать дополнительные методы в админке.
	* @param $lib String - Filename of libfile
	* @param $path String - Path to directory, where lib file is located
	* @param remember Boolean If true, do not flush cache after next use of this method.
	* @return void
	*/
	public function __loadLib($lib, $path = "", $remember = false) {
		$lib_path = ($path) ? $path . $lib : "classes/modules/" . get_class($this) . "/" . $lib;
		$lib_path = CURRENT_WORKING_DIR . "/" . $lib_path;

		if(isset($this->FORMS_CACHE[$lib_path])) {
			$FORMS = $this->FORMS_CACHE[$lib_path];
		} else {
			if(file_exists($lib_path)) {
				require $lib_path;
			}
		}

		if($remember) {
			$this->FORMS = $FORMS;
			$this->FORMS_CACHE[$lib_path] = $FORMS;
		}
		return true;
	}
	
	public function setHeader($header) {
		$cmsControllerInstance = cmsController::getInstance();
		$cmsControllerInstance->currentHeader = $header;
	}

	protected function setTitle($title = "", $mode = 0) {
		$cmsControllerInstance = cmsController::getInstance();
		if($title) {
			if($mode)
				$cmsControllerInstance->currentTitle = regedit::getInstance()->getVal('//domains/' . $_REQUEST['domain'] . '/title_pref_' . $_REQUEST['lang']) . $title;
			else
				$cmsControllerInstance->currentTitle = $title;
		}
		else
			$cmsControllerInstance->currentTitle = cmsController::getInstance()->currentHeader;

	}

	protected function setH1($h1) {
		$this->setHeader($h1);
	}

	public function flush($output = "", $ctype = false) {
		if($ctype !== false) {
			header("Content-type: " . $ctype);
		}

		echo $output;
		exit();
	}


	public static function loadTemplates($filepath = "") {
		$c = func_num_args();
		$args = func_get_args();

		$xslTemplater = xslTemplater::getInstance();

		if($xslTemplater->getIsInited()) {
				return $xslTemplater->loadTemplates($filepath, $c, $args);
		}
		
		
		$filepath = realpath($filepath);
		
		$filepath = getPrintableTpl($filepath);

		if(!file_exists($filepath)) {
			$lang_filepath = substr($filepath, 0, strlen($filepath) - 3) . cmsController::getInstance()->getCurrentLang()->getPrefix() . ".tpl";
			$lang_filepath = getPrintableTpl($lang_filepath);
			if(file_exists($lang_filepath)) {
				$filepath = $lang_filepath;
			} else {
				throw new publicException("Невозможно подключить шаблон {$filepath}");
				return false;
			}
		}

		if(!array_key_exists($filepath, def_module::$templates_cache)) {
			include $filepath;
			def_module::$templates_cache[$filepath] = $FORMS;
		}

		$templates = def_module::$templates_cache[$filepath];

		$tpls = Array();
		for($i = 1; $i < $c; $i++) {
			$tpl = "";
			if(array_key_exists($args[$i], $templates)) {
				$tpl = $templates[$args[$i]];
			}
			$tpls[] = $tpl;
		}
		return $tpls;
	}

	public static function parseTemplate($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
		$xslTemplater = xslTemplater::getInstance();
		if($xslTemplater->getIsInited()) {
				return $xslTemplater->parseTemplate($arr);
		}
		

		if(is_array($arr)) {
			foreach($arr as $m => $v) {
				$m = self::getRealKey($m);
			
				if(is_array($v)) {
					$res = "";
					$v = array_values($v);
					$sz = sizeof($v);
					for($i = 0; $i < $sz; $i++) {
						$str = $v[$i];
						
						$listClassFirst = ($i == 0) ? "first" : "";
						$listClassLast = ($i == $sz-1) ? "last" : "";
						$listClassOdd = (($i+1) % 2 == 0) ? "odd" : "";
						$listClassEven = $listClassOdd ? "" : "even";
						$listPosition = ($i + 1);
						$listComma = $listClassLast ? '' : ', ';
						
						$from = Array(
							'%list-class-first%', '%list-class-last%', '%list-class-odd%', '%list-class-even%', '%list-position%',
							'%list-comma%'
						);
						$to = Array(
							$listClassFirst, $listClassLast, $listClassOdd, $listClassEven, $listPosition, $listComma
						);
						$res .= str_replace($from, $to, $str);
					}
					$v = $res;
				}

				if(!is_object($v)) {
					$template = str_replace("%" . $m . "%", $v, $template);
				}
			}
		}

		if($parseElementPropsId !== false || $parseObjectPropsId != false) {
			if($parseElementPropsId) {
				$template = str_replace("%block-element-id%", $parseElementPropsId, $template);
			}
			
			if($parseObjectPropsId) {
				$template = str_replace("%block-object-id%", $parseObjectPropsId, $template);
			}
		
			$template = system_parse_short_calls($template, $parseElementPropsId, $parseObjectPropsId);
			
			$templater = templater::getInstance();
			if(!is_object($templater)) {
				return $template;
			}
			
			$template = $templater->parseInput($template);
			$template = system_parse_short_calls($template, $parseElementPropsId, $parseObjectPropsId);
		}
		return $template;
	}
	
	
	static public function getRealKey($key, $reverse = false) {
			if($pos = strpos($key, ":")) {
				++$pos;
			} else {
				$pos = 0;
				}
				return $reverse ? substr($key, 0, $pos - 1) : substr($key, $pos);
	}

	public function formatMessage($message, $b_split_long_mode = 0) {
		static $bb_from;
		static $bb_to;

		try {
			list($quote_begin, $quote_end) = $this->loadTemplates(SYS_TPLS_PATH . '/quote/default.tpl', 'quote_begin', 'quote_end');
		} catch (publicException $e) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if(xslTemplater::getInstance()->getIsInited()) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to))) {
			try {
				list($bb_from, $bb_to) = $this->loadTemplates(SYS_TPLS_PATH . '/bb/default.tpl', 'bb_from', 'bb_to');
				if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to) && count($bb_to))) {
					$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
						"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
					);
		
					$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
						$quote_begin, $quote_end, "<u>", "</u>", "<br />"
					);
				}
			} catch (publicException $e) {
				$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
					"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
				);
		
				$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
					$quote_begin, $quote_end, "<u>", "</u>", "<br />"
				);
			}
		}
		
		$openQuoteCount = substr_count(wa_strtolower($message), "[quote]");
		$closeQuoteCount = substr_count(wa_strtolower($message), "[/quote]");
		
		if($openQuoteCount > $closeQuoteCount) {
			$message .= str_repeat("[/quote]", $openQuoteCount - $closeQuoteCount);
		}
		if($openQuoteCount < $closeQuoteCount) {
			$message = str_repeat("[quote]", $closeQuoteCount - $openQuoteCount) . $message;
		}

		$message = preg_replace("`((http)+(s)?:(//)|(www\.))((\w|\.|\-|_)+)(/)?([/|#|?|&|=|\w|\.|\-|_]+)?`i", "[url]http\\3://\\5\\6\\8\\9[/url]", $message);

		$message = str_ireplace($bb_from, $bb_to, $message);
		$message = str_ireplace("</h4>", "</h4><p>", $message);
		$message = str_ireplace("</div>", "</p></div>", $message);

		$message = str_replace(".[/url]", "[/url].", $message);
		$message = str_replace(",[/url]", "[/url],", $message);

		$message = str_replace(Array("[url][url]", "[/url][/url]"), Array("[url]", "[/url]"), $message);

		// split long words
		if ($b_split_long_mode === 0) { // default
			$arr_matches = array();
			$b_succ = preg_match_all("/[^\s^<^>]{70,}/u", $message, $arr_matches);
			if ($b_succ && isset($arr_matches[0]) && is_array($arr_matches[0])) {
				foreach ($arr_matches[0] as $str) {
					$s = "";
					if (strpos($str, "[url]") === false) {
						for ($i = 0; $i<wa_strlen($str); $i++) $s .= wa_substr($str, $i, 1).(($i % 30) === 0 ? " " : "");
						$message = str_replace($str, $s, $message);
					}
				}
			}
		} elseif ($b_split_long_mode === 1) {
			// TODU abcdef...asdf
		}


		if (preg_match_all("/\[url\]([^А-я^\r^\n^\t]*)\[\/url\]/U", $message, $matches, PREG_SET_ORDER)) {
			for ($i=0; $i<count($matches); $i++) {
				$s_url = $matches[$i][1];
				$i_length = strlen($s_url);
				if ($i_length>40) {
					$i_cutpart = ceil(($i_length-40)/2);
					$i_center = ceil($i_length/2);
					
					$s_url = substr_replace($s_url, "...", $i_center-$i_cutpart, $i_cutpart*2);
				}
				$message = str_replace($matches[$i][0], "<a href='".$matches[$i][1]."' rel='nofollow' target='_blank' title='Ссылка откроется в новом окне'>".$s_url."</a>", $message);
			}
		}

		$message = str_replace("&", "&amp;", $message);


		$message = str_ireplace("[QUOTE][QUOTE]", "", $message);

		
	
		if(preg_match_all("/\[smile:([^\]]+)\]/im", $message, $out)) {
			foreach($out[1] as $smile_path) {
				$s = $smile_path;
				$smile_path = "images/forum/smiles/" . $smile_path . ".gif";
				if(file_exists($smile_path)) {
					$message = str_replace("[smile:" . $s . "]", "<img src='/{$smile_path}' />", $message);
				}
			}
		}

		
		$message = preg_replace("/<p>(<br \/>)+/", "<p>", $message);
		$message = nl2br($message);
		$message = str_replace("<<br />br /><br />", "", $message);
		$message = str_replace("<p<br />>", "<p>", $message);

		$message = str_replace("&amp;quot;", "\"", $message);
		$message = str_replace("&amp;quote;", "\"", $message);
		$message = html_entity_decode($message);
		$message = str_replace("%", "&#37;", $message);
		$message = templater::getInstance()->parseInput($message);

		return $message;
	}

	public function autoDetectAttributes() {
		if($element_id = cmsController::getInstance()->getCurrentElementId()) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return false;

			if($h1 = $element->getValue("h1")) {
				$this->setHeader($h1);
			} else {
				$this->setHeader($element->getName());
			}

			if($title = $element->getValue("title")) {
				$this->setTitle($title);
			}

		}
	}


	public function autoDetectOrders(umiSelection $sel, $object_type_id) {
		if(array_key_exists("order_filter", $_REQUEST)) {
			$sel->setOrderFilter();

			$type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			$order_filter = getRequest('order_filter');
			foreach($order_filter as $field_name => $direction) {
				if($direction === "asc") $direction = true;
				if($direction === "desc") $direction = false;
				
				if($field_name == "name") {
					$sel->setOrderByName((bool) $direction);
					continue;
				}
				
				if($field_name == "ord") {
					$sel->setOrderByOrd((bool) $direction);
					continue;
				}

				if($type) {
					if($field_id = $type->getFieldId($field_name)) {
						$sel->setOrderByProperty($field_id, (bool) $direction);
					} else {
						continue;
					}
				}
			}
		} else {
			return false;
		}
	}

	public function autoDetectFilters(umiSelection $sel, $object_type_id) {
		if(is_null(getRequest('search-all-text')) == false) {
			$searchStrings = getRequest('search-all-text');
			if(is_array($searchStrings)) {
				foreach($searchStrings as $searchString) {
					if($searchString) {
						$sel->searchText($searchString);
					}
				}
			}
		}
		
		if(array_key_exists("fields_filter", $_REQUEST)) { 
			$cmsController = cmsController::getInstance();
			$data_module = $cmsController->getModule("data");
			if(!$data_module) {
				throw publicException("Need data module installed to use dynamic filters");
			}
			$sel->setPropertyFilter();

			$type = umiObjectTypesCollection::getInstance()->getType($object_type_id);


			$order_filter = getRequest('fields_filter');
			if(!is_array($order_filter)) {
				return false;
			}

			foreach($order_filter as $field_name => $value) { 
				if($field_name == "name") {
					$data_module->applyFilterName($sel, $value);
					continue;
				}

				if($field_id = $type->getFieldId($field_name)) {
					$this->isSelectionFiltered = true;
					$field = umiFieldsCollection::getInstance()->getField($field_id);

					$field_type_id = $field->getFieldTypeId();
					$field_type = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id);

					$data_type = $field_type->getDataType();

					switch($data_type) {
						case "text": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "wysiwyg": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						
						case "string": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}
						
						case "tags": {
							$tmp = array_extract_values($value);
							if(empty($tmp)) {
								break;
							}
						}
						case "boolean":
						case "int": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}

						case "relation": {
							$data_module->applyFilterRelation($sel, $field, $value);
							break;
						}
						
						case "float": {
							$data_module->applyFilterFloat($sel, $field, $value);
							break;
						}
						
						case "price": {
							$emarket = $cmsController->getModule('emarket');
							if($emarket instanceof def_module) {
								$defaultCurrency = $emarket->getDefaultCurrency();
								$currentCurrency = $emarket->getCurrentCurrency();
								$prices = $emarket->formatCurrencyPrice($value, $defaultCurrency, $currentCurrency);
								foreach($value as $index => $void) {
									$value[$index] = getArrayKey($prices, $index);
								}
							}
							
							$data_module->applyFilterPrice($sel, $field, $value);
							break;
						}

						case "file":
						case "img_file":
						case "swf_file":
						case "boolean": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}
						
						case "date": {
							$data_module->applyFilterDate($sel, $field, $value);
							break;
						}
						
						default: {
							break;
						}
					}
				} else {
					continue;
				}
			}
		} else {
			return false;
		}
	}


	public function analyzeRequiredPath($pathOrId, $returnCurrentIfVoid = true) {

		if(is_numeric($pathOrId)) {
			return (umiHierarchy::getInstance()->isExists((int) $pathOrId)) ? (int) $pathOrId : false;
		} else {
			$pathOrId = trim($pathOrId);

			if($pathOrId) {
				if(strpos($pathOrId, " ") === false) {
					return umiHierarchy::getInstance()->getIdByPath($pathOrId);
				} else {
					$paths_arr = split(" ", $pathOrId);

					$ids = Array();

					foreach($paths_arr as $subpath) {
						$id = $this->analyzeRequiredPath($subpath, false);

						if($id === false) {
							continue;
						} else {
							$ids[] = $id;
						}
					}

					if(sizeof($ids) > 0) {
						return $ids;
					} else {
						return false;
					}
				}
			} else {
				if($returnCurrentIfVoid) {
					return cmsController::getInstance()->getCurrentElementId();
				} else {
					return false;
				}
			}
		}
	}


	public function checkPostIsEmpty($bRedirect = true) {
		$bResult = !is_array($_POST) || (is_array($_POST) && !count($_POST));
		if ($bResult && $bRedirect) {
			header("Location: ".$_REQUEST['pre_lang']."/admin/");
			exit();
		} else {
			return $bResult;
		}
	}


	public static function setEventPoint(umiEventPoint $eventPoint) {
		umiEventsController::getInstance()->callEvent($eventPoint);
	}
	
	public function breakMe() {
		$cmsController = cmsController::getInstance();

		if($cmsController->getCurrentMode() == "admin") {
			return false;
		}
		
		if(xslTemplater::getInstance()->getIsInited()) {
			if($cmsController->isContentMode) {
				return true;
			}
		}
		return false;
	}
	
	
	/**
		Methods for user errors notifications thru pages
	**/
	
	//Call this function to register error page url, which will be called after errors.
	public function errorRegisterFailPage($errorUrl) {
		cmsController::getInstance()->errorUrl = $errorUrl;
	}
	
	//Add new error message and call errorPanic(), is second argument is true
	public function errorNewMessage($errorMessage, $causePanic = true, $errorCode = false, $errorStrCode = false) {
		$requestId = 'errors_' . cmsController::getInstance()->getRequestId();
		if(!isset($_SESSION[$requestId])) {
			$_SESSION[$requestId] = Array();
		}
		
		$errorMessage = templater::getInstance()->putLangs($errorMessage);
		
		$_SESSION[$requestId][] = Array("message" => $errorMessage,
						"code" => $errorCode,
						"strcode" => $errorStrCode);

		if($causePanic) {
			$this->errorPanic();
		}
	}
	
	
	//Forces redirect to error page, if at least one error message registrated
	public function errorPanic() {
		if(is_null(getRequest('_err')) == false) {
			return false;
		}
		
		if(self::$noRedirectOnPanic) {
			$requestId = 'errors_' . cmsController::getInstance()->getRequestId();
			if(!isset($_SESSION[$requestId])) {
				$_SESSION[$requestId] = Array();
			}
			$errorMessage = "";
			foreach($_SESSION[$requestId] as $i => $errorInfo) {
				unset($_SESSION[$requestId][$i]);
				$errorMessage .= $errorInfo['message'];
			}
			throw new errorPanicException($errorMessage);
		}
		
		if($errorUrl = cmsController::getInstance()->errorUrl) {
			// validate url
			$errorUrl = preg_replace("/_err=\d+/is", '', $errorUrl);
			while (strpos($errorUrl, '&&') !== false || strpos($errorUrl, '??') !== false || strpos($errorUrl, '?&') !== false) {
				$errorUrl = str_replace('&&', '&', $errorUrl);
				$errorUrl = str_replace('??', '?', $errorUrl);
				$errorUrl = str_replace('?&', '?', $errorUrl);
			}
			if (strlen($errorUrl) && (substr($errorUrl, -1) === '?' || substr($errorUrl, -1) === '&')) $errorUrl = substr($errorUrl, 0, strlen($errorUrl)-1);
			// detect param concat
			$sUrlConcat = (strpos($errorUrl, '?') === false ? '?' : '&');
			//
			$errorUrl .= $sUrlConcat . "_err=" . cmsController::getInstance()->getRequestId();
			$this->redirect($errorUrl, false);
		} else {
			throw new privateException("Can't find error redirect string");
		}
	}

	public function importDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTImporter = new umiModuleDataImporter();
		$bSucc = $oDTImporter->loadXmlFile($sDTXmlPath);
		if ($bSucc) {
			$oDTImporter->import();
			return "data types imported ok";
		} else {
			return "can not import data from file '".$sDTXmlPath."'";
		}
	}
	public function exportDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTExporter = new umiModuleDataExporter(get_class($this));
		$sDTXmlData = $oDTExporter->getXml();
		$vSucc = file_put_contents($sDTXmlPath, $sDTXmlData);
		if ($vSucc === false) {
			return "can not write to file '".$sDTXmlPath."'";
		} else {
			@chmod($sDTXmlPath, 0777);
			return $vSucc." bytes exported to the file '".$sDTXmlPath."' successfully";
		}
	}
	
	
	public function guessDomain() {
		$res = false;
	
		for($i = 0; ($param = getRequest("param" . $i)) || $i <= 3; $i++) {
			if(is_numeric($param)) {
				$element = umiHierarchy::getInstance()->getElement($param);
				if($element instanceof umiHierarchyElement) {
					$domain_id = $element->getDomainId();
					if($domain_id) $res = $domain_id;
				} else {
					continue;
				}
			} else {
				continue;
			}
		}
		
		$domain = domainsCollection::getInstance()->getDomain($res);
		if($domain instanceof iDomain) {
			return $domain->getHost();
		} else {
			return false;
		}
	}
	
	/**
	* @desc Checks for method existance
	* @param String $_sMethodName Name of the method
	* @return Boolean    
	*/
	public function isMethodExists($_sMethodName) {//$this->__classes
		if(class_exists('ReflectionClass')) {
			$oReflection = new ReflectionClass($this); 
			if($oReflection->hasMethod($_sMethodName)) {
				return true;
			}
			
			foreach($this->__classes as $classname) {
				$oReflection = new ReflectionClass($classname); 
				if($oReflection->hasMethod($_sMethodName)) {
					return true;
				}
			}
			
			return false;
		} else {
			$aMethods = get_class_methods($this);
			if(in_array($_sMethodName, $aMethods)) {
				return true;
			}
			
			foreach($this->__classes as $classname) {
				$aMethods = get_class_methods($this);
				
				if(in_array($_sMethodName, $aMethods)) {
					return true;
				}
			}
			return false;
		}
	}
	
	public function flushAsXML($methodName) {
		static $c = 0;
		if($c++ == 0) {
			$buffer = outputBuffer::current();
			$buffer->contentType('text/xml');
			$buffer->charset('utf-8');
			$buffer->clear();
			$buffer->push(file_get_contents("udata://" . get_class($this) . "/" . $methodName));
			$buffer->end();	
		}
	}

	public function ifNotXmlMode() {
		if(getRequest('xmlMode') != 'force') {
			$this->setData(array('message' => 'This method returns result only by direct xml call'));
			return true;
		}
	}
	
	public function removeErrorParam($url) { return preg_replace("/_err=\d+/", "", $url); }

	public function getObjectEditLink() { return false; }
	

	public static function validateTemplate(&$templateName) {
		if(!$templateName && $templateName == 'default' && self::$defaultTemplateName != 'default') {
			$templateName = self::$defaultTemplateName;
		}
	}
	
	public function templatesMode($mode) {
		$isXslt = xslTemplater::getInstance()->getIsInited();
		if($mode == 'xslt' && !$isXslt) {
			throw new xsltOnlyException;
		}
		
		if($mode == 'tpl' && $isXslt) {
			throw new tplOnlyException;
		}
	}
	
	
	public function validateEntityByTypes($entity, $types) {
		if($entity instanceof iUmiHierarchyElement) {
			$module = $entity->getModule();
			$method = $entity->getMethod();
		} else if($entity instanceof iUmiObject) {
			$objectType = selector::get('object-type')->id($entity->getTypeId());
			if($hierarchyTypeId = $objectType->getHierarchyTypeId()) {
				$hierarchyType = selector::get('hierarchy-type')->id($hierarchyTypeId);
				$module = $hierarchyType->getModule();
				$method = $hierarchyType->getMethod();
			} else {
				$module = null;
				$method = null;
			}
		} else {
			throw new publicException("Page or object must be given");
		}
		
		if(is_null($module) && is_null($method) && is_null($types)) {
			return true;
		}
		
		if($module == 'content' && $method == '') {
			$method = 'page';
		}
		
		if(getArrayKey($types, 'module')) {
			$types = array($types);
		}
		
		foreach($types as $type) {
			$typeModule = getArrayKey($type, 'module');
			$typeMethod = getArrayKey($type, 'method');
			
			if($typeModule == 'content' && $typeMethod == '') {
				$typeMethod = 'page';
			}

			if($typeModule == $module) {
				if(is_null($typeMethod)) return;
				if($typeMethod == $method) return;
			}
		}
		throw new publicException(getLabel('error-common-type-mismatch'));
	}
};




	class cacheFrontend extends singleton implements iSingleton {
		public static $cacheMode = false;
		
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}
		
		public function getIsConnected() {
			return false;
		}
		
		protected function __construct() {
		}
		
		public function load() {
			return false;
		}
		
		public function save() {
			return false;
		}
		
		public function loadSql() {
			return false;
		}
		
		public function saveSql() {
			return false;
		}
		
		public function flush() {
			return false;
		}
		
		public function del() {
			return false;
		}
		
		public function loadData() {
			return false;
		}
		
		public function saveData($key, $data, $expire = 5) {
			
		}
	};
	
	class umiBranch {
		public static function checkIfBranchedByHierarchyTypeId($hierarchyTypeId) {
			return false;
		}
		public static function getBranchedTableByTypeId($objectTypeId) {
			return "cms3_object_content";
		}
		
		public static function saveBranchedTablesRelations() {
			return true;
		}
	};
	
	class umiEventPoint {
		public function setParam() {}
		public function addRef() {}
		public function call() {}
	};
?>