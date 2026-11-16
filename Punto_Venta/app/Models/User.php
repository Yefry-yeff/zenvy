<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'estado_id',
        'roles_id',
        'tienda_id',
        'update_user',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function detalle()
    {
        return $this->hasOne(UserDetalle::class, 'users_id', 'id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tiendas::class, 'tienda_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'roles_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'user_rol', 'user_id', 'rol_id');
    }
}
