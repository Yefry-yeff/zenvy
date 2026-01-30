<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Detalle de Pedido</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $pedido->numero_pedido }}</p>
            </div>
            <a href="{{ route('pedidos-web.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="px-6 py-4 mb-6 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="px-6 py-4 mb-6 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Estado Principal -->
            <div class="mb-6 overflow-hidden bg-white shadow-lg rounded-xl">
                <div class="px-6 py-8 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-4 mb-2">
                                <h3 class="text-3xl font-bold text-gray-900">{{ $pedido->numero_pedido }}</h3>
                                @if($pedido->estado === 'pendiente')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                        <span class="inline-flex w-2 h-2 bg-yellow-600 rounded-full"></span>
                                        Pendiente
                                    </span>
                                @elseif($pedido->estado === 'procesando')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-100 rounded-full">
                                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full animate-pulse"></span>
                                        Procesando
                                    </span>
                                @elseif($pedido->estado === 'facturado')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-green-700 bg-green-100 rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Facturado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-700 bg-red-100 rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Rechazado
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">Recibido: <span class="font-medium">{{ $pedido->created_at->format('d/m/Y H:i:s') }}</span> ({{ $pedido->created_at->diffForHumans() }})</p>

                            @if($pedido->factura_id)
                                <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm text-green-800">
                                        <span class="font-semibold">✓ Facturado</span> - Factura ID: <span class="font-mono">{{ $pedido->factura_id }}</span> el {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if($pedido->estado === 'pendiente')
                            <div class="flex gap-3">
                                <form method="POST" action="{{ route('pedidos-web.process', $pedido->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors font-semibold shadow-md hover:shadow-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Procesar y Facturar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('pedidos-web.reject', $pedido->id) }}" onsubmit="return confirm('¿Estás seguro de que deseas rechazar este pedido?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors font-semibold shadow-md hover:shadow-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Rechazar
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
                <!-- Información del Cliente -->
                <div class="overflow-hidden bg-white shadow-lg rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-lg font-bold text-gray-900">👤 Cliente</h4>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Nombre</dt>
                                <dd class="mt-1 text-base font-semibold text-gray-900">{{ $pedido->cliente_nombre }}</dd>
                            </div>
                            @if($pedido->cliente_email)
                                <div class="pt-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-600">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-700 break-all">{{ $pedido->cliente_email }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_telefono)
                                <div class="pt-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-600">Teléfono</dt>
                                    <dd class="mt-1 text-sm text-gray-700">{{ $pedido->cliente_telefono }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_rtn)
                                <div class="pt-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-600">RTN</dt>
                                    <dd class="mt-1 text-sm font-mono text-gray-700">{{ $pedido->cliente_rtn }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Información del Pago -->
                <div class="overflow-hidden bg-white shadow-lg rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-lg font-bold text-gray-900">💳 Pago</h4>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600">Método</dt>
                                <dd class="mt-1 text-base font-semibold text-gray-900">{{ $pedido->metodo_pago ?? 'No especificado' }}</dd>
                            </div>
                            
                            @if($pedido->metodo_pago === 'Transferencia Bancaria' && isset($pedido->metadata['transfer_info']))
                                @php
                                    $transferInfo = $pedido->metadata['transfer_info'];
                                @endphp
                                <div class="pt-3 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-600 mb-2">Información de Transferencia</dt>
                                    <dd class="space-y-2">
                                        @if(isset($transferInfo['account_bank']))
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Banco:</span>
                                                <span class="font-medium text-gray-900">{{ $transferInfo['account_bank'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($transferInfo['account_number']))
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Cuenta:</span>
                                                <span class="font-mono font-medium text-gray-900">{{ $transferInfo['account_number'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($transferInfo['transfer_date']))
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Fecha Depósito:</span>
                                                <span class="font-medium text-gray-900">{{ $transferInfo['transfer_date'] }}</span>
                                            </div>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            
                            <div class="pt-2 border-t border-gray-100">
                                <dt class="text-sm font-medium text-gray-600">Subtotal</dt>
                                <dd class="mt-1 text-base text-gray-900">L {{ number_format($pedido->subtotal, 2) }}</dd>
                            </div>
                            @if($pedido->descuento > 0)
                                <div class="pt-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-600">Descuento</dt>
                                    <dd class="mt-1 text-base text-red-600 font-semibold">-L {{ number_format($pedido->descuento, 2) }}</dd>
                                </div>
                            @endif
                            <div class="pt-2 border-t border-gray-100">
                                <dt class="text-sm font-medium text-gray-600">ISV (15%)</dt>
                                <dd class="mt-1 text-base text-gray-900">L {{ number_format($pedido->isv, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Resumen Total -->
                <div class="overflow-hidden bg-gradient-to-br from-indigo-600 to-blue-700 shadow-lg rounded-xl">
                    <div class="p-6 text-white">
                        <p class="text-sm font-medium text-indigo-100 mb-2">TOTAL A PAGAR</p>
                        <p class="text-4xl font-bold mb-2">L {{ number_format($pedido->total, 2) }}</p>
                        
                        @if(isset($pedido->metadata['shipping_cost']) && $pedido->metadata['shipping_cost'] > 0)
                            <div class="pt-2 mt-2 border-t border-indigo-400">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-indigo-100">Costo de Envío:</span>
                                    <span class="text-lg font-semibold">L {{ number_format($pedido->metadata['shipping_cost'], 2) }}</span>
                                </div>
                            </div>
                        @endif
                        
                        <div class="pt-4 border-t border-indigo-400 mt-4">
                            <p class="text-xs text-indigo-100">Cantidad de productos: <span class="font-semibold">{{ $pedido->items->count() }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            @if($pedido->cliente_direccion)
                <div class="mb-6 overflow-hidden bg-white shadow-lg rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-lg font-bold text-gray-900">📍 Dirección</h4>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700">{{ $pedido->cliente_direccion }}</p>
                    </div>
                </div>
            @endif

            <!-- Información de Transferencia Bancaria Detallada -->
            @if($pedido->metodo_pago === 'Transferencia Bancaria' && isset($pedido->metadata['transfer_info']))
                <div class="mb-6 overflow-hidden bg-white shadow-lg rounded-xl border-l-4 border-blue-500">
                    <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                        <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Detalles de Transferencia Bancaria
                        </h4>
                    </div>
                    <div class="p-6">
                        @php
                            $transferInfo = $pedido->metadata['transfer_info'];
                        @endphp
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if(isset($transferInfo['account_bank']))
                                <div>
                                    <dt class="text-sm font-medium text-gray-600">Banco</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ $transferInfo['account_bank'] }}</dd>
                                </div>
                            @endif
                            @if(isset($transferInfo['account_type']))
                                <div>
                                    <dt class="text-sm font-medium text-gray-600">Tipo de Cuenta</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ ucfirst($transferInfo['account_type']) }}</dd>
                                </div>
                            @endif
                            @if(isset($transferInfo['account_number']))
                                <div>
                                    <dt class="text-sm font-medium text-gray-600">Número de Cuenta</dt>
                                    <dd class="mt-1 text-base font-mono font-semibold text-gray-900">{{ $transferInfo['account_number'] }}</dd>
                                </div>
                            @endif
                            @if(isset($transferInfo['account_holder']))
                                <div>
                                    <dt class="text-sm font-medium text-gray-600">Titular de la Cuenta</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ $transferInfo['account_holder'] }}</dd>
                                </div>
                            @endif
                            @if(isset($transferInfo['transfer_date']))
                                <div class="md:col-span-2">
                                    <dt class="text-sm font-medium text-gray-600">Fecha de Depósito/Transferencia</dt>
                                    <dd class="mt-1 text-base font-semibold text-gray-900">{{ $transferInfo['transfer_date'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

            <!-- Productos -->
            <div class="overflow-hidden bg-white shadow-lg rounded-xl">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-lg font-bold text-gray-900">📦 Productos del Pedido</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Cantidad</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">P. Unit.</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Subtotal</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">ISV</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pedido->items as $item)
                                <tr class="hover:bg-blue-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $item->producto->nombre ?? "Producto ID {$item->producto_id}" }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">SKU: {{ $item->producto_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ $item->cantidad }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->precio_unitario, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->subtotal, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->isv, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">L {{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notas -->
            @if($pedido->notas)
                <div class="mt-6 overflow-hidden bg-white shadow-lg rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-100 bg-amber-50">
                        <h4 class="text-lg font-bold text-gray-900">📝 Notas</h4>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $pedido->notas }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
