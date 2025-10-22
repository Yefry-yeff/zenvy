<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\UnidadMedida;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\TipoCliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompraDeProducto extends Component
{
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
        $this->cargarDatosIniciales();
        $this->compra['fecha_emision'] = now()->format('Y-m-d');
        $this->compra['fecha_recepcion'] = now()->format('Y-m-d');
        
        // Cargar trámite temporal si existe
        if (session()->has('tramite_a_cargar')) {
            $this->cargarTramiteDesdeSession();
        }
    }

    public function cargarTramiteDesdeSession()
    {
        $tramite = session('tramite_a_cargar');
        
        if ($tramite) {
            $this->compra['numero_factura'] = $tramite['numero_factura'] ?? '';
            $this->compra['fecha_emision'] = $tramite['fecha_emision'] ?? now()->format('Y-m-d');
            $this->compra['fecha_recepcion'] = $tramite['fecha_recepcion'] ?? now()->format('Y-m-d');
            $this->compra['fecha_vencimiento'] = $tramite['fecha_vencimiento'] ?? '';
            $this->proveedorSeleccionado = $tramite['proveedor_id'] ?? null;
            $this->productosCompra = $tramite['productos'] ?? [];
            
            // Recalcular totales
            $this->calcularTotales();
            
            // Activar la sección de productos
            $this->mostrarSeccionProductosActiva = true;
            
            // Limpiar la sesión
            session()->forget('tramite_a_cargar');
            
            // Eliminar el trámite de la lista de temporales
            $tramites = session('tramites_temporales_compras', []);
            $tramites = array_filter($tramites, function($t) use ($tramite) {
                return $t['fecha_guardado'] !== $tramite['fecha_guardado'];
            });
            session(['tramites_temporales_compras' => array_values($tramites)]);
            
            session()->flash('success', '✅ Trámite temporal cargado. Puede continuar editando.');
        }
    }

    public function cargarDatosIniciales()
    {
        // Debug: Verificar qué tipos de cliente existen
        $tiposCliente = TipoCliente::all();
        Log::info('Tipos de cliente disponibles: ', $tiposCliente->toArray());

        // Cargar solo proveedores (clientes con tipo_cliente = "Proveedor")
        $this->proveedores = Cliente::whereHas('tipoCliente', function($query) {
            $query->where('nombre', 'LIKE', '%Proveedor%');
        })
        ->with(['tipoCliente', 'direccion'])
        ->where('estado_id', 1) // Solo clientes activos
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

        // Debug: Log para verificar proveedores encontrados
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

        // Cargar productos activos
        $this->productos = Producto::where('estado_id', 1)
            ->with(['marca', 'subcategoria'])
            ->orderBy('nombre')
            ->get()
            ->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo_barra' => $producto->codigo_barra,
                    'marca' => $producto->marca->nombre ?? 'N/A',
                    'subcategoria' => $producto->subcategoria->nombre ?? 'N/A'
                ];
            })
            ->toArray();

        // Cargar unidades de medida
        $this->unidadesMedida = UnidadMedida::orderBy('nombre')
            ->get()
            ->map(function($unidad) {
                return [
                    'id' => $unidad->id,
                    'nombre' => $unidad->nombre,
                    'simbolo' => $unidad->simbolo
                ];
            })
            ->toArray();
    }

    public function updatedBusquedaProducto()
    {
        // Solo buscar por código de barras exacto (para escáner)
        if (!empty($this->busquedaProducto)) {
            // Limpiar espacios en blanco
            $codigoBarra = trim($this->busquedaProducto);
            
            // Buscar producto por código de barras exacto (solo campos de la tabla producto)
            $productoPorCodigo = DB::table('producto')
                ->where('codigo_barra', $codigoBarra)
                ->where('estado_id', 1)
                ->select('id')
                ->first();

            if ($productoPorCodigo) {
                // Auto-seleccionar producto si coincide el código de barras exacto
                $this->seleccionarProducto($productoPorCodigo->id);
                return;
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
            // Mostrar solo el código de barras en el campo
            $this->busquedaProducto = $producto['codigo_barra'] ?? '';
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

        // Verificar si el producto ya está en la lista - ELIMINADO para permitir duplicados
        // Los productos pueden agregarse múltiples veces sin restricciones

        // Calcular subtotal e ISV para este producto
        $precio = (float) $this->productoTemporal['precio'];
        $cantidadRecibida = (int) ($this->productoTemporal['cantidad_recibida'] ?? 0);
        $cantidadPorUnidad = (int) ($this->productoTemporal['cantidad_por_unidad'] ?? 1);
        $cantidadIngresada = $cantidadRecibida * $cantidadPorUnidad; // Cálculo automático
        $isv = (float) $this->productoTemporal['isv'];

        // El subtotal se calcula con la cantidad recibida (paquetes/cajas)
        $subtotalProducto = $precio * $cantidadRecibida;
        $isvProducto = $subtotalProducto * ($isv / 100);
        $totalProducto = $subtotalProducto + $isvProducto;

        // Obtener información del producto
        $producto = collect($this->productos)->firstWhere('id', $this->productoTemporal['producto_id']);
        $unidadMedida = collect($this->unidadesMedida)->firstWhere('id', $this->productoTemporal['unidad_medida_id']);

        // Agregar producto a la lista (sin dispatch, más rápido)
        $this->productosCompra[] = [
            'producto_id' => $this->productoTemporal['producto_id'],
            'producto_nombre' => $producto['nombre'],
            'producto_codigo' => $producto['codigo_barra'],
            'precio' => $precio,
            'cantidad_recibida' => $cantidadRecibida, // Paquetes/cajas recibidos
            'cantidad_por_unidad' => $cantidadPorUnidad, // Unidades por paquete
            'cantidad_ingresada' => $cantidadIngresada, // Cantidad unitaria total (calculada)
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
    }

    public function eliminarProducto($index)
    {
        unset($this->productosCompra[$index]);
        $this->productosCompra = array_values($this->productosCompra); // Reindexar array
        $this->calcularTotales();
    }

    public function actualizarCantidadRecibida($index, $nuevaCantidadRecibida)
    {
        $nuevaCantidadRecibida = (int) $nuevaCantidadRecibida;

        if ($nuevaCantidadRecibida <= 0) {
            $this->mostrarAlertaError('La cantidad recibida debe ser mayor a cero');
            return;
        }

        if (isset($this->productosCompra[$index])) {
            $cantidadPorUnidad = $this->productosCompra[$index]['cantidad_por_unidad'] ?? 1;
            
            // Actualizar cantidad_recibida y recalcular cantidad_ingresada
            $this->productosCompra[$index]['cantidad_recibida'] = $nuevaCantidadRecibida;
            $this->productosCompra[$index]['cantidad_ingresada'] = $nuevaCantidadRecibida * $cantidadPorUnidad;
            $this->productosCompra[$index]['cantidad_sin_asignar'] = $nuevaCantidadRecibida * $cantidadPorUnidad;

            // Recalcular los totales para este producto (subtotal se calcula con cantidad_recibida)
            $precio = $this->productosCompra[$index]['precio'];
            $isv = $this->productosCompra[$index]['isv'];

            $subtotalProducto = $precio * $nuevaCantidadRecibida; // Precio x Cant. Recibida
            $isvProducto = $subtotalProducto * ($isv / 100);
            $totalProducto = $subtotalProducto + $isvProducto;

            $this->productosCompra[$index]['sub_total_producto'] = $subtotalProducto;
            $this->productosCompra[$index]['precio_total'] = $totalProducto;

            // Recalcular totales generales
            $this->calcularTotales();
        }
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

            // Guardar los productos de la compra
            foreach ($this->productosCompra as $producto) {
                CompraHasProducto::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto['producto_id'],
                    'precio' => $producto['precio'],
                    'cantidad_ingresada' => $producto['cantidad_ingresada'],
                    'cantidad_sin_asignar' => $producto['cantidad_sin_asignar'],
                    'fecha_expiracion' => $producto['fecha_expiracion'],
                    'sub_total_producto' => $producto['sub_total_producto'],
                    'isv' => $producto['sub_total_producto'] * ($producto['isv'] / 100),
                    'precio_total' => $producto['precio_total'],
                    'unidad_medida_id' => $producto['unidad_medida_id'],
                ]);
            }

            DB::commit();

            $this->mostrarModalExito = true;
            $this->mensajeModalExito = 'Compra registrada exitosamente';

            // Limpiar formulario
            $this->resetFormulario();

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
        $compraExistente = Compra::where('numero_factura', $numeroFactura)
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
            $compraExistente = Compra::where('numero_factura', $value)
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
        $query = Producto::select('producto.id', 'producto.nombre', 'producto.descripcion', 'producto.codigo_barra', 'producto.precio_base', 'producto.marca_id', 'producto.subcategoria_id')
            ->where('producto.estado_id', 1);

        // Filtro de búsqueda por texto (solo si hay 3+ caracteres)
        if ($this->busquedaModalProductos && strlen($this->busquedaModalProductos) >= 3) {
            $busqueda = $this->busquedaModalProductos;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%')
                  ->orWhere('codigo_barra', 'like', '%' . $busqueda . '%')
                  ->orWhere('descripcion', 'like', '%' . $busqueda . '%');
            });
        }

        // Filtro por marca
        if ($this->marcaSeleccionadaModal) {
            $query->where('marca_id', $this->marcaSeleccionadaModal);
        }

        // Filtro por subcategoría
        if ($this->subcategoriaSeleccionadaModal) {
            $query->where('subcategoria_id', $this->subcategoriaSeleccionadaModal);
        }

        // Filtro por categoría (a través de subcategoría)
        if ($this->categoriaSeleccionadaModal && !$this->subcategoriaSeleccionadaModal) {
            $query->join('subcategoria', 'producto.subcategoria_id', '=', 'subcategoria.id')
                  ->where('subcategoria.categoria_id', $this->categoriaSeleccionadaModal);
        }

        // Cargar solo 30 resultados y las relaciones necesarias
        $productos = $query->with(['marca:id,nombre', 'subcategoria:id,nombre,categoria_id', 'subcategoria.categoria:id,nombre'])
            ->limit(30)
            ->get();

        $this->resultadosBusquedaModal = $productos->map(function($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'codigo_barra' => $producto->codigo_barra,
                'precio_base' => $producto->precio_base,
                'marca' => $producto->marca->nombre ?? null,
                'categoria' => $producto->subcategoria->categoria->nombre ?? null,
                'subcategoria' => $producto->subcategoria->nombre ?? null,
            ];
        })->toArray();
    }

    public function seleccionarProductoModal($productoId)
    {
        $producto = Producto::with(['marca', 'subcategoria', 'unidadMedidaVenta'])->find($productoId);
        
        if ($producto) {
            // Llenar el campo de búsqueda con el código de barras o nombre
            $this->busquedaProducto = $producto->codigo_barra ?? $producto->nombre;
            
            // Establecer el producto temporal
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

            // Cerrar modal
            $this->cerrarModalBusqueda();
        }
    }

    // Métodos para trámites temporales
    public function guardarTramiteTemporal()
    {
        // Usar validación específica para guardado temporal
        if (!$this->validarParaGuardadoTemporal()) {
            return; // Si hay errores, no continuar
        }

        $proveedor = Cliente::find($this->proveedorSeleccionado);
        
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
    }

    public function render()
    {
        return view('livewire.inventario.compra-de-producto');
    }
}
