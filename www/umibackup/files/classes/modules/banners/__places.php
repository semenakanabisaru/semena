<?php
	abstract class __places_banners {
		public function places() {
			$mode = (string) getRequest('param0');

			if($mode == "do") {
				$this->saveEditedList("objects", array('type' => 'place'));
				$this->chooseRedirect();
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('banners', 'place');

			$this->setDataType("list");
			$this->setActionType("modify");
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);

			return $this->doData();
		}
	};
?>