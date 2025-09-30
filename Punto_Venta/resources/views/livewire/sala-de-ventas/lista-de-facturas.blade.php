<div class="px-4 container-fluid">
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
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Búsqueda -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
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
                                    <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('numero_factura')">
                                        <div class="flex items-center space-x-1">
                                            <span>No. Fac</span>
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
                                    <th class="px-4 py-3 text-end border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('sub_total')">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span>Subtotal</span>
                                            @if($ordenarPor === 'sub_total')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-end border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('isv')">
                                        <div class="flex items-center justify-end space-x-1">
                                            <span>ISV</span>
                                            @if($ordenarPor === 'isv')
                                                <span class="text-blue-500">@if($direccionOrden === 'asc') ↑ @else ↓ @endif</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-end border-b cursor-pointer hover:bg-gray-100" wire:click="ordenar('total')">
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
                                        <input type="text" wire:model.live.debounce.300ms="filtroId" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
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
                                        <input type="text" wire:model.live.debounce.300ms="filtroFecha" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroSubtotal" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
                                        <input type="text" wire:model.live.debounce.300ms="filtroISV" placeholder="Filtrar..." class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-2 border-b">
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
                                    <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" wire:key="factura-{{ $factura->id }}" wire:click="verDetalle({{ $factura->id }})">
                                        <td class="px-4 py-3">{{ $factura->id }}</td>
                                        <td class="px-4 py-3"><strong>{{ $factura->numero_factura ?? 'N/A' }}</strong></td>
                                        <td class="px-4 py-3">{{ $factura->nombre_cliente ?? 'Cliente General' }}</td>
                                        <td class="px-4 py-3">{{ $factura->rtn ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-end">L. {{ number_format($factura->sub_total, 2) }}</td>
                                        <td class="px-4 py-3 text-end">L. {{ number_format($factura->isv, 2) }}</td>
                                        <td class="px-4 py-3 text-end"><strong>L. {{ number_format($factura->total, 2) }}</strong></td>
                                        <td class="px-4 py-3">
                                            @if($factura->estado_factura_id == 1)
                                                <span class="badge bg-success">Pagada</span>
                                            @elseif($factura->estado_factura_id == 2)
                                                <span class="badge bg-warning">Pendiente</span>
                                            @elseif($factura->estado_factura_id == 3)
                                                <span class="badge bg-danger">Anulada</span>
                                            @else
                                                <span class="badge bg-secondary">Desconocido</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                            <a href="{{ route('factura.pdf.preview', $factura->id) }}" target="_blank" class="btn btn-sm btn-success" title="Imprimir Factura">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" wire:click="generarPDF({{ $factura->id }})" title="Descargar PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <!-- Mensaje cuando no hay facturas -->
                                    <tr>
                                        <td colspan="10" class="px-4 py-12 text-center">
                                            <div class="text-gray-400 text-6xl mb-4">🧾</div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron facturas</h3>
                                            <p class="text-gray-500 mb-4">
                                                @if($buscar || $filtroId || $filtroNumero || $filtroCliente || $filtroRTN || $filtroFecha || $filtroSubtotal || $filtroISV || $filtroTotal)
                                                    No hay facturas que coincidan con los filtros aplicados
                                                @else
                                                    No hay facturas registradas en el sistema
                                                @endif
                                            </p>
                                            @if($buscar || $filtroId || $filtroNumero || $filtroCliente || $filtroRTN || $filtroFecha || $filtroSubtotal || $filtroISV || $filtroTotal)
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
                        @php $esPaginador = method_exists($facturas, 'firstItem'); @endphp
                        @if($esPaginador && $facturas->hasPages())
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="text-sm text-gray-700">
                                        Mostrando {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} de {{ $facturas->total() }} resultados
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
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Detalle de Factura - {{ $facturaDetalle->numero_factura ?? 'N/A' }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarDetalle" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white">
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
                                    <div class="card-header bg-success text-white">
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
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-calculator"></i> Resumen de Totales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
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
</div>
