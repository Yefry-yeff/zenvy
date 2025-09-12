<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Producto as ProductoModel;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use App\Models\IdZenvyValencia;
use App\Services\SincronizacionMarcasService;
use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductoForm extends Component
{
    use WithFileUploads;
    
    public $productoId;
    public $isEditing = false;

    // Formulario principal
    public $form = [
        'nombre' => '',
        'descripcion' => '',
        'codigo_barra' => '',
        'codigo_estatal' => '',
        'estado_id' => 1,
        'subcategoria_id' => null,
        'marca_id' => null,
        'isv_id' => null,
        'precio_base' => 0,
        'descuento_unitario' => 0,
        'descuento_tercera' => false,
        'descuento_cuarta' => false,
        'ultimo_costo_compra' => 0,
        'costo_promedio' => 0,
        'unidad_medida_venta_id' => null,
        'users_id' => null,
        'producto_valencia' => 0,
        'precio1' => 0,
        'precio2' => 0,
        'precio3' => 0,
        'precio4' => 0,
    ];

    // Datos para los selectores
    public $categorias = [];
    public $subcategorias = [];
    public $marcas = [];
    public $unidadesMedida = [];
    public $isvs = [];
    public $categoriaSeleccionada = null;

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    // Propiedades para manejo de imagen
    public $imagen;
    public $tieneImagenAnterior = false; // Solo flag para saber si existe
    public $camposConError = [];
    public $erroresValidacion = [];

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Propiedades para sincronización con Valencia
    public $esProductoValencia = false;
    public $productosValencia = [];
    public $sincronizandoValencia = false;
    private $sincronizacionProductosService;

    protected $rules = [
        'form.nombre' => 'required|string|max:80',
        'form.descripcion' => 'nullable|string|max:45',
        'form.codigo_barra' => 'nullable|string|max:100',
        'form.codigo_estatal' => 'nullable|string|max:45',
        'form.estado_id' => 'required|integer',
        'form.subcategoria_id' => 'required|integer|exists:subcategoria,id',
        'form.marca_id' => 'required|integer|exists:marca,id',
        'form.isv_id' => 'required|integer|exists:isv,id',
        'form.precio_base' => 'required|numeric|min:0.01',
        'form.descuento_unitario' => 'nullable|numeric|min:0',
        'form.descuento_tercera' => 'boolean',
        'form.descuento_cuarta' => 'boolean',
        'form.ultimo_costo_compra' => 'nullable|numeric|min:0',
        'form.costo_promedio' => 'nullable|numeric|min:0',
        'form.unidad_medida_venta_id' => 'required|integer|exists:unidad_medida,id',
        'form.producto_valencia' => 'nullable|integer|in:0,1',
        'form.precio1' => 'nullable|numeric|min:0',
        'form.precio2' => 'nullable|numeric|min:0',
        'form.precio3' => 'nullable|numeric|min:0',
        'form.precio4' => 'nullable|numeric|min:0',
        'imagen' => 'nullable|image|max:5120', // 5MB máximo
    ];

    protected $messages = [
        'form.nombre.required' => 'El nombre es obligatorio',
        'form.nombre.max' => 'El nombre no puede exceder 80 caracteres',
        'form.descripcion.max' => 'La descripción no puede exceder 45 caracteres',
        'form.codigo_barra.unique' => 'Este código de barras ya está en uso por otro producto',
        'form.subcategoria_id.required' => 'La subcategoría es obligatoria',
        'form.subcategoria_id.exists' => 'La subcategoría seleccionada no existe',
        'form.marca_id.required' => 'La marca es obligatoria',
        'form.marca_id.exists' => 'La marca seleccionada no existe',
        'form.isv_id.required' => 'El tipo de ISV es obligatorio',
        'form.isv_id.exists' => 'El tipo de ISV seleccionado no existe',
        'form.precio_base.required' => 'El precio base es obligatorio',
        'form.precio_base.min' => 'El precio base debe ser mayor a 0',
        'form.descuento_unitario.min' => 'El descuento unitario no puede ser negativo',
        'form.unidad_medida_venta_id.required' => 'La unidad de medida es obligatoria',
        'form.unidad_medida_venta_id.exists' => 'La unidad de medida seleccionada no existe',
        'imagen.image' => 'El archivo debe ser una imagen válida',
        'imagen.max' => 'La imagen no puede ser mayor a 5MB',
    ];

    public function mount($id = null)
    {
        $this->cargarDatosIniciales();

        if ($id) {
            $this->productoId = $id;
            $this->isEditing = true;
            $this->cargarProducto();
            $this->verificarSiEsProductoValencia();
        }
        
        $this->cargarProductosValencia();
    }

    private function getSincronizacionProductosService()
    {
        if (!$this->sincronizacionProductosService) {
            $this->sincronizacionProductosService = SincronizacionProductosService::obtenerInstancia();
        }
        return $this->sincronizacionProductosService;
    }

    public function cargarDatosIniciales()
    {
        $this->categorias = Categoria::orderBy('nombre')->get();
        
        // Usar el servicio de sincronización para obtener marcas actualizadas
        $sincronizacionService = new SincronizacionMarcasService();
        $this->marcas = $sincronizacionService->obtenerMarcasDirectas();
        
        $this->unidadesMedida = DB::table('unidad_medida')->orderBy('nombre')->get();
        $this->isvs = DB::table('isv')->orderBy('cantidad')->get();
    }

    public function cargarProducto()
    {
        $producto = ProductoModel::find($this->productoId);

        if ($producto) {
            // Verificar si es un producto de Valencia antes de cargar
            $this->verificarSiEsProductoValencia();
            
            // Si es producto de Valencia, sincronizar automáticamente primero
            if ($this->esProductoValencia) {
                try {
                    $mapeo = IdZenvyValencia::where('id_zenvy', $this->productoId)
                        ->where('tipo_dato_migrado_id', 1) // 1 para productos
                        ->first();
                    
                    if ($mapeo) {
                        $this->sincronizandoValencia = true;
                        $this->dispatch('mostrarSincronizacion'); // Evento para UI
                        
                        Log::info("Sincronizando automáticamente producto de Valencia antes de editar. Valencia ID: {$mapeo->id_valencia}, Zenvy ID: {$this->productoId}");
                        
                        $service = $this->getSincronizacionProductosService();
                        $resultado = $service->sincronizarProducto($mapeo->id_valencia, true); // true = auto-sincronización
                        
                        $this->sincronizandoValencia = false;
                        
                        if ($resultado['success']) {
                            // Recargar el producto después de la sincronización
                            $producto = ProductoModel::find($this->productoId);
                            Log::info("Producto de Valencia sincronizado exitosamente antes de editar: " . $resultado['mensaje']);
                            
                            // NO mostrar modal de éxito para sincronización automática
                            // Solo agregar mensaje en log o sesión flash temporal
                            session()->flash('info', "🔄 Producto sincronizado automáticamente con Valencia.");
                        } else {
                            Log::warning("No se pudo sincronizar el producto de Valencia: " . $resultado['mensaje']);
                            // No mostramos error al usuario para no interrumpir la edición
                        }
                    }
                } catch (\Exception $e) {
                    $this->sincronizandoValencia = false;
                    Log::error("Error en sincronización automática: " . $e->getMessage());
                    // Continuar con la carga normal del producto
                }
            }
            
            $this->form = [
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'codigo_barra' => $producto->codigo_barra,
                'codigo_estatal' => $producto->codigo_estatal,
                'estado_id' => $producto->estado_id,
                'subcategoria_id' => $producto->subcategoria_id,
                'marca_id' => $producto->marca_id,
                'isv_id' => $producto->isv_id ?? null,
                'precio_base' => $producto->precio_base ?? 0,
                'descuento_unitario' => $producto->descuento_unitario ?? 0,
                'descuento_tercera' => $producto->descuento_tercera ? true : false,
                'descuento_cuarta' => $producto->descuento_cuarta ? true : false,
                'ultimo_costo_compra' => $producto->ultimo_costo_compra ?? 0,
                'costo_promedio' => $producto->costo_promedio ?? 0,
                'unidad_medida_venta_id' => $producto->unidad_medida_venta_id,
                'users_id' => $producto->users_id,
                'producto_valencia' => $producto->producto_valencia ?? 0,
                'precio1' => $producto->precio1 ?? 0,
                'precio2' => $producto->precio2 ?? 0,
                'precio3' => $producto->precio3 ?? 0,
                'precio4' => $producto->precio4 ?? 0,
            ];

            // Cargar categoría y subcategorías correspondientes
            if ($producto->subcategoria_id) {
                $subcategoria = Subcategoria::find($producto->subcategoria_id);
                if ($subcategoria) {
                    $this->categoriaSeleccionada = $subcategoria->categoria_id;
                    $this->cargarSubcategorias();
                }
            }

            // Marcar si tiene imagen anterior (sin cargar los datos BLOB)
            $this->tieneImagenAnterior = $producto->imagen !== null;
        }
    }

    private function verificarSiEsProductoValencia()
    {
        if ($this->productoId) {
            // Verificar si existe en la tabla de mapeo como producto de Valencia
            $mapeo = IdZenvyValencia::where('id_zenvy', $this->productoId)
                ->where('tipo_dato_migrado_id', 1) // 1 para productos
                ->first();
            
            $this->esProductoValencia = $mapeo !== null;
        }
    }

    public function cargarProductosValencia()
    {
        try {
            $service = $this->getSincronizacionProductosService();
            $productos = $service->obtenerProductosValenciaConEstado();
            $this->productosValencia = ['valencia' => $productos->toArray()];
        } catch (\Exception $e) {
            Log::error('Error al cargar productos de Valencia: ' . $e->getMessage());
            $this->productosValencia = ['valencia' => []];
        }
    }

    public function sincronizarProductosValencia()
    {
        try {
            $service = $this->getSincronizacionProductosService();
            $resultado = $service->sincronizarTodosLosProductos();
            
            if ($resultado['sincronizados'] > 0) {
                session()->flash('message', "Se sincronizaron {$resultado['sincronizados']} productos exitosamente.");
                $this->cargarProductosValencia();
                $this->cargarDatosIniciales(); // Recargar para mostrar nuevos datos
            }
            
            if ($resultado['errores'] > 0) {
                session()->flash('warning', "Hubo {$resultado['errores']} errores durante la sincronización.");
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar productos: ' . $e->getMessage());
        }
    }

    public function sincronizarProductoValencia($idProductoValencia)
    {
        try {
            $service = $this->getSincronizacionProductosService();
            $resultado = $service->sincronizarProducto($idProductoValencia);
            
            if ($resultado['success']) {
                session()->flash('message', $resultado['mensaje']);
                $this->cargarProductosValencia();
                $this->cargarDatosIniciales();
            } else {
                session()->flash('error', $resultado['mensaje']);
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar producto: ' . $e->getMessage());
        }
    }

    public function updatedCategoriaSeleccionada($value)
    {
        $this->form['subcategoria_id'] = null;
        $this->cargarSubcategorias();

        // Limpiar subcategoría si se cambia la categoría
        $this->limpiarErrorCampo('subcategoria');

        // Validar categoría
        if ($this->categoriaSeleccionada) {
            $this->limpiarErrorCampo('categoria');
        } else {
            $this->mostrarErrorCampo('categoria', 'Debe seleccionar una categoría');
        }
    }

    public function cargarSubcategorias()
    {
        if ($this->categoriaSeleccionada) {
            $this->subcategorias = Subcategoria::where('categoria_id', $this->categoriaSeleccionada)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->subcategorias = [];
        }
    }

    public function guardar()
    {
        // Verificar campos críticos antes de la validación completa
        $camposVacios = $this->verificarCamposCriticos();

        if (!empty($camposVacios)) {
            $primerCampoVacio = $camposVacios[0];
            $mensajes = [
                'nombre' => 'El nombre del producto es obligatorio',
                'marca' => 'Debe seleccionar una marca',
                'categoria' => 'Debe seleccionar una categoría',
                'subcategoria' => 'Debe seleccionar una subcategoría',
                'precio_base' => 'El precio base es obligatorio',
                'unidad_medida' => 'Debe seleccionar una unidad de medida',
                'isv_id' => 'Debe seleccionar un tipo de ISV'
            ];

            $this->mostrarErrorCampo($primerCampoVacio, $mensajes[$primerCampoVacio]);
            return;
        }

        // Verificación específica para precio_base = 0
        if ($this->form['precio_base'] == 0) {
            $this->mostrarErrorCampo('precio_base', 'El precio base no puede ser 0, debe ser mayor a 0');
            return;
        }

        // Limpiar alertas antes de validar
        $this->cerrarAlerta();

        try {
            // Validar los datos del formulario con reglas dinámicas
            $this->validate($this->getRules());

            $datos = $this->form;
            $datos['users_id'] = Auth::id();

            // Convertir checkboxes boolean a enteros para el SP
            $datos['descuento_tercera'] = $datos['descuento_tercera'] ? 1 : 0;
            $datos['descuento_cuarta'] = $datos['descuento_cuarta'] ? 1 : 0;

            // Si es producto de Valencia, solo permitir ciertos campos
            if ($this->isEditing && $this->esProductoValencia) {
                // Para productos de Valencia, mantener todos los campos requeridos por el SP
                // pero solo actualizar los campos permitidos
                $producto = ProductoModel::find($this->productoId);
                if ($producto) {
                    // Mantener todos los valores originales y solo actualizar los permitidos
                    $datosPermitidos = [
                        // Campos requeridos por el SP (mantener valores originales)
                        'nombre' => $producto->nombre,
                        'descripcion' => $producto->descripcion,
                        'isv_id' => $producto->isv_id,
                        'precio_base' => $datos['precio_base'], // Permitir edición del precio base
                        'ultimo_costo_compra' => $producto->ultimo_costo_compra,
                        'costo_promedio' => $producto->costo_promedio,
                        'codigo_barra' => $datos['codigo_barra'], // Permitir edición del código de barras
                        'codigo_estatal' => $producto->codigo_estatal,
                        'estado_id' => $producto->estado_id,
                        'subcategoria_id' => $producto->subcategoria_id,
                        'marca_id' => $producto->marca_id,
                        'unidad_medida_venta_id' => $producto->unidad_medida_venta_id,
                        'users_id' => $producto->users_id,
                        
                        // Campos permitidos para edición (valores del formulario)
                        'precio1' => $datos['precio1'],
                        'precio2' => $datos['precio2'],
                        'precio3' => $datos['precio3'],
                        'precio4' => $datos['precio4'],
                        'descuento_unitario' => $datos['descuento_unitario'],
                        'descuento_tercera' => $datos['descuento_tercera'],
                        'descuento_cuarta' => $datos['descuento_cuarta'],
                    ];
                    
                    // Si hay nueva imagen, procesarla
                    if ($this->imagen) {
                        $datosPermitidos['imagen'] = file_get_contents($this->imagen->getRealPath());
                    } else {
                        // Mantener la imagen existente
                        $datosPermitidos['imagen'] = $producto->imagen;
                    }
                    
                    ProductoModel::actualizarProducto($this->productoId, $datosPermitidos);
                    Log::info('Producto Valencia actualizado exitosamente', ['id' => $this->productoId]);
                    $this->mostrarExito('Producto de Valencia actualizado exitosamente.');
                    $this->dispatch('redirigirEnTresSeg');
                    return;
                }
            }

            // Procesar imagen si se subió una nueva
            if ($this->imagen) {
                $datos['imagen'] = file_get_contents($this->imagen->getRealPath());
            } elseif ($this->isEditing && $this->tieneImagenAnterior) {
                // Si estamos editando y no hay nueva imagen, obtener la anterior de la BD
                $productoAnterior = ProductoModel::select('imagen')->find($this->productoId);
                $datos['imagen'] = $productoAnterior ? $productoAnterior->imagen : null;
            } else {
                // No hay imagen
                $datos['imagen'] = null;
            }

            // Log para debugging
            Log::info('Intentando guardar producto', [
                'datos' => $datos,
                'isEditing' => $this->isEditing,
                'productoId' => $this->productoId
            ]);

            if ($this->isEditing) {
                ProductoModel::actualizarProducto($this->productoId, $datos);
                Log::info('Producto actualizado exitosamente', ['id' => $this->productoId]);
                $this->mostrarExito('Producto actualizado exitosamente.');
            } else {
                $resultado = ProductoModel::crearProducto($datos);
                Log::info('Producto creado exitosamente', ['resultado' => $resultado]);
                $this->mostrarExito('Producto creado exitosamente.');
            }

            // Redirigir después de mostrar el modal
            $this->dispatch('redirigirEnTresSeg');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación - mostrar errores específicos
            Log::warning('Error de validación al guardar producto', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            
            // Manejar error específico de código de barras
            if (isset($e->errors()['form.codigo_barra'])) {
                $this->mostrarErrorCampo('codigo_barra', $e->errors()['form.codigo_barra'][0]);
                return;
            }
            
            // Para otros errores de validación
            $primerError = collect($e->errors())->flatten()->first();
            $this->mostrarError('Error de validación: ' . $primerError);

        } catch (\Exception $e) {
            // Error general - log completo y mensaje simple al usuario
            Log::error('Error al guardar producto', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos' => $this->form,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar el producto');
        }
    }

    public function volverALista()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Producto');
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormNombre()
    {
        try {
            $this->validateOnly('form.nombre');
            $this->limpiarErrorCampo('nombre');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('nombre', 'El nombre es obligatorio y no puede estar vacío');
        }
    }

    public function updatedFormMarcaId()
    {
        try {
            $this->validateOnly('form.marca_id');
            $this->limpiarErrorCampo('marca');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('marca', 'Debe seleccionar una marca');
        }
    }

    public function updatedFormSubcategoriaId()
    {
        try {
            $this->validateOnly('form.subcategoria_id');
            $this->limpiarErrorCampo('subcategoria');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('subcategoria', 'Debe seleccionar una subcategoría');
        }
    }

    public function updatedFormCodigoBarra()
    {
        // Limpiar errores previos completamente
        $this->limpiarErrorCampo('codigo_barra');
        $this->resetErrorBag('form.codigo_barra');
        
        // Solo validar si hay contenido en el código de barras
        if (!empty($this->form['codigo_barra'])) {
            try {
                // Crear reglas específicas para este campo
                $codigoBarra = trim($this->form['codigo_barra']);
                
                // Si el código está vacío después del trim, no validar
                if (empty($codigoBarra)) {
                    return;
                }
                
                // Verificar si ya existe el código en otro producto
                $query = ProductoModel::where('codigo_barra', $codigoBarra);
                
                // Si estamos editando, excluir el producto actual
                if ($this->isEditing && $this->productoId) {
                    $query->where('id', '!=', $this->productoId);
                }
                
                $existe = $query->exists();
                
                if ($existe) {
                    $this->mostrarErrorCampo('codigo_barra', 'Este código de barras ya está en uso por otro producto');
                }
                
            } catch (\Exception $e) {
                Log::error('Error en validación de código de barras', ['error' => $e->getMessage()]);
                $this->mostrarErrorCampo('codigo_barra', 'Error al validar el código de barras');
            }
        }
    }

    // ===== MÉTODOS PARA MANEJO DE ERRORES Y ESTILOS =====

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
        // Remover de errores personalizados
        $this->camposConError = array_filter($this->camposConError, function($c) use ($campo) {
            return $c !== $campo;
        });

        // Remover de errores de validación personalizados
        unset($this->erroresValidacion[$campo]);

        // Limpiar errores de validación de Livewire
        $this->resetErrorBag('form.' . $campo);

        // Cerrar alerta si este era el campo con error
        if ($this->campoConError === $campo) {
            $this->cerrarAlerta();
        }
    }

    public function calcularMargenGanancia()
    {
        $ultimoCosto = floatval($this->form['ultimo_costo_compra'] ?? 0);
        $precioBase = floatval($this->form['precio_base'] ?? 0);

        if ($ultimoCosto > 0 && $precioBase > 0) {
            $ganancia = $precioBase - $ultimoCosto;
            $margen = ($ganancia / $ultimoCosto) * 100;
            return round($margen, 2);
        }

        return 0;
    }

    public function updatedFormUltimoCostoCompra()
    {
        $this->dispatch('actualizarMargen', $this->calcularMargenGanancia());
    }

    public function updatedFormPrecioBase()
    {
        $this->dispatch('actualizarMargen', $this->calcularMargenGanancia());
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    // ===== MÉTODOS PARA MANEJO DE MODALES =====

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

        // Redirigir a la tabla de productos después de cerrar el modal
        $this->dispatch('cambiarVista', ruta: 'Inventario.Producto');
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    // Método para obtener clases CSS dinámicas
    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }

        return '';
    }

    // Método para verificar si todos los campos críticos están completos
    public function verificarCamposCriticos()
    {
        $camposCriticos = ['nombre', 'marca', 'categoria', 'subcategoria', 'precio_base', 'unidad_medida', 'isv_id'];
        $camposVacios = [];

        foreach ($camposCriticos as $campo) {
            $valor = '';
            switch ($campo) {
                case 'nombre':
                    $valor = $this->form['nombre'];
                    break;
                case 'marca':
                    $valor = $this->form['marca_id'];
                    break;
                case 'categoria':
                    $valor = $this->categoriaSeleccionada;
                    break;
                case 'subcategoria':
                    $valor = $this->form['subcategoria_id'];
                    break;
                case 'precio_base':
                    $valor = $this->form['precio_base'];
                    break;
                case 'unidad_medida':
                    $valor = $this->form['unidad_medida_venta_id'];
                    break;
                case 'isv_id':
                    $valor = $this->form['isv_id'];
                    break;
            }

            if (empty($valor)) {
                $camposVacios[] = $campo;
            }
        }

        return $camposVacios;
    }

    /**
     * Obtiene las reglas de validación dinámicas
     */
    public function getRules()
    {
        $rules = $this->rules;
        
        // Agregar validación unique para código de barras
        if (!empty($this->form['codigo_barra'])) {
            if ($this->isEditing && $this->productoId) {
                // En edición, excluir el producto actual de la validación unique
                $rules['form.codigo_barra'] = 'nullable|string|max:100|unique:producto,codigo_barra,' . $this->productoId;
            } else {
                // En creación, validar que el código no exista
                $rules['form.codigo_barra'] = 'nullable|string|max:100|unique:producto,codigo_barra';
            }
        }
        
        // Validación especial para productos de Valencia: precio_base >= precio4
        if ($this->esProductoValencia && isset($this->form['precio4']) && $this->form['precio4'] > 0) {
            $precio4 = $this->form['precio4'];
            $rules['form.precio_base'] = 'required|numeric|min:' . $precio4;
            
            // Agregar mensaje personalizado para esta validación específica
            $this->messages['form.precio_base.min'] = 'Para productos de Valencia, el precio base no puede ser menor que el precio4 (L. ' . number_format($precio4, 2) . ')';
        }
        
        return $rules;
    }

    /**
     * Remover imagen actual
     */
    public function removerImagen()
    {
        $this->imagen = null;
        $this->tieneImagenAnterior = false;
    }

    /**
     * Obtener la imagen en formato base64 para mostrar en la vista
     */
    public function getImagenMiniatura()
    {
        if ($this->imagen) {
            return 'data:image/*;base64,' . base64_encode(file_get_contents($this->imagen->getRealPath()));
        } elseif ($this->isEditing && $this->tieneImagenAnterior) {
            // Obtener imagen anterior de la base de datos
            $producto = ProductoModel::select('imagen')->find($this->productoId);
            if ($producto && $producto->imagen) {
                return 'data:image/*;base64,' . base64_encode($producto->imagen);
            }
        }
        return null;
    }

    public function render()
    {
        return view('livewire.inventario.producto-form');
    }
}
