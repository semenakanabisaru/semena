<?php
	class banners extends def_module {
		static $arrVisibleBanners = array();
		public $updatedBanners = array();

		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();
			if($cmsController->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('lists');
					$commonTabs->add('places');
				}

				$this->__loadLib("__banners.php");
				$this->__implement("__banners_banners");

				$this->__loadLib("__admin.php");
				$this->__implement("__banners_admin");

				$this->__loadLib("__places.php");
				$this->__implement("__places_banners");


			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_banners");

			}

			$this->isStaticCache = (file_exists("./cache.config") || file_exists("banners.config")) ? true : false;
			$this->per_page = 20;

			$config = mainConfiguration::getInstance();
			$this->disableUpdateOpt = (int) $config->get('modules', 'banners.disable-update-optimization');
		}

		public function __destruct() {
			$this->saveUpdates();
		}

		public function getStaticBannerCall($place, $element_id) {
			return <<<JS
<div id="banner_place_{$place}"></div>
<script src="/static_banner.php?place={$place}&current_element_id={$element_id}" type="text/javascript" charset="utf-8"></script>
JS;
		}

		public function insert($sPlace = "", $iMacrosID=0, $bList = false, $current_element_id = false) {

			if($this->isStaticCache && $current_element_id === false) {
				return $this->getStaticBannerCall($sPlace, cmsController::getInstance()->getCurrentElementId());
			}

			if($current_element_id) {
				$currentPageId = $current_element_id;
			} else {
				$currentPageId = cmsController::getInstance()->getCurrentElementId();
			}

			$places = $this->getPlaceId($sPlace);
			if (!count($places)) return "";
			$placeId = $places[0];
			$place = umiObjectsCollection::getInstance()->getObject($placeId);
			$bShowRandomBanner = (bool) $place->getValue('is_show_rand_banner');

			$sResult = "";
			$arrBannersList = array();


			$sel = new selector("objects");
			$sel->types("object-type")->name("banners", "banner");
			$sel->where("show_start_date")->less(time());
			$sel->where("is_active")->equals(1);
			$sel->where('place')->equals($placeId);
			$sel->where('view_pages')->equals($currentPageId);
			$result = $sel->result();

			$currentPageParentsIds = umiHierarchy::getInstance()->getAllParents($currentPageId);
			$selWithoutNull = new selector("objects");
			$selWithoutNull->types("object-type")->name("banners", "banner");
			$selWithoutNull->where("show_start_date")->less(time());
			$selWithoutNull->where("is_active")->equals(1);
			$selWithoutNull->where('place')->equals($placeId);
			$selWithoutNull->where('view_pages')->equals($currentPageParentsIds);
			$resultWithoutNull = $selWithoutNull->result();

			$selWithNull = new selector("objects");
			$selWithNull->types("object-type")->name("banners", "banner");
			$selWithNull->where("show_start_date")->less(time());
			$selWithNull->where("is_active")->equals(1);
			$selWithNull->where('place')->equals($placeId);
			$selWithNull->where('view_pages')->isnull(true);
			$resultWithNull = $selWithNull->result();


			$arrSelResults = array_merge($result, $resultWithoutNull, $resultWithNull);
			$arrSelResults = array_unique($arrSelResults);

			// others filters =========================================================

			if((defined("DB_DRIVER") && DB_DRIVER != "xml")||!defined("DB_DRIVER"))
			if ($oStat = cmsController::getInstance()->getModule("stat")) {
				$arrGetTags = $oStat->getCurrentUserTags();
			}

			foreach ($arrSelResults as $id => $oNextBanner) {

				if ($oNextBanner instanceof umiObject) {
					
					$iNextBanId = $oNextBanner->getId();
					
					if (!in_array($currentPageId, $oNextBanner->getValue('not_view_pages'))) {

						// max count views filter
						if ($oNextBanner->getValue('max_views') <= 0 || $oNextBanner->getValue('views_count') <= $oNextBanner->getValue('max_views')) {
							$bShowActual = true;
							// tags filter
							$arrBannerTags = $oNextBanner->getValue("tags");
							if (count($arrBannerTags)) {
								$iCurrPageId = cmsController::getInstance()->getCurrentElementId();
								$oCurrPage = umiHierarchy::getInstance()->getElement($iCurrPageId, true);
								if(is_object($oCurrPage)) {
									$arrPageTags = $oCurrPage->getValue("tags");
								} else {
									$arrPageTags = Array();
								}
								$arrCommonTags = array_intersect($arrBannerTags, $arrPageTags);
								if (!count($arrCommonTags)) $bShowActual = false;
							}
							// do show till filter
							$oShowTillDate = $oNextBanner->GetValue('show_till_date');
							if ($oShowTillDate instanceof umiDate && $oShowTillDate->timestamp) {
								if ($oShowTillDate->timestamp < $oShowTillDate->getCurrentTimeStamp()) {
									$bShowActual = false;
								}
							}
							if ($bShowActual) {
								// time-targeting filter =======================
								if ($oNextBanner->getValue('time_targeting_is_active')) {
									$oRanges = new ranges();
									// by month
									$sByMonth = $oNextBanner->getValue('time_targeting_by_month');
									if (strlen($sByMonth)) {
										$iCurrMonth = (int) date("m");
										$arrShowByMonth = $oRanges->get($sByMonth, 1);
										if (array_search($iCurrMonth, $arrShowByMonth)===false) $bShowActual = false;
									}
									// by month days
									$sByMonthDays = $oNextBanner->getValue('time_targeting_by_month_days');
									if (strlen($sByMonthDays) && $bShowActual) {
										$iCurrMonthDay = (int) date("d");
										$arrShowByMonthDays = $oRanges->get($sByMonthDays);
										if (array_search($iCurrMonthDay, $arrShowByMonthDays)===false) $bShowActual = false;
									}
									// by week days
									$sByWeekDays = $oNextBanner->getValue('time_targeting_by_week_days');
									if (strlen($sByWeekDays) && $bShowActual) {
										$iCurrWeekDay = (int) date("w");
										$arrShowByWeekDays = $oRanges->get($sByWeekDays);
										if (array_search($iCurrWeekDay, $arrShowByWeekDays)===false) $bShowActual = false;
									}
									// by hours
									$sByHours = $oNextBanner->getValue('time_targeting_by_hours');
									if (strlen($sByHours) && $bShowActual) {
										$iCurrHour = (int) date("G");
										$arrShowByHours = $oRanges->get($sByHours);
										if (array_search($iCurrHour, $arrShowByHours)===false) $bShowActual = false;
									}
								}
								// user tags filter
								if ($bShowActual) {
									$arrBannerTags = $oNextBanner->getValue("user_tags");

									if (is_array($arrBannerTags) && count($arrBannerTags)) {
										if ($oStat = cmsController::getInstance()->getModule("stat")) {
											$arrUserTags = array();
											if (isset($arrGetTags) && is_array($arrGetTags)) {

												foreach ($arrGetTags as $sTmp => $arrTagInfo) {
													if (isset($arrTagInfo)) $arrUserTags[] = $arrTagInfo["tag"];
												}
											}
											$iExceptTags = 0;
											$iAllowTags = 0;
											for ($nI=0; $nI < count($arrBannerTags); $nI++) {
												$sNextTag = $arrBannerTags[$nI];
												if (strpos($sNextTag, "!") !== false) {
													$sNextTag = substr($sNextTag ,1);
													if (in_array($sNextTag, $arrUserTags)) {
														$iExceptTags = 1;
														break;
													}
												} else {
													if (in_array($sNextTag, $arrUserTags)) {
														$iAllowTags++;
													}
												}
											}
											if ($iExceptTags || !$iAllowTags) $bShowActual = false;
										}
									}
								}
								if($bShowActual) {

									$bTargetingActive = $oNextBanner->getValue("city_targeting_is_active");
									if($bTargetingActive) {

										$sBannerCity = $oNextBanner->getValue("city_targeting_city");

										if ($sBannerCity) {
											if($oGeoIPMod = cmsController::getInstance()->getModule("geoip")) {

												$info = $oGeoIPMod->lookupIp(getServer('REMOTE_ADDR'));
												if (isset($info['city'])) {
													$sCurrentCity = $info['city'];
													$city = umiObjectsCollection::getInstance()->getObject($sBannerCity)->getName();
													$bShowActual = ($sCurrentCity == $city);
												}
												else $bShowActual == false;
											}
										}
									}
								}
								if ($bShowActual) {
									$arrBannersList[] = $iNextBanId;
								}
							} else {
								//$oNextBanner->setValue('is_active', false);
							}
						} else {
							//$oNextBanner->setValue('is_active', false);
						}
					}
				}
			}
			if (count($arrBannersList)) {
				$iShowBanId = 0;
				if (count($arrBannersList) > 1) {
					foreach (self::$arrVisibleBanners as $sNextPlace => $arrPlaceBanners) {
						$arrBannersList = array_diff($arrBannersList, $arrPlaceBanners);
					}
				}
				if ($bShowRandomBanner) {
					// random banner
					srand((float) microtime() * 10000000);
					$iRandBanInd = array_rand($arrBannersList);
					$iShowBanId = $arrBannersList[$iRandBanInd];
				} else {
					reset($arrBannersList);
					$iShowBanId = current($arrBannersList);
				}

				if ($bList) {
					$params = array();
					$params['nodes:banners'] = array();
					foreach ($arrBannersList as $itmp => $iBannId) {
						self::$arrVisibleBanners[$sPlace][] = $iBannId;
						$params['nodes:banners'][] = self::renderBanner($iBannId);
					}
					$sResult = def_module::parseTemplate("", $params);
				} else {
					self::$arrVisibleBanners[$sPlace][] = $iShowBanId;
					$sResult = self::renderBanner($iShowBanId);
				}
			}

			$reg = regedit::getInstance();

			if (($reg->getVal("//modules/banners/days-before-notification") || $reg->getVal("//modules/banners/clicks-before-notification")) && $reg->getVal("//modules/banners/last-check-date") < (time()-3600*24)) {
				$this->sendNotification();
			}

			return $sResult;
		}

		protected function sendNotification() {
			$daysLeft = regedit::getInstance()->getVal("//modules/banners/days-before-notification");
			$daysLeft = $daysLeft*24*3600;

			$viewsLeft = regedit::getInstance()->getVal("//modules/banners/clicks-before-notification");

			$items = array();

			$sel = new selector('objects');
			$sel->types('object-type')->name('banners', 'banner');

			foreach ($sel->result() as $banner) {
				$tillDate = toTimeStamp($banner->getValue('show_till_date'));
				$viewsCount = $banner->getValue('views_count');
				$maxViews = $banner->getValue('max_views');

				$days = false;
				$views = false;

				if ((int) $tillDate && ((time() + $daysLeft) >= $tillDate)) $days = true;
				if ((int) $maxViews && (($viewsCount + $viewsLeft) >= $maxViews)) $views = true;

				if ($days || $views) {

					$bannerId = $banner->id;
					$bannerName = $banner->getName();

					$domain = domainsCollection::getInstance()->getDefaultDomain();
					$link = "http://".$domain->getHost().'/admin/banners/edit/'.$bannerId;

					list($templateLine) = def_module::loadTemplatesForMail("mail/banner_notification", "item");
					$itemArr['link'] = $link;
					$itemArr['bannerName'] = $bannerName;

					if ($days) {
						$itemArr['tillDate'] = ' - срок показа истекает ' . $banner->getValue('show_till_date')->getFormattedDate().'.';
					} elseif ($views) {
						$itemArr['tillDate'] = ' - оставшееся количество показов: ' . ($maxViews - $viewsCount ). '.';
					} else {
						$itemArr['tillDate'] ='';
					}

					$items[] = def_module::parseTemplateForMail($templateLine, $itemArr, false, $bannerId);
				}
			}

			if (count($items)) {
				$blockArr = array();
				list($subject, $template) = def_module::loadTemplatesForMail("mail/banner_notification", "subject", "body");

				$mailMessage = new umiMail();
				$from = regedit::getInstance()->getVal("//settings/email_from");
				$mailMessage->setFrom($from);
				$emailTo = regedit::getInstance()->getVal("//settings/admin_email");
				$mailMessage->addRecipient($emailTo);
				$mailMessage->setPriorityLevel("high");
				$subject = def_module::parseTemplateForMail($subject, $blockArr);
				$mailMessage->setSubject($subject);

				$blockArr['header'] = $subject;
				$blockArr['+items'] = $items;

				$content = def_module::parseTemplateForMail($template, $blockArr);

				$mailMessage->setContent($content);
				$mailMessage->commit();
				$mailMessage->send();
				regedit::getInstance()->setVal("//modules/banners/last-check-date", time());
			}
		}

		protected function renderBanner($iObjId) {
			$block_arr = Array();
			//

			$sResult = "";
			$oBanner = umiObjectsCollection::getInstance()->getObject($iObjId);
			if ($oBanner instanceof umiObject) {
				//$iBannerTypeId = $oBanner->getTypeId();
				//$oBannerType = umiObjectTypesCollection::getInstance()->getType($iBannerTypeId);
				$sBannerType = "";
				if ($oBanner->getValue('swf') !== false) $sBannerType="swf";
				if ($oBanner->getValue('image') !== false) $sBannerType="image";
				if ($oBanner->getValue('html_content') !== false) $sBannerType="html";
				$sUrl =  $oBanner->getValue('url');
				$bOpenInNewWindow = $oBanner->getValue('open_in_new_window');
				switch ($sBannerType) {
					case "swf":
								$oImgFile = $oBanner->getValue('swf');
								if ($oImgFile instanceof umiImageFile && !$oImgFile->getIsBroken()) {
									// banner sizes
									$iWidth =  (int) $oBanner->getValue('width');
									$iHeight = (int) $oBanner->getValue('height');
									if ($iWidth<=0) $iWidth = $oImgFile->getWidth();
									if ($iHeight<=0) $iHeight = $oImgFile->getHeight();
									$sSwfSrc = $oImgFile->getFilePath(true);
									$sSwfTarget = ($oBanner->getValue('open_in_new_window')? "_blank": "_self");
									$sGoLink = $this->pre_lang . "/banners/go_to/" . $iObjId;
									$sResult = <<<END
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="$iWidth" height="$iHeight" id="$iObjId" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="movie" value="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" />
	<param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />
	<param name="wmode" value="transparent" />

	<embed src="{$sSwfSrc}?target={$sSwfTarget}&amp;link1={$sGoLink}&amp;link={$sGoLink}" quality="high" bgcolor="#ffffff" width="$iWidth" height="$iHeight" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />

</object>
END;

									$banner = Array();
									$banner['attribute:type'] = $sBannerType;
									$banner['attribute:width'] = $iWidth;
									$banner['attribute:height'] = $iHeight;
									$banner['attribute:target'] = $sSwfTarget;
									$banner['href'] = $sUrl;
									$banner['source'] = $sSwfSrc;
									$banner['alt'] = $oBanner->getValue('alt');
									$block_arr['banner'] = $banner;

								}
								break;
					case "image":
								$oImgFile = $oBanner->getValue('image');
								if ($oImgFile instanceof umiImageFile && !$oImgFile->getIsBroken()) {
									// banner sizes
									$iWidth =  (int) $oBanner->getValue('width');
									$iHeight = (int) $oBanner->getValue('height');
									if ($iWidth<=0) $iWidth = $oImgFile->getWidth();
									if ($iHeight<=0) $iHeight = $oImgFile->getHeight();
									//
									$sBannerImg = "<img src=\"".$oImgFile->getFilePath(true)."\" border=\"0\" alt=\"".$oBanner->getValue('alt')."\" width=\"".$iWidth."\" height=\"".$iHeight."\" />";
									$sResult = $sBannerImg;
									if (strlen($sUrl)) {
										$sResult = "<a href=\"".$this->pre_lang."/banners/go_to/".$iObjId."/\" ".(($bOpenInNewWindow)? "target=\"_blank\"": "").">".$sBannerImg."</a>";
									}

									$banner = Array();
									$banner['attribute:type'] = $sBannerType;
									$banner['attribute:width'] = $iWidth;
									$banner['attribute:height'] = $iHeight;
									$banner['attribute:target'] = (($bOpenInNewWindow) ? "_blank" : "");

									$banner['source'] = $oImgFile->getFilePath(true);
									$banner['alt'] = $oBanner->getValue('alt');
									$banner['href'] = $sUrl;
									$block_arr['banner'] = $banner;
								}
								break;
					case "html":
								$sResult = $oBanner->getValue('html_content');

								$banner = Array();
								$banner['attribute:type'] = $sBannerType;
								$banner['source'] = $sResult;
								$banner['href'] = $sUrl;
								$banner['alt'] = $oBanner->getValue('alt');
								$block_arr['banner'] = $banner;
								// parse result
								$sResult = str_ireplace("%link%", $this->pre_lang."/banners/go_to/".$iObjId, $sResult);
								break;
					default:
						// do nothing
						break;
				}
				// set banner
				$iOldViewsCount = $oBanner->getValue('views_count') + 1;
				$oBanner->views_count = $iOldViewsCount;
				$this->updatedBanners[] = $oBanner;

				if($this->disableUpdateOpt) {
					$this->saveUpdates();
				}
			}


			$block_arr['attribute:id'] = $iObjId;
			if(isset($block_arr['banner'])) {
				$block_arr['banner']['xlink:href'] = "uobject://" . $iObjId;
			}
			$sResult = def_module::parseTemplate($sResult, $block_arr, false, $iObjId);
			return $sResult;
		}

		protected function saveUpdates() {
			foreach($this->updatedBanners as $i => $banner) {
				if($banner instanceof iUmiObject) {
					if($banner->max_views && ($banner->views_count >= $banner->max_views)) {
						$banner->is_active = false;
					}
					$banner->commit();
					unset($this->updatedBanners[$i]);
				}
			}
		}

		public function go_to(){
			$iObjId = $_REQUEST['param0'];
			$oBanner = umiObjectsCollection::getInstance()->getObject($iObjId);
			if ($oBanner instanceof umiObject) {
				$sUrl = $oBanner->getValue('url');
				// write stats
				$iOldClicksCount = $oBanner->getValue('clicks_count');
				$oBanner->setValue('clicks_count', ++$iOldClicksCount);
				$oBanner->commit();
				// try redirect
				$this->redirect($sUrl);
			}
		}


		public function getEditLink($object_id, $object_type) {
			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			switch($object_type) {
				case "banner": {
					$link_add = $this->pre_lang . "/admin/banners/banner_add/";
					$link_edit = $this->pre_lang . "/admin/banners/banner_edit/{$object_id}/";

					return array($link_add, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
		}

		public function fastInsert($placeName) {
			$sel = new umiSelection;
			// type filter
			$sel->setObjectTypeFilter();
			$hierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("banners", "banner")->getId();
			$objectTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hierarchyTypeId);
			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);

			if(!$objectType) {
				return false;
			}

			$bannerTypes = umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($hierarchyTypeId);
			$sel->addObjectType(array_keys($bannerTypes));
			$sel->addPropertyFilterEqual($objectType->getFieldId('place'), $this->getPlaceId($placeName));
			$sel->setOrderByRand();
			$result = umiSelectionsParser::runSelection($sel);

			$objects = umiObjectsCollection::getInstance();

			foreach($result as $bannerId) {
				$banner = $objects->getObject($bannerId);
				if(!$banner->is_active) {
					continue;
				}
				if($this->checkIfValidParent($banner->view_pages, $banner->not_view_pages) == false) continue;
				if($renderedBanner = $this->renderBanner($bannerId)) {
					return $renderedBanner;
				}
			}
		}

		protected function checkIfValidParent($pages, $notPages) {

			$currentPageId = cmsController::getInstance()->getCurrentElementId();
			if (count($notPages)) {
				foreach($notPages as $notPage) {
					if ($notPage->getId() == $currentPageId) return false;
				}
			}

			if(!is_array($pages) || sizeof($pages) == 0) {
				return true;
			}

			$parents = $this->getCurrentParents();

			foreach($pages as $page) {
				if(in_array($page->getId(), $parents)) {
					return true;
				}
			}
			return false;
		}

		protected function getCurrentParents() {
			static $parents = false;

			if(is_array($parents)) {
				return $parents;
			}

			$iCurrPageId = cmsController::getInstance()->getCurrentElementId();

			if($iCurrPageId) {
				return $parents = umiHierarchy::getInstance()->getAllParents($iCurrPageId, true);
			} else {
				return Array();
			}
		}


		protected function getPlaceId($placeName) {
			static $cache = Array();
			$placeName = (string) $placeName;

			if(isset($cache[$placeName])) {
				return $cache[$placeName];
			}

			$objectTypeId = umiObjectTypesCollection::getInstance()->getBaseType("banners", "place");

			$sel = new umiSelection;
			$sel->addObjectType($objectTypeId);
			$sel->addNameFilterEquals($placeName);
			return $cache[$placeName] = umiSelectionsParser::runSelection($sel);
		}

		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . "/admin/banners/edit/" . $objectId . "/";
		}
	};
?>