<div>
    <div class="min-h-screen p-6 bg-gradient-to-br from-blue-50 via-white to-green-50">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="p-6 mb-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Recepción de Efectivo</h1>
                        <p class="text-gray-600">Registrar entrada de dinero en caja</p>
                    </div>
                </div>
                <button wire:click="$dispatch('cambiarVista', 'caja.index')"
                        class="flex items-center px-4 py-2 space-x-2 font-semibold text-white transition-colors duration-200 bg-gray-500 rounded-lg hover:bg-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Volver</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Información de Caja Actual -->
            <div class="lg:col-span-1">
                <div class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
                    <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Estado de Caja
                    </h2>

                    @if($cajaActual)
                        <div class="space-y-4">
                            <div class="p-4 border border-green-200 rounded-lg bg-green-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-green-800">Estado:</span>
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                        {{ $cajaActual->estado_caja == 1 ? 'Abierta' : 'Cerrada' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-green-800">Saldo Actual:</span>
                                    <span class="text-lg font-bold text-green-600">
                                        L.{{ number_format($cajaActual->balance ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-green-800">Fecha Apertura:</span>
                                    <span class="text-sm text-green-600">
                                        {{ \Carbon\Carbon::parse($cajaActual->fecha_apertura)->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">No hay caja abierta</p>
                                    <p class="text-xs text-red-600">Debe abrir una caja antes de recibir efectivo</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Formulario de Recepción -->
            <div class="lg:col-span-2">
                <div class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl">
                    <h2 class="flex items-center mb-6 text-lg font-semibold text-gray-800">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Recibir Efectivo
                    </h2>

                    <!-- Mensajes -->
                    @if($mensajeExito)
                        <div class="p-4 mb-6 border border-green-200 rounded-lg bg-green-50" x-data="{ show: true }" x-show="show">
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
                        <div class="p-4 mb-6 border border-red-200 rounded-lg bg-red-50" x-data="{ show: true }" x-show="show">
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

                    <form wire:submit="recibirEfectivo" class="space-y-6">
                        <!-- Monto -->
                        <div>
                            <label for="monto" class="block mb-2 text-sm font-semibold text-gray-700">
                                Monto a Recibir *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <span class="text-lg font-semibold text-gray-500">L.</span>
                                </div>
                                <input wire:model="monto"
                                       type="number"
                                       id="monto"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="0.00"
                                       @if(!$cajaActual) disabled @endif
                                       class="block w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-lg font-semibold
                                              {{ !$cajaActual ? 'bg-gray-100 cursor-not-allowed border-gray-300' : ($errors->has('monto') ? 'border-red-500' : 'border-gray-300') }}">
                            </div>
                            @error('monto')
                                <p class="flex items-center mt-1 text-sm text-red-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Comentarios -->
                        <div>
                            <label for="comentarios" class="block mb-2 text-sm font-semibold text-gray-700">
                                Comentarios
                            </label>
                            <textarea wire:model="comentarios"
                                      id="comentarios"
                                      rows="3"
                                      placeholder="Descripción adicional del recibo de efectivo (opcional)"
                                      @if(!$cajaActual) disabled @endif
                                      class="block w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none
                                             {{ !$cajaActual ? 'bg-gray-100 cursor-not-allowed border-gray-300' : ($errors->has('comentarios') ? 'border-red-500' : 'border-gray-300') }}"></textarea>
                            @error('comentarios')
                                <p class="flex items-center mt-1 text-sm text-red-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Botones -->
                        <div class="flex pt-4 space-x-4">
                            <button type="submit"
                                    @if(!$cajaActual) disabled @endif
                                    class="flex items-center justify-center flex-1 px-6 py-3 space-x-2 font-semibold text-white transition-colors duration-200 bg-green-600 rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Recibir Efectivo</span>
                            </button>

                            <button type="button"
                                    wire:click="$set('monto', ''); $set('comentarios', '')"
                                    class="flex items-center justify-center px-6 py-3 space-x-2 font-semibold text-white transition-colors duration-200 bg-gray-500 rounded-lg hover:bg-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Limpiar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="p-6 mt-6 border border-blue-200 bg-blue-50 rounded-2xl">
            <h3 class="flex items-center mb-3 text-lg font-semibold text-blue-800">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Información Importante
            </h3>
            <div class="grid grid-cols-1 gap-4 text-sm text-blue-700 md:grid-cols-2">
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Esta operación registrará el recibo de efectivo en el sistema de transacciones</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>El saldo de la caja se actualizará automáticamente con el monto ingresado</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Se requiere tener una caja abierta para poder recibir efectivo</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Los comentarios ayudan a mantener un registro detallado de las operaciones</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
