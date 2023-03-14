<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    public function __construct($codeResponse, $info = '')
    {
        list($code, $message) = $codeResponse;
        parent::__construct($info ?: $message, $code);
    }

}
