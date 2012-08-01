<?php
	abstract class __search extends baseModuleAdmin {
		public function config() {
			$this->redirect($this->pre_lang . "/admin/search/index_control/");
		}


		public function index_control() {
			$regedit = regedit::getInstance();
			$searchModel = searchModel::getInstance();
			
			$params = array(
				"info" => array(
					"status:index_pages"		=> NULL,
					"status:index_words"		=> NULL,
					"status:index_words_uniq"		=> NULL,
					"status:index_last"		=> NULL
				),
				"globals" => array(
					"int:per_page"		=> NULL,
					"int:one_iteration_index"		=> NULL
				)
			);

			$mode = getRequest("param0");
			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVal("//modules/search/per_page", $params['globals']['int:per_page']);
				$regedit->setVal("//modules/search/one_iteration_index", $params['globals']['int:one_iteration_index']);
				$this->chooseRedirect();
			}

			$params['info']['status:index_pages'] = $searchModel->getIndexPages();
			$params['info']['status:index_words'] = $searchModel->getIndexWords();
			$params['info']['status:index_words_uniq'] = $searchModel->getIndexWordsUniq();
			$params['info']['status:index_last'] = ($index_last = $searchModel->getIndexLast()) ? date("Y-m-d H:i:s", $index_last) : "-";
			$params['globals']['int:per_page'] = $regedit->getVal("//modules/search/per_page");
			$params['globals']['int:one_iteration_index'] = $regedit->getVal("//modules/search/one_iteration_index");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function truncate() {
			searchModel::getInstance()->truncate_index();
			$this->redirect($this->pre_lang . "/admin/search/");
		}


		public function reindex() {
			searchModel::getInstance()->index_all();
			$this->redirect($this->pre_lang . "/admin/search/");
		}
		
		public function partialReindex() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$lastId = (int) getRequest("lastId");
			$search = searchModel::getInstance();
			
			$total = (int) $search->getAllIndexablePages();
			$limit = regedit::getInstance()->getVal("//modules/search/one_iteration_index");
			if ($limit==0) {
				$limit = 5;
			}
			$result = $search->index_all($limit, $lastId);
			
			$data = Array(
				'index-status' => Array(
					'attribute:current' => $result['current'],
					'attribute:total' => $total,
					'attribute:lastId' => $result['lastId']
				)
			);

			$this->setData($data);
			return $this->doData();
		}
	};
?>
