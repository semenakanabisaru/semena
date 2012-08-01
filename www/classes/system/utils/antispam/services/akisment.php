<?php
	require_once SYS_KERNEL_PATH . 'utils/antispam/services/libs/Akismet.class.php';

	class akismentAntiSpamService extends antiSpamService {
		protected
			$doer;
		
		public function __construct() {
			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();
			
			$siteUrl = 'http://' . $cmsController->getCurrentDomain()->getHost() . '/';
			$apiKey = (string) $config->get('anti-spam', 'akisment.wp-api-key');
			
			if(!$apiKey) {
				throw new coreException("Specify [anti-spam] akisment.wp-api-key in config.ini");
			}
			
			$this->doer = new Akismet($siteUrl, $apiKey);
		}
		
		public function isSpam() {
			return $this->doer->isCommentSpam();
		}
		
		public function setNick($name) {
			return $this->doer->setCommentAuthor($name);
		}

		public function setEmail($email) {
			return $this->doer->setCommentAuthorEmail($email);
		}

		public function setContent($content) {
			return $this->doer->setCommentContent($content);
		}

		public function setLink($link) {
			return $this->doer->setPermalink($link);
		}
		
		public function setReferrer($referer) {
			return $this->doer->setReferrer($referer);
		}
		
		public function reportSpam() {
			
		}

		public function reportHam() {
			
		}
	};
?>