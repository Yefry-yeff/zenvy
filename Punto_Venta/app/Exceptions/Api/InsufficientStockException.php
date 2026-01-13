<?php

namespace App\Exceptions\Api;

use Exception;

class InsufficientStockException extends Exception
{
    protected $message = 'Stock insuficiente';
    protected $code = 422;
    protected array $items = [];

    public function __construct(array $items)
    {
        $this->items = $items;
        $this->message = 'Stock insuficiente para completar la operación';
        
        parent::__construct($this->message, $this->code);
    }

    /**
     * Obtener los detalles de los items con stock insuficiente
     */
    public function getDetails(): array
    {
        return [
            'items' => $this->items
        ];
    }

    /**
     * Obtener los items con stock insuficiente
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
