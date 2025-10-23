<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\RecibidoBodega;
use App\Models\DistribucionStock;
use App\Models\Producto as ProductoModel;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\Bitacora;
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
    public $cantidadTotalBodega = 0;

    // Propiedades para el modal de secciones
    public $modalSeccionesAbierto = false;
    public $seccionesProducto = [];

    // Propiedades para selección de destino
    public $bodegas = [];
    public $segmentosDestino = [];
    public $seccionesDestino = [];

    // Formulario de distribución
    public $form = [
        'cantidad_asignada_bodega' => 0,
        'cantidad_distribuir' => 0,
        'precio_unitario' => 0,
        'fecha_distribucion' => '',
        'comentario' => '',
        'bodega_destino' => '',
        'segmento_destino' => '',
        'seccion_destino' => '',
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
        'form.fecha_distribucion' => 'required|date',
        'form.comentario' => 'nullable|string|max:400',
        'form.bodega_destino' => 'required|exists:bodega,id',
        'form.segmento_destino' => 'required|exists:segmento,id',
        'form.seccion_destino' => 'required|exists:seccion,id',
    ];

    protected $messages = [
        'form.cantidad_distribuir.required' => 'La cantidad a distribuir es obligatoria',
        'form.cantidad_distribuir.min' => 'La cantidad a distribuir debe ser mayor a 0',
        'form.fecha_distribucion.required' => 'La fecha de distribución es obligatoria',
        'form.comentario.max' => 'El comentario no puede exceder 400 caracteres',
        'form.bodega_destino.required' => 'Debe seleccionar una bodega destino',
        'form.bodega_destino.exists' => 'La bodega seleccionada no es válida',
        'form.segmento_destino.required' => 'Debe seleccionar un segmento destino',
        'form.segmento_destino.exists' => 'El segmento seleccionado no es válido',
        'form.seccion_destino.required' => 'Debe seleccionar una sección destino',
        'form.seccion_destino.exists' => 'La sección seleccionada no es válida',
    ];

    public function mount($productoId = null, $seccionId = null, $recibidoId = null)
    {
        try {
            Log::info('StockForm mount iniciado', [
                'producto_id_recibido' => $productoId,
                'seccion_id_recibida' => $seccionId,
                'recibido_id_recibido' => $recibidoId,
                'usuario_id' => Auth::id()
            ]);

            // Cargar bodegas disponibles
            $this->cargarBodegas();

            // CASO 1: Se especifica recibidoId directamente (nuevo método)
            if ($recibidoId) {
                Log::info('Cargando por recibido_id específico', ['recibido_id' => $recibidoId]);
                
                $this->recibido = RecibidoBodega::with(['producto.marca', 'producto.subcategoria.categoria', 'producto.unidadMedidaCompra', 'seccion.segmento.bodega.tienda'])
                    ->findOrFail($recibidoId);
                
                $this->producto = $this->recibido->producto;
                $this->seccionId = $this->recibido->seccion_id;
                $this->recibidoId = $this->recibido->id;
                $this->isEditing = true;
                
                Log::info('Datos cargados por recibido_id', [
                    'recibido_id' => $this->recibido->id,
                    'producto_id' => $this->producto->id,
                    'producto_nombre' => $this->producto->nombre,
                    'cantidad_inicial' => $this->recibido->cantidad_inicial_seccion,
                    'cantidad_disponible' => $this->recibido->cantidad_disponible,
                    'seccion_id' => $this->seccionId
                ]);

                // Cargar datos del recibido
                $this->cargarDatosRecibido();

                // Pre-seleccionar la bodega, segmento y sección actual
                $this->form['bodega_destino'] = $this->recibido->seccion->segmento->bodega_id ?? '';
                if ($this->form['bodega_destino']) {
                    $this->cargarSegmentosPorBodega();
                    $this->form['segmento_destino'] = $this->recibido->seccion->segmento_id ?? '';
                    if ($this->form['segmento_destino']) {
                        $this->cargarSeccionesPorSegmento();
                    }
                }
                
                // Inicializar fecha de distribución con la fecha actual
                $this->form['fecha_distribucion'] = now()->format('Y-m-d');
                
                return; // Salir temprano del método
            }
            
            // CASO 2: Método anterior por productoId (mantener compatibilidad)
            if (!$productoId) {
                throw new \Exception('Debe especificar productoId o recibidoId');
            }

            // Cargar datos del producto
            $this->producto = ProductoModel::with(['marca', 'subcategoria.categoria', 'unidadMedidaCompra'])
                ->findOrFail($productoId);

            // Si se pasa seccionId, estamos editando desde ProductosSeccion
            if ($seccionId) {
                $this->seccionId = $seccionId;
                // Buscar el registro de RecibidoBodega para este producto en esta sección
                $this->recibido = RecibidoBodega::where('producto_id', $productoId)
                    ->where('seccion_id', $seccionId)
                    ->with(['producto', 'seccion.segmento.bodega.tienda'])
                    ->first(); // Cambiado de firstOrFail() a first()

                if ($this->recibido) {
                    $this->recibidoId = $this->recibido->id;
                    $this->isEditing = true;
                    $this->cargarDatosRecibido();

                    // Pre-seleccionar la bodega actual
                    $this->form['bodega_destino'] = $this->recibido->seccion->segmento->bodega_id ?? '';
                    if ($this->form['bodega_destino']) {
                        $this->cargarSegmentosPorBodega();
                        $this->form['segmento_destino'] = $this->recibido->seccion->segmento_id ?? '';
                        if ($this->form['segmento_destino']) {
                            $this->cargarSeccionesPorSegmento();
                        }
                    }
                } else {
                    // Si no se encuentra el registro, crear valores por defecto
                    $this->cantidadTotalBodega = 0;
                    $this->stockDisponible = 0;
                    $this->totalDistribuido = 0;
                    Log::warning('No se encontró registro RecibidoBodega', [
                        'producto_id' => $productoId,
                        'seccion_id' => $seccionId,
                        'usuario_id' => Auth::id()
                    ]);
                }
            } else {
                // Si no hay sección, necesitamos calcular para una bodega por defecto
                // Por ahora inicializamos en 0 hasta que se seleccione una sección
                $this->cantidadTotalBodega = 0;
                $this->form['cantidad_asignada_bodega'] = 0;
            }

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
                        'traslado_a' => $distribucion->traslado_a ?? 'N/A',
                        'unidad_medida' => $distribucion->Unidad_medida ?? 'N/A',
                        'estado' => $distribucion->estado ?? 'N/A',
                    ];
                })
                ->toArray();

            // Calcular totales - solo contar envíos para evitar duplicar, incluir registros históricos sin estado
            $distribucionesCollection = $this->recibido->distribucionesStock()->where(function($query) {
                $query->where('estado', 'enviado')
                      ->orWhereNull('estado')
                      ->orWhere('estado', '');
            })->get();
            $this->totalDistribuido = $distribucionesCollection->sum('cantidad_distribuida') ?? 0;
            $this->stockDisponible = $this->recibido->cantidad_disponible ?? 0;

            // Calcular cantidad total en toda la bodega para este producto
            $this->calcularCantidadTotalBodega();

            // Inicializar formulario con valores correctos
            $this->form = [
                'cantidad_asignada_bodega' => $this->cantidadTotalBodega,
                'cantidad_distribuir' => 0,
                'precio_unitario' => $this->producto->precio_base ?? 0,
                'fecha_distribucion' => now()->format('Y-m-d'),
                'comentario' => '',
                'bodega_destino' => '',
                'segmento_destino' => '',
                'seccion_destino' => '',
            ];

            Log::info('Datos cargados en StockForm', [
                'cantidad_total_bodega' => $this->cantidadTotalBodega,
                'stock_disponible' => $this->stockDisponible,
                'total_distribuido' => $this->totalDistribuido,
                'cantidad_inicial_seccion' => $this->recibido->cantidad_inicial_seccion ?? 0,
                'cantidad_disponible_seccion' => $this->recibido->cantidad_disponible ?? 0,
            ]);
        }
    }

    private function calcularCantidadTotalBodega()
    {
        if ($this->recibido && $this->producto) {
            // Obtener el ID de la bodega a través de la sección
            $bodegaId = $this->recibido->seccion->segmento->bodega_id ?? null;

            if ($bodegaId) {
                // Sumar todas las cantidades del producto en todas las secciones de esta bodega
                $this->cantidadTotalBodega = RecibidoBodega::whereHas('seccion.segmento', function($query) use ($bodegaId) {
                    $query->where('bodega_id', $bodegaId);
                })
                ->where('producto_id', $this->producto->id)
                ->sum('cantidad_compra_lote');
            } else {
                $this->cantidadTotalBodega = 0;
            }
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

            // Verificar si ya existe un registro para este producto en la sección destino
            $recibidoDestino = RecibidoBodega::where('producto_id', $this->producto->id)
                ->where('seccion_id', $this->form['seccion_destino'])
                ->first();

            if (!$recibidoDestino) {
                // Crear nuevo registro en la sección destino
                $recibidoDestino = RecibidoBodega::create([
                    'producto_id' => $this->producto->id,
                    'seccion_id' => $this->form['seccion_destino'],
                    'cantidad_compra_lote' => 0,
                    'cantidad_inicial_seccion' => 0,
                    'cantidad_disponible' => 0,
                    'fecha_recibido' => now(),
                    'users_registro_id' => Auth::id(),
                    'estado_id' => 1,
                    'unidades_compra' => '0',
                    'unidad_medida_id' => $this->producto->unidad_medida_venta_id ?? $this->recibido->unidad_medida_id ?? 1
                ]);
            }

            // Obtener información de origen y destino para traslado_a
            $seccionOrigen = $this->recibido->seccion;
            $seccionDestino = \App\Models\Seccion::with(['segmento.bodega'])->find($this->form['seccion_destino']);

            $trasladoDesdeOrigen = $seccionDestino ?
                $seccionDestino->segmento->bodega->nombre . ' > ' .
                $seccionDestino->segmento->descripcion . ' > ' .
                $seccionDestino->descripcion : 'N/A';

            $trasladoDesdeDestino = $seccionOrigen ?
                $seccionOrigen->segmento->bodega->nombre . ' > ' .
                $seccionOrigen->segmento->descripcion . ' > ' .
                $seccionOrigen->descripcion : 'N/A';

            // 1. Crear registro de ENVÍO en la sección origen (estado = enviado)
            $distribucionEnviado = DistribucionStock::create([
                'cantidad_distribuida' => $this->form['cantidad_distribuir'],
                'precio_unitario' => $this->producto->precio_base ?? 0,
                'Unidad_medida' => $this->producto->unidadMedidaVenta->nombre ?? 'N/A',
                'fecha_distribucion' => $this->form['fecha_distribucion'],
                'comentario' => $this->form['comentario'],
                'recibido_bodega_id' => $this->recibido->id, // Sección origen
                'users_id' => Auth::id(),
                'traslado_a' => $trasladoDesdeOrigen,
                'estado' => 'enviado',
            ]);

            // 2. Crear registro de RECEPCIÓN en la sección destino (estado = recibido)
            $distribucionRecibido = DistribucionStock::create([
                'cantidad_distribuida' => $this->form['cantidad_distribuir'],
                'precio_unitario' => $this->producto->precio_base ?? 0,
                'Unidad_medida' => $this->producto->unidadMedidaVenta->nombre ?? 'N/A',
                'fecha_distribucion' => $this->form['fecha_distribucion'],
                'comentario' => $this->form['comentario'],
                'recibido_bodega_id' => $recibidoDestino->id, // Sección destino
                'users_id' => Auth::id(),
                'traslado_a' => $trasladoDesdeDestino,
                'estado' => 'recibido',
            ]);

            // Capturar datos anteriores para auditoría
            $recibidoOrigenAnterior = $this->recibido ? $this->recibido->toArray() : null;
            $recibidoDestinoAnterior = $recibidoDestino->toArray();

            // Actualizar cantidades en el RecibidoBodega origen (restar)
            if ($this->recibido) {
                $this->recibido->cantidad_disponible = $this->recibido->cantidad_disponible - $this->form['cantidad_distribuir'];
                $this->recibido->save();
            }

            // Actualizar cantidades en el RecibidoBodega destino (sumar)
            $recibidoDestino->cantidad_inicial_seccion += $this->form['cantidad_distribuir'];
            $recibidoDestino->cantidad_disponible += $this->form['cantidad_distribuir'];
            $recibidoDestino->save();

            // Registrar en bitácora: Envío de stock (sección origen)
            Bitacora::registrar(
                Auth::id(),
                'Inventario - Stock',
                'Distribuir stock - Envío',
                "Stock enviado: {$this->form['cantidad_distribuir']} unidades de '{$this->producto->nombre}' desde {$trasladoDesdeDestino} hacia {$trasladoDesdeOrigen}",
                $distribucionEnviado->id,
                'distribucion_stock',
                $recibidoOrigenAnterior,
                [
                    'cantidad_distribuida' => $this->form['cantidad_distribuir'],
                    'precio_unitario' => $this->producto->precio_base ?? 0,
                    'traslado_a' => $trasladoDesdeOrigen,
                    'estado' => 'enviado',
                    'comentario' => $this->form['comentario']
                ]
            );

            // Registrar en bitácora: Recepción de stock (sección destino)
            Bitacora::registrar(
                Auth::id(),
                'Inventario - Stock',
                'Distribuir stock - Recepción',
                "Stock recibido: {$this->form['cantidad_distribuir']} unidades de '{$this->producto->nombre}' en {$trasladoDesdeOrigen} desde {$trasladoDesdeDestino}",
                $distribucionRecibido->id,
                'distribucion_stock',
                $recibidoDestinoAnterior,
                [
                    'cantidad_distribuida' => $this->form['cantidad_distribuir'],
                    'precio_unitario' => $this->producto->precio_base ?? 0,
                    'traslado_a' => $trasladoDesdeDestino,
                    'estado' => 'recibido',
                    'comentario' => $this->form['comentario']
                ]
            );

            Log::info('Distribución de stock creada exitosamente', [
                'distribucion_enviado_id' => $distribucionEnviado->id,
                'distribucion_recibido_id' => $distribucionRecibido->id,
                'recibido_origen_id' => $this->recibidoId,
                'recibido_destino_id' => $recibidoDestino->id,
                'seccion_destino_id' => $this->form['seccion_destino'],
                'cantidad' => $this->form['cantidad_distribuir'],
                'precio_unitario' => $this->producto->precio_base ?? 0,
                'precio_total' => ($this->form['cantidad_distribuir'] * ($this->producto->precio_base ?? 0))
            ]);

            $this->mostrarExito('Distribución registrada exitosamente a la sección destino.');

            // Recargar datos (incluye recalcular cantidadTotalBodega)
            $this->cargarDatosRecibido();

            // Limpiar formulario para nueva distribución
            $this->form['cantidad_distribuir'] = 0;
            $this->form['precio_unitario'] = 0;
            $this->form['comentario'] = '';
            $this->form['bodega_destino'] = '';
            $this->form['segmento_destino'] = '';
            $this->form['seccion_destino'] = '';
            $this->segmentosDestino = [];
            $this->seccionesDestino = [];

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

    // ===== MÉTODOS PARA MODAL DE SECCIONES =====

    public function mostrarModalSecciones()
    {
        Log::info('Método mostrarModalSecciones llamado', [
            'recibido_existe' => !is_null($this->recibido),
            'producto_existe' => !is_null($this->producto),
            'recibido_id' => $this->recibidoId
        ]);

        $this->cargarSeccionesProducto();
        $this->modalSeccionesAbierto = true;

        Log::info('Modal configurado para mostrar', [
            'mostrar_modal' => $this->modalSeccionesAbierto,
            'cantidad_secciones' => count($this->seccionesProducto)
        ]);
    }

    public function cerrarModalSecciones()
    {
        $this->modalSeccionesAbierto = false;
        $this->seccionesProducto = [];
    }

    private function cargarSeccionesProducto()
    {
        if ($this->recibido && $this->producto) {
            // Obtener el ID de la bodega a través de la sección
            $bodegaId = $this->recibido->seccion->segmento->bodega_id ?? null;

            Log::info('Cargando secciones del producto', [
                'bodega_id' => $bodegaId,
                'producto_id' => $this->producto->id
            ]);

            if ($bodegaId) {
                // Obtener todas las secciones donde está el producto en esta bodega
                $registros = RecibidoBodega::whereHas('seccion.segmento', function($query) use ($bodegaId) {
                    $query->where('bodega_id', $bodegaId);
                })
                ->where('producto_id', $this->producto->id)
                ->with(['seccion.segmento.bodega'])
                ->get();

                Log::info('Registros encontrados', [
                    'cantidad_registros' => $registros->count()
                ]);

                $this->seccionesProducto = $registros->map(function($registro) {
                    return [
                        'id' => $registro->id,
                        'nombre_seccion' => $registro->seccion->descripcion ?? 'Sin nombre',
                        'cantidad_inicial' => $registro->cantidad_inicial_seccion ?? 0,
                        'cantidad_disponible' => $registro->cantidad_disponible ?? 0,
                        'fecha_recibido' => $registro->fecha_recibido ?? 'Sin fecha',
                        'seccion_actual' => $registro->id == $this->recibidoId
                    ];
                })->toArray();
            } else {
                $this->seccionesProducto = [];
            }
        } else {
            Log::warning('No se pueden cargar secciones', [
                'recibido_existe' => !is_null($this->recibido),
                'producto_existe' => !is_null($this->producto)
            ]);
            $this->seccionesProducto = [];
        }
    }

    // ===== MÉTODOS PARA CARGA DE UBICACIONES =====

    private function cargarBodegas()
    {
        $this->bodegas = Bodega::where('estado_id', 1) // Solo bodegas activas
            ->orderBy('nombre')
            ->get();
    }

    public function cargarSegmentosPorBodega()
    {
        if ($this->form['bodega_destino']) {
            $this->segmentosDestino = Segmento::where('bodega_id', $this->form['bodega_destino'])
                ->orderBy('descripcion')
                ->get();
        } else {
            $this->segmentosDestino = [];
        }

        // Limpiar selecciones dependientes
        $this->form['segmento_destino'] = '';
        $this->form['seccion_destino'] = '';
        $this->seccionesDestino = [];
    }

    public function cargarSeccionesPorSegmento()
    {
        if ($this->form['segmento_destino']) {
            $this->seccionesDestino = Seccion::where('segmento_id', $this->form['segmento_destino'])
                ->where('estado_id', 1) // Solo secciones activas
                ->orderBy('numeracion')
                ->get();
        } else {
            $this->seccionesDestino = [];
        }

        // Limpiar selección dependiente
        $this->form['seccion_destino'] = '';
    }

    public function render()
    {
        return view('livewire.inventario.stock-form');
    }
}
