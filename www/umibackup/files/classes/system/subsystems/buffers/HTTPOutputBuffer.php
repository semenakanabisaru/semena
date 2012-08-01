<?php
	class HTTPOutputBuffer extends outputBuffer {
		protected
			$charset		= 'utf-8',
			$contentType	= 'text/html',
			$headers		= array(),
			$headersSended	= false,
			$status			= '200 Ok',
			$options		= array(
				'compression' => true,
				'quick-edit' => true,
				'generation-time' => true,
				'send-stat-id' => true
			);

		public function __construct() {
			parent::__construct();
			session_start();
			$_SESSION['starttime']=time();
			$this->checkHTTPAuth();
		}

		public function send() {
			$this->sendHeaders();
			echo $this->buffer;	
			$this->clear();
		}

		public function status($status = false) {
			if($status) $this->status = $status;
			return $this->status;
		}

		public function charset($charset = false) {
			if($charset) $this->charset = $charset;
			return $this->charset;
		}

		public function contentType($contentType = false) {
			if($contentType) $this->contentType = $contentType;
			return $this->contentType;
		}

		public function getHTTPRequestBody() {
			$putdata = fopen("php://input", "r");
			$data = "";

			while (!feof($putdata)) {
					$data .= fread($putdata, 1024);
			}
			fclose($putdata);

			return $data;
		}

		public function sendHeaders() {
			if($this->headersSended) {
				return true;
			} else if (headers_sent()) {
				return false;
			}

			if($this->status != '404 Not Found' and CALC_LAST_MODIFIED) $this->sendLastModified();
			$this->sendStatusHeader();

			$this->sendDefaultHeaders();

			foreach($this->headers as $header => $value) {
				$this->sendHeader($header, $value);
			}
			$this->headersSended = true;
		}

		public function end() {
			@ob_clean();
			if(getArrayKey($this->options, 'quick-edit')) {
				templater::prepareQuickEdit();
			}

			if(CALC_E_TAG) $this->sendETag();
			$this->push($this->getCallTime());
			$this->send();
			exit;
		}

		public function option($key, $value = null) {
			if(is_null($value)) {
				return isset($this->options[$key]) ? $this->options[$key] : null;
			} else $this->options[$key] = $value;
		}


		public static function checkUrlSecurity($url) {
			return stripos($url, "javascript:") === false && stripos($url, "data:") === false && !preg_match('/^\/{2,}/i', $url);
		}

		public function redirect($url, $status = '301 Moved Permanently') {
			if (!self::checkUrlSecurity($url)) {
				$this->status("400 Bad Request");
				$this->end();
			}

			$this->status($status);
			$this->header('Location', $url);
			$this->end();
		}

		public function header($name, $value = false) {
			if($value === false) {
				unset($this->headers[$name]);
				return NULL;
			} else {
				return $this->headers[$name] = $value;
			}
		}

		protected function checkHTTPAuth() {
			$login = getServer('PHP_AUTH_USER');
			$password = getServer('PHP_AUTH_PW');


			if($login && $password) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isAuth() == false) {
					if($user = $permissions->checkLogin($login, $password)) {
						$permissions->loginAsUser($user->id);
					} else {
						$this->clear();
						$this->status('401 Unauthorized');
						$this->header('WWW-Authenticate: Basic realm="UMI.CMS"');
						$this->push('HTTP Authenticate failed');
						$this->end();
					}
				}
			}
		}

		protected function sendStatusHeader() {
			header("HTTP/1.1 " . $this->status);
			// Some servers close connection when we duplicate status header
			if((int)mainConfiguration::getInstance()->get("kernel", "send-additional-status-header")) {
				header("Status: " . $this->status);
			}
		}

		public function length() {
			ob_start();
			echo $this->buffer;
			$size = ob_get_length();
			ob_end_clean();
			return $size;
		}

		protected function sendDefaultHeaders() {
			$this->sendHeader('Content-type', $this->contentType . '; charset=' . $this->charset);
			$this->sendHeader('Content-length', $this->length());
			$this->sendHeader('Date', (gmdate("D, d M Y H:i:s") . " GMT"));
			$this->sendHeader('X-Generated-By', 'UMI.CMS');

			$version = regedit::getInstance()->getVal("//modules/autoupdate/system_version");
			$this->sendHeader('X-CMS-Version', $version);

			if(stristr(getServer('HTTP_USER_AGENT'), 'msie')) {
				$this->sendHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
				$this->sendHeader('Pragma', 'no-cache');
        		$this->sendHeader('Expires', gmdate('D, d M Y H:i:s') . ' GMT');
        		$this->sendHeader('X-XSS-Protection', '0');
			} else {
				$this->sendHeader('Cache-Control', 'max-age=3600, private, must-revalidate');
			}

			if($this->option('send-stat-id')) {
				if (!getCookie('stat_id')) {
					setcookie('stat_id', session_id(), strtotime('+10 years'), "/");
				}
			}
		}

		protected function sendHeader($header, $value) {
			header("{$header}: {$value}");
		}

		protected function sendETag() {
			$this->sendHeader('E-tag', sha1($this->content()));
		}

		protected function sendLastModified() {
			$hierarchy = umiHierarchy::getInstance();
			$updateTime = $hierarchy->getElementsLastUpdateTime() ; 
			if($updateTime) {
				$this->sendHeader('Last-Modified', (gmdate('D, d M Y H:i:s', $updateTime) . ' GMT'));
				$this->sendHeader('Expires', (gmdate('D, d M Y H:i:s', time() + 24 * 3600) . ' GMT'));

				if(function_exists("apache_request_headers")) {
					$request = apache_request_headers();
					if(isset($request['If-Modified-Since']) && (strtotime($request['If-Modified-Since']) >= ($updateTime) )) {

						$this->status('304 Not Modified');
						$this->sendHeader('Connection',  'close');
					}
				}
			}
		}

		protected function getCallTime() {
			$generationTime = round(microtime(true) - $this->invokeTime, 6);
			$config = mainConfiguration::getInstance();
			$showGenerateTime = (string) $config->get('kernel', 'show-generate-time');
			if(!$this->option('generation-time') || $showGenerateTime === "0") {
				return;
			}

			switch($this->contentType) {
				case 'text/html':
				case 'text/xml':
					return "<!-- This page generated in {$generationTime} secs -->";

				case 'application/javascript':
				case 'text/javascript':
					return "/* This page generated in {$generationTime} secs */";

				default: "";
			}
		}
	};
?>
