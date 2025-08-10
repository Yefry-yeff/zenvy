<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista;
    public $componenteId = null;
    public $parametros = [];

    public function mount()
    {
        $this->vista = 'DashboardDinamico';
        $this->componenteId = uniqid();
    }

    protected $listeners = ['cambiarVista'];

    public function cambiarVista($ruta, $parametros = [])
    {
        logger()->info('[Livewire] cambiarVista recibió:', ['ruta' => $ruta, 'parametros' => $parametros]);

        // Si la ruta es 'dashboard', usar el componente DashboardDinamico
        if ($ruta === 'dashboard') {
            $this->vista = 'DashboardDinamico';
        } else {
            $this->vista = $ruta;
        }

        $this->parametros = $parametros;
        $this->componenteId = uniqid();
    }

    public function render()
    {
        return view('livewire.dynamic-content');
    }
}

