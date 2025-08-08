<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Gestión de Bodegas --}}
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
            <h5 class="mb-0 text-lg">Gestión de Bodegas</h5>
            <button wire:click="crearNuevaBodega"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Nueva Bodega
            </button>
        </div>

        <!-- MENSAJES FLASH -->
        <div class="px-4">
            @if(session('message'))
                <div class="alert alert-secondary alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>{{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">
            <!-- Barra de búsqueda y filtros -->
            <div class="mb-4 row">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               placeholder="Buscar por nombre de bodega..."
                               wire:model.live="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroTienda">
                        <option value="">Todas las tiendas</option>
                        @foreach($tiendas as $tienda)
                            <option value="{{ $tienda->id }}">{{ $tienda->denominacion_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos los tipos</option>
                        <option value="1">Principal</option>
                        <option value="0">Secundaria</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="2">Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button wire:click="limpiarFiltros" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-broom me-1"></i>Limpiar
                    </button>
                </div>
            </div>

        <!-- Tarjetas de bodegas -->
        <div class="row">
            @forelse($bodegas as $bodega)
                <div class="mb-4 col-md-4" wire:key="bodega-{{ $bodega->id }}">
                    <div class="shadow-sm card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 card-title">
                                <i class="fas fa-warehouse me-2"></i>{{ $bodega->nombre }}
                                @if($bodega->principal == 1)
                                    <span class="badge bg-primary ms-2">
                                        <i class="fas fa-star me-1"></i>Principal
                                    </span>
                                @endif
                            </h5>
                            <span class="badge {{ $bodega->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                {{ $bodega->estado_id == 1 ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Tipo:</strong> 
                                @if($bodega->principal == 1)
                                    <span class="text-primary">🏢 Bodega Principal</span>
                                @else
                                    <span class="text-secondary">🏪 Bodega Secundaria</span>
                                @endif<br>
                                <strong>Dirección:</strong> {{ $bodega->direccion->domicilio_tributario ?? 'N/A' }}<br>
                                <strong>Tienda asignada:</strong> 
                                @if($bodega->tienda)
                                    {{ $bodega->tienda->denominacion_social }}
                                @else
                                    <span class="text-muted">Sin tienda asignada</span>
                                @endif<br>
                                <strong>Segmentos:</strong> {{ $bodega->segmentos->count() }}<br>
                                <strong>Secciones totales:</strong> {{ $bodega->segmentos->sum(fn($s) => $s->secciones->count()) }}
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <button wire:click="editarBodega({{ $bodega->id }})"
                                        class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button wire:click="verSegmentos({{ $bodega->id }})"
                                        class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-layer-group"></i> Segmentos
                                </button>
                                @if($bodega->estado_id == 1)
                                    <button wire:click="inactivarBodega({{ $bodega->id }})"
                                            class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-pause"></i> Inactivar
                                    </button>
                                @else
                                    <button wire:click="activarBodega({{ $bodega->id }})"
                                            class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-play"></i> Activar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No se encontraron bodegas con los criterios de búsqueda especificados.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($bodegas->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $bodegas->links() }}
            </div>
        @endif

        </div> {{-- Fin del card-body --}}
    </div> {{-- Fin del contenedor principal --}}

    <!-- Modal Confirmar Inactivación -->
    <div wire:key="modal-confirmar-inactivar">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($mostrarModalInactivar) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cancelarInactivar()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title">¿Inactivar Bodega?</h5>
                    </div>
                    <div class="modal-body">
                        @if($bodegaAInactivar)
                            <div class="mb-3">
                                <h6 class="text-secondary">🔸 Esta acción puede revertirse posteriormente</h6>
                                <p>¿Está seguro que desea inactivar la bodega <strong>"{{ $bodegaAInactivar->nombre }}"</strong>?</p>
                                
                                <div class="alert alert-secondary">
                                    <small><strong>Se inactivarán automáticamente:</strong></small>
                                    <ul class="mb-0 small">
                                        <li>• Todas las secciones asociadas a los segmentos</li>
                                        <li>• La bodega quedará inactiva (no se eliminará)</li>
                                    </ul>
                                    <div class="mt-2">
                                        <small class="text-muted"><em>Nota: Los segmentos se mantienen para posible reactivación futura.</em></small>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" wire:click="cancelarInactivar">Cancelar</button>
                            <button type="button" class="btn btn-secondary" wire:click="confirmarInactivacion">Sí, Inactivar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Activación -->
    <div wire:key="modal-confirmar-activar">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($mostrarModalActivar) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cancelarActivar()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">¿Activar Bodega?</h5>
                    </div>
                    <div class="modal-body">
                        @if($bodegaAActivar)
                            <div class="mb-3">
                                <h6 class="text-success">✅ Esta acción reactivará la bodega</h6>
                                <p>¿Está seguro que desea activar la bodega <strong>"{{ $bodegaAActivar->nombre }}"</strong>?</p>
                                
                                <div class="alert alert-success">
                                    <small><strong>Se activarán automáticamente:</strong></small>
                                    <ul class="mb-0 small">
                                        <li>• Todas las secciones asociadas a los segmentos</li>
                                        <li>• La bodega volverá a estar disponible</li>
                                    </ul>
                                    <div class="mt-2">
                                        <small class="text-muted"><em>Nota: La bodega y sus secciones estarán operativas nuevamente.</em></small>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" wire:click="cancelarActivar">Cancelar</button>
                            <button type="button" class="btn btn-success" wire:click="confirmarActivacion">Sí, Activar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> {{-- Fin del elemento raíz --}}
