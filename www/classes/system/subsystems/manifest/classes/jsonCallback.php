<?php
	class jsonManifestCallback implements iManifestCallback {
	    protected $silent = false, $buffer;
	    
	    public function __construct($silent = false) {
	    	$this->buffer = outputBuffer::current();
	        $this->silent = (bool) $silent;
	    }
	
		public function onBeforeTransactionExecute(iTransaction $transaction) {
			$this->log(getLabel('manifest-transaction-start') . " \"" . $transaction->getTitle() . "\"");
		}
		
		public function onAfterTransactionExecute(iTransaction $transaction) {}
		
		public function onBeforeActionExecute(iAtomicAction $action) {
			$this->log(getLabel('manifest-action-start') . " \"" . $action->getTitle() . "\"");
		}
		
		public function onAfterActionExecute(iAtomicAction $action) {}
		
		public function onBeforeExecute() {
			$this->log(getLabel('manifest-start'));
		}
		
		public function onAfterExecute() {
			$this->log(getLabel('manifest-finish'));
		}
		
		public function onBeforeRollback() {
			$this->log(getLabel('manifest-rollback-start'), true);
		}
		
		public function onAfterRollback() {}
		
		public function onException(Exception $e) {
			$this->log(getLabel('manifest-exception') . ": " . $e->getMessage(), true);
			$this->buffer->end();
		}
		
		protected function log($msg, $isError = false) {
		    if($this->silent && $isError == false) {
		        return false;
		    }
		    
			$from = array("'", "\n");
			$to = array("\\'", "\\n");
			$msg = str_replace($from, $to, $msg);
			
			$jsFunctionName = ($isError) ? "reportJsonError" : "reportJsonStatus";
			
			$log = <<<JS
{$jsFunctionName}('{$msg}');

JS;
			$this->buffer->push($log);
		}
		
		public function onBeforeActionRollback(iAtomicAction $action) {
			$this->log(getLabel('manifest-action-rollback') . " \"" . $action->getTitle() . "\"", true);
		}
		
		public function onAfterActionRollback(iAtomicAction $action) {}
	};
?>