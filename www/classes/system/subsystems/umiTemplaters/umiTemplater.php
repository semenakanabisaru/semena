<?php

	interface iUmiTemplater {
		public function setScope($elementId, $objectId = false);
		/**
		 * Подключает и возвращает все шаблоны из файла-источника
		 * Должен быть реализован в конкретном шаблонизаторе
		 * Использует кэширование загруженных ранее источников
		 *
		 * @param string $templatesSource - файл с шаблонами
		 * @return array - все шаблоны из источника
		 *
		 * @throws publicException - если шаблон не удалось подключить
		 */
		public static function loadTemplates($templatesSource);
		/**
		 * @static
		 * Возвращает список запрошенных шаблонов
		 * Должен быть реализован в конкретном шаблонизаторе
		 * Должен уметь принимать любое кол-во имен шаблонов и возвращать
		 * массив в виде order => шаблон, где order - порядковый номер запрашиваемого шаблона
		 *
		 * @param string $templatesSource - источник шаблонов
		 * @return array
		 */
		public static function getTemplates($templatesSource);

	}

	abstract class umiTemplater implements iUmiTemplater {
		protected $templatesSource;

		protected $scopeElementId = false, $scopeObjectId = false, $scopeObject = false;


		/**
		 * @abstract
		 * Парсит контент, используя переменные из $variables
		 *
		 * @param mixed $variables - переменные для парсинга контента
		 * @param mixed $content - контент для парсинга
		 * @return string
		 */
		abstract public function parse($variables, $content = null);


		/**
		 * Конструктор
		 * @param string $templatesSource - источник шаблонов
		 */
		public function __construct($templatesSource) {
			$config = mainConfiguration::getInstance();
			$this->templatesSource = $templatesSource;
		}

		/**
		 * Установить "область видимости" коротких макросов
		 *
		 * @param $elementId - id страницы
		 * @param $objectId - id объекта
		 */
		public function setScope($elementId, $objectId = false) {
			$this->scopeElementId = $elementId;
			$this->scopeObjectId = $objectId;
			$this->scopeObject = false;
		}

		/**
		 * Вернуть область видимости коротких макросов (контекстный umiObject)
		 * @return umiObject|null
		 */
		public function getScopeObject() {
			if ($this->scopeObject !== false) return $this->scopeObject;

			if($this->scopeElementId === false && $this->scopeObjectId === false) {
				return $this->scopeObject = null;
			}

			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();
			if ($this->scopeElementId && ($element = $hierarchy->getElement($this->scopeElementId))) {
				return $this->scopeObject = $element->getObject();
			}

			if ($this->scopeObjectId && ($object = $objects->getObject($this->scopeObjectId))) {
				return $this->scopeObject = $object;
			}

			return $this->scopeObject = null;
		}

		/**
		 * Чистит контент от мусора
		 * @param string $content
		 * @return string
		 */
		public function cleanup($content) {
			// удаляем EIP-атрибуты
			$permissions = permissionsCollection::getInstance();
			$config = mainConfiguration::getInstance();
			if (!$permissions->isAdmin() && (int) $config->get('system', 'clean-eip-attributes')) {
				$content = $this->cleanEIPAttributes($content);
			}
			// удаляем битые макросы макросы
			if (!intval($config->get("kernel", "show-broken-macro"))) {
				$content = $this->cleanBrokenMacro($content);
			}
			return $content;
		}

		/**
		 * @static
		 * Создать экземпляр шаблонизатора указанного типа
		 *
		 * @param string $type - тип шаблонизатора
		 * @param mixed $templatesSource - источник шаблонов
		 *
		 * @return umiTemplater
		 */
		public static final function create($type, $templatesSource = null) {

			$type = strtoupper($type);
			if (!strlen($type)) {
				throw new coreException("Templater type required for create instance.");
			}

			$className = __CLASS__ . $type;
			if (!class_exists($className)) {
				$filePath =  dirname(__FILE__) . '/types/' . $className . '.php';
				if (!is_file($filePath)) {
					throw new coreException("Can't load templater implemantation \"{$filePath}\".");
				}
				// @TODO: заменить прямой require
				require_once $filePath;
			}

			if (!class_exists($className)) {
				throw new coreException("Templater class \"{$className}\" not found");
			}

			$templater = new $className($templatesSource);

			if (!$templater instanceof umiTemplater) {
				throw new coreException("Templater class \"{$className}\" should be instance of " . __CLASS__);
			}
			return $templater;
		}

		/**
		 * Возвращает источник шаблонов
		 * @return string
		 */
		public function getTemplatesSource() {
			return $this->templatesSource;
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости
		 */
		public function setFilePath($filePath) {
			$this->templatesSource = $filePath;
		}


		public static $blocks = Array();

		public static function pushEditable($module, $method, $id) {
			if($module === false && $method === false) {
				if($element = umiHierarchy::getInstance()->getElement($id)) {
					$elementTypeId = $element->getTypeId();

					if($elementType = umiObjectTypesCollection::getInstance()->getType($elementTypeId)) {
						$elementHierarchyTypeId = $elementType->getHierarchyTypeId();

						if($elementHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($elementHierarchyTypeId)) {
							$module = $elementHierarchyType->getName();
							$method = $elementHierarchyType->getExt();
						} else {
							return false;
						}
					}
				}
			}

			self::$blocks[] = array($module, $method, $id);
			return true;
		}

		public static function prepareQuickEdit() {
			$toFlush = self::$blocks;

			if(sizeof($toFlush) == 0) return;

			$key = md5("http://" . getServer('HTTP_HOST') . getServer('REQUEST_URI'));
			$_SESSION[$key] = $toFlush;
		}

		final public static function getSomething($version_line = "pro", $forceHost = null) {
			$default_domain = domainsCollection::getInstance()->getDefaultDomain();
			$serverAddr = getServer('SERVER_ADDR');

			$cs2 = ($serverAddr) ? md5($serverAddr) : md5(str_replace("\\", "", getServer('DOCUMENT_ROOT')));
			$cs3 = '';

			$host = is_null($forceHost) ? $default_domain->getHost() : $forceHost;

			switch($version_line) {
				case "pro":
					$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($host))))))))));
					break;

				case "shop":
					$cs3 =  md5(md5($host . "shop"));
					break;

				case "lite":
					$cs3 = md5(md5(md5(md5(md5($host)))));
					break;

				case "start":
					$cs3 = md5(md5(md5($host)));
					break;

				case "trial": {
				$cs3 = md5(md5(md5(md5(md5(md5($host))))));
				}
			}

			$licenseKeyCode = strtoupper(substr($cs2, 0, 11) . "-" . substr($cs3, 0, 11));
			return $licenseKeyCode;
		}

		/**
		 * Удаляет EIP аттрибуты (umi:element-id и т.д.) из контента
		 *
		 * @param string $content
		 * @return string
		 */
		protected function cleanEIPAttributes($content) {
			return preg_replace('/[\s+]umi\:[^=\'"]+=["\'][^"\']*["\']/i', '', $content);
		}

		/**
		 * Удаляет неотработанные макросы из контента
		 *
		 * @param string $content
		 * @return string
		 */
		protected function cleanBrokenMacro($content) {
			$content = preg_replace("/%(?!cut%)([A-z_]{3,})%/m", "", $content);
			$content = preg_replace("/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu", "", $content);
			return $content;
		}



		/**
		 * @deprecated
		 * Оставлено для обратной совместимости,
		 * используйте метод parseTPLMacroses()
		 *
		 * @param $content
		 */
		public function putLangs($content) {
			return def_module::parseTPLMacroses($content);
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости,
		 * используйте метод parseTPLMacroses()
		 * @param string $input
		 * @param int $level
		 */
		public function parseInput($input, $level = 1) {
			return def_module::parseTPLMacroses($input);
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости,
		 * не используется
		 */
		public function init() {}

		/**
		 * @deporecated
		 * Оставлено для обратной совместимости, используйте def_module::isXSLTResultMode()
		 * @return bool
		 */
		public function getIsInited() {
			return def_module::isXSLTResultMode();
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости, используйте def_module::isXSLTResultMode()
		 * @param $new
		 * @return bool
		 */
		public function setIsInited($new) {
			return def_module::isXSLTResultMode($new);
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости, используйте parse()
		 */
		public function parseResult() {
			return $this->parse(array(), null);
		}
	}




?>