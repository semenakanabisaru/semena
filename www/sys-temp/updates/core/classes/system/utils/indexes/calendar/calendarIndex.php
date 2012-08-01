<?php
	class calendarIndex {
		public
			$timeStart, $timeEnd;

		protected
			$selector = null,
			$index = null,
			$year, $month;

		public function __construct(selector $sel) {
			$this->selector = $sel;
		}

		public function index($fieldName, $year = null, $month = null) {
			$this->setFieldName($fieldName);

			$this->year = $year ? (int) $year : date('Y');
			$this->month = $month ? (int) $month : date('m');

			$this->timeStart = strtotime($this->year . '-' . $this->month . '-' . 1);
			$this->timeEnd = strtotime($this->year . '-' . ($this->month + 1) . '-' . 1);

			$this->selector->where($fieldName)->between($this->timeStart, $this->timeEnd);
			$index = $this->run();

			$result = array();
			$days = round($this->timeEnd - $this->timeStart) / (3600*24);
			for($i = 1; $i <= $days; $i++) {
				$result[$i] = (int) (isset($index[$i]) ? $index[$i] : 0);
			}

			return array(
				'year'		=> $this->year,
				'month'		=> $this->month,
				'first-day'	=> ((int) date("w", $this->timeStart) + 6) % 7,
				'days'		=> $result
			);
		}

		protected function run() {
			$permissions = permissionsCollection::getInstance();
			$isSv = $permissions->isSv();
			$mode = $this->selector->mode;

			l_mysql_query("START TRANSACTION /* Get calendar index */");
			l_mysql_query("DROP TABLE IF EXISTS `calendar_index`");

			$sql = "CREATE TABLE `calendar_index` (";
			$sql .= "id int  unsigned not null,
			`rel_id` int(10) unsigned DEFAULT NULL)";

			l_mysql_query($sql);

			$query = $this->selector->query();

			l_mysql_query("INSERT INTO `calendar_index` {$query}");

			$fieldId = $this->fieldId;
			$tableName = selectorExecutor::getContentTableName($this->selector, $fieldId);

			if($mode == 'pages') {
				$sql = <<<SQL
SELECT
	COUNT(`h`.`id`),
	DATE_FORMAT(FROM_UNIXTIME(`oc`.`int_val`), '%d') as `day`
FROM
	`calendar_index` `tmp`,
	`cms3_objects` `o`,
	`cms3_hierarchy` `h`,
	`cms3_object_content` `oc`
WHERE
	`h`.`id` = `tmp`.`id` AND
	`o`.`id` = `h`.`obj_id` AND
	`oc`.`obj_id` = `o`.`id` AND
	`oc`.`field_id` = '{$fieldId}' AND
	`oc`.`int_val` BETWEEN '{$this->timeStart}' AND '{$this->timeEnd}'
GROUP BY
	`day`
ORDER BY
	`day` ASC
SQL;
			} else {
				$sql = <<<SQL
SELECT
	COUNT(`o`.`id`),
	DATE_FORMAT(FROM_UNIXTIME(`oc`.`int_val`), '%d') as `day`
FROM
	`calendar_index` `tmp`,
	`cms3_objects` `o`,
	`cms3_object_content` `oc`
WHERE
	`o`.`id` = `tmp`.`id` AND
	`oc`.`obj_id` = `o`.`id` AND
	`oc`.`field_id` = '{$fieldId}' AND
	`oc`.`int_val` BETWEEN '{$this->timeStart}' AND '{$this->timeEnd}'
GROUP BY
	`day`
ORDER BY
	`day` ASC
SQL;
			}

			$result = l_mysql_query($sql);
			$index = array();
			while(list($count, $day) = mysql_fetch_row($result)) {
				$index[(int) $day] = $count;
			}

			l_mysql_query("DROP TABLE IF EXISTS `calendar_index`");
			l_mysql_query("COMMIT");

			return $index;
		}

		protected function setFieldName($fieldName) {
			if($fieldId = $this->selector->searchField($fieldName))
				$this->fieldId = $fieldId;
			else throw new coreException("No field \"{$fieldName}\" not found in selector types list");
		}
	};
?>