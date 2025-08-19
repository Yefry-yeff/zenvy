<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'producto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'isv_id',
        'precio_base',
        'descuento_unitario',
        'descuento_tercera',
        'descuento_cuarta',
        'ultimo_costo_compra',
        'costo_promedio',
        'codigo_barra',
        'codigo_estatal',
        'estado_id',
        'subcategoria_id',
        'marca_id',
        'unidad_medida_venta_id',
        'users_id',
        'imagen'
    ];

    // Relationships
    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function isv()
    {
        return $this->belongsTo(Isv::class, 'isv_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    // Relación con compras
    public function compras()
    {
        return $this->belongsToMany(Compra::class, 'compra_has_producto')
                    ->withPivot([
                        'id',
                        'precio',
                        'cantidad_ingresada',
                        'cantidad_sin_asignar',
                        'fecha_expiracion',
                        'sub_total_producto',
                        'isv',
                        'precio_total',
                        'unidad_compra_id'
                    ])
                    ->withTimestamps();
    }

    // Relación con los detalles de compra
    public function compraHasProductos()
    {
        return $this->hasMany(CompraHasProducto::class, 'producto_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_venta_id');
    }

    public function unidadMedidaCompra()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_venta_id');
    }

    public function unidadMedidaVenta()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_venta_id');
    }

    public function recibidosBodega()
    {
        return $this->hasMany(RecibidoBodega::class, 'producto_id');
    }

    // Static methods for SP operations
    public static function crearProducto($datos)
    {
        try {
            return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                1, // Acción: crear
                null, // ID (se genera automáticamente)
                $datos['nombre'] ?? '',
                $datos['descripcion'] ?? '',
                $datos['isv_id'] ?? null,
                $datos['precio_base'] ?? 0,
                $datos['ultimo_costo_compra'] ?? 0,
                $datos['costo_promedio'] ?? 0,
                $datos['codigo_barra'] ?? '',
                $datos['codigo_estatal'] ?? '',
                $datos['estado_id'] ?? 1,
                $datos['subcategoria_id'] ?? null,
                $datos['marca_id'] ?? null,
                $datos['unidad_medida_venta_id'] ?? null,
                0, // precio1
                0, // precio2
                0, // precio3
                0, // precio4
                $datos['users_id'] ?? null,
                $datos['descuento_unitario'] ?? 0,
                $datos['descuento_tercera'] ?? 0, // SP normaliza a 0/1 automáticamente
                $datos['descuento_cuarta'] ?? 0,   // SP normaliza a 0/1 automáticamente
                $datos['imagen'] ?? null // Nuevo parámetro imagen
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en crearProducto: ' . $e->getMessage(), ['datos' => $datos]);
            throw $e;
        }
    }

    public static function actualizarProducto($id, $datos)
    {
        try {
            return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                2, // Acción: actualizar
                $id,
                $datos['nombre'] ?? '',
                $datos['descripcion'] ?? '',
                $datos['isv_id'] ?? null,
                $datos['precio_base'] ?? 0,
                $datos['ultimo_costo_compra'] ?? 0,
                $datos['costo_promedio'] ?? 0,
                $datos['codigo_barra'] ?? '',
                $datos['codigo_estatal'] ?? '',
                $datos['estado_id'] ?? 1,
                $datos['subcategoria_id'] ?? null,
                $datos['marca_id'] ?? null,
                $datos['unidad_medida_venta_id'] ?? null,
                0, // precio1
                0, // precio2
                0, // precio3
                0, // precio4
                $datos['users_id'] ?? null,
                $datos['descuento_unitario'] ?? 0,
                $datos['descuento_tercera'] ?? 0, // SP normaliza a 0/1 automáticamente
                $datos['descuento_cuarta'] ?? 0,   // SP normaliza a 0/1 automáticamente
                $datos['imagen'] ?? null // Nuevo parámetro imagen
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en actualizarProducto: ' . $e->getMessage(), ['id' => $id, 'datos' => $datos]);
            throw $e;
        }
    }

    public static function eliminarProducto($id)
    {
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            3, // Acción: eliminar
            $id,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null
        ]);
    }

    public static function consultarDetallado($id)
    {
        return DB::select('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            4, // Acción: consultar detallado
            $id,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null
        ]);
    }
}
