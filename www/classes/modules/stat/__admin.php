<?php
	abstract class __stat_admin extends baseModuleAdmin {

        public function config() {
            $regedit = regedit::getInstance();
			$params = array(
				'config' => array(
					'boolean:enabled' 		=> null,
					"string:yandex-token" 	=> null
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/stat/collect", $params['config']['boolean:enabled']);
				$regedit->setVar("//modules/stat/yandex-token", $params["config"]["string:yandex-token"]);
				$this->chooseRedirect();
			}

			$params['config']['boolean:enabled'] = (boolean) $regedit->getVal("//modules/stat/collect");
			$params["config"]["string:yandex-token"] = (string) $regedit->getVal("//modules/stat/yandex-token");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
        }

        public function toolbar() {
            $regedit = regedit::getInstance();
            $controller = cmsController::getInstance();

			$params = array(
				'common' => array(
					'boolean:tips' 		=> null,
					"int:request-time" 	=> null
				),
				'params' => array()
			);

			if ($controller->getModule("users")) $params['params']['boolean:users-user'] = null;
			if ($controller->getModule("emarket")) $params['params']['boolean:emarket-order'] = null;
			if ($controller->getModule("webforms")) $params['params']['boolean:webforms-message'] = null;
			if ($controller->getModule("comments")) $params['params']['boolean:comments-comment'] = null;
			if ($controller->getModule("vote")) $params['params']['boolean:vote-poll_item'] = null;
			if ($controller->getModule("blogs20")) {
				$params['params']['boolean:blogs20-post'] = null;
				$params['params']['boolean:blogs20-comment'] = null;
			}
			if ($controller->getModule("faq")) $params['params']['boolean:faq-question'] = null;
			if ($controller->getModule("forum")) {
				$params['params']['boolean:forum-topic'] = null;
				$params['params']['boolean:forum-message'] = null;
			}


			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/stat/tips", $params['common']['boolean:tips']);
				$regedit->setVar("//modules/stat/request-time", $params["common"]["int:request-time"]);

				if ($controller->getModule("users")) $regedit->setVar("//modules/stat/users-user", $params['params']['boolean:users-user']);
				if ($controller->getModule("emarket")) $regedit->setVar("//modules/stat/emarket-order", $params['params']['boolean:emarket-order']);
				if ($controller->getModule("webforms")) $regedit->setVar("//modules/stat/webforms-message", $params['params']['boolean:webforms-message']);
				if ($controller->getModule("comments")) $regedit->setVar("//modules/stat/comments-comment", $params['params']['boolean:comments-comment']);
				if ($controller->getModule("vote")) $regedit->setVar("//modules/stat/vote-poll_item", $params['params']['boolean:vote-poll_item']);
				if ($controller->getModule("blogs20")) {
					$regedit->setVar("//modules/stat/blogs20-post", $params['params']['boolean:blogs20-post']);
					$regedit->setVar("//modules/stat/blogs20-comment", $params['params']['boolean:blogs20-comment']);
				}
				if ($controller->getModule("faq")) $regedit->setVar("//modules/stat/faq-question", $params['params']['boolean:faq-question']);
				if ($controller->getModule("forum")) {
					$regedit->setVar("//modules/stat/forum-topic", $params['params']['boolean:forum-topic']);
					$regedit->setVar("//modules/stat/forum-message", $params['params']['boolean:forum-message']);
				}

				$this->chooseRedirect();
			}

			$params['common']['boolean:tips'] = (boolean) $regedit->getVal("//modules/stat/tips");
			$params["common"]["int:request-time"] = (int) $regedit->getVal("//modules/stat/request-time");

			if ($controller->getModule("users")) $params['params']['boolean:users-user'] = (boolean) $regedit->getVal("//modules/stat/users-user");
			if ($controller->getModule("emarket")) $params['params']['boolean:emarket-order'] = (boolean) $regedit->getVal("//modules/stat/emarket-order");
			if ($controller->getModule("webforms")) $params['params']['boolean:webforms-message']  = (boolean) $regedit->getVal("//modules/stat/webforms-message" );
			if ($controller->getModule("comments")) $params['params']['boolean:comments-comment'] = (boolean)$regedit->getVal("//modules/stat/comments-comment" );
			if ($controller->getModule("vote")) $params['params']['boolean:vote-poll_item'] = (boolean) $regedit->getVal("//modules/stat/vote-poll_item");
			if ($controller->getModule("blogs20")) {
				$params['params']['boolean:blogs20-post'] = (boolean) $regedit->getVal("//modules/stat/blogs20-post");
				$params['params']['boolean:blogs20-comment'] = (boolean) $regedit->getVal("//modules/stat/blogs20-comment");
			}
			if ($controller->getModule("faq")) $params['params']['boolean:faq-question'] = (boolean) $regedit->getVal("//modules/stat/faq-question");
			if ($controller->getModule("forum")) {
				 $params['params']['boolean:forum-topic'] = (boolean) $regedit->getVal("//modules/stat/forum-topic");
				 $params['params']['boolean:forum-message'] = (boolean) $regedit->getVal("//modules/stat/forum-message");
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
        }


        public function widget() {

        	$buffer = outputBuffer::current();
			$buffer->contentType('text/javascript');
			$buffer->option('generation-time', false);
			$buffer->clear();

			$permissions = permissionsCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$controller = cmsController::getInstance();
			$regedit = regedit::getInstance();
			$host = "http://" . $controller->getCurrentDomain()->getHost();

			$answer = array();
			$answer['version'] = 3;
			$interval = $regedit->getVal("//modules/stat/request-time");
			$answer["update_interval"] =  $interval > 0 ? $interval : 600;
			$answer['update_url_regexps'] = array("^" . str_replace('.', '\\.', $host) . "\/.*");
			$answer['buttons'] = array();
			$userId = $permissions->getUserId();

			if ($userId != $objects->getObjectIdByGUID('system-guest')) {


				//пользователи
				if ($regedit->getVal("//modules/stat/users-user") && $controller->getModule("users") && $permissions->isAllowedMethod($userId, "users", "users_list_all")) {

					$sel = new selector('objects');
					$sel->types('object-type')->name('users', 'user');
		            $sel->option('return')->value('id');
		            $result = $sel->length();

		            $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-users-user') : '';

					$answer['buttons'][] = array(
						"id" 			=> "users",
						"appearance" 	=> array(
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/users.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/users/users_list_all/"
			                )
						)
					);
				}


				//заказы
				if ($regedit->getVal("//modules/stat/emarket-order") && $controller->getModule("emarket") && $permissions->isAllowedMethod($userId, "emarket", "orders")) {

					$sel = new selector('objects');
					$sel->types('object-type')->name('emarket', 'order');
					$sel->where('name')->isNull(false);
		            $sel->where('name')->notequals('dummy');
		            $sel->option('return')->value('id');
		            $result = $sel->length();

		            $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-emarket-order') : '';

					$answer['buttons'][] = array(
						"id" 			=> "orders",
						"appearance" 	=> array (
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/orders.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/emarket/orders/"
			                )
						)
					);
				}

				//сообщения обратной связи
				if ($regedit->getVal("//modules/stat/webforms-message") && $controller->getModule("webforms") && $permissions->isAllowedMethod($userId, "webforms", "messages")) {

					$sel = new selector('objects');
					$sel->types('object-type')->name('webforms', 'form');
		            $sel->option('return')->value('id');
		            $result = $sel->length();

		            $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-webforms-message') : '';

					$answer['buttons'][] = array(
						"id" 			=> "webforms_messages",
						"appearance" 	=> array (
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/messages.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/webforms/messages/"
			                )
						)
					);
				}

				//комментарии
				if ($regedit->getVal("//modules/stat/comments-comment") && $controller->getModule("comments") && $permissions->isAllowedMethod($userId, "comments", "view_comments")) {

					$sel = new selector('pages');
					$sel->types('hierarchy-type')->name('comments', 'comment');
		            $sel->option('return')->value('id');
		            $result = $sel->length();

		            $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-comments-comment') : '';

					$answer['buttons'][] = array(
						"id" 			=> "comments",
						"appearance" 	=> array (
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/comments.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/comments/view_comments/"
			                )
						)
					);
				}

				//голосования
				if ($regedit->getVal("//modules/stat/vote-poll_item") && $controller->getModule("vote") && $permissions->isAllowedMethod($userId, "vote", "lists")) {

					$sel = new selector('objects');
					$sel->types('object-type')->name('vote', 'poll_item');
					$polls = $sel->result();

					$result = 0;
		            foreach ($polls as $pollItem) {
		            	$result += (int) $pollItem->getValue('count');
					}

					$label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-vote-poll_item') : '';

					$answer['buttons'][] = array(
						"id" 			=> "votes",
						"appearance" 	=> array (
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/votes.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/vote/lists/"
			                )
						)
					);
				}


				if ($controller->getModule("blogs20")){

					//посты блога
					if($regedit->getVal("//modules/stat/blogs20-post") && $permissions->isAllowedMethod($userId, "blogs20", "posts")) {
						$sel = new selector('pages');
						$sel->types('hierarchy-type')->name('blogs20', 'post');
				        $sel->option('return')->value('id');
				        $result = $sel->length();

				        $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-blogs20-post') : '';

						$answer['buttons'][] = array(
							"id" 			=> "blogs_posts",
							"appearance" 	=> array (
								"type" 	=> "combined",
								"label" => $label,
								"icon" 	=> "{$host}/images/cms/admin/toolbar/blog_posts.png",
							),
					        "badge" => array(
								"value" 	=> $result,
								"onclick"	=> "remove_badge",
								"highlight" => array(
									"enable"	=> "on_change",
									"color" 	=> "#0088E8"
								)
							),
							"onclick" => array(
								"action" => "openurl",
					            "params" => array (
					                "url" => "{$host}/admin/blogs20/posts/"
					            )
							)
						);
					}

					//комментарии блога
					if($regedit->getVal("//modules/stat/blogs20-comment") && $permissions->isAllowedMethod($userId, "blogs20", "comments")) {
						$sel = new selector('pages');
						$sel->types('hierarchy-type')->name('blogs20', 'comment');
				        $sel->option('return')->value('id');
				        $result = $sel->length();

				        $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-blogs20-comment') : '';

						$answer['buttons'][] = array(
							"id" 			=> "blogs_comments",
							"appearance" 	=> array (
								"type" 	=> "combined",
								"label" => $label,
								"icon" 	=> "{$host}/images/cms/admin/toolbar/blog_comments.png",
							),
					        "badge" => array(
								"value" 	=> $result,
								"onclick"	=> "remove_badge",
								"highlight" => array(
									"enable"	=> "on_change",
									"color" 	=> "#0088E8"
								)
							),
							"onclick" => array(
								"action" => "openurl",
					            "params" => array (
					                "url" => "{$host}/admin/blogs20/comments/"
					            )
							)
						);
					}
				}

				//вопросы
				if ($regedit->getVal("//modules/stat/faq-question") && $controller->getModule("faq") && $permissions->isAllowedMethod($userId, "faq", "projects_list")) {

					$sel = new selector('pages');
					$sel->types('hierarchy-type')->name('faq', 'question');
		            $sel->option('return')->value('id');
		            $result = $sel->length();

		            $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-faq-question') : '';

					$answer['buttons'][] = array(
						"id" 			=> "faq",
						"appearance" 	=> array (
							"type" 	=> "combined",
							"label" => $label,
							"icon" 	=> "{$host}/images/cms/admin/toolbar/questions.png",
						),
			            "badge" => array(
						    "value" 	=> $result,
						    "onclick"	=> "remove_badge",
						    "highlight" => array(
							    "enable"	=> "on_change",
							    "color" 	=> "#0088E8"
							)
						),
						"onclick" => array(
							"action" => "openurl",
			                "params" => array (
			                    "url" => "{$host}/admin/faq/projects_list/"
			                )
						)
					);
				}

				if ($controller->getModule("forum")){

					//Топики в форуме
					if($regedit->getVal("//modules/stat/forum-topic") && $permissions->isAllowedMethod($userId, "forum", "lists")) {
						$sel = new selector('pages');
						$sel->types('hierarchy-type')->name('forum', 'topic');
				        $sel->option('return')->value('id');
				        $result = $sel->length();

				        $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-forum-topic') : '';

						$answer['buttons'][] = array(
							"id" 			=> "forums_topics",
							"appearance" 	=> array (
								"type" 	=> "combined",
								"label" => $label,
								"icon" 	=> "{$host}/images/cms/admin/toolbar/forum_topics.png",
							),
					        "badge" => array(
								"value" 	=> $result,
								"onclick"	=> "remove_badge",
								"highlight" => array(
									"enable"	=> "on_change",
									"color" 	=> "#0088E8"
								)
							),
							"onclick" => array(
								"action" => "openurl",
					            "params" => array (
					                "url" => "{$host}/admin/forum/lists/"
					            )
							)
						);
					}

					//Сообщения в форуме
					if($regedit->getVal("//modules/stat/forum-message") && $permissions->isAllowedMethod($userId, "forum", "last_messages")) {
						$sel = new selector('pages');
						$sel->types('hierarchy-type')->name('forum', 'message');
				        $sel->option('return')->value('id');
				        $result = $sel->length();

				        $label = $regedit->getVal("//modules/stat/tips") ? getLabel('option-forum-message') : '';

						$answer['buttons'][] = array(
							"id" 			=> "forums_messages",
							"appearance" 	=> array (
								"type" 	=> "combined",
								"label" => $label,
								"icon" 	=> "{$host}/images/cms/admin/toolbar/forum_messages.png",
							),
					        "badge" => array(
								"value" 	=> $result,
								"onclick"	=> "remove_badge",
								"highlight" => array(
									"enable"	=> "on_change",
									"color" 	=> "#0088E8"
								)
							),
							"onclick" => array(
								"action" => "openurl",
					            "params" => array (
					                "url" => "{$host}/admin/forum/last_messages/"
					            )
							)
						);
					}
				}

			} else {

				$answer['buttons'][] = array(
					"id" 			=> "auth",
					"appearance" 	=> array (
						"type" 	=> "combined",
						"label" => "Войти",
						"icon" 	=> "{$host}/images/cms/admin/toolbar/umi.png",
					),
					"onclick" => array(
						"action" => "openurl",
		                "params" => array (
		                    "url" 		=> "{$host}/admin/"
		                )
					)
				);


			}

			$json = new jsonTranslator;
			//$answer = json_encode($answer);
			$answer = $json->translateToJson($answer);
			$buffer->push($answer);
			$buffer->end();

		}


        public function clear() {
            $mode      = (string) getRequest('param0');
            if($mode == 'do') {
                $aTables = array('cms_stat_domains', 'cms_stat_entry_points', 'cms_stat_entry_points_events', 'cms_stat_events',
                                 'cms_stat_events_collected', 'cms_stat_events_rel', 'cms_stat_events_urls', 'cms_stat_finders',
                                 'cms_stat_hits', 'cms_stat_holidays', 'cms_stat_pages', 'cms_stat_paths', 'cms_stat_phrases',
                                 'cms_stat_sites', 'cms_stat_sites_groups', 'cms_stat_sources', 'cms_stat_sources_coupon',
                                 'cms_stat_sources_coupon_events', 'cms_stat_sources_openstat', 'cms_stat_sources_openstat_ad',
                                 'cms_stat_sources_openstat_campaign', 'cms_stat_sources_openstat_service',
                                 'cms_stat_sources_openstat_source', 'cms_stat_sources_pr', 'cms_stat_sources_pr_events',
                                 'cms_stat_sources_pr_sites', 'cms_stat_sources_search', 'cms_stat_sources_search_queries',
                                 'cms_stat_sources_sites', 'cms_stat_sources_sites_domains', 'cms_stat_sources_ticket', 'cms_stat_users');
                foreach($aTables as $sTable) l_mysql_query('TRUNCATE `'.$sTable.'`');
                $this->chooseRedirect();
            }
            $params    = array('clear' => array('button:clear' => null));
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
        }

		public function total() {
            $this->updateFilter();
            $params = array();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');

            $params['tagss']['tags:tags_cloud'] = $this->tags_cloud();

			$factory->isValid('visitersCommon');
			$report = $factory->get('visitersCommon');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setDomain($this->domain);
            $report->setUser($this->user);


			$result = $report->get();

			$params['visits']['int:routine'] = (int) $result['avg']['routine'];
			$params['visits']['int:weekend'] = (int) $result['avg']['weekend'];
            $params['visits']['int:sum']     = (int) $result['summ'];

            $factory->isValid('hostsCommon');
            $report = $factory->get('hostsCommon');

            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);

            $result = $report->get();

            $params['visits']['int:hosts_total'] = (int) $result['summ'];

            $factory->isValid('visitCommon');
            $report = $factory->get('visitCommon');

            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);
            $report->setDomain($this->domain); $report->setUser($this->user);

            $result = $report->get();

            $params['visits']['int:hits_total'] = (int) $result['summ'];


			$factory->isValid('visitTime');
			$report = $factory->get('visitTime');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$visit_time = array_pop($result['dynamic']);
			$params['visits']['int:time'] = round($visit_time['minutes_avg'], 2);

			$factory->isValid('visitDeep');
			$report = $factory->get('visitDeep');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$visit_deep = array_pop($result['dynamic']);
			$params['visits']['int:deep'] = round($visit_deep['level_avg'], 2);


			$factory->isValid('sourcesTop');
			$report = $factory->get('sourcesTop');

			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			if (isset($result[0]['cnt'])) {
				$params['sources']['string:top_source'] =  ($result[0]['type'] == "direct" ? getLabel('label-direct-enter') : $result[0]['name']) . " (" . $result[0]['cnt'] . ")";
			}


			$factory->isValid('sourcesSEOKeywords');
			$report = $factory->get('sourcesSEOKeywords');
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$params['sources']['string:top_keyword'] = (isset($result['all'][0]['text'])&&strlen($result['all'][0]['text'])? $result['all'][0]['text']." (" . $result['all'][0]['cnt'] . ")" : "-");


			$factory->isValid('sourcesSEO');
			$report = $factory->get('sourcesSEO');
			$report->setStart($this->from_time);
			$report->setFinish($this->to_time);
			$report->setLimit(1);
			$report->setDomain($this->domain); $report->setUser($this->user);

			$result = $report->get();

			$params['sources']['string:top_searcher'] =
                                (isset($result['all'][0]['name'])&&strlen($result['all'][0]['name'])? $result['all'][0]['name']." (" . $result['all'][0]['cnt'] . ")" : "-");

            $params['filter'] = $this->getFilterPanel();

            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
		}

        public function tag() {
            $this->updateFilter();
            $sReturnMode = getRequest('param1');
            $iTagId      = (int) getRequest('param0');
            $thisHost   = cmsController::getInstance()->getCurrentDomain()->getHost();
            $thisLang   = cmsController::getInstance()->getCurrentLang()->getPrefix();
            $thisMdlUrl = '/'.$thisLang.'/admin/stat/';
            $thisUrl    = $thisMdlUrl.__FUNCTION__.'/'.$iTagId;
            //----------------------------------------------------------------------------------
            if($sReturnMode == 'xml') {
                $factory = new statisticFactory(dirname(__FILE__) . '/classes');
                $factory->isValid('tag');
                $report  = $factory->get('tag');
                $report->setStart($this->from_time);
                $report->setFinish($this->to_time);
                $report->setParams(array("tag_id" => $iTagId));
                $report->setDomain($this->domain); $report->setUser($this->user);
                $aRet  = $report->get();
                $sXML  = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
                $sXML .= "<statistics>\n";
                $sXML .= "  <report name=\"Tag\" title=\"\" lang=\"".$thisLang."\" host=\"".$thisHost."\">
                              <chart type=\"pie\">
                                <argument field=\"uri\" />
                                <value    field=\"count\" />
                                <caption  field=\"uri\" />
                              </chart>
                              <table>
                                <column field=\"uri\"   title=\"Страница\" />
                                <column field=\"count\" title=\"Показов тега (всего)\" />
                                <column field=\"rel\"   title=\"Показов тега (относительно других страниц)\" valueSuffix=\"%\" />
                              </table>
                              <data>";
                foreach($aRet as $aRow) {
                    $sXML .= "      <row uri=\"".$aRow['uri']."\" count=\"".$aRow['count']."\" rel=\"".number_format($aRow['rel']*100, 2, '.', '')."\" />";
                }
                $sXML .= "    </data>\n  </report>\n</statistics>";
                header("Content-type: text/xml; charset=utf-8");
                header("Content-length: ".strlen($sXML));
                $this->flush($sXML);
                return "";
            }
            //----------------------------------------------------------------------------------
            $params = array();
            $params['filter'] = $this->getFilterPanel();
            $params['ReportTag']['flash:report1'] = "url=".$thisUrl."/xml/";
            $this->setDataType("settings");
            $this->setActionType("view");
            $data = $this->prepareData($params, 'settings');
            $this->setData($data);
            return $this->doData();
        }

        public function getFilterPanel() {
            // Some preparings for writing
            $sCurrentURI    = $_SERVER['REQUEST_URI'];
            $sCurrentDomain = ($this->domain)    ? $this->domain    : 'all';
            $sCurrentUser   = ($this->user)      ? $this->user      : '0';
            $iFromTime      = ($this->from_time) ? $this->from_time : time();
            $iToTime        = ($this->to_time)   ? $this->to_time   : time();
            $aDays          = array();
            $aMonths        = array();
            $aYears         = array();

            $aMonthLetters  = array(	getLabel('month-jan'),
					getLabel('month-feb'),
					getLabel('month-mar'),
					getLabel('month-apr'),
					getLabel('month-may'),
					getLabel('month-jun'),
					getLabel('month-jul'),
					getLabel('month-aug'),
					getLabel('month-sep'),
					getLabel('month-oct'),
					getLabel('month-nov'),
					getLabel('month-dec')
				);

            foreach(range(1, 31) as $i) $aDays[]   = array('attribute:id' => $i, 'node:name' => $i);
            foreach(range(1, 12) as $i) $aMonths[] = array('attribute:id' => $i, 'node:name' => $aMonthLetters[$i-1]);
            foreach(range((int)date('Y') - 2, (int)date('Y')) as $iYear) $aYears[] = array('attribute:id' => $iYear, 'node:name' => $iYear);
            // Write in proper way
            $aFP = array();
            $aFP['domain:domain'] = array( 'nodes:item' => array() , 'attribute:id' => $sCurrentDomain );
            $aDomainItems         = &$aFP['domain:domain']['nodes:item'];
            foreach($this->domainArray as $sHost => $sTitle)    $aDomainItems[] = array( 'attribute:id' => $sHost, 'node:name' => $sTitle);
            $aFP['users:user']    = array( 'attribute:id' => $sCurrentUser, 'nodes:item' => array());
            $aUsersItems          = &$aFP['users:user']['nodes:item'];
            foreach($this->usersArray as $sUserId => $sUserName) $aUsersItems[] = array( 'attribute:id' => $sUserId, 'node:name' => $sUserName);
            $aFP['period:start']  = array( 'nodes:entity' => array(
                                            array( 'attribute:type' => 'day',   'attribute:id' => (int)date('d', $iFromTime), 'nodes:item' => $aDays ),
                                            array( 'attribute:type' => 'month', 'attribute:id' => (int)date('m', $iFromTime), 'nodes:item' => $aMonths ),
                                            array( 'attribute:type' => 'year',  'attribute:id' => (int)date('Y', $iFromTime), 'nodes:item' => $aYears )
                                            ));
            $aFP['period:end']    = array( 'nodes:entity' => array(
                                            array( 'attribute:type' => 'day',   'attribute:id' => (int)date('d', $iToTime), 'nodes:item' => $aDays ),
                                            array( 'attribute:type' => 'month', 'attribute:id' => (int)date('m', $iToTime), 'nodes:item' => $aMonths ),
                                            array( 'attribute:type' => 'year',  'attribute:id' => (int)date('Y', $iToTime), 'nodes:item' => $aYears )
                                            ));
            return $aFP;
        }

        public function updateFilter() {
            try {
                $aParam = array('config' => array(
                                        'string:domain'   => null,
                                        'int:user'        => null,
                                        'int:start_day'   => null,
                                        'int:start_month' => null,
                                        'int:start_year'  => null,
                                        'int:end_day'     => null,
                                        'int:end_month'   => null,
                                        'int:end_year'    => null,
                            ));
                $aParam = $this->expectParams($aParam);
                // Setup domian
                if(in_array($aParam['config']['string:domain'], $this->domainArray) || $aParam['config']['string:domain']=='all')
                {
                    $this->domain = $aParam['config']['string:domain'];
                    setcookie('stat_domain', $this->domain, 0, '/');
                } else {
                    if(isset($_COOKIE['stat_domain']))
                    if(in_array($_COOKIE['stat_domain'], $this->domainArray) || $_COOKIE['stat_domain'] == 'all')
                        $this->domain = $_COOKIE['stat_domain'];
                }
                // Setup user
                if(in_array($aParam['config']['int:user'], array_keys($this->usersArray)) || $aParam['config']['int:user']==0)
                {
                    $this->user = $aParam['config']['int:user'];
                    setcookie('stat_user', $this->user, 0, '/');
                } else {
                    if(isset($_COOKIE['stat_user']))
                    if(in_array($_COOKIE['stat_user'], $this->usersArray) || $_COOKIE['stat_user'] == 'all')
                        $this->user = $_COOKIE['stat_user'];
                }
                // Setup start of period
                $fd = (int) $aParam['config']['int:start_day'];
                $fm = (int) $aParam['config']['int:start_month'];
                $fy = (int) $aParam['config']['int:start_year'];
                $this->from_time = (int) strtotime($fy . "-" . $fm . "-" . $fd);
                setcookie("from_time", $this->from_time, 0, "/");
                // Setup end of period
                $td = (int) $aParam['config']['int:end_day'];
                $tm = (int) $aParam['config']['int:end_month'];
                $ty = (int) $aParam['config']['int:end_year'];
                $this->to_time = (int) strtotime($ty . "-" . $tm . "-" . $td);
                if ($this->to_time < $this->from_time) {
                    $this->to_time = strtotime('+1 day', $this->from_time);
                }
                setcookie("to_time", $this->to_time, 0, "/");
            } catch(Exception $e) {
                if(isset($_COOKIE['from_time']))   $this->from_time = (int) $_COOKIE['from_time'];
                if(isset($_COOKIE['to_time']))     $this->to_time   = (int) $_COOKIE['to_time'];
                if(isset($_COOKIE['stat_domain'])) $this->domain    = (in_array($_COOKIE['stat_domain'], $this->domainArray)  || $_COOKIE['stat_domain'] == 'all' )
                                                                ? $_COOKIE['stat_domain'] : 'all';
                if(!$this->domain)          $this->domain    = 'all';
                if(isset($_COOKIE['stat_user'])) $this->user    = (in_array($_COOKIE['stat_user'], array_keys($this->usersArray))  || $_COOKIE['stat_user'] == 0 )
                                                                ? $_COOKIE['stat_user'] : 0;
                if(!$this->user)          $this->user    = 0;
            }
        }

		public static function makeDate($_sFormat, $_iTimeStamp = -1) {
			$aMonthLong = array("Январь","Февраль","Март","Апрель","Май",
								"Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь");
			$aMonthShort = array("Янв","Фев","Мар","Апр","Май",
								 "Июнь","Июль","Авг","Сен","Окт","Ноя","Дек");
			if($_iTimeStamp == -1) $_iTimeStamp = time();
			$iFormatLength = strlen($_sFormat);
			$sDate = "";
			for($i=0; $i<$iFormatLength; $i++) {
				switch($_sFormat[$i]) {
					case 'F': $sDate .=  $aMonthLong[intval(date("n", $_iTimeStamp))]; break;
					case 'M': $sDate .= $aMonthShort[intval(date("n", $_iTimeStamp))-1]; break;
					default:  $sDate .= date($_sFormat[$i], $_iTimeStamp);
				}
			}
			return $sDate;
		}

	};

?>