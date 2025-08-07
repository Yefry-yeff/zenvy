<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\RecibidoBodega;
use App\Models\DistribucionStock;
use App\Models\Producto as ProductoModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StockForm extends Component
{
    public $recibidoId;
    public $seccionId;
    public $recibido;
    public $producto;
    public $isEditing = false;
    public $distribuciones = [];
    public $totalDistribuido = 0;
    public $stockDisponible = 0;

    // Formulario de distribución
    public $form = [
        'cantidad_asignada_bodega' => 0,
        'cantidad_distribuir' => 0,
        'precio_unitario' => 0,
        'fecha_distribucion' => '',
        'comentario' => '',
    ];

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';
    public $camposConError = [];
    public $erroresValidacion = [];

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    protected $rules = [
        'form.cantidad_distribuir' => 'required|integer|min:1',
        'form.precio_unitario' => 'required|numeric|min:0.01',
        'form.fecha_distribucion' => 'required|date',
        'form.comentario' => 'nullable|string|max:400',
    ];

    protected $messages = [
        'form.cantidad_distribuir.required' => 'La cantidad a distribuir es obligatoria',
        'form.cantidad_distribuir.min' => 'La cantidad a distribuir debe ser mayor a 0',
        'form.precio_unitario.required' => 'El precio unitario es obligatorio',
        'form.precio_unitario.min' => 'El precio unitario debe ser mayor a 0.01',
        'form.fecha_distribucion.required' => 'La fecha de distribución es obligatoria',
        'form.comentario.max' => 'El comentario no puede exceder 400 caracteres',
    ];

    public function mount($productoId, $seccionId = null)
    {
        try {
            // Si se pasa seccionId, estamos editando desde ProductosSeccion
            if ($seccionId) {
                $this->seccionId = $seccionId;
                // Buscar el registro de RecibidoBodega para este producto en esta sección
                $this->recibido = RecibidoBodega::where('producto_id', $productoId)
                    ->where('seccion_id', $seccionId)
                    ->with(['producto', 'seccion.segmento.bodega.tienda'])
                    ->firstOrFail();
                
                $this->recibidoId = $this->recibido->id;
                $this->isEditing = true;
                $this->cargarDatosRecibido();
            }

            // Cargar datos del producto
            $this->producto = ProductoModel::with(['marca', 'subcategoria.categoria', 'unidadMedidaCompra'])
                ->findOrFail($productoId);

            // Inicializar fecha de distribución con la fecha actual
            $this->form['fecha_distribucion'] = now()->format('Y-m-d');

        } catch (\Exception $e) {
            Log::error('Error al cargar datos para edición de stock', [
                'producto_id' => $productoId,
                'seccion_id' => $seccionId,
                'mensaje' => $e->getMessage(),
                'usuario_id' => Auth::id()
            ]);
            $this->mostrarError('Error al cargar los datos del producto');
        }
    }

    private function cargarDatosRecibido()
    {
        if ($this->recibido) {
            // Cargar distribuciones existentes con información del usuario
            $this->distribuciones = $this->recibido->distribucionesStock()
                ->with('usuario')
                ->orderBy('fecha_distribucion', 'desc')
                ->get()
                ->map(function($distribucion) {
                    return [
                        'cantidad_distribuida' => $distribucion->cantidad_distribuida,
                        'precio_unitario' => $distribucion->precio_unitario,
                        'fecha_distribucion' => (string) $distribucion->fecha_distribucion,
                        'comentario' => $distribucion->comentario,
                        'usuario_nombre' => $distribucion->usuario ? $distribucion->usuario->name : 'Usuario no encontrado',
                        'created_at' => $distribucion->created_at ? $distribucion->created_at->format('d/m/Y H:i') : 'Sin fecha',
                    ];
                })
                ->toArray();
            
            // Calcular totales
            $distribucionesCollection = $this->recibido->distribucionesStock;
            $this->totalDistribuido = $distribucionesCollection->sum('cantidad_distribuida') ?? 0;
            $this->stockDisponible = ($this->recibido->cantidad_inicial_seccion ?? 0) - $this->totalDistribuido;
            
            // Inicializar formulario
            $this->form = [
                'cantidad_asignada_bodega' => $this->recibido->cantidad_inicial_seccion ?? 0,
                'cantidad_distribuir' => 0,
                'precio_unitario' => 0,
                'fecha_distribucion' => now()->format('Y-m-d'),
                'comentario' => '',
            ];
        }
    }

    // ===== VALIDACIÓN PERSONALIZADA PARA DISTRIBUCIÓN =====

    public function updatedFormCantidadDistribuir()
    {
        $this->validarCantidadDistribuir();
    }

    private function validarCantidadDistribuir()
    {
        if ($this->form['cantidad_distribuir'] > $this->stockDisponible) {
            $this->mostrarErrorCampo('cantidad_distribuir', 
                'La cantidad a distribuir no puede ser mayor al stock disponible (' . $this->stockDisponible . ')');
        } else {
            $this->limpiarErrorCampo('cantidad_distribuir');
        }
    }

    public function guardar()
    {
        // Validar que la cantidad no exceda el stock disponible
        if ($this->form['cantidad_distribuir'] > $this->stockDisponible) {
            $this->mostrarErrorCampo('cantidad_distribuir', 
                'La cantidad a distribuir no puede ser mayor al stock disponible (' . $this->stockDisponible . ')');
            return;
        }

        $this->cerrarAlerta();

        try {
            $this->validate();

            // Crear nueva distribución
            $distribucion = DistribucionStock::create([
                'cantidad_distribuida' => $this->form['cantidad_distribuir'],
                'precio_unitario' => $this->form['precio_unitario'],
                'fecha_distribucion' => $this->form['fecha_distribucion'],
                'comentario' => $this->form['comentario'],
                'recibido_bodega_id' => $this->recibidoId,
                'users_id' => Auth::id(),
            ]);

            Log::info('Distribución de stock creada exitosamente', [
                'distribucion_id' => $distribucion->id,
                'recibido_id' => $this->recibidoId,
                'cantidad' => $this->form['cantidad_distribuir'],
                'precio_unitario' => $this->form['precio_unitario'],
                'precio_total' => $distribucion->precio_total
            ]);

            $this->mostrarExito('Distribución registrada exitosamente.');
            
            // Recargar datos
            $this->cargarDatosRecibido();
            
            // Limpiar formulario para nueva distribución
            $this->form['cantidad_distribuir'] = 0;
            $this->form['precio_unitario'] = 0;
            $this->form['comentario'] = '';

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->erroresValidacion = $e->errors();
            Log::warning('Errores de validación en distribución', [
                'errores' => $this->erroresValidacion,
                'datos' => $this->form
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear distribución de stock', [
                'recibido_id' => $this->recibidoId,
                'producto_id' => $this->producto->id,
                'mensaje' => $e->getMessage(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error al registrar la distribución de stock');
        }
    }

    public function volverAProductos()
    {
        if ($this->seccionId) {
            $this->dispatch('cambiarVista', ruta: 'Inventario.ProductosSeccion', parametros: [
                'seccionId' => $this->seccionId
            ]);
        } else {
            $this->dispatch('cambiarVista', ruta: 'Inventario.ProductoForm', parametros: [
                'productoId' => $this->producto->id
            ]);
        }
    }

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====

    public function updatedFormCantidadCompraLote()
    {
        if (empty($this->form['cantidad_compra_lote']) || $this->form['cantidad_compra_lote'] <= 0) {
            $this->mostrarErrorCampo('cantidad_compra_lote', 'La cantidad del lote debe ser mayor a 0');
        } else {
            $this->limpiarErrorCampo('cantidad_compra_lote');
            // Revalidar otros campos que dependen del lote
            $this->validarStockContraLote();
        }
    }

    public function updatedFormFechaRecibido()
    {
        if (empty($this->form['fecha_recibido'])) {
            $this->mostrarErrorCampo('fecha_recibido', 'La fecha de recibido es obligatoria');
        } else {
            $this->limpiarErrorCampo('fecha_recibido');
        }
    }

    // ===== MÉTODOS DE GESTIÓN DE ERRORES =====

    public function mostrarErrorCampo($campo, $mensaje)
    {
        $this->campoConError = $campo;
        $this->mensajeAlerta = $mensaje;
        $this->mostrarAlerta = true;
        $this->camposConError[] = $campo;
    }

    public function limpiarErrorCampo($campo)
    {
        if (($key = array_search($campo, $this->camposConError)) !== false) {
            unset($this->camposConError[$key]);
        }
        
        if ($this->campoConError === $campo) {
            $this->cerrarAlerta();
        }
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    public function getClaseCampo($campo)
    {
        return in_array($campo, $this->camposConError) ? 'is-invalid campo-obligatorio-vacio' : '';
    }

    // ===== PROPIEDADES COMPUTADAS =====

    public function getFormularioCompletoProperty()
    {
        return !empty($this->form['cantidad_distribuir']) && 
               $this->form['cantidad_distribuir'] > 0 &&
               !empty($this->form['precio_unitario']) && 
               $this->form['precio_unitario'] > 0 &&
               !empty($this->form['fecha_distribucion']) &&
               $this->form['cantidad_distribuir'] <= $this->stockDisponible;
    }

    // ===== MÉTODOS DE GESTIÓN DE MODALES =====

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
        $this->mensajeModalExito = '';
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    public function render()
    {
        return view('livewire.inventario.stock-form');
    }
}
