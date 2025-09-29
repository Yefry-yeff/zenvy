<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Producto as ProductoModel;
use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// Las librerías se cargarán dinámicamente si están disponibles

class Producto extends Component
{
    use WithPagination;

    // Propiedades de paginación y búsqueda
    public $buscar = '';
    public $filtroOrigen = 'todos'; // todos, valencia, paperland (compatible con zenvy)
    public $registrosPorPagina = 10; // Cambiado a 10
    public $ordenarPor = 'nombre';
    public $direccionOrden = 'asc';
    public $page = 1; // Agregar propiedad page

    // Filtros por columna
    public $filtroNombre = '';
    public $filtroCodigo = '';
    public $filtroCategoria = '';
    public $filtroMarca = '';
    public $filtroPrecio = '';

    // Modales y estados
    public $modalEliminarAbierto = false;
    public $productoAEliminar = null;
    public $productoSeleccionado = null;
    public $stockDisponible = 0;
    public $tieneCodigoBarras = false;
    public $puedeEliminar = false;
    public $tieneComprasActivas = false;

    // Propiedades para la barra de carga de sincronización
    public $sincronizandoValencia = false;
    public $progreso = 0;
    public $detallesSincronizacion = null;

    private $sincronizacionService;

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

    // Métodos de filtrado y búsqueda
    public function updatingBuscar()
    {
        $this->resetPage();
    }

    public function updatingFiltroOrigen()
    {
        $this->resetPage();
    }

    public function updatingFiltroNombre()
    {
        $this->resetPage();
    }

    public function updatingFiltroCodigo()
    {
        $this->resetPage();
    }

    public function updatingFiltroCategoria()
    {
        $this->resetPage();
    }

    public function updatingFiltroMarca()
    {
        $this->resetPage();
    }

    public function updatingFiltroPrecio()
    {
        $this->resetPage();
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
        $this->buscar = '';
        $this->filtroOrigen = 'todos';
        $this->filtroNombre = '';
        $this->filtroCodigo = '';
        $this->filtroCategoria = '';
        $this->filtroMarca = '';
        $this->filtroPrecio = '';
        $this->resetPage();
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = SincronizacionProductosService::obtenerInstancia();
        }
        return $this->sincronizacionService;
    }

    public function boot()
    {
        // Simplemente marcar el componente activo - DynamicContent maneja la limpieza URL
        session(['current_component' => 'producto']);
    }

    public function hydrate()
    {
        // Solo verificar compatibilidad básica - DynamicContent maneja los redirects
        $parametrosURL = request()->query();

        if (!empty($parametrosURL)) {
            // Solo verificar parámetros críticos que definitivamente no pertenecen aquí
            $parametrosProhibidos = ['busqueda', 'filtroEstado', 'filtroFecha', 'filtroProducto', 'filtroBodega'];

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
        session(['producto_filtros' => [
            'buscar' => $this->buscar,
            'filtroOrigen' => $this->filtroOrigen,
            'ordenarPor' => $this->ordenarPor,
            'direccionOrden' => $this->direccionOrden,
            'filtroNombre' => $this->filtroNombre,
            'filtroCodigo' => $this->filtroCodigo,
            'filtroCategoria' => $this->filtroCategoria,
            'filtroMarca' => $this->filtroMarca,
            'filtroPrecio' => $this->filtroPrecio,
            'page' => $this->page
        ]]);
    }

    public function mount()
    {
        // Restaurar filtros desde sesión si existen
        $filtrosSesion = session('producto_filtros');
        if ($filtrosSesion) {
            $this->buscar = $filtrosSesion['buscar'] ?? '';
            $this->filtroOrigen = $filtrosSesion['filtroOrigen'] ?? 'todos';
            $this->ordenarPor = $filtrosSesion['ordenarPor'] ?? 'nombre';
            $this->direccionOrden = $filtrosSesion['direccionOrden'] ?? 'asc';
            $this->filtroNombre = $filtrosSesion['filtroNombre'] ?? '';
            $this->filtroCodigo = $filtrosSesion['filtroCodigo'] ?? '';
            $this->filtroCategoria = $filtrosSesion['filtroCategoria'] ?? '';
            $this->filtroMarca = $filtrosSesion['filtroMarca'] ?? '';
            $this->filtroPrecio = $filtrosSesion['filtroPrecio'] ?? '';
            $this->page = $filtrosSesion['page'] ?? 1;
        }

        // Inicialización básica sin cargar datos
        $this->registrosPorPagina = 10; // Paginación por defecto en 10

        // Marcar componente activo
        session(['current_component' => 'producto']);
    }

    public function render()
    {
        // Query optimizada con paginación
        $query = ProductoModel::select([
                'id', 'nombre', 'descripcion', 'codigo_barra',
                'precio_base', 'producto_valencia', 'estado_id',
                'subcategoria_id', 'marca_id', 'created_at'
            ])
            ->with([
                'subcategoria:id,nombre,categoria_id',
                'subcategoria.categoria:id,nombre',
                'marca:id,nombre'
            ])
            ->where('estado_id', 1);

        // Aplicar filtro de búsqueda
        if (!empty($this->buscar)) {
            $query->where(function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->buscar . '%')
                  ->orWhere('codigo_barra', 'LIKE', '%' . $this->buscar . '%')
                  ->orWhere('descripcion', 'LIKE', '%' . $this->buscar . '%');
            });
        }

        // Aplicar filtros individuales
        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'LIKE', '%' . $this->filtroNombre . '%');
        }

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo_barra', 'LIKE', '%' . $this->filtroCodigo . '%');
        }

        if (!empty($this->filtroCategoria)) {
            $query->whereHas('subcategoria.categoria', function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->filtroCategoria . '%');
            });
        }

        if (!empty($this->filtroMarca)) {
            $query->whereHas('marca', function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->filtroMarca . '%');
            });
        }

        if (!empty($this->filtroPrecio)) {
            $query->where('precio_base', 'LIKE', '%' . $this->filtroPrecio . '%');
        }

        // Aplicar filtro de origen basado en los datos reales
        if ($this->filtroOrigen !== 'todos') {
            $filtroOrigenLower = strtolower(trim($this->filtroOrigen));

            if ($filtroOrigenLower === 'valencia') {
                // Productos de Valencia: producto_valencia = 1
                $query->where('producto_valencia', 1);
            } elseif ($filtroOrigenLower === 'paperland' || $filtroOrigenLower === 'zenvy') {
                // Productos locales (Paperland/Zenvy): producto_valencia es NULL
                // Según los datos, no hay productos con valor 0, solo NULL
                $query->whereNull('producto_valencia');
            }
        }

        // Aplicar ordenamiento
        $query->orderBy($this->ordenarPor, $this->direccionOrden);

        // Paginar resultados
        $productos = $query->paginate($this->registrosPorPagina, ['*'], 'page', $this->page);

        return view('livewire.inventario.producto', [
            'productos' => $productos
        ]);
    }
 protected function aplicarFiltros($query)
    {
        // Solo aplicar filtro de estado activo
        $query->where('estado_id', 1);

        // Aplicar filtro de búsqueda general
        if (!empty($this->buscar)) {
            $query->where(function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->buscar . '%')
                  ->orWhere('codigo_barra', 'LIKE', '%' . $this->buscar . '%')
                  ->orWhere('descripcion', 'LIKE', '%' . $this->buscar . '%');
            });
        }

        // Aplicar filtros individuales
        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'LIKE', '%' . $this->filtroNombre . '%');
        }

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo_barra', 'LIKE', '%' . $this->filtroCodigo . '%');
        }

        if (!empty($this->filtroCategoria)) {
            $query->whereHas('subcategoria.categoria', function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->filtroCategoria . '%');
            });
        }

        if (!empty($this->filtroMarca)) {
            $query->whereHas('marca', function($q) {
                $q->where('nombre', 'LIKE', '%' . $this->filtroMarca . '%');
            });
        }

        if (!empty($this->filtroPrecio)) {
            $query->where('precio_base', 'LIKE', '%' . $this->filtroPrecio . '%');
        }

        // Aplicar filtro de origen
        if ($this->filtroOrigen !== 'todos') {
            $filtroOrigenLower = strtolower(trim($this->filtroOrigen));

            if ($filtroOrigenLower === 'valencia') {
                $query->where('producto_valencia', 1);
            } elseif ($filtroOrigenLower === 'paperland' || $filtroOrigenLower === 'zenvy') {
                $query->whereNull('producto_valencia');
            }
        }

        return $query;
    }
    public function sincronizarProductosValencia()
    {
        try {
            // Iniciar el proceso de sincronización
            $this->sincronizandoValencia = true;
            $this->progreso = 0;
            $this->detallesSincronizacion = null;

            // Simular progreso de sincronización
            $this->progreso = 20;
            $this->dispatch('actualizarProgreso', $this->progreso);

            $service = $this->getSincronizacionService();

            $this->progreso = 60;
            $this->dispatch('actualizarProgreso', $this->progreso);

            $resultado = $service->sincronizarTodosLosProductos();

            $this->progreso = 90;
            $this->dispatch('actualizarProgreso', $this->progreso);

            // Finalizar progreso
            $this->progreso = 100;
            $this->dispatch('actualizarProgreso', $this->progreso);

            // Preparar detalles de sincronización
            $this->detallesSincronizacion = [
                'productos_sincronizados' => $resultado['sincronizados'] ?? 0,
                'productos_creados' => $resultado['creados'] ?? 0,
                'productos_actualizados' => $resultado['actualizados'] ?? 0,
                'errores' => $resultado['errores'] ?? 0,
                'total_procesados' => ($resultado['sincronizados'] ?? 0) + ($resultado['errores'] ?? 0),
                'tiempo_ejecucion' => '~3 segundos'
            ];

            // Mensajes de estado
            if ($resultado['sincronizados'] > 0) {
                session()->flash('message', "✅ Sincronización completada: {$resultado['sincronizados']} productos procesados exitosamente.");
            }

            if ($resultado['errores'] > 0) {
                session()->flash('warning', "⚠️ Hubo {$resultado['errores']} errores durante la sincronización.");
            }

            if (($resultado['sincronizados'] ?? 0) === 0 && ($resultado['errores'] ?? 0) === 0) {
                session()->flash('info', "ℹ️ No se encontraron productos nuevos para sincronizar.");
            }

            // Mantener el modal de detalles abierto por 3 segundos
            $this->dispatch('mostrarDetalles');

        } catch (\Exception $e) {
            $this->progreso = 0;
            $this->detallesSincronizacion = [
                'error' => true,
                'mensaje_error' => $e->getMessage(),
                'productos_sincronizados' => 0,
                'errores' => 1
            ];
            session()->flash('error', '❌ Error al sincronizar productos: ' . $e->getMessage());
        } finally {
            // Finalizar proceso después de 2 segundos
            $this->dispatch('finalizarSincronizacion');
        }
    }

    public function cerrarDetallesSincronizacion()
    {
        $this->sincronizandoValencia = false;
        $this->progreso = 0;
        $this->detallesSincronizacion = null;
    }

    public function sincronizarProductoValencia($idProductoValencia)
    {
        try {
            $service = $this->getSincronizacionService();
            $resultado = $service->sincronizarProducto($idProductoValencia);

            if ($resultado['success']) {
                session()->flash('message', $resultado['mensaje']);
                // Los productos se refrescarán automáticamente en render()
            } else {
                session()->flash('error', $resultado['mensaje']);
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar producto: ' . $e->getMessage());
        }
    }

    public function editar($id)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm', parametros: ['id' => $id]);
    }

    public function abrirModalCrear()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm');
    }

    public function confirmarEliminar($id)
    {
        $this->productoAEliminar = $id;

        // Obtener información del producto para mostrar en el modal
        $producto = ProductoModel::with(['marca', 'subcategoria.categoria'])
            ->find($id);

        if ($producto) {
            $this->productoSeleccionado = (object) [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'marca' => $producto->marca->nombre ?? 'Sin marca',
                'categoria' => $producto->subcategoria->categoria->nombre ?? 'Sin categoría',
                'subcategoria' => $producto->subcategoria->nombre ?? 'Sin subcategoría'
            ];

            // Verificar código de barras
            $this->tieneCodigoBarras = !empty($producto->codigo_barra) && trim($producto->codigo_barra) !== '';

            // Obtener stock disponible
            $this->stockDisponible = $this->obtenerStockProducto($id);

            // Verificar compras activas o pendientes con cantidad sin asignar
            $this->tieneComprasActivas = $this->verificarComprasActivas($id);

            // Determinar si se puede eliminar
            $this->puedeEliminar = !$this->tieneCodigoBarras && $this->stockDisponible == 0 && !$this->tieneComprasActivas;
        }

        $this->modalEliminarAbierto = true;
    }

    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->productoAEliminar = null;
        $this->productoSeleccionado = null;
        $this->stockDisponible = 0;
        $this->tieneCodigoBarras = false;
        $this->tieneComprasActivas = false;
        $this->puedeEliminar = false;
    }

    public function eliminarProducto()
    {
        if ($this->productoAEliminar && $this->puedeEliminar) {
            try {
                ProductoModel::eliminarProducto($this->productoAEliminar);
                session()->flash('mensaje', 'Producto eliminado exitosamente.');
            } catch (\Exception $e) {
                session()->flash('error', 'Error al eliminar el producto: ' . $e->getMessage());
            }
        }
        $this->cerrarModalEliminar();
    }

    /**
     * Obtiene el stock total disponible de un producto en la bodega principal
     */
    private function obtenerStockProducto($productoId)
    {
        try {
            // Obtener el usuario actual y su tienda
            $usuario = Auth::user();
            if (!$usuario || !$usuario->tienda_id) {
                return 0;
            }

            // Calcular stock total disponible en la bodega principal
            $stockTotal = \Illuminate\Support\Facades\DB::table('recibido_bodega as rb')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('b.tienda_id', $usuario->tienda_id)
                ->where('b.principal', 1)
                ->where('b.estado_id', 1)
                ->where('rb.producto_id', $productoId)
                ->where('rb.estado_id', 1)
                ->sum('rb.cantidad_disponible');

            return $stockTotal ?? 0;

        } catch (\Exception $e) {
            // En caso de error, retornar 0 para no bloquear innecesariamente
            return 0;
        }
    }

    /**
     * Verifica si el producto tiene compras activas o pendientes con cantidad sin asignar
     */
    private function verificarComprasActivas($productoId)
    {
        try {
            // Verificar si existe en compra_has_producto con compras en estado activo (1) o pendiente (5)
            // y que tengan cantidad_sin_asignar diferente de 0
            $comprasActivas = \Illuminate\Support\Facades\DB::table('compra_has_producto as chp')
                ->join('compra as c', 'chp.compra_id', '=', 'c.id')
                ->join('estado as e', 'c.estado_id', '=', 'e.id')
                ->where('chp.producto_id', $productoId)
                ->whereIn('c.estado_id', [1, 5]) // Estados: Activo (1) y Pendiente (5)
                ->where('chp.cantidad_sin_asignar', '>', 0)
                ->exists();

            return $comprasActivas;

        } catch (\Exception $e) {
            // En caso de error, retornar true para prevenir eliminación accidental
            return true;
        }
    }

    public function descargarExcel()
    {
        try {
            // Obtener todos los productos según los filtros actuales (sin paginación)
            $productos = $this->aplicarFiltros(ProductoModel::with(['marca', 'subcategoria.categoria']))
                ->orderBy($this->ordenarPor, $this->direccionOrden)
                ->get();

            // Generar nombre del archivo con timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "productos_{$timestamp}.csv";

            // Configurar headers para descarga CSV (compatible con Excel)
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Crear el output
            $output = fopen('php://output', 'w');

            // BOM para UTF-8 (para que Excel reconozca correctamente los caracteres especiales)
            fwrite($output, "\xEF\xBB\xBF");

            // Encabezados
            fputcsv($output, [
                'ID',
                'Código de Barras',
                'Nombre',
                'Descripción',
                'Marca',
                'Categoría',
                'Subcategoría',
                'Precio Base',
                'Origen',
                'Fecha Creación'
            ]);

            // Datos
            foreach ($productos as $producto) {
                fputcsv($output, [
                    $producto->id,
                    $producto->codigo_barra ?: 'Sin código',
                    $producto->nombre,
                    $producto->descripcion ?: '',
                    $producto->marca->nombre ?? 'Sin marca',
                    $producto->subcategoria->categoria->nombre ?? 'N/A',
                    $producto->subcategoria->nombre ?? 'N/A',
                    'L. ' . number_format($producto->precio_base, 2),
                    $producto->producto_valencia ? 'Valencia' : 'Paperland',
                    $producto->created_at ? $producto->created_at->format('d/m/Y') : 'N/A'
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            Log::error('Error generating Excel', ['error' => $e->getMessage()]);
        }
    }

    public function descargarPDF()
    {
        try {
            // Obtener todos los productos según los filtros actuales (sin paginación)
            $productos = $this->aplicarFiltros(ProductoModel::with(['marca', 'subcategoria.categoria']))
                ->orderBy($this->ordenarPor, $this->direccionOrden)
                ->get();

            // Generar HTML para visualización/impresión
            $html = $this->generarHTMLParaPDF($productos);

            // Generar nombre del archivo
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "productos_{$timestamp}.html";

            // Configurar headers para descarga HTML
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Mostrar el HTML (se puede imprimir como PDF desde el navegador)
            echo $html;
            exit;

        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar el archivo PDF: ' . $e->getMessage());
            Log::error('Error generating PDF', ['error' => $e->getMessage()]);
        }
    }

    private function generarHTMLParaPDF($productos)
    {
        $totalProductos = $productos->count();
        $fechaGeneracion = now()->format('d/m/Y H:i:s');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Listado de Productos</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 10px; }
                h1 { text-align: center; color: #333; font-size: 16px; margin-bottom: 5px; }
                .header-info { text-align: center; color: #666; font-size: 9px; margin-bottom: 15px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; font-size: 9px; }
                td { font-size: 8px; }
                .text-center { text-align: center; }
                .origen-valencia { background-color: #FFF3CD; color: #856404; }
                .origen-paperland { background-color: #D4EDDA; color: #155724; }
                .precio { text-align: right; font-weight: bold; color: #28a745; }
                .footer { margin-top: 15px; text-align: center; font-size: 8px; color: #666; }
            </style>
        </head>
        <body>
            <h1>📦 LISTADO DE PRODUCTOS</h1>
            <div class="header-info">
                Generado el: ' . $fechaGeneracion . ' | Total de productos: ' . $totalProductos . '
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="12%">Código</th>
                        <th width="25%">Nombre</th>
                        <th width="10%">Marca</th>
                        <th width="15%">Categoría</th>
                        <th width="10%">Precio</th>
                        <th width="8%">Origen</th>
                        <th width="10%">Fecha</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($productos as $producto) {
            $origenClass = $producto->producto_valencia ? 'origen-valencia' : 'origen-paperland';
            $origenTexto = $producto->producto_valencia ? '🏢 Valencia' : '🏠 Paperland';

            $html .= '
                    <tr>
                        <td class="text-center">' . $producto->id . '</td>
                        <td>' . ($producto->codigo_barra ?: 'Sin código') . '</td>
                        <td>' . htmlspecialchars($producto->nombre) . '</td>
                        <td>' . htmlspecialchars($producto->marca->nombre ?? 'Sin marca') . '</td>
                        <td>' . htmlspecialchars($producto->subcategoria->categoria->nombre ?? 'N/A') . '</td>
                        <td class="precio">L. ' . number_format($producto->precio_base, 2) . '</td>
                        <td class="text-center ' . $origenClass . '">' . $origenTexto . '</td>
                        <td class="text-center">' . ($producto->created_at ? $producto->created_at->format('d/m/Y') : 'N/A') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                Sistema ZENVY - Gestión de Inventario | Página {PAGE_NUM} de {PAGE_COUNT}
            </div>
        </body>
        </html>';

        return $html;
    }

    // Limpiar completamente al destruir el componente
    public function destroying()
    {
        // Limpiar todas las sesiones relacionadas incluyendo filtros
        session()->forget(['current_component', 'producto_filtros']);
    }
}
