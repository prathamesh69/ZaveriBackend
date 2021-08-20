<?php

namespace App\Exceptions;

class BaseException extends \Exception
{
    protected $errorCode;

    public function __construct($message = 'Server error occurred!', int $errorCode = 400, int $code = 400, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorArray()
    {
        return ['code' => $this->errorCode, 'message' => $this->message];
    }
}
