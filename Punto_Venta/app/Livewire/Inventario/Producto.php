<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto as ProductoModel;
use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Producto extends Component
{
    public $modalEliminarAbierto = false;
    public $productoAEliminar = null;
    public $productoSeleccionado = null;
    public $stockDisponible = 0;
    public $tieneCodigoBarras = false;
    public $puedeEliminar = false;
    public $tieneComprasActivas = false;

    // Propiedades para sincronización con Valencia
    public $productosValencia = [];
    private $sincronizacionService;

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = SincronizacionProductosService::obtenerInstancia();
        }
        return $this->sincronizacionService;
    }

    public function mount()
    {
        // Ya no necesitamos cargar productos Valencia por separado
        // Se obtienen directamente de db_zenvy en render()
    }

    public function render()
    {
        // Obtener solo productos activos (estado_id = 1) con sus relaciones para mostrar en la tabla
        $productos = ProductoModel::with(['subcategoria.categoria', 'marca', 'unidadMedidaVenta'])
            ->where('estado_id', 1)
            ->get();

        // Separar productos basado en la columna producto_valencia
        // producto_valencia = 0 -> Producto de Zenvy
        // producto_valencia = 1 -> Producto de Valencia
        $productosZenvy = $productos->filter(function ($producto) {
            return $producto->producto_valencia == 0;
        })->values();

        $productosValencia = $productos->filter(function ($producto) {
            return $producto->producto_valencia == 1;
        })->values();

        return view('livewire.inventario.producto', compact('productosZenvy', 'productosValencia'));
    }

    public function sincronizarProductosValencia()
    {
        try {
            $service = $this->getSincronizacionService();
            $resultado = $service->sincronizarTodosLosProductos();
            
            if ($resultado['sincronizados'] > 0) {
                session()->flash('message', "Se sincronizaron {$resultado['sincronizados']} productos exitosamente.");
                // Los productos se refrescarán automáticamente en render()
            }
            
            if ($resultado['errores'] > 0) {
                session()->flash('warning', "Hubo {$resultado['errores']} errores durante la sincronización.");
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar productos: ' . $e->getMessage());
        }
    }

    public function sincronizarProductoValencia($idProductoValencia)
    {
        try {
            $service = $this->getSincronizacionService();
            $resultado = $service->sincronizarProducto($idProductoValencia);
            
            if ($resultado['success']) {
                session()->flash('message', $resultado['mensaje']);
                // Los productos se refrescarán automáticamente en render()
            } else {
                session()->flash('error', $resultado['mensaje']);
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar producto: ' . $e->getMessage());
        }
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
            $this->productoSeleccionado = (object) [
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
            
            // Verificar compras activas o pendientes con cantidad sin asignar
            $this->tieneComprasActivas = $this->verificarComprasActivas($id);
            
            // Determinar si se puede eliminar
            $this->puedeEliminar = !$this->tieneCodigoBarras && $this->stockDisponible == 0 && !$this->tieneComprasActivas;
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
        $this->tieneComprasActivas = false;
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

    /**
     * Verifica si el producto tiene compras activas o pendientes con cantidad sin asignar
     */
    private function verificarComprasActivas($productoId)
    {
        try {
            // Verificar si existe en compra_has_producto con compras en estado activo (1) o pendiente (5)
            // y que tengan cantidad_sin_asignar diferente de 0
            $comprasActivas = \Illuminate\Support\Facades\DB::table('compra_has_producto as chp')
                ->join('compra as c', 'chp.compra_id', '=', 'c.id')
                ->join('estado as e', 'c.estado_id', '=', 'e.id')
                ->where('chp.producto_id', $productoId)
                ->whereIn('c.estado_id', [1, 5]) // Estados: Activo (1) y Pendiente (5)
                ->where('chp.cantidad_sin_asignar', '>', 0)
                ->exists();

            return $comprasActivas;

        } catch (\Exception $e) {
            // En caso de error, retornar true para prevenir eliminación accidental
            return true;
        }
    }
}
