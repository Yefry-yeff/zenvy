<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista = 'dashboard';

    protected $listeners = ['cambiarVista'];

    public function cambiarVista($ruta)
    {
        logger()->info('[Livewire] cambiarVista recibió:', ['ruta' => $ruta]);
         $this->vista = $ruta;
    }

    public function render()
    {
        return view('livewire.dynamic-content');
    }

    public function prueba()
{
    logger()->info('Livewire SÍ responde al botón');
}
}

