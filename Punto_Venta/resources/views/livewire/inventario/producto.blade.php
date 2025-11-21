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
            <h5 class="mb-0 text-lg">📦 Gestión de Productos</h5>
            <div class="flex gap-2">
                <button wire:click="sincronizarProductosValencia"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-orange-200 rounded hover:bg-orange-300 disabled:opacity-50"
                    wire:loading.attr="disabled"
                    wire:target="sincronizarProductosValencia">
                    <span wire:loading.remove wire:target="sincronizarProductosValencia">🔄</span>
                    <span wire:loading wire:target="sincronizarProductosValencia">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="sincronizarProductosValencia">Sincronizar</span>
                    <span wire:loading wire:target="sincronizarProductosValencia">Sincronizando...</span>
                </button>

                <button wire:click="abrirModalCrear"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>➕</span> Nuevo Producto
                </button>

                <!-- Botones de descarga -->
                <!-- Botón de descarga de reporte (Excel) junto al filtro de Origen -->
                <!-- ...existing code... -->
            </div>
        </div>

        <!-- BARRA DE PROGRESO -->
        @if($sincronizandoValencia)
        <div class="px-5 py-3 bg-blue-50">
            <div class="mb-2">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-blue-700">Sincronizando productos de Valencia...</span>
                    <span class="text-blue-600">{{ $progreso }}%</span>
                </div>
            </div>
            <div class="w-full h-2 bg-blue-200 rounded-full">
                <div class="h-2 transition-all duration-500 ease-out bg-blue-600 rounded-full"
                     style="width: {{ $progreso }}%"></div>
            </div>
        </div>
        @endif

        <!-- FILTROS Y BÚSQUEDA -->
        <div class="px-4 py-3 border-b bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Búsqueda -->
                <div class="md:col-span-2">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="buscar"
                           placeholder="Buscar por nombre, código o descripción..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Filtro de Origen -->
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Origen</label>
                        <select wire:model.live="filtroOrigen" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            <option value="todos">Todos</option>
                            <option value="paperland">🏠 Paperland</option>
                            <option value="valencia">🏢 Valencia</option>
                        </select>
                    </div>
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
        </div>

        <!-- INFORMACIÓN DE RESULTADOS -->
        <div class="px-4 py-2 bg-gray-100 border-b">
            <div class="text-sm text-gray-600">
                Showing {{ $productos->firstItem() ?? 0 }} to {{ $productos->lastItem() ?? 0 }}
                of {{ $productos->total() }} results
                @if($buscar)
                    | Filtrado por: "{{ $buscar }}"
                @endif
            </div>
        </div>

        <!-- TABLA OPTIMIZADA -->
        <div class="px-4 py-3">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 table-auto">
                        <thead class="bg-gray-50">
                            <!-- Encabezados con ordenamiento -->
                            <tr>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('id')">
                                    <div class="flex items-center space-x-1">
                                        <span>Código Producto</span>
                                        @if($ordenarPor === 'id')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('nombre')">
                                    <div class="flex items-center space-x-1">
                                        <span>Producto</span>
                                        @if($ordenarPor === 'nombre')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b">
                                    <div class="flex items-center space-x-1">
                                        <span>Código de Barras</span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b">
                                    <div class="flex items-center space-x-1">
                                        <span>Unidad de Medida</span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('subcategoria_id')">
                                    <div class="flex items-center space-x-1">
                                        <span>Categoría</span>
                                        @if($ordenarPor === 'subcategoria_id')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('marca_id')">
                                    <div class="flex items-center space-x-1">
                                        <span>Marca</span>
                                        @if($ordenarPor === 'marca_id')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('producto_valencia')">
                                    <div class="flex items-center justify-center space-x-1">
                                        <span>Origen</span>
                                        @if($ordenarPor === 'producto_valencia')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center border-b">Acciones</th>
                            </tr>
                            <!-- Fila de filtros -->
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 border-b">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroId"
                                           placeholder="Filtrar ID..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroNombre"
                                           placeholder="Filtrar..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroCodigoBarras"
                                           placeholder="Filtrar código..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroUnidadMedida"
                                           placeholder="Filtrar unidad..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroCategoria"
                                           placeholder="Filtrar..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="filtroMarca"
                                           placeholder="Filtrar..."
                                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <!-- Sin filtro para origen -->
                                </th>
                                <th class="px-4 py-2 border-b">
                                    <!-- Sin filtro para acciones -->
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @if($productos->count() > 0)
                                @foreach($productos as $producto)
                                    @php
                                        $numPresentaciones = !empty($producto->presentaciones) ? count($producto->presentaciones) : 1;
                                        $primeraPresentacion = true;
                                    @endphp
                                    
                                    @if(!empty($producto->presentaciones) && count($producto->presentaciones) > 0)
                                        @foreach($producto->presentaciones as $index => $presentacion)
                                        <tr class="transition-colors duration-150 cursor-pointer hover:bg-gray-50"
                                            wire:key="producto-{{ $producto->id }}-presentacion-{{ $index }}">
                                            @if($index === 0)
                                                <!-- Código del Producto (ID) - solo en primera fila -->
                                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 border-r" 
                                                    rowspan="{{ $numPresentaciones }}"
                                                    wire:click="editar({{ $producto->id }})">
                                                    #{{ $producto->id }}
                                                </td>
                                                <!-- Producto - solo en primera fila -->
                                                <td class="px-4 py-3 border-r" 
                                                    rowspan="{{ $numPresentaciones }}"
                                                    wire:click="editar({{ $producto->id }})">
                                                    <div>
                                                        <div class="font-medium text-gray-900">{{ $producto->nombre }}</div>
                                                        @if($producto->descripcion)
                                                            <div class="max-w-xs text-sm text-gray-500 truncate">
                                                                {{ Str::limit($producto->descripcion, 60) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                            <!-- Código de Barras -->
                                            <td class="px-4 py-3 text-sm text-gray-700" wire:click="editar({{ $producto->id }})">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-100 text-blue-800">
                                                    {{ $presentacion->codigo_barra }}
                                                </span>
                                            </td>
                                            <!-- Unidad de Medida -->
                                            <td class="px-4 py-3 text-sm text-gray-700" wire:click="editar({{ $producto->id }})">
                                                @if($presentacion->unidad_medida)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $presentacion->unidad_medida }}
                                                        @if($presentacion->unidad_simbolo)
                                                            ({{ $presentacion->unidad_simbolo }})
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 text-xs">N/A</span>
                                                @endif
                                            </td>
                                            @if($index === 0)
                                                <!-- Categoría - solo en primera fila -->
                                                <td class="px-4 py-3 text-sm text-gray-700 border-l" 
                                                    rowspan="{{ $numPresentaciones }}"
                                                    wire:click="editar({{ $producto->id }})">
                                                    <div>
                                                        <div class="font-medium">{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</div>
                                                        <div class="text-xs text-gray-500">{{ $producto->subcategoria->nombre ?? '' }}</div>
                                                    </div>
                                                </td>
                                                <!-- Marca - solo en primera fila -->
                                                <td class="px-4 py-3 text-sm text-gray-700" 
                                                    rowspan="{{ $numPresentaciones }}"
                                                    wire:click="editar({{ $producto->id }})">
                                                    {{ $producto->marca->nombre ?? 'Sin marca' }}
                                                </td>
                                                <!-- Origen - solo en primera fila -->
                                                <td class="px-4 py-3 text-center" 
                                                    rowspan="{{ $numPresentaciones }}"
                                                    wire:click="editar({{ $producto->id }})">
                                                    @if($producto->producto_valencia)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                            🏢 Valencia
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            🏠 Paperland
                                                        </span>
                                                    @endif
                                                </td>
                                                <!-- Acciones - solo en primera fila -->
                                                <td class="px-4 py-3 text-center" rowspan="{{ $numPresentaciones }}">
                                                    @if(!$producto->producto_valencia)
                                                        <button type="button"
                                                                class="p-1 text-red-600 transition-colors hover:text-red-800"
                                                                wire:click="confirmarEliminar({{ $producto->id }})"
                                                                onclick="event.stopPropagation();"
                                                                title="Eliminar producto">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    @else
                                                        <span class="text-xs text-gray-400">Solo lectura</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    @else
                                        <!-- Producto sin presentaciones -->
                                        <tr class="transition-colors duration-150 cursor-pointer hover:bg-gray-50"
                                            wire:key="producto-{{ $producto->id }}-sin-presentacion">
                                            <!-- Código del Producto (ID) -->
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" wire:click="editar({{ $producto->id }})">
                                                #{{ $producto->id }}
                                            </td>
                                            <!-- Producto -->
                                            <td class="px-4 py-3" wire:click="editar({{ $producto->id }})">
                                                <div>
                                                    <div class="font-medium text-gray-900">{{ $producto->nombre }}</div>
                                                    @if($producto->descripcion)
                                                        <div class="max-w-xs text-sm text-gray-500 truncate">
                                                            {{ Str::limit($producto->descripcion, 60) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <!-- Sin Código de Barras -->
                                            <td class="px-4 py-3 text-sm text-gray-400" wire:click="editar({{ $producto->id }})">
                                                Sin código
                                            </td>
                                            <!-- Sin Unidad de Medida -->
                                            <td class="px-4 py-3 text-sm text-gray-400" wire:click="editar({{ $producto->id }})">
                                                N/A
                                            </td>
                                            <!-- Categoría -->
                                            <td class="px-4 py-3 text-sm text-gray-700" wire:click="editar({{ $producto->id }})">
                                                <div>
                                                    <div class="font-medium">{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</div>
                                                    <div class="text-xs text-gray-500">{{ $producto->subcategoria->nombre ?? '' }}</div>
                                                </div>
                                            </td>
                                            <!-- Marca -->
                                            <td class="px-4 py-3 text-sm text-gray-700" wire:click="editar({{ $producto->id }})">
                                                {{ $producto->marca->nombre ?? 'Sin marca' }}
                                            </td>
                                            <!-- Origen -->
                                            <td class="px-4 py-3 text-center" wire:click="editar({{ $producto->id }})">
                                                @if($producto->producto_valencia)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        🏢 Valencia
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        🏠 Paperland
                                                    </span>
                                                @endif
                                            </td>
                                            <!-- Acciones -->
                                            <td class="px-4 py-3 text-center">
                                                @if(!$producto->producto_valencia)
                                                    <button type="button"
                                                            class="p-1 text-red-600 transition-colors hover:text-red-800"
                                                            wire:click="confirmarEliminar({{ $producto->id }})"
                                                            onclick="event.stopPropagation();"
                                                            title="Eliminar producto">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400">Solo lectura</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                        @else
                            <!-- Mensaje cuando no hay productos -->
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="mb-4 text-6xl text-gray-400">📦</div>
                                    <h3 class="mb-2 text-lg font-medium text-gray-900">No se encontraron productos</h3>
                                    <p class="mb-4 text-gray-500">
                                        @if($buscar || $filtroOrigen !== 'todos' || $filtroNombre || $filtroCodigo || $filtroCategoria || $filtroMarca || $filtroPrecio)
                                            No hay productos que coincidan con los filtros aplicados
                                        @else
                                            No hay productos registrados en el sistema
                                        @endif
                                    </p>
                                    @if($buscar || $filtroOrigen !== 'todos' || $filtroNombre || $filtroCodigo || $filtroCategoria || $filtroMarca || $filtroPrecio)
                                        <button wire:click="limpiarFiltros"
                                                class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600">
                                            Limpiar filtros
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN PERSONALIZADA CON SELECTOR -->
                <div class="mt-4">
                    @if($productos->hasPages())
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-700">
                                    Showing {{ $productos->firstItem() }} to {{ $productos->lastItem() }} of {{ $productos->total() }} results
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
                                @if($productos->onFirstPage())
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Previous</span>
                                @else
                                    <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</button>
                                @endif

                                {{-- Pagination Elements --}}
                                @php
                                    $currentPage = $productos->currentPage();
                                    $lastPage = $productos->lastPage();
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
                                @if($productos->hasMorePages())
                                    <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</button>
                                @else
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-200 border border-gray-300 rounded cursor-not-allowed">Next</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-700">
                                Showing {{ $productos->firstItem() ?? 0 }} to {{ $productos->lastItem() ?? 0 }} of {{ $productos->total() }} results
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
    </div>

    <!-- Modal Confirmar Eliminación (simplificado) -->
    @if($modalEliminarAbierto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         wire:click.self="cerrarModalEliminar">
        <div class="w-full max-w-md p-6 mx-4 bg-white rounded-lg shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-red-600">⚠️ Confirmar Eliminación</h3>
                <button wire:click="cerrarModalEliminar" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($productoSeleccionado)
                <div class="p-3 mb-4 rounded bg-gray-50">
                    <h6 class="font-medium">{{ $productoSeleccionado->nombre ?? '' }}</h6>
                    <p class="text-sm text-gray-600">{{ $productoSeleccionado->codigo_barra ?? 'Sin código' }}</p>
                </div>

                @if($puedeEliminar)
                    <p class="mb-4 text-gray-700">¿Estás seguro que deseas eliminar este producto?</p>
                    <div class="flex justify-end gap-2">
                        <button wire:click="cerrarModalEliminar" class="px-4 py-2 text-gray-700 bg-gray-300 rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button wire:click="eliminarProducto" class="px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600">
                            Eliminar
                        </button>
                    </div>
                @else
                    <div class="mb-4 text-red-600">
                        <p class="font-medium">No se puede eliminar este producto:</p>
                        <ul class="mt-2 space-y-1 text-sm">
                            @if($tieneCodigoBarras)<li>• Tiene código de barras asignado</li>@endif
                            @if($stockDisponible > 0)<li>• Tiene stock disponible ({{ $stockDisponible }})</li>@endif
                            @if($tieneComprasActivas)<li>• Tiene compras pendientes</li>@endif
                        </ul>
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="cerrarModalEliminar" class="px-4 py-2 text-gray-700 bg-gray-300 rounded hover:bg-gray-400">
                            Cerrar
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endif

    <!-- Modal Detalles de Sincronización -->
    @if($detallesSincronizacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="cerrarDetallesSincronizacion"></div>
            <div class="relative w-full max-w-md p-6 mx-4 bg-white rounded-lg shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Sincronización Completada</h3>
                    <button wire:click="cerrarDetallesSincronizacion" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded bg-green-50">
                        <span class="font-medium text-green-800">✅ Procesados:</span>
                        <span class="font-bold text-green-600">{{ $detallesSincronizacion['productos_sincronizados'] ?? 0 }}</span>
                    </div>

                    @if(($detallesSincronizacion['errores'] ?? 0) > 0)
                        <div class="flex items-center justify-between p-3 rounded bg-red-50">
                            <span class="font-medium text-red-800">❌ Errores:</span>
                            <span class="font-bold text-red-600">{{ $detallesSincronizacion['errores'] }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-6 text-center">
                    <button wire:click="cerrarDetallesSincronizacion"
                            class="px-4 py-2 text-white transition-colors bg-blue-600 rounded hover:bg-blue-700">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
// Escuchar el evento de descarga de archivos
document.addEventListener('DOMContentLoaded', function() {
    // Para Livewire v3
    if (typeof Livewire !== 'undefined') {
        Livewire.on('descargarArchivo', (data) => {
            console.log('Evento descargarArchivo recibido:', data);
            const eventData = Array.isArray(data) ? data[0] : data;
            const { url, filename } = eventData;

            console.log('URL de descarga:', url);

            // Crear un enlace temporal para descargar el archivo
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';

            // Agregar al DOM, hacer click y remover
            document.body.appendChild(link);
            link.click();

            // Remover después de un pequeño delay
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
        });
    }
});

// También intentar escuchar cuando Livewire esté completamente cargado
document.addEventListener('livewire:init', () => {
    Livewire.on('descargarArchivo', (data) => {
        console.log('Evento descargarArchivo recibido (livewire:init):', data);
        const eventData = Array.isArray(data) ? data[0] : data;
        const { url, filename } = eventData;

        // Crear un enlace temporal para descargar el archivo
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';

        // Agregar al DOM, hacer click y remover
        document.body.appendChild(link);
        link.click();

        setTimeout(() => {
            document.body.removeChild(link);
        }, 100);
    });
});
</script>
