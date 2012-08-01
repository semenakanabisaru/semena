<?php
	abstract class social_network extends umiObjectProxy {
		
		private static $list = array();

		final public static function getList() {
			if (empty( self::$list )) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('social_networks', 'network');
				self::$list = $sel->result;
			}
			return self::$list;
		}


		final public static function get($objectId) {
			if(empty($objectId)) return false;
			
			if($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);
				
				if($object instanceof iUmiObject == false) {
					throw new coreException("Couldn't load network #{$objectId}");
				}
			}

			$classPrefix = $object->social_id;

			objectProxyHelper::includeClass('social_networks/classes/networks/', $classPrefix);
			$className = $classPrefix . '_social_network';
			
			return new $className($object, $classPrefix);
		}
		
		public static function getByCodeName($code) { 

			$sel = new selector('objects');
			$sel->types('object-type')->name('social_networks','network');
			$sel->where('social_id')->equals($code);
		
			return self::get($sel->first); 
		}
		
		
		protected $network_name;

		
		public function __construct(iUmiObject $object, $network_name) {
			parent::__construct($object);
			$this->network_name = $network_name;
		
		}
		
		
		public function getCodeName() { 
			return $this->network_name;
		}		
		
		public function __toString() {
			return $this->network_name;
		}
	};
?>