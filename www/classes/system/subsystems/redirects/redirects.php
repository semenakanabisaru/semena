<?php
	class redirects implements iRedirects {
		/**
			* Получить экземпляр коллекции
			* @return iRedirects экземпляр коллекции
		*/
		public static function getInstance() {
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
			
			$source = l_mysql_real_escape_string($this->parseUri($source));
			$target = l_mysql_real_escape_string($this->parseUri($target));
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
			$sourceSQL = l_mysql_real_escape_string($this->parseUri($source));
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
			$targetSQL = l_mysql_real_escape_string($this->parseUri($target));
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
			$currentUri = l_mysql_real_escape_string($this->parseUri($currentUri));
			
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
			$uriParts = explode("/", trim($currentUri, "/"));
			do {
				array_pop($uriParts);
				$subUri = implode("/", $uriParts) . "/";
				$subUriSQL = l_mysql_real_escape_string($this->parseUri($subUri));
				
				if(!strlen($subUriSQL)) {
					if(count($uriParts)) continue;
					else break;
				}
				
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
?>