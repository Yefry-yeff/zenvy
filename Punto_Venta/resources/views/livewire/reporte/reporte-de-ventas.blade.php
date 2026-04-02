<div class="px-4 container-fluid">

    <!-- Encabezado -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0 text-gray-800 h3">Reporte de Ventas</h1>
            </div>
        </div>
    </div>

    <!-- Filtros principales -->
    <div class="overflow-hidden border border-gray-300 rounded shadow">
        <div class="px-4 py-3 border-b bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <!-- Fecha Inicio -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Inicio</label>
                    <input type="date"
                           wire:model.live="fechaInicio"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <!-- Fecha Final -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Final</label>
                    <input type="date"
                           wire:model.live="fechaFinal"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <!-- Cliente -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Cliente</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="filtroCliente"
                           placeholder="Buscar cliente..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <!-- N° Factura -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">N° Factura</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="filtroNumeroFactura"
                           placeholder="Número de factura..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <!-- Monto -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Monto</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="filtroMonto"
                           placeholder="Filtrar por monto..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <!-- Botones de acción -->
            <div class="flex flex-wrap items-center gap-3 mt-3">
                <button wire:click="limpiarFiltros"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-gray-500 rounded hover:bg-gray-600">
                    <i class="fas fa-eraser"></i> Limpiar filtros
                </button>

                <!-- Descargar Excel -->
                <button wire:click="descargarExcel"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                        wire:loading.attr="disabled"
                        wire:target="descargarExcel"
                        title="Descargar Excel">
                    <span wire:loading.remove wire:target="descargarExcel">📥</span>
                    <span wire:loading wire:target="descargarExcel">
                        <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="descargarExcel">Descargar Excel</span>
                    <span wire:loading wire:target="descargarExcel">Generando...</span>
                </button>

                <!-- Descargar PDF -->
                <button wire:click="descargarPDF"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
                        wire:loading.attr="disabled"
                        wire:target="descargarPDF"
                        title="Descargar PDF">
                    <span wire:loading.remove wire:target="descargarPDF">📄</span>
                    <span wire:loading wire:target="descargarPDF">
                        <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="descargarPDF">Descargar PDF</span>
                    <span wire:loading wire:target="descargarPDF">Generando...</span>
                </button>
            </div>
        </div>

        <!-- Spinner de carga -->
        <div wire:loading class="flex items-center justify-center py-8 bg-white border-b">
            <svg class="w-8 h-8 mr-3 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-600">Cargando reporte...</span>
        </div>

        <!-- Información de resultados -->
        <div wire:loading.remove class="px-4 py-2 bg-gray-100 border-b">
            <div class="text-sm text-gray-600">
                Mostrando
                @if($facturas->firstItem())
                    {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} de {{ $facturas->total() }} resultados
                @else
                    0 resultados
                @endif
            </div>
        </div>

        <!-- Tabla -->
        <div wire:loading.remove class="px-2 py-2">
            <div class="overflow-x-auto max-h-[calc(100vh-380px)]">
                <table class="min-w-full text-xs border border-gray-200">
                    <thead class="sticky top-0 z-10 bg-gray-100">
                        <!-- Encabezados con ordenamiento -->
                        <tr>
                            <th class="px-3 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-16" wire:click="ordenar('id')">
                                <div class="flex items-center space-x-1">
                                    <span class="text-xs font-semibold">ID</span>
                                    @if($ordenarPor === 'id')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-36" wire:click="ordenar('numero_factura')">
                                <div class="flex items-center space-x-1">
                                    <span class="text-xs font-semibold">N° Fact.</span>
                                    @if($ordenarPor === 'numero_factura')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left border-b cursor-pointer hover:bg-gray-200" wire:click="ordenar('nombre_cliente')">
                                <div class="flex items-center space-x-1">
                                    <span class="text-xs font-semibold">Cliente</span>
                                    @if($ordenarPor === 'nombre_cliente')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-28" wire:click="ordenar('rtn')">
                                <div class="flex items-center space-x-1">
                                    <span class="text-xs font-semibold">RTN</span>
                                    @if($ordenarPor === 'rtn')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('fecha_emision')">
                                <div class="flex items-center space-x-1">
                                    <span class="text-xs font-semibold">Fecha</span>
                                    @if($ordenarPor === 'fecha_emision')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-2 py-3 text-right border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('sub_total_grabado')">
                                <div class="flex items-center justify-end space-x-1">
                                    <span class="text-xs font-semibold">Gravado</span>
                                    @if($ordenarPor === 'sub_total_grabado')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-2 py-3 text-right border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('sub_total_exento')">
                                <div class="flex items-center justify-end space-x-1">
                                    <span class="text-xs font-semibold">Exento</span>
                                    @if($ordenarPor === 'sub_total_exento')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-2 py-3 text-right border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('monto_descuento')">
                                <div class="flex items-center justify-end space-x-1">
                                    <span class="text-xs font-semibold">Descuento</span>
                                    @if($ordenarPor === 'monto_descuento')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-2 py-3 text-right border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('isv')">
                                <div class="flex items-center justify-end space-x-1">
                                    <span class="text-xs font-semibold">ISV</span>
                                    @if($ordenarPor === 'isv')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-2 py-3 text-right border-b cursor-pointer hover:bg-gray-200 w-24" wire:click="ordenar('total')">
                                <div class="flex items-center justify-end space-x-1">
                                    <span class="text-xs font-semibold">Total</span>
                                    @if($ordenarPor === 'total')
                                        <span class="text-blue-500">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($facturas as $factura)
                            <tr class="transition-colors duration-150 hover:bg-gray-50" wire:key="rv-{{ $factura->id }}">
                                <td class="px-3 py-1 text-xs">{{ $factura->id }}</td>
                                <td class="px-3 py-1 text-xs font-semibold">{{ $factura->numero_factura ?? 'N/A' }}</td>
                                <td class="px-3 py-1 text-xs truncate max-w-[180px]" title="{{ $factura->nombre_cliente ?? 'Cliente General' }}">
                                    {{ Str::limit($factura->nombre_cliente ?? 'Cliente General', 30) }}
                                </td>
                                <td class="px-3 py-1 text-xs">{{ $factura->rtn ?? 'N/A' }}</td>
                                <td class="px-3 py-1 text-xs">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                                <td class="px-2 py-1 text-xs text-right">{{ number_format($factura->sub_total_grabado ?? 0, 2) }}</td>
                                <td class="px-2 py-1 text-xs text-right">{{ number_format($factura->sub_total_exento ?? 0, 2) }}</td>
                                <td class="px-2 py-1 text-xs text-right text-red-600">{{ number_format($factura->monto_descuento ?? 0, 2) }}</td>
                                <td class="px-2 py-1 text-xs text-right">{{ number_format($factura->isv ?? 0, 2) }}</td>
                                <td class="px-2 py-1 text-xs font-semibold text-right text-green-700">{{ number_format($factura->total ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center">
                                    <div class="mb-4 text-5xl text-gray-400">🧾</div>
                                    <h3 class="mb-2 text-lg font-medium text-gray-900">No se encontraron ventas</h3>
                                    <p class="mb-4 text-gray-500">Ajusta los filtros o el rango de fechas.</p>
                                    <button wire:click="limpiarFiltros" class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600">Limpiar filtros</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!-- Fila de totales -->
                    @if($facturas->count() > 0)
                    <tfoot>
                        <tr class="text-xs font-bold bg-gray-200 border-t-2 border-gray-400">
                            <td colspan="5" class="px-3 py-2 text-right text-gray-700 uppercase tracking-wide">Totales (todos los registros):</td>
                            <td class="px-2 py-2 text-right text-gray-800">{{ number_format($totales->total_gravado ?? 0, 2) }}</td>
                            <td class="px-2 py-2 text-right text-gray-800">{{ number_format($totales->total_exento ?? 0, 2) }}</td>
                            <td class="px-2 py-2 text-right text-red-700">{{ number_format($totales->total_descuento ?? 0, 2) }}</td>
                            <td class="px-2 py-2 text-right text-gray-800">{{ number_format($totales->total_isv ?? 0, 2) }}</td>
                            <td class="px-2 py-2 text-right text-green-800">{{ number_format($totales->total_total ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-4">
                @if($facturas->hasPages())
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-700">
                                Mostrando {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} de {{ $facturas->total() }} resultados
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Mostrar:</label>
                                <select wire:model.live="registrosPorPagina" class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex space-x-1">
                            @if($facturas->onFirstPage())
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Anterior</span>
                            @else
                                <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</button>
                            @endif

                            @php
                                $currentPage = $facturas->currentPage();
                                $lastPage    = $facturas->lastPage();
                                $start       = max(1, min($currentPage - 2, $lastPage - 4));
                                $end         = min($start + 4, $lastPage);
                            @endphp
                            @for($pg = $start; $pg <= $end; $pg++)
                                @if($pg == $currentPage)
                                    <span class="px-3 py-2 text-sm font-medium text-blue-600 border border-blue-300 rounded bg-blue-50">{{ $pg }}</span>
                                @else
                                    <button wire:click="gotoPage({{ $pg }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $pg }}</button>
                                @endif
                            @endfor

                            @if($end < $lastPage)
                                @if($end < $lastPage - 1)
                                    <span class="px-3 py-2 text-sm text-gray-400">...</span>
                                @endif
                                <button wire:click="gotoPage({{ $lastPage }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $lastPage }}</button>
                            @endif

                            @if($facturas->hasMorePages())
                                <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Siguiente</button>
                            @else
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Siguiente</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-700">{{ $facturas->count() }} resultados</div>
                @endif
            </div>
        </div>
    </div>
</div>
