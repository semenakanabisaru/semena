<?php
define("UPDATE_SERVER",       base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS9zbXVzL3NlcnZlci5waHA='));
define("LOCAL_PACKAGE_STORE", dirname(__FILE__).'/packages/');
define("UPDATE_TEMP_STORE",   dirname(__FILE__).'/install-temp/');
define("PROCESS_SAVE_FILE",   dirname(__FILE__).'/uprocess.ssd');
define("CONFIGURATION_FILE",  dirname(__FILE__).'/configuration.xml');
define("UPDATE_LOG", dirname(__FILE__)."/updatelog.txt");
/**
* @param String $_message
*/
function __dbg($_message) {
	file_put_contents(UPDATE_LOG, $_message . "\n", FILE_APPEND);
}
/**
*
*/
class SMUException extends Exception {
	// System errors
	const ERR_NOTAUTHORIZED = 1001;
	const MSG_NOTAUTHORIZED = "Not authorized";
	// Internal errors
	const ERR_NOSTEPDEFINED = 1101;
	const MSG_NOSTEPDEFINED = "No step defined";
	const ERR_CANTWRITEPACKAGEFILE = 1102;
	const MSG_CANTWRITEPACKAGEFILE = "Cant write package file";
	// Communication errors
	const ERR_WRONGRESPONSE     = 1201;
	const MSG_WRONGRESPONSE     = "Wrong response";
	const ERR_INCORRECTTRANSFER = 1202;
	const MSG_INCORRECTTRANSFER = "Incorrect transfer";
	// Special errors
	const ERR_UNKNOWN       = 1999;
	const MSG_UNKNOWN       = "Unknown error";
	//
	protected $type = "";
	public function __construct($_message = "", $_code = "", $_type = "exception") {
		parent::__construct($_message, $_code);
		$this->type = $_type;
	}
	public function getType() {
		return $this->type;
	}
	public function setType($_type) {
		$this->type = $_type;
	}
};
/**
* @desc
*/
class SMUCore {
	private $configuration = null;
	private $stepState     = null;
	public function __construct() {
		if(!file_exists(CONFIGURATION_FILE))	{
			$this->writeConfiguration();
		}
		if(!is_dir(LOCAL_PACKAGE_STORE)) {
			mkdir(LOCAL_PACKAGE_STORE);
		}
		$this->configuration = new umiSimpleXML( file_get_contents(CONFIGURATION_FILE) );
		$this->loadStepState();
	}
	public function __destruct() {
		$this->saveStepState();
	}
	public function doUpdate() {
		try {
			$continue = true;
			$step   = $this->getStepInstance();
			$result = $step->run($this->configuration, $this->stepState['parameters'], $continue);
			$this->stepState['sid'] = $step->getSID();
			$xml    = new xmlTranslator();
			$result = $xml->translateToXml($result);
			echo substr($result, strpos($result, '?>')+2);
			if(!$continue) {
				$this->getNextStep();
			}
		} catch(Exception $e) {
			if($e->getCode() == 202) {
				$this->stepState = null;
			}
			if($e instanceof SMUException) {
				$type  = $e->getType();
				$trace = '';
			} else {
				$type  = 'exception';
				$trace = "\n\t\t<trace>" . $e->getTraceAsString() . "</trace>\n";
			}
			echo <<<XML
<response type="{$type}">
	<error code="{$e->getCode()}">{$e->getMessage()}{$trace}</error>
</response>
XML;
			return false;
		}
		return true;

	}
	private function writeConfiguration() {
		$regedit = regedit::getInstance();
		$modules = $regedit->getList("//modules");
		$modules = array_map(create_function("\$a", "return \"\t\t\t<module>\".\$a[0].\"</module>\";"), $modules);
		$modules = implode("\r\n",$modules);
		// {$regedit->getVal('//settings/keycode')}
		$xml     = "<"."?xml version=\"1.0\" encoding=\"utf-8\" ?".">\n" . <<<XML
<configuration>
	<systeminfo>
		<domainkey></domainkey>
		<version>{$regedit->getVal('//modules/autoupdate/system_version')}</version>
		<build>{$regedit->getVal('//modules/autoupdate/system_build')}</build>
		<line>pro</line>
		<edition>{$regedit->getVal('//modules/autoupdate/system_edition')}</edition>
		<modules>
{$modules}
		</modules>
	</systeminfo>
</configuration>
XML;
		file_put_contents(CONFIGURATION_FILE, $xml);
	}
	private function loadStepState() {
		if(file_exists(PROCESS_SAVE_FILE)) {
			$raw = file_get_contents(PROCESS_SAVE_FILE);
			if($raw !== false) {
				$this->stepState = unserialize($raw);
			}
		}
		if(!is_array($this->stepState)) $this->stepState = array('step' => 'init', 'sid' => '', 'parameters' => array());
		return $this->stepState;
	}
	private function saveStepState() {
		if($this->stepState['step'] != 'done') {
			$raw = serialize($this->stepState);
			file_put_contents(PROCESS_SAVE_FILE, $raw);
		}
	}
	private function getStepInstance() {
        if(isset($_GET['cancel']) &&
            !($this->stepState['step'] == 'cleanup' || $this->stepState['step'] == 'finalize')) {
           $this->stepState['parameters']['cancel'] = true;
           $this->stepState['step']   = 'cleanup';
        }
		switch($this->stepState['step']) {
			case 'init'  		 : return new InitStep($this->stepState['sid']);
			case 'download'		 : return new DownloadPackageStep($this->stepState['sid']);
			case 'unpack'		 : return new UnpackStep($this->stepState['sid']);
			case 'install'		 : return new InstallUpdateStep($this->stepState['sid']);
			case 'cleanup'		 : return new ClearTemporaryFiles($this->stepState['sid']);
			case 'finalize'		 : return new FinalizeUpdateStep($this->stepState['sid']);
			default      		 : throw new SMUException(SMUException::MSG_NOSTEPDEFINED, SMUException::ERR_NOSTEPDEFINED); return null;
		}
	}
	private function getNextStep() {
		switch($this->stepState['step']) {
			case 'init'  	: $this->stepState['step'] = 'download'; break;
			case 'download' : $this->stepState['step'] = 'unpack';   break;
			case 'unpack' 	: $this->stepState['step'] = 'install';  break;
			case 'install' 	: $this->stepState['step'] = 'cleanup'; break;
			case 'cleanup'  : $this->stepState['step'] = 'finalize'; break;
			case 'finalize' : $this->stepState['step'] = 'done';	 break;
		}
		return $this->stepState['step'];
	}
};
/**
* @desc
*/
interface IUpdateStep {
	public function run($_Configuration, &$parameters, &$continue);
	public function getSID();
};
abstract class UpdateStep implements IUpdateStep {
	protected $sid = "";
	public function __construct($_sid = "") { $this->sid = $_sid; }
	public function getSID() { return $this->sid; }
};
class InitStep extends UpdateStep {
	public function run($_Configuration, &$parameters, &$continue) {
		$this->checkUpdatePossibility();
		$info = $_Configuration->systeminfo;
		$defaultDomain = ($tmp = domainsCollection::getInstance()->getDefaultDomain()) ? $tmp->getHost() : '';
		$data = array(
						'attribute:type'    => 'update-init',
						'nodes:system-info' => array(array(
															'nodes:version' => array(array('attribute:version' => $info->version->value(),
															 							   'attribute:build'   => $info->build->value())),
															'nodes:last-update-time' => array(array('node:name' => regedit::getInstance()->getVal('//modules/autoupdate/last_updated'))),
															'nodes:license' => array(array('attribute:edition' => $info->edition->value(),
															 							   'attribute:key'     => regedit::getInstance()->getVal('//settings/keycode'),
															 							   'attribute:domain'  => $defaultDomain)),
															'nodes:server'	=> array(array('attribute:domain' => $_SERVER['SERVER_NAME'],
																						   'attribute:ip'	  => $_SERVER['SERVER_ADDR'],
																						   'nodes:os' 		  => array(array('node:name' => php_uname())),
																						   'nodes:web-server' => array(array('node:name' => $_SERVER['SERVER_SOFTWARE'])),
																						   'nodes:db-driver'  => array(array('node:name' => $this->getDriverString()))
																						   )),
															'nodes:modules' => array(array('nodes:module'=>array()))
														  ))
					 );
		foreach($info->modules->module as $moduleName) {
			$data['nodes:system-info'][0]['nodes:modules'][0]['nodes:module'][] = array('node:name' => $moduleName);
		}
		if(defined("PACKAGEINSTALLER_VERSION")) {
			list(,$pibuild) = explode(":", PACKAGEINSTALLER_VERSION);
			$pibuild = trim($pibuild);
			$data['nodes:system-info'][0]['nodes:piversion'] = array(array('attribute:build' => $pibuild));
		}
		$xml = new xmlTranslator('request');
		$xmlString = $xml->translateToXml($data);
		$request   = getServerRequest();
		$request->addPostVariable('rdata', $xmlString);
		$result    = $request->execute(UPDATE_SERVER);
		$resultXML = new umiSimpleXML($result);
		if($resultXML->name() != 'response') {
			throw new SMUException(SMUException::MSG_WRONGRESPONSE, SMUException::ERR_WRONGRESPONSE, "update-init");
		}
		$this->sid = $resultXML->attribute('smu_sid');
		if(!$resultXML->package) {
			$errorDescription = SMUException::MSG_UNKNOWN;
			$errorCode		  = SMUException::ERR_UNKNOWN;
			if($resultXML->error) {
				$error = is_array( $tmp = $resultXML->error ) ? $tmp[0] : $tmp;
				$errorDescription = (string)$error->value();
				$errorCode		  = $error->attribute('code');
			}
			$parameters['last-error'] 		= $errorDescription;
			$parameters['last-error-code']  = $errorCode;
			throw new SMUException($errorDescription, $errorCode, $resultXML->attribute("type"));
		} else {
			$continue = false;
		}
		return $result;
	}
	private function getDriverString() {
		if(defined('DB_DRIVER')) {
			switch(DB_DRIVER) {
				case 'xml' : return 'xml';
				default    : return 'default MySQL';
			}
		} else {
			return 'default MySQL';
		}
	}
	private function checkUpdatePossibility() {
		session_start();
		$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
		$svId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor');
		if($userId != $svId && !$this->checkServer()) {
			throw new SMUException(SMUException::MSG_NOTAUTHORIZED, SMUException::ERR_NOTAUTHORIZED, "update-init");
		}
		return true;
	}
	private function checkServer() {
		$updateServerList = array(base64_decode('dWRvZC51bWlob3N0LnJ1'), base64_decode('ZHlhdGVsLnVtaWhvc3QucnU='), base64_decode('dW1paG9zdC5ydQ=='));
		$currentClient    = $_SERVER['REMOTE_ADDR'];
		foreach($updateServerList as $server) {
			if($currentClient == gethostbyname($server)) {
				return true;
			}
		}
		return false;
	}
};
class DownloadPackageStep extends UpdateStep {
	public function run($_Configuration, &$parameters, &$continue) {
		$result = null;
		if(!isset($parameters['version'])) {
			if(!isset($_REQUEST['package_version'])) {
				$init     = new InitStep($this->getSID());
				$result   = $init->run($_Configuration, $parameters, $continue);
				$continue = true;
				return $result;
			}
			$parameters['version'] = $_REQUEST['package_version'];
		}
		$version   = $parameters['version'];
		$xmlString = "<?xml version=\"1.0\" encoding=\"utf-8\"?><request type=\"update-download\" smu_sid=\"".$this->sid."\"><require version=\"".$version."\" /></request>";
		$request   = getServerRequest();
		$request->addPostVariable('smu_sid', $this->sid);
		$request->addPostVariable('rdata', $xmlString);
		$result    = $request->execute(UPDATE_SERVER."?smu_sid=".$this->sid);
		$resultXML = new umiSimpleXML($result);
		if($transfer = $resultXML->xpath("binary-transfer")) {
			$name  = $transfer->name;
			$range = $transfer->range;
			$size  = $transfer->size;
			$data  = $transfer->data;
			if(!($name && $range && $data)) {
				$errorDescription = SMUException::MSG_INCORRECTTRANSFER;
				$errorCode		  = SMUException::ERR_INCORRECTTRANSFER;
				$parameters['last-error'] 		= $errorDescription;
				$parameters['last-error-code']  = $errorCode;
				throw new SMUException($errorDescription, $errorCode, $resultXML->attribute("type"));
			}
			$filename = LOCAL_PACKAGE_STORE . $name->value();
			$start    = (int) $range->attribute('start');
			$length   = (int) $range->attribute('size');
			try{
				$this->WriteFilePiece($filename, base64_decode($data->value()), $start, $length);
			} catch(Exception $e) {
				$errorDescription = SMUException::MSG_CANTWRITEPACKAGEFILE;
				$errorCode		  = SMUException::ERR_CANTWRITEPACKAGEFILE;
				$parameters['last-error'] 		= $errorDescription;
				$parameters['last-error-code']  = $errorCode;
				throw $e;
			}
			if(!isset($parameters['recieved-bytes'])) {
				$parameters['recieved-bytes'] = $length;
			} else {
				$parameters['recieved-bytes']+= $length;
			}
			$parameters['filename']	= $name->value();
			$result = array('attribute:type'=>'update-download', 'nodes:downloaded'=>array(array('attribute:total'=>$size->value(),'attribute:recieved'=>$parameters['recieved-bytes'])));
			if($parameters['recieved-bytes'] >= $size->value() || $range->attribute("complete") == "1") {
				$continue = false;
				$result['nodes:downloaded'][0]['attribute:status'] = "complete";
			}
		} else if($resultXML->retry) {
			$result = array('nodes:wait'=>array(array('attribute:timeout'=>$retry->item(0)->attributes->getNamedItem('timeout')->nodeValue)));
		} else {
			$errorDescription = SMUException::MSG_UNKNOWN;
			$errorCode		  = SMUException::ERR_UNKNOWN;
			if($resultXML->error) {
				$error = is_array( $tmp = $resultXML->error ) ? $tmp[0] : $tmp;
				$errorDescription = (string)$error->value();
				$errorCode		  = $error->attribute('code');
			}
			$parameters['last-error'] 		= $errorDescription;
			$parameters['last-error-code']  = $errorCode;
			throw new SMUException($errorDescription, $errorCode, $resultXML->attribute("type"));
		}
		return $result;
	}
	/**
	* @desc Carefully writes a piece of file
	* @param String $name   Name of the file
	* @param Mixed  $data   Binary data to write
	* @param Int	$start  Begining of the piece on the file
	* @param Int	$length Count of bytes to write
	*/
	private function WriteFilePiece($name, $data, $start, $length) {
		//clearstatcache();
		//if(is_writable($name)) 					 	    throw new SMUException("Update package file is not writable", SMUException::ERR_CANTWRITEPACKAGEFILE, "update-download");
		if(($handle = @fopen($name, "a")) === false) 	throw new SMUException("Can not open update package file", SMUException::ERR_CANTWRITEPACKAGEFILE, "update-download");
		if(fseek($handle, $start) != 0) 			 	throw new SMUException("Can not seek {$start} bytes from the begining of the update package file", SMUException::ERR_CANTWRITEPACKAGEFILE, "update-download");
		if(fwrite($handle, $data, $length) != $length) 	throw new SMUException("Can not write {$length} bytes to the update package file", SMUException::ERR_CANTWRITEPACKAGEFILE, "update-download");
		if(!fclose($handle)) 							throw new SMUException("Can not close update package file", SMUException::ERR_CANTWRITEPACKAGEFILE, "update-download");
	}
};
class UnpackStep extends UpdateStep {
	public function run($_Configuration, &$parameters, &$continue) {
		if(!isset($parameters['check_done']))   $parameters['check_done'] 	= false;
		if(!isset($parameters['check_result'])) $parameters['check_result'] = true;
		if(!isset($parameters['check_repeat'])) $parameters['check_repeat'] = 0;
		if(isset($_REQUEST['recheck'])) { $parameters['check_repeat']++; $parameters['check_result'] = true; $parameters['check_done'] = false; }
		else if($parameters['check_result']&&$parameters['check_done']) {
			$continue = false;
			return "<?xml version=\"1.0\" encoding=\"utf-8\"?><response type=\"check-poll-response\" smu_sid=\"".$this->sid."\"><checking result=\"success\"></checking></response>";
		}
		$filename = LOCAL_PACKAGE_STORE . $parameters['filename'];
		$checker  = new packageChecker($filename, dirname(__FILE__) . "/..");
		$result   = 'progress';
		$status   = array('check_repeat' => $parameters['check_repeat'], 'result' => isset($parameters['check_result']) ? $parameters['check_result'] : true);
		if($checker->check($status)) {
			if($status['result'] == true) {
				$result   = 'success';
			} else {
				$result   = 'fail';
			}
			$parameters['check_done'] = true;
		}
		$parameters['check_result'] = $status['result'];
		return "<?xml version=\"1.0\" encoding=\"utf-8\"?><response type=\"check-poll-response\" smu_sid=\"".$this->sid."\"><checking result=\"{$result}\">".$this->prepareFileList($status['errors'])."</checking></response>";
	}
	private function prepareFileList($errors) {
		$result = "";
		if(!empty($errors['folders'])) 	$result .= "<folder>" . implode("</folder><folder>", $errors["folders"]) . "</folder>";
		if(!empty($errors['files'])) 	$result .= "<file>"   . implode("</file><file>", $errors["files"]) . "</file>";
		return $result;
	}
};
class InstallUpdateStep extends UpdateStep {
	public function run($_Configuration, &$parameters, &$continue) {
		$filename  = LOCAL_PACKAGE_STORE . $parameters['filename'];
		$installer = new packageInstaller($filename, dirname(__FILE__) . "/..");
		$ready     = 0;
		$status    = array();
		if($installer->install($status)) {
			$continue = false;
			$ready = "done";
		} else {
			$ready = $status['percent'];
		}
		header("Content-type: text/xml; charset=utf-8");
		if(!isset($status['component'])) {
			$status['component'] = null;
			$status['part'] = null;
		}

		return "<?xml version=\"1.0\" encoding=\"utf-8\"?><response type=\"poll-response\" smu_sid=\"".$this->sid."\"><status ready=\"{$ready}\"><component name=\"{$status['component']}\" part=\"{$status['part']}\" /></status></response>";
	}
};
class ClearTemporaryFiles extends UpdateStep {
	private $timeCounter 	 = null;
	const TIME_LIMIT     	 = 8.0;
	public function __construct($_sid = "") { parent::__construct($_sid); $this->timeCounter = microtime(true); }
	public function run($_Configuration, &$parameters, &$continue) {
		try {
			$filename  = substr(LOCAL_PACKAGE_STORE, 0, -1);
			$this->cleanupPackage($filename);
			$filename  = substr(UPDATE_TEMP_STORE, 0, -1);
			$this->cleanupPackage($filename);
		} catch(Exception $e) {
			if($e->getCode() == 8888) {
				return '<'.'?xml version="1.0" encoding="utf-8" ?'.'><response type="temp-cleanup"><status ready="progress" /></response>';
			} else {
				throw $e;
			}
		}
		$continue = false;
		return '<'.'?xml version="1.0" encoding="utf-8" ?'.'><response type="temp-cleanup"><status ready="done" /></response>';
	}
	private function cleanupPackage($folder) {
		$list = glob($folder . "/*");
		if(is_array($list))
		foreach($list as $path) {
			if(is_dir($path)) {
				$this->cleanupPackage($path);
				rmdir($path);
			} else {
				unlink($path);
			}
			if((microtime(true) - $this->timeCounter) > self::TIME_LIMIT) {
				throw new Exception("Time limit exceed", 8888);
			}
		}
		@unlink($folder.'/.htaccess');
	}
};
class FinalizeUpdateStep extends UpdateStep {
	public function run($_Configuration, &$parameters, &$continue) {
        if( !(isset($parameters['cancel']) && $parameters['cancel']) ) {
            $xmlString = "<?xml version=\"1.0\" encoding=\"utf-8\"?><request type=\"update-success\" smu_sid=\"".$this->sid."\"><status ready=\"done\" /></request>";
            $request   = getServerRequest();
            $request->addPostVariable('smu_sid', $this->sid);
            $request->addPostVariable('rdata', $xmlString);
            $result    = $request->execute(UPDATE_SERVER);
            $response  = new umiSimpleXML($result);
            $license   = $response->license;
            if($license) {
                $regedit = regedit::getInstance();
                $regedit->setVal('//modules/autoupdate/system_version', $license->attribute('version'));
                $regedit->setVal('//modules/autoupdate/system_build',   $license->attribute('build'));
                $regedit->setVal('//modules/autoupdate/last_updated', time());
            }
        } else {
            $xmlString = "<?xml version=\"1.0\" encoding=\"utf-8\"?><response type=\"update-success\" smu_sid=\"".$this->sid."\"><status ready=\"cancelled\" /></response>";
        }
		$this->systemCleanup(dirname(__FILE__) . "/..", $parameters);
		$continue = false;
		return $result;
	}
	private function systemCleanup($rootFolder, $parameters) {
		$filename  = LOCAL_PACKAGE_STORE . $parameters['filename'];
		unlink(PROCESS_SAVE_FILE);
		unlink(CONFIGURATION_FILE);
		$list = glob($rootFolder . "/sys-temp/runtime-cache/*");
		if(is_array($list))
		foreach($list as $path) {
			if(is_file($path) && !in_array(basename($path), array('trash', 'branchedTablesRelations.rel'))) {
				unlink($path);
			}
		}
	}
	private function cleanupPackage($folder) {
		$list = glob($folder . "/*");
		if(is_array($list))
		foreach($list as $path) {
			if(is_dir($path)) $this->cleanupPackage($path);
			if(basename($path) == "index.xml") unlink($path);
		}
	}
};
?>