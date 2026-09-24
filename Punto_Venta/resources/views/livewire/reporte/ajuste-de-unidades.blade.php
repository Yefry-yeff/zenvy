<div>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
        
        <!-- ENCABEZADO -->
        <div class="px-6 py-4 bg-white border-b shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📊 Reporte de Ajustes de Unidades</h1>
                    <p class="mt-1 text-sm text-gray-600">Historial completo de ajustes realizados al inventario</p>
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
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Filtro Usuario -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Usuario</label>
                        <select wire:model.live="filtroUsuario" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->usuario_id }}">{{ $usuario->usuario_nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro Tipo de Ajuste -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Tipo de Ajuste</label>
                        <select wire:model.live="filtroTipoAjuste" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="aumentar">Aumentar ⬆️</option>
                            <option value="disminuir">Disminuir ⬇️</option>
                        </select>
                    </div>

                    <!-- Filtro Bodega -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Bodega</label>
                        <input type="text" 
                               wire:model.live.debounce.300ms="filtroBodega"
                               placeholder="Buscar bodega..."
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Fecha Inicio -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Fecha Inicio</label>
                        <input type="date" 
                               wire:model.live="filtroFechaInicio"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Fecha Fin -->
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Fecha Fin</label>
                        <input type="date" 
                               wire:model.live="filtroFechaFin"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
            <div class="px-4 py-3 mb-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="text-sm font-medium text-blue-900">
                    📋 Mostrando {{ $ajustes->firstItem() ?? 0 }} a {{ $ajustes->lastItem() ?? 0 }} 
                    de {{ $ajustes->total() }} registros
                </div>
            </div>

            <!-- TABLA DE AJUSTES -->
            <div class="overflow-hidden bg-white rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gradient-to-r from-blue-600 to-purple-600">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b cursor-pointer hover:bg-blue-700"
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
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Tipo Ajuste</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Cantidad Anterior</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Cantidad Ajustada</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-white border-b">Cantidad Nueva</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-white border-b cursor-pointer hover:bg-blue-700"
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
                            @if($ajustes->count() > 0)
                                @foreach($ajustes as $ajuste)
                                    <tr class="transition hover:bg-gray-50">
                                        <!-- Fecha y Hora -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">
                                                {{ \Carbon\Carbon::parse($ajuste->fecha_ajuste)->format('d/m/Y') }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ \Carbon\Carbon::parse($ajuste->fecha_ajuste)->format('h:i A') }}
                                            </div>
                                        </td>

                                        <!-- Producto -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">{{ $ajuste->producto_nombre }}</div>
                                            <div class="text-gray-500">Cód: {{ $ajuste->producto_id }}</div>
                                        </td>

                                        <!-- Bodega / Sección -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="font-semibold text-gray-900">{{ $ajuste->bodega_nombre }}</div>
                                            <div class="text-gray-500">{{ $ajuste->seccion_nombre }}</div>
                                        </td>

                                        <!-- Tipo de Ajuste -->
                                        <td class="px-4 py-3 text-center border-b">
                                            @if($ajuste->tipo_ajuste === 'aumentar')
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">
                                                    ⬆️ Aumentar
                                                </span>
                                            @else
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold text-white bg-red-500 rounded-full">
                                                    ⬇️ Disminuir
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Cantidad Anterior -->
                                        <td class="px-4 py-3 text-xs font-bold text-center text-gray-700 border-b">
                                            {{ number_format($ajuste->cantidad_anterior, 2) }}
                                            <div class="text-xs text-gray-500">{{ $ajuste->unidad_medida }}</div>
                                        </td>

                                        <!-- Cantidad Ajustada -->
                                        <td class="px-4 py-3 text-center border-b">
                                            <span class="text-xs font-bold {{ $ajuste->tipo_ajuste === 'aumentar' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $ajuste->tipo_ajuste === 'aumentar' ? '+' : '-' }}{{ number_format($ajuste->cantidad_ajustada, 2) }}
                                            </span>
                                        </td>

                                        <!-- Cantidad Nueva -->
                                        <td class="px-4 py-3 text-xs font-bold text-center border-b {{ $ajuste->tipo_ajuste === 'aumentar' ? 'text-green-600' : 'text-blue-600' }}">
                                            {{ number_format($ajuste->cantidad_nueva, 2) }}
                                            <div class="text-xs text-gray-500">{{ $ajuste->unidad_medida }}</div>
                                        </td>

                                        <!-- Usuario -->
                                        <td class="px-4 py-3 text-xs border-b">
                                            <div class="flex items-center space-x-2">
                                                <div class="flex items-center justify-center w-8 h-8 text-white bg-blue-500 rounded-full">
                                                    <span class="text-xs font-bold">{{ substr($ajuste->usuario_nombre, 0, 1) }}</span>
                                                </div>
                                                <span class="font-medium text-gray-900">{{ $ajuste->usuario_nombre }}</span>
                                            </div>
                                        </td>

                                        <!-- Motivo -->
                                        <td class="px-4 py-3 text-xs text-gray-700 border-b">
                                            <div class="max-w-xs" title="{{ $ajuste->motivo }}">
                                                {{ Str::limit($ajuste->motivo, 60) }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-xl font-medium text-gray-500">No se encontraron ajustes</span>
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
                    {{ $ajustes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
