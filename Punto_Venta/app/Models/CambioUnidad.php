<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CambioUnidad extends Model
{
    use HasFactory;

    protected $table = 'Cambio_unidades';

    protected $fillable = [
        'cantidad_rebajada',
        'cantidad_convertir',
        'recibido_bodega_id_original',
        'recibido_bodega_id_cambio',
        'users_id'
    ];

    // Fix para Laravel: updated_at en lugar de update_at
    const UPDATED_AT = 'update_at';

    // Relaciones
    public function recibidoBodegaOriginal()
    {
        return $this->belongsTo(RecibidoBodega::class, 'recibido_bodega_id_original');
    }

    public function recibidoBodegaCambio()
    {
        return $this->belongsTo(RecibidoBodega::class, 'recibido_bodega_id_cambio');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
