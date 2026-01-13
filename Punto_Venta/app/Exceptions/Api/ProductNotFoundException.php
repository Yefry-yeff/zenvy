<?php

namespace App\Exceptions\Api;

use Exception;

class ProductNotFoundException extends Exception
{
    protected $message = 'Producto no encontrado';
    protected $code = 404;

    public function __construct(string $message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        
        parent::__construct($this->message, $this->code);
    }
}
