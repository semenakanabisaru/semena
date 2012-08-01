<?php

class hostsCommonHoursXml extends xmlDecorator
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

        foreach (array('routine', 'weekend') as $index) {
            $node = $avg->appendChild($dom->createElement($index . 's'));
            if (isset($array['avg'][$index]) && is_array($array['avg'][$index])) {
                foreach ($array['avg'][$index] as $key => $val) {
                    $routine = $dom->createElement($index);
                    $routine->setAttribute('hour', $key);
                    $routine->setAttribute('cnt', $val);

                    $node->appendChild($routine);
                }
            }
        }

        return $dom->saveXML();
    }
}

?>