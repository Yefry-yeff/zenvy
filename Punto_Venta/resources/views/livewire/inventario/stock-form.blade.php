<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

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
                <i class="fas fa-cubes me-2"></i>
                @if($isEditing)
                    Editar Stock de Producto
                @else
                    Gestión de Stock
                @endif
            </h5>
            <button wire:click="volverAProductos"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>←</span> Volver
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <form wire:submit.prevent="guardar">

                <!-- Información del Producto -->
                @if($producto)
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📦 Información del Producto</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong><i class="fas fa-tag text-primary me-2"></i>Producto:</strong><br>
                                    {{ $producto->nombre }}
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-align-left text-info me-2"></i>Descripción:</strong><br>
                                    {{ $producto->descripcion ?? 'Sin descripción' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong><i class="fas fa-copyright text-warning me-2"></i>Marca:</strong><br>
                                    {{ $producto->marca->nombre ?? 'Sin marca' }}
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-balance-scale text-success me-2"></i>Unidad de Medida:</strong><br>
                                    {{ $producto->unidadMedidaCompra->nombre ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        @if($recibido && $recibido->seccion)
                        <div class="p-3 mt-3 rounded bg-light">
                            <h6 class="mb-2 text-muted"><i class="fas fa-map-marker-alt me-2"></i>Ubicación en Inventario:</h6>
                            <div class="flex-wrap gap-2 d-flex">
                                <span class="badge bg-primary">
                                    <i class="fas fa-store me-1"></i>{{ $recibido->seccion->segmento->bodega->tienda->denominacion_social ?? 'N/A' }}
                                </span>
                                <span class="badge bg-info">
                                    <i class="fas fa-warehouse me-1"></i>{{ $recibido->seccion->segmento->bodega->nombre ?? 'N/A' }}
                                </span>
                                <span class="badge bg-success">
                                    <i class="fas fa-layer-group me-1"></i>{{ $recibido->seccion->segmento->descripcion ?? 'N/A' }}
                                </span>
                                <span class="badge bg-warning">
                                    <i class="fas fa-cube me-1"></i>{{ $recibido->seccion->descripcion }} ({{ $recibido->seccion->numeracion }})
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Gestión de Stock -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📊 Nueva Distribución de Stock</h2>

                        <!-- Primera fila: Cantidad asignada en bodega -->
                        <div class="row">
                            <!-- Cantidad Asignada en Bodega (Clickeable para ver detalle) -->
                            <div class="mb-3 col-md-12">
                                <label for="cantidad_asignada_bodega" class="form-label">
                                    Cantidad Asignada en Bodega
                                    <small class="text-muted d-block">Suma de todas las secciones de esta bodega - Clic para ver detalle</small>
                                </label>
                                <input type="number"
                                       id="cantidad_asignada_bodega"
                                       class="form-control bg-light cursor-pointer"
                                       wire:model.live="cantidadTotalBodega"
                                       wire:click="mostrarModalSecciones"
                                       readonly
                                       style="cursor: pointer;">
                                <small class="text-info">
                                    <i class="fas fa-warehouse me-1"></i>Total en toda la bodega - <i class="fas fa-mouse-pointer me-1"></i>Clic para ver distribución por secciones
                                </small>
                            </div>
                        </div>

                        <!-- Segunda fila: Asignación Inicial en Sección, Stock Disponible en sección -->
                        <div class="row">
                            <!-- Asignación Inicial en Sección -->
                            <div class="mb-3 col-md-6">
                                <label for="asignacion_inicial_seccion" class="form-label">
                                    Asignación Inicial en Sección
                                    <small class="text-muted d-block">Cantidad inicialmente asignada</small>
                                </label>
                                <input type="number"
                                       id="asignacion_inicial_seccion"
                                       class="form-control bg-light"
                                       value="{{ $recibido->cantidad_inicial_seccion ?? 0 }}"
                                       readonly>
                                <small class="text-info">
                                    <i class="fas fa-layer-group me-1"></i>Asignación inicial en esta sección
                                </small>
                            </div>

                            <!-- Stock Disponible en sección -->
                            <div class="mb-3 col-md-6">
                                <label for="stock_disponible_seccion" class="form-label">
                                    Stock Disponible en Sección
                                    <small class="text-muted d-block">Cantidad disponible actual</small>
                                </label>
                                <input type="number"
                                       id="stock_disponible_seccion"
                                       class="form-control bg-light"
                                       value="{{ $recibido->cantidad_disponible ?? 0 }}"
                                       readonly>
                                <small class="text-info">
                                    <i class="fas fa-box me-1"></i>Stock actual disponible en sección
                                </small>
                            </div>
                        </div>

                        <!-- Tercera fila: Cantidad a distribuir, Precio unitario, Medida de unidad, Precio total -->
                        <div class="row">
                            <!-- Cantidad a Distribuir -->
                            <div class="mb-3 col-md-3">
                                <label for="cantidad_distribuir" class="form-label">
                                    Cantidad a Distribuir <span class="text-red-600">*</span>
                                    <small class="text-muted d-block">Cantidad para esta distribución</small>
                                </label>
                                <input type="number"
                                       id="cantidad_distribuir"
                                       class="form-control {{ $this->getClaseCampo('cantidad_distribuir') }}"
                                       wire:model.live="form.cantidad_distribuir"
                                       placeholder="Ej: 25"
                                       min="1"
                                       max="{{ $stockDisponible ?? 999999 }}">
                                @error('form.cantidad_distribuir')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                                @if($stockDisponible > 0)
                                    <small class="text-info">Máximo disponible: {{ $stockDisponible }}</small>
                                @endif
                            </div>

                            <!-- Precio Unitario -->
                            <div class="mb-3 col-md-3">
                                <label for="precio_unitario" class="form-label">
                                    Precio Unitario (LPS)
                                    <small class="text-muted d-block">Precio base del producto (no editable)</small>
                                </label>
                                <input type="text"
                                       id="precio_unitario"
                                       class="form-control bg-light"
                                       value="L. {{ number_format($producto->precio_base ?? 0, 2) }}"
                                       readonly>
                            </div>

                            <!-- Medida de unidad (readonly, del producto) -->
                            <div class="mb-3 col-md-3">
                                <label for="unidad_medida" class="form-label">
                                    Medida de Unidad
                                    <small class="text-muted d-block">Del producto (no editable)</small>
                                </label>
                                <input type="text"
                                       id="unidad_medida"
                                       class="form-control bg-light"
                                       value="{{ $producto->unidadMedidaCompra->nombre ?? 'N/A' }}"
                                       readonly>
                            </div>

                            <!-- Precio Total (Calculado) -->
                            <div class="mb-3 col-md-3">
                                <label for="precio_total" class="form-label">
                                    Precio Total (LPS)
                                    <small class="text-muted d-block">Calculado automáticamente</small>
                                </label>
                                <input type="text"
                                       id="precio_total"
                                       class="form-control bg-light"
                                       value="L. {{ number_format((float)($form['cantidad_distribuir'] ?? 0) * (float)($producto->precio_base ?? 0), 2) }}"
                                       readonly>
                                <small class="text-info">
                                    <i class="fas fa-calculator me-1"></i>{{ $form['cantidad_distribuir'] ?? 0 }} × L. {{ number_format((float)($producto->precio_base ?? 0), 2) }}
                                </small>
                            </div>
                        </div>

                        <!-- Comentario -->
                        <div class="mb-3">
                            <label for="comentario" class="form-label">
                                Comentario de la Distribución
                                <small class="text-muted">(Opcional)</small>
                            </label>
                            <textarea id="comentario"
                                      class="form-control {{ $this->getClaseCampo('comentario') }}"
                                      wire:model.live="form.comentario"
                                      placeholder="Observaciones sobre esta distribución de stock..."
                                      rows="3"
                                      maxlength="400"></textarea>
                            @error('form.comentario')
                                <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ strlen($form['comentario'] ?? '') }}/400 caracteres</small>
                        </div>

                        <!-- Indicadores visuales -->
                        @if(isset($form['cantidad_asignada_bodega']) && $form['cantidad_asignada_bodega'] > 0)
                        <div class="p-3 mt-4 rounded bg-light">
                            <h6 class="mb-2 text-success"><i class="fas fa-chart-bar me-2"></i>Resumen de Stock:</h6>
                            <div class="text-center row">
                                <div class="col-md-3">
                                    <div class="p-2 text-white rounded bg-primary">
                                        <strong>{{ $form['cantidad_asignada_bodega'] ?? 0 }}</strong><br>
                                        <small>Total Recibido</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 text-white rounded bg-success">
                                        <strong>{{ $totalDistribuido ?? 0 }}</strong><br>
                                        <small>Total Distribuido</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 text-white rounded bg-info">
                                        <strong>{{ $stockDisponible ?? 0 }}</strong><br>
                                        <small>Disponible</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-2 text-white rounded bg-warning">
                                        <strong>{{ count($distribuciones ?? []) }}</strong><br>
                                        <small>Distribuciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Historial de Distribuciones -->
                @if(!empty($distribuciones) && count($distribuciones) > 0)
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📋 Historial de Distribuciones</h2>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Fecha Distribución</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Precio Unit.</th>
                                        <th class="text-center">Unidad de Venta</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Usuario</th>
                                        <th class="text-center">Creado</th>
                                        <th class="text-center">Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($distribuciones as $index => $distribucion)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $loop->iteration }}</span>
                                        </td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($distribucion['fecha_distribucion'])->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $distribucion['cantidad_distribuida'] }}</span>
                                        </td>
                                        <td class="text-center">L. {{ number_format((float)$distribucion['precio_unitario'], 2) }}</td>
                                        <td class="text-center">{{ $distribucion['unidad_medida'] ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <strong>L. {{ number_format((float)$distribucion['cantidad_distribuida'] * (float)$distribucion['precio_unitario'], 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                <i class="fas fa-user me-1"></i>{{ $distribucion['usuario_nombre'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center text-muted small">
                                            <i class="fas fa-clock me-1"></i>{{ $distribucion['created_at'] ?? 'Sin fecha' }}
                                        </td>
                                        <td class="text-center">{{ $distribucion['comentario'] ?? 'Sin comentario' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-info">
                                    <tr>
                                        <th class="text-center">-</th>
                                        <th class="text-center">TOTALES:</th>
                                        <th class="text-center">{{ $totalDistribuido ?? 0 }}</th>
                                        <th class="text-center">-</th>
                                        <th class="text-center">-</th>
                                        <th class="text-center">L. {{ number_format(array_sum(array_map(function($dist) { return (float)$dist['cantidad_distribuida'] * (float)$dist['precio_unitario']; }, $distribuciones ?? [])), 2) }}</th>
                                        <th class="text-center">-</th>
                                        <th class="text-center">-</th>
                                        <th class="text-center">-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="volverAProductos"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        Cancelar
                    </button>

                    @if($this->formularioCompleto)
                        <!-- Botón habilitado cuando el formulario está completo -->
                        <button type="submit"
                            class="px-4 py-2 text-white transition-all duration-200 rounded"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                            <i class="fas fa-save me-2"></i>
                            Registrar Distribución
                        </button>
                    @else
                        <!-- Botón deshabilitado cuando faltan campos -->
                        <button type="button"
                            disabled
                            class="px-4 py-2 text-white transition-all duration-200 bg-gray-400 rounded cursor-not-allowed opacity-60"
                            title="Complete todos los campos obligatorios">
                            <i class="fas fa-save me-2"></i>
                            Registrar Distribución
                            <span class="ml-1">🔒</span>
                        </button>
                    @endif
                </div>

            </form>
        </div>
    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Validación de Stock</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    <!-- Modal de Éxito con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalExito') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalExito()"
         @keydown.escape.window="$wire.cerrarModalExito()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-green-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">¡Stock Actualizado!</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">Las cantidades de stock se han actualizado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 0 1 0 1.414L5.414 7H11a7 7 0 0 1 7 7v2a1 1 0 1 1-2 0v-2a5 5 0 0 0-5-5H5.414l2.293 2.293a1 1 0 1 1-1.414 1.414l-4-4a1 1 0 0 1 0-1.414l4-4a1 1 0 0 1 1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalError') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalError()"
         @keydown.escape.window="$wire.cerrarModalError()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-red-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1-9a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Error en Stock</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1-9a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise las cantidades e intente nuevamente.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estilos CSS para validación -->
    <style>
        /* Campo con error - solo rojos */
        .is-invalid, .campo-obligatorio-vacio {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Mensaje de error personalizado */
        .text-danger {
            color: #dc3545 !important;
            font-size: 0.875rem;
            font-weight: 500;
        }

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
    </style>

    <!-- Modal para mostrar distribución por secciones -->
    @if($mostrarModalSecciones)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-warehouse me-2"></i>Distribución por Secciones
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cerrarModalSecciones"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="text-muted">
                                <i class="fas fa-box me-2"></i>Producto: {{ $producto->nombre ?? 'N/A' }}
                            </h6>
                            <h6 class="text-muted">
                                <i class="fas fa-warehouse me-2"></i>Bodega: {{ $recibido->seccion->segmento->bodega->nombre ?? 'N/A' }}
                            </h6>
                        </div>

                        @if(!empty($seccionesProducto))
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><i class="fas fa-layer-group me-1"></i>Sección</th>
                                            <th><i class="fas fa-cubes me-1"></i>Cantidad Inicial</th>
                                            <th><i class="fas fa-box me-1"></i>Cantidad Disponible</th>
                                            <th><i class="fas fa-calendar me-1"></i>Fecha Recibido</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($seccionesProducto as $seccion)
                                            <tr class="{{ $seccion['seccion_actual'] ? 'table-warning' : '' }}">
                                                <td>
                                                    {{ $seccion['nombre_seccion'] }}
                                                    @if($seccion['seccion_actual'])
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            <i class="fas fa-star me-1"></i>Sección Actual
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        {{ number_format($seccion['cantidad_inicial']) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $seccion['cantidad_disponible'] > 0 ? 'success' : 'secondary' }}">
                                                        {{ number_format($seccion['cantidad_disponible']) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $seccion['fecha_recibido'] }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th>TOTAL</th>
                                            <th>
                                                <span class="badge bg-info">
                                                    {{ number_format(collect($seccionesProducto)->sum('cantidad_inicial')) }}
                                                </span>
                                            </th>
                                            <th>
                                                <span class="badge bg-success">
                                                    {{ number_format(collect($seccionesProducto)->sum('cantidad_disponible')) }}
                                                </span>
                                            </th>
                                            <th>-</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No se encontraron registros de este producto en otras secciones de la bodega.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalSecciones">
                            <i class="fas fa-times me-1"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
