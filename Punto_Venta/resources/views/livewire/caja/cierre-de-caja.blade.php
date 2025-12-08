<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-red-600 to-red-700">
            <h1 class="flex items-center text-2xl font-bold">
                <i class="mr-3 fas fa-times-circle"></i>
                Cierre de Caja
            </h1>
            <p class="mt-1 text-red-100">Procesamiento de cierre y reinicio de caja</p>
        </div>

        <div class="p-6">
            {{-- Mensajes --}}
            @if($mensajeExito)
                <div class="p-4 mb-6 border border-green-200 rounded-lg bg-green-50">
                    <div class="flex items-center">
                        <i class="mr-3 text-green-500 fas fa-check-circle"></i>
                        <span class="font-medium text-green-800">{{ $mensajeExito }}</span>
                    </div>
                </div>
            @endif

            @if($mensajeError)
                <div class="p-4 mb-6 border border-red-200 rounded-lg bg-red-50">
                    <div class="flex items-center">
                        <i class="mr-3 text-red-500 fas fa-exclamation-triangle"></i>
                        <span class="font-medium text-red-800">{{ $mensajeError }}</span>
                    </div>
                </div>
            @endif

            @if(!$cierreProcesado)
                {{-- Resumen de Transacciones --}}
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Resumen de Transacciones</h3>
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Forma de Pago</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($resumenTransacciones as $resumen)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $resumen->forma_pago }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $resumen->cantidad }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900 whitespace-nowrap">L. {{ number_format($resumen->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-sm text-center text-gray-500">No hay transacciones registradas</td>
                                    </tr>
                                @endforelse
                                @if($resumenTransacciones->count() > 0)
                                    <tr class="bg-gray-50">
                                        <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900">TOTAL GENERAL</td>
                                        <td class="px-6 py-4 text-sm font-bold text-right text-gray-900">L. {{ number_format($totalSistema, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Desglose de Efectivo --}}
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Conteo de Efectivo</h3>
                    <div class="p-6 border border-gray-200 rounded-lg">
                        <div class="grid grid-cols-2 gap-4 mb-4 md:grid-cols-4">
                            {{-- Billetes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 500</label>
                                <input type="number" wire:model.live="billetes_500" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 200</label>
                                <input type="number" wire:model.live="billetes_200" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 100</label>
                                <input type="number" wire:model.live="billetes_100" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 50</label>
                                <input type="number" wire:model.live="billetes_50" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 20</label>
                                <input type="number" wire:model.live="billetes_20" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 10</label>
                                <input type="number" wire:model.live="billetes_10" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 5</label>
                                <input type="number" wire:model.live="billetes_5" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 2</label>
                                <input type="number" wire:model.live="billetes_2" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 1</label>
                                <input type="number" wire:model.live="billetes_1" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>

                            {{-- Monedas --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.50</label>
                                <input type="number" wire:model.live="monedas_0_50" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.20</label>
                                <input type="number" wire:model.live="monedas_0_20" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.10</label>
                                <input type="number" wire:model.live="monedas_0_10" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.05</label>
                                <input type="number" wire:model.live="monedas_0_05" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.02</label>
                                <input type="number" wire:model.live="monedas_0_02" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.01</label>
                                <input type="number" wire:model.live="monedas_0_01" min="0" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="p-4 rounded-lg bg-gray-50">
                                    <div class="text-sm text-gray-700">Efectivo Sistema</div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        L. {{ number_format($resumenTransacciones->filter(fn($item) => stripos($item->forma_pago, 'Efectivo') !== false)->sum('total') + 2000.00, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Incluye saldo inicial L. 2,000.00</div>
                                </div>
                                <div class="p-4 rounded-lg bg-blue-50">
                                    <div class="text-sm text-blue-700">Total Contado</div>
                                    <div class="text-2xl font-bold text-blue-900">L. {{ number_format($totalContado, 2) }}</div>
                                </div>
                                <div class="p-4 rounded-lg {{ $diferenciaEfectivo >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="text-sm {{ $diferenciaEfectivo >= 0 ? 'text-green-700' : 'text-red-700' }}">Diferencia</div>
                                    <div class="text-2xl font-bold {{ $diferenciaEfectivo >= 0 ? 'text-green-900' : 'text-red-900' }}">
                                        L. {{ number_format($diferenciaEfectivo, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conteo de Otros Métodos de Pago --}}
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Conteo de Otros Métodos de Pago</h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        {{-- Tarjeta --}}
                        <div class="p-6 border border-gray-200 rounded-lg">
                            <h4 class="mb-3 font-semibold text-gray-900">💳 Tarjeta</h4>
                            <div class="grid grid-cols-3 gap-2 mb-4 text-sm">
                                <div class="p-3 rounded-lg bg-gray-50">
                                    <div class="text-xs text-gray-600">Sistema</div>
                                    <div class="font-bold text-gray-900">L. {{ number_format($resumenTransacciones->filter(fn($item) => stripos($item->forma_pago, 'Tarjeta') !== false)->sum('total'), 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-blue-50">
                                    <div class="text-xs text-blue-600">Contado</div>
                                    <div class="font-bold text-blue-900">L. {{ number_format($totalTarjetaContado, 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg {{ $diferenciaTarjeta >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="text-xs {{ $diferenciaTarjeta >= 0 ? 'text-green-600' : 'text-red-600' }}">Diferencia</div>
                                    <div class="font-bold {{ $diferenciaTarjeta >= 0 ? 'text-green-900' : 'text-red-900' }}">L. {{ number_format($diferenciaTarjeta, 2) }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Ingresar Total Contado</label>
                                <input type="number" wire:model.live="totalTarjetaContado" step="0.01" min="0" class="block w-full border-gray-300 rounded-md" placeholder="0.00">
                            </div>
                        </div>

                        {{-- Transferencia --}}
                        <div class="p-6 border border-gray-200 rounded-lg">
                            <h4 class="mb-3 font-semibold text-gray-900">🏦 Transferencia</h4>
                            <div class="grid grid-cols-3 gap-2 mb-4 text-sm">
                                <div class="p-3 rounded-lg bg-gray-50">
                                    <div class="text-xs text-gray-600">Sistema</div>
                                    <div class="font-bold text-gray-900">L. {{ number_format($resumenTransacciones->filter(fn($item) => stripos($item->forma_pago, 'Transferencia') !== false)->sum('total'), 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-blue-50">
                                    <div class="text-xs text-blue-600">Contado</div>
                                    <div class="font-bold text-blue-900">L. {{ number_format($totalTransferenciaContado, 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg {{ $diferenciaTransferencia >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="text-xs {{ $diferenciaTransferencia >= 0 ? 'text-green-600' : 'text-red-600' }}">Diferencia</div>
                                    <div class="font-bold {{ $diferenciaTransferencia >= 0 ? 'text-green-900' : 'text-red-900' }}">L. {{ number_format($diferenciaTransferencia, 2) }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Ingresar Total Contado</label>
                                <input type="number" wire:model.live="totalTransferenciaContado" step="0.01" min="0" class="block w-full border-gray-300 rounded-md" placeholder="0.00">
                            </div>
                        </div>

                        {{-- Cheque --}}
                        <div class="p-6 border border-gray-200 rounded-lg">
                            <h4 class="mb-3 font-semibold text-gray-900">📝 Cheque</h4>
                            <div class="grid grid-cols-3 gap-2 mb-4 text-sm">
                                <div class="p-3 rounded-lg bg-gray-50">
                                    <div class="text-xs text-gray-600">Sistema</div>
                                    <div class="font-bold text-gray-900">L. {{ number_format($resumenTransacciones->filter(fn($item) => stripos($item->forma_pago, 'Cheque') !== false)->sum('total'), 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-blue-50">
                                    <div class="text-xs text-blue-600">Contado</div>
                                    <div class="font-bold text-blue-900">L. {{ number_format($totalChequeContado, 2) }}</div>
                                </div>
                                <div class="p-3 rounded-lg {{ $diferenciaCheque >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="text-xs {{ $diferenciaCheque >= 0 ? 'text-green-600' : 'text-red-600' }}">Diferencia</div>
                                    <div class="font-bold {{ $diferenciaCheque >= 0 ? 'text-green-900' : 'text-red-900' }}">L. {{ number_format($diferenciaCheque, 2) }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Ingresar Total Contado</label>
                                <input type="number" wire:model.live="totalChequeContado" step="0.01" min="0" class="block w-full border-gray-300 rounded-md" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="mb-6">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea wire:model="observaciones" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Notas adicionales sobre el cierre..."></textarea>
                </div>

                {{-- Botón de Cierre --}}
                <div class="flex justify-end">
                    <button
                        wire:click="procesarCierre"
                        wire:loading.attr="disabled"
                        class="flex items-center px-8 py-3 font-semibold text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                    >
                        <i class="mr-2 fas fa-times-circle"></i>
                        <span wire:loading.remove wire:target="procesarCierre">Procesar Cierre de Caja</span>
                        <span wire:loading wire:target="procesarCierre">
                            <i class="mr-2 fas fa-spinner fa-spin"></i>
                            Procesando...
                        </span>
                    </button>
                </div>
            @else
                <div class="text-center">
                    <i class="mb-4 text-6xl text-green-500 fas fa-check-circle"></i>
                    <h2 class="mb-2 text-2xl font-bold text-gray-900">Cierre Procesado Exitosamente</h2>
                    <p class="mb-6 text-gray-600">La caja se ha restablecido a L. 2,000.00</p>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-home"></i>
                        Volver al Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Vista de Impresión del Recibo (Inline) --}}
    @if($mostrarVistaImpresion && $cierreIdParaImprimir)
        <div class="mt-4 bg-white rounded-lg shadow-lg">
            <div class="p-4 text-white rounded-t-lg bg-gradient-to-r from-green-600 to-green-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">
                        <i class="mr-2 fas fa-receipt"></i>
                        Recibo de Cierre de Caja
                    </h2>
                    <div class="flex gap-2">
                        <a href="/cierre-caja/{{ $cierreIdParaImprimir }}/pdf" 
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 text-white transition-colors bg-blue-600 rounded hover:bg-blue-700">
                            <i class="mr-2 fas fa-download"></i>
                            Descargar PDF
                        </a>
                        <button wire:click="cerrarVistaImpresion" 
                                class="inline-flex items-center px-4 py-2 text-white transition-colors bg-gray-600 rounded hover:bg-gray-700">
                            <i class="mr-2 fas fa-arrow-left"></i>
                            Nueva Jornada
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-0">
                <iframe src="/cierre-caja/{{ $cierreIdParaImprimir }}/pdf/preview" 
                        class="w-full border-none"
                        style="min-height: 85vh; height: 700px;"
                        id="pdfViewerCierre">
                </iframe>
            </div>
        </div>

        <style>
            #pdfViewerCierre {
                background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="20" fill="none" stroke="%23dc2626" stroke-width="4" stroke-dasharray="31.416" stroke-dashoffset="31.416"><animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/><animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/></circle></svg>') center center no-repeat;
                background-size: 50px 50px;
            }
        </style>
    @endif
</div>
