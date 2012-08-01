<?php

class userStatXml extends xmlDecorator
{
    protected function generate($array)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement('statistic');
        $root = $dom->appendChild($element);

        if (empty($array)) {
            return $dom->saveXML();
        }

        $root->appendChild($dom->createElement('login', $array['login']));
        $root->appendChild($dom->createElement('first_visit', $array['first_visit']));
        $root->appendChild($dom->createElement('os', $array['os']));
        $root->appendChild($dom->createElement('browser', $array['browser']));
        $root->appendChild($dom->createElement('location', $array['location']));
        $root->appendChild($dom->createElement('js_version', $array['js_version']));
        $root->appendChild($dom->createElement('visit_count', $array['visit_count']));

        if (isset($array['source'])) {
            $source = $root->appendChild($dom->createElement('source'));
            $source->setAttribute('type', $array['source']['type']);
            $source->setAttribute('name', $array['source']['name']);
        }

        $root->appendChild($dom->createElement('first_visit_refuse', $array['first_visit_refuse']));

        $element = $dom->createElement('first_path');
        $first_path = $root->appendChild($element);
        foreach ($array['first_path'] as $val) {
            $element = $dom->createElement('page');
            $this->bind($element, $val);

            $first_path->appendChild($element);
        }

        $root->appendChild($dom->createElement('visit_frequency', $array['visit_frequency']));

        $refuse_frequency = $root->appendChild($dom->createElement('refuse_frequency'));
        $refuse_frequency->setAttribute('abs', $array['refuse_frequency']['abs']);
        $refuse_frequency->setAttribute('rel', $array['refuse_frequency']['rel']);

        $element = $dom->createElement('top_pages');
        $top_pages = $root->appendChild($element);
        foreach ($array['top_pages'] as $val) {
            $element = $dom->createElement('page');
            $this->bind($element, $val);

            $top_pages->appendChild($element);
        }

        if (isset($array['last_visit'])) {
            $root->appendChild($dom->createElement('last_visit', $array['last_visit']));
        }

        if (isset($array['last_source'])) {
            $last_source = $root->appendChild($dom->createElement('last_source'));
            $last_source->setAttribute('type', $array['last_source']['type']);
            $last_source->setAttribute('name', $array['last_source']['name']);
        }

        $root->appendChild($dom->createElement('last_visit_refuse', $array['last_visit_refuse']));

        $element = $dom->createElement('last_path');
        $last_path = $root->appendChild($element);
        foreach ($array['last_path'] as $val) {
            $element = $dom->createElement('page');
            $this->bind($element, $val);

            $last_path->appendChild($element);
        }

        $element = $dom->createElement('collected_events');
        $collected_events = $root->appendChild($element);
        foreach ($array['collected_events'] as $val) {
            $element = $dom->createElement('event');
            $this->bind($element, $val);

            $collected_events->appendChild($element);
        }

        $root->appendChild($dom->createElement('profit', $array['profit']));

        $element = $dom->createElement('avg');
        $labels = $root->appendChild($element);
        foreach (array('top'/*, 'collected'*/) as $index) {
            $node = $labels->appendChild($dom->createElement($index . 's'));
            foreach ($array['labels'][$index] as $key => $val) {
                $routine = $dom->createElement($index);
                $this->bind($routine, $val);

                $node->appendChild($routine);
            }
        }

        return $dom->saveXML();
    }
}

?>