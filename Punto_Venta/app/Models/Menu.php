<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ✅ <--- ESTA LÍNEA ES CLAVE

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = ['txt_comentario', 'icon', 'parent_id', 'route', 'orden'];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('orden');
    }

    public function scopePadres($query)
    {
        return $query->whereNull('parent_id')->orderBy('orden');
    }
}
