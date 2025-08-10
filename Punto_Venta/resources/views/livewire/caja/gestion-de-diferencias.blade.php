<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-balance-scale mr-3"></i>
                        Gestión de Diferencias
                    </h1>
                    <p class="text-orange-100 mt-1">Administrar diferencias de caja por tienda</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-orange-100">Usuario</div>
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    @if($nombreTienda)
                        <div class="text-xs text-orange-200 mt-1">
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
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas {{ $tipoMensaje === 'success' ? 'fa-check-circle text-green-500' : ($tipoMensaje === 'error' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-blue-500') }} mr-3"></i>
                            <span class="font-medium">{{ $mensaje }}</span>
                        </div>
                        <button wire:click="limpiarMensaje" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if($tiendaUsuario && $mensaje === '')
                <!-- Información de la Tienda -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Diferencias de {{ $nombreTienda }}
                    </h4>
                    <p class="text-sm text-blue-700">
                        Mostrando todas las cajas con diferencias de efectivo de su tienda. Haga clic en una fila para gestionar la diferencia.
                    </p>
                </div>

                <!-- Lista de Diferencias -->
                @if(count($diferencias) > 0)
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-list mr-2 text-orange-500"></i>
                                Cajas con Diferencias ({{ count($diferencias) }})
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-cash-register mr-1"></i>
                                            Caja
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-user mr-1"></i>
                                            Usuario
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-calendar mr-1"></i>
                                            Fecha del Cierre
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-calculator mr-1"></i>
                                            Diferencia Original
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Gestionado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Pendiente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <i class="fas fa-cogs mr-1"></i>
                                            Estado
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($diferencias as $diferencia)
                                        <tr class="hover:bg-orange-50 cursor-pointer transition-colors duration-150" 
                                            wire:click="abrirModal({{ $diferencia->cierre_id }})">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center">
                                                            <i class="fas fa-cash-register text-orange-600 text-sm"></i>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            Caja #{{ $diferencia->caja_id }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            ID Cierre: {{ $diferencia->cierre_id }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $diferencia->nombre_usuario }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: {{ $diferencia->users_id }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ \Carbon\Carbon::parse($diferencia->created_at)->format('d/m/Y') }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($diferencia->created_at)->format('H:i:s') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $diferencia->diferencia_efectivo > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    <i class="fas {{ $diferencia->diferencia_efectivo > 0 ? 'fa-plus' : 'fa-minus' }} mr-1"></i>
                                                    L. {{ number_format(abs($diferencia->diferencia_efectivo), 2) }}
                                                </span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $diferencia->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-blue-600">
                                                    L. {{ number_format($diferencia->total_gestionado, 2) }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $diferencia->gestiones_realizadas }} gestión(es)
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ abs($diferencia->diferencia_pendiente) < 0.01 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    <i class="fas {{ abs($diferencia->diferencia_pendiente) < 0.01 ? 'fa-check' : 'fa-clock' }} mr-1"></i>
                                                    L. {{ number_format(abs($diferencia->diferencia_pendiente), 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if(abs($diferencia->diferencia_pendiente) < 0.01)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Resuelto
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        Pendiente
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <i class="fas fa-balance-scale text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay diferencias pendientes</h3>
                        <p class="text-gray-600 mb-4">No se encontraron cajas con diferencias de efectivo en su tienda.</p>
                        <button 
                            wire:click="cargarDiferencias"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
                        >
                            <i class="fas fa-sync-alt mr-2"></i>
                            Actualizar
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Modal de Gestión de Diferencia -->
    @if($mostrarModal && $diferenciaSeleccionada)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-edit text-orange-500 mr-3"></i>
                        Gestionar Diferencia
                    </h3>
                    <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Información de la Transacción -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-3">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Información de la Transacción
                    </h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong class="text-gray-700">Caja:</strong>
                            <span class="text-gray-900">#{{ $diferenciaSeleccionada->caja_id }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Usuario:</strong>
                            <span class="text-gray-900">{{ $diferenciaSeleccionada->nombre_usuario }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Fecha del cierre:</strong>
                            <span class="text-gray-900">{{ \Carbon\Carbon::parse($diferenciaSeleccionada->created_at)->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Total esperado:</strong>
                            <span class="text-gray-900">L. {{ number_format($diferenciaSeleccionada->total_efectivo, 2) }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Total contado:</strong>
                            <span class="text-gray-900">L. {{ number_format($diferenciaSeleccionada->conteo_efectivo, 2) }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Diferencia original:</strong>
                            <span class="font-bold {{ $diferenciaSeleccionada->diferencia_efectivo > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $diferenciaSeleccionada->diferencia_efectivo > 0 ? '+' : '' }}L. {{ number_format($diferenciaSeleccionada->diferencia_efectivo, 2) }}
                                ({{ $diferenciaSeleccionada->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante' }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Estado Actual -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-yellow-800 mb-3">
                        <i class="fas fa-chart-line text-yellow-500 mr-2"></i>
                        Estado Actual de la Diferencia
                    </h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong class="text-gray-700">Total gestionado:</strong>
                            <span class="text-blue-600 font-medium">L. {{ number_format($diferenciaSeleccionada->total_gestionado, 2) }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Diferencia pendiente:</strong>
                            <span class="font-bold {{ abs($diferenciaSeleccionada->diferencia_pendiente) < 0.01 ? 'text-green-600' : 'text-orange-600' }}">
                                L. {{ number_format(abs($diferenciaSeleccionada->diferencia_pendiente), 2) }}
                            </span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Gestiones realizadas:</strong>
                            <span class="text-gray-900">{{ $diferenciaSeleccionada->gestiones_realizadas }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Estado:</strong>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ abs($diferenciaSeleccionada->diferencia_pendiente) < 0.01 ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ abs($diferenciaSeleccionada->diferencia_pendiente) < 0.01 ? 'Resuelto' : 'Pendiente' }}
                            </span>
                        </div>
                    </div>
                </div>

                @if(abs($diferenciaSeleccionada->diferencia_pendiente) >= 0.01)
                    <!-- Formulario de Gestión -->
                    <form wire:submit.prevent="gestionarDiferencia" class="space-y-4">
                        <div>
                            <label for="monto" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-dollar-sign text-green-500 mr-2"></i>
                                Monto a Gestionar
                            </label>
                            <input 
                                type="number" 
                                id="monto"
                                wire:model="monto"
                                step="0.01"
                                min="0.01"
                                max="{{ abs($diferenciaSeleccionada->diferencia_pendiente) }}"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('monto') border-red-500 @enderror"
                                placeholder="0.00"
                                required
                            >
                            @error('monto') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                Máximo permitido: L. {{ number_format(abs($diferenciaSeleccionada->diferencia_pendiente), 2) }}
                            </p>
                        </div>

                        <div>
                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-comment text-blue-500 mr-2"></i>
                                Descripción / Justificación
                            </label>
                            <textarea 
                                id="descripcion"
                                wire:model="descripcion"
                                rows="4"
                                maxlength="400"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('descripcion') border-red-500 @enderror"
                                placeholder="Describa la justificación para gestionar esta diferencia..."
                                required
                            ></textarea>
                            @error('descripcion') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                Máximo 400 caracteres. Explique el motivo de la gestión.
                            </p>
                        </div>

                        <!-- Botones del Modal -->
                        <div class="flex justify-end space-x-4 pt-4">
                            <button 
                                type="button"
                                wire:click="cerrarModal"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                            >
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </button>
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors"
                                wire:loading.attr="disabled"
                            >
                                <i class="fas fa-save mr-2"></i>
                                <span wire:loading.remove>Gestionar Diferencia</span>
                                <span wire:loading>Procesando...</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                        <p class="text-green-700 font-medium">Esta diferencia ya ha sido completamente gestionada.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <!-- Header del Modal -->
                <div class="bg-gradient-to-r {{ $diferenciaTotalmenteResuelta ? 'from-green-600 to-green-700' : 'from-blue-600 to-blue-700' }} text-white p-6 rounded-t-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas {{ $diferenciaTotalmenteResuelta ? 'fa-check-circle' : 'fa-info-circle' }} text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold">{{ $tituloExito }}</h3>
                            <p class="text-sm opacity-90 mt-1">Gestión de Diferencia</p>
                        </div>
                    </div>
                </div>

                <!-- Contenido del Modal -->
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-700 leading-relaxed">{{ $mensajeExito }}</p>
                    </div>

                    @if($diferenciaTotalmenteResuelta)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-green-800">Transacción Cerrada</h4>
                                    <p class="text-sm text-green-700">La diferencia ha sido completamente resuelta y la transacción se ha cerrado exitosamente.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-blue-500 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-800">Transacción Abierta</h4>
                                    <p class="text-sm text-blue-700">La diferencia aún tiene monto pendiente por gestionar. La transacción permanece abierta para futuras gestiones.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer del Modal -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg">
                    <div class="flex justify-end">
                        <button 
                            wire:click="cerrarModalExito"
                            class="px-6 py-2 bg-gradient-to-r {{ $diferenciaTotalmenteResuelta ? 'from-green-600 to-green-700 hover:from-green-700 hover:to-green-800' : 'from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800' }} text-white rounded-lg font-medium shadow-lg transform hover:scale-105 transition-all duration-200"
                        >
                            <i class="fas fa-check mr-2"></i>
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
