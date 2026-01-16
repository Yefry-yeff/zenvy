<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Bandeja de Pedidos Web') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Pedidos Recibidos</h3>
                            <p class="text-sm text-gray-500">Solicitudes desde e-commerce pendientes de procesamiento</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 text-sm text-blue-800 bg-blue-100 rounded-full">
                                Total: {{ $pedidos->total() }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pedido</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Items</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($pedidos as $pedido)
                                    <tr class="hover:bg-gray-50 {{ !$pedido->leido ? 'bg-blue-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(!$pedido->leido)
                                                <span class="text-2xl">🔔</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $pedido->numero_pedido }}</div>
                                            <div class="text-sm text-gray-500">
                                                @if($pedido->estado === 'pendiente')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-yellow-800 bg-yellow-100 rounded-full">
                                                        Pendiente
                                                    </span>
                                                @elseif($pedido->estado === 'procesando')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full">
                                                        Procesando
                                                    </span>
                                                @elseif($pedido->estado === 'facturado')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">
                                                        Facturado
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-red-800 bg-red-100 rounded-full">
                                                        Rechazado
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $pedido->cliente_nombre }}</div>
                                            @if($pedido->cliente_email)
                                                <div class="text-sm text-gray-500">{{ $pedido->cliente_email }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $pedido->items->count() }} productos
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">L {{ number_format($pedido->total, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $pedido->created_at->format('d/m/Y H:i') }}
                                            <div class="text-xs text-gray-400">{{ $pedido->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                            <a href="{{ route('pedidos-web.show', $pedido->id) }}" class="text-blue-600 hover:text-blue-900">
                                                Ver detalless →
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="mt-2">No hay pedidos web pendientes</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pedidos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
