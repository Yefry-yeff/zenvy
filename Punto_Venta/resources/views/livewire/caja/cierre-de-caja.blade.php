<div class="min-h-screen p-6 bg-gradient-to-br from-purple-50 via-white to-indigo-50">
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="p-6 mb-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Cierre de Caja</h1>
                        <p class="text-gray-600">Resumen del día y conteo de efectivo</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if($cierreProcesado)
                        <span class="px-3 py-2 text-sm font-semibold text-green-800 bg-green-100 rounded-lg">
                            ✅ Caja Cerrada
                        </span>
                    @endif

                </div>
            </div>
        </div>

        @if(!$cajaActual)
            <!-- No hay caja abierta -->
            <div class="p-8 text-center border border-red-200 bg-red-50 rounded-2xl">
                <svg class="w-16 h-16 mx-auto mb-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <h2 class="mb-2 text-xl font-bold text-red-800">No hay caja abierta</h2>
                <p class="text-red-600">Debe tener una caja abierta para realizar el cierre.</p>
            </div>
        @else
            <!-- Mensajes -->
            @if($mensajeExito)
                <div class="p-4 mb-6 border border-green-200 bg-green-50 rounded-2xl" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-medium text-green-800">{{ $mensajeExito }}</p>
                        </div>
                        <button @click="show = false; $wire.limpiarMensajes()" class="text-green-600 hover:text-green-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if($mensajeError)
                <div class="p-4 mb-6 border border-red-200 bg-red-50 rounded-2xl" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-medium text-red-800">{{ $mensajeError }}</p>
                        </div>
                        <button @click="show = false; $wire.limpiarMensajes()" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <!-- Resumen del día -->
                <div class="xl:col-span-1">
                    <div class="p-6 mb-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
                        <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Resumen del Día
                        </h2>

                        <div class="space-y-4">
                            <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-blue-800">Balance Sistema:</span>
                                    <span class="text-lg font-bold text-blue-600">
                                        L.{{ number_format($totalSistema, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-blue-800">Transacciones:</span>
                                    <span class="text-sm text-blue-600">
                                        {{ $resumenTransacciones['transacciones'] ?? 0 }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 border border-green-200 rounded-lg bg-green-50">
                                <h3 class="mb-2 text-sm font-semibold text-green-800">💰 Efectivo</h3>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-green-700">Entradas:</span>
                                        <span class="font-medium">L.{{ number_format($resumenTransacciones['efectivo_entrada'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-green-700">Salidas:</span>
                                        <span class="font-medium">L.{{ number_format($resumenTransacciones['efectivo_salida'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between pt-1 border-t">
                                        <span class="font-semibold text-green-800">Neto:</span>
                                        <span class="font-bold">L.{{ number_format($resumenTransacciones['efectivo_neto'] ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 border border-purple-200 rounded-lg bg-purple-50">
                                <h3 class="mb-2 text-sm font-semibold text-purple-800">💳 Otros Pagos</h3>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-purple-700">Tarjetas:</span>
                                        <span class="font-medium">L.{{ number_format($resumenTransacciones['tarjeta'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-purple-700">Cheques:</span>
                                        <span class="font-medium">L.{{ number_format($resumenTransacciones['cheque'] ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparación -->
                    <div class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
                        <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Comparación
                        </h2>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50">
                                <span class="text-sm font-medium text-blue-800">Sistema:</span>
                                <span class="text-lg font-bold text-blue-600">L.{{ number_format($totalSistema, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-green-50">
                                <span class="text-sm font-medium text-green-800">Contado:</span>
                                <span class="text-lg font-bold text-green-600">L.{{ number_format($totalContado, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-lg {{ $diferenciaEfectivo == 0 ? 'bg-green-50' : ($diferenciaEfectivo > 0 ? 'bg-yellow-50' : 'bg-red-50') }}">
                                <span class="text-sm font-medium {{ $diferenciaEfectivo == 0 ? 'text-green-800' : ($diferenciaEfectivo > 0 ? 'text-yellow-800' : 'text-red-800') }}">Diferencia:</span>
                                <span class="text-lg font-bold {{ $diferenciaEfectivo == 0 ? 'text-green-600' : ($diferenciaEfectivo > 0 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $diferenciaEfectivo >= 0 ? '+' : '' }}L.{{ number_format($diferenciaEfectivo, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteo de Billetes y Monedas -->
                <div class="xl:col-span-2">
                    <div class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
                        <h2 class="flex items-center mb-6 text-lg font-semibold text-gray-800">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Conteo de Efectivo
                        </h2>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- Billetes -->
                            <div class="space-y-4">
                                <h3 class="flex items-center text-lg font-semibold text-gray-700">
                                    <span class="mr-2 text-2xl">💵</span>
                                    Billetes
                                </h3>

                                @php
                                    $billetes = [
                                        ['value' => 500, 'label' => 'L.500', 'model' => 'billetes_500'],
                                        ['value' => 200, 'label' => 'L.200', 'model' => 'billetes_200'],
                                        ['value' => 100, 'label' => 'L.100', 'model' => 'billetes_100'],
                                        ['value' => 50, 'label' => 'L.50', 'model' => 'billetes_50'],
                                        ['value' => 20, 'label' => 'L.20', 'model' => 'billetes_20'],
                                        ['value' => 10, 'label' => 'L.10', 'model' => 'billetes_10'],
                                        ['value' => 5, 'label' => 'L.5', 'model' => 'billetes_5'],
                                        ['value' => 2, 'label' => 'L.2', 'model' => 'billetes_2'],
                                        ['value' => 1, 'label' => 'L.1', 'model' => 'billetes_1']
                                    ]
                                @endphp

                                @foreach($billetes as $billete)
                                    <div class="flex items-center p-3 space-x-3 rounded-lg bg-green-50">
                                        <div class="w-16 text-sm font-semibold text-green-800">{{ $billete['label'] }}</div>
                                        <div class="text-gray-600">×</div>
                                        <input wire:model.live="{{ $billete['model'] }}"
                                               type="number"
                                               min="0"
                                               placeholder="0"
                                               @if($cierreProcesado) disabled @endif
                                               class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center font-semibold {{ $cierreProcesado ? 'bg-gray-100' : 'focus:ring-2 focus:ring-green-500 focus:border-green-500' }}">
                                        <div class="text-gray-600">=</div>
                                        <div class="text-right font-bold text-green-600 min-w-[80px]">
                                            L.{{ number_format($this->{$billete['model']} * $billete['value'], 2) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Monedas -->
                            <div class="space-y-4">
                                <h3 class="flex items-center text-lg font-semibold text-gray-700">
                                    <span class="mr-2 text-2xl">🪙</span>
                                    Monedas
                                </h3>

                                @php
                                    $monedas = [
                                        ['value' => 0.50, 'label' => '50¢', 'model' => 'monedas_0_50'],
                                        ['value' => 0.20, 'label' => '20¢', 'model' => 'monedas_0_20'],
                                        ['value' => 0.10, 'label' => '10¢', 'model' => 'monedas_0_10'],
                                        ['value' => 0.05, 'label' => '5¢', 'model' => 'monedas_0_05'],
                                        ['value' => 0.02, 'label' => '2¢', 'model' => 'monedas_0_02'],
                                        ['value' => 0.01, 'label' => '1¢', 'model' => 'monedas_0_01']
                                    ]
                                @endphp

                                @foreach($monedas as $moneda)
                                    <div class="flex items-center p-3 space-x-3 rounded-lg bg-yellow-50">
                                        <div class="w-16 text-sm font-semibold text-yellow-800">{{ $moneda['label'] }}</div>
                                        <div class="text-gray-600">×</div>
                                        <input wire:model.live="{{ $moneda['model'] }}"
                                               type="number"
                                               min="0"
                                               placeholder="0"
                                               @if($cierreProcesado) disabled @endif
                                               class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center font-semibold {{ $cierreProcesado ? 'bg-gray-100' : 'focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500' }}">
                                        <div class="text-gray-600">=</div>
                                        <div class="text-right font-bold text-yellow-600 min-w-[80px]">
                                            L.{{ number_format($this->{$moneda['model']} * $moneda['value'], 2) }}
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Espaciador para centrar -->
                                <div class="space-y-4">
                                    <div class="h-8"></div>
                                    <div class="h-8"></div>
                                    <div class="h-8"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Cierre -->
                        @if(!$cierreProcesado)
                            <div class="pt-6 mt-8 border-t border-gray-200">
                                <button wire:click="procesarCierre"
                                        class="flex items-center justify-center w-full px-6 py-4 space-x-2 font-bold text-white transition-colors duration-200 bg-purple-600 rounded-lg hover:bg-purple-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Procesar Cierre de Caja</span>
                                </button>
                            </div>
                        @else
                            <div class="pt-6 mt-8 text-center border-t border-gray-200">
                                <div class="inline-flex items-center px-6 py-3 space-x-2 font-semibold text-green-800 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Cierre de Caja Completado</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
