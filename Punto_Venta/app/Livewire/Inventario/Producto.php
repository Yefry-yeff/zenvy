<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto as ProductoModel;

class Producto extends Component
{
    public $modalEliminarAbierto = false;
    public $productoAEliminar = null;

    public function render()
    {
        // Obtener productos con sus relaciones para mostrar en la tabla
        $productos = ProductoModel::with(['subcategoria.categoria', 'marca', 'unidadMedidaVenta'])
            ->select('id', 'nombre', 'descripcion', 'precio_base', 'subcategoria_id', 'marca_id', 'unidad_medida_venta_id', 'created_at')
            ->get();

        return view('livewire.inventario.producto', compact('productos'));
    }

    public function editar($id)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm', parametros: ['id' => $id]);
    }

    public function abrirModalCrear()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm');
    }

    public function confirmarEliminar($id)
    {
        $this->productoAEliminar = $id;
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->productoAEliminar = null;
    }

    public function eliminarProducto()
    {
        if ($this->productoAEliminar) {
            try {
                ProductoModel::eliminarProducto($this->productoAEliminar);
                session()->flash('mensaje', 'Producto eliminado exitosamente.');
            } catch (\Exception $e) {
                session()->flash('error', 'Error al eliminar el producto: ' . $e->getMessage());
            }
        }
        $this->cerrarModalEliminar();
    }
}
