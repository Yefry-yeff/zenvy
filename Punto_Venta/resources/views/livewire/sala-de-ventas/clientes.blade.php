<div class="px-4 container-fluid">
    <!-- Encabezado -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0 text-gray-800 h3">Lista de Actores</h1>
                <div>
                    <button wire:click="crearNuevoCliente" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Actor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="overflow-hidden border border-gray-300 rounded shadow">
                <!-- Barra de búsqueda y filtros principales -->
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Búsqueda -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="buscar"
                                   placeholder="Buscar por nombre, identidad, correo o teléfono..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <!-- Botón Excel -->
                        <div class="flex items-end gap-2">
                            <button wire:click="exportarExcel"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                                wire:loading.attr="disabled"
                                wire:target="exportarExcel"
                                title="Descargar reporte de actores">
                                <span wire:loading.remove wire:target="exportarExcel">📥</span>
                                <span wire:loading wire:target="exportarExcel">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                <span wire:loading.remove wire:target="exportarExcel">Descargar Excel</span>
                                <span wire:loading wire:target="exportarExcel">Generando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de resultados -->
                <div class="px-4 py-2 bg-gray-100 border-b">
                    <div class="text-sm text-gray-600">
                        @php $esPaginador = method_exists($clientes, 'firstItem'); @endphp
                        Mostrando
                        @if($esPaginador)
                            {{ $clientes->firstItem() ?? 0 }} a {{ $clientes->lastItem() ?? 0 }} de {{ $clientes->total() }} resultados
                        @else
                            {{ $clientes->count() }} resultados
                        @endif
                        @if($buscar)
                            | Filtrado por: "{{ $buscar }}"
                        @endif
                    </div>
                </div>

                <!-- Tabla optimizada -->
                <div class="px-4 py-3">
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto border border-gray-200">
                            <thead class="bg-gray-50">
                                <!-- Encabezados con ordenamiento -->
                                <tr>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('id')">
                                        <div class="flex items-center space-x-1">
                                            <span>ID</span>
                                            @if($ordenarPor === 'id')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('nombre')">
                                        <div class="flex items-center space-x-1">
                                            <span>Actor</span>
                                            @if($ordenarPor === 'nombre')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('identidad')">
                                        <div class="flex items-center space-x-1">
                                            <span>Identidad</span>
                                            @if($ordenarPor === 'identidad')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>

                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('correo')">
                                        <div class="flex items-center space-x-1">
                                            <span>Correo</span>
                                            @if($ordenarPor === 'correo')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b">Tipo Persona</th>
                                    <th class="px-4 py-3 text-left border-b">Tipo Actor</th>
                                    <th class="px-4 py-3 text-left border-b">Estado</th>
                                    <th class="px-4 py-3 text-center border-b">Acciones</th>
                                </tr>
                                <!-- Fila de filtros por columna -->
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroId" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroNombre" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroIdentidad" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>

                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroCorreo" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroTipoPersona" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroTipo" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroEstado" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @if($clientes->count() > 0)
                                    @foreach($clientes as $cliente)
                                    <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" wire:key="cliente-{{ $cliente->id }}" wire:click="editarCliente({{ $cliente->id }})">
                                        <td class="px-4 py-3">{{ $cliente->id }}</td>
                                        <td class="px-4 py-3"><strong>{{ $cliente->nombre }}</strong><br><small class="text-muted">Registrado: {{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : 'N/A' }}</small></td>
                                        <td class="px-4 py-3">{{ $cliente->identidad ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $cliente->correo ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ optional($cliente->tipoPersona)->nombre ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ optional($cliente->tipoCliente)->nombre ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">
                                            @if($cliente->estado_id == 1)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                            <button class="btn btn-sm btn-primary" wire:click="editarCliente({{ $cliente->id }})" title="Editar" type="button">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <!-- Mensaje cuando no hay clientes -->
                                    <tr>
                                        <td colspan="9" class="px-4 py-12 text-center">
                                            <div class="text-gray-400 text-6xl mb-4">👥</div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron actores</h3>
                                            <p class="text-gray-500 mb-4">
                                                @if($buscar || $filtroId || $filtroNombre || $filtroIdentidad || $filtroCorreo || $filtroTipo || $filtroEstado)
                                                    No hay actores que coincidan con los filtros aplicados
                                                @else
                                                    No hay actores registrados en el sistema
                                                @endif
                                            </p>
                                            @if($buscar || $filtroId || $filtroNombre || $filtroIdentidad || $filtroRTN || $filtroCorreo || $filtroTipo || $filtroEstado)
                                                <button wire:click="limpiarFiltros" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Limpiar filtros</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación personalizada -->
                    <div class="mt-4">
                        @php $esPaginador = method_exists($clientes, 'firstItem'); @endphp
                        @if($esPaginador && $clientes->hasPages())
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="text-sm text-gray-700">
                                        Mostrando {{ $clientes->firstItem() }} a {{ $clientes->lastItem() }} de {{ $clientes->total() }} resultados
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Mostrar:</label>
                                        <select wire:model.live="registrosPorPagina" class="px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex space-x-1">
                                    {{-- Previous Page Link --}}
                                    @if($clientes->onFirstPage())
                                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Anterior</span>
                                    @else
                                        <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</button>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $currentPage = $clientes->currentPage();
                                        $lastPage = $clientes->lastPage();
                                        $start = max(1, min($currentPage - 2, $lastPage - 4));
                                        $end = min($start + 4, $lastPage);
                                    @endphp

                                    @for($page = $start; $page <= min($end, $lastPage); $page++)
                                        @if($page == $currentPage)
                                            <span class="px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-300 rounded">{{ $page }}</span>
                                        @else
                                            <button wire:click="gotoPage({{ $page }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $page }}</button>
                                        @endif
                                    @endfor

                                    {{-- Show last page if not already shown --}}
                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="px-3 py-2 text-sm text-gray-400">...</span>
                                        @endif
                                        <button wire:click="gotoPage({{ $lastPage }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $lastPage }}</button>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if($clientes->hasMorePages())
                                        <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Siguiente</button>
                                    @else
                                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Siguiente</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-700">
                                    {{ $clientes->count() }} resultados
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales y alertas heredados del componente -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Campo Obligatorio</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    @if($mostrarModalExito)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>¡Éxito!</h5>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalExito" class="btn btn-success">Entendido</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($mostrarModalError)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Error</h5>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalError" class="btn btn-danger">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .alert-campo-obligatorio { position: fixed; top: 20px; right: 20px; z-index: 9999; background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; font-size: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); border-left: 4px solid #dc3545; }
        .cursor-pointer:hover { background-color: #f8f9fa !important; }
    </style>

</div>
