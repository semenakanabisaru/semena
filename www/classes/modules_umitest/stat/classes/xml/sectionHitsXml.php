<?php

class sectionHitsXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>