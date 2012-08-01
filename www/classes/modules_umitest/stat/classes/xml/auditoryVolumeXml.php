<?php

class auditoryVolumeXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>