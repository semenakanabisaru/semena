<?php
/**
	* ����� ������������� ������ ���������� ���� ����� ������ � ���������� ���� � ����������� app-serv � 1 ����� db-serv.
*/
	class clusterCacheSync {
		protected	$enabled = false, $nodeId, $loadedKeys = Array(), $modifiedKeys = Array();

		/**
			* �������� ��������� ������ �������������
		*/
		public static function getInstance() {
			static $instance;
			if(!$instance) {
				$instance = new clusterCacheSync;
			}
			return $instance;
		}
		
		/**
			* ��������� ������������� �� ���������� ����� �������
			* @param String $key ���� ������ ����
			* @return Boolean ��������� ��������
		*/
		public function notify($key) {
			$key = (string) $key;
			if(!$key) return false;
			
			if(in_array($key, $this->modifiedKeys)) {
				return false;
			} else {
				$this->modifiedKeys[] = $key;
				return true;
			}
		}
		
		/**
			* ������� ��� ���������� ����� ������� ������
		*/
		public function cleanup() {
			foreach($this->loadedKeys as $i => $key) {
				cacheFrontend::getInstance()->del();
			}
		}
		
		/**
			* ����������, ���������� ���������� ������ ���������� ������
		*/
		public function __destruct() {
			$this->saveKeys();
		}
		
		/**
			* �����������, ��������� ������������� �������������
		*/
		protected function __construct() {
			if(isset($_SERVER['SERVER_ADDR'])) {
				$this->enabled = true;
				$this->init();
			}
		}
		
		/**
			* �������� id ������� ����
			* @return Integer id ������� ����
		*/
		protected function getNodeId() {
			return $this->nodeId;
		}
		
		/**
			* ��������� ������ ���������� ������ �� ���� ����
		*/
		public function saveKeys() {
			if(empty($this->modifiedKeys)) return;

			$nodeId = $this->getNodeId();
			
			$sql = "INSERT INTO `cms3_cluster_nodes_cache_keys` (`key`) VALUES ";
			$vals = Array();
			foreach($this->modifiedKeys as $key) {
				$vals[] = "('{$key}')";
			}
			$sql .= implode(", ", $vals);

			l_mysql_query("START TRANSACTION");
			//Insert expired keys
			l_mysql_query($sql);
			
			//Copy inserted keys for each node
			$sql = <<<SQL
INSERT INTO `cms3_cluster_nodes_cache_keys`
	(`node_id`, `key`)
		SELECT `n`.`id`, `nk`.`key`
			FROM `cms3_cluster_nodes_cache_keys` `nk`, `cms3_cluster_nodes` `n`
				WHERE `nk`.`node_id` = ''
SQL;
			l_mysql_query($sql);
			
			//Delete temporary data
			l_mysql_query("DELETE FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = ''");
			l_mysql_query("COMMIT");
		}
		
		/**
			* ���������������� ������������� ������ ���� ����� ������.
			* ������� ��������������� �� ������ ����� ������.
		*/
		public function init() {
			if(($this->loadNodeId()) == false) {
				$this->bringUp();
				$this->loadNodeId();
			}
			
			$this->loadKeys();
			$this->cleanup();
		}
		
		/**
			* ��������� ������ ������ �� ��������
		*/
		protected function loadKeys() {
			$cache = cacheFrontend::getInstance();
			$nodeId = $this->getNodeId();
			
			$sql = "SELECT DISTINCT `key` FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = '{$nodeId}'";
			$result = mysql_unbuffered_query($sql);
			
			$keys = Array();
			while(list($key) = mysql_fetch_row($result)) {
				$cache->deleteKey($key, true);
				$keys[] = $key;
			}
			mysql_free_result($result);
			
			$node_id = $this->getNodeId();
			$sql = "DELETE FROM `cms3_cluster_nodes_cache_keys` WHERE `node_id` = '{$nodeId}' AND `key` IN ('" . implode("', '", $keys). "')";
			l_mysql_query($sql);
		}
		
		/**
			* �������� id ������� ���� � ��������
			* @return Boolean false � ������ ������
		*/
		protected function loadNodeId() {
			$serverIp = l_mysql_real_escape_string($_SERVER['SERVER_ADDR']);
			$result = l_mysql_query("SELECT `id` FROM `cms3_cluster_nodes` WHERE `node_ip` = '{$serverIp}'");
			
			if(mysql_num_rows($result)) {
				list($nodeId) = mysql_fetch_row($result);
				$this->nodeId = $nodeId;
				return true;
			} else {
				$sql = "INSERT INTO `cms3_cluster_nodes` (`node_ip`) VALUES ('{$serverIp}')";
				l_mysql_query($sql);
				$this->nodeId = l_mysql_insert_id();
				return true;
			}
		}
		
		/**
			* ������� ����������� �������
		*/
		protected function bringUp() {
			$sql = <<<SQL
CREATE TABLE `cms3_cluster_nodes_cache_keys` (
	`node_id` INT DEFAULT NULL,
	`key` VARCHAR(255) NOT NULL,

	KEY `node_id` (`node_id`),
	KEY `key` (`key`)
) ENGINE=InnoDB
SQL;
			l_mysql_query($sql);
			
			$sql = <<<SQL
CREATE TABLE `cms3_cluster_nodes` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`node_ip` VARCHAR(16) NOT NULL,

	KEY `node_id` (`id`),
	KEY `node_ip` (`node_ip`)
) ENGINE=InnoDB
SQL;
			l_mysql_query($sql);
		}
	};
?>
