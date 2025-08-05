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
                <div class="mb-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Campo requerido:</strong> {{ $mensajeAlerta }}
                    <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
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
                                <label for="categoria" class="form-label">Categoría <span class="text-red-600">*</span></label>
                                <select id="categoria" class="form-select {{ $this->getClaseCampo('categoria') }}" wire:model.live="categoriaSeleccionada">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @if(in_array('categoria', $camposConError))
                                    <div class="mt-1 text-sm text-danger">❌ {{ $erroresValidacion['categoria'] ?? 'Debe seleccionar una categoría' }}</div>
                                @endif
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="subcategoria_id" class="form-label">Subcategoría <span class="text-red-600">*</span></label>
                                <select id="subcategoria_id" class="form-select {{ $this->getClaseCampo('subcategoria') }}" wire:model.live="form.subcategoria_id">
                                    <option value="">Seleccionar subcategoría</option>
                                    @foreach($subcategorias as $subcategoria)
                                        <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.subcategoria_id')
                                    <div class="mt-1 text-sm text-danger">❌ La subcategoría es obligatoria</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="marca_id" class="form-label">Marca <span class="text-red-600">*</span></label>
                                <select id="marca_id" class="form-select {{ $this->getClaseCampo('marca') }}" wire:model.live="form.marca_id">
                                    <option value="">Seleccionar marca</option>
                                    @foreach($marcas as $marca)
                                        <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.marca_id')
                                    <div class="mt-1 text-sm text-danger">❌ La marca es obligatoria</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Compra -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🛒 Información de Compra</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="unidad_compra" class="form-label">Unidad de Compra <span class="text-red-600">*</span></label>
                                <input type="number" id="unidad_compra" class="form-control {{ $this->getClaseCampo('unidad_compra') }}" wire:model.live="form.unidad_compra" min="1" step="1">
                                @error('form.unidad_compra')
                                    <div class="mt-1 text-sm text-danger">❌ La unidad de compra es obligatoria y debe ser un número entero mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="unidad_medida_compra_id" class="form-label">Unidad de Medida <span class="text-red-600">*</span></label>
                                <select id="unidad_medida_compra_id" class="form-select {{ $this->getClaseCampo('unidad_medida') }}" wire:model.live="form.unidad_medida_compra_id">
                                    <option value="">Seleccionar unidad</option>
                                    @foreach($unidadesMedida as $unidad)
                                        <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.unidad_medida_compra_id')
                                    <div class="mt-1 text-sm text-danger">❌ La unidad de medida es obligatoria</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Costos -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💰 Información de Costos</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="precio_base" class="form-label">Precio Base <span class="text-red-600">*</span></label>
                                <input type="number" id="precio_base" class="form-control {{ $this->getClaseCampo('precio_base') }}" wire:model.live="form.precio_base" step="0.01" min="0.01">
                                @error('form.precio_base')
                                    <div class="mt-1 text-sm text-danger">❌ El precio base es obligatorio y debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="isv" class="form-label">ISV (%)</label>
                                <input type="number" id="isv" class="form-control {{ $this->getClaseCampo('isv') }}" wire:model.live="form.isv" step="0.01" min="0" max="100">
                                @error('form.isv')
                                    <div class="mt-1 text-sm text-danger">❌ El ISV debe estar entre 0 y 100</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="ultimo_costo_compra" class="form-label">Último Costo de Compra</label>
                                <input type="number" id="ultimo_costo_compra" class="form-control" wire:model.defer="form.ultimo_costo_compra" step="0.01" min="0">
                                @error('form.ultimo_costo_compra')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="costo_promedio" class="form-label">Costo Promedio</label>
                                <input type="number" id="costo_promedio" class="form-control" wire:model.defer="form.costo_promedio" step="0.01" min="0">
                                @error('form.costo_promedio')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios de Venta -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏪 Precios de Venta</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="precio1" class="form-label">Precio 1 <span class="text-red-600">*</span></label>
                                <input type="number" id="precio1" class="form-control {{ $this->getClaseCampo('precio1') }}" wire:model.live="form.precio1" step="0.01" min="0.01">
                                @error('form.precio1')
                                    <div class="mt-1 text-sm text-danger">❌ El precio 1 es obligatorio y debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="precio2" class="form-label">Precio 2</label>
                                <input type="number" id="precio2" class="form-control" wire:model.defer="form.precio2" step="0.01" min="0">
                                @error('form.precio2')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="precio3" class="form-label">Precio 3</label>
                                <input type="number" id="precio3" class="form-control" wire:model.defer="form.precio3" step="0.01" min="0">
                                @error('form.precio3')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="precio4" class="form-label">Precio 4</label>
                                <input type="number" id="precio4" class="form-control" wire:model.defer="form.precio4" step="0.01" min="0">
                                @error('form.precio4')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="volverALista"
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
                            @if($isEditing)
                                Actualizar Producto
                            @else
                                Crear Producto
                            @endif
                        </button>
                    @else
                        <!-- Botón deshabilitado cuando faltan campos -->
                        <button type="button"
                            disabled
                            class="px-4 py-2 text-white transition-all duration-200 bg-gray-400 rounded cursor-not-allowed opacity-60"
                            title="Complete todos los campos obligatorios para habilitar este botón">
                            @if($isEditing)
                                Actualizar Producto
                            @else
                                Crear Producto
                            @endif
                            <span class="ml-1">🔒</span>
                        </button>
                    @endif
                </div>

            </form>
        </div>
    </div>

    <!-- Modal de Éxito con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalExito') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="$wire.cerrarModalExito()"
         @keydown.escape.window="$wire.cerrarModalExito()">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

            <!-- Modal -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">

                <!-- Header -->
                <div class="flex items-center justify-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">El producto se ha procesado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="inline-flex items-center px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 717 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
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
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="$wire.cerrarModalError()"
         @keydown.escape.window="$wire.cerrarModalError()">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

            <!-- Modal -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">

                <!-- Header -->
                <div class="flex items-center justify-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente. Si el problema persiste, contacte al administrador.</p>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="inline-flex items-center px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para manejo de redirección automática -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('producto-guardado', () => {
                setTimeout(() => {
                    @this.cerrarModalExito();
                }, 3000);
            });
        });
    </script>

</div>
