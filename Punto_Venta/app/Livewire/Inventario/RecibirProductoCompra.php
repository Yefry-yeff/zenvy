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
                'unidadCompra'
            ])
            ->where('compra_id', $this->compraId)
            ->get()
            ->map(function($detalle) {
                return [
                    'id' => $detalle->id,
                    'codigo_producto' => $detalle->producto->codigo ?? 'N/A',
                    'nombre_producto' => $detalle->producto->nombre ?? 'N/A',
                    'marca' => $detalle->producto->marca->nombre ?? 'Sin marca',
                    'unidad_medida' => $detalle->unidadCompra->nombre ?? 'N/A',
                    'precio_unitario' => $detalle->precio,
                    'cantidad_comprada' => $detalle->cantidad_ingresada,
                    'cantidad_sin_asignar' => $detalle->cantidad_sin_asignar,
                    'subtotal' => $detalle->sub_total_producto,
                    'isv' => $detalle->isv,
                    'total' => $detalle->precio_total,
                    'fecha_vencimiento' => $detalle->fecha_expiracion,
                    'producto_id' => $detalle->producto_id,
                    'unidad_compra_id' => $detalle->unidad_compra_id
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
            $this->bodegas = Bodega::where('estado', 1)->orderBy('nombre')->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar bodegas', ['error' => $e->getMessage()]);
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
                    ->where('estado', 1)
                    ->orderBy('descripcion')
                    ->get();
            } catch (\Exception $e) {
                Log::error('Error al cargar segmentos', ['error' => $e->getMessage()]);
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
                    ->where('estado', 1)
                    ->orderBy('descripcion')
                    ->get();
            } catch (\Exception $e) {
                Log::error('Error al cargar secciones', ['error' => $e->getMessage()]);
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
               is_numeric($this->cantidadDistribuir) &&
               $this->cantidadDistribuir > 0 &&
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
            $this->mostrarError('La cantidad debe ser un número mayor a cero.');
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

            // Crear registro en recibido_bodega
            $recibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->detalleSeleccionado['producto_id'],
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
            
            // Si no hay productos con cantidad pendiente, cambiar estado a "Distribuido"
            if ($productosConCantidadPendiente == 0) {
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
            }

            DB::commit();

            // Preparar mensaje de éxito
            $mensaje = "Se distribuyeron {$cantidadDistribuir} {$this->detalleSeleccionado['unidad_medida']} de {$this->detalleSeleccionado['nombre_producto']} exitosamente a la bodega.";
            
            // Si la compra se completó, agregar información adicional
            if ($productosConCantidadPendiente == 0) {
                $mensaje .= " ¡La factura {$compra->numero_factura} ha sido marcada como completamente distribuida!";
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
        $this->dispatch('cambiarVista', ruta: 'Inventario.compradeproductos');
    }

    public function render()
    {
        return view('livewire.inventario.recibir-producto-compra');
    }
}
