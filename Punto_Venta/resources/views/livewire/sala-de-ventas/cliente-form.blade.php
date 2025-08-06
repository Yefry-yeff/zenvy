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
                <i class="fas fa-user me-2"></i>
                @if($isEditing)
                    Editar Cliente
                @else
                    Nuevo Cliente
                @endif
            </h5>
            <button wire:click="cancelar"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>←</span> Volver
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación flotante -->
           @if($mostrarAlerta)
                <div class="mb-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Campo requerido:</strong> {{ $mensajeAlerta }}
                    <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="guardar">

                <!-- Información Básica del Cliente -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">👤 Información Básica del Cliente</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="nombre" class="form-label">Nombre Completo <span class="text-red-600">*</span></label>
                                <input type="text" id="nombre" class="form-control {{ $this->getClaseCampo('form.nombre') }}" wire:model.blur="form.nombre">
                                @error('form.nombre')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" id="correo" class="form-control {{ $this->getClaseCampo('form.correo') }}" wire:model.blur="form.correo">
                                @error('form.correo')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="identidad" class="form-label">Número de Identidad</label>
                                <input type="text" id="identidad" class="form-control {{ $this->getClaseCampo('form.identidad') }}" wire:model.blur="form.identidad" placeholder="Ej: 0801-1990-12345">
                                @error('form.identidad')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="rtn" class="form-label">RTN</label>
                                <input type="text" id="rtn" class="form-control {{ $this->getClaseCampo('form.rtn') }}" wire:model.blur="form.rtn" placeholder="Ej: 08011990123456">
                                @error('form.rtn')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="tipo_persona_id" class="form-label">Tipo de Persona <span class="text-red-600">*</span></label>
                                <select id="tipo_persona_id" class="form-control {{ $this->getClaseCampo('form.tipo_persona_id') }}" wire:model="form.tipo_persona_id">
                                    <option value="">Seleccionar tipo de persona</option>
                                    @foreach($tiposPersona as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.tipo_persona_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="tipo_cliente_id" class="form-label">Tipo de Cliente <span class="text-red-600">*</span></label>
                                <select id="tipo_cliente_id" class="form-control {{ $this->getClaseCampo('form.tipo_cliente_id') }}" wire:model="form.tipo_cliente_id">
                                    <option value="">Seleccionar tipo de cliente</option>
                                    @foreach($tiposCliente as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.tipo_cliente_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Dirección (Igual que en sucursales) -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📍 Dirección del Cliente</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="domicilio_tributario" class="form-label">Domicilio Tributario <span class="text-red-600">*</span></label>
                                <input type="text" id="domicilio_tributario" class="form-control {{ $this->getClaseCampo('direccionForm.domicilio_tributario') }}" wire:model="direccionForm.domicilio_tributario" placeholder="Dirección completa del cliente">
                                @error('direccionForm.domicilio_tributario')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="tipo_direccion_id" class="form-label">Tipo de Dirección <span class="text-red-600">*</span></label>

                                @if($tipoDireccionCliente)
                                    {{-- Campo bloqueado siempre como "Cliente" --}}
                                    <div class="input-group">
                                        <input type="text"
                                               class="form-control bg-light"
                                               value="{{ $tipoDireccionCliente->nombre }}"
                                               readonly
                                               style="background-color: #f8f9fa !important; cursor: not-allowed;">
                                        <span class="input-group-text bg-light border-start-0" style="background-color: #f8f9fa !important;">
                                            <i class="fas fa-user text-muted" title="Tipo de dirección para cliente"></i>
                                        </span>
                                    </div>
                                    {{-- Campo oculto para mantener el valor --}}
                                    <input type="hidden" wire:model="direccionForm.tipo_direccion_id">
                                @else
                                    {{-- Campo normal como fallback --}}
                                    <select id="tipo_direccion_id" class="form-control {{ $this->getClaseCampo('direccionForm.tipo_direccion_id') }}" wire:model="direccionForm.tipo_direccion_id">
                                        <option value="">Seleccionar tipo</option>
                                        @foreach($tiposDireccion as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('direccionForm.tipo_direccion_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="departamento" class="form-label">Departamento <span class="text-red-600">*</span></label>
                                <select id="departamento" 
                                        class="form-control" 
                                        wire:model.lazy="departamentoSeleccionado"
                                        wire:loading.attr="disabled">
                                    <option value="">Seleccionar departamento</option>
                                    @foreach($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
                                    @endforeach
                                </select>
                                <div wire:loading wire:target="departamentoSeleccionado" class="mt-1">
                                    <small class="text-info">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando municipios...
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="municipio_id" class="form-label">Municipio <span class="text-red-600">*</span></label>
                                <select id="municipio_id" 
                                        class="form-control {{ $this->getClaseCampo('direccionForm.municipio_id') }}" 
                                        wire:model="direccionForm.municipio_id"
                                        @if(!$departamentoSeleccionado) disabled @endif>
                                    <option value="">
                                        @if(!$departamentoSeleccionado)
                                            Primero seleccione un departamento
                                        @else
                                            Seleccionar municipio
                                        @endif
                                    </option>
                                    @foreach($municipios as $municipio)
                                        <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                                    @endforeach
                                </select>
                                @if(!$departamentoSeleccionado)
                                    <small class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Debe seleccionar un departamento primero
                                    </small>
                                @endif
                                @error('direccionForm.municipio_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="colonia" class="form-label">Colonia</label>
                                <input type="text" id="colonia" class="form-control {{ $this->getClaseCampo('direccionForm.colonia') }}" wire:model="direccionForm.colonia">
                                @error('direccionForm.colonia')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="calle_blv" class="form-label">Calle/Boulevard</label>
                                <input type="text" id="calle_blv" class="form-control {{ $this->getClaseCampo('direccionForm.calle_blv') }}" wire:model="direccionForm.calle_blv">
                                @error('direccionForm.calle_blv')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="sector_zona" class="form-label">Sector/Zona</label>
                                <input type="text" id="sector_zona" class="form-control {{ $this->getClaseCampo('direccionForm.sector_zona') }}" wire:model="direccionForm.sector_zona">
                                @error('direccionForm.sector_zona')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="bloque" class="form-label">Bloque</label>
                                <input type="text" id="bloque" class="form-control {{ $this->getClaseCampo('direccionForm.bloque') }}" wire:model="direccionForm.bloque">
                                @error('direccionForm.bloque')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="cancelar"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        Cancelar
                    </button>
                    
                    <button type="submit"
                        class="px-4 py-2 text-white rounded transition-all duration-200"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }">
                        @if($isEditing)
                            Actualizar Cliente
                        @else
                            Crear Cliente
                        @endif
                    </button>
                </div>

            </form>
        </div>
    </div>

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
                        <h3 class="text-lg font-semibold">¡Cliente Guardado!</h3>
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
                    <p class="text-gray-600">El cliente se ha procesado correctamente en el sistema.</p>
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
                        <h3 class="text-lg font-semibold">Error en la Operación</h3>
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
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente. Si el problema persiste, contacte al administrador.</p>
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
        .is-invalid {
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
    </style>

</div>
