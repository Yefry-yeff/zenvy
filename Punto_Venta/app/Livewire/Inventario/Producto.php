<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto as ProductoModel;
use Illuminate\Support\Facades\Auth;

class Producto extends Component
{
    public $modalEliminarAbierto = false;
    public $productoAEliminar = null;
    public $productoSeleccionado = null;
    public $stockDisponible = 0;
    public $tieneCodigoBarras = false;
    public $puedeEliminar = false;

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
        
        // Obtener información del producto para mostrar en el modal
        $producto = ProductoModel::with(['marca', 'subcategoria.categoria'])
            ->find($id);
            
        if ($producto) {
            $this->productoSeleccionado = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'marca' => $producto->marca->nombre ?? 'Sin marca',
                'categoria' => $producto->subcategoria->categoria->nombre ?? 'Sin categoría',
                'subcategoria' => $producto->subcategoria->nombre ?? 'Sin subcategoría'
            ];
            
            // Verificar código de barras
            $this->tieneCodigoBarras = !empty($producto->codigo_barra) && trim($producto->codigo_barra) !== '';
            
            // Obtener stock disponible
            $this->stockDisponible = $this->obtenerStockProducto($id);
            
            // Determinar si se puede eliminar
            $this->puedeEliminar = !$this->tieneCodigoBarras && $this->stockDisponible == 0;
        }
        
        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->productoAEliminar = null;
        $this->productoSeleccionado = null;
        $this->stockDisponible = 0;
        $this->tieneCodigoBarras = false;
        $this->puedeEliminar = false;
    }

    public function eliminarProducto()
    {
        if ($this->productoAEliminar && $this->puedeEliminar) {
            try {
                ProductoModel::eliminarProducto($this->productoAEliminar);
                session()->flash('mensaje', 'Producto eliminado exitosamente.');
            } catch (\Exception $e) {
                session()->flash('error', 'Error al eliminar el producto: ' . $e->getMessage());
            }
        }
        $this->cerrarModalEliminar();
    }

    /**
     * Obtiene el stock total disponible de un producto en la bodega principal
     */
    private function obtenerStockProducto($productoId)
    {
        try {
            // Obtener el usuario actual y su tienda
            $usuario = Auth::user();
            if (!$usuario || !$usuario->tienda_id) {
                return 0;
            }

            // Calcular stock total disponible en la bodega principal
            $stockTotal = \Illuminate\Support\Facades\DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $usuario->tienda_id)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('rb.producto_id', $productoId)
                ->where('rb.estado_id', 1)
                ->sum('rb.cantidad_disponible');

            return $stockTotal ?? 0;

        } catch (\Exception $e) {
            // En caso de error, retornar 0 para no bloquear innecesariamente
            return 0;
        }
    }
}
