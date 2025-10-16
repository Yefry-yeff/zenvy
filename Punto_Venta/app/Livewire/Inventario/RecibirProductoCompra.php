<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\RecibidoBodega;
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
    public $cantidadAsignarStock = ''; // Nueva propiedad para cantidad en stock (solo Valencia)
    public $fechaDistribucion = '';
    public $bodegaDistribucion = '';
    public $segmentoDistribucion = '';
    public $seccionDistribucion = '';
    public $comentarioDistribucion = '';

    // Datos de ubicación
    public $bodegas = [];
    public $segmentos = [];
    public $secciones = [];
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
                return [
                    'id' => $detalle->id,
                    'codigo_producto' => $detalle->producto->id ?? 'N/A',
                    'nombre_producto' => $detalle->producto->nombre ?? 'N/A',
                    'marca' => $detalle->producto->marca->nombre ?? 'Sin marca',
                    'unidad_medida' => $detalle->unidadMedida->nombre ?? 'N/A',
                    'unidad_medida_venta' => $detalle->producto->unidadMedidaVenta->nombre ?? 'N/A',
                    'unidad_medida_venta_id' => $detalle->producto->unidad_medida_venta_id ?? null,
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
            $this->bodegaDistribucion = '';
            $this->segmentoDistribucion = '';
            $this->seccionDistribucion = '';
            $this->comentarioDistribucion = '';

            // Recargar bodegas para asegurar datos actualizados
            $this->cargarBodegas();

            // Debug: verificar bodegas cargadas
            Log::info('Modal abierto - Bodegas disponibles', [
                'user_id' => Auth::id(),
                'bodegas_count' => count($this->bodegas),
                'producto' => $detalle['nombre_producto'],
                'user_role' => Auth::user()->rol->txt_nombre ?? 'Sin rol',
                'user_tienda_id' => Auth::user()->tienda_id
            ]);

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
        $this->bodegaDistribucion = '';
        $this->segmentoDistribucion = '';
        $this->seccionDistribucion = '';
        $this->comentarioDistribucion = '';
        $this->segmentos = [];
        $this->secciones = [];
    }

    public function puedeConfirmarDistribucion()
    {
        $validacionBase = $this->cantidadDistribuir &&
               $this->fechaDistribucion &&
               $this->bodegaDistribucion &&
               $this->segmentoDistribucion &&
               $this->seccionDistribucion &&
               is_numeric($this->cantidadDistribuir) &&
               $this->cantidadDistribuir > 0 &&
               $this->detalleSeleccionado &&
               $this->cantidadDistribuir <= $this->detalleSeleccionado['cantidad_sin_asignar'];

        // Si es producto de Valencia (TRASLADO), validar también cantidadAsignarStock
        if ($this->compra && $this->compra->tipo_origen === 'TRASLADO') {
            return $validacionBase &&
                   $this->cantidadAsignarStock &&
                   is_numeric($this->cantidadAsignarStock) &&
                   $this->cantidadAsignarStock > 0;
        }

        return $validacionBase;
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

        // Validación adicional para productos de Valencia
        if ($this->compra && $this->compra->tipo_origen === 'TRASLADO') {
            if (!$this->cantidadAsignarStock || !is_numeric($this->cantidadAsignarStock) || $this->cantidadAsignarStock <= 0) {
                $this->mostrarError('Para productos de Valencia, la cantidad a asignar en stock debe ser un número mayor a cero.');
                return;
            }
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

            // Determinar la cantidad para el stock (para Valencia usar cantidadAsignarStock, para otros usar cantidadDistribuir)
            $cantidadParaStock = ($this->compra && $this->compra->tipo_origen === 'TRASLADO')
                ? floatval($this->cantidadAsignarStock)
                : $cantidadDistribuir;

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
            if ($productosConCantidadPendiente == 0) {
                // Todos los productos están completamente distribuidos
                $compra->estado_id = 3; // Estado "Distribuido"
                $compra->save();

                Log::info('Compra marcada como distribuida', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'nuevo_estado_id' => 3
                ]);

                // Emitir eventos para notificar a otros componentes
                $this->dispatch('compra-distribuida', $compra->id);
                $this->dispatch('estado-compra-actualizado', $compra->id, 'distribuido');
                $this->dispatch('compra-actualizada', $compra->id);
            } elseif ($productosConDistribucionParcial > 0 && $compra->estado_id == 1) {
                // Hay productos con distribución parcial y la compra está en estado "Activo"
                $compra->estado_id = 5; // Estado "Pendiente"
                $compra->save();

                Log::info('Compra marcada como pendiente por distribución parcial', [
                    'compra_id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'productos_con_distribucion_parcial' => $productosConDistribucionParcial,
                    'nuevo_estado_id' => 5
                ]);

                // Emitir eventos para notificar a otros componentes
                $this->dispatch('estado-compra-actualizado', $compra->id, 'pendiente');
                $this->dispatch('compra-actualizada', $compra->id);
            }

            DB::commit();

            // Preparar mensaje de éxito detallado
            $mensaje = "✅ Distribución exitosa:\n\n";
            $mensaje .= "📦 Producto: {$this->detalleSeleccionado['nombre_producto']}\n";
            
            // Si es producto de Valencia, mostrar ambas cantidades
            if ($this->compra && $this->compra->tipo_origen === 'TRASLADO') {
                $mensaje .= "🔢 Cantidad distribuida: {$cantidadDistribuir} {$this->detalleSeleccionado['unidad_medida']}\n";
                $mensaje .= "� Cantidad en stock: {$cantidadParaStock} unidades\n";
            } else {
                $mensaje .= "�🔢 Cantidad: {$cantidadDistribuir} {$this->detalleSeleccionado['unidad_medida']}\n";
            }
            
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
