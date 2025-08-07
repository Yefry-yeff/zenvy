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
        'isv',
        'precio_base',
        'ultimo_costo_compra',
        'costo_promedio',
        'codigo_barra',
        'codigo_estatal',
        'estado_id',
        'subcategoria_id',
        'marca_id',
        'unidad_medida_venta_id',
        'users_id'
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
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            1, // Acción: crear
            null, // ID (se genera automáticamente)
            $datos['nombre'],
            $datos['descripcion'],
            $datos['isv'],
            $datos['precio_base'],
            $datos['ultimo_costo_compra'] ?? 0,
            $datos['costo_promedio'] ?? 0,
            $datos['codigo_barra'],
            $datos['codigo_estatal'],
            $datos['estado_id'],
            $datos['subcategoria_id'],
            $datos['marca_id'],
            $datos['unidad_medida_venta_id'],
            0, // precio1
            0, // precio2
            0, // precio3
            0, // precio4
            $datos['users_id']
        ]);
    }

    public static function actualizarProducto($id, $datos)
    {
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            2, // Acción: actualizar
            $id,
            $datos['nombre'],
            $datos['descripcion'],
            $datos['isv'],
            $datos['precio_base'],
            $datos['ultimo_costo_compra'] ?? 0,
            $datos['costo_promedio'] ?? 0,
            $datos['codigo_barra'],
            $datos['codigo_estatal'],
            $datos['estado_id'],
            $datos['subcategoria_id'],
            $datos['marca_id'],
            $datos['unidad_medida_venta_id'],
            0, // precio1
            0, // precio2
            0, // precio3
            0, // precio4
            $datos['users_id']
        ]);
    }

    public static function eliminarProducto($id)
    {
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            3, // Acción: eliminar
            $id,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null
        ]);
    }

    public static function consultarDetallado($id)
    {
        return DB::select('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            4, // Acción: consultar detallado
            $id,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null
        ]);
    }
}
