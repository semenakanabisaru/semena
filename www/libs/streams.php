<?php

	$config = mainConfiguration::getInstance();
	$enabled = $config->get('streams', 'enable');
	if(is_array($enabled)) {
		foreach($enabled as $streamName) {
			umiBaseStream::registerStream($streamName);
		}
	}
	
	if($userAgent = $config->get('streams', 'user-agent')) {
		$opts = array(
			'http' => array(
					'user_agent' => $userAgent,
			)
		);
	
		$context = stream_context_create($opts);
		libxml_set_streams_context($context);
	}	
?>