<?php
	abstract class __yandex_stat extends baseModuleAdmin {


		public function get_counters() {

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');

			$this->setDataType("settings");
			$this->setActionType("view");

			$response = self::get_response('counters');
			$data['xml:info'] = $response;

			$this->setData($data);
			return $this->doData();

		}

		public function edit_counter($counterId = false) {
			$counterId = (int) getRequest("param0");
			$mode = (string) getRequest("param1");

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');
			$this->setDataType("settings");
			$this->setActionType("view");

			$response = self::get_response("counter/{$counterId}", "GET");
			$data['xml:info'] = $response;

			if($mode == "do") {

				$name = getRequest('counter-name');
				$site = getRequest('counter-site');
				$post = array('name' => $name, 'site' => $site);
				$post = json_encode($post);

				$headers = array ("Content-type" => 'application/json', 'Content-length' => strlen($post));

				$response = self::get_response("counter/{$counterId}", "PUT", $post, $headers);

				$dom = new DOMDocument;
				$dom->loadXML($response);
				$xpath = new DOMXPath($dom);

				$errors = $xpath->query("//errors/error/text");
				if($errors->length){
					$errorMessage = '';
					foreach($errors as $error) {
						$errorMessage .= $error->nodeValue;
					}

					$data = array(
						"error" => Array(
							"text" => $errorMessage,
							"name" => $name,
							"site" => $site
						)
					);

					$this->setData($data);
					return $this->doData();
				}

				$this->chooseRedirect("/admin/stat/edit_counter/{$counterId}");
			}

			$this->setData($data);
			return $this->doData();
		}

		public function add_counter() {
			$mode = (string) getRequest("param0");

			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');
			$this->setDataType("settings");
			$this->setActionType("view");

			if($mode == "do") {

				$name = getRequest('counter-name');
				$site = getRequest('counter-site');
				$post = array('name' => $name, 'site' => $site);
				$post = json_encode($post);

				$headers = array ("Content-type" => 'application/json', 'Content-length' => strlen($post));

				$response = self::get_response("counters", "POST", $post, $headers);

				$dom = new DOMDocument;
				$dom->loadXML($response);
				$xpath = new DOMXPath($dom);

				$errors = $xpath->query("//errors/error/text");
				if($errors->length){
					$errorMessage = '';
					foreach($errors as $error) {
						$errorMessage .= $error->nodeValue;
					}

					$data = array(
						"error" => Array(
							"text" => $errorMessage,
							"name" => $name,
							"site" => $site
						)
					);

					$this->setData($data);
					return $this->doData();
				}

				$counterId = $xpath->query("//counter/id")->item(0)->nodeValue;

				$this->chooseRedirect("/admin/stat/edit_counter/{$counterId}");
			}

			$this->setData($data);
			return $this->doData();
		}

		public function check_counter($counterId = false) {
			if (!$counterId) $counterId = getRequest('param0');
			self::get_response("counter/{$counterId}/check");
			$this->chooseRedirect();
		}

		public function delete_counter($counterId = false) {
			if (!$counterId) $counterId = getRequest('param0');
			self::get_response("counter/{$counterId}", "DELETE");
			$this->chooseRedirect();
		}

		protected function get_response($request, $method = "GET", $params = array(), $headers = array()) {

			$apiUrl = 'http://api-metrika.yandex.ru/';

			$regedit = regedit::getInstance();
			$token = (string) trim($regedit->getVal("//modules/stat/yandex-token"));

			if (!$token) throw new publicAdminException(getLabel('label-error-no-token'));

			$url = $apiUrl . $request . '/?oauth_token=' . $token;

			if (count($params) && is_array($params)) {
				$url .= '&' . http_build_query($params);
				$params = array();
			}

			$response = umiRemoteFileGetter::get($url, false, $headers, $params, true, $method);

			$result = preg_split("|(\r\n\r\n)|", $response);
			$headers = $result[0];
			$xml = $result[1];

			if (strpos($headers, '200 OK') === false) {
				if (strpos($headers, '401 Unauthorized') !== false) {
					throw new publicAdminException(getLabel('label-error-no-token'));
				} else {
					throw new publicAdminException($xml);
				}
			}

			$xml = str_replace(' xmlns="http://api.yandex.ru/metrika/"', ' id="' . $request . '"', $xml);

			return $xml;

		}

		public function view_counter_json() {

			$section = (string) getRequest('section');
			$report = (string) getRequest('report');
			$counterId = (int) getRequest('counter');
			$order = (string) getRequest('order');
			$date1 = (string) getRequest('date1');
			$date2 = (string) getRequest('date2');

			$section = trim($section, "_");

			$cacheDir = CURRENT_WORKING_DIR . '/sys-temp/metrika/';
			if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
			$key = md5($section . $report . $counterId . $date1 . $date2);
			$fileKey = md5($section . $report . $counterId);
			$cacheFile = $cacheDir . $fileKey;
			$filteredResponse = null;

			if (file_exists($cacheFile) && getSession('metrikaKey') == $key) {
				$filteredResponse = file_get_contents($cacheFile);
			} elseif(file_exists($cacheFile)) {
				unlink($cacheFile);
			}

			if (is_null($filteredResponse)) {

				$headers = array ("Content-type" => 'text/xml', "Accept" => 'application/x-yametrika+json');

				if ($section == "summary" && $report == "common") {
					return;
				}

				$system = system_buildin_load('system');

				$request = 'stat/' . $section . '/' . $report . '.json';
				if ($section == 'geo') {
					$request = 'stat/' . $section . '.json';
				}

				$params = array('id' => $counterId, 'pretty'=>1);

				if ($date1) $params['date1'] = $system->convertDate("", 'Ymd', $date1);
				if ($date2) $params['date2'] = $system->convertDate("", 'Ymd', $date2);

				try {
					$response = self::get_response($request, "GET", $params, $headers);
				} catch (Exception $e) {
					$error = $e->getMessage();
					$result = array(
						'errors' => array(
							0 => array(
								'text' => $error
							)
						)
					);

					$filteredResponse = json_encode($result);
					file_put_contents($cacheFile, $filteredResponse);
				}

				if (is_null($filteredResponse)) {

					$jsonDecoded = json_decode($response, true);

					if (isset($jsonDecoded['date1'])) $jsonDecoded['date1'] = $system->convertDate("", 'd.m.Y', $jsonDecoded['date1']);
					if (isset($jsonDecoded['date2'])) $jsonDecoded['date2'] = $system->convertDate("", 'd.m.Y', $jsonDecoded['date2']);

					$mainArray = 'data';
					if ($report == 'deepness') $mainArray = 'data_time' ;

					if (isset($jsonDecoded[$mainArray])) {
						if ($order == 'date') {

							$jsonDecodedData = array();
							foreach($jsonDecoded['data'] as $data) {
								$date = $data['date'];
								unset($data['date']);
								$jsonDecodedData[$date] = array_merge(array('date' => $system->convertDate("", 'd.m.Y', $date)), $data);
							}

							ksort($jsonDecodedData);
							$jsonDecoded['data'] = array_values($jsonDecodedData);

						} elseif ($section == 'geo') {

							require_once dirname(__FILE__) . '/__countries.php';

							$jsonDecodedData = array();
							$controller = cmsController::getInstance();
							$lang = $controller->getLang();
							$langPrefix = $lang->getPrefix();

							foreach($jsonDecoded['data'] as $data) {

								$orderValue = $data['name'];
								if ($langPrefix == 'ru' && isset($countries[$data['name']])) {
									$orderValue =  $countries[$data['name']];
								}
								$jsonDecodedData[] = array_merge(array($order => $orderValue), $data);
							}

							$jsonDecoded['data'] = $jsonDecodedData;

						} else {

							$jsonDecodedData = array();
							foreach($jsonDecoded[$mainArray] as $data) {
								$orderValue = $data[$order];
								if ($report == 'browsers') $orderValue .= ' ' . $data['version'];
								unset($data[$order]);
								$jsonDecodedData[] = array_merge(array($order => $orderValue), $data);
							}

							$jsonDecoded['data'] = $jsonDecodedData;
						}
					}

					$filteredResponse = json_encode($jsonDecoded);
					file_put_contents($cacheFile, $filteredResponse);

				}
			}

			$_SESSION['metrikaKey'] = $key;
			$buffer = outputBuffer::current();
			$buffer->contentType('text/javascript');
			$buffer->option('generation-time', false);
			$buffer->clear();
			$buffer->push($filteredResponse);
			$buffer->end();
		}

		public function view_counter($section = false, $report = false, $counterId = false) {

			if (!$section) $section = (string) getRequest('param0');
			if (!$report) $report = (string) getRequest('param1');
			if (!$counterId) $counterId = (int) getRequest('param2');
			$filter = (string) getRequest('filter');

			$params = array();

			$sections = array(
				'traffic' => array(
					'attributes' 	=> array(
						'default' 	=> 1
					),
					'reports' => array(
						'summary' => array(
							'attributes' => array(
								'default' 		=> 1,
								'graph'			=> 'ColumnChart',
								'order-by'		=> 'date'
							),
							'charts' => array (
								'ColumnChart',
								'PieChart',
								'LineChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'visitors' 		=> array('attributes' => array()),
								'new_visitors' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'deepness'	=> array(
							'attributes' => array(
								'graph'			=> 'BarChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'BarChart',
								'ColumnChart',
								'PieChart'
							),
							'filters'	 => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
									)
								)
							)
						),
						'hourly'	=> array(
							'attributes' => array(
								'graph'			=> 'ColumnChart',
								'order-by'		=> 'hours'
							),
							'charts' => array (
								'ColumnChart',
								'PieChart',
								'LineChart',
								'BarChart'
							),
							'filters' => array(
								'avg_visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'load' => array(
							'attributes' => array(
								'graph'			=> 'ColumnChart',
								'order-by'		=> 'date'
							),
							'charts' => array (
								'ColumnChart',
								'PieChart',
								'LineChart',
								'BarChart'
							),
							'filters' => array(
								'max_rps' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'max_users' 	=> array('attributes' => array())
							)
						)
					)
				),
				'sources' => array(
					'attributes' 	=> array(),
					'reports' => array(
						'summary' => array(
							'attributes' => array(
								'default' 		=> 1,
								'graph'			=> 'ColumnChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'ColumnChart',
								'PieChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'search_engines' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'phrases' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'phrase'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						)
					)
				),
				'content_' => array(
					'attributes' 	=> array(),
					'reports' => array(
						'popular' => array(
							'attributes' => array(
								'default' 		=> 1,
								'graph'			=> 'PieChart',
								'order-by'		=> 'url'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'page_views' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'exit' 	=> array('attributes' => array()),
								'entrance' 		=> array('attributes' => array())
							)
						),
						'entrance' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'url'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'exit' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'url'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'titles' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'page_views' 		=> array(
									'attributes' => array(
										'default' => 1
									)
								)
							)
						),
						'url_param' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'page_views' 		=> array(
									'attributes' => array(
										'default' => 1
									)
								)
							)
						)
					)
				),
				'tech' => array(
					'attributes' 	=> array(),
					'reports' => array(
						'browsers' => array(
							'attributes' => array(
								'default' 		=> 1,
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'os' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'mobile' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'flash' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						),
						'javascript' => array(
							'attributes' => array(
								'graph'			=> 'PieChart',
								'order-by'		=> 'name'
							),
							'charts' => array (
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						)
					)
				),
				'geo' => array(
					'attributes' 	=> array(
					),
					'reports' => array(
						'geo' => array(
							'attributes' => array(
								'graph'			=> 'GeoMap',
								'order-by'		=> 'en_name',
								'default' 	=> 1
							),
							'charts' => array (
								'GeoMap',
								'PieChart',
								'ColumnChart',
								'BarChart'
							),
							'filters' => array(
								'visits' 		=> array(
									'attributes' => array(
										'default' => 1
										)
									),
								'page_views' 	=> array('attributes' => array()),
								'denial' 		=> array('attributes' => array()),
								'depth' 		=> array('attributes' => array()),
								'visit_time' 	=> array('attributes' => array())
							)
						)
					)
				)
			);

			if ($section && isset($sections[$section])) {
				$sections[$section]['attributes']['selected'] = 1;
				if ($report && isset($sections[$section]['reports'][$report])) {
					$sections[$section]['reports'][$report]['attributes']['selected'] = 1;
					if ($filter && isset($sections[$section]['reports'][$report]['filters'][$filter])) {
						$sections[$section]['reports'][$report]['filters'][$filter]['attributes']['selected'] = 1;
					}
				}
			}

			$data = $this->prepareData($params, 'settings');
			$data['counter'] = $counterId;

			$nodes = array('nodes:section' => array());

			foreach($sections as $sectionName => $sectionValue) {
				$nodes['nodes:section'][$sectionName]['attribute:name'] = $sectionName;
				foreach ($sectionValue['attributes'] as $attributeName => $attributeValue) {
					$nodes['nodes:section'][$sectionName]['attribute:' . $attributeName] = $attributeValue;
				}
				$nodes['nodes:section'][$sectionName]['reports'] = array('nodes:report' => array());
				foreach ($sectionValue['reports'] as $reportName => $reportValue) {

					$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['attribute:name'] = $reportName;
					foreach ($reportValue['attributes'] as $attributeName => $attributeValue) {
						$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['attribute:' . $attributeName] = $attributeValue;
					}
					if (isset($reportValue['charts'])) {
					foreach ($reportValue['charts'] as $chartName) {
						$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['charts']['nodes:chart'][$chartName]['attribute:name'] = $chartName;
					}
					}

					$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['filters'] = array('nodes:filter' => array());
					foreach ($reportValue['filters'] as $filterName => $filterValue) {
						$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['filters']['nodes:filter'][$filterName]['attribute:name'] = $filterName;

						foreach ($filterValue['attributes'] as $attributeName => $attributeValue) {
							$nodes['nodes:section'][$sectionName]['reports']['nodes:report'][$reportName]['filters']['nodes:filter'][$filterName]['attribute:' . $attributeName] = $attributeValue;
						}
					}

				}

			}

			$data['sections'] = $nodes;

			$this->setDataType("settings");
			$this->setActionType("view");

			$this->setData($data);
			return $this->doData();
		}

	}
?>
