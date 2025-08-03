<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\Tiendas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BodegaForm extends Component
{
    public $bodegaId;
    public $isEditing = false;

    // Formulario principal
    public $form = [
        'nombre' => '',
        'direccion_id' => 1,
        'estado_id' => 1,
        'tienda_id' => null,
    ];

    // Datos para los selectores
    public $tiendas = [];

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';
    public $camposConError = [];
    public $erroresValidacion = [];

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Propiedad computada para validar si el formulario está completo
    public function getFormularioCompletoProperty()
    {
        $camposObligatorios = [
            'nombre',
            'tienda_id'
        ];

        foreach ($camposObligatorios as $campo) {
            if (empty($this->form[$campo]) || $this->form[$campo] === null || $this->form[$campo] === '') {
                return false;
            }
        }

        // Verificar que no haya errores de validación en campos obligatorios
        foreach ($camposObligatorios as $campo) {
            if (in_array("form.$campo", $this->camposConError)) {
                return false;
            }
        }

        return true;
    }

    protected $rules = [
        'form.nombre' => 'required|string|max:100',
        'form.direccion_id' => 'required|integer',
        'form.estado_id' => 'required|integer',
        'form.tienda_id' => 'required|integer|exists:tienda,id',
    ];

    protected $messages = [
        'form.nombre.required' => 'El nombre de la bodega es obligatorio',
        'form.nombre.max' => 'El nombre no puede exceder 100 caracteres',
        'form.tienda_id.required' => 'La tienda es obligatoria',
        'form.tienda_id.exists' => 'La tienda seleccionada no existe',
    ];

    public function mount($bodegaId = null)
    {
        $this->cargarDatosIniciales();

        if ($bodegaId) {
            $this->bodegaId = $bodegaId;
            $this->isEditing = true;
            $this->cargarBodega();
        }
    }

    private function cargarDatosIniciales()
    {
        try {
            $this->tiendas = Tiendas::where('estado_id', 1)->orderBy('denominacion_social')->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar datos iniciales de bodega', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->mostrarError('Error al cargar los datos iniciales');
        }
    }

    private function cargarBodega()
    {
        try {
            $bodega = Bodega::findOrFail($this->bodegaId);
            $this->form = [
                'nombre' => $bodega->nombre,
                'direccion_id' => $bodega->direccion_id,
                'estado_id' => $bodega->estado_id,
                'tienda_id' => $bodega->tienda_id,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega', [
                'bodega_id' => $this->bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar la bodega');
        }
    }

    public function guardar()
    {
        // Verificar si el formulario está completo
        if (!$this->formularioCompleto) {
            $this->mostrarError('❌ Complete todos los campos obligatorios antes de guardar la bodega. Los campos marcados en rojo son requeridos.');
            return;
        }

        try {
            $this->validate();

            if ($this->isEditing) {
                Bodega::actualizarBodega($this->bodegaId, $this->form);
                Log::info('Bodega actualizada exitosamente', ['id' => $this->bodegaId]);
                $this->mostrarExito('Bodega actualizada exitosamente.');
            } else {
                $resultado = Bodega::crearBodega($this->form);
                Log::info('Bodega creada exitosamente', ['resultado' => $resultado]);
                $this->mostrarExito('Bodega creada exitosamente.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación al guardar bodega', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error de validación: Revise los campos marcados en rojo');

        } catch (\Exception $e) {
            Log::error('Error al guardar bodega', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos' => $this->form,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar la bodega');
        }
    }

    public function volverALista()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.bodegas');
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormNombre()
    {
        try {
            $this->validateOnly('form.nombre');
            $this->limpiarErrorCampo('nombre');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('nombre', 'El nombre de la bodega es obligatorio y no puede estar vacío');
        }
    }

    public function updatedFormTiendaId()
    {
        try {
            $this->validateOnly('form.tienda_id');
            $this->limpiarErrorCampo('tienda');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('tienda', 'Debe seleccionar una tienda');
        }
    }

    // ===== MÉTODOS DE UTILIDAD =====

    private function mostrarErrorCampo($campo, $mensaje)
    {
        $this->campoConError = $campo;
        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;

        if (!in_array($campo, $this->camposConError)) {
            $this->camposConError[] = $campo;
        }
        $this->erroresValidacion[$campo] = $mensaje;
    }

    private function limpiarErrorCampo($campo)
    {
        $this->camposConError = array_filter($this->camposConError, function($item) use ($campo) {
            return $item !== $campo;
        });

        unset($this->erroresValidacion[$campo]);

        if (empty($this->camposConError)) {
            $this->mostrarAlerta = false;
            $this->mensajeAlerta = '';
            $this->campoConError = '';
        }
    }

    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }
        return '';
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    // ===== MÉTODOS DE MODALES =====

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        // Redirigir a la lista de bodegas después de cerrar el modal
        $this->volverALista();
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
    }

    public function render()
    {
        return view('livewire.inventario.bodega-form');
    }
}
