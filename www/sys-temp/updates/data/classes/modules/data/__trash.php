<?php
        abstract class __trash_data extends baseModuleAdmin {

		public function trash() {
			$per_page = 20;
			$curr_page = getRequest('p');
			
			$deleted = umiHierarchy::getInstance()->getDeletedList();
			$total = sizeof($deleted);
			$deleted = array_slice($deleted, $per_page * $curr_page, $per_page);

			$this->setDataType("list");
			$this->setActionType("view");
			$this->setDataRange($per_page, $curr_page * $per_page);
			
			$data = $this->prepareData($deleted, "pages");
			$this->setData($data, $total);
			return $this->doData();
			
		}

		public function trash_restore() {
			$redirect = true;
			$elements = $this->expectElementId("param0");
			if(!$elements) {
				$elements = getRequest("element");
				$redirect = false;
			}
			if(!is_array($elements)) {
				$elements = array($elements);
			}
			$hierarchy = umiHierarchy::getInstance();
			foreach($elements as $element_id) {
				$hierarchy->restoreElement($element_id);
			}
			if($redirect) {
				$this->chooseRedirect($this->pre_lang . "/admin/data/trash/");
			} else {
				$this->setData(array());
				return $this->doData();
			}
		}


		public function trash_del() {
			$redirect = true;
			$elements = $this->expectElementId("param0");
			if(!$elements) {
				$elements = getRequest("element");
				$redirect = false;
			}
			if(!is_array($elements)) {
				$elements = array($elements);
			}
			$hierarchy = umiHierarchy::getInstance();
			foreach($elements as $element_id) {
				$hierarchy->removeDeletedElement($element_id);
			}
			if($redirect) {
				$this->chooseRedirect($this->pre_lang . "/admin/data/trash/");
			} else {
				$this->setData(array());
				return $this->doData();
			}
		}


		public function trash_empty() {
			$limit = 100;
			$c = umiHierarchy::getInstance()->removeDeletedWithLimit($limit);
			
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");

			$data = array(
				"attribute:complete" => (int) ($c<$limit),
				"attribute:deleted" => $c
			);

			baseModuleAdmin::setData($data);
			return baseModuleAdmin::doData();
		}

	};
?>