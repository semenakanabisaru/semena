<?php
	class uobjectStream extends umiBaseStream {
		protected $scheme = "uobject", $prop_name = NULL;


		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}

			$object_id = $this->parsePath($path);
			$object = umiObjectsCollection::getInstance()->getObject($object_id);
			
			if($object instanceof iUmiObject) {
				if(is_null($this->prop_name)) {
					$showEmptyFlag = translatorWrapper::$showEmptyFields;
					if(!is_null( getArrayKey($this->params, 'show-empty') )) {
						translatorWrapper::$showEmptyFields = true;
					}
					
					$data = $this->translateToXml($object);
					
					translatorWrapper::$showEmptyFields = $showEmptyFlag;
				} else {
					$prop = $object->getPropByName($this->prop_name);
					if($prop instanceof iUmiObjectProperty) {
						$data = $this->translateToXml($object, $prop);
					} else {
						return $this->setDataError('not-found');
					}
				}

				if($this->expire) $cacheFrontend->saveObject($path, $data, $this->expire);
				return $this->setData($data);
			} else {
				return $this->setDataError('not-found');
			}
		}
		
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			
			if(strpos($path, ".") !== false) {
				list($path, $prop_name) = explode(".", $path);
				$this->prop_name = $prop_name;
			} else {
				$this->prop_name = NULL;
			}

			return (int) $path;
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$object = $args[0];

			if (isset($args[1])) {
				$property = $args[1];
			}
			else {
				$property = NULL;
			}

			if(is_null($property)) {
				$request = array("full:object" => $object);
			} else {
				$request = array('property' => $property);
			}
			return parent::translateToXml($request);
		}
	};
?>