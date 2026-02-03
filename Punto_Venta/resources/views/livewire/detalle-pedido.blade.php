<div class="min-h-screen bg-gray-50">
    @if(!$pedido)
        <div class="m-6">
            <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-6">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">Pedido no encontrado</h3>
                        <p class="text-gray-600 text-sm mt-1">Lo sentimos, no pudimos localizar este pedido.</p>
                    </div>
                </div>
            </div>
            <button wire:click="volverABandeja" class="mt-6 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a la bandeja
            </button>
        </div>
    @else
        <!-- Notificaciones -->
        @if(session('success'))
            <div class="m-6 bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-6">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-green-800">{{ session('success') }}</h3>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="m-6 bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-6">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-800">{{ session('error') }}</h3>
                    </div>
                </div>
            </div>
        @endif

        <!-- Header con botón volver -->
        <div class="sticky top-0 z-40 bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <button 
                    wire:click="volverABandeja" 
                    class="inline-flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a la bandeja
                </button>
            </div>
        </div>

        <!-- Título y Estado -->
        <div class="px-6 py-8 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $pedido->numero_pedido }}</h1>
                    <p class="text-gray-600">Recibido: <span class="font-medium">{{ $pedido->created_at->format('d/m/Y H:i:s') }}</span> ({{ $pedido->created_at->diffForHumans() }})</p>
                </div>
                <div class="flex flex-col gap-3">
                    <div>
                        @if($pedido->estado === 'pendiente')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                Pendiente
                            </span>
                        @elseif($pedido->estado === 'procesando')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span>
                                Procesando
                            </span>
                        @elseif($pedido->estado === 'facturado')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                Facturado
                            </span>
                        @else
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                Rechazado
                            </span>
                        @endif
                    </div>
                    @if($pedido->estado === 'pendiente')
                        <div class="flex gap-2">
                            <button 
                                wire:click="$set('mostrarModalProcesar', true)" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Procesar y Facturar
                            </button>
                            <button 
                                wire:click="$set('mostrarModalRechazar', true)" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors shadow-sm hover:shadow-md"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Rechazar
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            @if($pedido->factura_id)
                <div class="mt-4 p-4 bg-white rounded-lg border border-green-200">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-green-900">Facturado exitosamente</p>
                                <p class="text-sm text-green-700">ID de Factura: <strong>{{ $pedido->factura_id }}</strong>
                                @if($pedido->fecha_facturado)
                                    | {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('factura.pdf.preview', $pedido->factura_id) }}" 
                               target="_blank"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded transition-colors">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Imprimir Factura
                            </a>
                            <button 
                                wire:click="$set('mostrarModalEnviarFactura', true)"
                                class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded transition-colors">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Enviar Factura al Cliente
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-6">

        <!-- Grid de 3 columnas: Cliente | Pago | Total -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Información del Cliente -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Información del Cliente
                        </h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">{{ $pedido->cliente_nombre }}</dd>
                            </div>
                            @if($pedido->cliente_email)
                                <div>
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</dt>
                                    <dd class="mt-2 text-base text-gray-900">
                                        <a href="mailto:{{ $pedido->cliente_email }}" class="text-blue-600 hover:underline">{{ $pedido->cliente_email }}</a>
                                    </dd>
                                </div>
                            @endif
                            @if($pedido->cliente_telefono)
                                <div>
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</dt>
                                    <dd class="mt-2 text-base text-gray-900">{{ $pedido->cliente_telefono }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_rtn)
                                <div>
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">RTN</dt>
                                    <dd class="mt-2 text-base font-mono text-gray-900">{{ $pedido->cliente_rtn }}</dd>
                                </div>
                            @endif
                            @if($pedido->cliente_direccion)
                                <div class="md:col-span-2">
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Dirección</dt>
                                    <dd class="mt-2 text-base text-gray-900">{{ $pedido->cliente_direccion }}</dd>
                                </div>
                            @endif
                            
                            @if(is_array($pedido->metadata) && isset($pedido->metadata['delivery_type']))
                                <div>
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo de Entrega</dt>
                                    <dd class="mt-2">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            {{ $pedido->metadata['delivery_type'] === 'domicilio' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            @if($pedido->metadata['delivery_type'] === 'domicilio')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                                </svg>
                                                Envío a Domicilio
                                            @else
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Recoger en Tienda
                                            @endif
                                        </span>
                                    </dd>
                                </div>
                            @endif
                            
                            @if(is_array($pedido->metadata) && isset($pedido->metadata['delivery_address']) && $pedido->metadata['delivery_address'])
                                <div class="md:col-span-2">
                                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Dirección de Envío</dt>
                                    <dd class="mt-2 text-base text-gray-900">{{ $pedido->metadata['delivery_address'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Información de Pago y Total -->
            <div class="flex flex-col gap-6">
                <!-- Pago -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0m2 0a1 1 0 10-2 0m-7-4h.01M9 9a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Método de Pago
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-2xl font-bold text-gray-900 mb-4">{{ $pedido->metodo_pago ?? 'No especificado' }}</p>
                        
                        @if($pedido->metodo_pago === 'Transferencia Bancaria' && is_array($pedido->metadata) && isset($pedido->metadata['transfer_info']))
                            @php
                                $transferInfo = $pedido->metadata['transfer_info'];
                            @endphp
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Información de la Transferencia:</h4>
                                <dl class="space-y-2 text-sm">
                                    @if(isset($transferInfo['account_bank']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Banco:</dt>
                                            <dd class="font-medium text-gray-900">{{ $transferInfo['account_bank'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($transferInfo['account_number']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Cuenta:</dt>
                                            <dd class="font-mono font-medium text-gray-900">{{ $transferInfo['account_number'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($transferInfo['transfer_date']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600">Fecha de Depósito:</dt>
                                            <dd class="font-medium text-gray-900">{{ $transferInfo['transfer_date'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @elseif($pedido->metodo_pago === 'Transferencia Bancaria')
                            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-sm text-amber-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    Los detalles de la transferencia no están disponibles para este pedido.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Total -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Resumen del Pedido
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="w-full p-4 border border-gray-300 rounded-lg bg-gray-50">
                            <!-- Encabezado -->
                            <div class="mb-3 text-center">
                                <h6 class="mb-0 font-bold text-gray-700">RESUMEN DE FACTURACIÓN</h6>
                                <hr class="mt-2">
                            </div>

                            @php
                                // Calcular importe gravado y exento
                                $importeGravado = 0;
                                $importeExento = 0;
                                $impuestoVenta = 0;
                                
                                foreach($pedido->items as $item) {
                                    // Sumar el ISV ya calculado
                                    $impuestoVenta += $item->isv;
                                    
                                    // Separar por tipo de ISV
                                    if($item->isv > 0) {
                                        // Producto gravado - el subtotal es sin ISV
                                        $importeGravado += $item->subtotal;
                                    } else {
                                        // Producto exento
                                        $importeExento += $item->subtotal;
                                    }
                                }
                                
                                $subTotal = $importeGravado + $importeExento;
                                $costoEnvio = (is_array($pedido->metadata) && isset($pedido->metadata['shipping_cost'])) ? $pedido->metadata['shipping_cost'] : 0;
                                $totalAPagar = $subTotal - $pedido->descuento + $impuestoVenta + $costoEnvio;
                            @endphp

                            <!-- Importe Gravado -->
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-gray-700">Importe Gravado:</span>
                                <span class="font-medium">L {{ number_format($importeGravado, 2) }}</span>
                            </div>

                            <!-- Importe Exento -->
                            @if($importeExento > 0)
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-gray-700">Importe Exento:</span>
                                <span class="font-medium">L {{ number_format($importeExento, 2) }}</span>
                            </div>
                            @endif

                            <!-- Sub-Total -->
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-gray-700">Sub-Total:</span>
                                <span class="font-medium">L {{ number_format($subTotal, 2) }}</span>
                            </div>

                            <!-- Descuento general -->
                            @if($pedido->descuento > 0)
                                <div class="pl-3 mb-2 border-l-4 border-orange-400 bg-orange-50">
                                    <div class="flex justify-between mb-1">
                                        <span class="font-medium text-orange-700">
                                            <i class="mr-1 fas fa-tag"></i>
                                            Descuentos y rebajas:
                                        </span>
                                        <span class="font-medium text-orange-700">L {{ number_format($pedido->descuento, 2) }}</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Impuesto sobre venta -->
                            @if($impuestoVenta > 0)
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-blue-700">Impuesto sobre venta (15%):</span>
                                <span class="font-medium text-blue-700">L {{ number_format($impuestoVenta, 2) }}</span>
                            </div>
                            @endif

                            <!-- Costo de Envío -->
                            @if($costoEnvio > 0)
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-purple-700">
                                    <i class="mr-1 fas fa-truck"></i>
                                    Costo de Envío:
                                </span>
                                <span class="font-medium text-purple-700">L {{ number_format($costoEnvio, 2) }}</span>
                            </div>
                            @endif

                            <hr class="my-3 border-gray-400">

                            <!-- Total final -->
                            <div class="flex justify-between p-3 bg-green-100 border border-green-300 rounded">
                                <span class="text-xl font-bold text-green-800">
                                    <i class="mr-2 fas fa-calculator"></i>
                                    Total a Pagar:
                                </span>
                                <span class="text-xl font-bold text-green-800">L {{ number_format($totalAPagar, 2) }}</span>
                            </div>

                            <!-- Información adicional -->
                            <div class="mt-3 text-xs text-center text-gray-500">
                                @if($pedido->items->count() > 0)
                                    {{ $pedido->items->count() }} artículo(s) en el pedido
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Productos del Pedido
                    </h3>
                    <div class="flex gap-3">
                        <input 
                            type="text" 
                            wire:model.live="buscarProducto" 
                            placeholder="Buscar producto..." 
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                        <select 
                            wire:model.live="registrosPorPaginaProductos" 
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="5">5 por página</option>
                            <option value="10">10 por página</option>
                            <option value="20">20 por página</option>
                            <option value="50">50 por página</option>
                        </select>
                    </div>
                </div>
            </div>

            @php
                $productosPaginados = $this->getProductosPaginados();
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Producto</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Cantidad</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Subtotal</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">ISV</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($productosPaginados['datos'] as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $item->producto->nombre ?? "Producto ID {$item->producto_id}" }}
                                    </div>
                                    <div class="text-xs text-gray-500">ID: {{ $item->producto_id }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">{{ $item->cantidad }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->subtotal, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">L {{ number_format($item->isv, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">L {{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No se encontraron productos</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación de Productos -->
            @if($productosPaginados['total'] > 0)
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Mostrando <span class="font-medium">{{ $productosPaginados['desde'] }}</span> a 
                        <span class="font-medium">{{ $productosPaginados['hasta'] }}</span> de 
                        <span class="font-medium">{{ $productosPaginados['total'] }}</span> productos
                    </div>
                    <div class="flex gap-2">
                        <button 
                            wire:click="previousPageProductos" 
                            @if($productosPaginados['paginaActual'] <= 1) disabled @endif
                            class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Anterior
                        </button>
                        <span class="px-3 py-1.5 text-sm text-gray-700">
                            Página {{ $productosPaginados['paginaActual'] }} de {{ $productosPaginados['ultimaPagina'] }}
                        </span>
                        <button 
                            wire:click="nextPageProductos" 
                            @if($productosPaginados['paginaActual'] >= $productosPaginados['ultimaPagina']) disabled @endif
                            class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Información de Transferencia Bancaria -->
        @if($pedido->metodo_pago === 'Transferencia Bancaria' && isset($pedido->metadata['transfer_info']))
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Información de Transferencia Bancaria
                    </h3>
                </div>
                <div class="p-6 bg-blue-50">
                    @php
                        $transferInfo = $pedido->metadata['transfer_info'];
                    @endphp
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(isset($transferInfo['account_bank']))
                            <div>
                                <dt class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Banco</dt>
                                <dd class="mt-1 text-base font-medium text-gray-900">{{ $transferInfo['account_bank'] }}</dd>
                            </div>
                        @endif
                        @if(isset($transferInfo['account_type']))
                            <div>
                                <dt class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Tipo de Cuenta</dt>
                                <dd class="mt-1 text-base font-medium text-gray-900">{{ ucfirst($transferInfo['account_type']) }}</dd>
                            </div>
                        @endif
                        @if(isset($transferInfo['account_number']))
                            <div>
                                <dt class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Número de Cuenta</dt>
                                <dd class="mt-1 text-base font-mono text-gray-900">{{ $transferInfo['account_number'] }}</dd>
                            </div>
                        @endif
                        @if(isset($transferInfo['account_holder']))
                            <div>
                                <dt class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Titular de la Cuenta</dt>
                                <dd class="mt-1 text-base font-medium text-gray-900">{{ $transferInfo['account_holder'] }}</dd>
                            </div>
                        @endif
                        @if(isset($transferInfo['transfer_date']))
                            <div class="md:col-span-2">
                                <dt class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Fecha de Transferencia</dt>
                                <dd class="mt-1 text-base font-medium text-gray-900">{{ $transferInfo['transfer_date'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        @endif

        <!-- Notas -->
        @if($pedido->notas)
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Notas
                    </h3>
                </div>
                <div class="p-6 bg-amber-50">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $pedido->notas }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Confirmación para Procesar -->
    @if($mostrarModalProcesar ?? false)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalProcesar', false)">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-green-50">
                    <h3 class="text-xl font-bold text-green-900 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Confirmar Procesamiento
                    </h3>
                    <button wire:click="$set('mostrarModalProcesar', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        ¿Está seguro de procesar y facturar el pedido <strong class="text-lg">{{ $pedido->numero_pedido }}</strong>?
                    </p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                            </svg>
                            Resumen del Pedido
                        </h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li class="flex justify-between"><span>Cliente:</span> <strong>{{ $pedido->cliente_nombre }}</strong></li>
                            <li class="flex justify-between"><span>Productos:</span> <strong>{{ $pedido->items->count() }} items</strong></li>
                            <li class="flex justify-between"><span>Total:</span> <strong>L {{ number_format($pedido->total, 2) }}</strong></li>
                        </ul>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6">
                        <p class="text-xs text-amber-800 flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Esta acción generará una factura y descontará el inventario correspondiente.</span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button 
                            wire:click="procesarPedido" 
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <span wire:loading.remove wire:target="procesarPedido" class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Procesar y Facturar
                            </span>
                            <span wire:loading wire:target="procesarPedido">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
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
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-red-50">
                    <h3 class="text-xl font-bold text-red-900 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Confirmar Rechazo
                    </h3>
                    <button wire:click="$set('mostrarModalRechazar', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        ¿Está seguro de rechazar el pedido <strong class="text-lg">{{ $pedido->numero_pedido }}</strong>?
                    </p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-red-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                            </svg>
                            Información del Pedido
                        </h4>
                        <ul class="text-sm text-red-800 space-y-1">
                            <li class="flex justify-between"><span>Cliente:</span> <strong>{{ $pedido->cliente_nombre }}</strong></li>
                            <li class="flex justify-between"><span>Total:</span> <strong>L {{ number_format($pedido->total, 2) }}</strong></li>
                        </ul>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6">
                        <p class="text-xs text-amber-800 flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Esta acción marcará el pedido como rechazado y no se podrá facturar.</span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button 
                            wire:click="rechazarPedido" 
                            class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Continuar
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

    <!-- Modal de Enviar Factura al Cliente -->
    @if($mostrarModalEnviarFactura ?? false)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalEnviarFactura', false)">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-green-50">
                    <h3 class="text-xl font-bold text-green-900 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Enviar Factura al Cliente
                    </h3>
                    <button wire:click="$set('mostrarModalEnviarFactura', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-gray-700">
                            Se enviará la factura al correo: <strong>{{ $pedido->cliente_email }}</strong>
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Mensaje adicional (opcional):
                        </label>
                        <textarea 
                            wire:model="comentarioFactura"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="Ej: Gracias por su compra. Su pedido será entregado en 2-3 días hábiles..."
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Este mensaje se incluirá en el correo enviado al cliente.</p>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            wire:click="enviarFacturaCliente" 
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <span wire:loading.remove wire:target="enviarFacturaCliente" class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Enviar Factura
                            </span>
                            <span wire:loading wire:target="enviarFacturaCliente">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                        <button 
                            wire:click="$set('mostrarModalEnviarFactura', false)" 
                            class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Comentario para Rechazo -->
    @if($mostrarModalComentarioRechazo ?? false)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalComentarioRechazo', false)">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-red-50">
                    <h3 class="text-xl font-bold text-red-900 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Motivo del Rechazo
                    </h3>
                    <button wire:click="$set('mostrarModalComentarioRechazo', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-gray-700">
                            Por favor, indique el motivo del rechazo. Esta información será enviada al cliente.
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Motivo del rechazo: <span class="text-red-600">*</span>
                        </label>
                        <textarea 
                            wire:model="comentarioRechazo"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                            placeholder="Ej: Lo sentimos, no contamos con stock disponible del producto solicitado..."
                            required
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Este mensaje será enviado al correo del cliente.</p>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6">
                        <p class="text-xs text-amber-800 flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>El cliente recibirá una notificación por correo electrónico con el motivo del rechazo.</span>
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            wire:click="confirmarRechazo" 
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <span wire:loading.remove wire:target="confirmarRechazo" class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Rechazar Pedido
                            </span>
                            <span wire:loading wire:target="confirmarRechazo">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                        <button 
                            wire:click="$set('mostrarModalComentarioRechazo', false)" 
                            class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@push('scripts')
<script>
    // Escuchar evento para abrir impresión de factura
    document.addEventListener('livewire:init', () => {
        Livewire.on('abrirImpresionFactura', (data) => {
            const facturaId = Array.isArray(data) ? data[0].facturaId : data.facturaId;
            console.log('Abriendo impresión de factura:', facturaId);
            // Abrir la factura en una nueva ventana
            window.open(`/factura/${facturaId}/pdf`, '_blank');
        });

        Livewire.on('mostrarImpresionFactura', (data) => {
            const facturaId = Array.isArray(data) ? data[0].facturaId : data.facturaId;
            console.log('Mostrando impresión de factura:', facturaId);
            // Abrir la factura en una nueva ventana después de procesar
            setTimeout(() => {
                window.open(`/factura/${facturaId}/pdf`, '_blank');
            }, 500);
        });
    });
</script>
@endpush
