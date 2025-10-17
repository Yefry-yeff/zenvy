<div>
    <!-- MENSAJES DE SESIÓN -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✅ Éxito:</strong> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>⚠️ Advertencia:</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>❌ Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- CONTENEDOR PRINCIPAL OPTIMIZADO -->
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">📦 Productos Recibidos en Bodega</h5>
            <div class="flex items-center gap-2">
                <span class="text-sm opacity-90">Total: {{ $productosRecibidos->total() }}</span>
            </div>
        </div>

        <!-- FILTROS Y BÚSQUEDA -->
        <div class="px-4 py-3 border-b bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <!-- Búsqueda de Producto -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Buscar Producto</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="filtroProducto"
                           placeholder="Nombre, código de barras..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Filtro de Bodega -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Bodega</label>
                    <select wire:model.live="filtroBodega" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las bodegas</option>
                        @foreach($bodegas as $bodega)
                            <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Estado Stock -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Estado Stock</label>
                    <select wire:model.live="filtroEstado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="disponible">Disponible</option>
                        <option value="poco_stock">Poco Stock</option>
                        <option value="agotado">Agotado</option>
                    </select>
                </div>

                <!-- Filtro de Marca -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Marca</label>
                    <select wire:model.live="filtroMarca" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las marcas</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="flex gap-2 mt-3">
                <button wire:click="limpiarFiltros" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    🗑️ Limpiar Filtros
                </button>
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

        <!-- INFORMACIÓN DE RESULTADOS -->
        <div class="px-4 py-2 bg-gray-100 border-b">
            <div class="text-sm text-gray-600">
                Showing {{ $productosRecibidos->firstItem() ?? 0 }} to {{ $productosRecibidos->lastItem() ?? 0 }}
                of {{ $productosRecibidos->total() }} results
                @if($filtroProducto)
                    | Filtrado por: "{{ $filtroProducto }}"
                @endif
            </div>
        </div>

        <!-- TABLA OPTIMIZADA -->
        <div class="px-4 py-3">
            @if($productosRecibidos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 table-auto">
                        <thead class="bg-gray-50">
                            <!-- Encabezados con ordenamiento -->
                            <tr>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('producto_nombre')">
                                    <div class="flex items-center space-x-1">
                                        <span>Producto</span>
                                        @if($ordenarPor === 'producto_nombre')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('codigo_barra')">
                                    <div class="flex items-center space-x-1">
                                        <span>Código</span>
                                        @if($ordenarPor === 'codigo_barra')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('marca_nombre')">
                                    <div class="flex items-center space-x-1">
                                        <span>Marca</span>
                                        @if($ordenarPor === 'marca_nombre')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('bodega_nombre')">
                                    <div class="flex items-center space-x-1">
                                        <span>Bodega</span>
                                        @if($ordenarPor === 'bodega_nombre')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b">Segmento</th>
                                <th class="px-4 py-3 text-left border-b">Sección</th>
                                <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('cantidad_disponible')">
                                    <div class="flex items-center justify-center space-x-1">
                                        <span>Stock</span>
                                        @if($ordenarPor === 'cantidad_disponible')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('fecha_recibido')">
                                    <div class="flex items-center justify-center space-x-1">
                                        <span>Fecha Recibido</span>
                                        @if($ordenarPor === 'fecha_recibido')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center border-b">Fecha Expiración</th>
                                <th class="px-4 py-3 text-center border-b">Estado</th>
                                <th class="px-4 py-3 text-center border-b">Comentario</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($productosRecibidos as $item)
                                <tr class="hover:bg-gray-50 cursor-pointer {{ $item->cantidad_disponible > 10 ? 'bg-green-50' : ($item->cantidad_disponible > 0 ? 'bg-yellow-50' : 'bg-red-50') }}"
                                    wire:click="editarProducto({{ $item->producto_id }})"
                                    title="Clic para editar producto">

                                    <!-- Producto -->
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-900">{{ $item->producto_nombre }}</span>
                                            @if($item->producto_descripcion)
                                                <small class="text-gray-500">{{ Str::limit($item->producto_descripcion, 30) }}</small>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Código -->
                                    <td class="px-4 py-3 border-b">
                                        <code class="px-2 py-1 text-sm bg-gray-100 rounded">{{ $item->codigo_barra ?? 'N/A' }}</code>
                                    </td>

                                    <!-- Marca -->
                                    <td class="px-4 py-3 text-gray-700 border-b">
                                        {{ $item->marca_nombre ?? 'Sin marca' }}
                                    </td>

                                    <!-- Bodega -->
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-900">{{ $item->bodega_nombre }}</span>
                                            <small class="text-gray-500">{{ $item->tienda_nombre }}</small>
                                        </div>
                                    </td>

                                    <!-- Segmento -->
                                    <td class="px-4 py-3 text-gray-700 border-b">
                                        {{ $item->segmento_descripcion ?? 'N/A' }}
                                    </td>

                                    <!-- Sección -->
                                    <td class="px-4 py-3 text-gray-700 border-b">
                                        {{ $item->seccion_descripcion ?? 'N/A' }}
                                    </td>

                                    <!-- Stock -->
                                    <td class="px-4 py-3 text-center border-b">
                                        <span class="font-bold {{ $item->cantidad_disponible > 10 ? 'text-green-600' : ($item->cantidad_disponible > 0 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($item->cantidad_disponible, 0) }}
                                        </span>
                                        <div class="text-xs text-gray-500">{{ $item->unidad_medida_venta ?? $item->unidad_medida ?? 'Unidad' }}</div>
                                    </td>

                                    <!-- Fecha Recibido -->
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 border-b">
                                        {{ \Carbon\Carbon::parse($item->fecha_recibido)->format('d/m/Y') }}
                                    </td>

                                    <!-- Fecha Expiración -->
                                    <td class="px-4 py-3 text-sm text-center border-b">
                                        @if($item->fecha_expiracion)
                                            @php
                                                $fechaExpiracion = \Carbon\Carbon::parse($item->fecha_expiracion);
                                                $diasRestantes = $fechaExpiracion->diffInDays(now(), false);
                                            @endphp
                                            <span class="{{ $diasRestantes > 30 ? 'text-green-600' : ($diasRestantes > 7 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $fechaExpiracion->format('d/m/Y') }}
                                                @if($diasRestantes <= 30)
                                                    <div class="text-xs">({{ abs($diasRestantes) }} días)</div>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>

                                    <!-- Estado -->
                                    <td class="px-4 py-3 text-center border-b">
                                        @if($item->cantidad_disponible > 10)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">Disponible</span>
                                        @elseif($item->cantidad_disponible > 0)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-yellow-500 rounded-full">Poco Stock</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded-full">Agotado</span>
                                        @endif
                                    </td>

                                    <!-- Comentario -->
                                    <td class="px-4 py-3 text-sm border-b">
                                        @if($item->comentario)
                                            <span title="{{ $item->comentario }}" class="text-gray-700">{{ Str::limit($item->comentario, 20) }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                        </svg>
                        <span class="text-xl font-medium text-gray-500">No hay productos recibidos en bodega</span>
                        <small class="mt-1 text-gray-400">Los productos aparecerán aquí cuando sean distribuidos a las bodegas</small>
                    </div>
                </div>
            @endif

            <!-- PAGINACIÓN PERSONALIZADA CON SELECTOR -->
            <div class="mt-4">
                @if($productosRecibidos->hasPages())
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-700">
                                Showing {{ $productosRecibidos->firstItem() }} to {{ $productosRecibidos->lastItem() }} of {{ $productosRecibidos->total() }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Show:</label>
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
                            @if($productosRecibidos->onFirstPage())
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Previous</span>
                            @else
                                <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</button>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $productosRecibidos->currentPage();
                                $lastPage = $productosRecibidos->lastPage();
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
                            @if($productosRecibidos->hasMorePages())
                                <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</button>
                            @else
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Next</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-700">
                            Showing {{ $productosRecibidos->firstItem() ?? 0 }} to {{ $productosRecibidos->lastItem() ?? 0 }} of {{ $productosRecibidos->total() }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Show:</label>
                            <select wire:model.live="registrosPorPagina" class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
