<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\RecibidoBodega;
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

    // Formulario de stock
    public $form = [
        'cantidad_compra_lote' => 0,
        'cantidad_inicial_seccion' => 0,
        'cantidad_disponible' => 0,
        'fecha_recibido' => '',
        'fecha_expiracion' => '',
        'comentario' => '',
        'unidades_compra' => '',
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
        'form.cantidad_compra_lote' => 'required|integer|min:1',
        'form.cantidad_inicial_seccion' => 'required|integer|min:0',
        'form.cantidad_disponible' => 'required|integer|min:0',
        'form.fecha_recibido' => 'required|date',
        'form.fecha_expiracion' => 'nullable|date|after:fecha_recibido',
        'form.comentario' => 'nullable|string|max:150',
        'form.unidades_compra' => 'nullable|string|max:45',
    ];

    protected $messages = [
        'form.cantidad_compra_lote.required' => 'La cantidad de compra del lote es obligatoria',
        'form.cantidad_compra_lote.min' => 'La cantidad de compra del lote debe ser mayor a 0',
        'form.cantidad_inicial_seccion.required' => 'La cantidad inicial en sección es obligatoria',
        'form.cantidad_inicial_seccion.min' => 'La cantidad inicial en sección no puede ser negativa',
        'form.cantidad_disponible.required' => 'La cantidad disponible es obligatoria',
        'form.cantidad_disponible.min' => 'La cantidad disponible no puede ser negativa',
        'form.fecha_recibido.required' => 'La fecha de recibido es obligatoria',
        'form.fecha_expiracion.after' => 'La fecha de expiración debe ser posterior a la fecha de recibido',
        'form.comentario.max' => 'El comentario no puede exceder 150 caracteres',
        'form.unidades_compra.max' => 'Las unidades de compra no pueden exceder 45 caracteres',
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
            $this->form = [
                'cantidad_compra_lote' => $this->recibido->cantidad_compra_lote ?? 0,
                'cantidad_inicial_seccion' => $this->recibido->cantidad_inicial_seccion ?? 0,
                'cantidad_disponible' => $this->recibido->cantidad_disponible ?? 0,
                'fecha_recibido' => $this->recibido->fecha_recibido ? $this->recibido->fecha_recibido->format('Y-m-d') : '',
                'fecha_expiracion' => $this->recibido->fecha_expiracion ? $this->recibido->fecha_expiracion->format('Y-m-d') : '',
                'comentario' => $this->recibido->comentario ?? '',
                'unidades_compra' => $this->recibido->unidades_compra ?? '',
            ];
        }
    }

    // ===== VALIDACIÓN PERSONALIZADA PARA STOCK =====

    public function updatedFormCantidadInicialSeccion()
    {
        $this->validarStockContraLote();
    }

    public function updatedFormCantidadDisponible()
    {
        $this->validarStockDisponible();
    }

    private function validarStockContraLote()
    {
        if ($this->form['cantidad_inicial_seccion'] > $this->form['cantidad_compra_lote']) {
            $this->mostrarErrorCampo('cantidad_inicial_seccion', 
                'El stock en sección no puede ser mayor a la cantidad del lote de compra (' . $this->form['cantidad_compra_lote'] . ')');
        } else {
            $this->limpiarErrorCampo('cantidad_inicial_seccion');
        }
    }

    private function validarStockDisponible()
    {
        if ($this->form['cantidad_disponible'] > $this->form['cantidad_inicial_seccion']) {
            $this->mostrarErrorCampo('cantidad_disponible', 
                'El stock disponible no puede ser mayor al stock inicial en sección (' . $this->form['cantidad_inicial_seccion'] . ')');
        } else {
            $this->limpiarErrorCampo('cantidad_disponible');
        }
    }

    public function guardar()
    {
        // Validar que el stock no exceda el lote de compra
        if ($this->form['cantidad_inicial_seccion'] > $this->form['cantidad_compra_lote']) {
            $this->mostrarErrorCampo('cantidad_inicial_seccion', 
                'El stock en sección no puede ser mayor a la cantidad del lote de compra (' . $this->form['cantidad_compra_lote'] . ')');
            return;
        }

        // Validar que el stock disponible no exceda el inicial
        if ($this->form['cantidad_disponible'] > $this->form['cantidad_inicial_seccion']) {
            $this->mostrarErrorCampo('cantidad_disponible', 
                'El stock disponible no puede ser mayor al stock inicial en sección (' . $this->form['cantidad_inicial_seccion'] . ')');
            return;
        }

        $this->cerrarAlerta();

        try {
            $this->validate();

            $datos = $this->form;
            $datos['users_registro_id'] = Auth::id();

            if ($this->isEditing) {
                $this->recibido->update($datos);
                Log::info('Stock actualizado exitosamente', [
                    'recibido_id' => $this->recibidoId,
                    'producto_id' => $this->producto->id,
                    'datos' => $datos
                ]);
                $this->mostrarExito('Stock actualizado exitosamente.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->erroresValidacion = $e->errors();
            Log::warning('Errores de validación en stock', [
                'errores' => $this->erroresValidacion,
                'datos' => $this->form
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar stock', [
                'recibido_id' => $this->recibidoId,
                'producto_id' => $this->producto->id,
                'mensaje' => $e->getMessage(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error al guardar el stock del producto');
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
        return !empty($this->form['cantidad_compra_lote']) && 
               $this->form['cantidad_compra_lote'] > 0 &&
               isset($this->form['cantidad_inicial_seccion']) && 
               $this->form['cantidad_inicial_seccion'] >= 0 &&
               isset($this->form['cantidad_disponible']) && 
               $this->form['cantidad_disponible'] >= 0 &&
               !empty($this->form['fecha_recibido']) &&
               $this->form['cantidad_inicial_seccion'] <= $this->form['cantidad_compra_lote'] &&
               $this->form['cantidad_disponible'] <= $this->form['cantidad_inicial_seccion'];
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
