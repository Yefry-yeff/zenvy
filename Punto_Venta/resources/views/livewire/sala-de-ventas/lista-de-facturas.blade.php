<div class="px-4 container-fluid">
    <!-- Mensajes de éxito/error -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Encabezado -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0 text-gray-800 h3">Lista de Facturas</h1>
            </div>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="row">
        <div class="col-12">
            <!-- Información de filtro por usuario -->
            @auth
                @if($esAdmin ?? false)
                    <div class="mb-3 alert alert-success border-left-success">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield me-2"></i>
                            <div>
                                <strong>Vista de Administrador:</strong> Puedes ver todas las facturas del sistema.
                                <small class="d-block text-muted">Usuario: {{ Auth::user()->name }} (Admin)</small>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-3 alert alert-info border-left-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>Vista personalizada:</strong> Solo se muestran las facturas que has creado.
                                <small class="d-block text-muted">Usuario actual: {{ Auth::user()->name }}</small>
                            </div>
                        </div>
                    </div>
                @endif
            @endauth

            <div class="overflow-hidden border border-gray-300 rounded shadow">
                <!-- Barra de búsqueda y filtros principales -->
                <div class="px-4 py-3 border-b bg-gray-50">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Búsqueda -->
                        <div class="md:col-span-2">
                            <label class="block mb-1 text-sm font-medium text-gray-700">Buscar</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="buscar"
                                   placeholder="Buscar por cliente, número o RTN..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <!-- Botón Excel -->
                        <div class="flex items-end gap-2">
                            <button wire:click="descargarExcel"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                                wire:loading.attr="disabled"
                                wire:target="descargarExcel"
                                title="Descargar reporte de facturas">
                                <span wire:loading.remove wire:target="descargarExcel">📥</span>
                                <span wire:loading wire:target="descargarExcel">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                <span wire:loading.remove wire:target="descargarExcel">Descargar Excel</span>
                                <span wire:loading wire:target="descargarExcel">Generando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de resultados -->
                <div class="px-4 py-2 bg-gray-100 border-b">
                    <div class="text-sm text-gray-600">
                        @php
                            $esPaginador = method_exists($facturas, 'firstItem');
                        @endphp
                        Mostrando
                        @if($esPaginador)
                            {{ $facturas->firstItem() ?? 0 }} a {{ $facturas->lastItem() ?? 0 }} de {{ $facturas->total() }} resultados
                        @else
                            {{ $facturas->count() }} resultados
                        @endif
                        @if($buscar)
                            | Filtrado por: "{{ $buscar }}"
                        @endif
                    </div>
                </div>

                <!-- Tabla optimizada -->
                <div class="px-2 py-2">
                    <div class="overflow-x-auto max-h-[calc(100vh-320px)]">
                        <table class="min-w-full text-xs border border-gray-200 table-fixed">
                            <thead class="sticky top-0 z-10 bg-gray-100">
                                <!-- Encabezados con ordenamiento -->
                                <tr>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-20" wire:click="ordenar('id')">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-xs font-semibold">ID</span>
                                            @if($ordenarPor === 'id')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-40" wire:click="ordenar('numero_factura')">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-xs font-semibold">N° Fact.</span>
                                            @if($ordenarPor === 'numero_factura')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('nombre_cliente')">
                                        <div class="flex items-center space-x-1">
                                            <span>Cliente</span>
                                            @if($ordenarPor === 'nombre_cliente')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('rtn')">
                                        <div class="flex items-center space-x-1">
                                            <span>RTN</span>
                                            @if($ordenarPor === 'rtn')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('fecha_emision')">
                                        <div class="flex items-center space-x-1">
                                            <span>Fecha</span>
                                            @if($ordenarPor === 'fecha_emision')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-2 py-3 border-b cursor-pointer text-end hover:bg-gray-100 w-24" wire:click="ordenar('sub_total')">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span>Subtotal</span>
                                            @if($ordenarPor === 'sub_total')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-2 py-3 border-b cursor-pointer text-end hover:bg-gray-100 w-24" wire:click="ordenar('isv')">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span>ISV</span>
                                            @if($ordenarPor === 'isv')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-2 py-3 border-b cursor-pointer text-end hover:bg-gray-100 w-24" wire:click="ordenar('total')">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span>Total</span>
                                            @if($ordenarPor === 'total')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('estado_factura_id')">
                                        <div class="flex items-center justify-center space-x-1">
                                            <span>Estado</span>
                                            @if($ordenarPor === 'estado_factura_id')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center border-b">Acciones</th>
                                </tr>
                                <!-- Fila de filtros por columna -->
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroId" placeholder="Filtrar..." class="w-full pl-1 pr-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroNumero" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroCliente" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroRTN" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="date" wire:model.live.debounce.300ms="filtroFecha" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-2 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroSubtotal" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-2 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroISV" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-2 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroTotal" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <!-- Sin filtro para estado -->
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <!-- Sin filtro para acciones -->
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @if($facturas->count() > 0)
                                    @foreach($facturas as $factura)
                                    <tr class="transition-colors duration-150 cursor-pointer hover:bg-gray-50" wire:key="factura-{{ $factura->id }}" wire:click="verDetalle({{ $factura->id }})">
                                        <td class="px-2 py-1 text-xs">{{ $factura->id }}</td>
                                        <td class="px-2 py-1 text-xs font-semibold">{{ $factura->numero_factura ?? 'N/A' }}</td>
                                        <td class="px-2 py-1 text-xs truncate" title="{{ $factura->nombre_cliente ?? 'Cliente General' }}">{{ Str::limit($factura->nombre_cliente ?? 'Cliente General', 25) }}</td>
                                        <td class="px-2 py-1 text-xs">{{ $factura->rtn ?? 'N/A' }}</td>
                                        <td class="px-2 py-1 text-xs">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/y') }}</td>
                                        <td class="px-2 py-1 text-xs text-end">{{ number_format($factura->sub_total, 2) }}</td>
                                        <td class="px-2 py-1 text-xs text-end">{{ number_format($factura->isv, 2) }}</td>
                                        <td class="px-2 py-1 text-xs font-semibold text-end">{{ number_format($factura->total, 2) }}</td>
                                        <td class="px-2 py-1 text-center">
                                            @if($factura->estado_factura_id == 1)
                                                <span class="badge bg-success" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">✓</span>
                                            @elseif($factura->estado_factura_id == 2)
                                                <span class="badge bg-danger" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">✗</span>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">?</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1 text-center" onclick="event.stopPropagation()">
                                            <!-- Dropdown de acciones con Alpine.js -->
                                            <div class="position-relative" x-data="{ open: false }">
                                                <button @click="open = !open"
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 border rounded hover:bg-blue-700"
                                                        title="Acciones">
                                                    <span>⚙️</span>
                                                </button>

                                                <div x-show="open"
                                                     @click.away="open = false"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 transform scale-95"
                                                     x-transition:enter-end="opacity-100 transform scale-100"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100 transform scale-100"
                                                     x-transition:leave-end="opacity-0 transform scale-95"
                                                     class="bg-white border rounded shadow-lg position-absolute"
                                                     style="top: 100%; right: 0; z-index: 1050; min-width: 180px; margin-top: 0.25rem;">

                                                    <div class="p-1">
                                                        <!-- Imprimir -->
                                                        <a href="{{ route('factura.pdf.preview', $factura->id) }}" 
                                                           target="_blank"
                                                           class="flex items-center w-full px-2 py-1.5 text-xs text-left text-green-600 border-0 rounded hover:bg-green-50"
                                                           @click="open = false">
                                                            <i class="mr-2 fas fa-print"></i> Imprimir
                                                        </a>

                                                        <!-- Descargar PDF -->
                                                        <button type="button"
                                                                class="flex items-center w-full px-2 py-1.5 text-xs text-left text-red-600 border-0 rounded hover:bg-red-50"
                                                                wire:click="generarPDF({{ $factura->id }})"
                                                                @click="open = false">
                                                            <i class="mr-2 fas fa-file-pdf"></i> PDF
                                                        </button>

                                                        <!-- Anular Factura (solo si NO está anulada) -->
                                                        @if($factura->estado_factura_id != 2)
                                                            <hr class="my-1">
                                                            <button type="button"
                                                                    class="flex items-center w-full px-2 py-1.5 text-xs font-semibold text-left text-orange-600 border-0 rounded hover:bg-orange-50"
                                                                    wire:click="abrirModalAnular({{ $factura->id }})"
                                                                    @click="open = false">
                                                                <i class="mr-2 fas fa-ban"></i> Anular
                                                            </button>
                                                        @else
                                                            <hr class="my-1">
                                                            <div class="px-2 py-1 text-xs text-gray-500">
                                                                <i class="mr-1 fas fa-info-circle"></i> Anulada
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <!-- Mensaje cuando no hay facturas -->
                                    <tr>
                                        <td colspan="10" class="px-4 py-12 text-center">
                                            <div class="mb-4 text-6xl text-gray-400">🧾</div>
                                            <h3 class="mb-2 text-lg font-medium text-gray-900">No se encontraron facturas</h3>
                                            <p class="mb-4 text-gray-500">
                                                @if($buscar || $filtroId || $filtroNumero || $filtroCliente || $filtroRTN || $filtroFecha || $filtroSubtotal || $filtroISV || $filtroTotal)
                                                    No hay facturas que coincidan con los filtros aplicados
                                                @else
                                                    No hay facturas registradas en el sistema
                                                @endif
                                            </p>
                                            @if($buscar || $filtroId || $filtroNumero || $filtroCliente || $filtroRTN || $filtroFecha || $filtroSubtotal || $filtroISV || $filtroTotal)
                                                <button wire:click="limpiarFiltros" class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600">Limpiar filtros</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación personalizada -->
                    <div class="mt-4">
                        @php $esPaginador = method_exists($facturas, 'firstItem'); @endphp
                        @if($esPaginador && $facturas->hasPages())
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="text-sm text-gray-700">
                                        Mostrando {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} de {{ $facturas->total() }} resultados
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Mostrar:</label>
                                        <select wire:model.live="registrosPorPagina" class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex space-x-1">
                                    {{-- Previous Page Link --}}
                                    @if($facturas->onFirstPage())
                                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Anterior</span>
                                    @else
                                        <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</button>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $currentPage = $facturas->currentPage();
                                        $lastPage = $facturas->lastPage();
                                        $start = max(1, min($currentPage - 2, $lastPage - 4));
                                        $end = min($start + 4, $lastPage);
                                    @endphp

                                    @for($page = $start; $page <= min($end, $lastPage); $page++)
                                        @if($page == $currentPage)
                                            <span class="px-3 py-2 text-sm font-medium text-blue-600 border border-blue-300 rounded bg-blue-50">{{ $page }}</span>
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
                                    @if($facturas->hasMorePages())
                                        <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Siguiente</button>
                                    @else
                                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Siguiente</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-700">
                                    {{ $facturas->count() }} resultados
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de impresión de factura -->
    @if($facturaParaImprimir)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-print"></i>
                            Impresión de Factura {{ $facturaParaImprimir->numero_factura }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarImpresion"></button>
                    </div>
                    <div class="p-0 modal-body">
                        <div class="row no-gutters">
                            <!-- Vista previa de la factura -->
                            <div class="col-md-8">
                                <div class="p-3" style="background-color: #f8f9fa; height: 600px; overflow-y: auto;">
                                    <div class="p-4 bg-white shadow-sm" style="max-width: 400px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 12px;">
                                        @include('pdf.factura', [
                                            'factura' => $facturaParaImprimir,
                                            'productos' => collect($productosFacturaImpresa),
                                            'pagos' => collect($pagosFacturaImpresa),
                                            'empresa' => \Illuminate\Support\Facades\DB::table('empresa')->first(),
                                            'tienda' => \Illuminate\Support\Facades\DB::table('tienda as t')
                                                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                                                ->select('t.*', 'd.domicilio_tributario')
                                                ->where('t.id', 1)
                                                ->first(),
                                            'caiFacturaImpresa' => $caiFacturaImpresa
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de opciones -->
                            <div class="col-md-4">
                                <div class="p-4">
                                    <h6 class="mb-3">Opciones de impresión</h6>

                                    <div class="gap-2 mb-3 d-grid">
                                        <button onclick="window.print()" class="btn btn-primary">
                                            <i class="fas fa-print"></i> Imprimir ahora
                                        </button>

                                        <a href="{{ route('factura.pdf', $facturaParaImprimir->id) }}"
                                           target="_blank" class="btn btn-danger">
                                            <i class="fas fa-file-pdf"></i> Descargar PDF
                                        </a>

                                        @if($facturaParaImprimir->factura_imagen)
                                            <a href="{{ route('factura.imagen', $facturaParaImprimir->id) }}"
                                               target="_blank" class="btn btn-info">
                                                <i class="fas fa-image"></i> Ver imagen PNG
                                            </a>
                                        @endif
                                    </div>

                                    <div class="pt-3 border-top">
                                        <small class="text-muted">
                                            <strong>Información de la factura:</strong><br>
                                            • Cliente: {{ $facturaParaImprimir->nombre_cliente }}<br>
                                            • Total: L. {{ number_format($facturaParaImprimir->total, 2) }}<br>
                                            • Fecha: {{ $facturaParaImprimir->fecha_emision->format('d/m/Y') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarImpresion">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal para ver detalle de la factura -->
    @if($facturaDetalle && $facturaDetalle->id)
        <div class="modal fade show d-flex align-items-center justify-content-center"
             style="display: flex; background-color: rgba(0,0,0,0.5);"
             tabindex="-1"
             role="dialog"
             wire:click="cerrarDetalle">
            <div class="modal-dialog modal-lg"
                 role="document"
                 onclick="event.stopPropagation()">
                <div class="shadow-lg modal-content">
                    <div class="text-white modal-header bg-primary">
                        <h5 class="modal-title">
                            <i class="mr-2 fas fa-file-invoice"></i>
                            Detalle de Factura - {{ $facturaDetalle->numero_factura ?? 'N/A' }}
                        </h5>
                        <button type="button" class="text-white close" wire:click="cerrarDetalle" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="text-white card-header bg-info">
                                        <h6 class="mb-0"><i class="fas fa-user"></i> Información del Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nombre:</strong><br>{{ $facturaDetalle->nombre_cliente ?? 'Cliente General' }}</p>
                                        <p><strong>RTN:</strong><br>{{ $facturaDetalle->rtn ?? 'N/A' }}</p>
                                        <p><strong>Fecha:</strong><br>{{ \Carbon\Carbon::parse($facturaDetalle->fecha_emision)->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="text-white card-header bg-success">
                                        <h6 class="mb-0"><i class="fas fa-receipt"></i> Información de la Factura</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>ID:</strong><br>{{ $facturaDetalle->id }}</p>
                                        <p><strong>No. Factura:</strong><br>{{ $facturaDetalle->numero_factura ?? 'N/A' }}</p>
                                        <p><strong>Estado:</strong><br>
                                            @if($facturaDetalle->estado_factura_id == 1)
                                                <span class="badge badge-success">Pagada</span>
                                            @elseif($facturaDetalle->estado_factura_id == 2)
                                                <span class="badge badge-warning">Pendiente</span>
                                            @elseif($facturaDetalle->estado_factura_id == 3)
                                                <span class="badge badge-danger">Anulada</span>
                                            @else
                                                <span class="badge badge-secondary">Desconocido</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-calculator"></i> Resumen de Totales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center row">
                                            <div class="col-md-3">
                                                <div class="border-right">
                                                    <h6 class="text-muted">Subtotal</h6>
                                                    <h5 class="text-dark">L. {{ number_format($facturaDetalle->sub_total, 2) }}</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border-right">
                                                    <h6 class="text-muted">Descuento</h6>
                                                    <h5 class="text-dark">L. {{ number_format($facturaDetalle->descuento ?? 0, 2) }}</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border-right">
                                                    <h6 class="text-muted">ISV</h6>
                                                    <h5 class="text-dark">L. {{ number_format($facturaDetalle->isv, 2) }}</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <h6 class="text-muted">Total</h6>
                                                <h5 class="text-dark font-weight-bold">L. {{ number_format($facturaDetalle->total, 2) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarDetalle">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                        @if($facturaDetalle && $facturaDetalle->id)
                            <a href="{{ route('factura.pdf', $facturaDetalle->id) }}" target="_blank" class="btn btn-danger">
                                <i class="fas fa-download"></i> Descargar PDF
                            </a>
                            <a href="{{ route('factura.pdf.preview', $facturaDetalle->id) }}" target="_blank" class="btn btn-success">
                                <i class="fas fa-eye"></i> Visualizar Factura
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DE ANULACIÓN DE FACTURA -->
    @if($mostrarModalAnular && $facturaAAnular)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-scrollable" role="document" style="max-width: 600px;">
                <div class="modal-content" style="max-height: 90vh;">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-ban"></i> Anular Factura #{{ $facturaAAnular->numero_factura }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModalAnular" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: calc(90vh - 180px); overflow-y: auto;">
                        <!-- Información de la factura -->
                        <div class="alert alert-warning py-2 mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="d-block mb-1"><strong>N° Fact:</strong> {{ $facturaAAnular->numero_factura }}</small>
                                    <small class="d-block mb-1"><strong>Cliente:</strong> {{ Str::limit($facturaAAnular->nombre_cliente ?? 'N/A', 20) }}</small>
                                </div>
                                <div class="col-6">
                                    <small class="d-block mb-1"><strong>Total:</strong> L. {{ number_format($facturaAAnular->total, 2) }}</small>
                                    <small class="d-block mb-0"><strong>ISV:</strong> L. {{ number_format($facturaAAnular->isv, 2) }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de anulación -->
                        <div>
                            <div class="form-group mb-2">
                                <label for="motivoAnulacion" class="font-weight-bold mb-1">
                                    <small>Motivo <span class="text-danger">*</span></small>
                                </label>
                                <textarea 
                                    wire:model="motivoAnulacion" 
                                    id="motivoAnulacion" 
                                    class="form-control form-control-sm @error('motivoAnulacion') is-invalid @enderror" 
                                    rows="2" 
                                    placeholder="Mínimo 10 caracteres"
                                    required></textarea>
                                @error('motivoAnulacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">{{ strlen($motivoAnulacion) }}/500</small>
                            </div>

                            <div class="form-group mb-2">
                                <label for="metodoDevolucion" class="font-weight-bold mb-1">
                                    <small>Método Devolución <span class="text-danger">*</span></small>
                                </label>
                                <select 
                                    wire:model="metodoDevolucion" 
                                    id="metodoDevolucion" 
                                    class="form-control form-control-sm @error('metodoDevolucion') is-invalid @enderror"
                                    required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="nota_credito">Nota Crédito</option>
                                    <option value="no_aplica">No Aplica</option>
                                </select>
                                @error('metodoDevolucion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-2">
                                <label for="observacionesAnulacion" class="font-weight-bold mb-1">
                                    <small>Observaciones (Opcional)</small>
                                </label>
                                <textarea 
                                    wire:model="observacionesAnulacion" 
                                    id="observacionesAnulacion" 
                                    class="form-control form-control-sm" 
                                    rows="1" 
                                    placeholder="Info adicional..."></textarea>
                            </div>

                            <!-- Opciones de impacto -->
                            <div class="border rounded p-2 mb-2 bg-light">
                                <small class="font-weight-bold d-block mb-1">Impactos:</small>
                                
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input 
                                        type="checkbox" 
                                        class="custom-control-input" 
                                        id="afectarInventario" 
                                        wire:model="afectarInventario">
                                    <label class="custom-control-label" for="afectarInventario">
                                        <small>📦 Inventario</small>
                                    </label>
                                </div>

                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input 
                                        type="checkbox" 
                                        class="custom-control-input" 
                                        id="afectarFlujoCaja" 
                                        wire:model="afectarFlujoCaja">
                                    <label class="custom-control-label" for="afectarFlujoCaja">
                                        <small>💰 Flujo Caja</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Advertencia final -->
                            <div class="alert alert-danger py-2 mb-0">
                                <small><i class="fas fa-exclamation-circle"></i> <strong>Advertencia:</strong> Acción irreversible.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" wire:click="cerrarModalAnular">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-danger" 
                                wire:click="anularFactura" 
                                wire:loading.attr="disabled"
                                wire:target="anularFactura"
                                onclick="console.log('Botón presionado')">
                            <span wire:loading.remove wire:target="anularFactura">
                                <i class="fas fa-ban"></i> Confirmar
                            </span>
                            <span wire:loading wire:target="anularFactura">
                                <i class="fas fa-spinner fa-spin"></i> Procesando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
