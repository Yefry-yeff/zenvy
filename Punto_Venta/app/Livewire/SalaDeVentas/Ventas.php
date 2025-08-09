<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\Factura;
use App\Models\Bodega;
use App\Services\CAIService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

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
    public $caiFacturaImpresa = null;

    // Propiedades para CAI
    public $caiActual = null;
    public $informacionCAI = [];
    public $alertaCAI = null;

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
        $this->verificarCAI();
    }

    public function verificarCAI()
    {
        $caiService = new CAIService();

        try {
            // Desactivar CAIs vencidos
            $caisDesactivados = $caiService->desactivarCAIsVencidos();
            if ($caisDesactivados > 0) {
                Log::info("CAIs vencidos desactivados: $caisDesactivados");
            }

            // Verificar disponibilidad
            if (!$caiService->verificarDisponibilidadCAI()) {
                $this->alertaCAI = "¡CRÍTICO! No hay CAI activos disponibles para facturar. Contacte al administrador.";
            } else {
                // Obtener información de CAIs
                $this->informacionCAI = $caiService->obtenerInformacionCAIs();

                // Verificar si algún CAI está por agotarse
                foreach ($this->informacionCAI as $cai) {
                    if ($cai->cantidad_no_utilizada <= 10 && $cai->cantidad_no_utilizada > 0) {
                        $this->alertaCAI = "¡AVISO! El CAI {$cai->numero_base} tiene solo {$cai->cantidad_no_utilizada} facturas restantes.";
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Error al verificar CAI: " . $e->getMessage());
            $this->alertaCAI = "Error al verificar CAI: " . $e->getMessage();
        }
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

        // Forzar actualización de la vista
        $this->dispatch('totales-actualizados', [
            'subtotal' => $this->subtotal,
            'totalIsv' => $this->totalIsv,
            'total' => $this->total
        ]);
    }

    // Propiedades computadas para asegurar valores actualizados
    public function getSubtotalComputedProperty()
    {
        return $this->subtotal;
    }

    public function getTotalIsvComputedProperty()
    {
        return $this->totalIsv;
    }

    public function getTotalComputedProperty()
    {
        return $this->total;
    }

    public function getIsvPorTasaComputedProperty()
    {
        return $this->isvPorTasa;
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

        // Asegurar que los totales estén actualizados
        $this->calcularTotales();

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
        // DEBUG: Log inicial CON DATOS DEL MODAL
        Log::info("DEBUG procesarDistribucionPagos INICIO", [
            'productos_factura_count' => count($this->productosFactura),
            'montos_por_metodo' => $this->montosPorMetodo,
            'total' => $this->total,
            'metodos_activos_existentes' => $this->metodosActivosParaPago
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

        Log::info("DEBUG Antes de finalizar venta");

        // Procesar directamente la venta SIN cerrar el modal aún
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

            // Generar número de factura con CAI
            $factura->numero_factura = $this->generarNumeroFactura();

            // Usar el CAI real generado
            $factura->cai_id = $this->caiActual ? $this->caiActual['cai_id'] : 1;
            $factura->tipo_facturacion_id = 1; // Asumiendo que 1 es venta normal
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

            // Guardar productos de la factura con distribución FIFO por secciones
            $indice = 1;
            foreach ($this->productosFactura as $producto) {
                $this->guardarProductoConDistribucionSecciones($factura->id, $producto, $indice);
                $indice++;
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

            // Limpiar datos del modal DESPUÉS de procesar exitosamente
            $this->cerrarModalPago();

            Log::info("DEBUG Modal de pago cerrado y datos limpiados");

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
        Log::info("DEBUG guardarMetodosPagoDistribucion INICIO", [
            'factura_id' => $facturaId,
            'metodosActivosParaPago' => $this->metodosActivosParaPago,
            'montosPorMetodo' => $this->montosPorMetodo,
            'tiposPago' => $this->tiposPago
        ]);

        $metodosParaGuardar = [];

        // Prioridad 1: Si hay metodosActivosParaPago, usarlos (solo los que tienen monto > 0)
        if (!empty($this->metodosActivosParaPago)) {
            Log::info("DEBUG Usando metodosActivosParaPago");

            foreach ($this->metodosActivosParaPago as $metodo) {
                if ($metodo['monto'] > 0) { // Solo los que tienen monto mayor a 0
                    $metodosParaGuardar[] = $metodo;

                    Log::info("DEBUG Método agregado desde metodosActivosParaPago", [
                        'tipo_id' => $metodo['id'],
                        'nombre' => $metodo['nombre'],
                        'monto' => $metodo['monto']
                    ]);
                }
            }
        }
        // Prioridad 2: Si hay montosPorMetodo, construir desde ahí
        elseif (!empty($this->montosPorMetodo)) {
            Log::info("DEBUG Construyendo desde montosPorMetodo");

            foreach ($this->montosPorMetodo as $tipoId => $monto) {
                $tipoPago = collect($this->tiposPago)->firstWhere('id', $tipoId);
                if ($tipoPago && $monto > 0) { // Solo los que tienen monto mayor a 0
                    $metodosParaGuardar[] = [
                        'id' => $tipoId,
                        'nombre' => $tipoPago['nombre'],
                        'monto' => $monto
                    ];

                    Log::info("DEBUG Método agregado desde montosPorMetodo", [
                        'tipo_id' => $tipoId,
                        'nombre' => $tipoPago['nombre'],
                        'monto' => $monto
                    ]);
                }
            }
        }
        // Prioridad 3: Si hay tiposPago disponibles, guardar todos como 0 excepto efectivo con el total
        elseif (!empty($this->tiposPago)) {
            Log::info("DEBUG Creando métodos por defecto desde tiposPago");

            foreach ($this->tiposPago as $tipoPago) {
                $monto = 0;

                // Si es efectivo, poner el total completo
                if (strtolower($tipoPago['nombre']) === 'efectivo') {
                    $monto = $this->total;
                }

                $metodosParaGuardar[] = [
                    'id' => $tipoPago['id'],
                    'nombre' => $tipoPago['nombre'],
                    'monto' => $monto
                ];

                Log::info("DEBUG Método por defecto creado", [
                    'tipo_id' => $tipoPago['id'],
                    'nombre' => $tipoPago['nombre'],
                    'monto' => $monto
                ]);
            }
        }

        Log::info("DEBUG Métodos finales para guardar", [
            'metodosParaGuardar' => $metodosParaGuardar,
            'count_metodos' => count($metodosParaGuardar)
        ]);

        // Guardar solo los métodos con monto > 0
        $totalDistribuido = array_sum($this->montosPorMetodo ?? []);
        $metodosGuardados = 0;

        foreach ($metodosParaGuardar as $metodo) {
            Log::info("DEBUG Procesando método", [
                'metodo_id' => $metodo['id'],
                'metodo_nombre' => $metodo['nombre'] ?? 'N/A',
                'metodo_monto' => $metodo['monto'],
                'monto_mayor_cero' => $metodo['monto'] > 0
            ]);

            $tipoPago = TipoPago::find($metodo['id']);
            if ($tipoPago && $metodo['monto'] > 0) { // Validación adicional de monto > 0

                // Para cada método de pago, guardar el monto específico de ese método
                $montoMetodo = $metodo['monto'];
                $cambio = 0;

                // Calcular cambio solo si es efectivo y el total distribuido es mayor al total de la factura
                if (strtolower($tipoPago->nombre) === 'efectivo' && $totalDistribuido > $this->total) {
                    // El cambio se calcula solo en efectivo si hay exceso en el total distribuido
                    $cambioTotal = $totalDistribuido - $this->total;
                    $cambio = $cambioTotal; // Todo el cambio se asigna al efectivo
                }

                DB::table('factura_has_pago')->insert([
                    'factura_id' => $facturaId,
                    'tipo_pago_id' => $tipoPago->id,
                    'total_factura' => $this->total,
                    'pago_recibido' => $montoMetodo, // Monto específico de este método
                    'cambio' => $cambio,
                ]);

                $metodosGuardados++;

                Log::info("DEBUG Método de pago guardado", [
                    'tipo_pago_id' => $tipoPago->id,
                    'tipo_pago_nombre' => $tipoPago->nombre,
                    'total_factura' => $this->total,
                    'pago_recibido' => $montoMetodo,
                    'cambio' => $cambio,
                    'total_distribuido' => $totalDistribuido
                ]);
            } else {
                Log::info("DEBUG Método NO guardado", [
                    'razon' => !$tipoPago ? 'TipoPago no encontrado' : 'Monto es 0 o menor',
                    'tipo_pago_found' => !$tipoPago ? false : true,
                    'monto' => $metodo['monto']
                ]);
            }
        }

        Log::info("DEBUG guardarMetodosPagoDistribucion FINALIZADO", [
            'metodos_guardados' => $metodosGuardados,
            'total_metodos_procesados' => count($metodosParaGuardar)
        ]);
    }

    private function guardarProductoConDistribucionSecciones($facturaId, $producto, $indice)
    {
        Log::info("DEBUG guardarProductoConDistribucionSecciones INICIO", [
            'factura_id' => $facturaId,
            'producto_id' => $producto['id'],
            'cantidad_solicitada' => $producto['cantidad'],
            'indice' => $indice
        ]);

        // Obtener producto por código de barras para conseguir el ID correcto
        $productoDb = DB::table('producto')->where('id', $producto['id'])->first();
        if (!$productoDb) {
            Log::error("Producto no encontrado", ['producto_id' => $producto['id']]);
            return;
        }

        // Obtener secciones con stock disponible ordenadas por cantidad disponible (FIFO: más stock primero)
        $seccionesConStock = DB::table('tienda as t')
            ->join('bodega as b', 'b.tienda_id', '=', 't.id')
            ->join('segmento as s', 's.bodega_id', '=', 'b.id')
            ->join('seccion as sc', 'sc.segmento_id', '=', 's.id')
            ->join('recibido_bodega as rb', 'rb.seccion_id', '=', 'sc.id')
            ->where('t.id', Auth::user()->tienda_id)
            ->where('rb.producto_id', $producto['id'])
            ->where('b.principal', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->select(
                'sc.id as seccion_id',
                'sc.descripcion as seccion_nombre',
                'rb.cantidad_disponible',
                'rb.id as recibido_bodega_id'
            )
            ->orderBy('rb.cantidad_disponible', 'DESC') // Primero las secciones con más stock
            ->get();

        Log::info("DEBUG Secciones encontradas", [
            'secciones_con_stock' => $seccionesConStock->toArray()
        ]);

        if ($seccionesConStock->isEmpty()) {
            Log::error("No hay stock disponible", ['producto_id' => $producto['id']]);
            throw new \Exception("No hay stock disponible para el producto");
        }

        $cantidadRestante = $producto['cantidad'];
        $registrosCreados = 0;

        foreach ($seccionesConStock as $seccion) {
            if ($cantidadRestante <= 0) break;

            // Calcular cuánto tomar de esta sección
            $cantidadATomar = min($cantidadRestante, $seccion->cantidad_disponible);

            // Crear registro en factura_has_producto
            DB::table('factura_has_producto')->insert([
                'factura_id' => $facturaId,
                'producto_id' => $producto['id'],
                'seccion_id' => $seccion->seccion_id,
                'unidad_medida_id' => 1, // Valor por defecto
                'indice' => $indice,
                'numero_unidades_resta_inventario' => $cantidadATomar,
                'unidades_nota_credito_resta_inventario' => 0,
                'resta_inventario_total' => $cantidadATomar,
                'precio_unidad' => $producto['precio'],
                'cantidad' => $cantidadATomar,
                'subtotal' => $cantidadATomar * $producto['precio'],
                'isv' => ($cantidadATomar * $producto['precio']) * ($productoDb->isv / 100),
                'total' => ($cantidadATomar * $producto['precio']) + (($cantidadATomar * $producto['precio']) * ($productoDb->isv / 100)),
                'idPrecioSeleccionado' => '0',
                'precio_seleccionado' => 0
            ]);

            // Actualizar stock en recibido_bodega
            DB::table('recibido_bodega')
                ->where('id', $seccion->recibido_bodega_id)
                ->decrement('cantidad_disponible', $cantidadATomar);

            Log::info("DEBUG Registro creado en factura_has_producto", [
                'seccion_id' => $seccion->seccion_id,
                'seccion_nombre' => $seccion->seccion_nombre,
                'cantidad_tomada' => $cantidadATomar,
                'stock_anterior' => $seccion->cantidad_disponible,
                'stock_restante' => $seccion->cantidad_disponible - $cantidadATomar
            ]);

            $cantidadRestante -= $cantidadATomar;
            $registrosCreados++;
        }

        if ($cantidadRestante > 0) {
            Log::warning("Stock insuficiente", [
                'producto_id' => $producto['id'],
                'cantidad_faltante' => $cantidadRestante
            ]);
            throw new \Exception("Stock insuficiente. Faltan {$cantidadRestante} unidades");
        }

        Log::info("DEBUG guardarProductoConDistribucionSecciones FINALIZADO", [
            'registros_creados' => $registrosCreados,
            'cantidad_distribuida' => $producto['cantidad']
        ]);
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
            // Solo efectivo, finalizar venta inmediatamente usando distribución
            $this->cerrarModalEfectivo();
            $this->finalizarVentaConDistribucion();
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

        // Finalizar venta usando el método de distribución
        $this->finalizarVentaConDistribucion();
    }

    public function finalizarVenta($tipoPago)
    {
        try {
            DB::beginTransaction();

            // Crear la factura principal
            $factura = new Factura();
            $factura->tipo_facturacion_id = 1; // Asumiendo que 1 es venta normal

            // Generar número de factura con CAI
            $factura->numero_factura = $this->generarNumeroFactura();

            // Usar el CAI real generado
            $factura->cai_id = $this->caiActual ? $this->caiActual['cai_id'] : 1;
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

        // Cargar información del CAI asociado a la factura
        $this->caiFacturaImpresa = DB::table('cai')
            ->where('id', $this->facturaParaImprimir->cai_id)
            ->first();

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

        // Generar y guardar imagen de la factura
        $this->generarYGuardarImagenFactura($facturaId);
    }

    private function generarYGuardarImagenFactura($facturaId)
    {
        try {
            Log::info("DEBUG Generando imagen de factura", ['factura_id' => $facturaId]);

            // Obtener datos de la factura
            $factura = Factura::find($facturaId);
            if (!$factura) {
                Log::error("Factura no encontrada para generar imagen", ['factura_id' => $facturaId]);
                return;
            }

            // Obtener información de la empresa
            $empresa = DB::table('empresa')->first();

            // Obtener información de la tienda con dirección
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1) // Asumiendo tienda principal, puedes cambiarlo por un campo en factura
                ->first();

            // Crear una imagen en blanco (600x900 píxeles) - más alta para el nuevo encabezado
            $ancho = 600;
            $alto = 900;
            $imagen = imagecreatetruecolor($ancho, $alto);

            // Habilitar alpha blending y guardar alpha
            imagealphablending($imagen, false);
            imagesavealpha($imagen, true);

            // Definir colores
            $blanco = imagecolorallocate($imagen, 255, 255, 255);
            $negro = imagecolorallocate($imagen, 0, 0, 0);
            $gris = imagecolorallocate($imagen, 128, 128, 128);
            $azul = imagecolorallocate($imagen, 0, 100, 200);

            // Fondo blanco
            imagefill($imagen, 0, 0, $blanco);

            $y = 20; // Comenzar más arriba

            // LOGO DE LA EMPRESA (si existe)
            if ($empresa && $empresa->logo) {
                try {
                    // Crear imagen temporal del logo
                    $logoTemporal = imagecreatefromstring($empresa->logo);
                    if ($logoTemporal) {
                        // Obtener dimensiones del logo original
                        $logoAncho = imagesx($logoTemporal);
                        $logoAlto = imagesy($logoTemporal);

                        // Calcular nuevas dimensiones (máximo 80x80)
                        $maxTamano = 80;
                        $escala = min($maxTamano / $logoAncho, $maxTamano / $logoAlto);
                        $nuevoAncho = (int)($logoAncho * $escala);
                        $nuevoAlto = (int)($logoAlto * $escala);

                        // Posicionar logo en el centro horizontal
                        $logoX = ($ancho - $nuevoAncho) / 2;

                        // Redimensionar y copiar logo
                        imagecopyresampled($imagen, $logoTemporal, $logoX, $y, 0, 0,
                                         $nuevoAncho, $nuevoAlto, $logoAncho, $logoAlto);

                        imagedestroy($logoTemporal);
                        $y += $nuevoAlto + 15;
                    }
                } catch (Exception $e) {
                    Log::warning("Error al procesar logo: " . $e->getMessage());
                }
            }

            // NOMBRE DE LA TIENDA (grande)
            if ($tienda && $tienda->denominacion_social) {
                $nombreTienda = strtoupper($tienda->denominacion_social);
                // Centrar el texto
                $textoAncho = strlen($nombreTienda) * 12; // Aproximado para fuente 5
                $textoX = ($ancho - $textoAncho) / 2;
                imagestring($imagen, 5, max(20, $textoX), $y, $nombreTienda, $azul);
                $y += 30;
            }

            // NOMBRE DE LA EMPRESA (mediano)
            if ($empresa && $empresa->nombre) {
                $nombreEmpresa = $empresa->nombre;
                $textoAncho = strlen($nombreEmpresa) * 8; // Aproximado para fuente 3
                $textoX = ($ancho - $textoAncho) / 2;
                imagestring($imagen, 3, max(20, $textoX), $y, $nombreEmpresa, $negro);
                $y += 25;
            }

            // RTN DE LA EMPRESA
            if ($empresa && $empresa->rtn) {
                $rtnTexto = "RTN: " . $empresa->rtn;
                $textoAncho = strlen($rtnTexto) * 8;
                $textoX = ($ancho - $textoAncho) / 2;
                imagestring($imagen, 3, max(20, $textoX), $y, $rtnTexto, $negro);
                $y += 20;
            }

            // DIRECCIÓN DE LA SUCURSAL
            if ($tienda && $tienda->domicilio_tributario) {
                $direccion = $tienda->domicilio_tributario;
                // Dividir dirección si es muy larga
                if (strlen($direccion) > 50) {
                    $palabras = explode(' ', $direccion);
                    $linea1 = '';
                    $linea2 = '';
                    foreach ($palabras as $palabra) {
                        if (strlen($linea1 . ' ' . $palabra) <= 50) {
                            $linea1 .= ($linea1 ? ' ' : '') . $palabra;
                        } else {
                            $linea2 .= ($linea2 ? ' ' : '') . $palabra;
                        }
                    }

                    $textoAncho = strlen($linea1) * 6;
                    $textoX = ($ancho - $textoAncho) / 2;
                    imagestring($imagen, 2, max(20, $textoX), $y, $linea1, $gris);
                    $y += 15;

                    if ($linea2) {
                        $textoAncho = strlen($linea2) * 6;
                        $textoX = ($ancho - $textoAncho) / 2;
                        imagestring($imagen, 2, max(20, $textoX), $y, $linea2, $gris);
                        $y += 15;
                    }
                } else {
                    $textoAncho = strlen($direccion) * 6;
                    $textoX = ($ancho - $textoAncho) / 2;
                    imagestring($imagen, 2, max(20, $textoX), $y, $direccion, $gris);
                    $y += 15;
                }
            }

            // CORREO DE LA EMPRESA
            if ($empresa && $empresa->correo) {
                $correoTexto = "Email: " . $empresa->correo;
                $textoAncho = strlen($correoTexto) * 6;
                $textoX = ($ancho - $textoAncho) / 2;
                imagestring($imagen, 2, max(20, $textoX), $y, $correoTexto, $gris);
                $y += 15;
            }

            // TELÉFONO FORMATEADO (####-####)
            if ($empresa && $empresa->telefono) {
                $telefono = $empresa->telefono;
                // Formatear teléfono como ####-####
                if (strlen($telefono) == 8) {
                    $telefonoFormateado = substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4);
                } else {
                    $telefonoFormateado = $telefono;
                }
                $telefonoTexto = "Tel: " . $telefonoFormateado;
                $textoAncho = strlen($telefonoTexto) * 6;
                $textoX = ($ancho - $textoAncho) / 2;
                imagestring($imagen, 2, max(20, $textoX), $y, $telefonoTexto, $gris);
                $y += 25;
            }

            // Línea separadora
            imageline($imagen, 20, $y, $ancho-20, $y, $gris);
            $y += 30;

            // Información de la factura
            imagestring($imagen, 4, 30, $y, "FACTURA: " . $factura->numero_factura, $negro);
            $y += 25;
            imagestring($imagen, 3, 30, $y, "Cliente: " . ($factura->nombre_cliente ?: 'Consumidor Final'), $negro);
            $y += 20;
            imagestring($imagen, 3, 30, $y, "Fecha: " . $factura->fecha_emision, $negro);
            $y += 20;
            if ($factura->rtn) {
                imagestring($imagen, 3, 30, $y, "RTN: " . $factura->rtn, $negro);
                $y += 20;
            }
            $y += 10;

            // Línea separadora
            imageline($imagen, 20, $y, $ancho-20, $y, $gris);
            $y += 20;

            // Encabezados de productos
            imagestring($imagen, 3, 30, $y, "PRODUCTO", $negro);
            imagestring($imagen, 3, 350, $y, "CANT.", $negro);
            imagestring($imagen, 3, 420, $y, "PRECIO", $negro);
            imagestring($imagen, 3, 500, $y, "TOTAL", $negro);
            $y += 20;
            imageline($imagen, 20, $y, $ancho-20, $y, $gris);
            $y += 15;

            // Productos
            foreach ($this->productosFacturaImpresa as $producto) {
                $nombreCorto = substr($producto->nombre, 0, 25);
                imagestring($imagen, 2, 30, $y, $nombreCorto, $negro);
                imagestring($imagen, 2, 350, $y, $producto->cantidad, $negro);
                imagestring($imagen, 2, 420, $y, "L. " . number_format($producto->precio_unidad, 2), $negro);
                imagestring($imagen, 2, 500, $y, "L. " . number_format($producto->total, 2), $negro);
                $y += 15;
            }

            $y += 10;
            imageline($imagen, 20, $y, $ancho-20, $y, $gris);
            $y += 20;

            // Totales
            imagestring($imagen, 3, 350, $y, "Subtotal:", $negro);
            imagestring($imagen, 3, 470, $y, "L. " . number_format($factura->sub_total, 2), $negro);
            $y += 20;
            imagestring($imagen, 3, 350, $y, "ISV:", $negro);
            imagestring($imagen, 3, 470, $y, "L. " . number_format($factura->isv, 2), $negro);
            $y += 20;
            imagestring($imagen, 4, 350, $y, "TOTAL:", $azul);
            imagestring($imagen, 4, 470, $y, "L. " . number_format($factura->total, 2), $azul);
            $y += 30;

            // Métodos de pago
            if (!empty($this->pagosFacturaImpresa)) {
                imageline($imagen, 20, $y, $ancho-20, $y, $gris);
                $y += 20;
                imagestring($imagen, 3, 30, $y, "METODOS DE PAGO:", $negro);
                $y += 20;

                foreach ($this->pagosFacturaImpresa as $pago) {
                    imagestring($imagen, 2, 50, $y, $pago->metodo . ": L. " . number_format($pago->pago_recibido, 2), $negro);
                    $y += 15;
                }
            }

            // Convertir imagen a BLOB PNG de alta calidad
            ob_start();

            // Configurar PNG con máxima compresión (0) para mejor calidad
            imagepng($imagen, null, 0);
            $imagenBlob = ob_get_clean();

            // Verificar que se generó correctamente
            if (strlen($imagenBlob) === 0) {
                throw new \Exception("Error al generar PNG: el buffer está vacío");
            }

            // Verificar signature PNG
            $signature = bin2hex(substr($imagenBlob, 0, 8));
            if ($signature !== '89504e470d0a1a0a') {
                throw new \Exception("Error: PNG generado no tiene la signature correcta. Signature: $signature");
            }

            // Guardar en la base de datos
            DB::table('factura')
                ->where('id', $facturaId)
                ->update(['factura_imagen' => $imagenBlob]);

            // Limpiar memoria
            imagedestroy($imagen);

            Log::info("DEBUG Imagen de factura generada y guardada", [
                'factura_id' => $facturaId,
                'tamaño_bytes' => strlen($imagenBlob)
            ]);

        } catch (\Exception $e) {
            Log::error("ERROR al generar imagen de factura", [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function descargarImagenFactura($facturaId)
    {
        try {
            $factura = Factura::find($facturaId);

            if (!$factura || !$factura->factura_imagen) {
                session()->flash('error', 'Imagen de factura no encontrada');
                return;
            }

            // Crear respuesta con la imagen
            $nombreArchivo = 'factura_' . $factura->numero_factura . '.png';

            return response($factura->factura_imagen)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            Log::error("ERROR al descargar imagen de factura", [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al descargar la imagen de la factura');
        }
    }

    public function mostrarImagenFactura($facturaId)
    {
        try {
            $factura = Factura::find($facturaId);

            if (!$factura || !$factura->factura_imagen) {
                session()->flash('error', 'Imagen de factura no encontrada');
                return;
            }

            // Mostrar la imagen en el navegador
            return response($factura->factura_imagen)
                ->header('Content-Type', 'image/png');

        } catch (\Exception $e) {
            Log::error("ERROR al mostrar imagen de factura", [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al mostrar la imagen de la factura');
        }
    }

    public function volverAVentas()
    {
        $this->mostrarVistaImpresion = false;
        $this->facturaParaImprimir = null;
        $this->productosFacturaImpresa = [];
        $this->pagosFacturaImpresa = [];
        $this->caiFacturaImpresa = null;

        // Limpiar estado de venta
        $this->limpiarEstadoVenta();
    }

    public function generarPDFFactura()
    {
        try {
            if (!$this->facturaParaImprimir) {
                session()->flash('error', 'No hay factura para generar PDF');
                return;
            }

            // Redirigir a la ruta de generación de PDF
            return redirect()->route('factura.pdf', $this->facturaParaImprimir->id);

        } catch (Exception $e) {
            Log::error("Error al generar PDF: " . $e->getMessage());
            session()->flash('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    private function generarNumeroFactura()
    {
        $caiService = new CAIService();

        try {
            $resultadoCAI = $caiService->obtenerSiguienteNumeroFactura();

            // Guardar información para usar en la factura
            $this->caiActual = $resultadoCAI;

            // Si el CAI se agotó, mostrar alerta
            if ($resultadoCAI['cai_agotado']) {
                $this->alertaCAI = "¡ATENCIÓN! El CAI se ha agotado. Esta es la última factura disponible para este CAI.";
            } elseif ($resultadoCAI['cantidad_restante'] <= 10) {
                $this->alertaCAI = "¡AVISO! Quedan solo {$resultadoCAI['cantidad_restante']} facturas disponibles en el CAI actual.";
            }

            Log::info("DEBUG CAI generado", [
                'numero_factura' => $resultadoCAI['numero_factura'],
                'cai_id' => $resultadoCAI['cai_id'],
                'cantidad_restante' => $resultadoCAI['cantidad_restante']
            ]);

            return $resultadoCAI['numero_factura'];

        } catch (\Exception $e) {
            Log::error("ERROR al generar número CAI: " . $e->getMessage());
            $this->alertaCAI = "ERROR: " . $e->getMessage();

            // Fallback al método anterior si hay error
            $ultimo = Factura::orderBy('id', 'desc')->first();
            $numero = $ultimo ? $ultimo->id + 1 : 1;
            return str_pad($numero, 8, '0', STR_PAD_LEFT);
        }
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
            // Obtener información de la empresa
            $empresa = DB::table('empresa')->first();

            // Obtener información de la tienda con dirección
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1)
                ->first();

            return view('livewire.sala-de-ventas.factura-impresion', [
                'factura' => $this->facturaParaImprimir,
                'productos' => $this->productosFacturaImpresa,
                'pagos' => $this->pagosFacturaImpresa,
                'empresa' => $empresa,
                'tienda' => $tienda,
                'caiFacturaImpresa' => $this->caiFacturaImpresa
            ]);
        }

        return view('livewire.sala-de-ventas.ventas', [
            'tiposPago' => $this->tiposPago
        ]);
    }
}
