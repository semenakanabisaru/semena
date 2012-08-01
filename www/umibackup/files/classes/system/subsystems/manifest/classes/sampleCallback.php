<?php
	class sampleManifestCallback implements iManifestCallback {
	
		public function onBeforeTransactionExecute(iTransaction $transaction) {
			$this->log("Запуск транзакции \"" . $transaction->getTitle() . "\"");
		}
		
		public function onAfterTransactionExecute(iTransaction $transaction) {}
		
		public function onBeforeActionExecute(iAtomicAction $action) {
			$this->log("Выполняется действие \"" . $action->getTitle() . "\"");
		}
		
		public function onAfterActionExecute(iAtomicAction $action) {}
		
		public function onBeforeExecute() {
			$this->log("Запуск манифеста");
		}
		
		public function onAfterExecute() {
			$this->log("Завершение манифеста");
		}
		
		public function onBeforeRollback() {
			$this->log("Начинаем откат изменений", true);
		}
		
		public function onAfterRollback() {}
		
		public function onException(Exception $e) {
			$this->log("Возникло исключение", true);
		}
		
		protected function log($msg, $isError = false) {
			if($isError) {
				$msg = "<font color=\"red\">{$msg}</font>";
			}
			echo $msg, "\n";
		}
		
		public function onBeforeActionRollback(iAtomicAction $action) {
			$this->log("Откатывается действие \"" . $action->getTitle() . "\"", true);
		}
		
		public function onAfterActionRollback(iAtomicAction $action) {}
	};
?>