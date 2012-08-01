<?php
	class utypeStream extends umiBaseStream {
		protected $scheme = "utype", $group_name = NULL, $field_name = NULL;


		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}

			$type_id = $this->parsePath($path);
			$collection = umiObjectTypesCollection::getInstance();
			if(is_array($type_id)) {
				$types = array();
				foreach($type_id as $id) {
					$types[] = $collection->getType($id);
				}
			} else {
				$types = $collection->getType($type_id);
			}

			if(($types instanceof iUmiObjectType) || is_array($types)) {
				$data = $this->translateToXml($types);
				if($this->expire) $cacheFrontend->saveData($path, $data, $this->expire);
				return $this->setData($data);
			} else {
				return $this->setDataError('not-found');
			}
		}

		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$arr = explode("/", $path);
			if(sizeof($arr) >= 2) {
				switch($arr[0]){
					case "dominant" : {
						$hierarchy = umiHierarchy::getInstance();
						return $hierarchy->getDominantTypeId( $this->getTypeId($arr[1]) );
					}
					case "child" : {
						$collection = umiObjectTypesCollection::getInstance();
						return $collection->getChildClasses( $this->getTypeId($arr[1]) );
					}
				}
			}

			$arr = explode(".", $path);
			if(is_array($arr)) {
				$path = trim($arr[0], '/');

				if(sizeof($arr) > 1) {
					$this->group_name = $arr[1];
				}

				if(sizeof($arr) > 2) {
					$this->field_name = $arr[2];
				}
			}

			return $path;
		}

		private function getTypeId($typeString) {
			if((string)((int) $typeString) == $typeString) {
				return (int) $typeString;
			} else {
				list($module, $method) = explode("::", $typeString);
				return umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
			}
		}


		protected function translateToXml() {
			$args = func_get_args();
			$type = $args[0];

			switch(false) {
				case is_null($this->field_name): {
					$field_id = $type->getFieldId($this->field_name);
					$field = umiFieldsCollection::getInstance()->getField($field_id);
					$request = Array("full:field" => $field);
					break;
				}

				case is_null($this->group_name): {
					$group = $type->getFieldsGroupByName($this->group_name);
					$request = Array("full:group" => $group);
					break;
				}

				case !is_array($type): {
					$request = array();
					$request = array("nodes:type" => $type);
					break;
				}

				default: {
					$request = Array("full:type" => $type);
					break;
				}
			}

			return parent::translateToXml($request);
		}
	};
?>