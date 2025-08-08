<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\TipoPersona;
use App\Models\TipoCliente;
use App\Models\Estado;
use App\Models\Direccion;
use App\Models\TipoDireccion;
use App\Models\Departamento;
use App\Models\Municipio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClienteForm extends Component
{
    public $clienteId;
    public $isEditing = false;

    // Formulario principal de cliente
    public $form = [
        'nombre' => '',
        'correo' => '',
        'telefono' => '',
        'identidad' => '',
        'rtn' => '',
        'tipo_persona_id' => null,
        'tipo_cliente_id' => null,
        'estado_id' => 1,
    ];

    // Formulario de dirección (sin domicilio tributario)
    public $direccionForm = [
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
    public $tiposPersona = [];
    public $tiposCliente = [];
    public $estados = [];
    public $tiposDireccion = [];
    public $departamentos = [];
    public $municipios = [];
    public $departamentoSeleccionado = null;

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';
    public $camposConError = [];
    public $erroresValidacion = [];
    
    // Propiedades para modales de éxito y error
    public $mostrarModalExito = false;
    public $mensajeModalExito = '';
    public $mostrarModalError = false;
    public $mensajeModalError = '';

    protected function rules()
    {
        return [
            'form.nombre' => 'required|min:2|max:150',
            'form.correo' => 'nullable|email|max:45',
            'form.telefono' => 'nullable|max:9|regex:/^\d{4}-\d{4}$/',
            'form.identidad' => [
                'nullable',
                'max:45',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $exists = Cliente::where('identidad', $value)
                                      ->when($this->isEditing, function ($query) {
                                          return $query->where('id', '!=', $this->clienteId);
                                      })
                                      ->exists();
                        if ($exists) {
                            $fail('Esta identidad ya está registrada por otro cliente.');
                        }
                    }
                }
            ],
            'form.rtn' => [
                'nullable',
                'max:45',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $exists = Cliente::where('rtn', $value)
                                      ->when($this->isEditing, function ($query) {
                                          return $query->where('id', '!=', $this->clienteId);
                                      })
                                      ->exists();
                        if ($exists) {
                            $fail('Este RTN ya está registrado por otro cliente.');
                        }
                    }
                }
            ],
            'form.tipo_persona_id' => 'required|exists:tipo_persona,id',
            'form.tipo_cliente_id' => 'required|exists:tipo_cliente,id',
            'form.estado_id' => 'required|exists:estado,id',
            
            // Validaciones de dirección (sin domicilio tributario)
            'direccionForm.tipo_direccion_id' => 'required|exists:tipo_direccion,id',
            'direccionForm.municipio_id' => 'required|exists:municipio,id',
            'direccionForm.colonia' => 'nullable|max:100',
            'direccionForm.calle_blv' => 'nullable|max:100',
            'direccionForm.sector_zona' => 'nullable|max:100',
            'direccionForm.bloque' => 'nullable|max:50',
            'direccionForm.latitud' => 'nullable|numeric',
            'direccionForm.longitud' => 'nullable|numeric',
        ];
    }

    protected function messages()
    {
        return [
            'form.nombre.required' => 'El nombre del cliente es obligatorio',
            'form.nombre.min' => 'El nombre debe tener al menos 2 caracteres',
            'form.nombre.max' => 'El nombre no puede exceder 150 caracteres',
            'form.correo.email' => 'El formato del correo electrónico no es válido',
            'form.correo.max' => 'El correo no puede exceder 45 caracteres',
            'form.telefono.max' => 'El teléfono no puede exceder 9 caracteres',
            'form.telefono.regex' => 'El formato del teléfono debe ser ####-####',
            'form.identidad.max' => 'El número de identidad no puede exceder 45 caracteres',
            'form.rtn.max' => 'El RTN no puede exceder 45 caracteres',
            'form.tipo_persona_id.required' => 'Debe seleccionar un tipo de persona',
            'form.tipo_persona_id.exists' => 'El tipo de persona seleccionado no es válido',
            'form.tipo_cliente_id.required' => 'Debe seleccionar un tipo de cliente',
            'form.tipo_cliente_id.exists' => 'El tipo de cliente seleccionado no es válido',
            
            // Mensajes de dirección (sin domicilio tributario)
            'direccionForm.tipo_direccion_id.required' => 'Debe seleccionar un tipo de dirección',
            'direccionForm.municipio_id.required' => 'Debe seleccionar un municipio',
        ];
    }

    public function mount($clienteId = null)
    {
        $this->cargarDatosIniciales();

        if ($clienteId) {
            $this->clienteId = $clienteId;
            $this->isEditing = true;
            $this->cargarCliente();
        }
    }

    private function cargarDatosIniciales()
    {
        try {
            $this->tiposPersona = TipoPersona::activos()->orderBy('nombre')->get();
            $this->tiposCliente = TipoCliente::activos()->orderBy('nombre')->get();
            $this->estados = Estado::orderBy('descripcion')->get();
            $this->tiposDireccion = TipoDireccion::where('nombre', '!=', 'Tienda')->orderBy('nombre')->get();
            $this->departamentos = Departamento::orderBy('nombre')->get();
            
        } catch (\Exception $e) {
            Log::error('Error al cargar datos iniciales para cliente', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->mostrarError('Error al cargar los datos iniciales');
        }
    }

    private function cargarCliente()
    {
        try {
            $cliente = Cliente::with('direccion.municipio.departamento')->findOrFail($this->clienteId);
            
            $this->form = [
                'nombre' => $cliente->nombre,
                'correo' => $cliente->correo,
                'telefono' => $cliente->telefono,
                'identidad' => $cliente->identidad,
                'rtn' => $cliente->rtn,
                'tipo_persona_id' => $cliente->tipo_persona_id,
                'tipo_cliente_id' => $cliente->tipo_cliente_id,
                'estado_id' => $cliente->estado_id,
            ];

            // Cargar dirección si existe
            if ($cliente->direccion) {
                $this->direccionForm = [
                    'colonia' => $cliente->direccion->colonia,
                    'calle_blv' => $cliente->direccion->calle_blv,
                    'sector_zona' => $cliente->direccion->sector_zona,
                    'bloque' => $cliente->direccion->bloque,
                    'tipo_direccion_id' => $cliente->direccion->tipo_direccion_id,
                    'municipio_id' => $cliente->direccion->municipio_id,
                    'estado_id' => $cliente->direccion->estado_id,
                    'latitud' => $cliente->direccion->latitud,
                    'longitud' => $cliente->direccion->longitud,
                ];

                // Cargar departamento y municipios si existe dirección
                if ($cliente->direccion->municipio) {
                    $this->departamentoSeleccionado = $cliente->direccion->municipio->departamento_id;
                    $this->cargarMunicipios();
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error al cargar cliente', [
                'cliente_id' => $this->clienteId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar el cliente');
        }
    }

    // Método para cargar municipios cuando cambia el departamento
    public function updatedDepartamentoSeleccionado()
    {
        $this->direccionForm['municipio_id'] = null;
        $this->cargarMunicipios();
        
        // Limpiar error de departamento si tenía
        $this->limpiarErrorCampo('departamentoSeleccionado');
        // También limpiar error de municipio ya que se resetea
        $this->limpiarErrorCampo('direccionForm.municipio_id');
    }

    private function cargarMunicipios()
    {
        try {
            if ($this->departamentoSeleccionado) {
                $this->municipios = Municipio::where('departamento_id', $this->departamentoSeleccionado)
                                           ->orderBy('nombre')
                                           ->get();
            } else {
                $this->municipios = [];
            }
        } catch (\Exception $e) {
            Log::error('Error al cargar municipios', [
                'departamento_id' => $this->departamentoSeleccionado,
                'mensaje' => $e->getMessage()
            ]);
            $this->municipios = [];
        }
    }

    public function render()
    {
        return view('livewire.sala-de-ventas.cliente-form');
    }

    public function guardar()
    {
        try {
            // Limpiar alertas previas
            $this->cerrarAlerta();
            
            // Validar formulario
            $this->validate();
            
            // Verificar si hay errores después de la validación
            if ($this->getErrorBag()->isNotEmpty()) {
                $errors = $this->getErrorBag()->toArray();
                $firstError = collect($errors)->flatten()->first();
                $firstField = array_key_first($errors);
                
                Log::info('Errores de validación detectados', [
                    'errores' => $errors,
                    'primer_error' => $firstError,
                    'primer_campo' => $firstField
                ]);
                
                $this->mostrarAlerta($firstError, $firstField);
                return;
            }

            // Crear o actualizar dirección primero
            $direccionData = $this->direccionForm;
            $direccionData['users_id'] = Auth::id();

            if ($this->isEditing && Cliente::find($this->clienteId)->direccion) {
                // Actualizar dirección existente
                $direccion = Cliente::find($this->clienteId)->direccion;
                $direccion->update($direccionData);
            } else {
                // Crear nueva dirección
                $direccion = Direccion::create($direccionData);
            }

            // Preparar datos del cliente
            $clienteData = $this->form;
            $clienteData['direccion_id'] = $direccion->id;
            $clienteData['users_id'] = Auth::id();

            if ($this->isEditing) {
                // Actualizar cliente existente
                Cliente::actualizarCliente($this->clienteId, $clienteData);
                $this->mostrarExito('Cliente actualizado exitosamente');
            } else {
                // Crear nuevo cliente
                Cliente::crearCliente($clienteData);
                $this->mostrarExito('Cliente creado exitosamente');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación - mostrar alerta con el primer error
            Log::info('Errores de validación en cliente (catch)', [
                'errores' => $e->errors()
            ]);
            
            // Obtener el primer error para mostrar en la alerta
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            $firstField = array_key_first($errors);
            
            $this->mostrarAlerta($firstError, $firstField);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar cliente', [
                'mensaje' => $e->getMessage(),
                'datos' => $this->form,
                'direccion' => $this->direccionForm,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Error al guardar el cliente');
        }
    }

    public function cancelar()
    {
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.Clientes');
    }

    // ===== MÉTODOS DE GESTIÓN DE MODALES =====

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
        $this->mensajeModalExito = '';
        // Navegar de vuelta a la lista después del éxito
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.Clientes');
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormNombre()
    {
        try {
            $this->validateOnly('form.nombre');
            $this->limpiarErrorCampo('form.nombre');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('form.nombre', 'El nombre del cliente es obligatorio');
        }
    }

    public function updatedFormCorreo()
    {
        if (!empty($this->form['correo'])) {
            try {
                $this->validateOnly('form.correo');
                $this->limpiarErrorCampo('form.correo');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('form.correo', 'El formato del correo electrónico no es válido');
            }
        } else {
            $this->limpiarErrorCampo('form.correo');
        }
    }

    public function updatedFormIdentidad()
    {
        if (!empty($this->form['identidad'])) {
            try {
                $this->validateOnly('form.identidad');
                $this->limpiarErrorCampo('form.identidad');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('form.identidad', 'Esta identidad ya está registrada por otro cliente');
            }
        } else {
            $this->limpiarErrorCampo('form.identidad');
        }
    }

    public function updatedFormRtn()
    {
        if (!empty($this->form['rtn'])) {
            try {
                $this->validateOnly('form.rtn');
                $this->limpiarErrorCampo('form.rtn');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('form.rtn', 'Este RTN ya está registrado por otro cliente');
            }
        } else {
            $this->limpiarErrorCampo('form.rtn');
        }
    }

    public function updatedFormTipoPersonaId()
    {
        try {
            $this->validateOnly('form.tipo_persona_id');
            $this->limpiarErrorCampo('form.tipo_persona_id');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('form.tipo_persona_id', 'Debe seleccionar un tipo de persona');
        }
    }

    public function updatedFormTipoClienteId()
    {
        try {
            $this->validateOnly('form.tipo_cliente_id');
            $this->limpiarErrorCampo('form.tipo_cliente_id');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('form.tipo_cliente_id', 'Debe seleccionar un tipo de cliente');
        }
    }

    // Métodos para campos de dirección
    public function updatedDireccionFormTipoDireccionId()
    {
        try {
            $this->validateOnly('direccionForm.tipo_direccion_id');
            $this->limpiarErrorCampo('direccionForm.tipo_direccion_id');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('direccionForm.tipo_direccion_id', 'Debe seleccionar un tipo de dirección');
        }
    }

    public function updatedDireccionFormMunicipioId()
    {
        try {
            $this->validateOnly('direccionForm.municipio_id');
            $this->limpiarErrorCampo('direccionForm.municipio_id');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('direccionForm.municipio_id', 'Debe seleccionar un municipio');
        }
    }

    // Método para obtener clases CSS según validación
    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }
        
        return '';
    }

    // ===== MÉTODOS DE GESTIÓN DE ALERTAS =====

    public function mostrarAlerta($mensaje, $campo = '')
    {
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;
        $this->mostrarAlerta = true;
        
        // Agregar campo a la lista de errores si se especifica
        if (!empty($campo)) {
            $this->mostrarErrorCampo($campo, $mensaje);
        }
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
        $this->camposConError = [];
        $this->erroresValidacion = [];
    }

    private function mostrarErrorCampo($campo, $mensaje)
    {
        $this->camposConError[] = $campo;
        $this->camposConError = array_unique($this->camposConError);

        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;

        // Guardar error en array de errores
        $this->erroresValidacion[$campo] = $mensaje;
    }

    private function limpiarErrorCampo($campo)
    {
        // Remover de errores
        $this->camposConError = array_filter($this->camposConError, function($c) use ($campo) {
            return $c !== $campo;
        });

        // Remover de errores de validación
        unset($this->erroresValidacion[$campo]);
        
        // Si no hay más campos con error, ocultar la alerta
        if (empty($this->camposConError)) {
            $this->mostrarAlerta = false;
            $this->mensajeAlerta = '';
            $this->campoConError = '';
        }
    }
}
