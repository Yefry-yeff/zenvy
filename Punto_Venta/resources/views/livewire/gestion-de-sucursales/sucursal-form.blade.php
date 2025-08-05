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
                    Editar Sucursal
                @else
                    Nueva Sucursal
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

                <!-- Información Básica de la Sucursal -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏢 Información Básica</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="denominacion_social" class="form-label">Denominación Social <span class="text-red-600">*</span></label>
                                <input type="text" id="denominacion_social" class="form-control {{ $this->getClaseCampo('form.denominacion_social') }}" wire:model="form.denominacion_social">
                                @error('form.denominacion_social')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="numero_sucursal" class="form-label">Número de Sucursal</label>
                                <input type="text" id="numero_sucursal" class="form-control {{ $this->getClaseCampo('form.numero_sucursal') }}" wire:model="form.numero_sucursal">
                                @error('form.numero_sucursal')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" class="form-control {{ $this->getClaseCampo('form.descripcion') }}" rows="3" wire:model="form.descripcion"></textarea>
                                @error('form.descripcion')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="tipo_tienda_id" class="form-label">Tipo de Tienda <span class="text-red-600">*</span></label>

                                @if($existeSucursalPrincipal && $tipoTiendaSucursal)
                                    {{-- Campo bloqueado cuando ya existe sucursal principal --}}
                                    <div class="input-group">
                                        <input type="text"
                                               class="form-control bg-light"
                                               value="{{ $tipoTiendaSucursal->nombre }}"
                                               readonly
                                               style="background-color: #f8f9fa !important; cursor: not-allowed;">
                                        <span class="input-group-text bg-light border-start-0" style="background-color: #f8f9fa !important;">
                                            <i class="fas fa-lock text-muted" title="Ya existe una sucursal principal"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Ya existe una sucursal principal. Las nuevas sucursales deben ser de tipo "{{ $tipoTiendaSucursal->nombre }}".
                                    </small>
                                    {{-- Campo oculto para mantener el valor --}}
                                    <input type="hidden" wire:model="form.tipo_tienda_id">
                                @else
                                    {{-- Campo normal cuando no existe sucursal principal --}}
                                    <select id="tipo_tienda_id" class="form-control {{ $this->getClaseCampo('form.tipo_tienda_id') }}" wire:model="form.tipo_tienda_id">
                                        <option value="">Seleccionar tipo de tienda</option>
                                        @foreach($tiposTienda as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('form.tipo_tienda_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="estado_id" class="form-label">Estado <span class="text-red-600">*</span></label>
                                <select id="estado_id" class="form-control {{ $this->getClaseCampo('form.estado_id') }}" wire:model="form.estado_id">
                                    <option value="">Seleccionar estado</option>
                                    @foreach($estados as $estado)
                                        <option value="{{ $estado->id }}">{{ $estado->descripcion }}</option>
                                    @endforeach
                                </select>
                                @error('form.estado_id')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="identificador_legal" class="form-label">Identificador Legal</label>
                                <input type="text" id="identificador_legal" class="form-control {{ $this->getClaseCampo('form.identificador_legal') }}" wire:model="form.identificador_legal">
                                @error('form.identificador_legal')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📞 Información de Contacto</h2>
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text"
                                       id="telefono"
                                       class="form-control {{ $this->getClaseCampo('form.telefono') }}"
                                       wire:model="form.telefono"
                                       placeholder="2234-5678"
                                       maxlength="9"
                                       x-data
                                       @input="
                                           let value = $event.target.value.replace(/\D/g, '');
                                           if (value.length > 4) {
                                               value = value.substring(0, 4) + '-' + value.substring(4, 8);
                                           }
                                           $event.target.value = value;
                                           $wire.set('form.telefono', value);
                                       "
                                       @keypress="
                                           if (!/[\d]/.test($event.key) &&
                                               !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes($event.key)) {
                                               $event.preventDefault();
                                           }
                                       ">
                                @error('form.telefono')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="celular" class="form-label">Celular</label>
                                <input type="text"
                                       id="celular"
                                       class="form-control {{ $this->getClaseCampo('form.celular') }}"
                                       wire:model="form.celular"
                                       placeholder="9876-5432"
                                       maxlength="9"
                                       x-data
                                       @input="
                                           let value = $event.target.value.replace(/\D/g, '');
                                           if (value.length > 4) {
                                               value = value.substring(0, 4) + '-' + value.substring(4, 8);
                                           }
                                           $event.target.value = value;
                                           $wire.set('form.celular', value);
                                       "
                                       @keypress="
                                           if (!/[\d]/.test($event.key) &&
                                               !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes($event.key)) {
                                               $event.preventDefault();
                                           }
                                       ">
                                @error('form.celular')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" id="correo" class="form-control {{ $this->getClaseCampo('form.correo') }}" wire:model="form.correo">
                                @error('form.correo')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Dirección -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📍 Dirección</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="domicilio_tributario" class="form-label">Domicilio Tributario <span class="text-red-600">*</span></label>
                                <input type="text" id="domicilio_tributario" class="form-control {{ $this->getClaseCampo('direccionForm.domicilio_tributario') }}" wire:model="direccionForm.domicilio_tributario">
                                @error('direccionForm.domicilio_tributario')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="tipo_direccion_id" class="form-label">Tipo de Dirección <span class="text-red-600">*</span></label>

                                @if($tipoDireccionTienda)
                                    {{-- Campo bloqueado siempre como "Tienda" --}}
                                    <div class="input-group">
                                        <input type="text"
                                               class="form-control bg-light"
                                               value="{{ $tipoDireccionTienda->nombre }}"
                                               readonly
                                               style="background-color: #f8f9fa !important; cursor: not-allowed;">
                                        <span class="input-group-text bg-light border-start-0" style="background-color: #f8f9fa !important;">
                                            <i class="fas fa-store text-muted" title="Tipo de dirección para tienda"></i>
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

                            <div class="mb-3 col-md-6">
                                <label for="latitud" class="form-label">Latitud</label>
                                <input type="text" id="latitud" class="form-control {{ $this->getClaseCampo('direccionForm.latitud') }}" wire:model="direccionForm.latitud">
                                @error('direccionForm.latitud')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="longitud" class="form-label">Longitud</label>
                                <input type="text" id="longitud" class="form-control {{ $this->getClaseCampo('direccionForm.longitud') }}" wire:model="direccionForm.longitud">
                                @error('direccionForm.longitud')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="gap-2 p-4 d-flex justify-content-end">
                    <button type="button" wire:click="cancelar" class="btn btn-secondary">
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
                            Actualizar Sucursal
                        @else
                            Crear Sucursal
                        @endif
                    </button>
                </div>

            </form>
        </div>
    </div>

    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-success">
            {{ session('mensaje') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

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

        /* Estilos para campo bloqueado */
        .form-control[readonly] {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
        }

        .input-group-text.bg-light {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
            color: #6c757d !important;
        }

        /* Estilos para texto de ayuda */
        .form-text.text-muted {
            font-size: 0.8rem;
            color: #6c757d !important;
            margin-top: 0.25rem;
        }

        /* Estilos para placeholders */
        .form-control::placeholder {
            color: #adb5bd;
            font-style: italic;
        }

        /* Estilos adicionales para campos de teléfono */
        .form-control[maxlength="9"] {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }

        /* Animación suave para cambios en los inputs */
        .form-control {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        /* Estilos para campos deshabilitados */
        .form-control:disabled {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
        }

        /* Estilos para texto de advertencia */
        .text-warning {
            color: #ffc107 !important;
        }

        /* Estilos para texto de información */
        .text-info {
            color: #17a2b8 !important;
        }

        /* Animación para el spinner */
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }

        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                    <p class="text-gray-600">La sucursal se ha procesado correctamente en el sistema.</p>
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
