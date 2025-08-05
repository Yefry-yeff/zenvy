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
                @if($isEditing)
                    Editar Producto
                @else
                    Nuevo Producto
                @endif
            </h5>
            <button wire:click="volverALista"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>←</span> Volver
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Campo Obligatorio</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif

            <form wire:submit.prevent="guardar">

                <!-- Información Básica -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📝 Información Básica</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="nombre" class="form-label">Nombre <span class="text-red-600">*</span></label>
                                <input type="text" id="nombre" class="form-control {{ $this->getClaseCampo('nombre') }}" wire:model.live="form.nombre">
                                @error('form.nombre')
                                    <div class="mt-1 text-sm text-danger">❌ El nombre del producto es obligatorio y no puede estar vacío</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <input type="text" id="descripcion" class="form-control" wire:model.defer="form.descripcion">
                                @error('form.descripcion')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="codigo_barra" class="form-label">Código de Barras</label>
                                <input type="text" id="codigo_barra" class="form-control" wire:model.defer="form.codigo_barra">
                                @error('form.codigo_barra')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="codigo_estatal" class="form-label">Código Estatal</label>
                                <input type="text" id="codigo_estatal" class="form-control" wire:model.defer="form.codigo_estatal">
                                @error('form.codigo_estatal')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categorización -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏷️ Categorización</h2>
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="marca" class="form-label">Marca <span class="text-red-600">*</span></label>
                                <select id="marca" class="form-select {{ $this->getClaseCampo('marca') }}" wire:model.live="form.marca_id">
                                    <option value="">Seleccionar marca</option>
                                    @foreach($marcas as $marca)
                                        <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.marca_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una marca válida</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="categoria" class="form-label">Categoría <span class="text-red-600">*</span></label>
                                <select id="categoria" class="form-select {{ $this->getClaseCampo('categoria') }}" wire:model.live="categoriaSeleccionada">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('categoriaSeleccionada')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una categoría válida</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="subcategoria" class="form-label">Subcategoría <span class="text-red-600">*</span></label>
                                <select id="subcategoria" class="form-select {{ $this->getClaseCampo('subcategoria') }}" wire:model.live="form.subcategoria_id">
                                    <option value="">Seleccionar subcategoría</option>
                                    @foreach($subcategorias as $subcategoria)
                                        <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.subcategoria_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una subcategoría válida</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios y Costos -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💰 Precios y Costos</h2>

                        <!-- Fila 1: Precios Base y Costos -->
                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="precio_base" class="form-label">Precio Base <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio_base" class="form-control {{ $this->getClaseCampo('precio_base') }}" wire:model.live="form.precio_base" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio_base')
                                    <div class="mt-1 text-sm text-danger">❌ El precio base es obligatorio y debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="ultimo_costo_compra" class="form-label">Último Costo Compra</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="ultimo_costo_compra" class="form-control" wire:model.defer="form.ultimo_costo_compra" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.ultimo_costo_compra')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="costo_promedio" class="form-label">Costo Promedio</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="costo_promedio" class="form-control" wire:model.defer="form.costo_promedio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.costo_promedio')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="isv" class="form-label">ISV (Impuesto) %</label>
                                <div class="input-group">
                                    <input type="number"
                                           id="isv"
                                           class="form-control"
                                           wire:model.defer="form.isv"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           placeholder="15">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                                @error('form.isv')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Fila 2: Unidades de Medida -->
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="unidad_compra" class="form-label">
                                    Unidad de Compra <span class="text-red-600">*</span>
                                    <small class="text-muted">(Solo números enteros)</small>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-weight-hanging"></i></span>
                                    <input type="number"
                                           id="unidad_compra"
                                           class="form-control {{ $this->getClaseCampo('unidad_compra') }}"
                                           wire:model.live="form.unidad_compra"
                                           step="1"
                                           min="1"
                                           placeholder="1"
                                           onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                @error('form.unidad_compra')
                                    <div class="mt-1 text-sm text-danger">❌ La unidad de compra debe ser un número entero mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="unidad_medida" class="form-label">Unidad de Medida <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-ruler"></i></span>
                                    <select id="unidad_medida" class="form-select {{ $this->getClaseCampo('unidad_medida') }}" wire:model.live="form.unidad_medida_compra_id">
                                        <option value="">Seleccionar unidad de medida</option>
                                        @foreach($unidadesMedida as $unidad)
                                            <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->simbolo }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('form.unidad_medida_compra_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una unidad de medida válida</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios de Venta -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💵 Precios de Venta</h2>
                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="precio1" class="form-label">Precio 1 <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio1" class="form-control {{ $this->getClaseCampo('precio1') }}" wire:model.live="form.precio1" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio1')
                                    <div class="mt-1 text-sm text-danger">❌ El precio 1 no puede ser 0, debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="precio2" class="form-label">Precio 2</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio2" class="form-control" wire:model.defer="form.precio2" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio2')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="precio3" class="form-label">Precio 3</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio3" class="form-control" wire:model.defer="form.precio3" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio3')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="precio4" class="form-label">Precio 4</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio4" class="form-control" wire:model.defer="form.precio4" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio4')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="volverALista"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }">
                        @if($isEditing)
                            Actualizar Producto
                        @else
                            Crear Producto
                        @endif
                    </button>
                </div>

            </form>
        </div>

    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Campo Obligatorio</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

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

        /* Estilo para labels de campos obligatorios */
        .text-red-600 {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Ocultar elementos antes de que Alpine.js los maneje */
        [x-cloak] {
            display: none !important;
        }

        /* Estilos adicionales para modales */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .modal-body {
            padding: 1rem 1.5rem;
        }

        .modal-footer {
            border-top: none;
            padding: 0.5rem 1.5rem 1.5rem;
        }

        /* Animación para iconos en modales */
        .modal-body i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>

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
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">¡Operación Exitosa!</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">El producto se ha procesado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
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
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Error en la Operación</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente. Si el problema persiste, contacte al administrador.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para manejo de modal -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Solo logging para debug, la redirección se maneja al cerrar el modal
            Livewire.on('redirigirEnTresSeg', () => {
                console.log('Producto guardado exitosamente');
            });
        });
    </script>

</div> {{-- FIN ELEMENTO RAÍZ --}}
