<?php

namespace App\Livewire\Inventario;

use App\Models\Bodega;
use App\Models\Traslado;
use App\Services\TrasladoInventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Traslados extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $filtroEstado = '';
    public string $busquedaProducto = '';
    public bool $mostrarBusquedaProducto = false;
    public string $marcaBusqueda = '';
    public string $categoriaBusqueda = '';
    public string $subcategoriaBusqueda = '';
    public array $resultadosBusqueda = [];
    public array $marcasBusqueda = [];
    public array $categoriasBusqueda = [];
    public array $subcategoriasBusqueda = [];
    public int $paginaBusqueda = 1;
    public int $totalBusqueda = 0;
    public int $resultadosPorPagina = 12;
    public ?array $productoBusquedaSeleccionado = null;
    public bool $mostrarCrear = false;
    public bool $mostrarDetalle = false;
    public bool $mostrarRecepcion = false;
    public ?int $trasladoSeleccionadoId = null;
    public $trasladoSeleccionado = null;

    public string $bodegaOrigenId = '';
    public string $comentario = '';
    public string $productoSeleccionadoId = '';
    public string $presentacionSeleccionadaId = '';
    public string $cantidadSeleccionada = '';
    public array $productosSalida = [];
    public array $recepciones = [];

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $usuario = Auth::user();
        $bodegaPrincipal = Bodega::where('tienda_id', $usuario->tienda_id)
            ->where('estado_id', 1)
            ->where('cuenta_como_disponible', 1)
            ->where('principal', 1)
            ->first();

        $this->bodegaOrigenId = $bodegaPrincipal ? (string) $bodegaPrincipal->id : '';
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedBodegaOrigenId(): void
    {
        $this->productoSeleccionadoId = '';
        $this->presentacionSeleccionadaId = '';
    }

    public function updatedProductoSeleccionadoId(): void
    {
        $this->presentacionSeleccionadaId = '';
    }

    public function abrirBusquedaProductos(): void
    {
        if (!$this->bodegaOrigenId) {
            $this->addError('bodegaOrigenId', 'Seleccione primero la bodega de origen.');
            return;
        }

        $this->reset([
            'busquedaProducto',
            'marcaBusqueda',
            'categoriaBusqueda',
            'subcategoriaBusqueda',
            'resultadosBusqueda',
        ]);
        $this->paginaBusqueda = 1;
        $this->cargarFiltrosBusqueda();
        $this->buscarProductosDisponibles();
        $this->mostrarBusquedaProducto = true;
    }

    public function cerrarBusquedaProductos(): void
    {
        $this->mostrarBusquedaProducto = false;
    }

    public function updatedBusquedaProducto(): void
    {
        $this->paginaBusqueda = 1;
        $this->buscarProductosDisponibles();
    }

    public function updatedMarcaBusqueda(): void
    {
        $this->paginaBusqueda = 1;
        $this->buscarProductosDisponibles();
    }

    public function updatedCategoriaBusqueda($categoriaId): void
    {
        $this->subcategoriaBusqueda = '';
        $this->subcategoriasBusqueda = $categoriaId
            ? DB::table('subcategoria')->where('categoria_id', $categoriaId)->orderBy('nombre')->get()->map(fn ($item) => (array) $item)->all()
            : DB::table('subcategoria')->orderBy('nombre')->get()->map(fn ($item) => (array) $item)->all();
        $this->paginaBusqueda = 1;
        $this->buscarProductosDisponibles();
    }

    public function updatedSubcategoriaBusqueda(): void
    {
        $this->paginaBusqueda = 1;
        $this->buscarProductosDisponibles();
    }

    public function paginaAnteriorBusqueda(): void
    {
        if ($this->paginaBusqueda > 1) {
            $this->paginaBusqueda--;
            $this->buscarProductosDisponibles();
        }
    }

    public function paginaSiguienteBusqueda(): void
    {
        $ultimaPagina = max(1, (int) ceil($this->totalBusqueda / $this->resultadosPorPagina));
        if ($this->paginaBusqueda < $ultimaPagina) {
            $this->paginaBusqueda++;
            $this->buscarProductosDisponibles();
        }
    }

    public function seleccionarProductoBusqueda(int $productoId, int $precioVentaId): void
    {
        $item = collect($this->resultadosBusqueda)->first(
            fn (array $resultado): bool => (int) $resultado['producto_id'] === $productoId
                && (int) $resultado['precio_venta_id'] === $precioVentaId
        );

        if (!$item) {
            $this->buscarProductosDisponibles();
            $item = collect($this->resultadosBusqueda)->first(
                fn (array $resultado): bool => (int) $resultado['producto_id'] === $productoId
                    && (int) $resultado['precio_venta_id'] === $precioVentaId
            );
        }

        if (!$item || (int) $item['stock_disponible'] <= 0) {
            session()->flash('error', 'El producto seleccionado ya no tiene existencia disponible.');
            return;
        }

        $this->productoSeleccionadoId = (string) $productoId;
        $this->presentacionSeleccionadaId = (string) $precioVentaId;
        $this->cantidadSeleccionada = '1';
        $this->productoBusquedaSeleccionado = $item;
        $this->mostrarBusquedaProducto = false;
        $this->resetValidation([
            'productoSeleccionadoId',
            'presentacionSeleccionadaId',
            'cantidadSeleccionada',
        ]);
    }

    public function abrirCrear(): void
    {
        $this->resetFormulario();
        $usuario = Auth::user();
        $bodegaPrincipal = Bodega::where('tienda_id', $usuario->tienda_id)
            ->where('estado_id', 1)
            ->where('cuenta_como_disponible', 1)
            ->where('principal', 1)
            ->first();
        $this->bodegaOrigenId = $bodegaPrincipal ? (string) $bodegaPrincipal->id : '';
        $this->mostrarCrear = true;
    }

    public function cerrarCrear(): void
    {
        $this->mostrarCrear = false;
        $this->resetFormulario();
    }

    public function agregarProducto(): void
    {
        $this->validate([
            'bodegaOrigenId' => ['required', 'integer'],
            'productoSeleccionadoId' => ['required', 'integer'],
            'presentacionSeleccionadaId' => ['required', 'integer'],
            'cantidadSeleccionada' => ['required', 'integer', 'min:1'],
        ]);

        $presentacion = DB::table('precio_has_venta as phv')
            ->join('producto as p', 'p.id', '=', 'phv.producto_id')
            ->join('unidad_medida as um', 'um.id', '=', 'phv.unidad_medida_id')
            ->where('phv.id', $this->presentacionSeleccionadaId)
            ->where('phv.producto_id', $this->productoSeleccionadoId)
            ->where('phv.estado_id', 1)
            ->select('phv.id', 'phv.producto_id', 'phv.codigo_barra', 'phv.descripcion', 'um.nombre as unidad', 'p.nombre as producto')
            ->first();

        if (!$presentacion) {
            $this->addError('presentacionSeleccionadaId', 'La presentación seleccionada no es válida.');
            return;
        }

        $coincidenciaValencia = $this->obtenerCoincidenciaValencia(
            (int) $presentacion->producto_id,
            (int) $presentacion->id
        );
        if (!$coincidenciaValencia) {
            $this->addError(
                'presentacionSeleccionadaId',
                'Esta unidad no coincide con una unidad recibida previamente desde Valencia.'
            );
            return;
        }

        $disponible = $this->stockDisponible(
            (int) $this->bodegaOrigenId,
            (int) $presentacion->producto_id,
            (int) $presentacion->id
        );
        if ((int) $this->cantidadSeleccionada > $disponible) {
            $this->addError('cantidadSeleccionada', "Solo hay {$disponible} unidades disponibles.");
            return;
        }

        $indice = collect($this->productosSalida)->search(
            fn (array $item): bool => (int) $item['precio_venta_id'] === (int) $presentacion->id
        );
        if ($indice !== false) {
            $nuevaCantidad = $this->productosSalida[$indice]['cantidad'] + (int) $this->cantidadSeleccionada;
            if ($nuevaCantidad > $disponible) {
                $this->addError('cantidadSeleccionada', "La cantidad acumulada excede las {$disponible} unidades disponibles.");
                return;
            }
            $this->productosSalida[$indice]['cantidad'] = $nuevaCantidad;
        } else {
            $this->productosSalida[] = [
                'producto_id' => (int) $presentacion->producto_id,
                'precio_venta_id' => (int) $presentacion->id,
                'producto' => $presentacion->producto,
                'presentacion' => trim(($presentacion->unidad ?? '') . ' ' . ($presentacion->descripcion ?? '')),
                'codigo_barra' => $presentacion->codigo_barra,
                'producto_id_valencia' => (int) $coincidenciaValencia->producto_id_valencia,
                'unidad_medida_id_valencia' => (int) $coincidenciaValencia->unidad_medida_id_valencia,
                'unidad_nombre' => $coincidenciaValencia->unidad_nombre,
                'traslado_valencia_id' => (int) $coincidenciaValencia->ultimo_traslado_valencia,
                'cantidad' => (int) $this->cantidadSeleccionada,
                'disponible' => $disponible,
            ];
        }

        $this->productoSeleccionadoId = '';
        $this->presentacionSeleccionadaId = '';
        $this->cantidadSeleccionada = '';
        $this->productoBusquedaSeleccionado = null;
        $this->resetValidation();
    }

    public function quitarProducto(int $indice): void
    {
        if (isset($this->productosSalida[$indice])) {
            unset($this->productosSalida[$indice]);
            $this->productosSalida = array_values($this->productosSalida);
        }
    }

    public function guardarBorrador(TrasladoInventarioService $servicio): void
    {
        $this->validate([
            'bodegaOrigenId' => ['required', 'integer'],
            'comentario' => ['nullable', 'string', 'max:500'],
            'productosSalida' => ['required', 'array', 'min:1'],
        ]);

        try {
            $traslado = $servicio->crearSalida([
                'bodega_origen_id' => (int) $this->bodegaOrigenId,
                'comentario' => $this->comentario,
                'productos' => $this->productosSalida,
            ], Auth::id());

            $this->mostrarCrear = false;
            $this->resetFormulario();
            $this->verDetalle($traslado->id);
            session()->flash('success', "Envío {$traslado->codigo} preparado. Revise el resumen y confirme el despacho.");
        } catch (\Throwable $e) {
            Log::error('Error al crear traslado', ['error' => $e->getMessage(), 'usuario_id' => Auth::id()]);
            session()->flash('error', $e->getMessage());
        }
    }

    public function verDetalle(int $trasladoId): void
    {
        $this->trasladoSeleccionado = Traslado::with([
            'detalles.producto',
            'detalles.precioVenta.unidadMedida',
            'bodegaOrigen',
            'bodegaDestino',
        ])->findOrFail($trasladoId);
        $this->trasladoSeleccionadoId = $trasladoId;
        $this->mostrarDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->mostrarDetalle = false;
        $this->trasladoSeleccionado = null;
        $this->trasladoSeleccionadoId = null;
    }

    public function despachar(int $trasladoId, TrasladoInventarioService $servicio): void
    {
        try {
            $traslado = $servicio->despachar($trasladoId, Auth::id());
            $this->cerrarDetalle();
            session()->flash('success', "Traslado {$traslado->codigo} despachado. La existencia ya no está disponible para venta.");
        } catch (\Throwable $e) {
            Log::error('Error al despachar traslado', ['traslado_id' => $trasladoId, 'error' => $e->getMessage()]);
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelar(int $trasladoId, TrasladoInventarioService $servicio): void
    {
        try {
            $servicio->cancelarBorrador($trasladoId, Auth::id());
            $this->cerrarDetalle();
            session()->flash('success', 'Borrador de traslado cancelado.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function abrirRecepcion(int $trasladoId): void
    {
        $traslado = Traslado::with('detalles.producto')->findOrFail($trasladoId);
        if ($traslado->direccion !== 'valencia_zenvy') {
            session()->flash('error', 'Solo los traslados enviados por Valencia se reciben en esta pantalla.');
            return;
        }

        $seccionDefault = DB::table('seccion as s')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->where('sg.bodega_id', $traslado->bodega_destino_id)
            ->where('s.estado_id', 1)
            ->value('s.id');

        $this->trasladoSeleccionadoId = $trasladoId;
        $this->trasladoSeleccionado = $traslado;
        $this->recepciones = $traslado->detalles
            ->map(function ($detalle) use ($seccionDefault): array {
                $pendiente = $detalle->cantidad_enviada - $detalle->cantidad_recibida - $detalle->cantidad_rechazada;
                return [
                    'detalle_id' => $detalle->id,
                    'producto' => $detalle->producto->nombre,
                    'pendiente' => $pendiente,
                    'cantidad_recibida' => $pendiente,
                    'cantidad_rechazada' => 0,
                    'seccion_id' => $seccionDefault,
                ];
            })
            ->filter(fn (array $item): bool => $item['pendiente'] > 0)
            ->values()
            ->all();
        $this->mostrarRecepcion = true;
    }

    public function confirmarRecepcion(TrasladoInventarioService $servicio): void
    {
        $this->validate([
            'recepciones' => ['required', 'array', 'min:1'],
            'recepciones.*.cantidad_recibida' => ['required', 'integer', 'min:0'],
            'recepciones.*.cantidad_rechazada' => ['required', 'integer', 'min:0'],
            'recepciones.*.seccion_id' => ['required', 'integer'],
        ]);

        try {
            $traslado = $servicio->recibirEntrada($this->trasladoSeleccionadoId, $this->recepciones, Auth::id());
            $this->cerrarRecepcion();
            session()->flash('success', "Recepción de {$traslado->codigo} completada con estado {$traslado->estado}.");
        } catch (\Throwable $e) {
            Log::error('Error al recibir traslado', ['traslado_id' => $this->trasladoSeleccionadoId, 'error' => $e->getMessage()]);
            session()->flash('error', $e->getMessage());
        }
    }

    public function cerrarRecepcion(): void
    {
        $this->mostrarRecepcion = false;
        $this->trasladoSeleccionado = null;
        $this->trasladoSeleccionadoId = null;
        $this->recepciones = [];
    }

    private function resetFormulario(): void
    {
        $this->bodegaOrigenId = '';
        $this->comentario = '';
        $this->busquedaProducto = '';
        $this->productoSeleccionadoId = '';
        $this->presentacionSeleccionadaId = '';
        $this->cantidadSeleccionada = '';
        $this->productoBusquedaSeleccionado = null;
        $this->productosSalida = [];
        $this->resetValidation();
    }

    private function cargarFiltrosBusqueda(): void
    {
        $this->marcasBusqueda = DB::table('marca')->orderBy('nombre')->get()->map(fn ($item) => (array) $item)->all();
        $this->categoriasBusqueda = DB::table('categoria')->orderBy('nombre')->get()->map(fn ($item) => (array) $item)->all();
        $this->subcategoriasBusqueda = DB::table('subcategoria')->orderBy('nombre')->get()->map(fn ($item) => (array) $item)->all();
    }

    private function buscarProductosDisponibles(): void
    {
        if (!$this->bodegaOrigenId) {
            $this->resultadosBusqueda = [];
            $this->totalBusqueda = 0;
            return;
        }

        $stock = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->where('sg.bodega_id', $this->bodegaOrigenId)
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->groupBy('rb.producto_id', 'rb.precio_venta_id')
            ->select(
                'rb.producto_id',
                'rb.precio_venta_id',
                DB::raw('SUM(rb.cantidad_disponible) as stock_disponible')
            );

        $unidadesRecibidasValencia = DB::table('id_zenvy_valencia as iz')
            ->join('compra_has_producto as chp', 'chp.compra_id', '=', 'iz.id_zenvy')
            ->where('iz.tipo_dato_migrado_id', 9)
            ->groupBy('chp.producto_id', 'chp.unidad_medida_id')
            ->select(
                'chp.producto_id',
                'chp.unidad_medida_id',
                DB::raw('MAX(iz.id_valencia) as ultimo_traslado_valencia')
            );

        $query = DB::table('producto as p')
            ->join('producto_valencia_zenvy as pvz', function ($join): void {
                $join->on('pvz.producto_id_zenvy', '=', 'p.id')
                    ->where('pvz.sincronizado', '=', 1);
            })
            ->join('precio_has_venta as phv', function ($join): void {
                $join->on('phv.producto_id', '=', 'p.id')
                    ->where('phv.estado_id', '=', 1);
            })
            ->joinSub($stock, 'stock', function ($join): void {
                $join->on('stock.producto_id', '=', 'p.id')
                    ->on('stock.precio_venta_id', '=', 'phv.id');
            })
            ->joinSub($unidadesRecibidasValencia, 'uv', function ($join): void {
                $join->on('uv.producto_id', '=', 'p.id')
                    ->on('uv.unidad_medida_id', '=', 'phv.unidad_medida_id');
            })
            ->join('id_zenvy_valencia as umv', function ($join): void {
                $join->on('umv.id_zenvy', '=', 'phv.unidad_medida_id')
                    ->where('umv.tipo_dato_migrado_id', '=', 5);
            })
            ->join('unidad_medida as um', 'um.id', '=', 'phv.unidad_medida_id')
            ->leftJoin('marca as m', 'm.id', '=', 'p.marca_id')
            ->leftJoin('subcategoria as sc', 'sc.id', '=', 'p.subcategoria_id')
            ->leftJoin('categoria as c', 'c.id', '=', 'sc.categoria_id')
            ->where('p.estado_id', 1);

        if ($this->busquedaProducto !== '') {
            $busqueda = trim($this->busquedaProducto);
            $palabras = array_values(array_filter(preg_split('/\s+/', $busqueda)));
            $query->where(function ($consulta) use ($palabras, $busqueda): void {
                foreach ($palabras as $palabra) {
                    $consulta->where(function ($porPalabra) use ($palabra): void {
                        $porPalabra->where('p.nombre', 'like', "%{$palabra}%")
                            ->orWhere('p.descripcion', 'like', "%{$palabra}%");
                    });
                }
                $consulta->orWhere('phv.codigo_barra', 'like', "%{$busqueda}%")
                    ->orWhere('p.codigo_estatal', 'like', "%{$busqueda}%");
            });
        }
        if ($this->marcaBusqueda) {
            $query->where('p.marca_id', $this->marcaBusqueda);
        }
        if ($this->subcategoriaBusqueda) {
            $query->where('p.subcategoria_id', $this->subcategoriaBusqueda);
        } elseif ($this->categoriaBusqueda) {
            $query->where('sc.categoria_id', $this->categoriaBusqueda);
        }

        $resultados = $query
            ->orderBy('p.nombre')
            ->orderBy('phv.cantidad')
            ->limit(100)
            ->select(
                'p.id as producto_id',
                'p.nombre',
                'p.descripcion',
                'p.updated_at',
                DB::raw('p.imagen IS NOT NULL as tiene_imagen'),
                'pvz.producto_id_valencia',
                'phv.id as precio_venta_id',
                'phv.codigo_barra',
                'phv.descripcion as presentacion_descripcion',
                'phv.cantidad as cantidad_presentacion',
                'phv.precio',
                'um.nombre as unidad_nombre',
                'umv.id_valencia as unidad_medida_id_valencia',
                'uv.ultimo_traslado_valencia',
                'm.nombre as marca_nombre',
                'sc.nombre as subcategoria_nombre',
                'c.nombre as categoria_nombre',
                'stock.stock_disponible'
            )
            ->get()
            ->map(fn ($item): array => (array) $item)
            ->values();

        $this->totalBusqueda = $resultados->count();
        $ultimaPagina = max(1, (int) ceil($this->totalBusqueda / $this->resultadosPorPagina));
        $this->paginaBusqueda = min($this->paginaBusqueda, $ultimaPagina);
        $this->resultadosBusqueda = $resultados
            ->forPage($this->paginaBusqueda, $this->resultadosPorPagina)
            ->values()
            ->all();
    }

    private function stockDisponible(int $bodegaId, int $productoId, int $precioVentaId): int
    {
        return (int) DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->where('sg.bodega_id', $bodegaId)
            ->where('rb.producto_id', $productoId)
            ->where('rb.precio_venta_id', $precioVentaId)
            ->where('rb.estado_id', 1)
            ->sum('rb.cantidad_disponible');
    }

    private function obtenerCoincidenciaValencia(int $productoId, int $precioVentaId): ?object
    {
        return DB::table('precio_has_venta as phv')
            ->join('producto_valencia_zenvy as pvz', function ($join): void {
                $join->on('pvz.producto_id_zenvy', '=', 'phv.producto_id')
                    ->where('pvz.sincronizado', '=', 1);
            })
            ->join('id_zenvy_valencia as umv', function ($join): void {
                $join->on('umv.id_zenvy', '=', 'phv.unidad_medida_id')
                    ->where('umv.tipo_dato_migrado_id', '=', 5);
            })
            ->join('unidad_medida as um', 'um.id', '=', 'phv.unidad_medida_id')
            ->join('compra_has_producto as chp', function ($join): void {
                $join->on('chp.producto_id', '=', 'phv.producto_id')
                    ->on('chp.unidad_medida_id', '=', 'phv.unidad_medida_id');
            })
            ->join('id_zenvy_valencia as iz', function ($join): void {
                $join->on('iz.id_zenvy', '=', 'chp.compra_id')
                    ->where('iz.tipo_dato_migrado_id', '=', 9);
            })
            ->where('phv.id', $precioVentaId)
            ->where('phv.producto_id', $productoId)
            ->where('phv.estado_id', 1)
            ->groupBy(
                'pvz.producto_id_valencia',
                'umv.id_valencia',
                'um.nombre'
            )
            ->select(
                'pvz.producto_id_valencia',
                'umv.id_valencia as unidad_medida_id_valencia',
                'um.nombre as unidad_nombre',
                DB::raw('MAX(iz.id_valencia) as ultimo_traslado_valencia')
            )
            ->first();
    }

    public function render()
    {
        $usuario = Auth::user();
        $bodegas = Bodega::where('tienda_id', $usuario->tienda_id)
            ->where('estado_id', 1)
            ->where('cuenta_como_disponible', 1)
            ->orderBy('nombre')
            ->get();

        $secciones = collect();
        if ($this->trasladoSeleccionado?->bodega_destino_id) {
            $secciones = DB::table('seccion as s')
                ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
                ->where('sg.bodega_id', $this->trasladoSeleccionado->bodega_destino_id)
                ->where('s.estado_id', 1)
                ->select('s.id', 's.descripcion', 's.numeracion', 'sg.descripcion as segmento')
                ->orderBy('sg.descripcion')
                ->orderBy('s.descripcion')
                ->get();
        }

        $consultaTienda = function ($consulta) use ($usuario): void {
            $consulta->whereHas('bodegaOrigen', fn ($q) => $q->where('tienda_id', $usuario->tienda_id))
                ->orWhereHas('bodegaDestino', fn ($q) => $q->where('tienda_id', $usuario->tienda_id));
        };

        $estadisticasQuery = Traslado::where('direccion', 'zenvy_valencia')
            ->where($consultaTienda);
        $estadisticas = [
            'borradores' => (clone $estadisticasQuery)->where('estado', 'borrador')->count(),
            'en_transito' => (clone $estadisticasQuery)->whereIn('estado', ['en_transito', 'recibido_parcial'])->count(),
            'completados' => (clone $estadisticasQuery)->where('estado', 'recibido')->count(),
        ];

        $query = Traslado::with(['bodegaOrigen', 'bodegaDestino', 'detalles'])
            ->where('direccion', 'zenvy_valencia')
            ->where($consultaTienda);

        if ($this->busqueda) {
            $query->where(function ($consulta): void {
                $consulta->where('codigo', 'like', "%{$this->busqueda}%")
                    ->orWhere('referencia_externa', 'like', "%{$this->busqueda}%");
            });
        }
        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        $traslados = $query->latest('id')->paginate(15);

        return view('livewire.inventario.traslados', compact(
            'bodegas',
            'secciones',
            'traslados',
            'estadisticas'
        ));
    }
}
