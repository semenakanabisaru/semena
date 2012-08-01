<?php
/*
*/

class __tagsDomainUsageCloud {
	/*
	*/

	public function tagsDomainUsageCloud($s_template = "tags", $i_per_page = -1, $b_ignore_paging = true) {
		/*
		*/

		return $this->tags_mk_cloud(cmsController::getInstance()->getCurrentDomain()->getId(), $s_template, $i_per_page, $b_ignore_paging, false, array());
	}
}
?>