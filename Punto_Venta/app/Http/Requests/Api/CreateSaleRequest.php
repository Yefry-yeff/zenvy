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
            // Cliente
            'customer_name' => 'required|string|max:255',
            'customer_rtn' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:500',
            
            // Items
            'items' => 'required|array|min:1|max:100',
            'items.*.sku' => 'required|string|max:100',
            'items.*.quantity' => 'required|integer|min:1|max:10000',
            'items.*.price' => 'required|numeric|min:0|max:999999999',
            
            // Totales
            'subtotal' => 'required|numeric|min:0|max:999999999',
            'discount' => 'nullable|numeric|min:0|max:999999999',
            'tax' => 'required|numeric|min:0|max:999999999',
            'total' => 'required|numeric|min:0|max:999999999',
            
            // Opcionales
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es requerido',
            'items.required' => 'Debe incluir al menos un producto',
            'items.min' => 'Debe incluir al menos un producto',
            'items.*.sku.required' => 'El SKU del producto es requerido',
            'items.*.quantity.required' => 'La cantidad es requerida',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0',
            'items.*.price.required' => 'El precio es requerido',
            'subtotal.required' => 'El subtotal es requerido',
            'tax.required' => 'El ISV es requerido',
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
