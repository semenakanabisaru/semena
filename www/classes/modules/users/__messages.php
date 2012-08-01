<?php
	abstract class __messages_users {
		
		public function loadUserMessages() {
			$permissions = permissionsCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$user_id = $permissions->getUserId();
			$user = $objects->getObject($user_id);
			if($user instanceof umiObject == false) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}

			$block_arr = array();
			$items = array();

			if ($permissions->isSv($user->getId())) {
				$regedit = regedit::getInstance();

				if (file_exists(SYS_CACHE_RUNTIME.'umessages')) {
					$messages = unserialize(file_get_contents(SYS_CACHE_RUNTIME.'umessages'));
				}
				else {
					$messages = array();
				}

				$newMessages = $this->getNewUmiMessages();

				if (count($newMessages) > 0) {
					foreach($newMessages as $id=>$text) {
						$messages[$id] = $text;
					}
					$regedit->setVal("//umiMessages/lastMessageId", max(array_keys($newMessages)));
					file_put_contents(SYS_CACHE_RUNTIME.'umessages', serialize($messages));
				}

				if (count($messages) > 0) {
					$settings_data = $user->user_settings_data;
					$settings_data_arr = unserialize($settings_data);
					if(!is_array($settings_data_arr)) {
						$settings_data_arr = array();
					}

					if (!(isset($settings_data_arr['umiMessages']['notShow']) && $settings_data_arr['umiMessages']['notShow'] == 'true')) {

						if (isset($settings_data_arr['umiMessages']['closed']) && $settings_data_arr['umiMessages']['closed'] != '') {
							// Делаем фильтрацию сообщений
							$values = explode(';', $settings_data_arr['umiMessages']['closed']);
							foreach($values as $value) {
								unset($messages[$value]);
							}
							if (count($messages) >= 1) {
								$activeMessageId = max(array_keys($messages));
							}
							else {
								$activeMessageId = 0;
							}
						}
						else {
							// Выводим активное сообщение
							$activeMessageId = $regedit->getVal("//umiMessages/lastMessageId");
						}
						$item_arr = array();
						if (isset($messages[$activeMessageId])) {
							$item_arr['attribute:id'] = $activeMessageId;
							$item_arr['attribute:active'] = 'true';
							$item_arr['node:value'] = $messages[$activeMessageId];
							$items[] = $item_arr;
						}
					}
				}
			}

			$block_arr['messages']['nodes:message'] = $items;
			$block_arr['system']['edition'] = $regedit->getVal('//modules/autoupdate/system_edition');
			return $block_arr;
		}
		
		/**
		* Закрывает одно сообщение и сохраняет информацию об этом в профиле пользователя
		* 
		*/
		public function closeUmiMessage() {
			$this->flushAsXML("closeUmiMessage");

			$permissions = permissionsCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$user_id = $permissions->getUserId();
			$user = $objects->getObject($user_id);
			if($user instanceof umiObject == false) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}

			$settings_data = $user->getValue("user_settings_data");
			$settings_data = unserialize($settings_data);
			if(!is_array($settings_data)) {
				$settings_data = Array();
			}

			$value = (int) getRequest('value');

			if (isset($settings_data['umiMessages']['closed']) && $settings_data['umiMessages']['closed'] != '') {
				$closed = explode(';', $settings_data['umiMessages']['closed']);
				$closed[] = $value;
				$closed = array_unique($closed);
				$settings_data['umiMessages']['closed'] = implode(';', $closed);
			}
			else {
				$settings_data['umiMessages']['closed'] = $value;
			}

			$user->setValue("user_settings_data", serialize($settings_data));
			$user->commit();
		}

		/**
		* Загружает новые сообщения с сервера для данной системы.
		* 
		*/
		public function getNewUmiMessages() {
			$newMessages = array();
			$regedit = regedit::getInstance();
			$lastConnect = $regedit->getVal('//umiMessages/lastConnectTime');

			if (!$lastConnect || $lastConnect < time() - 86400) {
				$lastMessageId = $regedit->getVal('//umiMessages/lastMessageId');
				if (!$lastMessageId) {
					$lastMessageId = 0;
				}

				$info = array();
				$info['keycode'] = $regedit->getVal('//settings/keycode');
				$info['last-message-id'] = $lastMessageId;

				$package = base64_encode(serialize($info));

				$url = 'http://messages.umi-cms.ru/udata/custom/getUmiMessages/'.$package.'/';
				$result = umiRemoteFileGetter::get($url);

				if ($result) {
					$old = libxml_use_internal_errors(true);
					$xml = simplexml_load_string($result);
					if ( $xml && count($messages = $xml->xpath("//message")) > 0 ) {
						foreach($messages as $message) {
							$id = (string) $message->attributes()->id;
							$text = (string) $message;
							$newMessages[$id] = $text;
						}
					}
					libxml_use_internal_errors($old);
					$regedit->setVal('//umiMessages/lastConnectTime', time());
				}
			}
			return $newMessages;
		}

	}
?>
