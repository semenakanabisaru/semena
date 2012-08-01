<?php
abstract class __eventsHandlers {                                  
	public function onCloneElement(iUmiEventPoint $oEventPoint) {
		if($oEventPoint->getMode() == 'after') {
			/**
			* @var umiHierarchy
			*/
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $oEventPoint->getParam('newElementId');
			/**
			* @var umiHierarchyElement
			*/
			$element   = $hierarchy->getElement($elementId);
			if($element && 
			   $element->getTypeId() == umiHierarchyTypesCollection::getInstance()->getTypeByName('vote', 'poll')->getId()) {
			   	   $collection = umiObjectsCollection::getInstance();
			   	   $answersIDs = $element->getValue('answers');
			   	   $newAnswers = array();
			   	   foreach($answersIDs as $answerId) {
			   	   	   if($newAnswerId = $collection->cloneObject($answerId)) {
			   	   	   	   $newAnswers[] = $newAnswerId;
			   	   	   	   $answer       = $collection->getObject($newAnswerId);
			   	   	   	   $answer->setValue('poll_rel', $elementId);
			   	   	   	   $answer->setValue('count', 0);
			   	   	   	   $answer->commit();			   	   	   	   
			   	   	   }
			   	   }
			   	   $element->setValue('answers', $newAnswers);
			   	   $element->commit();
			}			
		}
	}
};
?>
