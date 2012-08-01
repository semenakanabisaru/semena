<?php
final class antiSpamHelper {
	/**
	* Проверка переданного элемента (комментарий, топик и т.п.) на спам и пометка
	*
	* @param Integer $entityId Идентификатор элемента
	* @param String $contentField название поля, содержащего контент
	* @return Boolean
	*/
	public static function checkForSpam($elementId, $contentField = 'content') {
		$isSpam  = false;
		$service = antiSpamService::get();
		$element = umiHierarchy::getInstance()->getElement($elementId);
		if($service && $element) {
			self::fillFields($service, $element, $contentField);
			$isSpam = $service->isSpam();
			$element->setValue('is_spam', $isSpam);
			$config = mainConfiguration::getInstance();
			if((int) $config->get('anti-spam', 'hide-spam')) {
				$element->setIsActive(!$isSpam);
			}
		}
		return $isSpam;
	}

	/**
	* Отчет на сервер о спаме или не спаме (в зависимости от значения поля isSpam)
	*
	* @param Integer $entityId Идентификатор элемента
	* @param String $contentField название поля, содержащего контент
	*/
	public static function report($elementId, $contentField = 'content') {
		$service = antiSpamService::get();
		$element = umiHierarchy::getInstance()->getElement($elementId);
		if($service && $element) {
			self::fillFields($service, $element, $contentField);
			if($element->getValue('is_spam')) {
				$service->reportSpam();
			} else {
				$service->reportHam();
			}
		}
	}

	/**
	* Заполняет соответствующие поля для отправки в сервис
	*
	* @param antiSpamService $service объект антиспам-сервиса
	* @param iUmiHierarchyElement $element элемент иерархии, используемый для заполнения полей
	* @param String $contentField название поля, содержащего контент
	* @return antiSpamService
	*/
	private static function fillFields(antiSpamService $service, iUmiHierarchyElement $element, $contentField = 'content') {
		$author  = null;
		$objects = umiObjectsCollection::getInstance();
		$hierarchy = umiHierarchy::getInstance();
		$authorId = ($t = $element->getValue('author_id')) ? $t : $element->getObject()->getOwnerId();
		$author  = $objects->getObject($authorId);
		if($author && $author->is_registrated) $author = $objects->getObject($author->user_id);
		if($author) {
			$nick  = $author->nickname ? $author->nickname : $author->login;
			$email = $author->email ? $author->email : $author->getValue('e-mail');
			$service->setNick($nick);
			$service->setEmail($email);
		} else {
			$service->setNick('');
			$service->setEmail('');
		}
		$service->setContent($element->getValue($contentField));
		$link = cmsController::getInstance()->getCurrentDomain()->getHost() . $hierarchy->getPathById($element->getId());
		$service->setLink($link);
		return $service;
	}

	public static function checkContent($content) {
		$blackTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('blacklist');
		$blackWords = umiObjectsCollection::getInstance()->getGuidedItems($blackTypeId);
		foreach ($blackWords as $wordId => $word) {
			if (strpos($content, $word)!== false) {
				return false;
			}
		}
		return true;
	}

};
?>
