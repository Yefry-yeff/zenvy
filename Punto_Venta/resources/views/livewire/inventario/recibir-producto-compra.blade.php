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
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <h6 class="text-sm font-medium text-blue-800 mb-1">Información General</h6>
                                <p class="text-sm mb-1"><strong>N° Factura:</strong> {{ $compra->numero_factura }}</p>
                                <p class="text-sm mb-0"><strong>Proveedor:</strong> {{ $compra->proveedor->nombre ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
                                <h6 class="text-sm font-medium text-green-800 mb-1">Fechas</h6>
                                <p class="text-sm mb-1"><strong>Emisión:</strong> {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</p>
                                <p class="text-sm mb-0"><strong>Recepción:</strong> {{ \Carbon\Carbon::parse($compra->fecha_recepcion)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                                <h6 class="text-sm font-medium text-yellow-800 mb-1">Estado</h6>
                                <p class="text-sm mb-0">
                                    @if($compra->estado && strtolower($compra->estado->nombre) === 'activo')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">{{ $compra->estado->nombre }}</span>
                                    @elseif($compra->estado && strtolower($compra->estado->nombre) === 'distribuido')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">{{ $compra->estado->nombre }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">{{ $compra->estado->nombre ?? 'Sin Estado' }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-purple-50 rounded-lg border-l-4 border-purple-500">
                                <h6 class="text-sm font-medium text-purple-800 mb-1">Productos</h6>
                                <p class="text-sm mb-0"><strong>Total:</strong> {{ count($detallesCompra) }} productos</p>
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
                        <small class="text-muted">Total: {{ count($detallesCompra) }} productos</small>
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
                    <div class="modal-header bg-primary text-white">
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
                            <p class="mb-1"><strong>Cantidad Disponible:</strong> {{ $detalleSeleccionado['cantidad_sin_asignar'] }} {{ $detalleSeleccionado['unidad_medida'] }}</p>
                            <p class="mb-0"><strong>Marca:</strong> {{ $detalleSeleccionado['marca'] }}</p>
                        </div>

                        <!-- Formulario de distribución -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cantidadDistribuir" class="form-label">Cantidad a Distribuir <span class="text-red-600">*</span></label>
                                    <input type="number" 
                                           id="cantidadDistribuir" 
                                           class="form-control" 
                                           wire:model.live="cantidadDistribuir"
                                           min="1" 
                                           max="{{ $detalleSeleccionado['cantidad_sin_asignar'] }}"
                                           placeholder="Cantidad a distribuir">
                                    <small class="text-muted">Máximo: {{ $detalleSeleccionado['cantidad_sin_asignar'] }} {{ $detalleSeleccionado['unidad_medida'] }}</small>
                                </div>
                            </div>
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

                        <!-- Selección de ubicación (Bodega > Segmento > Sección) -->
                        <div class="mb-3">
                            <label for="bodegaDistribucion" class="form-label">Bodega <span class="text-red-600">*</span></label>
                            <select id="bodegaDistribucion" class="form-select" wire:model.live="bodegaDistribucion">
                                <option value="">Seleccionar bodega</option>
                                @if(isset($bodegas) && is_iterable($bodegas))
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        @if($bodegaDistribucion)
                            <div class="mb-3">
                                <label for="segmentoDistribucion" class="form-label">Segmento <span class="text-red-600">*</span></label>
                                <select id="segmentoDistribucion" class="form-select" wire:model.live="segmentoDistribucion">
                                    <option value="">Seleccionar segmento</option>
                                    @if(isset($segmentos) && is_iterable($segmentos))
                                        @foreach($segmentos as $segmento)
                                            <option value="{{ $segmento->id }}">{{ $segmento->descripcion }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        @endif

                        @if($segmentoDistribucion)
                            <div class="mb-3">
                                <label for="seccionDistribucion" class="form-label">Sección <span class="text-red-600">*</span></label>
                                <select id="seccionDistribucion" class="form-select" wire:model.live="seccionDistribucion">
                                    <option value="">Seleccionar sección</option>
                                    @if(isset($secciones) && is_iterable($secciones))
                                        @foreach($secciones as $seccion)
                                            <option value="{{ $seccion->id }}">{{ $seccion->descripcion }} ({{ $seccion->numeracion }})</option>
                                        @endforeach
                                    @endif
                                </select>
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

                        @if($bodegaDistribucion && $segmentoDistribucion && $seccionDistribucion)
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Resumen de Distribución</h6>
                                <p class="mb-1"><strong>Cantidad:</strong> {{ $cantidadDistribuir }} {{ $detalleSeleccionado['unidad_medida'] }}</p>
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
                            <i class="fas fa-check me-2"></i>Confirmar Distribución
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>¡Éxito!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
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

        /* Tabla responsive */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            overflow-x: auto;
            min-height: 200px;
        }

        /* Asegurar que la tabla no se comprima demasiado */
        .table {
            min-width: 1200px;
            margin-bottom: 0;
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
                min-width: 1000px;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
        }
    </style>

</div>
