<?php

class pageNextXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>