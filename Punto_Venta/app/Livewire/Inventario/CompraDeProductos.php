<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compra;
use App\Models\Estado;
use App\Services\SincronizacionComprasService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class CompraDeProductos extends Component
{
    use WithPagination;

    // Propiedades para filtros y búsqueda
    public $busqueda = '';
    public $filtroEstado = '';
    public $filtroFecha = '';

    // Propiedades para ordenamiento
    public $ordenarPor = 'id';
    public $direccionOrden = 'desc';

    // Propiedades para paginación
    public $registrosPorPagina = 10;
    public $page = 1; // Agregamos la propiedad page

    // REMOVIDO: protected $queryString - Ya no persiste parámetros en URL
    // Los filtros se manejarán solo con sesión

    // Propiedades para alertas
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';

    // Propiedades para modal de anulación
    public $mostrarModalAnular = false;
    public $compraSeleccionada = null;
    public $motivoAnulacion = '';

    // Propiedades para modal de detalle
    public $mostrarModalDetalle = false;
    public $compraDetalle = null;

    // Propiedades para efectos de carga en sincronización
    public $sincronizandoCompras = false;
    public $progreso = null;
    public $detallesSincronizacion = null;

    private $sincronizacionService;

    public function boot()
    {
        // Simplemente marcar el componente activo - DynamicContent maneja la limpieza URL
        session(['current_component' => 'compra-de-productos']);
    }

    public function hydrate()
    {
        // Solo verificar compatibilidad básica - DynamicContent maneja los redirects
        $parametrosURL = request()->query();
        
        if (!empty($parametrosURL)) {
            // Solo verificar parámetros críticos que definitivamente no pertenecen aquí
            $parametrosProhibidos = ['filtroProducto', 'filtroBodega', 'filtroMarca'];
            
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
        session(['compras_filtros' => [
            'busqueda' => $this->busqueda,
            'filtroEstado' => $this->filtroEstado,
            'filtroFecha' => $this->filtroFecha,
            'ordenarPor' => $this->ordenarPor,
            'direccionOrden' => $this->direccionOrden,
            'page' => $this->page
        ]]);
    }

    public function booted()
    {
        // Inicializar servicio
        $this->sincronizacionService = app(SincronizacionComprasService::class);
    }

    public function mount()
    {
        // Restaurar filtros desde sesión si existen
        $filtrosSesion = session('compras_filtros');
        if ($filtrosSesion) {
            $this->busqueda = $filtrosSesion['busqueda'] ?? '';
            $this->filtroEstado = $filtrosSesion['filtroEstado'] ?? '';
            $this->filtroFecha = $filtrosSesion['filtroFecha'] ?? '';
            $this->ordenarPor = $filtrosSesion['ordenarPor'] ?? 'id';
            $this->direccionOrden = $filtrosSesion['direccionOrden'] ?? 'desc';
            $this->page = $filtrosSesion['page'] ?? 1;
        }
        
        // Detectar si hay parámetros de otra vista
        $parametrosURL = request()->query();
        $parametrosOtraVista = ['filtroProducto', 'filtroBodega', 'filtroMarca']; // Parámetros exclusivos de lista-de-productos
        
        foreach ($parametrosOtraVista as $param) {
            if (isset($parametrosURL[$param])) {
                // Si hay parámetros de otra vista, hacer redirect limpio
                return redirect()->route('dashboard');
            }
        }
        
        if (session('reset_compras_params')) {
            session()->forget('reset_compras_params');
            $this->ordenarPor = 'id';
            $this->direccionOrden = 'desc';
            $this->busqueda = '';
            $this->filtroEstado = '';
            $this->filtroFecha = '';
            $this->page = 1; // Resetear también la página
            $this->resetPage();
        }
        
        session(['current_component' => 'compra-de-productos']);
    }

        // Escuchar evento de distribución completada
    #[On('compra-distribuida')]
    public function actualizarDespuesDistribucion($compraId = null)
    {
        // Refrescar la vista para mostrar los nuevos estados
        $this->resetPage(); // Reset pagination to show changes
        $this->render(); // Force re-render

        // Agregar mensaje de confirmación
        if ($compraId) {
            $compra = Compra::find($compraId);
            if ($compra) {
                session()->flash('success', "La factura {$compra->numero_factura} ha sido marcada como distribuida.");
            }
        }
    }

    // Escuchar evento de cambio de estado de compra
    #[On('estado-compra-actualizado')]
    public function refrescarListado($compraId = null, $nuevoEstado = null)
    {
        // Refrescar la vista cuando se actualiza el estado de una compra
        $this->resetPage();
        $this->render(); // Force re-render
    }

    // Escuchar evento de cualquier actualización de compra
    #[On('compra-actualizada')]
    public function actualizarCompra($compraId = null)
    {
        // Refrescar la vista cuando se actualiza cualquier compra
        $this->resetPage();
        $this->render(); // Force re-render
    }

    // Método para ir a la vista de recibir producto específico
    public function irARecibirProducto($compraId)
    {
        $compra = Compra::with(['estado'])->find($compraId);
        if ($compra && $compra->estado && (strtolower($compra->estado->nombre) === 'activo' || strtolower($compra->estado->nombre) === 'pendiente' || $compra->estado_id == 5)) {
            // Redirigir a la vista de recibir producto con el ID de la compra
            $this->dispatch('cambiarVista', ruta: 'Inventario.RecibirProductoCompra', parametros: ['compraId' => $compraId]);
        } else {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Solo se pueden recibir productos de compras en estado "activo" o "pendiente".';
        }
    }

    // Método para refrescar manualmente el componente
    public function refrescarComponente()
    {
        $this->resetPage();
        $this->render();
    }

    // Resetear paginación cuando se cambian los filtros
    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedFiltroFecha()
    {
        $this->resetPage();
    }

    public function updatedRegistrosPorPagina()
    {
        $this->resetPage();
    }

    // Método para ordenamiento
    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
            
            // Para ID, empezar siempre en DESC
            if ($campo === 'id') {
                $this->direccionOrden = 'desc';
            }
        }
        $this->resetPage();
    }

    // Limpiar completamente al destruir el componente
    public function destroying()
    {
        // Limpiar todas las sesiones relacionadas incluyendo filtros
        session()->forget(['compras_ordenamiento', 'current_component', 'reset_compras_params', 'compras_filtros']);
        
        // Si hay parámetros en URL, forzar redirect limpio al dashboard
        if (request()->has(['ordenarPor', 'direccionOrden', 'busqueda', 'filtroEstado', 'filtroFecha', 'page'])) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    // Método para agregar nueva compra
    public function agregarCompra()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.CompraDeProducto');
    }

    // Método para cerrar alerta
    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
    }

    // Método para abrir modal de anulación
    public function abrirModalAnular($compraId)
    {
        $compra = Compra::with(['proveedor', 'estado'])->find($compraId);
        if ($compra && $compra->estado) {
            $estadoNombre = strtolower($compra->estado->nombre);
            $estadoId = $compra->estado_id;
            
            // Solo permitir anular si está en estado "activo" (1)
            // No permitir anular si está en estado "pendiente" (5) por distribución parcial
            if ($estadoNombre === 'activo' && $estadoId != 5) {
                $this->compraSeleccionada = [
                    'id' => $compra->id,
                    'numero_factura' => $compra->numero_factura,
                    'proveedor_nombre' => $compra->proveedor->nombre ?? 'N/A',
                    'total' => $compra->detallesCompra->sum('precio_total') ?? 0
                ];
                $this->motivoAnulacion = '';
                $this->mostrarModalAnular = true;
            } else {
                $this->mostrarAlerta = true;
                if ($estadoId == 5) {
                    $this->mensajeAlerta = 'No se puede anular esta compra porque tiene productos distribuidos parcialmente (estado Pendiente).';
                } else {
                    $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
                }
            }
        } else {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Compra no encontrada.';
        }
    }

    // Método para cerrar modal de anulación
    public function cerrarModalAnular()
    {
        $this->mostrarModalAnular = false;
        $this->compraSeleccionada = null;
        $this->motivoAnulacion = '';
    }

    // Método para abrir modal de detalle
    public function verDetalle($compraId)
    {
        $compra = Compra::with(['proveedor', 'estado', 'detallesCompra.producto', 'detallesCompra.unidadMedida'])->find($compraId);
        if ($compra) {
            $this->compraDetalle = [
                'id' => $compra->id,
                'numero_factura' => $compra->numero_factura,
                'proveedor_nombre' => $compra->proveedor->nombre ?? 'N/A',
                'fecha_emision' => $compra->fecha_emision,
                'fecha_recepcion' => $compra->fecha_recepcion,
                'fecha_vencimiento' => $compra->fecha_vencimiento,
                'estado' => $compra->estado->nombre ?? 'Sin Estado',
                'productos' => $compra->detallesCompra->map(function($detalle) {
                    return [
                        'nombre' => $detalle->producto->nombre ?? 'N/A',
                        'cantidad' => $detalle->cantidad_ingresada,
                        'precio_unitario' => $detalle->precio,
                        'subtotal' => $detalle->sub_total_producto,
                        'isv' => $detalle->isv,
                        'precio_total' => $detalle->precio_total,
                        'unidad' => $detalle->unidadMedida->nombre ?? 'N/A',
                        'fecha_expiracion' => $detalle->fecha_expiracion
                    ];
                })->toArray(),
                'total_productos' => $compra->detallesCompra->count(),
                'subtotal_general' => $compra->detallesCompra->sum('sub_total_producto'),
                'isv_general' => $compra->detallesCompra->sum('isv'),
                'total_general' => $compra->detallesCompra->sum('precio_total')
            ];
            $this->mostrarModalDetalle = true;
        }
    }

    // Método para cerrar modal de detalle
    public function cerrarModalDetalle()
    {
        $this->mostrarModalDetalle = false;
        $this->compraDetalle = null;
    }

    // Método para confirmar anulación
    public function confirmarAnulacion()
    {
        $this->validate([
            'motivoAnulacion' => 'required|min:10|max:500'
        ], [
            'motivoAnulacion.required' => 'El motivo de anulación es obligatorio.',
            'motivoAnulacion.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivoAnulacion.max' => 'El motivo no puede exceder 500 caracteres.'
        ]);

        try {
            $compra = Compra::with('estado')->find($this->compraSeleccionada['id']);
            if ($compra && $compra->estado) {
                $estadoNombre = strtolower($compra->estado->nombre);
                $estadoId = $compra->estado_id;
                
                // Solo permitir anular si está en estado "activo" (1)
                // No permitir anular si está en estado "pendiente" (5) por distribución parcial
                if ($estadoNombre === 'activo' && $estadoId != 5) {
                    // Buscar el estado "anulado"
                    $estadoAnulado = Estado::whereRaw('LOWER(nombre) = ?', ['anulado'])->first();
                    if ($estadoAnulado) {
                        $compra->estado_id = $estadoAnulado->id;
                        $compra->save();

                        session()->flash('success', 'Compra anulada exitosamente.');
                        $this->cerrarModalAnular();
                    } else {
                        $this->mostrarAlerta = true;
                        $this->mensajeAlerta = 'No se encontró el estado "anulado" en el sistema.';
                    }
                } else {
                    $this->mostrarAlerta = true;
                    if ($estadoId == 5) {
                        $this->mensajeAlerta = 'No se puede anular esta compra porque tiene productos distribuidos parcialmente (estado Pendiente).';
                    } else {
                        $this->mensajeAlerta = 'Solo se pueden anular compras en estado "activo".';
                    }
                }
            } else {
                $this->mostrarAlerta = true;
                $this->mensajeAlerta = 'Compra no encontrada.';
            }
        } catch (\Exception $e) {
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Error al anular la compra: ' . $e->getMessage();
        }
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionComprasService::class);
        }
        return $this->sincronizacionService;
    }

    public function sincronizarComprasValencia()
    {
        try {
            // Iniciar el proceso de sincronización
            $this->sincronizandoCompras = true;
            $this->progreso = 0;
            $this->detallesSincronizacion = null;

            // Simular progreso de sincronización
            for ($i = 0; $i <= 100; $i += 25) {
                $this->progreso = $i;
                $this->dispatch('actualizarProgreso', $this->progreso);
                usleep(200000); // 0.2 segundos
            }

            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            
            // Finalizar progreso
            $this->progreso = 100;
            $this->dispatch('actualizarProgreso', $this->progreso);
            
            // Preparar detalles de sincronización
            $this->detallesSincronizacion = [
                'compras_sincronizadas' => $resultado['estadisticas']['compras_nuevas'] ?? 0,
                'compras_nuevas' => $resultado['estadisticas']['compras_nuevas'] ?? 0,
                'productos_sincronizados' => $resultado['estadisticas']['productos_sincronizados'] ?? 0,
                'total_procesadas' => $resultado['estadisticas']['total_procesadas'] ?? 0,
                'errores' => $resultado['estadisticas']['errores'] ?? 0,
                'tiempo_ejecucion' => '~2 segundos'
            ];
            
            // Mensajes de estado
            if (($resultado['estadisticas']['compras_nuevas'] ?? 0) > 0) {
                session()->flash('mensaje', '✅ Sincronización completada: ' . $this->detallesSincronizacion['compras_nuevas'] . ' compras nuevas procesadas exitosamente.');
            } else {
                session()->flash('mensaje', '✅ Sincronización completada: No hay nuevas compras para sincronizar.');
            }
            
            Log::info('Sincronización manual de compras Valencia: ' . json_encode($resultado));
            
            // Finalizar estado de carga
            $this->sincronizandoCompras = false;
            
        } catch (\Exception $e) {
            $this->sincronizandoCompras = false;
            $this->progreso = 0;
            
            session()->flash('error', 'Error al sincronizar compras de Valencia: ' . $e->getMessage());
            Log::error('Error en sincronización Valencia: ' . $e->getMessage());
        }
    }

    public function cerrarDetallesSincronizacion()
    {
        $this->detallesSincronizacion = null;
    }

    public function render()
    {
        $query = Compra::with(['proveedor', 'detallesCompra', 'estado']);

        // Aplicar filtros
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('numero_factura', 'like', '%' . $this->busqueda . '%')
                  ->orWhereHas('proveedor', function($proveedorQuery) {
                      $proveedorQuery->where('nombre', 'like', '%' . $this->busqueda . '%');
                  });
            });
        }

        if ($this->filtroEstado) {
            $query->whereHas('estado', function($estadoQuery) {
                $estadoQuery->whereRaw('LOWER(nombre) = ?', [strtolower($this->filtroEstado)]);
            });
        }

        if ($this->filtroFecha) {
            $query->where(function($q) {
                $q->whereDate('fecha_emision', $this->filtroFecha)
                  ->orWhereDate('fecha_recepcion', $this->filtroFecha);
            });
        }

        // Aplicar ordenamiento dinámico
        switch ($this->ordenarPor) {
            case 'numero_factura':
                $query->orderBy('numero_factura', $this->direccionOrden);
                break;
            case 'proveedor':
                $query->join('proveedors', 'compras.proveedor_id', '=', 'proveedors.id')
                      ->orderBy('proveedors.nombre', $this->direccionOrden)
                      ->select('compras.*');
                break;
            case 'fecha_emision':
                $query->orderBy('fecha_emision', $this->direccionOrden);
                break;
            case 'fecha_recepcion':
                $query->orderBy('fecha_recepcion', $this->direccionOrden);
                break;
            case 'estado':
                $query->join('estados', 'compras.estado_id', '=', 'estados.id')
                      ->orderBy('estados.nombre', $this->direccionOrden)
                      ->select('compras.*');
                break;
            case 'productos':
                $query->withCount('detallesCompra')
                      ->orderBy('detalles_compra_count', $this->direccionOrden);
                break;
            case 'total':
                $query->withSum('detallesCompra', 'precio_total')
                      ->orderBy('detalles_compra_sum_precio_total', $this->direccionOrden);
                break;
            case 'id':
            default:
                $query->orderBy('id', $this->direccionOrden);
                break;
        }

        // Paginación configurable
        $compras = $query->paginate($this->registrosPorPagina);

        return view('livewire.inventario.compra-de-productos', [
            'compras' => $compras
        ]);
    }
}
