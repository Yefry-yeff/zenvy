<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'factura_id' => $this->id,
            'numero_factura' => $this->numero_factura,
            'external_order_id' => $this->external_order_id,
            'estado' => $this->estado,
            'cliente' => [
                'documento' => $this->cliente_documento,
                'nombre' => $this->cliente_nombre,
                'email' => $this->cliente_email,
                'telefono' => $this->cliente_telefono,
                'direccion' => $this->cliente_direccion,
            ],
            'items' => $this->when(
                $this->relationLoaded('items'),
                SaleItemResource::collection($this->items)
            ),
            'subtotal' => (float) $this->subtotal,
            'descuento' => (float) ($this->descuento ?? 0),
            'impuestos' => (float) ($this->impuestos ?? 0),
            'total' => (float) $this->total,
            'forma_pago' => $this->forma_pago,
            'metadatos' => $this->when(
                !empty($this->metadatos),
                json_decode($this->metadatos, true)
            ),
            'motivo_anulacion' => $this->when(
                $this->estado === 'anulada',
                $this->motivo_anulacion
            ),
            'anulada_at' => $this->when(
                $this->anulada_at,
                $this->anulada_at?->toIso8601String()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
