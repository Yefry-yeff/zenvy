<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Marca extends Component
{
    public function render()
    {
        $marcas = \App\Models\Marca::all(['id', 'nombre']);
        return view('livewire.inventario.marca', compact('marcas'));
    }
}
