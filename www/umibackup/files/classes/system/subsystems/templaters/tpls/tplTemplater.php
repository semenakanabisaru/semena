<?php

	class tplTemplater extends templater implements iTemplater {
		protected
			$result,
			$file_path,
			$folder_path;

		protected function __construct() {
		}

			public static function getInstance() {
				return singleton::getInstance(__CLASS__);
			}

		public function init() {
			$cmsController = cmsController::getInstance();
			$templateContent = str_replace("%pid%", $cmsController->getCurrentElementId(), file_get_contents($this->file_path));
			$result = str_replace("%catched_errors%", macros_catched_errors(), $this->parseInput($templateContent));
			$this->result = $this->cleanUpResult($result);
			$this->loadLangs();


			$this->cacheMacroses["%content%"] = $this->parseInput(cmsController::getInstance()->parsedContent);

			$res = $this->putLangs($this->result);

			$this->output = system_parse_short_calls($res);
			$cmsController->parsedContent = macros_content();
		}

		public function parseResult() {
			return $this->result;
		}

		public function loadTemplates($file_path, $c, $args) {
			$filepath = $this->folder_path . $file_path . ".tpl";
			if(!file_exists($filepath)) {
				$file_path_arr = explode("/", $file_path);
				$template = array_pop($file_path_arr);
				$file_path_arr[] = 'default';
				$file_path = implode("/", $file_path_arr);
				$filepath = $this->folder_path . $file_path . ".tpl";
				if(!file_exists($filepath)) {
					throw new publicException("Неверный путь к шаблону {$filepath}", 1);
				}
			}

			$filepath = getPrintableTpl($filepath);

			$currentLang = cmsController::getInstance()->getCurrentLang();
			if($currentLang->getId() != langsCollection::getInstance()->getDefaultLang()->getId()) {
				$langFilepath = substr($filepath, 0, strlen($filepath) - 3) . $currentLang->getPrefix() . ".tpl";
				$langFilepath = getPrintableTpl($langFilepath);

				$$langFilepath = getPrintableTpl($langFilepath);

				if(file_exists($langFilepath)) {
					$filepath = $langFilepath;
				}
			}

			if(!file_exists($filepath)) {
				throw new publicException("Невозможно подключить шаблон {$filepath}", 2);
				return false;
			}

			if(!array_key_exists($filepath, def_module::$templates_cache)) {
				include $filepath;
				def_module::$templates_cache[$filepath] = $FORMS;
			}

			$templates = def_module::$templates_cache[$filepath];

			$tpls = Array();
			for($i = 1; $i < $c; $i++) {
				$tpl = "";
				if(array_key_exists($args[$i], $templates)) {
					$tpl = $templates[$args[$i]];
				}
				$tpls[] = $tpl;
			}
			return $tpls;
		}

		public function parseTemplate($arr, $template, $parseElementPropsId, $parseObjectPropsId) {
			$meta = array();
			if(is_array($template)) {
				$meta = isset($template["#meta"]) ? $template["#meta"] : array() ;
				$template = isset($template["#template"]) ? $template["#template"] : "";
			}

			$scopeDump = (strpos($template, "%scope%") !== false);
			$scopeVariables = array("#meta" => $meta);


			if(is_array($arr)) {
				foreach($arr as $m => $v) {
					if(is_array($v) && isset($v["#template"])) $v = $v["#template"];
					$m = def_module::getRealKey($m);
					if($scopeDump) {
						$scopeVariables[$m] = $v;
					}
					if(is_array($v)) {
						$res = "";
						$v = array_values($v);
						$sz = sizeof($v);
						for($i = 0; $i < $sz; $i++) {
							$str = $v[$i];

							$listClassFirst = ($i == 0) ? "first" : "";
							$listClassLast = ($i == $sz-1) ? "last" : "";
							$listClassOdd = (($i+1) % 2 == 0) ? "odd" : "";
							$listClassEven = $listClassOdd ? "" : "even";
							$listPosition = ($i + 1);
							$listComma = $listClassLast ? '' : ', ';

							$from = Array(
								'%list-class-first%', '%list-class-last%', '%list-class-odd%', '%list-class-even%', '%list-position%',
								'%list-comma%'
							);
							$to = Array(
								$listClassFirst, $listClassLast, $listClassOdd, $listClassEven, $listPosition, $listComma
							);
							$res .= str_replace($from, $to, $str);
						}
						$v = $res;
					}

					if(!is_object($v)) {
						$template = str_replace("%" . $m . "%", $v, $template);
					}
				}
				if($scopeDump && $parseElementPropsId === false && $parseObjectPropsId === false) {
					$template = str_replace("%scope%", system_print_template_scope($scopeVariables), $template);
				}
			}

			if($parseElementPropsId !== false || $parseObjectPropsId != false) {
				if($parseElementPropsId) {
					$template = str_replace("%block-element-id%", $parseElementPropsId, $template);
				}

				if($parseObjectPropsId) {
					$template = str_replace("%block-object-id%", $parseObjectPropsId, $template);
				}

				$template = system_parse_short_calls($template, $parseElementPropsId, $parseObjectPropsId, $scopeVariables);

				$template = $this->parseInput($template);
				$template = system_parse_short_calls($template, $parseElementPropsId, $parseObjectPropsId, $scopeVariables);
			}
			return $template;
		}

		public function parseContent($arr, $template, $parseElementPropsId = false, $parseObjectPropsId = false) {
			return $this->parseTemplate($arr, $template, $parseElementPropsId, $parseObjectPropsId);
		}

	};
?>