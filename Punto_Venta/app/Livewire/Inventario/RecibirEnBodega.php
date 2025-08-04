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
    
    // Jerarquía Bodega → Segmento → Sección
    public $buscarBodega = '';
    public $bodegasSugeridas = [];
    public $mostrarSugerenciasBodegas = false;
    public $bodegaSeleccionada = null;
    public $nombreBodega = '';
    
    public $buscarSegmento = '';
    public $segmentosSugeridos = [];
    public $mostrarSugerenciasSegmentos = false;
    public $segmentoSeleccionado = null;
    public $nombreSegmento = '';
    
    public $buscarSeccion = '';
    public $seccionesSugeridas = [];
    public $mostrarSugerenciasSecciones = false;
    public $nombreSeccion = '';
    
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
    }

    // Búsqueda de Bodegas
    public function updatedBuscarBodega()
    {
        if (strlen($this->buscarBodega) >= 1) {
            $this->buscarBodegas();
        } else {
            $this->mostrarTodasBodegas();
        }
        $this->mostrarSugerenciasBodegas = true;
    }

    public function enfocarBodega()
    {
        $this->mostrarTodasBodegas();
        $this->mostrarSugerenciasBodegas = true;
    }

    public function mostrarTodasBodegas()
    {
        try {
            $user = Auth::user();
            
            $query = Bodega::with('tienda')
                ->where('estado_id', 1);
                
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $query->where('tienda_id', $user->tienda_id);
            }
                
            $this->bodegasSugeridas = $query->limit(10)->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todas las bodegas', [
                'mensaje' => $e->getMessage()
            ]);
            $this->bodegasSugeridas = [];
        }
    }

    public function buscarBodegas()
    {
        try {
            $user = Auth::user();
            
            $query = Bodega::with('tienda')
                ->where('nombre', 'like', '%' . $this->buscarBodega . '%')
                ->where('estado_id', 1);
                
            if ($user->rol && $user->rol->txt_nombre !== 'Admin') {
                $query->where('tienda_id', $user->tienda_id);
            }
                
            $this->bodegasSugeridas = $query->limit(10)->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar bodegas', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarBodega
            ]);
            $this->bodegasSugeridas = [];
        }
    }

    public function seleccionarBodega($bodegaId)
    {
        try {
            $bodega = Bodega::with('tienda')->findOrFail($bodegaId);
            
            $this->bodegaSeleccionada = $bodega;
            $this->buscarBodega = $bodega->nombre;
            $this->nombreBodega = $bodega->nombre;
            
            $this->mostrarSugerenciasBodegas = false;
            $this->bodegasSugeridas = [];
            
            // Limpiar selecciones dependientes
            $this->limpiarSeleccionSegmento();
            $this->limpiarSeleccionSeccion();
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar bodega', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la bodega');
        }
    }

    public function limpiarSeleccionBodega()
    {
        $this->bodegaSeleccionada = null;
        $this->nombreBodega = '';
        $this->limpiarSeleccionSegmento();
        $this->limpiarSeleccionSeccion();
    }

    // Búsqueda de Segmentos
    public function updatedBuscarSegmento()
    {
        if ($this->bodegaSeleccionada) {
            if (strlen($this->buscarSegmento) >= 1) {
                $this->buscarSegmentos();
            } else {
                $this->mostrarTodosSegmentos();
            }
            $this->mostrarSugerenciasSegmentos = true;
        } else {
            $this->segmentosSugeridos = [];
            $this->mostrarSugerenciasSegmentos = false;
        }
    }

    public function enfocarSegmento()
    {
        if ($this->bodegaSeleccionada) {
            $this->mostrarTodosSegmentos();
            $this->mostrarSugerenciasSegmentos = true;
        }
    }

    public function mostrarTodosSegmentos()
    {
        try {
            if (!$this->bodegaSeleccionada) return;
            
            $this->segmentosSugeridos = Segmento::where('bodega_id', $this->bodegaSeleccionada->id)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todos los segmentos', [
                'mensaje' => $e->getMessage(),
                'bodega_id' => $this->bodegaSeleccionada->id ?? null
            ]);
            $this->segmentosSugeridos = [];
        }
    }

    public function buscarSegmentos()
    {
        try {
            if (!$this->bodegaSeleccionada) return;
            
            $this->segmentosSugeridos = Segmento::where('bodega_id', $this->bodegaSeleccionada->id)
                ->where('descripcion', 'like', '%' . $this->buscarSegmento . '%')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar segmentos', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarSegmento,
                'bodega_id' => $this->bodegaSeleccionada->id ?? null
            ]);
            $this->segmentosSugeridos = [];
        }
    }

    public function seleccionarSegmento($segmentoId)
    {
        try {
            $segmento = Segmento::findOrFail($segmentoId);
            
            $this->segmentoSeleccionado = $segmento;
            $this->buscarSegmento = $segmento->descripcion;
            $this->nombreSegmento = $segmento->descripcion;
            
            $this->mostrarSugerenciasSegmentos = false;
            $this->segmentosSugeridos = [];
            
            // Limpiar selección de sección
            $this->limpiarSeleccionSeccion();
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar segmento', [
                'segmento_id' => $segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos del segmento');
        }
    }

    public function limpiarSeleccionSegmento()
    {
        $this->segmentoSeleccionado = null;
        $this->buscarSegmento = '';
        $this->nombreSegmento = '';
        $this->segmentosSugeridos = [];
        $this->mostrarSugerenciasSegmentos = false;
        $this->limpiarSeleccionSeccion();
    }

    // Búsqueda de Secciones
    public function updatedBuscarSeccion()
    {
        if ($this->segmentoSeleccionado) {
            if (strlen($this->buscarSeccion) >= 1) {
                $this->buscarSecciones();
            } else {
                $this->mostrarTodasSecciones();
            }
            $this->mostrarSugerenciasSecciones = true;
        } else {
            $this->seccionesSugeridas = [];
            $this->mostrarSugerenciasSecciones = false;
        }
    }

    public function enfocarSeccion()
    {
        if ($this->segmentoSeleccionado) {
            $this->mostrarTodasSecciones();
            $this->mostrarSugerenciasSecciones = true;
        }
    }

    public function mostrarTodasSecciones()
    {
        try {
            if (!$this->segmentoSeleccionado) return;
            
            $this->seccionesSugeridas = Seccion::where('segmento_id', $this->segmentoSeleccionado->id)
                ->where('estado_id', 1)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todas las secciones', [
                'mensaje' => $e->getMessage(),
                'segmento_id' => $this->segmentoSeleccionado->id ?? null
            ]);
            $this->seccionesSugeridas = [];
        }
    }

    public function buscarSecciones()
    {
        try {
            if (!$this->segmentoSeleccionado) return;
            
            $this->seccionesSugeridas = Seccion::where('segmento_id', $this->segmentoSeleccionado->id)
                ->where('descripcion', 'like', '%' . $this->buscarSeccion . '%')
                ->where('estado_id', 1)
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar secciones', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarSeccion,
                'segmento_id' => $this->segmentoSeleccionado->id ?? null
            ]);
            $this->seccionesSugeridas = [];
        }
    }

    public function seleccionarSeccion($seccionId)
    {
        try {
            $seccion = Seccion::findOrFail($seccionId);
            
            $this->seccionSeleccionada = $seccion->id;
            $this->buscarSeccion = $seccion->descripcion;
            $this->nombreSeccion = $seccion->descripcion;
            
            $this->mostrarSugerenciasSecciones = false;
            $this->seccionesSugeridas = [];
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar sección', [
                'seccion_id' => $seccionId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos de la sección');
        }
    }

    public function limpiarSeleccionSeccion()
    {
        $this->seccionSeleccionada = null;
        $this->buscarSeccion = '';
        $this->nombreSeccion = '';
        $this->seccionesSugeridas = [];
        $this->mostrarSugerenciasSecciones = false;
    }

    public function updatedBuscarProducto()
    {
        if (strlen($this->buscarProducto) >= 1) {
            $this->buscarProductos();
        } else {
            $this->mostrarTodosProductos();
        }
        $this->mostrarSugerenciasProductos = true;
    }

    public function enfocarProducto()
    {
        $this->mostrarTodosProductos();
        $this->mostrarSugerenciasProductos = true;
    }

    public function mostrarTodosProductos()
    {
        try {
            $this->productosSugeridos = Producto::with(['subcategoria.categoria', 'marca', 'unidadMedidaCompra'])
                ->where('estado_id', 1)
                ->orderBy('nombre')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todos los productos', [
                'mensaje' => $e->getMessage()
            ]);
            $this->productosSugeridos = [];
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
                ->limit(10)
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
        
        // Limpiar jerarquía bodega → segmento → sección
        $this->limpiarSeleccionBodega();
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
