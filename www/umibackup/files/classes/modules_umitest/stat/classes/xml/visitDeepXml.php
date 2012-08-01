<?php

class visitDeepXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateDetailDynamic($array);
    }
}

?>