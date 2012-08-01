<?php

class sourcesSummaryXml extends xmlDecorator
{
    protected function generate($array)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement('statistic');
        $root = $dom->appendChild($element);

        $element = $dom->createElement('details');
        $details = $root->appendChild($element);

        foreach ($array['detail'] as $val) {
            $detail = $dom->createElement('detail');
            $this->bind($detail, $val);

            $details->appendChild($detail);
        }

        $element = $dom->createElement('segments');
        $segments = $root->appendChild($element);

        foreach ($array['segments'] as $val) {
            $segment = $dom->createElement('segment');
            $this->bind($segment, $val);

            $segments->appendChild($segment);
        }

        return $dom->saveXML();
    }
}

?>