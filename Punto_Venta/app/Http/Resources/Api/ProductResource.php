<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener stock real desde recibido_bodega
        $stockReal = \DB::table('recibido_bodega')
            ->where('producto_id', $this->id)
            ->where('estado_id', 1)
            ->where('cantidad_disponible', '>', 0)
            ->sum('cantidad_disponible');
        
        return [
            'id' => $this->id,
            'sku' => (string) $this->id, // Usar ID como SKU
            'codigo_barra' => $this->codigo_barra,
            'codigo_estatal' => $this->codigo_estatal,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_venta' => (float) ($this->precio1 ?: $this->precio_base),
            'precio_base' => (float) $this->precio_base,
            'precio_costo' => $this->when(
                $request->get('include_cost'),
                (float) $this->ultimo_costo_compra
            ),
            'costo_promedio' => (float) $this->costo_promedio,
            'stock_actual' => (int) $stockReal, // Stock real desde bodega
            'stock_minimo' => 10, // Configurar según negocio
            'stock_maximo' => $this->when(
                $request->get('include_details'),
                9999
            ),
            'activo' => $this->estado_id == 1,
            'estado_id' => $this->estado_id,
            'subcategoria' => $this->when(
                $this->relationLoaded('subcategoria'),
                [
                    'id' => $this->subcategoria?->id,
                    'nombre' => $this->subcategoria?->nombre,
                ]
            ),
            'marca' => $this->when(
                $this->relationLoaded('marca'),
                [
                    'id' => $this->marca?->id,
                    'nombre' => $this->marca?->nombre,
                ]
            ),
            'proveedor' => $this->when(
                $this->relationLoaded('proveedor') && $request->get('include_details'),
                [
                    'id' => $this->proveedor?->id,
                    'nombre' => $this->proveedor?->nombre,
                ]
            ),
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
