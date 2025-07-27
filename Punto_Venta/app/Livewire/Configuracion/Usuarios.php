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
            ->leftJoin('user_detalle as d', 'd.users_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'u.roles_id')
            ->select(
                'u.id',
                'd.primer_nombre',
                'd.segundo_nombre',
                'd.primer_apellido',
                'd.segundo_apellido',
                DB::raw("CONCAT_WS(' ', d.primer_nombre, d.segundo_nombre, d.primer_apellido, d.segundo_apellido) as nombre"),
                'u.email as correo',
                'd.direccion',
                'd.telefono',
                'd.estado_id',
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
