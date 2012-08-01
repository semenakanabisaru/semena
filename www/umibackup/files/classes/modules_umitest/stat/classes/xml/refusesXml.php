<?php

class refusesXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>