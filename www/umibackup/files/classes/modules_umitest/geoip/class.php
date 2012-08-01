<?php
	class geoip extends def_module {

		public function __construct() {
	                parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__geoip");
			}
		}

		/**
		 * @desc Return geographical location
		 * @param  IP-address
		 * @return Array(Longitude, Longitude)
		 */
		public function getPosition($ip = false) {
			$info = $this->lookupIp($ip);
			if (isset ($info['lon']) && isset($info['lat'])) {
				$x = $info['lon'];
				$y = $info['lat'];
			}
			else {
				$x = null;
				$y = null;
			}
			return Array($x, $y);
		}

		/**
		 * @desc   Return geographical location info about the IP-address
		 * @param  IP-address
		 * @return Array(City RU, City EN, Country Abbr, Longitude, Longitude,
		 *               Country RU, Country EN, Country Region RU, Contry REgion EN)
		 */
		public function lookupIp($ip = false) {

			include_once CURRENT_WORKING_DIR . "/classes/modules/geoip/newcngeoip.php";

			static $cache = Array();

			if($ip === false) {
				$ip = getServer("REMOTE_ADDR");
			}

			$ip = gethostbyname($ip);

			if(isset($cache[$ip])) return $cache[$ip];

			$ipChecker = new CNGeoIP();
			$info = $ipChecker->get_description_by_ip($ip);

			if(!isset($info['special'])) {
				foreach ($info as $type){
					if (in_array('country', $type)) {
						$ipInfo['country'] = $type['name_ru'];
						if(!isset($i)) {
							$ipInfo['lat'] = $type['lat'];
							$ipInfo['lon'] = $type['lon'];
						}
					}
					if (in_array('region', $type)) {
						$ipInfo['region'] = $type['name_ru'];
						$ii = 1;
					}
					if (!isset($ii)) $ipInfo['region'] = null;

					if (in_array('city', $type)) {
						$ipInfo['city'] = $type['name_ru'];
						$ipInfo['lat'] = $type['lat'];
						$ipInfo['lon'] = $type['lon'];
						$i=1;
					}
					elseif (in_array('town', $type)) {
						$ipInfo['city'] = $type['name_ru'];
						$ipInfo['lat'] = $type['lat'];
						$ipInfo['lon'] = $type['lon'];
						$i=1;
					}
					elseif (in_array('village', $type)) {
						$ipInfo['city'] = $type['name_ru'];
						$ipInfo['lat'] = $type['lat'];
						$ipInfo['lon'] = $type['lon'];
						$i=1;
					}
					elseif (in_array('cdp', $type)) {
						if (isset($type['name_ru'])) $ipInfo['city'] = $type['name_ru'];
						else $ipInfo['city'] = $type['name_en'];
						$ipInfo['lat'] = $type['lat'];
						$ipInfo['lon'] = $type['lon'];
						$i=1;
					}
					if(!isset($i)) $ipInfo['city'] = null;
				}
			}
			else $ipInfo['special'] = $info['special'];
			return $cache[$ip] = $ipInfo;
		}
	};

?>