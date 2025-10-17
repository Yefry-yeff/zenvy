<?php

namespace App\Livewire\Reportes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RecibidoBodega;
use App\Models\Producto as ProductoModel;
use App\Models\Marca;
use App\Models\Compra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class RegaliasYRequisiciones extends Component
{
    use WithPagination;

    // Propiedades de paginación y ordenamiento
    public $registrosPorPagina = 10;
    public $ordenarPor = 'fecha_recibido';
    public $direccionOrden = 'desc';
    public $page = 1;

    // Filtros
    public $filtroProducto = '';
    public $filtroProveedor = '';
    public $filtroUsuario = '';
    public $filtroSegmento = '';
    public $filtroSeccion = '';
    public $filtroFechaDesde = '';
    public $filtroFechaHasta = '';

    // Datos para filtros
    public $proveedores = [];
    public $usuarios = [];
    public $segmentos = [];
    public $secciones = [];

    protected $queryString = [];

    public function mount()
    {
        $this->cargarDatosFiltros();
        
        // Debug: Verificar que hay productos en bodega 2
        $totalProductosBodega2 = DB::table('recibido_bodega as rb')
            ->join('seccion as sec', 'rb.seccion_id', '=', 'sec.id')
            ->join('segmento as seg', 'sec.segmento_id', '=', 'seg.id')
            ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
            ->where('b.id', 2)
            ->count();
            
        Log::info('Total productos en bodega 2', [
            'total' => $totalProductosBodega2,
            'user_id' => Auth::id()
        ]);
    }

    public function cargarDatosFiltros()
    {
        try {
            // Cargar proveedores (clientes tipo 3) que tienen compras con productos en bodega 2
            $this->proveedores = DB::table('compra as c')
                ->join('compra_has_producto as chp', 'c.id', '=', 'chp.compra_id')
                ->join('recibido_bodega as rb', function($join) {
                    $join->on('chp.producto_id', '=', 'rb.producto_id')
                         ->whereRaw('DATE(rb.created_at) >= DATE(chp.created_at) - INTERVAL 1 DAY')
                         ->whereRaw('DATE(rb.created_at) <= DATE(chp.created_at) + INTERVAL 1 DAY');
                })
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->join('cliente as cli', 'c.cliente_id', '=', 'cli.id')
                ->where('b.id', 2)
                ->where('cli.tipo_cliente_id', 3) // Solo proveedores
                ->select('cli.id', 'cli.nombre')
                ->distinct()
                ->orderBy('cli.nombre')
                ->get();

            // Cargar usuarios que han registrado productos en bodega 2
            $this->usuarios = DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->join('users as u', 'rb.users_registro_id', '=', 'u.id')
                ->where('b.id', 2)
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name')
                ->get();

            // Cargar segmentos de bodega 2
            $this->segmentos = DB::table('segmento as seg')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.id', 2)
                ->select('seg.id', 'seg.descripcion')
                ->orderBy('seg.descripcion')
                ->get();

            // Cargar secciones de bodega 2
            $this->secciones = DB::table('seccion as s')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.id', 2)
                ->select('s.id', 's.descripcion')
                ->orderBy('s.descripcion')
                ->get();

        } catch (\Exception $e) {
            Log::error('Error al cargar datos de filtros', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cargarDatos($paginacion = true)
    {
        try {
            $user = Auth::user();

            $query = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as sec', 'rb.seccion_id', '=', 'sec.id')
                ->join('segmento as seg', 'sec.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->join('tienda as t', 'b.tienda_id', '=', 't.id')
                ->leftJoin('users as u', 'rb.users_registro_id', '=', 'u.id')
                ->leftJoin('marca as m', 'p.marca_id', '=', 'm.id')
                ->leftJoin('unidad_medida as um', 'rb.unidad_medida_id', '=', 'um.id')
                ->leftJoin('unidad_medida as umv', 'p.unidad_medida_venta_id', '=', 'umv.id')
                ->leftJoin('compra_has_producto as chp', 'rb.producto_id', '=', 'chp.producto_id')
                ->leftJoin('compra as c', function($join) {
                    $join->on('chp.compra_id', '=', 'c.id')
                         ->whereRaw('DATE(rb.created_at) >= DATE(c.created_at) - INTERVAL 1 DAY')
                         ->whereRaw('DATE(rb.created_at) <= DATE(c.created_at) + INTERVAL 1 DAY');
                })
                ->leftJoin('cliente as cli', function($join) {
                    $join->on('c.cliente_id', '=', 'cli.id')
                         ->where('cli.tipo_cliente_id', '=', 3);
                })
                ->select(
                    'rb.id',
                    'rb.cantidad_inicial_seccion',
                    'rb.cantidad_disponible',
                    'rb.fecha_recibido',
                    'rb.fecha_expiracion',
                    'rb.comentario',
                    'rb.created_at',
                    'rb.estado_id',
                    'p.id as producto_id',
                    'p.nombre as producto_nombre',
                    'p.descripcion as producto_descripcion',
                    'p.codigo_barra',
                    'm.nombre as marca_nombre',
                    'b.nombre as bodega_nombre',
                    't.denominacion_social as tienda_nombre',
                    'seg.descripcion as segmento_descripcion',
                    'sec.descripcion as seccion_descripcion',
                    'um.nombre as unidad_medida',
                    'umv.nombre as unidad_medida_venta',
                    DB::raw("COALESCE(u.name, 'Sistema') as usuario_registro"),
                    'cli.nombre as proveedor',
                    'c.numero_factura',
                    DB::raw("CASE 
                        WHEN c.id IS NOT NULL THEN CONCAT('Compra - ', c.numero_factura)
                        WHEN rb.comentario LIKE '%traslado%' OR rb.comentario LIKE '%Traslado%' THEN 'Traslado'
                        ELSE 'Ajuste Manual'
                    END as origen")
                )
                ->where('b.id', 2); // Solo bodega 2
                // REMOVIDO: ->where('rb.estado_id', 1); // Mostrar todos los registros

            // Filtrar por tienda si no es admin
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $query->where('b.tienda_id', $user->tienda_id);
            }

            // Aplicar filtros
            if (!empty($this->filtroProducto)) {
                $query->where(function($q) {
                    $q->where('p.nombre', 'like', '%' . $this->filtroProducto . '%')
                      ->orWhere('p.codigo_barra', 'like', '%' . $this->filtroProducto . '%')
                      ->orWhere('p.codigo_estatal', 'like', '%' . $this->filtroProducto . '%');
                });
            }

            if (!empty($this->filtroProveedor)) {
                $query->where('cli.id', $this->filtroProveedor);
            }

            if (!empty($this->filtroUsuario)) {
                $query->where('u.id', $this->filtroUsuario);
            }

            if (!empty($this->filtroSegmento)) {
                $query->where('seg.id', $this->filtroSegmento);
            }

            if (!empty($this->filtroSeccion)) {
                $query->where('sec.id', $this->filtroSeccion);
            }

            if (!empty($this->filtroFechaDesde)) {
                $query->whereDate('rb.fecha_recibido', '>=', $this->filtroFechaDesde);
            }

            if (!empty($this->filtroFechaHasta)) {
                $query->whereDate('rb.fecha_recibido', '<=', $this->filtroFechaHasta);
            }

            // Agrupar por rb.id para evitar duplicados del LEFT JOIN con compra_has_producto
            $query->groupBy(
                'rb.id',
                'rb.cantidad_inicial_seccion',
                'rb.cantidad_disponible',
                'rb.fecha_recibido',
                'rb.fecha_expiracion',
                'rb.comentario',
                'rb.created_at',
                'rb.estado_id',
                'p.id',
                'p.nombre',
                'p.descripcion',
                'p.codigo_barra',
                'm.nombre',
                'b.nombre',
                't.denominacion_social',
                'seg.descripcion',
                'sec.descripcion',
                'um.nombre',
                'umv.nombre',
                'u.name',
                'cli.nombre',
                'c.numero_factura',
                'c.id'
            );

            // Ordenamiento
            $campoOrden = 'rb.' . $this->ordenarPor;
            if ($this->ordenarPor === 'producto_nombre') {
                $campoOrden = 'p.nombre';
            } elseif ($this->ordenarPor === 'usuario_registro') {
                $campoOrden = 'u.name';
            } elseif ($this->ordenarPor === 'proveedor') {
                $campoOrden = 'cli.nombre';
            }

            $query->orderBy($campoOrden, $this->direccionOrden);

            // Debug: Log de la consulta
            Log::info('Consulta historial bodega 2', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'user_rol' => $user->rol ? $user->rol->txt_nombre : 'Sin rol',
                'user_tienda_id' => $user->tienda_id ?? 'Sin tienda'
            ]);

            // Paginación
            if ($paginacion) {
                $resultado = $query->paginate($this->registrosPorPagina);
                Log::info('Resultado paginación', [
                    'total' => $resultado->total(),
                    'per_page' => $resultado->perPage(),
                    'current_page' => $resultado->currentPage()
                ]);
                return $resultado;
            } else {
                return $query->get();
            }

        } catch (\Exception $e) {
            Log::error('Error al cargar historial bodega 2', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($paginacion) {
                return new LengthAwarePaginator([], 0, $this->registrosPorPagina);
            } else {
                return collect([]);
            }
        }
    }

    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
        }
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->filtroProducto = '';
        $this->filtroProveedor = '';
        $this->filtroUsuario = '';
        $this->filtroSegmento = '';
        $this->filtroSeccion = '';
        $this->filtroFechaDesde = '';
        $this->filtroFechaHasta = '';
        $this->resetPage();
    }

    public function updatingFiltroProducto()
    {
        $this->resetPage();
    }

    public function updatingFiltroProveedor()
    {
        $this->resetPage();
    }

    public function updatingFiltroUsuario()
    {
        $this->resetPage();
    }

    public function updatingFiltroSegmento()
    {
        $this->resetPage();
    }

    public function updatingFiltroSeccion()
    {
        $this->resetPage();
    }

    public function descargarExcel()
    {
        try {
            // Obtener todos los registros según los filtros actuales (sin paginación)
            $registros = $this->cargarDatos(false);

            $fechaGeneracion = now()->format('d/m/Y H:i:s');
            $totalRegistros = $registros->count();
            $filtrosAplicados = $this->obtenerFiltrosAplicados();
            $usuarioReporte = Auth::user() ? Auth::user()->name : 'Invitado';

            // Crear el contenido HTML para Excel
            $html = view('exports.regalias-y-requisiciones', [
                'registros' => $registros,
                'fechaGeneracion' => $fechaGeneracion,
                'totalRegistros' => $totalRegistros,
                'filtrosAplicados' => $filtrosAplicados,
                'usuarioReporte' => $usuarioReporte
            ])->render();

            // Generar nombre de archivo
            $nombreArchivo = 'regalias_y_requisiciones_' . now()->format('Y-m-d_His') . '.xls';

            // Retornar descarga
            return response()->streamDownload(function() use ($html) {
                echo $html;
            }, $nombreArchivo, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            Log::error('Error generating Excel', ['error' => $e->getMessage()]);
        }
    }

    private function obtenerFiltrosAplicados()
    {
        $filtros = [];
        if (!empty($this->filtroProducto)) {
            $filtros[] = "Producto: '{$this->filtroProducto}'";
        }
        if (!empty($this->filtroProveedor)) {
            $proveedor = collect($this->proveedores)->firstWhere('id', $this->filtroProveedor);
            if ($proveedor) {
                $filtros[] = "Proveedor: '{$proveedor->nombre}'";
            }
        }
        if (!empty($this->filtroUsuario)) {
            $usuario = collect($this->usuarios)->firstWhere('id', $this->filtroUsuario);
            if ($usuario) {
                $filtros[] = "Usuario: '{$usuario->name}'";
            }
        }
        if (!empty($this->filtroSegmento)) {
            $segmento = collect($this->segmentos)->firstWhere('id', $this->filtroSegmento);
            if ($segmento) {
                $filtros[] = "Segmento: '{$segmento->descripcion}'";
            }
        }
        if (!empty($this->filtroSeccion)) {
            $seccion = collect($this->secciones)->firstWhere('id', $this->filtroSeccion);
            if ($seccion) {
                $filtros[] = "Sección: '{$seccion->descripcion}'";
            }
        }
        if (!empty($this->filtroFechaDesde)) {
            $filtros[] = "Desde: '{$this->filtroFechaDesde}'";
        }
        if (!empty($this->filtroFechaHasta)) {
            $filtros[] = "Hasta: '{$this->filtroFechaHasta}'";
        }
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }

    public function render()
    {
        $historial = $this->cargarDatos();

        return view('livewire.reportes.regalias-y-requisiciones', [
            'historial' => $historial
        ]);
    }
}
