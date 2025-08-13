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
    public $estadoJornada = null;

    public function mount()
    {
        $this->cargarDatosUsuario();
        $this->cargarEstadisticas();
        $this->cargarDatosPorRol();
        $this->cargarEstadoJornada();
        $this->cargarEstadoCaja();
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
                    ->where('b.tienda_id', $usuario->tienda_id);
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
                ->where('rb.cantidad_disponible', '>', 0);
            
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

    public function cargarEstadoJornada()
    {
        $usuario = Auth::user();
        
        // Solo cargar estado de jornada si el usuario tiene tienda asignada
        if ($usuario->tienda_id) {
            $fechaActual = date('Y-m-d');
            
            // Buscar la jornada del día actual para la tienda del usuario
            $jornadaActual = DB::table('jornada')
                ->where('fecha', $fechaActual)
                ->where('tienda_id', $usuario->tienda_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($jornadaActual) {
                // Determinar el estado de la jornada
                $estado = 'cerrada'; // Por defecto cerrada
                $estado_codigo = 0;
                
                if ($jornadaActual->apertura == 1 && $jornadaActual->cierre == 0) {
                    $estado = 'abierta';
                    $estado_codigo = 1;
                } elseif ($jornadaActual->apertura == 0 && $jornadaActual->cierre == 1) {
                    $estado = 'cerrada';
                    $estado_codigo = 2;
                } elseif ($jornadaActual->apertura == 0 && $jornadaActual->cierre == 0) {
                    $estado = 'sin_aperturar';
                    $estado_codigo = 0;
                }

                // Obtener información del usuario que aperturó y cerró
                $usuarioApertura = null;
                $usuarioCierre = null;
                
                if ($jornadaActual->user_id_apertura) {
                    $usuarioApertura = DB::table('users')
                        ->where('id', $jornadaActual->user_id_apertura)
                        ->select('name')
                        ->first();
                }
                
                if ($jornadaActual->user_id_cierre) {
                    $usuarioCierre = DB::table('users')
                        ->where('id', $jornadaActual->user_id_cierre)
                        ->select('name')
                        ->first();
                }

                $this->estadoJornada = [
                    'id' => $jornadaActual->id,
                    'fecha' => $jornadaActual->fecha,
                    'estado' => $estado,
                    'estado_codigo' => $estado_codigo,
                    'estado_texto' => $this->obtenerTextoEstadoJornada($estado),
                    'apertura' => $jornadaActual->apertura,
                    'cierre' => $jornadaActual->cierre,
                    'usuario_apertura' => $usuarioApertura->name ?? null,
                    'usuario_cierre' => $usuarioCierre->name ?? null,
                    'comentario' => $jornadaActual->comentario,
                    'fecha_creacion' => $jornadaActual->created_at,
                    'fecha_actualizacion' => $jornadaActual->updated_at
                ];
            } else {
                // No hay jornada para hoy
                $this->estadoJornada = [
                    'id' => null,
                    'fecha' => $fechaActual,
                    'estado' => 'sin_jornada',
                    'estado_codigo' => -1,
                    'estado_texto' => 'Sin jornada creada',
                    'apertura' => 0,
                    'cierre' => 0,
                    'usuario_apertura' => null,
                    'usuario_cierre' => null,
                    'comentario' => null,
                    'fecha_creacion' => null,
                    'fecha_actualizacion' => null
                ];
            }
        }
    }

    private function obtenerTextoEstadoJornada($estado)
    {
        return match($estado) {
            'abierta' => 'Abierta',
            'cerrada' => 'Cerrada',
            'sin_aperturar' => 'Sin aperturar',
            'sin_jornada' => 'Sin jornada',
            default => 'Desconocido'
        };
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
        
        if ($tienePermisosCaja) {
            // Obtener la tienda actual del usuario
            $tiendaId = $usuario->tienda_id;
            
            // Fecha actual para filtrar por día en transcurso
            $fechaHoy = date('Y-m-d');
            
            // Buscar la caja del usuario en la tienda actual y fecha actual
            $cajaActual = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $tiendaId)
                ->whereDate('created_at', $fechaHoy)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($cajaActual) {
                $this->estadoCaja = [
                    'id' => $cajaActual->id,
                    'estado' => $cajaActual->estado_caja,
                    'estado_texto' => $this->obtenerTextoEstado($cajaActual->estado_caja),
                    'balance' => $cajaActual->balance,
                    'fecha_creacion' => $cajaActual->created_at,
                    'fecha_actualizacion' => $cajaActual->updated_at,
                    'tienda_id' => $cajaActual->tienda_id
                ];
            } else {
                // Si no se encuentra caja, establecer mensaje apropiado
                $this->estadoCaja = [
                    'mensaje' => 'No se encontró caja para el usuario en esta tienda hoy'
                ];
            }
        }
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

    private function obtenerTextoEstado($estado)
    {
        return match($estado) {
            0 => 'Sin usar',
            1 => 'Abierta',
            2 => 'Cerrada',
            default => 'Desconocido'
        };
    }

    public function render()
    {
        return view('livewire.dashboard-dinamico');
    }
}
