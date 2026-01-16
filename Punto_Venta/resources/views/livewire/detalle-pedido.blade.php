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
                            <p class="text-sm text-green-600 mt-2 flex items-center gap-2">
                                ✓ Facturado con ID: <strong>{{ $pedido->factura_id }}</strong> 
                                @if($pedido->fecha_facturado)
                                    el {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                @endif
                                <button wire:click="imprimirFactura" class="ml-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                    🖨️ Imprimir Factura
                                </button>
                            </p>
                        @endif
                    </div>

                    @if($pedido->estado === 'pendiente')
                        <div class="flex gap-2">
                            <button wire:click="$set('mostrarModalProcesar', true)" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                ✓ Procesar y Facturar
                            </button>
                            <button wire:click="$set('mostrarModalRechazar', true)" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
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

        <!-- Modal de Confirmación para Procesar -->
        @if($mostrarModalProcesar ?? false)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalProcesar', false)">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">✓ Confirmar Procesamiento</h3>
                            <button wire:click="$set('mostrarModalProcesar', false)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <p class="text-gray-700 mb-4">
                                ¿Está seguro de procesar y facturar el pedido <strong>{{ $pedido->numero_pedido }}</strong>?
                            </p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Resumen del Pedido:</h4>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• Cliente: {{ $pedido->cliente_nombre }}</li>
                                    <li>• Productos: {{ $pedido->items->count() }} items</li>
                                    <li>• Total: L {{ number_format($pedido->total, 2) }}</li>
                                </ul>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">
                                ⚠️ Esta acción generará una factura y descontará el inventario correspondiente.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                wire:click="procesarPedido" 
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="procesarPedido">✓ Sí, Procesar y Facturar</span>
                                <span wire:loading wire:target="procesarPedido" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                            </button>
                            <button 
                                wire:click="$set('mostrarModalProcesar', false)" 
                                class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de Confirmación para Rechazar -->
        @if($mostrarModalRechazar ?? false)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalRechazar', false)">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">✗ Confirmar Rechazo</h3>
                            <button wire:click="$set('mostrarModalRechazar', false)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <p class="text-gray-700 mb-4">
                                ¿Está seguro de rechazar el pedido <strong>{{ $pedido->numero_pedido }}</strong>?
                            </p>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <h4 class="font-semibold text-red-900 mb-2">Información del Pedido:</h4>
                                <ul class="text-sm text-red-800 space-y-1">
                                    <li>• Cliente: {{ $pedido->cliente_nombre }}</li>
                                    <li>• Total: L {{ number_format($pedido->total, 2) }}</li>
                                </ul>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">
                                ⚠️ Esta acción marcará el pedido como rechazado y no se podrá facturar.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                wire:click="rechazarPedido" 
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="rechazarPedido">✗ Sí, Rechazar Pedido</span>
                                <span wire:loading wire:target="rechazarPedido" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Rechazando...
                                </span>
                            </button>
                            <button 
                                wire:click="$set('mostrarModalRechazar', false)" 
                                class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
                </div>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    // Escuchar evento para abrir impresión de factura
    document.addEventListener('livewire:init', () => {
        Livewire.on('abrirImpresionFactura', (event) => {
            const facturaId = event.facturaId;
            // Abrir la factura en una nueva ventana
            window.open(`/factura/${facturaId}/pdf`, '_blank');
        });

        Livewire.on('mostrarImpresionFactura', (event) => {
            const facturaId = event.facturaId;
            // Abrir la factura en una nueva ventana después de procesar
            setTimeout(() => {
                window.open(`/factura/${facturaId}/pdf`, '_blank');
            }, 500);
        });
    });
</script>
@endpush
