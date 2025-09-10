<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-unlock mr-3"></i>
                        Apertura de Caja
                    </h1>
                    <p class="text-green-100 mt-1">Aperturar caja con saldo inicial</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-green-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="p-6">
            <!-- Mensajes -->
            @if($mensaje)
                <div class="mb-6 p-4 rounded-lg {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
                    <div class="flex items-center">
                        <i class="fas {{ $tipoMensaje === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500' }} mr-3"></i>
                        <span class="font-medium">{{ $mensaje }}</span>
                    </div>
                </div>
            @endif

            <!-- Estado de la Caja -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>
                    Estado de la Caja
                </h3>
                @if($cajaActual)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Caja ID:</span>
                            <span class="font-semibold text-blue-800 ml-2">#{{ $cajaActual->id }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Estado:</span>
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium ml-2">Cerrada</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Balance Anterior:</span>
                            <span class="font-semibold text-blue-800 ml-2">L. {{ number_format($cajaActual->balance ?? 0, 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-blue-700">
                        <i class="fas fa-check-circle mr-1"></i>
                        Caja disponible para aperturar
                    </div>
                @else
                    <div class="text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No hay caja disponible para aperturar
                    </div>
                @endif
            </div>

            <!-- Formulario -->
            @if($cajaActual)
                <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                    <h3 class="text-lg font-semibold text-green-800 mb-2">
                        <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                        Balance de Apertura
                    </h3>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-700 mb-2">
                            L. {{ number_format($cajaActual->balance ?? 0, 2) }}
                        </div>
                        <p class="text-sm text-green-600">
                            La caja se aperturará con el balance anterior
                        </p>
                    </div>
                </div>

                <form wire:submit.prevent="aperturarCaja" class="space-y-6">
                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-comment text-blue-500 mr-2"></i>
                            Descripción (Opcional)
                        </label>
                        <textarea 
                            id="descripcion"
                            wire:model="descripcion"
                            rows="3"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            placeholder="Descripción de la apertura de caja..."
                        ></textarea>
                        @error('descripcion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botón de Acción -->
                    <div class="flex justify-end space-x-4">
                        <button 
                            type="button"
                            onclick="window.history.back()"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 font-medium shadow-lg transform hover:scale-105 transition-all duration-200"
                        >
                            <i class="fas fa-unlock mr-2"></i>
                            Aperturar Caja
                        </button>
                    </div>
                </form>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-times-circle text-yellow-500 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay caja disponible para aperturar</h3>
                    <p class="text-gray-600 mb-4">No se encontró una caja cerrada disponible para aperturar, o ya tienes una caja abierta para hoy.</p>
                    <button 
                        onclick="window.history.back()"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 mb-2">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
            Información Importante
        </h4>
        <ul class="text-sm text-gray-600 space-y-1">
            <li><i class="fas fa-check text-green-500 mr-2"></i>Solo puedes aperturar caja cuando está cerrada</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i>Se registrará una transacción de apertura</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i>El estado de la caja cambiará a "Abierta"</li>
            <li><i class="fas fa-check text-green-500 mr-2"></i>Si ya cerraste una caja hoy, se creará un nuevo registro</li>
        </ul>
    </div>
</div>
