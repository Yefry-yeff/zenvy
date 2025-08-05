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
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="mb-2 text-muted"><i class="fas fa-map-marker-alt me-2"></i>Ubicación en Inventario:</h6>
                            <div class="d-flex flex-wrap gap-2">
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
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📊 Gestión de Stock</h2>
                        
                        <div class="row">
                            <!-- Cantidad de Compra del Lote -->
                            <div class="col-md-6 mb-3">
                                <label for="cantidad_compra_lote" class="form-label">
                                    Cantidad Compra Lote <span class="text-red-600">*</span>
                                    <small class="text-muted d-block">Total del lote comprado</small>
                                </label>
                                <input type="number" 
                                       id="cantidad_compra_lote" 
                                       class="form-control {{ $this->getClaseCampo('cantidad_compra_lote') }}" 
                                       wire:model.live="form.cantidad_compra_lote" 
                                       placeholder="Ej: 100"
                                       min="1">
                                @error('form.cantidad_compra_lote')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Cantidad Inicial en Sección -->
                            <div class="col-md-6 mb-3">
                                <label for="cantidad_inicial_seccion" class="form-label">
                                    Stock en Sección <span class="text-red-600">*</span>
                                    <small class="text-muted d-block">Cantidad asignada a esta sección</small>
                                </label>
                                <input type="number" 
                                       id="cantidad_inicial_seccion" 
                                       class="form-control {{ $this->getClaseCampo('cantidad_inicial_seccion') }}" 
                                       wire:model.live="form.cantidad_inicial_seccion" 
                                       placeholder="Ej: 50"
                                       min="0"
                                       max="{{ $form['cantidad_compra_lote'] ?? 999999 }}">
                                @error('form.cantidad_inicial_seccion')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                                @if($form['cantidad_compra_lote'] > 0)
                                    <small class="text-info">Máximo permitido: {{ $form['cantidad_compra_lote'] }}</small>
                                @endif
                            </div>

                            <!-- Cantidad Disponible -->
                            <div class="col-md-6 mb-3">
                                <label for="cantidad_disponible" class="form-label">
                                    Stock Disponible <span class="text-red-600">*</span>
                                    <small class="text-muted d-block">Cantidad actualmente disponible</small>
                                </label>
                                <input type="number" 
                                       id="cantidad_disponible" 
                                       class="form-control {{ $this->getClaseCampo('cantidad_disponible') }}" 
                                       wire:model.live="form.cantidad_disponible" 
                                       placeholder="Ej: 45"
                                       min="0"
                                       max="{{ $form['cantidad_inicial_seccion'] ?? 999999 }}">
                                @error('form.cantidad_disponible')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                                @if($form['cantidad_inicial_seccion'] > 0)
                                    <small class="text-info">Máximo permitido: {{ $form['cantidad_inicial_seccion'] }}</small>
                                @endif
                            </div>

                            <!-- Unidades de Compra -->
                            <div class="col-md-6 mb-3">
                                <label for="unidades_compra" class="form-label">
                                    Unidades de Compra
                                    <small class="text-muted d-block">Ej: Caja, Paquete, etc.</small>
                                </label>
                                <input type="text" 
                                       id="unidades_compra" 
                                       class="form-control {{ $this->getClaseCampo('unidades_compra') }}" 
                                       wire:model.live="form.unidades_compra" 
                                       placeholder="Ej: Caja, Paquete"
                                       maxlength="45">
                                @error('form.unidades_compra')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Fecha de Recibido -->
                            <div class="col-md-6 mb-3">
                                <label for="fecha_recibido" class="form-label">
                                    Fecha de Recibido <span class="text-red-600">*</span>
                                </label>
                                <input type="date" 
                                       id="fecha_recibido" 
                                       class="form-control {{ $this->getClaseCampo('fecha_recibido') }}" 
                                       wire:model.live="form.fecha_recibido">
                                @error('form.fecha_recibido')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Fecha de Expiración -->
                            <div class="col-md-6 mb-3">
                                <label for="fecha_expiracion" class="form-label">
                                    Fecha de Expiración
                                    <small class="text-muted d-block">Opcional</small>
                                </label>
                                <input type="date" 
                                       id="fecha_expiracion" 
                                       class="form-control {{ $this->getClaseCampo('fecha_expiracion') }}" 
                                       wire:model.live="form.fecha_expiracion"
                                       min="{{ $form['fecha_recibido'] ?? '' }}">
                                @error('form.fecha_expiracion')
                                    <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Comentario -->
                        <div class="mb-3">
                            <label for="comentario" class="form-label">
                                Comentario
                                <small class="text-muted">(Opcional)</small>
                            </label>
                            <textarea id="comentario" 
                                      class="form-control {{ $this->getClaseCampo('comentario') }}" 
                                      wire:model.live="form.comentario" 
                                      placeholder="Observaciones adicionales sobre el stock..."
                                      rows="3"
                                      maxlength="150"></textarea>
                            @error('form.comentario')
                                <div class="text-danger mt-1 text-sm">❌ {{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ strlen($form['comentario'] ?? '') }}/150 caracteres</small>
                        </div>

                        <!-- Indicadores visuales -->
                        @if($form['cantidad_compra_lote'] > 0 && $form['cantidad_inicial_seccion'] > 0)
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2 text-success"><i class="fas fa-chart-bar me-2"></i>Resumen de Stock:</h6>
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="p-2 bg-primary text-white rounded">
                                        <strong>{{ $form['cantidad_compra_lote'] }}</strong><br>
                                        <small>Total Lote</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-info text-white rounded">
                                        <strong>{{ $form['cantidad_inicial_seccion'] }}</strong><br>
                                        <small>En Sección</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-success text-white rounded">
                                        <strong>{{ $form['cantidad_disponible'] }}</strong><br>
                                        <small>Disponible</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <small class="text-muted">
                                    Vendidas/Utilizadas: {{ ($form['cantidad_inicial_seccion'] - $form['cantidad_disponible']) }}
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="volverAProductos"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        Cancelar
                    </button>
                    
                    @if($this->formularioCompleto)
                        <!-- Botón habilitado cuando el formulario está completo -->
                        <button type="submit"
                            class="px-4 py-2 text-white rounded transition-all duration-200"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                            <i class="fas fa-save me-2"></i>
                            @if($isEditing)
                                Actualizar Stock
                            @else
                                Guardar Stock
                            @endif
                        </button>
                    @else
                        <!-- Botón deshabilitado cuando faltan campos -->
                        <button type="button" 
                            disabled
                            class="px-4 py-2 text-white bg-gray-400 rounded cursor-not-allowed opacity-60 transition-all duration-200"
                            title="Complete todos los campos obligatorios y asegúrese de que el stock no exceda el lote de compra">
                            <i class="fas fa-save me-2"></i>
                            @if($isEditing)
                                Actualizar Stock
                            @else
                                Guardar Stock
                            @endif
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
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-green-600 text-white p-4">
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">Las cantidades de stock se han actualizado correctamente en el sistema.</p>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-3 text-center">
                    <button wire:click="cerrarModalExito" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
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
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-red-600 text-white p-4">
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise las cantidades e intente nuevamente.</p>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-3 text-center">
                    <button wire:click="cerrarModalError" 
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
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

</div>
