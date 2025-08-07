<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;

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
        // Buscar la compra por ID (esto se debe implementar según tu lógica)
        $this->compraSeleccionada = $this->obtenerCompraPorId($compraId);
        $this->motivoAnulacion = '';
        $this->mostrarModalAnular = true;
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
            // Aquí implementar la lógica de anulación
            // $this->anularCompra($this->compraSeleccionada['id'], $this->motivoAnulacion);

            session()->flash('success', 'Compra anulada exitosamente.');
            $this->cerrarModalAnular();
        } catch (\Exception $e) {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error al anular la compra: ' . $e->getMessage();
        }
    }

    // Método privado para obtener compra por ID (implementar según tu lógica)
    private function obtenerCompraPorId($compraId)
    {
        // Ejemplo de datos - implementar según tu modelo de datos
        return [
            'id' => $compraId,
            'numero_factura' => 'F-00' . $compraId,
            'proveedor_nombre' => 'Proveedor Ejemplo',
            'total' => 1500.00
        ];
    }

    // Método para obtener las compras (implementar según tu lógica)
    private function obtenerCompras()
    {
        // Ejemplo de datos - implementar según tu modelo de datos
        $compras = collect([
            [
                'id' => 1,
                'numero_factura' => 'F-001',
                'proveedor_nombre' => 'Proveedor ABC',
                'fecha_emision' => '2025-08-01',
                'fecha_recepcion' => '2025-08-02',
                'estado' => 'activo',
                'total_productos' => 5,
                'total' => 1500.00
            ],
            [
                'id' => 2,
                'numero_factura' => 'F-002',
                'proveedor_nombre' => 'Proveedor XYZ',
                'fecha_emision' => '2025-08-03',
                'fecha_recepcion' => '2025-08-04',
                'estado' => 'distribuido',
                'total_productos' => 3,
                'total' => 890.50
            ]
        ]);

        // Aplicar filtros
        if ($this->busqueda) {
            $compras = $compras->filter(function ($compra) {
                return stripos($compra['numero_factura'], $this->busqueda) !== false ||
                       stripos($compra['proveedor_nombre'], $this->busqueda) !== false;
            });
        }

        if ($this->filtroEstado) {
            $compras = $compras->where('estado', $this->filtroEstado);
        }

        if ($this->filtroFecha) {
            $compras = $compras->filter(function ($compra) {
                return date('Y-m-d', strtotime($compra['fecha_emision'])) === $this->filtroFecha ||
                       date('Y-m-d', strtotime($compra['fecha_recepcion'])) === $this->filtroFecha;
            });
        }

        return $compras;
    }

    public function render()
    {
        $compras = $this->obtenerCompras();

        return view('livewire.inventario.compra-de-productos', [
            'compras' => $compras
        ]);
    }
}
