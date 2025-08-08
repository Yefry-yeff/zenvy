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
use Illuminate\Support\Facades\Log;

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
    public $mostrarModalSinStock = false;

    // Propiedades para la vista de impresión
    public $mostrarVistaImpresion = false;
    public $facturaParaImprimir = null;
    public $productosFacturaImpresa = [];
    public $pagosFacturaImpresa = [];

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
        
        $this->cargarTiposPago();
    }

    public function cargarTiposPago()
    {
        $this->tiposPago = TipoPago::all();
    }

    #[On('enfocar-codigo-barras')]
    public function enfocarCodigoBarras()
    {
        $this->dispatch('enfocar-input-codigo');
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

        // DEBUG: Log del valor de cantidad antes de validar
        Log::info("DEBUG agregarProductoPorCodigo", [
            'codigo_barras' => $this->codigoBarras,
            'cantidad_campo' => $this->cantidad,
            'productos_en_carrito' => count($this->productosFactura)
        ]);

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

        // Limpiar campos y mantener el foco en el input
        $this->codigoBarras = '';
        $this->cantidad = 1;
        
        // DEBUG: Log después de resetear
        Log::info("DEBUG después de reseteo", [
            'cantidad_despues_reset' => $this->cantidad,
            'codigo_barras_despues_reset' => $this->codigoBarras
        ]);
        
        $this->calcularTotales();
    }

    public function eliminarProducto($index)
    {
        // Limpiar alerta de stock para este producto
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
        
        // Validar stock con la nueva cantidad total
        $productoId = $this->productosFactura[$index]['id'];
        $stockTotal = $this->obtenerStockTotal($productoId);
        
        // Calcular cuánto hay en el carrito SIN incluir este item que estamos modificando
        $cantidadEnCarritoSinEsteItem = 0;
        foreach ($this->productosFactura as $i => $item) {
            if ($item['id'] == $productoId && $i != $index) {
                $cantidadEnCarritoSinEsteItem += $item['cantidad'];
            }
        }
        
        // La nueva cantidad total sería: cantidad en carrito (sin este item) + nueva cantidad de este item
        $nuevaCantidadTotal = $cantidadEnCarritoSinEsteItem + $nuevaCantidad;
        
        if ($nuevaCantidadTotal > $stockTotal) {
            $this->dispatch('mostrar-sin-stock');
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
        // DEBUG: Log inicial
        Log::info("DEBUG procesarDistribucionPagos INICIO", [
            'productos_factura' => count($this->productosFactura),
            'montos_por_metodo' => $this->montosPorMetodo,
            'total' => $this->total
        ]);

        if (empty($this->productosFactura)) {
            session()->flash('error', 'No hay productos en la factura.');
            return;
        }

        // Validar que la distribución sea correcta
        $totalDistribuido = array_sum($this->montosPorMetodo);
        
        Log::info("DEBUG Validación distribución", [
            'total_distribuido' => $totalDistribuido,
            'total_factura' => $this->total,
            'diferencia' => $totalDistribuido - $this->total
        ]);
        
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
        
        Log::info("DEBUG Métodos activos", [
            'metodos_activos' => $this->metodosActivosParaPago,
            'cambio_total' => $cambioTotal
        ]);
        
        // Mensaje informativo si hay cambio
        if ($cambioTotal > 0) {
            session()->flash('info', 'Se procesará el pago con cambio de L. ' . number_format($cambioTotal, 2));
        }
        
        $this->cerrarModalPago();
        
        Log::info("DEBUG Antes de finalizar venta");
        
        // En lugar de los modales intermedios, procesar directamente la venta
        $this->finalizarVentaConDistribucion();
    }
    
    public function finalizarVentaConDistribucion()
    {
        Log::info("DEBUG finalizarVentaConDistribucion INICIO");
        
        try {
            DB::beginTransaction();
            
            Log::info("DEBUG Transacción iniciada");
            
            // Crear la factura principal
            $factura = new Factura();
            $factura->cai_id = 1; // Valor por defecto para CAI
            $factura->tipo_facturacion_id = 1; // Asumiendo que 1 es venta normal
            $factura->numero_factura = $this->generarNumeroFactura();
            $factura->nombre_cliente = $this->cliente ? $this->cliente->nombre_completo : 'Consumidor Final';
            $factura->rtn = $this->cliente ? $this->cliente->rtn : null;
            $factura->sub_total = $this->subtotal;
            $factura->sub_total_grabado = $this->subtotal; // Por ahora todo gravado
            $factura->sub_total_exento = 0; // Por ahora sin exentos
            $factura->isv = $this->totalIsv;
            $factura->total = $this->total;
            $factura->credito = 0;
            $factura->fecha_emision = now()->format('Y-m-d');
            $factura->estado_factura_id = 1; // Asumiendo que 1 es "Activa"
            $factura->users_id = Auth::id();
            
            Log::info("DEBUG Datos de factura preparados", [
                'numero_factura' => $factura->numero_factura,
                'nombre_cliente' => $factura->nombre_cliente,
                'total' => $factura->total,
                'user_id' => $factura->users_id
            ]);
            
            $factura->save();
            
            Log::info("DEBUG Factura guardada con ID: " . $factura->id);
            
            // Guardar productos de la factura
            foreach ($this->productosFactura as $producto) {
                DB::table('factura_has_producto')->insert([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto['id'],
                    'seccion_id' => 1, // Valor por defecto
                    'unidad_medida_id' => 1, // Valor por defecto
                    'indice' => 1, // Valor por defecto
                    'numero_unidades_resta_inventario' => $producto['cantidad'],
                    'unidades_nota_credito_resta_inventario' => 0,
                    'resta_inventario_total' => $producto['cantidad'],
                    'precio_unidad' => $producto['precio'],
                    'cantidad' => $producto['cantidad'],
                    'subtotal' => $producto['cantidad'] * $producto['precio'],
                    'isv' => $producto['isv'],
                    'total' => ($producto['cantidad'] * $producto['precio']) + $producto['isv'],
                    'idPrecioSeleccionado' => '1',
                    'precio_seleccionado' => $producto['precio']
                ]);
                
                Log::info("DEBUG Producto guardado", [
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad']
                ]);
                
                // Actualizar stock en bodega principal
                $this->actualizarStockVenta($producto['id'], $producto['cantidad'], $factura->id);
            }
            
            // Guardar métodos de pago usando la distribución
            $this->guardarMetodosPagoDistribucion($factura->id);
            
            DB::commit();
            
            Log::info("DEBUG Transacción confirmada");
            
            // Cargar datos para la vista de impresión
            $this->cargarDatosParaImpresion($factura->id);
            
            Log::info("DEBUG Datos cargados para impresión");
            
            // Cambiar a vista de impresión
            $this->mostrarVistaImpresion = true;
            
            Log::info("DEBUG Vista de impresión activada");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERROR en finalizarVentaConDistribucion", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }
    
    private function guardarMetodosPagoDistribucion($facturaId)
    {
        foreach ($this->metodosActivosParaPago as $metodo) {
            $tipoPago = TipoPago::find($metodo['id']);
            if ($tipoPago && $metodo['monto'] > 0) {
                DB::table('factura_has_pago')->insert([
                    'factura_id' => $facturaId,
                    'tipo_pago_id' => $tipoPago->id,
                    'monto' => $metodo['monto'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
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
            // Solo efectivo, finalizar venta inmediatamente
            $this->cerrarModalEfectivo();
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

        $this->cerrarModalTarjeta();
        
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
            DB::beginTransaction();
            
            // Crear la factura principal
            $factura = new Factura();
            $factura->tipo_facturacion_id = 1; // Asumiendo que 1 es venta normal
            $factura->numero_factura = $this->generarNumeroFactura();
            $factura->nombre_cliente = $this->cliente ? $this->cliente->nombre_completo : 'Consumidor Final';
            $factura->rtn = $this->cliente ? $this->cliente->rtn : null;
            $factura->sub_total = $this->subtotal;
            $factura->sub_total_grabado = $this->subtotal; // Por ahora todo gravado
            $factura->sub_total_exento = 0; // Por ahora sin exentos
            $factura->isv = $this->totalIsv;
            $factura->total = $this->total;
            $factura->credito = 0;
            $factura->fecha_emision = now()->format('Y-m-d');
            $factura->estado_factura_id = 1; // Asumiendo que 1 es "Activa"
            $factura->users_id = Auth::id();
            $factura->save();
            
            // Guardar productos de la factura
            foreach ($this->productosFactura as $producto) {
                DB::table('factura_has_producto')->insert([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto['id'],
                    'seccion_id' => 1, // Valor por defecto
                    'unidad_medida_id' => 1, // Valor por defecto
                    'indice' => 1, // Valor por defecto
                    'numero_unidades_resta_inventario' => $producto['cantidad'],
                    'unidades_nota_credito_resta_inventario' => 0,
                    'resta_inventario_total' => $producto['cantidad'],
                    'precio_unidad' => $producto['precio'],
                    'cantidad' => $producto['cantidad'],
                    'subtotal' => $producto['cantidad'] * $producto['precio'],
                    'isv' => $producto['isv'],
                    'total' => ($producto['cantidad'] * $producto['precio']) + $producto['isv'],
                    'idPrecioSeleccionado' => '1',
                    'precio_seleccionado' => $producto['precio']
                ]);
                
                // Actualizar stock en bodega principal
                $this->actualizarStockVenta($producto['id'], $producto['cantidad'], $factura->id);
            }
            
            // Guardar métodos de pago
            $this->guardarMetodosPago($factura->id, $tipoPago);
            
            DB::commit();
            
            // Cargar datos para la vista de impresión
            $this->cargarDatosParaImpresion($factura->id);
            
            // Cambiar a vista de impresión
            $this->mostrarVistaImpresion = true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }
    
    private function cargarDatosParaImpresion($facturaId)
    {
        // Cargar la factura
        $this->facturaParaImprimir = Factura::find($facturaId);
        
        // Cargar productos
        $this->productosFacturaImpresa = DB::table('factura_has_producto as fp')
            ->join('producto as p', 'fp.producto_id', '=', 'p.id')
            ->where('fp.factura_id', $facturaId)
            ->select(
                'p.nombre',
                'p.codigo_barra',
                'fp.cantidad',
                'fp.precio_unidad',
                'fp.total',
                'fp.isv'
            )
            ->get()->toArray();
            
        // Cargar métodos de pago
        $this->pagosFacturaImpresa = DB::table('factura_has_pago as fp')
            ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
            ->where('fp.factura_id', $facturaId)
            ->select('tp.nombre as metodo', 'fp.pago_recibido')
            ->get()->toArray();
    }
    
    public function volverAVentas()
    {
        $this->mostrarVistaImpresion = false;
        $this->facturaParaImprimir = null;
        $this->productosFacturaImpresa = [];
        $this->pagosFacturaImpresa = [];
        
        // Limpiar estado de venta
        $this->limpiarEstadoVenta();
    }
    
    private function generarNumeroFactura()
    {
        $ultimo = Factura::orderBy('id', 'desc')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return str_pad($numero, 8, '0', STR_PAD_LEFT);
    }
    
    private function actualizarStockVenta($productoId, $cantidad, $facturaId)
    {
        // Buscar la bodega principal
        $bodegaPrincipal = Bodega::where('principal', 1)->first();
        
        if (!$bodegaPrincipal) {
            throw new \Exception('No se encontró bodega principal');
        }
        
        // Buscar el stock en la bodega principal para este producto
        $stock = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
            ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
            ->where('seg.bodega_id', $bodegaPrincipal->id)
            ->where('rb.producto_id', $productoId)
            ->where('rb.cantidad_disponible', '>', 0)
            ->orderBy('rb.fecha_recibido', 'asc')
            ->get();
            
        $cantidadRestante = $cantidad;
        
        foreach ($stock as $lote) {
            if ($cantidadRestante <= 0) break;
            
            $cantidadADescontar = min($cantidadRestante, $lote->cantidad_disponible);
            
            DB::table('recibido_bodega')
                ->where('id', $lote->id)
                ->decrement('cantidad_disponible', $cantidadADescontar);
                
            $cantidadRestante -= $cantidadADescontar;
            
            // Registrar en detalle_factura_lote
            DB::table('detalle_factura_lote')->insert([
                'factura_id' => $facturaId,
                'producto_id' => $productoId,
                'recibido_bodega_id' => $lote->id,
                'cantidad_usada' => $cantidadADescontar,
                'precio_unitario' => 0, // Por el momento usar 0, luego se puede obtener del producto
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        if ($cantidadRestante > 0) {
            throw new \Exception("Stock insuficiente para el producto ID: $productoId");
        }
    }
    
    private function guardarMetodosPago($facturaId, $tipoPago)
    {
        switch ($tipoPago) {
            case 'efectivo':
                $tipoPagoId = TipoPago::where('nombre', 'Efectivo')->first()->id;
                DB::table('factura_has_pago')->insert([
                    'factura_id' => $facturaId,
                    'tipo_pago_id' => $tipoPagoId,
                    'monto' => $this->total,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                break;
                
            case 'tarjeta':
                $tipoPagoId = TipoPago::where('nombre', 'Tarjeta')->first()->id;
                DB::table('factura_has_pago')->insert([
                    'factura_id' => $facturaId,
                    'tipo_pago_id' => $tipoPagoId,
                    'monto' => $this->total,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                break;
                
            case 'mixto':
                // Efectivo
                if ($this->montoEfectivo > 0) {
                    $tipoPagoEfectivoId = TipoPago::where('nombre', 'Efectivo')->first()->id;
                    DB::table('factura_has_pago')->insert([
                        'factura_id' => $facturaId,
                        'tipo_pago_id' => $tipoPagoEfectivoId,
                        'monto' => $this->montoEfectivo,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                // Tarjeta
                $montoTarjeta = $this->total - $this->montoEfectivo;
                if ($montoTarjeta > 0) {
                    $tipoPagoTarjetaId = TipoPago::where('nombre', 'Tarjeta')->first()->id;
                    DB::table('factura_has_pago')->insert([
                        'factura_id' => $facturaId,
                        'tipo_pago_id' => $tipoPagoTarjetaId,
                        'monto' => $montoTarjeta,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                break;
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
    }

    public function validarStockProducto($productoId, $cantidadSolicitada)
    {
        if (!$this->tiendaUsuario) {
            $this->dispatch('mostrar-sin-stock');
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

        $stockTotal = $stockTotal ?? 0;

        // Si no hay stock total disponible
        if ($stockTotal <= 0) {
            $this->dispatch('mostrar-sin-stock');
            return false;
        }

        // Calcular cuánto ya tenemos en el carrito de este producto
        $cantidadEnCarrito = 0;
        foreach ($this->productosFactura as $item) {
            if ($item['id'] == $productoId) {
                $cantidadEnCarrito += $item['cantidad'];
            }
        }
        
        // La nueva cantidad total que tendríamos sería: cantidad en carrito + cantidad solicitada
        $nuevaCantidadTotal = $cantidadEnCarrito + $cantidadSolicitada;
        
        // DEBUG: Log para entender qué está pasando
        Log::info("DEBUG Stock Validation", [
            'producto_id' => $productoId,
            'tienda_usuario' => $this->tiendaUsuario,
            'stock_total' => $stockTotal,
            'cantidad_en_carrito' => $cantidadEnCarrito,
            'cantidad_solicitada' => $cantidadSolicitada,
            'nueva_cantidad_total' => $nuevaCantidadTotal,
            'validacion' => $nuevaCantidadTotal <= $stockTotal ? 'VALIDO' : 'INVALIDO'
        ]);
        
        // Validar que la nueva cantidad total no exceda el stock total disponible
        if ($nuevaCantidadTotal > $stockTotal) {
            $this->dispatch('mostrar-sin-stock');
            return false;
        }
        
        return true;
    }

    public function obtenerStockDisponible($productoId)
    {
        if (!$this->tiendaUsuario) {
            return 0;
        }

        try {
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

            $stockTotal = $stockTotal ?? 0;

            // Calcular cuánto ya tenemos en el carrito de este producto
            $cantidadEnCarrito = 0;
            foreach ($this->productosFactura as $item) {
                if ($item['id'] == $productoId) {
                    $cantidadEnCarrito += $item['cantidad'];
                }
            }
            
            // Retornar stock disponible considerando lo que ya está en el carrito
            return max(0, $stockTotal - $cantidadEnCarrito);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function obtenerStockTotal($productoId)
    {
        if (!$this->tiendaUsuario) {
            return 0;
        }

        try {
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

            return $stockTotal ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function limpiarEstadoVenta()
    {
        $this->productosFactura = [];
        $this->cliente = null;
        $this->busquedaCliente = '';
        $this->calcularTotales();
        
        // Limpiar variables de pago
        $this->mostrarModalPagoFlag = false;
        $this->mostrarModalEfectivoFlag = false;
        $this->mostrarModalTarjetaFlag = false;
        $this->mostrarModalClientesFlag = false;
        $this->mostrarModalSinStock = false;
        
        // Resetear montos de pago
        $this->montosPorMetodo = [];
        $this->metodosActivosParaPago = [];
        $this->montoEfectivo = 0;
        $this->montoTarjeta = 0;
        $this->efectivoRecibido = 0;
        $this->cambio = 0;
        
        // Limpiar campos de entrada
        $this->codigoBarras = '';
        $this->cantidad = 1;
    }

    public function render()
    {
        if ($this->mostrarVistaImpresion) {
            return view('livewire.sala-de-ventas.factura-impresion', [
                'factura' => $this->facturaParaImprimir,
                'productos' => $this->productosFacturaImpresa,
                'pagos' => $this->pagosFacturaImpresa
            ]);
        }
        
        return view('livewire.sala-de-ventas.ventas', [
            'tiposPago' => $this->tiposPago
        ]);
    }
}
