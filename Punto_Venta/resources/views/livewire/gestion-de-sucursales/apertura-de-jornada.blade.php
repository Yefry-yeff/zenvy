<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-sun mr-3"></i>
                        Apertura de Jornada
                    </h1>
                    <p class="text-green-100 mt-1">Iniciar operaciones del día por tienda</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-green-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    @if($nombreTienda)
                        <div class="text-xs text-green-200 mt-1">
                            <i class="fas fa-store mr-1"></i>
                            {{ $nombreTienda }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="p-6">
            <!-- Mensajes -->
            @if($mensaje)
                <div class="mb-6 p-4 rounded-lg {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : ($tipoMensaje === 'error' ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-blue-50 border border-blue-200 text-blue-800') }}">
                    <div class="flex items-center">
                        <i class="fas {{ $tipoMensaje === 'success' ? 'fa-check-circle text-green-500' : ($tipoMensaje === 'error' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-blue-500') }} mr-3"></i>
                        <span class="font-medium">{{ $mensaje }}</span>
                    </div>
                </div>
            @endif

            <!-- Formulario Principal -->
            @if($tiendaUsuario)
                <form wire:submit.prevent="validarYProcesarApertura" class="space-y-6">
                    <!-- Información de la Tienda -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="font-semibold text-green-800 mb-2">
                            <i class="fas fa-store text-green-500 mr-2"></i>
                            Apertura de Jornada por Tienda
                        </h4>
                        <p class="text-sm text-green-700">
                            Se aperturará la jornada para: <strong>{{ $nombreTienda }}</strong>
                        </p>
                    </div>

                    <!-- Fecha de Apertura (Solo lectura - fecha actual) -->
                    <div>
                        <label for="fechaApertura" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
                            Fecha de Apertura
                        </label>
                        <input 
                            type="date" 
                            id="fechaApertura"
                            wire:model="fechaApertura"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            readonly
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Solo se puede aperturar la jornada de la fecha actual
                        </p>
                    </div>

                    <!-- Comentario de Apertura -->
                    <div>
                        <label for="comentario" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-comment text-blue-500 mr-2"></i>
                            Comentario de Apertura (Opcional)
                        </label>
                        <textarea 
                            id="comentario"
                            wire:model="comentario"
                            rows="3"
                            maxlength="400"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            placeholder="Escriba un comentario sobre la apertura de jornada..."
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Máximo 400 caracteres. El comentario se guardará en la jornada.
                        </p>
                    </div>

                    <!-- Información de Validaciones -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-shield-alt text-yellow-500 mr-2"></i>
                            Validaciones del Sistema
                        </h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li><i class="fas fa-check text-yellow-600 mr-2"></i>Se verificará que no haya una jornada ya aperturada para hoy</li>
                            <li><i class="fas fa-check text-yellow-600 mr-2"></i>Se validará que la jornada del día anterior esté cerrada</li>
                            <li><i class="fas fa-check text-yellow-600 mr-2"></i>Si es la primera jornada de la tienda, se permitirá automáticamente</li>
                            <li><i class="fas fa-check text-yellow-600 mr-2"></i>Solo se puede aperturar la fecha actual</li>
                        </ul>
                    </div>

                    <!-- Estado Actual -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Estado Actual
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700">Fecha actual:</span>
                                <span class="font-semibold text-blue-900">{{ $fechaApertura }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Tienda:</span>
                                <span class="font-semibold text-blue-900">{{ $nombreTienda }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Usuario:</span>
                                <span class="font-semibold text-blue-900">{{ Auth::user()->name }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700">Acción:</span>
                                <span class="font-semibold text-green-700">Aperturar Jornada</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Acción -->
                    <div class="flex justify-end space-x-4">
                        <button 
                            type="submit"
                            class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition-colors flex items-center"
                            wire:loading.attr="disabled"
                            wire:target="validarYProcesarApertura"
                        >
                            <i class="fas fa-sun mr-2"></i>
                            <span wire:loading.remove wire:target="validarYProcesarApertura">Aperturar Jornada</span>
                            <span wire:loading wire:target="validarYProcesarApertura">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Procesando...
                            </span>
                        </button>
                    </div>
                </form>
            @else
                <!-- Usuario sin tienda asignada -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                    <h3 class="text-xl font-bold text-red-800 mb-2">Sin Tienda Asignada</h3>
                    <p class="text-red-700">
                        Su usuario no tiene una tienda asignada. Contacte al administrador para poder aperturar jornadas.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
