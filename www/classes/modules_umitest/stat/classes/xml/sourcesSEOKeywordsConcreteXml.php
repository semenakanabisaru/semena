<?php

class sourcesSEOKeywordsConcreteXml extends xmlDecorator
{
    protected function generate($array)
    {
        return $this->generateFlat($array);
    }
}

?>