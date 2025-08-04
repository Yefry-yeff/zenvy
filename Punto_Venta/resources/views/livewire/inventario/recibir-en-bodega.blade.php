<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Recibir en Bodega --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

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
                <i class="fas fa-warehouse me-2"></i>Recibir en Bodega
            </h5>
            <button wire:click="limpiarFormulario"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-broom"></i> Limpiar
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4">
            
            <!-- Búsqueda de Producto -->
            <div class="mb-4">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <h3 class="mb-3 text-lg font-semibold text-gray-700">
                        <i class="fas fa-search me-2"></i>Buscar Producto
                    </h3>
                    
                    <div class="position-relative">
                        <label for="buscarProducto" class="form-label">Producto <span class="text-red-600">*</span></label>
                        <input type="text" 
                               id="buscarProducto"
                               class="form-control" 
                               wire:model.live="buscarProducto"
                               placeholder="Escriba el nombre, código de barra o código estatal del producto..."
                               autocomplete="off">
                        
                        <!-- Lista desplegable de productos -->
                        @if($mostrarSugerenciasProductos && count($productosSugeridos) > 0)
                            <div class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg" style="z-index: 1000; max-height: 250px; overflow-y: auto;">
                                @foreach($productosSugeridos as $producto)
                                    <div wire:click="seleccionarProducto({{ $producto->id }})" 
                                         class="px-3 py-2 cursor-pointer hover-bg-light border-bottom">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $producto->nombre }}</strong><br>
                                                <small class="text-muted">{{ $producto->descripcion }}</small><br>
                                                <small class="text-primary">
                                                    Marca: {{ $producto->marca ? $producto->marca->txt_descripcion : 'Sin marca' }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                @if($producto->codigo_barra)
                                                    <small class="text-success d-block">{{ $producto->codigo_barra }}</small>
                                                @endif
                                                <small class="text-info">
                                                    {{ $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->txt_descripcion : 'N/A' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($mostrarSugerenciasProductos && count($productosSugeridos) == 0 && strlen($buscarProducto) >= 2)
                            <div class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg" style="z-index: 1000;">
                                <div class="px-3 py-2 text-muted text-center">
                                    No se encontraron productos que coincidan con "{{ $buscarProducto }}"
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información del Producto Seleccionado -->
            @if($productoSeleccionado)
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-box me-2"></i>Información del Producto
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre</label>
                                    <div class="p-2 bg-light rounded">{{ $nombreProducto }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Descripción</label>
                                    <div class="p-2 bg-light rounded">{{ $descripcionProducto }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Código de Barra</label>
                                    <div class="p-2 bg-light rounded">{{ $codigoBarraProducto }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Marca</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-tags me-2 text-primary"></i>{{ $marcaProducto }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Unidad de Medida</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-balance-scale me-2 text-success"></i>{{ $unidadMedidaProducto }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos del Recibido -->
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-clipboard-list me-2"></i>Datos del Recibido
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cantidadCompraLote" class="form-label">Cantidad Compra Lote <span class="text-red-600">*</span></label>
                                    <input type="number" wire:model="cantidadCompraLote" id="cantidadCompraLote" class="form-control" 
                                           placeholder="Cantidad total del lote comprado" min="1">
                                </div>
                                <div class="mb-3">
                                    <label for="cantidadInicialSeccion" class="form-label">Cantidad Inicial en Sección <span class="text-red-600">*</span></label>
                                    <input type="number" wire:model="cantidadInicialSeccion" id="cantidadInicialSeccion" class="form-control" 
                                           placeholder="Cantidad a asignar a esta sección" min="1">
                                </div>
                                <div class="mb-3">
                                    <label for="unidadesCompra" class="form-label">Unidades de Compra</label>
                                    <input type="text" wire:model="unidadesCompra" id="unidadesCompra" class="form-control" 
                                           placeholder="Ej: Caja, Paquete, etc.">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fechaRecibido" class="form-label">Fecha de Recibido <span class="text-red-600">*</span></label>
                                    <input type="date" wire:model="fechaRecibido" id="fechaRecibido" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="fechaExpiracion" class="form-label">Fecha de Expiración</label>
                                    <input type="date" wire:model="fechaExpiracion" id="fechaExpiracion" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="comentario" class="form-label">Comentario</label>
                                    <textarea wire:model="comentario" id="comentario" class="form-control" rows="3" 
                                              placeholder="Comentarios adicionales sobre el recibido"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selección de Sección -->
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-map-marker-alt me-2"></i>Asignar a Sección
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <label for="seccionSeleccionada" class="form-label">Seleccionar Sección <span class="text-red-600">*</span></label>
                                <select wire:model="seccionSeleccionada" id="seccionSeleccionada" class="form-select">
                                    <option value="">Seleccione una sección...</option>
                                    @foreach($secciones as $seccion)
                                        <option value="{{ $seccion->id }}">
                                            {{ $seccion->descripcion }} 
                                            ({{ $seccion->segmento->descripcion ?? 'Sin segmento' }} - 
                                            {{ $seccion->segmento->bodega->nombre ?? 'Sin bodega' }} - 
                                            {{ $seccion->segmento->bodega->tienda->denominacion_social ?? 'Sin tienda' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button wire:click="confirmarRecibido" 
                                        class="btn w-100"
                                        :class="{
                                            'btn-success': theme === 'verde',
                                            'btn-primary': theme === 'azul',
                                            'btn-dark': theme === 'oscuro',
                                            'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                        }">
                                    <i class="fas fa-check me-2"></i>Confirmar Recibido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

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
                            <strong>Sección:</strong> {{ collect($secciones)->firstWhere('id', $seccionSeleccionada)->descripcion ?? 'N/A' }}<br>
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
                    <div class="modal-header bg-success text-white">
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
                    <div class="modal-header bg-danger text-white">
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
    </style>

</div>
