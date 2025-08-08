<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\Bodega;
use App\Models\TipoFacturacion;
use App\Models\EstadoFactura;
use App\Models\Cai;
use App\Models\TipoPago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FacturacionForm extends Component
{
    // Propiedades principales
    public $productos = [];
    public $productosFactura = [];
    public $busquedaProducto = '';
    
    // Datos del cliente
    public $nombreCliente = '';
    public $rtnCliente = '';
    
    // Totales
    public $subTotal = 0;
    public $isv = 0;
    public $total = 0;
    
    // Validación de stock
    public $alertasStock = [];
    
    // Tienda del usuario
    public $tiendaUsuario = null;
    public $bodegaPrincipal = null;

    public function mount()
    {
        // Obtener la tienda del usuario autenticado
        $user = Auth::user();
        $this->tiendaUsuario = $user->tienda_id ?? null;
        
        if ($this->tiendaUsuario) {
            // Obtener la bodega principal de la tienda
            $this->bodegaPrincipal = Bodega::where('tienda_id', $this->tiendaUsuario)
                                          ->where('principal', 1)
                                          ->where('estado_id', 1)
                                          ->first();
        }
        
        $this->cargarProductos();
    }

    public function cargarProductos()
    {
        if (!empty($this->busquedaProducto)) {
            $this->productos = Producto::where('nombre', 'like', '%' . $this->busquedaProducto . '%')
                                     ->orWhere('codigo_barra', 'like', '%' . $this->busquedaProducto . '%')
                                     ->where('estado_id', 1)
                                     ->limit(10)
                                     ->get();
        } else {
            $this->productos = [];
        }
    }

    public function updatedBusquedaProducto()
    {
        $this->cargarProductos();
    }

    public function agregarProducto($productoId)
    {
        $producto = Producto::find($productoId);
        
        if (!$producto) {
            session()->flash('error', 'Producto no encontrado');
            return;
        }

        // Verificar si el producto ya está en la factura
        if (isset($this->productosFactura[$productoId])) {
            $this->productosFactura[$productoId]['cantidad']++;
        } else {
            $this->productosFactura[$productoId] = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'precio_base' => $producto->precio_base,
                'cantidad' => 1,
                'subtotal' => $producto->precio_base,
                'isv' => $producto->precio_base * 0.15, // 15% ISV
                'total' => $producto->precio_base * 1.15
            ];
        }

        $this->validarStockProducto($productoId);
        $this->calcularTotales();
        $this->busquedaProducto = '';
        $this->productos = [];
    }

    public function validarStockProducto($productoId)
    {
        if (!$this->tiendaUsuario) {
            $this->alertasStock[$productoId] = 'Usuario sin tienda asignada';
            return;
        }

        $cantidadSolicitada = $this->productosFactura[$productoId]['cantidad'];
        
        $validacion = Factura::validarStockBodegaPrincipal(
            $productoId, 
            $cantidadSolicitada, 
            $this->tiendaUsuario
        );

        if (!$validacion['valido']) {
            $this->alertasStock[$productoId] = $validacion['mensaje'];
        } else {
            unset($this->alertasStock[$productoId]);
        }
    }

    public function actualizarCantidad($productoId, $nuevaCantidad)
    {
        if ($nuevaCantidad <= 0) {
            $this->eliminarProducto($productoId);
            return;
        }

        if (isset($this->productosFactura[$productoId])) {
            $this->productosFactura[$productoId]['cantidad'] = $nuevaCantidad;
            $precio = $this->productosFactura[$productoId]['precio_base'];
            $this->productosFactura[$productoId]['subtotal'] = $precio * $nuevaCantidad;
            $this->productosFactura[$productoId]['isv'] = ($precio * $nuevaCantidad) * 0.15;
            $this->productosFactura[$productoId]['total'] = ($precio * $nuevaCantidad) * 1.15;
            
            $this->validarStockProducto($productoId);
            $this->calcularTotales();
        }
    }

    public function eliminarProducto($productoId)
    {
        unset($this->productosFactura[$productoId]);
        unset($this->alertasStock[$productoId]);
        $this->calcularTotales();
    }

    public function calcularTotales()
    {
        $this->subTotal = 0;
        $this->isv = 0;
        $this->total = 0;

        foreach ($this->productosFactura as $item) {
            $this->subTotal += $item['subtotal'];
            $this->isv += $item['isv'];
            $this->total += $item['total'];
        }
    }

    public function hayErroresStock()
    {
        return !empty($this->alertasStock);
    }

    public function procesarFactura()
    {
        // Validar que no haya errores de stock
        if ($this->hayErroresStock()) {
            session()->flash('error', 'No se puede procesar la factura. Hay productos sin stock suficiente.');
            return;
        }

        if (empty($this->productosFactura)) {
            session()->flash('error', 'No hay productos en la factura.');
            return;
        }

        try {
            DB::beginTransaction();

            // Aquí implementarías la lógica de crear la factura
            // Por ahora solo mostramos un mensaje de éxito
            
            DB::commit();
            
            session()->flash('success', 'Factura procesada exitosamente.');
            $this->limpiarFormulario();
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al procesar factura', [
                'mensaje' => $e->getMessage(),
                'productos' => $this->productosFactura
            ]);
            session()->flash('error', 'Error al procesar la factura.');
        }
    }

    public function limpiarFormulario()
    {
        $this->productosFactura = [];
        $this->alertasStock = [];
        $this->nombreCliente = '';
        $this->rtnCliente = '';
        $this->calcularTotales();
    }

    public function render()
    {
        return view('livewire.sala-de-ventas.facturacion-form');
    }
}
