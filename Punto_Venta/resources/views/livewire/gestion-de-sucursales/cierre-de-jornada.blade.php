<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-red-600 to-red-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center text-2xl font-bold">
                        <i class="mr-3 fas fa-calendar-times"></i>
                        Cierre de Jornada
                    </h1>
                    <p class="mt-1 text-red-100">Finalizar operaciones del día por tienda</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-red-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    @if($nombreTienda)
                        <div class="mt-1 text-xs text-red-200">
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

            <!-- Modal de Alerta -->
            @if($mostrarAlerta)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="w-full max-w-2xl p-6 mx-4 overflow-y-auto bg-white rounded-lg max-h-96">
                        <div class="flex items-center mb-4">
                            <i class="mr-3 text-2xl text-yellow-500 fas fa-exclamation-triangle"></i>
                            <h3 class="text-xl font-bold text-gray-900">Advertencias de Cierre</h3>
                        </div>

                        <!-- Cajas Abiertas -->
                        @if(count($cajasAbiertas) > 0)
                            <div class="mb-4">
                                <h4 class="mb-2 font-semibold text-red-600">
                                    <i class="mr-2 text-red-500 fas fa-unlock"></i>
                                    Cajas Abiertas ({{ count($cajasAbiertas) }})
                                </h4>
                                <div class="p-3 border border-red-200 rounded bg-red-50">
                                    @foreach($cajasAbiertas as $caja)
                                        <div class="flex items-center justify-between py-2 border-b border-red-200 last:border-b-0">
                                            <div>
                                                <div class="font-medium text-gray-800">
                                                    <i class="mr-1 text-red-500 fas fa-cash-register"></i>
                                                    Caja #{{ $caja->id }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    <i class="mr-1 text-blue-500 fas fa-user"></i>
                                                    Usuario: {{ $caja->nombre_usuario ?? 'Usuario ID: ' . $caja->users_id }}
                                                </div>
                                            </div>
                                            <span class="font-semibold text-red-600">L. {{ number_format($caja->balance, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-sm text-red-600">
                                    <i class="mr-1 fas fa-info-circle"></i>
                                    Estas cajas se cerrarán automáticamente. Si tienen balance, se registrará como diferencia.
                                </p>
                            </div>
                        @endif

                        <!-- Cajas con Diferencias -->
                        @if(count($cajasConDiferencia) > 0)
                            <div class="mb-6">
                                <h4 class="mb-2 font-semibold text-orange-600">
                                    <i class="mr-2 text-orange-500 fas fa-exclamation-circle"></i>
                                    Cajas con Diferencias ({{ count($cajasConDiferencia) }})
                                </h4>
                                <div class="p-3 border border-orange-200 rounded bg-orange-50">
                                    @foreach($cajasConDiferencia as $caja)
                                        <div class="py-2 border-b border-orange-200 last:border-b-0 last:pb-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-800">
                                                        <i class="mr-1 text-orange-500 fas fa-cash-register"></i>
                                                        Caja #{{ $caja->id }}
                                                    </div>
                                                    <div class="mt-1 text-sm text-gray-600">
                                                        <i class="mr-1 text-blue-500 fas fa-user"></i>
                                                        <strong>Usuario:</strong> {{ $caja->nombre_usuario ?? 'Usuario ID: ' . $caja->users_id }}
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="mr-1 text-green-500 fas fa-calendar-alt"></i>
                                                        <strong>Fecha del cierre:</strong> {{ \Carbon\Carbon::parse($caja->fecha_cierre)->format('d/m/Y H:i:s') }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="space-y-1">
                                                        @if($caja->diferencia_efectivo != 0)
                                                            <div class="flex items-center justify-end">
                                                                <i class="mr-2 text-green-500 fas fa-money-bill-wave"></i>
                                                                <span class="mr-2 text-sm font-medium">Efectivo:</span>
                                                                <span class="font-bold {{ $caja->diferencia_efectivo > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                    {{ $caja->diferencia_efectivo > 0 ? '+' : '' }}L. {{ number_format($caja->diferencia_efectivo, 2) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if($caja->diferencia_tarjeta != 0)
                                                            <div class="flex items-center justify-end">
                                                                <i class="mr-2 text-blue-500 fas fa-credit-card"></i>
                                                                <span class="mr-2 text-sm font-medium">Tarjeta:</span>
                                                                <span class="font-bold {{ $caja->diferencia_tarjeta > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                    {{ $caja->diferencia_tarjeta > 0 ? '+' : '' }}L. {{ number_format($caja->diferencia_tarjeta, 2) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if($caja->diferencia_cheque != 0)
                                                            <div class="flex items-center justify-end">
                                                                <i class="mr-2 text-purple-500 fas fa-money-check"></i>
                                                                <span class="mr-2 text-sm font-medium">Cheque:</span>
                                                                <span class="font-bold {{ $caja->diferencia_cheque > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                    {{ $caja->diferencia_cheque > 0 ? '+' : '' }}L. {{ number_format($caja->diferencia_cheque, 2) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-sm text-orange-600">
                                    <i class="mr-1 fas fa-info-circle"></i>
                                    Estas cajas tuvieron diferencias en sus cierres del día (efectivo, tarjeta o cheque). Se muestra el usuario responsable y la fecha del cierre.
                                </p>
                            </div>
                        @endif

                        <!-- Resumen de Transacciones por Caja -->
                        @if(count($transaccionesPorCaja) > 0)
                            <div class="mb-6">
                                <h4 class="mb-2 font-semibold text-blue-600">
                                    <i class="mr-2 fas fa-exchange-alt"></i>
                                    Transacciones Registradas por Caja ({{ count($transaccionesPorCaja) }})
                                </h4>
                                <div class="overflow-y-auto border border-blue-200 rounded-lg max-h-48">
                                    @foreach($transaccionesPorCaja as $caja)
                                        <div class="flex items-start justify-between p-3 border-b border-blue-100 last:border-b-0">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-800">
                                                    <i class="mr-1 text-blue-500 fas fa-cash-register"></i>
                                                    Caja #{{ $caja->caja_id }}
                                                </div>
                                                <div class="mt-1 text-sm text-gray-600">
                                                    <i class="mr-1 text-green-500 fas fa-user"></i>
                                                    <strong>Usuario:</strong> {{ $caja->nombre_usuario }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    <i class="mr-1 text-orange-500 fas fa-wallet"></i>
                                                    <strong>Balance Actual:</strong> L. {{ number_format($caja->balance_actual, 2) }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="space-y-1">
                                                    @if($caja->total_efectivo_ingreso > 0 || $caja->total_efectivo_egreso > 0)
                                                        <div class="flex items-center justify-end">
                                                            <i class="mr-2 text-green-500 fas fa-money-bill-wave"></i>
                                                            <span class="mr-2 text-xs font-medium">Efectivo:</span>
                                                            <span class="text-xs">
                                                                <span class="text-green-600">+L. {{ number_format($caja->total_efectivo_ingreso, 2) }}</span>
                                                                @if($caja->total_efectivo_egreso > 0)
                                                                    <span class="ml-1 text-red-600">-L. {{ number_format($caja->total_efectivo_egreso, 2) }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if($caja->total_tarjeta_ingreso > 0 || $caja->total_tarjeta_egreso > 0)
                                                        <div class="flex items-center justify-end">
                                                            <i class="mr-2 text-blue-500 fas fa-credit-card"></i>
                                                            <span class="mr-2 text-xs font-medium">Tarjeta:</span>
                                                            <span class="text-xs">
                                                                <span class="text-green-600">+L. {{ number_format($caja->total_tarjeta_ingreso, 2) }}</span>
                                                                @if($caja->total_tarjeta_egreso > 0)
                                                                    <span class="ml-1 text-red-600">-L. {{ number_format($caja->total_tarjeta_egreso, 2) }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if($caja->total_cheque_ingreso > 0 || $caja->total_cheque_egreso > 0)
                                                        <div class="flex items-center justify-end">
                                                            <i class="mr-2 text-purple-500 fas fa-money-check"></i>
                                                            <span class="mr-2 text-xs font-medium">Cheque:</span>
                                                            <span class="text-xs">
                                                                <span class="text-green-600">+L. {{ number_format($caja->total_cheque_ingreso, 2) }}</span>
                                                                @if($caja->total_cheque_egreso > 0)
                                                                    <span class="ml-1 text-red-600">-L. {{ number_format($caja->total_cheque_egreso, 2) }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div class="flex items-center justify-end pt-1 mt-2 border-t border-gray-200">
                                                        <span class="text-xs font-bold {{ $caja->total_neto >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            Total Neto: {{ $caja->total_neto >= 0 ? '+' : '' }}L. {{ number_format($caja->total_neto, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-sm text-blue-600">
                                    <i class="mr-1 fas fa-info-circle"></i>
                                    Resumen de todas las transacciones registradas en las cajas durante la fecha de cierre. No incluye cajas que ya tienen diferencias registradas.
                                </p>
                            </div>
                        @endif

                        <!-- Botones del Modal -->
                        <div class="flex justify-end space-x-4">
                            <button
                                wire:click="cancelarCierre"
                                class="px-6 py-2 font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                <i class="mr-2 fas fa-times"></i>
                                Cancelar
                            </button>
                            <button
                                wire:click="procesarCierreJornada"
                                class="px-6 py-2 font-medium text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700"
                                wire:loading.attr="disabled"
                            >
                                <i class="mr-2 fas fa-check"></i>
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
                        <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                            <h4 class="mb-2 font-semibold text-blue-800">
                                <i class="mr-2 text-blue-500 fas fa-store"></i>
                                Cierre de Jornada por Tienda
                            </h4>
                            <p class="text-sm text-blue-700">
                                Este cierre procesará únicamente las cajas de: <strong>{{ $nombreTienda }}</strong>
                            </p>
                        </div>

                        <!-- Fecha de Cierre -->
                        <div>
                            <label for="fechaCierre" class="block mb-2 text-sm font-medium text-gray-700">
                                <i class="mr-2 text-red-500 fas fa-calendar-alt"></i>
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
                            <label for="comentario" class="block mb-2 text-sm font-medium text-gray-700">
                                <i class="mr-2 text-blue-500 fas fa-comment"></i>
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
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="mr-1 fas fa-info-circle"></i>
                                Máximo 400 caracteres. El comentario se guardará en la jornada.
                            </p>
                        </div>
                        <!-- Información Previa -->
                        <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
                            <h4 class="mb-2 font-semibold text-yellow-800">
                                <i class="mr-2 text-yellow-500 fas fa-exclamation-triangle"></i>
                                Validaciones del Sistema
                            </h4>
                            <ul class="space-y-1 text-sm text-yellow-700">
                                <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Se verificará que la jornada esté aperturada para la fecha</li>
                                <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Se detectarán cajas abiertas y diferencias en tu tienda</li>
                                <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Se cerrará oficialmente la jornada de la tienda</li>
                                <li><i class="mr-2 text-yellow-600 fas fa-check"></i>Las cajas abiertas se cerrarán automáticamente</li>
                            </ul>
                        </div>

                        <!-- Botón de Acción -->
                        <div class="flex justify-end space-x-4">
                            <button
                                type="button"
                                onclick="window.history.back()"
                                class="px-6 py-3 font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                <i class="mr-2 fas fa-arrow-left"></i>
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-8 py-3 font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:scale-105"
                                wire:loading.attr="disabled"
                            >
                                <i class="mr-2 fas fa-calendar-times"></i>
                                <span wire:loading.remove>Verificar y Cerrar Jornada</span>
                                <span wire:loading>Verificando...</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="py-8 text-center">
                        <i class="mb-4 text-4xl text-yellow-500 fas fa-exclamation-triangle"></i>
                        <h3 class="mb-2 text-lg font-medium text-gray-900">Usuario sin tienda asignada</h3>
                        <p class="mb-4 text-gray-600">No se puede procesar el cierre de jornada sin una tienda asignada.</p>
                        <button
                            onclick="window.history.back()"
                            class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700"
                        >
                            <i class="mr-2 fas fa-arrow-left"></i>
                            Volver
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Información del Proceso -->
    <div class="p-4 mt-6 rounded-lg bg-gray-50">
        <h4 class="mb-2 font-semibold text-gray-800">
            <i class="mr-2 text-blue-500 fas fa-info-circle"></i>
            ¿Qué hace el Cierre de Jornada?
        </h4>
        <div class="grid grid-cols-1 gap-4 text-sm text-gray-600 md:grid-cols-2">
            <div>
                <h5 class="mb-1 font-medium text-gray-700">Proceso Automático:</h5>
                <ul class="space-y-1">
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Registra el cierre en tabla jornada</li>
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Cambia apertura a 0 y cierre a 1</li>
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Cierra todas las cajas abiertas (estado 2)</li>
                </ul>
            </div>
            <div>
                <h5 class="mb-1 font-medium text-gray-700">Manejo de Diferencias:</h5>
                <ul class="space-y-1">
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Detecta cajas con balance pendiente</li>
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Registra el balance como diferencia</li>
                    <li><i class="mr-2 text-gray-400 fas fa-arrow-right"></i>Muestra alertas antes de procesar</li>
                </ul>
            </div>
        </div>
    </div>
</div>
