<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Isv extends Model
{
    use HasFactory;

    protected $table = 'isv';

    // Configurar nombres personalizados de timestamps
    const UPDATED_AT = 'update_at';

    protected $fillable = [
        'cantidad',
        'estado_id'
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'isv_id');
    }
}
