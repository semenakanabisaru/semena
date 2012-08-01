<?php
	class vkontakte_social_network extends social_network {

	
		public function isIframeEnabled() { 
		
			
			if(!empty($_GET['api_id'])) {
				$_SESSION['vk_iframe'] = true;
			}
	
			if(empty($_SESSION['vk_iframe'])) {
				return false;
			}
			
			return $this->getValue('is_iframe_enabled');
		}		
		
		public function isHierarchyAllowed($element_id) { 
			$element = umiHierarchy::getInstance()->getElement($element_id);
			
			if(!$element) return true;
			
			if($element->getIsDefault()) return true;
			
			

			$allowed_pages = array(
				'emarket', 'webforms', 'users', 'catalog'
			);
			
			$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());
			
			if( in_array($type-> getName(),$allowed_pages)) {
				return true;
			}
			
			$allowed_hierarchy_elements = $this -> getValue('iframe_pages');
			
			if(empty($allowed_hierarchy_elements)) return false;
			
			
			
			foreach ($allowed_hierarchy_elements as $hierarchy_element) { 
				if ($hierarchy_element->getId() ==  $element_id){
					return true;
				}	
				
			}

			foreach ($allowed_hierarchy_elements as $hierarchy_element) { 
				if (umiHierarchy::getInstance()->hasParent( $element_id, $hierarchy_element)){
					return true;
				}	
			}

			return false;
		}
	};
?>