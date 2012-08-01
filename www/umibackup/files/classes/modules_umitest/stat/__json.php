<?php
	abstract class __json_stat extends baseModuleAdmin {
		public function json_get_referer_pages() {
            $this->updateFilter();
			$requestId = (int) $_REQUEST['requestId'];
			
			if($host = getRequest('host')) {
				$_SERVER['HTTP_HOST'] = $host;
			}

			$domain_url = "http://" . $_SERVER['HTTP_HOST'];
			$referer_uri = str_replace($domain_url, "", $_SERVER['HTTP_REFERER']);

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('pageNext');
			$report = $factory->get('pageNext');



			$report->setStart(time() - 3600*24*7);	//TODO: Fix to real dates
			$report->setFinish(time() + 3600*24);	//TODO: Fix to real dates
			
			if(!$referer_uri) $referer_uri = "/";

			$report->setParams( Array("page_uri" => $referer_uri) );

			$result = $report->get();

			$res = <<<END
var response = new lLibResponse({$requestId});
response.links = new Array();


END;

			$total = 0;

			foreach($result as $r_item) {
				$total += (int) $r_item['abs'];

				$res .= <<<END
response.links[response.links.length] = {"uri": "{$r_item['uri']}", "abs": "{$r_item['abs']}"};

END;
			}


			$res .= <<<END

response.total = '{$total}';

END;


			$res .= <<<END

lLib.getInstance().makeResponse(response);

END;

			$this->flush($res);
		}
	};
?>