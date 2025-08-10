<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-calendar-times mr-3"></i>
                        Cierre de Jornada
                    </h1>
                    <p class="text-red-100 mt-1">Finalizar operaciones del día por tienda</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-red-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    @if($nombreTienda)
                        <div class="text-xs text-red-200 mt-1">
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

            <!-- Modal de Alerta -->
            @if($mostrarAlerta)
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
                            <h3 class="text-xl font-bold text-gray-900">Advertencias de Cierre</h3>
                        </div>

                        <!-- Cajas Abiertas -->
                        @if(count($cajasAbiertas) > 0)
                            <div class="mb-4">
                                <h4 class="font-semibold text-red-600 mb-2">
                                    <i class="fas fa-unlock text-red-500 mr-2"></i>
                                    Cajas Abiertas ({{ count($cajasAbiertas) }})
                                </h4>
                                <div class="bg-red-50 border border-red-200 rounded p-3">
                                    @foreach($cajasAbiertas as $caja)
                                        <div class="flex justify-between items-center py-2 border-b border-red-200 last:border-b-0">
                                            <div>
                                                <div class="font-medium text-gray-800">
                                                    <i class="fas fa-cash-register text-red-500 mr-1"></i>
                                                    Caja #{{ $caja->id }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    <i class="fas fa-user text-blue-500 mr-1"></i>
                                                    Usuario: {{ $caja->nombre_usuario ?? 'Usuario ID: ' . $caja->users_id }}
                                                </div>
                                            </div>
                                            <span class="font-semibold text-red-600">L. {{ number_format($caja->balance, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-sm text-red-600 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Estas cajas se cerrarán automáticamente. Si tienen balance, se registrará como diferencia.
                                </p>
                            </div>
                        @endif

                        <!-- Cajas con Diferencias -->
                        @if(count($cajasConDiferencia) > 0)
                            <div class="mb-6">
                                <h4 class="font-semibold text-orange-600 mb-2">
                                    <i class="fas fa-exclamation-circle text-orange-500 mr-2"></i>
                                    Cajas con Diferencias ({{ count($cajasConDiferencia) }})
                                </h4>
                                <div class="bg-orange-50 border border-orange-200 rounded p-3">
                                    @foreach($cajasConDiferencia as $caja)
                                        <div class="border-b border-orange-200 last:border-b-0 py-2 last:pb-0">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-800">
                                                        <i class="fas fa-cash-register text-orange-500 mr-1"></i>
                                                        Caja #{{ $caja->id }}
                                                    </div>
                                                    <div class="text-sm text-gray-600 mt-1">
                                                        <i class="fas fa-user text-blue-500 mr-1"></i>
                                                        <strong>Usuario:</strong> {{ $caja->nombre_usuario ?? 'Usuario ID: ' . $caja->users_id }}
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-calendar-alt text-green-500 mr-1"></i>
                                                        <strong>Fecha del cierre:</strong> {{ \Carbon\Carbon::parse($caja->created_at)->format('d/m/Y H:i:s') }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-bold text-lg {{ $caja->diferencia_efectivo > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $caja->diferencia_efectivo > 0 ? '+' : '' }}L. {{ number_format($caja->diferencia_efectivo, 2) }}
                                                    </span>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $caja->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-sm text-orange-600 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Estas cajas tuvieron diferencias en sus cierres del día. Se muestra el usuario responsable y la fecha del cierre.
                                </p>
                            </div>
                        @endif

                        <!-- Botones del Modal -->
                        <div class="flex justify-end space-x-4">
                            <button 
                                wire:click="cancelarCierre"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                            >
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                            <button 
                                wire:click="procesarCierreJornada"
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors"
                                wire:loading.attr="disabled"
                            >
                                <i class="fas fa-check mr-2"></i>
                                <span wire:loading.remove>Aceptar y Cerrar Jornada</span>
                                <span wire:loading>Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Formulario Principal -->
            @if(!$mostrarAlerta)
                @if($tiendaUsuario)
                    <form wire:submit.prevent="verificarCondicionesParaCierre" class="space-y-6">
                        <!-- Información de la Tienda -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-store text-blue-500 mr-2"></i>
                                Cierre de Jornada por Tienda
                            </h4>
                            <p class="text-sm text-blue-700">
                                Este cierre procesará únicamente las cajas de: <strong>{{ $nombreTienda }}</strong>
                            </p>
                        </div>

                        <!-- Fecha de Cierre -->
                        <div>
                            <label for="fechaCierre" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt text-red-500 mr-2"></i>
                                Fecha de Cierre
                            </label>
                            <input 
                                type="date" 
                                id="fechaCierre"
                                wire:model="fechaCierre"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                required
                            >
                        </div>

                        <!-- Comentario de Cierre -->
                        <div>
                            <label for="comentario" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-comment text-blue-500 mr-2"></i>
                                Comentario de Cierre (Opcional)
                            </label>
                            <textarea 
                                id="comentario"
                                wire:model="comentario"
                                rows="3"
                                maxlength="400"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Escriba un comentario sobre el cierre de jornada..."
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Máximo 400 caracteres. El comentario se guardará en la jornada.
                            </p>
                        </div>
                        <!-- Información Previa -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                Validaciones del Sistema
                            </h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li><i class="fas fa-check text-yellow-600 mr-2"></i>Se verificará que la jornada esté aperturada para la fecha</li>
                                <li><i class="fas fa-check text-yellow-600 mr-2"></i>Se detectarán cajas abiertas y diferencias en tu tienda</li>
                                <li><i class="fas fa-check text-yellow-600 mr-2"></i>Se cerrará oficialmente la jornada de la tienda</li>
                                <li><i class="fas fa-check text-yellow-600 mr-2"></i>Las cajas abiertas se cerrarán automáticamente</li>
                            </ul>
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
                                class="px-8 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 font-medium shadow-lg transform hover:scale-105 transition-all duration-200"
                                wire:loading.attr="disabled"
                            >
                                <i class="fas fa-calendar-times mr-2"></i>
                                <span wire:loading.remove>Verificar y Cerrar Jornada</span>
                                <span wire:loading>Verificando...</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Usuario sin tienda asignada</h3>
                        <p class="text-gray-600 mb-4">No se puede procesar el cierre de jornada sin una tienda asignada.</p>
                        <button 
                            onclick="window.history.back()"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Información del Proceso -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 mb-2">
            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
            ¿Qué hace el Cierre de Jornada?
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <h5 class="font-medium text-gray-700 mb-1">Proceso Automático:</h5>
                <ul class="space-y-1">
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Registra el cierre en tabla jornada</li>
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Cambia apertura a 0 y cierre a 1</li>
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Cierra todas las cajas abiertas (estado 2)</li>
                </ul>
            </div>
            <div>
                <h5 class="font-medium text-gray-700 mb-1">Manejo de Diferencias:</h5>
                <ul class="space-y-1">
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Detecta cajas con balance pendiente</li>
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Registra el balance como diferencia</li>
                    <li><i class="fas fa-arrow-right text-gray-400 mr-2"></i>Muestra alertas antes de procesar</li>
                </ul>
            </div>
        </div>
    </div>
</div>
