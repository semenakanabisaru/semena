<?php
	abstract class __content_antispam {
		public function switchSpamStatus() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = array($elements);
			}
			$spamStatus = (int) getRequest('spam-status');
			
			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				$element->is_spam = ($element->is_spam == 2) ? 1 : 2;
				$element->commit();
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}
		
		public function checkAllMessages() {
			$service = antiSpamService::get();
			if(!$service) return;
			
			$sel = new selector('pages');
			$sel->types->name('comments', 'comment');
			$sel->types->name('forum', 'message');
			$sel->where('is_spam')->isNull();
			
			foreach($sel->result() as $page) {
				$service->setNick(null);
				$service->setLink($page->link);
				$service->setContent($page->content);
				
				$page->is_spam = ($service->isSpam()) ? 2 : 1;
				$page->commit();
			}
		}
	};
?>