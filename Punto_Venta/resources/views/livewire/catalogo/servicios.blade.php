<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" 
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-concierge-bell me-2"></i>
                Servicios
            </h4>
            <small class="opacity-75">Gestión de servicios disponibles</small>
        </div>
        <button wire:click="crearServicio" class="btn btn-light">
            <i class="fas fa-plus me-1"></i> Nuevo Servicio
        </button>
    </div>

    <!-- FILTROS Y BÚSQUEDA -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       placeholder="Buscar servicios por nombre o descripción..."
                       wire:model.live="busqueda">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="filtroEstado">
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="2">Inactivos</option>
            </select>
        </div>
        <div class="col-md-3">
            <button wire:click="limpiarFiltros" class="btn btn-outline-secondary w-100">
                <i class="fas fa-eraser me-1"></i> Limpiar Filtros
            </button>
        </div>
    </div>

    <!-- GRID DE SERVICIOS -->
    <div class="row">
        @forelse($servicios as $servicio)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm hover-card" 
                     style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                     wire:click="editarServicio({{ $servicio->id }})"
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                     onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                    
                    <!-- IMAGEN DEL SERVICIO -->
                    <div class="card-img-top d-flex align-items-center justify-content-center" 
                         style="height: 200px; background: linear-gradient(45deg, #f8f9fa, #e9ecef); border-radius: 0.375rem 0.375rem 0 0;">
                        @if($servicio->imagen)
                            <img src="data:image/*;base64,{{ base64_encode($servicio->imagen) }}" 
                                 alt="{{ $servicio->nombre }}" 
                                 class="img-fluid rounded-top"
                                 style="height: 100%; width: 100%; object-fit: cover;">
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-concierge-bell fa-3x mb-2" style="opacity: 0.3;"></i>
                                <div class="small">Sin imagen</div>
                            </div>
                        @endif
                    </div>

                    <!-- INFORMACIÓN DEL SERVICIO -->
                    <div class="card-body p-3">
                        <h6 class="card-title mb-2 fw-bold text-truncate" title="{{ $servicio->nombre }}">
                            {{ $servicio->nombre }}
                        </h6>
                        
                        @if($servicio->descripcion)
                            <p class="card-text text-muted small mb-2" 
                               style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $servicio->descripcion }}
                            </p>
                        @endif

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary">
                                    L. {{ number_format($servicio->precio_base, 2) }}
                                </span>
                                @if($servicio->descuento_unitario > 0)
                                    <div class="small text-success">
                                        <i class="fas fa-tag fa-sm"></i>
                                        {{ number_format($servicio->descuento_unitario, 2) }}% desc.
                                    </div>
                                @endif
                            </div>
                            <div class="text-end">
                                @if($servicio->isv)
                                    <div class="small text-muted">
                                        ISV: {{ $servicio->isv->cantidad }}%
                                    </div>
                                @endif
                                @if($servicio->estado)
                                    @if($servicio->estado_id == 1)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>{{ $servicio->estado->descripcion }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times-circle me-1"></i>{{ $servicio->estado->descripcion }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-warning">
                                        <i class="fas fa-question-circle me-1"></i>Sin Estado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- BADGES DE DESCUENTOS ESPECIALES -->
                    @if($servicio->descuento_tercera || $servicio->descuento_cuarta)
                        <div class="card-footer p-2 bg-light border-top-0">
                            <div class="d-flex gap-1 flex-wrap">
                                @if($servicio->descuento_tercera)
                                    <span class="badge bg-info small">
                                        <i class="fas fa-user-clock me-1"></i>3ra Edad
                                    </span>
                                @endif
                                @if($servicio->descuento_cuarta)
                                    <span class="badge bg-secondary small">
                                        <i class="fas fa-user-friends me-1"></i>4ta Edad
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-concierge-bell fa-4x text-muted" style="opacity: 0.3;"></i>
                    </div>
                    <h5 class="text-muted">No se encontraron servicios</h5>
                    <p class="text-muted mb-4">
                        @if($busqueda)
                            No hay servicios que coincidan con "{{ $busqueda }}"
                        @else
                            No tienes servicios registrados aún
                        @endif
                    </p>
                    <button wire:click="crearServicio" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Crear Primer Servicio
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- PAGINACIÓN -->
    @if($servicios->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $servicios->links() }}
        </div>
    @endif

    <!-- INFORMACIÓN ADICIONAL -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body p-3">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="fw-bold text-primary">{{ $servicios->total() }}</div>
                            <small class="text-muted">Servicios activos</small>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold text-success">
                                {{ $servicios->where('imagen', '!=', null)->count() }}
                            </div>
                            <small class="text-muted">Con imagen</small>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold text-info">
                                {{ $servicios->where('descuento_unitario', '>', 0)->count() }}
                            </div>
                            <small class="text-muted">Con descuento</small>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold text-secondary">
                                L. {{ number_format($servicios->avg('precio_base'), 2) }}
                            </div>
                            <small class="text-muted">Precio promedio</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTAS -->
    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('mensaje') }}
            <button type="button" class="btn-close" @click="show = false"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" @click="show = false"></button>
        </div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
