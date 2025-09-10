<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'factura';

    protected $fillable = [
        'cai_id',
        'tipo_facturacion_id',
        'transaccion_id',
        'numero_factura',
        'numero_secuencia_cai',
        'nombre_cliente',
        'rtn',
        'sub_total',
        'sub_total_grabado',
        'sub_total_exento',
        'isv',
        'total',
        'credito',
        'dias_credito',
        'fecha_emision',
        'fecha_vencimiento',
        'comentario',
        'porc_descuento',
        'monto_descuento',
        'precio_dolar',
        'estado_factura_id',
        'users_id',
        'factura_imagen'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'sub_total' => 'decimal:2',
        'sub_total_grabado' => 'decimal:2',
        'sub_total_exento' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
        'credito' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'precio_dolar' => 'decimal:2'
    ];

    // Relationships
    public function cai()
    {
        return $this->belongsTo(Cai::class);
    }

    public function tipoFacturacion()
    {
        return $this->belongsTo(TipoFacturacion::class);
    }

    public function estadoFactura()
    {
        return $this->belongsTo(EstadoFactura::class);
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'factura_has_producto')
                    ->withPivot([
                        'seccion_id',
                        'unidad_medida_id',
                        'indice',
                        'numero_unidades_resta_inventario',
                        'unidades_nota_credito_resta_inventario',
                        'resta_inventario_total',
                        'precio_unidad',
                        'cantidad',
                        'subtotal',
                        'isv',
                        'total',
                        'idPrecioSeleccionado',
                        'precio_seleccionado'
                    ]);
    }

    public function pagos()
    {
        return $this->hasMany(FacturaHasPago::class);
    }

    public function detallesLote()
    {
        return $this->hasMany(DetalleFacturaLote::class);
    }

    // Método para validar stock en bodega principal
    public static function validarStockBodegaPrincipal($productoId, $cantidadSolicitada, $tiendaId)
    {
        // Obtener la bodega principal de la tienda
        $bodegaPrincipal = Bodega::where('tienda_id', $tiendaId)
                                ->where('principal', 1)
                                ->where('estado_id', 1)
                                ->first();

        if (!$bodegaPrincipal) {
            return [
                'valido' => false,
                'mensaje' => 'No se encontró bodega principal para esta tienda'
            ];
        }

        // Sumar el stock total del producto en todas las secciones de la bodega principal
        $stockTotal = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
            ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
            ->where('seg.bodega_id', $bodegaPrincipal->id)
            ->where('rb.producto_id', $productoId)
            ->where('rb.estado_id', 1)
            ->sum('rb.cantidad_disponible');

        if ($stockTotal < $cantidadSolicitada) {
            return [
                'valido' => false,
                'mensaje' => "Stock insuficiente en bodega principal. Disponible: {$stockTotal}, Solicitado: {$cantidadSolicitada}",
                'stock_disponible' => $stockTotal
            ];
        }

        return [
            'valido' => true,
            'stock_disponible' => $stockTotal
        ];
    }
}
