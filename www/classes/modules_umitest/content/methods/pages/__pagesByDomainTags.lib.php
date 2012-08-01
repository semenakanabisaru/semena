<?php
/*
*/

class __pagesByDomainTags {
	/*
	*/

	public function pagesByDomainTags($s_tags = NULL, $s_template = "tags", $s_base_types = NULL, $i_per_page = false, $b_ignore_paging = false) {
		/*
		*/

		if (is_null($s_tags)) $s_tags = getRequest('param0');
		if (!$s_template) $s_template = getRequest('param1');
		if (is_null($s_base_types)) $s_base_types = getRequest('param2');
		return $this->pages_mklist_by_tags($s_tags, cmsController::getInstance()->getCurrentDomain()->getId(), $s_template, $i_per_page, $b_ignore_paging, $s_base_types);
	}
}
?>