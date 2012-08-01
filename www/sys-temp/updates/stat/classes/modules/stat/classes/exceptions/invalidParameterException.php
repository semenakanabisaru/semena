<?php

class invalidParameterException extends baseException
{
    public function __construct($message, $param)
    {
        $message = $message . ' (' .parent::convertToString($param) . ')';
        parent::__construct($message, $code);
        $this->setName('Invalid Parameter');
    }

}

?>