<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Seccion;
use App\Models\RecibidoBodega;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductosSeccion extends Component
{
    public $seccionId;
    public $seccion;
    
    // Filtros y búsqueda
    public $buscar = '';
    public $filtroEstado = '';

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtroEstado' => ['except' => '']
    ];

    public function mount($seccionId)
    {
        $this->seccionId = $seccionId;
        $this->cargarDatos();
    }

    private function cargarDatos()
    {
        try {
            $this->seccion = Seccion::with(['segmento.bodega.tienda'])->findOrFail($this->seccionId);
        } catch (\Exception $e) {
            Log::error('Error al cargar datos de la sección', [
                'seccion_id' => $this->seccionId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la sección');
        }
    }

    public function render()
    {
        $productos = $this->obtenerProductos();

        return view('livewire.inventario.productos-seccion', [
            'productos' => $productos
        ]);
    }

    private function obtenerProductos()
    {
        try {
            $query = RecibidoBodega::with([
                'producto.marca',
                'producto.subcategoria.categoria',
                'producto.subcategoria',
                'producto.unidadMedidaCompra',
                'producto.unidadMedidaVenta'
            ])
            ->where('seccion_id', $this->seccionId)
            ->where('cantidad_inicial_seccion', '>', 0); // Solo productos con stock

            // Aplicar filtro de búsqueda
            if (!empty($this->buscar)) {
                $query->whereHas('producto', function($q) {
                    $q->where('nombre', 'like', '%' . $this->buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->buscar . '%')
                      ->orWhere('codigo_barra', 'like', '%' . $this->buscar . '%')
                      ->orWhere('codigo_estatal', 'like', '%' . $this->buscar . '%');
                });
            }

            // Aplicar filtro de estado (podríamos usar el estado del producto)
            if ($this->filtroEstado !== '') {
                $query->whereHas('producto', function($q) {
                    $q->where('estado_id', $this->filtroEstado);
                });
            }

            return $query->orderBy('fecha_recibido', 'desc')
                        ->orderBy('id', 'desc')
                        ->get(); // Cambiar a get() en lugar de paginate()

        } catch (\Exception $e) {
            Log::error('Error al obtener productos de la sección', [
                'seccion_id' => $this->seccionId,
                'mensaje' => $e->getMessage()
            ]);
            
            return collect(); // Retornar colección vacía
        }
    }

    // ===== MÉTODOS DE NAVEGACIÓN =====

    public function volverASecciones()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Secciones', parametros: [
            'bodegaId' => $this->seccion->segmento->bodega->id,
            'segmentoId' => $this->seccion->segmento->id
        ]);
    }

    public function volverASegmentos()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Segmentos', parametros: [
            'bodegaId' => $this->seccion->segmento->bodega->id
        ]);
    }

    public function volverABodegas()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Bodegas');
    }

    // ===== MÉTODOS DE FILTRADO =====

    public function limpiarFiltros()
    {
        $this->buscar = '';
        $this->filtroEstado = '';
    }

    public function updatedBuscar()
    {
        // DataTables manejará la actualización automáticamente
    }

    public function updatedFiltroEstado()
    {
        // DataTables manejará la actualización automáticamente
    }

    // ===== MÉTODOS DE MODALES =====

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
    }

    // ===== MÉTODOS DE UTILIDAD =====

    public function obtenerEstadoTexto($estado)
    {
        return $estado == 1 ? 'Activo' : 'Inactivo';
    }

    public function obtenerEstadoClase($estado)
    {
        return $estado == 1 ? 'badge bg-success' : 'badge bg-secondary';
    }

    public function formatearFecha($fecha)
    {
        return $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'N/A';
    }

    public function formatearMoneda($monto)
    {
        return 'L. ' . number_format($monto, 2, '.', ',');
    }
}
