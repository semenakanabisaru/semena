<?php
	abstract class __seo extends baseModuleAdmin {

		public $cook = '';

		public function oldseo() {
			$params = Array();

			$this->setDataType("settings");
			$this->setActionType("view");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function get() {
			$opts = Array(
				"header" =>	"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7\r\n" .
						"Accept-Language: ru\r\n" .
						"Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n" .
						"Accept-Charset: utf-8"
			);

			$context = stream_context_create($opts);

			//$url = urldecode(getRequest('q'));
			$url = getServer("REQUEST_URI");
			preg_match("/q=(.*)/", $url, $out);
			$url = $out[1];

			$res = file_get_contents($url, false, $context);

			header("Content-type: text/html; charset=windows-1251");
			echo $res = iconv("UTF-8", "CP1251//IGNORE", $res);
			exit();
		}

		public function megaindex() {

			$regedit = regedit::getInstance();
			$params = Array (
				"config" => Array (
					"string:megaindex-login" => null,
					"string:megaindex-password" => null
				)
			);

			$mode = getRequest("param0");

			if ($mode == "do"){
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/seo/megaindex-login", $params["config"]["string:megaindex-login"]);
				$regedit->setVar("//modules/seo/megaindex-password", $params["config"]["string:megaindex-password"]);
				$this->chooseRedirect();
			}

			$params["config"]["string:megaindex-login"] = $regedit->getVal("//modules/seo/megaindex-login");
			$params["config"]["string:megaindex-password"] = $regedit->getVal("//modules/seo/megaindex-password");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();

		}

		public function config() {
			$regedit = regedit::getInstance();
			$domains = domainsCollection::getInstance()->getList();
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$params = Array();

			foreach($domains as $domain) {
				$domain_id = $domain->getId();
				$domain_name = $domain->getHost();

				$seo_info = Array();
				$seo_info['status:domain'] = $domain_name;
				$seo_info['string:title-' . $domain_id] = $regedit->getVal("//settings/title_prefix/{$lang_id}/{$domain_id}");
				$seo_info['string:keywords-' . $domain_id] = $regedit->getVal("//settings/meta_keywords/{$lang_id}/{$domain_id}");
				$seo_info['string:description-' . $domain_id] = $regedit->getVal("//settings/meta_description/{$lang_id}/{$domain_id}");

				$params[$domain_name] = $seo_info;
			}

			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$params = $this->expectParams($params);

				foreach($domains as $domain) {
					$domain_id = $domain->getId();
					$domain_name = $domain->getHost();

					$title = $params[$domain_name]['string:title-' . $domain_id];
					$keywords = $params[$domain_name]['string:keywords-' . $domain_id];
					$description = $params[$domain_name]['string:description-' . $domain_id];

					$regedit->setVal("//settings/title_prefix/{$lang_id}/{$domain_id}", $title);
					$regedit->setVal("//settings/meta_keywords/{$lang_id}/{$domain_id}", $keywords);
					$regedit->setVal("//settings/meta_description/{$lang_id}/{$domain_id}", $description);
				}

				$this->chooseRedirect();
			}


			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}




		public function seo() {

			$regedit = regedit::getInstance();
			$login = trim($regedit->getVal("//modules/seo/megaindex-login"));
			$password = trim($regedit->getVal("//modules/seo/megaindex-password"));

			if (CURRENT_VERSION_LINE === 'demo' && getRequest("host") == '') {
				$host = 'umi-cms.ru';
			} else {
				$host = (string) (strlen(getRequest ("host"))) ? getRequest ("host") : getServer('HTTP_HOST');
			}

			$date = date('Y-m-d');

			$this->cook = '';

			$this->setDataType("settings");
			$this->setActionType("view");

			$preParams = Array(
				"config" => Array(
					"url:http_host" => $host
				)
			);

			$data = $this->prepareData($preParams, 'settings');
			if($this->ifNotXmlMode()) {
				$this->setData($data);
				return $this->doData();
			}

			if ($password && $login) $params = $this->siteAnalyzeXML($login, $password, $host, $date);

			if (isset($params)) {

				$result = '<?xml version="1.0" encoding="utf-8"?>';

				if (strpos($params, '<items>') !== false) {

					$newItems = array();

					$sort = (string) (strlen(getRequest ("sort"))) ? trim(getRequest ("sort")) : 'word';
					$order = (string) (strlen(getRequest ("order"))) ? trim(getRequest ("order")) : 'asc';

					$dom = new DOMDocument('1.0', 'utf-8');
					$dom->loadXML($params);

					$items = $dom->getElementsByTagName('item');
					foreach ($items as $item) {
						$value = $item->hasAttribute($sort) ? $item->getAttribute($sort) : $item->getAttribute('word');
						$newItems[$dom->saveXML($item)] = $value;
					}

					natsort($newItems);

					if($order != 'asc') $newItems = array_reverse($newItems);

					$result .='<response>';
					$result .= $dom->saveXML($dom->getElementsByTagName('query')->item(0));
					$result .= '<items>';

					foreach($newItems as $key => $value) {
						$result .= $key;
					}

					$result .= '</items></response>';
				} else {
					$result .= $params;
				}

				$data['xml:info'] = $result;
			} else {
				 $data['error'] = 'Для использования модуля необходима регистрация на сайте <a href="http://www.megaindex.ru" target="_blank" title="">MegaIndex</a>. Зарегистрируйтесь на нём, затем впишите свой логин и пароль в <a href="/admin/seo/megaindex/" title="" >Настройках модуля</a>.';
			}
			$this->setData($data);
			return $this->doData();

		}

		public function siteAnalyzeXML($login, $password, $site, $date){

			$doc = new DOMDocument('1.0', 'windows-1251');
			$request = $doc->createElement('request');
			$doc->appendChild($request);
			$query = $doc->createElement('query');
			$request->appendChild($query);
			$query->setAttribute('login', $login);
			$query->setAttribute('pswd', $password);
			$xml = $doc->saveXML();

			$response = $this->openXML($xml);

			$dom = new DOMDocument;
			if(@$dom->loadXML($response) === false) {
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
				return $response;
			}

			$xpath = new DOMXPath($dom);
			$error = $xpath->query("/response/error");
			if($error->length){
				$errorMessage = $error->item(0)->nodeValue;
				if ($error->item(0)->nodeValue == "Invalid `login` | `pswd`") $errorMessage = getLabel('error-authorization-failed');
				$response = '<error>' . $errorMessage . '</error>';
				return $response;

			}

			$doc = new DOMDocument('1.0', 'windows-1251');
			$request = $doc->createElement('request');
			$doc->appendChild($request);
			$query = $doc->createElement('query');
			$request->appendChild($query);
			$query->setAttribute('report', 'siteAnalyze');
			$query->setAttribute('site', $site);
			$query->setAttribute('date', $date);
			$xml = $doc->saveXML();

			$response = $this->openXML($xml);

			$dom = new DOMDocument;

			if(@$dom->loadXML($response) === false) {
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
				return $response;
			}

			$xpath = new DOMXPath($dom);
			$error = $xpath->query("/response/error");
			if($error->length){
				$errorMessage = $error->item(0)->nodeValue;
				$response = '<error>' . $errorMessage . '</error>';
				return $response;

			}

			if (strpos($response, '<items>') === false){
				$response = '<error>' . getLabel('error-invalid_answer') . '</error>';
			} else {
				$response = iconv('windows-1251', 'utf-8', str_replace('windows-1251', 'utf-8', $response));
				$response = preg_replace("/<item\s+word\s*=\s*\"[^a-zA-Zа-яА-Я0-9-]+(.*?)\/>/uim", '', $response);
			}

			return $response;
		}

		public function openXML($xml){

			$url = "http://www.megaindex.ru/xml.php";
			$addHeaders = array(
				"Content-type" => "application/x-www-form-urlencoded"
			);
			$postVars = array('text' => $xml);

			if (!$this->cook){

				$response = umiRemoteFileGetter::get($url, false, $addHeaders, $postVars, true);

				$result = preg_split("|(\r\n\r\n)|", $response);
				$header = array_shift($result);
				$response = implode('', $result);

				preg_match_all("!Set\-Cookie\: (.*)=(.*);!siU", $header, $matches);
				foreach($matches[1] as $i => $k){
					$this->cook .= "{$k}={$matches[2][$i]}; ";
				}

			} else {
				$addHeaders['Cookie'] = $this->cook;
				$response = umiRemoteFileGetter::get($url, false, $addHeaders, $postVars);
			}

			return $response;
		}


	};
?>
