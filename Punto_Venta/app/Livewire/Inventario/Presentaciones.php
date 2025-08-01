<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Presentaciones extends Component
{
    public $form = [
        'id' => null,
        'unidad' => '',
        'nombre' => '',
        'simbolo' => '',
    ];
    public $modalAbierto = false;
    
    public $modalCrearAbierto = false;
    public $nuevaUnidad = '';
    public $nuevoNombre = '';
    public $nuevoSimbolo = '';

    public $modalEliminarAbierto = false;
    public $unidadAEliminar = null;

    public function render()
    {
        $unidades = \App\Models\UnidadMedida::all(['id', 'unidad', 'nombre', 'simbolo', 'created_at']);
        return view('livewire.inventario.presentaciones', compact('unidades'));
    }

    public function editar($id)
    {
        $unidad = \App\Models\UnidadMedida::findOrFail($id);
        $this->form['id'] = $unidad->id;
        $this->form['unidad'] = $unidad->unidad;
        $this->form['nombre'] = $unidad->nombre;
        $this->form['simbolo'] = $unidad->simbolo;
        $this->modalAbierto = true;
    }

    public function guardar()
    {
        $this->validate([
            'form.unidad' => 'required|integer',
            'form.nombre' => 'required|string|max:255|unique:unidad_medida,nombre,' . $this->form['id'],
            'form.simbolo' => 'required|string|max:10|unique:unidad_medida,simbolo,' . $this->form['id'],
        ], [
            'form.unidad.required' => 'La unidad es obligatoria.',
            'form.unidad.integer' => 'La unidad debe ser un número.',
            'form.nombre.required' => 'El nombre es obligatorio.',
            'form.nombre.unique' => 'Ya existe una unidad de medida con ese nombre.',
            'form.simbolo.required' => 'El símbolo es obligatorio.',
            'form.simbolo.unique' => 'Ya existe una unidad de medida con ese símbolo.',
        ]);
        
        $unidad = \App\Models\UnidadMedida::findOrFail($this->form['id']);
        $unidad->unidad = $this->form['unidad'];
        $unidad->nombre = $this->form['nombre'];
        $unidad->simbolo = $this->form['simbolo'];
        $unidad->save();
        $this->modalAbierto = false;
        session()->flash('mensaje', 'Unidad de medida actualizada correctamente.');
    }

    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->nuevaUnidad = '';
        $this->nuevoNombre = '';
        $this->nuevoSimbolo = '';
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function cerrarModal()
    {
        $this->modalAbierto = false;
    }

    public function crearUnidad()
    {
        $this->validate([
            'nuevaUnidad' => 'required|integer',
            'nuevoNombre' => 'required|string|max:255|unique:unidad_medida,nombre',
            'nuevoSimbolo' => 'required|string|max:10|unique:unidad_medida,simbolo',
        ], [
            'nuevaUnidad.required' => 'La unidad es obligatoria.',
            'nuevaUnidad.integer' => 'La unidad debe ser un número.',
            'nuevoNombre.required' => 'El nombre es obligatorio.',
            'nuevoNombre.unique' => 'Ya existe una unidad de medida con ese nombre.',
            'nuevoSimbolo.required' => 'El símbolo es obligatorio.',
            'nuevoSimbolo.unique' => 'Ya existe una unidad de medida con ese símbolo.',
        ]);
        
        \App\Models\UnidadMedida::create([
            'unidad' => $this->nuevaUnidad,
            'nombre' => $this->nuevoNombre,
            'simbolo' => $this->nuevoSimbolo,
            'created_at' => now(),
        ]);
        $this->cerrarModalCrear();
        session()->flash('mensaje', 'Unidad de medida creada exitosamente.');
    }

    public function confirmarEliminar($id)
    {
        $this->unidadAEliminar = $id;
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->unidadAEliminar = null;
    }

    public function eliminarUnidad()
    {
        $unidad = \App\Models\UnidadMedida::find($this->unidadAEliminar);
        if ($unidad) {
            $unidad->delete();
            session()->flash('mensaje', 'Unidad de medida eliminada exitosamente.');
        }
        $this->cerrarModalEliminar();
    }
}
