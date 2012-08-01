<?php

class sourcesPagesXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>