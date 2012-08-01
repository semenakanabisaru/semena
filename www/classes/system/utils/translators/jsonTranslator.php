<?php
	class jsonTranslator {
		protected $result = "", $level = 1;
		protected	static $shortKeys = array(
			'@' => 'attribute',
			'#' => 'node',
			'+'	=> 'nodes',
			'%' => 'xlink',
			'*' => 'comment'
		);

		public function translateToJson($data) {
			$this->result = "{\n";
			$this->chooseTranslator($data);
			$this->result .= "\n}";

			if($callbackName = getRequest('json-callback')) {
				$this->result = $callbackName . "(" . $this->result . ");\n";
			} else $this->result .= "\n";
			return $this->result;
		}

		protected function chooseTranslator($data, $is_full = false) {
			switch(gettype($data)) {
				case 'array': {
					$this->translateArray($data);
					break;
				}

				case 'object': {
					$wrapper = translatorWrapper::get($data);
					$wrapper->isFull = $is_full;

					$this->result .= "{\n";
					$this->level++;
					$this->chooseTranslator($wrapper->translate($data));
					$this->level--;

					$tabs = str_repeat("\t", $this->level);
					$this->result .= "\n" . $tabs . "}";
					break;
				}

				default: {
					$this->translateBasic($data);
				}
			}
		}

		protected function translateArray($data) {
			$length = sizeof($data); $i = 0;

			foreach($data as $key => $value) {
				$subKey = $this->getSubKey($key);
				$realKey = $this->getRealKey($key);

				$q = (++$i < $length) ? ",\n" : "";
				$tabs = str_repeat("\t", $this->level);

				//Patch for value->node:value case
				if(is_array($value) && sizeof($value) == 1) {
					$key = key($value);
					if(substr($key, 0, 5) == 'node:') {
						$value = $value[$key];
					}
				}

				switch($subKey) {
					case 'void': {
						$c2 = substr($this->result, -2);
						$c3 = substr($this->result, -3);

						if($i == $length && (($c2 == ",\n" && $c = 2) || ($c3 == ",\n\n" && $c = 3))) {
							$this->result = substr($this->result, 0, strlen($this->result) - $c);
						}
						continue 2;
					}

					case 'xlink':
						$value = '/' . str_replace('://', '/', $value) . '.json';

					default: {
						if(is_array($value)) {
							if(sizeof($value) == 0) continue;

							$this->result .= "{$tabs}\"{$realKey}\": {\n";
							++$this->level;
							$this->chooseTranslator($value);
							$this->result .= "\n{$tabs}}{$q}\n";
							--$this->level;
						} else {
							$this->result .= "{$tabs}\"{$realKey}\": ";
							$this->chooseTranslator($value, ($subKey == 'full' || getRequest('viewMode') == 'full'));
							$this->result .= "{$q}";

						}
					}
				}
			}
		}

		protected function translateBasic($data) {
			if(is_numeric($data))
				if(is_float($data)) {
					$this->result .= (float) $data;
				}
				else {
					$this->result .= (int) $data;
				}
			else
				$this->result .= "\"" . umiObjectProperty::filterInputString($data) . "\"";
		}


		public function getRealKey($key) {
			$first = substr($key, 0, 1);
			if(isset(self::$shortKeys[$first])) {
				return substr($key, 1);
			}

			if($pos = strpos($key, ":")) {
				++$pos;
			} else {
				$pos = 0;
			}
			return substr($key, $pos);
		}

		public function getSubKey($key) {
			$first = substr($key, 0, 1);
			if(isset(self::$shortKeys[$first])) {
				return self::$shortKeys[$first];
			}

			if($pos = strpos($key, ":")) {
				return substr($key, 0, $pos);
			} else {
				return false;
			}
		}
	};
?>