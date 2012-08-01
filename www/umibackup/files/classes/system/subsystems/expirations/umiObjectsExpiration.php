<?php
	class umiObjectsExpiration extends singleton implements iSingleton, iUmiObjectsExpiration {
		protected $defaultExpires = 86400;
		
		protected function __construct() {
			
		}
		
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}
		
		public function run() {
			$time = time();
			
			$sql = <<<SQL
DELETE FROM `cms3_objects`
	WHERE `id` IN (
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
?>