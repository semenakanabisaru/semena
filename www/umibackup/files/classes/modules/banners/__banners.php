<?php
	abstract class __banners_banners extends baseModuleAdmin {

		public function banners_list() {
			//Deprecated method
			$regedit = regedit::getInstance();
			$regedit->setVar("//modules/banners/default_method_admin", "lists");
			$this->redirect($this->pre_lang . "/admin/banners/lists/");
		}

		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = $this->per_page;
			$curr_page = getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('banners', 'banner');
			$sel->order('name')->asc();
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);
			
			$this->setDataRange($limit, $offset);

			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function add() {
			$type = "banner";
			$mode = (string) getRequest("param0");
			$type_id = (int) getRequest("type-id");
			//Подготавливаем список параметров
			$inputData = Array("type" => $type, 'type-id' => getRequest('type-id'));

			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);
				$object->setTypeId($type_id);
				if (isset($_REQUEST['data']['new']['show_till_date']) && !strlen($_REQUEST['data']['new']['show_till_date'])) {
					$object->setValue('show_till_date', null);
					$object->commit();
				}
				$this->chooseRedirect($this->pre_lang . '/admin/banners/edit/' . $object->getId() . '/');
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}

		public function edit() {
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');

			if($mode == "do") {
				$this->saveEditedObjectData($object);
				if (isset($_REQUEST['data'][$object->getId()]['show_till_date']) && !strlen($_REQUEST['data'][$object->getId()]['show_till_date'])) {
					$object->setValue('show_till_date', null);
					$object->commit();
				}
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($object, "object");

			$this->setData($data);
			return $this->doData();
		}
		
		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			
			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				
				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('banner', 'place')
				);
				
				$this->deleteObject($params);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}
		
		
		public function activity() {
			$objects = getRequest('object');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			$is_active = (bool) getRequest('active');
			
			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$object->setValue("is_active", $is_active);
				$object->commit();
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function getDatasetConfiguration($param = '') {
			$result = array(
				'methods' => array(
					array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'banners', '#__name'=>'lists'),
					array('title'=>getLabel('smc-delete'), 'module'=>'banners', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
					array('title'=>getLabel('smc-activity'), 'module'=>'banners', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity')),
				'types' => array(
					array('common' => 'true', 'id' => 'banner')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'avatar', 'userpic', 'user_settings_data', 'user_dock', 'orders_refs', 'activate_code'),
					'default' => ''
				);
		
			$cmsController = cmsController::getInstance();
			if($cmsController->getModule('geoip') instanceof def_module == false) {
				$result['stoplist'][] = 'city_targeting_city';
				$result['stoplist'][] = 'city_targeting_is_active';
			}
		
			return $result;
		}		
		
		
	};
?>
