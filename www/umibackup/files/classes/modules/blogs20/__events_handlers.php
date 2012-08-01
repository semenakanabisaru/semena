<?php
abstract class __eventsHandlersBlogs {
	public function onCommentAdd(iUmiEventPoint $oEventPoint) {		
		$regedit = regedit::getInstance(); 
		if(!$regedit->getVal("//modules/blogs20/notifications/on_comment_add")) {
			return;
		}
		$template	= ($tmp = $oEventPoint->getParam('template')) ? $tmp : 'default';
		$commentId  = $oEventPoint->getParam('id');		
		
		$hierarchy  = umiHierarchy::getInstance();
		$collection = umiObjectsCollection::getInstance();
		$parentId   = $hierarchy->getElement($commentId, true)->getRel();		
		$element     = $hierarchy->getElement($parentId);
		$postHtypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'post')->getId();
		$post = $element;
		
		if($post instanceof umiHierarchyElement == false) {			
			return false;
		}
		while($post->getTypeId() != $postHtypeId) $post = $hierarchy->getElement($post->getRel(), true);
		if($element->getTypeId() == $postHtypeId) {
			$parentOwner = $collection->getObject( $element->getObject()->getOwnerId() );
			if($parentOwner instanceof umiObject == false) return false;
			$email = $parentOwner->getValue('e-mail');
			$nick  = $parentOwner->getValue('login');
			$fname = $parentOwner->getValue('fname');
			$lname = $parentOwner->getValue('lname');
			$patr  = $parentOwner->getValue('father_name');
			$name  = strlen($fname) ? ($fname . ' ' . $patr . ' ' . $lname) : $nick;
			list($tplSubject, $tplBody) = $this->loadTemplates('blogs20/mail/'.$template, 'comment_for_post_subj', 'comment_for_post_body');
		} else {
			$parentOwner = $collection->getObject( $element->getValue('author_id') );
			if($parentOwner->getValue('is_registrated')) {
				$user  = $collection->getObject( $parentOwner->getValue('user_id') );
				$email = $user->getValue('e-mail');
				$nick  = $user->getValue('login');
				$fname = $user->getValue('fname');
				$lname = $user->getValue('lname');
				$patr  = $user->getValue('father_name');
				$name  = strlen($fname) ? ($fname . ' ' . $patr . ' ' . $lname) : $nick;
			} else {				
				$email = $parentOwner->getValue('email');
				$name  = $parentOwner->getValue('nickname');				
			}
			list($tplSubject, $tplBody) = $this->loadTemplates('blogs20/mail/'.$template, 'comment_for_comment_subj', 'comment_for_comment_body');
		}
		$aParams = array();
		$aParams['name'] = $name;
		$aParams['link'] = 'http://' . cmsController::getInstance()->getCurrentDomain()->getHost() . $hierarchy->getPathById($post->getId()) . '#comment_' . $commentId;
		$subject   = def_module::parseContent($tplSubject, $aParams);
		$body	   = def_module::parseContent($tplBody,    $aParams);
		$fromEmail = $regedit->getVal("//settings/email_from");
		$fromName  = $regedit->getVal("//settings/fio_from");
		$oMail = new umiMail();
		$oMail->addRecipient( $email, $name );
		$oMail->setFrom( $fromEmail, $fromName );
		$oMail->setSubject($subject);
		$oMail->setContent($body);
		$oMail->commit();
		$oMail->send();
	}
}
?>
