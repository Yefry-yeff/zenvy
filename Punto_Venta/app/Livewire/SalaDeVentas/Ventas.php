<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\TipoPago;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

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
    public $isvPorTasa = []; // Nuevo: ISV agrupado por tasa

    // Variables para procesamiento de pagos
    public $mostrarModalPagoFlag = false;
    public $mostrarModalEfectivoFlag = false;
    public $mostrarModalTarjetaFlag = false;
    public $tiposPago = [];
    public $metodosPagoSeleccionados = [];
    
    // Variables para pago en efectivo
    public $efectivoRecibido = 0;
    public $montoEfectivo = 0;
    public $cambio = 0;
    
    // Variables para pago con tarjeta
    public $montoTarjeta = 0;

    public function mount()
    {
        $this->clientesModal = collect(); // Inicializar como colección vacía
        $this->cargarTiposPago();
    }

    public function cargarTiposPago()
    {
        $this->tiposPago = TipoPago::all();
    }

    public function buscarClientePorIdentidad($identidad)
    {
        $cliente = Cliente::select([
                'cliente.*',
                DB::raw("CONCAT_WS(', ', 
                    NULLIF(direccion.colonia, ''), 
                    NULLIF(direccion.calle_blv, ''), 
                    NULLIF(direccion.sector_zona, ''), 
                    NULLIF(direccion.bloque, '')
                ) as direccion_completa")
            ])
            ->leftJoin('direccion', 'cliente.direccion_id', '=', 'direccion.id')
            ->where('cliente.identidad', $identidad)
            ->first();
            
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
        $query = Cliente::select([
                'cliente.*',
                DB::raw("CONCAT_WS(', ', 
                    NULLIF(direccion.colonia, ''), 
                    NULLIF(direccion.calle_blv, ''), 
                    NULLIF(direccion.sector_zona, ''), 
                    NULLIF(direccion.bloque, '')
                ) as direccion_completa")
            ])
            ->leftJoin('direccion', 'cliente.direccion_id', '=', 'direccion.id')
            ->where('cliente.estado_id', 1); // Solo clientes activos
        
        if (!empty($this->busquedaCliente)) {
            $query->where(function($q) {
                $q->where('cliente.nombre', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('cliente.identidad', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('cliente.rtn', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('cliente.correo', 'LIKE', "%{$this->busquedaCliente}%");
            });
        }
        
        // Usar get() en lugar de paginate() para evitar problemas de serialización
        $this->clientesModal = $query->orderBy('cliente.nombre')->limit(50)->get();
    }

    public function updatedBusquedaCliente()
    {
        $this->cargarClientesModal();
    }

    public function seleccionarClienteModal($clienteId)
    {
        $cliente = Cliente::select([
                'cliente.*',
                DB::raw("CONCAT_WS(', ', 
                    NULLIF(direccion.colonia, ''), 
                    NULLIF(direccion.calle_blv, ''), 
                    NULLIF(direccion.sector_zona, ''), 
                    NULLIF(direccion.bloque, '')
                ) as direccion_completa")
            ])
            ->leftJoin('direccion', 'cliente.direccion_id', '=', 'direccion.id')
            ->where('cliente.id', $clienteId)
            ->first();
            
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

    public function modificarCantidad($index, $nuevaCantidad)
    {
        if ($nuevaCantidad <= 0) {
            $this->eliminarProducto($index);
            return;
        }
        
        $this->productosFactura[$index]['cantidad'] = $nuevaCantidad;
        $this->calcularTotales();
    }

    public function calcularTotales()
    {
        $this->subtotal = 0;
        $this->totalIsv = 0;
        $isvPorTasa = []; // Agrupamos ISV por tasa
        
        foreach ($this->productosFactura as $producto) {
            $subtotalProducto = $producto['precio'] * $producto['cantidad'];
            $this->subtotal += $subtotalProducto;
            
            $tasaIsv = $producto['isv'];
            $isvProducto = $subtotalProducto * ($tasaIsv / 100);
            $this->totalIsv += $isvProducto;
            
            // Agrupar ISV por tasa
            if (!isset($isvPorTasa[$tasaIsv])) {
                $isvPorTasa[$tasaIsv] = 0;
            }
            $isvPorTasa[$tasaIsv] += $isvProducto;
        }
        
        $this->isvPorTasa = $isvPorTasa;
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

    // Métodos para procesamiento de pagos
    public function mostrarModalPago()
    {
        if (count($this->productosFactura) === 0) {
            session()->flash('error', 'Debe agregar al menos un producto para procesar el pago');
            return;
        }
        
        $this->mostrarModalPagoFlag = true;
        $this->metodosPagoSeleccionados = [];
    }

    public function cerrarModalPago()
    {
        $this->mostrarModalPagoFlag = false;
        $this->metodosPagoSeleccionados = [];
    }

    public function procesarMetodosPago()
    {
        if (empty($this->metodosPagoSeleccionados)) {
            session()->flash('error', 'Debe seleccionar al menos un método de pago');
            return;
        }

        // Obtener los nombres de los métodos seleccionados
        $metodosSeleccionados = TipoPago::whereIn('id', $this->metodosPagoSeleccionados)->pluck('nombre', 'id');
        
        // Determinar el flujo según los métodos seleccionados
        $tieneEfectivo = $metodosSeleccionados->contains('Efectivo');
        $tieneTarjeta = $metodosSeleccionados->contains('Tarjeta');
        $tieneCheque = $metodosSeleccionados->contains('Cheque');

        $this->cerrarModalPago();

        if ($tieneEfectivo && ($tieneTarjeta || $tieneCheque)) {
            // Pago mixto: Efectivo + otro método
            $this->procesarPagoMixto();
        } elseif ($tieneEfectivo) {
            // Solo efectivo
            $this->procesarSoloEfectivo();
        } elseif ($tieneTarjeta) {
            // Solo tarjeta
            $this->procesarSoloTarjeta();
        } elseif ($tieneCheque) {
            // Solo cheque (tratar como tarjeta para confirmación)
            $this->procesarSoloTarjeta();
        }
    }

    public function procesarSoloEfectivo()
    {
        $this->montoEfectivo = $this->total;
        $this->efectivoRecibido = 0;
        $this->mostrarModalEfectivoFlag = true;
    }

    public function procesarSoloTarjeta()
    {
        $this->montoTarjeta = $this->total;
        $this->mostrarModalTarjetaFlag = true;
    }

    public function procesarPagoMixto()
    {
        // Para pago mixto, primero preguntamos cuánto paga en efectivo
        $this->montoEfectivo = $this->total; // Inicialmente el total, el usuario ajustará
        $this->efectivoRecibido = 0;
        $this->mostrarModalEfectivoFlag = true;
    }

    public function cerrarModalEfectivo()
    {
        $this->mostrarModalEfectivoFlag = false;
        $this->efectivoRecibido = 0;
        $this->montoEfectivo = 0;
    }

    public function confirmarPagoEfectivo()
    {
        if ($this->efectivoRecibido < $this->montoEfectivo) {
            session()->flash('error', 'El efectivo recibido es insuficiente');
            return;
        }

        $this->cambio = $this->efectivoRecibido - $this->montoEfectivo;
        
        // Verificar si es pago mixto
        $metodosSeleccionados = TipoPago::whereIn('id', $this->metodosPagoSeleccionados)->pluck('nombre');
        $tieneMetodoNoEfectivo = $metodosSeleccionados->contains('Tarjeta') || $metodosSeleccionados->contains('Cheque');
        
        if ($tieneMetodoNoEfectivo && $this->montoEfectivo < $this->total) {
            // Es pago mixto, continuar con tarjeta
            $this->montoTarjeta = $this->total - $this->montoEfectivo;
            $this->cerrarModalEfectivo();
            $this->mostrarModalTarjetaFlag = true;
        } else {
            // Solo efectivo, finalizar venta
            $this->finalizarVenta('efectivo');
        }
    }

    public function cerrarModalTarjeta()
    {
        $this->mostrarModalTarjetaFlag = false;
        $this->montoTarjeta = 0;
    }

    public function confirmarPagoTarjeta($pagoExitoso)
    {
        if (!$pagoExitoso) {
            $this->cerrarModalTarjeta();
            session()->flash('error', 'El pago con tarjeta no fue procesado correctamente. Intente nuevamente.');
            return;
        }

        // Verificar si hubo efectivo también
        if ($this->montoEfectivo > 0) {
            // Pago mixto completado
            $this->finalizarVenta('mixto');
        } else {
            // Solo tarjeta
            $this->finalizarVenta('tarjeta');
        }
    }

    public function finalizarVenta($tipoPago)
    {
        try {
            // Aquí implementaremos la lógica para guardar la venta completa
            // con el detalle de pagos
            
            $mensaje = '';
            switch ($tipoPago) {
                case 'efectivo':
                    $mensaje = 'Venta completada con pago en efectivo.';
                    if ($this->cambio > 0) {
                        $mensaje .= ' Cambio a entregar: L. ' . number_format($this->cambio, 2);
                    }
                    break;
                case 'tarjeta':
                    $mensaje = 'Venta completada con pago en tarjeta.';
                    break;
                case 'mixto':
                    $mensaje = 'Venta completada con pago mixto (efectivo + tarjeta).';
                    if ($this->cambio > 0) {
                        $mensaje .= ' Cambio a entregar: L. ' . number_format($this->cambio, 2);
                    }
                    break;
            }

            session()->flash('success', $mensaje);
            
            // Limpiar el estado
            $this->limpiarEstadoVenta();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }

    public function limpiarEstadoVenta()
    {
        $this->cliente = null;
        $this->productosFactura = [];
        $this->calcularTotales();
        
        // Limpiar variables de pago
        $this->mostrarModalPagoFlag = false;
        $this->mostrarModalEfectivoFlag = false;
        $this->mostrarModalTarjetaFlag = false;
        $this->metodosPagoSeleccionados = [];
        $this->efectivoRecibido = 0;
        $this->montoEfectivo = 0;
        $this->montoTarjeta = 0;
        $this->cambio = 0;
    }

    public function render()
    {
        return view('livewire.sala-de-ventas.ventas', [
            'tiposPago' => $this->tiposPago
        ]);
    }
}
