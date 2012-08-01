<?php
	class alphabeticalIndex {
		public static
			$letters = "абвгдежзийклмнопрстуфхцчшщыэюяabcdefghijklmnopqrstuvwxyz0123456789";
		
		protected
			$selector = null,
			$index = null;
		
		public function __construct(selector $sel) {
			$this->selector = $sel;
		}
		
		public function index($pattern = 'a-zа-я0-9') {
			$index = $this->run();
			
			$result = array(); 
			if(preg_match_all("/(([A-zА-я0-9])-([A-zА-я0-9]))/u", $pattern, $out)) {
				for($i = 0; $i < sizeof($out[2]); $i++) {
					$from = wa_strpos(self::$letters, $out[2][$i]);
					$to = wa_strpos(self::$letters, $out[3][$i]);
					
					if($from === false || $to === false) continue;
					
					for($j = $from; $j <= $to; $j++) {
						$char = wa_substr(self::$letters, $j, 1);
						$result[$char] = isset($index[$char]) ? $index[$char] : 0;
					}
				}
			}
			return $result;
		}
		
		protected function run() {
			$permissions = permissionsCollection::getInstance();
			$isSv = $permissions->isSv();
			$mode = $this->selector->mode;
			
			l_mysql_query("START TRANSACTION /* Get alphabetical index */");
			l_mysql_query("DROP TABLE IF EXISTS `alphabetical_index`");
			
			$sql = "CREATE TABLE `alphabetical_index` (";
			$sql .= "id int  unsigned not null)";
			
			l_mysql_query($sql);
			
			$query = $this->selector->query();
			$sql = "INSERT INTO `alphabetical_index` {$query}";
			
			l_mysql_query($sql);
			
			if($mode == 'pages') {
				$sql = <<<SQL
SELECT LEFT(LOWER(`o`.`name`), 1) AS `letter`, COUNT(*) AS `cnt`
	FROM `alphabetical_index` `ai`, `cms3_hierarchy` `h`, `cms3_objects` `o`
	WHERE `h`.`id` = `ai`.`id` AND `o`.`id` = `h`.`obj_id`
	GROUP BY `letter`
	ORDER BY `letter`;
SQL;
			} else {
				$sql = <<<SQL
SELECT SQCL_CACHE LEFT(LOWER(`o`.`name`), 1) AS `letter`, COUNT(*) AS `cnt`
	FROM `alphabetical_index` `ai`, `cms3_objects` `o`
	WHERE `o`.`id` = `ai`.`id`
	GROUP BY `letter`
	ORDER BY `letter`;
SQL;
			}
			
			$result = l_mysql_query($sql);
			$index = array();
			while(list($letter, $count) = mysql_fetch_row($result)) {
				$index[$letter] = (int) $count;
			}
			
			l_mysql_query("DROP TABLE IF EXISTS `alphabetical_index`");
			l_mysql_query("COMMIT");
			
			return $index;
		}
	};
?>