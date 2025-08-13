<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compra;
use App\Models\Estado;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class CompraDeProductos extends Component
{
    use WithPagination;

    // Propiedades para filtros y búsqueda
    public $busqueda = '';
    public $filtroEstado = '';
    public $filtroFecha = '';

    // Propiedades para alertas
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';

    // Propiedades para modal de anulación
    public $mostrarModalAnular = false;
    public $compraSeleccionada = null;
    public $motivoAnulacion = '';

    // Propiedades para modal de detalle
    public $mostrarModalDetalle = false;
    public $compraDetalle = null;

        // Escuchar evento de distribución completada
    #[On('compra-distribuida')]
    public function actualizarDespuesDistribucion($compraId = null)
    {
        // Refrescar la vista para mostrar los nuevos estados
        $this->resetPage(); // Reset pagination to show changes
        $this->render(); // Force re-render

        // Agregar mensaje de confirmación
        if ($compraId) {
            $compra = Compra::find($compraId);
            if ($compra) {
                session()->flash('success', "La factura {$compra->numero_factura} ha sido marcada como distribuida.");
            }
        }
    }

    // Escuchar evento de cambio de estado de compra
    #[On('estado-compra-actualizado')]
    public function refrescarListado($compraId = null, $nuevoEstado = null)
    {
        // Refrescar la vista cuando se actualiza el estado de una compra
        $this->resetPage();
        $this->render(); // Force re-render
    }

    // Escuchar evento de cualquier actualización de compra
    #[On('compra-actualizada')]
    public function actualizarCompra($compraId = null)
    {
        // Refrescar la vista cuando se actualiza cualquier compra
        $this->resetPage();
        $this->render(); // Force re-render
    }

    // Método para ir a la vista de recibir producto específico
    public function irARecibirProducto($compraId)
    {
        $compra = Compra::with(['estado'])->find($compraId);
        if ($compra && $compra->estado && strtolower($compra->estado->nombre) === 'activo') {
            // Redirigir a la vista de recibir producto con el ID de la compra
            $this->dispatch('cambiarVista', ruta: 'Inventario.RecibirProductoCompra', parametros: ['compraId' => $compraId]);
        } else {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Solo se pueden recibir productos de compras en estado "activo".';
        }
    }

    // Método para refrescar manualmente el componente
    public function refrescarComponente()
    {
        $this->resetPage();
        $this->render();
    }

    // Resetear paginación cuando se cambian los filtros
    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedFiltroFecha()
    {
        $this->resetPage();
    }

    // Método para agregar nueva compra
    public function agregarCompra()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CompraDeProducto');
    }

    // Método para cerrar alerta
    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
    }

    // Método para abrir modal de anulación
    public function abrirModalAnular($compraId)
    {
        $compra = Compra::with(['proveedor', 'estado'])->find($compraId);
        if ($compra && $compra->estado) {
            $estadoNombre = strtolower($compra->estado->nombre);
            $estadoId = $compra->estado_id;
            
            // Solo permitir anular si está en estado "activo" (1)
            // No permitir anular si está en estado "pendiente" (5) por distribución parcial
            if ($estadoNombre === 'activo' && $estadoId != 5) {
                $this->compraSeleccionada = [
                    'id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'proveedor_nombre' => $compra->proveedor->nombre ?? 'N/A',
                    'total' => $compra->detallesCompra->sum('precio_total') ?? 0
                ];
                $this->motivoAnulacion = '';
                $this->mostrarModalAnular = true;
            } else {
                $this->mostrarAlerta = true;
                if ($estadoId == 5) {
                    $this->mensajeAlerta = 'No se puede anular esta compra porque tiene productos distribuidos parcialmente (estado Pendiente).';
                } else {
                    $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
                }
            }
        } else {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Compra no encontrada.';
        }
    }

    // Método para cerrar modal de anulación
    public function cerrarModalAnular()
    {
        $this->mostrarModalAnular = false;
        $this->compraSeleccionada = null;
        $this->motivoAnulacion = '';
    }

    // Método para abrir modal de detalle
    public function verDetalle($compraId)
    {
        $compra = Compra::with(['proveedor', 'estado', 'detallesCompra.producto', 'detallesCompra.unidadMedida'])->find($compraId);
        if ($compra) {
            $this->compraDetalle = [
                'id' => $compra->id,
                'numero_factura' => $compra->numero_factura,
                'proveedor_nombre' => $compra->proveedor->nombre ?? 'N/A',
                'fecha_emision' => $compra->fecha_emision,
                'fecha_recepcion' => $compra->fecha_recepcion,
                'fecha_vencimiento' => $compra->fecha_vencimiento,
                'estado' => $compra->estado->nombre ?? 'Sin Estado',
                'productos' => $compra->detallesCompra->map(function($detalle) {
                    return [
                        'nombre' => $detalle->producto->nombre ?? 'N/A',
                        'cantidad' => $detalle->cantidad_ingresada,
                        'precio_unitario' => $detalle->precio,
                        'subtotal' => $detalle->sub_total_producto,
                        'isv' => $detalle->isv,
                        'precio_total' => $detalle->precio_total,
                        'unidad' => $detalle->unidadMedida->nombre ?? 'N/A',
                        'fecha_expiracion' => $detalle->fecha_expiracion
                    ];
                })->toArray(),
                'total_productos' => $compra->detallesCompra->count(),
                'subtotal_general' => $compra->detallesCompra->sum('sub_total_producto'),
                'isv_general' => $compra->detallesCompra->sum('isv'),
                'total_general' => $compra->detallesCompra->sum('precio_total')
            ];
            $this->mostrarModalDetalle = true;
        }
    }

    // Método para cerrar modal de detalle
    public function cerrarModalDetalle()
    {
        $this->mostrarModalDetalle = false;
        $this->compraDetalle = null;
    }

    // Método para confirmar anulación
    public function confirmarAnulacion()
    {
        $this->validate([
            'motivoAnulacion' => 'required|min:10|max:500'
        ], [
            'motivoAnulacion.required' => 'El motivo de anulación es obligatorio.',
            'motivoAnulacion.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivoAnulacion.max' => 'El motivo no puede exceder 500 caracteres.'
        ]);

        try {
            $compra = Compra::with('estado')->find($this->compraSeleccionada['id']);
            if ($compra && $compra->estado) {
                $estadoNombre = strtolower($compra->estado->nombre);
                $estadoId = $compra->estado_id;
                
                // Solo permitir anular si está en estado "activo" (1)
                // No permitir anular si está en estado "pendiente" (5) por distribución parcial
                if ($estadoNombre === 'activo' && $estadoId != 5) {
                    // Buscar el estado "anulado"
                    $estadoAnulado = Estado::whereRaw('LOWER(nombre) = ?', ['anulado'])->first();
                    if ($estadoAnulado) {
                        $compra->estado_id = $estadoAnulado->id;
                        $compra->save();

                        session()->flash('success', 'Compra anulada exitosamente.');
                        $this->cerrarModalAnular();
                    } else {
                        $this->mostrarAlerta = true;
                        $this->mensajeAlerta = 'No se encontró el estado "anulado" en el sistema.';
                    }
                } else {
                    $this->mostrarAlerta = true;
                    if ($estadoId == 5) {
                        $this->mensajeAlerta = 'No se puede anular esta compra porque tiene productos distribuidos parcialmente (estado Pendiente).';
                    } else {
                        $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
                    }
                }
            } else {
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'Compra no encontrada.';
            }
        } catch (\Exception $e) {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error al anular la compra: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $query = Compra::with(['proveedor', 'detallesCompra', 'estado']);

        // Aplicar filtros
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('numero_factura', 'like', '%' . $this->busqueda . '%')
                  ->orWhereHas('proveedor', function($proveedorQuery) {
                      $proveedorQuery->where('nombre', 'like', '%' . $this->busqueda . '%');
                  });
            });
        }

        if ($this->filtroEstado) {
            $query->whereHas('estado', function($estadoQuery) {
                $estadoQuery->whereRaw('LOWER(nombre) = ?', [strtolower($this->filtroEstado)]);
            });
        }

        if ($this->filtroFecha) {
            $query->where(function($q) {
                $q->whereDate('fecha_emision', $this->filtroFecha)
                  ->orWhereDate('fecha_recepcion', $this->filtroFecha);
            });
        }

        // Ordenar por ID descendente: el último ID creado (más alto) aparece primero
        $compras = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.inventario.compra-de-productos', [
            'compras' => $compras
        ]);
    }
}
