<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Categoria extends Component
{
    public $modalCrearAbierto = false;
    public $modalEliminarAbierto = false;
    public $nuevaCategoriaNombre = '';
    public $categoriaAEliminar = null;
    public $productosVinculados = [];

    public function render()
    {
        $categorias = \App\Models\Categoria::all(['id', 'nombre']);
        return view('livewire.inventario.categoria', compact('categorias'));
    }

    public function editar($id)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CategoriaForm', parametros: ['id' => $id]);
    }

    public function abrirModalCrear()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CategoriaForm');
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function confirmarEliminar($id)
    {
        $this->categoriaAEliminar = $id;
        
        // Obtener todos los productos que pertenecen a subcategorías de esta categoría
        $categoria = \App\Models\Categoria::with(['subcategorias.productos'])->find($id);
        $this->productosVinculados = [];
        
        if ($categoria && $categoria->subcategorias) {
            foreach ($categoria->subcategorias as $subcategoria) {
                foreach ($subcategoria->productos as $producto) {
                    $this->productosVinculados[] = [
                        'id' => $producto->id,
                        'codigo_barra' => $producto->codigo_barra ?? 'Sin código',
                        'nombre' => $producto->nombre,
                        'subcategoria' => $subcategoria->nombre
                    ];
                }
            }
        }
        
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->categoriaAEliminar = null;
        $this->productosVinculados = [];
    }

    public function eliminarCategoria()
    {
        // Verificar si hay productos vinculados antes de eliminar
        $categoria = \App\Models\Categoria::with(['subcategorias.productos'])->find($this->categoriaAEliminar);
        
        if ($categoria) {
            $tieneProductos = false;
            foreach ($categoria->subcategorias as $subcategoria) {
                if ($subcategoria->productos->count() > 0) {
                    $tieneProductos = true;
                    break;
                }
            }
            
            if ($tieneProductos) {
                session()->flash('error', 'No se puede eliminar la categoría porque tiene productos vinculados en sus subcategorías. Primero elimine o cambie la subcategoría de estos productos.');
                $this->cerrarModalEliminar();
                return;
            }
            
            // Si no tiene productos, eliminar subcategorías y luego la categoría
            $categoria->subcategorias()->delete();
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


