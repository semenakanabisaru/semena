<?php
	require CURRENT_WORKING_DIR . '/libs/config.php';

	$cmsController = cmsController::getInstance();
	$config = mainConfiguration::getInstance();
	$domain = $cmsController->getCurrentDomain();

	$buffer = outputBuffer::current('HTTPOutputBuffer');
	$buffer->contentType('text/plain');
	$buffer->charset('utf-8');

	$rules = "";

	$sel = new selector('pages');
	$sel->where('robots_deny')->equals(1);

	foreach($sel->result as $element) {
		$rules .= "Disallow: " . $element->link . "\r\n";
	}

	$rules .= "Disallow: /admin\r\n";
	$rules .= "Disallow: /index.php\r\n";
	$rules .= "Disallow: /emarket/addToCompare\r\n";
	$rules .= "Disallow: /emarket/basket\r\n";
	$rules .= "Disallow: /go_out.php\r\n";
	$rules .= "Disallow: /search\r\n";

	if (file_exists(CURRENT_WORKING_DIR . "/robots/" . $domain->getId() . ".robots.txt")) {
		$customRobots = file_get_contents(CURRENT_WORKING_DIR . "/robots/" . $domain->getId() . ".robots.txt");
		if (strlen($customRobots)) {
			if (strpos($customRobots, "%disallow_umi_pages%") !== false) {
				$customRobots = str_replace("%disallow_umi_pages%", $rules, $customRobots);
			}
			$buffer->push($customRobots);
			$buffer->end();
			exit();
		}
	}

	$rules  = "Disallow: /?\r\n" . $rules;

	$buffer->push("User-Agent: Googlebot\r\n");
	$buffer->push($rules . "\r\n");

	$buffer->push("User-Agent: Yandex\r\n");
	$buffer->push($rules);
	$crawlDelay = $config->get('seo', 'crawl-delay');
	$primaryWWW = (bool) $config->get('seo', 'primary-www');
	$host = $domain->getHost();
	$host = preg_replace('/^www./', '', $host);
	if($primaryWWW) {
		$host = 'www.' . $host;
	}
	$buffer->push("Host: {$host} \r\n");
	$buffer->push("Crawl-delay: {$crawlDelay}\r\n\r\n");

	$buffer->push("User-Agent: *\r\n");
	$buffer->push($rules . "\r\n");

	$buffer->push("Sitemap: http://{$host}/sitemap.xml \r\n");

	$buffer->end();
?>