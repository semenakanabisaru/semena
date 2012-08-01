<?php
	class socialVkontakteExporter extends umiExporter {

		protected $elements = array();
		protected $completed = false;

		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/html");
			return $buffer;
		}

		public function export($branches) {

			$exportId = getRequest('param0');
			$offset = (int) getSession("export_offset_" . $exportId);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.export.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.export.limit") : 25;

			

			if(!file_exists(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/" . $exportId . 'el')) {
				$this->prepareObjects($branches, $exportId);
			} else {
				$this->elements = unserialize(file_get_contents(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/" . $exportId . 'el'));
			}

			if(empty($this->elements)) {
				throw new publicException("No items to export");
			}
			
			if(getRequest('as_file') === '0') $blockSize = count($this->elements);

			$regedit = regedit::getInstance();
			$merchant_id = (string) $regedit->getVal('//modules/emarket/social_vkontakte_merchant_id');
			$secret = $regedit->getVal('//modules/emarket/social_vkontakte_key');

			
			if (!$merchant_id){
				throw new publicException(getLabel('error-social-config'));;
			}
			
			$sel = new selector('objects');
			$sel->types('object-type')->id(  umiObjectTypesCollection::getInstance()->getTypeIdByGUID('social_categories_vkontakte') ); 
			$result = $sel->result(	);
			
			$social_categories = array();
			foreach($result as $v) {
				$social_categories[$v->getId()] = $v->getValue("social_id");
			}
			
			$request_url = 'http://api.vkontakte.ru/merchant.php';
			
			$hierarchy = umiHierarchy::getInstance();
				
				$params = array(
				  'merchant_id' => $merchant_id,
				  'timestamp'   => time(),
				  'test_mode'   => $regedit->getVal('//modules/emarket/social_vkontakte_testmode'),
				  'method'      => 'catalog.changeItems',
				  'random'      => mt_rand(0, 1000000)
				);

				$i=1;

			for ($k = $offset; $k <= $offset + $blockSize -1; $k++) {
				if(!array_key_exists($k, $this->elements)) {
					$this->completed = true;
					break;
				}

				$elementId = $this->elements[$k];
				$element = $hierarchy->getElement($elementId, false, false);
					if(!$element instanceof umiHierarchyElement ) {
					    continue;
					}	
					
				$dsc = $element->getValue('description');
					if(empty($dsc)) {
						$dsc = getLabel('error-social-nodsc');
					}
					$dsc = templater::getInstance()->parseInput(str_replace('&#037;', '%', $dsc));
				$category = $hierarchy->getElement($element->getRel(), false, false);

				$element_social_category = $category->getValue("social_category_vkontakte") ;
					
					if(!isset($social_categories[$element_social_category])) {
						$element_social_category  = 1;
				} else {
						$element_social_category  = $social_categories[$element_social_category];
					}

				$photo = '';
				if ($element->getValue('photo') instanceof umiFile) {
					$photo = 'http://' . getServer('HTTP_HOST') . $element->getValue('photo')->getFilePath(true);
				}

					$params['item_id_'.$i] = (string) $element->getId();
					$params['item_name_'.$i] = (string) $element->getName();
					$params['item_description_'.$i] = (string) $dsc;
					$params['item_currency_'.$i] = 'RUB';
					$params['item_price_'.$i] = (string) $element->getValue('price');
				$params['item_photo_url_' . $i . '_1'] = $photo;
					$params['item_category_'.$i] = $element_social_category;
				$params['item_unavailable_' . $i] = (int) !$element->getIsActive();
				$params['item_digital_' . $i] = 0;
					$params['item_tags_'.$i] = implode ( ', ', $element->getValue('tags'));
					
					 $i++;
				}
			
				ksort($params);
				$params_sig = '';
				$params_pairs = array();
				foreach ($params as $k => $v) {
				  $params_sig .= $k.'='.$v;
				  $params_pairs[] = $k.'='.urlencode($v);
				}
				$params_sig .= $secret;
				$params_pairs[] = 'sig='.md5($params_sig);
				$params_str = implode('&', $params_pairs);

				$rc = 0;
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $request_url);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 15);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params_str);

				$result = curl_exec($ch);
				$rc = curl_errno($ch);
				
				if ($rc) {
					throw new publicException(getLabel('error-social-export'));;
				}
				
				curl_close($ch);
				
				if(strpos($result, 'error') !== false) {
					preg_match('/<error_code>(.*)<\/error_code>/iu', $result, $match);
					$code = isset($match[1]) ? $match[1]:"";
					preg_match('/<error_msg>(.*)<\/error_msg>/iu', $result, $match);
					$msg = isset($match[1]) ? $match[1]:"";
					
					return  (getLabel('error-social-export') . " (" . $code .": " .$msg . ")");
				}
			
			$_SESSION["export_offset_" . $exportId] = $offset + $blockSize;
			if ($this->completed) {
				unset($_SESSION["export_offset_" . $exportId]);
				if(file_exists(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/" . $exportId . 'el')) unlink(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/" . $exportId . 'el');
			return 'Export is done! Press Back button';
		}
		
			return 'Export is partly done! Press Back button';

		}

		private function prepareObjects($branches, $exportId) {
			foreach($branches as $branch) {
				$this->prepareObject($branch);
			}  

			$this->elements = array_unique($this->elements);
			sort($this->elements);
			file_put_contents(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/" . $exportId . 'el', serialize($this->elements));

			}
		
		private function prepareObject($branch) { 
			if(!$branch instanceof umiHierarchyElement ) {
				$branch = umiHierarchy::getInstance()->getElement( $branch, false, false );
				if(!$branch instanceof umiHierarchyElement ) return false;
				}
			$childs = umiHierarchy::getInstance()->getChilds($branch->getId(), true, true, 1);

			if (sizeof($childs)) {
				foreach ($childs as $child_id => $tmp) {
					$this->prepareObject($child_id);
				}
			}
			
			$type = umiHierarchyTypesCollection::getInstance()->getType( $branch->getTypeId() );
			if($type->getExt() == 'object' &&  $type -> getName() == 'catalog') {
				$this->elements[] = $branch->getId();
			}
		}
			
	};
?>