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
        'rtn_identidad' => '',
        'tipo_persona_id' => null,
        'tipo_cliente_id' => null,
        'estado_id' => 1,
        'direccion' => '',
    ];

    // Variables para validación de RTN único
    public $rtnExiste = false;

    // Datos para los selectores
    public $tiposPersona = [];
    public $tiposCliente = [];

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
            'form.telefono' => 'nullable|max:9',
            'form.rtn_identidad' => [
                'required',
                'max:13',
                'min:13',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $exists = Cliente::where('identidad', $value)
                                      ->when($this->isEditing, function ($query) {
                                          return $query->where('id', '!=', $this->clienteId);
                                      })
                                      ->exists();
                        if ($exists) {
                            $fail('Este RTN/Identidad ya está registrado en el sistema.');
                        }
                    }
                }
            ],
            'form.tipo_persona_id' => 'required|exists:tipo_persona,id',
            'form.tipo_cliente_id' => 'required|exists:tipo_cliente,id',
            'form.direccion' => 'nullable|max:500',
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
            'form.rtn_identidad.required' => 'El RTN/Identidad es obligatorio',
            'form.rtn_identidad.min' => 'El RTN/Identidad debe tener exactamente 13 dígitos',
            'form.rtn_identidad.max' => 'El RTN/Identidad debe tener exactamente 13 dígitos',
            'form.tipo_persona_id.required' => 'Debe seleccionar un tipo de persona',
            'form.tipo_persona_id.exists' => 'El tipo de persona seleccionado no es válido',
            'form.tipo_cliente_id.required' => 'Debe seleccionar un tipo de cliente',
            'form.tipo_cliente_id.exists' => 'El tipo de cliente seleccionado no es válido',
            'form.direccion.max' => 'La dirección no puede exceder 500 caracteres',
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
            $cliente = Cliente::findOrFail($this->clienteId);

            $this->form = [
                'nombre' => $cliente->nombre ?? '',
                'correo' => $cliente->correo ?? '',
                'telefono' => $cliente->telefono ?? '',
                'rtn_identidad' => $cliente->identidad ?? '',
                'tipo_persona_id' => $cliente->tipo_persona_id,
                'tipo_cliente_id' => $cliente->tipo_cliente_id,
                'direccion' => $cliente->direccion ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Error al cargar cliente', [
                'cliente_id' => $this->clienteId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar el cliente');
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
            $this->rtnExiste = false;

            // Validar RTN único antes de continuar
            $this->validarRtnUnico();
            if ($this->rtnExiste) {
                return;
            }

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

            // Preparar datos del cliente
            $clienteData = [
                'nombre' => $this->form['nombre'],
                'correo' => $this->form['correo'],
                'telefono' => $this->form['telefono'],
                'identidad' => $this->form['rtn_identidad'], // Guardar RTN/Identidad en campo identidad
                'tipo_persona_id' => $this->form['tipo_persona_id'],
                'tipo_cliente_id' => $this->form['tipo_cliente_id'],
                'direccion' => $this->form['direccion'],
                'users_id' => Auth::id(),
                'estado_id' => 1,
            ];

            if ($this->isEditing) {
                // Actualizar cliente existente
                Cliente::where('id', $this->clienteId)->update($clienteData);
                $this->mostrarExito('Actor actualizado exitosamente');
            } else {
                // Crear nuevo cliente
                Cliente::create($clienteData);
                $this->mostrarExito('Actor creado exitosamente');
                
                // Limpiar formulario después de crear
                $this->reset('form');
                $this->rtnExiste = false;
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
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Error al guardar el actor');
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

    public function validarRtnUnico()
    {
        $this->rtnExiste = false;
        
        if (!empty($this->form['rtn_identidad'])) {
            try {
                // Validar longitud
                if (strlen($this->form['rtn_identidad']) !== 13) {
                    $this->mostrarErrorCampo('form.rtn_identidad', 'El RTN/Identidad debe tener exactamente 13 dígitos');
                    return;
                }

                // Validar que solo contenga números
                if (!preg_match('/^\d{13}$/', $this->form['rtn_identidad'])) {
                    $this->mostrarErrorCampo('form.rtn_identidad', 'El RTN/Identidad solo debe contener números');
                    return;
                }

                // Verificar si ya existe
                $existe = Cliente::where('identidad', $this->form['rtn_identidad'])
                ->when($this->isEditing, function ($query) {
                    return $query->where('id', '!=', $this->clienteId);
                })
                ->exists();

                if ($existe) {
                    $this->rtnExiste = true;
                    $this->mostrarErrorCampo('form.rtn_identidad', 'Este RTN/Identidad ya está registrado en el sistema');
                } else {
                    $this->rtnExiste = false;
                    $this->limpiarErrorCampo('form.rtn_identidad');
                }
                
            } catch (\Exception $e) {
                Log::error('Error al validar RTN único', [
                    'rtn_identidad' => $this->form['rtn_identidad'],
                    'mensaje' => $e->getMessage()
                ]);
            }
        } else {
            $this->limpiarErrorCampo('form.rtn_identidad');
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
