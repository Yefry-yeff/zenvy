<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\TipoPago;
use App\Models\TipoPersona;
use App\Models\TipoCliente;
use App\Models\Factura;
use App\Models\Bodega;
use App\Models\Descuento;
use App\Models\DescuentoAdulto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\Subcategoria;
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

    // Cliente manual - campos editables
    public $rtnManual = '';
    public $nombreCompletoManual = '';
    public $telefonoManual = '';
    public $correoManual = '';
    public $direccionManual = '';
    public $modoClienteManual = false;

    // Propiedades adicionales para cliente manual (para compatibilidad con el blade)
    public $nombreClienteManual = '';
    public $correoClienteManual = '';
    public $telefonoClienteManual = '';
    public $direccionClienteManual = '';
    public $tipoPersonaId = 1; // Nueva propiedad para tipo de persona
    public $tipoClienteId = 1; // Nueva propiedad para tipo de cliente

    // Datos para los selectores
    public $tiposPersona = [];
    public $tiposCliente = [];

    // Control de estado de campos
    public $camposBloqueados = false;

    // Búsqueda de productos
    public $codigoBarras = '';
    public $mostrarModalBusqueda = false;
    public $marcaSeleccionada = '';
    public $categoriaSeleccionada = '';
    public $subcategoriaSeleccionada = '';
    public $resultadosBusqueda = [];
    public $marcas = [];
    public $categorias = [];
    public $subcategorias = [];
    public $filtroStock = 'todos'; // 'todos', 'con_stock', 'sin_stock'

    // Productos en la factura
    public $productosFactura = [];

    // Productos y Servicios para selección visual
    public $productos = [];
    public $servicios = [];
    public $busquedaProductosServicios = '';
    public $mostrarProductosServicios = false; // Panel unificado
    public $tipoSeleccion = 'todos'; // 'productos', 'servicios', 'todos'

    // Totales
    public $subtotal = 0;
    public $subtotalBruto = 0; // Suma de cantidad * precio unitario (sin descuentos)
    public $isv = 15; // Porcentaje de ISV
    public $totalIsv = 0;
    public $total = 0;
    public $isvPorTasa = []; // Nuevo: ISV agrupado por tasa

    // Descuentos por edad
    public $descuentoTerceraEdad = false;
    public $descuentoCuartaEdad = false;
    public $totalDescuentos = 0;

    // Descuentos guardados en BD (para mostrar en facturas guardadas)
    public $descuentosGuardados = [];

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

    // Control de procesamiento para evitar duplicados
    public $procesandoVenta = false;

    // Modal y datos de descuento para adulto mayor
    public $mostrarModalDescuentoAdulto = false;
    public $tipoDescuentoActual = null; // 'tercera' o 'cuarta'
    public $dniAdulto = '';
    public $nombreAdulto = '';
    public $edadAdulto = null;
    public $datosDescuentoAdulto = []; // Para mantener en memoria

    // Modal y datos de descuento por producto
    public $modalDescuentoProductoVisible = false;

    // Control de visibilidad del catálogo visual basado en permisos de menú
    public $mostrarCatalogoVisual = true;
    public $indiceProductoSeleccionado = null;
    public $productoSeleccionadoDescuento = null;
    public $porcentajeDescuentoProducto = 0;

    // Propiedades para trámites temporales
    public $mostrarModalTramitesTemporales = false;
    public $tramitesTemporales = [];
    public $cantidadTramitesTemporales = 0;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'abrirModal' => 'abrirModal',
        'cerrarModal' => 'cerrarModal'
    ];

    public function mostrarModalBusqueda()
    {
        $this->mostrarModalBusqueda = true;
        $this->cargarFiltros();
    }

    public function abrirModal($modal)
    {
        Log::info('Abriendo modal: ' . $modal);
        switch ($modal) {
            case 'busqueda':
                $this->mostrarModalBusqueda = true;
                $this->cargarFiltros();
                $this->dispatch('refresh');
                break;
            case 'pago':
                $this->mostrarModalPagoFlag = true;
                $this->dispatch('refresh');
                break;
            case 'descuentoAdulto':
                $this->mostrarModalDescuentoAdulto = true;
                $this->reset(['dniAdulto', 'nombreAdulto', 'edadAdulto']);
                break;
            case 'efectivo':
                $this->mostrarModalEfectivoFlag = true;
                break;
            case 'descuentoProducto':
                $this->modalDescuentoProductoVisible = true;
                break;
        }
    }

    public function cerrarModal($modal)
    {
        Log::info('Cerrando modal: ' . $modal);
        switch ($modal) {
            case 'busqueda':
                $this->mostrarModalBusqueda = false;
                $this->reset(['marcaSeleccionada', 'categoriaSeleccionada', 'subcategoriaSeleccionada', 'resultadosBusqueda']);
                $this->dispatch('refresh');
                break;
            case 'pago':
                $this->mostrarModalPagoFlag = false;
                $this->reset(['montosPorMetodo', 'efectivoRecibido', 'montoEfectivo', 'cambio', 'montoTarjeta']);
                $this->dispatch('refresh');
                break;
            case 'descuentoAdulto':
                $this->mostrarModalDescuentoAdulto = false;
                $this->reset(['dniAdulto', 'nombreAdulto', 'edadAdulto']);
                break;
            case 'efectivo':
                $this->mostrarModalEfectivoFlag = false;
                break;
            case 'descuentoProducto':
                $this->modalDescuentoProductoVisible = false;
                break;
        }
    }

    public function mount()
    {
        $this->clientesModal = collect(); // Inicializar como colección vacía

        // Inicializar propiedades de búsqueda avanzada
        $this->mostrarModalBusqueda = false;
        $this->busquedaProductosServicios = '';
        $this->resultadosBusqueda = collect();

        // Cargar trámites temporales
        $this->cargarTramitesTemporales();

        // Cargar trámite temporal si existe
        if (session()->has('tramite_venta_a_cargar')) {
            $this->cargarTramiteDesdeSession();
        }
        $this->marcaSeleccionada = '';
        $this->categoriaSeleccionada = '';
        $this->subcategoriaSeleccionada = '';
        $this->marcas = collect();
        $this->categorias = collect();
        $this->subcategorias = collect();

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

        // Activar modo cliente manual directamente sin modal
        $this->modoClienteManual = true;
        $this->limpiarCamposManual();

        $this->cargarTiposPago();
        $this->cargarTiposPersonaYCliente(); // Nueva función
        $this->verificarCAI();
        // Cargar productos y servicios para la interfaz unificada
        $this->cargarProductosYServicios();

        // Verificar si el menú de servicios está activo para mostrar el catálogo visual
        $this->verificarEstadoMenuServicios();

        // Cargar descuentos guardados si hay una factura específica
        $this->cargarDescuentosGuardados();
    }

    /**
     * Función para depurar productos con descuento unitario
     */
    public function verificarProductosConDescuento()
    {
        $productos = DB::table('producto')
            ->where('descuento_unitario', '>', 0)
            ->where('estado_id', 1)
            ->get();

        Log::info("Productos con descuento unitario en BD", [
            'cantidad' => $productos->count(),
            'productos' => $productos->toArray()
        ]);

        session()->flash('info', 'Verificación completada. Revisa los logs.');
    }

    /**
     * Nuevo método para cargar tipos de persona y cliente
     */
    public function cargarTiposPersonaYCliente()
    {
        try {
            // Cargar tipos de persona desde la base de datos
            $this->tiposPersona = TipoPersona::select('id', 'nombre')->get();

            // Cargar tipos de cliente desde la base de datos
            $this->tiposCliente = TipoCliente::select('id', 'nombre')->get();

        } catch (Exception $e) {
            Log::error('Error al cargar tipos de persona y cliente: ' . $e->getMessage());
            // Valores por defecto si hay error
            $this->tiposPersona = collect([]);
            $this->tiposCliente = collect([]);
        }
    }

    /**
     * Cargar descuentos guardados de la base de datos para una factura específica
     */
    public function cargarDescuentosGuardados($facturaId = null)
    {
        if ($facturaId) {
            $descuentos = Descuento::where('factura_id', $facturaId)->get();
            $this->descuentosGuardados = $descuentos->keyBy('producto_id')->toArray();
        } else {
            $this->descuentosGuardados = [];
        }
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

            // Verificar disponibilidad específica para la tienda del usuario
            $validacionTienda = $caiService->validarCAIParaTienda($this->tiendaUsuario);

            if (!$validacionTienda['valido']) {
                $this->alertaCAI = "¡CRÍTICO! " . $validacionTienda['mensaje'] . " - " . $validacionTienda['detalle'];
                $this->caiActual = null;
            } else {
                // Verificar disponibilidad general (método anterior como respaldo)
                if (!$caiService->verificarDisponibilidadCAI($this->tiendaUsuario)) {
                    $this->alertaCAI = "¡CRÍTICO! No hay CAI activos disponibles para facturar en su tienda. Contacte al administrador.";
                    $this->caiActual = null;
                } else {
                    // Obtener información de CAIs de la tienda
                    $this->informacionCAI = $caiService->obtenerInformacionCAIs();
                    $this->caiActual = $validacionTienda['cai_info'];

                    // Verificar si algún CAI está por agotarse
                    if ($validacionTienda['cai_info']['cantidad_disponible'] <= 10) {
                        $this->alertaCAI = "¡AVISO! Su CAI tiene solo {$validacionTienda['cai_info']['cantidad_disponible']} facturas restantes.";
                    } else {
                        $this->alertaCAI = null; // Todo está bien
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Error al verificar CAI: " . $e->getMessage());
            $this->alertaCAI = "Error al verificar CAI: " . $e->getMessage();
            $this->caiActual = null;
        }
    }

    public function cargarTiposPago()
    {
        $this->tiposPago = TipoPago::all();
    }

    public function cargarServicios()
    {
        $this->servicios = Servicio::with(['isv', 'estado'])
            ->select('id', 'nombre', 'descripcion', 'precio_base', 'estado_id', 'isv_id',
                    'descuento_unitario', 'descuento_tercera', 'descuento_cuarta') // Excluir 'imagen'
            ->where('estado_id', 1) // Solo servicios activos
            ->when($this->busquedaProductosServicios, function ($query) {
                $query->where('nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->busquedaProductosServicios . '%');
            })
            ->orderBy('nombre')
            ->get();
    }

    public function cargarProductos()
    {
        // CAMBIO: Buscar también en precio_has_venta.codigo_barra
        $this->productos = Producto::with(['isv', 'estado'])
            ->select('id', 'nombre', 'descripcion', 'precio_base', 'estado_id', 'isv_id',
                    'descuento_unitario', 'descuento_tercera', 'descuento_cuarta', 'codigo_barra') // Excluir 'imagen'
            ->where('estado_id', 1) // Solo productos activos
            ->when($this->busquedaProductosServicios, function ($query) {
                $query->where('nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhereExists(function($subQuery) {
                          $subQuery->select(DB::raw(1))
                              ->from('precio_has_venta')
                              ->whereColumn('precio_has_venta.producto_id', 'producto.id')
                              ->where('precio_has_venta.codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%')
                              ->where('precio_has_venta.estado_id', 1);
                      });
            })
            ->orderBy('nombre')
            ->get();
    }

    public function cargarProductosYServicios()
    {
        if ($this->tipoSeleccion === 'productos' || $this->tipoSeleccion === 'todos') {
            $this->cargarProductos();
        }

        if ($this->tipoSeleccion === 'servicios' || $this->tipoSeleccion === 'todos') {
            $this->cargarServicios();
        }
    }

    public function updatedTipoSeleccion()
    {
        $this->cargarProductosYServicios();
    }

    /**
     * Obtener la imagen de un servicio específico como base64
     */
    public function getServicioImagen($servicioId)
    {
        $servicio = Servicio::select('imagen')->find($servicioId);
        return $servicio && $servicio->imagen ? base64_encode($servicio->imagen) : null;
    }

    /**
     * Obtener la imagen de un producto específico como base64
     */
    public function getProductoImagen($productoId)
    {
        $producto = Producto::select('imagen')->find($productoId);
        return $producto && $producto->imagen ? base64_encode($producto->imagen) : null;
    }

    public function agregarServicio($servicioId)
    {
        $servicio = Servicio::with('isv')->find($servicioId);

        if (!$servicio) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Servicio no encontrado']);
            return;
        }

        // Verificar si el servicio ya está en la factura
        $servicioExistente = false;
        foreach ($this->productosFactura as $index => $item) {
            if (isset($item['servicio_id']) && $item['servicio_id'] == $servicio->id) {
                // Aumentar la cantidad - Convertir a entero para evitar errores de tipos
                $nuevaCantidad = (int)$this->productosFactura[$index]['cantidad'] + 1;
                $this->productosFactura[$index]['cantidad'] = $nuevaCantidad;

                // Recalcular el descuento unitario aplicado con la nueva cantidad
                $descuentoUnitarioProducto = $item['descuento_unitario_producto'] ?? 0;
                if ($descuentoUnitarioProducto > 0) {
                    $this->productosFactura[$index]['descuento_unitario_aplicado'] = $descuentoUnitarioProducto * $nuevaCantidad;
                }

                // Recalcular subtotal con descuento para este item
                $subtotalOriginal = $item['precio'] * $nuevaCantidad;
                $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
                $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

                $servicioExistente = true;
                break;
            }
        }

        if (!$servicioExistente) {
            // Obtener el valor de ISV desde la relación
            $valorIsv = $servicio->isv ? $servicio->isv->cantidad : 0;

            // Calcular descuento unitario automático si existe
            $subtotalOriginal = $servicio->precio_base;
            $descuentoUnitarioAplicado = 0;

            if (($servicio->descuento_unitario ?? 0) > 0) {
                $descuentoUnitarioAplicado = $servicio->descuento_unitario;
            }

            $this->productosFactura[] = [
                'servicio_id' => $servicio->id,
                'id' => null, // NULL para diferenciarlo de productos
                'nombre' => $servicio->nombre,
                'codigo' => 'SRV-' . $servicio->id, // Código especial para servicios
                'precio' => $servicio->precio_base,
                'isv' => $valorIsv,
                'cantidad' => 1, // Los servicios siempre cantidad 1 inicialmente
                'descuento_tercera' => $servicio->descuento_tercera ?? 0,
                'descuento_cuarta' => $servicio->descuento_cuarta ?? 0,
                'descuento_unitario_producto' => $servicio->descuento_unitario ?? 0,
                'descuento_unitario_aplicado' => $descuentoUnitarioAplicado,
                'descuento_aplicado' => 0,
                'subtotal_con_descuento' => $subtotalOriginal - $descuentoUnitarioAplicado,
                'tipo' => 'servicio' // Identificador para diferenciar en la vista
            ];

            // Mostrar mensaje si se aplicó descuento automático
            if (($servicio->descuento_unitario ?? 0) > 0) {
                session()->flash('success', 'Servicio aplicado con descuento');
            }
        }

        $this->calcularTotales();

        // Forzar actualización de la vista
        $this->dispatch('$refresh');
    }

    #[On('enfocar-codigo-barras')]
    public function enfocarCodigoBarras()
    {
        $this->dispatch('enfocar-input-codigo');
    }

    public function buscarClientePorIdentidad($identidad)
    {
        $cliente = Cliente::select([
                'cliente.*'
            ])
            ->where(function($q) use ($identidad) {
                $q->where('cliente.identidad', $identidad)
                  ->orWhere('cliente.rtn', $identidad);
            })
            ->first();

        if ($cliente) {
            $this->cliente = $cliente;
            $this->modoClienteManual = false;
            $this->limpiarCamposManual();
            session()->forget('cliente_no_encontrado');
        } else {
            $this->cliente = null;
            session()->flash('cliente_no_encontrado', 'No existe un cliente con esa identidad.');
        }
        $this->dispatch('cerrar-modal-busqueda');
    }

    public function activarModoClienteManual()
    {
        $this->cliente = null;
        $this->modoClienteManual = true;
        $this->limpiarCamposManual();
        $this->dispatch('cerrar-modal-busqueda');
    }

    public function limpiarCamposManual()
    {
        $this->rtnManual = '';
        $this->nombreCompletoManual = '';
        $this->telefonoManual = '';
        $this->correoManual = '';
        $this->direccionManual = '';

        // También limpiar las propiedades adicionales
        $this->nombreClienteManual = '';
        $this->correoClienteManual = '';
        $this->telefonoClienteManual = '';
        $this->direccionClienteManual = '';
        $this->tipoPersonaId = 1;
        $this->tipoClienteId = 1;

        // Desbloquear campos
        $this->camposBloqueados = false;
    }

    public function limpiarDatosCliente()
    {
        $this->cliente = null;
        $this->modoClienteManual = true;
        $this->limpiarCamposManual();
        session()->flash('success', 'Datos del cliente limpiados correctamente.');
    }

    public function desactivarModoClienteManual()
    {
        $this->modoClienteManual = false;
        $this->limpiarCamposManual();
    }

    public function guardarClienteManual()
    {
        // Validar que el nombre esté presente
        if (empty($this->nombreClienteManual)) {
            session()->flash('error', 'El nombre del cliente es requerido');
            return;
        }

        // Validar que el teléfono esté presente
        if (empty($this->telefonoClienteManual)) {
            session()->flash('error', 'Telefono debe ser obligatorio');
            $this->dispatch('marcarCampoError', 'telefonoClienteManual');
            return;
        }

        try {
            // Crear cliente con los datos ingresados según la estructura real de la tabla
            $clienteData = [
                'nombre' => $this->nombreClienteManual,
                'identidad' => $this->rtnManual, // Campo unificado RTN/Identidad
                'telefono' => $this->telefonoClienteManual,
                'correo' => $this->correoClienteManual,
                'direccion' => $this->direccionClienteManual, // Campo correcto según la tabla
                'estado_id' => 1, // Siempre 1 como indicaste
                'tipo_cliente_id' => $this->tipoClienteId ?? 1, // Nueva propiedad
                'tipo_persona_id' => $this->tipoPersonaId ?? 1, // Nueva propiedad
                'users_id' => Auth::id(), // Usuario actual que crea el cliente
            ];

            $nuevoCliente = Cliente::create($clienteData);

            // Seleccionar el cliente recién creado
            $this->cliente = $nuevoCliente;

            // Bloquear campos después de guardar (en lugar de limpiarlos)
            $this->camposBloqueados = true;

            session()->flash('success', 'Cliente guardado exitosamente ');

        } catch (Exception $e) {
            Log::error('Error al guardar cliente manual: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar el cliente: ' . $e->getMessage());
        }
    }

    // Nueva función para buscar cliente por RTN automáticamente
    public function buscarClientePorRtn()
    {
        if (empty($this->rtnManual)) {
            $this->limpiarCamposManual();
            return;
        }

        try {
            // Buscar cliente por identidad (campo unificado RTN/Identidad)
            $clienteEncontrado = Cliente::with(['tipoPersona', 'tipoCliente'])
                ->where('identidad', $this->rtnManual)
                ->first();

            if ($clienteEncontrado) {
                // Cliente encontrado, llenar los campos
                $this->nombreClienteManual = $clienteEncontrado->nombre;
                $this->telefonoClienteManual = $clienteEncontrado->telefono ?? '';
                $this->correoClienteManual = $clienteEncontrado->correo ?? '';
                $this->direccionClienteManual = $clienteEncontrado->direccion ?? '';
                $this->tipoPersonaId = $clienteEncontrado->tipo_persona_id ?? 1;
                $this->tipoClienteId = $clienteEncontrado->tipo_cliente_id ?? 1;
                $this->cliente = $clienteEncontrado;

                // Bloquear campos cuando se encuentra un cliente
                $this->camposBloqueados = true;

                session()->flash('success', 'Cliente encontrado: ' . $clienteEncontrado->nombre);
            } else {
                // Cliente no encontrado - mantener RTN y limpiar solo otros campos
                $rtnTemp = $this->rtnManual; // Guardar el RTN ingresado antes de limpiar

                // Limpiar solo los otros campos, no el RTN
                $this->nombreClienteManual = '';
                $this->telefonoClienteManual = '';
                $this->correoClienteManual = '';
                $this->direccionClienteManual = '';
                $this->tipoPersonaId = 1;
                $this->tipoClienteId = 1;

                $this->rtnManual = $rtnTemp; // Restaurar el RTN ingresado
                $this->cliente = null;
                $this->camposBloqueados = false; // Permitir edición para nuevo cliente

                session()->flash('error', 'Cliente con RTN/Identidad "' . $rtnTemp . '" no existe. Puede crear un nuevo cliente con estos datos.');
            }
        } catch (Exception $e) {
            Log::error('Error al buscar cliente: ' . $e->getMessage());
            session()->flash('error', 'Error al buscar el cliente');
        }
    }

    public function cancelarFactura()
    {
        // Limpiar todos los datos de la factura
        $this->cliente = null;
        $this->modoClienteManual = false;
        $this->limpiarCamposManual();
        $this->productosFactura = [];
        $this->descuentoTerceraEdad = false;
        $this->descuentoCuartaEdad = false;
        $this->totalDescuentos = 0;
        $this->datosDescuentoAdulto = [];
        $this->calcularTotales();

        // Redirigir al dashboard
        return redirect()->route('dashboard');
    }

    public function obtenerNombreCliente()
    {
        Log::info("DEBUG obtenerNombreCliente", [
            'cliente_existe' => $this->cliente ? 'Sí' : 'No',
            'nombreClienteManual' => $this->nombreClienteManual,
            'nombreClienteManual_vacio' => empty($this->nombreClienteManual),
            'cliente_nombre_completo' => $this->cliente ? $this->cliente->nombre_completo : 'N/A'
        ]);

        // Prioridad 1: Si hay cliente seleccionado Y tiene nombre válido
        if ($this->cliente && !empty($this->cliente->nombre_completo) && $this->cliente->nombre_completo !== 'N/A') {
            return $this->cliente->nombre_completo;
        }
        // Prioridad 2: Si hay nombre manual
        elseif (!empty($this->nombreClienteManual)) {
            return $this->nombreClienteManual;
        }
        // Por defecto
        else {
            return 'Consumidor Final';
        }
    }

    public function obtenerRtnCliente()
    {
        Log::info("DEBUG obtenerRtnCliente", [
            'cliente_existe' => $this->cliente ? 'Sí' : 'No',
            'rtnManual' => $this->rtnManual,
            'rtnManual_vacio' => empty($this->rtnManual),
            'cliente_rtn' => $this->cliente ? $this->cliente->rtn : 'N/A'
        ]);

        // Prioridad 1: Si hay cliente seleccionado Y tiene RTN válido
        if ($this->cliente && !empty($this->cliente->rtn) && $this->cliente->rtn !== 'N/A') {
            return $this->cliente->rtn;
        }
        // Prioridad 2: Si hay RTN manual
        elseif (!empty($this->rtnManual)) {
            return $this->rtnManual;
        }
        // Por defecto
        else {
            return null;
        }
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

    public function cerrarModalSinStock()
    {
        $this->mostrarModalSinStock = false;
    }

    public function cargarClientesModal()
    {
        $query = Cliente::with(['tipoPersona', 'tipoCliente'])
            ->where('cliente.estado_id', 1); // Solo clientes activos

        if (!empty($this->busquedaCliente)) {
            $query->where(function($q) {
                $q->where('cliente.nombre', 'LIKE', "%{$this->busquedaCliente}%")
                  ->orWhere('cliente.identidad', 'LIKE', "%{$this->busquedaCliente}%")
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
        $cliente = Cliente::with(['tipoPersona', 'tipoCliente'])
            ->where('cliente.id', $clienteId)
            ->first();

        if ($cliente) {
            $this->cliente = $cliente;

            // Llenar los campos manuales con los datos del cliente seleccionado
            $this->rtnManual = $cliente->identidad ?? '';
            $this->nombreClienteManual = $cliente->nombre ?? '';
            $this->telefonoClienteManual = $cliente->telefono ?? '';
            $this->correoClienteManual = $cliente->correo ?? '';
            $this->direccionClienteManual = $cliente->direccion ?? '';
            $this->tipoPersonaId = $cliente->tipo_persona_id ?? 1;
            $this->tipoClienteId = $cliente->tipo_cliente_id ?? 1;

            // Bloquear campos al seleccionar cliente desde el modal
            $this->camposBloqueados = true;
            $this->modoClienteManual = false;
            $this->cerrarModalClientes();

            session()->flash('success', 'Cliente seleccionado: ' . $cliente->nombre);
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

        // DEBUG: Log del valor antes de validar
        Log::info("DEBUG agregarProductoPorCodigo", [
            'codigo_barras' => $this->codigoBarras,
            'productos_en_carrito' => count($this->productosFactura)
        ]);

        // CAMBIO: Buscar primero en precio_has_venta en lugar de producto.codigo_barra
        $precioEncontrado = DB::table('precio_has_venta')
            ->where('codigo_barra', $this->codigoBarras)
            ->where('estado_id', 1)
            ->first();

        if (!$precioEncontrado) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Código de barras no encontrado']);
            return;
        }

        // Obtener el producto desde el precio_has_venta encontrado
        $producto = Producto::with('isv')->find($precioEncontrado->producto_id);

        if (!$producto) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
            return;
        }

        // Cargar precios disponibles desde precio_has_venta
        $preciosDisponibles = DB::table('precio_has_venta')
            ->join('unidad_medida', 'precio_has_venta.unidad_medida_id', '=', 'unidad_medida.id')
            ->where('precio_has_venta.producto_id', $producto->id)
            ->where('precio_has_venta.estado_id', 1)
            ->select(
                'precio_has_venta.id as precio_id',
                'precio_has_venta.unidad_medida_id',
                'precio_has_venta.codigo_barra',
                'unidad_medida.nombre as unidad_nombre',
                'unidad_medida.simbolo as unidad_simbolo',
                'precio_has_venta.cantidad',
                'precio_has_venta.precio'
            )
            ->orderBy('precio_has_venta.cantidad', 'asc')
            ->get();

        if ($preciosDisponibles->isEmpty()) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Este producto no tiene precios configurados']);
            return;
        }

        // NUEVO: Buscar la primera unidad de medida que tenga stock disponible
        // considerando lo que ya está en el carrito
        $precioConStock = null;
        $stockTotalUnidad = 0;

        foreach ($preciosDisponibles as $precio) {
            // Calcular stock total en bodega
            $stockEnBodega = $this->calcularStockTotalPorUnidad($producto->id, $precio->unidad_medida_id);

            // Calcular cuánto ya está en el carrito para esta combinación producto+unidad
            $cantidadEnCarrito = 0;
            foreach ($this->productosFactura as $itemCarrito) {
                // Verificar si es el mismo producto y la misma unidad de medida
                if ($itemCarrito['id'] == $producto->id &&
                    isset($itemCarrito['unidad_medida_id']) &&
                    $itemCarrito['unidad_medida_id'] == $precio->unidad_medida_id) {
                    $cantidadEnCarrito += (int)($itemCarrito['cantidad'] ?? 0);
                }
            }

            // Stock real disponible = stock en bodega - lo que ya está en el carrito
            $stockDisponibleReal = $stockEnBodega - $cantidadEnCarrito;

            if ($stockDisponibleReal > 0) {
                $precioConStock = $precio;
                $stockTotalUnidad = $stockEnBodega; // Guardamos el stock total para referencia
                break; // Encontramos la primera unidad con stock real, salimos del loop
            }
        }

        // Si ninguna unidad tiene stock real disponible, mostrar alerta
        if (!$precioConStock) {
            $this->mostrarModalSinStock = true;
            $this->dispatch('mostrar-error', ['mensaje' => 'No hay stock disponible para este producto. Todo el stock está en el carrito o agotado.']);
            return;
        }

        // Usar la unidad de medida con stock como precio por defecto
        $precioDefecto = $precioConStock;

        // CAMBIO: Siempre agregar una nueva línea, permitir múltiples líneas del mismo producto con diferentes unidades
        // Obtener el valor de ISV desde la relación
        $valorIsv = $producto->isv ? $producto->isv->cantidad : 0;

        // Calcular descuento unitario automático si existe
        $subtotalOriginal = $precioDefecto->precio;

        // NO aplicar descuento automáticamente - el usuario debe aplicarlo manualmente si lo desea
        $descuentoUnitarioAplicado = 0;

        $this->productosFactura[] = [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $precioDefecto->codigo_barra ?? $producto->codigo_barra,
            'precio' => $precioDefecto->precio, // Precio de la unidad de medida
            'precio_id' => $precioDefecto->precio_id,
            'unidad_medida_id' => $precioDefecto->unidad_medida_id,
            'unidad_medida_nombre' => $precioDefecto->unidad_nombre,
            'unidad_medida_simbolo' => $precioDefecto->unidad_simbolo,
            'cantidad_por_unidad' => $precioDefecto->cantidad, // Unidades reales del producto
            'precios_disponibles' => $preciosDisponibles->toArray(),
            'stock_total_unidad' => $stockTotalUnidad, // Stock disponible para esta unidad
            'producto_valencia' => $producto->producto_valencia,
            // Agregar precios de Valencia para el dropdown
            'precio1' => $producto->precio1 ?? 0,
            'precio2' => $producto->precio2 ?? 0,
            'precio3' => $producto->precio3 ?? 0,
            'precio4' => $producto->precio4 ?? 0,
            'precio_base' => $producto->precio_base ?? 0,
            'tipo_precio' => 'precio_has_venta', // Indicar que usa precio_has_venta por defecto
            'isv' => $valorIsv,
            'cantidad' => 1, // Cantidad editable
            'descuento_tercera' => $producto->descuento_tercera ?? 0,
            'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
            'descuento_unitario_producto' => $producto->descuento_unitario ?? 0, // Guardamos el valor para referencia
            'descuento_unitario_aplicado' => 0, // SIEMPRE INICIA EN 0 - no se aplica automáticamente
            'descuento_aplicado' => 0,
            'subtotal_con_descuento' => $subtotalOriginal // Sin descuento inicial
        ];

        // Limpiar campo de código de barras
        $this->codigoBarras = '';

        // DEBUG: Log después de resetear
        Log::info("DEBUG después de reseteo", [
            'codigo_barras_despues_reset' => $this->codigoBarras
        ]);

        $this->calcularTotales();

        // Forzar actualización de la vista
        $this->dispatch('$refresh');
    }

    public function eliminarProducto($index)
    {
        // Limpiar alerta de stock para este producto
        unset($this->productosFactura[$index]);
        $this->productosFactura = array_values($this->productosFactura);
        $this->calcularTotales();
    }

    public function cambiarPrecioProducto($index, $tipoPrecio)
    {
        // Verificar que el índice existe
        if (!isset($this->productosFactura[$index])) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado en el carrito']);
            return;
        }

        // Verificar si es cambio de unidad de medida (nuevo sistema) o tipo de precio (sistema anterior)
        $producto = $this->productosFactura[$index];
        $cantidadActual = $this->productosFactura[$index]['cantidad']; // Preservar cantidad

        // Si el producto tiene precios_disponibles (nuevo sistema)
        if (isset($producto['precios_disponibles']) && !empty($producto['precios_disponibles'])) {
            // Verificar si es un precio de Valencia (precio1, precio2, precio3, precio4)
            if (in_array($tipoPrecio, ['precio1', 'precio2', 'precio3', 'precio4'])) {
                // Cambio a precio de Valencia
                $productoModel = Producto::find($producto['id']);
                if (!$productoModel) {
                    $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
                    return;
                }

                $nuevoPrecio = 0;
                switch ($tipoPrecio) {
                    case 'precio1':
                        $nuevoPrecio = $productoModel->precio1 ?? 0;
                        break;
                    case 'precio2':
                        $nuevoPrecio = $productoModel->precio2 ?? 0;
                        break;
                    case 'precio3':
                        $nuevoPrecio = $productoModel->precio3 ?? 0;
                        break;
                    case 'precio4':
                        $nuevoPrecio = $productoModel->precio4 ?? 0;
                        break;
                }

                if ($nuevoPrecio <= 0) {
                    $this->dispatch('mostrar-error', ['mensaje' => 'El precio seleccionado no está disponible']);
                    return;
                }

                // IMPORTANTE: Solo actualizar el precio, mantener cantidad_por_unidad de la unidad seleccionada
                $this->productosFactura[$index]['precio'] = $nuevoPrecio;
                $this->productosFactura[$index]['tipo_precio'] = $tipoPrecio;
                // NO modificar cantidad_por_unidad - se mantiene la de precio_has_venta

                // Recalcular descuento unitario SOLO si ya tenía descuento aplicado
                $descuentoUnitarioAplicadoActual = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
                if ($descuentoUnitarioAplicadoActual > 0) {
                    $cantidadPorUnidad = $producto['cantidad_por_unidad'] ?? 1;
                    $descuentoUnitarioProducto = $producto['descuento_unitario_producto'] ?? 0;
                    if ($descuentoUnitarioProducto > 0) {
                        $this->productosFactura[$index]['descuento_unitario_aplicado'] =
                            $descuentoUnitarioProducto * $cantidadPorUnidad * $cantidadActual;
                    }
                }
                // Si no tenía descuento, mantenerlo en 0

                // Recalcular subtotal
                $subtotalOriginal = $nuevoPrecio * $cantidadActual;
                $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
                $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

                $this->calcularTotales();
                session()->flash('success', 'Precio actualizado correctamente');
                return;
            }

            // Si no es precio de Valencia, es precio_has_venta
            // Extraer el ID del precio_has_venta (formato: "precio_has_venta_123")
            $precioId = str_replace('precio_has_venta_', '', $tipoPrecio);

            // Buscar el precio seleccionado en los precios disponibles
            $precioSeleccionado = collect($producto['precios_disponibles'])->firstWhere('precio_id', $precioId);

            if (!$precioSeleccionado) {
                $this->dispatch('mostrar-error', ['mensaje' => 'Precio no encontrado']);
                return;
            }

            // Validar que el precio sea válido
            if ($precioSeleccionado->precio <= 0) {
                $this->dispatch('mostrar-error', ['mensaje' => 'El precio seleccionado no es válido']);
                return;
            }

            // Actualizar el producto con el nuevo precio y unidad de medida
            $this->productosFactura[$index]['precio'] = $precioSeleccionado->precio;
            $this->productosFactura[$index]['precio_id'] = $precioSeleccionado->precio_id;
            $this->productosFactura[$index]['unidad_medida_id'] = $precioSeleccionado->unidad_medida_id;
            $this->productosFactura[$index]['unidad_medida_nombre'] = $precioSeleccionado->unidad_nombre;
            $this->productosFactura[$index]['unidad_medida_simbolo'] = $precioSeleccionado->unidad_simbolo;
            $this->productosFactura[$index]['cantidad_por_unidad'] = $precioSeleccionado->cantidad;
            $this->productosFactura[$index]['tipo_precio'] = 'precio_has_venta';

            // NUEVO: Calcular stock total disponible para esta unidad de medida específica
            $stockEnBodega = $this->calcularStockTotalPorUnidad($producto['id'], $precioSeleccionado->unidad_medida_id);

            // Calcular cuánto hay en el carrito de este producto+unidad EXCLUYENDO esta línea
            $cantidadEnCarritoOtrasLineas = 0;
            foreach ($this->productosFactura as $i => $itemCarrito) {
                if ($i != $index &&
                    $itemCarrito['id'] == $producto['id'] &&
                    isset($itemCarrito['unidad_medida_id']) &&
                    $itemCarrito['unidad_medida_id'] == $precioSeleccionado->unidad_medida_id) {
                    $cantidadEnCarritoOtrasLineas += (int)($itemCarrito['cantidad'] ?? 0);
                }
            }

            // Stock real disponible para esta línea
            $stockDisponibleReal = $stockEnBodega - $cantidadEnCarritoOtrasLineas;

            // CRÍTICO: Si no hay stock disponible para esta unidad, NO permitir el cambio
            if ($stockDisponibleReal <= 0) {
                $this->dispatch('mostrar-error', [
                    'mensaje' => "No se puede cambiar a '{$precioSeleccionado->unidad_nombre}'. No hay stock disponible para esta unidad de medida. Todo el stock está en el carrito o agotado."
                ]);
                return; // Salir sin hacer cambios
            }

            // Si hay stock, proceder con el cambio
            $this->productosFactura[$index]['stock_total_unidad'] = $stockEnBodega;

            // Ajustar cantidad si excede el stock disponible real
            if ($cantidadActual > $stockDisponibleReal) {
                $this->productosFactura[$index]['cantidad'] = $stockDisponibleReal;
                $cantidadActual = $stockDisponibleReal;
                session()->flash('warning', "Cantidad ajustada a stock disponible: {$stockDisponibleReal} (considerando otras líneas del carrito)");
            }

            // Recalcular descuento unitario SOLO si ya tenía descuento aplicado
            $descuentoUnitarioAplicadoActual = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
            if ($descuentoUnitarioAplicadoActual > 0) {
                $descuentoUnitarioProducto = $producto['descuento_unitario_producto'] ?? 0;
                if ($descuentoUnitarioProducto > 0) {
                    $this->productosFactura[$index]['descuento_unitario_aplicado'] =
                        $descuentoUnitarioProducto * $precioSeleccionado->cantidad * $cantidadActual;
                }
            }
            // Si no tenía descuento, mantenerlo en 0

            // Recalcular subtotal con descuento
            $subtotalOriginal = $precioSeleccionado->precio * $cantidadActual;
            $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
            $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

            // Recalcular totales de la factura
            $this->calcularTotales();

            session()->flash('success', 'Unidad de medida actualizada correctamente');
            return;
        }

        // Sistema anterior: productos de Valencia con precio1, precio2, etc.
        $productoModel = Producto::find($producto['id']);
        if (!$productoModel) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Error al actualizar precio: producto no encontrado']);
            return;
        }

        // Determinar el nuevo precio según el tipo seleccionado
        $nuevoPrecio = 0;
        switch ($tipoPrecio) {
            case 'precio1':
                $nuevoPrecio = $productoModel->precio1 ?? 0;
                break;
            case 'precio2':
                $nuevoPrecio = $productoModel->precio2 ?? 0;
                break;
            case 'precio3':
                $nuevoPrecio = $productoModel->precio3 ?? 0;
                break;
            case 'precio4':
                $nuevoPrecio = $productoModel->precio4 ?? 0;
                break;
            case 'precio_base':
            default:
                $nuevoPrecio = $productoModel->precio_base ?? 0;
                break;
        }

        // Validar que el precio sea válido
        if ($nuevoPrecio <= 0) {
            $this->dispatch('mostrar-error', ['mensaje' => 'El precio seleccionado no está disponible']);
            return;
        }

        // Actualizar el precio en el carrito
        $this->productosFactura[$index]['precio'] = $nuevoPrecio;
        $this->productosFactura[$index]['tipo_precio'] = $tipoPrecio;

        // Recalcular subtotal con descuento para este item
        $cantidad = $this->productosFactura[$index]['cantidad'];
        $subtotalOriginal = $nuevoPrecio * $cantidad;
        $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
        $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

        // Recalcular totales de la factura
        $this->calcularTotales();

        // Mostrar mensaje de éxito
        session()->flash('success', 'Precio actualizado correctamente');
    }

    private function determinarPrecioPorDefecto($producto)
    {
        // SIEMPRE usar precio_base por defecto para todos los productos
        // tanto de Paperland como de Valencia cuando se agregan por código de barras
        return ['precio' => $producto->precio_base, 'tipo' => 'precio_base'];
    }

    public function modificarCantidad($index, $nuevaCantidad)
    {
        // Convertir a entero para evitar errores de tipos
        $nuevaCantidad = (int)$nuevaCantidad;

        if ($nuevaCantidad <= 0) {
            $this->eliminarProducto($index);
            return;
        }

        // Obtener el item actual
        $item = $this->productosFactura[$index];

        // Validar stock si es un producto (no servicio)
        $esServicio = isset($item['servicio_id']) && $item['servicio_id'] !== null;

        if (!$esServicio) {
            // NUEVO: Si tiene stock_total_unidad, validar contra ese valor
            if (isset($item['stock_total_unidad'])) {
                if ($nuevaCantidad > $item['stock_total_unidad']) {
                    $this->dispatch('mostrar-error', [
                        'mensaje' => "Stock insuficiente. Solo hay {$item['stock_total_unidad']} disponibles de esta unidad."
                    ]);
                    return;
                }
            } else {
                // Sistema anterior: validar con obtenerStockTotal
                $productoId = $item['id'];
                $stockTotal = $this->obtenerStockTotal($productoId);

                // Para el nuevo sistema, multiplicar por cantidad_por_unidad
                $cantidadRealNecesaria = $nuevaCantidad;
                if (isset($item['cantidad_por_unidad'])) {
                    $cantidadRealNecesaria = $nuevaCantidad * $item['cantidad_por_unidad'];
                }

                // Calcular cuánto hay en el carrito SIN incluir este item
                $cantidadEnCarritoSinEsteItem = 0;
                foreach ($this->productosFactura as $i => $itemCarrito) {
                    if ($itemCarrito['id'] == $productoId && $i != $index) {
                        $cantidadItem = (int)$itemCarrito['cantidad'];
                        // Multiplicar por cantidad_por_unidad si existe
                        if (isset($itemCarrito['cantidad_por_unidad'])) {
                            $cantidadItem *= $itemCarrito['cantidad_por_unidad'];
                        }
                        $cantidadEnCarritoSinEsteItem += $cantidadItem;
                    }
                }

                // La nueva cantidad total en unidades reales
                $nuevaCantidadTotal = $cantidadEnCarritoSinEsteItem + $cantidadRealNecesaria;

                if ($nuevaCantidadTotal > $stockTotal) {
                    $this->mostrarModalSinStock = true;
                    return;
                }
            }
        }

        // Actualizar la cantidad
        $this->productosFactura[$index]['cantidad'] = $nuevaCantidad;

        // Recalcular el descuento unitario aplicado con la nueva cantidad
        $descuentoUnitarioProducto = $item['descuento_unitario_producto'] ?? 0;
        if ($descuentoUnitarioProducto > 0) {
            // Para nuevo sistema: descuento × cantidad_por_unidad × cantidad
            if (isset($item['cantidad_por_unidad'])) {
                $this->productosFactura[$index]['descuento_unitario_aplicado'] =
                    $descuentoUnitarioProducto * $item['cantidad_por_unidad'] * $nuevaCantidad;
            } else {
                $this->productosFactura[$index]['descuento_unitario_aplicado'] =
                    $descuentoUnitarioProducto * $nuevaCantidad;
            }
        }

        // Recalcular subtotal con descuento para este item
        $subtotalOriginal = $item['precio'] * $nuevaCantidad;
        $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
        $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

        // Recalcular todos los totales
        $this->calcularTotales();

        // Forzar actualización de la vista
        $this->dispatch('$refresh');
    }

    /**
     * Método que se ejecuta automáticamente cuando cambia productosFactura mediante wire:model
     * Valida el stock y recalcula totales en tiempo real
     */
    public function updatedProductosFactura($value, $key)
    {
        // Extraer el índice y el campo que cambió
        // $key tiene formato: "0.cantidad" o "1.cantidad"
        $parts = explode('.', $key);

        if (count($parts) !== 2) {
            return;
        }

        $index = (int)$parts[0];
        $campo = $parts[1];

        // Solo procesar cambios en el campo 'cantidad'
        if ($campo !== 'cantidad') {
            return;
        }

        // Verificar que el índice exista
        if (!isset($this->productosFactura[$index])) {
            return;
        }

        $nuevaCantidad = (int)$value;
        $item = $this->productosFactura[$index];

        // Si la cantidad es 0 o negativa, eliminar el producto
        if ($nuevaCantidad <= 0) {
            $this->eliminarProducto($index);
            return;
        }

        // Validar stock si es un producto (no servicio)
        $esServicio = isset($item['servicio_id']) && $item['servicio_id'] !== null;

        if (!$esServicio) {
            // Si tiene stock_total_unidad, validar contra ese valor
            if (isset($item['stock_total_unidad']) && isset($item['unidad_medida_id'])) {
                // NUEVO: Calcular stock en bodega
                $stockEnBodega = $this->calcularStockTotalPorUnidad($item['id'], $item['unidad_medida_id']);

                // Calcular cuánto hay en el carrito EXCLUYENDO este item
                $cantidadEnCarritoOtrasLineas = 0;
                foreach ($this->productosFactura as $i => $itemCarrito) {
                    // Si es otra línea del mismo producto y misma unidad
                    if ($i != $index &&
                        $itemCarrito['id'] == $item['id'] &&
                        isset($itemCarrito['unidad_medida_id']) &&
                        $itemCarrito['unidad_medida_id'] == $item['unidad_medida_id']) {
                        $cantidadEnCarritoOtrasLineas += (int)($itemCarrito['cantidad'] ?? 0);
                    }
                }

                // Stock real disponible para esta línea
                $stockDisponibleReal = $stockEnBodega - $cantidadEnCarritoOtrasLineas;

                if ($nuevaCantidad > $stockDisponibleReal) {
                    // Limitar al stock disponible real
                    $this->productosFactura[$index]['cantidad'] = max(1, $stockDisponibleReal);

                    $this->dispatch('mostrar-error', [
                        'mensaje' => "Stock insuficiente. Solo hay {$stockDisponibleReal} disponibles (considerando otras líneas del carrito)."
                    ]);
                }

                // Actualizar el stock_total_unidad mostrado para esta línea
                $this->productosFactura[$index]['stock_total_unidad'] = $stockEnBodega;

                // IMPORTANTE: Actualizar stock_total_unidad en TODAS las líneas del mismo producto+unidad
                // para que todas muestren el mismo stock de bodega
                foreach ($this->productosFactura as $i => $itemCarrito) {
                    if ($itemCarrito['id'] == $item['id'] &&
                        isset($itemCarrito['unidad_medida_id']) &&
                        $itemCarrito['unidad_medida_id'] == $item['unidad_medida_id']) {
                        $this->productosFactura[$i]['stock_total_unidad'] = $stockEnBodega;
                    }
                }
            } else {
                // Sistema anterior: validar con obtenerStockTotal
                $productoId = $item['id'];
                $stockTotal = $this->obtenerStockTotal($productoId);

                $cantidadRealNecesaria = $nuevaCantidad;
                if (isset($item['cantidad_por_unidad'])) {
                    $cantidadRealNecesaria = $nuevaCantidad * $item['cantidad_por_unidad'];
                }

                // Calcular cuánto hay en el carrito SIN incluir este item
                $cantidadEnCarritoSinEsteItem = 0;
                foreach ($this->productosFactura as $i => $itemCarrito) {
                    if ($itemCarrito['id'] == $productoId && $i != $index) {
                        $cantidadItem = (int)$itemCarrito['cantidad'];
                        if (isset($itemCarrito['cantidad_por_unidad'])) {
                            $cantidadItem *= $itemCarrito['cantidad_por_unidad'];
                        }
                        $cantidadEnCarritoSinEsteItem += $cantidadItem;
                    }
                }

                $nuevaCantidadTotal = $cantidadEnCarritoSinEsteItem + $cantidadRealNecesaria;

                if ($nuevaCantidadTotal > $stockTotal) {
                    // Calcular cantidad máxima permitida
                    $cantidadMaxima = floor(($stockTotal - $cantidadEnCarritoSinEsteItem) / ($item['cantidad_por_unidad'] ?? 1));
                    $this->productosFactura[$index]['cantidad'] = max(1, $cantidadMaxima);

                    $this->mostrarModalSinStock = true;
                    $this->dispatch('mostrar-error', [
                        'mensaje' => "Stock insuficiente. Stock disponible: {$stockTotal}"
                    ]);
                }
            }
        }

        // Recalcular el descuento unitario aplicado SOLO si ya tenía descuento aplicado
        $descuentoUnitarioAplicadoActual = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
        if ($descuentoUnitarioAplicadoActual > 0) {
            // Si ya tenía descuento, recalcularlo proporcionalmente con la nueva cantidad
            $descuentoUnitarioProducto = $item['descuento_unitario_producto'] ?? 0;
            if ($descuentoUnitarioProducto > 0) {
                $this->productosFactura[$index]['descuento_unitario_aplicado'] = $descuentoUnitarioProducto * $this->productosFactura[$index]['cantidad'];
            }
        }
        // Si no tenía descuento, no aplicarlo automáticamente

        // Recalcular subtotal con descuento para este item
        $cantidad = $this->productosFactura[$index]['cantidad'];
        $subtotalOriginal = $item['precio'] * $cantidad;
        $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
        $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

        // Recalcular todos los totales
        $this->calcularTotales();
    }

    public function toggleProductosServicios()
    {
        $this->mostrarProductosServicios = !$this->mostrarProductosServicios;
        if ($this->mostrarProductosServicios) {
            $this->cargarProductosYServicios();
        }
    }

    public function calcularTotales()
    {
        $this->subtotal = 0;
        $this->subtotalBruto = 0; // Resetear subtotal bruto
        $this->totalIsv = 0;
        $this->totalDescuentos = 0;
        $isvPorTasa = []; // Agrupamos ISV por tasa

        foreach ($this->productosFactura as $index => $producto) {
            $subtotalProducto = round($producto['precio'] * $producto['cantidad'], 2);

            // Acumular subtotal bruto (cantidad * precio unitario sin descuentos)
            $this->subtotalBruto += $subtotalProducto;

            // Aplicar descuento unitario automático del producto primero (valor monetario)
            $descuentoUnitario = $producto['descuento_unitario_aplicado'] ?? 0;

            // Si el producto cambió de cantidad, recalcular el descuento unitario automático
            if (($producto['descuento_unitario_producto'] ?? 0) > 0) {
                // El descuento es un valor monetario que se multiplica por la cantidad
                $descuentoUnitario = ($producto['descuento_unitario_producto'] ?? 0) * $producto['cantidad'];
                $this->productosFactura[$index]['descuento_unitario_aplicado'] = $descuentoUnitario;
            }

            // Aplicar el descuento unitario al subtotal
            $subtotalConDescuentoUnitario = $subtotalProducto - $descuentoUnitario;

            // Aplicar descuento individual por producto (porcentaje)
            $descuentoIndividual = 0;
            if (isset($producto['porcentaje_descuento']) && $producto['porcentaje_descuento'] > 0) {
                $descuentoIndividual = $subtotalProducto * ($producto['porcentaje_descuento'] / 100);
                $this->productosFactura[$index]['descuento_monto'] = $descuentoIndividual;
            }

            // Aplicar descuentos por edad al subtotal ORIGINAL (sin descuento unitario aplicado)
            $descuentoProducto = 0;

            // Verificar descuento de tercera edad (25%) - solo si el producto lo permite
            if ($this->descuentoTerceraEdad && ($producto['descuento_tercera'] ?? 0) == 1) {
                $descuentoProducto = $subtotalProducto * 0.25; // 25% sobre precio original
            }
            // Verificar descuento de cuarta edad (35%) - solo si el producto lo permite y no hay descuento de tercera edad
            elseif ($this->descuentoCuartaEdad && ($producto['descuento_cuarta'] ?? 0) == 1) {
                $descuentoProducto = $subtotalProducto * 0.35; // 35% sobre precio original
            }

            // Calcular subtotal final restando todos los descuentos del subtotal original
            $subtotalConDescuento = $subtotalProducto - $descuentoUnitario - $descuentoIndividual - $descuentoProducto;
            $this->subtotal += $subtotalConDescuento;

            // Sumar todos los tipos de descuentos al total de descuentos
            $this->totalDescuentos += ($descuentoUnitario + $descuentoIndividual + $descuentoProducto);

            // Actualizar el producto con la información de los descuentos aplicados
            $this->productosFactura[$index]['descuento_aplicado'] = $descuentoProducto;
            $this->productosFactura[$index]['descuento_individual_aplicado'] = $descuentoIndividual;
            $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalConDescuento;

            // Calcular ISV sobre el subtotal con descuento
            $tasaIsv = $producto['isv'];
            $isvProducto = round($subtotalConDescuento * ($tasaIsv / 100), 2);
            $this->totalIsv = round($this->totalIsv + $isvProducto, 2);

            // IMPORTANTE: Guardar el monto del ISV calculado en el producto
            $this->productosFactura[$index]['isv_calculado'] = $isvProducto;

            // Actualizar el total del producto en el array (subtotal + ISV)
            $this->productosFactura[$index]['total'] = $subtotalConDescuento + $isvProducto;

            // Agrupar ISV por tasa
            if (!isset($isvPorTasa[$tasaIsv])) {
                $isvPorTasa[$tasaIsv] = 0;
            }
            $isvPorTasa[$tasaIsv] += $isvProducto;
        }

        // Asegurar que todos los valores tengan exactamente 2 decimales
        $this->isvPorTasa = array_map(function($monto) {
            return (float)number_format($monto, 2, '.', '');
        }, $isvPorTasa);

        $this->subtotal = (float)number_format($this->subtotal, 2, '.', '');
        $this->totalIsv = (float)number_format($this->totalIsv, 2, '.', '');
        $this->total = (float)number_format($this->subtotal + $this->totalIsv, 2, '.', '');

        // Forzar actualización de la vista
        $this->dispatch('totales-actualizados', [
            'subtotal' => $this->subtotal,
            'totalIsv' => $this->totalIsv,
            'total' => $this->total,
            'totalDescuentos' => $this->totalDescuentos
        ]);
    }

    // Métodos para manejar descuentos por edad
    public function aplicarDescuentoTerceraEdad()
    {
        // Si ya hay un descuento de cuarta edad activo, no permitir
        if ($this->descuentoCuartaEdad) {
            session()->flash('warning', 'Ya hay un descuento de cuarta edad aplicado. Solo se permite un descuento por edad a la vez.');
            return;
        }

        // Verificar que hay productos en la factura
        if (empty($this->productosFactura)) {
            session()->flash('error', 'No hay productos en la factura para aplicar el descuento.');
            return;
        }

        // Si el descuento ya está activo, removerlo
        if ($this->descuentoTerceraEdad) {
            $this->descuentoTerceraEdad = false;
            $this->datosDescuentoAdulto = []; // Limpiar datos en memoria
            $this->calcularTotales();
            session()->flash('success', 'Descuento de tercera edad removido');
            return;
        }

        // Verificar que hay productos elegibles para descuento de tercera edad
        $productosElegibles = collect($this->productosFactura)->filter(function($producto) {
            return ($producto['descuento_tercera'] ?? 0) == 1;
        });

        if ($productosElegibles->isEmpty()) {
            session()->flash('warning', 'Ningún producto en la factura permite descuento de tercera edad.');
            return;
        }

        // Abrir modal para capturar datos del adulto mayor
        $this->tipoDescuentoActual = 'tercera';
        $this->mostrarModalDescuentoAdulto = true;
    }

    public function aplicarDescuentoCuartaEdad()
    {
        // Si ya hay un descuento de tercera edad activo, no permitir
        if ($this->descuentoTerceraEdad) {
            session()->flash('warning', 'Ya hay un descuento de tercera edad aplicado. Solo se permite un descuento por edad a la vez.');
            return;
        }

        // Verificar que hay productos en la factura
        if (empty($this->productosFactura)) {
            session()->flash('error', 'No hay productos en la factura para aplicar el descuento.');
            return;
        }

        // Si el descuento ya está activo, removerlo
        if ($this->descuentoCuartaEdad) {
            $this->descuentoCuartaEdad = false;
            $this->datosDescuentoAdulto = []; // Limpiar datos en memoria
            $this->calcularTotales();
            session()->flash('success', 'Descuento de cuarta edad removido');
            return;
        }

        // Verificar que hay productos elegibles para descuento de cuarta edad
        $productosElegibles = collect($this->productosFactura)->filter(function($producto) {
            return ($producto['descuento_cuarta'] ?? 0) == 1;
        });

        if ($productosElegibles->isEmpty()) {
            session()->flash('warning', 'Ningún producto en la factura permite descuento de cuarta edad.');
            return;
        }

        // Abrir modal para capturar datos del adulto mayor
        $this->tipoDescuentoActual = 'cuarta';
        $this->mostrarModalDescuentoAdulto = true;
    }

    // Funciones para manejar el modal de descuento de adulto mayor
    public function cerrarModalDescuentoAdulto()
    {
        $this->mostrarModalDescuentoAdulto = false;
        $this->tipoDescuentoActual = null;
        $this->limpiarDatosModalAdulto();
    }

    public function limpiarDatosModalAdulto()
    {
        $this->dniAdulto = '';
        $this->nombreAdulto = '';
        $this->edadAdulto = null;
    }

    public function confirmarDescuentoAdulto()
    {
        // Validar campos requeridos
        if (empty($this->dniAdulto) || empty($this->nombreAdulto) || empty($this->edadAdulto)) {
            session()->flash('error', 'Todos los campos son obligatorios');
            return;
        }

        // Validar edad según el tipo de descuento
        if ($this->tipoDescuentoActual === 'tercera' && ($this->edadAdulto < 60 || $this->edadAdulto > 64)) {
            session()->flash('error', 'Para descuento de tercera edad, la edad debe estar entre 60 y 64 años');
            return;
        }

        if ($this->tipoDescuentoActual === 'cuarta' && $this->edadAdulto < 65) {
            session()->flash('error', 'Para descuento de cuarta edad, la edad debe ser de 65 años o más');
            return;
        }

        // Guardar datos en memoria
        $this->datosDescuentoAdulto = [
            'dni' => $this->dniAdulto,
            'nombre' => $this->nombreAdulto,
            'edad' => $this->edadAdulto,
            'tipo_descuento' => $this->tipoDescuentoActual
        ];

        // Aplicar el descuento correspondiente
        if ($this->tipoDescuentoActual === 'tercera') {
            $this->descuentoTerceraEdad = true;
            $porcentaje = 25;
        } else {
            $this->descuentoCuartaEdad = true;
            $porcentaje = 35;
        }

        $this->calcularTotales();

        // Mensaje de éxito
        $tipoTexto = $this->tipoDescuentoActual === 'tercera' ? 'tercera' : 'cuarta';
        session()->flash('success', "Descuento del {$porcentaje}% para {$tipoTexto} edad aplicado correctamente para {$this->nombreAdulto}");

        // Cerrar modal
        $this->cerrarModalDescuentoAdulto();
    }

    // Métodos para descuento por producto
    public function mostrarModalDescuentoProducto($indice)
    {
        // Validar que el índice sea válido
        if (!isset($this->productosFactura[$indice])) {
            session()->flash('error', 'Producto no encontrado');
            return;
        }

        $this->indiceProductoSeleccionado = $indice;
        $this->productoSeleccionadoDescuento = $this->productosFactura[$indice];
        $this->porcentajeDescuentoProducto = $this->productoSeleccionadoDescuento['porcentaje_descuento'] ?? 0;
        $this->modalDescuentoProductoVisible = true;
    }

    public function aplicarDescuentoProducto()
    {
        // Validaciones
        if ($this->porcentajeDescuentoProducto < 0 || $this->porcentajeDescuentoProducto > 100) {
            session()->flash('error', 'El porcentaje de descuento debe estar entre 0 y 100');
            return;
        }

        if ($this->indiceProductoSeleccionado === null || !isset($this->productosFactura[$this->indiceProductoSeleccionado])) {
            session()->flash('error', 'Producto no válido para aplicar descuento');
            return;
        }

        // Aplicar el descuento al producto
        $producto = &$this->productosFactura[$this->indiceProductoSeleccionado];

        // Guardar el porcentaje de descuento
        $producto['porcentaje_descuento'] = $this->porcentajeDescuentoProducto;

        // Calcular el descuento basado en el precio unitario (lógica original)
        $precioUnitario = $producto['precio'];
        $descuentoPorUnidad = $precioUnitario * ($this->porcentajeDescuentoProducto / 100);
        $producto['descuento_monto'] = $descuentoPorUnidad; // Solo el descuento por unidad

        // El descuento se aplica al subtotal actual (precio × cantidad)
        $subtotalActual = $producto['cantidad'] * $producto['precio'];
        $producto['total'] = $subtotalActual - $descuentoPorUnidad;

        // Recalcular totales generales
        $this->calcularTotales();

        // Mensaje de éxito
        $nombreProducto = $producto['nombre'];
        session()->flash('success', "Descuento del {$this->porcentajeDescuentoProducto}% (basado en precio unitario) aplicado a {$nombreProducto}");

        // Cerrar modal y limpiar datos
        $this->cerrarModalDescuentoProducto();
    }

    public function cerrarModalDescuentoProducto()
    {
        $this->modalDescuentoProductoVisible = false;
        $this->indiceProductoSeleccionado = null;
        $this->productoSeleccionadoDescuento = null;
        $this->porcentajeDescuentoProducto = 0;
    }

    // Método para resetear completamente la factura
    public function resetearFactura()
    {
        $this->cliente = null;
        $this->modoClienteManual = false;
        $this->limpiarCamposManual();
        $this->productosFactura = [];
        $this->descuentoTerceraEdad = false;
        $this->descuentoCuartaEdad = false;
        $this->totalDescuentos = 0;
        $this->datosDescuentoAdulto = []; // Limpiar datos del adulto mayor

        // Limpiar datos del descuento por producto
        $this->cerrarModalDescuentoProducto();

        $this->calcularTotales();
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

    // Métodos para procesamiento de pagos
    public function mostrarModalPago()
    {
        if (count($this->productosFactura) === 0) {
            session()->flash('error', 'Debe agregar al menos un producto para procesar el pago');
            return;
        }

        // Validar jornada y caja antes de permitir procesar pago
        if (!$this->validarJornadaYCaja()) {
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

    /**
     * Validar que la jornada esté abierta y la caja esté abierta
     */
    private function validarJornadaYCaja()
    {
        $user = Auth::user();
        $tiendaId = $user->tienda_id;

        // 1. Verificar jornada (apertura = 1 y cierre = 0)
        $jornadaAbierta = DB::table('jornada')
            ->where('tienda_id', $tiendaId)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->whereDate('fecha', now()->toDateString())
            ->exists();

        if (!$jornadaAbierta) {
            session()->flash('error', '❌ No se puede procesar la venta: La jornada debe estar abierta para realizar ventas.');
            return false;
        }

        // 2. Verificar caja del usuario (que esté abierta Y tenga apertura para el día de la jornada)
        $cajaAbierta = DB::table('caja')
            ->where('users_id', $user->id)
            ->where('tienda_id', $tiendaId)
            ->where('estado_caja', 1) // 1 = abierta
            ->first();

        if (!$cajaAbierta) {
            session()->flash('error', '❌ No se puede procesar la venta: Su caja debe estar abierta para realizar ventas.');
            return false;
        }

        // 3. Verificar que la caja tenga apertura para la fecha de la jornada abierta
        $fechaJornadaAbierta = DB::table('jornada')
            ->where('tienda_id', $tiendaId)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->value('fecha');

        $tieneAperturaCaja = DB::table('apertura_caja')
            ->where('caja_id', $cajaAbierta->id)
            ->whereDate('fecha_apertura', $fechaJornadaAbierta)
            ->exists();

        if (!$tieneAperturaCaja) {
            session()->flash('error', '❌ No se puede procesar la venta: Su caja debe tener apertura para la fecha de la jornada activa (' . \Carbon\Carbon::parse($fechaJornadaAbierta)->format('d/m/Y') . ').');
            return false;
        }

        return true;
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
        $totalRedondeado = round($this->total, 2); // Asegurar que el total esté redondeado

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
        $totalDistribuido = round(array_sum($this->montosPorMetodo), 2);

        Log::info("DEBUG Validación distribución", [
            'total_distribuido' => $totalDistribuido,
            'total_factura' => round($this->total, 2),
            'diferencia' => round($totalDistribuido - $this->total, 2)
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
                        'monto' => round($monto, 2)
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
        // Prevenir procesamiento duplicado
        if ($this->procesandoVenta) {
            Log::warning("Intento de procesamiento duplicado detectado");
            return;
        }

        $this->procesandoVenta = true;

        Log::info("DEBUG finalizarVentaConDistribucion INICIO");

        // IMPORTANTE: Obtener los valores del cliente AL INICIO para evitar que se pierdan
        $nombreClienteParaFactura = $this->obtenerNombreCliente();
        $rtnClienteParaFactura = $this->obtenerRtnCliente();

        Log::info("DEBUG Valores de cliente capturados al inicio", [
            'rtnManual_crudo' => $this->rtnManual,
            'nombreClienteManual_crudo' => $this->nombreClienteManual,
            'cliente_objeto' => $this->cliente ? [
                'id' => $this->cliente->id ?? 'N/A',
                'nombre_completo' => $this->cliente->nombre_completo ?? 'N/A',
                'rtn' => $this->cliente->rtn ?? 'N/A'
            ] : 'No hay cliente seleccionado',
            'nombre_cliente_capturado' => $nombreClienteParaFactura,
            'rtn_cliente_capturado' => $rtnClienteParaFactura,
            'es_vacio_nombre' => empty($nombreClienteParaFactura),
            'es_vacio_rtn' => empty($rtnClienteParaFactura)
        ]);

        try {
            // VALIDACIÓN CAI ANTES DE FACTURAR
            $caiService = new CAIService();
            $validacionCAI = $caiService->validarCAIParaTienda($this->tiendaUsuario);

            if (!$validacionCAI['valido']) {
                $this->procesandoVenta = false;
                session()->flash('error', '❌ No se puede facturar: ' . $validacionCAI['mensaje']);
                $this->dispatch('mostrar-error-cai', [
                    'titulo' => 'Error de CAI',
                    'mensaje' => $validacionCAI['mensaje'],
                    'detalle' => $validacionCAI['detalle']
                ]);
                return;
            }

            // Actualizar información de CAI actual
            $this->caiActual = $validacionCAI['cai_info'];

            DB::beginTransaction();

            Log::info("DEBUG Transacción iniciada");

            // Generar número de factura
            $numeroFactura = $this->generarNumeroFactura();

            // Crear la transacción primero y obtener su ID
            $transaccionId = $this->crearTransaccion($numeroFactura);

            Log::info("DEBUG Resultado de crearTransaccion", [
                'transaccion_id_retornado' => $transaccionId,
                'es_null' => $transaccionId === null,
                'metodosActivosParaPago' => $this->metodosActivosParaPago,
                'montosPorMetodo' => $this->montosPorMetodo
            ]);

            // Si no hay transaccion_id, crear una transacción por defecto
            if ($transaccionId === null) {
                Log::warning("DEBUG Transacción fue null, creando transacción por defecto");
                $transaccionId = DB::table('transaccion')->insertGetId([
                    'caja_id' => 1, // Valor por defecto
                    'efectivo' => $this->total,
                    'tarjeta' => 0,
                    'cheque' => 0,
                    'transferencia' => 0,
                    'transaccion' => 'Facturacion',
                    'descripcion' => "Factura #$numeroFactura (transacción por defecto)",
                    'created_at' => now(),
                    'update_at' => now()
                ]);
                Log::info("DEBUG Transacción por defecto creada con ID: " . $transaccionId);
            }

            // Debug detallado antes de crear la factura
            $datosFactura = [
                'numero_factura' => $numeroFactura,
                'cai_id' => $this->caiActual ? $this->caiActual['cai_id'] : 1,
                'tipo_facturacion_id' => 1,
                'transaccion_id' => $transaccionId,
                'nombre_cliente' => $nombreClienteParaFactura,
                'rtn' => $rtnClienteParaFactura,
                'sub_total' => $this->subtotal,
                'sub_total_grabado' => $this->subtotal,
                'sub_total_exento' => 0,
                'isv' => $this->totalIsv,
                'total' => $this->total,
                'credito' => 0,
                'fecha_emision' => now()->format('Y-m-d'),
                'estado_factura_id' => 1,
                'users_id' => Auth::id()
            ];

            Log::info("DEBUG Datos que se van a insertar en factura", $datosFactura);

            // Crear la factura principal usando los valores capturados al inicio
            $factura = Factura::create($datosFactura);

            Log::info("DEBUG Datos de factura preparados", [
                'numero_factura' => $factura->numero_factura,
                'nombre_cliente' => $factura->nombre_cliente,
                'rtn' => $factura->rtn,
                'total' => $factura->total,
                'user_id' => $factura->users_id,
                'transaccion_id' => $factura->transaccion_id
            ]);

            // Debug adicional: verificar qué se guardó realmente en la BD
            $facturaVerificacion = DB::table('factura')->where('id', $factura->id)->first(['id', 'nombre_cliente', 'rtn', 'transaccion_id']);
            Log::info("DEBUG Verificación de factura en BD", [
                'factura_id' => $facturaVerificacion->id,
                'nombre_cliente_bd' => $facturaVerificacion->nombre_cliente,
                'rtn_bd' => $facturaVerificacion->rtn,
                'transaccion_id_bd' => $facturaVerificacion->transaccion_id
            ]);

            Log::info("DEBUG Factura guardada con ID: " . $factura->id);

            // Separar productos y servicios para procesamiento diferenciado
            $productos = array_filter($this->productosFactura, function($item) {
                return !isset($item['servicio_id']) || $item['servicio_id'] === null;
            });

            $servicios = array_filter($this->productosFactura, function($item) {
                return isset($item['servicio_id']) && $item['servicio_id'] !== null;
            });

            // Guardar productos de la factura con distribución FIFO por secciones
            $indice = 1;
            foreach ($productos as $producto) {
                $this->guardarProductoConDistribucionSecciones($factura->id, $producto, $indice);

                // Log para debug del producto
                Log::info("DEBUG Producto en factura", [
                    'producto_id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'descuento_unitario_aplicado' => $producto['descuento_unitario_aplicado'] ?? 0,
                    'descuento_unitario_producto' => $producto['descuento_unitario_producto'] ?? 0
                ]);

                // Guardar descuento unitario si existe
                $descuentoUnitario = $producto['descuento_unitario_aplicado'] ?? 0;
                if ($descuentoUnitario > 0) {
                    Log::info("DEBUG Creando descuento", [
                        'factura_id' => $factura->id,
                        'producto_id' => $producto['id'],
                        'monto_unidad' => $producto['descuento_unitario_producto'] ?? 0,
                        'monto_total' => $descuentoUnitario,
                        'users_id' => Auth::id()
                    ]);

                    Descuento::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $producto['id'],
                        'Tipo_descuento' => 'Producto',
                        'monto_unidad' => $producto['descuento_unitario_producto'] ?? 0,
                        'monto_total' => $descuentoUnitario,
                        'indice_factura_has_producto' => $indice,
                        'users_id' => Auth::id(),
                        'created_at' => now()
                    ]);

                    Log::info("DEBUG Descuento creado exitosamente");
                } else {
                    Log::info("DEBUG No se creó descuento porque descuentoUnitario es 0 o null");
                }

                // Guardar descuento individual si existe
                $descuentoIndividual = $producto['descuento_individual_aplicado'] ?? 0;
                if ($descuentoIndividual > 0) {
                    Log::info("DEBUG Creando descuento individual", [
                        'factura_id' => $factura->id,
                        'producto_id' => $producto['id'],
                        'tipo_descuento' => 'Individual',
                        'monto_total' => $descuentoIndividual,
                        'users_id' => Auth::id()
                    ]);

                    Descuento::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $producto['id'],
                        'Tipo_descuento' => 'Individual',
                        'monto_unidad' => 0,
                        'monto_total' => $descuentoIndividual,
                        'indice_factura_has_producto' => $indice,
                        'users_id' => Auth::id(),
                        'created_at' => now()
                    ]);

                    Log::info("DEBUG Descuento individual creado exitosamente");
                } else {
                    Log::info("DEBUG No se creó descuento individual porque es 0 o null");
                }

                // Guardar descuento de adulto mayor si existe
                $descuentoAdultoMayor = $producto['descuento_aplicado'] ?? 0;
                if ($descuentoAdultoMayor > 0) {
                    // Determinar tipo de descuento basado en los flags activos
                    $tipoDescuentoAdultoMayor = '';
                    if ($this->descuentoTerceraEdad) {
                        $tipoDescuentoAdultoMayor = '3ra edad';
                    } elseif ($this->descuentoCuartaEdad) {
                        $tipoDescuentoAdultoMayor = '4ta edad';
                    }

                    if ($tipoDescuentoAdultoMayor) {
                        Log::info("DEBUG Creando descuento de adulto mayor", [
                            'factura_id' => $factura->id,
                            'producto_id' => $producto['id'],
                            'tipo_descuento' => $tipoDescuentoAdultoMayor,
                            'monto_total' => $descuentoAdultoMayor,
                            'users_id' => Auth::id()
                        ]);

                        Descuento::create([
                            'factura_id' => $factura->id,
                            'producto_id' => $producto['id'],
                            'Tipo_descuento' => $tipoDescuentoAdultoMayor,
                            'monto_unidad' => 0, // Los descuentos de adulto mayor no tienen monto_unidad
                            'monto_total' => $descuentoAdultoMayor,
                            'indice_factura_has_producto' => $indice,
                            'users_id' => Auth::id(),
                            'created_at' => now()
                        ]);

                        Log::info("DEBUG Descuento de adulto mayor creado exitosamente");
                    }
                }

                $indice++;
            }

            // IMPORTANTE: Actualizar el campo descuento en factura_has_producto con la suma de todos los descuentos por índice
            $descuentosPorIndice = DB::table('descuentos')
                ->select('factura_id', 'producto_id', 'indice_factura_has_producto', DB::raw('SUM(monto_total) as descuento_total'))
                ->where('factura_id', $factura->id)
                ->groupBy('factura_id', 'producto_id', 'indice_factura_has_producto')
                ->get();

            foreach ($descuentosPorIndice as $descuento) {
                DB::table('factura_has_producto')
                    ->where('factura_id', $descuento->factura_id)
                    ->where('producto_id', $descuento->producto_id)
                    ->where('indice', $descuento->indice_factura_has_producto)
                    ->update(['descuento' => $descuento->descuento_total]);

                Log::info("DEBUG Descuento actualizado en factura_has_producto", [
                    'factura_id' => $descuento->factura_id,
                    'producto_id' => $descuento->producto_id,
                    'indice' => $descuento->indice_factura_has_producto,
                    'descuento_total' => $descuento->descuento_total
                ]);
            }

            // Guardar servicios de la factura (nueva funcionalidad híbrida)
            foreach ($servicios as $servicio) {
                Log::info("DEBUG Servicio en factura", [
                    'servicio_id' => $servicio['servicio_id'],
                    'nombre' => $servicio['nombre'],
                    'cantidad' => $servicio['cantidad'],
                    'precio' => $servicio['precio'],
                    'subtotal_con_descuento' => $servicio['subtotal_con_descuento'] ?? 0
                ]);

                // Calcular valores para el servicio
                $subtotalOriginal = round($servicio['cantidad'] * $servicio['precio'], 2);
                $descuentoAplicado = round($servicio['descuento_aplicado'] ?? 0, 2);
                $subtotalConDescuento = round($servicio['subtotal_con_descuento'] ?? $subtotalOriginal, 2);
                $isvAplicado = round($servicio['isv'] ?? 0, 2);
                $isvCalculado = round($subtotalConDescuento * ($isvAplicado / 100), 2);
                $totalFinal = round($subtotalConDescuento + $isvCalculado, 2);

                // Crear registro en factura_has_producto (usamos la misma tabla pero con servicio_id)
                DB::table('factura_has_producto')->insert([
                    'factura_id' => $factura->id,
                    'producto_id' => null, // NULL para servicios
                    'Servicios_id' => $servicio['servicio_id'], // ID del servicio
                    'seccion_id' => null, // Los servicios no tienen secciones
                    'unidad_medida_id' => $this->obtenerUnidadMedidaDisponible(),
                    'indice' => $indice, // Continuar numeración después de productos
                    'numero_unidades_resta_inventario' => 0, // Los servicios no afectan inventario
                    'unidades_nota_credito_resta_inventario' => 0,
                    'resta_inventario_total' => 0,
                    'precio_unidad' => $servicio['precio'],
                    'cantidad' => $servicio['cantidad'],
                    'subtotal' => $subtotalConDescuento,
                    'descuento' => $descuentoAplicado,
                    'isv_aplicado' => $isvAplicado,
                    'isv' => $isvCalculado,
                    'total' => $totalFinal,
                    'idPrecioSeleccionado' => '0',
                    'precio_seleccionado' => 0
                ]);

                // Crear descuento unitario para servicio si aplica
                $descuentoUnitario = $servicio['descuento_unitario_aplicado'] ?? 0;
                if ($descuentoUnitario > 0) {
                    // TODO: Agregar campo servicio_id a tabla descuentos para servicios
                    /* Descuento::create([
                        'factura_id' => $factura->id,
                        'producto_id' => null, // NULL porque es servicio
                        'Tipo_descuento' => 'Servicio',
                        'monto_unidad' => $servicio['descuento_unitario_producto'] ?? 0,
                        'monto_total' => $descuentoUnitario,
                        'users_id' => Auth::id(),
                        'created_at' => now()
                    ]); */

                    Log::info("DEBUG Descuento de servicio omitido (estructura de tabla pendiente)");
                }

                // Crear descuento de adulto mayor para servicio si aplica
                $descuentoAdultoMayor = $servicio['descuento_aplicado'] ?? 0;
                if ($descuentoAdultoMayor > 0) {
                    $tipoDescuentoAdultoMayor = '';
                    if ($this->descuentoTerceraEdad) {
                        $tipoDescuentoAdultoMayor = '3ra edad';
                    } elseif ($this->descuentoCuartaEdad) {
                        $tipoDescuentoAdultoMayor = '4ta edad';
                    }

                    if ($tipoDescuentoAdultoMayor) {
                        // TODO: Agregar campo servicio_id a tabla descuentos para servicios
                        /* Descuento::create([
                            'factura_id' => $factura->id,
                            'producto_id' => null,
                            'Tipo_descuento' => $tipoDescuentoAdultoMayor,
                            'monto_unidad' => 0,
                            'monto_total' => $descuentoAdultoMayor,
                            'users_id' => Auth::id(),
                            'created_at' => now()
                        ]); */

                        Log::info("DEBUG Descuento de adulto mayor para servicio omitido (estructura de tabla pendiente)");
                    }
                }

                $indice++;
            }

            // Guardar métodos de pago usando la distribución
            $this->guardarMetodosPagoDistribucion($factura->id);

            // La transacción ya fue registrada al crear la factura
            // $this->registrarTransaccionesPorMetodoPago($factura->id, $factura->numero_factura);

            // Guardar datos del descuento de adulto mayor si aplica
            $this->guardarDescuentoAdultoMayor($factura->id);

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

            // Resetear bandera de procesamiento
            $this->procesandoVenta = false;

        } catch (\Exception $e) {
            DB::rollBack();

            // Resetear bandera de procesamiento en caso de error
            $this->procesandoVenta = false;

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

    /**
     * Crear transacción y retornar el ID generado
     */
    private function crearTransaccion($numeroFactura)
    {
        Log::info("DEBUG crearTransaccion INICIO", [
            'numero_factura' => $numeroFactura
        ]);

        $user = Auth::user();

        // Obtener el ID de la caja del usuario en su tienda actual
        $caja = DB::table('caja')
            ->where('users_id', $user->id)
            ->where('tienda_id', $user->tienda_id)
            ->where('estado_caja', 1)
            ->first();

        if (!$caja) {
            Log::error("No se encontró caja abierta para el usuario: " . $user->id);
            return null;
        }

        $cajaId = $caja->id;

        // Inicializar montos de cada método de pago
        $montoEfectivo = 0;
        $montoTarjeta = 0;
        $montoCheque = 0;
        $montoTransferencia = 0;

        $metodosParaRegistrar = [];

        // Obtener métodos activos con monto > 0
        if (!empty($this->metodosActivosParaPago)) {
            foreach ($this->metodosActivosParaPago as $metodo) {
                if ($metodo['monto'] > 0) {
                    $metodosParaRegistrar[] = $metodo;
                }
            }
        } elseif (!empty($this->montosPorMetodo)) {
            foreach ($this->montosPorMetodo as $tipoId => $monto) {
                $tipoPago = collect($this->tiposPago)->firstWhere('id', $tipoId);
                if ($tipoPago && $monto > 0) {
                    $metodosParaRegistrar[] = [
                        'id' => $tipoId,
                        'nombre' => $tipoPago['nombre'],
                        'monto' => $monto
                    ];
                }
            }
        }

        // Acumular montos por tipo de pago
        foreach ($metodosParaRegistrar as $metodo) {
            $tipoPago = TipoPago::find($metodo['id']);
            if (!$tipoPago) continue;

            $montoMetodo = $metodo['monto'];
            $nombreTipoPago = strtolower($tipoPago->nombre);

            switch ($nombreTipoPago) {
                case 'efectivo':
                    $montoEfectivo += $montoMetodo;
                    break;
                case 'tarjeta':
                case 'tarjeta(pos)':
                case 'pos':
                    $montoTarjeta += $montoMetodo;
                    break;
                case 'cheque':
                    $montoCheque += $montoMetodo;
                    break;
                case 'transferencia':
                case 'transferencia bancaria':
                case 'transferencia_bancaria':
                    $montoTransferencia += $montoMetodo;
                    break;
                default:
                    // Para otros tipos de pago, intentar identificar el tipo por palabras clave
                    if (str_contains($nombreTipoPago, 'tarjeta') || str_contains($nombreTipoPago, 'pos')) {
                        $montoTarjeta += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'transfer')) {
                        $montoTransferencia += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'cheque')) {
                        $montoCheque += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'efectivo')) {
                        $montoEfectivo += $montoMetodo;
                    } else {
                        // Por defecto, asignar a transferencia
                        $montoTransferencia += $montoMetodo;
                    }
                    break;
            }
        }

        // Calcular el cambio total para restar del efectivo
        $totalDistribuido = $montoEfectivo + $montoTarjeta + $montoCheque + $montoTransferencia;
        $cambioTotal = $totalDistribuido > $this->total ? $totalDistribuido - $this->total : 0;

        // El efectivo neto es el monto efectivo menos el cambio (ya que el cambio sale de caja)
        $efectivoNeto = $montoEfectivo - $cambioTotal;

        // Crear registro de transacción y obtener el ID
        $transaccionId = null;
        if ($efectivoNeto > 0 || $montoTarjeta > 0 || $montoCheque > 0 || $montoTransferencia > 0) {
            $transaccionId = DB::table('transaccion')->insertGetId([
                'caja_id' => $cajaId,
                'efectivo' => $efectivoNeto, // Efectivo neto (sin incluir el cambio)
                'tarjeta' => $montoTarjeta,
                'cheque' => $montoCheque,
                'transferencia' => $montoTransferencia,
                'transaccion' => 'Facturacion',
                'descripcion' => "Factura #$numeroFactura",
                'created_at' => now(),
                'update_at' => now()
            ]);

            Log::info("DEBUG Transacción creada", [
                'transaccion_id' => $transaccionId,
                'numero_factura' => $numeroFactura,
                'efectivo_recibido' => $montoEfectivo,
                'cambio_calculado' => $cambioTotal,
                'efectivo_neto' => $efectivoNeto,
                'tarjeta' => $montoTarjeta,
                'cheque' => $montoCheque,
                'transferencia' => $montoTransferencia,
                'total_factura' => $this->total,
                'total_distribuido' => $totalDistribuido
            ]);

            // Actualizar balance de caja con el efectivo neto (sin incluir el cambio)
            if ($efectivoNeto > 0) {
                $this->actualizarBalanceCaja($efectivoNeto, $cajaId);
            }
        }

        Log::info("DEBUG crearTransaccion FINALIZADO con ID: " . $transaccionId);
        return $transaccionId;
    }

    /**
     * Registrar transacciones por método de pago y actualizar balance de caja si hay efectivo
     */
    private function registrarTransaccionesPorMetodoPago($facturaId, $numeroFactura)
    {
        Log::info("DEBUG registrarTransaccionesPorMetodoPago INICIO", [
            'factura_id' => $facturaId,
            'numero_factura' => $numeroFactura
        ]);

        $user = Auth::user();

        // Obtener el ID de la caja del usuario en su tienda actual
        $caja = DB::table('caja')
            ->where('users_id', $user->id)
            ->where('tienda_id', $user->tienda_id)
            ->where('estado_caja', 1)
            ->first();

        if (!$caja) {
            Log::error("No se encontró caja abierta para el usuario: " . $user->id);
            return;
        }

        $cajaId = $caja->id;

        // Inicializar montos de cada método de pago
        $montoEfectivo = 0;
        $montoTarjeta = 0;
        $montoCheque = 0;
        $montoTransferencia = 0;

        $metodosParaRegistrar = [];

        // Obtener métodos activos con monto > 0
        if (!empty($this->metodosActivosParaPago)) {
            foreach ($this->metodosActivosParaPago as $metodo) {
                if ($metodo['monto'] > 0) {
                    $metodosParaRegistrar[] = $metodo;
                }
            }
        } elseif (!empty($this->montosPorMetodo)) {
            foreach ($this->montosPorMetodo as $tipoId => $monto) {
                $tipoPago = collect($this->tiposPago)->firstWhere('id', $tipoId);
                if ($tipoPago && $monto > 0) {
                    $metodosParaRegistrar[] = [
                        'id' => $tipoId,
                        'nombre' => $tipoPago['nombre'],
                        'monto' => $monto
                    ];
                }
            }
        }

        // Acumular montos por tipo de pago (sin actualizar balance aún)
        foreach ($metodosParaRegistrar as $metodo) {
            $tipoPago = TipoPago::find($metodo['id']);
            if (!$tipoPago) continue;

            $montoMetodo = $metodo['monto'];
            $nombreTipoPago = strtolower($tipoPago->nombre);

            switch ($nombreTipoPago) {
                case 'efectivo':
                    $montoEfectivo += $montoMetodo;
                    break;
                case 'tarjeta':
                case 'tarjeta(pos)':
                case 'pos':
                    $montoTarjeta += $montoMetodo;
                    break;
                case 'cheque':
                    $montoCheque += $montoMetodo;
                    break;
                case 'transferencia':
                case 'transferencia bancaria':
                case 'transferencia_bancaria':
                    $montoTransferencia += $montoMetodo;
                    break;
                default:
                    // Para otros tipos de pago, intentar identificar el tipo por palabras clave
                    if (str_contains($nombreTipoPago, 'tarjeta') || str_contains($nombreTipoPago, 'pos')) {
                        $montoTarjeta += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'transfer')) {
                        $montoTransferencia += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'cheque')) {
                        $montoCheque += $montoMetodo;
                    } elseif (str_contains($nombreTipoPago, 'efectivo')) {
                        $montoEfectivo += $montoMetodo;
                    } else {
                        // Por defecto, asignar a transferencia
                        $montoTransferencia += $montoMetodo;
                    }
                    break;
            }

            Log::info("DEBUG Método procesado", [
                'tipo_pago' => $tipoPago->nombre,
                'monto' => $montoMetodo,
                'asignado_a' => $nombreTipoPago
            ]);
        }

        // Calcular el cambio total para restar del efectivo
        $totalDistribuido = $montoEfectivo + $montoTarjeta + $montoCheque + $montoTransferencia;
        $cambioTotal = $totalDistribuido > $this->total ? $totalDistribuido - $this->total : 0;

        // El efectivo neto es el monto efectivo menos el cambio
        $efectivoNeto = $montoEfectivo - $cambioTotal;

        // Crear un solo registro de transacción con todos los montos
        if ($efectivoNeto > 0 || $montoTarjeta > 0 || $montoCheque > 0 || $montoTransferencia > 0) {
            DB::table('transaccion')->insert([
                'caja_id' => $cajaId,
                'efectivo' => $efectivoNeto, // Efectivo neto (sin incluir el cambio)
                'tarjeta' => $montoTarjeta,
                'cheque' => $montoCheque,
                'transferencia' => $montoTransferencia,
                'transaccion' => 'Facturacion',
                'descripcion' => "Factura #$numeroFactura",
                'created_at' => now(),
                'update_at' => now()
            ]);

            Log::info("DEBUG Transacción ÚNICA registrada", [
                'numero_factura' => $numeroFactura,
                'efectivo_recibido' => $montoEfectivo,
                'cambio_calculado' => $cambioTotal,
                'efectivo_neto' => $efectivoNeto,
                'tarjeta' => $montoTarjeta,
                'cheque' => $montoCheque,
                'transferencia' => $montoTransferencia,
                'total_factura' => $this->total,
                'total_distribuido' => $totalDistribuido
            ]);

            // Actualizar balance de caja con el efectivo neto (sin incluir el cambio)
            if ($efectivoNeto > 0) {
                $this->actualizarBalanceCaja($efectivoNeto, $cajaId);
            }
        }

        Log::info("DEBUG registrarTransaccionesPorMetodoPago FINALIZADO");
    }

    /**
     * Actualizar balance de caja cuando hay pago en efectivo
     */
    private function actualizarBalanceCaja($montoEfectivo, $cajaId = null)
    {
        $user = Auth::user();

        // Si se proporciona cajaId, usar ese, sino buscar la caja abierta del usuario
        if ($cajaId) {
            $caja = DB::table('caja')
                ->where('id', $cajaId)
                ->where('users_id', $user->id)
                ->where('tienda_id', $user->tienda_id)
                ->where('estado_caja', 1)
                ->first();
        } else {
            $caja = DB::table('caja')
                ->where('users_id', $user->id)
                ->where('tienda_id', $user->tienda_id)
                ->where('estado_caja', 1) // 1 = abierta
                ->first();
        }

        if ($caja) {
            // Incrementar el balance con el efectivo recibido
            $nuevoBalance = $caja->balance + $montoEfectivo;

            DB::table('caja')
                ->where('id', $caja->id)
                ->update([
                    'balance' => $nuevoBalance,
                    'updated_at' => now()
                ]);

            Log::info("DEBUG Balance de caja actualizado", [
                'caja_id' => $caja->id,
                'balance_anterior' => $caja->balance,
                'monto_agregado' => $montoEfectivo,
                'balance_nuevo' => $nuevoBalance
            ]);
        } else {
            Log::warning("No se encontró caja abierta para actualizar balance", [
                'user_id' => $user->id
            ]);
        }
    }

    private function guardarProductoConDistribucionSecciones($facturaId, $producto, $indice)
    {
        // NUEVA LÓGICA: Guardar en factura_has_producto tal como está en la factura (respetando descuentos por línea)
        // Luego reducir el inventario usando FIFO

        $cantidadParaInventario = $producto['cantidad']; // Cantidad exacta a rebajar del inventario

        Log::info("DEBUG guardarProductoConDistribucionSecciones INICIO", [
            'factura_id' => $facturaId,
            'producto_id' => $producto['id'],
            'cantidad_en_factura' => $producto['cantidad'],
            'cantidad_para_inventario' => $cantidadParaInventario,
            'unidad_medida_id' => $producto['unidad_medida_id'] ?? 'N/A',
            'unidad_medida_nombre' => $producto['unidad_medida_nombre'] ?? 'N/A',
            'indice' => $indice,
            'descuento_aplicado' => $producto['descuento_aplicado'] ?? 0,
            'subtotal' => $producto['subtotal'] ?? 0
        ]);

        // Obtener producto de la base de datos
        $productoDb = DB::table('producto')->where('id', $producto['id'])->first();
        if (!$productoDb) {
            Log::error("Producto no encontrado", ['producto_id' => $producto['id']]);
            return;
        }

        // Obtener la primera sección disponible para este producto (para el registro en factura_has_producto)
        $primeraSeccion = DB::table('tienda as t')
            ->join('bodega as b', 'b.tienda_id', '=', 't.id')
            ->join('segmento as s', 's.bodega_id', '=', 'b.id')
            ->join('seccion as sc', 'sc.segmento_id', '=', 's.id')
            ->join('recibido_bodega as rb', 'rb.seccion_id', '=', 'sc.id')
            ->where('t.id', Auth::user()->tienda_id)
            ->where('rb.producto_id', $producto['id'])
            ->where('rb.unidad_medida_id', $producto['unidad_medida_id'])
            ->where('b.principal', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->where('rb.estado_id', 1)
            ->select('sc.id as seccion_id')
            ->first();

        if (!$primeraSeccion) {
            Log::error("No hay stock disponible", ['producto_id' => $producto['id'], 'unidad_medida_id' => $producto['unidad_medida_id']]);
            throw new \Exception("No hay stock disponible para el producto con la unidad de medida seleccionada");
        }

        // PASO 1: Guardar en factura_has_producto TAL COMO ESTÁ EN LA FACTURA
        $registroFacturaProducto = [
            'factura_id' => $facturaId,
            'producto_id' => $producto['id'],
            'Servicios_id' => null,
            'seccion_id' => $primeraSeccion->seccion_id,
            'unidad_medida_id' => $producto['unidad_medida_id'] ?? null,
            'indice' => $indice,
            'numero_unidades_resta_inventario' => $producto['cantidad'],
            'unidades_nota_credito_resta_inventario' => 0,
            'resta_inventario_total' => $producto['cantidad'],
            'precio_unidad' => $producto['precio'],
            'cantidad' => $producto['cantidad'], // Cantidad de la línea de factura
            'subtotal' => $producto['subtotal_con_descuento'] ?? ($producto['cantidad'] * $producto['precio']),
            'descuento' => $producto['descuento_aplicado'] ?? 0,
            'isv_aplicado' => $producto['isv'] ?? 0, // Tasa de ISV (15, 18, etc.)
            'isv' => $producto['isv_calculado'] ?? 0, // Monto del ISV calculado
            'total' => $producto['total'] ?? (($producto['subtotal_con_descuento'] ?? 0) + ($producto['isv_calculado'] ?? 0)),
            'idPrecioSeleccionado' => '0',
            'precio_seleccionado' => 0
        ];

        Log::info("DEBUG Insertando en factura_has_producto (Línea de factura original)", [
            'indice' => $indice,
            'cantidad' => $producto['cantidad'],
            'precio_unidad' => $producto['precio'],
            'subtotal' => $registroFacturaProducto['subtotal'],
            'descuento' => $registroFacturaProducto['descuento'],
            'isv' => $registroFacturaProducto['isv'],
            'total' => $registroFacturaProducto['total']
        ]);

        DB::table('factura_has_producto')->insert($registroFacturaProducto);

        // PASO 2: Reducir inventario usando FIFO
        $registrosStock = DB::table('tienda as t')
            ->join('bodega as b', 'b.tienda_id', '=', 't.id')
            ->join('segmento as s', 's.bodega_id', '=', 'b.id')
            ->join('seccion as sc', 'sc.segmento_id', '=', 's.id')
            ->join('recibido_bodega as rb', 'rb.seccion_id', '=', 'sc.id')
            ->where('t.id', Auth::user()->tienda_id)
            ->where('rb.producto_id', $producto['id'])
            ->where('rb.unidad_medida_id', $producto['unidad_medida_id'])
            ->where('b.principal', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->where('rb.estado_id', 1)
            ->select(
                'rb.id as recibido_bodega_id',
                'rb.cantidad_disponible',
                'rb.fecha_recibido',
                'sc.descripcion as seccion_nombre'
            )
            ->orderBy('rb.fecha_recibido', 'ASC') // FIFO: primero el más antiguo
            ->get();

        Log::info("DEBUG Registros FIFO para reducción de inventario", [
            'total_registros' => $registrosStock->count(),
            'cantidad_a_reducir' => $cantidadParaInventario
        ]);

        $cantidadRestante = $cantidadParaInventario;

        foreach ($registrosStock as $registro) {
            if ($cantidadRestante <= 0) break;

            $cantidadATomar = min($cantidadRestante, $registro->cantidad_disponible);

            // Actualizar stock en recibido_bodega (FIFO)
            DB::table('recibido_bodega')
                ->where('id', $registro->recibido_bodega_id)
                ->decrement('cantidad_disponible', $cantidadATomar);

            Log::info("DEBUG Stock reducido (FIFO)", [
                'recibido_bodega_id' => $registro->recibido_bodega_id,
                'seccion' => $registro->seccion_nombre,
                'fecha_recibido' => $registro->fecha_recibido,
                'cantidad_descontada' => $cantidadATomar,
                'stock_anterior' => $registro->cantidad_disponible,
                'stock_nuevo' => $registro->cantidad_disponible - $cantidadATomar
            ]);

            // NUEVO: Inactivar registro si se agotó el stock
            if (($registro->cantidad_disponible - $cantidadATomar) <= 0) {
                DB::table('recibido_bodega')
                    ->where('id', $registro->recibido_bodega_id)
                    ->update(['estado_id' => 2]); // Inactivo

                Log::info("DEBUG Stock agotado - Registro inactivado", [
                    'recibido_bodega_id' => $registro->recibido_bodega_id,
                    'estado_anterior' => 1,
                    'estado_nuevo' => 2
                ]);
            }

            $cantidadRestante -= $cantidadATomar;
        }

        if ($cantidadRestante > 0) {
            Log::warning("Stock insuficiente", [
                'producto_id' => $producto['id'],
                'cantidad_faltante' => $cantidadRestante
            ]);
            throw new \Exception("Stock insuficiente. Faltan {$cantidadRestante} unidades");
        }

        Log::info("DEBUG guardarProductoConDistribucionSecciones FINALIZADO", [
            'cantidad_reducida' => $cantidadParaInventario,
            'registros_procesados' => $registrosStock->count()
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
        // Usar number_format para asegurar exactamente 2 decimales sin problemas de punto flotante
        $this->montoEfectivo = (float)number_format($this->total, 2, '.', '');
        $this->efectivoRecibido = (float)number_format($this->total, 2, '.', '');
        $this->mostrarModalEfectivoFlag = true;
    }

    public function distribuirTotalEnTarjeta()
    {
        // Buscar el ID del método "Tarjeta"
        $tarjetaId = null;
        $totalRedondeado = (float)number_format($this->total, 2, '.', '');

        foreach ($this->tiposPago as $tipoPago) {
            if ($tipoPago->nombre === 'Tarjeta(POS)') {
                $tarjetaId = $tipoPago->id;
                break;
            }
        }

        if ($tarjetaId) {
            // Limpiar montos anteriores
            $this->montosPorMetodo = [];
            // Asignar el total a tarjeta
            $this->montosPorMetodo[$tarjetaId] = $totalRedondeado;
        }
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
        // Redondear los valores a 2 decimales para comparación
        $efectivoRecibido = round($this->efectivoRecibido, 2);
        $montoEfectivo = round($this->montoEfectivo, 2);

        if ($efectivoRecibido < $montoEfectivo) {
            session()->flash('error', 'El efectivo recibido es insuficiente');
            return;
        }

        $this->cambio = round($efectivoRecibido - $montoEfectivo, 2);

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

    private function cargarDatosParaImpresion($facturaId)
    {
        // Cargar la factura con la relación del usuario
        $this->facturaParaImprimir = Factura::with('usuario')->find($facturaId);

        // Cargar información del CAI asociado a la factura
        $cai = DB::table('cai')
            ->where('id', $this->facturaParaImprimir->cai_id)
            ->first();

        $this->caiFacturaImpresa = $cai ? (array) $cai : null;

        // Cargar productos y servicios de forma unificada con descuentos agrupados por índice
        $descuentosAgrupados = DB::table('descuentos')
            ->select('factura_id', 'producto_id', 'indice_factura_has_producto', DB::raw('SUM(monto_total) as descuento_total'))
            ->where('factura_id', $facturaId)
            ->groupBy('factura_id', 'producto_id', 'indice_factura_has_producto');

        $this->productosFacturaImpresa = DB::table('factura_has_producto as fp')
            ->leftJoin('producto as p', 'fp.producto_id', '=', 'p.id')
            ->leftJoin('servicios as s', 'fp.Servicios_id', '=', 's.id')
            ->leftJoin('isv as i_producto', 'p.isv_id', '=', 'i_producto.id')
            ->leftJoin('isv as i_servicio', 's.isv_id', '=', 'i_servicio.id')
            ->leftJoinSub($descuentosAgrupados, 'd', function($join) {
                $join->on('d.factura_id', '=', 'fp.factura_id')
                     ->on('d.producto_id', '=', DB::raw('COALESCE(fp.producto_id, fp.Servicios_id)'))
                     ->on('d.indice_factura_has_producto', '=', 'fp.indice');
            })
            ->where('fp.factura_id', $facturaId)
            ->select(
                DB::raw('COALESCE(p.id, s.id) as item_id'),
                DB::raw('COALESCE(p.nombre, s.nombre) as nombre'),
                DB::raw('COALESCE(p.codigo_barra, "SERVICIO") as codigo_barra'),
                DB::raw('COALESCE(i_producto.cantidad, i_servicio.cantidad, 0) as tasa_isv'),
                DB::raw('CASE WHEN p.id IS NOT NULL THEN "producto" ELSE "servicio" END as tipo'),
                'fp.cantidad',
                'fp.precio_unidad',
                'fp.subtotal',
                'fp.descuento',
                'fp.isv_aplicado',
                'fp.isv',
                'fp.total',
                'fp.indice',
                DB::raw('COALESCE(d.descuento_total, 0) as descuento_unitario')
            )
            ->get()
            ->map(function($item) {
                return (array) $item;
            })
            ->toArray();

        // Cargar métodos de pago
        $this->pagosFacturaImpresa = DB::table('factura_has_pago as fp')
            ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
            ->where('fp.factura_id', $facturaId)
            ->select('tp.nombre as metodo', 'fp.pago_recibido')
            ->get()->map(function($item) {
                return (array) $item;
            })->toArray();

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
            $rojo = imagecolorallocate($imagen, 200, 0, 0);

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
            $totalDescuentos = 0;
            $isvPorTasa = [];

            foreach ($this->productosFacturaImpresa as $producto) {
                $nombreCorto = substr($producto['nombre'], 0, 25);
                imagestring($imagen, 2, 30, $y, $nombreCorto, $negro);
                imagestring($imagen, 2, 350, $y, $producto['cantidad'], $negro);
                imagestring($imagen, 2, 420, $y, "L. " . number_format($producto['precio_unidad'], 2), $negro);
                imagestring($imagen, 2, 500, $y, "L. " . number_format($producto['total'], 2), $negro);
                $y += 15;

                // Mostrar descuento si existe
                if ($producto['descuento'] > 0) {
                    $porcentajeDescuento = ($producto['descuento'] / ($producto['subtotal'] + $producto['descuento'])) * 100;
                    $tipoDescuento = $porcentajeDescuento >= 15 ? "4ta edad" : "3ra edad";
                    imagestring($imagen, 1, 50, $y, "Descuento - " . number_format($porcentajeDescuento, 0) . "% " . $tipoDescuento, $gris);
                    imagestring($imagen, 1, 500, $y, "-L. " . number_format($producto['descuento'], 2), $gris);
                    $y += 12;
                    $totalDescuentos += $producto['descuento'];
                }

                // Agrupar ISV por tasa
                $tasaIsv = $producto['isv_aplicado'];
                if ($tasaIsv > 0) {
                    if (!isset($isvPorTasa[$tasaIsv])) {
                        $isvPorTasa[$tasaIsv] = 0;
                    }
                    $isvPorTasa[$tasaIsv] += $producto['isv'];
                }
            }

            $y += 10;
            imageline($imagen, 20, $y, $ancho-20, $y, $gris);
            $y += 20;

            // Totales
            imagestring($imagen, 3, 350, $y, "Subtotal:", $negro);
            imagestring($imagen, 3, 470, $y, "L. " . number_format((float)$factura->sub_total, 2), $negro);
            $y += 20;

            // Mostrar descuentos si existen
            if ($totalDescuentos > 0) {
                imagestring($imagen, 3, 350, $y, "Descuentos y rebajas:", $rojo);
                imagestring($imagen, 3, 470, $y, "-L. " . number_format($totalDescuentos, 2), $rojo);
                $y += 20;
            }

            // Mostrar ISV por tasa
            foreach ($isvPorTasa as $tasa => $montoIsv) {
                if ($tasa > 0 && $montoIsv > 0) {
                    imagestring($imagen, 3, 350, $y, "ISV (" . $tasa . "%):", $negro);
                    imagestring($imagen, 3, 470, $y, "L. " . number_format($montoIsv, 2), $negro);
                    $y += 20;
                }
            }

            // Si no hay ISV por tasa, mostrar el total de ISV
            if (empty($isvPorTasa) || array_sum($isvPorTasa) == 0) {
                imagestring($imagen, 3, 350, $y, "ISV:", $negro);
                imagestring($imagen, 3, 470, $y, "L. " . number_format((float)$factura->isv, 2), $negro);
                $y += 20;
            }

            imagestring($imagen, 4, 350, $y, "TOTAL:", $azul);
            imagestring($imagen, 4, 470, $y, "L. " . number_format((float)$factura->total, 2), $azul);
            $y += 30;

            // Métodos de pago
            if (!empty($this->pagosFacturaImpresa)) {
                imageline($imagen, 20, $y, $ancho-20, $y, $gris);
                $y += 20;
                imagestring($imagen, 3, 30, $y, "METODOS DE PAGO:", $negro);
                $y += 20;

                foreach ($this->pagosFacturaImpresa as $pago) {
                    imagestring($imagen, 2, 50, $y, $pago['metodo'] . ": L. " . number_format($pago['pago_recibido'], 2), $negro);
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
            // Usar CAI específico de la tienda del usuario
            $resultadoCAI = $caiService->obtenerSiguienteNumeroFactura($this->tiendaUsuario);

            // Guardar información para usar en la factura
            $this->caiActual = $resultadoCAI;

            // Si el CAI se agotó, mostrar alerta
            if ($resultadoCAI['cai_agotado']) {
                $this->alertaCAI = "¡ATENCIÓN! El CAI de su tienda se ha agotado. Esta es la última factura disponible para este CAI.";
            } elseif ($resultadoCAI['cantidad_restante'] <= 10) {
                $this->alertaCAI = "¡AVISO! Quedan solo {$resultadoCAI['cantidad_restante']} facturas disponibles en el CAI de su tienda.";
            }

            Log::info("DEBUG CAI generado para tienda", [
                'tienda_id' => $this->tiendaUsuario,
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
        $this->resetearFactura();
        $this->busquedaCliente = '';

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
        // Usar cantidad fija de 1 (funcionalidad de cantidad manual removida)
        $cantidadSolicitada = 1;

        if (!$this->tiendaUsuario) {
            $this->mostrarModalSinStock = true;
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
            $this->mostrarModalSinStock = true;
            return false;
        }

        // Calcular cuánto ya tenemos en el carrito de este producto (en UNIDADES REALES)
        $cantidadEnCarrito = 0;
        foreach ($this->productosFactura as $item) {
            if ($item['id'] == $productoId) {
                $cantidadItem = (int)$item['cantidad'];
                $cantidadPorUnidad = $item['cantidad_por_unidad'] ?? 1;
                $cantidadEnCarrito += ($cantidadItem * $cantidadPorUnidad);
            }
        }

        // La nueva cantidad total que tendríamos sería: cantidad en carrito + cantidad solicitada
        $nuevaCantidadTotal = $cantidadEnCarrito + $cantidadSolicitada;

        // DEBUG: Log para entender qué está pasando
        Log::info("DEBUG Stock Validation", [
            'producto_id' => $productoId,
            'tienda_usuario' => $this->tiendaUsuario,
            'stock_total' => $stockTotal,
            'cantidad_en_carrito_unidades_reales' => $cantidadEnCarrito,
            'cantidad_solicitada' => $cantidadSolicitada,
            'nueva_cantidad_total' => $nuevaCantidadTotal,
            'validacion' => $nuevaCantidadTotal <= $stockTotal ? 'VALIDO' : 'INVALIDO'
        ]);

        // Validar que la nueva cantidad total no exceda el stock total disponible
        if ($nuevaCantidadTotal > $stockTotal) {
            $this->mostrarModalSinStock = true;
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
            // NOTA: Se excluye la bodega ID 2 porque no suma al stock para venta
            $stockTotal = DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $this->tiendaUsuario)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('b.id', '!=', 2) // Excluir bodega ID 2 (productos sin venta)
                ->where('rb.producto_id', $productoId)
                ->where('rb.estado_id', 1)
                ->sum('rb.cantidad_disponible');

            $stockTotal = $stockTotal ?? 0;

            // Calcular cuánto ya tenemos en el carrito de este producto (en UNIDADES REALES)
            $cantidadEnCarrito = 0;
            foreach ($this->productosFactura as $item) {
                if ($item['id'] == $productoId) {
                    $cantidadItem = $item['cantidad'];
                    $cantidadPorUnidad = $item['cantidad_por_unidad'] ?? 1;
                    $cantidadEnCarrito += ($cantidadItem * $cantidadPorUnidad);
                }
            }

            // Retornar stock disponible considerando lo que ya está en el carrito
            return max(0, $stockTotal - $cantidadEnCarrito);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener stock disponible considerando la unidad de medida seleccionada
     * Ejemplo: 100 unidades - (2 paquetes × 13 unidades) = 74 unidades disponibles
     */
    public function obtenerStockDisponibleConUnidad($productoId, $cantidadPorUnidad, $cantidadActual, $indiceActual)
    {
        if (!$this->tiendaUsuario) {
            return 0;
        }

        try {
            // Obtener stock total disponible en UNIDADES
            $stockTotal = DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $this->tiendaUsuario)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('b.id', '!=', 2)
                ->where('rb.producto_id', $productoId)
                ->where('rb.estado_id', 1)
                ->sum('rb.cantidad_disponible');

            $stockTotal = $stockTotal ?? 0;

            // Calcular cuántas UNIDADES ya están en el carrito (considerando otras líneas)
            $unidadesEnCarrito = 0;
            foreach ($this->productosFactura as $index => $item) {
                // Solo contar otros items, no el actual
                if ($item['id'] == $productoId && $index != $indiceActual) {
                    $cantidadItem = $item['cantidad'] ?? 0;
                    $cantidadPorUnidadItem = $item['cantidad_por_unidad'] ?? 1;
                    $unidadesEnCarrito += ($cantidadItem * $cantidadPorUnidadItem);
                }
            }

            // Calcular unidades del item actual
            $unidadesItemActual = $cantidadActual * $cantidadPorUnidad;

            // Stock disponible = Stock total - unidades en otras líneas - unidades del item actual
            $stockDisponible = $stockTotal - $unidadesEnCarrito - $unidadesItemActual;

            return max(0, $stockDisponible);
        } catch (\Exception $e) {
            Log::error("Error en obtenerStockDisponibleConUnidad: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular el stock total disponible para una unidad de medida específica de un producto
     * Suma TODOS los registros de recibido_bodega que coincidan con producto_id y unidad_medida_id
     */
    public function calcularStockTotalPorUnidad($productoId, $unidadMedidaId)
    {
        if (!$this->tiendaUsuario) {
            return 0;
        }

        try {
            // Obtener la suma total de cantidad_disponible para esta unidad de medida específica
            $stockTotal = DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $this->tiendaUsuario)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('b.id', '!=', 2)
                ->where('rb.producto_id', $productoId)
                ->where('rb.unidad_medida_id', $unidadMedidaId)
                ->where('rb.estado_id', 1)
                ->where('rb.cantidad_disponible', '>', 0)
                ->sum('rb.cantidad_disponible');

            return $stockTotal ?? 0;
        } catch (\Exception $e) {
            Log::error("Error en calcularStockTotalPorUnidad: " . $e->getMessage());
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

    /**
     * Obtener la primera unidad de medida disponible
     */
    private function obtenerUnidadMedidaDisponible()
    {
        try {
            $unidad = DB::table('unidad_medida')->select('id')->first();
            return $unidad ? $unidad->id : 3; // Fallback al ID 3 que sabemos que existe
        } catch (\Exception $e) {
            return 3; // Fallback al ID 3
        }
    }

    public function agregarProductoPorClic($productoId)
    {
        try {
            $producto = Producto::with('isv')->find($productoId);

            if (!$producto) {
                $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
                return;
            }

            // Log para debug
            Log::info("agregarProductoPorClic - ProductoId: {$productoId}");
            Log::info("Productos en factura antes: ", $this->productosFactura);

            // Verificar stock disponible
            $stockDisponible = $this->obtenerStockDisponible($producto->id);
            if ($stockDisponible <= 0) {
                $this->dispatch('mostrar-error', ['mensaje' => 'Producto sin stock disponible']);
                return;
            }

            // Verificar si el producto ya está en la factura
            $productoExistente = false;
            foreach ($this->productosFactura as $index => $item) {
                if (!isset($item['servicio_id']) && $item['id'] == $producto->id) {
                    // Log para debug
                    Log::info("Producto existente encontrado en índice {$index}, cantidad actual: {$item['cantidad']}");

                    // Verificar que no exceda el stock (sumar 1 unidad)
                    $nuevaCantidad = (int)$this->productosFactura[$index]['cantidad'] + 1;
                    Log::info("Nueva cantidad será: {$nuevaCantidad}, stock disponible: {$stockDisponible}");

                    if ($nuevaCantidad > $stockDisponible) {
                        $this->dispatch('mostrar-error', ['mensaje' => 'No se puede agregar más cantidad. Stock limitado a: ' . $stockDisponible]);
                        return;
                    }

                    // Actualizar la cantidad
                    $this->productosFactura[$index]['cantidad'] = $nuevaCantidad;

                    // Recalcular el descuento unitario aplicado con la nueva cantidad
                    $descuentoUnitarioProducto = $item['descuento_unitario_producto'] ?? 0;
                    if ($descuentoUnitarioProducto > 0) {
                        $this->productosFactura[$index]['descuento_unitario_aplicado'] = $descuentoUnitarioProducto * $nuevaCantidad;
                    }

                    // Recalcular subtotal con descuento para este item
                    $subtotalOriginal = $item['precio'] * $nuevaCantidad;
                    $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
                    $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

                    Log::info("Cantidad actualizada a: {$nuevaCantidad}");
                    $productoExistente = true;
                    break;
                }
            }

            if (!$productoExistente) {
                // Obtener el valor de ISV desde la relación
                $valorIsv = $producto->isv ? $producto->isv->cantidad : 0;

                // Determinar precio por defecto según reglas de negocio
                $precioDefecto = $this->determinarPrecioPorDefecto($producto);

                // Calcular descuento unitario automático si existe (para cantidad de 1)
                $subtotalOriginal = $precioDefecto['precio'];
                $descuentoUnitarioAplicado = 0;

                if (($producto->descuento_unitario ?? 0) > 0) {
                    $descuentoUnitarioAplicado = $producto->descuento_unitario;
                }

                $this->productosFactura[] = [
                    'id' => $producto->id,
                    'servicio_id' => null,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo_barra,
                    'precio' => $precioDefecto['precio'],
                    'tipo_precio' => $precioDefecto['tipo'],
                    'precio1' => $producto->precio1 ?? 0,
                    'precio2' => $producto->precio2 ?? 0,
                    'precio3' => $producto->precio3 ?? 0,
                    'precio4' => $producto->precio4 ?? 0,
                    'precio_base' => $producto->precio_base,
                    'producto_valencia' => $producto->producto_valencia,
                    'isv' => $valorIsv,
                    'cantidad' => 1,
                    'descuento_tercera' => $producto->descuento_tercera ?? 0,
                    'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
                    'descuento_unitario_producto' => $producto->descuento_unitario ?? 0,
                    'descuento_unitario_aplicado' => $descuentoUnitarioAplicado,
                    'descuento_aplicado' => 0,
                    'subtotal_con_descuento' => $subtotalOriginal - $descuentoUnitarioAplicado,
                    'tipo' => 'producto'
                ];

                // Mostrar mensaje si se aplicó descuento automático
                if (($producto->descuento_unitario ?? 0) > 0) {
                    session()->flash('success', 'Producto agregado con descuento automático');
                } else {
                    session()->flash('success', 'Producto agregado exitosamente');
                }
            }

            $this->calcularTotales();

            // Forzar actualización de la vista
            $this->dispatch('$refresh');

            $this->codigoBarras = ''; // Limpiar código de barras

        } catch (\Exception $e) {
            Log::error('Error al agregar producto por clic: ' . $e->getMessage());
            $this->dispatch('mostrar-error', ['mensaje' => 'Error al agregar el producto']);
        }
    }

    /**
     * Agregar producto desde modal de búsqueda usando el ID del producto
     * MISMA LÓGICA que agregarProductoPorCodigo: busca automáticamente la primera unidad con stock
     */
    public function agregarProductoDesdeModal($productoId)
    {
        try {
            // Obtener el producto con sus relaciones (igual que en agregarProductoPorCodigo)
            $producto = Producto::with('isv')->find($productoId);

            if (!$producto) {
                $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
                return;
            }

            // Cargar precios disponibles desde precio_has_venta
            $preciosDisponibles = DB::table('precio_has_venta')
                ->join('unidad_medida', 'precio_has_venta.unidad_medida_id', '=', 'unidad_medida.id')
                ->where('precio_has_venta.producto_id', $producto->id)
                ->where('precio_has_venta.estado_id', 1)
                ->select(
                    'precio_has_venta.id as precio_id',
                    'precio_has_venta.unidad_medida_id',
                    'precio_has_venta.codigo_barra',
                    'unidad_medida.nombre as unidad_nombre',
                    'unidad_medida.simbolo as unidad_simbolo',
                    'precio_has_venta.cantidad',
                    'precio_has_venta.precio'
                )
                ->orderBy('precio_has_venta.cantidad', 'asc')
                ->get();

            if ($preciosDisponibles->isEmpty()) {
                session()->flash('error', '⚠️ Este producto no tiene precios configurados en precio_has_venta');
                $this->dispatch('mostrar-error', ['mensaje' => 'Este producto no tiene precios configurados']);
                return;
            }

            // MISMO ALGORITMO: Buscar la primera unidad de medida que tenga stock disponible
            $precioConStock = null;
            $stockTotalUnidad = 0;

            foreach ($preciosDisponibles as $precio) {
                // Calcular stock total en bodega para esta unidad
                $stockEnBodega = $this->calcularStockTotalPorUnidad($producto->id, $precio->unidad_medida_id);

                // Calcular cuánto ya está en el carrito para esta combinación producto+unidad
                $cantidadEnCarrito = 0;
                foreach ($this->productosFactura as $itemCarrito) {
                    if ($itemCarrito['id'] == $producto->id &&
                        isset($itemCarrito['unidad_medida_id']) &&
                        $itemCarrito['unidad_medida_id'] == $precio->unidad_medida_id) {
                        $cantidadEnCarrito += (int)($itemCarrito['cantidad'] ?? 0);
                    }
                }

                // Stock real disponible = stock en bodega - lo que ya está en el carrito
                $stockDisponibleReal = $stockEnBodega - $cantidadEnCarrito;

                if ($stockDisponibleReal > 0) {
                    $precioConStock = $precio;
                    $stockTotalUnidad = $stockEnBodega;
                    break; // Encontramos la primera unidad con stock real
                }
            }

            // Si ninguna unidad tiene stock real disponible, mostrar alerta con mensaje específico
            if (!$precioConStock) {
                session()->flash('error', '⚠️ Producto no cuenta con esa unidad de venta. El producto no ha sido recepcionado con ninguna unidad disponible para venta. Por favor, verifique las unidades disponibles en stock.');
                $this->dispatch('mostrar-error', ['mensaje' => 'No hay stock disponible para este producto']);
                return;
            }

            // Usar la unidad de medida con stock como precio por defecto
            $precioDefecto = $precioConStock;

            // SIEMPRE agregar una nueva línea (igual que agregarProductoPorCodigo)
            $valorIsv = $producto->isv ? $producto->isv->cantidad : 0;

            // NO aplicar descuento automáticamente - el usuario debe aplicarlo manualmente
            $descuentoUnitarioAplicado = 0;
            $subtotalOriginal = $precioDefecto->precio;

            $this->productosFactura[] = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $precioDefecto->codigo_barra ?? $producto->codigo_barra,
                'precio' => $precioDefecto->precio,
                'precio_id' => $precioDefecto->precio_id,
                'unidad_medida_id' => $precioDefecto->unidad_medida_id,
                'unidad_medida_nombre' => $precioDefecto->unidad_nombre,
                'unidad_medida_simbolo' => $precioDefecto->unidad_simbolo,
                'cantidad_por_unidad' => $precioDefecto->cantidad,
                'precios_disponibles' => $preciosDisponibles->toArray(),
                'stock_total_unidad' => $stockTotalUnidad,
                'producto_valencia' => $producto->producto_valencia,
                'precio1' => $producto->precio1 ?? 0,
                'precio2' => $producto->precio2 ?? 0,
                'precio3' => $producto->precio3 ?? 0,
                'precio4' => $producto->precio4 ?? 0,
                'precio_base' => $producto->precio_base ?? 0,
                'tipo_precio' => 'precio_has_venta',
                'isv' => $valorIsv,
                'cantidad' => 1,
                'descuento_tercera' => $producto->descuento_tercera ?? 0,
                'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
                'descuento_unitario_producto' => $producto->descuento_unitario ?? 0,
                'descuento_unitario_aplicado' => 0, // SIEMPRE INICIA EN 0
                'descuento_aplicado' => 0,
                'subtotal_con_descuento' => $subtotalOriginal
            ];

            $this->calcularTotales();

            // Cerrar modal y limpiar búsqueda
            $this->mostrarModalBusqueda = false;
            $this->busquedaProductosServicios = '';
            $this->resultadosBusqueda = collect();

            // Forzar actualización de la vista
            $this->dispatch('$refresh');

            // Enfocar campo de código de barras
            $this->dispatch('enfocar-codigo-barras');

        } catch (\Exception $e) {
            Log::error('Error al agregar producto desde modal: ' . $e->getMessage());
            $this->dispatch('mostrar-error', ['mensaje' => 'Error al agregar el producto']);
        }
    }

    public function limpiarEstadoVenta()
    {
        $this->resetearFactura();
        $this->busquedaCliente = '';

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

        // Resetear bandera de procesamiento
        $this->procesandoVenta = false;
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

    /**
     * Guardar datos del descuento de adulto mayor si hay descuento aplicado
     */
    private function guardarDescuentoAdultoMayor($facturaId)
    {
        // Verificar si hay descuento de edad aplicado y datos capturados
        if (($this->descuentoTerceraEdad || $this->descuentoCuartaEdad) && !empty($this->datosDescuentoAdulto)) {
            try {
                DescuentoAdulto::create([
                    'factura_id' => $facturaId,
                    'dni' => $this->datosDescuentoAdulto['dni'],
                    'nombre' => $this->datosDescuentoAdulto['nombre'],
                    'edad' => $this->datosDescuentoAdulto['edad'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("DEBUG Datos de descuento adulto mayor guardados", [
                    'factura_id' => $facturaId,
                    'dni' => $this->datosDescuentoAdulto['dni'],
                    'nombre' => $this->datosDescuentoAdulto['nombre'],
                    'edad' => $this->datosDescuentoAdulto['edad']
                ]);

            } catch (\Exception $e) {
                Log::error("ERROR al guardar descuento adulto mayor", [
                    'factura_id' => $facturaId,
                    'error' => $e->getMessage()
                ]);
                // No lanzar la excepción para no afectar el guardado de la factura
            }
        }
    }

    /**
     * Método para obtener productos y servicios filtrados para el catálogo
     */
    public function obtenerProductosYServiciosFiltrados()
    {
        $query = collect();

        // Obtener productos si se están mostrando
        if ($this->tipoSeleccion === 'productos' || $this->tipoSeleccion === 'todos') {
            $productos = Producto::query()
                ->where('estado_id', 1)
                ->when($this->busquedaProductosServicios, function ($q) {
                    $q->where('nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhereExists(function($subQuery) {
                          $subQuery->select(DB::raw(1))
                              ->from('precio_has_venta')
                              ->whereColumn('precio_has_venta.producto_id', 'producto.id')
                              ->where('precio_has_venta.codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%')
                              ->where('precio_has_venta.estado_id', 1);
                      });
                })
                ->get()
                ->map(function ($producto) {
                    $producto->esServicio = false;
                    // Calcular stock disponible usando el método existente
                    $producto->stockDisponible = $this->obtenerStockDisponible($producto->id);
                    return $producto;
                });

            $query = $query->merge($productos);
        }

        // Obtener servicios si se están mostrando
        if ($this->tipoSeleccion === 'servicios' || $this->tipoSeleccion === 'todos') {
            $servicios = Servicio::query()
                ->where('estado_id', 1)
                ->when($this->busquedaProductosServicios, function ($q) {
                    $q->where('nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->busquedaProductosServicios . '%');
                })
                ->get()
                ->map(function ($servicio) {
                    $servicio->esServicio = true;
                    $servicio->stockDisponible = null; // Los servicios no tienen stock
                    return $servicio;
                });

            $query = $query->merge($servicios);
        }

        return $query->sortBy('nombre')->values();
    }

    /**
     * Verificar si el menú de servicios está activo para mostrar el catálogo visual
     */
    public function verificarEstadoMenuServicios()
    {
        try {
            // Verificar si el menú "Catalogo.Servicios" está activo
            $menuServicios = DB::table('menu')
                ->where('route', 'Catalogo.Servicios')
                ->where('estado_id', 1) // 1 = Activo
                ->first();

            // Si no existe el menú o está inactivo, ocultar el catálogo visual
            $this->mostrarCatalogoVisual = $menuServicios !== null;

            Log::info('Verificación estado menú servicios', [
                'menu_encontrado' => $menuServicios !== null,
                'mostrar_catalogo' => $this->mostrarCatalogoVisual
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar estado del menú servicios', [
                'error' => $e->getMessage()
            ]);
            // En caso de error, mantener el catálogo visible por defecto
            $this->mostrarCatalogoVisual = true;
        }
    }

    /**
     * Métodos para búsqueda avanzada
     */
    public function cerrarModalBusqueda()
    {
        $this->mostrarModalBusqueda = false;
        $this->reset(['marcaSeleccionada', 'categoriaSeleccionada', 'subcategoriaSeleccionada', 'resultadosBusqueda']);
    }

    protected function cargarFiltros()
    {
        // Cargar las listas para los filtros
        $this->marcas = DB::table('marca')->orderBy('nombre')->get();
        $this->categorias = DB::table('categoria')->orderBy('nombre')->get();
        $this->subcategorias = DB::table('subcategoria')->orderBy('nombre')->get();
    }

    public function buscarProductos()
    {
        // Primero, obtener los productos que coinciden con los filtros
        $query = Producto::with(['subcategoria.categoria', 'marca'])
            ->where('estado_id', 1); // Solo productos activos

        // Aplicar filtros de búsqueda de texto
        if ($this->busquedaProductosServicios) {
            $busqueda = $this->busquedaProductosServicios;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%')
                  ->orWhere('codigo_barra', 'like', '%' . $busqueda . '%')
                  ->orWhere('descripcion', 'like', '%' . $busqueda . '%')
                  ->orWhereExists(function($subQuery) use ($busqueda) {
                      $subQuery->select(DB::raw(1))
                          ->from('precio_has_venta')
                          ->whereColumn('precio_has_venta.producto_id', 'producto.id')
                          ->where('precio_has_venta.codigo_barra', 'like', '%' . $busqueda . '%')
                          ->where('precio_has_venta.estado_id', 1);
                  });
            });
        }

        // Aplicar filtro de marca
        if ($this->marcaSeleccionada) {
            $query->where('marca_id', $this->marcaSeleccionada);
        }

        // Aplicar filtro de subcategoría (que incluye la categoría)
        if ($this->subcategoriaSeleccionada) {
            $query->where('subcategoria_id', $this->subcategoriaSeleccionada);
        } elseif ($this->categoriaSeleccionada) {
            // Si solo hay categoría seleccionada, buscar por subcategorías de esa categoría
            $query->whereHas('subcategoria', function($q) {
                $q->where('categoria_id', $this->categoriaSeleccionada);
            });
        }

        // Obtener TODOS los productos (con o sin stock) - igual que lista-de-productos.blade.php
        $productos = $query->orderBy('nombre')
                          ->limit(100) // Aumentar límite para mostrar más productos
                          ->get();

        // Expandir cada producto con sus unidades de precio_has_venta
        $resultadosExpandidos = collect();

        foreach ($productos as $producto) {
            // Obtener todas las unidades de precio para este producto
            $preciosVenta = DB::table('precio_has_venta as phv')
                ->join('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                ->where('phv.producto_id', $producto->id)
                ->where('phv.estado_id', 1)
                ->select(
                    'phv.id as precio_id',
                    'phv.precio',
                    'phv.codigo_barra',
                    'phv.cantidad as cantidad_por_unidad',
                    'um.id as unidad_medida_id',
                    'um.nombre as unidad_nombre'
                )
                ->get();

            // Si el producto tiene precios de venta definidos, crear una entrada por cada uno
            if ($preciosVenta->count() > 0) {
                foreach ($preciosVenta as $precioVenta) {
                    // Convertir imagen a base64 si existe, o null
                    $imagenBase64 = null;
                    if ($producto->imagen) {
                        try {
                            $imagenBase64 = base64_encode($producto->imagen);
                        } catch (\Exception $e) {
                            $imagenBase64 = null;
                        }
                    }

                    // Calcular stock específico para esta unidad de medida
                    $stockUnidadTotal = $this->calcularStockTotalPorUnidad($producto->id, $precioVenta->unidad_medida_id);

                    // Calcular cuánto hay en el carrito para esta misma unidad (en unidades reales de venta)
                    $cantidadEnCarritoUnidad = 0;
                    foreach ($this->productosFactura as $itemCarrito) {
                        if (isset($itemCarrito['id']) && $itemCarrito['id'] == $producto->id &&
                            isset($itemCarrito['unidad_medida_id']) && $itemCarrito['unidad_medida_id'] == $precioVenta->unidad_medida_id) {
                            $cantidadEnCarritoUnidad += (int)($itemCarrito['cantidad'] ?? 0);
                        }
                    }

                    $stockDisponibleUnidad = max(0, $stockUnidadTotal - $cantidadEnCarritoUnidad);

                    // CAMBIO: Mostrar TODAS las unidades, incluso sin stock (puede_vender dependerá del stock)
                    $puedeVender = $stockDisponibleUnidad > 0;

                    $resultadosExpandidos->push((object)[
                        'precio_id' => $precioVenta->precio_id ?? 0,
                        'id' => $producto->id,
                        'nombre' => $producto->nombre ?? '',
                        'descripcion' => $producto->descripcion ?? '',
                        'codigo_barra' => $precioVenta->codigo_barra ?? $producto->codigo_barra ?? '',
                        'imagen_base64' => $imagenBase64,
                        'tiene_imagen' => $imagenBase64 !== null,
                        'precio_base' => $producto->precio_base ?? 0,
                        'precio' => $precioVenta->precio ?? 0,
                        'cantidad_por_unidad' => $precioVenta->cantidad_por_unidad ?? 1,
                        'unidad_medida_id' => $precioVenta->unidad_medida_id ?? 0,
                        'unidad_nombre' => $precioVenta->unidad_nombre ?? 'Unidad',
                        'stock_total_unidad' => $stockUnidadTotal,
                        'stock_disponible_unidad' => $stockDisponibleUnidad,
                        'puede_vender' => $puedeVender, // true solo si tiene stock > 0
                        'subcategoria_nombre' => optional($producto->subcategoria)->nombre ?? '',
                        'categoria_nombre' => optional(optional($producto->subcategoria)->categoria)->nombre ?? '',
                        'marca_nombre' => optional($producto->marca)->nombre ?? '',
                        'descuento_unitario' => $producto->descuento_unitario ?? 0,
                        'descuento_tercera' => $producto->descuento_tercera ?? 0,
                        'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
                        'isv_id' => $producto->isv_id ?? 1,
                        'producto_valencia' => $producto->producto_valencia ?? 0,
                    ]);
                }
            } else {
                // Si no tiene precio_has_venta, mostrar con precio base
                // Convertir imagen a base64 si existe
                $imagenBase64 = null;
                if ($producto->imagen) {
                    try {
                        $imagenBase64 = base64_encode($producto->imagen);
                    } catch (\Exception $e) {
                        $imagenBase64 = null;
                    }
                }

                // Si no tiene unidades en precio_has_venta, marcar como no vendible por unidad (unidad no asignada)
                $resultadosExpandidos->push((object)[
                    'precio_id' => null,
                    'id' => $producto->id,
                    'nombre' => $producto->nombre ?? '',
                    'descripcion' => $producto->descripcion ?? '',
                    'codigo_barra' => $producto->codigo_barra ?? '',
                    'imagen_base64' => $imagenBase64,
                    'tiene_imagen' => $imagenBase64 !== null,
                    'precio_base' => $producto->precio_base ?? 0,
                    'precio' => $producto->precio_base ?? 0,
                    'cantidad_por_unidad' => 1,
                    'unidad_medida_id' => null,
                    'unidad_nombre' => 'Unidad',
                    'puede_vender' => false, // No tiene unidad en precio_has_venta
                    'stock_total_unidad' => $this->obtenerStockTotal($producto->id),
                    'stock_disponible_unidad' => 0,
                    'subcategoria_nombre' => optional($producto->subcategoria)->nombre ?? '',
                    'categoria_nombre' => optional(optional($producto->subcategoria)->categoria)->nombre ?? '',
                    'marca_nombre' => optional($producto->marca)->nombre ?? '',
                    'descuento_unitario' => $producto->descuento_unitario ?? 0,
                    'descuento_tercera' => $producto->descuento_tercera ?? 0,
                    'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
                    'isv_id' => $producto->isv_id ?? 1,
                    'producto_valencia' => $producto->producto_valencia ?? 0,
                ]);
            }
        }

        // Aplicar filtro de stock si está seleccionado
        if ($this->filtroStock === 'con_stock') {
            $resultadosExpandidos = $resultadosExpandidos->filter(function($item) {
                return $item->stock_disponible_unidad > 0;
            });
        } elseif ($this->filtroStock === 'sin_stock') {
            $resultadosExpandidos = $resultadosExpandidos->filter(function($item) {
                return $item->stock_disponible_unidad <= 0;
            });
        }

        // SIEMPRE ordenar: primero productos con stock, luego sin stock
        // Dentro de cada grupo, ordenar alfabéticamente
        $resultadosExpandidos = $resultadosExpandidos->sortBy([
            function($a, $b) {
                // Primero comparar por stock (descendente: con stock primero)
                if ($a->stock_disponible_unidad > 0 && $b->stock_disponible_unidad <= 0) {
                    return -1;
                } elseif ($a->stock_disponible_unidad <= 0 && $b->stock_disponible_unidad > 0) {
                    return 1;
                }
                // Si ambos tienen mismo estado de stock, ordenar alfabéticamente
                return strcmp($a->nombre, $b->nombre);
            }
        ])->values(); // values() para reindexar la colección

        $this->resultadosBusqueda = $resultadosExpandidos->take(100); // Limitar resultados finales
    }

    public function updatedCategoriaSeleccionada($value)
    {
        $this->reset('subcategoriaSeleccionada');
        if ($value) {
            $this->subcategorias = DB::table('subcategoria')
                ->where('categoria_id', $value)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->subcategorias = DB::table('subcategoria')->orderBy('nombre')->get();
        }
        // Auto-buscar cuando cambie la categoría
        $this->buscarProductos();
    }

    public function buscarProductosModal()
    {
        $this->buscarProductos();
    }

    public function updatedMarcaSeleccionada()
    {
        // Auto-buscar cuando cambie la marca
        $this->buscarProductos();
    }

    public function updatedSubcategoriaSeleccionada()
    {
        // Auto-buscar cuando cambie la subcategoría
        $this->buscarProductos();
    }

    public function updatedFiltroStock()
    {
        // Auto-buscar cuando cambie el filtro de stock
        $this->buscarProductos();
    }

    public function updatedBusquedaProductosServicios()
    {
        // Auto-buscar cuando cambie el texto de búsqueda
        $this->buscarProductos();
    }

    /**
     * Método público para refrescar el estado del catálogo visual
     * Útil si se cambia el estado del menú sin recargar la página
     */
    public function refrescarEstadoCatalogo()
    {
        $this->verificarEstadoMenuServicios();
    }

    // Métodos para trámites temporales
    public function cargarTramitesTemporales()
    {
        $this->tramitesTemporales = session('tramites_temporales_ventas', []);
        $this->cantidadTramitesTemporales = count($this->tramitesTemporales);
    }

    public function abrirModalTramitesTemporales()
    {
        $this->cargarTramitesTemporales();
        $this->mostrarModalTramitesTemporales = true;
    }

    public function cerrarModalTramitesTemporales()
    {
        $this->mostrarModalTramitesTemporales = false;
    }

    public function guardarTramiteTemporal()
    {
        // Validar que hay productos en la factura
        if (empty($this->productosFactura)) {
            session()->flash('error', 'Debe agregar al menos un producto para guardar un trámite temporal.');
            return;
        }

        $tramite = [
            'cliente' => $this->cliente,
            'cliente_manual' => [
                'rtn' => $this->rtnManual,
                'nombre' => $this->nombreCompletoManual,
                'telefono' => $this->telefonoManual,
                'correo' => $this->correoManual,
                'direccion' => $this->direccionManual,
            ],
            'modo_cliente_manual' => $this->modoClienteManual,
            'productos' => $this->productosFactura,
            'descuento_tercera_edad' => $this->descuentoTerceraEdad,
            'descuento_cuarta_edad' => $this->descuentoCuartaEdad,
            'datos_descuento_adulto' => $this->datosDescuentoAdulto,
            'subtotal' => $this->subtotal,
            'total_isv' => $this->totalIsv,
            'total' => $this->total,
            'total_descuentos' => $this->totalDescuentos,
            'fecha_guardado' => now()->toDateTimeString(),
        ];

        // Guardar en sesión
        $tramites = session('tramites_temporales_ventas', []);
        $tramites[] = $tramite;
        session(['tramites_temporales_ventas' => $tramites]);

        $this->cargarTramitesTemporales();

        session()->flash('success', '✅ Trámite guardado temporalmente. Puede continuar más tarde.');

        // Limpiar formulario
        $this->resetearFactura();
    }

    public function cargarTramiteTemporal($index)
    {
        $tramites = session('tramites_temporales_ventas', []);

        if (isset($tramites[$index])) {
            $tramite = $tramites[$index];

            // Restaurar datos del cliente
            $this->cliente = $tramite['cliente'] ?? null;
            $this->modoClienteManual = $tramite['modo_cliente_manual'] ?? false;
            $this->rtnManual = $tramite['cliente_manual']['rtn'] ?? '';
            $this->nombreCompletoManual = $tramite['cliente_manual']['nombre'] ?? '';
            $this->telefonoManual = $tramite['cliente_manual']['telefono'] ?? '';
            $this->correoManual = $tramite['cliente_manual']['correo'] ?? '';
            $this->direccionManual = $tramite['cliente_manual']['direccion'] ?? '';

            // Restaurar productos
            $this->productosFactura = $tramite['productos'] ?? [];

            // Restaurar descuentos
            $this->descuentoTerceraEdad = $tramite['descuento_tercera_edad'] ?? false;
            $this->descuentoCuartaEdad = $tramite['descuento_cuarta_edad'] ?? false;
            $this->datosDescuentoAdulto = $tramite['datos_descuento_adulto'] ?? [];

            // Recalcular totales
            $this->calcularTotales();

            // Eliminar el trámite de la lista de temporales
            unset($tramites[$index]);
            $tramites = array_values($tramites);
            session(['tramites_temporales_ventas' => $tramites]);

            $this->cargarTramitesTemporales();
            $this->cerrarModalTramitesTemporales();

            session()->flash('success', '✅ Trámite temporal cargado. Puede continuar editando.');
        }
    }

    public function cargarTramiteDesdeSession()
    {
        $tramite = session('tramite_venta_a_cargar');

        if ($tramite) {
            // Restaurar datos del cliente
            $this->cliente = $tramite['cliente'] ?? null;
            $this->modoClienteManual = $tramite['modo_cliente_manual'] ?? false;
            $this->rtnManual = $tramite['cliente_manual']['rtn'] ?? '';
            $this->nombreCompletoManual = $tramite['cliente_manual']['nombre'] ?? '';
            $this->telefonoManual = $tramite['cliente_manual']['telefono'] ?? '';
            $this->correoManual = $tramite['cliente_manual']['correo'] ?? '';
            $this->direccionManual = $tramite['cliente_manual']['direccion'] ?? '';

            // Restaurar productos
            $this->productosFactura = $tramite['productos'] ?? [];

            // Restaurar descuentos
            $this->descuentoTerceraEdad = $tramite['descuento_tercera_edad'] ?? false;
            $this->descuentoCuartaEdad = $tramite['descuento_cuarta_edad'] ?? false;
            $this->datosDescuentoAdulto = $tramite['datos_descuento_adulto'] ?? [];

            // Recalcular totales
            $this->calcularTotales();

            // Limpiar la sesión
            session()->forget('tramite_venta_a_cargar');

            // Eliminar el trámite de la lista de temporales
            $tramites = session('tramites_temporales_ventas', []);
            $tramites = array_filter($tramites, function($t) use ($tramite) {
                return $t['fecha_guardado'] !== $tramite['fecha_guardado'];
            });
            session(['tramites_temporales_ventas' => array_values($tramites)]);

            session()->flash('success', '✅ Trámite temporal cargado. Puede continuar editando.');
        }
    }

    public function eliminarTramiteTemporal($index)
    {
        $tramites = session('tramites_temporales_ventas', []);

        if (isset($tramites[$index])) {
            unset($tramites[$index]);
            $tramites = array_values($tramites);
            session(['tramites_temporales_ventas' => $tramites]);

            $this->cargarTramitesTemporales();
            session()->flash('success', 'Trámite temporal eliminado correctamente.');
        }
    }
}
