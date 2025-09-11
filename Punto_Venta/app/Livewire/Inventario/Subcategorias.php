<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Services\SincronizacionSubcategoriasService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Subcategorias extends Component
{
    public $modalCrearAbierto = false;
    public $modalEliminarAbierto = false;
    public $nuevaSubcategoriaNombre = '';
    public $categoriaSeleccionada = '';
    public $subcategoriaAEliminar = null;
    public $productosVinculados = [];

    private $sincronizacionService;

    public function mount()
    {
        $this->sincronizarSubcategorias();
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionSubcategoriasService::class);
        }
        return $this->sincronizacionService;
    }

    private function sincronizarSubcategorias()
    {
        try {
            $resultado = $this->getSincronizacionService()->sincronizarSubcategoriasEnTiempoReal();
            Log::info('Sincronización de subcategorías en gestión: ' . json_encode($resultado));
        } catch (\Exception $e) {
            Log::error('Error al sincronizar subcategorías en gestión: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Obtener subcategorías propias de Zenvy (que NO están en la tabla de mapeo)
        $subcategoriasZenvy = \App\Models\Subcategoria::with('categoria')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('id_zenvy_valencia')
                      ->whereRaw('id_zenvy_valencia.id_zenvy = subcategoria.id')
                      ->where('id_zenvy_valencia.tipo_dato_migrado_id', 4);
            })->orderBy('nombre')->get(['id', 'nombre', 'categoria_id']);

        // Obtener subcategorías de Valencia (que SÍ están en la tabla de mapeo)
        $subcategoriasValencia = \App\Models\Subcategoria::with('categoria')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('id_zenvy_valencia')
                      ->whereRaw('id_zenvy_valencia.id_zenvy = subcategoria.id')
                      ->where('id_zenvy_valencia.tipo_dato_migrado_id', 4);
            })->orderBy('nombre')->get(['id', 'nombre', 'categoria_id']);

        // Obtener categorías para el formulario de creación
        $categorias = \App\Models\Categoria::orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.inventario.subcategorias', compact('subcategoriasZenvy', 'subcategoriasValencia', 'categorias'));
    }

    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->nuevaSubcategoriaNombre = '';
        $this->categoriaSeleccionada = '';
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function crearSubcategoria()
    {
        $this->validate([
            'nuevaSubcategoriaNombre' => 'required|string|max:255',
            'categoriaSeleccionada' => 'required|exists:categoria,id',
        ], [
            'nuevaSubcategoriaNombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'categoriaSeleccionada.required' => 'Debe seleccionar una categoría.',
            'categoriaSeleccionada.exists' => 'La categoría seleccionada no existe.',
        ]);

        // Verificar que no exista una subcategoría con el mismo nombre en la misma categoría
        $existe = \App\Models\Subcategoria::where('nombre', $this->nuevaSubcategoriaNombre)
                                         ->where('categoria_id', $this->categoriaSeleccionada)
                                         ->exists();

        if ($existe) {
            session()->flash('error', 'Ya existe una subcategoría con ese nombre en la categoría seleccionada.');
            return;
        }

        \App\Models\Subcategoria::create([
            'nombre' => $this->nuevaSubcategoriaNombre,
            'categoria_id' => $this->categoriaSeleccionada,
            'created_at' => now(),
        ]);

        $this->cerrarModalCrear();
        session()->flash('mensaje', 'Subcategoría creada exitosamente.');
    }

    public function confirmarEliminar($id)
    {
        $this->subcategoriaAEliminar = $id;
        
        // Verificar si hay productos vinculados
        $subcategoria = \App\Models\Subcategoria::with('productos')->find($id);
        if ($subcategoria) {
            $this->productosVinculados = $subcategoria->productos->pluck('nombre')->toArray();
        }
        
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->subcategoriaAEliminar = null;
        $this->productosVinculados = [];
    }

    public function eliminarSubcategoria()
    {
        // Verificar si hay productos vinculados antes de eliminar
        $subcategoria = \App\Models\Subcategoria::with('productos')->find($this->subcategoriaAEliminar);
        
        if ($subcategoria && $subcategoria->productos->count() > 0) {
            session()->flash('error', 'No se puede eliminar la subcategoría porque tiene productos vinculados. Primero elimine o cambie la subcategoría de estos productos.');
            $this->cerrarModalEliminar();
            return;
        }
        
        if ($subcategoria) {
            $subcategoria->delete();
            session()->flash('mensaje', 'Subcategoría eliminada exitosamente.');
        }
        $this->cerrarModalEliminar();
    }

    public function sincronizarSubcategoriasValencia()
    {
        try {
            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            session()->flash('mensaje', 'Subcategorías de Valencia sincronizadas exitosamente. Nuevas: ' . $resultado['nuevas'] . ', Actualizadas: ' . $resultado['actualizadas']);
            Log::info('Sincronización manual de subcategorías Valencia: ' . json_encode($resultado));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar subcategorías de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }
}