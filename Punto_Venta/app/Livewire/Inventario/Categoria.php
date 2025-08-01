<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Categoria extends Component
{
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
        $this->dispatch('cambiarVista', ruta: 'Inventario.categoriaform', parametros: ['id' => $id]);
    }

    public function abrirModalCrear()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.categoriaform');
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
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
            // Primero eliminar todas las subcategorías
            $categoria->subcategorias()->delete();

            // Luego eliminar la categoría
            $categoria->delete();
            session()->flash('mensaje', 'Categoría y sus subcategorías eliminadas exitosamente.');
        }
        $this->cerrarModalEliminar();
    }

    public function crearCategoria()
    {
        $this->validate([
            'nuevaCategoriaNombre' => 'required|string|max:255|unique:categoria,nombre',
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


