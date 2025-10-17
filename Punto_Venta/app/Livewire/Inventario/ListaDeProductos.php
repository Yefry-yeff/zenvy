<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RecibidoBodega;
use App\Models\Bodega;
use App\Models\Marca;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListaDeProductos extends Component
{
    use WithPagination;

    // Propiedades de paginación y ordenamiento
    public $registrosPorPagina = 10;
    public $ordenarPor = 'fecha_recibido';
    public $direccionOrden = 'desc';
    public $page = 1; // Agregamos la propiedad page

    // Filtros
    public $filtroProducto = '';
    public $filtroBodega = '';
    public $filtroEstado = '';
    public $filtroMarca = '';

    // Datos
    public $bodegas = [];
    public $marcas = [];

    // REMOVIDO: protected $queryString - Ya no persiste parámetros en URL
    // Los filtros se manejarán solo con sesión

    // Deshabilitar persistencia de paginación en URL
    protected $queryString = [];

    // Sobrescribir método para que la paginación no use URL
    public function getPage()
    {
        return $this->page;
    }

    // Sobrescribir método para cambiar página sin URL
    public function setPage($page)
    {
        $this->page = $page;
    }

    // Sobrescribir resetPage para que funcione con nuestra propiedad
    public function resetPage()
    {
        $this->page = 1;
    }

    // Métodos para manejar navegación de páginas
    public function nextPage()
    {
        $this->page++;
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function gotoPage($page)
    {
        $this->page = $page;
    }

    public function mount()
    {
        // Restaurar filtros desde sesión si existen
        $filtrosSesion = session('productos_filtros');
        if ($filtrosSesion) {
            $this->filtroProducto = $filtrosSesion['filtroProducto'] ?? '';
            $this->filtroBodega = $filtrosSesion['filtroBodega'] ?? '';
            $this->filtroEstado = $filtrosSesion['filtroEstado'] ?? '';
            $this->filtroMarca = $filtrosSesion['filtroMarca'] ?? '';
            $this->ordenarPor = $filtrosSesion['ordenarPor'] ?? 'fecha_recibido';
            $this->direccionOrden = $filtrosSesion['direccionOrden'] ?? 'desc';
            $this->page = $filtrosSesion['page'] ?? 1;
        }

        // Detectar si hay parámetros de otra vista
        $parametrosURL = request()->query();
        $parametrosOtraVista = ['busqueda', 'filtroFecha']; // Parámetros exclusivos de compra-de-productos

        foreach ($parametrosOtraVista as $param) {
            if (isset($parametrosURL[$param])) {
                // Si hay parámetros de otra vista, hacer redirect limpio
                return redirect()->route('dashboard');
            }
        }

        // Verificar si se necesita resetear parámetros
        if (session('reset_lista_productos_params')) {
            session()->forget('reset_lista_productos_params');
            $this->ordenarPor = 'fecha_recibido';
            $this->direccionOrden = 'desc';
            $this->filtroProducto = '';
            $this->filtroBodega = '';
            $this->filtroEstado = '';
            $this->filtroMarca = '';
            $this->page = 1; // Resetear también la página
            $this->resetPage();
        }

        session(['current_component' => 'lista-de-productos']);
        $this->cargarFiltros();
    }

    public function boot()
    {
        // Simplemente marcar el componente activo - DynamicContent maneja la limpieza URL
        session(['current_component' => 'lista-de-productos']);
    }

    public function hydrate()
    {
        // Solo verificar compatibilidad básica - DynamicContent maneja los redirects
        $parametrosURL = request()->query();

        if (!empty($parametrosURL)) {
            // Solo verificar parámetros críticos que definitivamente no pertenecen aquí
            $parametrosProhibidos = ['busqueda', 'filtroFecha'];

            foreach ($parametrosProhibidos as $param) {
                if (isset($parametrosURL[$param])) {
                    // Dejar que DynamicContent maneje el redirect
                    return;
                }
            }
        }
    }

    public function dehydrate()
    {
        // Guardar filtros en sesión en cada actualización
        session(['productos_filtros' => [
            'filtroProducto' => $this->filtroProducto,
            'filtroBodega' => $this->filtroBodega,
            'filtroEstado' => $this->filtroEstado,
            'filtroMarca' => $this->filtroMarca,
            'ordenarPor' => $this->ordenarPor,
            'direccionOrden' => $this->direccionOrden,
            'page' => $this->page
        ]]);
    }

    // Limpiar completamente al destruir el componente
    public function destroying()
    {
        // Limpiar todas las sesiones relacionadas incluyendo filtros
        session()->forget(['lista_productos_ordenamiento', 'current_component', 'reset_lista_productos_params', 'productos_filtros']);

        // Si hay parámetros en URL, forzar redirect limpio al dashboard
        if (request()->has(['ordenarPor', 'direccionOrden', 'filtroProducto', 'filtroBodega', 'filtroEstado', 'filtroMarca', 'page'])) {
            $this->redirectRoute('dashboard', navigate: true);
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

    // Métodos para actualización de filtros en tiempo real
    public function updatedFiltroProducto()
    {
        $this->resetPage();
    }

    public function updatedFiltroBodega()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedFiltroMarca()
    {
        $this->resetPage();
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
                ->leftJoin('marca as m', 'p.marca_id', '=', 'm.id')
                ->leftJoin('unidad_medida as um', 'rb.unidad_medida_id', '=', 'um.id')
                ->leftJoin('unidad_medida as umv', 'p.unidad_medida_venta_id', '=', 'umv.id')
                ->select(
                    'rb.id',
                    'rb.cantidad_disponible',
                    'rb.fecha_recibido',
                    'rb.fecha_expiracion',
                    'rb.comentario',
                    'p.id as producto_id',
                    'p.nombre as producto_nombre',
                    'p.descripcion as producto_descripcion',
                    'p.codigo_barra',
                    'm.nombre as marca_nombre',
                    'm.id as marca_id',
                    'b.nombre as bodega_nombre',
                    'b.id as bodega_id',
                    't.denominacion_social as tienda_nombre',
                    'seg.descripcion as segmento_descripcion',
                    'sec.descripcion as seccion_descripcion',
                    'um.nombre as unidad_medida',
                    'umv.nombre as unidad_medida_venta'
                )
                ->where('rb.estado_id', 1) // Solo activos
                ->where('b.id', '!=', 2); // Excluir bodega ID 2 (productos sin venta)

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

            if (!empty($this->filtroBodega)) {
                $query->where('b.id', $this->filtroBodega);
            }

            if (!empty($this->filtroMarca)) {
                $query->where('m.id', $this->filtroMarca);
            }

            if (!empty($this->filtroEstado)) {
                switch ($this->filtroEstado) {
                    case 'disponible':
                        $query->where('rb.cantidad_disponible', '>', 10);
                        break;
                    case 'poco_stock':
                        $query->whereBetween('rb.cantidad_disponible', [1, 10]);
                        break;
                    case 'agotado':
                        $query->where('rb.cantidad_disponible', '<=', 0);
                        break;
                }
            }

            // Aplicar ordenamiento
            $campoOrden = $this->ordenarPor;
            if ($this->ordenarPor === 'producto_nombre') {
                $campoOrden = 'p.nombre';
            } elseif ($this->ordenarPor === 'fecha_recibido') {
                $campoOrden = 'rb.fecha_recibido';
            } elseif ($this->ordenarPor === 'cantidad_disponible') {
                $campoOrden = 'rb.cantidad_disponible';
            } elseif ($this->ordenarPor === 'bodega_nombre') {
                $campoOrden = 'b.nombre';
            } elseif ($this->ordenarPor === 'marca_nombre') {
                $campoOrden = 'm.nombre';
            } elseif ($this->ordenarPor === 'codigo_barra') {
                $campoOrden = 'p.codigo_barra';
            }

            $query->orderBy($campoOrden, $this->direccionOrden);

            // Paginar resultados
            return $paginacion ? $query->paginate($this->registrosPorPagina, ['*'], 'page', $this->page) : $query->get();

        } catch (\Exception $e) {
            Log::error('Error al cargar productos recibidos', [
                'mensaje' => $e->getMessage(),
                'usuario' => Auth::id(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);

            // Crear una paginación vacía manualmente
            return new LengthAwarePaginator(
                collect(), // Colección vacía
                0, // Total de elementos
                $this->registrosPorPagina, // Elementos por página
                $this->page, // Página actual
                ['path' => request()->url()]
            );
        }
    }

    public function cargarFiltros()
    {
        try {
            $user = Auth::user();

            // Cargar bodegas
            $queryBodegas = Bodega::with('tienda')->where('estado_id', 1);

            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $queryBodegas->where('tienda_id', $user->tienda_id);
            }

            $this->bodegas = $queryBodegas->orderBy('nombre')->get();

            // Cargar marcas (solo las que tienen productos en bodega)
            $queryMarcas = DB::table('marca as m')
                ->join('producto as p', 'm.id', '=', 'p.marca_id')
                ->join('recibido_bodega as rb', 'p.id', '=', 'rb.producto_id')
                ->join('seccion as sec', 'rb.seccion_id', '=', 'sec.id')
                ->join('segmento as seg', 'sec.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->select('m.id', 'm.nombre')
                ->where('rb.estado_id', 1)
                ->distinct();

            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $queryMarcas->where('b.tienda_id', $user->tienda_id);
            }

            $this->marcas = $queryMarcas->orderBy('m.nombre')->get();

        } catch (\Exception $e) {
            Log::error('Error al cargar filtros', [
                'mensaje' => $e->getMessage(),
                'usuario' => Auth::id()
            ]);

            $this->bodegas = collect();
            $this->marcas = collect();
        }
    }

    public function limpiarFiltros()
    {
        $this->filtroProducto = '';
        $this->filtroBodega = '';
        $this->filtroEstado = '';
        $this->filtroMarca = '';
        $this->resetPage();
    }

    public function editarProducto($productoId)
    {
        // Emitir evento para cambiar al componente de edición
        $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm', parametros: ['id' => $productoId]);
    }

    public function descargarExcel()
    {
        try {
            // Obtener todos los productos recibidos según los filtros actuales (sin paginación)
            $productos = $this->cargarDatos(false); // false para no paginar

            $fechaGeneracion = now()->format('d/m/Y H:i:s');
            $totalProductos = $productos->count();
            $filtrosAplicados = $this->obtenerFiltrosAplicados();

            $usuarioReporte = auth()->user() ? auth()->user()->name : 'Invitado';

            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "lista_productos_{$timestamp}.xlsx";

            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Excel\ListaDeProductosExport($productos, $fechaGeneracion, $totalProductos, $filtrosAplicados, $usuarioReporte),
                $filename,
                'temp'
            );

            return $this->redirectRoute('download.file', ['file' => $filename]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            \Log::error('Error generating Excel', ['error' => $e->getMessage()]);
        }
    }

    private function obtenerFiltrosAplicados()
    {
        $filtros = [];
        if (!empty($this->filtroProducto)) {
            $filtros[] = "Producto: '{$this->filtroProducto}'";
        }
        if (!empty($this->filtroBodega)) {
            $filtros[] = "Bodega: '{$this->filtroBodega}'";
        }
        if (!empty($this->filtroEstado)) {
            $filtros[] = "Estado: '{$this->filtroEstado}'";
        }
        if (!empty($this->filtroMarca)) {
            $filtros[] = "Marca: '{$this->filtroMarca}'";
        }
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }

    public function render()
    {
        $productosRecibidos = $this->cargarDatos();

        return view('livewire.inventario.lista-de-productos', [
            'productosRecibidos' => $productosRecibidos
        ]);
    }
}
