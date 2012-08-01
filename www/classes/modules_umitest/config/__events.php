<?php
	abstract class __events_config {
		
		public function runGarbageCollector(iUmiEventPoint $e) {
			if($e->getMode() == "process") {
				$gc = new garbageCollector;
				$gc->run();
			}
		}
	};
?>