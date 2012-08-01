<?php
	class selectorHelper {
		
		static function detectFilters(selector $sel) {
			if($sel->mode == 'pages') {
				$domains = (array) getRequest('domain_id');
				foreach($domains as $domainId) {
					$sel->where('domain')->equals($domainId);
				}
				
				$langs = (array) getRequest('lang_id');
				foreach($langs as $langId) {
					$sel->where('lang')->equals($langId);
				}
			}
			
			
			if($sel->mode == 'pages' && sizeof($sel->types) && is_array(getRequest('rel'))) {
				$sel->types('hierarchy-type')->name('comments', 'comment');
			}
			
			if( isset($_REQUEST['hierarchy_types'] ) ) {
				$htypes = (array) $_REQUEST['hierarchy_types'];
				
				foreach($htypes as $v) {
					$v = explode('-',$v);
					
					if(sizeof($v)==2) 
						$sel->types('hierarchy-type')->name($v[0],$v[1]);
				}
				
			}
			
			self::detectHierarchyFilters($sel);
			self::detectWhereFilters($sel);
			self::detectOrderFilters($sel);
			
			//$sel->option('exclude-nested', true);
			
			self::checkSyncParams($sel);
		}
		
		static function checkSyncParams(selector $sel) {
			if(getRequest('export')) {
				quickCsvExporter::autoExport($sel, (bool) getRequest('force-hierarchy'));
			}
			
			if(getRequest('import')) {
				quickCsvImporter::autoImport($sel, (bool) getRequest('force-hierarchy'));
			}
		}
		
		
		static function detectHierarchyFilters(selector $sel) {
			//if(sizeof(getRequest('fields_filter'))) return;
			//if(sizeof(getRequest('order_filter'))) return;
		
			$rels = (array) getRequest('rel');
			
			if(sizeof($rels) == 0 && $sel->mode == 'pages') {				
				//$rels[] = '0';
				$sel->option('exclude-nested', true);
			}
			
			foreach($rels as $id) {
				try {
					if($id || $id === '0') $sel->where('hierarchy')->page($id)->childs(1);
					if($id === '0') $sel->option('exclude-nested', true);
				} catch (selectorException $e) {}
			}
		}
		
		static function detectWhereFilters(selector $sel) {
			static $funcs = array('eq' => 'equals', 'ne' => 'notequals', 'like' => 'like', 'gt' => 'more', 'lt' => 'less' );
			
			
			$searchAllText = (array) getRequest('search-all-text');
			//fix for guide items without fields
			if(sizeof($sel->types) == 1 && ($sel->types[0]->objectType instanceof iUmiObjectType) && sizeof($sel->types[0]->objectType->getAllFields()) == 0) {
				foreach($searchAllText as $searchString) {
					$sel->where('name')->like('%' . $searchString . '%');
				}
				return;
			} else {
				foreach($searchAllText as $searchString) {
					try {
						if($searchString !== "") $sel->where('*')->like('%' . $searchString . '%');
					} catch (selectorException $e) {}
				}
			}

			$filters = (array) getRequest('fields_filter');
			foreach($filters as $fieldName => $info) {
				if(is_array($info)) {
					//Old-style between filter
					if(isset($info[0]) && isset($info[1])) {
						try {
							$sel->where($fieldName)->between($info[0], $info[1]);
						} catch (selectorException $e) {}
					}
					
					//Try new-style filter
					foreach($info as $i => $v) {
						if(isset($funcs[$i])) {
							try {
								if($funcs[$i] == 'like') {
									$v .= '%';
								}
								
								if($v !== "") $sel->where($fieldName)->$funcs[$i]($v);
							} catch(selectorException $e) { self::tryException($e); }
						}
					}
				} else {
					//Old-style strict equals filter
					try {
						if($info !== "") $sel->where($fieldName)->equals($info);
					} catch(selectorException $e) {}
				}
			}
		}
		
		static function detectOrderFilters(selector $sel) {
			$orders = (array) getRequest('order_filter');
			foreach($orders as $fieldName => $direction) {
				$func = (strtolower($direction) == 'desc') ? 'desc' : 'asc';
				
				try {
					$sel->order($fieldName)->$func();
				} catch (selectorException $e) { self::tryException($e); }
			}
		}
		
		static private function tryException(Exception $e) {
			//if(DEBUG) throw $e;
		}
	};
?>