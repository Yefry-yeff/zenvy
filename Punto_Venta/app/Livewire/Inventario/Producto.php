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
        // Obtener solo productos activos (estado_id = 1) con sus relaciones para mostrar en la tabla
        $productos = ProductoModel::with(['subcategoria.categoria', 'marca', 'unidadMedidaVenta'])
            ->where('estado_id', 1)
            ->select('id', 'nombre', 'descripcion', 'precio_base', 'codigo_barra', 'subcategoria_id', 'marca_id', 'unidad_medida_venta_id', 'created_at')
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
                // Validar que el código de barras esté vacío antes de eliminar
                $producto = ProductoModel::find($this->productoAEliminar);
                
                if (!$producto) {
                    session()->flash('error', 'Producto no encontrado.');
                    $this->cerrarModalEliminar();
                    return;
                }
                
                // Verificar si el código de barras está vacío o es null
                if (!empty($producto->codigo_barra) && trim($producto->codigo_barra) !== '') {
                    session()->flash('error', 'No se puede eliminar el producto porque tiene código de barras asignado. Debe eliminar el código de barras primero.');
                    $this->cerrarModalEliminar();
                    return;
                }
                
                // Si pasa la validación, proceder con la eliminación
                ProductoModel::eliminarProducto($this->productoAEliminar);
                session()->flash('mensaje', 'Producto eliminado exitosamente.');
            } catch (\Exception $e) {
                session()->flash('error', 'Error al eliminar el producto: ' . $e->getMessage());
            }
        }
        $this->cerrarModalEliminar();
    }
}
