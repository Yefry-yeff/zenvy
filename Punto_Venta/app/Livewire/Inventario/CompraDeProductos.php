<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compra;
use App\Models\Estado;
use Illuminate\Support\Facades\Log;

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
        $this->dispatch('cambiarVista', ruta: 'Inventario.compradeproducto');
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
        if ($compra && $compra->estado && strtolower($compra->estado->nombre) === 'activo') {
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
            $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
        }
    }

    // Método para cerrar modal de anulación
    public function cerrarModalAnular()
    {
        $this->mostrarModalAnular = false;
        $this->compraSeleccionada = null;
        $this->motivoAnulacion = '';
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
            if ($compra && $compra->estado && strtolower($compra->estado->nombre) === 'activo') {
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
                $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
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

        $compras = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.inventario.compra-de-productos', [
            'compras' => $compras
        ]);
    }
}