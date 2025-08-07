<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- Mensajes de error -->
    @if (session()->has('error'))
        <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Mensajes de éxito -->
    @if (session()->has('success'))
        <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
            <strong class="font-bold">¡Éxito!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="overflow-hidden border border-gray-300 rounded shadow">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">
                📋 Listado de Compras de Productos
            </h5>
            <div class="flex gap-2">
                <button wire:click="agregarCompra"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <span>➕</span> Nueva Compra
                </button>
            </div>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Error</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif



            <!-- TABLA DE COMPRAS -->
            <div class="p-4 bg-white border shadow rounded-xl">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <h2 class="text-lg font-semibold text-gray-700">📋 Compras Realizadas</h2>
                    <small class="text-muted">Total: {{ $compras->total() }} compras</small>
                </div>

                <!-- Tabla de Compras -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 15%;">N° Factura</th>
                                <th style="width: 20%;">Proveedor</th>
                                <th style="width: 12%;">Fecha Emisión</th>
                                <th style="width: 12%;">Fecha Recepción</th>
                                <th style="width: 10%;">Estado</th>
                                <th style="width: 8%;">Productos</th>
                                <th style="width: 15%;" class="text-end">Total</th>
                                <th style="width: 10%;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compras as $compra)
                                <tr style="cursor: pointer;" 
                                    wire:click="verDetalle({{ $compra->id }})"
                                    title="Clic para ver detalle">
                                    <td><span class="badge bg-secondary">#{{ $compra->id }}</span></td>
                                    <td><strong>{{ $compra->numero_factura }}</strong></td>
                                    <td>{{ $compra->proveedor->nombre ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($compra->fecha_recepcion)->format('d/m/Y') }}</td>
                                    <td>
                                        @if($compra->estado)
                                            @if(strtolower($compra->estado->nombre) === 'activo')
                                                <span class="badge bg-success">Activo</span>
                                            @elseif(strtolower($compra->estado->nombre) === 'distribuido')
                                                <span class="badge bg-warning">Distribuido</span>
                                            @elseif(strtolower($compra->estado->nombre) === 'anulado')
                                                <span class="badge bg-danger">Anulado</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($compra->estado->nombre) }}</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Sin Estado</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $compra->detallesCompra->count() }}</td>
                                    <td class="text-end"><strong>L. {{ number_format($compra->detallesCompra->sum('precio_total'), 2) }}</strong></td>
                                    <td class="text-center" onclick="event.stopPropagation()">
                                        @if($compra->estado && strtolower($compra->estado->nombre) === 'activo')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    wire:click="abrirModalAnular({{ $compra->id }})"
                                                    title="Anular compra">
                                                ❌ Anular
                                            </button>
                                        @else
                                            <span class="text-muted small">Sin acciones</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-4 text-center text-muted">
                                        <div>
                                            <i style="font-size: 2rem;">📦</i>
                                            <p class="mt-2 mb-0">No se encontraron compras</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-3">
                    {{ $compras->links() }}
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Detalle de Compra -->
    <div x-data="{ open: @entangle('mostrarModalDetalle') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalDetalle()"
         @keydown.escape.window="$wire.cerrarModalDetalle()">

        <div class="w-full max-w-3xl mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-2xl">
                <!-- Header -->
                <div class="px-4 py-3 text-white bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 3a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            <h3 class="text-lg font-semibold">Detalle de Compra</h3>
                        </div>
                        <button wire:click="cerrarModalDetalle" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-4 max-h-80 overflow-y-auto custom-scrollbar">
                    @if($compraDetalle)
                    <!-- Información General -->
                    <div class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-blue-500">
                                <div class="text-xs text-gray-600 uppercase tracking-wide font-medium mb-1">Información General</div>
                                <p class="text-sm mb-1"><span class="font-medium">N° Factura:</span> {{ $compraDetalle['numero_factura'] }}</p>
                                <p class="text-sm mb-1"><span class="font-medium">Proveedor:</span> {{ $compraDetalle['proveedor_nombre'] }}</p>
                                <p class="text-sm mb-0"><span class="font-medium">Estado:</span> 
                                    @if(strtolower($compraDetalle['estado']) === 'activo')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">{{ $compraDetalle['estado'] }}</span>
                                    @elseif(strtolower($compraDetalle['estado']) === 'distribuido')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">{{ $compraDetalle['estado'] }}</span>
                                    @elseif(strtolower($compraDetalle['estado']) === 'anulado')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">{{ $compraDetalle['estado'] }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">{{ $compraDetalle['estado'] }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-green-500">
                                <div class="text-xs text-gray-600 uppercase tracking-wide font-medium mb-1">Fechas</div>
                                <p class="text-sm mb-1"><span class="font-medium">Emisión:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_emision'])->format('d/m/Y') }}</p>
                                <p class="text-sm mb-1"><span class="font-medium">Recepción:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_recepcion'])->format('d/m/Y') }}</p>
                                @if($compraDetalle['fecha_vencimiento'])
                                <p class="text-sm mb-0"><span class="font-medium">Vencimiento:</span> {{ \Carbon\Carbon::parse($compraDetalle['fecha_vencimiento'])->format('d/m/Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs text-gray-600 uppercase tracking-wide font-medium">Productos Comprados</div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">{{ $compraDetalle['total_productos'] }} productos</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-100 text-xs text-gray-600 uppercase tracking-wide">
                                        <th class="px-2 py-2 text-left">Producto</th>
                                        <th class="px-2 py-2 text-center">Cant.</th>
                                        <th class="px-2 py-2 text-center">Unidad</th>
                                        <th class="px-2 py-2 text-right">P. Unit.</th>
                                        <th class="px-2 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($compraDetalle['productos'] as $producto)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 py-2 font-medium text-gray-900">{{ $producto['nombre'] }}</td>
                                        <td class="px-2 py-2 text-center">{{ $producto['cantidad'] }}</td>
                                        <td class="px-2 py-2 text-center text-gray-600">{{ $producto['unidad'] }}</td>
                                        <td class="px-2 py-2 text-right text-gray-600">L. {{ number_format($producto['precio_unitario'], 2) }}</td>
                                        <td class="px-2 py-2 text-right font-medium">L. {{ number_format($producto['precio_total'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="border-t pt-3">
                        <div class="bg-blue-50 rounded-lg p-3">
                            <div class="text-xs text-gray-600 uppercase tracking-wide font-medium mb-2">Resumen Financiero</div>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div class="text-center">
                                    <div class="text-gray-600">Subtotal</div>
                                    <div class="font-semibold">L. {{ number_format($compraDetalle['subtotal_general'], 2) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-600">ISV</div>
                                    <div class="font-semibold">L. {{ number_format($compraDetalle['isv_general'], 2) }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-600">Total</div>
                                    <div class="text-lg font-bold text-blue-600">L. {{ number_format($compraDetalle['total_general'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 bg-gray-50 border-t">
                    <div class="flex justify-end">
                        <button wire:click="cerrarModalDetalle"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Anulación -->
    <div x-data="{ open: @entangle('mostrarModalAnular') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalAnular()"
         @keydown.escape.window="$wire.cerrarModalAnular()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-red-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Anular Compra</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    @if($compraSeleccionada)
                    <div class="mb-4">
                        <div class="p-3 border rounded bg-gray-50">
                            <p class="mb-1"><strong>Compra:</strong> {{ $compraSeleccionada['numero_factura'] }}</p>
                            <p class="mb-1"><strong>Proveedor:</strong> {{ $compraSeleccionada['proveedor_nombre'] ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Total:</strong> L. {{ number_format($compraSeleccionada['total'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <p class="mb-3 text-gray-700">
                            ⚠️ <strong>¿Está seguro que desea anular esta compra?</strong>
                        </p>
                        <p class="mb-4 text-sm text-gray-600">
                            Esta acción no se puede deshacer. La compra será marcada como anulada y no podrá ser distribuida.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label for="motivoAnulacion" class="form-label">
                            <strong>Motivo de anulación</strong> <span class="text-red-600">*</span>
                        </label>
                        <textarea id="motivoAnulacion"
                                  class="form-control"
                                  rows="3"
                                  wire:model.defer="motivoAnulacion"
                                  placeholder="Ingrese el motivo por el cual está anulando esta compra..."
                                  maxlength="500"></textarea>
                        @error('motivoAnulacion')
                            <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Máximo 500 caracteres</small>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 px-6 py-3 bg-gray-50">
                    <button wire:click="cerrarModalAnular"
                            class="px-4 py-2 text-gray-700 transition-colors duration-200 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="confirmarAnulacion"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700"
                            @disabled(!$motivoAnulacion || strlen($motivoAnulacion) < 10)>
                        Anular Compra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Error</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    <!-- Estilos CSS -->
    <style>
        /* Alerta flotante personalizada */
        .alert-campo-obligatorio {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Ocultar elementos antes de que Alpine.js los maneje */
        [x-cloak] {
            display: none !important;
        }

        /* Tabla responsive */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            overflow-x: auto;
            min-height: 200px;
        }

        /* Asegurar que la tabla no se comprima demasiado */
        .table {
            min-width: 900px;
            margin-bottom: 0;
        }

        /* Botón deshabilitado */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Hover en filas */
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Filas clickeables */
        .table tbody tr[style*="cursor: pointer"]:hover {
            background-color: #e3f2fd !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Estados de compra */
        .badge {
            font-size: 0.75rem;
        }

        /* Botones de acción */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Modal responsive */
        @media (max-width: 576px) {
            .fixed.inset-0 .w-full.max-w-md {
                max-width: 95%;
                margin: 1rem;
            }
        }

        /* Mejoras para tabla en móvil */
        @media (max-width: 768px) {
            .table {
                min-width: 800px;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
        }

        /* Scrollbar personalizado */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Modal responsive mejorado */
        @media (max-width: 768px) {
            .fixed.inset-0 .w-full.max-w-3xl {
                max-width: 95%;
                margin: 0.5rem;
            }
            
            .max-h-80 {
                max-height: 60vh;
            }
        }
    </style>

</div>
