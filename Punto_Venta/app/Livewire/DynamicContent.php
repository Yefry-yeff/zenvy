<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista = 'livewire.inventario.productos';

    protected $listeners = ['cambiarVista'];

    public function cambiarVista($ruta)
    {
        logger()->info('[Livewire] cambiarVista recibió:', ['ruta' => $ruta]);
        $this->vista = 'livewire.' . str_replace('.', '.', $ruta);
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

