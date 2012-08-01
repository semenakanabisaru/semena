<?php

class cityStatXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateDetailDynamic($array);
    }
}

?>