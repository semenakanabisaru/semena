<?php
/*
*/

class __tagsDomainCloud {
	/*
	*/

	public function tagsDomainCloud($s_template = "tags", $b_curr_user = false, $i_per_page = -1, $b_ignore_paging = true) {
		/*
		*/

		return $this->tags_mk_cloud(cmsController::getInstance()->getCurrentDomain()->getId(), $s_template, $i_per_page, $b_ignore_paging, false, ($b_curr_user ? cmsController::getInstance()->getModule('users')->user_id : array()));
	}
}
?>