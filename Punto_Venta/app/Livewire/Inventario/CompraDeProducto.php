<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\UnidadCompra;
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
    public $unidadesCompra = [];
    public $proveedorSeleccionado = null;

    // Productos en la compra
    public $productosCompra = [];
    
    // Producto temporal para agregar
    public $productoTemporal = [
        'producto_id' => null,
        'precio' => 0,
        'cantidad_ingresada' => 1,
        'fecha_expiracion' => '',
        'unidad_compra_id' => null,
        'isv' => 0,
    ];

    // Busqueda de productos
    public $busquedaProducto = '';
    public $productosFiltrados = [];
    public $mostrarListaProductos = false;
    
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

    protected $rules = [
        'compra.numero_factura' => 'required|string|max:90',
        'compra.fecha_emision' => 'required|date',
        'compra.fecha_recepcion' => 'required|date',
        'proveedorSeleccionado' => 'required|exists:cliente,id',
        'productosCompra' => 'required|array|min:1',
        'productosCompra.*.producto_id' => 'required|exists:producto,id',
        'productosCompra.*.precio' => 'required|numeric|min:0.01',
        'productosCompra.*.cantidad_ingresada' => 'required|integer|min:1',
        'productosCompra.*.unidad_compra_id' => 'required|exists:unidad_compra,id',
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

        // Cargar unidades de compra
        $this->unidadesCompra = UnidadCompra::orderBy('nombre')
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
        if (strlen($this->busquedaProducto) >= 2) {
            // Buscar por código de barras exacto primero
            $productoPorCodigo = collect($this->productos)->first(function($producto) {
                return $producto['codigo_barra'] === $this->busquedaProducto;
            });

            if ($productoPorCodigo) {
                // Auto-seleccionar producto si coincide el código de barras exacto
                $this->seleccionarProducto($productoPorCodigo['id']);
                return;
            }

            // Si no es código exacto, mostrar lista filtrada
            $this->productosFiltrados = collect($this->productos)->filter(function($producto) {
                return stripos($producto['nombre'], $this->busquedaProducto) !== false ||
                       stripos($producto['codigo_barra'], $this->busquedaProducto) !== false;
            })->take(10)->values()->toArray();
            $this->mostrarListaProductos = count($this->productosFiltrados) > 0;
        } else {
            $this->productosFiltrados = [];
            $this->mostrarListaProductos = false;
        }
    }

    public function seleccionarProducto($productoId)
    {
        $producto = collect($this->productos)->firstWhere('id', $productoId);
        if ($producto) {
            $this->productoTemporal['producto_id'] = $producto['id'];
            $this->busquedaProducto = $producto['nombre'] . ' (' . ($producto['codigo_barra'] ?? 'Sin código') . ')';
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
               !empty($this->productoTemporal['unidad_compra_id']) &&
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

        if (!$this->productoTemporal['unidad_compra_id']) {
            $this->mostrarAlertaError('Debe seleccionar una unidad de compra');
            return;
        }

        // Verificar si el producto ya está en la lista - ELIMINADO para permitir duplicados
        // Los productos pueden agregarse múltiples veces sin restricciones

        // Calcular subtotal e ISV para este producto
        $precio = (float) $this->productoTemporal['precio'];
        $cantidad = (int) $this->productoTemporal['cantidad_ingresada'];
        $isv = (float) $this->productoTemporal['isv'];
        
        $subtotalProducto = $precio * $cantidad;
        $isvProducto = $subtotalProducto * ($isv / 100);
        $totalProducto = $subtotalProducto + $isvProducto;

        // Obtener información del producto
        $producto = collect($this->productos)->firstWhere('id', $this->productoTemporal['producto_id']);
        $unidadCompra = collect($this->unidadesCompra)->firstWhere('id', $this->productoTemporal['unidad_compra_id']);

        // Agregar producto a la lista
        $this->productosCompra[] = [
            'producto_id' => $this->productoTemporal['producto_id'],
            'producto_nombre' => $producto['nombre'],
            'producto_codigo' => $producto['codigo_barra'],
            'precio' => $precio,
            'cantidad_ingresada' => $cantidad,
            'cantidad_sin_asignar' => $cantidad, // Inicialmente toda la cantidad sin asignar
            'fecha_expiracion' => $this->productoTemporal['fecha_expiracion'] ?: null,
            'unidad_compra_id' => $this->productoTemporal['unidad_compra_id'],
            'unidad_compra_nombre' => $unidadCompra['nombre'] ?? '',
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
            'cantidad_ingresada' => 1,
            'fecha_expiracion' => '',
            'unidad_compra_id' => null,
            'isv' => 0,
        ];
        $this->busquedaProducto = '';
        $this->mostrarListaProductos = false;
    }

    public function guardarCompra()
    {
        try {
            $this->validate();

            DB::beginTransaction();

            // Crear la compra principal
            $compra = Compra::create([
                'numero_factura' => $this->compra['numero_factura'],
                'fecha_vencimiento' => $this->compra['fecha_vencimiento'] ?: null,
                'fecha_emision' => $this->compra['fecha_emision'],
                'fecha_recepcion' => $this->compra['fecha_recepcion'],
                'estado_id' => 1, // Estado "Activo" por defecto
                'cliente_id' => $this->proveedorSeleccionado, // Proveedor seleccionado
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
                    'unidad_compra_id' => $producto['unidad_compra_id'],
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

    public function render()
    {
        return view('livewire.inventario.compra-de-producto');
    }
}
