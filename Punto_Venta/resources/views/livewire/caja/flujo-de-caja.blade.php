<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-exchange-alt mr-3"></i>
                        Flujo de Caja
                    </h1>
                    <p class="text-blue-100 mt-1">Historial de transacciones de mis cajas</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="p-6 border-b bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Filtro de Fecha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-1"></i>
                        Fecha
                    </label>
                    <input type="date" 
                           wire:model.live="fechaFiltro" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filtro de Tipo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter mr-1"></i>
                        Tipo de Transacción
                    </label>
                    <select wire:model.live="tipoTransaccionFiltro" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las transacciones</option>
                        <option value="apertura_caja">Apertura de Caja</option>
                        <option value="cierre_caja">Cierre de Caja</option>
                        <option value="venta">Venta</option>
                        <option value="entrada_efectivo">Entrada de Efectivo</option>
                        <option value="salida_efectivo">Salida de Efectivo</option>
                    </select>
                </div>

                <!-- Botón de Actualizar -->
                <div class="flex items-end">
                    <button wire:click="filtrarTransacciones" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="p-6 border-b bg-blue-50">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Total Efectivo -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-money-bill-wave text-green-500 text-xl mr-2"></i>
                        <span class="text-sm font-medium text-gray-600">Efectivo</span>
                    </div>
                    <div class="text-lg font-bold text-green-600">
                        L. {{ number_format($totalEfectivo, 2) }}
                    </div>
                </div>

                <!-- Total Tarjeta -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-credit-card text-blue-500 text-xl mr-2"></i>
                        <span class="text-sm font-medium text-gray-600">Tarjeta</span>
                    </div>
                    <div class="text-lg font-bold text-blue-600">
                        L. {{ number_format($totalTarjeta, 2) }}
                    </div>
                </div>

                <!-- Total Cheque -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-money-check text-purple-500 text-xl mr-2"></i>
                        <span class="text-sm font-medium text-gray-600">Cheque</span>
                    </div>
                    <div class="text-lg font-bold text-purple-600">
                        L. {{ number_format($totalCheque, 2) }}
                    </div>
                </div>

                <!-- Total General -->
                <div class="text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-calculator text-gray-700 text-xl mr-2"></i>
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
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <!-- Información Principal -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="flex items-center">
                                            @switch($transaccion['transaccion'])
                                                @case('apertura_caja')
                                                    <i class="fas fa-unlock-alt text-green-500 mr-2"></i>
                                                    <span class="font-medium text-green-600">Apertura de Caja</span>
                                                    @break
                                                @case('cierre_caja')
                                                    <i class="fas fa-lock text-red-500 mr-2"></i>
                                                    <span class="font-medium text-red-600">Cierre de Caja</span>
                                                    @break
                                                @case('venta')
                                                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>
                                                    <span class="font-medium text-blue-600">Venta</span>
                                                    @break
                                                @case('entrada_efectivo')
                                                    <i class="fas fa-arrow-down text-green-500 mr-2"></i>
                                                    <span class="font-medium text-green-600">Entrada de Efectivo</span>
                                                    @break
                                                @case('salida_efectivo')
                                                    <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                                                    <span class="font-medium text-red-600">Salida de Efectivo</span>
                                                    @break
                                                @default
                                                    <i class="fas fa-exchange-alt text-gray-500 mr-2"></i>
                                                    <span class="font-medium text-gray-600">{{ ucfirst($transaccion['transaccion']) }}</span>
                                            @endswitch
                                        </div>
                                        
                                        <span class="text-sm text-gray-500">
                                            Caja #{{ $transaccion['caja_id'] }}
                                        </span>
                                    </div>
                                    
                                    @if($transaccion['descripcion'])
                                        <p class="text-sm text-gray-600 mb-2">{{ $transaccion['descripcion'] }}</p>
                                    @endif
                                    
                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaccion['created_at'])->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>

                                <!-- Montos -->
                                <div class="text-right space-y-1">
                                    @if($transaccion['efectivo'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="fas fa-money-bill-wave text-green-500 text-xs"></i>
                                            <span class="text-sm font-medium {{ $transaccion['efectivo'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaccion['efectivo'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['efectivo'], 2) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($transaccion['tarjeta'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="fas fa-credit-card text-blue-500 text-xs"></i>
                                            <span class="text-sm font-medium {{ $transaccion['tarjeta'] > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                                {{ $transaccion['tarjeta'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['tarjeta'], 2) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($transaccion['cheque'] != 0)
                                        <div class="flex items-center justify-end space-x-2">
                                            <i class="fas fa-money-check text-purple-500 text-xs"></i>
                                            <span class="text-sm font-medium {{ $transaccion['cheque'] > 0 ? 'text-purple-600' : 'text-red-600' }}">
                                                {{ $transaccion['cheque'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['cheque'], 2) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- Total de la transacción -->
                                    <hr class="border-gray-200 my-1">
                                    <div class="text-sm font-bold {{ $transaccion['total'] > 0 ? 'text-gray-800' : 'text-red-600' }}">
                                        {{ $transaccion['total'] > 0 ? '+' : '' }}L. {{ number_format($transaccion['total'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay transacciones</h3>
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
