<?php
	class umiObjectPropertyWrapper extends translatorWrapper {
		public static $showEmptyFields = false;

		public function translate($data) {
			return $this->translateData($data);
		}

		protected function translateData(iUmiObjectProperty $property) {
			$resultArray = array();

			$value = $property->getValue();
			$field = $property->getField();

			$fieldId = $field->getId();
			$fieldName = $field->getName();
			$fieldTitle = $field->getTitle();
			$fieldType = $field->getFieldType();
			$fieldDataType = $fieldType->getDataType();

			if(($fieldDataType == "password" && !xmlTranslator::$showUnsecureFields) || (in_array($fieldName, array('user_dock', 'user_settings_data')) && !xmlTranslator::$showUnsecureFields)) {
				return false;
			}

			$has_value = (is_array($value) && !empty($value)) || (!is_array($value) && strlen($value));

			if($has_value || self::$showEmptyFields || translatorWrapper::$showEmptyFields) {
				$resultArray['@id'] = $fieldId;
				$resultArray['@name'] = $fieldName;
				$resultArray['@type'] = $fieldDataType;

				if($fieldDataType == 'relation' && $field->getFieldType()->getIsMultiple()) {
					$resultArray['@multiple'] = 'multiple';
				}

				$resultArray['title'] = $fieldTitle;
				if ($fieldDataType == 'price') {

					$eshopCur = regedit::getInstance()->getVal("//modules/eshop/default_currency_code");
					if ($eshopCur) {
						$currencyId = umiBasket::getInstance()->getCurrencyIdBySId($eshopCur);
						if ($currencyId) {
							$currency = umiObjectsCollection::getInstance()->getObject($currencyId);
							if ($currency) {
								$resultArray['currency'] = array(
									'@id' => $currencyId,
									'@code' => $currency->getValue('eshop_currency_letter_code'),
									'@symbol' => $currency->getValue('eshop_currency_symbol'),
									'@rate' => $currency->getValue('eshop_currency_exchange_rate')
								);
								$resultArray['curSymb'] = $currency->getValue("eshop_currency_symbol"); //For previous versions
							}
						}
					}
				}

				$resultArray['value'] = array();

				switch($fieldDataType) {
					case "symlink": {
						$resultArray['value']['nodes:page'] = array();
						foreach($value as $element) {
							$resultArray['value']['nodes:page'][] = $element;
						}
						break;
					}


					case "relation": {
						$objects = umiObjectsCollection::getInstance();
						$resultArray['value']['nodes:item'] = array();

						if(is_array($value)) {
							foreach($value as $objectId) {
								$resultArray['value']['nodes:item'][] = $objects->getObject($objectId);
							}
						} else {
							$resultArray['value']['item'] = $objects->getObject($value);
						}
						break;
					}

					case "date": {
						if($value instanceof iUmiDate) {
							$magicMethods = array('get_editable_region', 'save_editable_region');
							$cmsController = cmsController::getInstance();
							$format = (in_array($cmsController->getCurrentMethod (), $magicMethods)) ? false : 'r';

							$resultArray['value']['@unix-timestamp'] = $value->getFormattedDate("U");
							$resultArray['value']['#rfc'] = $value->getDateTimeStamp() > 0 ? $value->getFormattedDate($format) : "";
						}

						break;
					}

					case "optioned": {
						$options = array();
						$hierarchy = umiHierarchy::getInstance();
						$objects = umiObjectsCollection::getInstance();

						foreach($value as $val) {
							$optionInfo = array();

							foreach($val as $type => $sval) {
								switch ($type) {
									case "tree": {
										$element = $hierarchy->getElement($sval);
										if($element instanceof iUmiHierarchyElement) {
											$optionInfo['page'] = $element;
										}
										break;
									}

									case "rel": {
										$object = $objects->getObject($sval);
										if($object instanceof iUmiObject) {
											$optionInfo['object'] = $object;
										}
										break;
									}

									default:
										$optionInfo['@' . $type] = $sval;
								}
							}


							$options[] = $optionInfo;
						}
						$resultArray['value']['nodes:option'] = $options;
						break;
					}

					case "price":
						$resultArray['value']['xlink:href'] = 'udata://emarket/price/' . $property->getObjectId();
					default: {
						if(is_array($value)) {
							unset($resultArray['value']);
							$resultArray['nodes:value'] = $value;
							if($fieldDataType == 'tags') {
								$resultArray['combined'] = implode(', ', $value);;
							}
						} else {
							$cmsController = cmsController::getInstance();
							$value = system_parse_short_calls($value, false, $property->getObjectId());
							$value = xmlTranslator::executeMacroses($value);

							if(defined("XML_PROP_VALUE_MODE") && $fieldDataType == "wysiwyg" && $cmsController->getCurrentMode() != "admin") {
								if(XML_PROP_VALUE_MODE == "XML") {
									$resultArray['value'] = Array("xml:xvalue" => "<xvalue>" . $value . "</xvalue>");
									break;
								}
							}

							if($value && in_array($fieldDataType, array('img_file', 'swf_file', 'file'))) {
								$value = $value->getFilePath(true);
								$info = getPathInfo($value);

								$regexp = "|^".CURRENT_WORKING_DIR."|";
								$value  = preg_replace($regexp, "", $value);

								$resultArray['value']['@path'] = '.' . $value;
								$resultArray['value']['@folder'] = preg_replace($regexp, "", $info['dirname']);
								$resultArray['value']['@name'] = $info['filename'];
								$resultArray['value']['@ext'] = $info['extension'];

								if(in_array($fieldDataType, array('img_file', 'swf_file'))) {
									$arr = getimagesize('.' . $value);
									if(is_array($arr)) {
										$resultArray['value']['@width'] = $arr[0];
										$resultArray['value']['@height'] = $arr[1];
									}
								}
							}

							$resultArray['value']['#value'] = $value;
						}
						break;
					}
				}
			}
			return $resultArray;
		}
	};
?>