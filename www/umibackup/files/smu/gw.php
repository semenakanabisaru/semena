<?php
error_reporting(~E_ALL);
ini_set("display_errors", 0);
//---------------------------------------------
define("SMU_PROCESS", true);

require("./core.php");
require("./lib/ServerRequest.php");
require("./lib/baseXmlConfig.php");
require("./lib/umiSimpleXML.php");
require("./lib/xmlTranslator.php");
require("./lib/packageInstaller.php");
require("./lib/pclzip.lib.php");

define("CONFIG_INI_PATH", dirname(dirname(__FILE__)) . "/config.ini");
define("_C_REQUIRES", true);
define("_C_ERRORS", true);

require("./lib/umicms-microcore.php");

//---------------------------------------------
header("Content-type: text/xml; charset=utf-8");
//--------------------------------------------- 
if(!is_writable(dirname(__FILE__))) die('<'.'?xml version="1.0" encoding="utf-8" ?'.'><response type="exception"><error code="1002">SMU directory is not writable</error></response>');
$Core = new SMUCore();
$Core->doUpdate();
?>