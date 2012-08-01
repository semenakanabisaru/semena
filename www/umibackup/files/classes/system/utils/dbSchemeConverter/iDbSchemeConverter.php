<?php
	interface iDbSchemeConverter {

		/**
		* создает экземпляр класса dbSchemeConverter
		*
		* @param iConnection $connection - соединение с бд
		* @param mixed $path - путь к файлу, в котором будет хранится структура эталонной базы данных
		*/
		public function __construct (iConnection $connection, $path);

		/**
		* сохраняет структуру эталонной базы данных в файл
		*
		*/
		public function saveXmlToFile();

		/**
		* сравнивает эталонную базу данных с текущей базой данных и восттанавливает в случае необходимости
		*
		*/
		public function restoreDataBase();

		/**
		* возвращает запрос create table $tableName со всеми параметрами
		*
		* @param mixed $tableName - имя таблицы
		*/
		public function restoreShowCreateTable($tableName);

		/**
		* возвращает DomDocument со структурой базу данных. Использовать только, если доступна таблица INFORMATION_SCHEMA
		*
		*/
		public function getTablesInfo();
	}
?>
