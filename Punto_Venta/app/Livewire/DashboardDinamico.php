<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Rol;

class DashboardDinamico extends Component
{
    public $datosUsuario;
    public $estadisticas = [];
    public $ventasRecientes = [];
    public $productosStockBajo = [];
    public $actividad = [];
    public $estadoCaja = null;

    // Datos para gráficos
    public $ventasSemana = [];
    public $diasSemanaLabels = [];
    public $topProductosLabels = [];
    public $topProductosData = [];
    public $metodosPagoLabels = [];
    public $metodosPagoData = [];
    public $topClientesLabels = [];
    public $topClientesData = [];
    public $chartKey; // Key única para forzar re-render de gráficos

    protected $listeners = ['actualizarDashboard'];

    public function mount()
    {
        $this->chartKey = uniqid('chart_');
        $this->cargarTodosDatos();
    }

    /**
     * Método llamado por wire:init
     */
    public function inicializarDashboard()
    {
        // Este método se ejecuta después de que el DOM esté listo
        // Emitir evento para inicializar gráficos
        $this->dispatch('dashboardRenderizado');
    }

    /**
     * Cargar todos los datos del dashboard
     */
    protected function cargarTodosDatos()
    {
        $this->cargarDatosUsuario();
        $this->cargarEstadisticas();
        $this->cargarDatosPorRol();
        $this->cargarEstadoCaja();
        $this->cargarDatosGraficos();
    }

    public function cargarDatosUsuario()
    {
        $usuario = Auth::user();
        $this->datosUsuario = [
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $usuario->rol->txt_nombre ?? 'Sin rol',
            'tienda' => $usuario->tienda->denominacion_social ?? 'Sin tienda',
            'ultimo_acceso' => $usuario->updated_at->format('d/m/Y H:i')
        ];
    }

    public function cargarEstadisticas()
    {
        $usuario = Auth::user();

        // Estadísticas básicas para todos
        $this->estadisticas = [
            'facturas_hoy' => DB::table('factura')
                ->whereDate('created_at', today())
                ->count(),

            'ventas_hoy' => DB::table('factura')
                ->whereDate('created_at', today())
                ->sum('total'),

            'ventas_mes' => DB::table('factura')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),

            'productos_activos' => DB::table('producto')
                ->where('estado_id', 1)
                ->count(),

            'usuarios_activos' => DB::table('users')
                ->where('estado_id', 1)
                ->count(),
        ];

        // Estadísticas específicas por permisos
        if ($this->usuarioTienePermisos(['Configuracion.Usuarios', 'Configuracion.Roles'])) {
            $this->estadisticas = array_merge($this->estadisticas, [
                'tiendas_activas' => DB::table('tienda')->where('estado_id', 1)->count(),
                'bodegas_activas' => DB::table('bodega')->where('estado_id', 1)->count(),
                'roles_activos' => DB::table('roles')->where('estado', 1)->count(),
                'menu_items' => DB::table('menu')->where('estado_id', 1)->count(),
            ]);
        }

        if ($this->usuarioTienePermisos(['Inventario.Producto', 'Inventario.CompraDeProductos', 'Inventario.Bodegas'])) {
            // Para stock bajo, filtrar por bodegas de la tienda del usuario
            $queryStockBajo = DB::table('recibido_bodega')
                ->where('cantidad_disponible', '<', 10);

            // Si el usuario no es Admin, filtrar por su tienda
            if (!$this->usuarioTienePermisos(['Configuracion.Usuarios', 'Configuracion.Roles']) && $usuario->tienda_id) {
                $queryStockBajo->join('seccion as s', 'recibido_bodega.seccion_id', '=', 's.id')
                    ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                    ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                    ->where('b.tienda_id', $usuario->tienda_id)
                    ->where('b.id', '!=', 2); // Excluir bodega ID 2 (productos sin venta)
            } else {
                // Para Admin, también excluir bodega ID 2
                $queryStockBajo->join('seccion as s', 'recibido_bodega.seccion_id', '=', 's.id')
                    ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                    ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                    ->where('b.id', '!=', 2); // Excluir bodega ID 2 (productos sin venta)
            }

            // Para recepciones de productos del mes, filtrar por usuario actual si no es Admin
            $queryRecepciones = DB::table('recibido_bodega')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);

            if (!$this->usuarioTienePermisos(['Configuracion.Usuarios', 'Configuracion.Roles'])) {
                $queryRecepciones->where('users_registro_id', $usuario->id);
            }

            $this->estadisticas = array_merge($this->estadisticas, [
                'stock_bajo' => $queryStockBajo->count(),
                'recepciones_mes' => $queryRecepciones->count(),
            ]);
        }
    }

    public function cargarDatosPorRol()
    {
        $usuario = Auth::user();

        // Ventas recientes (para usuarios con permisos de ventas y admin)
        if ($this->usuarioTienePermisos(['SalaDeVentas.Ventas', 'Inventario.Producto'])) {
            $this->ventasRecientes = DB::table('factura as f')
                ->join('users as u', 'f.users_id', '=', 'u.id')
                ->select(
                    'f.numero_factura',
                    'f.total',
                    'f.created_at',
                    'u.name as usuario',
                    'f.nombre_cliente'
                )
                ->orderBy('f.created_at', 'desc')
                ->limit(5)
                ->get();
        }

        // Productos con stock bajo (para usuarios con permisos de inventario y admin)
        if ($this->usuarioTienePermisos(['Inventario.Producto', 'Inventario.CompraDeProductos', 'Inventario.Bodegas'])) {
            $queryProductosStockBajo = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('rb.cantidad_disponible', '<', 10)
                ->where('rb.cantidad_disponible', '>', 0)
                ->where('b.id', '!=', 2); // Excluir bodega ID 2 (productos sin venta)

            // Si el usuario no tiene permisos administrativos, filtrar por su tienda
            if (!$this->usuarioTienePermisos(['Configuracion.Usuarios', 'Configuracion.Roles']) && $usuario->tienda_id) {
                $queryProductosStockBajo->where('b.tienda_id', $usuario->tienda_id);
            }

            $this->productosStockBajo = $queryProductosStockBajo
                ->select(
                    'p.nombre as producto',
                    'rb.cantidad_disponible',
                    'b.nombre as bodega',
                    's.descripcion as seccion'
                )
                ->orderBy('rb.cantidad_disponible', 'asc')
                ->limit(8)
                ->get();
        }

        // Actividad reciente del sistema (para admin)
        if ($this->usuarioTienePermisos(['Configuracion.Usuarios', 'Configuracion.Roles'])) {
            $this->actividad = collect([
                [
                    'tipo' => 'factura',
                    'descripcion' => 'Nueva factura generada',
                    'usuario' => 'Pedro Ruiz',
                    'fecha' => now()->subMinutes(15),
                    'icono' => '🧾'
                ],
                [
                    'tipo' => 'producto',
                    'descripcion' => 'Producto agregado al inventario',
                    'usuario' => 'Ana López',
                    'fecha' => now()->subHours(2),
                    'icono' => '📦'
                ],
                [
                    'tipo' => 'usuario',
                    'descripcion' => 'Nuevo usuario registrado',
                    'usuario' => 'Sistema',
                    'fecha' => now()->subHours(4),
                    'icono' => '👤'
                ]
            ]);
        }
    }



    public function cargarEstadoCaja()
    {
        $usuario = Auth::user();

        // Verificar si el usuario tiene permisos específicos para ver estado de caja
        $tienePermisosCaja = $this->usuarioTienePermisos([
            'SalaDeVentas.Ventas',
            'Caja.RecibidoDeEfectivo',
            'Caja.EntregaDeEfectivo',
            'Caja.SaldoInicial',
            'Caja.CierreDeCaja'
        ]);

        if ($tienePermisosCaja && $usuario->tienda_id) {
            // Obtener la tienda actual del usuario
            $tiendaId = $usuario->tienda_id;

            // Fecha actual para filtrar por día en transcurso
            $fechaHoy = date('Y-m-d');

            // Buscar la caja actual del usuario en la tienda
            $cajaActual = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $tiendaId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($cajaActual) {
                // Obtener la última apertura de caja para obtener la fecha
                $ultimaApertura = DB::table('apertura_caja')
                    ->where('caja_id', $cajaActual->id)
                    ->orderBy('fecha_apertura', 'desc')
                    ->first();

                // Verificar si tiene caja abierta hoy
                $tieneAperturaHoy = $ultimaApertura &&
                    date('Y-m-d', strtotime($ultimaApertura->fecha_apertura)) == $fechaHoy;

                // Determinar el tipo de estado
                $esCajaHoy = $tieneAperturaHoy;

                // Calcular balances por tipo de pago basándose en transacciones del día de la jornada abierta
                $fechaJornada = $this->obtenerFechaJornadaAbierta();
                $balancesPorTipo = DB::table('transaccion')
                    ->where('caja_id', $cajaActual->id)
                    ->whereDate('created_at', $fechaJornada)
                    ->selectRaw('
                        IFNULL(SUM(efectivo), 0) as balance_efectivo,
                        IFNULL(SUM(tarjeta), 0) as balance_tarjeta,
                        IFNULL(SUM(cheque), 0) as balance_cheque,
                        IFNULL(SUM(transferencia), 0) as balance_transferencia
                    ')
                    ->first();

                // Balance total
                $balanceTotal = ($balancesPorTipo->balance_efectivo ?? 0) +
                               ($balancesPorTipo->balance_tarjeta ?? 0) +
                               ($balancesPorTipo->balance_cheque ?? 0) +
                               ($balancesPorTipo->balance_transferencia ?? 0);

                $this->estadoCaja = [
                    'id' => $cajaActual->id,
                    'estado' => $cajaActual->estado_caja,
                    'estado_texto' => $this->obtenerTextoEstadoCaja($cajaActual->estado_caja, $esCajaHoy),
                    'balance' => $cajaActual->balance,
                    'balance_efectivo' => $balancesPorTipo->balance_efectivo ?? 0,
                    'balance_tarjeta' => $balancesPorTipo->balance_tarjeta ?? 0,
                    'balance_cheque' => $balancesPorTipo->balance_cheque ?? 0,
                    'balance_transferencia' => $balancesPorTipo->balance_transferencia ?? 0,
                    'balance_total_calculado' => $balanceTotal,
                    'fecha_apertura' => $ultimaApertura ? $ultimaApertura->fecha_apertura : null,
                    'fecha_actualizacion' => $cajaActual->updated_at,
                    'tienda_id' => $cajaActual->tienda_id,
                    'es_caja_hoy' => $esCajaHoy,
                    'tiene_caja_hoy' => $tieneAperturaHoy
                ];
            } else {
                // Si no se encuentra ninguna caja
                $this->estadoCaja = [
                    'id' => null,
                    'estado' => 0,
                    'estado_texto' => 'Sin caja creada',
                    'balance' => 0,
                    'balance_efectivo' => 0,
                    'balance_tarjeta' => 0,
                    'balance_cheque' => 0,
                    'balance_transferencia' => 0,
                    'balance_total_calculado' => 0,
                    'fecha_apertura' => null,
                    'fecha_actualizacion' => null,
                    'tienda_id' => $tiendaId,
                    'es_caja_hoy' => false,
                    'tiene_caja_hoy' => false,
                    'mensaje' => 'No se encontró caja para el usuario en esta tienda'
                ];
            }
        }
    }

    private function obtenerTextoEstadoCaja($estado, $esCajaHoy = true)
    {
        return match($estado) {
            1 => 'Abierta',
            2 => 'Cerrada',
            0 => 'Sin usar',
            default => 'Desconocido'
        };
    }

    /**
     * Verifica si el usuario tiene alguno de los permisos especificados
     * @param array $permisos Array de routes/permisos a verificar
     * @return bool
     */
    private function usuarioTienePermisos($permisos)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->roles_id) {
            return false;
        }

        // Verificar si es admin (tiene acceso a todo)
        $rolNombre = $usuario->rol->txt_nombre ?? '';
        $esAdmin = in_array($rolNombre, ['Admin', 'Administrador']);

        if ($esAdmin) {
            return true;
        }

        // Verificar permisos específicos a través de la tabla rol_permiso y menu
        $tienePermiso = DB::table('menu')
            ->join('rol_permiso', 'menu.id', '=', 'rol_permiso.menu_id')
            ->where('rol_permiso.rol_id', $usuario->roles_id)
            ->where('rol_permiso.estado', 1)
            ->where('menu.estado_id', 1)
            ->whereIn('menu.route', $permisos)
            ->exists();

        return $tienePermiso;
    }

    /**
     * Método público para verificar permisos desde la vista Blade
     * @param array|string $permisos Permiso o array de permisos a verificar
     * @return bool
     */
    public function tienePermiso($permisos)
    {
        if (is_string($permisos)) {
            $permisos = [$permisos];
        }

        return $this->usuarioTienePermisos($permisos);
    }

    /**
     * Obtiene la fecha de la jornada que esté abierta
     *
     * @return string
     */
    private function obtenerFechaJornadaAbierta()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            return Carbon::now()->format('Y-m-d');
        }

        // Buscar jornada aperturada (puede ser de cualquier fecha)
        $jornadaAbierta = DB::table('jornada')
            ->where('tienda_id', $usuario->tienda_id)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->first();

        if ($jornadaAbierta) {
            return Carbon::parse($jornadaAbierta->fecha)->format('Y-m-d');
        }

        // Si no hay jornada abierta, usar fecha actual
        return Carbon::now()->format('Y-m-d');
    }

    /**
     * Cargar datos para los gráficos del dashboard
     */
    public function cargarDatosGraficos()
    {
        // Generar nueva key para forzar re-render de los gráficos
        $this->chartKey = uniqid('chart_');

        $usuario = Auth::user();

        // 1. Ventas de la última semana (últimos 7 días) con nombres de días dinámicos
        if ($this->usuarioTienePermisos(['SalaDeVentas.Ventas'])) {
            $ventasPorDia = DB::table('factura')
                ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(total) as total'))
                ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('fecha', 'asc')
                ->get()
                ->keyBy('fecha');

            // Llenar con 0 los días sin ventas y generar labels dinámicos
            $this->ventasSemana = [];
            $this->diasSemanaLabels = [];
            for ($i = 6; $i >= 0; $i--) {
                $fecha = Carbon::now()->subDays($i);
                $fechaStr = $fecha->format('Y-m-d');

                // Nombre del día en español
                $nombreDia = $fecha->locale('es')->isoFormat('dddd');
                $this->diasSemanaLabels[] = ucfirst($nombreDia);

                $this->ventasSemana[] = $ventasPorDia->has($fechaStr)
                    ? round($ventasPorDia[$fechaStr]->total, 2)
                    : 0;
            }
        }

        // 2. Top 5 productos más vendidos (del mes actual)
        if ($this->usuarioTienePermisos(['Inventario.Producto', 'SalaDeVentas.Ventas'])) {
            $topProductos = DB::table('factura_has_producto as fhp')
                ->join('factura as f', 'fhp.factura_id', '=', 'f.id')
                ->join('producto as p', 'fhp.producto_id', '=', 'p.id')
                ->select('p.nombre', DB::raw('SUM(fhp.cantidad) as total_vendido'))
                ->whereMonth('f.created_at', now()->month)
                ->whereYear('f.created_at', now()->year)
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();

            $this->topProductosLabels = $topProductos->pluck('nombre')->toArray();
            $this->topProductosData = $topProductos->pluck('total_vendido')->toArray();

            // Si no hay datos, poner valores por defecto
            if (empty($this->topProductosLabels)) {
                $this->topProductosLabels = ['Sin datos'];
                $this->topProductosData = [0];
            }
        }

        // 3. Ventas por método de pago (hoy) - Dinámico para cualquier tipo de pago
        if ($this->usuarioTienePermisos(['SalaDeVentas.Ventas', 'Caja.RecibidoDeEfectivo'])) {
            $metodosPago = DB::table('factura_has_pago as fhp')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->join('factura as f', 'fhp.factura_id', '=', 'f.id')
                ->select('tp.nombre', DB::raw('SUM(fhp.pago_recibido) as total'))
                ->whereDate('f.created_at', today())
                ->groupBy('tp.id', 'tp.nombre')
                ->orderByDesc('total')
                ->limit(4)
                ->get();

            if ($metodosPago->isNotEmpty()) {
                $this->metodosPagoLabels = $metodosPago->pluck('nombre')->toArray();
                $this->metodosPagoData = $metodosPago->pluck('total')->map(function($value) {
                    return round($value, 2);
                })->toArray();
            } else {
                // Si no hay datos de hoy, mostrar valores vacíos
                $this->metodosPagoLabels = ['Sin datos'];
                $this->metodosPagoData = [0];
            }
        }

        // 4. Top 5 clientes que más compran (basado en nombre_cliente de factura)
        if ($this->usuarioTienePermisos(['SalaDeVentas.Ventas', 'Clientes.Clientes'])) {
            $topClientes = DB::table('factura as f')
                ->select(
                    DB::raw('COALESCE(NULLIF(f.nombre_cliente, ""), "CONSUMIDOR FINAL") as cliente_nombre'),
                    DB::raw('COUNT(f.id) as total_compras'),
                    DB::raw('SUM(f.total) as total_gastado')
                )
                ->whereMonth('f.created_at', now()->month)
                ->whereYear('f.created_at', now()->year)
                ->whereNotNull('f.nombre_cliente')
                ->groupBy('cliente_nombre')
                ->orderByDesc('total_gastado')
                ->limit(5)
                ->get();

            $this->topClientesLabels = $topClientes->pluck('cliente_nombre')->toArray();
            $this->topClientesData = $topClientes->pluck('total_gastado')->map(function($value) {
                return round($value, 2);
            })->toArray();

            // Si no hay datos, poner valores por defecto
            if (empty($this->topClientesLabels)) {
                $this->topClientesLabels = ['Sin datos'];
                $this->topClientesData = [0];
            }
        }
    }

    /**
     * Actualizar todos los datos del dashboard
     */
    public function actualizarDatos()
    {
        // Recargar todas las estadísticas
        $this->cargarTodosDatos();

        // Emitir evento para que Alpine.js recargue los gráficos
        $this->dispatch('datosActualizados');
    }

    public function actualizarDashboard()
    {
        // Recargar todas las estadísticas
        $this->cargarTodosDatos();

        // Emitir evento para que Alpine.js recargue los gráficos
        $this->dispatch('datosActualizados');
    }

    public function render()
    {
        // Recargar datos cada vez que se renderiza (cuando regresas a la vista)
        $this->cargarTodosDatos();

        // Emitir evento para que Alpine.js reinicialice los gráficos
        $this->dispatch('dashboardRenderizado');

        return view('livewire.dashboard-dinamico');
    }
}
