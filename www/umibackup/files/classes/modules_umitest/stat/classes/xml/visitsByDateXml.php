<?php

class visitsByDateXml extends xmlDecorator
{
    protected function generate($array)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement('statistic');
        $root = $dom->appendChild($element);

        $element = $dom->createElement('users');
        $details = $root->appendChild($element);

        foreach ($array as $val) {
            $detail = $dom->createElement('user', $val['user_id']);

            $details->appendChild($detail);
        }
/*
        $element = $dom->createElement('avg');
        $avg = $root->appendChild($element);

        $avg->appendChild($dom->createElement('routine', $array['avg']['routine']));
        $avg->appendChild($dom->createElement('weekend', $array['avg']['weekend']));
*/
        return $dom->saveXML();
    }
}

?>