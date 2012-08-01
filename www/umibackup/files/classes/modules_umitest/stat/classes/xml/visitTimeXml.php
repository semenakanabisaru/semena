<?php

class visitTimeXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateDetailDynamic($array);
    }
}

?>