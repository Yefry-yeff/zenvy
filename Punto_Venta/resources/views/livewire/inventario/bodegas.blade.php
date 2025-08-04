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

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">
            <!-- Barra de búsqueda y filtros -->
            <div class="mb-4 row">
                <div class="col-md-6">
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
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filtroTienda">
                    <option value="">Todas las tiendas</option>
                    @foreach($tiendas as $tienda)
                        <option value="{{ $tienda->id }}">{{ $tienda->denominacion_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="1">Activas</option>
                    <option value="0">Inactivas</option>
                </select>
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
                            </h5>
                            <span class="badge {{ $bodega->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                {{ $bodega->estado_id == 1 ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
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
                                <button wire:click="eliminarBodega({{ $bodega->id }})"
                                        class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
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

    <!-- Modal Confirmar Eliminación -->
    <div wire:key="modal-confirmar-eliminar">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($mostrarModalEliminar) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cancelarEliminar()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">¿Eliminar Bodega?</h5>
                    </div>
                    <div class="modal-body">
                        @if($bodegaAEliminar)
                            <div class="mb-3">
                                <h6 class="text-danger">⚠️ Esta acción es irreversible</h6>
                                <p>¿Está seguro que desea eliminar la bodega <strong>"{{ $bodegaAEliminar->nombre }}"</strong>?</p>
                                
                                <div class="alert alert-warning">
                                    <small><strong>Se eliminarán automáticamente:</strong></small>
                                    <ul class="mb-0 small">
                                        <li>• Todas las secciones asociadas a los segmentos</li>
                                        <li>• Todos los segmentos de esta bodega</li>
                                        <li>• La bodega completa y toda su información</li>
                                    </ul>
                                </div>
                            </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" wire:click="cancelarEliminar">Cancelar</button>
                            <button type="button" class="btn btn-danger" wire:click="confirmarEliminacion">Sí, Eliminar Todo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> {{-- Fin del elemento raíz --}}
