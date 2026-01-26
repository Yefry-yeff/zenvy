<div class="container-fluid px-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-history text-info"></i> Bitácora del Sistema
            </h1>
            <p class="text-muted">Registro de todas las acciones realizadas en el sistema</p>
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total de Registros
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($estadisticas['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Módulo más Activo
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                @if($estadisticas['porModulo']->count() > 0)
                                    {{ $estadisticas['porModulo']->first()->modulo }}
                                    <small class="text-muted">({{ $estadisticas['porModulo']->first()->total }})</small>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cube fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Acción más Frecuente
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                @if($estadisticas['porAccion']->count() > 0)
                                    {{ $estadisticas['porAccion']->first()->accion }}
                                    <small class="text-muted">({{ $estadisticas['porAccion']->first()->total }})</small>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bolt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-info">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-filter"></i> Filtros de Búsqueda
                </h6>
                <button wire:click="limpiarFiltros" class="btn btn-light btn-sm">
                    <i class="fas fa-eraser"></i> Limpiar Filtros
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Usuario -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Usuario</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroUsuario"
                           class="form-control form-control-sm"
                           placeholder="Buscar usuario...">
                </div>

                <!-- Módulo -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Módulo</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroModulo"
                           class="form-control form-control-sm"
                           placeholder="Ej: Inventario, Ventas...">
                </div>

                <!-- Acción -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Acción</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroAccion"
                           class="form-control form-control-sm"
                           placeholder="Ej: Crear, Actualizar...">
                </div>

                <!-- IP -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">IP del Equipo</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroIp"
                           class="form-control form-control-sm"
                           placeholder="Ej: 192.168.1.1">
                </div>
            </div>

            <div class="row">
                <!-- Fecha Inicio -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha (Desde)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaInicio"
                           class="form-control form-control-sm">
                </div>

                <!-- Fecha Fin -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha (Hasta)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaFin"
                           class="form-control form-control-sm">
                </div>

                <!-- Descripción -->
                <div class="col-md-6 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Descripción</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroDescripcion"
                           class="form-control form-control-sm"
                           placeholder="Buscar en descripción...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-info">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-table"></i> Registros de Bitácora
                </h6>
                <div>
                    <span class="badge badge-light">{{ $registros->total() }} registros encontrados</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($registros->count() > 0)
                <div class="table-responsive" style="max-height: calc(100vh - 500px); overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm text-xs">
                        <thead class="bg-info text-white sticky-top">
                            <tr>
                                <th style="width: 60px;" class="cursor-pointer" wire:click="ordenar('id')">
                                    ID
                                    @if($ordenarPor === 'id')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th style="width: 150px;" class="cursor-pointer" wire:click="ordenar('created_at')">
                                    Fecha/Hora
                                    @if($ordenarPor === 'created_at')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th style="width: 120px;">Usuario</th>
                                <th style="width: 100px;" class="cursor-pointer" wire:click="ordenar('modulo')">
                                    Módulo
                                    @if($ordenarPor === 'modulo')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th style="width: 100px;" class="cursor-pointer" wire:click="ordenar('accion')">
                                    Acción
                                    @if($ordenarPor === 'accion')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>Descripción</th>
                                <th style="width: 120px;">IP Equipo</th>
                                <th style="width: 80px;" class="text-center">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registros as $registro)
                                <tr>
                                    <td class="font-weight-bold">{{ $registro->id }}</td>
                                    <td class="text-center">
                                        <div class="font-weight-bold">
                                            {{ $registro->created_at->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $registro->created_at->format('H:i:s') }}
                                        </small>
                                    </td>
                                    <td>
                                        <i class="fas fa-user text-info"></i> 
                                        {{ $registro->usuario->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $registro->modulo }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match(strtolower($registro->accion)) {
                                                'crear', 'crear producto', 'nueva compra' => 'badge-success',
                                                'actualizar', 'editar', 'modificar' => 'badge-warning',
                                                'eliminar', 'anular' => 'badge-danger',
                                                'consultar', 'ver' => 'badge-info',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $registro->accion }}</span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $registro->descripcion }}">
                                            {{ $registro->descripcion }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <code class="text-xs">{{ $registro->ip_Equipo ?? 'N/A' }}</code>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-info"
                                                onclick="$('#modalDetalle{{ $registro->id }}').modal('show')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Modales de detalle -->
                @foreach($registros as $registro)
                    <div class="modal fade" id="modalDetalle{{ $registro->id }}" tabindex="-1" wire:ignore.self>
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-info-circle"></i> Detalle de Registro #{{ $registro->id }}
                                    </h5>
                                    <button type="button" class="close text-white" onclick="event.preventDefault(); event.stopPropagation(); var modal = $('#modalDetalle{{ $registro->id }}'); modal.find('*').blur(); modal.modal('hide'); return false;">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="border-bottom pb-2"><i class="fas fa-info"></i> Información General</h6>
                                            <p><strong>ID:</strong> {{ $registro->id }}</p>
                                            <p><strong>Fecha/Hora:</strong> {{ $registro->created_at->format('d/m/Y H:i:s') }}</p>
                                            <p><strong>Usuario:</strong> {{ $registro->usuario->name ?? 'N/A' }}</p>
                                            <p><strong>Módulo:</strong> <span class="badge badge-primary">{{ $registro->modulo }}</span></p>
                                            <p><strong>Acción:</strong> 
                                                @php
                                                    $badgeClass = match(strtolower($registro->accion)) {
                                                        'crear', 'crear producto', 'nueva compra' => 'badge-success',
                                                        'actualizar', 'editar', 'modificar' => 'badge-warning',
                                                        'eliminar', 'anular' => 'badge-danger',
                                                        'consultar', 'ver' => 'badge-info',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $registro->accion }}</span>
                                            </p>
                                            <p><strong>IP del Equipo:</strong> <code>{{ $registro->ip_Equipo ?? 'N/A' }}</code></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="border-bottom pb-2"><i class="fas fa-database"></i> Información de Referencia</h6>
                                            <p><strong>ID Referencia:</strong> {{ $registro->idReferencia ?? 'N/A' }}</p>
                                            <p><strong>Tabla Referencia:</strong> {{ $registro->tablaReferencia ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6 class="border-bottom pb-2"><i class="fas fa-comment-alt"></i> Descripción</h6>
                                            <p class="bg-light p-3 rounded">{{ $registro->descripcion }}</p>
                                        </div>
                                    </div>

                                    @if($registro->datosAnteriores)
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="border-bottom pb-2"><i class="fas fa-history"></i> Datos Anteriores</h6>
                                                <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode($registro->datosAnteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </div>
                                        </div>
                                    @endif

                                    @if($registro->datosNuevos)
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="border-bottom pb-2"><i class="fas fa-file-alt"></i> Datos Nuevos</h6>
                                                <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode($registro->datosNuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="event.preventDefault(); event.stopPropagation(); var modal = $('#modalDetalle{{ $registro->id }}'); modal.find('*').blur(); modal.modal('hide'); return false;">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Paginación -->
                <div class="mt-3">
                    {{ $registros->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No hay registros</h5>
                    <p class="text-muted">No se encontraron registros con los filtros aplicados.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Reinicializar modales después de actualizaciones de Livewire
        Livewire.hook('message.processed', (message, component) => {
            // Asegurar que Bootstrap modal esté disponible
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $('.modal').modal('dispose');
            }
        });

        // Prevenir el error de aria-hidden
        $(document).on('show.bs.modal', '.modal', function () {
            $(this).removeAttr('aria-hidden');
        });

        $(document).on('shown.bs.modal', '.modal', function () {
            $(this).removeAttr('aria-hidden');
            $(this).find('[aria-hidden="true"]').removeAttr('aria-hidden');
        });

        $(document).on('hide.bs.modal', '.modal', function () {
            $(this).removeAttr('aria-hidden');
            $(this).find('*').blur();
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            $(this).removeAttr('aria-hidden');
            $(this).removeAttr('style');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
        });
    });
</script>
@endpush
