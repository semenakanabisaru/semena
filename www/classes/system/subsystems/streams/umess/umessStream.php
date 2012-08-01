<?php
	class umessStream extends umiBaseStream {
		protected $scheme = "umess", $group_name = NULL, $field_name = NULL;


		public function stream_open($path, $mode, $options, $opened_path) {
			$params = $this->parsePath($path);
			$messages = umiMessages::getInstance();
			
			switch($params['folder']) {
				case 'inbox': {
					$messagesList =  $messages->getMessages($params['user-id'], true);
					break;
				}
				
				case 'outbox': {
					$messagesList = $messages->getSendedMessages($params['user-id']);
					break;
				}
				
				default: {
					$messagesList = false;
				}
			}
			
			if(isset($this->params['limit'])) {
				$limit = (int) $this->params['limit'];
				$messagesList = array_slice($messagesList, 0, $limit);
			}
			
			if(is_array($messagesList)) {
				$data = $this->translateToXml($messagesList);
				$this->setData($data);
				return true;
			} else {
				return $this->setDataError('not-found');
			}
		}
		
		protected function parsePath($path) {
			$folderName = false;
			$userId = false;
			
			$path = parent::parsePath($path);
			$arr = explode("/", $path);
			
			
			if(sizeof($arr) >= 1) $folderName = $arr[0];
			if(sizeof($arr) >= 2) $userId  = $arr[1];

			$result = Array(
				'folder' => $folderName,
				'user-id' => $userId
			);
			
			return $result;
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$messages = $args[0];

			$items = array();
			$markAllAsOpened = (bool) getRequest('mark-as-opened');
			
			foreach($messages as $message) {
				if($markAllAsOpened) $message->setIsOpened(true);
				$items[] = $this->translateMessageToXml($message);
			}
			
			$result = array(
				'messages' => array('nodes:message' => $items)
			);
			
			return parent::translateToXml($result);
		}
		
		protected function translateMessageToXml(iUmiMessage $message) {
			$objects = umiObjectsCollection::getInstance();
			$sender = $objects->getObject($message->getSenderId());
			
			$result = Array(
				'attribute:id'			=> $message->getId(),
				'attribute:title'		=> $message->getTitle(),
				'attribute:type'		=> $message->getType(),
				'attribute:priority'	=> $message->getPriority(),
				'date'					=> array(
					'attribute:unix-timestamp'	=> $message->getCreateTime()->getDateTimestamp(),
					'node:value'				=> $message->getCreateTime()->getFormattedDate()
				),
				'sender'				=> $sender,
				'content'				=> $message->getContent()
			);
			return $result;
		}
	};
?>