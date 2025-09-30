<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- Mensajes de error -->
    @if (session()->has('error'))
        <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Mensajes de éxito -->
    @if (session()->has('success'))
        <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
            <strong class="font-bold">¡Éxito!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Mensajes de sincronización -->
    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded transition-opacity duration-300" role="alert">
            <strong class="font-bold">Sincronización:</strong>
            <span class="block sm:inline">{{ session('mensaje') }}</span>
        </div>
    @endif

    <div class="overflow-hidden border border-gray-300 rounded shadow">

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
                📋 Listado de Compras de Productos
            </h5>
            <div class="flex gap-2 flex-wrap">
                <!-- Botón de sincronización con estado de carga -->
                <div class="relative">
                    <button wire:click="sincronizarComprasValencia"
                        wire:loading.attr="disabled"
                        wire:target="sincronizarComprasValencia"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100 disabled:opacity-75 disabled:cursor-not-allowed">
                        <div wire:loading wire:target="sincronizarComprasValencia" class="inline-block w-4 h-4 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin"></div>
                        <span wire:loading.remove wire:target="sincronizarComprasValencia">🔄</span>
                        <span wire:loading.remove wire:target="sincronizarComprasValencia">Actualizar Lista de Compras</span>
                        <span wire:loading wire:target="sincronizarComprasValencia">Sincronizando...</span>
                    </button>
                </div>

                <button wire:click="agregarCompra"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>➕</span> Nueva Compra
                </button>

                <!-- Botón Descargar reporte -->
                <button wire:click="descargarExcel"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                    wire:loading.attr="disabled"
                    wire:target="descargarExcel"
                    title="Descargar reporte">
                    <span wire:loading.remove wire:target="descargarExcel">📥</span>
                    <span wire:loading wire:target="descargarExcel">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="descargarExcel">Descargar reporte</span>
                    <span wire:loading wire:target="descargarExcel">Generando...</span>
                </button>
            </div>
        </div>

        <!-- Barra de progreso para sincronización -->
        @if($sincronizandoCompras && $progreso !== null)
            <div class="px-5 pb-3">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-orange-600 h-2 rounded-full transition-all duration-300"
                         style="width: {{ $progreso }}%"></div>
                </div>
                <p class="text-sm text-gray-600 mt-1">Sincronizando compras... {{ $progreso }}%</p>
            </div>
        @endif

        <!-- CONTENIDO PRINCIPAL -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Error</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif



            <!-- TABLA DE COMPRAS -->
            <div class="p-4 bg-white border shadow rounded-xl">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <h2 class="text-lg font-semibold text-gray-700">📋 Compras Realizadas</h2>
                    <div class="d-flex align-items-center gap-3">

        <!-- INFORMACIÓN DE RESULTADOS -->
        <div class="px-4 py-2 bg-gray-100 border-b">
            <div class="text-sm text-gray-600">
                Showing {{ $compras->firstItem() ?? 0 }} to {{ $compras->lastItem() ?? 0 }} 
                of {{ $compras->total() }} results
            </div>
        </div>
                        <small class="text-muted">Ordenadas por ID (más recientes primero)</small>
                        <small class="text-muted">Total: {{ $compras->total() }} compras</small>
                    </div>
                </div>

                <!-- TABLA DE COMPRAS -->
                <div class="px-4 py-3">
                    @if($compras->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-auto border border-gray-200">
                                <thead class="bg-gray-50">
                                    <!-- Encabezados con ordenamiento -->
                                    <tr>
                                        <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100" 
                                            wire:click="ordenar('id')">
                                            <div class="flex items-center space-x-1">
                                                <span>ID</span>
                                                @if($ordenarPor === 'id')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'desc') ↓ @else ↑ @endif
                                                    </span>
                                                @endif
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">(DESC)</small>
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('numero_factura')">
                                            <div class="flex items-center space-x-1">
                                                <span>N° Factura</span>
                                                @if($ordenarPor === 'numero_factura')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('proveedor')">
                                            <div class="flex items-center space-x-1">
                                                <span>Proveedor</span>
                                                @if($ordenarPor === 'proveedor')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('fecha_emision')">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>Fecha Emisión</span>
                                                @if($ordenarPor === 'fecha_emision')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('fecha_recepcion')">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>Fecha Recepción</span>
                                                @if($ordenarPor === 'fecha_recepcion')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('estado')">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>Estado</span>
                                                @if($ordenarPor === 'estado')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('productos')">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>Productos</span>
                                                @if($ordenarPor === 'productos')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                            wire:click="ordenar('total')">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>Total</span>
                                                @if($ordenarPor === 'total')
                                                    <span class="text-blue-500">
                                                        @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center border-b">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($compras as $compra)
                                        <tr class="hover:bg-gray-50 cursor-pointer"
                                            wire:click="verDetalle({{ $compra->id }})"
                                            title="Clic para ver detalles">
                                            
                                            <!-- ID -->
                                            <td class="px-4 py-3 border-b">
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-gray-900 bg-blue-100 px-2 py-1 rounded text-center">{{ $compra->id }}</span>
                                                </div>
                                            </td>

                                            <!-- N° Factura -->
                                            <td class="px-4 py-3 border-b">
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-gray-900">{{ $compra->numero_factura }}</span>
                                                </div>
                                            </td>

                                            <!-- Proveedor -->
                                            <td class="px-4 py-3 border-b text-gray-700">
                                                {{ $compra->proveedor->nombre ?? 'N/A' }}
                                            </td>

                                            <!-- Fecha Emisión -->
                                            <td class="px-4 py-3 border-b text-center text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}
                                            </td>

                                            <!-- Fecha Recepción -->
                                            <td class="px-4 py-3 border-b text-center text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($compra->fecha_recepcion)->format('d/m/Y') }}
                                            </td>

                                            <!-- Estado -->
                                            <td class="px-4 py-3 border-b text-center">
                                                @if($compra->estado)
                                                    @if(strtolower($compra->estado->nombre) === 'activo')
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">Activo</span>
                                                    @elseif(strtolower($compra->estado->nombre) === 'distribuido')
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-yellow-500 rounded-full">Distribuido</span>
                                                    @elseif(strtolower($compra->estado->nombre) === 'anulado')
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded-full">Anulado</span>
                                                    @elseif(strtolower($compra->estado->nombre) === 'pendiente' || $compra->estado_id == 5)
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-blue-500 rounded-full">Pendiente</span>
                                                    @else
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-gray-500 rounded-full">{{ ucfirst($compra->estado->nombre) }}</span>
                                                    @endif
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-gray-500 rounded-full">Sin Estado</span>
                                                @endif
                                            </td>

                                            <!-- Productos -->
                                            <td class="px-4 py-3 border-b text-center">
                                                <span class="font-bold text-blue-600">
                                                    {{ $compra->detallesCompra->count() }}
                                                </span>
                                            </td>

                                            <!-- Total -->
                                            <td class="px-4 py-3 border-b text-center">
                                                <span class="font-bold text-green-600">
                                                    L. {{ number_format($compra->detallesCompra->sum('precio_total'), 2) }}
                                                </span>
                                            </td>

                                            <!-- Acciones -->
                                            <td class="px-4 py-3 border-b text-center" onclick="event.stopPropagation()">
                                                @if($compra->estado && (strtolower($compra->estado->nombre) === 'activo' || strtolower($compra->estado->nombre) === 'pendiente' || $compra->estado_id == 5))
                                                    <div class="position-relative" x-data="{ open: false }">
                                                        <button @click="open = !open"
                                                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
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
                                                             class="position-absolute bg-white border rounded shadow-lg"
                                                             style="top: 100%; right: 0; z-index: 1050; min-width: 180px; margin-top: 0.25rem;">

                                                            <div class="p-1">
                                                                @if($compra->estado_id != 5)
                                                                    <button type="button"
                                                                            class="w-full px-3 py-2 text-sm text-left text-red-600 hover:bg-red-50 border-0 rounded"
                                                                            wire:click="abrirModalAnular({{ $compra->id }})"
                                                                            @click="open = false">
                                                                        ❌ Anular Compra
                                                                    </button>
                                                                @endif
                                                                
                                                                <button type="button"
                                                                        class="w-full px-3 py-2 text-sm text-left text-blue-600 hover:bg-blue-50 border-0 rounded"
                                                                        wire:click="irARecibirProducto({{ $compra->id }})"
                                                                        @click="open = false">
                                                                    📦 Recibir Producto
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded">Sin acciones</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-4 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center space-y-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                    <span class="text-2xl">📦</span>
                                </div>
                                <p class="text-lg font-medium">No hay compras</p>
                                <p class="text-sm">No se encontraron compras registradas.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Paginación -->
                <div class="px-4 py-3 bg-white border-t border-gray-200">
                    @if($compras->hasPages())
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-700">
                                    Showing {{ $compras->firstItem() }} to {{ $compras->lastItem() }} of {{ $compras->total() }} results
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Show:</label>
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
                                @if($compras->onFirstPage())
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Previous</span>
                                @else
                                    <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</button>
                                @endif

                                {{-- Pagination Elements --}}
                                @php
                                    $currentPage = $compras->currentPage();
                                    $lastPage = $compras->lastPage();
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
                                @if($compras->hasMorePages())
                                    <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</button>
                                @else
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Next</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-700">
                                Showing {{ $compras->firstItem() ?? 0 }} to {{ $compras->lastItem() ?? 0 }} of {{ $compras->total() }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Show:</label>
                                <select wire:model.live="registrosPorPagina" class="px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Detalle de Compra -->
    <div x-data="{ open: @entangle('mostrarModalDetalle') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalDetalle()"
         @keydown.escape.window="$wire.cerrarModalDetalle()">

        <div class="w-full max-w-3xl mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-2xl">
                <!-- Header -->
                <div class="px-4 py-3 text-white bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 3a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            <h3 class="text-lg font-semibold">Detalle de Compra</h3>
                        </div>
                        <button wire:click="cerrarModalDetalle" class="text-white transition-colors hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-4 overflow-y-auto max-h-80 custom-scrollbar">
                    @if($compraDetalle)
                    <!-- Información General -->
                    <div class="mb-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="p-3 border-l-4 border-blue-500 rounded-lg bg-gray-50">
                                <div class="mb-1 text-xs font-medium tracking-wide text-gray-600 uppercase">Información General</div>
                                <p class="mb-1 text-sm"><span class="font-medium">N° Factura:</span> {{ $compraDetalle['numero_factura'] }}</p>
                                <p class="mb-1 text-sm"><span class="font-medium">Proveedor:</span> {{ $compraDetalle['proveedor_nombre'] }}</p>
                                <p class="mb-0 text-sm"><span class="font-medium">Estado:</span>
                                    @if(strtolower($compraDetalle['estado']) === 'activo')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">{{ $compraDetalle['estado'] }}</span>
                                    @elseif(strtolower($compraDetalle['estado']) === 'distribuido')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-yellow-800 bg-yellow-100 rounded-full">{{ $compraDetalle['estado'] }}</span>
                                    @elseif(strtolower($compraDetalle['estado']) === 'anulado')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-red-800 bg-red-100 rounded-full">{{ $compraDetalle['estado'] }}</span>
                                    @elseif(strtolower($compraDetalle['estado']) === 'pendiente')
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">{{ $compraDetalle['estado'] }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">{{ $compraDetalle['estado'] }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="p-3 border-l-4 border-green-500 rounded-lg bg-gray-50">
                                <div class="mb-1 text-xs font-medium tracking-wide text-gray-600 uppercase">Fechas</div>
                                <p class="mb-1 text-sm"><span class="font-medium">Emisión:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_emision'])->format('d/m/Y') }}</p>
                                <p class="mb-1 text-sm"><span class="font-medium">Recepción:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_recepcion'])->format('d/m/Y') }}</p>
                                @if($compraDetalle['fecha_vencimiento'])
                                <p class="mb-0 text-sm"><span class="font-medium">Vencimiento:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_vencimiento'])->format('d/m/Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium tracking-wide text-gray-600 uppercase">Productos Comprados</div>
                            <span class="inline-flex items-center px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">{{ $compraDetalle['total_productos'] }} productos</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs tracking-wide text-gray-600 uppercase bg-gray-100">
                                        <th class="px-2 py-2 text-left">Producto</th>
                                        <th class="px-2 py-2 text-center">Cant.</th>
                                        <th class="px-2 py-2 text-center">Unidad</th>
                                        <th class="px-2 py-2 text-right">P. Unit.</th>
                                        <th class="px-2 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($compraDetalle['productos'] as $producto)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 py-2 font-medium text-gray-900">{{ $producto['nombre'] }}</td>
                                        <td class="px-2 py-2 text-center">{{ $producto['cantidad'] }}</td>
                                        <td class="px-2 py-2 text-center text-gray-600">{{ $producto['unidad'] }}</td>
                                        <td class="px-2 py-2 text-right text-gray-600">L. {{ number_format($producto['precio_unitario'], 2) }}</td>
                                        <td class="px-2 py-2 font-medium text-right">L. {{ number_format($producto['precio_total'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="pt-3 border-t">
                        <div class="p-3 rounded-lg bg-blue-50">
                            <div class="mb-2 text-xs font-medium tracking-wide text-gray-600 uppercase">Resumen Financiero</div>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div class="text-center">
                                    <div class="text-gray-600">Subtotal</div>
                                    <div class="font-semibold">L. {{ number_format($compraDetalle['subtotal_general'], 2) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-600">ISV</div>
                                    <div class="font-semibold">L. {{ number_format($compraDetalle['isv_general'], 2) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-600">Total</div>
                                    <div class="text-lg font-bold text-blue-600">L. {{ number_format($compraDetalle['total_general'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 border-t bg-gray-50">
                    <div class="flex justify-between">
                        <button wire:click="descargarDetalleExcel"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center gap-2">
                            <span wire:loading.remove wire:target="descargarDetalleExcel">📥</span>
                            <span wire:loading wire:target="descargarDetalleExcel">Generando...</span>
                            Descargar detalle
                        </button>
                        <button wire:click="cerrarModalDetalle"
                                class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Anulación -->
    <div x-data="{ open: @entangle('mostrarModalAnular') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalAnular()"
         @keydown.escape.window="$wire.cerrarModalAnular()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-red-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Anular Compra</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    @if($compraSeleccionada)
                    <div class="mb-4">
                        <div class="p-3 border rounded bg-gray-50">
                            <p class="mb-1"><strong>Compra:</strong> {{ $compraSeleccionada['numero_factura'] }}</p>
                            <p class="mb-1"><strong>Proveedor:</strong> {{ $compraSeleccionada['proveedor_nombre'] ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Total:</strong> L. {{ number_format($compraSeleccionada['total'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <p class="mb-3 text-gray-700">
                            ⚠️ <strong>¿Está seguro que desea anular esta compra?</strong>
                        </p>
                        <p class="mb-4 text-sm text-gray-600">
                            Esta acción no se puede deshacer. La compra será marcada como anulada y no podrá ser distribuida.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label for="motivoAnulacion" class="form-label">
                            <strong>Motivo de anulación</strong> <span class="text-red-600">*</span>
                        </label>
                        <textarea id="motivoAnulacion"
                                  class="form-control"
                                  rows="3"
                                  wire:model.defer="motivoAnulacion"
                                  placeholder="Ingrese el motivo por el cual está anulando esta compra..."
                                  maxlength="500"></textarea>
                        @error('motivoAnulacion')
                            <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Máximo 500 caracteres</small>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 px-6 py-3 bg-gray-50">
                    <button type="button" wire:click="cerrarModalAnular"
                            class="px-4 py-2 text-gray-700 transition-colors duration-200 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarAnulacion"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        Anular Compra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Error</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    <!-- Estilos CSS -->
    <style>
        /* Alerta flotante personalizada */
        .alert-campo-obligatorio {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Ocultar elementos antes de que Alpine.js los maneje */
        [x-cloak] {
            display: none !important;
        }

        /* Estados de compra */
        .badge {
            font-size: 0.75rem;
        }

        /* Rotación del icono chevron */
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Mejora del botón de acciones */
        .btn-outline-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Transiciones suaves para todos los botones */
        .btn {
            transition: all 0.2s ease;
        }

        /* Hover effects para items del dropdown */
        .dropdown-item-hover:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
        }

        /* Asegurar z-index del dropdown */
        [style*="z-index: 1050"] {
            z-index: 1050 !important;
        }

        /* Mejorar la apariencia del dropdown */
        .dropdown-menu-custom {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>

    {{-- Modal de Detalles de Sincronización de Compras --}}
    @if($detallesSincronizacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="cerrarDetallesSincronizacion"></div>

            {{-- Modal --}}
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Sincronización de Compras Completada</h3>
                    <button wire:click="cerrarDetallesSincronizacion" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                        <span class="font-medium text-green-800">✅ Compras procesadas:</span>
                        <span class="font-bold text-green-600">{{ $detallesSincronizacion['compras_sincronizadas'] }}</span>
                    </div>

                    @if($detallesSincronizacion['compras_nuevas'] > 0)
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                            <span class="font-medium text-blue-800">🆕 Compras nuevas:</span>
                            <span class="font-bold text-blue-600">{{ $detallesSincronizacion['compras_nuevas'] }}</span>
                        </div>
                    @endif

                    @if($detallesSincronizacion['productos_sincronizados'] > 0)
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded">
                            <span class="font-medium text-yellow-800">📦 Productos sincronizados:</span>
                            <span class="font-bold text-yellow-600">{{ $detallesSincronizacion['productos_sincronizados'] }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded">
                        <span class="font-medium text-orange-800">📈 Total procesadas:</span>
                        <span class="font-bold text-orange-600">{{ $detallesSincronizacion['total_procesadas'] }}</span>
                    </div>

                    @if($detallesSincronizacion['errores'] > 0)
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                            <span class="font-medium text-red-800">❌ Errores:</span>
                            <span class="font-bold text-red-600">{{ $detallesSincronizacion['errores'] }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded">
                        <span class="font-medium text-purple-800">⏱️ Tiempo:</span>
                        <span class="font-bold text-purple-600">{{ $detallesSincronizacion['tiempo_ejecucion'] }}</span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button wire:click="cerrarDetallesSincronizacion"
                            class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
