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

class ListaDeProductos extends Component
{
    use WithPagination;

    // Propiedades de paginación y ordenamiento
    public $registrosPorPagina = 10;
    public $ordenarPor = 'fecha_recibido';
    public $direccionOrden = 'desc';
    
    // Filtros
    public $filtroProducto = '';
    public $filtroBodega = '';
    public $filtroEstado = '';
    public $filtroMarca = '';

    // Datos
    public $bodegas = [];
    public $marcas = [];

    protected $queryString = [
        'filtroProducto' => ['except' => ''],
        'filtroBodega' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroMarca' => ['except' => ''],
        'ordenarPor' => ['except' => 'fecha_recibido'],
        'direccionOrden' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->cargarFiltros();
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

    public function cargarDatos()
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
                    'um.nombre as unidad_medida'
                )
                ->where('rb.estado_id', 1); // Solo activos

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
            return $query->paginate($this->registrosPorPagina);

        } catch (\Exception $e) {
            Log::error('Error al cargar productos recibidos', [
                'mensaje' => $e->getMessage(),
                'usuario' => Auth::id(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
            
            return collect()->paginate($this->registrosPorPagina);
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

    public function render()
    {
        $productosRecibidos = $this->cargarDatos();
        
        return view('livewire.inventario.lista-de-productos', [
            'productosRecibidos' => $productosRecibidos
        ]);
    }
}
