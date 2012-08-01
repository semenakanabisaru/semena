<?php

abstract class __loginza_users {
	
	public function  getLoginzaProvider() {
		$loginzaAPI = new loginzaAPI();
		
		$result = Array(); 
		foreach($loginzaAPI->getProvider() as $k=>$v) {
			$result['providers']['nodes:provider'][] = array('attribute:name'=>$k, 'attribute:title'=>$v);
		}
		
		$result ['widget_url'] = $loginzaAPI->getWidgetUrl() . "&providers_set=google,yandex,mailru,vkontakte,facebook,twitter,loginza,rambler,lastfm,myopenid,openid,mailruapi";

		return $result;
	}
 
 	public function loginza($template = 'default') {
		if(!empty($_POST['token']) ) {
			$loginzaAPI = new loginzaAPI();
			
			$profile = $loginzaAPI->getAuthInfo($_POST['token']);
			
			if( !empty($profile)) {
				 $profile = new loginzaUserProfile($profile); 
				 
				 $objectTypes = umiObjectTypesCollection::getInstance();
				 $objectTypeId	= $objectTypes->getBaseType("users",	"user");
				 $objectType = $objectTypes->getType($objectTypeId);
				
				$nickname = $profile->genNickname();
				$provider = $profile->genProvider();
				$provider_url = parse_url($provider);
				$provider_name = str_ireplace('www.', '', $provider_url['host']);
				$login = $nickname . "@" . $provider_name;
				 $password = $profile->genRandomPassword();
				 $email = $profile->genUserEmail();
				 $lname = $profile->getLname();
				 $fname = $profile->getFname();  
				 
				if(!$fname) $fname=$nickname;
				
				 $sel = new selector('objects');
				 $sel->types('object-type')->name('users', 'user');
				 $sel->where('login')->equals($login);
				$sel->where('loginza')->equals($provider_name);
				 $user =  $sel->first;
				 $from_page = getRequest("from_page");

				 if($user instanceof iUmiObject) {
				 	permissionsCollection::getInstance()->loginAsUser($user);
				 	session_commit();
				 
				 	$this->redirect($from_page ? $from_page : ($this->pre_lang . '/users/auth/'));
				 }
				
				 if(!preg_match("/.+@.+\..+/", $email)) {
					  while(true) {
						$email = $nickname.rand(1,100)."@".getServer('HTTP_HOST');
						if($this->checkIsUniqueEmail($email)) {
							 break;
						}	
					  }
				 }
				 
				$object_id = umiObjectsCollection::getInstance()->addObject($login, $objectTypeId);
				$object = umiObjectsCollection::getInstance()->getObject($object_id);

				$object->setValue("login", $login);
				$object->setValue("password", md5($password));
				$object->setValue("e-mail", $email);		
				$object->setValue("fname", ($fname));
				$object->setValue("lname", $lname);		
				$object->setValue("loginza", $provider_name);		

				$object->setValue("is_activated", '1');
				$object->setValue("activate_code", '');
					
				$_SESSION['cms_login'] = $login;
				$_SESSION['cms_pass'] = md5($password);
				$_SESSION['user_id'] = $object_id;
				session_commit();

				$group_id = regedit::getInstance()->getVal("//modules/users/def_group");
				$object->setValue("groups", Array($group_id));
				
				
				$data_module = cmsController::getInstance()->getModule('data');
				$data_module->saveEditedObject($object_id, true);

				$object->commit();
				
				$this->redirect($from_page ? $from_page : ($this->pre_lang . '/users/auth/'));
			}
		}
		return $this->auth();
	}
}
