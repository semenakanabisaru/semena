<?php
	abstract class baseException extends Exception {
		protected $strcode, $id;

		public static $catchedExceptions = Array();
		
		public function __construct ($message, $code = 0, $strcode = "") {
			$message = templater::putLangsStatic($message);
		
			baseException::$catchedExceptions[$this->getId()] = $this;
			$this->strcode = $strcode;
			parent::__construct($message, $code);
		}
		
		
		public function getStrCode() {
			return (string) $this->strcode;
		}
		
		public function unregister() {
			$catched = &baseException::$catchedExceptions;
			$id = $this->getId();
			
			if(isset($catched[$id])) {
				unset($catched[$id]);
			}
		}
		
		protected function getId() {
			static $id = 0;
			if(is_null($this->id))  {
				$this->id = $id++;
			}
			return $this->id;
		}
	};
?>
