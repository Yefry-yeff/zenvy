<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Services\SincronizacionMarcasService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    // Propiedades para indicador de carga
    public $sincronizandoMarcas = false;
    public $progreso = 0;
    public $detallesSincronizacion = null;

    private $sincronizacionService;

    public function mount()
    {
        $this->sincronizarMarcas();
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionMarcasService::class);
        }
        return $this->sincronizacionService;
    }

    private function sincronizarMarcas()
    {
        try {
            $resultado = $this->getSincronizacionService()->sincronizarMarcasEnTiempoReal();
            Log::info('Sincronización de marcas en gestión: ' . json_encode($resultado));
        } catch (\Exception $e) {
            Log::error('Error al sincronizar marcas en gestión: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Obtener marcas propias de Zenvy (que NO están en la tabla de mapeo)
        $marcasZenvy = \App\Models\Marca::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = marca.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 2);
        })->orderBy('nombre')->get(['id', 'nombre']);

        // Obtener marcas de Valencia (que SÍ están en la tabla de mapeo)
        $marcasValencia = \App\Models\Marca::whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = marca.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 2);
        })->orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.inventario.marca', compact('marcasZenvy', 'marcasValencia'));
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

    public function actualizarMarcas()
    {
        try {
            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            session()->flash('mensaje', 'Marcas actualizadas exitosamente desde Valencia.');
            Log::info('Sincronización manual de marcas: ' . json_encode($resultado));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar marcas: ' . $e->getMessage());
            Log::error('Error en sincronización manual: ' . $e->getMessage());
        }
    }

    public function sincronizarMarcasValencia()
    {
        try {
            // Iniciar el proceso de sincronización
            $this->sincronizandoMarcas = true;
            $this->progreso = 0;
            $this->detallesSincronizacion = null;

            // Simular progreso de sincronización
            for ($i = 0; $i <= 100; $i += 25) {
                $this->progreso = $i;
                $this->dispatch('actualizarProgreso', $this->progreso);
                usleep(200000); // 0.2 segundos
            }

            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            
            // Finalizar progreso
            $this->progreso = 100;
            $this->dispatch('actualizarProgreso', $this->progreso);
            
            // Preparar detalles de sincronización
            $this->detallesSincronizacion = [
                'marcas_sincronizadas' => ($resultado['estadisticas']['nuevas'] ?? 0) + ($resultado['estadisticas']['actualizadas'] ?? 0),
                'marcas_nuevas' => $resultado['estadisticas']['nuevas'] ?? 0,
                'marcas_actualizadas' => $resultado['estadisticas']['actualizadas'] ?? 0,
                'sin_cambios' => $resultado['estadisticas']['sin_cambios'] ?? 0,
                'total_procesadas' => $resultado['estadisticas']['total_procesadas'] ?? 0,
                'tiempo_ejecucion' => '~2 segundos'
            ];
            
            // Mensajes de estado
            if (($resultado['estadisticas']['nuevas'] ?? 0) > 0 || ($resultado['estadisticas']['actualizadas'] ?? 0) > 0) {
                session()->flash('mensaje', '✅ Sincronización completada: ' . $this->detallesSincronizacion['marcas_sincronizadas'] . ' marcas procesadas exitosamente.');
            } else {
                session()->flash('mensaje', '✅ Sincronización completada: Todas las marcas están actualizadas.');
            }
            
            Log::info('Sincronización manual de marcas Valencia: ' . json_encode($resultado));
            
            // Finalizar estado de carga
            $this->sincronizandoMarcas = false;
            
        } catch (\Exception $e) {
            $this->sincronizandoMarcas = false;
            $this->progreso = 0;
            
            session()->flash('error', 'Error al sincronizar marcas de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }

    public function cerrarDetallesSincronizacion()
    {
        $this->detallesSincronizacion = null;
    }
}
