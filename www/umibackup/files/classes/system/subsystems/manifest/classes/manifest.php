<?php
	class manifest implements iManifest {
		public $hibernationsCountLeft = -1;
		protected $config, $callback, $transactions = Array(), $completed_transactions = Array(),
		$enviroment, $includedActions = Array(), $externalParams = Array();
		
		public function __construct(iBaseXmlConfig $config) {
			$this->config = $config;
			$this->readConfig();
		}
		
		public function setCallback(iManifestCallback $callback) {
			$this->callback = $callback;
		}
		
		public function execute() {
			if($this->callback instanceof iManifestCallback) {
				$this->callback->onBeforeExecute();
			}
			
			$completed_transactions = &$this->completed_transactions;
			
			foreach($this->transactions as $transaction) {
				$this->executeTransaction($transaction);
			}
			
			
			if($this->callback instanceof iManifestCallback) {
				$this->callback->onAfterExecute();
			}
			
			$this->hibernationsCountLeft = -1;
		}
		
		public function rollback() {
			if($this->callback instanceof iManifestCallback) {
					$this->callback->onBeforeRollback();
			}
			
			$completed_transactions = array_reverse($this->completed_transactions);
			
			foreach($completed_transactions as $transaction) {
				$transaction->rollback();
			}
			
			if($this->callback instanceof iManifestCallback) {
					$this->callback->onAfterRollback();
			}
		}
		
		public function hibernate() {
			$name = $this->config->getName();
			
			$data = serialize($this);
			$_SESSION['manifests'][$name]['manifest'] = $data;
			
			if(isset($_SESSION['manifests'][$name]['manifest_content'])) {
				$content = $_SESSION['manifests'][$name]['manifest_content'];
			} else {
				$content = "";
			}
			
			$new_content = ob_get_contents();
			$new_content = explode("\n", $new_content);
			foreach($new_content as $str) {
			    $str = trim($str);
			    if(!$str) continue;
			    if(strstr($content, $str) === false) {
			        $content .= $str . "\n";
			    }
			}
			$content = str_replace("\n\n", "\n", $content);
			@ob_clean();
			$_SESSION['manifests'][$name]['manifest_content'] = $content;
			
			return true;
		}
		
		public function getName() {
			return $this->config->getName();
		}
		
		public function result($clean = false) {
			$name = $this->getName();
			
			if(isset($_SESSION['manifests'][$name]['manifest_content'])) {
				$content = $_SESSION['manifests'][$name]['manifest_content'];
				unset($_SESSION['manifests'][$name]);
				
				$content .= ob_get_contents();
				@ob_clean();
				
				return $content;
			}
		}
		
		public function addParam($name, $value) {
			$this->externalParams[$name] = $value;
		}
		
		public function getParams() {
			return $this->externalParams;
		}
		
		public static function unhibernate($name) {
			if(isset($_SESSION['manifests'][$name])) {
				foreach($_SESSION['manifests'][$name]['manifest_included_actions'] as $actionInfo) {
					list($actionName, $package) = $actionInfo;
					include_once atomicAction::getSourceFilePath($actionName, $package);
				}
				
				$manifest = $_SESSION['manifests'][$name]['manifest'];
				$manifest = unserialize($manifest);
				$manifest->execute();
				
				return $manifest->result(true);
			}
		}
		
		protected function executeTransaction(iTransaction $transaction) {
			try {
				if($this->callback instanceof iManifestCallback) {
					$this->callback->onBeforeTransactionExecute($transaction);
					$transaction->setCallback($this->callback);
				}
				
				$transaction->execute();
				
				if($this->callback instanceof iManifestCallback) {
					$this->callback->onAfterTransactionExecute($transaction);
				}
			} catch(Exception $e) {
				if($this->callback instanceof iManifestCallback) {
					$this->callback->onException($e);
				}
				throw $e;
			}
		}
		
		protected function readConfig() {
			$this->loadEnviromentSettings();
			$this->loadTransactions();
		}
		
		protected function loadEnviromentSettings() {
			$config = $this->config;
			
			$temporaryDirPath = CURRENT_WORKING_DIR . '/' . $config->getValue("/manifest/enviroment/temporary-directory/@path");
			$backupDirPath = CURRENT_WORKING_DIR . '/' . $config->getValue("/manifest/enviroment/backup-directory/@path");
			$loggerDirPath = CURRENT_WORKING_DIR . '/' . $config->getValue("/manifest/enviroment/logger-directory/@path");
			
			$this->enviroment = Array(
				"temporary-directory-path" => $temporaryDirPath,
				"backup-directory-path" => $backupDirPath,
				"logger-directory-path" => $loggerDirPath
			);
		}
		
		protected function loadTransactions() {
			$config = $this->config;
			
			$pattern = Array('name' => '@name', 'title' => '/title');
			$transactions = $config->getList("//transaction", $pattern);

			foreach($transactions as $info) {
				$name = $info['name'];
				$title = $info['title'];
				
				$transaction = new transaction($name, $this);
				$transaction->setTitle($title);
				$this->loadActions($transaction);
				
				$this->transactions[] = $transaction;
			}
		}
		
		protected function loadActions(iTransaction $transaction) {
			$config = $this->config;
			$transactionName = $transaction->getName();
			
			$pattern = Array('name' => '@name', 'title' => '/title', 'params' => '+params', 'manifest' => '@manifest', 'package' => '@package');
			$actions = $config->getList("//transaction[@name = '{$transactionName}']/action", $pattern);
			
			foreach($actions as $action) {
				$name = $action['name'];
				$title = $action['title'];
				$params = $action['params'];
				$package = $action['package'];
				
				try {
					$action = atomicAction::load($name, $transaction, $package);
					$this->addIncludedAction($name, $package);
					
					if($action instanceof iAtomicAction) {
						$action->setTitle($title);
						$action->setParams($params);
						$action->setEnviroment($this->enviroment);
						
						$transaction->addAtomicAction($action);
					}
				} catch(Exception $e) {
					if($this->callback instanceof iManifestCallback) {
						$this->callback->onException($e);
					}
					throw $e;	//TODO: Errors handling here
				}
			}
		}
		
		protected function addIncludedAction($name, $package) {
			$this->includedActions[] = Array($name, $package);
			$_SESSION['manifests'][$this->getName()]['manifest_included_actions'] = $this->includedActions;
		}
	};
?>