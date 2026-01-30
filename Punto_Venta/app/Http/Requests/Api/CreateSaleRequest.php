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
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|regex:/^\+504[0-9]{8}$/|max:13',
            'customer_rtn' => 'nullable|string|max:50',
            
            // Items - Ahora recibe product_id en lugar de sku
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:producto,id',
            'items.*.quantity' => 'required|integer|min:1|max:10000',
            'items.*.price' => 'required|numeric|min:0|max:999999999',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            
            // Totales
            'subtotal' => 'required|numeric|min:0|max:999999999',
            'discount' => 'nullable|numeric|min:0|max:999999999',
            'shipping_cost' => 'nullable|numeric|min:0|max:999999',
            'tax' => 'required|numeric|min:0|max:999999999',
            'total' => 'required|numeric|min:0|max:999999999',
            
            // Datos de entrega
            'delivery_type' => 'required|in:retiro_tienda,domicilio,recoger',
            'delivery_address' => 'required_if:delivery_type,domicilio|nullable|string|max:500',
            
            // Método de pago y notas
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            
            // Información de transferencia bancaria (para payment_method = 'Transferencia Bancaria')
            'transfer_info' => 'nullable|array',
            'transfer_info.account_bank' => 'nullable|string|max:100',
            'transfer_info.account_type' => 'nullable|string|max:50',
            'transfer_info.account_number' => 'nullable|string|max:50',
            'transfer_info.account_holder' => 'nullable|string|max:200',
            'transfer_info.transfer_date' => 'nullable|date',
            
            // ID de orden externo (opcional, para tracking)
            'external_order_id' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es requerido',
            'customer_email.required' => 'El email del cliente es requerido',
            'customer_email.email' => 'El email debe ser válido',
            'customer_phone.required' => 'El teléfono del cliente es requerido',
            'customer_phone.regex' => 'El teléfono debe estar en formato +504XXXXXXXX',
            'items.required' => 'Debe incluir al menos un producto',
            'items.min' => 'Debe incluir al menos un producto',
            'items.*.product_id.required' => 'El ID del producto es requerido',
            'items.*.product_id.exists' => 'El producto no existe',
            'items.*.quantity.required' => 'La cantidad es requerida',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0',
            'items.*.price.required' => 'El precio es requerido',
            'subtotal.required' => 'El subtotal es requerido',
            'tax.required' => 'El ISV es requerido',
            'total.required' => 'El total es requerido',
            'delivery_type.required' => 'El tipo de entrega es requerido',
            'delivery_type.in' => 'El tipo de entrega debe ser "retiro_tienda" o "domicilio"',
            'delivery_address.required_if' => 'La dirección de envío es requerida para entregas a domicilio',
            'payment_method.required' => 'El método de pago es requerido',
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
