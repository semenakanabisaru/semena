<?php
/**
	* Предоставляет доступ к корзине товаров, а так же к заказам пользователя
	* В случае авторизованного пользователя работает с соответствующим объектом "заказ в интернет магазине",
	* в случае гостя работает с сессией.
	* После авторизации "объединяет" товары из сессии с товарами, хранящимися в объекте "заказ в интернет магазине"
	* PS. В описании методов будет использован термин "Корзина", в нашем случае "Корзина" представляет собой "Заказ в интернет магазине" со статусом "В корзине"
	* Синглтон, экземпляр корзины товаров можно получить через статический метод getInstance()
*/
	class umiBasket extends singleton implements iSingleton, iUmiBasket {

		private $arrBasket = array(); // for unautorized

		private $oAuthUser = null;
		private $oUserBasket = null;
		private $arrUserOrders = array();

		public $iBasketOrderId = null;


		/**
			* Получить экземпляр корзины товаров
			* @return umiBasket экземпляр класса umiBasket
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Конструктор
		*/
		protected function addDefaultCurrency() {
			$currencyTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "currency");
			if (!$this->getCurrencyIdBySId("RUB")) {
				if ($newCurrencyId = umiObjectsCollection::getInstance()->addObject(" Российский рубль", $currencyTypeId)) {
					$newCurrency = umiObjectsCollection::getInstance()->getObject($newCurrencyId);
					$nominal = 1;
					$newCurrency->setName("Российский рубль");
					$newCurrency->setValue("eshop_currency_letter_code", "RUB");
					$newCurrency->setValue("eshop_currency_symbol", "Руб.");
					$newCurrency->setValue("eshop_currency_exchange_rate", 1);
					$newCurrency->setValue("eshop_currency_nominal", 1);
					$newCurrency->setValue("use_in_eshop", true);
					$newCurrency->commit();
				}
			}
		}

		protected function __construct() {
			// init
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			$this->pre_lang = $oUsersMdl->pre_lang;
			// get orders from user profile
			self::addDefaultCurrency();
			if (!regedit::getInstance()->getVal("//modules/eshop/default_currency_code")) {
				regedit::getInstance()->setVal("//modules/eshop/default_currency_code", "RUB");
			}

			if (!$sessionCurrency = getSession("eshop_currency")) {
				if ($defaultCurrencyCode = regedit::getInstance()->getVal("//modules/eshop/default_currency_code")) {
					if ($defaultCurrencyId = $this->getCurrencyIdBySId($defaultCurrencyCode)) {
						if ($currencyFullInfo = $this->getCurrencyFullInfo($defaultCurrencyId)) {
							$_SESSION['eshop_currency'] = $currencyFullInfo;
							$this->currencyInfo = $currencyFullInfo;
						} else {
							$this->currencyInfo = $this->getCurrencyFullInfo(false);
						}

					}
				}
			} else {
				$this->currencyInfo = getSession("eshop_currency");
			}
			if ($oUsersMdl && $oUsersMdl->is_auth()) {
				$iUserId = cmsController::getInstance()->getModule("users")->user_id;
				$oAuthUser = umiObjectsCollection::getInstance()->getObject($iUserId);
				if ($oAuthUser instanceof umiObject) {
					$this->oAuthUser = $oAuthUser;

					$arrUserOrders = $oAuthUser->getValue("orders_refs");
					sort($arrUserOrders, SORT_NUMERIC);
					$this->arrUserOrders = array_reverse($arrUserOrders);
					$this->oUserBasket = null;
					$this->oUserBasket = $this->getUserBasket();


					$this->iBasketOrderId = $this->oUserBasket->getId();

				}
				if ($userCurrencyId = $oAuthUser->getValue("preffered_currency")) {
					if ($userCurrencyInfo = $this->getCurrencyFullInfo($userCurrencyId)) {
						$sessionCurrency = getSession("eshop_currency");
						if ($sessionCurrency) {
							$currency = umiObjectsCollection::getInstance()->getObject($sessionCurrency['ID']);
							if ($currency instanceof umiObject) {
								$isActive = $currency->getValue("use_in_eshop");
								if (!$isActive) {
									$defCur = regedit::getInstance()->getVal("//modules/eshop/default_currency_code");
									$currencyId = $this->getCurrencyIdBySId($defCur);
									if ($currencyId) {
										if ($defCurInfo = $this->getCurrencyFullInfo($currencyId)) {
											$_SESSION["eshop_currency"] = $defCurInfo;	
										}
									}
								}
							}
						} else {
							$_SESSION['eshop_currency'] = $userCurrencyInfo;
							$this->currencyInfo = $userCurrencyInfo;
						}	
					}
				}

				// split session data with order
				if (isset($_SESSION['umi_basket']) && is_array($_SESSION['umi_basket'])) {
				
					foreach ($_SESSION['umi_basket'] as $iElementId => $arrInfo) {
						$iCount = isset($arrInfo['count'])? (int) $arrInfo['count'] : 1;
						$this->addToBasket($iElementId, $iCount, false);
						usleep(100);
					}
					$this->recalcBasket();


					unset($_SESSION['umi_basket']);
					session_commit();
					$this->oUserBasket->update();
				}

			} else {
				if (isset($_SESSION['umi_basket']) && is_array($_SESSION['umi_basket'])) {
					$this->arrBasket = $_SESSION['umi_basket'];
				}
			}
			if (!isset($this->currencyInfo)) {
				$this->currencyInfo = Array(
					'ID'	=> "",
					'name'	=> "",
					'char_code' => "",
					'num_code'	=> "",
					'exchange' => 1,
					'symbol'	=> ""
				);
			}
		}

		/**
			* Возвращает объект, представляющий собой заказ со статусом "в корзине"
			* Если объект не существует, создает его, связывает с текущим пользователем и возвращает
			* @return umiObject объект "заказ в интернет магазине" со статусом "в корзине"
		*/
		public function getUserBasket() {
			if ($this->oUserBasket instanceof umiObject) return $this->oUserBasket;

			$oResult = null;
			
			for ($iI = 0; $iI < count($this->arrUserOrders); $iI++) {
				$oNextOrder = umiObjectsCollection::getInstance()->getObject($this->arrUserOrders[$iI]);
				if ($oNextOrder instanceof umiObject) {
					$iStatusId = $oNextOrder->getValue("status");
					$oStatus = umiObjectsCollection::getInstance()->getObject($iStatusId);
					if ($oStatus instanceof umiObject) {
						$sId = $oStatus->getValue("id_name");
						if ($sId == "cart") {
							$oResult = $oNextOrder;
							break;
						}
					}
				}
			}

			if (!$oResult instanceof umiObject) {
				// create new cart
				$iTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "order");
				$iOrderId = umiObjectsCollection::getInstance()->addObject("tmp", $iTypeId);

				$oOrder = umiObjectsCollection::getInstance()->getObject($iOrderId);
				if ($oOrder instanceof umiObject) {
					$iStatusId = $this->getStatusBySId("cart");
					if ($iStatusId !== false) {
						$oOrder->setValue("status", $iStatusId);
						$oOrder->setValue("order_time", new umiDate());

						$this->arrUserOrders[] = $iOrderId;
						$this->oAuthUser->setValue("orders_refs", $this->arrUserOrders);
						$this->oAuthUser->commit();
					}
					$oOrder->setName("#" . $iOrderId);
					$ownerId = $oOrder->getOwnerId();
					if($ownerId) {
					    $oOrder->setValue("user_id", $oOrder->getOwnerId());
					}
					$oOrder->commit();
				}

				$oResult = $oOrder;
			}

			return $oResult;
		}


		/**
			* Возвращает id объекта "Статус заказа" по его строковому идентификатору (id_name)
			* @return Integer id объекта (umiObject), либо false, если статуса с таким id_name не существует
		*/
		public static function getStatusBySId($sId) {
			$iTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "order_status");

			$oSel = new umiSelection;

			$oSel->setObjectTypeFilter();
			$oSel->addObjectType($iTypeId);

			$arrResult = umiSelectionsParser::runSelection($oSel);

			foreach($arrResult as $iStatusId) {
				$oNextStatus = umiObjectsCollection::getInstance()->getObject($iStatusId);
				if ($oNextStatus instanceof umiObject && $oNextStatus->getValue("id_name") == $sId) {
					return $iStatusId;
				} else {
					umiObjectsCollection::getInstance()->unloadObject($iStatusId);
				}
			}

			return false;
		}

		public function getCurrencyFullInfo($currencyId) {
			if ($currency = umiObjectsCollection::getInstance()->getObject($currencyId)) {
				$res = Array(
					'ID'	=> $currency->getId(),
					'name'	=> $currency->getName(),
					'char_code' => $currency->getValue("eshop_currency_letter_code"),
					'num_code'	=> $currency->getValue("eshop_currency_digit_code"),
					'exchange' => $currency->getValue("eshop_currency_exchange_rate"),
					'symbol'	=> $currency->getValue("eshop_currency_symbol"),
					'used'		=> $currency->getValue("use_in_eshop")
				);
				return $res;
			} else {
				return $this->currencyInfo;
			}
		}

		/**
			* Пересичтывает цены в корзине заказов и возвращает общую сумму заказа
			* @return Float общая сумма заказа
		*/
		public function recalcBasket() {
			$fResult = 0;
			if ($this->oUserBasket instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue('items');
				foreach ($arrBasketItems as $iItemId) {
					$oItem = umiObjectsCollection::getInstance()->getObject($iItemId);
					if ($oItem instanceof umiObject) {
						$iCount = (int) $oItem->getValue("count");

						// get item price and discount
						$iElementId = $oItem->getValue("catalog_relation");
						$oElement = umiHierarchy::getInstance()->getElement($iElementId, true, true);

						$fDiscountProc = 0;

						if ($oElement instanceof umiHierarchyElement) {
							$fPrice = (float) $oElement->getValue("price");
							$mode = cmsController::getInstance()->getCurrentMode();
							if(!$oElement->getValue("ignore_discounts") && $mode != "admin") {
								$fDiscountProc = cmsController::getInstance()->getModule("eshop")->getElementDiscountSize($iElementId);
							}
							// clear memory
							umiHierarchy::getInstance()->unloadElement($iElementId);
						} else {
							$fPrice = (float) $oItem->getValue("price_item");
						}
						$fPriceTotal = $fPrice * $iCount;
						$fResult += $fPriceTotal;

						$oItem->setValue("price_item", $fPrice);
						$oItem->setValue("price_total",  $fPriceTotal);
						$oItem->setValue("discount_size", $fDiscountProc);
						if ($curId = $this->getCurrencyIdBySId($this->currencyInfo['char_code'])) {
							$priceCurrency = $this->recalcCurrency($fPrice, $this->currencyInfo['char_code']);
							$priceTotalCurrency = $this->recalcCurrency($fPriceTotal, $this->currencyInfo['char_code']);
							$oItem->setValue("eshop_order_item_currency_price", $priceCurrency);
							$oItem->setValue("eshop_order_item_currency_price-total", $priceTotalCurrency);
						}
						$oItem->commit();
						//
						umiObjectsCollection::getInstance()->unloadObject($iItemId);
					}
				}
				if ($curId = $this->getCurrencyIdBySId($this->currencyInfo['char_code'])) {
					$fResultCurrency = $this->recalcCurrency($fResult,$this->currencyInfo['char_code']);
					$this->oUserBasket->setValue("eshop_order_currency", $curId);
					$this->oUserBasket->setValue("currency_exchange_rate", umiObjectsCollection::getInstance()->getObject($curId)->getValue("eshop_currency_exchange_rate"));
					$this->oUserBasket->setValue("eshop_order_currency_total", $fResultCurrency);
					$this->oUserBasket->commit();
				}
				$this->oUserBasket->setValue("order_price", $fResult);
				if (isset($this->currencyInfo['ID'])) {
					if ($user = umiObjectsCollection::getInstance()->getObject($this->oUserBasket->getOwnerId())) {
						$user->setValue("preffered_currency", $this->currencyInfo['ID']);
						$user->commit();
					}
				}
			} else {
				foreach ($this->arrBasket as $iElementId => $arrElementInfo) {
					$this->arrBasket[$iElementId]['price_total'] = $arrElementInfo['count'] * (float) $arrElementInfo['price_item'];
					$fResult += $this->arrBasket[$iElementId]['price_total'];//isset($arrElementInfo['price_total']) ? $arrElementInfo['price_total'] : 0.0;
					$total_discount = cmsController::getInstance()->getModule("eshop")->getGlobalDiscount($fResult);
				}
			}
			//Calculate global discount
			$regedit = regedit::getInstance();
			$total_discount = 0;
			if ($regedit) {
				if (!$regedit->getVal("//modules/eshop/disable_global_discount")) {
					$total_discount = cmsController::getInstance()->getModule("eshop")->getGlobalDiscount($fResult);
				}
			}

			if ($this->oUserBasket instanceof umiObject){
				$this->oUserBasket->setValue("global-discount-size", $fResult*$total_discount/100);
				$this->oUserBasket->setValue("eshop_global_discount_proc", $total_discount);
				$this->oUserBasket->commit();
				$this->globalDiscount = $fResult*$total_discount/100;
				$fResult = $fResult-($fResult*$total_discount)/100;
			}else{
				//$this->arrBasket['global-discount-size'] = $total_discount;
				$this->globalDiscount = $fResult*$total_discount/100;
				$fResult = $fResult-($fResult*$total_discount)/100;
			}

			return $fResult;
		}

		public function getCurrencyIdBySId($stringId) {
			$currencyTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "currency");

			$currencyType = umiObjectTypesCollection::getInstance()->getType($currencyTypeId);
			if ($currencyType) {
				$fieldCharCode = $currencyType->getFieldId('eshop_currency_letter_code');
				$sel = new umiSelection();
				$sel->addObjectType($currencyTypeId);
				$sel->addPropertyFilterEqual($fieldCharCode, $stringId);
				$result = umiSelectionsParser::runSelection($sel);
				if (!$result) {
					return false;
				}
				return $result[0];
			}
		}

		/**
			* Изменяет информацию о товаре в корзине
			* После изменения информации о товаре, пересчитывает все цены в корзине
			* @param Integer $iElementId - id товара (umiHierarchyElement)
			* @param Array $arrItemInfo - массив с новой информацией о товаре в корзине
		*/
		public function changeBasketItem($iElementId, $arrItemInfo) {
			$iElementId = (int) $iElementId;
			$iCount = isset($arrItemInfo['count'])? (int) $arrItemInfo['count'] : 1;
			if ($this->oUserBasket instanceof umiObject) {
				$oBasketItem = $this->getBasketItem($iElementId);
				if ($oBasketItem instanceof umiObject) {
					$oBasketItem->setValue("count", $iCount);
					$oBasketItem->commit();
				}
			} elseif (isset($this->arrBasket[$iElementId])) {
				$this->arrBasket[$iElementId]['count'] = $iCount;
			}

			$this->recalcBasket();
		}

		/**
			* Получить количество товара в корзине
			* @param Integer $iElementId - id товара (umiHierarchyElement)
			* @return Integer количество товара
		*/
		public function getBasketItemCount($iElementId) {
			$iResult = 1;
			$iElementId = (int) $iElementId;

			if ($this->oUserBasket instanceof umiObject) {
				$oBasketItem = $this->getBasketItem($iElementId);
				if ($oBasketItem instanceof umiObject) {
					$iResult = $oBasketItem->getValue("count");
				}
			} elseif (isset($this->arrBasket[$iElementId])) {
				$iResult = $this->arrBasket[$iElementId]['count'];
			}
			return $iResult;
		}

		/**
			* Получить информацию о товаре в корзине по идентификатору товара (umiHierarchyElement)
			* @param Integer $iElementId - id товара (umiHierarchyElement)
			* @return umiObject - объект "товар в заказе", либо Array - массив с информацией о товаре в корзине, если пользователь "Гость", либо NULL, в случае если товара нет в корзине
		*/
		public function getBasketItem($iElementOrObjectId) {
			if ($this->oUserBasket instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue('items');
				foreach ($arrBasketItems as $iItemId) {
					$oItem = umiObjectsCollection::getInstance()->getObject($iItemId);
					if ($iItemId == $iElementOrObjectId && !$oItem->getValue("catalog_relation")) {
						return $oItem;
					}
					if ($oItem instanceof umiObject) {
						if ($oItem->getValue("catalog_relation") == $iElementOrObjectId) {
							return $oItem;
						}
					}
					umiObjectsCollection::getInstance()->unloadObject($iItemId);
				}
			} elseif (isset($this->arrBasket[$iElementOrObjectId])) {
				return $this->arrBasket[$iElementOrObjectId];
			}
			return null;
		}

		public function setItemPropertyValue($element_id, $field_name, $value) {
			$element = $this->getBasketItem($element_id);
			if($element instanceof umiHierarchyElement) {
				$res = $element->setValue($field_name, $value);
				$element->commit();
				return $res;
			} else {
				return false;
			}
		}

		/**
			* Добавляет объект "наименование в заказе" в корзину.
			* Служит для добавления любых, не связанных с реальным товаром позиций
			* Работает только для авторизованных пользователей
			* @param umiObject $oBasketItem - Объект "наименование в заказе"
		*/
		public function addItemToUserBasket($oBasketItem) {
			if ($this->oUserBasket instanceof umiObject && $oBasketItem instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue('items');
				$arrBasketItems[] = $oBasketItem->getId();
				$this->oUserBasket->setValue("items", $arrBasketItems);
				$this->oUserBasket->commit();
			}
		}

		/**
			* Добавляет товар в корзину
			* После добавления пересчитываются все цены в корзине
			* @param Integer $iElementId - id товара (umiHierarchyElement)
			* @param Integer количество товара, по умолчанию 1
		*/
		public function addToBasket($iElementId, $iCount = 1, $bRecalc =true) {
			$iCount = (int) $iCount;

			// set before event point
			$oEventPoint = new umiEventPoint("eshop_add_tobasket");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("element_id", $iElementId);
			$oEventPoint->setParam("count", $iCount);
			def_module::setEventPoint($oEventPoint);

			$oElement = umiHierarchy::getInstance()->getElement($iElementId, true, true);
			if ($oElement instanceof umiHierarchyElement) {
				$sElementName = $oElement->getName();
				$fElementPrice = (float) $oElement->getValue("price");

				$iNewBasketItemId = null;
				if ($this->oUserBasket instanceof umiObject) {
					$oBasketItem = $this->getBasketItem($iElementId);
					if ($oBasketItem instanceof umiObject) {
						$iNewBasketItemId = $oBasketItem->getId();
						$oBasketItem->setValue("count", $oBasketItem->getValue("count") + $iCount);
						$oBasketItem->commit();
					} else {
						$iTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "order_item");
						$iNewItemId = umiObjectsCollection::getInstance()->addObject($sElementName, $iTypeId);
						$iNewBasketItemId = $iNewItemId;
						$oBasketItem = umiObjectsCollection::getInstance()->getObject($iNewItemId);
						if ($oBasketItem instanceof umiObject) {
							$oBasketItem->setValue("price_item", $fElementPrice);
							$oBasketItem->setValue("count", $iCount);
							$oBasketItem->setValue("catalog_relation", array($iElementId));
							$oBasketItem->commit();

							$arrBasketItems = $this->oUserBasket->getValue('items');
							$arrBasketItems[] = $oBasketItem->getId();
							$this->oUserBasket->setValue("items", $arrBasketItems);
							$this->oUserBasket->commit();
						}
					}
				} else {
					if (isset($this->arrBasket[$iElementId])) {
						$this->arrBasket[$iElementId]['count'] += $iCount;
					} else {
						$this->arrBasket[$iElementId] = array('price_item' => $fElementPrice, 'count' => $iCount);
					}
					$_SESSION['umi_basket'] = $this->arrBasket;
				}

				if ($bRecalc) {
					$this->recalcBasket();
				}

				// set after event point
				$oEventPoint = new umiEventPoint("eshop_add_tobasket");
				$oEventPoint->setMode("after");
				$oEventPoint->setParam("element_id", $iElementId);
				$oEventPoint->setParam("price_item", $fElementPrice);
				$oEventPoint->setParam("count", $iCount);
				$oEventPoint->setParam("new_basket_item_id", $iNewBasketItemId);

				def_module::setEventPoint($oEventPoint);

				// clear memory
				umiHierarchy::getInstance()->unloadElement($iElementId);
			}

		}

		/**
			* Удалить товар из корзины
			* После удаления пересчитываются все цены в корзине
			* @param Integer $iElementId - id товара (umiHierarchyElement)
		*/
		public function removeFromBasket($iElementId) {
			// set before event point
			$oEventPoint = new umiEventPoint("eshop_remove_frombasket");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("element_id", $iElementId);
			def_module::setEventPoint($oEventPoint);

			if ($this->oUserBasket instanceof umiObject) {
				$oItem = $this->getBasketItem($iElementId);
				if ($oItem instanceof umiObject) {
					umiObjectsCollection::getInstance()->delObject($oItem->getId());
					$arrItems = $this->oUserBasket->getValue('items');
					if (is_array($arrItems)) {
						$arrItems = array_diff($arrItems, array($oItem->getId()));
						$this->oUserBasket->setValue('items', $arrItems);
					}
					$this->oUserBasket->commit();
					$this->recalcBasket();
				}
			} elseif (isset($this->arrBasket[$iElementId])) {
				unset($this->arrBasket[$iElementId]);
				$this->recalcBasket();
			}
		}


		/**
			* Удалить все товары из корзины
			* Вызывает метод removeFromBasket для каждого товара
		*/
		public function clear() {
			if ($this->oUserBasket instanceof umiObject) {
				$arrItems = $this->oUserBasket->getValue('items');
				if (is_array($arrItems)) {
					foreach($arrItems as $id) {
						$item = umiObjectsCollection::getInstance()->getObject($id);
						if (!$item) continue;
						$catalog_rel = $item->getValue("catalog_relation");
						$this->removeFromBasket($catalog_rel);
					}
				}
			} elseif (count($this->arrBasket)) {
				foreach ($this->arrBasket as $catalog_rel => $tmp) {
					$this->removeFromBasket($catalog_rel);
				}
			}
		}

		private function renderBasketItem($oItemOrElement, $sBasketItemBlock, &$count = NULL, &$price = NULL) {

			$block_arr = array();
			$block_arr['node:name'] = "";
			$block_arr['attribute:count'] = 0;
			$block_arr['attribute:price'] = 0;
			$block_arr['void:price_total'] = 0;

			if ($oItemOrElement instanceof umiObject) {
				$oItem = $oItemOrElement;
				$iElementId = $oItemOrElement->getValue("catalog_relation");
				$oElement = umiHierarchy::getInstance()->getElement($iElementId, true, true);
			} elseif ($oItemOrElement instanceof umiHierarchyElement) {
				$oElement = $oItemOrElement;
				$iElementId = $oElement->getId();
			} else {
				return false;
			}

			if ($oElement instanceof umiHierarchyElement) {
				$block_arr['node:name'] = $oElement->getName();
				if ($this->oUserBasket instanceof umiObject) {
					$oItem = $this->getBasketItem($iElementId);
					if ($oItem instanceof umiObject) {
						$fPrice      = (float) $oItem->getValue("price_item");
						$fPriceTotal = (float) $oItem->getValue("price_total");
						$block_arr['attribute:count'] = (int) $oItem->getValue("count");
						$fPrice = $this->recalcCurrency($fPrice, $this->currencyInfo['exchange']);
						$block_arr['attribute:price'] = number_format($fPrice, $fPrice-floor($fPrice)>0.005 ? 2 : 0, '.', '');
						$fPriceTotal = $this->recalcCurrency($fPriceTotal, $this->currencyInfo['exchange']);
						$block_arr['attribute:price-total'] = $block_arr['void:price_total'] = number_format($fPriceTotal, $fPriceTotal-floor($fPriceTotal)>0.005 ? 2 : 0, '.', '');
						$count += (int) $oItem->getValue("count");
						$price += (float) $oItem->getValue("price_total");
					}
				} elseif (isset($this->arrBasket[$iElementId])) {
					$fPrice      = (float) $this->arrBasket[$iElementId]['price_item'];
					$fPriceTotal = (float) $this->arrBasket[$iElementId]['price_total'];
					$block_arr['attribute:count'] = (int) $this->arrBasket[$iElementId]['count'];
					$fPrice = $this->recalcCurrency($fPrice, $this->currencyInfo['exchange']);
					$fPriceTotal = $this->recalcCurrency($fPriceTotal, $this->currencyInfo['exchange']);
					$block_arr['attribute:price'] = number_format($fPrice, $fPrice-floor($fPrice)>0.005 ? 2 : 0, '.', '');
					$block_arr['attribute:price-total'] = $block_arr['void:price_total'] = number_format($fPriceTotal, $fPriceTotal-floor($fPriceTotal)>0.005 ? 2 : 0, '.', '');
					$count += (int) $this->arrBasket[$iElementId]['count'];
					$price = $this->recalcCurrency($price, $this->currencyInfo['char_code']);
					$price += (float) $this->arrBasket[$iElementId]['price_total'];
				}
				$block_arr['attribute:element-id'] = $block_arr['void:element_id'] = $iElementId;
				$block_arr['xlink:href'] = "upage://" . $iElementId;

				return def_module::parseTemplate($sBasketItemBlock, $block_arr, $iElementId);
			} elseif ($this->oUserBasket instanceof umiObject) {
				$block_arr['node:name'] = $oItem->getName();
				$fPrice      = (float) $oItem->getValue("price_item");
				$fPriceTotal = (float) $oItem->getValue("price_total");
				$block_arr['attribute:count'] = (int) $oItem->getValue("count");
				$fPrice = $this->recalcCurrency($fPrice, $this->currencyInfo['exchange']);
				$block_arr['attribute:price'] = number_format($fPrice, $fPrice-floor($fPrice)>0.005 ? 2 : 0, '.', '');
				$fPriceTotal = $this->recalcCurrency($fPriceTotal, $this->currencyInfo['exchange']);
				$block_arr['attribute:price-total'] = $block_arr['void:price_total'] = number_format($fPriceTotal, $fPriceTotal-floor($fPriceTotal)>0.005 ? 2 : 0, '.', '');
				$count += (int) $oItem->getValue("count");
				$price += (float) $oItem->getValue("price_total");
				$block_arr['attribute:element-id'] = $block_arr['void:element_id'] = $oItem->getId();
				$block_arr['xlink:href'] = "uobject://" . $oItem->getId();
				return def_module::parseTemplate($sBasketItemBlock, $block_arr, $iElementId);
			}
			return "";
		}

		/**
			* Возвращает информацию о корзине, используя шаблон
			* После удаления пересчитываются все цены в корзине
			* @param String $sTemplate - шаблон, по умолчанию default
			* @return String информация о корзине
		*/
		public function renderBasket($sTemplate = "default") {
			if(!$sTemplate) $sTemplate = "default";

			list(
				$sBasket, $sEmptyBasket, $sBasketItem, $sBasketNewItem
			) = def_module::loadTemplates("eshop/basket/{$sTemplate}",
				"basket", "basket_empty", "basket_item", "basket_new_item"
			);

			$block_arr = array();
			$fOrderPrice = $this->recalcBasket();
			$fOrderPrice = $this->recalcCurrency($fOrderPrice, $this->currencyInfo['exchange']);

			if (isset($this->globalDiscount)) {
				$globalDiscount = $this->recalcCurrency($this->globalDiscount, $this->currencyInfo['exchange']);
			}

			$block_arr['void:order_price'] = number_format($fOrderPrice, $fOrderPrice-floor($fOrderPrice)>0.005 ? 2 : 0, '.', '');
			$block_arr['subnodes:items'] = array();
			$block_arr['void:currency_symbol'] = $this->currencyInfo['symbol'];
			$block_arr['void:currency_choose'] = $this->renderCurrencyChooser();
			if (isset($globalDiscount)) {
				$block_arr['void:global_discount'] = number_format($globalDiscount, $globalDiscount-floor($globalDiscount)>0.005 ? 2 : 0, '.', '');
			} else {
				$block_arr['void:global_discount'] = 0;
			}

			$count = 0;
			$price = 0;

			if ($this->oUserBasket instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue('items');
				$arr_actual_items = array();
				foreach ($arrBasketItems as $iItemId) {
					$oNextItem = umiObjectsCollection::getInstance()->getObject($iItemId);
					if ($oNextItem instanceof umiObject) {
						$v_item = $this->renderBasketItem($oNextItem, $sBasketItem, $count, $price);
						if ($v_item !== false) {
							$block_arr['subnodes:items'][] = $v_item;
							$arr_actual_items[] = $iItemId;
						}
						umiObjectsCollection::getInstance()->unloadObject($iItemId);
					}
				}
				$this->oUserBasket->setValue("items", $arr_actual_items);
				$this->oUserBasket->commit();
			} else {
				foreach ($this->arrBasket as $iNextElementId => $arrInfo) {
					$oElement = umiHierarchy::getInstance()->getElement($iNextElementId);
					$block_arr['subnodes:items'][] = $this->renderBasketItem($oElement, $sBasketItem, $count, $price);
					umiHierarchy::getInstance()->unloadElement($iNextElementId);
				}
			}

			$block_arr['attribute:order-id'] = $block_arr['void:order_id'] = $this->iBasketOrderId;
			$block_arr['attribute:total-count'] = $block_arr['void:total_count'] = $count;

			$price = $this->recalcCurrency($price, $this->currencyInfo['exchange']);
			$block_arr['attribute:total-price'] = $block_arr['void:total_price'] = number_format($price, $price-floor($price)>0.005 ? 2 : 0, '.', '');

			$templater = cmsController::getInstance()->getCurrentTemplater();

			if (count($block_arr['subnodes:items'])) {
				if (def_module::isXSLTResultMode()) {
					$block_arr['subnodes:items'][] = $sBasketNewItem;
				}
				return def_module::parseTemplate($sBasket, $block_arr);
			} else {
				if (!def_module::isXSLTResultMode()) {
					$block_arr['subnodes:items'][] = $sBasketNewItem;
				}
				return def_module::parseTemplate($sEmptyBasket, $block_arr);
			}
		}
		/**
			* Возвращает контрол выбора валюты
			* @param String - if,kj
			* @return String HTML код контрола
		*/

		public function renderCurrencyChooser($template = "default") {
			if(!$template) $template = "default";

			list ($templateChooser) = def_module::loadTemplates("eshop/basket/".$template, "currency_choose");
			$currencyTypeId = umiObjectTypesCollection::getInstance()->getBaseType("eshop", "currency");
			$currencyType = umiObjectTypesCollection::getInstance()->getType($currencyTypeId);
			$usePropId = $currencyType->getFieldId("use_in_eshop");
			$sel = new umiSelection();
			$sel->addObjectType($currencyTypeId);
			$sel->addPropertyFilterEqual($usePropId, true);
			$result = umiSelectionsParser::runSelection($sel);
			$blockArr = Array ("subnodes:currency_items" => "");
			if (count($result) < 2) {
				return false;
			}
			foreach ($result as $currencyId) {
				$blockArr['subnodes:currency_items'].= $this->renderCurrencyItem($currencyId);
			}
			return def_module::parseTemplate($templateChooser, $blockArr);
		}

		public function renderCurrencyItem($itemId, $template = "default") {
			$currency = umiObjectsCollection::getInstance()->getObject($itemId);
			list($currencyItem) = def_module::loadTemplates("eshop/basket/".$template, "currency_item");
			$blockArr['void:currency_id'] = $currency->getId();
			$blockArr['void:currency_name'] = $currency->getName();
			$blockArr['void:currency_s_code'] = $currency->getValue("eshop_currency_letter_code");
			$blockArr['void:currency_d_code'] = $currency->getValue("eshop_currency_digit_code");
			if ($blockArr['void:currency_s_code'] == $this->currencyInfo['char_code']) {
				$blockArr['void:selected'] = "selected";
			} else {
				$blockArr['void:selected'] = "";
			}
			return def_module::parseTemplate($currencyItem, $blockArr);
		}
		/**
			* Возвращает информацию о корзине ввиде javascript кода (используется для json'a)
			* @param Integer $iRequestId - идентификатор запроса
			* @return String JavaScript код объекта с информацией о корзине
		*/

		public function render4JSON($iRequestId, $b_short = false) {
			$arrBasketItems = array();

			$iTotalCount = 0;
			if ($this->oUserBasket instanceof umiObject) {
				$arrItms = $this->oUserBasket->getValue('items');
				foreach ($arrItms as $iItemId) {
					$oNextItem = umiObjectsCollection::getInstance()->getObject($iItemId);
					if ($oNextItem instanceof umiObject) {
						$iNextElementId = $oNextItem->getValue("catalog_relation");
						if (!$iNextElementId) $iNextElementId = $iItemId;
						$arrBasketItems[$iNextElementId] = array();
						$arrBasketItems[$iNextElementId]["count"] = $oNextItem->getValue("count");
						$arrBasketItems[$iNextElementId]["price_item"] = (float) $oNextItem->getValue("price_item");
						$arrBasketItems[$iNextElementId]["price_total"] = (float) $oNextItem->getValue("price_total");
						$iTotalCount += $arrBasketItems[$iNextElementId]["count"];
						//
						umiObjectsCollection::getInstance()->unloadObject($iItemId);
					}
				}
			} else {
				$arrBasketItems = $this->arrBasket;
			}

			$iTotalCount = 0;

			$sBasketItems = "var basket_items = new Array();\r\n";

			foreach ($arrBasketItems as $iElementId => $arrItemInfo) {
				if ($b_short) {
					$iTotalCount += $arrItemInfo['count'];
				} else {
					$oElement = umiHierarchy::getInstance()->getElement($iElementId, true, true);
					if ($oElement instanceof umiHierarchyElement) {
						$iCount = isset($arrItemInfo['count']) ? (int) $arrItemInfo['count'] : 1;
						$fTotalItemPrice = isset($arrItemInfo['count']) ? (float) $arrItemInfo['price_total'] : 0;
						$fTotalItemPrice = (float) $this->recalcCurrency($fTotalItemPrice, $this->currencyInfo['exchange']);
						$fTotalItemPrice = number_format($fTotalItemPrice, $fTotalItemPrice-floor($fTotalItemPrice)>0.005 ? 2 : 0, '.', '');
						$priceItem = (float) $this->recalcCurrency($arrItemInfo["price_item"], $this->currencyInfo['exchange']);
						$fPriceItem = number_format($priceItem, $priceItem-floor($priceItem)>0.005 ? 2 : 0, '.', '');
						$sElementPath = umiHierarchy::getInstance()->getPathById($iElementId);
						$sBasketItems .= <<<END
							basket_items[basket_items.length] = {
								'id'			: '{$iElementId}',
								'name'			: '{$oElement->getName()}',
								'count'			: '{$iCount}',
								'price'			: '{$fPriceItem}',
								'price_total'	: '{$fTotalItemPrice}',
								'element_path'	: '{$sElementPath}'
							};
END;
						$iTotalCount += $arrItemInfo['count'];
						//
						umiHierarchy::getInstance()->unloadElement($iElementId);
					} elseif ($this->oUserBasket instanceof umiObject) {
						$oItem = umiObjectsCollection::getInstance()->getObject($iElementId);
						$iCount = isset($arrItemInfo['count']) ? (int) $arrItemInfo['count'] : 1;
						$fTotalItemPrice = isset($arrItemInfo['count']) ? (float) $arrItemInfo['price_total'] : 0;
						$fTotalItemPrice = (float) $this->recalcCurrency($fTotalItemPrice, $this->currencyInfo['exchange']);
						$fTotalItemPrice = number_format($fTotalItemPrice, $fTotalItemPrice-floor($fTotalItemPrice)>0.005 ? 2 : 0, '.', '');
						$priceItem = (float) $this->recalcCurrency($arrItemInfo["price_item"], $this->currencyInfo['exchange']); // !!! v
						$priceItem = $arrItemInfo["price_item"]; // !!! ^
						$fPriceItem = number_format($priceItem, $priceItem-floor($priceItem)>0.005 ? 2 : 0, '.', '');
						$sElementPath = umiHierarchy::getInstance()->getPathById($iElementId);
						$sBasketItems .= <<<END
							basket_items[basket_items.length] = {
								'id'			: '{$iElementId}',
								'name'			: '{$oItem->getName()}',
								'count'			: '{$iCount}',
								'price'			: '{$fPriceItem}',
								'price_total'	: '{$fTotalItemPrice}',
								'element_path'	: '#'
							};
END;
						$iTotalCount += $arrItemInfo['count'];
						//
						umiObjectsCollection::getInstance()->unloadObject($iElementId);
					}
				}
			}

			$fOrderTotal = $this->recalcCurrency($this->recalcBasket(), $this->currencyInfo['exchange']);
			$fOrderTotal = number_format($fOrderTotal, $fOrderTotal-floor($fOrderTotal)>0.005 ? 2 : 0, '.', '');
			if (isset($this->globalDiscount)) {
				$globalDiscount = $this->recalcCurrency($this->globalDiscount, $this->currencyInfo['exchange']);
				if (isset($globalDiscount)) {
					$globalDiscount = number_format($globalDiscount, $globalDiscount-floor($globalDiscount)>0.005 ? 2 : 0, '.', '');
				} else {
					$globalDiscount = 0;
				}
			} else {
				$globalDiscount = 0;
			}

			$sResult = <<<END
				var response = new lLibResponse({$iRequestId});
				{$sBasketItems}
				response.basket_items = basket_items;
				response.order_total = '{$fOrderTotal}';
				response.total_count = '{$iTotalCount}';
				response.global_discount = '{$globalDiscount}';
				lLib.getInstance().makeResponse(response);
END;
			return $sResult;
		}

		/**
			* Возвращает информацию о корзине для формирования письма о заказе, используя шаблон
			* @param String $sTemplate - шаблон, по умолчанию default
			* @return String информация о корзине
		*/
		public function render4Mail($sTemplate = "default") {
			if(!$sTemplate) $sTemplate = "default";

			list($sOrderItems, $sOrderItem) = def_module::loadTemplates("eshop/".$sTemplate, "order_items", "order_item");

			$block_arr = array();

			$block_arr['order_price'] = $this->recalcBasket();
			$block_arr['items'] = "";
			$block_arr['order_id'] = $this->oUserBasket->getId();
			if ($this->oUserBasket instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue('items');
				foreach ($arrBasketItems as $iItemId) {
					$oNextItem = umiObjectsCollection::getInstance()->getObject($iItemId);
					if ($oNextItem instanceof umiObject) {
						$block_arr['items'] .= $this->renderBasketItem($oNextItem, $sOrderItem);
						//
						umiObjectsCollection::getInstance()->unloadObject($iItemId);
					}
				}
			}

			return def_module::parseTemplate($sOrderItems, $block_arr, false, $block_arr['order_id']);
		}

		/**
			* Возвращает информацию обо всех заказах пользователя (история заказов), используя шаблон
			* @param String $sTemplate - шаблон, по умолчанию default
			* @return String информация о заказах
		*/
		public function renderUserOrders($sTemplate = "default") {
			if(!$sTemplate) $sTemplate = "default";

			if (!$this->oAuthUser) return "";

			list(
				$sOrdersBlock, $sOrder, $sOrdersEmpty, $sBlockCancelBtn, $sBlockCancelLocked
			) = def_module::loadTemplates("eshop/orders/".$sTemplate,
				"orders_block", "order_line", "orders_empty", "cancel_button", "cancel_locked"
			);
			$block_arr = array();
			$block_arr['void:lines'] = array();
			$iOrdersCnt = 0;
			for ($iI = 0; $iI < count($this->arrUserOrders); $iI++) {
				$iNextOrderId = $this->arrUserOrders[$iI];
				$oNextOrder = umiObjectsCollection::getInstance()->getObject($iNextOrderId);
				if ($oNextOrder instanceof umiObject) {
					$iStatusId = $oNextOrder->getValue("status");
					$oStatus = umiObjectscollection::getInstance()->getObject($iStatusId);
					if ($oStatus instanceof umiObject) {
						$sStatusSId = $oStatus->getValue("id_name");
						if ($sStatusSId === "cart" || $sStatusSId === "canceled") continue;

						$oOrderTime = $oNextOrder->getValue("order_time");
						$sOrderTime = "";
						if ($oOrderTime instanceof umiDate) {
							$sOrderTime = $oOrderTime->getFormattedDate();
						}
						$order_block_arr = array();
						$order_block_arr['attribute:id'] = $iNextOrderId;
						$order_block_arr['attribute:order-time'] = $oOrderTime->getFormattedDate("U");
						$order_block_arr['void:order_id'] = $iNextOrderId;
						$order_block_arr['status'] = $oStatus->getName();
						$order_block_arr['void:order_time'] = $sOrderTime;

						if ($orderCurrencyId = $oNextOrder->getValue("eshop_order_currency")) {
							$currency = umiObjectsCollection::getInstance()->getObject($orderCurrencyId);
								if ($currency && $currencySymbol = $currency->getValue("eshop_currency_symbol")) {
									$order_block_arr['currency_symbol'] = $currencySymbol;
								} else {
									$order_block_arr['currency_symbol'] = "";
								}

						}
						if ($orderCurrencyExchangeRate = $oNextOrder->getValue("currency_exchange_rate")) {
							$newPrice = $oNextOrder->getValue("order_price")/$orderCurrencyExchangeRate;
							$order_block_arr['order_price'] = number_format($newPrice, $newPrice-floor($newPrice)>0.005 ? 2 : 0, '.', '');
						} else {
							$order_block_arr['order_price'] = $oNextOrder->getValue("order_price");
						}

						$bCancelLocked = (bool) $oStatus->getValue('lock_cancel');

						$order_block_arr['void:cancel_button'] = "";

						if (!$bCancelLocked) {
							$order_block_arr['cancel_link'] = $this->pre_lang . "/eshop/cancel_order/{$iNextOrderId}/";
							$order_block_arr['void:cancel_button'] = def_module::parseTemplate($sBlockCancelBtn, array("cancel_link" => $order_block_arr['cancel_link']));
						} else {
							$order_block_arr['void:cancel_button'] = def_module::parseTemplate($sBlockCancelLocked, array());
						}

						$order_block_arr['void:info_link'] = $this->pre_lang . "/eshop/user_order_info/{$iNextOrderId}/".$sTemplate;
						$order_block_arr['xlink:href'] = "uobject://".$iNextOrderId;

						$iOrdersCnt++;
						$block_arr['void:lines'][] = def_module::parseTemplate($sOrder, $order_block_arr, false, $iNextOrderId);
					}
					//
					umiObjectsCollection::getInstance()->unloadObject($iNextOrderId);
				}
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'];

			if ($iOrdersCnt) {
				return def_module::parseTemplate($sOrdersBlock, $block_arr);
			} else {
				return def_module::parseTemplate($sOrdersEmpty, $block_arr);
			}
		}

		/**
			* Возвращает информацию об определенном заказе пользователя, используя шаблон
			* @param Integer идентификатор заказа
			* @param String $sTemplate - шаблон, по умолчанию default
			* @return String информация о заказе
		*/
		public function renderOrderInfo($iOrderId, $sTemplate = "default") {
			if (!$sTemplate) $sTemplate = "default";
			$oOrder = umiObjectsCollection::getInstance()->getObject($iOrderId);
			if (in_array((string) $iOrderId, $this->arrUserOrders) && $oOrder instanceof umiObject) {
				list(
					$sOrderInfoBlock, $sOrderInfoLine, $sOrderCanceled, $sBlockCancelBtn, $sBlockCancelLocked, $sPaymentBlock, $sPaymentLocked
				) = def_module::loadTemplates("eshop/orders/".$sTemplate,
					"order_info_block", "order_info_line" ,"order_canceled", "cancel_button", "cancel_locked", "order_payment_block", "order_payment_locked"
				);

				$block_arr = array();

				$iStatusId = $oOrder->getValue("status");
				$oStatus = umiObjectscollection::getInstance()->getObject($iStatusId);
				$sStatusSId = $oStatus->getValue("id_name");
				if ($sStatusSId === "canceled" || $sStatusSId === "cart") {
					return def_module::parseTemplate($sOrderCanceled, $block_arr, false, $iOrderId);
				}
				if ($orderCurrencyId = $oOrder->getValue("eshop_order_currency")) {
					$currency = umiObjectsCollection::getInstance()->getObject($orderCurrencyId);
						if ($currency && $currencySymbol = $currency->getValue("eshop_currency_symbol")) {
							$block_arr['currency_symbol'] = $currencySymbol;
						} else {
							$block_arr['currency_symbol'] = $currencySymbol;
						}
				}
				$block_arr['status'] = $oStatus->getName();
				$block_arr['id'] = $iOrderId;
				$block_arr['order_id'] = $iOrderId;
				$block_arr['items'] = "";
				if ($exchangeRate = $oOrder->getValue("currency_exchange_rate")) {
					$newPrice = $oOrder->getValue('order_price')/$exchangeRate;
					$block_arr['order_price'] = number_format($newPrice, $newPrice-floor($newPrice)>0.005 ? 2 : 0, '.', '');
				} else {
					$block_arr['order_price'] = $oOrder->getValue('order_price');
				}

				$block_arr['cancel_button'] = "";
				$bCacelLocked = (bool) $oStatus->getValue('lock_cancel');
				if (!$bCacelLocked) {
					$block_arr['cancel_link'] = $this->pre_lang . "/eshop/cancel_order/{$iOrderId}/";
					$block_arr['cancel_button'] = def_module::parseTemplate($sBlockCancelBtn, array("cancel_link" => $block_arr['cancel_link']));
				} else {
					$block_arr['cancel_button'] = def_module::parseTemplate($sBlockCancelLocked);
				}

				$arrOrderItems = $oOrder->getValue("items");
				for ($iI = 0; $iI < count($arrOrderItems); $iI++) {
					$iNextItemId = $arrOrderItems[$iI];
					$oItem = umiObjectsCollection::getInstance()->getObject($iNextItemId);
					if ($oItem instanceof umiObject) {
						$line_arr = array();
						$line_arr['element_id'] = $oItem->getValue("catalog_relation");
						$line_arr['name'] = $oItem->getName();
						$line_arr['count'] = (int) $oItem->getValue("count");
						if ($exchangeRate = $oOrder->getValue("currency_exchange_rate")) {
							$newPrice = (float) $oItem->getValue("price_item")/$exchangeRate;
							$newPriceTotal = (float) $oItem->getValue("price_total")/$exchangeRate;
							$line_arr['price'] = number_format($newPrice, $newPrice-floor($newPrice)>0.005 ? 2 : 0, '.', '');;
							$line_arr['price_total'] = number_format($newPriceTotal, $newPriceTotal-floor($newPriceTotal)>0.005 ? 2 : 0, '.', '');;
						} else {
							$line_arr['price'] = (float) $oItem->getValue("price_item");
							$line_arr['price_total'] = (float) $oItem->getValue("price_total");
						}


						$block_arr['items'] .= def_module::parseTemplate($sOrderInfoLine, $line_arr, $line_arr['element_id']);
						//
						umiObjectsCollection::getInstance()->unloadObject($iNextItemId);
					}
				}

				return def_module::parseTemplate($sOrderInfoBlock, $block_arr, false, $iOrderId);
			}
		}


		/**
			* Проверяет пуста ли корзина
			* @return Boolean true, если корзина пуста
		*/
		public function checkIsEmpty() {
			$bResult = true;
			if ($this->oUserBasket instanceof umiObject) {
				$arrBasketItems = $this->oUserBasket->getValue("items");
				if (count($arrBasketItems)) $bResult = false;
			} elseif (count($this->arrBasket)) {
				$bResult = false;
			}
			return $bResult;
		}

		/**
			* Оформляет заказ
			* После оформления заказа объект занова инициализируется, создается новый заказ со статусом "в корзине"
			* @param Integer $iDeliveryAddressId - идетификатор адреса доставки
			* @param String $sCustomerComments - комментарии к заказу
			* @param String $iOrderStatus - идентификатор статуса, который будет у заказа после оформления. По умолчанию "ожидает проверки" (wait)
		*/
		public function order($iDeliveryAddressId = false, $sCustomerComments = "", $iOrderStatus = false) {
			if ($this->oUserBasket instanceof umiObject && !$this->checkIsEmpty()) {
				$oUserBasket = $this->oUserBasket;

				$objectsCollection = umiObjectsCollection::getInstance();

				if (!$iOrderStatus) $iOrderStatus = $this->getStatusBySId('wait');
				$oUserBasket->setValue("status", $iOrderStatus);
				$oUserBasket->setValue('order_time', new umiDate());
				$oUserBasket->setValue('order_price', (float) $this->recalcBasket());
				$oUserBasket->setValue('customer_comments', $sCustomerComments);

				if ($iDeliveryAddressId) {
					$iAddrId = $objectsCollection->cloneObject($iDeliveryAddressId);

					$oDeliveryAddress = $objectsCollection->getObject($iAddrId);

					if ($oDeliveryAddress instanceof umiObject) {
						$oUserBasket->setValue("delivery_address", $oDeliveryAddress->getId());
					}
				}

				$oUserBasket->commit();
				$this->oUserBasket = null;
				self::__construct();
			}
		}

		/**
			* Пересчитать цену $basePriceValue из базовой валюты в $toCurrencyCode
			* @param Float $basePriceValue цена в базовой валюте
			* @param String $toCurrencyCode = false строковой код валюты конвертации
			* @return Float конвертированная цена
		*/
		public function recalcCurrency($basePriceValue, $toCurrencyCode = false) {
			if (!$baseCurrency = regedit::getInstance()->getVal("//modules/eshop/default_currency_code")) {
				return $basePriceValue;
			}
			if (!$baseCurrencyId = $this->getCurrencyIdBySId($baseCurrency)) {
				return $basePriceValue;
			}
			if ($newCurrency = umiObjectsCollection::getInstance()->getObject($this->currencyInfo['ID'])) {
				$baseCurrency = umiObjectsCollection::getInstance()->getObject($baseCurrencyId);
				if ($exchangeRate = $newCurrency->getValue("eshop_currency_exchange_rate")) {
					if(is_object($baseCurrency) == false) {
						return $basePriceValue;
					}
					if ($baseCurrency->getValue("eshop_currency_letter_code") != $toCurrencyCode ) {
						return $basePriceValue/$exchangeRate;
					} else {
						return $basePriceValue;
					}
				}
			}


		}

		public function __destruct() {
			if ($this->oUserBasket instanceof umiObject) {
				/*
				$this->oAuthUser->setValue("orders_refs", $this->arrUserOrders);
				$this->oAuthUser->commit();
				*/
			} else {
				$_SESSION['umi_basket'] = $this->arrBasket;
			}
		}

	}

?>