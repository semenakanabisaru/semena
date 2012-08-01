<?php
	abstract class __emarket_currency {
		public function applyPriceCurrency($price = 0, $template = 'default') {
			list($tpl_block) = def_module::loadTemplates("emarket/{$template}", 'price_block');

			$price = $this->parsePriceTpl($template, $this->formatCurrencyPrice(array('actual' => $price)));

			$result = array('price' => $price);

			$result['void:price-original'] = getArrayKey($result['price'], 'original');
			$result['void:price-actual'] = getArrayKey($result['price'], 'actual');

			return def_module::parseTemplate($tpl_block, $result);
		}

		/**
			* Вывести список с выбором предпочитаемой валюты пользователя
			* @param String $template = 'default' шаблон макроса (только tpl-версия)
			* @return Mixed
		*/
		public function currencySelector($template = 'default') {
			list(
				$tpl_block, $tpl_item, $tpl_item_a
			) = def_module::loadTemplates("emarket/currency/{$template}",
				'currency_block', 'currency_item', 'currency_item_a'
			);

			$defaultCurrency = $this->getDefaultCurrency();
			$currentCurrency = $this->getCurrentCurrency();
			$items_arr = array();
			foreach($this->getCurrencyList() as $currency) {
				$item_arr = array(
					'attribute:id'			=> $currency->id,
					'attribute:name'		=> $currency->name,
					'attribute:codename'	=> $currency->codename,
					'attribute:rate'	=> $currency->rate,
					'xlink:href'			=> $currency->xlink,
				);

				if($currency->codename == $defaultCurrency->codename) {
					$item_arr['attribute:default'] = 'default';
				}

				$tpl = ($currentCurrency->id == $currency->id) ? $tpl_item_a : $tpl_item;
				$items_arr[] = def_module::parseTemplate($tpl, $item_arr, false, $currency->id);
			}

			$block_arr = array(
				'subnodes:items' => $items_arr
			);

			return def_module::parseTemplate($tpl_block, $block_arr);
		}


		public function selectCurrency() {
			$currencyCode = getRequest('currency-codename');
			$selectedCurrency = $this->getCurrency($currencyCode);

			if($currencyCode && $selectedCurrency) {
				$defaultCurrency = $this->getDefaultCurrency();

				if(permissionsCollection::getInstance()->isAuth()){
					$customer = customer::get();
					if($customer->preffered_currency != $selectedCurrency->id) {
						if($selectedCurrency->id == $defaultCurrency->id) {
							$customer->preffered_currency = null;
						} else {
							$customer->preffered_currency = $selectedCurrency->id;
						}
						$customer->commit();
					}
				} else {
					setcookie('customer_currency', $selectedCurrency->id, (time() + customer::$defaultExpiration), '/');
				}
			}

			if($redirectUri = getRequest('redirect-uri')) {
				$this->redirect($redirectUri);
			} else {
				$this->redirect(getServer('HTTP_REFERER'));
			}
		}


		/**
			*
			* @param array $prices
			* @param iUmiObject $currency = null
			* @param iUmiObject $defaultCurrency = null
			* @return array
		*/
		public function formatCurrencyPrices($prices, iUmiObject $defaultCurrency = null) {
			$currencyIds = $this->getCurrencyList();
			$result = array();

			foreach($currencyIds as $currency) {
				$info = $this->formatCurrencyPrice($prices, $currency, $defaultCurrency);
				if(is_array($info)) {
					$result[] = $info;
				}
			}
			return array('nodes:price' => $result);
		}

		public function parseCurrencyPricesTpl($template = 'default', $pricesData = array(), iUmiObject $currentCurrency = null) {
			list($tpl_block, $tpl_item) = def_module::loadTemplates("emarket/currency/{$template}", 'currency_prices_block', 'currency_prices_item');

			if(is_null($currentCurrency)) {
				$currentCurrency = $this->getCurrentCurrency();
			}

			$block_arr = array();
			$currencyIds = $this->getCurrencyList();
			foreach($currencyIds as $currency) {
				if($currentCurrency->id == $currency->id) continue;

				if($info = $this->formatCurrencyPrice($pricesData, $currency, $currentCurrency)) {
					if(!$info['original']) $info['original'] = $info['actual'];
					$info['price-original'] = $info['original'];
					$info['price-actual'] = $info['actual'];
					$items_arr[] = def_module::parseTemplate($tpl_item, $info);
				}
			}
			$block_arr['subnodes:items'] = $items_arr;
			return def_module::parseTemplate($tpl_block, $block_arr);
		}


		/**
			* Пересчитать цены в массиве $prices в валюту $currency
			* @param array $prices
			* @param iUmiObject $currency = null
			* @param iUmiObject $defaultCurrency = null
			* @return array
		*/
		public function formatCurrencyPrice($prices, iUmiObject $currency = null, iUmiObject $defaultCurrency = null) {
			if(is_null($defaultCurrency)) {
				$defaultCurrency = $this->getDefaultCurrency();
			}
			$currentCurrency = $this->getCurrentCurrency();

			if(is_null($currency)) {
				$currency = $currentCurrency;
			} else {
				if(($currency->getId() == $currentCurrency->id) && ($defaultCurrency == $this->getDefaultCurrency())) {
					return $prices;
				}
			}

			$result = array(
				'attribute:name'		=> $currency->name,
				'attribute:rate'		=> $currency->rate,
				'attribute:nominal'		=> $currency->nominal,
				'void:currency_name'	=> $currency->name
			);

			if($currency->prefix) $result['attribute:prefix'] = $currency->prefix; else $result['void:prefix'] = false;
			if($currency->suffix) $result['attribute:suffix'] = $currency->suffix; else $result['void:suffix'] = false;

			foreach($prices as $key => $price) {
				if($price == null) {
					$result[$key] = null;
					continue;
				}

				$price = $price * $defaultCurrency->nominal * $defaultCurrency->rate;
				$price = $price  / $currency->rate / $currency->nominal;
				$result[$key] = round($price, 2);
			}

			return $result;
		}


		/**
			* Получить валюту по умолчанию
			* @return iUmiObject валюта по умолчанию
		*/
		public function getDefaultCurrency() {
			static $currency = null;
			if(!is_null($currency)) {
				return $currency;
			}

			$config = mainConfiguration::getInstance();
			$defaultCode = $config->get('system', 'default-currency');

			if(!$defaultCode) {
				throw new coreException("Default currency is not defined (system.default-currency)");
			}

			return $currency = $this->getCurrency($defaultCode);
		}

		/**
			* Получить текущую валюту
			* @return iUmiObject текущая валюта
		*/
		public function getCurrentCurrency() {
			static $currency = null;
			if(!is_null($currency)) {
				return $currency;
			}

			  if (permissionsCollection::getInstance()->isAuth()){
			  	  $customer = customer::get();
              	  if ($customer->preffered_currency){
              	  	  $currencyTypeId = umiObjectTypesCollection::getInstance()->getBaseType('emarket', 'currency');
              	  	  $currency = selector::get('object')->id($customer->preffered_currency);
              	  	  if($currency->typeId == $currencyTypeId) return $currency;
              	  }
              } else {
              	  if($v = (int) getCookie('customer_currency')) return $currency = selector::get('object')->id($v);
			}

              	  $guest = umiObjectsCollection::getInstance()->getObjectByGUID('system-guest');
              	  if ($v = $guest->getValue('preffered_currency')) return $currency = selector::get('object')->id($v);

            return $currency = $this->getDefaultCurrency();
		}


		/**
			* Получить валюту с кодом currencycode
			* @return iUmiObject текущая валюта
		*/
		public function getCurrency($codeName) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'currency');
			$sel->where('codename')->equals($codeName);

			if($sel->first) {
				return $sel->first;
			} else throw new privateException("Currency \"{$codeName}\" not found");
		}


		/**
			* Получить список всех валют
			* @return array массив id валют
		*/
		public function getCurrencyList() {

			static $currencyList = null;
			if(!is_null($currencyList)) {
				return $currencyList;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'currency');
			return $currencyList = $sel->result;
		}
	};
?>