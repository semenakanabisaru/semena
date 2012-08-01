<?php
	class umiMessage extends umiEntinty implements iUmiEntinty, iUmiMessage {
		protected $store_type = 'message', $title, $content, $senderId, $createTime, $type, $priority, $isSended;

		public function getTitle() {
			return $this->title;
		}
		
		public function setTitle($title) {
			$this->title = (string) $title;
			$this->setIsUpdated();
		}
		
		public function getContent() {
			return $this->content;
		}
		
		public function setContent($content) {
			$this->content = (string) $content;
			$this->setIsUpdated();
		}
		
		public function getSenderId() {
			return $this->senderId;
		}
		
		public function setSenderId($senderId = null) {
			$this->senderId = (int) $senderId;
			$this->setIsUpdated();
		}
		
		public function getType() {
			return $this->type;
		}
		
		public function setType($type) {
			if(in_array($type, umiMessages::getAllowedTypes()) == false) {
				throw new coreException("Unkown message type \"{$type}\"");
			}
			
			$this->type = (string) $type;
			$this->setIsUpdated();
		}
		
		public function getPriority() {
			return $this->priority;
		}
		
		public function setPriority($priority = 0) {
			$this->priority = (int) $priority;
			$this->setIsUpdated();
		}
		
		public function getCreateTime() {
			return $this->createTime;
		}
		
		public function setCreateTime($time) {
			$this->createTime = ($time instanceof umiDate) ? $time : new umiDate($time);
			$this->setIsUpdated();
		}
		
		public function getIsSended() {
			return $this->isSended;
		}
		
		public function getRecipients() {
			$sql = <<<SQL
SELECT `recipient_id` FROM `cms3_messages_inbox` WHERE `message_id` = '{$this->id}'
SQL;
			$result = l_mysql_query($sql);
			
			$recipients = array();
			while(list($recipientId) = mysql_fetch_row($result)) {
				$recipients[] = $recipientId;
			}
			return $recipients;
		}
		
		public function send($recipients) {
			if($this->getIsSended()) {
				return false;
			}
			
			if(sizeof($recipients)) {
				$recipientsSql = implode(", ", array_map('intval', $recipients));
				
				$sql = <<<SQL
INSERT INTO `cms3_messages_inbox`
	(`message_id`, `recipient_id`)
		SELECT '{$this->id}', `id` FROM `cms3_objects`
			WHERE `id` IN ({$recipientsSql})
SQL;
				l_mysql_query($sql);
			}
			$this->setIsSended(true);
			$this->setIsUpdated();
		}
		
		public function setIsOpened($isOpened, $userId = false) {
			if($userId == false) {
				$userId = permissionsCollection::getInstance()->getUserId();
			} else {
				$userId = (int) $userId;
			}
			$isOpened = (int) $isOpened;
			
			$sql = <<<SQL
UPDATE `cms3_messages_inbox` SET `is_opened` = '{$isOpened}' WHERE `message_id` = '{$this->id}' AND `recipient_id` = '{$userId}'
SQL;
			l_mysql_query($sql);
		}
		
		private function setIsSended($isSended) {
			$this->isSended = (bool) $isSended;
		}

		protected function loadInfo() {
			$sql = <<<SQL
SELECT `title`, `content`, `sender_id`, `create_time`, `type`, `priority`, `is_sended`
	FROM `cms3_messages` WHERE `id` = '{$this->id}'
SQL;
			$result = l_mysql_query($sql);
			if(list($title, $content, $senderId, $createTime, $type, $priority, $isSended) = mysql_fetch_row($result)) {
				$this->title = (string) $title;
				$this->content = (string) $content;
				$this->senderId = (int) $senderId;
				$this->createTime = new umiDate($createTime);
				$this->type = (string) $type;
				$this->priority = (int) $priority;
				$this->isSended = (bool) $isSended;
			}
		}
		
		protected function save() {
			$title = l_mysql_real_escape_string($this->title);
			$content  = l_mysql_real_escape_string($this->content);
			$senderId = $this->senderId ? $this->senderId : 'NULL';
			$createTime = $this->createTime->getDateTimeStamp();
			$priority = (int) $this->priority;
			$type = $this->type;
			$isSended = (int) $this->isSended;
			
			$sql = <<<SQL
UPDATE `cms3_messages`
	SET `title` = '{$title}', `content` = '{$content}',
		`create_time` = '{$createTime}', `priority` = '{$priority}',
		`type` = '{$type}', `sender_id` = {$senderId}, `is_sended` = '{$isSended}'
			WHERE `id` = '{$this->id}'
SQL;
			l_mysql_query($sql);
		}
	};
?>