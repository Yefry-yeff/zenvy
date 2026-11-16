<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompraProductosImport implements ToCollection, WithHeadingRow
{
    protected $errores = [];
    protected $productosImportados = [];
    protected $compraId;

    public function __construct($compraId = null)
    {
        $this->compraId = $compraId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $fila = $index + 2; // +2 porque la fila 1 es el header y empezamos en 0
            
            try {
                // Validar datos obligatorios
                if (empty($row['producto_id'])) {
                    $this->errores[] = "Fila {$fila}: El producto_id es obligatorio";
                    continue;
                }

                if (empty($row['cantidad']) || $row['cantidad'] <= 0) {
                    $this->errores[] = "Fila {$fila}: La cantidad debe ser mayor a 0";
                    continue;
                }

                if (empty($row['precio_unitario']) || $row['precio_unitario'] <= 0) {
                    $this->errores[] = "Fila {$fila}: El precio unitario debe ser mayor a 0";
                    continue;
                }

                if (empty($row['unidad_medida_id'])) {
                    $this->errores[] = "Fila {$fila}: La unidad_medida_id es obligatoria";
                    continue;
                }

                // Verificar que el producto existe
                $producto = Producto::find($row['producto_id']);
                if (!$producto) {
                    $this->errores[] = "Fila {$fila}: El producto con ID {$row['producto_id']} no existe";
                    continue;
                }

                // Verificar que la unidad de medida existe
                $unidadMedida = UnidadMedida::find($row['unidad_medida_id']);
                if (!$unidadMedida) {
                    $this->errores[] = "Fila {$fila}: La unidad de medida con ID {$row['unidad_medida_id']} no existe";
                    continue;
                }

                // Calcular subtotal
                $cantidad = floatval($row['cantidad']);
                $precioUnitario = floatval($row['precio_unitario']);
                $subtotal = $cantidad * $precioUnitario;

                // Agregar producto procesado
                $this->productosImportados[] = [
                    'producto_id' => $row['producto_id'],
                    'nombre' => $row['nombre'] ?? $producto->nombre,
                    'cantidad' => $cantidad,
                    'unidades' => $row['unidades'] ?? 0,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal,
                    'unidad_medida_id' => $row['unidad_medida_id'],
                    'unidad_medida_nombre' => $unidadMedida->nombre,
                    'seccion_id' => $row['seccion_id'] ?? null,
                    'fila_excel' => $fila
                ];

            } catch (\Exception $e) {
                $this->errores[] = "Fila {$fila}: Error al procesar - " . $e->getMessage();
            }
        }
    }

    public function getErrores()
    {
        return $this->errores;
    }

    public function getProductosImportados()
    {
        return $this->productosImportados;
    }

    public function tieneErrores()
    {
        return count($this->errores) > 0;
    }
}
