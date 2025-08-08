<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\Factura;
use App\Models\Bodega;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
    public $montosPorMetodo = []; // Nueva: array con montos por cada método
    public $metodosActivosParaPago = []; // Métodos que tendrán monto > 0
    
    // Variables para pago en efectivo
    public $efectivoRecibido = 0;
    public $montoEfectivo = 0;
    public $cambio = 0;
    
    // Variables para pago con tarjeta
    public $montoTarjeta = 0;

    // Variables para validación de stock
    public $tiendaUsuario = null;
    public $bodegaPrincipal = null;
    public $alertasStock = [];
    public $hayErroresStock = false;

    public function mount()
    {
        $this->clientesModal = collect(); // Inicializar como colección vacía
        
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
        
        // Inicializar estado de errores de stock
        $this->hayErroresStock = false;
        
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

        // Validar stock en bodega principal antes de agregar
        if (!$this->validarStockProducto($producto->id, $this->cantidad)) {
            return; // El error ya se muestra en validarStockProducto
        }

        // Verificar si el producto ya está en la factura
        $productoExistente = false;
        foreach ($this->productosFactura as $index => $item) {
            if ($item['id'] == $producto->id) {
                $cantidadTotal = $item['cantidad'] + $this->cantidad;
                
                // Validar stock con la nueva cantidad total
                if (!$this->validarStockProducto($producto->id, $cantidadTotal)) {
                    return;
                }
                
                $this->productosFactura[$index]['cantidad'] = $cantidadTotal;
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

        // Limpiar alertas de stock para este producto si existe
        unset($this->alertasStock[$producto->id]);
        $this->actualizarEstadoErroresStock();

        // Limpiar campos y mantener el foco en el input
        $this->codigoBarras = '';
        $this->cantidad = 1;
        $this->dispatch('producto-agregado');
        
        $this->calcularTotales();
    }

    public function eliminarProducto($index)
    {
        // Limpiar alerta de stock para este producto
        if (isset($this->productosFactura[$index]['id'])) {
            unset($this->alertasStock[$this->productosFactura[$index]['id']]);
        }
        
        unset($this->productosFactura[$index]);
        $this->productosFactura = array_values($this->productosFactura);
        $this->actualizarEstadoErroresStock();
        $this->calcularTotales();
    }

    public function modificarCantidad($index, $nuevaCantidad)
    {
        if ($nuevaCantidad <= 0) {
            $this->eliminarProducto($index);
            return;
        }
        
        // Validar stock con la nueva cantidad
        $productoId = $this->productosFactura[$index]['id'];
        if (!$this->validarStockProducto($productoId, $nuevaCantidad)) {
            // Revertir a la cantidad anterior si no hay stock suficiente
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
        
        // Inicializar montos en 0 para todos los métodos
        $this->montosPorMetodo = [];
        foreach ($this->tiposPago as $tipoPago) {
            $this->montosPorMetodo[$tipoPago->id] = 0;
        }
        
        $this->mostrarModalPagoFlag = true;
    }

    public function cerrarModalPago()
    {
        $this->mostrarModalPagoFlag = false;
        $this->montosPorMetodo = [];
        $this->metodosActivosParaPago = [];
    }

    public function distribuirTotalEnEfectivo()
    {
        // Buscar el ID del método "Efectivo"
        $efectivoId = null;
        foreach ($this->tiposPago as $tipoPago) {
            if ($tipoPago->nombre === 'Efectivo') {
                $efectivoId = $tipoPago->id;
                break;
            }
        }
        
        if ($efectivoId) {
            // Resetear todos los montos
            foreach ($this->montosPorMetodo as $key => $value) {
                $this->montosPorMetodo[$key] = 0;
            }
            // Asignar todo el total al efectivo
            $this->montosPorMetodo[$efectivoId] = $this->total;
        }
    }

    public function procesarDistribucionPagos()
    {
        // Validar que no haya errores de stock antes de procesar
        if ($this->hayErroresStock()) {
            session()->flash('error', 'No se puede procesar la factura. Hay productos sin stock suficiente.');
            return;
        }

        if (empty($this->productosFactura)) {
            session()->flash('error', 'No hay productos en la factura.');
            return;
        }

        // Validar que la distribución sea correcta
        $totalDistribuido = array_sum($this->montosPorMetodo);
        
        if ($totalDistribuido < $this->total) {
            $faltante = $this->total - $totalDistribuido;
            session()->flash('error', 'El total distribuido es menor al total a pagar. Faltan: L. ' . number_format($faltante, 2));
            return;
        }
        
        if ($totalDistribuido <= 0) {
            session()->flash('error', 'Debe distribuir al menos un monto en los métodos de pago');
            return;
        }
        
        // Calcular cambio si hay exceso
        $cambioTotal = $totalDistribuido - $this->total;
        
        // Obtener métodos activos (con monto > 0)
        $this->metodosActivosParaPago = [];
        foreach ($this->montosPorMetodo as $tipoId => $monto) {
            if ($monto > 0) {
                $tipoPago = collect($this->tiposPago)->firstWhere('id', $tipoId);
                if ($tipoPago) {
                    $this->metodosActivosParaPago[] = [
                        'id' => $tipoId,
                        'nombre' => $tipoPago['nombre'],
                        'monto' => $monto
                    ];
                }
            }
        }
        
        // Mensaje informativo si hay cambio
        if ($cambioTotal > 0) {
            session()->flash('info', 'Se procesará el pago con cambio de L. ' . number_format($cambioTotal, 2));
        }
        
        $this->cerrarModalPago();
        
        // Determinar el flujo según los métodos activos
        $tieneEfectivo = collect($this->metodosActivosParaPago)->contains('nombre', 'Efectivo');
        $tieneOtros = collect($this->metodosActivosParaPago)->contains(function($metodo) {
            return $metodo['nombre'] !== 'Efectivo';
        });
        
        if ($tieneEfectivo && $tieneOtros) {
            // Pago mixto
            $this->procesarPagoMixtoDistribucion();
        } elseif ($tieneEfectivo) {
            // Solo efectivo
            $this->procesarSoloEfectivoDistribucion();
        } else {
            // Solo otros métodos (tarjeta/cheque)
            $this->procesarSoloOtrosDistribucion();
        }
    }

    public function procesarSoloEfectivoDistribucion()
    {
        $efectivo = collect($this->metodosActivosParaPago)->firstWhere('nombre', 'Efectivo');
        $this->montoEfectivo = $efectivo['monto'];
        $this->efectivoRecibido = 0;
        $this->mostrarModalEfectivoFlag = true;
    }

    public function procesarSoloOtrosDistribucion()
    {
        // Para métodos como tarjeta/cheque, usar el primer método activo
        $primerMetodo = collect($this->metodosActivosParaPago)->first();
        
        if (!$primerMetodo) {
            session()->flash('error', 'No hay métodos de pago activos.');
            return;
        }
        
        $this->montoTarjeta = $primerMetodo['monto'];
        $this->mostrarModalTarjetaFlag = true;
    }

    public function procesarPagoMixtoDistribucion()
    {
        // En pago mixto, empezar con efectivo si existe
        $efectivo = collect($this->metodosActivosParaPago)->firstWhere('nombre', 'Efectivo');
        if ($efectivo) {
            $this->montoEfectivo = $efectivo['monto'];
            $this->efectivoRecibido = 0;
            $this->mostrarModalEfectivoFlag = true;
        } else {
            // Si no hay efectivo, procesar el primer método
            $this->procesarSoloOtrosDistribucion();
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

    public function confirmarEfectivo()
    {
        if ($this->efectivoRecibido < $this->montoEfectivo) {
            session()->flash('error', 'El efectivo recibido es insuficiente');
            return;
        }

        $this->cambio = $this->efectivoRecibido - $this->montoEfectivo;
        
        // Verificar si es pago mixto
        $tieneMetodoNoEfectivo = collect($this->metodosActivosParaPago)->contains(function($metodo) {
            return $metodo['nombre'] !== 'Efectivo';
        });
        
        if ($tieneMetodoNoEfectivo && $this->montoEfectivo < $this->total) {
            // Es pago mixto, continuar con el siguiente método
            $siguienteMetodo = collect($this->metodosActivosParaPago)->firstWhere(function($metodo) {
                return $metodo['nombre'] !== 'Efectivo';
            });
            
            $this->montoTarjeta = $siguienteMetodo['monto'];
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

    public function limpiarCarrito()
    {
        $this->productosFactura = [];
        $this->cliente = null;
        $this->busquedaCliente = '';
        $this->calcularTotales();
        
        // Limpiar variables de pago
        $this->mostrarModalPagoFlag = false;
        $this->mostrarModalEfectivoFlag = false;
        $this->mostrarModalTarjetaFlag = false;
        $this->montosPorMetodo = [];
        $this->metodosActivosParaPago = [];
        $this->efectivoRecibido = 0;
        $this->montoEfectivo = 0;
        $this->montoTarjeta = 0;
        $this->cambio = 0;
        $this->alertasStock = [];
        $this->hayErroresStock = false;
    }

    public function validarStockProducto($productoId, $cantidadSolicitada)
    {
        if (!$this->tiendaUsuario) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Usuario sin tienda asignada']);
            return false;
        }

        // Obtener stock total disponible
        $stockTotal = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
            ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
            ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
            ->where('b.tienda_id', $this->tiendaUsuario)
            ->where('b.principal', 1)
            ->where('b.estado_id', 1)
            ->where('rb.producto_id', $productoId)
            ->where('rb.estado_id', 1)
            ->sum('rb.cantidad_disponible');

        // Calcular cantidad ya en el carrito
        $cantidadEnCarrito = 0;
        foreach ($this->productosFactura as $item) {
            if ($item['id'] == $productoId) {
                $cantidadEnCarrito += $item['cantidad'];
            }
        }

        // Calcular stock disponible real
        $stockDisponible = ($stockTotal ?? 0) - $cantidadEnCarrito;

        if ($stockDisponible < $cantidadSolicitada) {
            $mensaje = "Stock insuficiente. Disponible: {$stockDisponible}, Solicitado: {$cantidadSolicitada}";
            if ($cantidadEnCarrito > 0) {
                $mensaje .= " (Ya tienes {$cantidadEnCarrito} en el carrito)";
            }
            
            $this->alertasStock[$productoId] = $mensaje;
            $this->actualizarEstadoErroresStock();
            $this->dispatch('mostrar-error', ['mensaje' => $mensaje]);
            return false;
        } else {
            unset($this->alertasStock[$productoId]);
            $this->actualizarEstadoErroresStock();
            return true;
        }
    }

    public function hayErroresStock()
    {
        return !empty($this->alertasStock);
    }

    private function actualizarEstadoErroresStock()
    {
        $this->hayErroresStock = !empty($this->alertasStock);
    }

    public function obtenerStockDisponible($productoId)
    {
        if (!$this->tiendaUsuario) {
            return 0;
        }

        try {
            // Obtener stock total menos lo que ya está en el carrito
            $stockTotal = DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $this->tiendaUsuario)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('rb.producto_id', $productoId)
                ->where('rb.estado_id', 1)
                ->sum('rb.cantidad_disponible');

            // Restar lo que ya está en el carrito
            $cantidadEnCarrito = 0;
            foreach ($this->productosFactura as $item) {
                if ($item['id'] == $productoId) {
                    $cantidadEnCarrito += $item['cantidad'];
                }
            }

            $stockDisponible = ($stockTotal ?? 0) - $cantidadEnCarrito;
            return max(0, $stockDisponible); // No permitir valores negativos
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function render()
    {
        return view('livewire.sala-de-ventas.ventas', [
            'tiposPago' => $this->tiposPago
        ]);
    }
}
