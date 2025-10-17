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
            // Cargar todas las unidades de medida como colección de Eloquent (igual que en producto-form)
            $this->unidadesMedida = UnidadMedida::orderBy('nombre', 'asc')->get();

        } catch (\Exception $e) {
            \Log::error('Error al cargar unidades de medida', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->unidadesMedida = collect([]);
        }
    }

    public function updatedUnidadMedidaProducto()
    {
        if ($this->unidadMedidaProducto) {
            $unidad = $this->unidadesMedida->firstWhere('id', $this->unidadMedidaProducto);
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
            $this->unidadMedidaProducto = $detalle['unidad_medida_venta_id'] ?? '';
            $this->nombreUnidadMedidaProducto = $detalle['unidad_medida_venta'] ?? '';
            $this->bodegaDistribucion = '';
            $this->segmentoDistribucion = '';
            $this->seccionDistribucion = '';
            $this->comentarioDistribucion = '';

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
        $this->segmentos = [];
        $this->secciones = [];
    }

    public function puedeConfirmarDistribucion()
    {
        return $this->cantidadDistribuir &&
               $this->fechaDistribucion &&
               $this->bodegaDistribucion &&
               $this->segmentoDistribucion &&
               $this->seccionDistribucion &&
               $this->unidadMedidaProducto &&
               is_numeric($this->cantidadDistribuir) &&
               $this->cantidadDistribuir > 0 &&
               $this->cantidadAsignarStock &&
               is_numeric($this->cantidadAsignarStock) &&
               $this->cantidadAsignarStock > 0 &&
               $this->detalleSeleccionado &&
               $this->cantidadDistribuir <= $this->detalleSeleccionado['cantidad_sin_asignar'];
    }

    public function confirmarDistribucion()
    {
        // Validaciones
        if (!$this->cantidadDistribuir || !$this->fechaDistribucion || !$this->seccionDistribucion) {
            $this->mostrarError('Todos los campos obligatorios deben estar completos.');
            return;
        }

        if (!is_numeric($this->cantidadDistribuir) || $this->cantidadDistribuir <= 0) {
            $this->mostrarError('La cantidad a distribuir debe ser un número mayor a cero.');
            return;
        }

        // Validación de cantidad en stock y unidad de medida (ahora para todos los productos)
        if (!$this->cantidadAsignarStock || !is_numeric($this->cantidadAsignarStock) || $this->cantidadAsignarStock <= 0) {
            $this->mostrarError('La cantidad a asignar en stock debe ser un número mayor a cero.');
            return;
        }

        if (!$this->unidadMedidaProducto) {
            $this->mostrarError('Debe seleccionar una unidad de medida para el producto.');
            return;
        }

        if (!$this->detalleSeleccionado) {
            $this->mostrarError('No hay producto seleccionado para distribuir.');
            return;
        }

        $cantidadDistribuir = floatval($this->cantidadDistribuir);
        $cantidadPendiente = floatval($this->detalleSeleccionado['cantidad_sin_asignar']);

        if ($cantidadDistribuir > $cantidadPendiente) {
            $this->mostrarError("La cantidad a distribuir ({$cantidadDistribuir}) no puede ser mayor a la cantidad pendiente ({$cantidadPendiente}).");
            return;
        }

        try {
            DB::beginTransaction();

            // Buscar el detalle de compra
            $detalleCompra = CompraHasProducto::find($this->detalleSeleccionado['id']);

            if (!$detalleCompra) {
                throw new \Exception('No se encontró el detalle de compra.');
            }

            // Verificar que la cantidad aún esté disponible
            if ($detalleCompra->cantidad_sin_asignar < $cantidadDistribuir) {
                throw new \Exception("La cantidad disponible ha cambiado. Solo quedan {$detalleCompra->cantidad_sin_asignar} unidades disponibles.");
            }

            // Actualizar la unidad de medida de venta del producto si cambió
            if ($this->unidadMedidaProducto != $this->detalleSeleccionado['unidad_medida_venta_id']) {
                $producto = Producto::find($this->detalleSeleccionado['producto_id']);
                if ($producto) {
                    $producto->unidad_medida_venta_id = $this->unidadMedidaProducto;
                    $producto->save();
                    
                    Log::info('Unidad de medida de venta actualizada', [
                        'producto_id' => $producto->id,
                        'unidad_anterior' => $this->detalleSeleccionado['unidad_medida_venta_id'],
                        'unidad_nueva' => $this->unidadMedidaProducto
                    ]);
                }
            }

            // Usar cantidadAsignarStock para el inventario
            $cantidadParaStock = floatval($this->cantidadAsignarStock);

            // Crear registro en recibido_bodega
            $recibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->detalleSeleccionado['producto_id'],
                'seccion_id' => $this->seccionDistribucion,
                'cantidad_compra_lote' => $cantidadDistribuir,
                'cantidad_inicial_seccion' => $cantidadParaStock,
                'cantidad_disponible' => $cantidadParaStock,
                'fecha_recibido' => $this->fechaDistribucion,
                'fecha_expiracion' => $detalleCompra->fecha_expiracion,
                'comentario' => $this->comentarioDistribucion,
                'unidades_compra' => $cantidadDistribuir,
                'unidad_medida_id' => $detalleCompra->unidad_medida_id,
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

            // Preparar mensaje de éxito detallado
            $mensaje = "✅ Distribución exitosa:\n\n";
            $mensaje .= "📦 Producto: {$this->detalleSeleccionado['nombre_producto']}\n";
            $mensaje .= "🔢 Cantidad distribuida: {$cantidadDistribuir} {$this->detalleSeleccionado['unidad_medida']}\n";
            $mensaje .= "📊 Cantidad en stock: {$cantidadParaStock} {$this->nombreUnidadMedidaProducto}\n";
            $mensaje .= "🏢 Bodega: {$this->nombreBodegaDistribucion}\n";
            $mensaje .= "📍 Ubicación: {$this->nombreSegmentoDistribucion} > {$this->nombreSeccionDistribucion}";

            // Si la compra se completó, agregar información adicional
            if ($productosConCantidadPendiente == 0) {
                $mensaje .= "\n\n🎉 ¡La factura {$compra->numero_factura} ha sido completamente distribuida!";
            }

            $this->mostrarExito($mensaje);
            $this->cerrarModalDistribucion();
            $this->cargarDatosCompra(); // Recargar datos para actualizar cantidades

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error al distribuir producto', [
                'detalle_compra_id' => $this->detalleSeleccionado['id'] ?? null,
                'cantidad' => $cantidadDistribuir,
                'seccion_id' => $this->seccionDistribucion,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al distribuir el producto: ' . $e->getMessage());
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
