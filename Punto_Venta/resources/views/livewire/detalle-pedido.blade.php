<div class="p-6">
    @if(!$pedido)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            Pedido no encontrado
        </div>
        <button wire:click="volverABandeja" class="mt-4 text-blue-600 hover:text-blue-800">
            ← Volver a la bandeja
        </button>
    @else
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Botón volver -->
        <div class="mb-4">
            <button wire:click="volverABandeja" class="text-sm text-blue-600 hover:text-blue-800">
                ← Volver a la bandeja
            </button>
        </div>

        <!-- Estado y Acciones -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $pedido->numero_pedido }}</h3>
                            @if($pedido->estado === 'pendiente')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    ⏱️ Pendiente
                                </span>
                            @elseif($pedido->estado === 'procesando')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    🔄 Procesando
                                </span>
                            @elseif($pedido->estado === 'facturado')
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    ✅ Facturado
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    ❌ Rechazado
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">Recibido: {{ $pedido->created_at->format('d/m/Y H:i:s') }} ({{ $pedido->created_at->diffForHumans() }})</p>
                        
        @if($pedido->factura_id)
                            <p class="text-sm text-green-600 mt-2 flex items-center gap-2">
                                ✓ Facturado con ID: <strong>{{ $pedido->factura_id }}</strong> 
                                @if($pedido->fecha_facturado)
                                    el {{ $pedido->fecha_facturado->format('d/m/Y H:i') }}
                                @endif
                                <button wire:click="imprimirFactura" class="ml-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                    🖨️ Imprimir Factura
                                </button>
                            </p>
                        @endif
                    </div>

                    @if($pedido->estado === 'pendiente')
                        <div class="flex gap-2">
                            <button wire:click="$set('mostrarModalProcesar', true)" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                ✓ Procesar y Facturar
                            </button>
                            <button wire:click="$set('mostrarModalRechazar', true)" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                ✗ Rechazar
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Información del Cliente -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">👤 Información del Cliente</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                            <dd class="text-sm text-gray-900">{{ $pedido->cliente_nombre }}</dd>
                        </div>
                        @if($pedido->cliente_email)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->cliente_email }}</dd>
                            </div>
                        @endif
                        @if($pedido->cliente_telefono)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->cliente_telefono }}</dd>
                            </div>
                        @endif
                        @if($pedido->cliente_rtn)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">RTN</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->cliente_rtn }}</dd>
                            </div>
                        @endif
                        @if($pedido->cliente_direccion)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                <dd class="text-sm text-gray-900">{{ $pedido->cliente_direccion }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Información del Pago -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">💳 Información de Pago</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                            <dd class="text-sm text-gray-900">{{ $pedido->metodo_pago ?? 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                            <dd class="text-sm text-gray-900">L {{ number_format($pedido->subtotal, 2) }}</dd>
                        </div>
                        @if($pedido->descuento > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Descuento</dt>
                                <dd class="text-sm text-gray-900">L {{ number_format($pedido->descuento, 2) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ISV (15%)</dt>
                            <dd class="text-sm text-gray-900">L {{ number_format($pedido->isv, 2) }}</dd>
                        </div>
                        <div class="pt-2 border-t border-gray-200">
                            <dt class="text-base font-semibold text-gray-700">Total</dt>
                            <dd class="text-xl font-bold text-gray-900">L {{ number_format($pedido->total, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-800">📦 Productos del Pedido</h4>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model.live="buscarProducto" 
                            placeholder="Buscar producto..." 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <select 
                            wire:model.live="registrosPorPaginaProductos" 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                @php
                    $productosPaginados = $this->getProductosPaginados();
                @endphp

                <div class="overflow-x-auto" style="max-height: 400px; overflow-y: auto;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                    Producto
                                    <input 
                                        type="text" 
                                        wire:model.live="filtroNombreProducto" 
                                        placeholder="Filtrar..." 
                                        class="mt-1 block w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                </th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">ISV</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($productosPaginados['datos'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $item->producto->nombre ?? "Producto ID {$item->producto_id}" }}
                                        </div>
                                        <div class="text-xs text-gray-500">ID: {{ $item->producto_id }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">{{ $item->cantidad }}</td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">L {{ number_format($item->precio_unitario, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">L {{ number_format($item->subtotal, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-900">L {{ number_format($item->isv, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">L {{ number_format($item->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No se encontraron productos
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($productosPaginados['total'] > 0)
                    <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                        <div class="text-sm text-gray-700">
                            Mostrando <span class="font-medium">{{ $productosPaginados['desde'] }}</span>
                            a <span class="font-medium">{{ $productosPaginados['hasta'] }}</span>
                            de <span class="font-medium">{{ $productosPaginados['total'] }}</span> productos
                        </div>
                        <div class="flex gap-2">
                            <button 
                                wire:click="previousPageProductos" 
                                @if($productosPaginados['paginaActual'] <= 1) disabled @endif
                                class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Anterior
                            </button>
                            <span class="px-3 py-1 text-sm text-gray-700">
                                Página {{ $productosPaginados['paginaActual'] }} de {{ $productosPaginados['ultimaPagina'] }}
                            </span>
                            <button 
                                wire:click="nextPageProductos" 
                                @if($productosPaginados['paginaActual'] >= $productosPaginados['ultimaPagina']) disabled @endif
                                class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Notas -->
        @if($pedido->notas)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">📝 Notas</h4>
                    <p class="text-sm text-gray-700">{{ $pedido->notas }}</p>

        <!-- Modal de Confirmación para Procesar -->
        @if($mostrarModalProcesar ?? false)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalProcesar', false)">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">✓ Confirmar Procesamiento</h3>
                            <button wire:click="$set('mostrarModalProcesar', false)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <p class="text-gray-700 mb-4">
                                ¿Está seguro de procesar y facturar el pedido <strong>{{ $pedido->numero_pedido }}</strong>?
                            </p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Resumen del Pedido:</h4>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• Cliente: {{ $pedido->cliente_nombre }}</li>
                                    <li>• Productos: {{ $pedido->items->count() }} items</li>
                                    <li>• Total: L {{ number_format($pedido->total, 2) }}</li>
                                </ul>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">
                                ⚠️ Esta acción generará una factura y descontará el inventario correspondiente.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                wire:click="procesarPedido" 
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="procesarPedido">✓ Sí, Procesar y Facturar</span>
                                <span wire:loading wire:target="procesarPedido" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                            </button>
                            <button 
                                wire:click="$set('mostrarModalProcesar', false)" 
                                class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de Confirmación para Rechazar -->
        @if($mostrarModalRechazar ?? false)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="$set('mostrarModalRechazar', false)">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">✗ Confirmar Rechazo</h3>
                            <button wire:click="$set('mostrarModalRechazar', false)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-6">
                            <p class="text-gray-700 mb-4">
                                ¿Está seguro de rechazar el pedido <strong>{{ $pedido->numero_pedido }}</strong>?
                            </p>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <h4 class="font-semibold text-red-900 mb-2">Información del Pedido:</h4>
                                <ul class="text-sm text-red-800 space-y-1">
                                    <li>• Cliente: {{ $pedido->cliente_nombre }}</li>
                                    <li>• Total: L {{ number_format($pedido->total, 2) }}</li>
                                </ul>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">
                                ⚠️ Esta acción marcará el pedido como rechazado y no se podrá facturar.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                wire:click="rechazarPedido" 
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="rechazarPedido">✗ Sí, Rechazar Pedido</span>
                                <span wire:loading wire:target="rechazarPedido" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Rechazando...
                                </span>
                            </button>
                            <button 
                                wire:click="$set('mostrarModalRechazar', false)" 
                                class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
                </div>
            </div>
        @endif
    @endif

    <!-- Modal de Impresión de Factura -->
    @if($modalImpresion && $facturaParaImprimir)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" style="margin: 0;" id="modal-impresion">
            <div class="relative top-4 mx-auto border max-w-7xl shadow-lg rounded-md bg-white" style="width: calc(100% - 2rem); max-height: calc(100vh - 2rem);">
                <!-- Header del Modal -->
                <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Impresión de Factura {{ $facturaParaImprimir->numero_factura ?? 'Sin número' }}
                    </h3>
                    <button wire:click="cerrarImpresion" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                        ×
                    </button>
                </div>
                
                <!-- Contenido del Modal -->
                <div class="flex" style="height: calc(100vh - 200px);">
                    <!-- Vista previa de la factura (Lado izquierdo) -->
                    <div class="w-2/3 p-4 bg-gray-50 overflow-y-auto border-r">
                        @if($facturaParaImprimir && $empresaFacturaImpresa && $tiendaFacturaImpresa && $caiFacturaImpresa)
                            <div class="max-w-md mx-auto bg-white shadow-sm p-6 rounded-lg" style="font-family: Arial, sans-serif; font-size: 12px;">
                                @include('pdf.factura', [
                                    'factura' => $facturaParaImprimir,
                                    'caiFacturaImpresa' => $caiFacturaImpresa,
                                    'productos' => collect($productosFacturaImpresa ?? []),
                                    'pagos' => collect($pagosFacturaImpresa ?? []),
                                    'empresa' => $empresaFacturaImpresa,
                                    'tienda' => $tiendaFacturaImpresa
                                ])
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                    <p class="text-gray-600">Cargando vista previa de la factura...</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Panel de opciones (Lado derecho) -->
                    <div class="w-1/3 p-6 bg-white">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Opciones de impresión</h4>

                        <div class="space-y-3 mb-6">
                            <button 
                                onclick="window.print()" 
                                class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-semibold shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Imprimir ahora
                            </button>

                            <a 
                                href="{{ route('factura.pdf', $facturaParaImprimir->id) }}"
                                target="_blank" 
                                class="w-full flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-semibold shadow-sm"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Descargar PDF
                            </a>

                            @if($facturaParaImprimir->factura_imagen)
                                <a 
                                    href="{{ route('factura.imagen', $facturaParaImprimir->id) }}"
                                    target="_blank" 
                                    class="w-full flex items-center justify-center px-4 py-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg transition-colors font-semibold shadow-sm"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Ver imagen PNG
                                </a>
                            @endif
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <h5 class="text-sm font-semibold text-gray-700 mb-2">Información de la factura</h5>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Cliente:</strong> {{ $facturaParaImprimir->nombre_cliente }}</p>
                                <p><strong>Total:</strong> L {{ number_format($facturaParaImprimir->total, 2) }}</p>
                                <p><strong>Fecha:</strong> {{ $facturaParaImprimir->fecha_emision->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="flex justify-end items-center p-4 border-t bg-gray-50">
                    <button 
                        wire:click="cerrarImpresion" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors font-semibold flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Escuchar evento para abrir impresión de factura
    document.addEventListener('livewire:init', () => {
        Livewire.on('abrirImpresionFactura', (data) => {
            const facturaId = Array.isArray(data) ? data[0].facturaId : data.facturaId;
            console.log('Abriendo impresión de factura:', facturaId);
            // Abrir la factura en una nueva ventana
            window.open(`/factura/${facturaId}/pdf`, '_blank');
        });

        Livewire.on('mostrarImpresionFactura', (data) => {
            const facturaId = Array.isArray(data) ? data[0].facturaId : data.facturaId;
            console.log('Mostrando impresión de factura:', facturaId);
            // Abrir la factura en una nueva ventana después de procesar
            setTimeout(() => {
                window.open(`/factura/${facturaId}/pdf`, '_blank');
            }, 500);
        });
    });
</script>
@endpush
