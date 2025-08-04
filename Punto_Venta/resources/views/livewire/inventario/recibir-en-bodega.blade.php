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
                <i class="fas fa-warehouse me-2"></i>Recibir en Bodega
            </h5>
            <button wire:click="limpiarFormulario"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-broom"></i> Limpiar
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4" style="overflow: visible; position: relative; z-index: 2;">
            
            <!-- Búsqueda de Producto -->
            <div class="mb-4" style="position: relative; z-index: 10;">
                <div class="p-4 bg-white border shadow rounded-xl" style="overflow: visible;">
                    <h3 class="mb-3 text-lg font-semibold text-gray-700">
                        <i class="fas fa-search me-2"></i>Buscar Producto
                    </h3>
                    
                    <div class="position-relative" style="z-index: 20;">
                        <label for="buscarProducto" class="form-label">Producto <span class="text-red-600">*</span></label>
                        <input type="text" 
                               id="buscarProducto"
                               class="form-control" 
                               wire:model.live="buscarProducto"
                               placeholder="Escriba el nombre, código de barra o código estatal del producto..."
                               autocomplete="off">
                        
                        <!-- Lista desplegable de productos -->
                        @if($mostrarSugerenciasProductos && count($productosSugeridos) > 0)
                            <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                @foreach($productosSugeridos as $producto)
                                    <div wire:click="seleccionarProducto({{ $producto->id }})" 
                                         class="dropdown-item-custom px-3 py-2 cursor-pointer border-bottom">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $producto->nombre }}</strong><br>
                                                <small class="text-muted">{{ $producto->descripcion }}</small><br>
                                                <small class="text-primary">
                                                    Marca: {{ $producto->marca ? $producto->marca->nombre : 'Sin marca' }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                @if($producto->codigo_barra)
                                                    <small class="text-success d-block">{{ $producto->codigo_barra }}</small>
                                                @endif
                                                <small class="text-info">
                                                    {{ $producto->unidadMedidaCompra ? $producto->unidadMedidaCompra->nombre : 'N/A' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($mostrarSugerenciasProductos && count($productosSugeridos) == 0 && strlen($buscarProducto) >= 2)
                            <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
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

                <!-- Selección de Sección (Jerarquía: Bodega → Segmento → Sección) -->
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-map-marker-alt me-2"></i>Asignar a Sección
                        </h3>
                        
                        <!-- Selección de Bodega -->
                        <div class="mb-3" style="position: relative; z-index: 9;">
                            <label for="buscarBodega" class="form-label">Bodega <span class="text-red-600">*</span></label>
                            <div class="position-relative" style="z-index: 19;">
                                <input type="text" 
                                       id="buscarBodega"
                                       class="form-control" 
                                       wire:model.live="buscarBodega"
                                       wire:focus="enfocarBodega"
                                       wire:click="enfocarBodega"
                                       placeholder="Escriba el nombre de la bodega..."
                                       autocomplete="off">
                                
                                <!-- Lista de bodegas -->
                                @if($mostrarSugerenciasBodegas && count($bodegasSugeridas) > 0)
                                    <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                        @foreach($bodegasSugeridas as $bodega)
                                            <div wire:click="seleccionarBodega({{ $bodega->id }})" 
                                                 class="dropdown-item-custom px-3 py-2 cursor-pointer border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $bodega->nombre }}</strong><br>
                                                        <small class="text-muted">{{ $bodega->tienda->denominacion_social ?? 'Sin tienda' }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-primary">
                                                            <i class="fas fa-warehouse"></i>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                @if($mostrarSugerenciasBodegas && count($bodegasSugeridas) == 0 && strlen($buscarBodega) >= 1)
                                    <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                        <div class="px-3 py-2 text-muted text-center">
                                            No se encontraron bodegas que coincidan con "{{ $buscarBodega }}"
                                        </div>
                                    </div>
                                @endif

                                @if($mostrarSugerenciasBodegas && count($bodegasSugeridas) == 0 && strlen($buscarBodega) == 0)
                                    <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                        <div class="px-3 py-2 text-muted text-center">
                                            Haga clic para ver todas las bodegas disponibles
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Selección de Segmento -->
                        @if($bodegaSeleccionada)
                            <div class="mb-3" style="position: relative; z-index: 8;">
                                <label for="buscarSegmento" class="form-label">Segmento <span class="text-red-600">*</span></label>
                                <div class="position-relative" style="z-index: 18;">
                                    <input type="text" 
                                           id="buscarSegmento"
                                           class="form-control" 
                                           wire:model.live="buscarSegmento"
                                           wire:focus="enfocarSegmento"
                                           wire:click="enfocarSegmento"
                                           placeholder="Escriba el nombre del segmento..."
                                           autocomplete="off">
                                    
                                    <!-- Lista de segmentos -->
                                    @if($mostrarSugerenciasSegmentos && count($segmentosSugeridos) > 0)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            @foreach($segmentosSugeridos as $segmento)
                                                <div wire:click="seleccionarSegmento({{ $segmento->id }})" 
                                                     class="dropdown-item-custom px-3 py-2 cursor-pointer border-bottom">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>{{ $segmento->descripcion }}</strong>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-success">
                                                                <i class="fas fa-layer-group"></i>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if($mostrarSugerenciasSegmentos && count($segmentosSugeridos) == 0 && strlen($buscarSegmento) >= 1)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            <div class="px-3 py-2 text-muted text-center">
                                                No se encontraron segmentos que coincidan con "{{ $buscarSegmento }}"
                                            </div>
                                        </div>
                                    @endif

                                    @if($mostrarSugerenciasSegmentos && count($segmentosSugeridos) == 0 && strlen($buscarSegmento) == 0)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            <div class="px-3 py-2 text-muted text-center">
                                                Haga clic para ver todos los segmentos disponibles
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Selección de Sección -->
                        @if($segmentoSeleccionado)
                            <div class="mb-3" style="position: relative; z-index: 7;">
                                <label for="buscarSeccion" class="form-label">Sección <span class="text-red-600">*</span></label>
                                <div class="position-relative" style="z-index: 17;">
                                    <input type="text" 
                                           id="buscarSeccion"
                                           class="form-control" 
                                           wire:model.live="buscarSeccion"
                                           wire:focus="enfocarSeccion"
                                           wire:click="enfocarSeccion"
                                           placeholder="Escriba el nombre de la sección..."
                                           autocomplete="off">
                                    
                                    <!-- Lista de secciones -->
                                    @if($mostrarSugerenciasSecciones && count($seccionesSugeridas) > 0)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            @foreach($seccionesSugeridas as $seccion)
                                                <div wire:click="seleccionarSeccion({{ $seccion->id }})" 
                                                     class="dropdown-item-custom px-3 py-2 cursor-pointer border-bottom">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>{{ $seccion->descripcion }}</strong><br>
                                                            <small class="text-muted">Numeración: {{ $seccion->numeracion }}</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-info">
                                                                <i class="fas fa-cube"></i>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if($mostrarSugerenciasSecciones && count($seccionesSugeridas) == 0 && strlen($buscarSeccion) >= 1)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            <div class="px-3 py-2 text-muted text-center">
                                                No se encontraron secciones que coincidan con "{{ $buscarSeccion }}"
                                            </div>
                                        </div>
                                    @endif

                                    @if($mostrarSugerenciasSecciones && count($seccionesSugeridas) == 0 && strlen($buscarSeccion) == 0)
                                        <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                            <div class="px-3 py-2 text-muted text-center">
                                                Haga clic para ver todas las secciones disponibles
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Resumen de selección y botón confirmar -->
                        @if($seccionSeleccionada)
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="mb-2 text-success"><i class="fas fa-check-circle me-2"></i>Ubicación Seleccionada:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary"><i class="fas fa-warehouse me-1"></i>{{ $nombreBodega }}</span>
                                    <span class="badge bg-success"><i class="fas fa-layer-group me-1"></i>{{ $nombreSegmento }}</span>
                                    <span class="badge bg-info"><i class="fas fa-cube me-1"></i>{{ $nombreSeccion }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-3 d-flex justify-content-end">
                                <button wire:click="confirmarRecibido" 
                                        class="btn"
                                        :class="{
                                            'btn-success': theme === 'verde',
                                            'btn-primary': theme === 'azul',
                                            'btn-dark': theme === 'oscuro',
                                            'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                        }">
                                    <i class="fas fa-check me-2"></i>Confirmar Recibido
                                </button>
                            </div>
                        @endif
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
