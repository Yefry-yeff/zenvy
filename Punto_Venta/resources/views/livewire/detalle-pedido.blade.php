<div class="p-6">
    @if(!$pedido)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            Pedido no encontrado
        </div>
        <button wire:click="volverABandeja" class="mt-4 text-blue-600 hover:text-blue-800">
            ← Volver a la bandeja
        </button>
    @else
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

        <!-- Botón volver -->
        <div class="mb-4">
            <button wire:click="volverABandeja" class="text-sm text-blue-600 hover:text-blue-800">
                ← Volver a la bandeja
            </button>
        </div>

        <!-- Estado y Acciones -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $pedido->numero_pedido }}</h3>
                            @if($pedido->estado === 'pendiente')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    ⏱️ Pendiente
                                </span>
                            @elseif($pedido->estado === 'procesando')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    🔄 Procesando
                                </span>
                            @elseif($pedido->estado === 'facturado')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    ✅ Facturado
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    ❌ Rechazado
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">Recibido: {{ $pedido->created_at->format('d/m/Y H:i:s') }} ({{ $pedido->created_at->diffForHumans() }})</p>
                        
                        @if($pedido->factura_id)
                            <p class="text-sm text-green-600 mt-2">
                                ✓ Facturado con ID: <strong>{{ $pedido->factura_id }}</strong> 
                                @if($pedido->fecha_facturado)
                                    el {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        @endif
                    </div>

                    @if($pedido->estado === 'pendiente')
                        <div class="flex gap-2">
                            <button wire:click="procesarPedido" wire:confirm="¿Procesar y facturar este pedido?" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                ✓ Procesar y Facturar
                            </button>
                            <button wire:click="rechazarPedido" wire:confirm="¿Rechazar este pedido?" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                ✗ Rechazar
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Información del Cliente -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">👤 Información del Cliente</h4>
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">💳 Información de Pago</h4>
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
        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">📦 Productos del Pedido</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ISV</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
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
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">{{ $item->cantidad }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->precio_unitario, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->subtotal, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->isv, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">L {{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notas -->
        @if($pedido->notas)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">📝 Notas</h4>
                    <p class="text-sm text-gray-700">{{ $pedido->notas }}</p>
                </div>
            </div>
        @endif
    @endif
</div>
