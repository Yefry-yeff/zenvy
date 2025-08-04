<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\RecibidoBodega;
use App\Models\UnidadMedida;
use App\Models\Bodega;
use App\Models\Segmento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibirEnBodega extends Component
{
    // Búsqueda de productos
    public $buscarProducto = '';
    public $productosSugeridos = [];
    public $mostrarSugerenciasProductos = false;
    
    // Producto seleccionado
    public $productoSeleccionado = null;
    public $nombreProducto = '';
    public $descripcionProducto = '';
    public $codigoBarraProducto = '';
    public $marcaProducto = '';
    public $unidadMedidaProducto = '';
    
    // Datos del recibido
    public $cantidadCompraLote = '';
    public $cantidadInicialSeccion = '';
    public $fechaRecibido = '';
    public $fechaExpiracion = '';
    public $comentario = '';
    public $unidadesCompra = '';
    public $unidadCompraId = null;
    
    // Secciones y bodegas
    public $seccionSeleccionada = null;
    public $secciones = [];
    public $unidadesMedida = [];
    
    // Modales y mensajes
    public $mostrarModalConfirmacion = false;
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    public function mount()
    {
        $this->fechaRecibido = date('Y-m-d');
        $this->cargarUnidadesMedida();
        $this->cargarSecciones();
    }

    public function updatedBuscarProducto()
    {
        if (strlen($this->buscarProducto) >= 2) {
            $this->buscarProductos();
            $this->mostrarSugerenciasProductos = true;
        } else {
            $this->productosSugeridos = [];
            $this->mostrarSugerenciasProductos = false;
            $this->limpiarSeleccionProducto();
        }
    }

    public function buscarProductos()
    {
        try {
            $this->productosSugeridos = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])
                ->where(function($query) {
                    $query->where('nombre', 'like', '%' . $this->buscarProducto . '%')
                          ->orWhere('codigo_barra', 'like', '%' . $this->buscarProducto . '%')
                          ->orWhere('codigo_estatal', 'like', '%' . $this->buscarProducto . '%');
                })
                ->where('estado_id', 1) // Solo productos activos
                ->limit(8)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar productos', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarProducto
            ]);
            $this->productosSugeridos = [];
        }
    }

    public function seleccionarProducto($productoId)
    {
        try {
            $producto = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])->findOrFail($productoId);
            
            // Debug temporal
            Log::info('Producto seleccionado:', [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'marca_id' => $producto->marca_id,
                'marca' => $producto->marca ? $producto->marca->toArray() : null,
                'unidad_medida_compra_id' => $producto->unidad_medida_compra_id,
                'unidad_medida' => $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->toArray() : null
            ]);
            
            $this->productoSeleccionado = $producto;
            $this->buscarProducto = $producto->nombre;
            $this->nombreProducto = $producto->nombre;
            $this->descripcionProducto = $producto->descripcion;
            $this->codigoBarraProducto = $producto->codigo_barra ?? 'N/A';
            $this->marcaProducto = $producto->marca ? $producto->marca->nombre : 'Sin marca';
            $this->unidadMedidaProducto = $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->nombre : 'N/A';
            $this->unidadCompraId = $producto->unidad_medida_compra_id;
            
            $this->mostrarSugerenciasProductos = false;
            $this->productosSugeridos = [];
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar producto', [
                'producto_id' => $productoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos del producto');
        }
    }

    public function limpiarSeleccionProducto()
    {
        $this->productoSeleccionado = null;
        $this->nombreProducto = '';
        $this->descripcionProducto = '';
        $this->codigoBarraProducto = '';
        $this->marcaProducto = '';
        $this->unidadMedidaProducto = '';
        $this->unidadCompraId = null;
    }

    public function confirmarRecibido()
    {
        // Validaciones
        if (!$this->productoSeleccionado) {
            $this->mostrarError('Debe seleccionar un producto');
            return;
        }

        if (!$this->cantidadCompraLote || $this->cantidadCompraLote <= 0) {
            $this->mostrarError('La cantidad del lote debe ser mayor a 0');
            return;
        }

        if (!$this->cantidadInicialSeccion || $this->cantidadInicialSeccion <= 0) {
            $this->mostrarError('La cantidad inicial en sección debe ser mayor a 0');
            return;
        }

        if (!$this->seccionSeleccionada) {
            $this->mostrarError('Debe seleccionar una sección');
            return;
        }

        if (!$this->fechaRecibido) {
            $this->mostrarError('Debe especificar la fecha de recibido');
            return;
        }

        $this->mostrarModalConfirmacion = true;
    }

    public function ejecutarRecibido()
    {
        try {
            DB::beginTransaction();

            $recibidoBodega = RecibidoBodega::create([
                'producto_id' => $this->productoSeleccionado->id,
                'seccion_id' => $this->seccionSeleccionada,
                'cantidad_compra_lote' => $this->cantidadCompraLote,
                'cantidad_inicial_seccion' => $this->cantidadInicialSeccion,
                'cantidad_disponible' => $this->cantidadInicialSeccion, // Inicialmente igual a la cantidad inicial
                'fecha_recibido' => $this->fechaRecibido,
                'fecha_expiracion' => $this->fechaExpiracion,
                'comentario' => $this->comentario,
                'unidades_compra' => $this->unidadesCompra,
                'unidad_compra_id' => $this->unidadCompraId,
                'users_registro_id' => Auth::id(),
                'estado_id' => 1
            ]);

            DB::commit();

            $this->mostrarModalConfirmacion = false;
            $this->mostrarExito('Producto recibido en bodega exitosamente');
            $this->limpiarFormulario();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar recibido en bodega', [
                'producto_id' => $this->productoSeleccionado->id,
                'seccion_id' => $this->seccionSeleccionada,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarModalConfirmacion = false;
            $this->mostrarError('Error al registrar el recibido en bodega');
        }
    }

    public function cancelarRecibido()
    {
        $this->mostrarModalConfirmacion = false;
    }

    public function limpiarFormulario()
    {
        $this->buscarProducto = '';
        $this->productosSugeridos = [];
        $this->mostrarSugerenciasProductos = false;
        $this->limpiarSeleccionProducto();
        
        $this->cantidadCompraLote = '';
        $this->cantidadInicialSeccion = '';
        $this->fechaRecibido = date('Y-m-d');
        $this->fechaExpiracion = '';
        $this->comentario = '';
        $this->unidadesCompra = '';
        $this->seccionSeleccionada = null;
    }

    private function cargarSecciones()
    {
        try {
            $user = Auth::user();
            
            if ($user->rol && $user->rol->txt_nombre === 'Admin') {
                // Admin puede ver todas las secciones de todas las bodegas
                $this->secciones = Seccion::with(['segmento.bodega.tienda'])
                    ->whereHas('segmento.bodega', function($query) {
                        $query->where('estado_id', 1);
                    })
                    ->where('estado_id', 1)
                    ->orderBy('descripcion')
                    ->get();
            } else {
                // Usuarios normales solo ven secciones de bodegas de su tienda
                $this->secciones = Seccion::with(['segmento.bodega.tienda'])
                    ->whereHas('segmento.bodega', function($query) use ($user) {
                        $query->where('tienda_id', $user->tienda_id)
                              ->where('estado_id', 1);
                    })
                    ->where('estado_id', 1)
                    ->orderBy('descripcion')
                    ->get();
            }
        } catch (\Exception $e) {
            Log::error('Error al cargar secciones', [
                'mensaje' => $e->getMessage()
            ]);
            $this->secciones = [];
        }
    }

    private function cargarUnidadesMedida()
    {
        try {
            $this->unidadesMedida = UnidadMedida::orderBy('nombre')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar unidades de medida', [
                'mensaje' => $e->getMessage()
            ]);
            $this->unidadesMedida = [];
        }
    }

    private function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    private function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    public function render()
    {
        return view('livewire.inventario.recibir-en-bodega');
    }
}
