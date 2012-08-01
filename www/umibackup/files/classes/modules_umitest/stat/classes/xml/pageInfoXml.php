<?php

class pageInfoXml extends xmlDecorator
{
    protected function generate($array)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement('statistic');
        $root = $dom->appendChild($element);

        $element = $dom->createElement('sources');
        $sources = $root->appendChild($element);

        foreach ($array['source'] as $val) {
            $element = $dom->createElement('source');
            $this->bind($element, $val);

            $sources->appendChild($element);
        }

        $visits = $root->appendChild($dom->createElement('visits'));
        $visits->appendChild($dom->createElement('abs', $array['visits']['abs']));
        $visits->appendChild($dom->createElement('rel', $array['visits']['rel']));

        $element = $dom->createElement('nexts');
        $nexts = $root->appendChild($element);

        foreach ($array['next'] as $val) {
            $element = $dom->createElement('next');
            $this->bind($element, $val);

            $nexts->appendChild($element);
        }

        $root->appendChild($dom->createElement('entry', $array['entry']['abs']));
        $root->appendChild($dom->createElement('exit', $array['exit']['abs']));
        $root->appendChild($dom->createElement('refuse', $array['refuse']['abs']));

        $profits = $root->appendChild($dom->createElement('profits'));

        foreach (array('direct', 'nonDirect') as $index) {
            $node = $profits->appendChild($dom->createElement($index));
            $node->setAttribute('abs', $array['profit'][$index]['abs']);
            $node->setAttribute('profit', $array['profit'][$index]['profit']);
        }

        return $dom->saveXML();
    }
}

?>