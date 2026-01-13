<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se hace en el middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'external_order_id' => 'required|string|max:100',
            
            // Cliente
            'cliente' => 'required|array',
            'cliente.documento' => 'nullable|string|max:50',
            'cliente.nombre' => 'required|string|max:255',
            'cliente.email' => 'nullable|email|max:255',
            'cliente.telefono' => 'nullable|string|max:50',
            'cliente.direccion' => 'nullable|string|max:500',
            
            // Items
            'items' => 'required|array|min:1|max:100',
            'items.*.sku' => 'required|string|max:100',
            'items.*.cantidad' => 'required|integer|min:1|max:10000',
            'items.*.precio_unitario' => 'required|numeric|min:0|max:999999999',
            
            // Totales
            'subtotal' => 'required|numeric|min:0|max:999999999',
            'descuento' => 'nullable|numeric|min:0|max:999999999',
            'impuestos' => 'nullable|numeric|min:0|max:999999999',
            'total' => 'required|numeric|min:0|max:999999999',
            
            // Forma de pago
            'forma_pago' => 'nullable|string|max:50',
            
            // Metadatos opcionales
            'metadatos' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'external_order_id.required' => 'El ID del pedido externo es requerido',
            'cliente.required' => 'Los datos del cliente son requeridos',
            'cliente.nombre.required' => 'El nombre del cliente es requerido',
            'items.required' => 'Debe incluir al menos un producto',
            'items.min' => 'Debe incluir al menos un producto',
            'items.*.sku.required' => 'El SKU del producto es requerido',
            'items.*.cantidad.required' => 'La cantidad es requerida',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'items.*.precio_unitario.required' => 'El precio unitario es requerido',
            'subtotal.required' => 'El subtotal es requerido',
            'total.required' => 'El total es requerido',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Los datos enviados no son válidos',
                    'details' => $validator->errors()
                ]
            ], 422)
        );
    }
}
