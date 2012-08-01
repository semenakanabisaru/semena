<?php
class umiSimpleXML {
	/**
	* @var DOMDocument
	*/	
	private $DOM   = null;
	/**
	* @var DOMXPath
	*/
	private $XPath = null;
	/**
	* @var DOMNode
	*/
	private $Node  = null;
	/**
	* Public constructor
	* 
	*/
	public function __construct() {
		$argsNum = func_num_args();
		if($argsNum > 0) {
			$arg1 = func_get_arg(0);
			$arg2 = ($argsNum > 1) ? func_get_arg(1) : null;
			$arg3 = ($argsNum > 2) ? func_get_arg(2) : null;
			if(is_string($arg1)) {
				$this->DOM   = new DOMDocument();
				$this->DOM->loadXML($arg1);
				$this->XPath = new DOMXPath($this->DOM);
				$this->Node  = $this->DOM->firstChild;
			} else if($arg1 instanceof DOMDocument) {
				$this->DOM = $arg1;
				if($arg2 instanceof DOMNode) {
					$this->Node = $arg2;
				} else {
					$this->Node = $this->DOM->firstChild;
				}
				if($arg3 instanceof DOMXPath) {
					$this->XPath = $arg3;
				} else {
					$this->XPath = new DOMXPath($this->DOM);
				}
			} else {
				$this->DOM 	 = new DOMDocument(); 
				$this->XPath = new DOMXPath($this->DOM);
				$this->Node  = $this->DOM->firstChild;
			}
		} else {
			$this->DOM 	 = new DOMDocument(); 
			$this->XPath = new DOMXPath($this->DOM);
			$this->Node  = $this->DOM->firstChild;
		}
	}
	/**
	* Устанавливает строку с xml-документом
	* @param String $_xmlString строка с документом 
	*/
	public function loadXML($_xmlString) {
		$this->DOM->loadXML($_xmlString);
		$this->XPath = new DOMXPath($this->DOM);
		$this->Node  = $this->DOM->firstChild;
	}
	/**
	* Возвращает документ в виде Xml-строки
	* @return String 
	*/
	public function saveXML() {
		return $this->DOM->saveXML();
	}
	/**
	* Вычисляет xpath учитывая контекст
	* @param String $_path строка с XPath запросом
	* @return umiSimpleXML|array(umiSimpleXML) прокси для результирующих нодов
	*/
	public function xpath($_path, $list = false) {
		$nodes = $this->XPath->evaluate($_path, $this->Node);
		if($nodes->length) {
			$result = array();
			for($i=0; $i < $nodes->length; $i++)
				$result[] = new umiSimpleXML($this->DOM, $nodes->item($i), $this->XPath);
			return ($nodes->length == 1 && !$list) ? $result[0] : $result ;
		}
		return null;
	}
	/**
	* Считает количество элементов 
	* @param String $_path
	* @return Int
	*/
	public function count($_path) {
		return $this->XPath->evaluate($_path, $this->Node)->length;		
	}
	/**
	* Возвращает дочернюю ноду или список нодов
	* @return umiSimpleXML|array(umiSimpleXML) прокси для результирующих нодов
	*/
	public function __get($_elementName) {
		return $this->xpath($_elementName);
	}
	/**
	* Устанавливает текстовое значение ноды 
	* @param mixed $_elementName
	* @param mixed $_elementValue
	*/
	public function __set($_elementName, $_elementValue) {		
		$element = $this->DOM->createElement($_elementName);
		$this->Node->appendChild($element);		
		return $element->nodeValue = $_elementValue;
	}
	/**
	* Врзвращает имя текущей ноды
	* @return String
	*/
	public function name() {
		return $this->Node->nodeName;
	}
	/**
	* Возвращает или устанавливает значение указаного атрибута
	* @param String $attributeName Имя атрибута
	* @param String $attributeValue Значение атрибута
	* @return String значение атрибута или null если атрибута не существует
	*/
	public function attribute($_attributeName) {
		$attribute = $this->Node->attributes->getNamedItem($_attributeName);
		if(func_num_args() < 2) {
			return $attribute ? $attribute->nodeValue : null;
		} else {
			$_attributeValue = func_get_arg(1);
			if(!$attribute) {
				$attribute = $this->DOM->createAttribute($_attributeName);
				$this->Node->appendChild($attribute);
			}
			return $attribute->nodeValue = $_attributeValue;
		}
	}
	/**
	* Возвращает или устанавливает текстовое содержимое элемента
	* @param String $newValue
	* @return String 
	*/
	public function value() {
		if(func_num_args() > 0) $this->Node->nodeValue = func_get_arg(0);
		return $this->Node->nodeValue;
	}
	/**
	* Возращает текстовое содержимое элемента (при приведение к строке)
	* @return String
	*/
	public function __toString() {
		return $this->value();
	}
};
?>