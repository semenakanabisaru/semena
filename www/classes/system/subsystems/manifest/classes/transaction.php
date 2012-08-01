<?php
	class transaction implements iTransaction {
		protected $name, $title = "", 
			$actions = Array(), $completedActions = Array(), $callback,
			$manifest;
	
		public function __construct($name, manifest $manifest) {
			$this->name = (string) $name;
			$this->manifest = $manifest;
		}
		
		public function getName() {
			return $this->name;
		}
		
		public function setTitle($title) {
			$this->title = (string) $title;
		}
		
		public function getTitle() {
			return $this->title;
		}
		
		public function addAtomicAction(iAtomicAction $action) {
			$this->actions[] = $action;
		}
		
		public function execute() {
			$completedActions = &$this->completedActions;
			
			foreach($this->actions as $action) {
				if(in_array($action, $completedActions)) {
					continue;
				}
				
				$params = $this->getManifest()->getParams();
				foreach($params as $name => $value) {
					$action->addParam($name, $value);
				}
				
				$this->executeAction($action);
				
				$completedActions[] = $action;
				
				if($this->getManifest()->hibernationsCountLeft > 0) {
					if($action->hibernate()) {
						$action->refresh();
					}
				}
			}
		}
		
		protected function executeAction(iAtomicAction $action) {
			try {
				if($this->callback instanceof iManifestCallback) {
					$this->callback->onBeforeActionExecute($action);
					
					$action->setCallback($this->callback);
				}
				
				$action->execute();
				
				if($this->callback instanceof iManifestCallback) {
					$this->callback->onAfterActionExecute($action);
				}
			} catch(Exception $e) {
				$this->rollback();
				throw $e;
			}
		}
		
		public function rollback() {
			$completedActions = array_reverse($this->completedActions);
			
			foreach($completedActions as $action) {
				try {
					if($this->callback instanceof iManifestCallback) {
						$this->callback->onBeforeActionRollback($action);
					}
					
					$action->rollback();
					
					if($this->callback instanceof iManifestCallback) {
						$this->callback->onAfterActionRollback($action);
					}
				} catch(Exception $e) {
					$this->registerException($e);
				}
			}
		}
		
		public function setCallback(iManifestCallback $callback) {
			$this->callback = $callback;
		}
		
		public function getManifest() {
			return $this->manifest;
		}
		
		protected function registerException(Exception $e) {
			if($this->callback instanceof iManifestCallback) {
				$this->callback->onException($e);
			}
		}
	};
?>