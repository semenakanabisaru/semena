<?php

	define("STAT_DISABLE", true);
	define("VIA_HTTP_SCHEME", true);

	require CURRENT_WORKING_DIR . '/libs/config.php';
	$buffer = outputBuffer::current('HTTPOutputBuffer');
	$buffer->charset('utf-8');

	$safeSchemes = array('ulang', 'utype');
	$scheme = (string) getRequest('scheme');
	$path = (string) getRequest('path');

	$scheme = preg_replace("/[^\w]/im", "", $scheme);

	$config = mainConfiguration::getInstance();
	$permissions = permissionsCollection::getInstance();
	$objects = umiObjectsCollection::getInstance();

	$cmsController = cmsController::getInstance();
	$cmsController->analyzePath();
	$cmsController->getModule($cmsController->getCurrentModule());
	$currentTemplater = $cmsController->getCurrentTemplater();

	if(!isAllowedScheme($scheme)) streamHTTPError('unkown-scheme', $scheme);

	if($path_srv = getServer('REQUEST_URI')) {
		preg_match("/\/(" . implode("|", $config->get('streams', 'enable')) . "):?\/{0,2}(.*)?/i", $path_srv, $out);
		$path = $out[2];

		$_SERVER['REQUEST_URI'] = '/' . $scheme . '/' . $path;
	}

	$isJson = strpos($path, '.json') !== false;
	$buffer->contentType($isJson ? 'text/javascript' : 'text/xml');
	$buffer->option('generation-time', !$isJson);

	if(!$config->get('streams', $scheme . '.http.allow') && !in_array($scheme, $safeSchemes)) {
			$securityLevel = $config->get('streams', $scheme . '.http.permissions');

			$isAllowedPermission = FALSE;
			if($securityLevel && $securityLevel != 'all') {
				$userId = $permissions->getUserId();
				$user = $objects->getObject($userId);
				$groups = $user->groups;

				$isAllowedPermission = $permissions->isSv($userId);
				switch($securityLevel) {
					case 'sv': break;
					case 'admin':
						$isAllowedPermission = $permissions->isAdmin() || $isAllowedPermission;
						break;

					case 'auth':
						$isAllowedPermission = $permissions->isAuth() || $isAllowedPermission;
						break;

					default: {
						$ids = split(",", $securityLevel);
						foreach($ids as $id) {
							$id = trim($id);
							if(is_numeric($id) && ($id == $userId) || (is_array($groups) && in_array($id, $groups))) {
								$isAllowedPermission = true;
								break;
							}
						}
					}
				}
			}

				$data =  explode('/',$path);
				$module = isset($data[0]) ? $data[0]:'';
				$method = isset($data[1]) ? $data[1]:'';

			$isAllowedIp = FALSE;
			$isAllowedMethod = $config->get('streams', $scheme . '.http.allow.'.$module.".".$method) == '1';

			$remoteIP = getServer('REMOTE_ADDR');
			if(!$isAllowedMethod && $remoteIP !== null) {
				$ipList = $config->get('streams', $scheme . '.http.ip-allow.'.$module.".".$method);
				$ipListWholeScheme = $config->get('streams', $scheme . '.http.ip-allow');

				if( !empty ($ipList)  ) {
					$isAllowedIp = strpos($ipList, $remoteIP) !== false;
				}
				else if( !empty ($ipListWholeScheme)  ) {
					$isAllowedIp = strpos($ipListWholeScheme, $remoteIP) !== false;
				}
			}

			if(!$isAllowedPermission && !$isAllowedIp && !$isAllowedMethod) {
				streamHTTPError('http-not-allowed', $scheme);
			}

	}

	if ($scheme == 'ulang') {
		$buffer->contentType('text/plain');
		if(strpos(getServer('HTTP_USER_AGENT'), "MSIE") !== false) {
			$buffer->option('compression', false);
		}
	}

	try {
		$result = $cmsController->executeStream($scheme . "://" . $path);
		$buffer->push($result);
		$buffer->end();
	} catch (Exception $e) {
		streamHTTPError(false, false, $e);
	}


	function isAllowedScheme($scheme) {
		static $allowedSchemes = null;
		if(is_null($allowedSchemes)) {
			$allowedSchemes = mainConfiguration::getInstance()->get('streams', 'enable');
		}
		return in_array($scheme, $allowedSchemes);
	}

	function streamHTTPError($errorCode = false, $scheme = false, Exception $exception = NULL) {
		$buffer = outputBuffer::current();
		$buffer->contentType('text/xml');


		switch($errorCode) {
			case 'unkown-scheme': {
				$errorResponse = <<<XML
<error><![CDATA[Unknown scheme "{$scheme}"]]></error>
XML;
				break;
			}

			case 'http-disabled': {
				$errorResponse = <<<XML
<udata generation-time="0.0"><error><![CDATA[Protocol "{$scheme}://" is not allowed on this site]]></error></udata>
XML;
				break;
			}

			case 'http-not-allowed': {
				$errorResponse = <<<XML
<udata generation-time="0.0"><error><![CDATA[You don't have permissions to call protocol "{$scheme}://" via HTTP]]></error></udata>
XML;
				break;
			}

			default: {
				$message = 'Requested resource not found';
				if($exception) {
					$message = $exception->getMessage();
				}
				$errorResponse = <<<XML
<udata generation-time="0.0"><error><![CDATA[{$message}]]></error></udata>
XML;
			}
		}
		$buffer->push('<?xml version="1.0" encoding="utf8" ?>');
		$buffer->push($errorResponse);
		$buffer->end();
	}
?>