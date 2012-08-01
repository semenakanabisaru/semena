<?php
	class RSSFeed implements iRSSFeed {
		private $url,
			$xml,
			$items;

		public function __construct($url) {
			$this->url = $url;
		}

		public function loadContent() {
			$cont = umiRemoteFileGetter::get($this->url);
			if(!$cont) {
				trigger_error("Can't load \"{$url}\" RSS.", E_USER_WARNING);
				return false;
			}
			if (function_exists('mb_detect_encoding')) {
				if (mb_detect_encoding($cont, "UTF-8, ISO-8859-1, GBK, CP1251") != "UTF-8") {
					$cont = iconv ("CP1251", "UTF-8//IGNORE", $cont);
					$cont = preg_replace("/(encoding=\"windows-1251\")/i", "encoding=\"UTF-8\"", $cont);
				}
			}
			$this->xml = simplexml_load_string($cont);
		}

		public function loadRSS() {
			foreach($this->xml->channel->item as $xml_item) {
				$item = new RSSItem();
				$item->setTitle($xml_item->title);
				$item->setContent($xml_item->description);
				if ($xml_item->pubDate) {
					$item->setDate($xml_item->pubDate);
				}else {
					$item->setDate(date("Y-m-d H:i"));
				}
				$item->setUrl($xml_item->link);

				$this->items[] = $item;
			}
		}

		public function loadAtom() {
			foreach($this->xml as $tag => $xml_item) {
				if($tag != "entry") continue;
				
				if($xml_item->content) {
					$content = $xml_item->content;
				} else {
					$content = $xml_item->summary;
				}

				$item = new RSSItem();
				$item->setTitle($xml_item->title);
				$item->setContent($content);
				$item->setDate($xml_item->published);
				$item->setUrl($xml_item->link['href']);

				$this->items[] = $item;
			}

		}

		public function returnItems() {
			return $this->items;
		}
	}
?>
