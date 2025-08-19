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

    public $modalEliminarAbierto = false;
    public $marcaAEliminar = null;
    public $productosVinculados = [];

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
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:marca,nombre,' . $this->form['id'],
        ], [
            'form.nombre.unique' => 'Ya existe una marca con ese nombre.'
        ]);
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

    public function cerrarModal()
    {
        $this->modalAbierto = false;
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
        session()->flash('mensaje', 'Marca creada exitosamente.');
    }

    public function confirmarEliminar($id)
    {
        $this->marcaAEliminar = $id;
        
        // Obtener los productos vinculados a esta marca
        $marca = \App\Models\Marca::with('productos')->find($id);
        $this->productosVinculados = $marca->productos->map(function($producto) {
            return [
                'id' => $producto->id,
                'codigo_barra' => $producto->codigo_barra ?? 'Sin código',
                'nombre' => $producto->nombre,
                'precio' => $producto->precio ?? 0
            ];
        })->toArray();
        
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->marcaAEliminar = null;
        $this->productosVinculados = [];
    }

    public function eliminarMarca()
    {
        // Verificar si hay productos vinculados antes de eliminar
        $marca = \App\Models\Marca::with('productos')->find($this->marcaAEliminar);
        
        if ($marca && $marca->productos->count() > 0) {
            session()->flash('error', 'No se puede eliminar la marca porque tiene productos vinculados. Primero elimine o cambie la marca de estos productos.');
            $this->cerrarModalEliminar();
            return;
        }
        
        if ($marca) {
            $marca->delete();
            session()->flash('mensaje', 'Marca eliminada exitosamente.');
        }
        $this->cerrarModalEliminar();
    }
}
