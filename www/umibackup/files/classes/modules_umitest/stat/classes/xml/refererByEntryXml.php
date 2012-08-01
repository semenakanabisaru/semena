<?php

class refererByEntryXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateDetailDynamic($array);
    }
}

?>