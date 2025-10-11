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

    protected $listeners = ['mostrarBusquedaModal'];

    public function mostrarBusquedaModal()
    {
        $this->mostrarModalBusqueda = true;
        $this->cargarFiltros();
    }

    public function mount()
    {
        $this->clientesModal = collect(); // Inicializar como colección vacía
        
        // Inicializar propiedades de búsqueda avanzada
        $this->mostrarModalBusqueda = false;
        $this->busquedaProductosServicios = '';
        $this->resultadosBusqueda = collect();
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
        $this->productos = Producto::with(['isv', 'estado'])
            ->select('id', 'nombre', 'descripcion', 'precio_base', 'estado_id', 'isv_id',
                    'descuento_unitario', 'descuento_tercera', 'descuento_cuarta', 'codigo_barra') // Excluir 'imagen'
            ->where('estado_id', 1) // Solo productos activos
            ->when($this->busquedaProductosServicios, function ($query) {
                $query->where('nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->busquedaProductosServicios . '%')
                      ->orWhere('codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%');
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

    public function updatedBusquedaProductosServicios()
    {
        $this->cargarProductosYServicios();
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

        $producto = Producto::with('isv')->where('codigo_barra', $this->codigoBarras)->first();

        if (!$producto) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Producto no encontrado']);
            return;
        }

        // Validar stock en bodega principal antes de agregar (cantidad fija de 1)
        if (!$this->validarStockProducto($producto->id, 1)) {
            return; // El error ya se muestra en validarStockProducto
        }

        // Verificar si el producto ya está en la factura
        $productoExistente = false;
        foreach ($this->productosFactura as $index => $item) {
            if ($item['id'] == $producto->id) {
                // Actualizar la cantidad - Sumar 1 unidad
                $cantidadTotal = (int)$item['cantidad'] + 1;
                $this->productosFactura[$index]['cantidad'] = $cantidadTotal;

                // Recalcular el descuento unitario aplicado con la nueva cantidad
                $descuentoUnitarioProducto = $item['descuento_unitario_producto'] ?? 0;
                if ($descuentoUnitarioProducto > 0) {
                    $this->productosFactura[$index]['descuento_unitario_aplicado'] = $descuentoUnitarioProducto * $cantidadTotal;
                }

                // Recalcular subtotal con descuento para este item
                $subtotalOriginal = $item['precio'] * $cantidadTotal;
                $descuentoUnitarioAplicado = $this->productosFactura[$index]['descuento_unitario_aplicado'] ?? 0;
                $this->productosFactura[$index]['subtotal_con_descuento'] = $subtotalOriginal - $descuentoUnitarioAplicado;

                $productoExistente = true;
                break;
            }
        }

        if (!$productoExistente) {
            // Obtener el valor de ISV desde la relación
            $valorIsv = $producto->isv ? $producto->isv->cantidad : 0;

            // Determinar precio por defecto según reglas de negocio
            $precioDefecto = $this->determinarPrecioPorDefecto($producto);

            // Calcular descuento unitario automático si existe (valor monetario directo para cantidad de 1)
            $subtotalOriginal = $precioDefecto['precio'] * 1;
            $descuentoUnitarioAplicado = 0;

            if (($producto->descuento_unitario ?? 0) > 0) {
                // El descuento es un valor monetario que se aplica por cantidad (1 en este caso)
                $descuentoUnitarioAplicado = $producto->descuento_unitario * 1;
            }

            $this->productosFactura[] = [
                'id' => $producto->id,
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
                'subtotal_con_descuento' => $subtotalOriginal - $descuentoUnitarioAplicado
            ];

            // Mostrar mensaje si se aplicó descuento automático
            if (($producto->descuento_unitario ?? 0) > 0) {
                session()->flash('success', 'Producto aplicado con descuento');
            }
        }

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

        // Obtener el producto completo de la base de datos
        $producto = Producto::find($this->productosFactura[$index]['id']);
        if (!$producto) {
            $this->dispatch('mostrar-error', ['mensaje' => 'Error al actualizar precio: producto no encontrado']);
            return;
        }

        // Determinar el nuevo precio según el tipo seleccionado
        $nuevoPrecio = 0;
        switch ($tipoPrecio) {
            case 'precio1':
                $nuevoPrecio = $producto->precio1 ?? 0;
                break;
            case 'precio2':
                $nuevoPrecio = $producto->precio2 ?? 0;
                break;
            case 'precio3':
                $nuevoPrecio = $producto->precio3 ?? 0;
                break;
            case 'precio4':
                $nuevoPrecio = $producto->precio4 ?? 0;
                break;
            case 'precio_base':
            default:
                $nuevoPrecio = $producto->precio_base ?? 0;
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

        // Solo validar stock si es un producto (no servicio)
        $esServicio = isset($item['servicio_id']) && $item['servicio_id'] !== null;

        if (!$esServicio) {
            // Validar stock con la nueva cantidad total
            $productoId = $item['id'];
            $stockTotal = $this->obtenerStockTotal($productoId);

            // Calcular cuánto hay en el carrito SIN incluir este item que estamos modificando
            $cantidadEnCarritoSinEsteItem = 0;
            foreach ($this->productosFactura as $i => $itemCarrito) {
                if ($itemCarrito['id'] == $productoId && $i != $index) {
                    $cantidadEnCarritoSinEsteItem += (int)$itemCarrito['cantidad'];
                }
            }

            // La nueva cantidad total sería: cantidad en carrito (sin este item) + nueva cantidad de este item
            $nuevaCantidadTotal = $cantidadEnCarritoSinEsteItem + $nuevaCantidad;

            if ($nuevaCantidadTotal > $stockTotal) {
                $this->mostrarModalSinStock = true;
                return;
            }
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

        // Recalcular todos los totales
        $this->calcularTotales();

        // Forzar actualización de la vista
        $this->dispatch('$refresh');
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
            
            // Actualizar el total del producto en el array
            $this->productosFactura[$index]['total'] = $subtotalConDescuento;

            // Calcular ISV sobre el subtotal con descuento
            $tasaIsv = $producto['isv'];
            $isvProducto = round($subtotalConDescuento * ($tasaIsv / 100), 2);
            $this->totalIsv = round($this->totalIsv + $isvProducto, 2);

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
                            'users_id' => Auth::id(),
                            'created_at' => now()
                        ]);

                        Log::info("DEBUG Descuento de adulto mayor creado exitosamente");
                    }
                }

                $indice++;
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

            // Calcular valores con descuento aplicado
            $subtotalOriginal = $cantidadATomar * $producto['precio'];
            $descuentoAplicado = $producto['descuento_aplicado'] ?? 0;
            $subtotalConDescuento = $producto['subtotal_con_descuento'] ?? $subtotalOriginal;
            $isvAplicado = $producto['isv'] ?? 0; // Tasa de ISV del producto
            $isvCalculado = $subtotalConDescuento * ($isvAplicado / 100);
            $totalFinal = $subtotalConDescuento + $isvCalculado;

            // Verificar si el registro ya existe para evitar duplicados
            $existeRegistro = DB::table('factura_has_producto')
                ->where('factura_id', $facturaId)
                ->where('producto_id', $producto['id'])
                ->where('seccion_id', $seccion->seccion_id)
                ->exists();

            if ($existeRegistro) {
                Log::warning("Registro duplicado detectado", [
                    'factura_id' => $facturaId,
                    'producto_id' => $producto['id'],
                    'seccion_id' => $seccion->seccion_id
                ]);
                continue; // Saltar esta sección si ya existe el registro
            }

            // Crear registro en factura_has_producto
            DB::table('factura_has_producto')->insert([
                'factura_id' => $facturaId,
                'producto_id' => $producto['id'],
                'Servicios_id' => null, // NULL para productos
                'seccion_id' => $seccion->seccion_id,
                'unidad_medida_id' => $this->obtenerUnidadMedidaDisponible(),
                'indice' => $indice,
                'numero_unidades_resta_inventario' => $cantidadATomar,
                'unidades_nota_credito_resta_inventario' => 0,
                'resta_inventario_total' => $cantidadATomar,
                'precio_unidad' => $producto['precio'],
                'cantidad' => $cantidadATomar,
                'subtotal' => $subtotalConDescuento,
                'descuento' => $descuentoAplicado,
                'isv_aplicado' => $isvAplicado, // Tasa de ISV
                'isv' => $isvCalculado, // Monto calculado de ISV
                'total' => $totalFinal,
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

        // Cargar productos y servicios de forma unificada
        $this->productosFacturaImpresa = DB::table('factura_has_producto as fp')
            ->leftJoin('producto as p', 'fp.producto_id', '=', 'p.id')
            ->leftJoin('servicios as s', 'fp.Servicios_id', '=', 's.id')
            ->leftJoin('isv as i_producto', 'p.isv_id', '=', 'i_producto.id')
            ->leftJoin('isv as i_servicio', 's.isv_id', '=', 'i_servicio.id')
            ->leftJoin('descuentos as d', function($join) use ($facturaId) {
                $join->where('d.factura_id', '=', $facturaId)
                     ->where(function($query) {
                         $query->whereNotNull('d.producto_id')
                               ->orWhere('d.Tipo_descuento', '=', 'Servicio');
                     });
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
                'd.monto_total as descuento_unitario'
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

        // Calcular cuánto ya tenemos en el carrito de este producto
        $cantidadEnCarrito = 0;
        foreach ($this->productosFactura as $item) {
            if ($item['id'] == $productoId) {
                $cantidadEnCarrito += (int)$item['cantidad'];
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
                      ->orWhere('codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%');
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
        $this->marcas = DB::table('marcas')->orderBy('nombre')->get();
        $this->categorias = DB::table('categorias')->orderBy('nombre')->get();
        $this->subcategorias = DB::table('sub_categorias')->orderBy('nombre')->get();
    }

    public function buscarProductos()
    {
        $query = Producto::with(['categoria', 'marca'])
            ->select('productos.*', DB::raw('COALESCE(stocks.cantidad, 0) as existencia'))
            ->leftJoin('stocks', function($join) {
                $join->on('productos.id', '=', 'stocks.producto_id')
                     ->where('stocks.bodega_id', '=', Auth::user()->bodega_id);
            });

        // Aplicar filtros
        if ($this->busquedaProductosServicios) {
            $query->where(function($q) {
                $q->where('productos.nombre', 'like', '%' . $this->busquedaProductosServicios . '%')
                  ->orWhere('productos.codigo', 'like', '%' . $this->busquedaProductosServicios . '%')
                  ->orWhere('productos.codigo_barra', 'like', '%' . $this->busquedaProductosServicios . '%')
                  ->orWhere('productos.descripcion', 'like', '%' . $this->busquedaProductosServicios . '%');
            });
        }

        if ($this->marcaSeleccionada) {
            $query->where('marca_id', $this->marcaSeleccionada);
        }

        if ($this->categoriaSeleccionada) {
            $query->where('categoria_id', $this->categoriaSeleccionada);
        }

        if ($this->subcategoriaSeleccionada) {
            $query->where('sub_categoria_id', $this->subcategoriaSeleccionada);
        }

        $this->resultadosBusqueda = $query->orderBy('nombre')->get();
    }

    public function updatedCategoriaSeleccionada($value)
    {
        $this->reset('subcategoriaSeleccionada');
        if ($value) {
            $this->subcategorias = DB::table('sub_categorias')
                ->where('categoria_id', $value)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->subcategorias = DB::table('sub_categorias')->orderBy('nombre')->get();
        }
        $this->buscarProductos();
    }

    public function buscarProductosModal()
    {
        $this->buscarProductos();
    }

    public function updatedMarcaSeleccionada()
    {
        $this->buscarProductos();
    }

    public function updatedSubcategoriaSeleccionada()
    {
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
}
