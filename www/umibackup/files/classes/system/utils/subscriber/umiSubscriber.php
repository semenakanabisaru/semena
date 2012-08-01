<?php
/**
	* Подписчик на рассылку. Данный класс наследуется от класса umiObject
*/
	class umiSubscriber extends umiObject implements iUmiObject, iUmiSubscriber {

		protected $o_user = null;

		/**
			* Получить подписчика по id объекта
			* @param Integer $id = false id объекта (класс umiObject)
		*/
		public function __construct($id = false) {
			$this->store_type = 'subscriber';
			$oObject = umiObjectsCollection::getInstance()->getObject($id);
			if ($oObject instanceof umiObject) {
				// check type
				$iTypeId = $oObject->getTypeId();
				$oType = umiObjectTypesCollection::getInstance()->getType($iTypeId);
				$oHType = umiHierarchyTypesCollection::getInstance()->getType($oType->getHierarchyTypeId());
				
				if ($oHType->getName() === "dispatches" && $oHType->getExt() === "subscriber") {
					$iUId = $oObject->getValue('uid');
					$this->o_user = umiObjectsCollection::getInstance()->getObject($iUId);
				} elseif ($oHType->getName() === "users" && $oHType->getExt() === "user") {
					$this->o_user = $oObject;
					// try get or create subscriber by user id
					$id = $this->getSubscriberByUserId($id);
				}
			}
			parent::__construct($id);
		}

		/**
			* Узнать, является ли подписчик зарегистрированным пользователем
			* @return Boolean true, если подписчик зарегистрирован на сайте, false если нет
		*/
		public function isRegistredUser() {
			return ($this->o_user instanceof umiObject);
		}

		/**
			* Получить список рассылок, на которые подписан подписчик
			* @return Array массив из id объектов-рассылок
		*/
		public function getDispatches() {
			return $this->getValue('subscriber_dispatches');
		}

		/**
			* Статический метод, который вернет подписчика по id пользователя. Если такого нет, он будет создан
			* @param Integer $iUserId id пользователя, для которого мы ищем подписчика
			* @return umiSubscriber объект подписчика
		*/
		public static function getSubscriberByUserId($iUserId) {
			$iResult = false;

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();
			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setPropertyFilter();
			$oSbsSelection->addPropertyFilterEqual($oSbsType->getFieldId('uid'), $iUserId);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);
			if (is_array($arrSbsSelResults) && count($arrSbsSelResults)) {
				$iResult = $arrSbsSelResults[0];
			} else {
				// create subscriber
				$oUserObj = umiObjectsCollection::getInstance()->getObject($iUserId);
				$sSbsMail = $oUserObj->getValue("e-mail");
				$sSbsLName = $oUserObj->getValue("lname");
				$sSbsFName = $oUserObj->getValue("fname");
				$sSbsFatherName = $oUserObj->getValue("father_name");
				$iSbsGender = $oUserObj->getValue("gender");

				$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
				$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
				$iResult = umiObjectsCollection::getInstance()->addObject($sSbsMail, $iSbsTypeId);

				$oSubscriber = umiObjectsCollection::getInstance()->getObject($iResult);
				if ($oSubscriber instanceof umiObject) {
					$oSubscriber->setName($sSbsMail);
					$oSubscriber->setValue('lname', $sSbsLName);
					$oSubscriber->setValue('fname', $sSbsFName);
					$oSubscriber->setValue('father_name', $sSbsFatherName);
					$oCurrDate = new umiDate(time());
					$oSubscriber->setValue('subscribe_date', $oCurrDate);
					$oSubscriber->setValue('gender', $iSbsGender);
					$oSubscriber->setValue('uid', $iUserId);
				}
				$oSubscriber->commit();
			}

			return $iResult;
		}
	};

?>