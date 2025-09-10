<div>
    <style>
        /* Estilos para tabla de productos recibidos */
        .producto-row-disponible {
            background-color: rgba(34, 197, 94, 0.05) !important;
        }
        .producto-row-agotado {
            background-color: rgba(239, 68, 68, 0.05) !important;
            opacity: 0.7;
        }
        .badge-disponible {
            background-color: #22c55e !important;
            color: white;
            font-weight: 600;
        }
        .badge-agotado {
            background-color: #ef4444 !important;
            color: white;
            font-weight: 600;
        }
        .badge-poco-stock {
            background-color: #f59e0b !important;
            color: white;
            font-weight: 600;
        }
    </style>

    <!-- Mensajes de éxito y error -->
    @if (session()->has('mensaje'))
    <div x-data="{ show: true }" x-init="$nextTick(() => show = true)"
        x-show="show"
        x-transition
        style="display: none;"
    >
        <div class="modal fade show d-block" tabindex="-1" role="dialog" @click.away="show = false">
            <div class="modal-dialog modal-dialog-centered" @click.stop>
                <div class="shadow modal-content border-success">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title">✅ Éxito</h5>
                        <button type="button" class="btn-close" @click="show = false"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ session('mensaje') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" @click="show = false">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" x-show="show" x-transition></div>
    </div>
    @endif

    @if (session()->has('error'))
    <div x-data="{ show: true }" x-init="$nextTick(() => show = true)"
        x-show="show"
        x-transition
        style="display: none;"
    >
        <div class="modal fade show d-block" tabindex="-1" role="dialog" @click.away="show = false">
            <div class="modal-dialog modal-dialog-centered modal-lg" @click.stop>
                <div class="shadow modal-content border-danger">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">❌ Error</h5>
                        <button type="button" class="btn-close btn-close-white" @click="show = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <strong>Se ha producido un error:</strong>
                            <br><br>
                            <div class="font-monospace small bg-light p-2 rounded border">
                                {{ session('error') }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" @click="show = false">Entendido</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" x-show="show" x-transition></div>
    </div>
    @endif

    <!-- Filtros y búsqueda -->
    <div class="overflow-hidden border border-gray-300 rounded shadow mb-4" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
        <div class="flex items-center justify-between px-5 py-3 font-semibold text-white" 
             :class="{'bg-emerald-600': theme === 'verde','bg-blue-600': theme === 'azul','bg-gray-900': theme === 'oscuro','bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'}">
            <h5 class="mb-0 text-lg">🔍 Filtros de Búsqueda</h5>
        </div>
        <div class="px-4 py-3 card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="filtroProducto" class="form-label">Buscar Producto</label>
                    <input type="text" 
                           id="filtroProducto" 
                           class="form-control" 
                           wire:model.live="filtroProducto"
                           placeholder="Nombre, código de barras...">
                </div>
                <div class="col-md-3">
                    <label for="filtroBodega" class="form-label">Bodega</label>
                    <select id="filtroBodega" class="form-select" wire:model.live="filtroBodega">
                        <option value="">Todas las bodegas</option>
                        @foreach($bodegas as $bodega)
                            <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtroEstado" class="form-label">Estado Stock</label>
                    <select id="filtroEstado" class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        <option value="disponible">Disponible</option>
                        <option value="poco_stock">Poco Stock</option>
                        <option value="agotado">Agotado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtroMarca" class="form-label">Marca</label>
                    <select id="filtroMarca" class="form-select" wire:model.live="filtroMarca">
                        <option value="">Todas las marcas</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button wire:click="limpiarFiltros" class="btn btn-outline-secondary btn-sm">
                        🗑️ Limpiar Filtros
                    </button>
                    <span class="badge bg-info ms-2">{{ count($productosRecibidos) }} productos encontrados</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t" 
             :class="{'bg-emerald-600': theme === 'verde','bg-blue-600': theme === 'azul','bg-gray-900': theme === 'oscuro','bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'}">
            <h5 class="mb-0 text-lg">📦 Productos Recibidos en Bodega</h5>
            <div class="flex items-center gap-2">
                <span class="text-sm opacity-90">Total: {{ count($productosRecibidos) }}</span>
            </div>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="tbl_productos_bodega" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Código Barra</th>
                            <th>Marca</th>
                            <th>Bodega</th>
                            <th>Segmento</th>
                            <th>Sección</th>
                            <th>Cantidad Disponible</th>
                            <th>Unidad</th>
                            <th>Fecha Recibido</th>
                            <th>Fecha Expiración</th>
                            <th>Estado</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosRecibidos as $item)
                            <tr class="text-center align-middle hover:bg-gray-50 {{ $item->cantidad_disponible > 10 ? 'producto-row-disponible' : ($item->cantidad_disponible > 0 ? '' : 'producto-row-agotado') }}">
                                <td class="fw-semibold">{{ $item->id }}</td>
                                <td class="text-start">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $item->producto_nombre }}</span>
                                        @if($item->producto_descripcion)
                                            <small class="text-muted">{{ Str::limit($item->producto_descripcion, 30) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-start">
                                    <code class="small">{{ $item->codigo_barra ?? 'N/A' }}</code>
                                </td>
                                <td class="text-start">{{ $item->marca_nombre ?? 'Sin marca' }}</td>
                                <td class="text-start">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $item->bodega_nombre }}</span>
                                        <small class="text-muted">{{ $item->tienda_nombre }}</small>
                                    </div>
                                </td>
                                <td class="text-start">{{ $item->segmento_descripcion }}</td>
                                <td class="text-start">{{ $item->seccion_descripcion }}</td>
                                <td class="text-center">
                                    <span class="fw-bold {{ $item->cantidad_disponible > 10 ? 'text-success' : ($item->cantidad_disponible > 0 ? 'text-warning' : 'text-danger') }}">
                                        {{ number_format($item->cantidad_disponible, 0) }}
                                    </span>
                                </td>
                                <td class="text-start">{{ $item->unidad_medida ?? 'Unidad' }}</td>
                                <td class="text-start">
                                    {{ \Carbon\Carbon::parse($item->fecha_recibido)->format('d/m/Y') }}
                                </td>
                                <td class="text-start">
                                    @if($item->fecha_expiracion)
                                        @php
                                            $fechaExpiracion = \Carbon\Carbon::parse($item->fecha_expiracion);
                                            $diasRestantes = $fechaExpiracion->diffInDays(now(), false);
                                        @endphp
                                        <span class="{{ $diasRestantes > 30 ? 'text-success' : ($diasRestantes > 7 ? 'text-warning' : 'text-danger') }}">
                                            {{ $fechaExpiracion->format('d/m/Y') }}
                                            @if($diasRestantes <= 30)
                                                <br><small>({{ abs($diasRestantes) }} días)</small>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->cantidad_disponible > 10)
                                        <span class="badge badge-disponible">Disponible</span>
                                    @elseif($item->cantidad_disponible > 0)
                                        <span class="badge badge-poco-stock">Poco Stock</span>
                                    @else
                                        <span class="badge badge-agotado">Agotado</span>
                                    @endif
                                </td>
                                <td class="text-start">
                                    @if($item->comentario)
                                        <span title="{{ $item->comentario }}">{{ Str::limit($item->comentario, 20) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <svg class="w-16 h-16 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 64px; height: 64px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                                        </svg>
                                        <span class="text-muted">No hay productos recibidos en bodega</span>
                                        <small class="text-muted">Los productos aparecerán aquí cuando sean distribuidos a las bodegas</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div> {{-- FIN ELEMENTO RAÍZ --}}