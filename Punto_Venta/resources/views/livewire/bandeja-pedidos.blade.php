<div class="p-6">
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">📦 Bandeja de Pedidos Web</h3>
                    <p class="text-sm text-gray-500">Solicitudes desde e-commerce pendientes de procesamiento</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                        Total: {{ $total }}
                    </span>
                </div>
            </div>

            <!-- Barra de herramientas: Búsqueda y Filtros -->
            <div class="mb-4 flex flex-wrap gap-3">
                <div class="flex-1 min-w-[250px]">
                    <input 
                        type="text" 
                        wire:model.live="buscar" 
                        placeholder="🔍 Buscar por pedido, cliente o email..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <select 
                    wire:model.live="filtroEstado" 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="procesando">Procesando</option>
                    <option value="facturado">Facturado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
                <input 
                    type="date" 
                    wire:model.live="filtroFecha" 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <select 
                    wire:model.live="registrosPorPagina" 
                    class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="5">5 por página</option>
                    <option value="10">10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="ordenar('numero_pedido')">
                                Pedido
                                @if($ordenarPor === 'numero_pedido')
                                    <span>{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cliente
                                <input 
                                    type="text" 
                                    wire:model.live="filtroCliente" 
                                    placeholder="Filtrar..." 
                                    class="mt-1 block w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="ordenar('total')">
                                Total
                                @if($ordenarPor === 'total')
                                    <span>{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="ordenar('created_at')">
                                Fecha
                                @if($ordenarPor === 'created_at')
                                    <span>{{ $direccionOrden === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pedidos as $pedido)
                            <tr class="hover:bg-gray-50 {{ !$pedido->leido ? 'bg-blue-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if(!$pedido->leido)
                                            <span class="text-2xl">🔔</span>
                                        @endif
                                        @if($pedido->estado === 'pendiente')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pendiente
                                            </span>
                                        @elseif($pedido->estado === 'procesando')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                Procesando
                                            </span>
                                        @elseif($pedido->estado === 'facturado')
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Facturado
                                            </span>
                                        @else
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Rechazado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $pedido->numero_pedido }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $pedido->cliente_nombre }}</div>
                                    @if($pedido->cliente_email)
                                        <div class="text-sm text-gray-500">{{ $pedido->cliente_email }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $pedido->items->count() }} productos
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">L {{ number_format($pedido->total, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $pedido->created_at->format('d/m/Y H:i') }}
                                    <div class="text-xs text-gray-400">{{ $pedido->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button 
                                        wire:click="$dispatch('cambiarVista', ['DetallePedido', {pedidoId: {{ $pedido->id }}}])" 
                                        class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                    >
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Ver Detalle
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2">No se encontraron pedidos con los filtros aplicados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($total > 0)
                <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-700">
                        Mostrando <span class="font-medium">{{ $desde }}</span>
                        a <span class="font-medium">{{ $hasta }}</span>
                        de <span class="font-medium">{{ $total }}</span> pedidos
                    </div>
                    <div class="flex gap-2">
                        <button 
                            wire:click="anteriorPagina" 
                            @if($paginaActual <= 1) disabled @endif
                            class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            ← Anterior
                        </button>
                        <span class="px-4 py-2 text-sm text-gray-700 bg-gray-50 rounded-md">
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
</div>
