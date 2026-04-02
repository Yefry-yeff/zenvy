<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Recibir Producto de Compra Específica --}}
    <div class="overflow-visible border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))" style="position: relative; z-index: 1;">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">
                <i class="fas fa-warehouse me-2"></i>Recibir Productos - Factura: {{ $compra->numero_factura ?? 'N/A' }}
            </h5>
            <button wire:click="volver"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4" style="overflow: visible; position: relative; z-index: 2;">

            <!-- Información de la Compra -->
            @if($compra)
            <div class="mb-4">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="p-3 border-l-4 border-blue-500 rounded-lg bg-blue-50">
                                <h6 class="mb-1 text-sm font-medium text-blue-800">Información General</h6>
                                <p class="mb-1 text-sm"><strong>N° Factura:</strong> {{ $compra->numero_factura }}</p>
                                <p class="mb-1 text-sm"><strong>Proveedor:</strong> {{ $compra->proveedor->nombre ?? 'N/A' }}</p>
                                <p class="mb-1 text-sm">
                                    <strong>Creado por:</strong>
                                    {{ $compra->user ?? 'N/A' }}
                                </p>
                                <p class="mb-0 text-sm">
                                    <strong>Tipo:</strong>
                                    @if($compra->tipo_origen === 'TRASLADO')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-orange-800 bg-orange-100 rounded-full">
                                            <i class="fas fa-exchange-alt me-1"></i>TRASLADO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">
                                            <i class="fas fa-shopping-cart me-1"></i>COMPRA
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border-l-4 border-green-500 rounded-lg bg-green-50">
                                <h6 class="mb-1 text-sm font-medium text-green-800">Fechas</h6>
                                <p class="mb-1 text-sm"><strong>Emisión:</strong> {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</p>
                                <p class="mb-0 text-sm"><strong>Recepción:</strong> {{ \Carbon\Carbon::parse($compra->fecha_recepcion)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border-l-4 border-yellow-500 rounded-lg bg-yellow-50">
                                <h6 class="mb-1 text-sm font-medium text-yellow-800">Estado</h6>
                                <p class="mb-0 text-sm">
                                    @if($compra->estado && strtolower($compra->estado->nombre) === 'activo')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">{{ $compra->estado->nombre }}</span>
                                    @elseif($compra->estado && strtolower($compra->estado->nombre) === 'distribuido')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-yellow-800 bg-yellow-100 rounded-full">{{ $compra->estado->nombre }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">{{ $compra->estado->nombre ?? 'Sin Estado' }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border-l-4 border-purple-500 rounded-lg bg-purple-50">
                                <h6 class="mb-1 text-sm font-medium text-purple-800">Productos</h6>
                                <p class="mb-0 text-sm"><strong>Total:</strong> {{ count($detallesCompra) }} productos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Tabla de Productos de la Compra -->
            <div class="mb-4">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fas fa-boxes me-2"></i>Productos para Distribuir
                        </h3>
                        <div class="gap-3 d-flex align-items-center">
                            <small class="text-muted">Total: {{ count($detallesCompra) }} productos</small>
                            @if(collect($detallesCompra)->sum('cantidad_sin_asignar') > 0)
                                <button wire:click="abrirModalRecepcionMasiva"
                                        class="gap-2 btn btn-success btn-sm d-flex align-items-center">
                                    <i class="fas fa-download"></i>
                                    Recibir Todos
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Tabla responsive -->
                    <div class="table-responsive">
                        <table id="productosTabla" class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 12%;">Cód. Producto</th>
                                    <th style="width: 8%;">Unidad</th>
                                    <th style="width: 18%;">Nombre</th>
                                    <th style="width: 8%;" class="text-center">Precio</th>
                                    <th style="width: 8%;" class="text-center">Cantidad</th>
                                    <th style="width: 8%;" class="text-center">Asignados</th>
                                    <th style="width: 8%;" class="text-center">Sin Asignar</th>
                                    <th style="width: 8%;" class="text-end">Subtotal</th>
                                    <th style="width: 8%;" class="text-end">ISV</th>
                                    <th style="width: 8%;" class="text-end">Total</th>
                                    <th style="width: 8%;" class="text-center">F. Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detallesCompra as $detalle)
                                    <tr style="cursor: pointer;"
                                        wire:click="abrirModalDistribuir({{ $detalle['id'] }})"
                                        title="Clic para distribuir este producto"
                                        class="@if($detalle['cantidad_sin_asignar'] <= 0) table-secondary @endif">
                                        <td><strong>{{ $detalle['codigo_producto'] }}</strong></td>
                                        <td><span class="badge bg-info">{{ $detalle['unidad_medida'] }}</span></td>
                                        <td>
                                            <div>
                                                <strong>{{ $detalle['nombre_producto'] }}</strong><br>
                                                <small class="text-muted">{{ $detalle['marca'] }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">L. {{ number_format($detalle['precio_unitario'], 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $detalle['cantidad_comprada'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $asignados = $detalle['cantidad_comprada'] - $detalle['cantidad_sin_asignar'];
                                            @endphp
                                            @if($asignados > 0)
                                                <span class="badge bg-success">{{ $asignados }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($detalle['cantidad_sin_asignar'] > 0)
                                                <span class="badge bg-warning">{{ $detalle['cantidad_sin_asignar'] }}</span>
                                            @else
                                                <span class="badge bg-success">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">L. {{ number_format($detalle['subtotal'], 2) }}</td>
                                        <td class="text-end">L. {{ number_format($detalle['isv'], 2) }}</td>
                                        <td class="text-end"><strong>L. {{ number_format($detalle['total'], 2) }}</strong></td>
                                        <td class="text-center">
                                            @if($detalle['fecha_vencimiento'])
                                                {{ \Carbon\Carbon::parse($detalle['fecha_vencimiento'])->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="py-4 text-center text-muted">
                                            <div>
                                                <i class="fas fa-box-open" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">No se encontraron productos en esta compra</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Distribución a Bodega -->
    @if($mostrarModalDistribucion && $detalleSeleccionado)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="text-white modal-header bg-primary">
                        <h5 class="modal-title">
                            <i class="fas fa-warehouse me-2"></i>Distribuir a Bodega
                        </h5>
                        <button type="button" wire:click="cerrarModalDistribucion" class="btn-close btn-close-white"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Información del producto -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-box me-2"></i>Producto a Distribuir</h6>
                            <p class="mb-1"><strong>Producto:</strong> {{ $detalleSeleccionado['nombre_producto'] }}</p>
                            <p class="mb-1"><strong>Código:</strong> {{ $detalleSeleccionado['codigo_producto'] }}</p>
                            <p class="mb-1"><strong>Cantidad de la Compra:</strong> {{ $detalleSeleccionado['cantidad_sin_asignar'] }} {{ $detalleSeleccionado['unidad_medida'] }}</p>
                            <p class="mb-0"><strong>Marca:</strong> {{ $detalleSeleccionado['marca'] }}</p>
                        </div>

                        <!-- Formulario de distribución -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fechaDistribucion" class="form-label">Fecha de Distribución <span class="text-red-600">*</span></label>
                                    <input type="date"
                                           id="fechaDistribucion"
                                           class="form-control"
                                           wire:model.live="fechaDistribucion">
                                </div>
                            </div>
                        </div>

                        <!-- Unidad de Medida y Cantidad en Stock -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unidadMedidaProducto" class="form-label">Presentación del Producto <span class="text-red-600">*</span></label>
                                    <select id="unidadMedidaProducto"
                                            class="form-select"
                                            wire:model.live="unidadMedidaProducto">
                                        <option value="">Seleccionar presentación</option>
                                        @foreach($unidadesMedida as $unidad)
                                            <option value="{{ $unidad->precio_venta_id }}">
                                                {{ $unidad->nombre }} ({{ $unidad->simbolo }})@if(!empty($unidad->codigo_barra)) - Código: {{ $unidad->codigo_barra }}@endif@if(!empty($unidad->descripcion_precio)) - {{ $unidad->descripcion_precio }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Seleccione la presentación/código de barras</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cantidadAsignarStock" class="form-label">Cantidad en Stock (Unidades) <span class="text-red-600">*</span></label>
                                    <input type="number"
                                           id="cantidadAsignarStock"
                                           class="form-control"
                                           wire:model.live="cantidadAsignarStock"
                                           min="0.01"
                                           step="0.01"
                                           placeholder="Ej: 50, 30, etc.">
                                    <small class="text-muted">Cantidad de unidades para esta presentación</small>
                                </div>
                            </div>
                        </div>

                        <!-- Selección de ubicación (Bodega > Segmento > Sección) -->
                        <div class="mb-3">
                            <label for="bodegaDistribucion" class="form-label">Bodega <span class="text-red-600">*</span></label>
                            <select id="bodegaDistribucion" class="form-select" wire:model.live="bodegaDistribucion">
                                <option value="">Seleccionar bodega</option>
                                @if(isset($bodegas) && is_iterable($bodegas))
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No hay bodegas disponibles</option>
                                @endif
                            </select>
                            <div wire:loading wire:target="bodegaDistribucion" class="mt-1 text-muted small">
                                <i class="fas fa-spinner fa-spin me-1"></i>Cargando segmentos...
                            </div>
                        </div>

                        @if($bodegaDistribucion)
                            <div class="mb-3">
                                <label for="segmentoDistribucion" class="form-label">Segmento <span class="text-red-600">*</span></label>
                                <select id="segmentoDistribucion" class="form-select" wire:model.live="segmentoDistribucion"
                                        wire:loading.attr="disabled" wire:target="bodegaDistribucion">
                                    <option value="">Seleccionar segmento</option>
                                    @if(isset($segmentos) && is_iterable($segmentos) && count($segmentos) > 0)
                                        @foreach($segmentos as $segmento)
                                            <option value="{{ $segmento->id }}">{{ $segmento->descripcion }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if(isset($segmentos) && count($segmentos) === 0 && $bodegaDistribucion)
                                    <small class="text-warning">No hay segmentos disponibles para esta bodega</small>
                                @endif
                                <div wire:loading wire:target="segmentoDistribucion" class="mt-1 text-muted small">
                                    <i class="fas fa-spinner fa-spin me-1"></i>Cargando secciones...
                                </div>
                            </div>
                        @endif

                        @if($segmentoDistribucion)
                            <div class="mb-3">
                                <label for="seccionDistribucion" class="form-label">Sección <span class="text-red-600">*</span></label>
                                <select id="seccionDistribucion" class="form-select" wire:model.live="seccionDistribucion"
                                        wire:loading.attr="disabled" wire:target="segmentoDistribucion">
                                    <option value="">Seleccionar sección</option>
                                    @if(isset($secciones) && is_iterable($secciones) && count($secciones) > 0)
                                        @foreach($secciones as $seccion)
                                            <option value="{{ $seccion->id }}">{{ $seccion->descripcion }} ({{ $seccion->numeracion }})</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if(isset($secciones) && count($secciones) === 0 && $segmentoDistribucion)
                                    <small class="text-warning">No hay secciones disponibles para este segmento</small>
                                @endif
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="comentarioDistribucion" class="form-label">Comentario</label>
                            <textarea id="comentarioDistribucion"
                                      class="form-control"
                                      wire:model="comentarioDistribucion"
                                      rows="3"
                                      placeholder="Comentarios adicionales sobre la distribución"></textarea>
                        </div>
                        
                        <!-- Botón para agregar distribución -->
                        <div class="mb-3">
                            <button type="button" 
                                    class="btn btn-success w-100"
                                    wire:click="agregarDistribucion"
                                    @if(!$cantidadAsignarStock || !$unidadMedidaProducto || !$bodegaDistribucion || !$segmentoDistribucion || !$seccionDistribucion) disabled @endif>
                                <i class="fas fa-plus me-2"></i>Agregar Distribución
                            </button>
                        </div>
                        
                        <!-- Tabla de distribuciones agregadas -->
                        @if(count($distribucionesMultiples) > 0)
                            <div class="mb-3">
                                <h6><i class="fas fa-list me-2"></i>Distribuciones Agregadas ({{ count($distribucionesMultiples) }})</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Presentación</th>
                                                <th>Código</th>
                                                <th>Descripción</th>
                                                <th>Stock</th>
                                                <th>Ubicación</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($distribucionesMultiples as $index => $dist)
                                                <tr>
                                                    <td>{{ $dist['unidad_nombre'] }} ({{ $dist['unidad_simbolo'] }})</td>
                                                    <td>
                                                        @if(!empty($dist['codigo_barra']))
                                                            <span class="badge bg-secondary">{{ $dist['codigo_barra'] }}</span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($dist['descripcion']))
                                                            <small>{{ $dist['descripcion'] }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><strong>{{ $dist['cantidad_stock'] }}</strong> unidades</td>
                                                    <td>
                                                        <small>{{ $dist['bodega_nombre'] }} > {{ $dist['segmento_nombre'] }} > {{ $dist['seccion_nombre'] }}</small>
                                                    </td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                wire:click="eliminarDistribucion({{ $index }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if($bodegaDistribucion && $segmentoDistribucion && $seccionDistribucion && $unidadMedidaProducto && $cantidadAsignarStock)
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Listo para agregar</h6>
                                <p class="mb-1"><strong>Cantidad en Stock:</strong> {{ $cantidadAsignarStock }} unidades</p>
                                <p class="mb-1"><strong>Ubicación:</strong> {{ $nombreBodegaDistribucion }} > {{ $nombreSegmentoDistribucion }} > {{ $nombreSeccionDistribucion }}</p>
                                <p class="mb-0"><strong>Fecha:</strong> {{ $fechaDistribucion ? \Carbon\Carbon::parse($fechaDistribucion)->format('d/m/Y') : '' }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalDistribucion" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button"
                                wire:click="confirmarDistribucion"
                                class="btn btn-primary"
                                @if(!$this->puedeConfirmarDistribucion()) disabled @endif>
                            <i class="fas fa-check me-2"></i>Confirmar Distribuciones ({{ count($distribucionesMultiples) }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show d-block modal-exito" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1070;">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>
                            @if(str_contains($mensajeModalExito ?? '', 'masiva') || str_contains($mensajeModalExito ?? '', 'Masiva'))
                                ¡Recepción Masiva Exitosa!
                            @else
                                ¡Distribución Exitosa!
                            @endif
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>

                        <!-- Información detallada de la distribución -->
                        <div class="p-3 mb-3 rounded bg-light">
                            @php
                                $lineas = explode("\n", $mensajeModalExito);
                            @endphp

                            @foreach($lineas as $linea)
                                @if(trim($linea))
                                    <div class="mb-2">
                                        @if(str_contains($linea, '📦 Producto:'))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box text-primary me-2"></i>
                                                <strong>{{ str_replace('📦 Producto:', '', $linea) }}</strong>
                                            </div>
                                        @elseif(str_contains($linea, '🔢 Cantidad:'))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-sort-numeric-up text-info me-2"></i>
                                                <span>{{ str_replace('🔢 Cantidad:', 'Cantidad:', $linea) }}</span>
                                            </div>
                                        @elseif(str_contains($linea, '🏢 Bodega:'))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-warehouse text-warning me-2"></i>
                                                <span>{{ str_replace('🏢 Bodega:', 'Bodega:', $linea) }}</span>
                                            </div>
                                        @elseif(str_contains($linea, '📍 Ubicación:'))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                                <span>{{ str_replace('📍 Ubicación:', 'Ubicación:', $linea) }}</span>
                                            </div>
                                        @elseif(str_contains($linea, '🎉'))
                                            <div class="mt-3 mb-0 alert alert-success">
                                                <i class="fas fa-trophy me-2"></i>
                                                <strong>{{ str_replace('🎉 ', '', $linea) }}</strong>
                                            </div>
                                        @else
                                            <div>{{ $linea }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalExito" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Error -->
    @if($mostrarModalError)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalError" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Alerta flotante para validación de cantidad -->
    @if($cantidadDistribuir && $detalleSeleccionado && $cantidadDistribuir > $detalleSeleccionado['cantidad_sin_asignar'])
        <div class="alert-cantidad-excedida">
            <strong>⚠️ Cantidad Excedida</strong>
            <br><small>No puede distribuir más de {{ $detalleSeleccionado['cantidad_sin_asignar'] }} {{ $detalleSeleccionado['unidad_medida'] }}</small>
        </div>
    @endif

    <!-- Estilos CSS adicionales -->
    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .modal.show {
            display: block !important;
        }

        /* Alerta flotante para cantidad excedida */
        .alert-cantidad-excedida {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideInAlert 0.3s ease-out;
            max-width: 300px;
        }

        @keyframes slideInAlert {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Input con error */
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4M6 7v.01'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        /* Feedback de error */
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        /* Estados de carga */
        [wire\:loading] {
            color: #6c757d;
            font-style: italic;
        }

        [wire\:loading] .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Select deshabilitado durante carga */
        select[disabled] {
            background-color: #f8f9fa;
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Filas deshabilitadas */
        .table-secondary {
            opacity: 0.6;
        }

        /* Hover en filas */
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Filas clickeables */
        .table tbody tr[style*="cursor: pointer"]:hover {
            background-color: #e3f2fd !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Alerta flotante de error encima del modal */
        .position-fixed .alert {
            animation: slideDownAlert 0.3s ease-out;
        }

        @keyframes slideDownAlert {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Z-index para modales superpuestos */
        .modal.show {
            z-index: 1055 !important;
        }

        .modal.show.modal-exito {
            z-index: 1070 !important;
        }

        /* Tabla responsive */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            overflow-x: auto;
            min-height: 200px;
        }

        /* Asegurar que la tabla no se comprima demasiado */
        .table {
            margin-bottom: 0;
            width: 100%;
            table-layout: auto;
        }

        /* Tabla dentro del modal de recepción masiva - sin scroll horizontal */
        .modal-dialog-scrollable .table-responsive {
            overflow-x: visible;
        }

        .modal-dialog-scrollable .table {
            min-width: auto;
            font-size: 0.85rem;
        }

        .modal-dialog-scrollable .table th,
        .modal-dialog-scrollable .table td {
            padding: 0.5rem 0.25rem;
            word-wrap: break-word;
            white-space: normal;
        }

        .modal-dialog-scrollable .table select,
        .modal-dialog-scrollable .table input {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        /* Estados visuales */
        .badge {
            font-size: 0.75rem;
        }

        /* Botones de acción */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Mejoras para dispositivos móviles */
        @media (max-width: 768px) {
            .table {
                min-width: auto;
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }
        }
    </style>

    <!-- MODAL DE RECEPCIÓN MASIVA -->
    @if($mostrarModalRecepcionMasiva)
    <div class="modal fade show d-flex align-items-center justify-content-center" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 9999; display: flex !important; position: fixed; top: 50px; left: 0; right: 0; bottom: 0; overflow-y: auto;">
        <!-- Zona de mensajes de error encima del modal -->
        @if($mostrarModalError && !empty($mensajeModalError))
        <div class="position-fixed w-100 d-flex justify-content-center" style="top: 20px; z-index: 10000;">
            <div class="mx-3 border-0 shadow-lg alert alert-danger rounded-3" style="max-width: 600px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 text-danger" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 alert-heading">
                            <strong>Error en la Recepción Masiva</strong>
                        </h6>
                        <p class="mb-0">{{ $mensajeModalError }}</p>
                    </div>
                    <button type="button" class="btn-close" wire:click="cerrarModalError"></button>
                </div>
            </div>
        </div>
        @endif

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; max-height: 85vh;">
            <div class="modal-content" style="max-height: 85vh; overflow-y: auto;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-download me-2"></i>Recibir Todos los Productos
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModalRecepcionMasiva"></button>
                </div>
                <div class="p-3 modal-body">
                    <!-- Configuración general -->
                    <div class="mb-2 row">
                        <div class="col-md-6">
                            <label for="fechaRecepcionMasiva" class="mb-1 form-label">Fecha de Recepción <span class="text-danger">*</span></label>
                            <input type="date"
                                   id="fechaRecepcionMasiva"
                                   class="form-control form-control-sm"
                                   wire:model.live="fechaRecepcionMasiva">
                        </div>

                    </div>

                    <!-- Comentario general -->
                    <div class="mb-3 row">
                        <div class="col-12">
                            <label for="comentarioRecepcionMasiva" class="mb-1 form-label">Comentario General</label>
                            <textarea id="comentarioRecepcionMasiva"
                                      class="form-control form-control-sm"
                                      rows="2"
                                      wire:model.live="comentarioRecepcionMasiva"
                                      placeholder="Comentario opcional..."></textarea>
                        </div>
                    </div>

                    {{-- <!-- Validación de productos sin unidad de medida -->
                    @php
                        $productosSinUnidad = collect($productosRecepcionMasiva)
                            ->filter(fn($p) => empty($p['unidad_medida_id']))
                            ->values();
                    @endphp
                    @if($productosSinUnidad->isNotEmpty())
                        <div class="mb-3 border-4 alert alert-danger border-start">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-circle me-2" style="font-size: 1rem; flex-shrink: 0; margin-top: 2px;"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 alert-heading" style="font-size: 0.9rem;">
                                        <strong>Productos sin unidad de medida</strong>
                                    </h6>
                                    <p class="mb-2" style="font-size: 0.85rem;">Los siguientes productos no tienen unidad registrada en precio_has_venta</p>
                                    <ul class="mb-0" style="font-size: 0.8rem;">
                                        @foreach($productosSinUnidad as $prod)
                                            <li>
                                                <strong>{{ $prod['nombre_producto'] }}</strong>
                                                <br>
                                                <small class="text-muted">ID: {{ $prod['producto_id'] }} - Haz clic en el nombre para ir al producto</small>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif --}}

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 12%;">Producto</th>
                                    <th style="width: 6%;" class="text-center">Cant. Pendiente</th>
                                    <th style="width: 6%;" class="text-center">Cant. Distribuir</th>
                                    <th style="width: 10%;">Unidad Medida</th>
                                    <th style="width: 6%;" class="text-center">Cant. Stock</th>
                                    <th style="width: 10%;">Bodega</th>
                                    <th style="width: 10%;">Segmento</th>
                                    <th style="width: 10%;">Sección</th>
                                    <th style="width: 8%;" class="text-center">Fecha Exp.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productosRecepcionMasiva as $index => $producto)
                                <tr wire:key="producto-masivo-{{ $producto['id'] }}"
                                    @if(empty($producto['unidad_medida_id'])) class="table-danger" style="background-color: #f8d7da;" @endif></tr>
                                    <td>
                                        <button type="button" 
                                                class="p-0 btn btn-link btn-sm text-decoration-none text-start"
                                                wire:click="irAlProducto({{ $producto['producto_id'] }})"
                                                title="Clic para editar el producto">
                                            <strong class="text-primary">{{ $producto['nombre_producto'] }}</strong>
                                        </button>
                                        <br><small class="text-muted">ID: {{ $producto['producto_id'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            {{ $producto['cantidad_pendiente'] }}
                                        </span>
                                        <br><small class="text-muted">{{ $producto['unidad_medida_compra'] }}</small>
                                    </td>
                                    <!-- Cant. Distribuir -->
                                    <td>
                                        <input type="number"
                                               class="form-control form-control-sm text-center @if($producto['cantidad_distribuir'] > $producto['cantidad_pendiente']) is-invalid @endif"
                                               wire:model.live="productosRecepcionMasiva.{{ $index }}.cantidad_distribuir"
                                               min="1"
                                               max="{{ $producto['cantidad_pendiente'] }}"
                                               step="1"
                                               title="Máximo: {{ $producto['cantidad_pendiente'] }} {{ $producto['unidad_medida_compra'] }}"
                                               placeholder="{{ $producto['cantidad_pendiente'] }}">
                                        @if($producto['cantidad_distribuir'] > $producto['cantidad_pendiente'])
                                            <div class="invalid-feedback">
                                                <small>⚠️ No puede exceder {{ $producto['cantidad_pendiente'] }} {{ $producto['unidad_medida_compra'] }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm @if(empty($producto['unidad_medida_id'])) is-invalid @endif"
                                                wire:model.live="productosRecepcionMasiva.{{ $index }}.unidad_medida_id"
                                                wire:change="cambiarUnidadProducto({{ $index }}, $event.target.value)"
                                                required>
                                            <option value="">Seleccionar</option>
                                            @foreach($producto['unidades_disponibles'] as $unidad)
                                                <option value="{{ $unidad['id'] }}">
                                                    {{ $unidad['nombre'] }} ({{ $unidad['simbolo'] }})@if(!empty($unidad['codigo_barra'])) - Código: {{ $unidad['codigo_barra'] }}@endif@if(!empty($unidad['descripcion'])) - {{ $unidad['descripcion'] }}@endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(empty($producto['unidad_medida_id']))
                                            <div class="invalid-feedback">
                                                <small>⚠️ Unidad de medida requerida</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number"
                                               class="text-center form-control form-control-sm"
                                               wire:model.live="productosRecepcionMasiva.{{ $index }}.cantidad_stock"
                                               min="1"
                                               step="1">
                                    </td>
                                    <!-- Bodega -->
                                    <td>
                                        <select class="form-select form-select-sm"
                                                wire:model.live="productosRecepcionMasiva.{{ $index }}.bodega_id"
                                                wire:change="cambiarBodegaProducto({{ $index }}, $event.target.value)"
                                                x-init="
                                                    @php
                                                        $bodegaPaperland = collect($bodegas)->firstWhere('nombre', 'Paperland');
                                                        $bodegaPaperlandId = $bodegaPaperland ? $bodegaPaperland->id : 1;
                                                    @endphp
                                                    @if(empty($producto['bodega_id']))
                                                        $nextTick(() => {
                                                            $el.value = '{{ $bodegaPaperlandId }}';
                                                            $el.dispatchEvent(new Event('change'));
                                                            $wire.cambiarBodegaProducto({{ $index }}, '{{ $bodegaPaperlandId }}');
                                                        });
                                                    @endif
                                                ">
                                            <option value="">Seleccionar bodega</option>
                                            @foreach($bodegas as $bodega)
                                                @php
                                                    $esPaperland = strtolower($bodega->nombre) === 'paperland';
                                                    $debeSeleccionar = $producto['bodega_id'] == $bodega->id || ($esPaperland && empty($producto['bodega_id']));
                                                @endphp
                                                <option value="{{ $bodega->id }}"
                                                        @if($debeSeleccionar) selected @endif>
                                                    {{ $bodega->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <!-- Segmento -->
                                    <td>
                                        <select class="form-select form-select-sm"
                                                wire:model.live="productosRecepcionMasiva.{{ $index }}.segmento_id"
                                                wire:change="cambiarSegmentoProducto({{ $index }}, $event.target.value)"
                                                @if(empty($producto['bodega_id'])) disabled @endif>
                                            <option value="">Seleccionar segmento</option>
                                            @if(isset($producto['segmentos_disponibles']))
                                                @foreach($producto['segmentos_disponibles'] as $segmento)
                                                    <option value="{{ $segmento['id'] }}"
                                                            @if($producto['segmento_id'] == $segmento['id']) selected @endif>
                                                        {{ $segmento['descripcion'] }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    <!-- Sección -->
                                    <td>
                                        <select class="form-select form-select-sm"
                                                wire:model.live="productosRecepcionMasiva.{{ $index }}.seccion_id"
                                                wire:change="cambiarSeccionProducto({{ $index }}, $event.target.value)"
                                                @if(empty($producto['segmento_id'])) disabled @endif>
                                            <option value="">Seleccionar sección</option>
                                            @if(isset($producto['secciones_disponibles']))
                                                @foreach($producto['secciones_disponibles'] as $seccion)
                                                    <option value="{{ $seccion['id'] }}"
                                                            @if($producto['seccion_id'] == $seccion['id']) selected @endif>
                                                        {{ $seccion['descripcion'] }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        @if($producto['fecha_expiracion'])
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($producto['fecha_expiracion'])->format('d/m/Y') }}</small>
                                        @else
                                            <small class="text-muted">N/A</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="cerrarModalRecepcionMasiva">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    @php
                        $tieneProductosSinUnidad = collect($productosRecepcionMasiva)
                            ->contains(fn($p) => empty($p['unidad_medida_id']));
                    @endphp
                    {{-- @if($tieneProductosSinUnidad)
                        <div class="mb-0 alert alert-warning flex-grow-1 ms-2" style="font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small><strong>⚠️ Hay productos sin unidad de medida</strong></small>
                        </div>
                    @endif --}}
                    <button type="button" class="btn btn-sm btn-success" wire:click="confirmarRecepcionMasiva"
                            @if($tieneProductosSinUnidad) disabled @endif>
                        <i class="fas fa-check me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
