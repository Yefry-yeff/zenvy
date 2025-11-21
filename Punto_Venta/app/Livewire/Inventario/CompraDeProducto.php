<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\UnidadMedida;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\TipoCliente;
use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Livewire\DynamicContent;

class CompraDeProducto extends Component
{
    /**
     * Inicializa proveedores y unidades de medida para la vista de compras.
     */
    public function cargarDatosIniciales()
    {
        // Proveedores activos (tipo proveedor)
        $this->proveedores = Cliente::whereHas('tipoCliente', function($query) {
            $query->where('nombre', 'LIKE', '%proveedor%');
        })
        ->where('estado_id', '=', 1, 'and')
        ->orderBy('nombre')
        ->get()
        ->map(function($cliente) {
            return [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'rtn' => $cliente->rtn,
                'tipo_cliente' => $cliente->tipoCliente->nombre ?? 'N/A'
            ];
        })
        ->toArray();

        // Unidades de medida
        $this->unidadesMedida = UnidadMedida::orderBy('nombre', 'asc')->get()->map(function($unidad) {
            return [
                'id' => $unidad->id,
                'nombre' => $unidad->nombre,
                'simbolo' => $unidad->simbolo
            ];
        })->toArray();
    }

    public $readyToLoad = false;

    // Datos de la compra principal
    public $compra = [
        'numero_factura' => '',
        'fecha_vencimiento' => '',
        'fecha_emision' => '',
        'fecha_recepcion' => '',
    ];

    // Datos para los selectores
    public $proveedores = [];
    public $productos = [];
    public $unidadesMedida = [];
    public $proveedorSeleccionado = null;

    // Productos en la compra
    public $productosCompra = [];

    // Producto temporal para agregar
    public $productoTemporal = [
        'producto_id' => null,
        'precio' => 0,
        'cantidad_recibida' => 1,
        'cantidad_por_unidad' => 1, // Cuántas unidades tiene cada paquete/caja
        'cantidad_ingresada' => 1,
        'fecha_expiracion' => '',
        'unidad_medida_id' => null,
        'isv' => 0,
    ];

    // Busqueda de productos
    public $busquedaProducto = '';
    public $productosFiltrados = [];
    public $mostrarListaProductos = false;

    // Código de barras para escáner
    public $codigoBarras = '';
    public $productoSeleccionado = null;

    // Modal de búsqueda avanzada
    public $mostrarModalBusqueda = false;
    public $busquedaModalProductos = '';
    public $marcaSeleccionadaModal = '';
    public $categoriaSeleccionadaModal = '';
    public $subcategoriaSeleccionadaModal = '';
    public $resultadosBusquedaModal = [];
    public $marcasDisponibles = [];
    public $categoriasDisponibles = [];
    public $subcategoriasDisponibles = [];

    // Control de visibilidad de sección de productos
    public $mostrarSeccionProductosActiva = false;

    // Propiedades para validación y modales
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Nuevos modales para confirmación y éxito
    public $mostrarModalConfirmacion = false;
    public $mostrarModalCompraExitosa = false;
    public $numeroFacturaProcesada = '';
    public $totalCompraProcesada = 0;

    // Totales
    public $subtotal = 0;
    public $totalIsv = 0;
    public $total = 0;

    // Propiedades para validación de campos (igual que cliente-form)
    public $camposConError = [];
    public $campoConError = false;
    public $erroresValidacion = [];

    protected $rules = [
        'compra.numero_factura' => 'required|string|max:90',
        'compra.fecha_emision' => 'required|date',
        'compra.fecha_recepcion' => 'required|date',
        'proveedorSeleccionado' => 'required|exists:cliente,id',
        'productosCompra' => 'required|array|min:1',
        'productosCompra.*.producto_id' => 'required|exists:producto,id',
        'productosCompra.*.precio' => 'required|numeric|min:0.01',
        'productosCompra.*.cantidad_ingresada' => 'required|integer|min:1',
        'productosCompra.*.unidad_medida_id' => 'required|exists:unidad_medida,id',
    ];

    protected $messages = [
        'compra.numero_factura.required' => 'El número de factura es obligatorio',
        'compra.fecha_emision.required' => 'La fecha de emisión es obligatoria',
        'compra.fecha_recepcion.required' => 'La fecha de recepción es obligatoria',
        'proveedorSeleccionado.required' => 'Debe seleccionar un proveedor',
        'productosCompra.required' => 'Debe agregar al menos un producto',
        'productosCompra.min' => 'Debe agregar al menos un producto',
    ];

    public function mount()
    {
        // No cargar datos pesados en mount para evitar latencia al abrir la vista.
        // Se hará carga diferida desde la vista con wire:init llamando a loadInitialData().
        $this->compra['fecha_emision'] = now()->format('Y-m-d');
        $this->compra['fecha_recepcion'] = now()->format('Y-m-d');

        // Cargar trámite temporal si existe
        if (session()->has('tramite_a_cargar')) {
            $this->cargarTramiteDesdeSession();
        }
    }

    /**
     * Carga inicial invocada vía wire:init desde la vista.
     * Evita bloquear la apertura de la vista y carga datos pesados en segundo plano.
     */
    public function loadInitialData()
    {
        // Marcar que estamos listos y cargar datos
        $this->readyToLoad = true;
        $this->cargarDatosIniciales();
    }

    public function agregarProductoPorCodigo()
    {
        if (empty($this->codigoBarras)) {
            return;
        }

        // Buscar presentación por código de barras en precio_has_venta
        $presentacion = DB::table('precio_has_venta as phv')
            ->join('producto as p', 'phv.producto_id', '=', 'p.id')
            ->leftJoin('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
            ->leftJoin('marca as m', 'p.marca_id', '=', 'm.id')
            ->leftJoin('subcategoria as sc', 'p.subcategoria_id', '=', 'sc.id')
            ->leftJoin('categoria as c', 'sc.categoria_id', '=', 'c.id')
            ->where('phv.codigo_barra', trim($this->codigoBarras))
            ->where('phv.estado_id', 1)
            ->where('p.estado_id', 1)
            ->select(
                'phv.id as precio_id',
                'phv.producto_id',
                'phv.precio as precio',
                'phv.cantidad as cantidad_por_unidad',
                'phv.unidad_medida_id',
                'p.nombre as producto_nombre',
                'p.descripcion as producto_descripcion',
                'p.ultimo_costo_compra',
                'p.precio_base',
                'um.nombre as unidad_medida_nombre',
                'm.nombre as marca_nombre',
                'sc.nombre as subcategoria_nombre',
                'c.nombre as categoria_nombre'
            )
            ->first();

        if (!$presentacion) {
            session()->flash('error', 'No se encontró ninguna presentación activa con el código: ' . $this->codigoBarras);
            $this->codigoBarras = '';
            $this->productoSeleccionado = null;
            return;
        }

        // Guardar la información completa del producto seleccionado
        $this->productoSeleccionado = [
            'id' => $presentacion->producto_id,
            'nombre' => $presentacion->producto_nombre,
            'codigo_barra' => $this->codigoBarras,
            'precio_base' => $presentacion->precio ?? 0,
            'ultimo_costo_compra' => $presentacion->ultimo_costo_compra ?? $presentacion->precio_base ?? 0,
            'descripcion' => $presentacion->producto_descripcion ?? '',
            'marca' => $presentacion->marca_nombre ?? 'N/A',
            'subcategoria' => $presentacion->subcategoria_nombre ?? 'N/A',
            'categoria' => $presentacion->categoria_nombre ?? 'N/A',
        ];

        // Actualizar el producto en el formulario temporal con el precio de la presentación
        $this->productoTemporal = [
            'producto_id' => $presentacion->producto_id,
            'precio' => $presentacion->precio ?? 0,
            'cantidad_recibida' => 1,
            'cantidad_por_unidad' => $presentacion->cantidad_por_unidad ?? 1,
            'cantidad_ingresada' => $presentacion->cantidad_por_unidad ?? 1,
            'fecha_expiracion' => '',
            'unidad_medida_id' => $presentacion->unidad_medida_id ?? null,
            'isv' => 0,
        ];

        // Activar la sección de productos si no está activa
        $this->mostrarSeccionProductosActiva = true;

        // Limpiar código de barras
        $this->codigoBarras = '';

        // Mensaje actualizado para indicar que se seleccionó/actualizó la presentación
        session()->flash('success', '✅ Presentación seleccionada: ' . $presentacion->producto_nombre);
        Log::info('Proveedores encontrados: ', $this->proveedores);

        // Si no hay proveedores, intentar con diferentes variaciones del nombre
        if (empty($this->proveedores)) {
            Log::warning('No se encontraron proveedores, intentando con búsqueda más amplia...');

            $this->proveedores = Cliente::whereHas('tipoCliente', function($query) {
                $query->where('nombre', 'LIKE', '%proveedor%')
                      ->orWhere('nombre', 'LIKE', '%Proveedor%')
                      ->orWhere('nombre', 'LIKE', '%PROVEEDOR%')
                      ->orWhere('nombre', 'LIKE', '%supplier%')
                      ->orWhere('nombre', 'LIKE', '%Supplier%');
            })
            ->with(['tipoCliente', 'direccion'])
            ->where('estado_id', 1)
            ->orderBy('nombre')
            ->get()
            ->map(function($cliente) {
                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'rtn' => $cliente->rtn,
                    'tipo_cliente' => $cliente->tipoCliente->nombre ?? 'N/A'
                ];
            })
            ->toArray();

            Log::info('Proveedores encontrados con búsqueda amplia: ', $this->proveedores);
        }

        // No cargar la lista completa de productos en la apertura (puede ser muy grande).
        // Los productos se consultan desde el modal de búsqueda (buscarProductosModal).
        $this->productos = [];

        // Cachear unidades de medida (rara vez cambian)
        $this->unidadesMedida = Cache::remember('unidades_medida', 60 * 60, function() {
            return UnidadMedida::orderBy('nombre', 'asc')
                ->get()
                ->map(function($unidad) {
                    return [
                        'id' => $unidad->id,
                        'nombre' => $unidad->nombre,
                        'simbolo' => $unidad->simbolo
                    ];
                })
                ->toArray();
        });
    }

    public function updatedBusquedaProducto()
    {
        // Solo buscar por código de barras exacto (para escáner)
        if (!empty($this->busquedaProducto)) {
            // Limpiar espacios en blanco
            $codigoBarra = trim($this->busquedaProducto);

            // Primero intentar encontrar una presentación (precio_has_venta) por su código de barras
            try {
                $presentacion = DB::table('precio_has_venta as phv')
                    ->join('producto as p', 'phv.producto_id', '=', 'p.id')
                    ->leftJoin('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                    ->where('phv.codigo_barra', $codigoBarra)
                    ->where('phv.estado_id', 1)
                    ->where('p.estado_id', 1)
                    ->select(
                        'phv.id as precio_id',
                        'phv.producto_id',
                        'phv.precio as precio',
                        'phv.cantidad as cantidad_por_unidad',
                        'phv.unidad_medida_id',
                        'p.nombre as producto_nombre',
                        'p.codigo_barra as producto_codigo_barra',
                        'um.nombre as unidad_medida_nombre'
                    )
                    ->first();

                if ($presentacion) {
                    // Llenar la información del producto seleccionado y el temporal
                    $this->productoSeleccionado = [
                        'id' => $presentacion->producto_id,
                        'nombre' => $presentacion->producto_nombre,
                        'codigo_barra' => $presentacion->producto_codigo_barra,
                        'precio_base' => $presentacion->precio ?? 0,
                        'ultimo_costo_compra' => $presentacion->precio ?? 0,
                        'descripcion' => '',
                        'marca' => null,
                        'subcategoria' => null,
                    ];

                    $this->productoTemporal = [
                        'producto_id' => $presentacion->producto_id,
                        'precio' => $presentacion->precio ?? 0,
                        'cantidad_recibida' => 1,
                        'cantidad_por_unidad' => $presentacion->cantidad_por_unidad ?? 1,
                        'cantidad_ingresada' => $presentacion->cantidad_por_unidad ?? 1,
                        'fecha_expiracion' => '',
                        'unidad_medida_id' => $presentacion->unidad_medida_id ?? null,
                        'isv' => 0,
                    ];

                    // Mostrar en el campo de búsqueda el código escaneado (presentación)
                    $this->busquedaProducto = $codigoBarra;
                    $this->mostrarListaProductos = false;

                    return;
                }
            } catch (\Exception $e) {
                Log::error('Error buscando presentacion por codigo_barra', ['codigo' => $codigoBarra, 'error' => $e->getMessage()]);
                // continuar con fallback a producto
            }

        }

        // No mostrar lista de productos filtrados (solo funciona con códigos exactos)
        $this->productosFiltrados = [];
        $this->mostrarListaProductos = false;
    }

    public function seleccionarProducto($productoId)
    {
        $producto = collect($this->productos)->firstWhere('id', $productoId);
        if ($producto) {
            $this->productoTemporal['producto_id'] = $producto['id'];

            // Intentar obtener un código de barra desde precio_has_venta para esta producto
            try {
                $phv = DB::table('precio_has_venta')
                    ->where('producto_id', $producto['id'])
                    ->where('estado_id', 1)
                    ->whereNotNull('codigo_barra')
                    ->where('codigo_barra', '<>', '')
                    ->select('codigo_barra')
                    ->first();

                if ($phv && !empty($phv->codigo_barra)) {
                    $this->busquedaProducto = $phv->codigo_barra;
                } else {
                    // Si no hay código en precio_has_venta, dejar el campo vacío (no usar producto.codigo_barra)
                    $this->busquedaProducto = '';
                }
            } catch (\Exception $e) {
                Log::error('Error obteniendo codigo_barra desde precio_has_venta en seleccionarProducto', ['producto_id' => $producto['id'], 'error' => $e->getMessage()]);
                $this->busquedaProducto = '';
            }

            $this->mostrarListaProductos = false;
        }
    }

    public function incrementarCantidad()
    {
        $this->productoTemporal['cantidad_ingresada']++;
    }

    public function decrementarCantidad()
    {
        if ($this->productoTemporal['cantidad_ingresada'] > 1) {
            $this->productoTemporal['cantidad_ingresada']--;
        }
    }

    // Validación en tiempo real para cantidad ingresada manualmente
    // Calcular automáticamente cantidad_ingresada cuando cambia cantidad_recibida o cantidad_por_unidad
    public function updatedProductoTemporalCantidadRecibida()
    {
        $this->calcularCantidadIngresada();
    }

    public function updatedProductoTemporalCantidadPorUnidad()
    {
        $this->calcularCantidadIngresada();
    }

    private function calcularCantidadIngresada()
    {
        $cantidadRecibida = (int) ($this->productoTemporal['cantidad_recibida'] ?? 1);
        $cantidadPorUnidad = (int) ($this->productoTemporal['cantidad_por_unidad'] ?? 1);

        $this->productoTemporal['cantidad_ingresada'] = $cantidadRecibida * $cantidadPorUnidad;
    }

    public function updatedProductoTemporalCantidadIngresada($value)
    {
        // Asegurar que sea un número entero positivo
        $cantidad = (int) $value;

        if ($cantidad < 1) {
            $this->productoTemporal['cantidad_ingresada'] = 1;
        } else {
            $this->productoTemporal['cantidad_ingresada'] = $cantidad;
        }
    }

    public function limpiarBusqueda()
    {
        $this->busquedaProducto = '';
        $this->productoTemporal['producto_id'] = null;
        $this->mostrarListaProductos = false;
    }

    // Propiedad computada para habilitar/deshabilitar el botón de agregar producto
    public function getBotonHabilitadoProperty()
    {
        // Solo requiere producto, precio y unidad para agregar productos
        return !empty($this->productoTemporal['producto_id']) &&
               !empty($this->productoTemporal['precio']) &&
               $this->productoTemporal['precio'] > 0 &&
               !empty($this->productoTemporal['unidad_medida_id']) &&
               $this->productoTemporal['cantidad_ingresada'] > 0;
    }

    // Propiedad computada para habilitar/deshabilitar el botón de guardar compra
    public function getBotonGuardarHabilitadoProperty()
    {
        // Solo requiere que haya productos agregados, ya que la información de compra
        // es prerequisito para mostrar la sección de productos
        return count($this->productosCompra) > 0;
    }

    // Propiedad computada para mostrar la sección de agregar productos
    public function getMostrarSeccionProductosProperty()
    {
        // Solo mostrar sección de productos cuando la información básica esté completa
        return !empty($this->compra['numero_factura']) &&
               !empty($this->compra['fecha_emision']) &&
               !empty($this->compra['fecha_recepcion']) &&
               !empty($this->proveedorSeleccionado);
    }

    // Método para activar la sección de productos
    public function activarSeccionProductos()
    {
        $this->mostrarSeccionProductosActiva = true;
    }

    // Método para validar campos y activar la sección de productos
    public function validarYActivarSeccionProductos()
    {
        // Validar campos obligatorios
        $errores = [];

        if (empty($this->compra['numero_factura'])) {
            $errores[] = 'El número de factura es obligatorio';
        }

        if (empty($this->compra['fecha_emision'])) {
            $errores[] = 'La fecha de emisión es obligatoria';
        }

        if (empty($this->compra['fecha_recepcion'])) {
            $errores[] = 'La fecha de recepción es obligatoria';
        }

        if (empty($this->proveedorSeleccionado)) {
            $errores[] = 'Debe seleccionar un proveedor';
        }

        // Si hay errores, mostrar alerta
        if (!empty($errores)) {
            $this->mostrarAlertaError('Complete los siguientes campos: ' . implode(', ', $errores));
            return;
        }

        // Si todo está correcto, activar la sección de productos
        $this->mostrarSeccionProductosActiva = true;
    }

    public function agregarProducto()
    {
        // Validar producto temporal
        if (!$this->productoTemporal['producto_id']) {
            $this->mostrarAlertaError('Debe seleccionar un producto válido');
            return;
        }

        // Validar que tenemos información del producto seleccionado
        if (!$this->productoSeleccionado) {
            $this->mostrarAlertaError('No se ha seleccionado un producto válido. Escanee el código de barras nuevamente.');
            return;
        }

        if ($this->productoTemporal['precio'] <= 0) {
            $this->mostrarAlertaError('El precio debe ser mayor a cero');
            return;
        }

        if ($this->productoTemporal['cantidad_ingresada'] <= 0) {
            $this->mostrarAlertaError('La cantidad debe ser mayor a cero');
            return;
        }

        if (!$this->productoTemporal['unidad_medida_id']) {
            $this->mostrarAlertaError('Debe seleccionar una unidad de medida');
            return;
        }

        // Actualizar ultimo_costo_compra en la tabla producto cuando se agrega el producto
        try {
            $producto = Producto::find($this->productoTemporal['producto_id']); // Correcto, $columns es opcional
            if ($producto && $this->productoTemporal['precio'] > 0) {
                $costoAnterior = $producto->ultimo_costo_compra;
                $producto->ultimo_costo_compra = $this->productoTemporal['precio'];
                $producto->save();

                // Actualizar también la información del producto seleccionado
                if ($this->productoSeleccionado) {
                    $this->productoSeleccionado['ultimo_costo_compra'] = $this->productoTemporal['precio'];
                }

                // Registrar en bitácora: Actualización de último costo al agregar producto
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Producto',
                    'Actualizar último costo (agregar producto)',
                    "Último costo actualizado al agregar producto '{$producto->nombre}' a compra. Costo anterior: L. " . number_format($costoAnterior, 2) . " → Nuevo costo: L. " . number_format($this->productoTemporal['precio'], 2),
                    $this->productoTemporal['producto_id'],
                    'producto',
                    ['ultimo_costo_compra' => $costoAnterior],
                    ['ultimo_costo_compra' => $this->productoTemporal['precio']]
                );

                Log::info("Último costo actualizado al agregar producto", [
                    'producto_id' => $this->productoTemporal['producto_id'],
                    'producto_nombre' => $producto->nombre,
                    'costo_anterior' => $costoAnterior,
                    'costo_nuevo' => $this->productoTemporal['precio']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar último costo al agregar producto: ' . $e->getMessage());
            // Continuar con el proceso aunque falle la actualización del costo
        }

        // Verificar si el producto ya está en la lista - ELIMINADO para permitir duplicados
        // Los productos pueden agregarse múltiples veces sin restricciones

    // Calcular subtotal e ISV para este producto usando cantidad_ingresada (unidades)
    $precio = (float) $this->productoTemporal['precio'];
    $cantidadIngresada = (int) ($this->productoTemporal['cantidad_ingresada'] ?? 1);
    $isv = (float) $this->productoTemporal['isv'];

    // Para compatibilidad, dejar cantidad_recibida y cantidad_por_unidad en 1
    $cantidadRecibida = 1;
    $cantidadPorUnidad = 1;

    // El subtotal se calcula con la cantidad ingresada (unidades)
    $subtotalProducto = $precio * $cantidadIngresada;
    $isvProducto = $subtotalProducto * ($isv / 100);
    $totalProducto = $subtotalProducto + $isvProducto;

        // Obtener información del producto y unidad de medida
        $unidadMedida = collect($this->unidadesMedida)->firstWhere('id', $this->productoTemporal['unidad_medida_id']);

        // Usar la información del producto seleccionado si está disponible
        $nombreProducto = $this->productoSeleccionado['nombre'] ?? 'Producto desconocido';
        $codigoProducto = $this->productoSeleccionado['codigo_barra'] ?? 'N/A';

        // Agregar producto a la lista (sin dispatch, más rápido)
        $this->productosCompra[] = [
            'producto_id' => $this->productoTemporal['producto_id'],
            'producto_nombre' => $nombreProducto,
            'producto_codigo' => $codigoProducto,
            'precio' => $precio,
            'cantidad_recibida' => $cantidadRecibida, // Paquetes/cajas recibidos (compatibilidad)
            'cantidad_por_unidad' => $cantidadPorUnidad, // Unidades por paquete (compatibilidad)
            'cantidad_ingresada' => $cantidadIngresada, // Cantidad unitaria total (unidades)
            'cantidad_sin_asignar' => $cantidadIngresada, // Inicialmente toda sin asignar
            'fecha_expiracion' => $this->productoTemporal['fecha_expiracion'] ?: null,
            'unidad_medida_id' => $this->productoTemporal['unidad_medida_id'],
            'unidad_medida_nombre' => $unidadMedida['nombre'] ?? '',
            'isv' => $isv,
            'sub_total_producto' => $subtotalProducto,
            'precio_total' => $totalProducto,
        ];

        // Limpiar formulario temporal
        $this->resetProductoTemporal();

        // Recalcular totales
        $this->calcularTotales();

        // Disparar evento para enfocar el campo de código de barras
        $this->dispatch('producto-agregado');
    }

    public function eliminarProducto($index)
    {
        unset($this->productosCompra[$index]);
        $this->productosCompra = array_values($this->productosCompra); // Reindexar array
        $this->calcularTotales();
    }

    public function actualizarCantidadRecibida($index, $nuevaCantidadRecibida)
    {
        // Compatibilidad: delegar en actualizarCantidad para usar cantidad_ingresada (unidades)
        $nuevaCantidad = (int) $nuevaCantidadRecibida;
        $this->actualizarCantidad($index, $nuevaCantidad);
    }

    public function actualizarCantidad($index, $nuevaCantidad)
    {
        $nuevaCantidad = (int) $nuevaCantidad;

        if ($nuevaCantidad <= 0) {
            $this->mostrarAlertaError('La cantidad debe ser mayor a cero');
            return;
        }

        if (isset($this->productosCompra[$index])) {
            // Actualizar la cantidad
            $this->productosCompra[$index]['cantidad_ingresada'] = $nuevaCantidad;
            $this->productosCompra[$index]['cantidad_sin_asignar'] = $nuevaCantidad;

            // Recalcular los totales para este producto
            $precio = $this->productosCompra[$index]['precio'];
            $isv = $this->productosCompra[$index]['isv'];

            $subtotalProducto = $precio * $nuevaCantidad;
            $isvProducto = $subtotalProducto * ($isv / 100);
            $totalProducto = $subtotalProducto + $isvProducto;

            $this->productosCompra[$index]['sub_total_producto'] = $subtotalProducto;
            $this->productosCompra[$index]['precio_total'] = $totalProducto;

            // Recalcular totales generales
            $this->calcularTotales();
        }
    }

    public function calcularTotales()
    {
        $this->subtotal = 0;
        $this->totalIsv = 0;
        $this->total = 0;

        foreach ($this->productosCompra as $producto) {
            $this->subtotal += $producto['sub_total_producto'];
            $this->totalIsv += $producto['sub_total_producto'] * ($producto['isv'] / 100);
            $this->total += $producto['precio_total'];
        }
    }

    public function resetProductoTemporal()
    {
        $this->productoTemporal = [
            'producto_id' => null,
            'precio' => 0,
            'cantidad_recibida' => 1, // Paquetes/cajas recibidos
            'cantidad_por_unidad' => 1, // Unidades por paquete
            'cantidad_ingresada' => 1, // Cantidad unitaria para stock
            'fecha_expiracion' => '',
            'unidad_medida_id' => null,
            'isv' => 0,
        ];
        $this->productoSeleccionado = null; // Limpiar información del producto seleccionado
        $this->busquedaProducto = '';
        $this->mostrarListaProductos = false;
    }

    public function guardarCompra()
    {
        try {
            // Usar el nuevo sistema de validación (igual que cliente-form)
            if (!$this->validarAntesDeGuardar()) {
                return; // Si hay errores, no continuar
            }

            DB::beginTransaction();

            // Obtener el nombre completo del usuario autenticado
            $usuario = Auth::user();
            $nombreUsuario = 'N/A';

            if ($usuario && $usuario->detalle) {
                $nombreUsuario = trim(
                    ($usuario->detalle->primer_nombre ?? '') . ' ' .
                    ($usuario->detalle->segundo_nombre ?? '') . ' ' .
                    ($usuario->detalle->primer_apellido ?? '') . ' ' .
                    ($usuario->detalle->segundo_apellido ?? '')
                );
            } elseif ($usuario) {
                $nombreUsuario = $usuario->name ?? 'N/A';
            }

            // Crear la compra principal
            $compra = Compra::create([
                'numero_factura' => $this->compra['numero_factura'],
                'fecha_vencimiento' => $this->compra['fecha_vencimiento'] ?: null,
                'fecha_emision' => $this->compra['fecha_emision'],
                'fecha_recepcion' => $this->compra['fecha_recepcion'],
                'estado_id' => 1, // Estado "Activo" por defecto
                'cliente_id' => $this->proveedorSeleccionado, // Proveedor seleccionado
                'user' => $nombreUsuario, // Usuario que creó la compra
            ]);

            // Registrar en bitácora: Creación de compra
            Bitacora::registrar(
                Auth::id(),
                'Inventario - Compra de Producto',
                'Crear compra',
                "Nueva compra creada con factura '{$this->compra['numero_factura']}'. Total: L. " . number_format($this->total, 2) . ". Productos: " . count($this->productosCompra),
                $compra->id,
                'compra',
                null,
                [
                    'numero_factura' => $this->compra['numero_factura'],
                    'fecha_emision' => $this->compra['fecha_emision'],
                    'estado_id' => 1,
                    'cliente_id' => $this->proveedorSeleccionado,
                    'total_productos' => count($this->productosCompra),
                    'monto_total' => $this->total
                ]
            );

            // Guardar los productos de la compra
            foreach ($this->productosCompra as $producto) {
                $detalleCompra = CompraHasProducto::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto['producto_id'],
                    'precio' => $producto['precio'],
                    'fecha_expiracion' => $producto['fecha_expiracion'],
                    'sub_total_producto' => $producto['sub_total_producto'],
                    'isv' => $producto['sub_total_producto'] * ($producto['isv'] / 100),
                    'precio_total' => $producto['precio_total'],
                    'unidad_medida_id' => $producto['unidad_medida_id'],
                ]);

                // Nota: El ultimo_costo_compra ya se actualizo al agregar cada producto
                // No es necesario actualizar nuevamente aqui

                // Registrar en bitácora: Detalle de cada producto
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Compra de Producto',
                    'Crear detalle compra',
                    "Producto agregado a compra '{$this->compra['numero_factura']}': {$producto['producto_nombre']} - Cantidad: {$producto['cantidad_ingresada']} - Precio: L. " . number_format($producto['precio'], 2),
                    $detalleCompra->id,
                    'compra_has_producto',
                    null,
                    [
                        'compra_id' => $compra->id,
                        'producto_id' => $producto['producto_id'],
                        'nombre_producto' => $producto['producto_nombre'],
                        'precio' => $producto['precio'],
                        'cantidad_ingresada' => $producto['cantidad_ingresada'],
                        'cantidad_sin_asignar' => $producto['cantidad_sin_asignar'],
                        'precio_total' => $producto['precio_total']
                    ]
                );
            }

            DB::commit();

            // Guardar información para el modal de éxito
            $this->numeroFacturaProcesada = $this->compra['numero_factura'];
            $this->totalCompraProcesada = $this->total;

            // Log para depuración
            Log::info('Compra procesada, activando modal de éxito', [
                'numero_factura' => $this->numeroFacturaProcesada,
                'total' => $this->totalCompraProcesada
            ]);
            session()->flash('success', 'Se ejecutó mostrarModalCompraExitosa = true');

            // Mostrar modal de compra exitosa
            $this->mostrarModalCompraExitosa = true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar compra: ' . $e->getMessage());
            $this->mostrarModalError = true;
            $this->mensajeModalError = 'Error al guardar la compra: ' . $e->getMessage();
        }
    }

    /**
     * Valida que el número de factura sea único
     * Solo permite duplicados si la compra anterior está anulada
     */
    private function validarNumeroFacturaUnico()
    {
        $numeroFactura = $this->compra['numero_factura'];

        // Buscar compras existentes con el mismo número de factura
        $compraExistente = Compra::where('numero_factura', '=', $numeroFactura, 'and')
            ->with('estado')
            ->first();

        if ($compraExistente) {
            // Verificar si la compra existente está anulada
            $estadoAnulado = strtolower($compraExistente->estado->nombre ?? '') === 'anulado';

            if (!$estadoAnulado) {
                // Si existe una compra activa con el mismo número, lanzar error
                $this->addError('compra.numero_factura',
                    'Ya existe una compra con este número de factura. Solo se puede reutilizar si la compra anterior está anulada.');

                // También mostrar alerta visual
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'El número de factura "' . $numeroFactura . '" ya está en uso. Solo se puede reutilizar si la compra anterior está anulada.';

                throw new \Exception('Número de factura duplicado');
            }
        }
    }

    /**
     * Valida el número de factura en tiempo real
     */
    public function updatedCompraNumeroFactura($value)
    {
        // Limpiar errores previos
        $this->resetErrorBag('compra.numero_factura');
        $this->mostrarAlerta = false;

        if (!empty($value)) {
            // Buscar compras existentes con el mismo número de factura
            $compraExistente = Compra::where('numero_factura', '=', $value, 'and')
                ->with('estado')
                ->first();

            if ($compraExistente) {
                // Verificar si la compra existente está anulada
                $estadoAnulado = strtolower($compraExistente->estado->nombre ?? '') === 'anulado';

                if (!$estadoAnulado) {
                    // Mostrar error inmediato
                    $this->addError('compra.numero_factura',
                        'Este número de factura ya está en uso. Solo se puede reutilizar si la compra anterior está anulada.');
                } else {
                    // Mostrar advertencia pero permitir continuar
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = 'ℹ️ Información: Este número de factura fue usado anteriormente en una compra anulada. Puede reutilizarlo.';
                }
            }
        }
    }

    public function resetFormulario()
    {
        $this->compra = [
            'numero_factura' => '',
            'fecha_vencimiento' => '',
            'fecha_emision' => now()->format('Y-m-d'),
            'fecha_recepcion' => now()->format('Y-m-d'),
        ];
        $this->proveedorSeleccionado = null;
        $this->productosCompra = [];
        $this->mostrarSeccionProductosActiva = false;
        $this->resetProductoTemporal();
        $this->calcularTotales();
    }

    public function mostrarAlertaError($mensaje)
    {
        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
    }

    public function mostrarConfirmacionProcesar()
    {
        // Primero validar que todos los campos estén correctos
        if (!$this->validarAntesDeGuardar()) {
            return; // Si hay errores, no mostrar confirmación
        }

        // Si la validación pasa, mostrar modal de confirmación
        $this->mostrarModalConfirmacion = true;
    }

    public function cancelarProcesamiento()
    {
        $this->mostrarModalConfirmacion = false;
    }

    public function confirmarProcesamiento()
    {
        // Cerrar modal de confirmación
        $this->mostrarModalConfirmacion = false;

        // Procesar la compra
        $this->guardarCompra();
    }

    public function nuevaCompra()
    {
        // Limpiar todo el formulario para nueva compra
        $this->mostrarModalCompraExitosa = false;
        $this->reiniciarFormulario();

        session()->flash('success', '✅ Listo para nueva compra');
    }

    public function recibirProductos()
    {
        // Cerrar modal
        $this->mostrarModalCompraExitosa = false;

        try {
            // Buscar la compra por número de factura para obtener el ID
            $compra = Compra::with(['estado'])->where('numero_factura', $this->numeroFacturaProcesada)->first();

            if ($compra && $compra->estado && (strtolower($compra->estado->nombre) === 'activo' || strtolower($compra->estado->nombre) === 'pendiente' || $compra->estado_id == 5)) {
                // Usar exactamente la misma lógica que CompraDeProductos
                $this->dispatch('cambiarVista', ruta: 'Inventario.RecibirProductoCompra', parametros: ['compraId' => $compra->id]);

                session()->flash('success', '🚛 Dirigiendo a recepción de productos...');

            } else {
                session()->flash('error', 'Solo se pueden recibir productos de compras en estado "activo" o "pendiente".');
            }

        } catch (\Exception $e) {
            Log::error('Error al cambiar a recibir productos: ' . $e->getMessage());
            session()->flash('error', 'Error al acceder a la recepción de productos');
        }
    }
    public function reiniciarFormulario()
    {
        // Limpiar datos de la compra
        $this->compra = [
            'numero_factura' => '',
            'fecha_vencimiento' => '',
            'fecha_emision' => now()->format('Y-m-d'),
            'fecha_recepcion' => now()->format('Y-m-d'),
        ];

        $this->proveedorSeleccionado = null;
        $this->productosCompra = [];

        // Limpiar producto temporal
        $this->productoTemporal = [
            'producto_id' => null,
            'precio' => 0,
            'cantidad_recibida' => 1,
            'cantidad_por_unidad' => 1,
            'cantidad_ingresada' => 1,
            'fecha_expiracion' => '',
            'unidad_medida_id' => null,
            'isv' => 0,
        ];

        // Limpiar búsquedas
        $this->busquedaProducto = '';
        $this->mostrarListaProductos = false;

        // Limpiar errores y alertas
        $this->limpiarTodosLosErrores();
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';

        // Recalcular totales
        $this->calcularTotales();

        // Ocultar sección de productos
        $this->mostrarSeccionProductosActiva = false;
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CompraDeProductos');
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    // Método para debugging - verificar clientes disponibles
    public function debugClientes()
    {
        $todosLosClientes = Cliente::with('tipoCliente')->get();
        Log::info('Todos los clientes: ', $todosLosClientes->toArray());

        $this->mostrarAlertaError('Debug ejecutado. Revise los logs para ver la información de clientes.');
    }

    // Métodos para modal de búsqueda de productos
    public function abrirModalBusqueda()
    {
        $this->mostrarModalBusqueda = true;
        $this->busquedaModalProductos = '';
        $this->marcaSeleccionadaModal = '';
        $this->categoriaSeleccionadaModal = '';
        $this->subcategoriaSeleccionadaModal = '';
        $this->cargarDatosModalBusqueda();
        $this->buscarProductosModal();
    }

    public function cerrarModalBusqueda()
    {
        $this->mostrarModalBusqueda = false;
        $this->busquedaModalProductos = '';
        $this->resultadosBusquedaModal = [];
    }

    public function cargarDatosModalBusqueda()
    {
        // Cargar solo los datos esenciales para mejorar rendimiento
        $this->marcasDisponibles = DB::table('marca')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->toArray();

        $this->categoriasDisponibles = DB::table('categoria')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->toArray();

        $this->subcategoriasDisponibles = DB::table('subcategoria')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    public function updatedBusquedaModalProductos()
    {
        $this->buscarProductosModal();
    }

    public function updatedMarcaSeleccionadaModal()
    {
        $this->buscarProductosModal();
    }

    public function updatedCategoriaSeleccionadaModal()
    {
        $this->buscarProductosModal();
    }

    public function updatedSubcategoriaSeleccionadaModal()
    {
        $this->buscarProductosModal();
    }

    public function buscarProductosModal()
    {
        // Limitar a 30 resultados para mejorar rendimiento
        $resultados = [];
        if ($this->busquedaModalProductos && strlen($this->busquedaModalProductos) >= 3) {
            $busqueda = $this->busquedaModalProductos;
            // Buscar productos que tengan presentaciones con ese código de barra
            $presentaciones = DB::table('precio_has_venta as phv')
                ->join('producto as p', 'phv.producto_id', '=', 'p.id')
                ->leftJoin('marca as m', 'p.marca_id', '=', 'm.id')
                ->leftJoin('subcategoria as sc', 'p.subcategoria_id', '=', 'sc.id')
                ->leftJoin('categoria as c', 'sc.categoria_id', '=', 'c.id')
                ->where('phv.codigo_barra', 'like', '%' . $busqueda . '%')
                ->where('phv.estado_id', 1)
                ->where('p.estado_id', 1)
                ->select(
                    'p.id as producto_id',
                    'p.nombre as producto_nombre',
                    'p.descripcion',
                    'phv.codigo_barra',
                    'phv.precio as precio_base',
                    'p.ultimo_costo_compra',
                    'm.nombre as marca',
                    'sc.nombre as subcategoria',
                    'c.nombre as categoria'
                )
                ->limit(30)
                ->get();

            $resultados = $presentaciones->map(function($row) {
                return [
                    'id' => $row->producto_id,
                    'nombre' => $row->producto_nombre,
                    'descripcion' => $row->descripcion,
                    'codigo_barra' => $row->codigo_barra, // SIEMPRE de precio_has_venta
                    'precio_base' => $row->precio_base,
                    'ultimo_costo_compra' => $row->ultimo_costo_compra,
                    'marca' => $row->marca,
                    'categoria' => $row->categoria,
                    'subcategoria' => $row->subcategoria,
                ];
            })->toArray();
        }
        $this->resultadosBusquedaModal = $resultados;
    }

    public function seleccionarProductoModal($productoId, $codigoBarraSeleccionado = null)
    {

        $producto = Producto::with(['marca', 'subcategoria', 'unidadMedidaVenta'])->find($productoId);

        // Si no se pasa el código de barra, buscarlo en los resultados del modal
        if ($codigoBarraSeleccionado === null && $producto) {
            foreach ($this->resultadosBusquedaModal as $row) {
                if ($row['id'] == $producto->id) {
                    $codigoBarraSeleccionado = $row['codigo_barra'] ?? null;
                    break;
                }
            }
        }

        // Buscar la presentación exacta en precio_has_venta
        $presentacion = null;
        if ($producto && $codigoBarraSeleccionado) {
            $presentacion = DB::table('precio_has_venta as phv')
                ->join('producto as p', 'phv.producto_id', '=', 'p.id')
                ->leftJoin('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                ->leftJoin('marca as m', 'p.marca_id', '=', 'm.id')
                ->leftJoin('subcategoria as sc', 'p.subcategoria_id', '=', 'sc.id')
                ->leftJoin('categoria as c', 'sc.categoria_id', '=', 'c.id')
                ->where('phv.codigo_barra', $codigoBarraSeleccionado)
                ->where('phv.estado_id', 1)
                ->where('p.estado_id', 1)
                ->select(
                    'phv.id as precio_id',
                    'phv.producto_id',
                    'phv.precio as precio',
                    'phv.cantidad as cantidad_por_unidad',
                    'phv.unidad_medida_id',
                    'p.nombre as producto_nombre',
                    'p.descripcion as producto_descripcion',
                    'p.ultimo_costo_compra',
                    'p.precio_base',
                    'um.nombre as unidad_medida_nombre',
                    'm.nombre as marca_nombre',
                    'sc.nombre as subcategoria_nombre',
                    'c.nombre as categoria_nombre'
                )
                ->first();
        }

        if ($presentacion) {
            $this->busquedaProducto = $codigoBarraSeleccionado;
            $this->productoSeleccionado = [
                'id' => $presentacion->producto_id,
                'nombre' => $presentacion->producto_nombre,
                'codigo_barra' => $codigoBarraSeleccionado,
                'precio_base' => $presentacion->precio ?? 0,
                'ultimo_costo_compra' => $presentacion->ultimo_costo_compra ?? $presentacion->precio_base ?? 0,
                'descripcion' => $presentacion->producto_descripcion ?? '',
                'marca' => $presentacion->marca_nombre ?? 'N/A',
                'subcategoria' => $presentacion->subcategoria_nombre ?? 'N/A',
                'categoria' => $presentacion->categoria_nombre ?? 'N/A',
            ];
            $this->productoTemporal = [
                'producto_id' => $presentacion->producto_id,
                'precio' => $presentacion->precio ?? 0,
                'cantidad_recibida' => 1,
                'cantidad_por_unidad' => $presentacion->cantidad_por_unidad ?? 1,
                'cantidad_ingresada' => $presentacion->cantidad_por_unidad ?? 1,
                'fecha_expiracion' => '',
                'unidad_medida_id' => $presentacion->unidad_medida_id ?? null,
                'isv' => 0,
            ];
        } else if ($producto) {
            // Fallback: comportamiento anterior si no se encuentra la presentación
            $this->busquedaProducto = $codigoBarraSeleccionado ?: $producto->nombre;
            $this->productoSeleccionado = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $codigoBarraSeleccionado,
                'precio_base' => $producto->precio_base ?? 0,
                'ultimo_costo_compra' => $producto->ultimo_costo_compra ?? 0,
                'descripcion' => $producto->descripcion ?? '',
                'marca' => $producto->marca->nombre ?? 'N/A',
                'subcategoria' => $producto->subcategoria->nombre ?? 'N/A',
            ];
            $this->productoTemporal = [
                'producto_id' => $producto->id,
                'precio' => $producto->ultimo_costo_compra ?? $producto->precio_base ?? 0,
                'cantidad_recibida' => 1,
                'cantidad_por_unidad' => 1,
                'cantidad_ingresada' => 1,
                'fecha_expiracion' => '',
                'unidad_medida_id' => $producto->unidad_medida_venta_id ?? null,
                'isv' => 0,
            ];
        }
        // Cerrar modal
        $this->cerrarModalBusqueda();
    }

    // Método para seleccionar producto desde modal de búsqueda
    public function seleccionarProductoDesdeModal($productoId, $codigoBarra = null)
    {
        // Si se pasa el código de barra, usarlo directamente
        if ($codigoBarra) {
            $this->seleccionarProductoModal($productoId, $codigoBarra);
        } else {
            // Fallback: buscar el código de barra en los resultados del modal
            $codigoBarraSeleccionado = null;
            foreach ($this->resultadosBusquedaModal as $row) {
                if ($row['id'] == $productoId) {
                    $codigoBarraSeleccionado = $row['codigo_barra'] ?? null;
                    break;
                }
            }
            if ($codigoBarraSeleccionado) {
                $this->seleccionarProductoModal($productoId, $codigoBarraSeleccionado);
            } else {
                $this->seleccionarProductoModal($productoId);
            }
        }
    }

    // Métodos para trámites temporales
    public function guardarTramiteTemporal()
    {
        // Usar validación específica para guardado temporal
        if (!$this->validarParaGuardadoTemporal()) {
            return; // Si hay errores, no continuar
        }

        $proveedor = Cliente::find($this->proveedorSeleccionado); // Correcto, $columns es opcional

        $tramite = [
            'numero_factura' => $this->compra['numero_factura'],
            'proveedor_id' => $this->proveedorSeleccionado,
            'proveedor_nombre' => $proveedor ? $proveedor->nombre : 'N/A',
            'fecha_emision' => $this->compra['fecha_emision'],
            'fecha_recepcion' => $this->compra['fecha_recepcion'],
            'fecha_vencimiento' => $this->compra['fecha_vencimiento'] ?? null,
            'productos' => $this->productosCompra,
            'total' => $this->total,
            'fecha_guardado' => now()->toDateTimeString(),
        ];

        // Guardar en sesión
        $tramites = session('tramites_temporales_compras', []);
        $tramites[] = $tramite;
        session(['tramites_temporales_compras' => $tramites]);

        session()->flash('success', '✅ Trámite guardado temporalmente. Puede continuar más tarde.');

        // Volver a la lista
        $this->volver();
    }

    // Métodos de validación de campos (igual que cliente-form)
    public function getClaseCampo($campo = null)
    {
        if ($campo && in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }
        return '';
    }

    public function mostrarErrorCampo($campo)
    {
        if (!in_array($campo, $this->camposConError)) {
            $this->camposConError[] = $campo;
        }
        $this->campoConError = true;
    }

    public function limpiarErrorCampo($campo)
    {
        $this->camposConError = array_filter($this->camposConError, function($c) use ($campo) {
            return $c !== $campo;
        });

        if (empty($this->camposConError)) {
            $this->campoConError = false;
        }
    }

    public function limpiarTodosLosErrores()
    {
        $this->camposConError = [];
        $this->campoConError = false;
        $this->erroresValidacion = [];
    }

    public function validarAntesDeGuardar()
    {
        $this->limpiarTodosLosErrores();

        try {
            // Validación paso a paso - se detiene en el primer error encontrado (igual que cliente-form)

            // 1. Validar número de factura
            if (empty($this->compra['numero_factura'])) {
                $this->mostrarErrorCampo('compra.numero_factura');
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'El campo Número de Factura es obligatorio';
                return false;
            }

            // 2. Validar que el número de factura sea único
            try {
                $this->validarNumeroFacturaUnico();
            } catch (\Exception $e) {
                $this->mostrarErrorCampo('compra.numero_factura');
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = $e->getMessage();
                return false;
            }

            // 3. Validar proveedor
            if (empty($this->proveedorSeleccionado)) {
                $this->mostrarErrorCampo('proveedorSeleccionado');
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'El campo Proveedor es obligatorio';
                return false;
            }

            // 4. Validar fecha de emisión
            if (empty($this->compra['fecha_emision'])) {
                $this->mostrarErrorCampo('compra.fecha_emision');
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'El campo Fecha de Emisión es obligatorio';
                return false;
            }

            // 5. Validar fecha de recepción
            if (empty($this->compra['fecha_recepcion'])) {
                $this->mostrarErrorCampo('compra.fecha_recepcion');
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'El campo Fecha de Recepción es obligatorio';
                return false;
            }

            // 6. Validar que tenga al menos un producto
            if (empty($this->productosCompra)) {
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'Debe agregar al menos un producto a la compra';
                return false;
            }

            // 7. Validar productos individuales (solo si hay productos)
            foreach ($this->productosCompra as $index => $producto) {
                if (empty($producto['producto_id'])) {
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = "Debe seleccionar un producto en la posición " . ($index + 1);
                    return false;
                }

                if (empty($producto['precio']) || $producto['precio'] <= 0) {
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = "El precio del producto en la posición " . ($index + 1) . " debe ser mayor a 0";
                    return false;
                }

                if (empty($producto['cantidad_ingresada']) || $producto['cantidad_ingresada'] <= 0) {
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = "La cantidad del producto en la posición " . ($index + 1) . " debe ser mayor a 0";
                    return false;
                }

                if (empty($producto['unidad_medida_id'])) {
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = "Debe seleccionar una unidad de medida para el producto en la posición " . ($index + 1);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error en validación: ' . $e->getMessage());
            $this->erroresValidacion[] = 'Error en la validación: ' . $e->getMessage();
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error en la validación';
            return false;
        }
    }

    public function validarParaGuardadoTemporal()
    {
        $this->limpiarTodosLosErrores();

        try {
            // Para guardado temporal solo necesitamos al menos un producto
            if (empty($this->productosCompra)) {
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'Debe agregar al menos un producto para guardar temporalmente';
                return false;
            }

            // Validar que los productos agregados tengan información básica
            foreach ($this->productosCompra as $index => $producto) {
                if (empty($producto['producto_id'])) {
                    $this->mostrarAlerta = true;
                    $this->mensajeAlerta = "Debe seleccionar un producto en la posición " . ($index + 1);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error en validación temporal: ' . $e->getMessage());
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error en la validación: ' . $e->getMessage();
            return false;
        }
    }

    // Listeners para limpiar errores cuando cambien los campos
    public function updated($propertyName)
    {
        // Limpiar error del campo específico cuando cambie
        if (str_contains($propertyName, 'compra.numero_factura') && !empty($this->compra['numero_factura'])) {
            $this->limpiarErrorCampo('compra.numero_factura');
        }

        if (str_contains($propertyName, 'compra.fecha_emision') && !empty($this->compra['fecha_emision'])) {
            $this->limpiarErrorCampo('compra.fecha_emision');
        }

        if (str_contains($propertyName, 'compra.fecha_recepcion') && !empty($this->compra['fecha_recepcion'])) {
            $this->limpiarErrorCampo('compra.fecha_recepcion');
        }

        if (str_contains($propertyName, 'proveedorSeleccionado') && !empty($this->proveedorSeleccionado)) {
            $this->limpiarErrorCampo('proveedorSeleccionado');
        }

        // Si se actualiza el código de barras, limpiar mensajes de error previos
        if (str_contains($propertyName, 'codigoBarras')) {
            // Limpiar mensaje de flash si hay uno
            session()->forget(['error', 'success']);
        }

        // Limpiar alerta si no hay más errores y si el campo específico se completó
        if (empty($this->camposConError)) {
            $this->mostrarAlerta = false;
            $this->mensajeAlerta = '';
        }
    }

    public function dehydrate()
    {
        // Limpiar propiedades que pueden causar problemas en DOM morphing
        $this->dispatch('limpiar-alertas-dom');

        // Limpiar arrays grandes para mejorar rendimiento
        if (count($this->resultadosBusquedaModal) > 30) {
            $this->resultadosBusquedaModal = [];
        }

        // Limpiar propiedades temporales que pueden causar conflictos
        if (!$this->mostrarModalBusqueda) {
            $this->busquedaModalProductos = '';
        }

        // Forzar limpieza de cache de productos si hay muchos elementos
        if (count($this->productos) > 100) {
            $this->productos = [];
        }

        // Limpiar referencias problemáticas para DOM morphing
        if (!$this->productoTemporal['producto_id']) {
            $this->productoSeleccionado = null;
        }

        // Asegurar que los arrays no tengan elementos null o inválidos
        $this->productos = array_filter($this->productos ?: []);
        $this->productosCompra = array_filter($this->productosCompra ?: []);

        // Limpiar datos temporales que pueden causar conflictos de navegación
        if (empty($this->codigoBarras)) {
            // Limpiar estados relacionados con el scanner
            $this->dispatch('limpiar-scanner-state');
        }

        // Detectar si hay inconsistencias que requieren refresh forzado
        if (count($this->productosCompra) > 0) {
            foreach ($this->productosCompra as $index => $producto) {
                if (!is_array($producto) || !isset($producto['producto_id'])) {
                    // Producto corrupto, forzar refresh
                    $this->dispatch('force-refresh');
                    break;
                }
            }
        }
    }

    public function destroying()
    {
        // Limpieza completa al destruir el componente
        $this->dispatch('cleanup-component');
        $this->reset();
    }

    public function forceRefresh()
    {
        // Método para forzar refresh cuando hay problemas de DOM
        $this->dispatch('dom-refresh-complete');
        // Forzar re-render limpiando cache interno
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.inventario.compra-de-producto');
    }
}
