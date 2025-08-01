<?php

namespace App\Livewire\Gestion;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Cai extends Component
{
     public $form = [
        'id' => null,
        'cai' => '',
        'fecha_limite_emision' => '',
        'fecha_solicitud' => '',
        'punto_emision' => '',
        'tipo_documento_fiscal_id' => '',
        'cantidad_solicitada' => '',
        'cantidad_otorgada' => '',
        'rango_inicio' => '',
        'rango_final' => '',
        'tienda_id' => '',
        'users_registro_id' => '',
        'estado_id' => '',
        'created_at' => '',
        'updated_at' => '',
    ];
    public $modalAbierto = false;

    public $modalCrearAbierto = false;
    public $nuevaMarcaNombre = '';

    public $modalEliminarAbierto = false;
    public $marcaAEliminar = null;
    public function render()
    {
        $cai = DB::SELECT(
            "
            SELECT
                A.id,
                A.cai,
                A.fecha_limite_emision,
                A.fecha_solicitud,
                A.punto_emision,
                B.nombre AS 'tipo_documento_fiscal',
                A.cantidad_solicitada,
                A.cantidad_otorgada,
                A.rango_inicio,
                A.rango_final,
                D.denominacion_social,
                C.name AS 'users_registro',
                A.estado_id,
                A.created_at,
                A.updated_at

            FROM cai A
            inner join tipo_documento_fiscal B ON A.tipo_documento_fiscal_id = B.id
            inner join users C ON C.id = A.users_registro_id
            inner join tienda D ON D.id = A.tienda_id
            "
        );

        return view('livewire.gestion.cai', compact('cai'));
    }


}
