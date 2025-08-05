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
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="mb-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Error:</strong> {{ $mensajeAlerta }}
                    <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="guardar">
                
                <!-- Información Básica de la Sucursal -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏢 Información Básica</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="denominacion_social" class="form-label">Denominación Social <span class="text-red-600">*</span></label>
                                <input type="text" id="denominacion_social" class="form-control {{ $this->getClaseCampo('form.denominacion_social') }}" wire:model="form.denominacion_social">
                                @error('form.denominacion_social')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="numero_sucursal" class="form-label">Número de Sucursal</label>
                                <input type="text" id="numero_sucursal" class="form-control {{ $this->getClaseCampo('form.numero_sucursal') }}" wire:model="form.numero_sucursal">
                                @error('form.numero_sucursal')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" class="form-control {{ $this->getClaseCampo('form.descripcion') }}" rows="3" wire:model="form.descripcion"></textarea>
                                @error('form.descripcion')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tipo_tienda_id" class="form-label">Tipo de Tienda <span class="text-red-600">*</span></label>
                                <select id="tipo_tienda_id" class="form-control {{ $this->getClaseCampo('form.tipo_tienda_id') }}" wire:model="form.tipo_tienda_id">
                                    <option value="">Seleccionar tipo de tienda</option>
                                    @foreach($tiposTienda as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.tipo_tienda_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="estado_id" class="form-label">Estado <span class="text-red-600">*</span></label>
                                <select id="estado_id" class="form-control {{ $this->getClaseCampo('form.estado_id') }}" wire:model="form.estado_id">
                                    <option value="">Seleccionar estado</option>
                                    @foreach($estados as $estado)
                                        <option value="{{ $estado->id }}">{{ $estado->descripcion }}</option>
                                    @endforeach
                                </select>
                                @error('form.estado_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="identificador_legal" class="form-label">Identificador Legal</label>
                                <input type="text" id="identificador_legal" class="form-control {{ $this->getClaseCampo('form.identificador_legal') }}" wire:model="form.identificador_legal">
                                @error('form.identificador_legal')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
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
                            <div class="col-md-4 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" id="telefono" class="form-control {{ $this->getClaseCampo('form.telefono') }}" wire:model="form.telefono">
                                @error('form.telefono')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="celular" class="form-label">Celular</label>
                                <input type="text" id="celular" class="form-control {{ $this->getClaseCampo('form.celular') }}" wire:model="form.celular">
                                @error('form.celular')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" id="correo" class="form-control {{ $this->getClaseCampo('form.correo') }}" wire:model="form.correo">
                                @error('form.correo')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
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
                            <div class="col-md-6 mb-3">
                                <label for="domicilio_tributario" class="form-label">Domicilio Tributario <span class="text-red-600">*</span></label>
                                <input type="text" id="domicilio_tributario" class="form-control {{ $this->getClaseCampo('direccionForm.domicilio_tributario') }}" wire:model="direccionForm.domicilio_tributario">
                                @error('direccionForm.domicilio_tributario')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tipo_direccion_id" class="form-label">Tipo de Dirección <span class="text-red-600">*</span></label>
                                <select id="tipo_direccion_id" class="form-control {{ $this->getClaseCampo('direccionForm.tipo_direccion_id') }}" wire:model="direccionForm.tipo_direccion_id">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($tiposDireccion as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('direccionForm.tipo_direccion_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="departamento" class="form-label">Departamento <span class="text-red-600">*</span></label>
                                <select id="departamento" class="form-control" wire:model="departamentoSeleccionado">
                                    <option value="">Seleccionar departamento</option>
                                    @foreach($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="municipio_id" class="form-label">Municipio <span class="text-red-600">*</span></label>
                                <select id="municipio_id" class="form-control {{ $this->getClaseCampo('direccionForm.municipio_id') }}" wire:model="direccionForm.municipio_id">
                                    <option value="">Seleccionar municipio</option>
                                    @foreach($municipios as $municipio)
                                        <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('direccionForm.municipio_id')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="colonia" class="form-label">Colonia</label>
                                <input type="text" id="colonia" class="form-control {{ $this->getClaseCampo('direccionForm.colonia') }}" wire:model="direccionForm.colonia">
                                @error('direccionForm.colonia')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="calle_blv" class="form-label">Calle/Boulevard</label>
                                <input type="text" id="calle_blv" class="form-control {{ $this->getClaseCampo('direccionForm.calle_blv') }}" wire:model="direccionForm.calle_blv">
                                @error('direccionForm.calle_blv')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sector_zona" class="form-label">Sector/Zona</label>
                                <input type="text" id="sector_zona" class="form-control {{ $this->getClaseCampo('direccionForm.sector_zona') }}" wire:model="direccionForm.sector_zona">
                                @error('direccionForm.sector_zona')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bloque" class="form-label">Bloque</label>
                                <input type="text" id="bloque" class="form-control {{ $this->getClaseCampo('direccionForm.bloque') }}" wire:model="direccionForm.bloque">
                                @error('direccionForm.bloque')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="latitud" class="form-label">Latitud</label>
                                <input type="text" id="latitud" class="form-control {{ $this->getClaseCampo('direccionForm.latitud') }}" wire:model="direccionForm.latitud">
                                @error('direccionForm.latitud')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="longitud" class="form-label">Longitud</label>
                                <input type="text" id="longitud" class="form-control {{ $this->getClaseCampo('direccionForm.longitud') }}" wire:model="direccionForm.longitud">
                                @error('direccionForm.longitud')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-end gap-2 p-4">
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
    </style>

</div> {{-- FIN ELEMENTO RAÍZ --}}
