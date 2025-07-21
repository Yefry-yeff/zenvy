<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista = 'livewire.dashboard';

    protected $listeners = ['cambiarVista'];

    public function cambiarVista($ruta)
    {
        $this->vista = $ruta;
    }

    public function render()
    {
        return view('livewire.dynamic-content');
    }
}

