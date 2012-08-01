<?php
	abstract class __config	extends	baseModuleAdmin	{
		public function	main() {
			$regedit = regedit::getInstance();
			$config = mainConfiguration::getInstance();

			include_once('timezones.php');
			$timezones['value'] = $config->get("system", "time-zone");

			$params	= array(
				"globals" => array(
					"string:site_name"	=> NULL,
					"email:admin_email"	=> NULL,
					"string:keycode"	=> NULL,
					"boolean:chache_browser"	=> NULL,
					"boolean:disable_url_autocorrection"	=> NULL,
					"boolean:disable_captcha"	=> NULL,
					"int:max_img_filesize"		=> NULL,
					"status:upload_max_filesize" =>	NULL,
					"boolean:allow-alt-name-with-module-collision" => NULL,
					"boolean:allow-redirects-watch" => NULL,
					"int:session_lifetime"	=> NULL,
					"status:busy_quota_files_and_images"	=> NULL,
					"int:quota_files_and_images"	=> NULL,
					"boolean:search_morph_disabled"	=> NULL,
					"boolean:disable_too_many_childs_notification"	=> NULL,
					'select:timezones' => NULL
				)
			);
			
			$upload_max_filesize = cmsController::getInstance()->getModule('data')->getAllowedMaxFileSize();

			$mode =	getRequest("param0");

			if($mode ==	"do") {
				$params	= $this->expectParams($params);

				$regedit->setVar("//settings/site_name", $params['globals']['string:site_name']);
				$regedit->setVar("//settings/admin_email", $params['globals']['email:admin_email']);
				$regedit->setVar("//settings/chache_browser", $params['globals']['boolean:chache_browser']);
				$regedit->setVar("//settings/keycode", $params['globals']['string:keycode']);
				$regedit->setVar("//settings/disable_url_autocorrection", $params['globals']['boolean:disable_url_autocorrection']);
				$config->set('anti-spam', 'captcha.enabled', !$params['globals']['boolean:disable_captcha']);

				$maxImgFilesize = $params['globals']['int:max_img_filesize'];
				if ($maxImgFilesize <= 0 || $maxImgFilesize > $upload_max_filesize) $maxImgFilesize = $upload_max_filesize;
				$regedit->setVar("//settings/max_img_filesize",	$maxImgFilesize);

				$config->set('kernel', 'ignore-module-names-overwrite', $params['globals']['boolean:allow-alt-name-with-module-collision']);
				$config->set('seo', 'watch-redirects-history', $params['globals']['boolean:allow-redirects-watch']);
				$config->set("system", "session-lifetime", $params['globals']['int:session_lifetime']);
				$quota = (int) $params['globals']['int:quota_files_and_images'];
				if ($quota<0) {
					$quota = 0;
				}
				$config->set("system", "quota-files-and-images", $quota * 1024 * 1024);
				$config->set("system", "search-morph-disabled", $params['globals']['boolean:search_morph_disabled']);
				$config->set("system", "disable-too-many-childs-notification", $params['globals']['boolean:disable_too_many_childs_notification']);
				$config->set("system", "time-zone", $params['globals']['select:timezones']);

				$this->chooseRedirect();
			}

			$params['globals']['string:site_name'] = $regedit->getVal("//settings/site_name");
			$params['globals']['email:admin_email']	= $regedit->getVal("//settings/admin_email");
			$params['globals']['boolean:chache_browser'] = $regedit->getVal("//settings/chache_browser");
			$params['globals']['string:keycode'] = $regedit->getVal("//settings/keycode");
			$params['globals']['boolean:disable_url_autocorrection'] = $regedit->getVal("//settings/disable_url_autocorrection");
			$params['globals']['boolean:disable_captcha'] =	!$config->get('anti-spam', 'captcha.enabled');
			$params['globals']['status:upload_max_filesize'] = $upload_max_filesize;

			$max_img_filesize =	$regedit->getVal("//settings/max_img_filesize");

			$params['globals']['int:max_img_filesize'] = $max_img_filesize ? $max_img_filesize : $upload_max_filesize;
			$params['globals']['boolean:allow-alt-name-with-module-collision'] = $config->get('kernel', 'ignore-module-names-overwrite');
			$params['globals']['boolean:allow-redirects-watch'] = $config->get('seo', 'watch-redirects-history');
			$params['globals']['status:busy_quota_files_and_images']	= ceil(getBusyDiskSize() / (1024*1024));
			$params['globals']['int:quota_files_and_images'] = (int) (getBytesFromString($config->get('system', 'quota-files-and-images')) / (1024*1024));
			$params['globals']['int:session_lifetime'] = $config->get('system', 'session-lifetime');
			$params['globals']['boolean:search_morph_disabled'] = $config->get('system', 'search-morph-disabled');
			$params['globals']['boolean:disable_too_many_childs_notification'] = $config->get('system', 'disable-too-many-childs-notification');
			$params['globals']['select:timezones'] = $timezones;

			$this->setDataType("settings");
			$this->setActionType("modify");

			if(is_demo()) {
				unset($params ["globals"] ['string:keycode']	);
			}

			$data =	$this->prepareData($params,	"settings");

			$this->setData($data);
			return $this->doData();
		}


		public function menu() {
			$block_arr = Array();

			$regedit = regedit::getInstance();
			$mdls_list =	$regedit->getList('//modules');

			$priority_list = Array(
				// user modules (priority < 100)
				'content'		=> 1,
				'news'			=> 2,
				'blogs20'		=> 3,
				'forum'			=> 4,
				'comments'		=> 5,
				'vote'			=> 6,
				'webforms'		=> 7,
				'photoalbum'	=> 8,
				'faq'			=> 9,
				'dispatches'	=> 10,
				'catalog'		=> 11,
				'eshop'			=> 12,
				'emarket'		=> 13,
				'banners'		=> 14,
				'users'			=> 15,
				'stat'			=> 16,
				'seo'			=> 17,
				'trash'			=> 18,
				// administrative modules (priority > 100)
				'config'		=> 102,
				'data'			=> 101,
				'backup'		=> 103,
				'autoupdate'	=> 104,
				'webo'			=> 105,
				'search'		=> 106,
				'filemanager'	=> 107,
			);


			$permissions = permissionsCollection::getInstance();
			$modules = Array();

			$modules_list = Array();
			foreach($mdls_list as $module_name) {
				list($module_name) = $module_name;
				if($permissions->isAllowedModule(false,	$module_name) == false)	{
					continue;
				}
				$priority = isset($priority_list[$module_name]) ? $priority_list[$module_name] : 99;
				$modules_list[] = $priority. "^" . $module_name;
			}

			$isTrashAllowed = $permissions->isAllowedMethod($permissions->getUserId(),	"data", "trash");
			if (system_get_skinName() == "mac" && $isTrashAllowed != false) {
				$modules_list[] = "999^trash";
			}

			natsort($modules_list);

			foreach($modules_list as $mdl_info) {
				$module_name ="";
				$priority = "99";
				list($priority, $module_name) = explode("^", $mdl_info);

				$module_config = $regedit->getVal("//modules/{$module_name}/config");
				$current_module	= cmsController::getInstance()->getCurrentModule();
				$current_method	= cmsController::getInstance()->getCurrentMethod();

				$line_arr =	Array();
				$line_arr['attribute:name']	= $module_name;
				$line_arr['attribute:label'] = getLabel("module-" .	$module_name);

				$line_arr['attribute:priority']= $priority;

				if($current_module == $module_name && !($current_method	== 'mainpage'))	{
					$line_arr['attribute:active'] =	"active";
				}

				if($module_config && system_is_allowed($current_module,	"config")) {
					$line_arr['attribute:config'] =	"config";
				}

				$modules[] = $line_arr;
			}
			$block_arr['items']	= Array("nodes:item" =>	$modules);
			return $block_arr;
		}


		public function	modules() {
			$modules = Array();
			$regedit = regedit::getInstance();
			$modules_list =	$regedit->getList("//modules");

			foreach($modules_list as $module_name) {
				list($module_name) = $module_name;

				$modules[] = $module_name;
			}


			$this->setDataType("list");
			$this->setActionType("view");

			$data =	$this->prepareData($modules, "modules");

			$this->setData($data);
			return $this->doData();
		}


		public function	add_module_do()	{
			$cmsController = cmsController::getInstance();

			$modulePath = getRequest('module_path');

			$moduleName = '';
			if(preg_match("/\/modules\/(\S+)\//", $modulePath, $out)) {
				$moduleName = getArrayKey($out, 1);
			}

			if (!preg_match("/.\.php$/", $modulePath )){
				$modulePath .= "/install.php";
			}

			if(!is_demo()) {
				$cmsController->installModule($modulePath);
				if($moduleName == 'geoip') {
					self::switchGroupsActivity('city_targeting', true);
				}
			}

			$this->chooseRedirect($this->pre_lang .	"/admin/config/modules/");
		}


		public function	del_module() {
			$restrictedModules = array('config', 'content', 'users', 'data');

			$target	= getRequest('param0');

			if(in_array($target, $restrictedModules))	{
				throw new publicAdminException(getLabel("error-can-not-delete-{$target}-module"));
			}

			$module	= cmsController::getInstance()->getModule($target);

			if(!is_demo()) {
				if($module instanceof def_module) {
					$module->uninstall();
				}
				if($target == 'geoip') {
					self::switchGroupsActivity('city_targeting', false);
				}
			}

			$this->chooseRedirect($this->pre_lang .	"/admin/config/modules/");
		}

		// for testing  generation time
		public function speedtest() {

			  $buffer = outputBuffer::current();
			  $buffer-> option('generation-time', false);
			  $buffer-> clear();
			  $calltime = $buffer->calltime();
			  $buffer-> push( $calltime );
			  $buffer-> end();

		}

	};
?>
