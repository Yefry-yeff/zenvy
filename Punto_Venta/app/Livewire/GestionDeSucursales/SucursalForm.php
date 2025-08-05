<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use App\Models\Tienda;
use App\Models\TipoTienda;
use App\Models\Estado;
use App\Models\Direccion;
use App\Models\TipoDireccion;
use App\Models\Departamento;
use App\Models\Municipio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SucursalForm extends Component
{
    public $sucursalId;
    public $isEditing = false;

    // Formulario principal de sucursal
    public $form = [
        'denominacion_social' => '',
        'descripcion' => '',
        'telefono' => '',
        'celular' => '',
        'correo' => '',
        'tipo_tienda_id' => null,
        'estado_id' => 1,
        'numero_sucursal' => '',
        'identificador_legal' => '',
    ];

    // Formulario de dirección
    public $direccionForm = [
        'domicilio_tributario' => '',
        'colonia' => '',
        'calle_blv' => '',
        'sector_zona' => '',
        'bloque' => '',
        'tipo_direccion_id' => null,
        'municipio_id' => null,
        'estado_id' => 1,
        'latitud' => '',
        'longitud' => '',
    ];

    // Datos para los selectores
    public $tiposTienda = [];
    public $estados = [];
    public $tiposDireccion = [];
    public $departamentos = [];
    public $municipios = [];
    public $departamentoSeleccionado = null;

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    protected $rules = [
        'form.denominacion_social' => 'required|min:2|max:145',
        'form.telefono' => 'nullable|max:45',
        'form.celular' => 'nullable|max:45',
        'form.correo' => 'nullable|email|max:45',
        'form.tipo_tienda_id' => 'required|exists:tipo_tienda,id',
        'form.estado_id' => 'required|exists:estado,id',
        'form.numero_sucursal' => 'nullable|max:45',
        'form.identificador_legal' => 'nullable|max:45',
        'direccionForm.domicilio_tributario' => 'required|max:100',
        'direccionForm.municipio_id' => 'required|exists:municipio,id',
        'direccionForm.tipo_direccion_id' => 'required|exists:tipo_direccion,id',
    ];

    protected $messages = [
        'form.denominacion_social.required' => 'La denominación social es obligatoria.',
        'form.denominacion_social.min' => 'La denominación social debe tener al menos 2 caracteres.',
        'form.denominacion_social.max' => 'La denominación social no puede exceder 145 caracteres.',
        'form.correo.email' => 'El formato del correo electrónico no es válido.',
        'form.tipo_tienda_id.required' => 'El tipo de tienda es obligatorio.',
        'form.estado_id.required' => 'El estado es obligatorio.',
        'direccionForm.domicilio_tributario.required' => 'El domicilio tributario es obligatorio.',
        'direccionForm.municipio_id.required' => 'El municipio es obligatorio.',
        'direccionForm.tipo_direccion_id.required' => 'El tipo de dirección es obligatorio.',
    ];

    public function mount($id = null)
    {
        $this->sucursalId = $id;
        $this->isEditing = !is_null($id);

        $this->cargarDatos();

        if ($this->isEditing) {
            $this->cargarSucursal();
        }
    }

    public function render()
    {
        return view('livewire.gestion-de-sucursales.sucursal-form');
    }

    private function cargarDatos()
    {
        $this->tiposTienda = TipoTienda::orderBy('nombre')->get();
        $this->estados = Estado::orderBy('descripcion')->get();
        $this->tiposDireccion = TipoDireccion::orderBy('nombre')->get();
        $this->departamentos = Departamento::orderBy('nombre')->get();
    }

    public function updatedDepartamentoSeleccionado($departamentoId)
    {
        if ($departamentoId) {
            $this->municipios = Municipio::where('departamento_id', $departamentoId)
                                       ->orderBy('nombre')
                                       ->get();
            $this->direccionForm['municipio_id'] = null;
        } else {
            $this->municipios = [];
        }
    }

    private function cargarSucursal()
    {
        $sucursal = Tienda::with('direccion.municipio.departamento')->find($this->sucursalId);
        
        if ($sucursal) {
            $this->form = [
                'denominacion_social' => $sucursal->denominacion_social,
                'descripcion' => $sucursal->descripcion,
                'telefono' => $sucursal->telefono,
                'celular' => $sucursal->celular,
                'correo' => $sucursal->correo,
                'tipo_tienda_id' => $sucursal->tipo_tienda_id,
                'estado_id' => $sucursal->estado_id,
                'numero_sucursal' => $sucursal->numero_sucursal,
                'identificador_legal' => $sucursal->identificador_legal,
            ];

            if ($sucursal->direccion) {
                $this->direccionForm = [
                    'domicilio_tributario' => $sucursal->direccion->domicilio_tributario,
                    'colonia' => $sucursal->direccion->colonia,
                    'calle_blv' => $sucursal->direccion->calle_blv,
                    'sector_zona' => $sucursal->direccion->sector_zona,
                    'bloque' => $sucursal->direccion->bloque,
                    'tipo_direccion_id' => $sucursal->direccion->tipo_direccion_id,
                    'municipio_id' => $sucursal->direccion->municipio_id,
                    'estado_id' => $sucursal->direccion->estado_id,
                    'latitud' => $sucursal->direccion->latitud,
                    'longitud' => $sucursal->direccion->longitud,
                ];

                // Cargar departamento y municipios
                if ($sucursal->direccion->municipio) {
                    $this->departamentoSeleccionado = $sucursal->direccion->municipio->departamento_id;
                    $this->updatedDepartamentoSeleccionado($this->departamentoSeleccionado);
                }
            }
        }
    }

    public function guardar()
    {
        // Verificar campos críticos antes de la validación completa
        $camposVacios = $this->verificarCamposCriticos();

        if (!empty($camposVacios)) {
            $primerCampoVacio = $camposVacios[0];
            $mensajes = [
                'denominacion_social' => 'La denominación social es obligatoria',
                'tipo_tienda_id' => 'Debe seleccionar un tipo de tienda',
                'estado_id' => 'Debe seleccionar un estado',
                'domicilio_tributario' => 'El domicilio tributario es obligatorio',
                'municipio_id' => 'Debe seleccionar un municipio',
                'tipo_direccion_id' => 'Debe seleccionar un tipo de dirección'
            ];

            $this->mostrarErrorCampo($primerCampoVacio, $mensajes[$primerCampoVacio]);
            return;
        }

        $this->validate();

        try {
            // Crear o actualizar dirección primero
            if ($this->isEditing) {
                $sucursal = Tienda::find($this->sucursalId);
                if ($sucursal->direccion) {
                    $sucursal->direccion->update($this->direccionForm);
                    $direccionId = $sucursal->direccion->id;
                } else {
                    $direccion = Direccion::create($this->direccionForm);
                    $direccionId = $direccion->id;
                }
            } else {
                $direccion = Direccion::create($this->direccionForm);
                $direccionId = $direccion->id;
            }

            // Crear o actualizar sucursal
            $this->form['direccion_sucursal_id'] = $direccionId;
            
            if ($this->isEditing) {
                $sucursal->update($this->form);
                session()->flash('mensaje', 'Sucursal actualizada correctamente.');
            } else {
                $this->form['users_creador_id'] = Auth::id();
                Tienda::create($this->form);
                session()->flash('mensaje', 'Sucursal creada correctamente.');
            }

            return $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursales');

        } catch (\Exception $e) {
            Log::error('Error al guardar sucursal: ' . $e->getMessage());
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error al guardar la sucursal. Por favor, inténtelo de nuevo.';
        }
    }

    public function cancelar()
    {
        $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursales');
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    // ===== MÉTODOS PARA VALIDACIÓN DE CAMPOS CRÍTICOS =====

    public function verificarCamposCriticos()
    {
        $camposCriticos = ['denominacion_social', 'tipo_tienda_id', 'estado_id', 'domicilio_tributario', 'municipio_id', 'tipo_direccion_id'];
        $camposVacios = [];

        foreach ($camposCriticos as $campo) {
            $valor = '';
            switch ($campo) {
                case 'denominacion_social':
                    $valor = $this->form['denominacion_social'];
                    break;
                case 'tipo_tienda_id':
                    $valor = $this->form['tipo_tienda_id'];
                    break;
                case 'estado_id':
                    $valor = $this->form['estado_id'];
                    break;
                case 'domicilio_tributario':
                    $valor = $this->direccionForm['domicilio_tributario'];
                    break;
                case 'municipio_id':
                    $valor = $this->direccionForm['municipio_id'];
                    break;
                case 'tipo_direccion_id':
                    $valor = $this->direccionForm['tipo_direccion_id'];
                    break;
            }

            if (empty($valor)) {
                $camposVacios[] = $campo;
            }
        }

        return $camposVacios;
    }

    private function mostrarErrorCampo($campo, $mensaje)
    {
        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;
    }

    public function getClaseCampo($campo)
    {
        // Si hay error de validación, mostrar como inválido
        if ($this->getErrorBag()->has($campo)) {
            return 'is-invalid';
        }
        
        // Si es el campo con error de validación backend, mostrar como campo obligatorio vacío
        if ($this->campoConError === $campo) {
            return 'campo-obligatorio-vacio';
        }
        
        return '';
    }
}
