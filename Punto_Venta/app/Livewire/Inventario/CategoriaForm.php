<?php

namespace App\Livewire\Inventario;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Livewire\Component;

class CategoriaForm extends Component
{
    public $categoriaId;
    public $form = [
        'nombre' => '',
    ];
    public $subcategorias;
    public $nuevaSubcategoria = '';
    public $mostrarMensaje = false;

    // Propiedades para modal de eliminar subcategoría
    public $modalEliminarSubcategoriaAbierto = false;
    public $subcategoriaAEliminar = null;

    // Propiedades para modal de editar subcategoría
    public $modalEditarSubcategoriaAbierto = false;
    public $subcategoriaEditando = null;
    public $formSubcategoria = [
        'id' => null,
        'nombre' => '',
    ];

    public function mount($id = null)
    {
        $this->subcategorias = collect(); // Inicializar como colección vacía

        if ($id) {
            $this->categoriaId = $id;
            $categoria = Categoria::findOrFail($id);
            $this->form['nombre'] = $categoria->nombre;
            $this->cargarSubcategorias();
        } else {
            $this->categoriaId = null;
        }
    }

    public function cargarSubcategorias()
    {
        if ($this->categoriaId) {
            $this->subcategorias = Subcategoria::where('categoria_id', $this->categoriaId)->get();
        }
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.categoria');
    }

    public function guardar()
    {
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:categoria,nombre,' . $this->categoriaId,
        ], [
            'form.nombre.required' => 'El nombre de la categoría es obligatorio.',
            'form.nombre.unique' => 'Ya existe una categoría con ese nombre.'
        ]);

        if ($this->categoriaId) {
            // Actualizar categoría existente
            $categoria = Categoria::findOrFail($this->categoriaId);
            $categoria->update($this->form);
        } else {
            // Crear nueva categoría
            $categoria = Categoria::create($this->form);
            $this->categoriaId = $categoria->id;
            $this->cargarSubcategorias();
        }

        $this->mostrarMensaje = true;
    }

    public function agregarSubcategoria()
    {
        $this->validate([
            'nuevaSubcategoria' => 'required|string|max:255',
        ], [
            'nuevaSubcategoria.required' => 'El nombre de la subcategoría es obligatorio.',
        ]);

        if (!$this->categoriaId) {
            session()->flash('error', 'Primero debe guardar la categoría.');
            return;
        }

        Subcategoria::create([
            'nombre' => $this->nuevaSubcategoria,
            'categoria_id' => $this->categoriaId,
        ]);

        $this->nuevaSubcategoria = '';
        $this->cargarSubcategorias();
        session()->flash('mensaje', 'Subcategoría agregada correctamente.');
    }

    public function eliminarSubcategoria($subcategoriaId)
    {
        $this->subcategoriaAEliminar = $subcategoriaId;
        $this->modalEliminarSubcategoriaAbierto = true;
    }

    public function confirmarEliminarSubcategoria()
    {
        $subcategoria = Subcategoria::find($this->subcategoriaAEliminar);
        if ($subcategoria) {
            $subcategoria->delete();
            $this->cargarSubcategorias();
            session()->flash('mensaje', 'Subcategoría eliminada correctamente.');
        }
        $this->cerrarModalEliminarSubcategoria();
    }

    public function cerrarModalEliminarSubcategoria()
    {
        $this->modalEliminarSubcategoriaAbierto = false;
        $this->subcategoriaAEliminar = null;
    }

    public function editarSubcategoria($subcategoriaId)
    {
        $subcategoria = Subcategoria::findOrFail($subcategoriaId);
        $this->formSubcategoria['id'] = $subcategoria->id;
        $this->formSubcategoria['nombre'] = $subcategoria->nombre;
        $this->subcategoriaEditando = $subcategoriaId;
        $this->modalEditarSubcategoriaAbierto = true;
    }

    public function guardarSubcategoria()
    {
        $this->validate([
            'formSubcategoria.nombre' => 'required|string|max:255|unique:subcategoria,nombre,' . $this->formSubcategoria['id'],
        ], [
            'formSubcategoria.nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'formSubcategoria.nombre.unique' => 'Ya existe una subcategoría con ese nombre.',
        ]);

        $subcategoria = Subcategoria::findOrFail($this->formSubcategoria['id']);
        $subcategoria->update(['nombre' => $this->formSubcategoria['nombre']]);

        $this->cargarSubcategorias();
        $this->cerrarModalEditarSubcategoria();
        session()->flash('mensaje', 'Subcategoría actualizada correctamente.');
    }

    public function cerrarModalEditarSubcategoria()
    {
        $this->modalEditarSubcategoriaAbierto = false;
        $this->subcategoriaEditando = null;
        $this->formSubcategoria = ['id' => null, 'nombre' => ''];
    }

    public function render()
    {
        return view('livewire.inventario.categoria-form');
    }
}
