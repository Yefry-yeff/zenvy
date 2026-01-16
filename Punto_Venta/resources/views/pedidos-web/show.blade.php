<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pedidoa Web #{{ $pedido->numero_pedido }}
            </h2>
            <a href="{{ route('pedidos-web.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← Volver a la bandeja
            </a>
        </div>
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

            <!-- Estado y Acciones -->
            <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-2xl font-bold text-gray-900">{{ $pedido->numero_pedido }}</h3>
                                @if($pedido->estado === 'pendiente')
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold leading-5 text-yellow-800 bg-yellow-100 rounded-full">
                                        ⏱️ Pendiente
                                    </span>
                                @elseif($pedido->estado === 'procesando')
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full">
                                        🔄 Procesando
                                    </span>
                                @elseif($pedido->estado === 'facturado')
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold leading-5 text-green-800 bg-green-100 rounded-full">
                                        ✅ Facturado
                                    </span>
                                @else
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold leading-5 text-red-800 bg-red-100 rounded-full">
                                        ❌ Rechazado
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">Recibido: {{ $pedido->created_at->format('d/m/Y H:i:s') }} ({{ $pedido->created_at->diffForHumans() }})</p>

                            @if($pedido->factura_id)
                                <p class="mt-2 text-sm text-green-600">
                                    ✓ Facturado con ID: <strong>{{ $pedido->factura_id }}</strong> el {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>

                        @if($pedido->estado === 'pendiente')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('pedidos-web.process', $pedido->id) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded-md hover:bg-green-700">
                                        ✓ Procesar y Facturar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('pedidos-web.reject', $pedido->id) }}" onsubmit="return confirm('¿Rechazar este pedido?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700">
                                        ✗ Rechazar
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Información del Cliente -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800">👤 Información del Cliente</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->cliente_nombre }}</dd>
                            </div>
                            @if($pedido->cliente_email)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900">{{ $pedido->cliente_email }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_telefono)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                                    <dd class="text-sm text-gray-900">{{ $pedido->cliente_telefono }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_rtn)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">RTN</dt>
                                    <dd class="text-sm text-gray-900">{{ $pedido->cliente_rtn }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_direccion)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                    <dd class="text-sm text-gray-900">{{ $pedido->cliente_direccion }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Información del Pago -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800">💳 Información de Pago</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->metodo_pago ?? 'No especificado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                                <dd class="text-sm text-gray-900">L {{ number_format($pedido->subtotal, 2) }}</dd>
                            </div>
                            @if($pedido->descuento > 0)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Descuento</dt>
                                    <dd class="text-sm text-gray-900">L {{ number_format($pedido->descuento, 2) }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">ISV (15%)</dt>
                                <dd class="text-sm text-gray-900">L {{ number_format($pedido->isv, 2) }}</dd>
                            </div>
                            <div class="pt-2 border-t border-gray-200">
                                <dt class="text-base font-semibold text-gray-700">Total</dt>
                                <dd class="text-xl font-bold text-gray-900">L {{ number_format($pedido->total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="mb-4 text-lg font-semibold text-gray-800">📦 Productos del Pedido</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Producto</th>
                                    <th class="px-6 py-3 text-xs font-medium text-right text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-6 py-3 text-xs font-medium text-right text-gray-500 uppercase">Precio Unit.</th>
                                    <th class="px-6 py-3 text-xs font-medium text-right text-gray-500 uppercase">Subtotal</th>
                                    <th class="px-6 py-3 text-xs font-medium text-right text-gray-500 uppercase">ISV</th>
                                    <th class="px-6 py-3 text-xs font-medium text-right text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($pedido->items as $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->producto->nombre ?? "Producto ID {$item->producto_id}" }}
                                            </div>
                                            <div class="text-sm text-gray-500">ID: {{ $item->producto_id }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $item->cantidad }}</td>
                                        <td class="px-6 py-4 text-sm text-right text-gray-900">L {{ number_format($item->precio_unitario, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-right text-gray-900">L {{ number_format($item->subtotal, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-right text-gray-900">L {{ number_format($item->isv, 2) }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-right text-gray-900">L {{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notas -->
            @if($pedido->notas)
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="mb-2 text-lg font-semibold text-gray-800">📝 Notas</h4>
                        <p class="text-sm text-gray-700">{{ $pedido->notas }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
