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
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
        })->orderBy('nombre')->get(['id', 'unidad', 'nombre', 'simbolo', 'created_at']);

        // Obtener unidades de Valencia (que SÍ están en la tabla de mapeo)
        $unidadesValencia = \App\Models\UnidadMedida::whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('id_zenvy_valencia')
                  ->whereRaw('id_zenvy_valencia.id_zenvy = unidad_medida.id')
                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
        })->orderBy('nombre')->get(['id', 'unidad', 'nombre', 'simbolo', 'created_at']);

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
            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            session()->flash('mensaje', 'Unidades de Valencia sincronizadas exitosamente. Nuevas: ' . $resultado['nuevas'] . ', Actualizadas: ' . $resultado['actualizadas']);
            Log::info('Sincronización manual de unidades Valencia: ' . json_encode($resultado));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar unidades de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }
}
