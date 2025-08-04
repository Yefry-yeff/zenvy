<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Gestión de Segmentos --}}
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
                Gestión de Segmentos
                @if($bodega)
                    - {{ $bodega->nombre }}
                @endif
            </h5>
            <div class="flex gap-2">
                <button wire:click="volverABodegas"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>←</span> Volver a Bodegas
                </button>
                <button wire:click="crearNuevoSegmento"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>➕</span> Nuevo Segmento
                </button>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">
            <!-- Barra de búsqueda -->
            <div class="mb-4 row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               placeholder="Buscar por descripción de segmento..."
                               wire:model.live="busqueda">
                    </div>
                </div>
            </div>

            @if($bodega)
                <div class="mb-4 alert alert-info">
                    <strong>📍 Bodega:</strong> {{ $bodega->nombre }}<br>
                    <strong>🏪 Tienda:</strong> {{ $bodega->tienda->denominacion_social ?? 'N/A' }}
                </div>
            @endif

            <!-- Tarjetas de segmentos -->
            <div class="row">
                @forelse($segmentos as $segmento)
                    <div class="mb-4 col-md-4" wire:key="segmento-{{ $segmento->id }}">
                        <div class="shadow-sm card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 card-title">
                                    <i class="fas fa-layer-group me-2"></i>{{ $segmento->descripcion }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <strong>Bodega:</strong> {{ $segmento->bodega->nombre ?? 'N/A' }}<br>
                                    <strong>Secciones:</strong> {{ $segmento->secciones->count() }}<br>
                                    <strong>Creado:</strong> {{ $segmento->created_at ? $segmento->created_at->format('d/m/Y') : 'N/A' }}
                                </p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <button wire:click="editarSegmento({{ $segmento->id }})"
                                            class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button wire:click="verSecciones({{ $segmento->id }})"
                                            class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-th-large"></i> Secciones
                                    </button>
                                    <button wire:click="eliminarSegmento({{ $segmento->id }})"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Está seguro de eliminar este segmento? También se eliminarán todas sus secciones.')">
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
                            @if($bodega)
                                No se encontraron segmentos para la bodega "{{ $bodega->nombre }}" con los criterios especificados.
                            @else
                                No se encontraron segmentos con los criterios de búsqueda especificados.
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Paginación -->
            @if($segmentos->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $segmentos->links() }}
                </div>
            @endif

        </div> {{-- Fin del card-body --}}
    </div> {{-- Fin del contenedor principal --}}
</div> {{-- Fin del elemento raíz --}}
