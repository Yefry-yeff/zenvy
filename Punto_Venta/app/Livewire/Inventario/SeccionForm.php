<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use Illuminate\Support\Facades\Log;

class SeccionForm extends Component
{
    public $bodegaId;
    public $segmentoId;
    public $seccionId;
    public $isEditing = false;
    public $bodega;
    public $segmento;

    // Formulario principal
    public $form = [
        'descripcion' => '',
        'numeracion' => '',
        'estado_id' => 1,
        'segmento_id' => null,
    ];

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
            'descripcion',
            'numeracion'
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
        'form.descripcion' => 'required|string|max:255',
        'form.numeracion' => 'required|string|max:50',
        'form.estado_id' => 'required|integer|in:0,1',
        'form.segmento_id' => 'required|integer|exists:segmento,id',
    ];

    protected $messages = [
        'form.descripcion.required' => 'La descripción de la sección es obligatoria',
        'form.descripcion.max' => 'La descripción no puede exceder 255 caracteres',
        'form.numeracion.required' => 'La numeración de la sección es obligatoria',
        'form.numeracion.max' => 'La numeración no puede exceder 50 caracteres',
        'form.segmento_id.required' => 'El segmento es obligatorio',
        'form.segmento_id.exists' => 'El segmento seleccionado no existe',
    ];

    public function mount($bodegaId, $segmentoId, $seccionId = null)
    {
        $this->bodegaId = $bodegaId;
        $this->segmentoId = $segmentoId;
        $this->form['segmento_id'] = $segmentoId;
        
        $this->cargarDatos();
        
        if ($seccionId) {
            $this->seccionId = $seccionId;
            $this->isEditing = true;
            $this->cargarSeccion();
        }
    }

    private function cargarDatos()
    {
        try {
            $this->bodega = Bodega::with('tienda')->findOrFail($this->bodegaId);
            $this->segmento = Segmento::findOrFail($this->segmentoId);
        } catch (\Exception $e) {
            Log::error('Error al cargar datos para sección', [
                'bodega_id' => $this->bodegaId,
                'segmento_id' => $this->segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos');
        }
    }

    private function cargarSeccion()
    {
        try {
            $seccion = Seccion::findOrFail($this->seccionId);
            $this->form = [
                'descripcion' => $seccion->descripcion,
                'numeracion' => $seccion->numeracion,
                'estado_id' => $seccion->estado_id,
                'segmento_id' => $seccion->segmento_id,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cargar sección', [
                'seccion_id' => $this->seccionId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar la sección');
        }
    }

    public function guardar()
    {
        // Verificar si el formulario está completo
        if (!$this->formularioCompleto) {
            $this->mostrarError('❌ Complete todos los campos obligatorios antes de guardar la sección. Los campos marcados en rojo son requeridos.');
            return;
        }

        try {
            $this->validate();

            if ($this->isEditing) {
                Seccion::actualizarSeccion($this->seccionId, $this->form);
                Log::info('Sección actualizada exitosamente', ['id' => $this->seccionId]);
                $this->mostrarExito('Sección actualizada exitosamente.');
            } else {
                $resultado = Seccion::crearSeccion($this->form);
                Log::info('Sección creada exitosamente', ['resultado' => $resultado]);
                $this->mostrarExito('Sección creada exitosamente.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación al guardar sección', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error de validación: Revise los campos marcados en rojo');
            
        } catch (\Exception $e) {
            Log::error('Error al guardar sección', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos' => $this->form,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar la sección');
        }
    }

    public function volverASecciones()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Secciones', parametros: [
            'bodegaId' => $this->bodegaId,
            'segmentoId' => $this->segmentoId
        ]);
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormDescripcion()
    {
        try {
            $this->validateOnly('form.descripcion');
            $this->limpiarErrorCampo('descripcion');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('descripcion', 'La descripción de la sección es obligatoria y no puede estar vacía');
        }
    }

    public function updatedFormNumeracion()
    {
        try {
            $this->validateOnly('form.numeracion');
            $this->limpiarErrorCampo('numeracion');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('numeracion', 'La numeración de la sección es obligatoria y no puede estar vacía');
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
        // Redirigir a la lista de secciones después de cerrar el modal
        $this->volverASecciones();
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
    }

    public function render()
    {
        return view('livewire.inventario.seccion-form');
    }
}
