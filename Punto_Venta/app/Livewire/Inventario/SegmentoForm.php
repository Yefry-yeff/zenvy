<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Bodega;
use App\Models\Segmento;
use Illuminate\Support\Facades\Log;

class SegmentoForm extends Component
{
    public $bodegaId;
    public $segmentoId;
    public $isEditing = false;
    public $bodega;

    // Formulario principal
    public $form = [
        'descripcion' => '',
        'bodega_id' => null,
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
            'descripcion'
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
        'form.bodega_id' => 'required|integer|exists:bodega,id',
    ];

    protected $messages = [
        'form.descripcion.required' => 'La descripción del segmento es obligatoria',
        'form.descripcion.max' => 'La descripción no puede exceder 255 caracteres',
        'form.bodega_id.required' => 'La bodega es obligatoria',
        'form.bodega_id.exists' => 'La bodega seleccionada no existe',
    ];

    public function mount($bodegaId, $segmentoId = null)
    {
        $this->bodegaId = $bodegaId;
        $this->form['bodega_id'] = $bodegaId;
        
        $this->cargarBodega();
        
        if ($segmentoId) {
            $this->segmentoId = $segmentoId;
            $this->isEditing = true;
            $this->cargarSegmento();
        }
    }

    private function cargarBodega()
    {
        try {
            $this->bodega = Bodega::findOrFail($this->bodegaId);
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega', [
                'bodega_id' => $this->bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar la bodega');
        }
    }

    private function cargarSegmento()
    {
        try {
            $segmento = Segmento::findOrFail($this->segmentoId);
            $this->form = [
                'descripcion' => $segmento->descripcion,
                'bodega_id' => $segmento->bodega_id,
            ];
        } catch (\Exception $e) {
            Log::error('Error al cargar segmento', [
                'segmento_id' => $this->segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar el segmento');
        }
    }

    public function guardar()
    {
        // Verificar si el formulario está completo
        if (!$this->formularioCompleto) {
            $this->mostrarError('❌ Complete todos los campos obligatorios antes de guardar el segmento. Los campos marcados en rojo son requeridos.');
            return;
        }

        try {
            $this->validate();

            if ($this->isEditing) {
                Segmento::actualizarSegmento($this->segmentoId, $this->form);
                Log::info('Segmento actualizado exitosamente', ['id' => $this->segmentoId]);
                $this->mostrarExito('Segmento actualizado exitosamente.');
            } else {
                $resultado = Segmento::crearSegmento($this->form);
                Log::info('Segmento creado exitosamente', ['resultado' => $resultado]);
                $this->mostrarExito('Segmento creado exitosamente.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación al guardar segmento', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error de validación: Revise los campos marcados en rojo');
            
        } catch (\Exception $e) {
            Log::error('Error al guardar segmento', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos' => $this->form,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar el segmento');
        }
    }

    public function volverASegmentos()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Segmentos', parametros: ['bodegaId' => $this->bodegaId]);
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormDescripcion()
    {
        try {
            $this->validateOnly('form.descripcion');
            $this->limpiarErrorCampo('descripcion');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('descripcion', 'La descripción del segmento es obligatoria y no puede estar vacía');
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
        // Redirigir a la lista de segmentos después de cerrar el modal
        $this->volverASegmentos();
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
    }

    public function render()
    {
        return view('livewire.inventario.segmento-form');
    }
}
