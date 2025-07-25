<?php

namespace App\Livewire\Configuracion;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Usuarios extends Component
{
    public $usuarios = [];

    public function mount()
    {
        $this->usuarios = DB::table('users as u')
            ->join('user_rol as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->join('mi_actor as a', 'a.id', '=', 'u.id_actor')
            ->select(
                'u.id',
                'a.txt_primer_nombre',
                'a.txt_segundo_nombre',
                'a.txt_primer_apellido',
                'a.txt_segundo_apellido',
                DB::raw("CONCAT_WS(' ', a.txt_primer_nombre, a.txt_segundo_nombre, a.txt_primer_apellido, a.txt_segundo_apellido) as nombre"),
                'u.email as correo',
                'a.txt_direccion as direccion',
                'a.telefono',
                'a.estado',
                'r.txt_nombre as rol'
            )
            ->get();
    }

public function irADetalle($id)
{
    session(['usuario_editar_id' => $id]);
    $this->dispatch('cambiarVista', 'Configuracion.usuariosform');
}
public function editar($id)
{
    session(['usuario_editar_id' => $id]);
    $this->dispatch('cambiarVista', 'Configuracion.usuariosform');
}
    public function render()
    {
        return view('livewire.configuracion.usuarios');
    }
}
