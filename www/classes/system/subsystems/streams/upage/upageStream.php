<?php
	class upageStream extends umiBaseStream {
		protected $scheme = "upage", $prop_name = NULL;

		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}

			$element_id = $this->parsePath($path);
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if($element instanceof iUmiHierarchyElement) {
				if(is_null($this->prop_name)) {
					$showEmptyFlag = translatorWrapper::$showEmptyFields;
					if(!is_null( getArrayKey($this->params, 'show-empty') )) {
						translatorWrapper::$showEmptyFields = true;
					}
					
					$data = $this->translateToXml($element);
					
					 translatorWrapper::$showEmptyFields = $showEmptyFlag;
				} else {
					$prop = $element->getObject()->getPropByName($this->prop_name);
					if($prop instanceof iUmiObjectProperty) {
						$data = $this->translateToXml($element, $prop);
					} else {
						return $this->setDataError('not-found');
					}
				}
				
				if($this->expire) $cacheFrontend->saveData($path, $data, $this->expire);
                return $this->setData($data);
			} else {
				return $this->setDataError('not-found');
			}
		}
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);

			$path = trim($path, "( )");

			if(($pos = strrpos($path, ".")) !== false && strpos($path, "/", $pos) === false) {
				$prop_name = substr($path, $pos + 1);
				$path = substr($path, 0, $pos);

				$this->prop_name = $prop_name;
			} else {
				$this->prop_name = NULL;
			}
			
			if(is_numeric($path)) {
				if( (string) (int) $path == $path) {
					return (int) $path;
				}
			}

			return umiHierarchy::getInstance()->getIdByPath($path);
		}
		
		protected function translateToXml() {
			$args = func_get_args();
			$element = $args[0];
			if (isset($args[1])) {
				$property = $args[1];
			}
			else {
				$property = NULL;
			}
						
			$request = is_null($property) ? array("full:page" => $element) : array('property' => $property);
			return parent::translateToXml($request);
		}
	};
?>