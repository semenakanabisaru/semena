<?php
	abstract class __rss_data {

		public function rss() {
			$element_id = (int) getRequest('param0');

			if(defined("VIA_HTTP_SCHEME")) {
				throw new publicException("Not avalibable via scheme");
			}

			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "%data_feed_nofeed%";
			}

			if(!$this->checkIfFeedable($element_id)) {
				return "%data_feed_wrong%";
			}

			$xslPath = "xsl/rss.xsl";

			$this->generateFeed($element_id, $xslPath);
		}


		public function atom() {
			$element_id = (int) getRequest('param0');
			
			if(defined("VIA_HTTP_SCHEME")) {
				throw new publicException("Not avalibable via scheme");
			}
			
			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "%data_feed_nofeed%";
			}

			if(!$this->checkIfFeedable($element_id)) {
				return "%data_feed_wrong%";
			}

			$xslPath = "xsl/atom.xsl";

			$this->generateFeed($element_id, $xslPath);
		}

		public function rssByCustomIds($iRootId = NULL, $vFeedIds = NULL, $sXslTpl = NULL) {
			if (is_null($iRootId)) { // RETURN
				return $this->rss();
			} else {
				if (is_null($sXslTpl)) $sXslTpl = "xsl/rss.xsl";
				return $this->feedByCustomIds($iRootId, $vFeedIds, $sXslTpl);
			}
		}
		public function atomByCustomIds($iRootId = NULL, $vFeedIds = NULL, $sXslTpl = NULL) {
			if (is_null($iRootId)) { // RETURN
				return $this->rss();
			} else {
				if (is_null($sXslTpl)) $sXslTpl = "xsl/atom.xsl";
				return $this->feedByCustomIds($iRootId, $vFeedIds, $sXslTpl);
			}
		}
		public function feedByCustomIds($iRootId, $vFeedIds, $sXslPath) {
			if (!umiHierarchy::getInstance()->isExists($iRootId)) { // RETURN
				return "%data_feed_nofeed%";
			//} elseif (!$this->checkIfFeedable($iRootId)) { // RETURN
			//	return "%data_feed_wrong%";
			} else {
				$arrFeedIds = array();
				if (is_string($vFeedIds)) {
					$arrFeedIds = preg_split("/[^\d]/is", $vFeedIds);
				} elseif (is_numeric($vValue)) {
					$arrFeedIds = array(intval($vFeedIds));
				} elseif (!is_array($vFeedIds)) {
					$arrFeedIds = array();
				}
				$arrFeedIds = array_map('intval', $arrFeedIds);
				//
				$result = array();
				$result[] = $iRootId;
				foreach ($arrFeedIds as $iNextId) {
					if ($iNextId && $oNextElement = umiHierarchy::getInstance()->getElement($iNextId)) {
						$iNextParent = intval($oNextElement->getRel());
						if ($this->checkIfFeedable($iNextParent)) {
							$result[] = $iNextId;
						}
					}
				}
				//
				$t = new umiXmlExporter();
				$t->setElements($result);
				$t->run();
				$src = $t->getResultFile();
				$xmldata = DomDocument::loadXML($src);
//echo "[".($xmldata->asXML())."]";
				$xslt = new xsltProcessor;
				$xslt->importStyleSheet(DomDocument::load($sXslPath));
				$resultXml = $xslt->transformToXML($xmldata);
//echo "[".$resultXml."]";
				header("Content-type: text/xml; charset=utf-8");
				$this->flush($resultXml);
			}
		}


		public function generateFeed($element_id, $xslPath) {
			$hierarchy = umiHierarchy::getInstance();

			$rss_per_page = (int) regedit::getInstance()->getVal("//modules/news/rss_per_page");
			$rss_per_page = $rss_per_page > 0 ? $rss_per_page : 10;

			$sel = new umiSelection();
			$sel->addLimit($rss_per_page);
			$sel->addHierarchyFilter($element_id);

			if($type_id = $hierarchy->getDominantTypeId($element_id)) {
				 $type = umiObjectTypesCollection::getInstance()->getType($type_id);
				 if($type instanceof umiObjectType) {
					$field_id = $type->getFieldId("publish_time");
					$sel->setOrderByProperty($field_id, false);
				 }
			}
			

			$result = Array($element_id);
			$result = array_merge($result, umiSelectionsParser::runSelection($sel));

			$t = new umiXmlExporter();
			$t->setElements($result);
			$t->run();
			$src = $t->getResultFile();

			$xmldata = DomDocument::loadXML($src);

			$xslt = new xsltProcessor;
			$xslt->importStyleSheet(DomDocument::load($xslPath));
			$resultXml = $xslt->transformToXML($xmldata);

			$buffer = outputBuffer::current();
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($resultXml);
			$buffer->end();
		}


		public function getRssMeta($element_id = false, $title_prefix = "") {
			$element_id = $this->analyzeRequiredPath($element_id);

			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "";
			}

			if(!$this->checkIfFeedable($element_id)) {
				return "";
			}

			$element = umiHierarchy::getInstance()->getElement($element_id);
			$element_title = $title_prefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/rss/{$element_id}/\" title=\"{$element_title}\" />";
		}


		public function getRssMetaByPath($path, $title_prefix = "") {
			if($element_id = umiHierarchy::getInstance()->getIdByPath($path)) {
				return $this->getRssMeta($element_id, $title_prefix);
			} else {
				return "";
			}
		}


		public function getAtomMeta($element_id = false, $title_prefix = "") {
			$element_id = $this->analyzeRequiredPath($element_id);

			if(!umiHierarchy::getInstance()->isExists($element_id)) {
				return "";
			}

			if(!$this->checkIfFeedable($element_id)) {
				return "";
			}
			
			$element = umiHierarchy::getInstance()->getElement($element_id);
			$element_title = $title_prefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/atom/{$element_id}/\" title=\"{$element_title}\" />";
		}

		public function getAtomMetaByPath($path, $title_prefix = "") {
			if($element_id = umiHierarchy::getInstance()->getIdByPath($path)) {
				return $this->getAtomMeta($element_id, $title_prefix);
			} else {
				return "";
			}
		}


		public function checkIfFeedable($element_id) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return false;

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());

			$module = $hierarchy_type->getName();
			$method = $hierarchy_type->getExt();

			foreach($this->alowed_source as $allowed) {
				if($module == $allowed[0] && $method == $allowed[1]) {
					return true;
				}
			}
			return false;
		}
	};
?>