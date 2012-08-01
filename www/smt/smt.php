<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	include('testhost.php');

	header("Cache-Control: no-store, no-cache, must-revalidate");	// HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);	// HTTP/1.1
	header("Pragma: no-cache");	// HTTP/1.0
	header("Date: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("X-XSS-Protection: 0"); //Disable new IE8 XSS filter
	header("Content-type: text/html; charset=utf-8");

	header("Content-type: text/xml; charset=utf-8");

	$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 'test-mode';

	$testMode = isset($_REQUEST['test-mode']) ? $_REQUEST['test-mode'] : 'install'; // install | migration
	$dbHost = isset($_REQUEST['db-host']) ? $_REQUEST['db-host'] : false;
	$dbUser = isset($_REQUEST['db-user']) ? $_REQUEST['db-user'] : false;
	$dbPassword = isset($_REQUEST['db-password']) ? $_REQUEST['db-password'] : false;
	$dbName = isset($_REQUEST['db-name']) ? $_REQUEST['db-name'] : false;

	$userEmail = isset($_REQUEST['user-email']) ? $_REQUEST['user-email'] : false;
	$userKey = isset($_REQUEST['user-key']) ? $_REQUEST['user-key'] : false;

	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

	if($step == 'test-results'){
		if($dbHost == false || $dbUser == false || $dbPassword == false || $dbName == false){
			echo <<<XML
			<response type="test-mode">
			<error><![CDATA[13042]]></error>
			</response>
XML;
		}
		else {
			$test = new testHost;
			$test->setConnect($dbHost, $dbUser, $dbPassword, $dbName, $testMode);
			$test->getResults();
			if($testMode == 'install'){
				echo '<response type="test-complete">';
				if (count($test->listErrors) != 0){
					foreach($test->listErrors as $key => $value) {
						if($value[1] == 1) $is_critical = 'is-critical="1"';
						else $is_critical = '';
						echo'<error ' . $is_critical . ' params="' . $value[2] . '"><![CDATA[' . $value[0] . ']]></error>';
					}
				}
				echo'</response>';
			}
			else {
				if (count($test->listErrors) == 0){
					echo '<response type="test-change-key" />';
				}
				else {
					echo '<response type="test-complete">';
						foreach($test->listErrors as $key => $value) {
							if($value[1] == 1) $is_critical = 'is-critical="1"';
							else $is_critical = '';
							echo'<error ' . $is_critical . ' params="' . $value[2] . '"><![CDATA[' . $value[0] . ']]></error>';
						}
					echo'</response>';
				}
			}
		}
	}
	elseif($step == 'test-change-key'){

			echo <<<XML
			<response type="test--change-key">
			<error><![CDATA[А здесь будет ссылка]]></error>
			</response>
XML;
	}
?>
