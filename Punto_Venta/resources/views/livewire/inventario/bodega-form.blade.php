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
                    Editar Bodega
                @else
                    Nueva Bodega
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
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏭 Información de la Bodega</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-red-600">*</span></label>
                                <input type="text" id="nombre" class="form-control {{ $this->getClaseCampo('nombre') }}" wire:model.live="form.nombre">
                                @error('form.nombre')
                                    <div class="text-danger mt-1 text-sm">❌ El nombre de la bodega es obligatorio y no puede estar vacío</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tienda_id" class="form-label">Tienda <span class="text-red-600">*</span></label>
                                <select id="tienda_id" class="form-select {{ $this->getClaseCampo('tienda') }}" wire:model.live="form.tienda_id">
                                    <option value="">Seleccionar tienda</option>
                                    @foreach($tiendas as $tienda)
                                        <option value="{{ $tienda->id }}">{{ $tienda->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.tienda_id')
                                    <div class="text-danger mt-1 text-sm">❌ La tienda es obligatoria</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="direccion_id" class="form-label">Dirección ID</label>
                                <input type="number" id="direccion_id" class="form-control" wire:model.defer="form.direccion_id" min="1">
                                @error('form.direccion_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estado_id" class="form-label">Estado</label>
                                <select id="estado_id" class="form-select" wire:model.defer="form.estado_id">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('form.estado_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
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
                            class="px-4 py-2 text-white rounded transition-all duration-200"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                            @if($isEditing)
                                Actualizar Bodega
                            @else
                                Crear Bodega
                            @endif
                        </button>
                    @else
                        <!-- Botón deshabilitado cuando faltan campos -->
                        <button type="button" 
                            disabled
                            class="px-4 py-2 text-white bg-gray-400 rounded cursor-not-allowed opacity-60 transition-all duration-200"
                            title="Complete todos los campos obligatorios para habilitar este botón">
                            @if($isEditing)
                                Actualizar Bodega
                            @else
                                Crear Bodega
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">La bodega se ha procesado correctamente en el sistema.</p>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-2 text-center">
                    <button wire:click="cerrarModalExito" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors duration-200">
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
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente. Si el problema persiste, contacte al administrador.</p>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-2 text-center">
                    <button wire:click="cerrarModalError" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
