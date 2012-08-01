<?php

class hostsCommonXml extends xmlDecorator
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

        $element = $dom->createElement('avg');
        $avg = $root->appendChild($element);

        $avg->appendChild($dom->createElement('routine', $array['avg']['routine']));
        $avg->appendChild($dom->createElement('weekend', $array['avg']['weekend']));

        return $dom->saveXML();
    }
}

?>