<?php
	abstract class __custom_users {
		//TODO: Write here your own macroses
		public function oldPass() {
       $result = 0;

			 $objects = umiObjectsCollection::getInstance();
			 $permissions = permissionsCollection::getInstance();
 			 $userId = $permissions->getUserId();
       if ($userId) {
     			 $old = (string) htmlspecialchars(getRequest('oldpass'));
           $object = $objects->getObject($userId);
           if ($object->getValue("password") == md5($old)) {
           	$result = 1; 
           }
       }
       return $result;
		}
	};
	
?>