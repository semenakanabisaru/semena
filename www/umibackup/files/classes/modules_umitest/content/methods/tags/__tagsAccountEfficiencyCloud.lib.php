<?php
/*
*/

class __tagsAccountEfficiencyCloud {
	/*
	*/

	public function tagsAccountEfficiencyCloud($s_template = "tags", $b_curr_user = false, $i_per_page = -1, $b_ignore_paging = true) {
		/*
		*/

		return $this->tags_mk_eff_cloud(NULL, $s_template, $i_per_page, $b_ignore_paging, ($b_curr_user ? cmsController::getInstance()->getModule('users')->user_id : array()));
	}
}
?>