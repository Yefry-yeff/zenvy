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

    public function mount()
    {
        $this->cargarDatosUsuario();
        $this->cargarEstadisticas();
        $this->cargarDatosPorRol();
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
            $this->estadisticas = array_merge($this->estadisticas, [
                'stock_bajo' => DB::table('recibido_bodega')
                    ->where('cantidad_disponible', '<', 10)
                    ->count(),
                
                'compras_mes' => DB::table('compra')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
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
            $this->productosStockBajo = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('rb.cantidad_disponible', '<', 10)
                ->where('rb.cantidad_disponible', '>', 0)
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

    public function render()
    {
        return view('livewire.dashboard-dinamico');
    }
}
