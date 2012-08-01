<?php
	abstract class __author_users {
		public function createAuthorUser($user_id) {
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			
			if($objects->isExists($user_id) === false) {
				return false;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'author');
			$sel->where('user_id')->equals($user_id);
			$sel->limit(0, 1);
			
			if($sel->first) {
				return $sel->first->id;
			} else {
				$user_object = $objects->getObject($user_id);
				$user_name = $user_object->getName();

				$object_type_id = $objectTypes->getBaseType("users", "author");
				$author_id = $objects->addObject($user_name, $object_type_id);
				$author = $objects->getObject($author_id);
				$author->is_registrated = true;
				$author->user_id = $user_id;
				$author->commit();

				return $author_id;
			}
		}

		public function createAuthorGuest($nick, $email, $ip) {
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			
			$nick = trim($nick);
			$email = trim($email);
			
			if(!$nick) $nick = getLabel('author-anonymous');
			if(!$email) $email = getServer('REMOTE_ADDR');
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'author');
			$sel->where('email')->equals($email);
			$sel->where('nickname')->equals($nick);
			$sel->where('ip')->equals($ip);
			$sel->limit(0, 1);

			if($sel->first) {
				return $sel->first->id;
			} else {
				$user_name = $nick . " ({$email})";

				$object_type_id = $objectTypes->getBaseType("users", "author");
				$author_id = $objects->addObject($user_name, $object_type_id);
				$author = $objects->getObject($author_id);
				$author->name = $user_name;
				$author->is_registrated = false;
				$author->nickname = $nick;
				$author->email = $email;
				$author->ip = $ip;
				$author->commit();

				return $author_id;
			}
		}

		public function viewAuthor($author_id = false, $template = "default") {
			if($author_id === false) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $author_id));
			}

			if(!($author = umiObjectsCollection::getInstance()->getObject($author_id))) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $author_id));
			}             

			if(!$template) $template = "default";
			list($template_user, $template_guest, $template_sv) = def_module::loadTemplates("users/author/{$template}", "user_block", "guest_block", "sv_block");
            
			$block_arr = Array();
			if($author->getTypeId() == umiObjectTypesCollection::getInstance()->getBaseType('users', 'user')) {
				$template = $template_user;
				$block_arr['user_id'] = $author_id;

				$user = $author;								

				$block_arr['nickname'] = $user->getValue("login");
				$block_arr['email']    = $user->getValue("e-mail");
				$block_arr['fname']    = $user->getValue("fname");
				$block_arr['lname']    = $user->getValue("lname");
				
				$block_arr['subnodes:groups'] = $groups = $user->getValue("groups");
				if(in_array(SV_GROUP_ID, $groups)) {
					if($template_sv) {
						$template = $template_sv;
					}
				}                
			} else if($author->getValue("is_registrated")) {
				$template = $template_user;
				$block_arr['user_id'] = $user_id = $author->getValue("user_id");

				$user = umiObjectsCollection::getInstance()->getObject($user_id);

				if (!$user instanceof umiObject) {
					$block_arr['user_id'] = $user_id = intval(regedit::getInstance()->getVal("//modules/users/guest_id"));
					$user = umiObjectsCollection::getInstance()->getObject($user_id);
				}

				if (!$user instanceof umiObject) return false;

				$block_arr['nickname'] = $user->getValue("login");
				$block_arr['login'] = $user->getValue("login");
				$block_arr['email'] = $user->getValue("e-mail");
				$block_arr['fname'] = $user->getValue("fname");
				$block_arr['lname'] = $user->getValue("lname");
				
				$block_arr['subnodes:groups'] = $groups = $user->getValue("groups");
				if(in_array(SV_GROUP_ID, $groups)) {
					if($template_sv) {
						$template = $template_sv;
					}
				}                
			} else {
				$template = $template_guest;
				$block_arr['nickname'] = $author->getValue("nickname");
				$block_arr['email'] = $author->getValue("email");
			}
			return def_module::parseTemplate($template, $block_arr, false, $author_id);
		}
	};
?>