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
use App\Models\Empresa;
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
        'empresa_id' => null,
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
    public $empresas = [];
    public $tiposDireccion = [];
    public $departamentos = [];
    public $municipios = [];
    public $departamentoSeleccionado = null;

    // Control para sucursal principal
    public $existeSucursalPrincipal = false;
    public $tipoTiendaSucursal = null;

    // Control para tipo de dirección (siempre Tienda)
    public $tipoDireccionTienda = null;

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    // Propiedades para modales de éxito y error
    public $mostrarModalExito = false;
    public $mensajeModalExito = '';
    public $mostrarModalError = false;
    public $mensajeModalError = '';

    protected function rules()
    {
        return [
            'form.denominacion_social' => 'required|min:2|max:145',
            'form.telefono' => [
                'nullable',
                'max:45',
                'regex:/^\d{4}-\d{4}$/'
            ],
            'form.celular' => [
                'nullable',
                'max:45',
                'regex:/^\d{4}-\d{4}$/'
            ],
            'form.correo' => 'nullable|email|max:45',
            'form.tipo_tienda_id' => 'required|exists:tipo_tienda,id',
            'form.estado_id' => 'required|exists:estado,id',
            'form.empresa_id' => 'required|exists:empresa,id',
            'form.numero_sucursal' => 'nullable|max:45',
            'form.identificador_legal' => [
                'nullable',
                'max:45',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $exists = Tienda::where('identificador_legal', $value)
                                      ->when($this->isEditing, function ($query) {
                                          return $query->where('id', '!=', $this->sucursalId);
                                      })
                                      ->exists();

                        if ($exists) {
                            $fail('Este identificador legal ya está en uso por otra sucursal.');
                        }
                    }
                }
            ],
            'direccionForm.domicilio_tributario' => 'required|max:100',
            'direccionForm.municipio_id' => 'required|exists:municipio,id',
            'direccionForm.tipo_direccion_id' => 'required|exists:tipo_direccion,id',
        ];
    }

    protected $messages = [
        'form.denominacion_social.required' => 'La denominación social es obligatoria.',
        'form.denominacion_social.min' => 'La denominación social debe tener al menos 2 caracteres.',
        'form.denominacion_social.max' => 'La denominación social no puede exceder 145 caracteres.',
        'form.telefono.regex' => 'El teléfono debe tener el formato ####-#### (ejemplo: 2234-5678).',
        'form.celular.regex' => 'El celular debe tener el formato ####-#### (ejemplo: 9876-5432).',
        'form.correo.email' => 'El formato del correo electrónico no es válido.',
        'form.tipo_tienda_id.required' => 'El tipo de tienda es obligatorio.',
        'form.estado_id.required' => 'El estado es obligatorio.',
        'direccionForm.domicilio_tributario.required' => 'El domicilio tributario es obligatorio.',
        'direccionForm.municipio_id.required' => 'El municipio es obligatorio.',
        'direccionForm.tipo_direccion_id.required' => 'El tipo de dirección es obligatorio.',
    ];

    public function hydrate()
    {
        // Asegurar que los municipios se mantengan cargados si hay un departamento seleccionado
        if ($this->departamentoSeleccionado && empty($this->municipios)) {
            $this->municipios = Municipio::where('departamento_id', $this->departamentoSeleccionado)
                                       ->orderBy('nombre')
                                       ->get();
        }
    }

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
        $this->empresas = Empresa::orderBy('nombre')->get();
        $this->tiposDireccion = TipoDireccion::orderBy('nombre')->get();
        $this->departamentos = Departamento::orderBy('nombre')->get();

        // Configurar tipo de dirección como "Tienda" automáticamente
        $this->configurarTipoDireccionTienda();

        // Verificar si ya existe una sucursal principal
        $this->verificarSucursalPrincipal();
    }

    private function configurarTipoDireccionTienda()
    {
        // Buscar tipo de dirección "Tienda"
        $this->tipoDireccionTienda = TipoDireccion::where('nombre', 'LIKE', '%tienda%')
                                                  ->orWhere('nombre', 'LIKE', '%Tienda%')
                                                  ->orWhere('nombre', 'LIKE', '%TIENDA%')
                                                  ->first();

        // Si existe el tipo "Tienda", configurarlo automáticamente
        if ($this->tipoDireccionTienda) {
            $this->direccionForm['tipo_direccion_id'] = $this->tipoDireccionTienda->id;
        }
    }

    private function verificarSucursalPrincipal()
    {
        // Buscar tipo de tienda "Principal" (ajusta el nombre según tu BD)
        $tipoTiendaPrincipal = TipoTienda::where('nombre', 'LIKE', '%principal%')
                                        ->orWhere('nombre', 'LIKE', '%Principal%')
                                        ->orWhere('nombre', 'LIKE', '%PRINCIPAL%')
                                        ->first();

        if ($tipoTiendaPrincipal) {
            // Verificar si ya existe una sucursal con tipo principal
            $sucursalPrincipalExiste = Tienda::where('tipo_tienda_id', $tipoTiendaPrincipal->id)
                                           ->where('id', '!=', $this->sucursalId ?? 0) // Excluir la sucursal actual si está editando
                                           ->exists();

            if ($sucursalPrincipalExiste) {
                $this->existeSucursalPrincipal = true;

                // Buscar tipo de tienda "Sucursal"
                $this->tipoTiendaSucursal = TipoTienda::where('nombre', 'LIKE', '%sucursal%')
                                                     ->orWhere('nombre', 'LIKE', '%Sucursal%')
                                                     ->orWhere('nombre', 'LIKE', '%SUCURSAL%')
                                                     ->first();

                // Si existe sucursal principal y no estamos editando una sucursal principal, forzar tipo sucursal
                if ($this->tipoTiendaSucursal && !$this->isEditing) {
                    $this->form['tipo_tienda_id'] = $this->tipoTiendaSucursal->id;
                }
            }
        }
    }

    public function updatedDepartamentoSeleccionado($departamentoId)
    {
        if ($departamentoId) {
            $this->municipios = Municipio::where('departamento_id', $departamentoId)
                                       ->orderBy('nombre')
                                       ->get();
            $this->direccionForm['municipio_id'] = null;

            // Limpiar error de municipio si había uno
            $this->limpiarErrorCampo('municipio_id');
        } else {
            $this->municipios = [];
            $this->direccionForm['municipio_id'] = null;
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
                'empresa_id' => $sucursal->empresa_id,
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

                // Cargar departamento y municipios correspondientes al editar
                if ($sucursal->direccion->municipio && $sucursal->direccion->municipio->departamento) {
                    $this->departamentoSeleccionado = $sucursal->direccion->municipio->departamento->id;
                    $this->municipios = Municipio::where('departamento_id', $this->departamentoSeleccionado)
                                               ->orderBy('nombre')
                                               ->get();

                    // Asegurar que el municipio se mantenga seleccionado
                    $this->direccionForm['municipio_id'] = $sucursal->direccion->municipio_id;
                }
            }

            // Reconfigurar tipo de dirección como "Tienda" (sin afectar municipio)
            $tipoDireccionOriginal = $this->direccionForm['tipo_direccion_id'];
            $this->configurarTipoDireccionTienda();

            // Si estamos editando, mantener el tipo de dirección original si ya existe
            if ($this->isEditing && $tipoDireccionOriginal) {
                $this->direccionForm['tipo_direccion_id'] = $tipoDireccionOriginal;
            }

            // Verificar nuevamente después de cargar los datos de la sucursal
            $this->verificarSucursalPrincipal();
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
                'empresa_id' => 'Debe seleccionar una empresa',
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
                $this->mensajeModalExito = 'Sucursal actualizada correctamente.';
            } else {
                $this->form['users_creador_id'] = Auth::id();
                Tienda::create($this->form);
                $this->mensajeModalExito = 'Sucursal creada correctamente.';
            }

            $this->mostrarModalExito = true;

        } catch (\Exception $e) {
            Log::error('Error al guardar sucursal: ' . $e->getMessage());
            $this->mensajeModalError = 'Error al guardar la sucursal. Por favor, inténtelo de nuevo.';
            $this->mostrarModalError = true;
        }
    }

    public function cancelar()
    {
        $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursales');
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormDenominacionSocial()
    {
        if (empty($this->form['denominacion_social'])) {
            $this->mostrarErrorCampo('denominacion_social', 'La denominación social es obligatoria');
        } else {
            try {
                $this->validateOnly('form.denominacion_social');
                $this->limpiarErrorCampo('denominacion_social');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('denominacion_social', 'La denominación social es obligatoria');
            }
        }
    }

    public function updatedFormTipoTiendaId()
    {
        if (empty($this->form['tipo_tienda_id'])) {
            $this->mostrarErrorCampo('tipo_tienda_id', 'Debe seleccionar un tipo de tienda');
        } else {
            try {
                $this->validateOnly('form.tipo_tienda_id');
                $this->limpiarErrorCampo('tipo_tienda_id');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('tipo_tienda_id', 'Debe seleccionar un tipo de tienda');
            }
        }
    }

    public function updatedFormEstadoId()
    {
        if (empty($this->form['estado_id'])) {
            $this->mostrarErrorCampo('estado_id', 'Debe seleccionar un estado');
        } else {
            try {
                $this->validateOnly('form.estado_id');
                $this->limpiarErrorCampo('estado_id');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('estado_id', 'Debe seleccionar un estado');
            }
        }
    }

    public function updatedDireccionFormDomicilioTributario()
    {
        if (empty($this->direccionForm['domicilio_tributario'])) {
            $this->mostrarErrorCampo('domicilio_tributario', 'El domicilio tributario es obligatorio');
        } else {
            try {
                $this->validateOnly('direccionForm.domicilio_tributario');
                $this->limpiarErrorCampo('domicilio_tributario');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('domicilio_tributario', 'El domicilio tributario es obligatorio');
            }
        }
    }

    public function updatedDireccionFormMunicipioId()
    {
        // Verificar que se haya seleccionado un departamento primero
        if (empty($this->departamentoSeleccionado)) {
            $this->mostrarErrorCampo('municipio_id', 'Debe seleccionar un departamento primero');
            $this->direccionForm['municipio_id'] = null;
            return;
        }

        if (empty($this->direccionForm['municipio_id'])) {
            $this->mostrarErrorCampo('municipio_id', 'Debe seleccionar un municipio');
        } else {
            try {
                $this->validateOnly('direccionForm.municipio_id');
                $this->limpiarErrorCampo('municipio_id');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('municipio_id', 'Debe seleccionar un municipio');
            }
        }
    }

    public function updatedDireccionFormTipoDireccionId()
    {
        if (empty($this->direccionForm['tipo_direccion_id'])) {
            $this->mostrarErrorCampo('tipo_direccion_id', 'Debe seleccionar un tipo de dirección');
        } else {
            try {
                $this->validateOnly('direccionForm.tipo_direccion_id');
                $this->limpiarErrorCampo('tipo_direccion_id');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('tipo_direccion_id', 'Debe seleccionar un tipo de dirección');
            }
        }
    }

    public function updatedFormCorreo()
    {
        // El correo no es obligatorio, solo validar formato si tiene valor
        if (!empty($this->form['correo'])) {
            try {
                $this->validateOnly('form.correo');
                $this->limpiarErrorCampo('correo');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->mostrarErrorCampo('correo', 'El formato del correo electrónico no es válido');
            }
        } else {
            // Si está vacío, limpiar cualquier error previo
            $this->limpiarErrorCampo('correo');
        }
    }

    public function updatedFormIdentificadorLegal()
    {
        // El identificador legal no es obligatorio, pero debe ser único si se proporciona
        if (!empty($this->form['identificador_legal'])) {
            // Verificar si ya existe en otra sucursal
            $exists = Tienda::where('identificador_legal', $this->form['identificador_legal'])
                           ->when($this->isEditing, function ($query) {
                               return $query->where('id', '!=', $this->sucursalId);
                           })
                           ->exists();

            if ($exists) {
                $this->mostrarErrorCampo('identificador_legal', 'Este identificador legal ya está en uso por otra sucursal');
            } else {
                try {
                    $this->validateOnly('form.identificador_legal');
                    $this->limpiarErrorCampo('identificador_legal');
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->mostrarErrorCampo('identificador_legal', 'El identificador legal no es válido');
                }
            }
        } else {
            // Si está vacío, limpiar cualquier error previo
            $this->limpiarErrorCampo('identificador_legal');
        }
    }

    public function updatedFormTelefono()
    {
        // El teléfono no es obligatorio, pero debe tener formato correcto si se proporciona
        if (!empty($this->form['telefono'])) {
            // Verificar formato ####-####
            if (!preg_match('/^\d{4}-\d{4}$/', $this->form['telefono'])) {
                $this->mostrarErrorCampo('telefono', 'El teléfono debe tener el formato ####-#### (ejemplo: 2234-5678)');
            } else {
                try {
                    $this->validateOnly('form.telefono');
                    $this->limpiarErrorCampo('telefono');
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->mostrarErrorCampo('telefono', 'El formato del teléfono no es válido');
                }
            }
        } else {
            // Si está vacío, limpiar cualquier error previo
            $this->limpiarErrorCampo('telefono');
        }
    }

    public function updatedFormCelular()
    {
        // El celular no es obligatorio, pero debe tener formato correcto si se proporciona
        if (!empty($this->form['celular'])) {
            // Verificar formato ####-####
            if (!preg_match('/^\d{4}-\d{4}$/', $this->form['celular'])) {
                $this->mostrarErrorCampo('celular', 'El celular debe tener el formato ####-#### (ejemplo: 9876-5432)');
            } else {
                try {
                    $this->validateOnly('form.celular');
                    $this->limpiarErrorCampo('celular');
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $this->mostrarErrorCampo('celular', 'El formato del celular no es válido');
                }
            }
        } else {
            // Si está vacío, limpiar cualquier error previo
            $this->limpiarErrorCampo('celular');
        }
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
        $camposCriticos = ['denominacion_social', 'tipo_tienda_id', 'estado_id', 'empresa_id', 'domicilio_tributario', 'municipio_id', 'tipo_direccion_id'];
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
                case 'empresa_id':
                    $valor = $this->form['empresa_id'];
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

    private function limpiarErrorCampo($campo)
    {
        if ($this->campoConError === $campo) {
            $this->cerrarAlerta();
        }
    }

    public function getClaseCampo($campo)
    {
        // Si hay error de validación, mostrar como inválido
        if ($this->getErrorBag()->has($campo)) {
            return 'is-invalid';
        }

        // Mapear nombres de campos para validación backend
        $mapasCampos = [
            'form.denominacion_social' => 'denominacion_social',
            'form.tipo_tienda_id' => 'tipo_tienda_id',
            'form.estado_id' => 'estado_id',
            'form.correo' => 'correo',
            'form.telefono' => 'telefono',
            'form.celular' => 'celular',
            'form.identificador_legal' => 'identificador_legal',
            'direccionForm.domicilio_tributario' => 'domicilio_tributario',
            'direccionForm.municipio_id' => 'municipio_id',
            'direccionForm.tipo_direccion_id' => 'tipo_direccion_id',
            'departamentoSeleccionado' => 'departamento'
        ];

        $campoMapeado = $mapasCampos[$campo] ?? null;

        // Si es el campo con error de validación backend, mostrar como campo obligatorio vacío
        if ($campoMapeado && $this->campoConError === $campoMapeado) {
            return 'campo-obligatorio-vacio';
        }

        return '';
    }

    // ===== MÉTODOS PARA MODALES =====

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';

        // Redirigir a la lista después de cerrar el modal
        return $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursales');
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }
}
