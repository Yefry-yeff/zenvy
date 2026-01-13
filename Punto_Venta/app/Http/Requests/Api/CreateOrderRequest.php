<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_number' => 'nullable|string|max:50|unique:pedidos_web,numero_pedido',
            'customer_name' => 'required|string|max:200',
            'customer_email' => 'nullable|email|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'customer_rtn' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es requerido',
            'items.required' => 'Debe incluir al menos un producto',
            'items.*.sku.required' => 'El SKU del producto es requerido',
            'items.*.quantity.required' => 'La cantidad es requerida',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0',
            'items.*.price.required' => 'El precio es requerido',
        ];
    }
}
