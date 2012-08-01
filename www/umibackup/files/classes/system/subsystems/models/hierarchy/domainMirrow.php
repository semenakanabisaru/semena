<?php
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
?>