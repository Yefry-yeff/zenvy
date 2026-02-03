<div class="min-h-screen bg-gray-50">
    @if(session('success'))
        <div class="mx-6 mt-6 px-6 py-4 bg-green-50 border border-green-200 rounded-lg shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mx-6 mt-6 px-6 py-4 bg-red-50 border border-red-200 rounded-lg shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-xl m-6">
        <div class="px-6 py-8 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">📦 Pedidos Web</h3>
                    <p class="mt-1 text-sm text-gray-600">Gestiona y procesa solicitudes desde tu e-commerce</p>
                </div>
                <div class="flex gap-3">
                    <span class="inline-flex items-center px-4 py-2 text-sm font-semibold text-indigo-800 bg-indigo-100 rounded-full">
                        📊 Total: {{ $total }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros -->
        <div class="px-6 py-6 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input 
                        type="text" 
                        wire:model.live="buscar" 
                        placeholder="Pedido, cliente o email..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select 
                        wire:model.live="filtroEstado" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="">Todos</option>
                        <option value="pendiente">⏱️ Pendiente</option>
                        <option value="procesando">🔄 Procesando</option>
                        <option value="facturado">✓ Facturado</option>
                        <option value="rechazado">✗ Rechazado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                    <input 
                        type="date" 
                        wire:model.live="filtroFecha" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Por página</label>
                    <select 
                        wire:model.live="registrosPorPagina" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
            @if($pedidos->count() > 0)
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 border-b border-gray-300">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors" wire:click="ordenar('numero_pedido')">
                                Pedido
                                @if($ordenarPor === 'numero_pedido')
                                    <span class="ml-1">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors" wire:click="ordenar('total')">
                                Total
                                @if($ordenarPor === 'total')
                                    <span class="ml-1">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors" wire:click="ordenar('created_at')">
                                Fecha
                                @if($ordenarPor === 'created_at')
                                    <span class="ml-1">{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pedidos as $pedido)
                            <tr class="hover:bg-blue-50 transition-colors {{ !$pedido->leido ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if(!$pedido->leido)
                                            <span class="inline-flex w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                                        @endif
                                        @if($pedido->estado === 'pendiente')
                                            <span class="px-3 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                                ⏱️ Pendiente
                                            </span>
                                        @elseif($pedido->estado === 'procesando')
                                            <span class="px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                                                🔄 Procesando
                                            </span>
                                        @elseif($pedido->estado === 'facturado')
                                            <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                                ✓ Facturado
                                            </span>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                ✗ Rechazado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">{{ $pedido->numero_pedido }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $pedido->cliente_nombre }}</div>
                                    @if($pedido->cliente_email)
                                        <div class="text-xs text-gray-500 mt-1">{{ $pedido->cliente_email }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-600 whitespace-nowrap">
                                    {{ $pedido->items->count() }} productos
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @php
                                        $costoEnvio = (is_array($pedido->metadata) && isset($pedido->metadata['shipping_cost'])) ? $pedido->metadata['shipping_cost'] : 0;
                                        $totalConEnvio = $pedido->total + $costoEnvio;
                                    @endphp
                                    <div class="text-base font-bold text-gray-900">L {{ number_format($totalConEnvio, 2) }}</div>
                                    @if($costoEnvio > 0)
                                        <div class="text-xs text-gray-500">Inc. envío: L {{ number_format($costoEnvio, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    <div class="font-medium">{{ $pedido->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $pedido->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <button 
                                        wire:click="$dispatch('cambiarVista', ['DetallePedido', {pedidoId: {{ $pedido->id }}}])" 
                                        class="inline-flex items-center gap-2 px-3 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors font-medium"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p class="text-gray-600 font-medium">No hay pedidos que coincidan</p>
                                        <p class="text-sm text-gray-400">Intenta cambiar los filtros de búsqueda</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-600 font-medium">Bandeja vacía</p>
                    <p class="text-sm text-gray-400">No hay pedidos pendientes de procesamiento</p>
                </div>
            @endif
        </div>

        <!-- Paginación -->
        @if($total > 0)
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Mostrando <span class="font-medium">{{ $desde }}</span> a 
                    <span class="font-medium">{{ $hasta }}</span> de 
                    <span class="font-medium">{{ $total }}</span> pedidos
                </div>
                <div class="flex gap-2">
                    <button 
                        wire:click="anteriorPagina" 
                        @if($paginaActual <= 1) disabled @endif
                        class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        ← Anterior
                    </button>
                    <span class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md">
                        Página {{ $paginaActual }} de {{ $ultimaPagina }}
                    </span>
                    <button 
                        wire:click="siguientePagina" 
                        @if($paginaActual >= $ultimaPagina) disabled @endif
                        class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Siguiente →
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
