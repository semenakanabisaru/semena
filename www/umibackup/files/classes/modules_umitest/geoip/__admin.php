<?php
	abstract class __geoip extends baseModuleAdmin {

		public function info() {
			$params = Array(
				"global" => Array(
					"string:ip"	=> NULL
				)
			);

			$mode = (string) getRequest('param0');
			if($mode == "do") {
				$params = $this->expectParams($params);
				$info = $this->lookupIp($params['global']['string:ip']);

				if(!isset($info['special'])) {
					$params['geoinfo'] = Array(
							'string:country'	=> $info['country'],
							'string:region'		=> $info['region'],
							'string:city'		=> $info['city'],
							'string:latitude'	=> $info['lat'],
							'string:longitude'	=> $info['lon']
						);
				} else {
					$params['geoinfo'] = array('string:special' => $info['special']);
				}

			}

			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}
	};
?>