<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Cambio de Sucursal --}}
    <div class="overflow-visible border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))" style="position: relative; z-index: 1;">

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
                <i class="fas fa-exchange-alt me-2"></i>Cambio de Sucursal
            </h5>
            <button wire:click="limpiarFormulario"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-broom"></i> Limpiar
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4" style="overflow: visible; position: relative; z-index: 2;">
            
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="mb-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Campo requerido:</strong> {{ $mensajeAlerta }}
                    <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
                </div>
            @endif
            
            <!-- Búsqueda de Usuario -->
            <div class="mb-4" style="position: relative; z-index: 10;">
                <div class="p-4 bg-white border shadow rounded-xl" style="overflow: visible;">
                    <h3 class="mb-3 text-lg font-semibold text-gray-700">
                        <i class="fas fa-search me-2"></i>Buscar Usuario
                    </h3>
                    
                    <div class="position-relative" style="z-index: 20;">
                        <label for="buscarUsuario" class="form-label">Usuario <span class="text-red-600">*</span></label>
                        <input type="text" 
                               id="buscarUsuario"
                               class="form-control {{ $this->getClaseCampo('buscarUsuario') }}" 
                               wire:model.live="buscarUsuario"
                               wire:focus="enfocarUsuario"
                               wire:click="enfocarUsuario"
                               placeholder="Escriba el nombre o email del usuario..."
                               autocomplete="off">
                        
                        @if(in_array('buscarUsuario', $camposConError))
                            <div class="text-danger mt-1 text-sm">❌ Debe seleccionar un usuario válido</div>
                        @endif
                        
                        <!-- Lista desplegable de usuarios -->
                        @if($mostrarSugerencias && count($usuariosSugeridos) > 0)
                            <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                @foreach($usuariosSugeridos as $usuario)
                                    <div wire:click="seleccionarUsuario({{ $usuario->id }})" 
                                         class="dropdown-item-custom px-3 py-2 cursor-pointer border-bottom">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $usuario->name }}</strong><br>
                                                <small class="text-muted">{{ $usuario->email }}</small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-primary">
                                                    {{ $usuario->tienda ? $usuario->tienda->denominacion_social : 'Sin sucursal' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($mostrarSugerencias && count($usuariosSugeridos) == 0 && strlen($buscarUsuario) >= 1)
                            <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                <div class="px-3 py-2 text-muted text-center">
                                    No se encontraron usuarios que coincidan con "{{ $buscarUsuario }}"
                                </div>
                            </div>
                        @endif

                        @if($mostrarSugerencias && count($usuariosSugeridos) == 0 && strlen($buscarUsuario) == 0)
                            <div class="dropdown-suggestions position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg">
                                <div class="px-3 py-2 text-muted text-center">
                                    Haga clic para ver todos los usuarios disponibles
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información del Usuario Seleccionado -->
            @if($usuarioSeleccionado)
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-user me-2"></i>Información del Usuario
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre Completo</label>
                                    <div class="p-2 bg-light rounded">{{ $nombreUsuario }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <div class="p-2 bg-light rounded">{{ $emailUsuario }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sucursal Actual</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-store me-2 text-primary"></i>{{ $sucursalActual }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Rol Asignado</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-user-tag me-2 text-success"></i>{{ $rolUsuario }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selección de Nueva Sucursal -->
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-map-marker-alt me-2"></i>Nueva Sucursal
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <label for="nuevaSucursal" class="form-label">Seleccionar Nueva Sucursal <span class="text-red-600">*</span></label>
                                <select wire:model="nuevaSucursalId" wire:change="$refresh" id="nuevaSucursal" class="form-select {{ $this->getClaseCampo('nuevaSucursalId') }}">
                                    <option value="">Seleccione una sucursal...</option>
                                    @foreach($tiendas as $tienda)
                                        <option value="{{ $tienda->id }}" {{ $tienda->id == $sucursalActualId ? 'disabled' : '' }}>
                                            {{ $tienda->denominacion_social }}
                                            {{ $tienda->id == $sucursalActualId ? '(Sucursal actual)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(in_array('nuevaSucursalId', $camposConError))
                                    <div class="text-danger mt-1 text-sm">❌ Debe seleccionar una nueva sucursal válida</div>
                                @endif
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button wire:click="confirmarCambio" 
                                        class="btn w-100"
                                        :class="{
                                            'btn-success': theme === 'verde' && {{ $this->botonHabilitado ? 'true' : 'false' }},
                                            'btn-primary': theme === 'azul' && {{ $this->botonHabilitado ? 'true' : 'false' }},
                                            'btn-dark': theme === 'oscuro' && {{ $this->botonHabilitado ? 'true' : 'false' }},
                                            'btn-secondary': !{{ $this->botonHabilitado ? 'true' : 'false' }} || (theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && {{ $this->botonHabilitado ? 'true' : 'false' }})
                                        }"
                                        {{ !$this->botonHabilitado ? 'disabled' : '' }}>
                                    <i class="fas fa-exchange-alt me-2"></i>Cambiar Sucursal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Modal de Confirmación -->
    @if($mostrarModalConfirmacion)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Cambio de Sucursal
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de que desea cambiar la sucursal del usuario?</p>
                        <div class="alert alert-info">
                            <strong>Usuario:</strong> {{ $nombreUsuario }}<br>
                            <strong>De:</strong> {{ $sucursalActual }}<br>
                            <strong>A:</strong> {{ collect($tiendas)->firstWhere('id', $nuevaSucursalId)->denominacion_social ?? 'N/A' }}
                        </div>
                        <p class="text-muted small">Esta acción se registrará en el sistema y no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancelarCambio" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" wire:click="ejecutarCambio" class="btn btn-warning">
                            <i class="fas fa-check me-2"></i>Confirmar Cambio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>¡Éxito!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalExito" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Error -->
    @if($mostrarModalError)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalError" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estilos CSS adicionales -->
    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .modal.show {
            display: block !important;
        }
        
        /* Estilos para el dropdown de usuarios */
        .dropdown-suggestions {
            z-index: 9999 !important;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #dee2e6 !important;
        }
        
        .dropdown-item-custom {
            transition: background-color 0.2s ease;
        }
        
        .dropdown-item-custom:hover {
            background-color: #f8f9fa !important;
            cursor: pointer;
        }
        
        .dropdown-item-custom:last-child {
            border-bottom: none !important;
        }
        
        /* Asegurar que el contenedor padre no corte el dropdown */
        .position-relative {
            overflow: visible !important;
        }
        
        /* Mejorar la visualización en diferentes tamaños de pantalla */
        @media (max-width: 768px) {
            .dropdown-suggestions {
                max-height: 250px;
            }
        }
        
        /* Estilos para campos obligatorios con error */
        .campo-obligatorio-vacio {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            background-color: #fdf2f2 !important;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
        }
        
        .text-danger {
            color: #dc3545 !important;
        }
    </style>

</div>
