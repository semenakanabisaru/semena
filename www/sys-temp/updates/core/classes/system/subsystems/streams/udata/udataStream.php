<?php
	class udataStream extends umiBaseStream {
		protected $scheme = "udata";

		public function stream_open($path, $mode, $options, $opened_path) {

			$cacheFrontend = cacheFrontend::getInstance();
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}
			
			$macros = $this->parsePath($path);
			try {
				if(!is_array($data)) {
					$data = $this->executeMacros($macros);

					if($data === false) {
						$data = array(
							'error' => array(
								'attribute:code' => 'require-more-permissions',
								'node:message' => getLabel('error-require-more-permissions')
							)
						);
					}
				}
			} catch (publicException $e) {
				$error = array();
				if($error_code = $e->getCode()) {
					$error['attribute:code'] = $error_code;
				}
				
				if($error_str_code = $e->getStrCode()) {
					$error['attribute:str-code'] = $error_str_code;
				}

				$error['node:message'] = $e->getMessage();
				$data = array("error" => $error);
			}

			if($data === false) {
				return true;
			} else {
				$data = $this->translateToXml(getArrayKey($macros, 'module'), getArrayKey($macros, 'method'), $data);
				if($this->expire) {
					$cacheFrontend->saveData($path, $data, $this->expire);
				}
				return $this->setData($data);;
			}
		}
		
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$path = trim($path, "/");
			
			$path = str_replace(")(", ") (", $path);
			$path = preg_replace("/\(([^\)]+)\)/Ue", "umiBaseStream::protectParams('\\1')", $path);

			$path_arr = explode("/", $path);

			$macros = Array();
			$params = Array();

			$sz = sizeof($path_arr);
			for($i = 0; $i < $sz; $i++) {
				$val = $this->normalizeString($path_arr[$i]);

				if($i == 0) $macros['module'] = $val;
				if($i == 1) $macros['method'] = $val;
				
				if($i > 1) {
					$params[] = umiBaseStream::unprotectParams($val);
				}
			}
			$macros['params'] = $params;

			return $macros;
		}
		
		
		protected function executeMacros($macros) {
			if($macros['module'] == "core" || $macros['module'] == "system" || $macros['module'] == "custom") {
				$module = &system_buildin_load($macros['module']);
			} else {
				$module = cmsController::getInstance()->getModule($macros['module']);
			}
			$method = isset($macros['method']) ? $macros['method'] : false;

			if($module && $method) {
				$is_allowed = false;
				if($macros['module'] != "core" && $macros['module'] != "system" && $macros['module'] != "custom") {
					$is_allowed = (bool) system_is_allowed($macros['module'], $macros['method']);
				} else {
					$is_allowed = true;
				}

				if($is_allowed) {
					$res = call_user_func_array(Array($module, $macros['method']), $macros['params']);
					return $res;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$module = $args[0];
			$method = $args[1];
			$data = $args[2];
			
			if (is_scalar($data)) {
				$data = Array("node:result" => (string) $data);
			}

			$data['attribute:module'] = $module;
			$data['attribute:method'] = $method;

			return parent::translateToXml($data);
		}
	};
?>
