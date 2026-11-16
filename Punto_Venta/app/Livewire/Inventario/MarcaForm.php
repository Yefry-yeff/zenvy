<?php

namespace App\Livewire\Inventario;

use App\Models\Marca;
use Livewire\Component;

class MarcaForm extends Component
{
    public $marcaId;
    public $form = [
        'nombre' => '',
    ];
    public $mostrarMensaje = false;

    public function mount($id = null)
    {
        if ($id) {
            $this->marcaId = $id;
            $marca = Marca::findOrFail($id);
            $this->form['nombre'] = $marca->nombre;
        } else {
            $this->marcaId = null;
        }
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Marca');
    }

    public function guardar()
    {
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:marca,nombre,' . $this->marcaId,
        ], [
            'form.nombre.required' => 'El nombre de la marca es obligatorio.',
            'form.nombre.unique' => 'Ya existe una marca con ese nombre.'
        ]);

        if ($this->marcaId) {
            // Actualizar marca existente
            $marca = Marca::findOrFail($this->marcaId);
            $marca->update($this->form);
        } else {
            // Crear nueva marca
            $marca = Marca::create($this->form);
            $this->marcaId = $marca->id;
        }

        $this->mostrarMensaje = true;
    }

    public function render()
    {
        return view('livewire.inventario.marca-form');
    }
}
