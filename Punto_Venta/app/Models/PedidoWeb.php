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
    
    public function rechazar(): void
    {
        $this->update(['estado' => 'rechazado']);
    }
}
