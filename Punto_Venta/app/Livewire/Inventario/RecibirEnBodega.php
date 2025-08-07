<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\RecibidoBodega;
use App\Models\UnidadMedida;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibirEnBodega extends Component
{
    // Búsqueda de productos
    public $buscarProducto = '';
    public $productosSugeridos = [];
    public $mostrarSugerenciasProductos = false;
    
    // Producto seleccionado
    public $productoSeleccionado = null;
    public $nombreProducto = '';
    public $descripcionProducto = '';
    public $codigoBarraProducto = '';
    public $marcaProducto = '';
    public $unidadMedidaProducto = '';
    
    // Datos del recibido
    public $cantidadCompraLote = '';
    public $cantidadInicialSeccion = '';
    public $fechaRecibido = '';
    public $fechaExpiracion = '';
    public $comentario = '';
    public $unidadesCompra = '';
    public $unidadCompraId = null;
    
    // Secciones y bodegas
    public $seccionSeleccionada = null;
    public $secciones = [];
    public $unidadesMedida = [];
    
    // Jerarquía Bodega → Segmento → Sección
    public $buscarBodega = '';
    public $bodegasSugeridas = [];
    public $mostrarSugerenciasBodegas = false;
    public $bodegaSeleccionada = null;
    public $nombreBodega = '';

    // Nuevas propiedades para distribución de compras
    public $comprasActivas = [];
    public $proveedores = [];
    public $filtroProducto = '';
    public $filtroProveedor = '';
    public $filtroEstadoDistribucion = '';
    
    // Modal de distribución
    public $mostrarModalDistribucion = false;
    public $productoParaDistribuir = null;
    public $cantidadDistribuir = '';
    public $fechaDistribucion = '';
    public $bodegaDistribucion = '';
    public $segmentoDistribucion = '';
    public $seccionDistribucion = '';
    public $comentarioDistribucion = '';
    public $bodegas = [];
    public $segmentos = [];
    public $nombreBodegaDistribucion = '';
    public $nombreSegmentoDistribucion = '';
    public $nombreSeccionDistribucion = '';
    
    public $buscarSegmento = '';
    public $segmentosSugeridos = [];
    public $mostrarSugerenciasSegmentos = false;
    public $segmentoSeleccionado = null;
    public $nombreSegmento = '';
    
    public $buscarSeccion = '';
    public $seccionesSugeridas = [];
    public $mostrarSugerenciasSecciones = false;
    public $nombreSeccion = '';
    
    // Modales y mensajes
    public $mostrarModalConfirmacion = false;
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    public function mount()
    {
        $this->fechaRecibido = date('Y-m-d');
        $this->fechaDistribucion = date('Y-m-d');
        $this->cargarUnidadesMedida();
        $this->cargarComprasActivas();
        $this->cargarProveedores();
        $this->cargarBodegas();
    }

    // Búsqueda de Bodegas
    public function updatedBuscarBodega()
    {
        if (strlen($this->buscarBodega) >= 1) {
            $this->buscarBodegas();
        } else {
            $this->mostrarTodasBodegas();
        }
        $this->mostrarSugerenciasBodegas = true;
    }

    public function enfocarBodega()
    {
        $this->mostrarTodasBodegas();
        $this->mostrarSugerenciasBodegas = true;
    }

    public function mostrarTodasBodegas()
    {
        try {
            $user = Auth::user();
            
            $query = Bodega::with('tienda')
                ->where('estado_id', 1);
                
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $query->where('tienda_id', $user->tienda_id);
            }
                
            $this->bodegasSugeridas = $query->limit(10)->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todas las bodegas', [
                'mensaje' => $e->getMessage()
            ]);
            $this->bodegasSugeridas = [];
        }
    }

    public function buscarBodegas()
    {
        try {
            $user = Auth::user();
            
            $query = Bodega::with('tienda')
                ->where('nombre', 'like', '%' . $this->buscarBodega . '%')
                ->where('estado_id', 1);
                
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $query->where('tienda_id', $user->tienda_id);
            }
                
            $this->bodegasSugeridas = $query->limit(10)->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar bodegas', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarBodega
            ]);
            $this->bodegasSugeridas = [];
        }
    }

    public function seleccionarBodega($bodegaId)
    {
        try {
            $bodega = Bodega::with('tienda')->findOrFail($bodegaId);
            
            $this->bodegaSeleccionada = $bodega;
            $this->buscarBodega = $bodega->nombre;
            $this->nombreBodega = $bodega->nombre;
            
            $this->mostrarSugerenciasBodegas = false;
            $this->bodegasSugeridas = [];
            
            // Limpiar selecciones dependientes
            $this->limpiarSeleccionSegmento();
            $this->limpiarSeleccionSeccion();
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar bodega', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la bodega');
        }
    }

    public function limpiarSeleccionBodega()
    {
        $this->bodegaSeleccionada = null;
        $this->nombreBodega = '';
        $this->limpiarSeleccionSegmento();
        $this->limpiarSeleccionSeccion();
    }

    // Búsqueda de Segmentos
    public function updatedBuscarSegmento()
    {
        if ($this->bodegaSeleccionada) {
            if (strlen($this->buscarSegmento) >= 1) {
                $this->buscarSegmentos();
            } else {
                $this->mostrarTodosSegmentos();
            }
            $this->mostrarSugerenciasSegmentos = true;
        } else {
            $this->segmentosSugeridos = [];
            $this->mostrarSugerenciasSegmentos = false;
        }
    }

    public function enfocarSegmento()
    {
        if ($this->bodegaSeleccionada) {
            $this->mostrarTodosSegmentos();
            $this->mostrarSugerenciasSegmentos = true;
        }
    }

    public function mostrarTodosSegmentos()
    {
        try {
            if (!$this->bodegaSeleccionada) return;
            
            $this->segmentosSugeridos = Segmento::where('bodega_id', $this->bodegaSeleccionada->id)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todos los segmentos', [
                'mensaje' => $e->getMessage(),
                'bodega_id' => $this->bodegaSeleccionada->id ?? null
            ]);
            $this->segmentosSugeridos = [];
        }
    }

    public function buscarSegmentos()
    {
        try {
            if (!$this->bodegaSeleccionada) return;
            
            $this->segmentosSugeridos = Segmento::where('bodega_id', $this->bodegaSeleccionada->id)
                ->where('descripcion', 'like', '%' . $this->buscarSegmento . '%')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar segmentos', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarSegmento,
                'bodega_id' => $this->bodegaSeleccionada->id ?? null
            ]);
            $this->segmentosSugeridos = [];
        }
    }

    public function seleccionarSegmento($segmentoId)
    {
        try {
            $segmento = Segmento::findOrFail($segmentoId);
            
            $this->segmentoSeleccionado = $segmento;
            $this->buscarSegmento = $segmento->descripcion;
            $this->nombreSegmento = $segmento->descripcion;
            
            $this->mostrarSugerenciasSegmentos = false;
            $this->segmentosSugeridos = [];
            
            // Limpiar selección de sección
            $this->limpiarSeleccionSeccion();
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar segmento', [
                'segmento_id' => $segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos del segmento');
        }
    }

    public function limpiarSeleccionSegmento()
    {
        $this->segmentoSeleccionado = null;
        $this->buscarSegmento = '';
        $this->nombreSegmento = '';
        $this->segmentosSugeridos = [];
        $this->mostrarSugerenciasSegmentos = false;
        $this->limpiarSeleccionSeccion();
    }

    // Búsqueda de Secciones
    public function updatedBuscarSeccion()
    {
        if ($this->segmentoSeleccionado) {
            if (strlen($this->buscarSeccion) >= 1) {
                $this->buscarSecciones();
            } else {
                $this->mostrarTodasSecciones();
            }
            $this->mostrarSugerenciasSecciones = true;
        } else {
            $this->seccionesSugeridas = [];
            $this->mostrarSugerenciasSecciones = false;
        }
    }

    public function enfocarSeccion()
    {
        if ($this->segmentoSeleccionado) {
            $this->mostrarTodasSecciones();
            $this->mostrarSugerenciasSecciones = true;
        }
    }

    public function mostrarTodasSecciones()
    {
        try {
            if (!$this->segmentoSeleccionado) return;
            
            $this->seccionesSugeridas = Seccion::where('segmento_id', $this->segmentoSeleccionado->id)
                ->where('estado_id', 1)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todas las secciones', [
                'mensaje' => $e->getMessage(),
                'segmento_id' => $this->segmentoSeleccionado->id ?? null
            ]);
            $this->seccionesSugeridas = [];
        }
    }

    public function buscarSecciones()
    {
        try {
            if (!$this->segmentoSeleccionado) return;
            
            $this->seccionesSugeridas = Seccion::where('segmento_id', $this->segmentoSeleccionado->id)
                ->where('descripcion', 'like', '%' . $this->buscarSeccion . '%')
                ->where('estado_id', 1)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar secciones', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarSeccion,
                'segmento_id' => $this->segmentoSeleccionado->id ?? null
            ]);
            $this->seccionesSugeridas = [];
        }
    }

    public function seleccionarSeccion($seccionId)
    {
        try {
            $seccion = Seccion::findOrFail($seccionId);
            
            $this->seccionSeleccionada = $seccion->id;
            $this->buscarSeccion = $seccion->descripcion;
            $this->nombreSeccion = $seccion->descripcion;
            
            $this->mostrarSugerenciasSecciones = false;
            $this->seccionesSugeridas = [];
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar sección', [
                'seccion_id' => $seccionId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la sección');
        }
    }

    public function limpiarSeleccionSeccion()
    {
        $this->seccionSeleccionada = null;
        $this->buscarSeccion = '';
        $this->nombreSeccion = '';
        $this->seccionesSugeridas = [];
        $this->mostrarSugerenciasSecciones = false;
    }

    public function updatedBuscarProducto()
    {
        if (strlen($this->buscarProducto) >= 1) {
            $this->buscarProductos();
        } else {
            $this->mostrarTodosProductos();
        }
        $this->mostrarSugerenciasProductos = true;
    }

    public function enfocarProducto()
    {
        $this->mostrarTodosProductos();
        $this->mostrarSugerenciasProductos = true;
    }

    public function mostrarTodosProductos()
    {
        try {
            $this->productosSugeridos = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])
                ->where('estado_id', 1)
                ->orderBy('nombre')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todos los productos', [
                'mensaje' => $e->getMessage()
            ]);
            $this->productosSugeridos = [];
        }
    }

    public function buscarProductos()
    {
        try {
            $this->productosSugeridos = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])
                ->where(function($query) {
                    $query->where('nombre', 'like', '%' . $this->buscarProducto . '%')
                          ->orWhere('codigo_barra', 'like', '%' . $this->buscarProducto . '%')
                          ->orWhere('codigo_estatal', 'like', '%' . $this->buscarProducto . '%');
                })
                ->where('estado_id', 1) // Solo productos activos
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar productos', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarProducto
            ]);
            $this->productosSugeridos = [];
        }
    }

    public function seleccionarProducto($productoId)
    {
        try {
            $producto = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])->findOrFail($productoId);
            
            // Debug temporal
            Log::info('Producto seleccionado:', [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'marca_id' => $producto->marca_id,
                'marca' => $producto->marca ? $producto->marca->toArray() : null,
                'unidad_medida_compra_id' => $producto->unidad_medida_compra_id,
                'unidad_medida' => $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->toArray() : null
            ]);
            
            $this->productoSeleccionado = $producto;
            $this->buscarProducto = $producto->nombre;
            $this->nombreProducto = $producto->nombre;
            $this->descripcionProducto = $producto->descripcion;
            $this->codigoBarraProducto = $producto->codigo_barra ?? 'N/A';
            $this->marcaProducto = $producto->marca ? $producto->marca->nombre : 'Sin marca';
            $this->unidadMedidaProducto = $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->nombre : 'N/A';
            $this->unidadCompraId = $producto->unidad_medida_compra_id;
            
            $this->mostrarSugerenciasProductos = false;
            $this->productosSugeridos = [];
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar producto', [
                'producto_id' => $productoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos del producto');
        }
    }

    public function limpiarSeleccionProducto()
    {
        $this->productoSeleccionado = null;
        $this->nombreProducto = '';
        $this->descripcionProducto = '';
        $this->codigoBarraProducto = '';
        $this->marcaProducto = '';
        $this->unidadMedidaProducto = '';
        $this->unidadCompraId = null;
    }

    public function confirmarRecibido()
    {
        // Validaciones
        if (!$this->productoSeleccionado) {
            $this->mostrarError('Debe seleccionar un producto');
            return;
        }

        if (!$this->cantidadCompraLote || $this->cantidadCompraLote <= 0) {
            $this->mostrarError('La cantidad del lote debe ser mayor a 0');
            return;
        }

        if (!$this->cantidadInicialSeccion || $this->cantidadInicialSeccion <= 0) {
            $this->mostrarError('La cantidad inicial en sección debe ser mayor a 0');
            return;
        }

        if (!$this->seccionSeleccionada) {
            $this->mostrarError('Debe seleccionar una sección');
            return;
        }

        if (!$this->fechaRecibido) {
            $this->mostrarError('Debe especificar la fecha de recibido');
            return;
        }

        $this->mostrarModalConfirmacion = true;
    }

    public function ejecutarRecibido()
    {
        try {
            DB::beginTransaction();

            $recibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->productoSeleccionado->id,
                'seccion_id' => $this->seccionSeleccionada,
                'cantidad_compra_lote' => $this->cantidadCompraLote,
                'cantidad_inicial_seccion' => $this->cantidadInicialSeccion,
                'cantidad_disponible' => $this->cantidadInicialSeccion, // Inicialmente igual a la cantidad inicial
                'fecha_recibido' => $this->fechaRecibido,
                'fecha_expiracion' => $this->fechaExpiracion,
                'comentario' => $this->comentario,
                'unidades_compra' => $this->unidadesCompra,
                'unidad_compra_id' => $this->unidadCompraId,
                'users_registro_id' => Auth::id(),
                'estado_id' => 1
            ]);

            DB::commit();

            $this->mostrarModalConfirmacion = false;
            $this->mostrarExito('Producto recibido en bodega exitosamente');
            $this->limpiarFormulario();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar recibido en bodega', [
                'producto_id' => $this->productoSeleccionado->id,
                'seccion_id' => $this->seccionSeleccionada,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarModalConfirmacion = false;
            $this->mostrarError('Error al registrar el recibido en bodega');
        }
    }

    public function cancelarRecibido()
    {
        $this->mostrarModalConfirmacion = false;
    }

    public function limpiarFormulario()
    {
        $this->buscarProducto = '';
        $this->productosSugeridos = [];
        $this->mostrarSugerenciasProductos = false;
        $this->limpiarSeleccionProducto();
        
        $this->cantidadCompraLote = '';
        $this->cantidadInicialSeccion = '';
        $this->fechaRecibido = date('Y-m-d');
        $this->fechaExpiracion = '';
        $this->comentario = '';
        $this->unidadesCompra = '';
        
        // Limpiar jerarquía bodega → segmento → sección
        $this->limpiarSeleccionBodega();
    }

    private function cargarUnidadesMedida()
    {
        try {
            $this->unidadesMedida = UnidadMedida::orderBy('nombre')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar unidades de medida', [
                'mensaje' => $e->getMessage()
            ]);
            $this->unidadesMedida = [];
        }
    }

    private function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    private function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
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

    // Métodos para la nueva funcionalidad de distribución

    private function cargarComprasActivas()
    {
        try {
            // Cargar compras con productos que tengan cantidad sin asignar
            $compras = \App\Models\Compra::with([
                'detallesCompra.producto.marca',
                'detallesCompra.unidadCompra',
                'estado',
                'cliente.tipoCliente'
            ])
            ->whereHas('detallesCompra', function($query) {
                $query->where('cantidad_sin_asignar', '>', 0);
            })
            ->whereHas('estado', function($query) {
                $query->where('descripcion', 'like', '%activ%')
                      ->orWhere('descripcion', 'like', '%proceso%')
                      ->orWhere('descripcion', 'like', '%pendiente%');
            })
            ->whereHas('cliente.tipoCliente', function($query) {
                $query->where('nombre', 'like', '%proveedor%');
            })
            ->get();

            $this->comprasActivas = [];
            
            foreach ($compras as $compra) {
                foreach ($compra->detallesCompra as $detalle) {
                    if ($detalle->cantidad_sin_asignar > 0) {
                        // Aplicar filtros si están definidos
                        $cumpleFiltros = true;
                        
                        if ($this->filtroProducto && stripos($detalle->producto->nombre ?? '', $this->filtroProducto) === false) {
                            $cumpleFiltros = false;
                        }
                        
                        if ($this->filtroProveedor && stripos($compra->cliente->nombre ?? '', $this->filtroProveedor) === false) {
                            $cumpleFiltros = false;
                        }
                        
                        if ($this->filtroEstadoDistribucion === 'pendiente' && $detalle->cantidad_sin_asignar == 0) {
                            $cumpleFiltros = false;
                        }
                        
                        if ($this->filtroEstadoDistribucion === 'parcial' && ($detalle->cantidad_sin_asignar == 0 || $detalle->cantidad_sin_asignar == $detalle->cantidad_ingresada)) {
                            $cumpleFiltros = false;
                        }
                        
                        if ($cumpleFiltros) {
                            $this->comprasActivas[] = [
                                'compra_id' => $compra->id,
                                'producto_id' => $detalle->producto_id,
                                'detalle_id' => $detalle->id,
                                'numero_factura' => $compra->numero_factura,
                                'fecha_compra' => $compra->fecha_emision,
                                'producto_nombre' => $detalle->producto->nombre ?? 'Sin nombre',
                                'codigo_barra' => $detalle->producto->codigo_barra ?? '',
                                'marca' => $detalle->producto->marca->nombre ?? 'Sin marca',
                                'proveedor' => $compra->cliente->nombre ?? 'Sin proveedor',
                                'proveedor_nombre' => $compra->cliente->nombre ?? 'Sin proveedor',
                                'cantidad_total' => $detalle->cantidad_ingresada,
                                'cantidad_comprada' => $detalle->cantidad_ingresada, // Agregar esta clave
                                'cantidad_pendiente' => $detalle->cantidad_sin_asignar,
                                'cantidad_distribuida' => $detalle->cantidad_ingresada - $detalle->cantidad_sin_asignar,
                                'unidad' => $detalle->unidadCompra->nombre ?? 'Unidad',
                                'precio_unitario' => $detalle->precio,
                                'estado_distribucion' => $this->determinarEstadoDistribucion($detalle),
                                'fecha_expiracion' => $detalle->fecha_expiracion,
                            ];
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error al cargar compras activas', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->comprasActivas = [];
        }
    }

    private function determinarEstadoDistribucion($detalle)
    {
        if ($detalle->cantidad_sin_asignar == $detalle->cantidad_ingresada) {
            return 'Pendiente';
        } elseif ($detalle->cantidad_sin_asignar > 0) {
            return 'Parcial';
        } else {
            return 'Completo';
        }
    }

    private function cargarProveedores()
    {
        try {
            // Cargar proveedores únicos que tienen compras activas
            $this->proveedores = \App\Models\Cliente::whereHas('compras.detallesCompra', function($query) {
                $query->where('cantidad_sin_asignar', '>', 0);
            })
            ->whereHas('compras.estado', function($query) {
                $query->where('descripcion', 'like', '%activ%')
                      ->orWhere('descripcion', 'like', '%proceso%')
                      ->orWhere('descripcion', 'like', '%pendiente%');
            })
            ->whereHas('tipoCliente', function($query) {
                $query->where('nombre', 'like', '%proveedor%');
            })
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get(); // Eliminar ->toArray() para mantener como objetos
            
        } catch (\Exception $e) {
            Log::error('Error al cargar proveedores', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->proveedores = collect(); // Usar collect() en lugar de array vacío
        }
    }

    private function cargarBodegas()
    {
        try {
            $this->bodegas = Bodega::all();
        } catch (\Exception $e) {
            Log::error('Error al cargar bodegas', [
                'mensaje' => $e->getMessage()
            ]);
            $this->bodegas = [];
        }
    }

    public function limpiarFiltros()
    {
        $this->filtroProducto = '';
        $this->filtroProveedor = '';
        $this->filtroEstadoDistribucion = '';
        $this->cargarComprasActivas();
    }

    // Métodos para actualización de filtros en tiempo real
    public function updatedFiltroProducto()
    {
        $this->cargarComprasActivas();
    }

    public function updatedFiltroProveedor()
    {
        $this->cargarComprasActivas();
    }

    public function updatedFiltroEstadoDistribucion()
    {
        $this->cargarComprasActivas();
    }

    public function abrirModalDistribuir($compraId, $productoId)
    {
        try {
            // Buscar los datos del producto en las compras activas
            $productoCompra = collect($this->comprasActivas)->first(function($item) use ($compraId, $productoId) {
                return $item['compra_id'] == $compraId && $item['producto_id'] == $productoId;
            });
            
            if ($productoCompra) {
                $this->productoParaDistribuir = [
                    'compra_id' => $productoCompra['compra_id'],
                    'producto_id' => $productoCompra['producto_id'],
                    'detalle_id' => $productoCompra['detalle_id'],
                    'numero_factura' => $productoCompra['numero_factura'],
                    'nombre' => $productoCompra['producto_nombre'], // Usar producto_nombre para nombre
                    'cantidad_pendiente' => $productoCompra['cantidad_pendiente'],
                    'unidad' => $productoCompra['unidad'],
                    'proveedor' => $productoCompra['proveedor']
                ];
            } else {
                // Si no se encuentra en la lista actual, buscar en la base de datos
                $detalle = CompraHasProducto::with(['compra.cliente', 'producto', 'unidadCompra'])
                    ->where('compra_id', $compraId)
                    ->where('producto_id', $productoId)
                    ->first();
                
                if ($detalle) {
                    $this->productoParaDistribuir = [
                        'compra_id' => $detalle->compra_id,
                        'producto_id' => $detalle->producto_id,
                        'detalle_id' => $detalle->id,
                        'numero_factura' => $detalle->compra->numero_factura,
                        'nombre' => $detalle->producto->nombre, // Cambiar producto_nombre por nombre
                        'cantidad_pendiente' => $detalle->cantidad_sin_asignar,
                        'unidad' => $detalle->unidadCompra->nombre ?? 'Unidad',
                        'proveedor' => $detalle->compra->cliente->nombre ?? 'Sin proveedor'
                    ];
                } else {
                    $this->mostrarError('No se encontró el producto en la compra especificada.');
                    return;
                }
            }
            
            // Limpiar campos del modal
            $this->cantidadDistribuir = '';
            $this->fechaDistribucion = date('Y-m-d');
            $this->bodegaDistribucion = '';
            $this->segmentoDistribucion = '';
            $this->seccionDistribucion = '';
            $this->comentarioDistribucion = '';
            
            // Limpiar listas dependientes
            $this->segmentos = [];
            $this->secciones = [];
            
            $this->mostrarModalDistribucion = true;
            
        } catch (\Exception $e) {
            Log::error('Error al abrir modal de distribución', [
                'compra_id' => $compraId,
                'producto_id' => $productoId,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->mostrarError('Error al cargar los datos del producto: ' . $e->getMessage());
        }
    }

    public function cerrarModalDistribucion()
    {
        $this->mostrarModalDistribucion = false;
        $this->productoParaDistribuir = null;
        $this->cantidadDistribuir = '';
        $this->bodegaDistribucion = '';
        $this->segmentoDistribucion = '';
        $this->seccionDistribucion = '';
        $this->comentarioDistribucion = '';
        $this->segmentos = [];
        $this->secciones = [];
    }

    public function updatedBodegaDistribucion()
    {
        if ($this->bodegaDistribucion) {
            try {
                $this->segmentos = Segmento::where('bodega_id', $this->bodegaDistribucion)->get();
                $this->nombreBodegaDistribucion = Bodega::find($this->bodegaDistribucion)->nombre ?? '';
            } catch (\Exception $e) {
                $this->segmentos = [];
            }
        } else {
            $this->segmentos = [];
        }
        $this->segmentoDistribucion = '';
        $this->seccionDistribucion = '';
        $this->secciones = [];
    }

    public function updatedSegmentoDistribucion()
    {
        if ($this->segmentoDistribucion) {
            try {
                $this->secciones = Seccion::where('segmento_id', $this->segmentoDistribucion)->get();
                $this->nombreSegmentoDistribucion = Segmento::find($this->segmentoDistribucion)->descripcion ?? '';
            } catch (\Exception $e) {
                $this->secciones = [];
            }
        } else {
            $this->secciones = [];
        }
        $this->seccionDistribucion = '';
    }

    public function updatedSeccionDistribucion()
    {
        if ($this->seccionDistribucion) {
            try {
                $seccion = Seccion::find($this->seccionDistribucion);
                $this->nombreSeccionDistribucion = $seccion ? $seccion->descripcion : '';
            } catch (\Exception $e) {
                $this->nombreSeccionDistribucion = '';
            }
        } else {
            $this->nombreSeccionDistribucion = '';
        }
    }

    public function puedeConfirmarDistribucion()
    {
        return !empty($this->cantidadDistribuir) && 
               $this->cantidadDistribuir > 0 && 
               !empty($this->fechaDistribucion) && 
               !empty($this->seccionDistribucion);
    }

    public function actualizarDatosProducto()
    {
        if ($this->productoParaDistribuir && isset($this->productoParaDistribuir['detalle_id'])) {
            try {
                $detalle = CompraHasProducto::with(['compra.cliente', 'producto', 'unidadCompra'])
                    ->find($this->productoParaDistribuir['detalle_id']);
                
                if ($detalle) {
                    $this->productoParaDistribuir['cantidad_pendiente'] = $detalle->cantidad_sin_asignar;
                    
                    // Si ya no hay cantidad disponible, cerrar el modal y recargar
                    if ($detalle->cantidad_sin_asignar <= 0) {
                        $this->mostrarError('Este producto ya no tiene cantidad disponible para distribuir.');
                        $this->cerrarModalDistribucion();
                        $this->cargarComprasActivas();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error al actualizar datos del producto', [
                    'detalle_id' => $this->productoParaDistribuir['detalle_id'],
                    'mensaje' => $e->getMessage()
                ]);
            }
        }
    }

    public function confirmarDistribucion()
    {
        // Validaciones
        if (!$this->cantidadDistribuir || !$this->fechaDistribucion || !$this->seccionDistribucion) {
            $this->mostrarError('Todos los campos obligatorios deben estar completos.');
            return;
        }

        if (!is_numeric($this->cantidadDistribuir) || $this->cantidadDistribuir <= 0) {
            $this->mostrarError('La cantidad debe ser un número mayor a cero.');
            return;
        }

        if (!$this->productoParaDistribuir) {
            $this->mostrarError('No hay producto seleccionado para distribuir.');
            return;
        }

        $cantidadDistribuir = floatval($this->cantidadDistribuir);
        $cantidadPendiente = floatval($this->productoParaDistribuir['cantidad_pendiente']);

        if ($cantidadDistribuir > $cantidadPendiente) {
            $this->mostrarError("La cantidad a distribuir ({$cantidadDistribuir}) no puede ser mayor a la cantidad pendiente ({$cantidadPendiente}).");
            return;
        }

        try {
            DB::beginTransaction();

            // Buscar el detalle de compra y verificar estado actual
            $detalleCompra = CompraHasProducto::find($this->productoParaDistribuir['detalle_id']);
            
            if (!$detalleCompra) {
                throw new \Exception('No se encontró el detalle de compra.');
            }

            // Verificar que la cantidad aún esté disponible
            if ($detalleCompra->cantidad_sin_asignar < $cantidadDistribuir) {
                // Si la cantidad cambió, actualizar los datos del modal
                $this->productoParaDistribuir['cantidad_pendiente'] = $detalleCompra->cantidad_sin_asignar;
                
                if ($detalleCompra->cantidad_sin_asignar <= 0) {
                    throw new \Exception('Este producto ya no tiene cantidad disponible para distribuir. La página se actualizará automáticamente.');
                } else {
                    throw new \Exception("La cantidad disponible ha cambiado. Solo quedan {$detalleCompra->cantidad_sin_asignar} unidades disponibles. Por favor, ajuste la cantidad a distribuir.");
                }
            }

            // Crear registro en recibido_bodega
            $recibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->productoParaDistribuir['producto_id'],
                'seccion_id' => $this->seccionDistribucion,
                'cantidad_compra_lote' => $cantidadDistribuir,
                'cantidad_inicial_seccion' => $cantidadDistribuir,
                'cantidad_disponible' => $cantidadDistribuir,
                'fecha_recibido' => $this->fechaDistribucion,
                'fecha_expiracion' => $detalleCompra->fecha_expiracion,
                'comentario' => $this->comentarioDistribucion,
                'unidades_compra' => $cantidadDistribuir,
                'unidad_compra_id' => $detalleCompra->unidad_compra_id,
                'users_registro_id' => Auth::id(),
                'estado_id' => 1 // Estado activo
            ]);

            // Actualizar la cantidad sin asignar en el detalle de compra
            $detalleCompra->cantidad_sin_asignar -= $cantidadDistribuir;
            $detalleCompra->save();

            // Verificar si todos los productos de la compra están completamente distribuidos
            $compra = $detalleCompra->compra;
            $productosConCantidadPendiente = $compra->detallesCompra()
                ->where('cantidad_sin_asignar', '>', 0)
                ->count();
            
            // Log para debugging
            Log::info('Verificando estado de distribución', [
                'compra_id' => $compra->id,
                'numero_factura' => $compra->numero_factura,
                'productos_con_cantidad_pendiente' => $productosConCantidadPendiente,
                'total_productos' => $compra->detallesCompra()->count()
            ]);
            
            // Si no hay productos con cantidad pendiente, cambiar estado a "Distribuido"
            if ($productosConCantidadPendiente == 0) {
                $estadoAnterior = $compra->estado_id;
                $compra->estado_id = 3; // Estado "Distribuido"
                $compra->save();
                
                Log::info('Compra marcada como distribuida', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'nuevo_estado_id' => 3,
                    'estado_anterior' => $estadoAnterior
                ]);
                
                // Emitir eventos globales para notificar a otros componentes
                $this->dispatch('compra-distribuida', $compra->id);
                $this->dispatch('estado-compra-actualizado', $compra->id, 'distribuido');
                $this->dispatch('compra-actualizada', $compra->id);
            } else {
                Log::info('Compra aún tiene productos pendientes', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'productos_pendientes' => $productosConCantidadPendiente
                ]);
            }

            DB::commit();

            // Preparar mensaje de éxito
            $mensaje = "Se distribuyeron {$cantidadDistribuir} {$this->productoParaDistribuir['unidad']} de {$this->productoParaDistribuir['nombre']} exitosamente a la bodega.";
            
            // Si la compra se completó, agregar información adicional
            if ($productosConCantidadPendiente == 0) {
                $mensaje .= " ¡La factura {$compra->numero_factura} ha sido marcada como completamente distribuida!";
            }
            
            $this->mostrarExito($mensaje);
            $this->cerrarModalDistribucion();
            $this->cargarComprasActivas(); // Recargar datos
            
            // Emitir evento global para actualizar cualquier vista que muestre compras
            $this->dispatch('compra-actualizada', $compra->id);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // Si es un error de cantidad, intentar actualizar los datos
            if (str_contains($e->getMessage(), 'cantidad disponible ha cambiado') || 
                str_contains($e->getMessage(), 'ya no tiene cantidad disponible')) {
                $this->actualizarDatosProducto();
                $this->cargarComprasActivas(); // Recargar la tabla
            }
            
            Log::error('Error al distribuir producto', [
                'detalle_compra_id' => $this->productoParaDistribuir['detalle_id'] ?? null,
                'cantidad' => $cantidadDistribuir,
                'seccion_id' => $this->seccionDistribucion,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            $this->mostrarError('Error al distribuir el producto: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.inventario.recibir-en-bodega');
    }
}
