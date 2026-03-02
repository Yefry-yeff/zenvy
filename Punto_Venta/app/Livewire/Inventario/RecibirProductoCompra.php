<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\RecibidoBodega;
use App\Models\UnidadMedida;
use App\Models\Producto;
use App\Models\Bitacora;
use App\Services\WebInventorySyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibirProductoCompra extends Component
{
    public $compraId;
    public $compra;
    public $detallesCompra = [];

    // Propiedades para el modal de distribución
    public $mostrarModalDistribucion = false;
    public $productoSeleccionado = null;
    public $detalleSeleccionado = null;

    // Datos del formulario de distribución
    public $cantidadDistribuir = '';
    public $cantidadAsignarStock = ''; // Cantidad en stock para todas las compras
    public $unidadMedidaProducto = ''; // Unidad de medida de venta del producto (editable)
    public $nombreUnidadMedidaProducto = ''; // Nombre de la unidad seleccionada
    public $fechaDistribucion = '';
    public $bodegaDistribucion = '';
    public $segmentoDistribucion = '';
    public $seccionDistribucion = '';
    public $comentarioDistribucion = '';
    
    // Array para múltiples distribuciones del mismo producto
    public $distribucionesMultiples = [];

    // Datos de ubicación
    public $bodegas = [];
    public $segmentos = [];
    public $secciones = [];
    public $unidadesMedida = []; // Lista de unidades de medida disponibles
    public $nombreBodegaDistribucion = '';
    public $nombreSegmentoDistribucion = '';
    public $nombreSeccionDistribucion = '';

    // Propiedades para modales de mensaje
    public $mostrarModalExito = false;
    public $mensajeModalExito = '';

    // Propiedades para el modal de recepción masiva
    public $mostrarModalRecepcionMasiva = false;
    public $productosRecepcionMasiva = [];
    public $fechaRecepcionMasiva = '';
    public $bodegaRecepcionMasiva = '';
    public $segmentoRecepcionMasiva = '';
    public $seccionRecepcionMasiva = '';
    public $comentarioRecepcionMasiva = '';
    public $nombreBodegaRecepcionMasiva = '';
    public $nombreSegmentoRecepcionMasiva = '';
    public $nombreSeccionRecepcionMasiva = '';
    public $segmentosMasiva = [];
    public $seccionesMasiva = [];
    public $mostrarModalError = false;
    public $mensajeModalError = '';

    public function mount($compraId)
    {
        $this->compraId = $compraId;
        $this->cargarDatosCompra();
        $this->cargarBodegas();
        $this->cargarUnidadesMedida();
        $this->fechaDistribucion = now()->format('Y-m-d');
    }

    public function cargarDatosCompra()
    {
        try {
            $this->compra = Compra::with(['proveedor', 'estado'])->findOrFail($this->compraId);

            // Cargar detalles de la compra con productos y relaciones necesarias
            $this->detallesCompra = CompraHasProducto::with([
                'producto.marca',
                'producto.unidadMedidaCompra',
                'producto.unidadMedidaVenta',
                'unidadMedida'
            ])
            ->where('compra_id', $this->compraId)
            ->get()
            ->map(function($detalle) {
                // Priorizar siempre la unidad de medida de venta del producto
                $unidadMedidaVenta = null;
                $unidadMedidaVentaId = null;

                if ($detalle->producto && $detalle->producto->unidadMedidaVenta) {
                    $unidadMedidaVenta = $detalle->producto->unidadMedidaVenta->nombre;
                    $unidadMedidaVentaId = $detalle->producto->unidad_medida_venta_id;
                } elseif ($detalle->producto && $detalle->producto->unidad_medida_venta_id) {
                    // Si tiene ID pero no se cargó la relación, intentar obtenerla
                    $unidadMedidaVentaId = $detalle->producto->unidad_medida_venta_id;
                    $unidadMedidaVenta = $detalle->producto->unidadMedidaVenta->nombre ?? 'N/A';
                }

                return [
                    'id' => $detalle->id,
                    'codigo_producto' => $detalle->producto->id ?? 'N/A',
                    'nombre_producto' => $detalle->producto->nombre ?? 'N/A',
                    'marca' => $detalle->producto->marca->nombre ?? 'Sin marca',
                    'unidad_medida' => $detalle->unidadMedida->nombre ?? 'N/A',
                    'unidad_medida_venta' => $unidadMedidaVenta ?? 'N/A',
                    'unidad_medida_venta_id' => $unidadMedidaVentaId,
                    'precio_unitario' => $detalle->precio,
                    'cantidad_comprada' => $detalle->cantidad_ingresada,
                    'cantidad_sin_asignar' => $detalle->cantidad_sin_asignar,
                    'subtotal' => $detalle->sub_total_producto,
                    'isv' => $detalle->isv,
                    'total' => $detalle->precio_total,
                    'fecha_vencimiento' => $detalle->fecha_expiracion,
                    'producto_id' => $detalle->producto_id,
                    'unidad_medida_id' => $detalle->unidad_medida_id
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error al cargar datos de compra', [
                'compra_id' => $this->compraId,
                'error' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la compra: ' . $e->getMessage());
        }
    }

    public function cargarBodegas()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning('Usuario no autenticado intentando cargar bodegas');
                $this->bodegas = [];
                return;
            }

            $query = Bodega::with('tienda')
                ->where('estado_id', 1);

            // Si el usuario no es Admin, solo mostrar bodegas de su tienda
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                if (!$user->tienda_id) {
                    Log::warning('Usuario sin tienda asignada intentando cargar bodegas', [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                    $this->bodegas = [];
                    return;
                }
                $query->where('tienda_id', $user->tienda_id);
            }

            $this->bodegas = $query->orderBy('nombre')->get();

            Log::info('Bodegas cargadas exitosamente', [
                'user_id' => $user->id,
                'user_role' => $user->rol->txt_nombre ?? 'Sin rol',
                'user_tienda_id' => $user->tienda_id,
                'bodegas_count' => count($this->bodegas)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar bodegas', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->rol->txt_nombre ?? 'Sin rol'
            ]);
            $this->bodegas = [];
        }
    }

    public function cargarUnidadesMedida()
    {
        try {
            // Cargar todas las unidades de medida como colección de Eloquent (para inicialización general)
            $this->unidadesMedida = UnidadMedida::orderBy('nombre', 'asc')->get();

        } catch (\Exception $e) {
            Log::error('Error al cargar unidades de medida', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->unidadesMedida = collect([]);
        }
    }

    public function cargarUnidadesMedidaProducto($productoId)
    {
        try {
            // Cargar todas las presentaciones (registros de precio_has_venta) del producto
            // Cada presentación puede tener diferente código de barras, unidad, descripción
            $this->unidadesMedida = DB::table('precio_has_venta as phv')
                ->join('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                ->where('phv.producto_id', $productoId)
                ->select(
                    'phv.id as precio_venta_id',
                    'phv.unidad_medida_id',
                    'phv.codigo_barra',
                    'phv.descripcion',
                    'phv.cantidad',
                    'phv.precio',
                    'um.nombre as unidad_nombre',
                    'um.simbolo as unidad_simbolo'
                )
                ->orderBy('um.nombre', 'asc')
                ->orderBy('phv.codigo_barra', 'asc')
                ->get()
                ->map(function($presentacion) {
                    return (object)[
                        'id' => $presentacion->unidad_medida_id,
                        'precio_venta_id' => $presentacion->precio_venta_id,
                        'nombre' => $presentacion->unidad_nombre,
                        'simbolo' => $presentacion->unidad_simbolo,
                        'codigo_barra' => $presentacion->codigo_barra ?? '',
                        'descripcion_precio' => $presentacion->descripcion ?? '',
                        'cantidad' => $presentacion->cantidad,
                        'precio' => $presentacion->precio
                    ];
                });

            Log::info('Unidades de medida cargadas para producto', [
                'producto_id' => $productoId,
                'unidades_count' => collect($this->unidadesMedida)->count(),
                'unidades' => collect($this->unidadesMedida)->pluck('nombre', 'id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar unidades de medida del producto', [
                'producto_id' => $productoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->unidadesMedida = collect([]);
        }
    }

    public function updatedUnidadMedidaProducto()
    {
        if ($this->unidadMedidaProducto) {
            // Asegurar que unidadesMedida sea una colección
            $unidades = collect($this->unidadesMedida);
            $unidad = $unidades->firstWhere('id', $this->unidadMedidaProducto);
            $this->nombreUnidadMedidaProducto = $unidad ? $unidad->nombre : '';
        } else {
            $this->nombreUnidadMedidaProducto = '';
        }
    }

    public function updatedBodegaDistribucion()
    {
        $this->segmentoDistribucion = '';
        $this->seccionDistribucion = '';
        $this->nombreSegmentoDistribucion = '';
        $this->nombreSeccionDistribucion = '';

        if ($this->bodegaDistribucion) {
            $bodega = collect($this->bodegas)->firstWhere('id', $this->bodegaDistribucion);
            $this->nombreBodegaDistribucion = $bodega ? $bodega->nombre : '';

            try {
                $this->segmentos = Segmento::where('bodega_id', $this->bodegaDistribucion)
                    ->orderBy('descripcion')
                    ->get();

                Log::info('Segmentos cargados para bodega', [
                    'bodega_id' => $this->bodegaDistribucion,
                    'bodega_nombre' => $this->nombreBodegaDistribucion,
                    'segmentos_count' => count($this->segmentos)
                ]);

            } catch (\Exception $e) {
                Log::error('Error al cargar segmentos', [
                    'bodega_id' => $this->bodegaDistribucion,
                    'error' => $e->getMessage()
                ]);
                $this->segmentos = [];
            }
        } else {
            $this->segmentos = [];
            $this->nombreBodegaDistribucion = '';
        }
        $this->secciones = [];
    }

    public function updatedSegmentoDistribucion()
    {
        $this->seccionDistribucion = '';
        $this->nombreSeccionDistribucion = '';

        if ($this->segmentoDistribucion) {
            $segmento = collect($this->segmentos)->firstWhere('id', $this->segmentoDistribucion);
            $this->nombreSegmentoDistribucion = $segmento ? $segmento->descripcion : '';

            try {
                $this->secciones = Seccion::where('segmento_id', $this->segmentoDistribucion)
                    ->where('estado_id', 1) // Cambiado de 'estado' a 'estado_id'
                    ->orderBy('descripcion')
                    ->get();

                Log::info('Secciones cargadas para segmento', [
                    'segmento_id' => $this->segmentoDistribucion,
                    'segmento_nombre' => $this->nombreSegmentoDistribucion,
                    'secciones_count' => count($this->secciones)
                ]);

            } catch (\Exception $e) {
                Log::error('Error al cargar secciones', [
                    'segmento_id' => $this->segmentoDistribucion,
                    'error' => $e->getMessage()
                ]);
                $this->secciones = [];
            }
        } else {
            $this->secciones = [];
            $this->nombreSegmentoDistribucion = '';
        }
    }

    public function updatedSeccionDistribucion()
    {
        if ($this->seccionDistribucion) {
            $seccion = collect($this->secciones)->firstWhere('id', $this->seccionDistribucion);
            $this->nombreSeccionDistribucion = $seccion ? $seccion->descripcion : '';
        } else {
            $this->nombreSeccionDistribucion = '';
        }
    }

    public function abrirModalDistribuir($detalleId)
    {
        try {
            $detalle = collect($this->detallesCompra)->firstWhere('id', $detalleId);

            if (!$detalle) {
                $this->mostrarError('No se encontró el detalle del producto.');
                return;
            }

            if ($detalle['cantidad_sin_asignar'] <= 0) {
                $this->mostrarError('Este producto ya no tiene cantidad disponible para distribuir.');
                return;
            }

            $this->detalleSeleccionado = $detalle;
            $this->cantidadDistribuir = '';
            $this->cantidadAsignarStock = '';
            $this->unidadMedidaProducto = '';
            $this->nombreUnidadMedidaProducto = '';
            $this->bodegaDistribucion = '';
            $this->segmentoDistribucion = '';
            $this->seccionDistribucion = '';
            $this->comentarioDistribucion = '';
            
            // Inicializar array de distribuciones múltiples vacío
            $this->distribucionesMultiples = [];

            // Cargar las unidades de medida específicas de este producto desde precio_has_venta
            $this->cargarUnidadesMedidaProducto($detalle['producto_id']);

            // Recargar bodegas para asegurar datos actualizados
            $this->cargarBodegas();

            $this->mostrarModalDistribucion = true;

        } catch (\Exception $e) {
            Log::error('Error al abrir modal de distribución', [
                'detalle_id' => $detalleId,
                'error' => $e->getMessage()
            ]);
            $this->mostrarError('Error al abrir el modal de distribución.');
        }
    }

    public function cerrarModalDistribucion()
    {
        $this->mostrarModalDistribucion = false;
        $this->detalleSeleccionado = null;
        $this->cantidadDistribuir = '';
        $this->cantidadAsignarStock = '';
        $this->unidadMedidaProducto = '';
        $this->nombreUnidadMedidaProducto = '';
        $this->bodegaDistribucion = '';
        $this->segmentoDistribucion = '';
        $this->seccionDistribucion = '';
        $this->comentarioDistribucion = '';
        $this->distribucionesMultiples = [];
        $this->segmentos = [];
        $this->secciones = [];
    }
    
    public function agregarDistribucion()
    {
        // Validar campos obligatorios
        if (!$this->cantidadAsignarStock || !$this->unidadMedidaProducto || 
            !$this->bodegaDistribucion || !$this->segmentoDistribucion || !$this->seccionDistribucion) {
            $this->mostrarError('Complete todos los campos antes de agregar la distribución.');
            return;
        }
        
        if (!is_numeric($this->cantidadAsignarStock) || $this->cantidadAsignarStock <= 0) {
            $this->mostrarError('La cantidad en stock debe ser mayor a cero.');
            return;
        }
        
        // Obtener datos de la unidad seleccionada
        $unidadSeleccionada = collect($this->unidadesMedida)->firstWhere('precio_venta_id', $this->unidadMedidaProducto);
        
        if (!$unidadSeleccionada) {
            $this->mostrarError('No se encontró la presentación seleccionada.');
            return;
        }
        
        // Agregar distribución al array
        $this->distribucionesMultiples[] = [
            'cantidad_stock' => $this->cantidadAsignarStock,
            'unidad_medida_id' => $unidadSeleccionada->id,
            'precio_venta_id' => $unidadSeleccionada->precio_venta_id,
            'unidad_nombre' => $unidadSeleccionada->nombre ?? '',
            'unidad_simbolo' => $unidadSeleccionada->simbolo ?? '',
            'codigo_barra' => $unidadSeleccionada->codigo_barra ?? '',
            'descripcion' => $unidadSeleccionada->descripcion_precio ?? '',
            'cantidad' => $unidadSeleccionada->cantidad ?? 1,
            'precio' => $unidadSeleccionada->precio ?? 0,
            'bodega_id' => $this->bodegaDistribucion,
            'bodega_nombre' => $this->nombreBodegaDistribucion,
            'segmento_id' => $this->segmentoDistribucion,
            'segmento_nombre' => $this->nombreSegmentoDistribucion,
            'seccion_id' => $this->seccionDistribucion,
            'seccion_nombre' => $this->nombreSeccionDistribucion,
        ];
        
        // Limpiar campos del formulario
        $this->cantidadAsignarStock = '';
        $this->unidadMedidaProducto = '';
        $this->nombreUnidadMedidaProducto = '';
        
        session()->flash('success', 'Distribución agregada correctamente.');
    }
    
    public function eliminarDistribucion($index)
    {
        if (isset($this->distribucionesMultiples[$index])) {
            unset($this->distribucionesMultiples[$index]);
            $this->distribucionesMultiples = array_values($this->distribucionesMultiples);
            session()->flash('success', 'Distribución eliminada.');
        }
    }
    
    public function calcularTotalDistribuido()
    {
        // El total no se calcula aquí porque cada unidad puede tener distinta conversión
        // Retornamos el conteo de distribuciones
        return count($this->distribucionesMultiples);
    }
    
    public function calcularCantidadRestante()
    {
        if (!$this->detalleSeleccionado) {
            return 0;
        }
        
        // La cantidad restante es la cantidad sin asignar de la compra
        // porque aún no se ha confirmado la distribución
        return $this->detalleSeleccionado['cantidad_sin_asignar'];
    }

    public function puedeConfirmarDistribucion()
    {
        return count($this->distribucionesMultiples) > 0 &&
               $this->fechaDistribucion &&
               $this->detalleSeleccionado;
    }

    public function confirmarDistribucion()
    {
        // Validar que haya distribuciones agregadas
        if (empty($this->distribucionesMultiples)) {
            $this->mostrarError('Debe agregar al menos una distribución antes de confirmar.');
            return;
        }

        if (!$this->fechaDistribucion) {
            $this->mostrarError('Debe seleccionar una fecha de distribución.');
            return;
        }

        if (!$this->detalleSeleccionado) {
            $this->mostrarError('No hay producto seleccionado para distribuir.');
            return;
        }

        try {
            DB::beginTransaction();

            // Buscar el detalle de compra
            $detalleCompra = CompraHasProducto::find($this->detalleSeleccionado['id']);

            if (!$detalleCompra) {
                throw new \Exception('No se encontró el detalle de compra.');
            }

            // La cantidad total a distribuir es toda la cantidad sin asignar
            $cantidadTotalDistribuir = $detalleCompra->cantidad_sin_asignar;

            // Verificar que aún esté disponible
            if ($cantidadTotalDistribuir <= 0) {
                throw new \Exception("No hay cantidad disponible para distribuir.");
            }

            // Procesar cada distribución
            foreach ($this->distribucionesMultiples as $distribucion) {
                $cantidadParaStock = floatval($distribucion['cantidad_stock']);

                // Crear registro en recibido_bodega
                $recibidoBodega = RecibidoBodega::create([
                    'producto_id' => $this->detalleSeleccionado['producto_id'],
                    'seccion_id' => $distribucion['seccion_id'],
                    'cantidad_compra_lote' => $cantidadTotalDistribuir, // Cantidad de la compra
                    'cantidad_inicial_seccion' => $cantidadParaStock, // Cantidad en stock (unidades)
                    'cantidad_disponible' => $cantidadParaStock,
                    'fecha_recibido' => $this->fechaDistribucion,
                    'fecha_expiracion' => $detalleCompra->fecha_expiracion,
                    'comentario' => $this->comentarioDistribucion,
                    'unidades_compra' => $cantidadTotalDistribuir,
                    'unidad_medida_id' => $distribucion['unidad_medida_id'],
                    'precio_venta_id' => $distribucion['precio_venta_id'], // ID específico de precio_has_venta
                    'users_registro_id' => Auth::id(),
                    'estado_id' => 1
                ]);

                // Registrar en bitácora
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Recepción Múltiple',
                    'Crear recibido_bodega',
                    "Producto '{$this->detalleSeleccionado['nombre_producto']}' distribuido. Compra: {$cantidadTotalDistribuir} {$this->detalleSeleccionado['unidad_medida']}, Stock: {$cantidadParaStock} {$distribucion['unidad_nombre']}, Código: {$distribucion['codigo_barra']}, Sección: {$distribucion['seccion_nombre']}",
                    $recibidoBodega->id,
                    'recibido_bodega',
                    null,
                    [
                        'producto_id' => $this->detalleSeleccionado['producto_id'],
                        'seccion_id' => $distribucion['seccion_id'],
                        'cantidad_compra_lote' => $cantidadTotalDistribuir,
                        'cantidad_inicial_seccion' => $cantidadParaStock,
                        'unidad_medida_id' => $distribucion['unidad_medida_id'],
                        'codigo_barra' => $distribucion['codigo_barra']
                    ]
                );

                Log::info('Distribución individual procesada', [
                    'recibido_bodega_id' => $recibidoBodega->id,
                    'cantidad_compra' => $cantidadTotalDistribuir,
                    'cantidad_stock' => $cantidadParaStock,
                    'unidad_medida_id' => $distribucion['unidad_medida_id'],
                    'codigo_barra' => $distribucion['codigo_barra']
                ]);
            }

            // Guardar cantidad anterior para bitácora
            $cantidadSinAsignarAnterior = $detalleCompra->cantidad_sin_asignar;

            // Actualizar la cantidad sin asignar en el detalle de compra (ahora queda en 0)
            $detalleCompra->cantidad_sin_asignar = 0;
            $detalleCompra->save();

            // Registrar en bitácora: Actualización de compra_has_producto
            Bitacora::registrar(
                Auth::id(),
                'Inventario - Recepción Múltiple',
                'Actualizar cantidad_sin_asignar',
                "Cantidad sin asignar del producto '{$this->detalleSeleccionado['nombre_producto']}' actualizada de {$cantidadSinAsignarAnterior} a 0. Distribuido en " . count($this->distribucionesMultiples) . " presentaciones.",
                $detalleCompra->id,
                'compra_has_producto',
                ['cantidad_sin_asignar' => $cantidadSinAsignarAnterior],
                ['cantidad_sin_asignar' => 0]
            );

            // Verificar si todos los productos de la compra están completamente distribuidos
            $compra = $detalleCompra->compra;
            $productosConCantidadPendiente = $compra->detallesCompra()
                ->where('cantidad_sin_asignar', '>', 0)
                ->count();

            // Verificar si hay productos con distribución parcial (cantidad original > cantidad sin asignar > 0)
            $productosConDistribucionParcial = $compra->detallesCompra()
                ->whereRaw('cantidad_sin_asignar > 0 AND cantidad_sin_asignar < cantidad_ingresada')
                ->count();

            // Actualizar estado según la distribución
            // Lógica de estados:
            // - Activo (1): Ningún producto distribuido
            // - Pendiente (5): Al menos un producto distribuido pero quedan productos sin completar
            // - Distribuido (3): Todos los productos completamente distribuidos
            // - Anulado: No se modifica desde aquí

            if ($productosConCantidadPendiente == 0) {
                // Caso 1: Todos los productos están completamente distribuidos
                $estadoAnterior = $compra->estado_id;
                $compra->estado_id = 3; // Estado "Distribuido"
                $compra->save();

                // Registrar en bitácora: Cambio de estado a Distribuido
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Recepción Individual',
                    'Cambio estado compra',
                    "Compra '{$compra->numero_factura}' cambió de estado {$estadoAnterior} a 3 (Distribuido). Todos los productos han sido distribuidos.",
                    $compra->id,
                    'compra',
                    ['estado_id' => $estadoAnterior],
                    ['estado_id' => 3]
                );

                Log::info('Compra marcada como distribuida', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado_id' => 3
                ]);

                // Emitir eventos para notificar a otros componentes
                $this->dispatch('compra-distribuida', $compra->id);
                $this->dispatch('estado-compra-actualizado', $compra->id, 'distribuido');
                $this->dispatch('compra-actualizada', $compra->id);

            } elseif ($productosConCantidadPendiente > 0) {
                // Caso 2: Hay productos con cantidad pendiente
                Log::info('Verificando cambio de estado a pendiente', [
                    'compra_id' => $compra->id,
                    'estado_actual' => $compra->estado_id,
                    'productos_con_cantidad_pendiente' => $productosConCantidadPendiente,
                    'puede_cambiar' => in_array($compra->estado_id, [1, 5]) ? 'SI' : 'NO'
                ]);

                // Cambiar a Pendiente solo si está en Activo (1) o ya está en Pendiente (5)
                // No cambiar si está en Distribuido (3) o Anulado
                if (in_array($compra->estado_id, [1, 5])) {
                    $estadoAnterior = $compra->estado_id;
                    $compra->estado_id = 5; // Estado "Pendiente"
                    $compra->save();

                    // Registrar en bitácora: Cambio de estado a Pendiente
                    Bitacora::registrar(
                        Auth::id(),
                        'Inventario - Recepción Individual',
                        'Cambio estado compra',
                        "Compra '{$compra->numero_factura}' cambió de estado {$estadoAnterior} a 5 (Pendiente). Quedan {$productosConCantidadPendiente} productos por distribuir.",
                        $compra->id,
                        'compra',
                        ['estado_id' => $estadoAnterior],
                        ['estado_id' => 5]
                    );

                    Log::info('Compra marcada como pendiente', [
                        'compra_id' => $compra->id,
                        'numero_factura' => $compra->numero_factura,
                        'estado_anterior' => $estadoAnterior,
                        'productos_con_cantidad_pendiente' => $productosConCantidadPendiente,
                        'productos_con_distribucion_parcial' => $productosConDistribucionParcial,
                        'nuevo_estado_id' => 5,
                        'guardado' => 'SI'
                    ]);

                    // Emitir eventos para notificar a otros componentes
                    $this->dispatch('estado-compra-actualizado', $compra->id, 'pendiente');
                    $this->dispatch('compra-actualizada', $compra->id);
                } else {
                    Log::warning('Estado no cambiado a pendiente', [
                        'compra_id' => $compra->id,
                        'estado_actual' => $compra->estado_id,
                        'razon' => 'Estado no es Activo (1) ni Pendiente (5)'
                    ]);
                }
            }

            DB::commit();

            // Sincronizar ingreso de compra con página web
            try {
                $syncService = app(WebInventorySyncService::class);
                // Enviar webhook por cada distribución individual
                foreach ($this->distribucionesMultiples as $distribucion) {
                    $syncService->sincronizarCompraRecibida(
                        $this->detalleSeleccionado['producto_id'],
                        $this->detalleSeleccionado['nombre_producto'],
                        (int) $distribucion['cantidad_stock'],
                        [
                            'fecha_recibido' => $this->fechaDistribucion,
                            'compra_id' => $compra->id,
                            'numero_factura' => $compra->numero_factura,
                            'seccion_id' => $distribucion['seccion_id'],
                            'comentario' => $this->comentarioDistribucion,
                            'tipo_recepcion' => 'multiple',
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Error al enviar webhook de compra recibida (distribución múltiple)', [
                    'producto_id' => $this->detalleSeleccionado['producto_id'],
                    'error' => $e->getMessage()
                ]);
            }

            // Preparar mensaje de éxito detallado
            $mensaje = "✅ Distribución múltiple exitosa:\n\n";
            $mensaje .= "📦 Producto: {$this->detalleSeleccionado['nombre_producto']}\n";
            $mensaje .= "🔢 Cantidad comprada: {$cantidadTotalDistribuir} {$this->detalleSeleccionado['unidad_medida']}\n";
            $mensaje .= "📋 Distribuido en " . count($this->distribucionesMultiples) . " presentaciones:\n\n";
            
            foreach ($this->distribucionesMultiples as $index => $dist) {
                $mensaje .= ($index + 1) . ". {$dist['unidad_nombre']} ({$dist['unidad_simbolo']})";
                if (!empty($dist['codigo_barra'])) {
                    $mensaje .= " - Código: {$dist['codigo_barra']}";
                }
                if (!empty($dist['descripcion'])) {
                    $mensaje .= " - {$dist['descripcion']}";
                }
                $mensaje .= "\n   Stock: {$dist['cantidad_stock']} unidades";
                $mensaje .= "\n   Ubicación: {$dist['bodega_nombre']} > {$dist['segmento_nombre']} > {$dist['seccion_nombre']}\n\n";
            }

            // Si la compra se completó, agregar información adicional
            if ($productosConCantidadPendiente == 0) {
                $compra = $detalleCompra->compra;
                $mensaje .= "🎉 ¡La factura {$compra->numero_factura} ha sido completamente distribuida!";
            }

            $this->mostrarExito($mensaje);
            $this->cerrarModalDistribucion();
            $this->cargarDatosCompra(); // Recargar datos para actualizar cantidades

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error al distribuir producto', [
                'detalle_compra_id' => $this->detalleSeleccionado['id'] ?? null,
                'distribuciones' => $this->distribucionesMultiples,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al distribuir el producto: ' . $e->getMessage());
        }
    }

    public function abrirModalRecepcionMasiva()
    {
        try {
            // Preparar productos con cantidad pendiente
            $this->productosRecepcionMasiva = [];

            foreach ($this->detallesCompra as $detalle) {
                if ($detalle['cantidad_sin_asignar'] > 0) {
                    // Cargar todas las presentaciones del producto con sus códigos de barras
                    $unidadesProducto = DB::table('precio_has_venta as phv')
                        ->join('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                        ->where('phv.producto_id', $detalle['producto_id'])
                        ->select(
                            'phv.id as precio_venta_id',
                            'phv.unidad_medida_id as id',
                            'phv.codigo_barra',
                            'phv.descripcion',
                            'phv.cantidad',
                            'phv.precio',
                            'um.nombre',
                            'um.simbolo'
                        )
                        ->orderBy('um.nombre', 'asc')
                        ->orderBy('phv.codigo_barra', 'asc')
                        ->get()
                        ->map(function($presentacion) {
                            return [
                                'id' => $presentacion->id,
                                'precio_venta_id' => $presentacion->precio_venta_id,
                                'nombre' => $presentacion->nombre,
                                'simbolo' => $presentacion->simbolo,
                                'codigo_barra' => $presentacion->codigo_barra ?? '',
                                'descripcion' => $presentacion->descripcion ?? '',
                                'cantidad' => $presentacion->cantidad,
                                'precio' => $presentacion->precio
                            ];
                        });

                    // Si no tiene unidades en price_has_venta, mantener el array vacío
                    // Esto asegura que el comportamiento sea consistente con el modal individual

                    // Obtener el precio_venta_id por defecto (primera unidad disponible)
                    $precioVentaIdDefault = $unidadesProducto->isNotEmpty() && isset($unidadesProducto[0]['precio_venta_id']) 
                        ? $unidadesProducto[0]['precio_venta_id'] 
                        : null;

                    $this->productosRecepcionMasiva[] = [
                        'id' => $detalle['id'],
                        'producto_id' => $detalle['producto_id'],
                        'nombre_producto' => $detalle['nombre_producto'],
                        'cantidad_pendiente' => $detalle['cantidad_sin_asignar'],
                        'cantidad_distribuir' => $detalle['cantidad_sin_asignar'], // Por defecto toda la cantidad
                        'unidad_medida_compra' => $detalle['unidad_medida'],
                        'unidad_medida_id' => $detalle['unidad_medida_id'],
                        'cantidad_stock' => $detalle['cantidad_sin_asignar'], // Por defecto la misma cantidad
                        'unidades_disponibles' => $unidadesProducto->toArray(),
                        'fecha_expiracion' => $detalle['fecha_vencimiento'] ?? null,
                        'precio_venta_id' => $precioVentaIdDefault, // ID de precio_has_venta
                        // Campos de distribución por producto
                        'bodega_id' => '', // Bodega seleccionada para este producto
                        'segmento_id' => '', // Segmento seleccionado para este producto
                        'seccion_id' => '', // Sección seleccionada para este producto
                        'segmentos_disponibles' => [], // Segmentos cargados dinámicamente
                        'secciones_disponibles' => [] // Secciones cargadas dinámicamente
                    ];
                }
            }

            // Configurar valores por defecto
            $this->fechaRecepcionMasiva = now()->format('Y-m-d');

            // Buscar bodega Paperland por defecto
            $bodegaPaperland = Bodega::where('nombre', 'like', '%paperland%')
                                    ->orWhere('nombre', 'like', '%paper land%')
                                    ->first();

            if ($bodegaPaperland) {
                $this->bodegaRecepcionMasiva = $bodegaPaperland->id;
                $this->nombreBodegaRecepcionMasiva = $bodegaPaperland->nombre;
                $this->cargarSegmentosMasiva();
            }

            $this->mostrarModalRecepcionMasiva = true;

        } catch (\Exception $e) {
            Log::error('Error al abrir modal recepción masiva', [
                'error' => $e->getMessage(),
                'compra_id' => $this->compraId
            ]);
            $this->mostrarError('Error al preparar la recepción masiva: ' . $e->getMessage());
        }
    }

    public function cerrarModalRecepcionMasiva()
    {
        $this->mostrarModalRecepcionMasiva = false;
        $this->productosRecepcionMasiva = [];
        $this->fechaRecepcionMasiva = '';
        $this->bodegaRecepcionMasiva = '';
        $this->segmentoRecepcionMasiva = '';
        $this->seccionRecepcionMasiva = '';
        $this->comentarioRecepcionMasiva = '';
        $this->nombreBodegaRecepcionMasiva = '';
        $this->nombreSegmentoRecepcionMasiva = '';
        $this->nombreSeccionRecepcionMasiva = '';
        $this->segmentosMasiva = [];
        $this->seccionesMasiva = [];
    }

    public function cargarSegmentosMasiva()
    {
        if ($this->bodegaRecepcionMasiva) {
            $this->segmentosMasiva = Segmento::where('bodega_id', $this->bodegaRecepcionMasiva)
                                            ->orderBy('descripcion', 'asc')
                                            ->get();

            $bodega = Bodega::find($this->bodegaRecepcionMasiva);
            $this->nombreBodegaRecepcionMasiva = $bodega ? $bodega->nombre : '';

            // Limpiar selecciones dependientes
            $this->segmentoRecepcionMasiva = '';
            $this->seccionRecepcionMasiva = '';
            $this->nombreSegmentoRecepcionMasiva = '';
            $this->nombreSeccionRecepcionMasiva = '';
            $this->seccionesMasiva = [];
        }
    }

    public function cargarSeccionesMasiva()
    {
        if ($this->segmentoRecepcionMasiva) {
            $this->seccionesMasiva = Seccion::where('segmento_id', $this->segmentoRecepcionMasiva)
                                           ->where('estado_id', 1)
                                           ->orderBy('nombre', 'asc')
                                           ->get();

            $segmento = Segmento::find($this->segmentoRecepcionMasiva);
            $this->nombreSegmentoRecepcionMasiva = $segmento ? $segmento->nombre : '';

            // Limpiar selección de sección
            $this->seccionRecepcionMasiva = '';
            $this->nombreSeccionRecepcionMasiva = '';
        }
    }

    public function updatedBodegaRecepcionMasiva()
    {
        $this->cargarSegmentosMasiva();
    }

    public function updatedSegmentoRecepcionMasiva()
    {
        $this->cargarSeccionesMasiva();
    }

    public function updatedSeccionRecepcionMasiva()
    {
        if ($this->seccionRecepcionMasiva) {
            $seccion = Seccion::find($this->seccionRecepcionMasiva);
            $this->nombreSeccionRecepcionMasiva = $seccion ? $seccion->nombre : '';
        }
    }

    // Métodos para manejar cambios por producto individual
    public function cambiarBodegaProducto($productoIndex, $bodegaId)
    {
        if (isset($this->productosRecepcionMasiva[$productoIndex])) {
            $this->productosRecepcionMasiva[$productoIndex]['bodega_id'] = $bodegaId;
            $this->productosRecepcionMasiva[$productoIndex]['segmento_id'] = '';
            $this->productosRecepcionMasiva[$productoIndex]['seccion_id'] = '';

            // Cargar segmentos para la bodega seleccionada
            if ($bodegaId) {
                $segmentos = Segmento::where('bodega_id', $bodegaId)
                                   ->orderBy('descripcion', 'asc')
                                   ->get();
                $this->productosRecepcionMasiva[$productoIndex]['segmentos_disponibles'] = $segmentos->toArray();
            } else {
                $this->productosRecepcionMasiva[$productoIndex]['segmentos_disponibles'] = [];
            }

            $this->productosRecepcionMasiva[$productoIndex]['secciones_disponibles'] = [];
        }
    }

    public function cambiarSegmentoProducto($productoIndex, $segmentoId)
    {
        if (isset($this->productosRecepcionMasiva[$productoIndex])) {
            $this->productosRecepcionMasiva[$productoIndex]['segmento_id'] = $segmentoId;
            $this->productosRecepcionMasiva[$productoIndex]['seccion_id'] = '';

            // Cargar secciones para el segmento seleccionado
            if ($segmentoId) {
                $secciones = Seccion::where('segmento_id', $segmentoId)
                                   ->where('estado_id', 1)
                                   ->orderBy('descripcion', 'asc')
                                   ->get();
                $this->productosRecepcionMasiva[$productoIndex]['secciones_disponibles'] = $secciones->toArray();
            } else {
                $this->productosRecepcionMasiva[$productoIndex]['secciones_disponibles'] = [];
            }
        }
    }

    public function cambiarSeccionProducto($productoIndex, $seccionId)
    {
        if (isset($this->productosRecepcionMasiva[$productoIndex])) {
            $this->productosRecepcionMasiva[$productoIndex]['seccion_id'] = $seccionId;
        }
    }

    public function cambiarUnidadProducto($productoIndex, $unidadMedidaId)
    {
        if (isset($this->productosRecepcionMasiva[$productoIndex])) {
            $producto = $this->productosRecepcionMasiva[$productoIndex];
            
            // Buscar la presentación correspondiente en las unidades disponibles
            $unidadSeleccionada = collect($producto['unidades_disponibles'])
                ->firstWhere('id', $unidadMedidaId);
            
            if ($unidadSeleccionada) {
                // Actualizar el precio_venta_id correspondiente
                $this->productosRecepcionMasiva[$productoIndex]['precio_venta_id'] = $unidadSeleccionada['precio_venta_id'];
                
                Log::info('Unidad de medida actualizada para recepción masiva', [
                    'producto_index' => $productoIndex,
                    'producto_id' => $producto['producto_id'],
                    'unidad_medida_id' => $unidadMedidaId,
                    'precio_venta_id' => $unidadSeleccionada['precio_venta_id']
                ]);
            }
        }
    }

    public function confirmarRecepcionMasiva()
    {
        // Validaciones
        if (empty($this->productosRecepcionMasiva)) {
            $this->mostrarError('No hay productos para recibir.');
            return;
        }

        if (!$this->fechaRecepcionMasiva) {
            $this->mostrarError('La fecha de recepción es obligatoria.');
            return;
        }

        // Validar que todos los productos tengan cantidades válidas y distribución completa
        foreach ($this->productosRecepcionMasiva as $producto) {
            if (!$producto['cantidad_distribuir'] || $producto['cantidad_distribuir'] <= 0) {
                $this->mostrarError("La cantidad a distribuir para {$producto['nombre_producto']} debe ser mayor a 0.");
                return;
            }

            if ($producto['cantidad_distribuir'] > $producto['cantidad_pendiente']) {
                $this->mostrarError("La cantidad a distribuir para {$producto['nombre_producto']} no puede exceder la cantidad pendiente.");
                return;
            }

            if (!$producto['cantidad_stock'] || $producto['cantidad_stock'] <= 0) {
                $this->mostrarError("La cantidad en stock para {$producto['nombre_producto']} debe ser mayor a 0.");
                return;
            }

            if (!$producto['unidad_medida_id']) {
                $this->mostrarError("Debe seleccionar una unidad de medida para {$producto['nombre_producto']}.");
                return;
            }

            // Validar distribución completa para cada producto
            if (!$producto['bodega_id']) {
                $this->mostrarError("Debe seleccionar una bodega para {$producto['nombre_producto']}.");
                return;
            }

            if (!$producto['segmento_id']) {
                $this->mostrarError("Debe seleccionar un segmento para {$producto['nombre_producto']}.");
                return;
            }

            if (!$producto['seccion_id']) {
                $this->mostrarError("Debe seleccionar una sección para {$producto['nombre_producto']}.");
                return;
            }
        }

        try {
            DB::beginTransaction();

            $productosRecibidos = 0;
            $mensajeDetalle = "✅ Recepción masiva exitosa:\n\n";

            foreach ($this->productosRecepcionMasiva as $producto) {
                // Obtener detalle de compra
                $detalleCompra = CompraHasProducto::find($producto['id']);

                if (!$detalleCompra) {
                    continue;
                }

                $cantidadDistribuir = floatval($producto['cantidad_distribuir']);
                $cantidadParaStock = floatval($producto['cantidad_stock']);

                // Verificar que la cantidad aún esté disponible
                if ($detalleCompra->cantidad_sin_asignar < $cantidadDistribuir) {
                    throw new \Exception("La cantidad disponible para {$producto['nombre_producto']} ha cambiado. Solo quedan {$detalleCompra->cantidad_sin_asignar} unidades disponibles.");
                }

                // Actualizar la unidad de medida de venta del producto si cambió
                $productoModel = Producto::find($producto['producto_id']);
                if ($productoModel && $producto['unidad_medida_id'] != $productoModel->unidad_medida_venta_id) {
                    $unidadAnterior = $productoModel->unidad_medida_venta_id;
                    $productoModel->unidad_medida_venta_id = $producto['unidad_medida_id'];
                    $productoModel->save();

                    // Registrar en bitácora: Actualización de unidad de medida
                    Bitacora::registrar(
                        Auth::id(),
                        'Inventario - Recepción Masiva',
                        'Actualizar unidad_medida_venta',
                        "Unidad de medida de venta del producto '{$producto['nombre_producto']}' actualizada de {$unidadAnterior} a {$producto['unidad_medida_id']}",
                        $productoModel->id,
                        'producto',
                        ['unidad_medida_venta_id' => $unidadAnterior],
                        ['unidad_medida_venta_id' => $producto['unidad_medida_id']]
                    );

                    Log::info('Unidad de medida de venta actualizada', [
                        'producto_id' => $productoModel->id,
                        'unidad_anterior' => $unidadAnterior,
                        'unidad_nueva' => $producto['unidad_medida_id']
                    ]);
                }

                // Guardar cantidad anterior para bitácora
                $cantidadSinAsignarAnterior = $detalleCompra->cantidad_sin_asignar;

                // Obtener precio_venta_id correspondiente a la unidad de medida seleccionada
                $precioVentaId = $producto['precio_venta_id'] ?? null;
                
                // Si no hay precio_venta_id en el producto, intentar buscarlo
                if (!$precioVentaId && $producto['unidad_medida_id']) {
                    $unidadSeleccionada = collect($producto['unidades_disponibles'])
                        ->firstWhere('id', $producto['unidad_medida_id']);
                    $precioVentaId = $unidadSeleccionada['precio_venta_id'] ?? null;
                }

                // Crear registro en recibido_bodega usando la sección específica del producto
                $recibidoBodega = RecibidoBodega::create([
                    'producto_id' => $producto['producto_id'],
                    'seccion_id' => $producto['seccion_id'], // Usar la sección específica del producto
                    'cantidad_compra_lote' => $cantidadDistribuir,
                    'cantidad_inicial_seccion' => $cantidadParaStock,
                    'cantidad_disponible' => $cantidadParaStock,
                    'fecha_recibido' => $this->fechaRecepcionMasiva,
                    'fecha_expiracion' => $producto['fecha_expiracion'] ?? null,
                    'comentario' => $this->comentarioRecepcionMasiva,
                    'unidades_compra' => $cantidadDistribuir,
                    'unidad_medida_id' => $producto['unidad_medida_id'],
                    'precio_venta_id' => $precioVentaId, // ID de precio_has_venta
                    'users_registro_id' => Auth::id(),
                    'estado_id' => 1
                ]);

                // Registrar en bitácora: Creación de recibido_bodega (recepción masiva)
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Recepción Masiva',
                    'Crear recibido_bodega',
                    "Producto '{$producto['nombre_producto']}' distribuido masivamente. Cantidad: {$cantidadDistribuir} {$producto['unidad_medida_compra']}, Stock: {$cantidadParaStock}",
                    $recibidoBodega->id,
                    'recibido_bodega',
                    null, // No hay datos anteriores (es inserción)
                    [
                        'producto_id' => $producto['producto_id'],
                        'seccion_id' => $producto['seccion_id'],
                        'cantidad_compra_lote' => $cantidadDistribuir,
                        'cantidad_inicial_seccion' => $cantidadParaStock,
                        'cantidad_disponible' => $cantidadParaStock,
                        'fecha_recibido' => $this->fechaRecepcionMasiva,
                        'comentario' => $this->comentarioRecepcionMasiva,
                        'precio_venta_id' => $precioVentaId,
                        'compra_id' => $this->compraId
                    ]
                );

                // Actualizar la cantidad sin asignar en el detalle de compra
                $detalleCompra->cantidad_sin_asignar -= $cantidadDistribuir;
                $detalleCompra->save();

                // Registrar en bitácora: Actualización de compra_has_producto (recepción masiva)
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Recepción Masiva',
                    'Actualizar cantidad_sin_asignar',
                    "Cantidad sin asignar del producto '{$producto['nombre_producto']}' actualizada de {$cantidadSinAsignarAnterior} a {$detalleCompra->cantidad_sin_asignar} (recepción masiva)",
                    $detalleCompra->id,
                    'compra_has_producto',
                    ['cantidad_sin_asignar' => $cantidadSinAsignarAnterior],
                    ['cantidad_sin_asignar' => $detalleCompra->cantidad_sin_asignar]
                );

                $productosRecibidos++;
                $unidad = collect($producto['unidades_disponibles'])->firstWhere('id', $producto['unidad_medida_id']);
                $nombreUnidad = $unidad ? $unidad['nombre'] : '';

                $mensajeDetalle .= "📦 {$producto['nombre_producto']}: {$cantidadDistribuir} {$producto['unidad_medida_compra']} → {$cantidadParaStock} {$nombreUnidad}\n";
            }

            // Verificar si todos los productos de la compra están completamente distribuidos
            $compra = Compra::find($this->compraId);
            $productosConCantidadPendiente = $compra->detallesCompra()
                ->where('cantidad_sin_asignar', '>', 0)
                ->count();

            // Verificar si hay productos con distribución parcial
            $productosConDistribucionParcial = $compra->detallesCompra()
                ->whereRaw('cantidad_sin_asignar > 0 AND cantidad_sin_asignar < cantidad_ingresada')
                ->count();

            // Actualizar estado según la distribución
            if ($productosConCantidadPendiente == 0) {
                // Caso 1: Todos los productos están completamente distribuidos
                $estadoAnterior = $compra->estado_id;
                $compra->estado_id = 3; // Estado "Distribuido"
                $compra->save();

                // Registrar en bitácora: Cambio de estado a Distribuido (recepción masiva)
                Bitacora::registrar(
                    Auth::id(),
                    'Inventario - Recepción Masiva',
                    'Cambio estado compra',
                    "Compra '{$compra->numero_factura}' cambió de estado {$estadoAnterior} a 3 (Distribuido) mediante recepción masiva. {$productosRecibidos} productos distribuidos.",
                    $compra->id,
                    'compra',
                    ['estado_id' => $estadoAnterior],
                    ['estado_id' => 3]
                );

                Log::info('Compra marcada como distribuida (recepción masiva)', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado_id' => 3
                ]);

                // Emitir eventos para notificar a otros componentes
                $this->dispatch('compra-distribuida', $compra->id);
                $this->dispatch('estado-compra-actualizado', $compra->id, 'distribuido');
                $this->dispatch('compra-actualizada', $compra->id);

            } elseif ($productosConCantidadPendiente > 0) {
                // Caso 2: Hay productos con cantidad pendiente
                if (in_array($compra->estado_id, [1, 5])) {
                    $estadoAnterior = $compra->estado_id;
                    $compra->estado_id = 5; // Estado "Pendiente"
                    $compra->save();

                    // Registrar en bitácora: Cambio de estado a Pendiente (recepción masiva)
                    Bitacora::registrar(
                        Auth::id(),
                        'Inventario - Recepción Masiva',
                        'Cambio estado compra',
                        "Compra '{$compra->numero_factura}' cambió de estado {$estadoAnterior} a 5 (Pendiente) mediante recepción masiva. {$productosRecibidos} productos distribuidos, quedan {$productosConCantidadPendiente} productos por distribuir.",
                        $compra->id,
                        'compra',
                        ['estado_id' => $estadoAnterior],
                        ['estado_id' => 5]
                    );

                    Log::info('Compra marcada como pendiente (recepción masiva)', [
                        'compra_id' => $compra->id,
                        'numero_factura' => $compra->numero_factura,
                        'estado_anterior' => $estadoAnterior,
                        'productos_con_cantidad_pendiente' => $productosConCantidadPendiente,
                        'productos_con_distribucion_parcial' => $productosConDistribucionParcial,
                        'nuevo_estado_id' => 5
                    ]);

                    // Emitir eventos para notificar a otros componentes
                    $this->dispatch('estado-compra-actualizado', $compra->id, 'pendiente');
                    $this->dispatch('compra-actualizada', $compra->id);
                }
            }

            // Registrar en bitácora: Resumen de recepción masiva
            Bitacora::registrar(
                Auth::id(),
                'Inventario - Recepción Masiva',
                'Recepción masiva completada',
                "Recepción masiva completada para compra '{$compra->numero_factura}'. {$productosRecibidos} productos distribuidos en total.",
                $compra->id,
                'compra',
                null,
                [
                    'productos_distribuidos' => $productosRecibidos,
                    'fecha_recepcion' => $this->fechaRecepcionMasiva,
                    'comentario' => $this->comentarioRecepcionMasiva,
                    'estado_final' => $compra->estado_id
                ]
            );

            DB::commit();

            // Sincronizar ingreso de compras con página web (recepción masiva)
            try {
                $syncService = app(WebInventorySyncService::class);
                // Enviar webhook por cada producto recibido
                foreach ($this->productosParaRecibir as $producto) {
                    if (isset($producto['recibir']) && $producto['recibir'] && isset($producto['cantidad_asignar_stock']) && $producto['cantidad_asignar_stock'] > 0) {
                        $syncService->sincronizarCompraRecibida(
                            $producto['producto_id'],
                            $producto['nombre_producto'],
                            (int) $producto['cantidad_asignar_stock'],
                            [
                                'fecha_recibido' => $this->fechaRecepcionMasiva,
                                'compra_id' => $this->compraId,
                                'numero_factura' => $compra->numero_factura,
                                'seccion_id' => $producto['seccion_id'],
                                'comentario' => $this->comentarioRecepcionMasiva,
                                'tipo_recepcion' => 'masiva',
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al enviar webhook de compra recibida (recepción masiva)', [
                    'compra_id' => $this->compraId,
                    'error' => $e->getMessage()
                ]);
            }

            $mensajeDetalle .= "\n📊 Total productos recibidos: {$productosRecibidos}";

            // Si la compra se completó, agregar información adicional
            if ($productosConCantidadPendiente == 0) {
                $mensajeDetalle .= "\n\n🎉 ¡La factura {$compra->numero_factura} ha sido completamente distribuida!";
            }

            $this->mostrarExito($mensajeDetalle);
            $this->cerrarModalRecepcionMasiva();
            $this->cargarDatosCompra(); // Recargar datos

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en recepción masiva', [
                'error' => $e->getMessage(),
                'compra_id' => $this->compraId
            ]);
            $this->mostrarError('Error al procesar la recepción masiva: ' . $e->getMessage());
        }
    }

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';
    }

    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CompraDeProductos');
    }

    public function render()
    {
        return view('livewire.inventario.recibir-producto-compra');
    }
}
