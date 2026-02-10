<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoWeb extends Model
{
    protected $table = 'pedidos_web';
    
    protected $fillable = [
        'numero_pedido',
        'factura_id',
        'estado',
        'cliente_nombre',
        'cliente_email',
        'cliente_telefono',
        'cliente_rtn',
        'cliente_direccion',
        'subtotal',
        'descuento',
        'isv',
        'total',
        'metodo_pago',
        'notas',
        'metadata',
        'procesado_por',
        'fecha_procesado',
        'fecha_facturado',
        'leido',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'leido' => 'boolean',
        'fecha_procesado' => 'datetime',
        'fecha_facturado' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    public function items(): HasMany
    {
        return $this->hasMany(PedidoWebItem::class);
    }
    
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }
    
    public function procesadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }
    
    public function reservas(): HasMany
    {
        return $this->hasMany(ReservaInventario::class);
    }
    
    public function reservasActivas(): HasMany
    {
        return $this->hasMany(ReservaInventario::class)->where('estado', 'activa');
    }
    
    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
    
    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false);
    }
    
    public function scopePendientesNoLeidos($query)
    {
        return $query->where('estado', 'pendiente')
                     ->where('leido', false);
    }
    
    // Métodos de estado
    public function marcarComoLeido(): void
    {
        $this->update(['leido' => true]);
    }
    
    public function marcarComoProcesando(int $userId): void
    {
        $this->update([
            'estado' => 'procesando',
            'procesado_por' => $userId,
            'fecha_procesado' => now(),
        ]);
    }
    
    public function marcarComoFacturado(int $facturaId): void
    {
        $this->update([
            'estado' => 'facturado',
            'factura_id' => $facturaId,
            'fecha_facturado' => now(),
        ]);
    }
    
    public function rechazar(string $motivo = 'Pedido rechazado', ?int $usuarioId = null): void
    {
        // Obtener items antes de liberar para sincronizar con la web
        $items = $this->items()->with('producto')->get();
        
        // Liberar reservas de inventario antes de cambiar el estado
        $reservaService = app(\App\Services\ReservaInventarioService::class);
        $reservaService->liberarReservas($this, $motivo, $usuarioId);
        
        // Sincronizar cambios de inventario con la página web
        $this->sincronizarLiberacionReservas($items);
        
        $this->update(['estado' => 'rechazado']);
    }
    
    /**
     * Sincronizar con la página web después de liberar reservas
     */
    private function sincronizarLiberacionReservas($items): void
    {
        try {
            $syncService = app(\App\Services\WebInventorySyncService::class);
            
            foreach ($items as $item) {
                if (!$item->producto) continue;
                
                // Calcular stock actual después de liberar las reservas
                $stockActual = \DB::table('recibido_bodega')
                    ->where('producto_id', $item->producto_id)
                    ->where('estado_id', 1)
                    ->sum('cantidad_disponible');
                
                // Obtener reservas activas (ya excluye las que se acaban de liberar)
                $reservasActivas = \DB::table('reservas_inventario')
                    ->where('producto_id', $item->producto_id)
                    ->where('estado', 'activa')
                    ->sum('cantidad_reservada');
                
                // Stock disponible = stock total - reservas activas
                $stockDisponible = max(0, $stockActual - $reservasActivas);
                
                // Stock anterior tenía menos disponible (porque las reservas estaban activas)
                $stockAnterior = max(0, $stockActual - $reservasActivas - $item->cantidad);
                
                // Sincronizar con página web
                $syncService->sincronizarCambioStock(
                    $item->producto_id,
                    $item->producto->nombre,
                    (int) $stockAnterior,
                    (int) $stockDisponible,
                    'rechazo_pedido_web',
                    [
                        'cantidad_liberada' => $item->cantidad,
                        'pedido_numero' => $this->numero_pedido,
                        'tipo_operacion' => 'liberacion_reserva'
                    ]
                );
                
                \Log::info('Stock sincronizado con página web por rechazo de pedido', [
                    'pedido_id' => $this->id,
                    'pedido_numero' => $this->numero_pedido,
                    'producto_id' => $item->producto_id,
                    'producto_nombre' => $item->producto->nombre,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockDisponible,
                    'cantidad_liberada' => $item->cantidad
                ]);
            }
        } catch (\Exception $e) {
            // No fallar el rechazo si hay error en la sincronización
            \Log::error('Error al sincronizar liberación de reservas con página web', [
                'pedido_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
