<?php
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
	public function __construct($host, $login, $password, $dbname, $port = false, $persistent = false, $critical = true);
	/**
	 * Открывает соединение
	 * @return Boolean
	 */
	public function open();
	/**
	 * Закрывает текущее соединение
	 */
	public function close();
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return Resource результат выполнения запроса
	 */
	public function query($queryString, $noCache = false);
	/**
	 * Выполняет запрос к БД
	 * @param String  $queryString строка запроса
	 * @param Boolean $noCache true - кэшировать результат, false - не кэшировать
	 * @return IQueryResult результат выполнения запроса
	 */
	public function queryResult($queryString, $noCache = false);
	/**
	 * Проверяет, успешно ли завершен последний запрос
	 * @return Boolean true в случае возникновения ошибки, иначе false
	 */
	public function errorOccured();
	/**
	 * Возвращает описание последней возникшей ошибки
	 * @return String
	 */
	public function errorDescription();
	/**
	 * Возвращает признак открыто соединение или нет
	 * @return Boolean
	 */
	public function isOpen();
	/**
	* Экранирует входящую строку
	* @param String $input строка для экранирования
	* @return экранированная строка
	*/
	public function escape($input);

	/**
	* Возвращает массив с описанием соединения:
	*@return Array
	*
	*/
	public function getConnectionInfo();
};
?>