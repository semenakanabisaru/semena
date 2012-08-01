<?php

	abstract class __watermark_config extends baseModuleAdmin {
	
		public function watermark() {
			
			$regedit = regedit::getInstance();

			$params = Array(
				"watermark" => Array(
					"string:image"  => NULL,
					"int:alpha"     => NULL,
					"select:valign" => array (
							"top"   => getLabel ("watermark-valign-top"),
							"bottom"=> getLabel ("watermark-valign-bottom"),
							"bottom"=> getLabel ("watermark-valign-center")
						),
					"select:halign" => array (
							"left"  => getLabel ("watermark-halign-left"),
							"right" => getLabel ("watermark-halign-right"),
							"center" => getLabel ("watermark-halign-center")
						)
				)
			);
			
			$mode = getRequest("param0");

			if($mode == "do") {
				
				$params = $this->expectParams($params);
				
				if ($regedit->getKey("//settings/watermark") === false) {
					$regedit->setVar("//settings/watermark", "");
				}
				
				$imagePath = trim ($params['watermark']['string:image']);
				$imagePath = str_replace ("./", "", $imagePath);
				if (substr ($imagePath, 0, 1) == "/") {
					$imagePath = substr ($imagePath, 1);
				}
				
				if (!empty($imagePath) && file_exists ("./".$imagePath)  ) {
					$imagePath = ("./".$imagePath);
					
				}
				if (intval($params['watermark']['int:alpha']) > 0 && intval($params['watermark']['int:alpha']) <= 100) {
					$regedit->setVar("//settings/watermark/alpha", $params['watermark']['int:alpha']);
				}
				
				$regedit->setVar("//settings/watermark/image", $imagePath);
				$regedit->setVar("//settings/watermark/valign", $params['watermark']['select:valign']);
				$regedit->setVar("//settings/watermark/halign", $params['watermark']['select:halign']);
				
				$this->chooseRedirect();
			}
			 
			$params['watermark']['string:image'] = $regedit->getVal("//settings/watermark/image");
			$params['watermark']['int:alpha'] = $regedit->getVal("//settings/watermark/alpha");
			
			$params['watermark']['select:valign'] = array (
					"top" => getLabel ("watermark-valign-top"),
					"bottom" => getLabel ("watermark-valign-bottom"),
					"center" => getLabel ("watermark-valign-center"), 
					"value" => $regedit->getVal("//settings/watermark/valign")
				);
			$params['watermark']['select:halign'] = array (
					"left" => getLabel ("watermark-halign-left"),
					"right" => getLabel ("watermark-halign-right"),
					"center" => getLabel ("watermark-valign-center"),
					"value" => $regedit->getVal("//settings/watermark/halign")
				);
			
			$this->setDataType("settings");
			$this->setActionType("modify");
			
			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}
	
	};