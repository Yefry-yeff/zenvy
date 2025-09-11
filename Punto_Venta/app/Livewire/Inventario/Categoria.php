<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Services\SincronizacionCategoriasService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Categoria extends Component
{
    public $modalCrearAbierto = false;
    public $modalEliminarAbierto = false;
    public $nuevaCategoriaNombre = '';
    public $categoriaAEliminar = null;
    public $productosVinculados = [];

    private $sincronizacionService;

    public function mount()
    {
        $this->sincronizarCategorias();
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionCategoriasService::class);
        }
        return $this->sincronizacionService;
    }

    private function sincronizarCategorias()
    {
        try {
            $resultado = $this->getSincronizacionService()->sincronizarCategoriasEnTiempoReal();
            Log::info('Sincronización de categorías en gestión: ' . json_encode($resultado));
        } catch (\Exception $e) {
            Log::error('Error al sincronizar categorías en gestión: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Obtener categorías propias de Zenvy (que NO están en la tabla de mapeo)
        $categoriasZenvy = \App\Models\Categoria::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = categoria.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
        })->orderBy('nombre')->get(['id', 'nombre']);

        // Obtener categorías de Valencia (que SÍ están en la tabla de mapeo)
        $categoriasValencia = \App\Models\Categoria::whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = categoria.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
        })->orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.inventario.categoria', compact('categoriasZenvy', 'categoriasValencia'));
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

    public function sincronizarCategoriasValencia()
    {
        try {
            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            session()->flash('mensaje', 'Categorías de Valencia sincronizadas exitosamente. Nuevas: ' . $resultado['nuevas'] . ', Actualizadas: ' . $resultado['actualizadas']);
            Log::info('Sincronización manual de categorías Valencia: ' . json_encode($resultado));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar categorías de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }
}


