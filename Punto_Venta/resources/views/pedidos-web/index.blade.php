<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Bandeja de Pedidos Web</h2>
                <p class="mt-1 text-sm text-gray-600">Gestiona y procesa pedidos desde tu e-commerce</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="px-6 py-4 mb-6 bg-green-50 border border-green-200 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="px-6 py-4 mb-6 bg-red-50 border border-red-200 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-lg rounded-xl">
                <div class="px-6 py-8 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Pedidos Pendientes</h3>
                            <p class="mt-1 text-sm text-gray-600">Solicitudes que requieren procesamiento</p>
                        </div>
                        <div class="flex gap-3">
                            <span class="inline-flex items-center px-4 py-2 text-sm font-semibold text-indigo-800 bg-indigo-100 rounded-full">
                                📊 Total: {{ $pedidos->total() }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="overflow-x-auto">
                        @if($pedidos->count() > 0)
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100 border-b border-gray-300">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Pedido</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Cliente</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Items</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-right text-gray-700 uppercase">Total</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase">Fecha</th>
                                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-700 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($pedidos as $pedido)
                                        <tr class="hover:bg-blue-50 transition-colors {{ !$pedido->leido ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if(!$pedido->leido)
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                                                        <span class="text-sm font-medium text-red-600">Nuevo</span>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900">{{ $pedido->numero_pedido }}</div>
                                                <div class="mt-1">
                                                    @if($pedido->estado === 'pendiente')
                                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                                            ⏱️ Pendiente
                                                        </span>
                                                    @elseif($pedido->estado === 'procesando')
                                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                                                            🔄 Procesando
                                                        </span>
                                                    @elseif($pedido->estado === 'facturado')
                                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                                            ✓ Facturado
                                                        </span>
                                                    @else
                                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                            ✗ Rechazado
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $pedido->cliente_nombre }}</div>
                                                @if($pedido->cliente_email)
                                                    <div class="text-xs text-gray-500 mt-1">{{ $pedido->cliente_email }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap font-medium">
                                                {{ $pedido->items->count() }} productos
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-base font-bold text-gray-900">L {{ number_format($pedido->total, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                                <div class="font-medium">{{ $pedido->created_at->format('d/m/Y') }}</div>
                                                <div class="text-xs text-gray-500">{{ $pedido->created_at->format('H:i') }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-center">
                                                <a href="{{ route('pedidos-web.show', $pedido->id) }}" class="inline-flex items-center gap-2 px-3 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                    </svg>
                                                    <p class="text-gray-500 font-medium">No hay pedidos pendientes</p>
                                                    <p class="text-sm text-gray-400">Todos los pedidos han sido procesados</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <div class="py-12 text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-600 font-medium">No hay pedidos en la bandeja</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($pedidos->count() > 0)
                <div class="mt-8 px-6 py-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <span class="font-semibold">💡 Consejo:</span> Los pedidos nuevos se destacan con un indicador rojo. Procesa los pendientes para mantener la bandeja actualizada.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
