<?php

class pathsXml extends xmlDecorator
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

        $element = $dom->createElement('paths');
        $paths = $root->appendChild($element);

        foreach ($array['path'] as $val) {
            $path = $dom->createElement('path');
            $path->setAttribute('path', $val);

            $paths->appendChild($path);
        }

        return $dom->saveXML();
    }
}

?>