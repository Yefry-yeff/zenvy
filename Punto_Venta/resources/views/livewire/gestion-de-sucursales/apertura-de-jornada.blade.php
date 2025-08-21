<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-green-600 to-green-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center text-2xl font-bold">
                        <i class="mr-3 fas fa-sun"></i>
                        Apertura de Jornada
                    </h1>
                    <p class="mt-1 text-green-100">Iniciar operaciones del día por tienda</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-green-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    @if($nombreTienda)
                        <div class="mt-1 text-xs text-green-200">
                            <i class="mr-1 fas fa-store"></i>
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
                    <div class="p-4 border border-green-200 rounded-lg bg-green-50">
                        <h4 class="mb-2 font-semibold text-green-800">
                            <i class="mr-2 text-green-500 fas fa-store"></i>
                            Apertura de Jornada por Tienda
                        </h4>
                        <p class="text-sm text-green-700">
                            Se aperturará la jornada para: <strong>{{ $nombreTienda }}</strong>
                        </p>
                    </div>

                    <!-- Fecha de Apertura (Solo lectura - fecha actual) -->
                    <div>
                        <label for="fechaApertura" class="block mb-2 text-sm font-medium text-gray-700">
                            <i class="mr-2 text-green-500 fas fa-calendar-alt"></i>
                            Fecha de Apertura
                        </label>
                        <input
                            type="date"
                            id="fechaApertura"
                            wire:model="fechaApertura"
                            class="block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            readonly
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="mr-1 fas fa-info-circle"></i>
                            Solo se puede aperturar la jornada de la fecha actual
                        </p>
                    </div>

                    <!-- Comentario de Apertura -->
                    <div>
                        <label for="comentario" class="block mb-2 text-sm font-medium text-gray-700">
                            <i class="mr-2 text-blue-500 fas fa-comment"></i>
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
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="mr-1 fas fa-info-circle"></i>
                            Máximo 400 caracteres. El comentario se guardará en la jornada.
                        </p>
                    </div>

                    <!-- Información de Validaciones -->
                    <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
                        <h4 class="mb-2 font-semibold text-yellow-800">
                            <i class="mr-2 text-yellow-500 fas fa-shield-alt"></i>
                            Validaciones del Sistema
                        </h4>
                        <ul class="space-y-1 text-sm text-yellow-700">
                            <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Se verificará que no haya una jornada ya aperturada para hoy</li>
                            <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Se validará que la jornada del día anterior esté cerrada</li>
                            <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Si es la primera jornada de la tienda, se permitirá automáticamente</li>
                            <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Solo se puede aperturar la fecha actual</li>
                        </ul>
                    </div>

                    <!-- Estado Actual -->
                    <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                        <h4 class="mb-2 font-semibold text-blue-800">
                            <i class="mr-2 text-blue-500 fas fa-info-circle"></i>
                            Estado Actual
                        </h4>
                        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
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
                            class="flex items-center px-8 py-3 font-semibold text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700"
                            wire:loading.attr="disabled"
                            wire:target="validarYProcesarApertura"
                        >
                            <i class="mr-2 fas fa-sun"></i>
                            <span wire:loading.remove wire:target="validarYProcesarApertura">Aperturar Jornada</span>
                            <span wire:loading wire:target="validarYProcesarApertura">
                                <i class="mr-2 fas fa-spinner fa-spin"></i>
                                Procesando...
                            </span>
                        </button>
                    </div>
                </form>
            @else
                <!-- Usuario sin tienda asignada -->
                <div class="p-6 text-center border border-red-200 rounded-lg bg-red-50">
                    <i class="mb-4 text-3xl text-red-500 fas fa-exclamation-triangle"></i>
                    <h3 class="mb-2 text-xl font-bold text-red-800">Sin Tienda Asignada</h3>
                    <p class="text-red-700">
                        Su usuario no tiene una tienda asignada. Contacte al administrador para poder aperturar jornadas.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
