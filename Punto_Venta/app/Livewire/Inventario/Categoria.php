<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Categoria extends Component
{
    public $form = [
        'id' => null,
        'nombre' => '',
    ];
    public $modalAbierto = false;
    public $modalCrearAbierto = false;
    public $modalEliminarAbierto = false;
    public $nuevaCategoriaNombre = '';
    public $categoriaAEliminar = null;

    public function render()
    {
        $categorias = \App\Models\Categoria::all(['id', 'nombre']);
        return view('livewire.inventario.categoria', compact('categorias'));
    }

    public function editar($id)
    {
        $categoria = \App\Models\Categoria::findOrFail($id);
        $this->form['id'] = $categoria->id;
        $this->form['nombre'] = $categoria->nombre;
        $this->modalAbierto = true;
    }

    public function guardar()
    {
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:categorias,nombre,' . $this->form['id'],
        ], [
            'form.nombre.unique' => 'Ya existe una categoría con ese nombre.'
        ]);
        $categoria = \App\Models\Categoria::findOrFail($this->form['id']);
        $categoria->nombre = $this->form['nombre'];
        $categoria->save();
        $this->modalAbierto = false;
        session()->flash('mensaje', 'Categoría actualizada correctamente.');
    }

    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->nuevaCategoriaNombre = '';
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function cerrarModal()
    {
        $this->modalAbierto = false;
    }

    public function confirmarEliminar($id)
    {
        $this->categoriaAEliminar = $id;
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->categoriaAEliminar = null;
    }

    public function eliminarCategoria()
    {
        $categoria = \App\Models\Categoria::find($this->categoriaAEliminar);
        if ($categoria) {
            $categoria->delete();
            session()->flash('mensaje', 'Categoría eliminada exitosamente.');
        }
        $this->cerrarModalEliminar();
    }

    public function crearCategoria()
    {
        $this->validate([
            'nuevaCategoriaNombre' => 'required|string|max:255|unique:categorias,nombre',
        ], [
            'nuevaCategoriaNombre.unique' => 'Ya existe una categoría con ese nombre.'
        ]);
        \App\Models\Categoria::create([
            'nombre' => $this->nuevaCategoriaNombre,
            'created_at' => now(),
        ]);
        $this->cerrarModalCrear();
        session()->flash('mensaje', 'Categoría creada exitosamente.');
    }
}


