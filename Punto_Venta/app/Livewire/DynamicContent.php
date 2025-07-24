<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista;
    public $componenteId = null;

    public function mount()
{
    $this->vista = 'dashboard';
    $this->componenteId = uniqid();
}
    protected $listeners = ['cambiarVista'];

    public function cambiarVista($ruta)
    {
        logger()->info('[Livewire] cambiarVista recibió:', ['ruta' => $ruta]);
         $this->vista = $ruta;
         $this->componenteId = uniqid();
    }

    public function render()
    {
        return view('livewire.dynamic-content');
    }


}

