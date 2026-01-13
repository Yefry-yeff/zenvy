<?php

namespace App\Exceptions\Api;

use Exception;

class ApiAuthException extends Exception
{
    protected $message = 'Error de autenticación';
    protected $code = 401;

    public function __construct(string $message = null, int $code = 401)
    {
        if ($message) {
            $this->message = $message;
        }
        
        $this->code = $code;
        
        parent::__construct($this->message, $this->code);
    }
}
