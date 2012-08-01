<?php
	abstract class antiSpamService {
		/**
		* Получить экземпляр враппера для антиспам-сервиса
		* 
		* @return antiSpamService
		*/
		static public function get() {
			$config = mainConfiguration::getInstance();
			if((int) $config->get('anti-spam', 'service.enabled')) {
				$serviceName = $config->get('anti-spam', 'service.name');
				
				$classPath = SYS_KERNEL_PATH . 'utils/antispam/services/' . $serviceName . '.php';
				$className = $serviceName . 'AntiSpamService';
				
				if(!is_file($classPath)) {
					throw new coreException("Antispam service \"{$serviceName}\" file not found in \"{$classPath}\"");
				}

				require_once $classPath;
				
				if(class_exists($className) == false) {
					throw new coreException("Antispam service class \"{$className}\" not found");
				}
				
				$service = new $className;
				if($service instanceof antiSpamService == false) {
					throw new coreException("Class \"{$className}\" must be instance of antiSpamService class");
				}
				return $service;
			}
			return false;
		}
		
		abstract public function isSpam();
		
		abstract public function setNick($name);
		abstract public function setEmail($email);
		abstract public function setContent($content);
		abstract public function setLink($link);
		abstract public function setReferrer($referer);
		
		abstract public function reportSpam();
		abstract public function reportHam();
	};
?>