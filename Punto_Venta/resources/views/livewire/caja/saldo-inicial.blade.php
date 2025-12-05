<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-blue-600 to-blue-700">
            <h1 class="flex items-center text-2xl font-bold">
                <i class="mr-3 fas fa-cash-register"></i>
                Estado de Caja
            </h1>
            <p class="mt-1 text-blue-100">Sistema de caja siempre activa</p>
        </div>

        <div class="p-6">
            @if($mensaje)
                <div class="p-4 mb-6 rounded-lg {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200' : ($tipoMensaje === 'error' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200') }}">
                    <div class="flex items-center">
                        <i class="mr-3 fas {{ $tipoMensaje === 'success' ? 'fa-check-circle text-green-500' : ($tipoMensaje === 'error' ? 'fa-exclamation-triangle text-red-500' : 'fa-info-circle text-blue-500') }}"></i>
                        <span class="font-medium {{ $tipoMensaje === 'success' ? 'text-green-800' : ($tipoMensaje === 'error' ? 'text-red-800' : 'text-blue-800') }}">{{ $mensaje }}</span>
                    </div>
                </div>
            @endif

            <div class="p-6 border-2 border-blue-300 rounded-lg bg-blue-50">
                <div class="text-center">
                    <div class="mb-2 text-sm font-medium text-blue-700">SALDO INICIAL FIJO</div>
                    <div class="text-5xl font-bold text-blue-900">
                        L. {{ number_format($saldoInicial, 2) }}
                    </div>
                    <div class="mt-4 text-sm text-blue-600">
                        <i class="mr-2 fas fa-info-circle"></i>
                        Su caja está siempre activa y lista para procesar ventas
                    </div>
                </div>
            </div>

            @if($cajaActual)
                <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-2">
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-sm text-gray-600">Balance Actual</div>
                        <div class="text-2xl font-bold text-gray-900">
                            L. {{ number_format($cajaActual->balance, 2) }}
                        </div>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-sm text-gray-600">Estado</div>
                        <div class="text-2xl font-bold text-green-600">
                            <i class="mr-2 fas fa-check-circle"></i>
                            ACTIVA
                        </div>
                    </div>
                </div>
            @endif

            <div class="p-4 mt-6 border border-yellow-200 rounded-lg bg-yellow-50">
                <h4 class="mb-2 font-semibold text-yellow-800">
                    <i class="mr-2 fas fa-lightbulb"></i>
                    Información Importante
                </h4>
                <ul class="space-y-1 text-sm text-yellow-700">
                    <li><i class="mr-2 fas fa-check"></i>La caja siempre inicia con L. 2,000.00</li>
                    <li><i class="mr-2 fas fa-check"></i>No requiere apertura manual</li>
                    <li><i class="mr-2 fas fa-check"></i>Puede procesar ventas en cualquier momento</li>
                    <li><i class="mr-2 fas fa-check"></i>Al cerrar caja, el saldo se restablece automáticamente</li>
                </ul>
            </div>
        </div>
    </div>
</div>
