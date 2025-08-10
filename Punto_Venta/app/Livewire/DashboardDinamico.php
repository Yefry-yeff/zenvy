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
        $rolNombre = $usuario->rol->txt_nombre ?? '';

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

        // Estadísticas específicas por rol
        if (in_array($rolNombre, ['Admin', 'Administrador'])) {
            $this->estadisticas = array_merge($this->estadisticas, [
                'tiendas_activas' => DB::table('tienda')->where('estado_id', 1)->count(),
                'bodegas_activas' => DB::table('bodega')->where('estado_id', 1)->count(),
                'roles_activos' => DB::table('roles')->where('estado', 1)->count(),
                'menu_items' => DB::table('menu')->where('estado_id', 1)->count(),
            ]);
        }

        if (in_array($rolNombre, ['Inventario', 'Admin', 'Administrador'])) {
            // Para stock bajo, filtrar por bodegas de la tienda del usuario
            $queryStockBajo = DB::table('recibido_bodega')
                ->where('cantidad_disponible', '<', 10);
            
            // Si el usuario no es Admin, filtrar por su tienda
            if (!in_array($rolNombre, ['Admin', 'Administrador']) && $usuario->tienda_id) {
                $queryStockBajo->join('seccion as s', 'recibido_bodega.seccion_id', '=', 's.id')
                    ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                    ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                    ->where('b.tienda_id', $usuario->tienda_id);
            }
            
            // Para recepciones de productos del mes, filtrar por usuario actual si no es Admin
            $queryRecepciones = DB::table('recibido_bodega')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            
            if (!in_array($rolNombre, ['Admin', 'Administrador'])) {
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
        $rolNombre = $usuario->rol->txt_nombre ?? '';

        // Ventas recientes (para roles de ventas y admin)
        if (in_array($rolNombre, ['Facturador', 'Admin', 'Administrador', 'Inventario'])) {
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

        // Productos con stock bajo (para inventario y admin)
        if (in_array($rolNombre, ['Inventario', 'Admin', 'Administrador'])) {
            $queryProductosStockBajo = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('rb.cantidad_disponible', '<', 10)
                ->where('rb.cantidad_disponible', '>', 0);
            
            // Si el usuario no es Admin, filtrar por su tienda
            if (!in_array($rolNombre, ['Admin', 'Administrador']) && $usuario->tienda_id) {
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
        if (in_array($rolNombre, ['Admin', 'Administrador'])) {
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
        $rolNombre = $usuario->rol->txt_nombre ?? '';

        // Solo cargar estado de caja si el usuario tiene permisos de caja
        // Verificar si el rol tiene permisos relacionados con caja
        $rolesCaja = ['Cajero', 'Facturador', 'Admin', 'Administrador'];
        
        if (in_array($rolNombre, $rolesCaja)) {
            // Buscar la caja más reciente del usuario
            $cajaActual = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($cajaActual) {
                $this->estadoCaja = [
                    'id' => $cajaActual->id,
                    'estado' => $cajaActual->estado_caja,
                    'estado_texto' => $this->obtenerTextoEstado($cajaActual->estado_caja),
                    'balance' => $cajaActual->balance,
                    'fecha_creacion' => $cajaActual->created_at,
                    'fecha_actualizacion' => $cajaActual->updated_at
                ];
            }
        }
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
