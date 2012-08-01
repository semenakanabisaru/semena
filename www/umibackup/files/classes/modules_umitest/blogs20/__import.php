<?php
abstract class __import {	
	public function import_old_blogs() {
		// Initializing collections
		$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
		$typesCollection 		  = umiObjectTypesCollection::getInstance();
		$hierarchy  = umiHierarchy::getInstance();
		$objects    = umiObjectsCollection::getInstance();
		// Loading types info
		$blog20HType 		= $hierarchyTypesCollection->getTypeByName('blogs20', 'blog');
		$blog20Type  		= $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blog20HType->getId()) );
		$blog20PostHType 	= $hierarchyTypesCollection->getTypeByName('blogs20', 'post');
		$blog20PostType  	= $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blog20PostHType->getId()) );
		$blog20CommentHType = $hierarchyTypesCollection->getTypeByName('blogs20', 'comment');
		$blog20CommentType  = $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blog20CommentHType->getId()) );
		$blogHType   		= $hierarchyTypesCollection->getTypeByName('blogs', 'blog');
		$blogType    		= $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blogHType->getId()) );
		$blogPostHType   	= $hierarchyTypesCollection->getTypeByName('blogs', 'blog_message');
		$blogPostType    	= $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blogHType->getId()) );
		$blogCommentHType   = $hierarchyTypesCollection->getTypeByName('blogs', 'blog_comment');
		$blogCommentType    = $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($blogHType->getId()) );		
		// Collecting all blogs
		$selection = new umiSelection();
		$selection->addElementType($blogHType->getId());
		$blogList  = umiSelectionsParser::runSelection($selection);
		// Processing each blog
		foreach($blogList as $blogId) {
			$blog      = $hierarchy->getElement($blogId, true, false);
			$newBlogId = $this->makeElementFromExisting($blogId, $blog->getRel(), $blog20HType->getId(), 
														array('content'=>'description', 'prvlist_friends'=>'friendlist'));
			$selection = new umiSelection();
			$selection->addElementType($blogPostHType->getId());
			$selection->addHierarchyFilter($blogId);
			$postList  = umiSelectionsParser::runSelection($selection);
			foreach($postList as $postId) {
				$newPostId = $this->makeElementFromExisting($postId, $newBlogId, $blog20PostHType->getId());
				$this->import_comments($postId, $newPostId, $blogCommentHType->getId(), $blog20CommentHType->getId());				
			}
		}
		regedit::getInstance()->setVar("//modules/blogs20/import/old", 1);
		$this->chooseRedirect( getServer('HTTP_REFERER') );
		return null; // Never pass into here
	}
	public function import_comments($from, $to, $fromHTypeId, $toHTypeId) {
		$selection = new umiSelection();
		$selection->addElementType($fromHTypeId);
		$selection->addHierarchyFilter($from);
		$commentList  = umiSelectionsParser::runSelection($selection);    
		foreach($commentList as $commentId) {
			$newCommentId = $this->makeElementFromExisting($commentId, $to, $toHTypeId);
			$this->import_comments($commentId, $newCommentId, $fromHTypeId, $toHTypeId);
		}		
	}
	public function makeElementFromExisting($sourceId, $parentId, $newHTypeId, $additionalCopyPairs = array()) {		
		static $oldPrivacyFriends = 0;
		static $oldPrivacyOwner   = 0;
		$hierarchy  = umiHierarchy::getInstance();
		$objects    = umiObjectsCollection::getInstance();
		$typesCollection = umiObjectTypesCollection::getInstance();
		// Prepare privacy values
		if(!($oldPrivacyFriends && $oldPrivacyOwner)) {
			$privacyType   = $typesCollection->getBaseType('blogs', 'blog_privacy');
			$privacyValues = $objects->getGuidedItems($privacyType);
			foreach($privacyValues as $privacyKindId => $privacyKindName) {
				if($privacyKindName == 'friends') $oldPrivacyFriends = $privacyKindId;
				if($privacyKindName == 'owner')   $oldPrivacyOwner   = $privacyKindOwner;
			}
		}
		$newType    = $typesCollection->getType( $typesCollection->getTypeByHierarchyTypeId($newHTypeId) );
		// Clone element data (relations etc.)		
		$source = $hierarchy->getElement($sourceId, true, false);
		$newId  = $hierarchy->copyElement($sourceId, $parentId, false);		
		$new = $hierarchy->getElement($newId);
		$new->setTypeId($newHTypeId);
		// Creating the tata object
		$sourceObject = $new->getObject();			
		$newObjectId  = $objects->addObject($sourceObject->getName(), $newType->getId());
		$newObject    = $objects->getObject($newObjectId);		
		// Copying the data
		$objectFields = $newType->getAllFields();
		foreach($objectFields as $field) {
			$value = $sourceObject->getValue( $field->getName() );
			if($value !== false) {
				$newObject->setValue($field->getName(), $value);
			}
		}		
		foreach($additionalCopyPairs as $fromFieldName => $toFieldName) {
			$newObject->setValue($toFieldName, $sourceObject->getValue($fromFieldName));			
		}
		switch($sourceObject->getValue('privacy')) {
			case $oldPrivacyFriends: $newObject->setValue('only_for_friends', 1); break;
			case $oldPrivacyOwner  : $new->setIsActive(false); break;
		}
		$newObject->setOwnerId( $sourceObject->getOwnerId() );
		$new->setObject($newObject);
		return $newId;
	}
}
?>