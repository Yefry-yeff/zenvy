<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Recibir en Bodega --}}
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
                <i class="fas fa-boxes me-2"></i>Distribución de Compras a Bodega
            </h5>
            <button wire:click="limpiarFiltros"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-filter"></i> Limpiar Filtros
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4" style="overflow: visible; position: relative; z-index: 2;">

            <!-- Tabla de Compras Activas por Producto -->
            <div class="mb-4">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fas fa-boxes me-2"></i>Compras Activas por Producto
                        </h3>
                        <small class="text-muted">Total: {{ is_countable($comprasActivas) ? count($comprasActivas) : 0 }} productos</small>
                    </div>

                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       wire:model.live="filtroProducto"
                                       placeholder="Buscar por producto, marca o número de factura...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="filtroProveedor">
                                <option value="">Todos los proveedores</option>
                                @if(isset($proveedores) && is_iterable($proveedores))
                                    @foreach($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="filtroEstadoDistribucion">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente distribución</option>
                                <option value="parcial">Parcialmente distribuido</option>
                                <option value="completo">Completamente distribuido</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla responsive -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Producto</th>
                                    <th style="width: 12%;">N° Factura</th>
                                    <th style="width: 15%;">Proveedor</th>
                                    <th style="width: 10%;">Fecha Compra</th>
                                    <th style="width: 8%;" class="text-center">Cant. Comprada</th>
                                    <th style="width: 8%;" class="text-center">Cant. Distribuida</th>
                                    <th style="width: 8%;" class="text-center">Pendiente</th>
                                    <th style="width: 10%;" class="text-center">Estado</th>
                                    <th style="width: 9%;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($comprasActivas ?? [] as $compra)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $compra['producto_nombre'] }}</strong><br>
                                                <small class="text-muted">{{ $compra['marca'] }}</small><br>
                                                @if($compra['codigo_barra'])
                                                    <small class="text-info">{{ $compra['codigo_barra'] }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td><strong>{{ $compra['numero_factura'] }}</strong></td>
                                        <td>{{ $compra['proveedor_nombre'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($compra['fecha_compra'])->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $compra['cantidad_comprada'] }}</span>
                                            <br><small class="text-muted">{{ $compra['unidad'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $compra['cantidad_distribuida'] }}</span>
                                            <br><small class="text-muted">{{ $compra['unidad'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">{{ $compra['cantidad_pendiente'] }}</span>
                                            <br><small class="text-muted">{{ $compra['unidad'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($compra['cantidad_pendiente'] == 0)
                                                <span class="badge bg-success">Distribuido</span>
                                            @elseif($compra['cantidad_distribuida'] > 0)
                                                <span class="badge bg-warning">Parcial</span>
                                            @else
                                                <span class="badge bg-danger">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($compra['cantidad_pendiente'] > 0)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary"
                                                        wire:click="abrirModalDistribuir({{ $compra['compra_id'] }}, {{ $compra['producto_id'] }})"
                                                        title="Distribuir a bodega">
                                                    <i class="fas fa-warehouse"></i> Distribuir
                                                </button>
                                            @else
                                                <span class="text-muted small">Completo</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-4 text-center text-muted">
                                            <div>
                                                <i class="fas fa-box-open" style="font-size: 2rem;"></i>
                                                <p class="mt-2 mb-0">No se encontraron compras activas pendientes de distribución</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Información de resultados -->
                    @if(isset($comprasActivas) && is_countable($comprasActivas))
                        <div class="mt-3 text-muted">
                            <small>Mostrando {{ count($comprasActivas) }} producto(s) pendiente(s) de distribución</small>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Distribución a Bodega -->
    @if($mostrarModalDistribucion)
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
                        @if($productoParaDistribuir)
                            <!-- Información del producto -->
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="fas fa-box me-2"></i>Producto a Distribuir</h6>
                                        <p class="mb-1"><strong>Producto:</strong> {{ $productoParaDistribuir['nombre'] }}</p>
                                        <p class="mb-1"><strong>Factura:</strong> {{ $productoParaDistribuir['numero_factura'] }}</p>
                                        <p class="mb-1"><strong>Cantidad Pendiente:</strong> {{ $productoParaDistribuir['cantidad_pendiente'] }} {{ $productoParaDistribuir['unidad'] }}</p>
                                        <p class="mb-0"><strong>Proveedor:</strong> {{ $productoParaDistribuir['proveedor'] }}</p>
                                    </div>
                                    <button type="button" 
                                            wire:click="actualizarDatosProducto" 
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Actualizar datos del producto">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
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
                                               max="{{ $productoParaDistribuir['cantidad_pendiente'] }}"
                                               placeholder="Cantidad a distribuir">
                                        <small class="text-muted">Máximo: {{ $productoParaDistribuir['cantidad_pendiente'] }} {{ $productoParaDistribuir['unidad'] }}</small>
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
                                    <p class="mb-1"><strong>Cantidad:</strong> {{ $cantidadDistribuir }} {{ $productoParaDistribuir['unidad'] }}</p>
                                    <p class="mb-1"><strong>Ubicación:</strong> {{ $nombreBodegaDistribucion }} > {{ $nombreSegmentoDistribucion }} > {{ $nombreSeccionDistribucion }}</p>
                                    <p class="mb-0"><strong>Fecha:</strong> {{ $fechaDistribucion ? \Carbon\Carbon::parse($fechaDistribucion)->format('d/m/Y') : '' }}</p>
                                </div>
                            @endif
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

    <!-- Modal de Confirmación -->
    @if($mostrarModalConfirmacion)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Recibido en Bodega
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de que desea registrar este recibido en bodega?</p>
                        <div class="alert alert-info">
                            <strong>Producto:</strong> {{ $nombreProducto }}<br>
                            <strong>Cantidad Lote:</strong> {{ $cantidadCompraLote }}<br>
                            <strong>Cantidad en Sección:</strong> {{ $cantidadInicialSeccion }}<br>
                            <strong>Bodega:</strong> {{ $nombreBodega }}<br>
                            <strong>Segmento:</strong> {{ $nombreSegmento }}<br>
                            <strong>Sección:</strong> {{ $nombreSeccion }}<br>
                            <strong>Fecha:</strong> {{ $fechaRecibido }}
                        </div>
                        <p class="text-muted small">Esta acción registrará el producto en el inventario de la sección seleccionada.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancelarRecibido" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" wire:click="ejecutarRecibido" class="btn btn-warning">
                            <i class="fas fa-check me-2"></i>Confirmar Recibido
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

        /* Estilos para el dropdown de productos */
        .dropdown-suggestions {
            z-index: 9999 !important;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #dee2e6 !important;
        }

        .dropdown-item-custom {
            transition: background-color 0.2s ease;
        }

        .dropdown-item-custom:hover {
            background-color: #f8f9fa !important;
            cursor: pointer;
        }

        .dropdown-item-custom:last-child {
            border-bottom: none !important;
        }

        /* Asegurar que el contenedor padre no corte el dropdown */
        .position-relative {
            overflow: visible !important;
        }

        /* Mejorar la visualización en diferentes tamaños de pantalla */
        @media (max-width: 768px) {
            .dropdown-suggestions {
                max-height: 250px;
            }
        }
    </style>

</div>
