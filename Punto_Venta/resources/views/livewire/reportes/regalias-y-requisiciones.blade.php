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

    <!-- CONTENEDOR PRINCIPAL -->
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
            <h5 class="mb-0 text-lg">🎁 Historial de Regalías y Requisiciones (Bodega 2)</h5>
            <div class="flex items-center gap-2">
                <span class="text-sm opacity-90">Total: {{ $historial->total() }}</span>
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

                <!-- Filtro de Proveedor -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Proveedor</label>
                    <select wire:model.live="filtroProveedor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Usuario -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Usuario</label>
                    <select wire:model.live="filtroUsuario" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los usuarios</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Segmento -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Segmento</label>
                    <select wire:model.live="filtroSegmento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los segmentos</option>
                        @foreach($segmentos as $segmento)
                            <option value="{{ $segmento->id }}">{{ $segmento->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Sección -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Sección</label>
                    <select wire:model.live="filtroSeccion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las secciones</option>
                        @foreach($secciones as $seccion)
                            <option value="{{ $seccion->id }}">{{ $seccion->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Fecha Desde -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Desde</label>
                    <input type="date"
                           wire:model.live="filtroFechaDesde"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filtro de Fecha Hasta -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Hasta</label>
                    <input type="date"
                           wire:model.live="filtroFechaHasta"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
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
                    <span>Descargar Excel</span>
                </button>
            </div>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3">
            @if($historial->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 table-auto">
                        <thead class="bg-gray-50">
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
                                <th class="px-4 py-3 text-left border-b">Código</th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('proveedor')">
                                    <div class="flex items-center space-x-1">
                                        <span>Origen/Proveedor</span>
                                        @if($ordenarPor === 'proveedor')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b">Segmento</th>
                                <th class="px-4 py-3 text-left border-b">Sección</th>
                                <th class="px-4 py-3 text-center border-b">Cantidad Inicial</th>
                                <th class="px-4 py-3 text-center border-b">Cantidad Actual</th>
                                <th class="px-4 py-3 text-center border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('fecha_recibido')">
                                    <div class="flex items-center justify-center space-x-1">
                                        <span>Fecha Ingreso</span>
                                        @if($ordenarPor === 'fecha_recibido')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b cursor-pointer hover:bg-gray-100"
                                    wire:click="ordenar('usuario_registro')">
                                    <div class="flex items-center space-x-1">
                                        <span>Usuario</span>
                                        @if($ordenarPor === 'usuario_registro')
                                            <span class="text-blue-500">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left border-b">Comentario</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($historial as $item)
                                <tr class="hover:bg-gray-50">
                                    <!-- Producto -->
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-900">{{ $item->producto_nombre }}</span>
                                            @if($item->producto_descripcion)
                                                <small class="text-gray-500">{{ Str::limit($item->producto_descripcion, 30) }}</small>
                                            @endif
                                            @if($item->marca_nombre)
                                                <small class="text-blue-600">{{ $item->marca_nombre }}</small>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Código -->
                                    <td class="px-4 py-3 border-b">
                                        <code class="px-2 py-1 text-sm bg-gray-100 rounded">{{ $item->codigo_barra ?? 'N/A' }}</code>
                                    </td>

                                    <!-- Origen/Proveedor -->
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900">{{ $item->origen }}</span>
                                            @if($item->proveedor)
                                                <small class="text-gray-600">{{ $item->proveedor }}</small>
                                            @endif
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

                                    <!-- Cantidad Inicial -->
                                    <td class="px-4 py-3 text-center border-b">
                                        <span class="font-bold text-blue-600">
                                            {{ number_format($item->cantidad_inicial_seccion, 0) }}
                                        </span>
                                        <div class="text-xs text-gray-500">{{ $item->unidad_medida_venta ?? $item->unidad_medida ?? 'Unidad' }}</div>
                                    </td>

                                    <!-- Cantidad Actual -->
                                    <td class="px-4 py-3 text-center border-b">
                                        <span class="font-bold {{ $item->cantidad_disponible > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($item->cantidad_disponible, 0) }}
                                        </span>
                                        <div class="text-xs text-gray-500">{{ $item->unidad_medida_venta ?? $item->unidad_medida ?? 'Unidad' }}</div>
                                    </td>

                                    <!-- Fecha Ingreso -->
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 border-b">
                                        {{ \Carbon\Carbon::parse($item->fecha_recibido)->format('d/m/Y') }}
                                        <div class="text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}
                                        </div>
                                    </td>

                                    <!-- Usuario -->
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex items-center">
                                            <div class="flex items-center justify-center w-8 h-8 mr-2 text-white bg-blue-500 rounded-full">
                                                <span class="text-xs font-bold">{{ substr($item->usuario_registro, 0, 2) }}</span>
                                            </div>
                                            <span class="text-sm text-gray-700">{{ $item->usuario_registro }}</span>
                                        </div>
                                    </td>

                                    <!-- Comentario -->
                                    <td class="px-4 py-3 text-sm border-b">
                                        @if($item->comentario)
                                            <span title="{{ $item->comentario }}" class="text-gray-700">{{ Str::limit($item->comentario, 30) }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <div class="px-4 py-3 border-t">
                    {{ $historial->links() }}
                </div>
            @else
                <div class="py-12 text-center">
                    <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                        </svg>
                        <span class="text-xl font-medium text-gray-500">No hay registros en el historial</span>
                        <small class="mt-1 text-gray-400">Los productos aparecerán aquí cuando sean ingresados a la bodega 2</small>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
