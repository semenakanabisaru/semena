<?php

class social_networks extends def_module {
	public $current_network = false;

	public function __construct() {

		parent::__construct();

		if(cmsController::getInstance()->getCurrentModule() == __CLASS__
		&& cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__social_networks");
			$networks = social_network::getList();

			$Tabs = $this->getCommonTabs();
			foreach($networks as $id) {
				$network = social_network::get($id);
				$Tabs->add($network->getCodeName());

			}
		}
	}


	protected function display_social_frame($network) {

		$cmsController = cmscontroller::getInstance();

		$path = getRequest('path');
		$path = trim($path, "/");
		$path = explode("/", $path);

		if( $cmsController->getCurrentLang()->getPrefix()==$path[0] )
		{
			array_shift($path);
		}

		$path = array_slice($path, 2);

		$_REQUEST['path'] = $path = '/'.implode('/',$path);

		if(!$network || !$network -> isIframeEnabled()) {
			$buffer = outputBuffer::current();
			$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
			$buffer->end();
		}

		// find element again
		cmsController::getInstance()->analyzePath(true);

		$current_element_id = cmscontroller::getInstance()->getCurrentElementId();

		$cmsController->setUrlPrefix(''. __CLASS__ .'/'.$network->getCodeName());

		//print_R($cmsController);exit;

		if($cmsController->getCurrentMode() == "admin" || !$network->isHierarchyAllowed($current_element_id )  ) {
			$buffer = outputBuffer::current();
			$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
			$buffer->end();
		}

		$this-> current_network = $network;

		$currentModule = $cmsController->getCurrentModule();
		$cmsController->getModule($currentModule);

		$xsltTemplater = xslTemplater::getInstance();
		$xsltTemplater->setIsInited(false);
		$xsltTemplater->init();

		$cmsController->parsedContent = macros_content();


	}


	public function systemonBeforeDisplay() {

		if(!$this->current_network) {
			return;
		}

		$config = mainConfiguration::getInstance();

		 $templateName = 'xsl/social/'.$this->current_network->getCodeName().'.xsl';

		if($this-> current_network -> getValue('template')) {
			$templateName = $config->includeParam('templates.xsl').$this-> current_network -> getValue('template');
		}

		$xsltTemplater = xslTemplater::getInstance();
		$xsltTemplater->init($templateName);

		$GLOBALS['templatePath'] = $templateName;

		return true;
	}

	public function includeApi($network_code) {

		$network = social_network::getByCodeName($network_code);

		if(!$network) {
			return;
		}

		$sJS = '';
		if($network->isIframeEnabled() ) {
		$sJS .= '
			<script src="http://vkontakte.ru/js/api/xd_connection.js?2" type="text/javascript"></script>
			<script type="text/javascript" src="http://vkontakte.ru/js/api/merchant.js" charset="windows-1251"></script>
';
		}

		return $sJS;

	}

	public function  getSettings() {
		$regedit = regedit::getInstance();

		$merchant_id = (int) $regedit->getVal('//modules/emarket/social_vkontakte_merchant_id');
		$key 		 = (string) $regedit->getVal('//modules/emarket/social_vkontakte_key');

		$widgets = !empty($merchant_id) && !empty($key);

		$result = Array();
		$result['vkontakte'] = Array(
			"attribute:url_success"			=> 'http://'.getServer('HTTP_HOST').'/emarket/ordersList/',
			"attribute:url_fail"			=>  'http://'.getServer('HTTP_HOST').'/',
			"attribute:currency"			=>  'RUB',
			"attribute:wishlist_enabled"	=>  (int) $regedit->getVal('//modules/emarket/social_vkontakte_wishlist'),
			"attribute:order_enabled"		=>  (int) $regedit->getVal('//modules/emarket/social_vkontakte_order'),
			"attribute:shop_enabled"		=>  (int) $regedit->getVal('//modules/emarket/social_vkontakte_shop_enabled'),
			"attribute:test_mode"		=>  (int) $regedit->getVal('//modules/emarket/social_vkontakte_testmode'),
			"attribute:merchant_id"			=>  (int) $regedit->getVal('//modules/emarket/social_vkontakte_merchant_id'),
			"attribute:key"					=>  (string) $regedit->getVal('//modules/emarket/social_vkontakte_key'),
		);

		return $result;
	}


	public function  getKeyForProduct($element_id = false){
		if(empty($element_id)) return array();

		if(!cmsController::getinstance()->getModule("emarket")) {
			return array();
		}

		$element = umiHierarchy::getInstance()->getElement((int) $element_id, false, false);

		$result = Array();
		if($element instanceof umiHierarchyElement) {
			$regedit = regedit::getInstance();

			$photo = '';
			if ($element->getValue('photo') instanceof umiFile) {
				$photo = 'http://' . getServer('HTTP_HOST') . $element->getValue('photo')->getFilePath(true);
			}

			$social_vkontakte_sig_arr = array(
				'merchant_id'=> (string) $regedit->getVal('//modules/emarket/social_vkontakte_merchant_id'),
				'item_id'=>(string) $element->getId(),
				'item_name'=>(string) $element->getName(),
				'item_description'=>(string) $element->getValue('description'),
				'item_currency'=>'RUB',
				'item_price'=>(string) $element->getValue('price'),
				'item_photo_url'=> $photo
			);

			ksort($social_vkontakte_sig_arr);

			$social_vkontakte_sig = '';
			foreach($social_vkontakte_sig_arr as $k=>$v ) {
				$social_vkontakte_sig .= "$k=".($v);
			}

			$result['vkontakte'] = md5($social_vkontakte_sig.$regedit->getVal('//modules/emarket/social_vkontakte_key') );
		}


		return $result;
	}


	public function getCurrentSocial() {
		return $this->current_network;
	}

	public function getCurrentSocialParams($param = '') {
		if(!$this->current_network) {
			return;
		}
		return $this->current_network-> getValue($param);
	}

	public function __call($m, $a) {
		$network = social_network::getByCodeName($m);

		if($network) {
			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				return parent::__call('_network_settings', array($network));
				}
			else {
				return$this->display_social_frame($network);
			}
		}

		return parent::__call($m, $a);
	}


};

?>
