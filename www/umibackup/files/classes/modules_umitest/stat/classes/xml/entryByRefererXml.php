<?php

class entryByRefererXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateDetailDynamic($array);
    }
}

?>