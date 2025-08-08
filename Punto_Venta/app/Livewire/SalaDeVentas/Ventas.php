<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Producto;
use Livewire\Attributes\On;

class Ventas extends Component
{
    // Cliente
    public $cliente = null;
    public $mostrarModalClientesFlag = false;
    public $busquedaCliente = '';
    public $clientesModal;
    
    // Búsqueda de productos
    public $codigoBarras = '';
    public $cantidad = 1;
    
    // Productos en la factura
    public $productosFactura = [];
    
    // Totales
    public $subtotal = 0;
    public $isv = 15; // Porcentaje de ISV
    public $totalIsv = 0;
    public $total = 0;

    public function mount()
    {
        $this->clientesModal = collect(); // Inicializar como colección vacía
    }

    public function buscarClientePorIdentidad($identidad)
    {
        $cliente = Cliente::where('identidad', $identidad)->first();
        if ($cliente) {
            $this->cliente = $cliente;
            session()->forget('cliente_no_encontrado');
        } else {
            $this->cliente = null;
            session()->flash('cliente_no_encontrado', 'No existe un cliente con esa identidad.');
        }
        $this->dispatch('cerrar-modal-busqueda');
    }

    public function mostrarModalClientes()
    {
        $this->mostrarModalClientesFlag = true;
        $this->cargarClientesModal();
    }

    public function cerrarModalClientes()
    {
        $this->mostrarModalClientesFlag = false;
        $this->busquedaCliente = '';
    }

    public function cargarClientesModal()
    {
        $query = Cliente::where('estado_id', 1); // Solo clientes activos
        
        if (!empty($this->busquedaCliente)) {
            $query->where(function($q) {
                $q->where('nombre', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('identidad', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('rtn', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('correo', 'LIKE', "%{$this->busquedaCliente}%");
            });
        }
        
        // Usar get() en lugar de paginate() para evitar problemas de serialización
        $this->clientesModal = $query->orderBy('nombre')->limit(50)->get();
    }

    public function updatedBusquedaCliente()
    {
        $this->cargarClientesModal();
    }

    public function seleccionarClienteModal($clienteId)
    {
        $cliente = Cliente::find($clienteId);
        if ($cliente) {
            $this->cliente = $cliente;
            $this->cerrarModalClientes();
        }
    }

    public function updatedIdentidadEdit($value)
    {
        if (!empty($value) && strlen($value) >= 8) { // Buscar automáticamente cuando tenga al menos 8 caracteres
            $this->buscarClientePorIdentidad($value);
        }
    }

    public function agregarProductoPorCodigo()
    {
        if (empty($this->codigoBarras)) {
            return;
        }

        $producto = Producto::where('codigo_barra', $this->codigoBarras)->first();

        if (!$producto) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
            return;
        }

        // Verificar si el producto ya está en la factura
        $productoExistente = false;
        foreach ($this->productosFactura as $index => $item) {
            if ($item['id'] == $producto->id) {
                $this->productosFactura[$index]['cantidad'] += $this->cantidad;
                $productoExistente = true;
                break;
            }
        }

        if (!$productoExistente) {
            $this->productosFactura[] = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo_barra,
                'precio' => $producto->precio_base,
                'isv' => $producto->isv,
                'cantidad' => $this->cantidad
            ];
        }

        // Limpiar campos y mantener el foco en el input
        $this->codigoBarras = '';
        $this->cantidad = 1;
        $this->dispatch('producto-agregado');
        
        $this->calcularTotales();
    }

    public function eliminarProducto($index)
    {
        unset($this->productosFactura[$index]);
        $this->productosFactura = array_values($this->productosFactura);
        $this->calcularTotales();
    }

    public function calcularTotales()
    {
        $this->subtotal = 0;
        $this->totalIsv = 0;
        
        foreach ($this->productosFactura as $producto) {
            $subtotalProducto = $producto['precio'] * $producto['cantidad'];
            $this->subtotal += $subtotalProducto;
            $this->totalIsv += $subtotalProducto * ($producto['isv'] / 100);
        }
        
        $this->total = $this->subtotal + $this->totalIsv;
    }

    public function guardarFactura()
    {
        if (count($this->productosFactura) === 0) {
            session()->flash('error', 'Debe agregar al menos un producto a la factura');
            return;
        }

        try {
            // Aquí implementaremos la lógica para guardar la factura
            // Por ahora solo mostraremos un mensaje de éxito
            session()->flash('success', 'Factura guardada exitosamente');
            
            // Limpiar el estado
            $this->cliente = null;
            $this->productosFactura = [];
            $this->calcularTotales();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la factura: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.sala-de-ventas.ventas');
    }
}
