<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-blue-600 to-blue-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center text-2xl font-bold">
                        <i class="mr-3 fas fa-exchange-alt"></i>
                        Flujo de Caja
                    </h1>
                    <p class="mt-1 text-blue-100">Historial de transacciones de mis cajas</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="p-6 border-b bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Filtro de Fecha -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-calendar"></i>
                        Fecha
                    </label>
                    <input type="date"
                           wire:model.live="fechaFiltro"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filtro de Tipo -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-filter"></i>
                        Tipo de Transacción
                    </label>
                    <select wire:model.live="tipoTransaccionFiltro"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las transacciones</option>
                        <option value="apertura_caja">Apertura de Caja</option>
                        <option value="cierre">Cierre</option>
                        <option value="cierre_caja">Cierre de Caja (Jornada)</option>
                        <option value="Facturacion">Facturación</option>
                        <option value="Recibo de Efectivo">Recibo de Efectivo</option>
                        <option value="Entrega de Efectivo">Entrega de Efectivo</option>
                    </select>
                </div>

                <!-- Botón de Actualizar -->
                <div class="flex items-end">
                    <button wire:click="filtrarTransacciones"
                            class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="mr-2 fas fa-sync-alt"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="p-6 border-b bg-blue-50">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <!-- Total Efectivo -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="mr-2 text-xl text-green-500 fas fa-money-bill-wave"></i>
                        <span class="text-sm font-medium text-gray-600">Efectivo</span>
                    </div>
                    <div class="text-lg font-bold text-green-600">
                        L. {{ number_format($totalEfectivo, 2) }}
                    </div>
                </div>

                <!-- Total Tarjeta -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="mr-2 text-xl text-blue-500 fas fa-credit-card"></i>
                        <span class="text-sm font-medium text-gray-600">Tarjeta</span>
                    </div>
                    <div class="text-lg font-bold text-blue-600">
                        L. {{ number_format($totalTarjeta, 2) }}
                    </div>
                </div>

                <!-- Total Cheque -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="mr-2 text-xl text-purple-500 fas fa-money-check"></i>
                        <span class="text-sm font-medium text-gray-600">Cheque</span>
                    </div>
                    <div class="text-lg font-bold text-purple-600">
                        L. {{ number_format($totalCheque, 2) }}
                    </div>
                </div>

                <!-- Total Transferencia -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="mr-2 text-xl text-orange-500 fas fa-exchange-alt"></i>
                        <span class="text-sm font-medium text-gray-600">Transferencia</span>
                    </div>
                    <div class="text-lg font-bold text-orange-600">
                        L. {{ number_format($totalTransferencia, 2) }}
                    </div>
                </div>

                <!-- Total General -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="mr-2 text-xl text-gray-700 fas fa-calculator"></i>
                        <span class="text-sm font-medium text-gray-600">Total</span>
                    </div>
                    <div class="text-xl font-bold text-gray-800">
                        L. {{ number_format($totalGeneral, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Transacciones -->
        <div class="p-6">
            @if(count($transacciones) > 0)
                <div class="space-y-4">
                    @foreach($transacciones as $transaccion)
                        <div class="p-4 transition-colors border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <!-- Información Principal -->
                                <div class="flex-1">
                                    <div class="flex items-center mb-2 space-x-3">
                                        <div class="flex items-center">
                                            @switch($transaccion['transaccion'])
                                                @case('apertura_caja')
                                                    <i class="mr-2 text-green-500 fas fa-unlock-alt"></i>
                                                    <span class="font-medium text-green-600">Apertura de Caja</span>
                                                    @break
                                                @case('cierre_caja')
                                                    <i class="mr-2 text-red-500 fas fa-lock"></i>
                                                    <span class="font-medium text-red-600">Cierre de Caja</span>
                                                    @break
                                                @case('Facturacion')
                                                    <i class="mr-2 text-blue-500 fas fa-shopping-cart"></i>
                                                    <span class="font-medium text-blue-600">Facturación</span>
                                                    @break
                                                @case('Recibo de Efectivo')
                                                    <i class="mr-2 text-green-500 fas fa-arrow-down"></i>
                                                    <span class="font-medium text-green-600">Recibo de Efectivo</span>
                                                    @break
                                                @case('Entrega de Efectivo')
                                                    <i class="mr-2 text-red-500 fas fa-arrow-up"></i>
                                                    <span class="font-medium text-red-600">Entrega de Efectivo</span>
                                                    @break
                                                @default
                                                    <i class="mr-2 text-gray-500 fas fa-exchange-alt"></i>
                                                    <span class="font-medium text-gray-600">{{ ucfirst($transaccion['transaccion']) }}</span>
                                            @endswitch
                                        </div>

                                        <span class="text-sm text-gray-500">
                                            Caja #{{ $transaccion['caja_id'] }}
                                        </span>
                                    </div>

                                    @if($transaccion['descripcion'])
                                        <p class="mb-2 text-sm text-gray-600">{{ $transaccion['descripcion'] }}</p>
                                    @endif

                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaccion['created_at'])->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>

                                <!-- Montos -->
                                <div class="space-y-1 text-right">
                                    @if($transaccion['efectivo'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="text-xs text-green-500 fas fa-money-bill-wave"></i>
                                            <span class="text-sm font-medium {{ $transaccion['efectivo'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaccion['efectivo'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['efectivo'], 2) }}
                                            </span>
                                        </div>
                                    @endif

                                    @if($transaccion['tarjeta'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="text-xs text-blue-500 fas fa-credit-card"></i>
                                            <span class="text-sm font-medium {{ $transaccion['tarjeta'] > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                                {{ $transaccion['tarjeta'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['tarjeta'], 2) }}
                                            </span>
                                        </div>
                                    @endif

                                    @if($transaccion['cheque'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="text-xs text-purple-500 fas fa-money-check"></i>
                                            <span class="text-sm font-medium {{ $transaccion['cheque'] > 0 ? 'text-purple-600' : 'text-red-600' }}">
                                                {{ $transaccion['cheque'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['cheque'], 2) }}
                                            </span>
                                        </div>
                                    @endif

                                    @if($transaccion['transferencia'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="text-xs text-orange-500 fas fa-exchange-alt"></i>
                                            <span class="text-sm font-medium {{ $transaccion['transferencia'] > 0 ? 'text-orange-600' : 'text-red-600' }}">
                                                {{ $transaccion['transferencia'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['transferencia'], 2) }}
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Total de la transacción -->
                                    @php
                                        $esAperturaOCierre = in_array(strtolower($transaccion['transaccion']), ['apertura_caja', 'cierre']);
                                    @endphp

                                    @if(!$esAperturaOCierre && $transaccion['total'] != 0)
                                        <hr class="my-1 border-gray-200">
                                        <div class="text-sm font-bold {{ $transaccion['total'] > 0 ? 'text-gray-800' : 'text-red-600' }}">
                                            {{ $transaccion['total'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['total'], 2) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <i class="mb-4 text-6xl text-gray-400 fas fa-inbox"></i>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No hay transacciones</h3>
                    <p class="text-gray-500">
                        @if($fechaFiltro || $tipoTransaccionFiltro)
                            No se encontraron transacciones con los filtros aplicados.
                        @else
                            Aún no has realizado ninguna transacción en caja.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
