<?php
	abstract class __rate_vote {
		public function json_rate($template = "default") {
			if(!$template) $template = "default";

			$block_arr = array();
			$element_id = (int) getRequest('param0');
			$element = umiHierarchy::getInstance()->getElement($element_id);
			
			if(regedit::getInstance()->getVal("//modules/vote/is_graded")) {
				$bid = (int) getRequest('param1');
				
				if($bid > 5) $bid = 5;
				if($bid < 0) $bid = 0;
			} else {
				$bid = ((bool) getRequest('param1')) ? 1 : -1;
			}

			list(
				$template_ok, $template_not_found, $template_rated, $template_permitted
			) = def_module::loadTemplates("vote/rate/".$template,
				"rate_ok", "rate_not_found", "rate_rated", "rate_permitted"
			);

			if($is_private = (bool) regedit::getInstance()->getVal("//modules/vote/is_private")) {
				$users_module = cmsController::getInstance()->getModule("users");
				if(!$users_module->is_auth()) {
					header("Content-type: text/javascript; charset=utf-8");
					$template_permitted = def_module::parseTemplate($template_permitted, $block_arr, $element_id);
					$this->flush($template_permitted);
				}
			}

			$block_arr = Array();
			$block_arr['request_id'] = getRequest('requestId');


			if($element) {
				$block_arr['element_id'] = $element_id;
				if(self::getIsRated($element_id)) {
					$rate_voters = $element->getValue("rate_voters");
					$rate_sum = $element->getValue("rate_sum");

					$res = $template_rated;
				} else {
					$rate_voters = (int) $element->getValue("rate_voters");
					$rate_sum = (int) $element->getValue("rate_sum") + (int) $bid;

					$element->setValue("rate_voters", ++$rate_voters);
					$element->setValue("rate_sum", $rate_sum);
					
					$element->setValue("rate", round($rate_sum / $rate_voters, 2));

					$element->commit();
					umiHierarchy::getInstance()->unloadElement($element_id);

					$res = $template_ok;

					self::setIsRated($element_id);
				}


				$block_arr['current_rating'] = $rate_sum / $rate_voters;
			} else {
				$res = $template_not_found;
			}

			$res = def_module::parseTemplate($res, $block_arr, $element_id);

			header("Content-type: text/javascript; charset=utf-8");
			$this->flush($res);
		}

		public function getElementRating($element_id) {
			$element = umiHierarchy::getInstance()->getElement($element_id);
			if (!$element) return '';


			$block_arr = array();
			$block_arr['rate_sum'] = (int) $element->getValue("rate_sum");
			$block_arr['rate_voters'] = (int) $element->getValue("rate_voters");
			$block_arr['is_rated'] = self::getIsRated($element_id);
			if ($block_arr['rate_voters'] > 0) {
				$block_arr['rate'] = round($block_arr['rate_sum'] / $block_arr['rate_voters'], 2);
				$block_arr['ceil_rate'] = round($block_arr['rate']);
			}

			return def_module::parseTemplate("", $block_arr, $element_id);
		}

		public static function getIsRated($element_id) {
			$is_private = (bool) regedit::getInstance()->getVal("//modules/vote/is_private");

			if($is_private) {
				$users_module = cmsController::getInstance()->getModule("users");
				$user_id = $users_module->user_id;
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				$rated_pages = $user->getValue("rated_pages");
				
				$element = umiHierarchy::getInstance()->getElement($element_id);
				return in_array($element, $rated_pages);
			}

			return is_array(getSession('rated')) && in_array($element_id, getSession('rated'));
		}


		public static function setIsRated($element_id) {
			$is_private = (bool) regedit::getInstance()->getVal("//modules/vote/is_private");

			if($is_private) {
				$users_module = cmsController::getInstance()->getModule("users");
				$user_id = $users_module->user_id;
				$user = umiObjectsCollection::getInstance()->getObject($user_id);

				$element = umiHierarchy::getInstance()->getElement($element_id);

				$rated_pages = $user->getValue("rated_pages");
				$rated_pages[] = $element;

				$user->setValue("rated_pages", $rated_pages);
				$user->commit();
			}



			if(!is_array(getSession('rated'))) {
				$_SESSION['rated'] = Array();
			}
			$_SESSION['rated'][] = (int) $element_id;
		}
	};
?>