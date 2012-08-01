<?php
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
	static public function getInstance() {
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
?>
