<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Services\SincronizacionUnidadesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    // Propiedades para efectos de carga en sincronización
    public $sincronizandoUnidades = false;
    public $progreso = null;
    public $detallesSincronizacion = null;

    private $sincronizacionService;

    public function mount()
    {
        $this->sincronizarUnidades();
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionUnidadesService::class);
        }
        return $this->sincronizacionService;
    }

    private function sincronizarUnidades()
    {
        try {
            $resultado = $this->getSincronizacionService()->sincronizarUnidadesEnTiempoReal();
            Log::info('Sincronización de unidades en gestión: ' . json_encode($resultado));
        } catch (\Exception $e) {
            Log::error('Error al sincronizar unidades en gestión: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Obtener unidades propias de Zenvy (que NO están en la tabla de mapeo)
        $unidadesZenvy = \App\Models\UnidadMedida::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = unidad_medida.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 5);
        })->orderBy('nombre')->get(['id', 'nombre', 'simbolo', 'created_at']);

        // Obtener unidades de Valencia (que SÍ están en la tabla de mapeo)
        $unidadesValencia = \App\Models\UnidadMedida::whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = unidad_medida.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 5);
        })->orderBy('nombre')->get(['id', 'nombre', 'simbolo', 'created_at']);

        return view('livewire.inventario.presentaciones', compact('unidadesZenvy', 'unidadesValencia'));
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
            'form.simbolo' => 'required|string|max:10|unique:unidad_medida,simbolo,' . $this->form['id'],
        ], [
            'form.simbolo.required' => 'El símbolo es obligatorio.',
            'form.simbolo.unique' => 'Ya existe una unidad de medida con ese símbolo.',
        ]);

        $unidad = \App\Models\UnidadMedida::findOrFail($this->form['id']);
        $unidad->unidad = 1; // Mandar null como solicitado
        // No actualizamos el nombre porque está readonly
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
            'nuevoNombre' => 'required|string|max:255|unique:unidad_medida,nombre',
            'nuevoSimbolo' => 'required|string|max:10|unique:unidad_medida,simbolo',
        ], [
            'nuevoNombre.required' => 'El nombre es obligatorio.',
            'nuevoNombre.unique' => 'Ya existe una unidad de medida con ese nombre.',
            'nuevoSimbolo.required' => 'El símbolo es obligatorio.',
            'nuevoSimbolo.unique' => 'Ya existe una unidad de medida con ese símbolo.',
        ]);

        \App\Models\UnidadMedida::create([
            'unidad' => null, // Mandar null como solicitado
            'nombre' => $this->nuevoNombre,
            'simbolo' => $this->nuevoSimbolo,
            'created_at' => now(),
        ]);
        $this->cerrarModalCrear();
        session()->flash('mensaje', 'Unidad de medida creada exitosamente.');
    }

    public function sincronizarUnidadesValencia()
    {
        try {
            // Iniciar el proceso de sincronización
            $this->sincronizandoUnidades = true;
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
                'unidades_sincronizadas' => ($resultado['estadisticas']['nuevas'] ?? 0) + ($resultado['estadisticas']['actualizadas'] ?? 0),
                'unidades_nuevas' => $resultado['estadisticas']['nuevas'] ?? 0,
                'unidades_actualizadas' => $resultado['estadisticas']['actualizadas'] ?? 0,
                'sin_cambios' => $resultado['estadisticas']['sin_cambios'] ?? 0,
                'total_procesadas' => $resultado['estadisticas']['total_procesadas'] ?? 0,
                'tiempo_ejecucion' => '~2 segundos'
            ];
            
            // Mensajes de estado
            if (($resultado['estadisticas']['nuevas'] ?? 0) > 0 || ($resultado['estadisticas']['actualizadas'] ?? 0) > 0) {
                session()->flash('mensaje', '✅ Sincronización completada: ' . $this->detallesSincronizacion['unidades_sincronizadas'] . ' unidades procesadas exitosamente.');
            } else {
                session()->flash('mensaje', '✅ Sincronización completada: Todas las unidades están actualizadas.');
            }
            
            Log::info('Sincronización manual de unidades Valencia: ' . json_encode($resultado));
            
            // Finalizar estado de carga
            $this->sincronizandoUnidades = false;
            
        } catch (\Exception $e) {
            $this->sincronizandoUnidades = false;
            $this->progreso = 0;
            
            session()->flash('error', 'Error al sincronizar unidades de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }

    public function cerrarDetallesSincronizacion()
    {
        $this->detallesSincronizacion = null;
    }
}
