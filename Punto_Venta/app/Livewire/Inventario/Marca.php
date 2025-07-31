<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Marca extends Component
{
    public $form = [
        'id' => null,
        'nombre' => '',
    ];
    public $modalAbierto = false;
    public $modalCrearAbierto = false;
    public $nuevaMarcaNombre = '';

    public function render()
    {
        $marcas = \App\Models\Marca::all(['id', 'nombre']);
        return view('livewire.inventario.marca', compact('marcas'));
    }

    public function editar($id)
    {
        $marca = \App\Models\Marca::findOrFail($id);
        $this->form['id'] = $marca->id;
        $this->form['nombre'] = $marca->nombre;
        $this->modalAbierto = true;
    }

    public function guardar()
    {
        $marca = \App\Models\Marca::findOrFail($this->form['id']);
        $marca->nombre = $this->form['nombre'];
        $marca->save();
        $this->modalAbierto = false;
        session()->flash('mensaje', 'Marca actualizada correctamente.');
    }

    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->nuevaMarcaNombre = '';
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function crearMarca()
    {
        $this->validate([
            'nuevaMarcaNombre' => 'required|string|max:255|unique:marca,nombre',
        ], [
            'nuevaMarcaNombre.unique' => 'Ya existe una marca con ese nombre.'
        ]);
        \App\Models\Marca::create([
            'nombre' => $this->nuevaMarcaNombre,
            'created_at' => now(),
        ]);
        $this->cerrarModalCrear();
        $this->dispatch('marcaCreada');
        session()->flash('mensaje', 'Marca creada exitosamente.');
    }
}
