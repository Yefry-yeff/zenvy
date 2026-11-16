<div>
    <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50">
        
        <!-- ENCABEZADO -->
        <div class="px-6 py-4 bg-white border-b shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">🔄 Reporte de Cambios de Unidades</h1>
                    <p class="mt-1 text-sm text-gray-600">Historial completo de conversiones de unidades de medida</p>
                </div>
                <button wire:click="descargarExcel" 
                        class="flex items-center px-4 py-2 space-x-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                    <span wire:loading.remove wire:target="descargarExcel">📥</span>
                    <span wire:loading wire:target="descargarExcel">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="descargarExcel">Exportar a Excel</span>
                    <span wire:loading wire:target="descargarExcel">Generando...</span>
                </button>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="p-6">
            <div class="p-6 mb-6 bg-white rounded-lg shadow-md">
                <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Filtro Producto -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Producto</label>
                        <input type="text" 
                               wire:model.live.debounce.300ms="filtroProducto"
                               placeholder="Buscar por nombre o código..."
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Filtro Usuario -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Usuario</label>
                        <select wire:model.live="filtroUsuario" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Todos los usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->usuario_id }}">{{ $usuario->usuario_nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro Bodega -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Bodega</label>
                        <input type="text" 
                               wire:model.live.debounce.300ms="filtroBodega"
                               placeholder="Buscar bodega..."
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Filtro Unidad Original -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Unidad Original</label>
                        <select wire:model.live="filtroUnidadOriginal" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Todas</option>
                            @foreach($unidadesMedida as $unidad)
                                <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro Unidad Nueva -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Unidad Nueva</label>
                        <select wire:model.live="filtroUnidadNueva" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Todas</option>
                            @foreach($unidadesMedida as $unidad)
                                <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha Inicio -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Fecha Inicio</label>
                        <input type="date" 
                               wire:model.live="filtroFechaInicio"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Fecha Fin -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Fecha Fin</label>
                        <input type="date" 
                               wire:model.live="filtroFechaFin"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Botón Limpiar Filtros -->
                <div class="flex justify-end">
                    <button wire:click="limpiarFiltros" 
                            class="flex items-center px-4 py-2 space-x-2 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Limpiar Filtros</span>
                    </button>
                </div>
            </div>

            <!-- INFORMACIÓN DE RESULTADOS -->
            <div class="px-4 py-3 mb-4 border rounded-lg bg-purple-50 border-purple-200">
                <div class="text-sm font-medium text-purple-900">
                    🔄 Mostrando {{ $cambios->firstItem() ?? 0 }} a {{ $cambios->lastItem() ?? 0 }} 
                    de {{ $cambios->total() }} conversiones
                </div>
            </div>

            <!-- TABLA DE CAMBIOS -->
            <div class="overflow-hidden bg-white rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gradient-to-r from-purple-600 to-indigo-600">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b cursor-pointer hover:bg-purple-700"
                                    wire:click="ordenar('created_at')">
                                    <div class="flex items-center space-x-1">
                                        <span>Fecha y Hora</span>
                                        @if($ordenarPor === 'created_at')
                                            <span class="text-yellow-300">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b">Producto</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b">Bodega / Sección</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">De → A</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Cantidad Rebajada</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Cantidad Convertida</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Factor</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b cursor-pointer hover:bg-purple-700"
                                    wire:click="ordenar('usuario_nombre')">
                                    <div class="flex items-center space-x-1">
                                        <span>Usuario</span>
                                        @if($ordenarPor === 'usuario_nombre')
                                            <span class="text-yellow-300">
                                                @if($direccionOrden === 'asc') ↑ @else ↓ @endif
                                            </span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if($cambios->count() > 0)
                                @foreach($cambios as $cambio)
                                    <tr class="transition hover:bg-purple-50">
                                        <!-- Fecha y Hora -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">
                                                {{ \Carbon\Carbon::parse($cambio->fecha_cambio)->format('d/m/Y') }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ \Carbon\Carbon::parse($cambio->fecha_cambio)->format('h:i A') }}
                                            </div>
                                        </td>

                                        <!-- Producto -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">{{ $cambio->producto_nombre }}</div>
                                            <div class="text-gray-500">Cód: {{ $cambio->producto_id }}</div>
                                        </td>

                                        <!-- Bodega / Sección -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">{{ $cambio->bodega_nombre }}</div>
                                            <div class="text-gray-500">{{ $cambio->seccion_nombre }}</div>
                                        </td>

                                        <!-- De → A -->
                                        <td class="px-4 py-3 text-center border-b">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded">
                                                    {{ $cambio->unidad_original }}
                                                </span>
                                                <span class="text-gray-400">→</span>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded">
                                                    {{ $cambio->unidad_nueva }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Cantidad Rebajada -->
                                        <td class="px-4 py-3 text-xs font-bold text-center text-red-600 border-b">
                                            - {{ number_format($cambio->cantidad_rebajada, 2) }}
                                            <div class="text-xs text-gray-500">{{ $cambio->unidad_original }}</div>
                                        </td>

                                        <!-- Cantidad Convertida -->
                                        <td class="px-4 py-3 text-xs font-bold text-center text-green-600 border-b">
                                            + {{ number_format($cambio->cantidad_convertida, 2) }}
                                            <div class="text-xs text-gray-500">{{ $cambio->unidad_nueva }}</div>
                                        </td>

                                        <!-- Factor de Conversión -->
                                        <td class="px-4 py-3 text-xs font-semibold text-center border-b text-purple-600">
                                            @if($cambio->factor_conversion)
                                                {{ number_format($cambio->factor_conversion, 4) }}
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>

                                        <!-- Usuario -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="flex items-center space-x-2">
                                                <div class="flex items-center justify-center w-8 h-8 text-white rounded-full bg-purple-500">
                                                    <span class="text-xs font-bold">{{ substr($cambio->usuario_nombre, 0, 1) }}</span>
                                                </div>
                                                <span class="font-medium text-gray-900">{{ $cambio->usuario_nombre }}</span>
                                            </div>
                                        </td>

                                        <!-- Motivo -->
                                        <td class="px-4 py-3 text-xs text-gray-700 border-b">
                                            <div class="max-w-xs" title="{{ $cambio->motivo }}">
                                                {{ Str::limit($cambio->motivo ?? 'N/A', 60) }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                            </svg>
                                            <span class="text-xl font-medium text-gray-500">No se encontraron cambios de unidades</span>
                                            <small class="mt-1 text-gray-400">Intenta ajustar los filtros de búsqueda</small>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <div class="px-4 py-3 bg-gray-50 border-t">
                    {{ $cambios->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
