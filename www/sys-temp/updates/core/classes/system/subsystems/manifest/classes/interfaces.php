<?php
	/**
		* Правило выполнения транзакций и действий
	*/
	interface iManifest {
		public function __construct(iBaseXmlConfig $config);
		public function setCallback(iManifestCallback $callback);
		
		public function execute();
		public function rollback();
	};

	/**
		* Транзакция, состоящая из списка атомарных действий
	*/
	interface iTransaction {
		public function __construct($name, manifest $manifest);
		public function setCallback(iManifestCallback $callback);
		
		public function execute();
		public function rollback();
		
		public function addAtomicAction(iAtomicAction $action);
		
		public function setTitle($title);
		public function getTitle();
		
		public function getName();
	};
	
	
	/**
		* Атомарное обратимое действие в рамках одной транзакции.
	*/
	interface iAtomicAction {
		public function __construct($name, transaction $transaction);
	
		public function setParams($params);
		public function execute();
		public function rollback();
		
		public function getName();
		public function setTitle($title);
		public function getTitle();
		
		public function setEnviroment($enviroment);
		
		public static function load($name, transaction $transaction, $package = false);
		
		public function setCallback(iManifestCallback $callback);
	};
	
	/**
		* 
	*/
	
	interface iManifestCallback {
		public function onBeforeTransactionExecute(iTransaction $transaction);
		public function onAfterTransactionExecute(iTransaction $transaction);
		
		public function onBeforeActionExecute(iAtomicAction $action);
		public function onAfterActionExecute(iAtomicAction $action);
		
		public function onBeforeExecute();
		public function onAfterExecute();
		
		public function onBeforeRollback();
		public function onAfterRollback();
		
		public function onException(Exception $e);

		public function onBeforeActionRollback(iAtomicAction $action);
		public function onAfterActionRollback(iAtomicAction $action);
	}
?>