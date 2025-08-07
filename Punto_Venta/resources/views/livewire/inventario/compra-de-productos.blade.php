<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- Mensajes de error -->
    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 rounded relative bg-red-100 border border-red-400 text-red-700" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Mensajes de éxito -->
    @if (session()->has('success'))
        <div class="mb-4 px-4 py-3 rounded relative bg-green-100 border border-green-400 text-green-700" role="alert">
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
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-white text-gray-800 rounded hover:bg-gray-100">
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

            <!-- FILTROS Y BÚSQUEDA -->
            <div class="p-4 mb-4 bg-white border shadow rounded-xl">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="busqueda" class="form-label">Buscar compras</label>
                        <input type="text" 
                               id="busqueda" 
                               class="form-control" 
                               wire:model.live="busqueda" 
                               placeholder="Buscar por número de factura, proveedor...">
                    </div>
                    <div class="col-md-3">
                        <label for="filtroEstado" class="form-label">Estado</label>
                        <select id="filtroEstado" class="form-select" wire:model.live="filtroEstado">
                            <option value="">Todos los estados</option>
                            <option value="activo">Activo</option>
                            <option value="distribuido">Distribuido</option>
                            <option value="anulado">Anulado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtroFecha" class="form-label">Fecha</label>
                        <input type="date" 
                               id="filtroFecha" 
                               class="form-control" 
                               wire:model.live="filtroFecha">
                    </div>
                </div>
            </div>

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
                                <tr>
                                    <td><span class="badge bg-secondary">#{{ $compra->id }}</span></td>
                                    <td><strong>{{ $compra->numero_factura }}</strong></td>
                                    <td>{{ $compra->proveedor->nombre ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($compra->fecha_recepcion)->format('d/m/Y') }}</td>
                                    <td>
                                        @if($compra->estado->nombre === 'activo')
                                            <span class="badge bg-success">Activo</span>
                                        @elseif($compra->estado->nombre === 'distribuido')
                                            <span class="badge bg-warning">Distribuido</span>
                                        @elseif($compra->estado->nombre === 'anulado')
                                            <span class="badge bg-danger">Anulado</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($compra->estado->nombre ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $compra->detallesCompra->count() }}</td>
                                    <td class="text-end"><strong>L. {{ number_format($compra->detallesCompra->sum('precio_total'), 2) }}</strong></td>
                                    <td class="text-center">
                                        @if($compra->estado->nombre === 'activo')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    wire:click="abrirModalAnular({{ $compra->id }})"
                                                    title="Anular compra">
                                                ❌ Anular
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <div>
                                            <i style="font-size: 2rem;">📦</i>
                                            <p class="mb-0 mt-2">No se encontraron compras</p>
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
                        <div class="p-3 bg-gray-50 rounded border">
                            <p class="mb-1"><strong>Compra:</strong> {{ $compraSeleccionada['numero_factura'] }}</p>
                            <p class="mb-1"><strong>Proveedor:</strong> {{ $compraSeleccionada['proveedor_nombre'] ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Total:</strong> L. {{ number_format($compraSeleccionada['total'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <p class="text-gray-700 mb-3">
                            ⚠️ <strong>¿Está seguro que desea anular esta compra?</strong>
                        </p>
                        <p class="text-sm text-gray-600 mb-4">
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
    </style>

</div>
