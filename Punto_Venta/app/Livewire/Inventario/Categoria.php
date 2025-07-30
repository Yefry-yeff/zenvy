<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Categoria extends Component
{
       public function render()
    {
        $categorias = \App\Models\Categoria::all(['id', 'nombre']);
        return view('livewire.inventario.categoria', compact('categorias'));
    }
}


