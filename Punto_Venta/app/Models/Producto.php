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
        'unidad_compra',
        'unidad_medida_compra_id',
        'precio1',
        'precio2',
        'precio3',
        'precio4',
        'users_id'
    ];

    // Relationships
    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_compra_id');
    }

    public function unidadMedidaCompra()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_compra_id');
    }

    public function recibidosBodega()
    {
        return $this->hasMany(RecibidoBodega::class, 'producto_id');
    }

    // Static methods for SP operations
    public static function crearProducto($datos)
    {
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            1, // Acción: insertar
            null, // ID (no necesario para insertar)
            $datos['nombre'],
            $datos['descripcion'],
            $datos['isv'],
            $datos['precio_base'],
            $datos['ultimo_costo_compra'],
            $datos['costo_promedio'],
            $datos['codigo_barra'],
            $datos['codigo_estatal'],
            $datos['estado_id'],
            $datos['subcategoria_id'],
            $datos['marca_id'],
            $datos['unidad_compra'],
            $datos['unidad_medida_compra_id'],
            $datos['precio1'],
            $datos['precio2'],
            $datos['precio3'],
            $datos['precio4'],
            $datos['users_id']
        ]);
    }

    public static function actualizarProducto($id, $datos)
    {
        return DB::statement('CALL sp_crud_producto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            2, // Acción: actualizar
            $id,
            $datos['nombre'],
            $datos['descripcion'],
            $datos['isv'],
            $datos['precio_base'],
            $datos['ultimo_costo_compra'],
            $datos['costo_promedio'],
            $datos['codigo_barra'],
            $datos['codigo_estatal'],
            $datos['estado_id'],
            $datos['subcategoria_id'],
            $datos['marca_id'],
            $datos['unidad_compra'],
            $datos['unidad_medida_compra_id'],
            $datos['precio1'],
            $datos['precio2'],
            $datos['precio3'],
            $datos['precio4'],
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
