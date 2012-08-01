<?php

abstract class xmlDecorator
{
    protected $decorated;
    public function __construct($object)
    {
        $this->decorated = $object;
    }

    public function get()
    {
        $array = $this->decorated->get();

//        echo htmlspecialchars($this->generate($array));

        return $array;
    }

    abstract protected function generate($array);

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->decorated, $name), $args);
    }

    protected function bind($node, $array)
    {
        foreach ($array as $key => $val) {
            $node->setAttribute($key, $val);
        }
    }

    protected function generateFlat($array)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement('statistic');
        $root = $dom->appendChild($element);

        foreach ($array as $val) {
            $data = $dom->createElement('data');
            $this->bind($data, $val);

            $root->appendChild($data);
        }

        return $dom->saveXML();
    }

    protected function generateDetailDynamic($array)
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

        $element = $dom->createElement('dynamics');
        $dynamics = $root->appendChild($element);

        foreach ($array['dynamic'] as $val) {
            $dynamic = $dom->createElement('dynamic');
            $this->bind($dynamic, $val);

            $dynamics->appendChild($dynamic);
        }

        return $dom->saveXML();
    }
}

?>