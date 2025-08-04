<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Gestión de Secciones --}}
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
                Gestión de Secciones
                @if($segmento)
                    - {{ $segmento->descripcion }}
                    @if($segmento->bodega)
                        ({{ $segmento->bodega->nombre }})
                    @endif
                @endif
            </h5>
            <div class="flex gap-2">
                <button wire:click="volverASegmentos"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>←</span> Volver a Segmentos
                </button>
                <button wire:click="crearNuevaSeccion"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>➕</span> Nueva Sección
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
                               placeholder="Buscar por descripción de sección..."
                               wire:model.live="buscar">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
            </div>

            @if($segmento)
                <div class="mb-4 alert alert-info">
                    <div class="row">
                        <div class="col-md-8">
                            <strong>📦 Segmento:</strong> {{ $segmento->descripcion }}<br>
                            <strong>📍 Bodega:</strong> {{ $segmento->bodega->nombre ?? 'N/A' }}<br>
                            <strong>🏪 Tienda:</strong> {{ $segmento->bodega->tienda->denominacion_social ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 text-end">
                            <strong>📊 Resumen:</strong><br>
                            <span class="badge bg-primary fs-6">{{ $secciones->count() }} Secciones</span><br>
                            <span class="badge bg-success fs-6">{{ $secciones->sum('productos_count') }} Productos</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tarjetas de secciones -->
            <div class="row">
                @forelse($secciones as $seccion)
                    <div class="mb-4 col-md-4" wire:key="seccion-{{ $seccion->id }}">
                        <div class="shadow-sm card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 card-title">
                                    <i class="fas fa-th-large me-2"></i>{{ $seccion->descripcion }}
                                </h5>
                                <span class="badge {{ $seccion->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $seccion->estado_id == 1 ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <strong>Numeración:</strong> {{ $seccion->numeracion ?? 'N/A' }}<br>
                                    <strong>Segmento:</strong> {{ $seccion->segmento->descripcion ?? 'N/A' }}<br>
                                    <strong>Bodega:</strong> {{ $seccion->segmento->bodega->nombre ?? 'N/A' }}<br>
                                    <strong>📦 Productos:</strong> 
                                    <span class="badge {{ $seccion->productos_count > 0 ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $seccion->productos_count ?? 0 }}
                                    </span><br>
                                    <strong>Creado:</strong> {{ $seccion->created_at ? $seccion->created_at->format('d/m/Y') : 'N/A' }}
                                </p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <button wire:click="editarSeccion({{ $seccion->id }})"
                                            class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button wire:click="verProductos({{ $seccion->id }})"
                                            class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-box"></i> Productos
                                    </button>
                                    <button wire:click="eliminarSeccion({{ $seccion->id }})"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Está seguro de eliminar esta sección? También se eliminarán todos sus productos.')">
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
                            @if($segmento)
                                No se encontraron secciones para el segmento "{{ $segmento->descripcion }}" con los criterios especificados.
                            @else
                                No se encontraron secciones con los criterios de búsqueda especificados.
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Paginación -->
            @if($secciones->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $secciones->links() }}
                </div>
            @endif

        </div> {{-- Fin del card-body --}}
    </div> {{-- Fin del contenedor principal --}}
</div> {{-- Fin del elemento raíz --}}