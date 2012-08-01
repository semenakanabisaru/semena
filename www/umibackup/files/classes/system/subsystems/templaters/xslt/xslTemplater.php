<?php
	class xslTemplater extends templater implements iTemplater {
		protected
			$domDocument = false,
			$xmlTranslator = false,
			$globalVariables = Array(),
			$isInited = false,
			$file_path,
			$folder_path;

		protected function __construct() {
		}

		public static function getInstance() {
			return singleton::getInstance(__CLASS__);
		}

		public function init() {
			if(!$this->isInited) {
				$this->prepareXmlDocument();
				$this->prepareXmlTranslator();
				cmsController::getInstance()->parsedContent = macros_content();
				$this->initGlobalVariables();
				$this->initXmlDocument();
			}
			$this->isInited = true;
		}

		protected function generateTplNotFoundError() {
			$buffer = outputBuffer::current();
			$buffer->contentType('text/html');
			$buffer->charset('utf-8');
			$buffer->clear();
			$buffer->push(file_get_contents(SYS_ERRORS_PATH . 'no_design_template.html'));
			$buffer->end();
		}


		public function getIsInited() {
			return $this->isInited;
		}

		public function setIsInited($new) {
			$old = $this->isInited;
			$this->isInited = (bool) $new;
			return $old;
		}


		protected function checkFile($filePath) {
			if(file_exists($filePath)) {
				if(is_readable($filePath)) {
					return true;
				}
			}
			return false;
		}


		protected function prepareXmlDocument() {
			$dom = new DOMDocument("1.0", "utf-8");
			$dom->formatOutput = XML_FORMAT_OUTPUT;

			$this->domDocument = $dom;
		}


		protected function prepareXmlTranslator() {
			$this->xmlTranslator = new xmlTranslator($this->getXmlDocument());
		}


		protected function initGlobalVariables() {
			$cmsController = cmsController::getInstance();
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$element_id = $cmsController->getCurrentElementId();

			$current_module = $cmsController->getCurrentModule();
			$current_method = $cmsController->getCurrentMethod();

			if($permissions->isAllowedMethod($userId, $current_module, $current_method)) {
				$permitted = false;
				if($element_id) {
					list($r) = $permissions->isAllowedObject($userId, $element_id);
					if (!$r) {
						$permitted = true;
						$this->globalVariables['attribute:not-permitted'] = 1;
					}
				}
			} else {
				$permitted = true;
			}

			if($permitted) {
				$current_module = "users";
				$current_method = "login";

				$cmsController->setCurrentModule($current_module);
				$cmsController->setCurrentMethod($current_method);
			}

			$this->globalVariables['attribute:module'] = $current_module;
			$this->globalVariables['attribute:method'] = $current_method;

			$this->globalVariables['attribute:domain'] = $cmsController->getCurrentDomain()->getHost();
			$this->globalVariables['attribute:system-build'] = regedit::getInstance()->getVal("//modules/autoupdate/system_build");

			$this->globalVariables['attribute:lang'] = $cmsController->getCurrentLang()->getPrefix();
			$this->globalVariables['attribute:pre-lang'] = $cmsController->pre_lang;

			if(defined('CURRENT_VERSION_LINE') and CURRENT_VERSION_LINE=='demo') {
				$this->globalVariables['attribute:demo'] = 1;
			}

			$cmsController->currentHeader = false;
			$this->globalVariables['attribute:header'] = $this->parseInput(macros_header());
			$cmsController->currentHeader = $this->globalVariables['attribute:header'];
			$this->globalVariables['attribute:title'] = $this->parseInput(macros_title());

			$this->globalVariables['meta'] = Array();
			$this->globalVariables['meta']['keywords'] = macros_keywords();
			$this->globalVariables['meta']['description'] = macros_describtion();


			if(is_null(getRequest('p')) == false) {
				$this->globalVariables['attribute:paging'] = "yes";
			}

			$social_module = cmsController::getInstance()->getModule("social_networks");
			if ($social_module && $social_module->getCurrentSocial()) {
				$this->globalVariables['attribute:socialId'] = $social_module->getCurrentSocial()->getId();
			}

			if ($requestUri = getServer('REQUEST_URI')) {
				if ($social_module && $social_module->getCurrentSocial()) {
					$requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
				}
				$requestUriInfo = @parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');
				if($queryParams) {
					parse_str($queryParams, $queryParamsArr);
					if(isset($queryParamsArr['p'])) unset($queryParamsArr['p']);
					if(isset($queryParamsArr['xmlMode'])) unset($queryParamsArr['xmlMode']);

					$queryParams = http_build_query($queryParamsArr, '', '&');
					if($queryParams) $requestUri .= '?' . $queryParams;
				}
				$this->globalVariables['attribute:request-uri'] = $requestUri;
			}


			$user = array();
			$user['attribute:id'] = $user_id = $cmsController->getModule('users')->user_id;

			$userType = 'guest';
			if($permissions->isAuth()) {
				$userType = 'user';
				if($permissions->isAdmin()) {
					$userType = 'admin';
					if($permissions->isSv()) $userType = 'sv';
				}
			}
			$user['attribute:type'] = $userType;

			if($permissions->isAuth()) {
				$user['attribute:status'] = "auth";

				$oUser = umiObjectsCollection::getInstance()->getObject($user_id);
				if($oUser instanceof umiObject) {
					$user['attribute:login'] = $oUser->login;
				}
				$user['xlink:href'] = $oUser->xlink;
			}

			if($geoip = $cmsController->getModule("geoip")) {
				$geoinfo = $geoip->lookupIp();
				if(!isset($geoinfo['special'])) {
					$user['geo'] = array(
					'country'	=> $geoinfo['country'],
					'region'	=> $geoinfo['region'],
					'city'		=> $geoinfo['city'],
					'latitude'	=> $geoinfo['lat'],
					'longitude'	=> $geoinfo['lon']
				);
				}
				else $user['geo'] = array('special' => $geoinfo['special']);

			}

			$this->globalVariables['user'] = $user;

			if($element_id && is_null(getRequest('scheme'))) {
				$this->globalVariables['attribute:pageId'] = $element_id;

				$this->globalVariables['parents'] = Array();
				$this->globalVariables['parents']['nodes:page'] = Array();
				$parentElements = umiHierarchy::getInstance()->getAllParents($element_id);

				foreach($parentElements as $parentElementId) {
					if($parentElementId == 0) continue;

					if($parentElement = umiHierarchy::getInstance()->getElement($parentElementId)) {
						$this->globalVariables['parents']['nodes:page'][] = $parentElement;
					} else continue;
				}

				$element = umiHierarchy::getInstance()->getElement($element_id);
				$this->globalVariables['page'] = $element;

				templater::pushEditable($current_module, $current_method, $element_id);

			} else {

				if($current_module === "content" && $current_method === "content") {
					$buffer = outputBuffer::current();
					$buffer->status("404 Not Found");

					$this->globalVariables['attribute:method'] = "notfound";
				}
			}


		}

		protected function initXmlDocument() {
			$this->prepareXmlDocument();
			$this->prepareXmlTranslator();
			$dom = $this->getXmlDocument();
			$rootNode = $dom->createElement("result");
			$dom->appendChild($rootNode);
			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');
			$this->getXmlTranslator()->translateToXml($rootNode, $this->globalVariables);
		}


		public function getXmlDocument() {
			return $this->domDocument;
		}

		public function setXmlDocument(DOMDocument $doc) {
			$this->domDocument = $doc;
		}


		protected function getXmlTranslator() {
			return $this->xmlTranslator;
		}


		public function parseResult() {
			$dom = $this->getXmlDocument();

			$xsltDom = new DomDocument;

			$xsltDom->resolveExternals = true;
			$xsltDom->substituteEntities = true;
			$xsltDom->load($this->file_path, DOM_LOAD_OPTIONS);

			checkXmlError($xsltDom);

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();

			$er = errorsXsltListen();

			$xslt->importStyleSheet($xsltDom);
			$this->addRequestParams($xslt, $_REQUEST);
			$this->addRequestParams($xslt, $_SERVER, "_");
			$res = $xslt->transformToXML($dom);

			errorsXsltCheck($er);

			return $res;
		}


		protected function addRequestParams(&$xslt, $array, $prefix = "") {
			foreach($array as $key => $val) {
				$key = strtolower($key);
				if(!is_array($val)) {
					// Fix to prevent warning on some strings
					if(strpos($val, "'") !== false && strpos($val, "\"") !== false) {
						$val = str_replace("'", "\\\"", $val);
					}
					$xslt->setParameter("", $prefix . $key, $val);
				} else {
					$this->addRequestParams($xslt, $val, $prefix . $key . ".");
				}
			}
		}

		public function flushXml() {
			$dom = $this->getXmlDocument();
			return $dom->saveXml();
		}

		public function flushJson() {
			$translator = new jsonTranslator;
			return $translator->translateToJson($this->globalVariables);
		}

		public function loadTemplates($filepath, $c, $args) {
			$filepath = str_replace('mail/', '', str_replace('mails/', '', $filepath));
			$filepath = $this->getFolderPath() . 'mail/' . $filepath . '.xsl';

			$tpls = Array();
			for($i = 1; $i < $c; $i++) {
				$tpls[] = array($args[$i] => $filepath);
			}
			return $tpls;
		}

		public function parseTemplate($arr) {
			$res = array();

			foreach($arr as $key => $val) {
				if (is_null($val) || $val === false || $val === "") continue;
				if (is_array($val)) $val = $this->parseTemplate($val);
				else $val = $this->putLangs($val);

				$subKey = $this->getXmlTranslator()->getSubKey($key);
				if($subKey == "subnodes") {
					$realKey = $this->getXmlTranslator()->getRealKey($key);

					$res[$realKey] = array(
						'nodes:item' => $val
					);
					continue;
				}

				$res[$key] = $val;
			}
			return $res;
		}

		protected function executeMacrosTemplate($res, $module, $method) {
			if (is_array($res)) {
				$controller = cmsController::getInstance();
				$path = ($controller->getCurrentMode() == 'admin') ? $controller->getDefaultTemplater()->getFolderPath() : $this->folder_path;
				if ($method) {
					$res = array_merge(array(
						'@module' => $module,
						'@method' => $method
					), $res);
					$path = $path . 'modules/' . $module . '/' . $method . '.xsl';
				}
				else $path = $path . $module . '.xsl';

				return $this->parseContent($res, array('udata' => $path));
			}
			return $res;
		}

		public function parseContent($arr, $template) {
			if (!is_array($template)) {
				exit(debug_print_backtrace());
			}
			$dom = new DOMDocument("1.0", "utf-8");
			$dom->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode = $dom->createElement(key($template));
			$dom->appendChild($rootNode);
			$translator = new xmlTranslator($dom);
			$translator->translateToXml($rootNode, $arr);

			$xsltDom = new DomDocument;

			$xsltDom->resolveExternals = true;
			$xsltDom->substituteEntities = true;
			$xsltDom->load($template[key($template)], DOM_LOAD_OPTIONS);

			checkXmlError($xsltDom);

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();

			$er = errorsXsltListen();

			$xslt->importStyleSheet($xsltDom);
			$this->addRequestParams($xslt, $_REQUEST);
			$this->addRequestParams($xslt, $_SERVER, "_");
			$res = $xslt->transformToXML($dom);

			errorsXsltCheck($er);
			return $res;
		}

	};
?>