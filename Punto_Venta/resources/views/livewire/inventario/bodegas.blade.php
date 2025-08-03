<div>
    <div class="container-fluid">
        <!-- Header con título y botón de agregar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">🏭 Gestión de Bodegas</h2>
            <button wire:click="crearNuevaBodega" 
                    class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Bodega
            </button>
        </div>

        <!-- Barra de búsqueda y filtros -->
        <div class="row mb-4">
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
                        <option value="{{ $tienda->id }}">{{ $tienda->nombre }}</option>
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
                <div class="col-md-4 mb-4" wire:key="bodega-{{ $bodega->id }}">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-warehouse me-2"></i>{{ $bodega->nombre }}
                            </h5>
                            <span class="badge {{ $bodega->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                {{ $bodega->estado_id == 1 ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Tienda:</strong> {{ $bodega->tienda->nombre ?? 'N/A' }}<br>
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
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('¿Está seguro de eliminar esta bodega?')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No se encontraron bodegas con los criterios de búsqueda especificados.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($bodegas->hasPages())
            <div class="d-flex justify-content-center">
                {{ $bodegas->links() }}
            </div>
        @endif
    </div>
</div>
