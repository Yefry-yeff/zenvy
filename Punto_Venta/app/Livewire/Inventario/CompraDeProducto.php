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
            
            // Buscar producto por código de barras exacto
            $productoPorCodigo = collect($this->productos)->first(function($producto) use ($codigoBarra) {
                return $producto['codigo_barra'] === $codigoBarra;
            });

            if ($productoPorCodigo) {
                // Auto-seleccionar producto si coincide el código de barras exacto
                $this->seleccionarProducto($productoPorCodigo['id']);
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
        $cantidad = (int) $this->productoTemporal['cantidad_ingresada'];
        $isv = (float) $this->productoTemporal['isv'];

        $subtotalProducto = $precio * $cantidad;
        $isvProducto = $subtotalProducto * ($isv / 100);
        $totalProducto = $subtotalProducto + $isvProducto;

        // Obtener información del producto
        $producto = collect($this->productos)->firstWhere('id', $this->productoTemporal['producto_id']);
        $unidadMedida = collect($this->unidadesMedida)->firstWhere('id', $this->productoTemporal['unidad_medida_id']);

        // Agregar producto a la lista
        $this->productosCompra[] = [
            'producto_id' => $this->productoTemporal['producto_id'],
            'producto_nombre' => $producto['nombre'],
            'producto_codigo' => $producto['codigo_barra'],
            'precio' => $precio,
            'cantidad_ingresada' => $cantidad,
            'cantidad_sin_asignar' => $cantidad, // Inicialmente toda la cantidad sin asignar
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
            'unidad_medida_id' => null,
            'isv' => 0,
        ];
        $this->busquedaProducto = '';
        $this->mostrarListaProductos = false;
    }

    public function guardarCompra()
    {
        try {
            // Validar campos básicos primero
            $this->validate();

            // Validar número de factura único
            $this->validarNumeroFacturaUnico();

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
        // Cargar marcas (sin filtro de estado_id porque la tabla marca no tiene esa columna)
        $this->marcasDisponibles = \App\Models\Marca::orderBy('nombre')
            ->get()
            ->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])
            ->toArray();

        // Cargar categorías (sin filtro de estado_id porque la tabla categoria no tiene esa columna)
        $this->categoriasDisponibles = \App\Models\Categoria::orderBy('nombre')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
            ->toArray();

        // Cargar subcategorías (sin filtro de estado_id porque la tabla subcategoria no tiene esa columna)
        $this->subcategoriasDisponibles = \App\Models\Subcategoria::orderBy('nombre')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])
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
        $query = Producto::with(['marca', 'subcategoria.categoria'])
            ->where('estado_id', 1);

        // Filtro de búsqueda por texto
        if ($this->busquedaModalProductos) {
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->busquedaModalProductos . '%')
                  ->orWhere('codigo_barra', 'like', '%' . $this->busquedaModalProductos . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->busquedaModalProductos . '%');
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
            $query->whereHas('subcategoria', function($q) {
                $q->where('categoria_id', $this->categoriaSeleccionadaModal);
            });
        }

        $productos = $query->limit(50)->get();

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
        // Validar que hay datos suficientes para guardar
        if (empty($this->compra['numero_factura']) || !$this->proveedorSeleccionado || empty($this->productosCompra)) {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Debe completar al menos el número de factura, proveedor y agregar productos para guardar un trámite temporal.';
            return;
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

    public function render()
    {
        return view('livewire.inventario.compra-de-producto');
    }
}
