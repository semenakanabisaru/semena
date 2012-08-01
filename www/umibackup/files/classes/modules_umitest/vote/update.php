<?php
  // Get instances
  $oHierarchy  = umiHierarchy::getInstance();
  $oCollection = umiObjectsCollection::getInstance();
  // Let's select the polls
  $oSelection = new umiSelection();
  $iHTypeID   = umiHierarchyTypesCollection::getInstance()->getTypeByName("vote", "poll")->getId();
  $oSelection->addElementType($iHTypeID);
  $aPolls     = umiSelectionsParser::runSelection($oSelection);
  $oSelection = new umiSelection();
  $iTypeID    = umiObjectTypesCollection::getInstance()->getBaseType("vote", "poll_item");
  $oSelection->addObjectType($iTypeID);
  $aAnswers   = umiSelectionsParser::runSelection($oSelection);
  foreach($aPolls as $iPollID) {
      $oPoll            = $oHierarchy->getElement($iPollID);
      $oPollObjectID    = $oPoll->getObject()->getId();
      if(!($oPoll instanceof umiHierarchyElement)) continue;
      $aLocalAnswersIDs = $oPoll->getValue('answers');
      foreach($aAnswers as $iAnswerID) {
          $oAnswer      = $oCollection->getObject($iAnswerID);
          if(!($oAnswer instanceof umiObject)) continue;
          $iLocalPollID = $oAnswer->getValue('poll_rel');
          if($oPollObjectID != $iLocalPollID) continue;
          if(!in_array($iAnswerID, $aLocalAnswersIDs)) {
              $aLocalAnswersIDs[] = $iAnswerID;
          }
      }
      $aAnswers = array_diff($aAnswers, $aLocalAnswersIDs);
      $oPoll->setValue('answers', $aLocalAnswersIDs);
      $oPoll->commit();
  }
?>
