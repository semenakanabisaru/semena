<?php
	class umiMessages extends singleton implements iSingleton, iUmiMessages {
		private static $messageTypes = Array('private', 'sys-event', 'sys-log');

		protected function __construct() {
			//Do nothing here
		}


		public function getMessages($senderId = false, $onlyNew = false) {
			$userId = $this->getCurrentUserId();
			$senderId = (int) $senderId;

			$conds = $senderId ? " AND m.`sender_id` = '{$senderId}'" : "";
			$conds = $onlyNew ? " AND mi.`is_opened` = 0" : "";


			$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m, `cms3_messages_inbox` mi
		WHERE mi.`recipient_id` = '{$userId}' AND m.`id` = mi.`message_id` {$conds}
			ORDER BY m.`create_time` DESC
SQL;
			$result = l_mysql_query($sql);
			$messages = Array();
			while(list($messageId) = mysql_fetch_row($result)) {
				$messages[] = new umiMessage($messageId);
			}
			return $messages;
		}

		public function getSendedMessages($recipientId = false) {
			$userId = $this->getCurrentUserId();
			$recipientId = (int) $recipientId;

			if($recipientId) {
				$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m, `cms3_messages_inbox` mi
		WHERE m.`sender_id` = '{$userId}' AND mi.`recipient_id` = '{$recipientId}' AND m.`id` = mi.`message_id`
			ORDER BY m.`create_time` DESC
SQL;
			} else {
				$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m
		WHERE m.`sender_id` = '{$userId}'
			ORDER BY m.`create_time` DESC
SQL;
			}

			$result = l_mysql_query($sql);
			$messages = Array();
			while(list($messageId) = mysql_fetch_row($result)) {
				$messages[] = new umiMessage($messageId);
			}
			return $messages;
		}


		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function create($type = 'private') {
			$senderId = $this->getCurrentUserId();

			if($this->checkMessageType($type) == false) {
				throw new coreException('Unkown message type \"{$messageType}\"');
			}

			$time = time();

			$sql = <<<SQL
INSERT INTO `cms3_messages` (`sender_id`, `create_time`, `type`)
	VALUES ('{$senderId}', '{$time}', '{$type}')
SQL;
			l_mysql_query($sql);

			$messageId = l_mysql_insert_id();
			return new umiMessage($messageId);
		}

		static public function getAllowedTypes() {
			return self::$messageTypes;
		}

		private function getCurrentUserId() {
			$permissions = permissionsCollection::getInstance();
			return $permissions->getUserId();
		}

		private function checkMessageType($messageType) {
			return in_array($messageType, self::getAllowedTypes());
		}
	};
?>
