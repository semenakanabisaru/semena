<?php
	class stores extends umiObjectProxy {


		public static function clearPrimary($except = 0) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'store');
			
			
			$stores = $sel->result;
			
			foreach($stores as $v) {
				if($except == $v->getId()) continue;
				
				$v->setValue('primary', 0); $v->commit();
			}
			
			return true;
		}


	};
?>