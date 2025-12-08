<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RecibidoBodega;
use App\Models\CambioUnidad;
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
    public $filtroCodigoProducto = '';
    public $filtroCodigoBarra = '';
    public $filtroBodega = '';
    public $filtroEstado = '';
    public $filtroMarca = '';
    public $filtroSegmento = '';
    public $filtroSeccion = '';
    public $filtroFechaRecibido = '';
    public $filtroFechaExpiracion = '';

    // Datos
    public $bodegas = [];
    public $marcas = [];

    // Modal de cambio de unidad
    public $mostrarModalCambiarUnidad = false;
    public $stockSeleccionado = null;
    public $cantidadAConvertir = '';
    public $cantidadVerificacion = '';
    public $nuevaUnidadMedida = '';
    public $unidadesDisponibles = [];
    public $cantidadTotalDisponible = 0;
    public $procesandoConversion = false;

    // Modal de ajuste de cantidades
    public $mostrarModalAjusteCantidades = false;
    public $stockParaAjuste = null;
    public $tipoAjuste = 'aumentar'; // 'aumentar' o 'disminuir'
    public $cantidadAjuste = '';
    public $motivoAjuste = '';
    public $cantidadDisponibleActual = 0;
    public $productoNombreAjuste = '';
    public $bodegaNombreAjuste = '';
    public $seccionNombreAjuste = '';
    public $unidadMedidaAjuste = '';

    // Alerta de validación
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';

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
            $this->filtroCodigoProducto = $filtrosSesion['filtroCodigoProducto'] ?? '';
            $this->filtroCodigoBarra = $filtrosSesion['filtroCodigoBarra'] ?? '';
            $this->filtroBodega = $filtrosSesion['filtroBodega'] ?? '';
            $this->filtroEstado = $filtrosSesion['filtroEstado'] ?? '';
            $this->filtroMarca = $filtrosSesion['filtroMarca'] ?? '';
            $this->filtroSegmento = $filtrosSesion['filtroSegmento'] ?? '';
            $this->filtroSeccion = $filtrosSesion['filtroSeccion'] ?? '';
            $this->filtroFechaRecibido = $filtrosSesion['filtroFechaRecibido'] ?? '';
            $this->filtroFechaExpiracion = $filtrosSesion['filtroFechaExpiracion'] ?? '';
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
            $this->filtroCodigoProducto = '';
            $this->filtroCodigoBarra = '';
            $this->filtroBodega = '';
            $this->filtroEstado = '';
            $this->filtroMarca = '';
            $this->filtroSegmento = '';
            $this->filtroSeccion = '';
            $this->filtroFechaRecibido = '';
            $this->filtroFechaExpiracion = '';
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
            'filtroCodigoProducto' => $this->filtroCodigoProducto,
            'filtroCodigoBarra' => $this->filtroCodigoBarra,
            'filtroBodega' => $this->filtroBodega,
            'filtroEstado' => $this->filtroEstado,
            'filtroMarca' => $this->filtroMarca,
            'filtroSegmento' => $this->filtroSegmento,
            'filtroSeccion' => $this->filtroSeccion,
            'filtroFechaRecibido' => $this->filtroFechaRecibido,
            'filtroFechaExpiracion' => $this->filtroFechaExpiracion,
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

    public function updatedFiltroCodigoProducto()
    {
        $this->resetPage();
    }

    public function updatedFiltroCodigoBarra()
    {
        $this->resetPage();
    }

    public function updatedFiltroSegmento()
    {
        $this->resetPage();
    }

    public function updatedFiltroSeccion()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaRecibido()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaExpiracion()
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
                ->leftJoin('precio_has_venta as phv', 'rb.precio_venta_id', '=', 'phv.id')
                ->select(
                    'rb.id',
                    'rb.cantidad_disponible',
                    'rb.fecha_recibido',
                    'rb.fecha_expiracion',
                    'rb.comentario',
                    'rb.unidad_medida_id',
                    'rb.precio_venta_id',
                    'p.id as producto_id',
                    'p.nombre as producto_nombre',
                    'p.descripcion as producto_descripcion',
                    DB::raw('COALESCE(
                        phv.codigo_barra,
                        (SELECT phv2.codigo_barra 
                         FROM precio_has_venta phv2 
                         WHERE phv2.producto_id = p.id 
                         AND phv2.unidad_medida_id = rb.unidad_medida_id
                         AND phv2.estado_id = 1 
                         LIMIT 1),
                        (SELECT phv3.codigo_barra 
                         FROM precio_has_venta phv3 
                         WHERE phv3.producto_id = p.id 
                         AND phv3.unidad_medida_id = p.unidad_medida_venta_id
                         AND phv3.estado_id = 1 
                         LIMIT 1),
                        (SELECT phv4.codigo_barra 
                         FROM precio_has_venta phv4 
                         WHERE phv4.producto_id = p.id 
                         AND phv4.estado_id = 1 
                         LIMIT 1)
                    ) as codigo_barra'),
                    DB::raw('COALESCE(phv.descripcion, "") as presentacion_descripcion'),
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
                      ->orWhere('p.codigo_estatal', 'like', '%' . $this->filtroProducto . '%')
                      ->orWhereExists(function($subQuery) {
                          $subQuery->select(DB::raw(1))
                              ->from('precio_has_venta as phv_busqueda')
                              ->whereRaw('phv_busqueda.producto_id = p.id')
                              ->where('phv_busqueda.estado_id', 1)
                              ->where('phv_busqueda.codigo_barra', 'like', '%' . $this->filtroProducto . '%');
                      });
                });
            }

            if (!empty($this->filtroCodigoProducto)) {
                $query->where('p.id', 'like', '%' . $this->filtroCodigoProducto . '%');
            }

            if (!empty($this->filtroCodigoBarra)) {
                $query->whereExists(function($q) {
                    $q->select(DB::raw(1))
                      ->from('precio_has_venta as phv_filtro')
                      ->whereRaw('phv_filtro.producto_id = p.id')
                      ->where('phv_filtro.estado_id', 1)
                      ->where('phv_filtro.codigo_barra', 'like', '%' . $this->filtroCodigoBarra . '%');
                });
            }

            if (!empty($this->filtroSegmento)) {
                $query->where('seg.descripcion', 'like', '%' . $this->filtroSegmento . '%');
            }

            if (!empty($this->filtroSeccion)) {
                $query->where('sec.descripcion', 'like', '%' . $this->filtroSeccion . '%');
            }

            if (!empty($this->filtroFechaRecibido)) {
                $query->whereDate('rb.fecha_recibido', $this->filtroFechaRecibido);
            }

            if (!empty($this->filtroFechaExpiracion)) {
                $query->whereDate('rb.fecha_expiracion', $this->filtroFechaExpiracion);
            }

            if (!empty($this->filtroBodega)) {
                $query->where('b.id', $this->filtroBodega);
            }

            if (!empty($this->filtroMarca)) {
                $query->where('m.id', $this->filtroMarca);
            }

            // Filtro de estado stock
            if (!empty($this->filtroEstado)) {
                switch ($this->filtroEstado) {
                    case 'disponible':
                        $query->where('rb.cantidad_disponible', '>', 10);
                        break;
                    case 'poco_stock':
                        $query->whereBetween('rb.cantidad_disponible', [1, 10]);
                        break;
                    case 'agotado':
                        // Mostrar productos con stock 0 o menor SOLO cuando se seleccione explícitamente
                        $query->where('rb.cantidad_disponible', '<=', 0);
                        break;
                }
            } else {
                // Por defecto, NO mostrar productos con stock 0 (solo si usuario selecciona "Agotado")
                $query->where('rb.cantidad_disponible', '>', 0);
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
                $campoOrden = DB::raw('(SELECT phv_orden.codigo_barra FROM precio_has_venta phv_orden WHERE phv_orden.producto_id = p.id AND phv_orden.estado_id = 1 LIMIT 1)');
            } elseif ($this->ordenarPor === 'producto_id') {
                $campoOrden = 'p.id';
            }

            // Aplicar ordenamiento primario
            $query->orderBy($campoOrden, $this->direccionOrden);
            
            // SIEMPRE aplicar ordenamiento secundario por fecha de recepción (más reciente primero)
            // Excepto si ya estamos ordenando por fecha_recibido
            if ($this->ordenarPor !== 'fecha_recibido') {
                $query->orderBy('rb.fecha_recibido', 'desc');
            }

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
        $this->filtroCodigoProducto = '';
        $this->filtroCodigoBarra = '';
        $this->filtroBodega = '';
        $this->filtroEstado = '';
        $this->filtroMarca = '';
        $this->filtroSegmento = '';
        $this->filtroSeccion = '';
        $this->filtroFechaRecibido = '';
        $this->filtroFechaExpiracion = '';
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
        if (!empty($this->filtroCodigoProducto)) {
            $filtros[] = "Código Producto: '{$this->filtroCodigoProducto}'";
        }
        if (!empty($this->filtroProducto)) {
            $filtros[] = "Producto: '{$this->filtroProducto}'";
        }
        if (!empty($this->filtroCodigoBarra)) {
            $filtros[] = "Código de Barras: '{$this->filtroCodigoBarra}'";
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
        if (!empty($this->filtroSegmento)) {
            $filtros[] = "Segmento: '{$this->filtroSegmento}'";
        }
        if (!empty($this->filtroSeccion)) {
            $filtros[] = "Sección: '{$this->filtroSeccion}'";
        }
        if (!empty($this->filtroFechaRecibido)) {
            $filtros[] = "Fecha Recibido: '{$this->filtroFechaRecibido}'";
        }
        if (!empty($this->filtroFechaExpiracion)) {
            $filtros[] = "Fecha Expiración: '{$this->filtroFechaExpiracion}'";
        }
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }

    public function editarStock($recibidoBodegaId)
    {
        // Emitir evento para cambiar al componente de edición de stock
        $this->dispatch('cambiarVista', ruta: 'Inventario.StockForm', parametros: ['recibidoId' => $recibidoBodegaId]);
    }

    // Métodos para el modal de cambio de unidad
    public function abrirModalCambiarUnidad($recibidoBodegaId)
    {
        $this->stockSeleccionado = RecibidoBodega::with([
            'producto.preciosVenta.unidadMedida',
            'seccion.segmento.bodega',
            'seccion.segmento',
            'seccion'
        ])->find($recibidoBodegaId);

        // Obtener la unidad de medida del stock seleccionado
        $unidadMedidaActual = $this->stockSeleccionado->unidad_medida
            ?? ($this->stockSeleccionado->unidadMedida ? $this->stockSeleccionado->unidadMedida->nombre : null);

        // Calcular el total disponible de todos los registros con la misma unidad de medida
        $registrosStock = RecibidoBodega::where('producto_id', $this->stockSeleccionado->producto_id)
            ->where('seccion_id', $this->stockSeleccionado->seccion_id)
            ->where('estado_id', 1)
            ->where('cantidad_disponible', '>', 0)
            ->get();

        // Filtrar por unidad de medida y sumar
        $this->cantidadTotalDisponible = $registrosStock->filter(function($registro) use ($unidadMedidaActual) {
            $unidadRegistro = $registro->unidad_medida
                ?? ($registro->unidadMedida ? $registro->unidadMedida->nombre : null);
            return $unidadRegistro === $unidadMedidaActual;
        })->sum('cantidad_disponible');

        // Obtener las unidades de medida disponibles del producto desde precio_has_venta
        if ($this->stockSeleccionado && $this->stockSeleccionado->producto) {
            $this->unidadesDisponibles = $this->stockSeleccionado->producto->preciosVenta()
                ->with('unidadMedida')
                ->where('estado_id', 1)
                ->get()
                ->map(function($precio) {
                    return [
                        'id' => $precio->unidadMedida->id,
                        'nombre' => $precio->unidadMedida->nombre
                    ];
                })
                ->unique('id')
                ->values()
                ->toArray();
        }

        $this->mostrarModalCambiarUnidad = true;
        $this->cantidadAConvertir = '';
        $this->cantidadVerificacion = '';
        $this->nuevaUnidadMedida = '';
        $this->resetValidation();
    }

    public function cerrarModalCambiarUnidad()
    {
        $this->mostrarModalCambiarUnidad = false;
        $this->stockSeleccionado = null;
        $this->cantidadAConvertir = '';
        $this->cantidadVerificacion = '';
        $this->nuevaUnidadMedida = '';
        $this->unidadesDisponibles = [];
        $this->cantidadTotalDisponible = 0;
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->procesandoConversion = false;
        $this->resetValidation();
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
    }

    public function procesarCambioUnidad()
    {
        // Evitar procesamiento duplicado
        if ($this->procesandoConversion) {
            return;
        }
        
        $this->procesandoConversion = true;

        // Validación previa con alertas
        if (empty($this->cantidadVerificacion) || $this->cantidadVerificacion <= 0) {
            $this->procesandoConversion = false;
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'La cantidad a rebajar es obligatoria y debe ser mayor a 0.';
            return;
        }

        if ($this->cantidadVerificacion > $this->cantidadTotalDisponible) {
            $this->procesandoConversion = false;
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'La cantidad a rebajar no puede exceder el stock total disponible (' . $this->cantidadTotalDisponible . ').';
            return;
        }

        if (empty($this->nuevaUnidadMedida)) {
            $this->procesandoConversion = false;
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Debe seleccionar una unidad de medida a convertir.';
            return;
        }

        if (empty($this->cantidadAConvertir) || $this->cantidadAConvertir <= 0) {
            $this->procesandoConversion = false;
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'La cantidad a convertir es obligatoria y debe ser mayor a 0.';
            return;
        }

        // Validaciones formales
        $this->validate([
            'cantidadVerificacion' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . $this->cantidadTotalDisponible
            ],
            'nuevaUnidadMedida' => 'required|string',
            'cantidadAConvertir' => [
                'required',
                'numeric',
                'min:0.01'
            ]
        ], [
            'cantidadVerificacion.required' => 'La cantidad a rebajar es requerida.',
            'cantidadVerificacion.numeric' => 'La cantidad debe ser un número válido.',
            'cantidadVerificacion.min' => 'La cantidad debe ser mayor a 0.',
            'cantidadVerificacion.max' => 'La cantidad no puede exceder el stock total disponible (' . $this->cantidadTotalDisponible . ').',
            'nuevaUnidadMedida.required' => 'Debe seleccionar una unidad de medida.',
            'cantidadAConvertir.required' => 'La cantidad a convertir es requerida.',
            'cantidadAConvertir.numeric' => 'La cantidad a convertir debe ser un número válido.',
            'cantidadAConvertir.min' => 'La cantidad a convertir debe ser mayor a 0.'
        ]);

        try {
            DB::beginTransaction();

            // Obtener la unidad de medida actual
            $unidadMedidaActual = $this->stockSeleccionado->unidad_medida
                ?? ($this->stockSeleccionado->unidadMedida ? $this->stockSeleccionado->unidadMedida->nombre : null);

            // Verificar que no se esté convirtiendo a la misma unidad
            if ($this->nuevaUnidadMedida === $unidadMedidaActual) {
                $this->procesandoConversion = false;
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'No puede convertir a la misma unidad de medida actual.';
                DB::rollback();
                return;
            }

            // Obtener todos los registros del mismo producto y unidad de medida, ordenados del más antiguo al más nuevo
            $registrosStock = RecibidoBodega::where('producto_id', $this->stockSeleccionado->producto_id)
                ->where('seccion_id', $this->stockSeleccionado->seccion_id)
                ->where('estado_id', 1) // Solo activos
                ->where('cantidad_disponible', '>', 0) // Solo con stock disponible
                ->orderBy('fecha_recibido', 'asc') // Del más antiguo al más nuevo (FIFO)
                ->get();

            // Filtrar por unidad de medida (puede ser string o relación)
            $registrosStock = $registrosStock->filter(function($registro) use ($unidadMedidaActual) {
                $unidadRegistro = $registro->unidad_medida
                    ?? ($registro->unidadMedida ? $registro->unidadMedida->nombre : null);
                return $unidadRegistro === $unidadMedidaActual;
            });

            // Verificar que hay suficiente stock total
            $stockTotalDisponible = $registrosStock->sum('cantidad_disponible');
            if ($this->cantidadVerificacion > $stockTotalDisponible) {
                $this->procesandoConversion = false;
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'La cantidad a rebajar (' . $this->cantidadVerificacion . ') excede el stock total disponible (' . $stockTotalDisponible . ').';
                DB::rollback();
                return;
            }

            // Restar cantidad de los registros (FIFO)
            $cantidadRestante = $this->cantidadVerificacion;
            $registrosAfectados = [];

            foreach ($registrosStock as $registro) {
                if ($cantidadRestante <= 0) break;

                $cantidadARestar = min($cantidadRestante, $registro->cantidad_disponible);
                $nuevoStock = $registro->cantidad_disponible - $cantidadARestar;

                // Actualizar el registro
                $registro->update([
                    'cantidad_disponible' => max(0, $nuevoStock),
                    'estado_id' => $nuevoStock > 0 ? 1 : 2 // Inactivo si llega a 0
                ]);

                // Guardar información del registro afectado
                $registrosAfectados[] = [
                    'id' => $registro->id,
                    'cantidad_rebajada' => $cantidadARestar
                ];

                $cantidadRestante -= $cantidadARestar;
            }

            // Crear nuevo registro con la nueva unidad
            $unidadMedidaOriginal = $unidadMedidaActual;

            $nuevoRecibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->stockSeleccionado->producto_id,
                'seccion_id' => $this->stockSeleccionado->seccion_id,
                'cantidad_compra_lote' => $this->cantidadAConvertir,
                'cantidad_inicial_seccion' => $this->cantidadAConvertir,
                'cantidad_disponible' => $this->cantidadAConvertir,
                'unidad_medida_id' => $this->obtenerUnidadMedidaId($this->nuevaUnidadMedida),
                'fecha_recibido' => now(),
                'comentario' => 'Conversión de unidad de ' . $this->cantidadVerificacion . ' ' . $unidadMedidaOriginal . ' a ' . $this->cantidadAConvertir . ' ' . $this->nuevaUnidadMedida,
                'users_registro_id' => Auth::id(),
                'estado_id' => 1,
                'unidades_compra' => $this->cantidadAConvertir
            ]);

            // Obtener IDs de unidades de medida y bodega
            $unidadMedidaOriginalId = $this->obtenerUnidadMedidaId($unidadMedidaOriginal);
            $unidadMedidaNuevaId = $this->obtenerUnidadMedidaId($this->nuevaUnidadMedida);
            
            // Obtener bodega_id desde la sección
            $seccion = DB::table('seccion')
                ->join('segmento', 'seccion.segmento_id', '=', 'segmento.id')
                ->where('seccion.id', $this->stockSeleccionado->seccion_id)
                ->select('segmento.bodega_id')
                ->first();
            
            $bodegaId = $seccion ? $seccion->bodega_id : null;

            // Calcular factor de conversión
            $factorConversion = $this->cantidadVerificacion > 0 
                ? round($this->cantidadAConvertir / $this->cantidadVerificacion, 4) 
                : null;

            // Registrar cada cambio en la tabla cambio_unidad
            foreach ($registrosAfectados as $registroInfo) {
                DB::table('cambio_unidad')->insert([
                    'recibido_bodega_id_original' => $registroInfo['id'],
                    'recibido_bodega_id_nuevo' => $nuevoRecibidoBodega->id,
                    'producto_id' => $this->stockSeleccionado->producto_id,
                    'bodega_id' => $bodegaId,
                    'seccion_id' => $this->stockSeleccionado->seccion_id,
                    'unidad_medida_id_original' => $unidadMedidaOriginalId,
                    'unidad_medida_id_nueva' => $unidadMedidaNuevaId,
                    'cantidad_rebajada' => $registroInfo['cantidad_rebajada'],
                    'cantidad_convertida' => $this->cantidadAConvertir,
                    'factor_conversion' => $factorConversion,
                    'motivo' => 'Conversión de ' . $this->cantidadVerificacion . ' ' . $unidadMedidaOriginal . ' a ' . $this->cantidadAConvertir . ' ' . $this->nuevaUnidadMedida,
                    'users_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Registrar en bitácora
            $this->registrarEnBitacora([
                'tabla_afectada' => 'recibido_bodega',
                'operacion' => 'conversion_unidad',
                'registro_id' => $nuevoRecibidoBodega->id,
                'datos_anteriores' => [
                    'registros_afectados' => count($registrosAfectados),
                    'cantidad_total_rebajada' => $this->cantidadVerificacion,
                    'unidad_medida' => $unidadMedidaOriginal
                ],
                'datos_nuevos' => [
                    'cantidad_convertida' => $this->cantidadAConvertir,
                    'nueva_unidad' => $this->nuevaUnidadMedida,
                    'nuevo_registro_id' => $nuevoRecibidoBodega->id
                ]
            ]);

            DB::commit();

            $this->cerrarModalCambiarUnidad();
            session()->flash('success', 'Conversión de unidad realizada exitosamente. Se afectaron ' . count($registrosAfectados) . ' registro(s).');

        } catch (\Exception $e) {
            DB::rollback();
            $this->procesandoConversion = false;
            Log::error('Error en conversión de unidad: ' . $e->getMessage());
            session()->flash('error', 'Error al procesar la conversión de unidad: ' . $e->getMessage());
        }
    }

    private function registrarEnBitacora($datos)
    {
        try {
            DB::table('bitacora')->insert([
                'tablaReferencia' => $datos['tabla_afectada'],
                'accion' => $datos['operacion'],
                'idReferencia' => $datos['registro_id'],
                'datosAnteriores' => json_encode($datos['datos_anteriores']),
                'datosNuevos' => json_encode($datos['datos_nuevos']),
                'users_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error registrando en bitácora: ' . $e->getMessage());
        }
    }

    private function obtenerUnidadMedidaId($nombreUnidad)
    {
        // Buscar el ID de la unidad de medida por nombre
        $unidadMedida = DB::table('unidad_medida')
            ->where('nombre', $nombreUnidad)
            ->first();

        return $unidadMedida ? $unidadMedida->id : null;
    }

    // ===== MÉTODOS PARA AJUSTE DE CANTIDADES =====
    
    public function abrirModalAjusteCantidades($recibidoBodegaId)
    {
        try {
            $stock = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as sec', 'rb.seccion_id', '=', 'sec.id')
                ->join('segmento as seg', 'sec.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->join('unidad_medida as um', 'rb.unidad_medida_id', '=', 'um.id')
                ->select(
                    'rb.id',
                    'rb.cantidad_disponible',
                    'rb.producto_id',
                    'rb.seccion_id',
                    'rb.unidad_medida_id',
                    'p.nombre as producto_nombre',
                    'b.nombre as bodega_nombre',
                    'b.id as bodega_id',
                    'sec.descripcion as seccion_nombre',
                    'um.nombre as unidad_medida_nombre'
                )
                ->where('rb.id', $recibidoBodegaId)
                ->first();

            if (!$stock) {
                session()->flash('error', 'No se encontró el registro de stock.');
                return;
            }

            $this->stockParaAjuste = $stock;
            $this->cantidadDisponibleActual = $stock->cantidad_disponible;
            $this->productoNombreAjuste = $stock->producto_nombre;
            $this->bodegaNombreAjuste = $stock->bodega_nombre;
            $this->seccionNombreAjuste = $stock->seccion_nombre;
            $this->unidadMedidaAjuste = $stock->unidad_medida_nombre;
            $this->tipoAjuste = 'aumentar';
            $this->cantidadAjuste = '';
            $this->motivoAjuste = '';
            $this->mostrarModalAjusteCantidades = true;

        } catch (\Exception $e) {
            Log::error('Error al abrir modal de ajuste: ' . $e->getMessage());
            session()->flash('error', 'Error al cargar los datos del producto.');
        }
    }

    public function cerrarModalAjusteCantidades()
    {
        $this->mostrarModalAjusteCantidades = false;
        $this->stockParaAjuste = null;
        $this->tipoAjuste = 'aumentar';
        $this->cantidadAjuste = '';
        $this->motivoAjuste = '';
        $this->cantidadDisponibleActual = 0;
        $this->productoNombreAjuste = '';
        $this->bodegaNombreAjuste = '';
        $this->seccionNombreAjuste = '';
        $this->unidadMedidaAjuste = '';
    }

    public function procesarAjusteCantidades()
    {
        // Validar que el stock esté cargado
        if (!$this->stockParaAjuste) {
            session()->flash('error', 'No se ha seleccionado ningún producto para ajustar.');
            $this->cerrarModalAjusteCantidades();
            return;
        }

        // Validaciones de campos
        $this->validate([
            'cantidadAjuste' => 'required|numeric|min:0.01',
            'motivoAjuste' => 'required|string|min:5|max:500',
        ], [
            'cantidadAjuste.required' => 'Debe ingresar una cantidad.',
            'cantidadAjuste.numeric' => 'La cantidad debe ser un número.',
            'cantidadAjuste.min' => 'La cantidad debe ser mayor a 0.',
            'motivoAjuste.required' => 'Debe ingresar un motivo para el ajuste.',
            'motivoAjuste.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivoAjuste.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        try {
            DB::beginTransaction();

            $cantidadAnterior = floatval($this->stockParaAjuste->cantidad_disponible);
            $cantidadNueva = 0;

            if ($this->tipoAjuste === 'aumentar') {
                $cantidadNueva = $cantidadAnterior + floatval($this->cantidadAjuste);
            } else {
                // Disminuir
                if (floatval($this->cantidadAjuste) > $cantidadAnterior) {
                    $this->cerrarModalAjusteCantidades();
                    session()->flash('error', 'No puede disminuir más de la cantidad disponible (' . number_format($cantidadAnterior, 2) . ').');
                    DB::rollBack();
                    return;
                }
                $cantidadNueva = $cantidadAnterior - floatval($this->cantidadAjuste);
            }

            // Actualizar cantidad en recibido_bodega
            $actualizados = DB::table('recibido_bodega')
                ->where('id', $this->stockParaAjuste->id)
                ->update([
                    'cantidad_disponible' => $cantidadNueva,
                    'updated_at' => now()
                ]);

            if ($actualizados === 0) {
                throw new \Exception('No se pudo actualizar el registro de stock.');
            }

            // Insertar en tabla ajuste_inventario
            DB::table('ajuste_inventario')->insert([
                'recibido_bodega_id' => $this->stockParaAjuste->id,
                'producto_id' => $this->stockParaAjuste->producto_id,
                'bodega_id' => $this->stockParaAjuste->bodega_id,
                'seccion_id' => $this->stockParaAjuste->seccion_id,
                'tipo_ajuste' => $this->tipoAjuste,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_ajustada' => floatval($this->cantidadAjuste),
                'cantidad_nueva' => $cantidadNueva,
                'unidad_medida_id' => $this->stockParaAjuste->unidad_medida_id,
                'motivo' => $this->motivoAjuste,
                'users_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Registrar en bitácora
            $this->registrarEnBitacora([
                'tabla_afectada' => 'recibido_bodega',
                'operacion' => 'AJUSTE_CANTIDAD_' . strtoupper($this->tipoAjuste),
                'registro_id' => $this->stockParaAjuste->id,
                'datos_anteriores' => [
                    'producto_id' => $this->stockParaAjuste->producto_id,
                    'producto' => $this->productoNombreAjuste,
                    'bodega' => $this->bodegaNombreAjuste,
                    'seccion' => $this->seccionNombreAjuste,
                    'unidad_medida' => $this->unidadMedidaAjuste,
                    'cantidad_disponible' => $cantidadAnterior
                ],
                'datos_nuevos' => [
                    'producto_id' => $this->stockParaAjuste->producto_id,
                    'producto' => $this->productoNombreAjuste,
                    'bodega' => $this->bodegaNombreAjuste,
                    'seccion' => $this->seccionNombreAjuste,
                    'unidad_medida' => $this->unidadMedidaAjuste,
                    'cantidad_disponible' => $cantidadNueva,
                    'tipo_ajuste' => $this->tipoAjuste,
                    'cantidad_ajustada' => floatval($this->cantidadAjuste),
                    'motivo' => $this->motivoAjuste
                ]
            ]);

            DB::commit();

            $mensaje = $this->tipoAjuste === 'aumentar' 
                ? 'Se aumentaron ' . number_format(floatval($this->cantidadAjuste), 2) . ' unidades.'
                : 'Se disminuyeron ' . number_format(floatval($this->cantidadAjuste), 2) . ' unidades.';
            
            $this->cerrarModalAjusteCantidades();
            session()->flash('message', $mensaje . ' Nueva cantidad: ' . number_format($cantidadNueva, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en ajuste de cantidades: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->cerrarModalAjusteCantidades();
            session()->flash('error', 'Error al procesar el ajuste: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $productosRecibidos = $this->cargarDatos();

        return view('livewire.inventario.lista-de-productos', [
            'productosRecibidos' => $productosRecibidos
        ]);
    }
}
